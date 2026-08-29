<?php
namespace Tests\Api;

use PHPUnit\Framework\TestCase;
use Tests\Support\Fixtures;
use Tests\Support\Http;

/**
 * Covers the MySQL/PHP-authoritative side of Chat.php - classmate scoping,
 * block/report/voice-note ownership, and oversight authorization. Message
 * content, typing status, and Security Rules live in Firestore and are
 * explicitly out of reach of PHPUnit here (no live Firebase project is
 * configured in this dev/test environment) - those are verified manually
 * per the plan. What CAN and must be covered here is the same property
 * every class-scoped endpoint in this app needs: a student outside the
 * conversation/class must never be able to read or act on it.
 */
final class ChatTest extends TestCase
{
    private Fixtures $fixtures;
    private int $branchId;
    private int $yearId;
    private int $classId;
    private int $sectionId;
    /** @var array<int,string> */
    private array $tempFiles = array();

    protected function setUp(): void
    {
        $this->fixtures = new Fixtures();
        $this->branchId = $this->fixtures->createBranch();
        $this->yearId = $this->fixtures->createSchoolYear();
        $this->classId = $this->fixtures->createClass($this->branchId);
        $this->sectionId = $this->fixtures->createSection($this->branchId);
    }

    protected function tearDown(): void
    {
        $db = Fixtures::db();
        $notes = $db->query('SELECT stored_file FROM schooledge_chat_voice_notes WHERE branch_id = ' . $this->branchId);
        while ($row = $notes->fetch_assoc()) {
            $path = dirname(__DIR__, 2) . '/uploads/attachments/chat_voice/' . $row['stored_file'];
            if (file_exists($path)) @unlink($path);
        }
        $db->query('DELETE FROM schooledge_chat_voice_notes WHERE branch_id = ' . $this->branchId);
        $db->query('DELETE FROM schooledge_chat_reports WHERE branch_id = ' . $this->branchId);
        $db->query('DELETE FROM schooledge_chat_blocks WHERE branch_id = ' . $this->branchId);
        foreach ($this->tempFiles as $path) {
            if (file_exists($path)) @unlink($path);
        }
        $this->fixtures->cleanupMobileAuthForBranch($this->branchId);
        $this->fixtures->cleanup();
    }

    /**
     * A minimal but genuinely valid WAV file (not just a renamed text file) -
     * CI3's upload library sniffs actual content type via fileinfo, not just
     * the extension, so the marker text has to live inside a real "data"
     * chunk to pass as audio/x-wav and still be recoverable byte-for-byte
     * when the endpoint streams it back.
     */
    private function tempAudioFile(string $marker): string
    {
        $dataSize = strlen($marker);
        $header = 'RIFF' . pack('V', 36 + $dataSize) . 'WAVE'
            . 'fmt ' . pack('V', 16) . pack('v', 1) . pack('v', 1) . pack('V', 8000) . pack('V', 8000) . pack('v', 1) . pack('v', 8)
            . 'data' . pack('V', $dataSize);
        $path = tempnam(sys_get_temp_dir(), 'chat_voice_') . '.wav';
        file_put_contents($path, $header . $marker);
        $this->tempFiles[] = $path;
        return $path;
    }

    public function test_token_endpoint_requires_a_student_role(): void
    {
        $teacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $session = $this->fixtures->login($teacher['username'], $teacher['password']);

        $response = Http::post('chat/token', array(), $session['access_token']);
        $this->assertSame(403, $response['status']);
        $this->assertSame('role_not_supported', $response['body']['error']['code'] ?? null);
    }

