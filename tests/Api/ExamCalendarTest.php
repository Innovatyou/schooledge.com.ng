<?php
namespace Tests\Api;

use PHPUnit\Framework\TestCase;
use Tests\Support\Fixtures;
use Tests\Support\Http;

/**
 * Covers Timetable::exams() - reads the previously mark-only `timetable_exam`
 * table as a "when is my next exam" schedule. Role-aware like the existing
 * class timetable: a student/parent sees only their own class+section, a
 * teacher only classes they're actually assigned to (teacherClasses()).
 */
final class ExamCalendarTest extends TestCase
{
    private Fixtures $fixtures;
    private int $branchId;
    private int $yearId;
    private int $classId;
    private int $sectionId;
    private int $examId;
    private int $hallId;
    private int $subjectId;

    protected function setUp(): void
    {
        $this->fixtures = new Fixtures();
        $this->branchId = $this->fixtures->createBranch();
        // Timetable::exams() scopes a teacher's view by the real
        // global_settings.session_id (same as the existing Timetable::index()
        // teacher branch it mirrors) rather than the requested student's own
        // enrollment session, so fixtures must use the live current session -
        // a throwaway createSchoolYear() would never match for the teacher path.
        $this->yearId = (int)Fixtures::db()->query('SELECT session_id FROM global_settings WHERE id = 1')->fetch_assoc()['session_id'];
        $this->classId = $this->fixtures->createClass($this->branchId);
        $this->sectionId = $this->fixtures->createSection($this->branchId);
        $this->examId = $this->fixtures->createExam($this->branchId, $this->yearId);
        $this->hallId = $this->fixtures->createExamHall($this->branchId);
        $this->subjectId = $this->fixtures->createSubject($this->branchId);
        $this->fixtures->createExamSchedule($this->branchId, $this->yearId, $this->examId, $this->classId, $this->sectionId, $this->subjectId, $this->hallId);
    }

    protected function tearDown(): void
    {
        $this->fixtures->cleanupMobileAuthForBranch($this->branchId);
        $this->fixtures->cleanup();
    }

    public function test_a_student_sees_their_own_class_exam_schedule(): void
    {
        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $session = $this->fixtures->login($student['username'], $student['password']);

        $response = Http::get('timetable/exams', $session['access_token']);

        $this->assertSame(200, $response['status'], json_encode($response['body']));
        $exams = $response['body']['data']['exams'];
        $this->assertCount(1, $exams);
        $this->assertArrayHasKey('exam_name', $exams[0]);
        $this->assertArrayHasKey('hall_name', $exams[0]);
        $this->assertArrayHasKey('subject_name', $exams[0]);
    }

    public function test_a_student_in_a_different_class_sees_no_exams(): void
    {
        $otherClassId = $this->fixtures->createClass($this->branchId);
        $otherSectionId = $this->fixtures->createSection($this->branchId);
        $student = $this->fixtures->createStudent($this->branchId, $otherClassId, $otherSectionId, $this->yearId, array('with_login' => true));
        $session = $this->fixtures->login($student['username'], $student['password']);

        $response = Http::get('timetable/exams', $session['access_token']);

        $this->assertSame(200, $response['status']);
        $this->assertSame(array(), $response['body']['data']['exams']);
    }

    public function test_a_teacher_sees_exams_only_for_assigned_classes(): void
    {
        $teacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $this->fixtures->createTeacherAllocation($this->branchId, $teacher['staff_id'], $this->classId, $this->sectionId, $this->yearId);
        $session = $this->fixtures->login($teacher['username'], $teacher['password']);

        $response = Http::get('timetable/exams', $session['access_token']);
        $this->assertSame(200, $response['status'], json_encode($response['body']));
        $this->assertCount(1, $response['body']['data']['exams']);

        $unassignedTeacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $otherSession = $this->fixtures->login($unassignedTeacher['username'], $unassignedTeacher['password']);
        $otherResponse = Http::get('timetable/exams', $otherSession['access_token']);
        $this->assertSame(200, $otherResponse['status']);
        $this->assertSame(array(), $otherResponse['body']['data']['exams']);
    }
}
