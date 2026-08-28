<?php
namespace Tests\Api;

use PHPUnit\Framework\TestCase;
use Tests\Support\Fixtures;
use Tests\Support\Http;

/**
 * Covers Admin::approve_admission()/reject_admission(), which mirrors
 * Online_admission::admission_approval_save() - the checker action that
 * actually creates the real student/parent/login_credential rows and emails
 * credentials. Never touches the real online_admission tables outside its own
 * throwaway branch; the created student/parent/enroll rows this produces on
 * approval are tracked and deleted in tearDown just like any other fixture.
 */
final class AdmissionApprovalTest extends TestCase
{
    private Fixtures $fixtures;
    private int $branchId;
    private int $classId;
    private int $sectionId;
    private int $yearId;
    private array $checker; // has permission, will approve/reject
    private array $maker;   // stages the admission (no permission needed to stage in this test - staged directly via fixture)

    protected function setUp(): void
    {
        $this->fixtures = new Fixtures();
        $this->branchId = $this->fixtures->createBranch(); // stu_generate=1/grd_generate=1 by default - finalizeSave() auto-generates credentials, no staged secrets needed
        $this->yearId = $this->fixtures->createSchoolYear();
        $this->classId = $this->fixtures->createClass($this->branchId);
        $this->sectionId = $this->fixtures->createSection($this->branchId);

        $this->checker = $this->fixtures->createStaff($this->branchId, 'TestPass123');
        $this->fixtures->grantPermission($this->checker['role_id'], 'online_admission_approve', array('is_view' => 1, 'is_add' => 1));

        $this->maker = $this->fixtures->createStaff($this->branchId, 'TestPass123');
    }

    protected function tearDown(): void
    {
        $this->fixtures->cleanupMobileAuthForBranch($this->branchId);
        $this->fixtures->cleanup();
    }

    private function stagePayload(): array
    {
        return array(
            'first_name' => 'Ada', 'last_name' => 'Test', 'gender' => 'female', 'birthday' => '2015-01-01',
            'class_id' => $this->classId, 'section_id' => $this->sectionId, 'year_id' => $this->yearId,
            'mobileno' => '08010000000', 'email' => 'ada-' . $this->fixtures->randomSuffix() . '@example.test',
        );
    }

    public function test_approving_a_staged_admission_creates_real_student_and_parent_rows(): void
    {
        $staging = $this->fixtures->createOnlineAdmissionStaging($this->branchId, $this->maker['staff_id'], $this->stagePayload());
        $session = $this->fixtures->login($this->checker['username'], $this->checker['password']);

        $response = Http::post('admin/approvals/admission/' . $staging['staging_id'] . '/approve', array('comments' => 'looks good'), $session['access_token']);

        $this->assertSame(200, $response['status'], json_encode($response['body']));
        $this->assertTrue($response['body']['data']['approved'] ?? false);
        $studentId = $response['body']['data']['student_id'] ?? null;
        $this->assertNotEmpty($studentId);

        $db = Fixtures::db();
        $student = $db->query('SELECT * FROM student WHERE id = ' . (int)$studentId)->fetch_assoc();
        $this->assertNotNull($student, 'the real student row must have been created');
        $this->assertSame('Ada', $student['first_name']);
        $enroll = $db->query('SELECT * FROM enroll WHERE student_id = ' . (int)$studentId)->fetch_assoc();
        $this->assertNotNull($enroll, 'the real enroll row must have been created');
        $credential = $db->query('SELECT * FROM login_credential WHERE user_id = ' . (int)$studentId . ' AND role = 7')->fetch_assoc();
        $this->assertNotNull($credential, 'a real student login must have been created');
        $stagingRow = $db->query('SELECT status FROM online_admission_staging WHERE id = ' . (int)$staging['staging_id'])->fetch_assoc();
        $this->assertSame('2', (string)$stagingRow['status']);

        // Track for cleanup - these were created by app code, not Fixtures::insert().
        $db->query('DELETE FROM enroll WHERE student_id = ' . (int)$studentId);
        $db->query('DELETE FROM login_credential WHERE user_id = ' . (int)$studentId . ' AND role IN (6,7)');
        if (!empty($student['parent_id'])) $db->query('DELETE FROM parent WHERE id = ' . (int)$student['parent_id']);
        $db->query('DELETE FROM student WHERE id = ' . (int)$studentId);
    }

    public function test_rejecting_a_staged_admission_creates_nothing(): void
    {
        $staging = $this->fixtures->createOnlineAdmissionStaging($this->branchId, $this->maker['staff_id'], $this->stagePayload());
        $session = $this->fixtures->login($this->checker['username'], $this->checker['password']);
        $studentCountBefore = Fixtures::db()->query('SELECT COUNT(*) c FROM student')->fetch_assoc()['c'];

        $response = Http::post('admin/approvals/admission/' . $staging['staging_id'] . '/reject', array('comments' => 'incomplete documents'), $session['access_token']);

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['body']['data']['rejected'] ?? false);
        $studentCountAfter = Fixtures::db()->query('SELECT COUNT(*) c FROM student')->fetch_assoc()['c'];
        $this->assertSame($studentCountBefore, $studentCountAfter, 'no student row should be created on rejection');
        $stagingRow = Fixtures::db()->query('SELECT status FROM online_admission_staging WHERE id = ' . (int)$staging['staging_id'])->fetch_assoc();
        $this->assertSame('3', (string)$stagingRow['status']);
    }

    public function test_the_maker_cannot_approve_their_own_submission(): void
    {
        $this->fixtures->grantPermission($this->maker['role_id'], 'online_admission_approve', array('is_view' => 1, 'is_add' => 1));
        $staging = $this->fixtures->createOnlineAdmissionStaging($this->branchId, $this->maker['staff_id'], $this->stagePayload());
        $session = $this->fixtures->login($this->maker['username'], $this->maker['password']);

        $response = Http::post('admin/approvals/admission/' . $staging['staging_id'] . '/approve', array(), $session['access_token']);

        $this->assertSame(403, $response['status']);
        $this->assertSame('self_approval_not_allowed', $response['body']['error']['code'] ?? null);
    }

    public function test_approving_the_same_staged_admission_twice_is_rejected(): void
    {
        $staging = $this->fixtures->createOnlineAdmissionStaging($this->branchId, $this->maker['staff_id'], $this->stagePayload());
        $session = $this->fixtures->login($this->checker['username'], $this->checker['password']);

        $first = Http::post('admin/approvals/admission/' . $staging['staging_id'] . '/approve', array(), $session['access_token']);
        $this->assertSame(200, $first['status']);
        $studentId = $first['body']['data']['student_id'];

        $second = Http::post('admin/approvals/admission/' . $staging['staging_id'] . '/approve', array(), $session['access_token']);
        $this->assertSame(404, $second['status']);
        $this->assertSame('request_not_found', $second['body']['error']['code'] ?? null);

        $db = Fixtures::db();
        $db->query('DELETE FROM enroll WHERE student_id = ' . (int)$studentId);
        $db->query('DELETE FROM login_credential WHERE user_id = ' . (int)$studentId . ' AND role IN (6,7)');
        $student = $db->query('SELECT parent_id FROM student WHERE id = ' . (int)$studentId)->fetch_assoc();
        if (!empty($student['parent_id'])) $db->query('DELETE FROM parent WHERE id = ' . (int)$student['parent_id']);
        $db->query('DELETE FROM student WHERE id = ' . (int)$studentId);
    }
}
