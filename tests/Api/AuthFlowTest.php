<?php
namespace Tests\Api;

use PHPUnit\Framework\TestCase;
use Tests\Support\Fixtures;
use Tests\Support\Http;

/** Core auth invariants: bad credentials are rejected, a protected endpoint requires a token, and a used-up refresh token can't be replayed. */
final class AuthFlowTest extends TestCase
{
    private Fixtures $fixtures;
    private int $branchId;
    private array $student;

    protected function setUp(): void
    {
        $this->fixtures = new Fixtures();
        $this->branchId = $this->fixtures->createBranch();
        $yearId = $this->fixtures->createSchoolYear();
        $classId = $this->fixtures->createClass($this->branchId);
        $sectionId = $this->fixtures->createSection($this->branchId);
        $this->student = $this->fixtures->createStudent($this->branchId, $classId, $sectionId, $yearId, array('with_login' => true));
    }

    protected function tearDown(): void
    {
        $this->fixtures->cleanupMobileAuthForBranch($this->branchId);
        $this->fixtures->cleanup();
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        $response = Http::post('auth/login', array('username' => $this->student['username'], 'password' => 'not-the-password'));

        $this->assertSame(401, $response['status']);
        $this->assertSame('invalid_credentials', $response['body']['error']['code'] ?? null);
    }

    public function test_login_with_correct_password_issues_usable_tokens(): void
    {
        $response = Http::post('auth/login', array('username' => $this->student['username'], 'password' => $this->student['password']));

        $this->assertSame(200, $response['status']);
        $this->assertNotEmpty($response['body']['data']['tokens']['access_token'] ?? null);
        $this->assertNotEmpty($response['body']['data']['tokens']['refresh_token'] ?? null);

        $me = Http::get('profile', $response['body']['data']['tokens']['access_token']);
        $this->assertSame(200, $me['status']);
    }

    public function test_a_protected_endpoint_rejects_a_missing_or_garbage_token(): void
    {
        $noToken = Http::get('profile');
        $this->assertSame(401, $noToken['status']);

        $garbage = Http::get('profile', 'not-a-real-token');
        $this->assertSame(401, $garbage['status']);
        $this->assertSame('invalid_token', $garbage['body']['error']['code'] ?? null);
    }

    public function test_a_refresh_token_cannot_be_replayed_after_rotation(): void
    {
        $session = $this->fixtures->login($this->student['username'], $this->student['password']);

        $first = Http::post('auth/refresh', array('refresh_token' => $session['refresh_token']));
        $this->assertSame(200, $first['status']);

        // The original refresh token was consumed by the rotation above - reusing
        // it is the classic stolen-refresh-token replay scenario and must fail.
        $replay = Http::post('auth/refresh', array('refresh_token' => $session['refresh_token']));
        $this->assertSame(401, $replay['status']);
        $this->assertSame('invalid_refresh_token', $replay['body']['error']['code'] ?? null);
    }
}