    /**
     * Minting a token is the gateway to every subsequent Firestore write
     * (messages, typing status) - none of which blockIfDemoReadonly()'s
     * MySQL check can reach once a token is issued, so a demo branch must be
     * refused at the token step itself, not just on the MySQL-backed
     * block/unblock/report/voice-note endpoints.
     */
    public function test_a_demo_branch_student_cannot_mint_a_chat_token(): void
    {
        $demoBranchId = $this->fixtures->createBranch(array('is_demo' => 1));
        $classId = $this->fixtures->createClass($demoBranchId);
        $sectionId = $this->fixtures->createSection($demoBranchId);
        $student = $this->fixtures->createStudent($demoBranchId, $classId, $sectionId, $this->yearId, array('with_login' => true));
        $session = $this->fixtures->login($student['username'], $student['password']);

        $response = Http::post('chat/token', array(), $session['access_token']);
        $this->assertSame(403, $response['status']);
        $this->assertSame('demo_readonly', $response['body']['error']['code'] ?? null);

        $this->fixtures->cleanupMobileAuthForBranch($demoBranchId);
    }

    public function test_token_endpoint_mints_a_correctly_signed_custom_token(): void
    {
        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $session = $this->fixtures->login($student['username'], $student['password']);

        $response = Http::post('chat/token', array(), $session['access_token']);
        $this->assertSame(200, $response['status'], json_encode($response['body']));
        $jwt = $response['body']['data']['firebase_token'];
        $this->assertSame($this->branchId . '_' . $this->classId . '_' . $this->sectionId, $response['body']['data']['classroom_key']);

        // Verifying the signature actually satisfies Firebase requires a live
        // client-side signInWithCustomToken() call (a manual verification
        // step, per the plan) - what PHPUnit CAN and should check is that the
        // JWT this endpoint hands back has exactly the shape Firebase's
        // custom-token spec requires, with the right claims embedded.
        list($headerB64, $payloadB64) = explode('.', $jwt);
        $header = json_decode(base64_decode(strtr($headerB64, '-_', '+/')), true);
        $payload = json_decode(base64_decode(strtr($payloadB64, '-_', '+/')), true);
        $this->assertSame('RS256', $header['alg']);
        $this->assertSame('https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit', $payload['aud']);
        $this->assertSame($this->branchId . '-7-' . $student['student_id'], $payload['uid']);
        $this->assertSame($this->branchId . '_' . $this->classId . '_' . $this->sectionId, $payload['claims']['classroomKey']);
        $this->assertSame((string)$session['membership']['id'], $payload['claims']['membershipId']);
        $this->assertLessThanOrEqual(3600, $payload['exp'] - $payload['iat']);
    }

