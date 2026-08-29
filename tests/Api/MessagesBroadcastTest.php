<?php
namespace Tests\Api;

use PHPUnit\Framework\TestCase;
use Tests\Support\Fixtures;
use Tests\Support\Http;

/**
 * Covers Messages::broadcast() - a teacher messaging every student in one of
 * their own classes at once. The critical property under test is the same
 * one every class-scoped endpoint in this app needs: a student outside the
 * targeted class+section must never receive it, and a teacher can only
 * broadcast to a class they're actually assigned to.
 */
final class MessagesBroadcastTest extends TestCase
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
        Fixtures::db()->query("DELETE FROM message WHERE subject LIKE 'Test broadcast %'");
        $this->fixtures->cleanupMobileAuthForBranch($this->branchId);
        $this->fixtures->cleanup();
    }

    public function test_broadcast_reaches_every_student_in_the_class_and_only_that_class(): void
    {
        $teacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $this->fixtures->createTeacherAllocation($this->branchId, $teacher['staff_id'], $this->classId, $this->sectionId, $this->yearId);

        $studentA = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $studentB = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));

        $otherClassId = $this->fixtures->createClass($this->branchId);
        $otherSectionId = $this->fixtures->createSection($this->branchId);
        $outsideStudent = $this->fixtures->createStudent($this->branchId, $otherClassId, $otherSectionId, $this->yearId, array('with_login' => true));

        $teacherSession = $this->fixtures->login($teacher['username'], $teacher['password']);
        $subject = 'Test broadcast ' . $this->fixtures->randomSuffix();
        $send = Http::postForm('messages/broadcast', array(
            'class_id' => $this->classId, 'section_id' => $this->sectionId,
            'subject' => $subject, 'message' => 'Bring your textbooks tomorrow.',
        ), $teacherSession['access_token']);
        $this->assertSame(200, $send['status'], json_encode($send['body']));
        $this->assertSame(2, $send['body']['data']['recipient_count']);

        $studentASession = $this->fixtures->login($studentA['username'], $studentA['password']);
        $inboxA = Http::get('messages', $studentASession['access_token']);
        $this->assertContains($subject, array_column($inboxA['body']['data'], 'subject'));

        $studentBSession = $this->fixtures->login($studentB['username'], $studentB['password']);
        $inboxB = Http::get('messages', $studentBSession['access_token']);
        $this->assertContains($subject, array_column($inboxB['body']['data'], 'subject'));

        $outsideSession = $this->fixtures->login($outsideStudent['username'], $outsideStudent['password']);
        $inboxOutside = Http::get('messages', $outsideSession['access_token']);
        $this->assertNotContains($subject, array_column($inboxOutside['body']['data'], 'subject'), 'a student in a different class must never receive the broadcast');
    }

    public function test_teacher_cannot_broadcast_to_a_class_they_do_not_own(): void
    {
        $teacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        // Deliberately no teacher_allocation/subject_assign row for this class+section.

        $session = $this->fixtures->login($teacher['username'], $teacher['password']);
        $send = Http::postForm('messages/broadcast', array(
            'class_id' => $this->classId, 'section_id' => $this->sectionId,
            'subject' => 'Test broadcast ' . $this->fixtures->randomSuffix(), 'message' => 'Hello',
        ), $session['access_token']);

        $this->assertSame(403, $send['status']);
        $this->assertSame('class_not_assigned', $send['body']['error']['code'] ?? null);
    }

    public function test_a_student_cannot_broadcast(): void
    {
        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $session = $this->fixtures->login($student['username'], $student['password']);

        $send = Http::postForm('messages/broadcast', array(
            'class_id' => $this->classId, 'section_id' => $this->sectionId,
            'subject' => 'Test broadcast ' . $this->fixtures->randomSuffix(), 'message' => 'Hello',
        ), $session['access_token']);

        $this->assertSame(403, $send['status']);
        $this->assertSame('role_not_supported', $send['body']['error']['code'] ?? null);
    }

    public function test_demo_branch_broadcast_is_blocked(): void
    {
        $demoBranchId = $this->fixtures->createBranch(array('is_demo' => 1));
        $classId = $this->fixtures->createClass($demoBranchId);
        $sectionId = $this->fixtures->createSection($demoBranchId);
        $teacher = $this->fixtures->createStaff($demoBranchId, 'TestPass123', 3);
        $this->fixtures->createTeacherAllocation($demoBranchId, $teacher['staff_id'], $classId, $sectionId, $this->yearId);
        $this->fixtures->createStudent($demoBranchId, $classId, $sectionId, $this->yearId, array('with_login' => true));

        $session = $this->fixtures->login($teacher['username'], $teacher['password']);
        $send = Http::postForm('messages/broadcast', array(
            'class_id' => $classId, 'section_id' => $sectionId,
            'subject' => 'Test broadcast ' . $this->fixtures->randomSuffix(), 'message' => 'Hello',
        ), $session['access_token']);

        $this->assertSame(403, $send['status']);
        $this->assertSame('demo_readonly', $send['body']['error']['code'] ?? null);

        // A second branch created within this one test - cleanupMobileAuthForBranch()
        // in tearDown() only knows about $this->branchId, so sweep this one here.
        $this->fixtures->cleanupMobileAuthForBranch($demoBranchId);
    }
}
