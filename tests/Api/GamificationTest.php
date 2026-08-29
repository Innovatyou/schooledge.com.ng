<?php
namespace Tests\Api;

use PHPUnit\Framework\TestCase;
use Tests\Support\Fixtures;
use Tests\Support\Http;

/**
 * Covers the automatic points/badges ledger (Gamification_model), hooked
 * into Attendance::capture()/scan() and Homework::submit(). The critical
 * property under test throughout is idempotency: none of these signals can
 * ever be double-awarded, since the ledger's UNIQUE key
 * (enroll_id, reason_code, related_type, related_id) makes a repeat award
 * for the same underlying record a silent no-op (INSERT IGNORE).
 */
final class GamificationTest extends TestCase
{
    private Fixtures $fixtures;
    private int $branchId;
    private int $yearId;
    private int $classId;
    private int $sectionId;

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
        // Ledger/badge rows are written by application logic (not via
        // Fixtures::insert()), so they need their own sweep before the
        // branch (and everything FK-tied to it) is deleted - same pattern
        // as cleanupMobileAuthForBranch().
        $db = Fixtures::db();
        $enrollIds = array();
        $result = $db->query('SELECT id FROM enroll WHERE branch_id = ' . $this->branchId);
        while ($row = $result->fetch_assoc()) $enrollIds[] = (int)$row['id'];
        if ($enrollIds) {
            $inList = implode(',', $enrollIds);
            $db->query('DELETE FROM schooledge_points_ledger WHERE enroll_id IN (' . $inList . ')');
            $db->query('DELETE FROM schooledge_student_badges WHERE enroll_id IN (' . $inList . ')');
        }
        $db->query('DELETE FROM student_attendance WHERE branch_id = ' . $this->branchId);
        $this->fixtures->cleanupMobileAuthForBranch($this->branchId);
        $this->fixtures->cleanup();
    }

    private function pointsFor(int $enrollId): int
    {
        $row = Fixtures::db()->query('SELECT COALESCE(SUM(points),0) as total FROM schooledge_points_ledger WHERE enroll_id = ' . $enrollId)->fetch_assoc();
        return (int)$row['total'];
    }

    private function ledgerRowCount(int $enrollId, string $reasonCode): int
    {
        $row = Fixtures::db()->query("SELECT COUNT(*) as c FROM schooledge_points_ledger WHERE enroll_id = $enrollId AND reason_code = '" . Fixtures::db()->real_escape_string($reasonCode) . "'")->fetch_assoc();
        return (int)$row['c'];
    }

    public function test_marking_a_student_present_awards_points_exactly_once_even_if_resaved(): void
    {
        $teacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $this->fixtures->createTeacherAllocation($this->branchId, $teacher['staff_id'], $this->classId, $this->sectionId, $this->yearId);
        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId);
        $enrollRow = Fixtures::db()->query('SELECT id FROM enroll WHERE student_id = ' . $student['student_id'])->fetch_assoc();
        $enrollId = (int)$enrollRow['id'];

        $teacherSession = $this->fixtures->login($teacher['username'], $teacher['password']);
        $date = date('Y-m-d');
        $capture = function () use ($teacherSession, $enrollId, $date) {
            return Http::post('attendance/capture', array(
                'class_id' => $this->classId, 'section_id' => $this->sectionId, 'date' => $date,
                'entries' => array(array('enroll_id' => $enrollId, 'status' => 'P')),
            ), $teacherSession['access_token']);
        };

        $first = $capture();
        $this->assertSame(200, $first['status'], json_encode($first['body']));
        $this->assertSame(2, $this->pointsFor($enrollId));
        $this->assertSame(1, $this->ledgerRowCount($enrollId, 'attendance_present'));

        // Re-saving the SAME day as present again (e.g. teacher re-submits the
        // roster) must not double-award.
        $second = $capture();
        $this->assertSame(200, $second['status']);
        $this->assertSame(2, $this->pointsFor($enrollId));
        $this->assertSame(1, $this->ledgerRowCount($enrollId, 'attendance_present'));
    }

    public function test_a_5_day_attendance_streak_awards_the_streak_badge_once(): void
    {
        $teacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $this->fixtures->createTeacherAllocation($this->branchId, $teacher['staff_id'], $this->classId, $this->sectionId, $this->yearId);
        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId);
        $enrollRow = Fixtures::db()->query('SELECT id FROM enroll WHERE student_id = ' . $student['student_id'])->fetch_assoc();
        $enrollId = (int)$enrollRow['id'];
        $teacherSession = $this->fixtures->login($teacher['username'], $teacher['password']);

        for ($i = 5; $i >= 1; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $response = Http::post('attendance/capture', array(
                'class_id' => $this->classId, 'section_id' => $this->sectionId, 'date' => $date,
                'entries' => array(array('enroll_id' => $enrollId, 'status' => 'P')),
            ), $teacherSession['access_token']);
            $this->assertSame(200, $response['status'], json_encode($response['body']));
        }

        $badges = Fixtures::db()->query('SELECT b.code FROM schooledge_student_badges sb JOIN schooledge_badges b ON b.id = sb.badge_id WHERE sb.enroll_id = ' . $enrollId)->fetch_all(MYSQLI_ASSOC);
        $codes = array_column($badges, 'code');
        $this->assertContains('streak_5', $codes);
        $this->assertCount(1, array_filter($codes, fn ($c) => $c === 'streak_5'), 'streak_5 must be awarded exactly once');
    }

    public function test_submitting_the_same_homework_twice_awards_points_exactly_once(): void
    {
        $teacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $subjectId = $this->fixtures->createSubject($this->branchId);
        $homeworkId = $this->fixtures->createHomework($this->branchId, $this->classId, $this->sectionId, $this->yearId, $subjectId, $teacher['staff_id']);
        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $enrollRow = Fixtures::db()->query('SELECT id FROM enroll WHERE student_id = ' . $student['student_id'])->fetch_assoc();
        $enrollId = (int)$enrollRow['id'];

        $studentSession = $this->fixtures->login($student['username'], $student['password']);
        $submit = function () use ($studentSession, $homeworkId) {
            return Http::postForm("homework/$homeworkId/submit", array('message' => 'Done!'), $studentSession['access_token']);
        };

        $first = $submit();
        $this->assertSame(200, $first['status'], json_encode($first['body']));
        $this->assertSame(10, $this->pointsFor($enrollId));
        $this->assertSame(1, $this->ledgerRowCount($enrollId, 'homework_ontime'));

        // Resubmitting (editing the message) is an UPDATE, not a new insert -
        // must not re-check or re-award.
        $second = $submit();
        $this->assertSame(200, $second['status']);
        $this->assertSame(10, $this->pointsFor($enrollId));
        $this->assertSame(1, $this->ledgerRowCount($enrollId, 'homework_ontime'));

        $badges = Fixtures::db()->query('SELECT COUNT(*) c FROM schooledge_student_badges WHERE enroll_id = ' . $enrollId)->fetch_assoc();
        $this->assertSame('1', (string)$badges['c']);
    }

    public function test_a_late_homework_submission_earns_no_points(): void
    {
        $teacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $subjectId = $this->fixtures->createSubject($this->branchId);
        $homeworkId = $this->fixtures->createHomework($this->branchId, $this->classId, $this->sectionId, $this->yearId, $subjectId, $teacher['staff_id'], array(
            'date_of_submission' => date('Y-m-d', strtotime('-1 day')),
        ));
        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $enrollRow = Fixtures::db()->query('SELECT id FROM enroll WHERE student_id = ' . $student['student_id'])->fetch_assoc();
        $enrollId = (int)$enrollRow['id'];

        $studentSession = $this->fixtures->login($student['username'], $student['password']);
        $response = Http::postForm("homework/$homeworkId/submit", array('message' => 'Sorry, late!'), $studentSession['access_token']);

        $this->assertSame(200, $response['status'], json_encode($response['body']));
        $this->assertSame(0, $this->pointsFor($enrollId));
    }

    public function test_leaderboard_never_crosses_class_boundary(): void
    {
        $teacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $this->fixtures->createTeacherAllocation($this->branchId, $teacher['staff_id'], $this->classId, $this->sectionId, $this->yearId);
        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));

        $otherClassId = $this->fixtures->createClass($this->branchId);
        $otherSectionId = $this->fixtures->createSection($this->branchId);
        $otherTeacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $this->fixtures->createTeacherAllocation($this->branchId, $otherTeacher['staff_id'], $otherClassId, $otherSectionId, $this->yearId);
        $otherStudent = $this->fixtures->createStudent($this->branchId, $otherClassId, $otherSectionId, $this->yearId);
        $otherEnrollRow = Fixtures::db()->query('SELECT id FROM enroll WHERE student_id = ' . $otherStudent['student_id'])->fetch_assoc();

        // Award the OTHER class's student points directly (bypassing HTTP -
        // just need ledger data to exist to prove it doesn't leak across).
        Fixtures::db()->query('INSERT INTO schooledge_points_ledger (branch_id,enroll_id,points,reason_code,reason_label,related_type,related_id,created_at) VALUES (' . $this->branchId . ',' . (int)$otherEnrollRow['id'] . ",50,'attendance_present','Present','student_attendance',1,NOW())");

        $studentSession = $this->fixtures->login($student['username'], $student['password']);
        $response = Http::get('gamification/leaderboard', $studentSession['access_token']);

        $this->assertSame(200, $response['status'], json_encode($response['body']));
        $names = array_column($response['body']['data']['leaderboard'], 'enroll_id');
        $this->assertNotContains((int)$otherEnrollRow['id'], $names, 'a different class\'s student must never appear on this leaderboard');
    }

    public function test_me_endpoint_reports_points_rank_and_badges(): void
    {
        $teacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $this->fixtures->createTeacherAllocation($this->branchId, $teacher['staff_id'], $this->classId, $this->sectionId, $this->yearId);
        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $teacherSession = $this->fixtures->login($teacher['username'], $teacher['password']);
        $enrollRow = Fixtures::db()->query('SELECT id FROM enroll WHERE student_id = ' . $student['student_id'])->fetch_assoc();

        Http::post('attendance/capture', array(
            'class_id' => $this->classId, 'section_id' => $this->sectionId, 'date' => date('Y-m-d'),
            'entries' => array(array('enroll_id' => (int)$enrollRow['id'], 'status' => 'P')),
        ), $teacherSession['access_token']);

        $studentSession = $this->fixtures->login($student['username'], $student['password']);
        $response = Http::get('gamification/me', $studentSession['access_token']);

        $this->assertSame(200, $response['status'], json_encode($response['body']));
        $this->assertSame(2, $response['body']['data']['points_total']);
        $this->assertSame(1, $response['body']['data']['rank_in_class']);
    }
}
