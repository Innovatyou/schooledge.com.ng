<?php
namespace Tests\Api;

use PHPUnit\Framework\TestCase;
use Tests\Support\Fixtures;
use Tests\Support\Http;

/**
 * Covers the new QR attendance flow: a student (or their parent) fetches a
 * short-lived signed token (Attendance::qr_token()), a teacher scans it
 * (Attendance::scan()) to mark that student present for today -
 * Attendance::assertTeacherOwnsClass() still gates every scan exactly like
 * the manual roster capture, so scanning is never a way around "teachers can
 * only mark their own assigned classes."
 */
final class AttendanceQrTest extends TestCase
{
    private Fixtures $fixtures;
    private int $branchId;
    private int $classId;
    private int $sectionId;
    private array $teacher;
    private array $student;

    protected function setUp(): void
    {
        $this->fixtures = new Fixtures();
        $this->branchId = $this->fixtures->createBranch();
        $yearId = $this->fixtures->createSchoolYear();
        $this->classId = $this->fixtures->createClass($this->branchId);
        $this->sectionId = $this->fixtures->createSection($this->branchId);

        $this->teacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $this->fixtures->createTeacherAllocation($this->branchId, $this->teacher['staff_id'], $this->classId, $this->sectionId, $yearId);

        $this->student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $yearId, array('with_login' => true));

        // clear today's attendance row for this fresh enrollment, if any
        Fixtures::db()->query('DELETE FROM student_attendance WHERE enroll_id NOT IN (SELECT id FROM enroll)');
    }

    protected function tearDown(): void
    {
        Fixtures::db()->query('DELETE FROM student_attendance WHERE branch_id = ' . $this->branchId);
        $this->fixtures->cleanupMobileAuthForBranch($this->branchId);
        $this->fixtures->cleanup();
    }

    public function test_teacher_scanning_a_students_qr_token_marks_them_present(): void
    {
        $studentSession = $this->fixtures->login($this->student['username'], $this->student['password']);
        $tokenResponse = Http::get('attendance/qr-token', $studentSession['access_token']);
        $this->assertSame(200, $tokenResponse['status']);
        $token = $tokenResponse['body']['data']['token'];
        $this->assertNotEmpty($token);

        $teacherSession = $this->fixtures->login($this->teacher['username'], $this->teacher['password']);
        $scan = Http::post('attendance/scan', array('token' => $token), $teacherSession['access_token']);

        $this->assertSame(200, $scan['status'], json_encode($scan['body']));
        $this->assertTrue($scan['body']['data']['marked'] ?? false);
        $this->assertFalse($scan['body']['data']['already_marked'] ?? true);
        $this->assertSame((int)$this->student['student_id'], (int)$scan['body']['data']['student']['id']);

        $row = Fixtures::db()->query('SELECT status FROM student_attendance WHERE branch_id = ' . $this->branchId)->fetch_assoc();
        $this->assertSame('P', $row['status']);
    }

    public function test_scanning_the_same_token_twice_is_idempotent(): void
    {
        $studentSession = $this->fixtures->login($this->student['username'], $this->student['password']);
        $token = Http::get('attendance/qr-token', $studentSession['access_token'])['body']['data']['token'];
        $teacherSession = $this->fixtures->login($this->teacher['username'], $this->teacher['password']);

        $first = Http::post('attendance/scan', array('token' => $token), $teacherSession['access_token']);
        $this->assertFalse($first['body']['data']['already_marked'] ?? true);

        $second = Http::post('attendance/scan', array('token' => $token), $teacherSession['access_token']);
        $this->assertSame(200, $second['status']);
        $this->assertTrue($second['body']['data']['already_marked'] ?? false);

        $count = Fixtures::db()->query('SELECT COUNT(*) c FROM student_attendance WHERE branch_id = ' . $this->branchId)->fetch_assoc()['c'];
        $this->assertSame('1', (string)$count);
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $studentSession = $this->fixtures->login($this->student['username'], $this->student['password']);
        // A token with exp in the past can't be minted through the real endpoint (it always issues +20s),
        // so this proves the expiry check itself works by waiting past a real one instead of forging a fake token.
        $token = Http::get('attendance/qr-token', $studentSession['access_token'])['body']['data']['token'];
        sleep(21);

        $teacherSession = $this->fixtures->login($this->teacher['username'], $this->teacher['password']);
        $scan = Http::post('attendance/scan', array('token' => $token), $teacherSession['access_token']);

        $this->assertSame(422, $scan['status']);
        $this->assertSame('invalid_qr', $scan['body']['error']['code'] ?? null);
    }

    public function test_a_teacher_not_assigned_to_the_students_class_cannot_scan(): void
    {
        $studentSession = $this->fixtures->login($this->student['username'], $this->student['password']);
        $token = Http::get('attendance/qr-token', $studentSession['access_token'])['body']['data']['token'];

        $otherTeacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3); // no teacher_allocation row
        $otherSession = $this->fixtures->login($otherTeacher['username'], $otherTeacher['password']);
        $scan = Http::post('attendance/scan', array('token' => $token), $otherSession['access_token']);

        $this->assertSame(403, $scan['status']);
        $this->assertSame('class_not_assigned', $scan['body']['error']['code'] ?? null);
    }

    public function test_a_tampered_token_is_rejected(): void
    {
        $studentSession = $this->fixtures->login($this->student['username'], $this->student['password']);
        $token = Http::get('attendance/qr-token', $studentSession['access_token'])['body']['data']['token'];
        $tampered = substr($token, 0, -2) . 'xx';

        $teacherSession = $this->fixtures->login($this->teacher['username'], $this->teacher['password']);
        $scan = Http::post('attendance/scan', array('token' => $tampered), $teacherSession['access_token']);

        $this->assertSame(422, $scan['status']);
        $this->assertSame('invalid_qr', $scan['body']['error']['code'] ?? null);
    }
}
