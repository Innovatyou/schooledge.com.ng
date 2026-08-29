<?php
namespace Tests\Api;

use PHPUnit\Framework\TestCase;
use Tests\Support\Fixtures;
use Tests\Support\Http;

/**
 * Covers Profile::id_card() - the in-app digital ID card. It must resolve
 * ownership exactly like every other student-scoped endpoint
 * (resolveOwnedEnrollment()): a student sees only their own card, a parent
 * only their linked child's, and never a student outside that relationship.
 */
final class IdCardTest extends TestCase
{
    private Fixtures $fixtures;
    private int $branchId;

    protected function setUp(): void
    {
        $this->fixtures = new Fixtures();
        $this->branchId = $this->fixtures->createBranch();
    }

    protected function tearDown(): void
    {
        $this->fixtures->cleanupMobileAuthForBranch($this->branchId);
        $this->fixtures->cleanup();
    }

    public function test_a_student_can_fetch_their_own_id_card(): void
    {
        $yearId = $this->fixtures->createSchoolYear();
        $classId = $this->fixtures->createClass($this->branchId);
        $sectionId = $this->fixtures->createSection($this->branchId);
        $student = $this->fixtures->createStudent($this->branchId, $classId, $sectionId, $yearId, array('with_login' => true));

        $session = $this->fixtures->login($student['username'], $student['password']);
        $response = Http::get('profile/id-card', $session['access_token']);

        $this->assertSame(200, $response['status'], json_encode($response['body']));
        $data = $response['body']['data'];
        $this->assertNotEmpty($data['name']);
        $this->assertArrayHasKey('roll', $data);
        $this->assertArrayHasKey('class_name', $data);
        $this->assertArrayHasKey('school', $data);
    }

    public function test_a_parent_can_fetch_their_linked_childs_id_card(): void
    {
        $yearId = $this->fixtures->createSchoolYear();
        $classId = $this->fixtures->createClass($this->branchId);
        $sectionId = $this->fixtures->createSection($this->branchId);
        $parentId = $this->fixtures->createParent($this->branchId);
        $student = $this->fixtures->createStudent($this->branchId, $classId, $sectionId, $yearId, array('parent_id' => $parentId));
        $parentLogin = $this->fixtures->createParentLogin($parentId);

        $session = $this->fixtures->login($parentLogin['username'], $parentLogin['password']);
        $response = Http::get('profile/id-card?student_id=' . $student['student_id'], $session['access_token']);

        $this->assertSame(200, $response['status'], json_encode($response['body']));
        $this->assertNotEmpty($response['body']['data']['name']);
    }

    public function test_a_parent_cannot_fetch_an_unrelated_students_id_card(): void
    {
        $yearId = $this->fixtures->createSchoolYear();
        $classId = $this->fixtures->createClass($this->branchId);
        $sectionId = $this->fixtures->createSection($this->branchId);
        $ownParentId = $this->fixtures->createParent($this->branchId);
        $this->fixtures->createStudent($this->branchId, $classId, $sectionId, $yearId, array('parent_id' => $ownParentId));
        $parentLogin = $this->fixtures->createParentLogin($ownParentId);

        $otherStudent = $this->fixtures->createStudent($this->branchId, $classId, $sectionId, $yearId);

        $session = $this->fixtures->login($parentLogin['username'], $parentLogin['password']);
        $response = Http::get('profile/id-card?student_id=' . $otherStudent['student_id'], $session['access_token']);

        $this->assertSame(404, $response['status']);
        $this->assertSame('student_not_found', $response['body']['error']['code'] ?? null);
    }
}
