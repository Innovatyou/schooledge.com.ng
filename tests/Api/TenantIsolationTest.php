<?php
namespace Tests\Api;

use PHPUnit\Framework\TestCase;
use Tests\Support\Fixtures;
use Tests\Support\Http;

/**
 * Api_Controller::resolveOwnedEnrollment() is the one place every per-student
 * mobile endpoint derives which enrollment a membership may act on. For a
 * parent role, the requested student_id is client-supplied input - the real
 * IDOR risk is a parent passing a *different* parent's child's id and getting
 * back real data instead of a clean 404. This exercises that directly against
 * two fully isolated throwaway branches/families.
 */
final class TenantIsolationTest extends TestCase
{
    private Fixtures $fixtures;
    private array $branch;
    private array $otherBranch;

    protected function setUp(): void
    {
        $this->fixtures = new Fixtures();
        $this->branch = $this->makeFamily();
        $this->otherBranch = $this->makeFamily();
    }

    private function makeFamily(): array
    {
        $branchId = $this->fixtures->createBranch();
        $yearId = $this->fixtures->createSchoolYear();
        $classId = $this->fixtures->createClass($branchId);
        $sectionId = $this->fixtures->createSection($branchId);
        $parentId = $this->fixtures->createParent($branchId);
        $student = $this->fixtures->createStudent($branchId, $classId, $sectionId, $yearId, array('parent_id' => $parentId));
        $parentLogin = $this->fixtures->createParentLogin($parentId);

        return array(
            'branch_id' => $branchId, 'student_id' => $student['student_id'],
            'parent_username' => $parentLogin['username'], 'parent_password' => $parentLogin['password'],
        );
    }

    protected function tearDown(): void
    {
        $this->fixtures->cleanupMobileAuthForBranch($this->branch['branch_id']);
        $this->fixtures->cleanupMobileAuthForBranch($this->otherBranch['branch_id']);
        $this->fixtures->cleanup();
    }

    public function test_a_parent_cannot_read_another_familys_child_by_guessing_the_student_id(): void
    {
        $session = $this->fixtures->login($this->branch['parent_username'], $this->branch['parent_password']);

        $ownChild = Http::get('fees/summary?student_id=' . $this->branch['student_id'], $session['access_token']);
        $this->assertSame(200, $ownChild['status'], 'sanity check: a parent must be able to read their own child');

        $otherChild = Http::get('fees/summary?student_id=' . $this->otherBranch['student_id'], $session['access_token']);
        $this->assertSame(404, $otherChild['status']);
        $this->assertSame('student_not_found', $otherChild['body']['error']['code'] ?? null);
    }

    public function test_a_student_token_can_only_ever_resolve_to_their_own_enrollment(): void
    {
        // Students never send a student_id themselves - resolveOwnedEnrollment
        // takes it from the membership's own user_id for role 7, ignoring any
        // client input entirely. Confirms passing a foreign id has no effect.
        $studentLogin = $this->fixtures->createStudent(
            $this->branch['branch_id'],
            $this->fixtures->createClass($this->branch['branch_id']),
            $this->fixtures->createSection($this->branch['branch_id']),
            $this->fixtures->createSchoolYear(),
            array('with_login' => true)
        );
        $session = $this->fixtures->login($studentLogin['username'], $studentLogin['password']);

        $response = Http::get('fees/summary?student_id=' . $this->otherBranch['student_id'], $session['access_token']);

        $this->assertSame(200, $response['status']);
        $this->assertSame((int)$studentLogin['student_id'], (int)($response['body']['data']['student']['id'] ?? null));
    }
}