    public function test_classmates_excludes_other_classes_and_students_without_a_mobile_membership(): void
    {
        $studentA = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $studentB = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        // Same class, but never logged into the mobile app - no mobile_membership to chat as.
        $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));

        $otherClassId = $this->fixtures->createClass($this->branchId);
        $otherSectionId = $this->fixtures->createSection($this->branchId);
        $outsideStudent = $this->fixtures->createStudent($this->branchId, $otherClassId, $otherSectionId, $this->yearId, array('with_login' => true));

        $sessionA = $this->fixtures->login($studentA['username'], $studentA['password']);
        $sessionB = $this->fixtures->login($studentB['username'], $studentB['password']);
        $this->fixtures->login($outsideStudent['username'], $outsideStudent['password']);

        $response = Http::get('chat/classmates', $sessionA['access_token']);
        $this->assertSame(200, $response['status'], json_encode($response['body']));
        $membershipIds = array_column($response['body']['data'], 'membership_id');
        $this->assertContains($sessionB['membership']['id'], $membershipIds);
        $this->assertNotContains($sessionA['membership']['id'], $membershipIds, 'a student must never appear in their own classmate list');
        $this->assertCount(1, $membershipIds, 'the never-logged-in classmate and the outside-class student must both be excluded');
    }

    public function test_voice_note_round_trip_and_participant_gating(): void
    {
        $studentA = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $studentB = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $outsideStudent = $this->fixtures->createStudent($this->branchId, $this->fixtures->createClass($this->branchId), $this->fixtures->createSection($this->branchId), $this->yearId, array('with_login' => true));

        $sessionA = $this->fixtures->login($studentA['username'], $studentA['password']);
        $sessionB = $this->fixtures->login($studentB['username'], $studentB['password']);
        $sessionOutside = $this->fixtures->login($outsideStudent['username'], $outsideStudent['password']);

        $ids = array($sessionA['membership']['id'], $sessionB['membership']['id']);
        sort($ids);
        $conversationId = $ids[0] . '_' . $ids[1];

        $audioPath = $this->tempAudioFile('ID3 test voice note bytes');
        $upload = Http::postMultipart('chat/voice-notes', array('conversation_id' => $conversationId, 'duration_ms' => '4200'), array('file' => $audioPath), $sessionA['access_token']);
        $this->assertSame(200, $upload['status'], json_encode($upload['body']));
        $noteId = $upload['body']['data']['id'];

        $forB = Http::get("chat/voice-notes/$noteId", $sessionB['access_token']);
        $this->assertSame(200, $forB['status']);
        $this->assertStringContainsString('test voice note bytes', $forB['body']['raw'] ?? '');

        $forOutsider = Http::get("chat/voice-notes/$noteId", $sessionOutside['access_token']);
        $this->assertSame(404, $forOutsider['status'], 'a student outside the conversation must never be able to fetch the voice note');
    }

    public function test_a_student_cannot_submit_a_voice_note_to_a_conversation_they_are_not_part_of(): void
    {
        $studentA = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $studentB = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $outsideStudent = $this->fixtures->createStudent($this->branchId, $this->fixtures->createClass($this->branchId), $this->fixtures->createSection($this->branchId), $this->yearId, array('with_login' => true));

        $sessionA = $this->fixtures->login($studentA['username'], $studentA['password']);
        $sessionB = $this->fixtures->login($studentB['username'], $studentB['password']);
        $sessionOutside = $this->fixtures->login($outsideStudent['username'], $outsideStudent['password']);

        $ids = array($sessionA['membership']['id'], $sessionB['membership']['id']);
        sort($ids);
        $conversationId = $ids[0] . '_' . $ids[1];

        $audioPath = $this->tempAudioFile('bytes');
        $upload = Http::postMultipart('chat/voice-notes', array('conversation_id' => $conversationId), array('file' => $audioPath), $sessionOutside['access_token']);
        $this->assertSame(404, $upload['status']);
        $this->assertSame('conversation_not_found', $upload['body']['error']['code'] ?? null);
    }

    public function test_block_and_unblock_round_trip(): void
    {
        $studentA = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $studentB = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $sessionA = $this->fixtures->login($studentA['username'], $studentA['password']);
        $sessionB = $this->fixtures->login($studentB['username'], $studentB['password']);

        $block = Http::postForm('chat/block', array('membership_id' => $sessionB['membership']['id']), $sessionA['access_token']);
        $this->assertSame(200, $block['status'], json_encode($block['body']));
        $this->assertTrue($block['body']['data']['blocked']);

        $row = Fixtures::db()->query('SELECT * FROM schooledge_chat_blocks WHERE blocker_membership_id = ' . (int)$sessionA['membership']['id'] . ' AND blocked_membership_id = ' . (int)$sessionB['membership']['id'])->fetch_assoc();
        $this->assertNotNull($row);

        // The blocked student cannot lift a block someone else placed on them.
        $wrongUnblock = Http::postForm('chat/unblock', array('membership_id' => $sessionA['membership']['id']), $sessionB['access_token']);
        $this->assertSame(200, $wrongUnblock['status']);
        $stillThere = Fixtures::db()->query('SELECT * FROM schooledge_chat_blocks WHERE blocker_membership_id = ' . (int)$sessionA['membership']['id'] . ' AND blocked_membership_id = ' . (int)$sessionB['membership']['id'])->fetch_assoc();
        $this->assertNotNull($stillThere, 'only the original blocker can lift their own block');

        $unblock = Http::postForm('chat/unblock', array('membership_id' => $sessionB['membership']['id']), $sessionA['access_token']);
        $this->assertSame(200, $unblock['status']);
        $gone = Fixtures::db()->query('SELECT * FROM schooledge_chat_blocks WHERE blocker_membership_id = ' . (int)$sessionA['membership']['id'] . ' AND blocked_membership_id = ' . (int)$sessionB['membership']['id'])->fetch_assoc();
        $this->assertNull($gone);
    }

    public function test_report_creates_a_row_and_notifies_the_class_teacher_and_admin(): void
    {
        $studentA = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $studentB = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $teacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $this->fixtures->createTeacherAllocation($this->branchId, $teacher['staff_id'], $this->classId, $this->sectionId, $this->yearId);
        $admin = $this->fixtures->createStaff($this->branchId, 'TestPass123', 2);

        $sessionA = $this->fixtures->login($studentA['username'], $studentA['password']);
        $sessionB = $this->fixtures->login($studentB['username'], $studentB['password']);
        // The teacher/admin need an existing active membership row for
        // notifyIdentity()/the admin lookup to resolve into - logging in once
        // is what creates that row, same as it would on their real device.
        $teacherSession = $this->fixtures->login($teacher['username'], $teacher['password']);
        $adminSession = $this->fixtures->login($admin['username'], $admin['password']);

        $ids = array($sessionA['membership']['id'], $sessionB['membership']['id']);
        sort($ids);
        $conversationId = $ids[0] . '_' . $ids[1];

        $report = Http::postForm('chat/reports', array(
            'conversation_id' => $conversationId, 'reported_membership_id' => $sessionB['membership']['id'],
            'message_excerpt' => 'something unkind',
        ), $sessionA['access_token']);
        $this->assertSame(200, $report['status'], json_encode($report['body']));

        $row = Fixtures::db()->query('SELECT * FROM schooledge_chat_reports WHERE id = ' . (int)$report['body']['data']['id'])->fetch_assoc();
        $this->assertSame((string)$sessionA['membership']['id'], $row['reporter_membership_id']);
        $this->assertSame((string)$sessionB['membership']['id'], $row['reported_membership_id']);

        $teacherNotified = Fixtures::db()->query("SELECT * FROM mobile_notification_inbox WHERE membership_id = " . (int)$teacherSession['membership']['id'] . " AND category = 'chat_report'")->fetch_assoc();
        $this->assertNotNull($teacherNotified, 'the reported student\'s class teacher must be notified');

        $adminNotified = Fixtures::db()->query("SELECT * FROM mobile_notification_inbox WHERE membership_id = " . (int)$adminSession['membership']['id'] . " AND category = 'chat_report'")->fetch_assoc();
        $this->assertNotNull($adminNotified, 'a branch admin with chat_oversight view access must be notified');
    }

    public function test_oversight_requires_the_caller_to_be_an_authorized_teacher_or_admin(): void
    {
        $classroomKey = $this->branchId . '_' . $this->classId . '_' . $this->sectionId;

        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $studentSession = $this->fixtures->login($student['username'], $student['password']);
        $asStudent = Http::get("chat/oversight/$classroomKey", $studentSession['access_token']);
        $this->assertSame(403, $asStudent['status']);
        $this->assertSame('role_not_supported', $asStudent['body']['error']['code'] ?? null);

        $unassignedTeacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $unassignedSession = $this->fixtures->login($unassignedTeacher['username'], $unassignedTeacher['password']);
        $asUnassignedTeacher = Http::get("chat/oversight/$classroomKey", $unassignedSession['access_token']);
        $this->assertSame(403, $asUnassignedTeacher['status']);
        $this->assertSame('class_not_assigned', $asUnassignedTeacher['body']['error']['code'] ?? null);

        // An assigned teacher clears authorization and reaches the live
        // Firestore call. Cloud Firestore itself isn't enabled yet for the
        // schooledgeapp project (a real one-time operational step, not a code
        // gap - see the plan's "Operational steps" section) - Firestore_client
        // degrades that gracefully to an empty result rather than an error,
        // the same philosophy Fcm_push already uses, so 200+empty is the
        // correct outcome here and proves authorization succeeded.
        $assignedTeacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $this->fixtures->createTeacherAllocation($this->branchId, $assignedTeacher['staff_id'], $this->classId, $this->sectionId, $this->yearId);
        $assignedSession = $this->fixtures->login($assignedTeacher['username'], $assignedTeacher['password']);
        $asAssignedTeacher = Http::get("chat/oversight/$classroomKey", $assignedSession['access_token']);
        $this->assertSame(200, $asAssignedTeacher['status'], json_encode($asAssignedTeacher['body']));
        $this->assertSame(array(), $asAssignedTeacher['body']['data']['conversations']);
    }

    public function test_oversight_classes_returns_only_a_teachers_own_classes_but_every_class_for_an_admin(): void
    {
        $otherClassId = $this->fixtures->createClass($this->branchId);
        $otherSectionId = $this->fixtures->createSection($this->branchId);
        // Give both classes real enrollment, since oversightClasses() for an
        // admin derives the list from `enroll`, not from the class/section
        // tables directly.
        $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $this->fixtures->createStudent($this->branchId, $otherClassId, $otherSectionId, $this->yearId, array('with_login' => true));

        $teacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $this->fixtures->createTeacherAllocation($this->branchId, $teacher['staff_id'], $this->classId, $this->sectionId, $this->yearId);
        $teacherSession = $this->fixtures->login($teacher['username'], $teacher['password']);
        $asTeacher = Http::get('chat/oversight/classes', $teacherSession['access_token']);
        $this->assertSame(200, $asTeacher['status'], json_encode($asTeacher['body']));
        $teacherKeys = array_column($asTeacher['body']['data'], 'classroom_key');
        $this->assertContains($this->branchId . '_' . $this->classId . '_' . $this->sectionId, $teacherKeys);
        $this->assertNotContains($this->branchId . '_' . $otherClassId . '_' . $otherSectionId, $teacherKeys, 'a teacher must only see their own assigned classes');

        $admin = $this->fixtures->createStaff($this->branchId, 'TestPass123', 2);
        $adminSession = $this->fixtures->login($admin['username'], $admin['password']);
        $asAdmin = Http::get('chat/oversight/classes', $adminSession['access_token']);
        $this->assertSame(200, $asAdmin['status'], json_encode($asAdmin['body']));
        $adminKeys = array_column($asAdmin['body']['data'], 'classroom_key');
        $this->assertContains($this->branchId . '_' . $this->classId . '_' . $this->sectionId, $adminKeys);
        $this->assertContains($this->branchId . '_' . $otherClassId . '_' . $otherSectionId, $adminKeys, 'an admin with chat_oversight access must see every enrolled class in the branch');
    }

    public function test_a_voice_note_is_playable_by_an_oversight_authorized_teacher_but_not_an_unassigned_one(): void
    {
        $studentA = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $studentB = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $sessionA = $this->fixtures->login($studentA['username'], $studentA['password']);
        $sessionB = $this->fixtures->login($studentB['username'], $studentB['password']);
        $ids = array($sessionA['membership']['id'], $sessionB['membership']['id']);
        sort($ids);
        $conversationId = $ids[0] . '_' . $ids[1];

        $audioPath = $this->tempAudioFile('oversight playable bytes');
        $upload = Http::postMultipart('chat/voice-notes', array('conversation_id' => $conversationId), array('file' => $audioPath), $sessionA['access_token']);
        $this->assertSame(200, $upload['status'], json_encode($upload['body']));
        $noteId = $upload['body']['data']['id'];

        $assignedTeacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $this->fixtures->createTeacherAllocation($this->branchId, $assignedTeacher['staff_id'], $this->classId, $this->sectionId, $this->yearId);
        $assignedSession = $this->fixtures->login($assignedTeacher['username'], $assignedTeacher['password']);
        $forAssignedTeacher = Http::get("chat/voice-notes/$noteId", $assignedSession['access_token']);
        $this->assertSame(200, $forAssignedTeacher['status']);
        $this->assertStringContainsString('oversight playable bytes', $forAssignedTeacher['body']['raw'] ?? '');

        $unassignedTeacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $unassignedSession = $this->fixtures->login($unassignedTeacher['username'], $unassignedTeacher['password']);
        $forUnassignedTeacher = Http::get("chat/voice-notes/$noteId", $unassignedSession['access_token']);
        $this->assertSame(404, $forUnassignedTeacher['status'], 'a teacher not assigned to this class must never be able to play the voice note');
    }
}
