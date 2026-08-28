# Mobile API test suite

PHPUnit integration tests that exercise the real `api/v1/mobile/*` endpoints
over real HTTP, against the same dev database the app itself runs against -
there is no separate test database or mocking layer, matching how every
endpoint in this app was manually verified throughout development.

## Running

```sh
composer install   # once, installs phpunit/phpunit as a dev-only dependency
vendor/bin/phpunit
```

`tests/bootstrap.php` starts a real `php -S` server (via `tests/router.php`,
which replicates the Apache rewrite rule the app expects in production) once
for the whole run and kills it on exit - no separate server needs to be
running first.

## How fixtures work (`tests/Support/Fixtures.php`)

Every test creates its own disposable branch/class/section/school-year/
staff/student/parent rows directly in the database, then deletes all of them
(plus anything the app itself created along the way - `mobile_memberships`,
`mobile_refresh_tokens`, `mobile_devices`, audit rows) in `tearDown()`. Nothing
here is ever allowed to touch a pre-existing branch, the real demo school, or
a real staff/student/parent record:

- Demo-branch tests create their **own** throwaway `is_demo=1` branch rather
  than logging into the real demo school - the assertion is about whether
  `blockIfDemoReadonly()` blocks writes at all, not about any specific account.
- Test "staff" identities always use a role id computed past whatever the
  real max role id in the database is at the time (`Fixtures::testRoleId()`),
  so granting a permission for a test never changes real permission
  behaviour for a real school's role.
- `mobile_rate_limits` is cleared before every fixture login - the real
  10-requests/minute-per-IP login throttle is correct production behaviour,
  it just isn't compatible with a suite that logs in a dozen times in a few
  seconds from the same IP.

## What's covered

- `Api/AuthFlowTest.php` - bad credentials rejected, a real login issues
  usable tokens, protected endpoints reject a missing/garbage token, a
  rotated refresh token can't be replayed.
- `Api/DemoReadonlyTest.php` - the standing rule that a demo-branch membership
  can read but never write (`Api_Controller::blockIfDemoReadonly()`).
- `Api/TenantIsolationTest.php` - a parent can't read another family's child
  by passing a different `student_id` (IDOR check on
  `resolveOwnedEnrollment()`); a student token always resolves to its own
  enrollment regardless of what's passed.
- `Api/AdmissionApprovalTest.php` - `Admin::approve_admission()`/
  `reject_admission()` (the online-admission maker-checker flow): approval
  creates real student/parent/login rows, rejection creates nothing, a maker
  can't approve their own submission, and a staging row can't be approved
  twice.

This is not exhaustive - it covers the highest-risk invariants (tenant
isolation, the demo-readonly rule, auth, and the newest/least-exercised
approval flow), not every endpoint. Extending it: add a new `Api/*Test.php`
class, build fixtures via `Fixtures`, call the real endpoint via `Http`, and
always clean up in `tearDown()`.
