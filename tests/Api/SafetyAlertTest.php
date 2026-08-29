<?php
namespace Tests\Api;

use PHPUnit\Framework\TestCase;
use Tests\Support\Fixtures;
use Tests\Support\Http;

/**
 * Covers Safety::submit()/index()/acknowledge() - the on-demand location
 * share + SOS panic button. The critical property under test is the 3-tier
 * visibility rule being enforced SERVER-SIDE on every read: a family's alert
 * must never leak to an unrelated parent or an unassigned teacher, no
 * matter what the client asks for.
 */
final class SafetyAlertTest extends TestCase
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
        Fixtures::db()->query('DELETE FROM schooledge_safety_alerts WHERE branch_id = ' . $this->branchId);
        $this->fixtures->cleanupMobileAuthForBranch($this->branchId);
        $this->fixtures->cleanup();
    }

    public function test_a_students_sos_is_visible_to_their_own_parent_but_not_an_unrelated_parent(): void
    {
        $parentId = $this->fixtures->createParent($this->branchId);
        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true, 'parent_id' => $parentId));
        $parentLogin = $this->fixtures->createParentLogin($parentId);

        $unrelatedParentId = $this->fixtures->createParent($this->branchId);
        $unrelatedParentLogin = $this->fixtures->createParentLogin($unrelatedParentId);

        $studentSession = $this->fixtures->login($student['username'], $student['password']);
        $submit = Http::post('safety/alerts', array('alert_type' => 'sos', 'latitude' => 6.5244, 'longitude' => 3.3792), $studentSession['access_token']);
        $this->assertSame(200, $submit['status'], json_encode($submit['body']));
        $alertId = $submit['body']['data']['alert_id'];

        $parentSession = $this->fixtures->login($parentLogin['username'], $parentLogin['password']);
        $parentView = Http::get('safety/alerts', $parentSession['access_token']);
        $this->assertSame(200, $parentView['status']);
        $this->assertContains($alertId, array_column($parentView['body']['data']['alerts'], 'id'));

        $unrelatedSession = $this->fixtures->login($unrelatedParentLogin['username'], $unrelatedParentLogin['password']);
        $unrelatedView = Http::get('safety/alerts', $unrelatedSession['access_token']);
        $this->assertSame(200, $unrelatedView['status']);
        $this->assertNotContains($alertId, array_column($unrelatedView['body']['data']['alerts'], 'id'), 'an unrelated parent must never see another family\'s alert');
    }

    public function test_a_students_sos_is_visible_to_their_assigned_teacher_but_not_an_unassigned_teacher(): void
    {
        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $assignedTeacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $this->fixtures->createTeacherAllocation($this->branchId, $assignedTeacher['staff_id'], $this->classId, $this->sectionId, $this->yearId);
        $unassignedTeacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);

        $studentSession = $this->fixtures->login($student['username'], $student['password']);
        $submit = Http::post('safety/alerts', array('alert_type' => 'share', 'latitude' => 6.5, 'longitude' => 3.4), $studentSession['access_token']);
        $this->assertSame(200, $submit['status'], json_encode($submit['body']));
        $alertId = $submit['body']['data']['alert_id'];

        $assignedSession = $this->fixtures->login($assignedTeacher['username'], $assignedTeacher['password']);
        $assignedView = Http::get('safety/alerts', $assignedSession['access_token']);
        $this->assertContains($alertId, array_column($assignedView['body']['data']['alerts'], 'id'));

        $unassignedSession = $this->fixtures->login($unassignedTeacher['username'], $unassignedTeacher['password']);
        $unassignedView = Http::get('safety/alerts', $unassignedSession['access_token']);
        $this->assertNotContains($alertId, array_column($unassignedView['body']['data']['alerts'], 'id'), 'a teacher not assigned to this class must never see the alert');
    }

    public function test_admin_sees_every_alert_in_the_branch_including_a_teachers_own_sos(): void
    {
        $teacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $teacherSession = $this->fixtures->login($teacher['username'], $teacher['password']);
        $submit = Http::post('safety/alerts', array('alert_type' => 'sos', 'latitude' => 6.5, 'longitude' => 3.4), $teacherSession['access_token']);
        $this->assertSame(200, $submit['status'], json_encode($submit['body']));
        $alertId = $submit['body']['data']['alert_id'];

        $admin = $this->fixtures->createStaff($this->branchId, 'TestPass123', 2);
        $adminSession = $this->fixtures->login($admin['username'], $admin['password']);
        $adminView = Http::get('safety/alerts', $adminSession['access_token']);
        $this->assertSame(200, $adminView['status'], json_encode($adminView['body']));
        $this->assertContains($alertId, array_column($adminView['body']['data']['alerts'], 'id'));

        // A different, unrelated teacher must NOT see a colleague's own SOS.
        $otherTeacher = $this->fixtures->createStaff($this->branchId, 'TestPass123', 3);
        $otherSession = $this->fixtures->login($otherTeacher['username'], $otherTeacher['password']);
        $otherView = Http::get('safety/alerts', $otherSession['access_token']);
        $this->assertNotContains($alertId, array_column($otherView['body']['data']['alerts'], 'id'));
    }

    public function test_a_parent_cannot_submit_an_alert(): void
    {
        $parentId = $this->fixtures->createParent($this->branchId);
        $parentLogin = $this->fixtures->createParentLogin($parentId);
        $session = $this->fixtures->login($parentLogin['username'], $parentLogin['password']);

        $response = Http::post('safety/alerts', array('alert_type' => 'sos', 'latitude' => 6.5, 'longitude' => 3.4), $session['access_token']);

        $this->assertSame(403, $response['status']);
        $this->assertSame('role_not_supported', $response['body']['error']['code'] ?? null);
    }

    public function test_an_authorized_viewer_can_acknowledge_an_alert(): void
    {
        $parentId = $this->fixtures->createParent($this->branchId);
        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true, 'parent_id' => $parentId));
        $parentLogin = $this->fixtures->createParentLogin($parentId);

        $studentSession = $this->fixtures->login($student['username'], $student['password']);
        $submit = Http::post('safety/alerts', array('alert_type' => 'sos', 'latitude' => 6.5, 'longitude' => 3.4), $studentSession['access_token']);
        $alertId = $submit['body']['data']['alert_id'];

        $parentSession = $this->fixtures->login($parentLogin['username'], $parentLogin['password']);
        $ack = Http::post("safety/alerts/$alertId/acknowledge", array(), $parentSession['access_token']);
        $this->assertSame(200, $ack['status'], json_encode($ack['body']));

        $row = Fixtures::db()->query('SELECT status FROM schooledge_safety_alerts WHERE id = ' . $alertId)->fetch_assoc();
        $this->assertSame('acknowledged', $row['status']);
    }

    public function test_an_unrelated_viewer_cannot_acknowledge_an_alert(): void
    {
        $student = $this->fixtures->createStudent($this->branchId, $this->classId, $this->sectionId, $this->yearId, array('with_login' => true));
        $studentSession = $this->fixtures->login($student['username'], $student['password']);
        $submit = Http::post('safety/alerts', array('alert_type' => 'share', 'latitude' => 6.5, 'longitude' => 3.4), $studentSession['access_token']);
        $alertId = $submit['body']['data']['alert_id'];

        $unrelatedParentId = $this->fixtures->createParent($this->branchId);
        $unrelatedParentLogin = $this->fixtures->createParentLogin($unrelatedParentId);
        $unrelatedSession = $this->fixtures->login($unrelatedParentLogin['username'], $unrelatedParentLogin['password']);

        $ack = Http::post("safety/alerts/$alertId/acknowledge", array(), $unrelatedSession['access_token']);
        $this->assertSame(404, $ack['status']);
    }
}
