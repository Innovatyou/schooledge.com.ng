<?php
namespace Tests\Api;

use PHPUnit\Framework\TestCase;
use Tests\Support\Fixtures;
use Tests\Support\Http;

/**
 * The one standing rule every mobile-API mutating endpoint must respect: a
 * membership on a branch with is_demo=1 can read but never write
 * (Api_Controller::blockIfDemoReadonly()). This uses its own disposable
 * is_demo=1 branch, never the real production demo school - the assertion is
 * about the mechanism (does the flag block writes at all), not about any
 * specific demo account.
 */
final class DemoReadonlyTest extends TestCase
{
    private Fixtures $fixtures;
    private int $branchId;
    private array $student;

    protected function setUp(): void
    {
        $this->fixtures = new Fixtures();
        $this->branchId = $this->fixtures->createBranch(array('is_demo' => 1));
        $yearId = $this->fixtures->createSchoolYear();
        $classId = $this->fixtures->createClass($this->branchId);
        $sectionId = $this->fixtures->createSection($this->branchId);
        $this->student = $this->fixtures->createStudent($this->branchId, $classId, $sectionId, $yearId, array(
            'with_login' => true,
        ));
    }

    protected function tearDown(): void
    {
        $this->fixtures->cleanupMobileAuthForBranch($this->branchId);
        $this->fixtures->cleanup();
    }

    public function test_mutating_endpoint_is_blocked_on_a_demo_branch(): void
    {
        $session = $this->fixtures->login($this->student['username'], $this->student['password']);

        $response = Http::patch('profile', array('mobileno' => '08011112222'), $session['access_token']);

        $this->assertSame(403, $response['status']);
        $this->assertSame('demo_readonly', $response['body']['error']['code'] ?? null);
    }

    public function test_read_only_endpoint_still_works_on_a_demo_branch(): void
    {
        $session = $this->fixtures->login($this->student['username'], $this->student['password']);

        $response = Http::get('profile', $session['access_token']);

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['body']['success'] ?? false);
    }

    public function test_mutating_endpoint_is_allowed_on_a_non_demo_branch(): void
    {
        // Sanity check for the test mechanism itself - the same request against
        // an otherwise-identical non-demo branch must succeed, so a passing
        // "blocked" assertion above can't be hiding a broken/always-403 route.
        $otherBranchId = $this->fixtures->createBranch(array('is_demo' => 0));
        $yearId = $this->fixtures->createSchoolYear();
        $classId = $this->fixtures->createClass($otherBranchId);
        $sectionId = $this->fixtures->createSection($otherBranchId);
        $student = $this->fixtures->createStudent($otherBranchId, $classId, $sectionId, $yearId, array('with_login' => true));
        $session = $this->fixtures->login($student['username'], $student['password']);

        $response = Http::patch('profile', array('mobileno' => '08011112222'), $session['access_token']);

        $this->assertSame(200, $response['status']);
        $this->fixtures->cleanupMobileAuthForBranch($otherBranchId);
    }
}
