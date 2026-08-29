<?php
namespace Tests\Support;

/**
 * Creates and tears down isolated, throwaway rows directly against the same
 * dev database the app itself runs against (there is no separate test
 * database - the app has no fixture/seeding story of its own beyond the
 * one-time install migrations). Every row this class creates is tracked and
 * deleted in `tearDown()`, in FK-safe reverse order - nothing here is ever
 * allowed to touch pre-existing branches, students, staff, or the real demo
 * school. Test "staff" roles always use a role id past whatever the real
 * max role id in the database is at the time the test runs (see
 * testRoleId()) so granting `staff_privileges` for a test never changes
 * permission behaviour for a real school's role.
 */
final class Fixtures
{
    private static $mysqli;
    /** @var array<int, array{0:string,1:int}> */
    private $created = array();

    public static function db()
    {
        if (!self::$mysqli) {
            $root = dirname(__DIR__, 2);
            if (!defined('BASEPATH')) define('BASEPATH', $root . '/system/');
            if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'development');
            $config = null;
            $db = null;
            require $root . '/application/config/database.php';
            $c = $db['default'];
            self::$mysqli = new \mysqli($c['hostname'], $c['username'], $c['password'], $c['database']);
            self::$mysqli->set_charset('utf8');
        }
        return self::$mysqli;
    }

    public function randomSuffix()
    {
        return bin2hex(random_bytes(4));
    }

    private function insert($table, array $data)
    {
        $db = self::db();
        $cols = array_keys($data);
        $values = array_map(function ($value) use ($db) {
            if ($value === null) return 'NULL';
            if (is_int($value) || is_float($value)) return $value;
            return "'" . $db->real_escape_string((string)$value) . "'";
        }, array_values($data));
        $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', $values) . ')';
        if (!$db->query($sql)) {
            throw new \RuntimeException("Fixture insert into $table failed: {$db->error}\n$sql");
        }
        $id = (int)$db->insert_id;
        $this->created[] = array($table, $id);
        return $id;
    }

    public function createBranch(array $overrides = array())
    {
        $suffix = $this->randomSuffix();
        return $this->insert('branch', array_merge(array(
            'name' => 'Test Branch ' . $suffix,
            'school_name' => 'Test School ' . $suffix,
            'email' => 'branch-' . $suffix . '@example.test',
            'mobileno' => '08000000000',
            'currency' => 'NGN', 'symbol' => 'N', 'currency_formats' => 1, 'symbol_position' => 1,
            'city' => 'Lagos', 'state' => 'Lagos', 'address' => 'Test address',
            'stu_generate' => 1, 'stu_username_prefix' => 'stu' . $suffix, 'stu_default_password' => 'TestPass123',
            'grd_generate' => 1, 'grd_username_prefix' => 'grd' . $suffix, 'grd_default_password' => 'TestPass123',
            'timezone' => 'Africa/Lagos', 'reg_prefix_digit' => 4,
            'status' => 1, 'is_demo' => 0,
        ), $overrides));
    }

    public function createSchoolYear()
    {
        $suffix = $this->randomSuffix();
        return $this->insert('schoolyear', array('school_year' => '20xx-' . $suffix, 'created_by' => 1));
    }

    public function createClass($branchId)
    {
        $suffix = $this->randomSuffix();
        return $this->insert('class', array('name' => 'Test Class ' . $suffix, 'name_numeric' => '1', 'branch_id' => $branchId));
    }

    public function createSection($branchId)
    {
        $suffix = $this->randomSuffix();
        return $this->insert('section', array('name' => 'Test Section ' . $suffix, 'branch_id' => $branchId));
    }

    private static $testRoleId;

    /**
     * `login_credential.role` is a signed TINYINT (max 127) - there's no id
     * "far away" from real usage the way there would be with a normal int
     * column, so this picks one past whatever the real max role id in the
     * database happens to be right now, computed once per test run and
     * reused by every fixture (cleaned up between tests, so reuse across
     * tests is safe). See class docblock for why this matters.
     */
    public static function testRoleId()
    {
        if (self::$testRoleId === null) {
            $db = self::db();
            $a = (int)$db->query('SELECT MAX(role) as m FROM login_credential')->fetch_assoc()['m'];
            $b = (int)$db->query('SELECT MAX(role_id) as m FROM staff_privileges')->fetch_assoc()['m'];
            self::$testRoleId = max($a, $b) + 1;
        }
        return self::$testRoleId;
    }

    /** A staff identity using a role_id past any real role currently in use - see testRoleId(). */
    public function createStaff($branchId, $password, $roleId = null)
    {
        if ($roleId === null) $roleId = self::testRoleId();
        $suffix = $this->randomSuffix();
        $staffId = $this->insert('staff', array(
            'staff_id' => 'T' . $suffix, 'name' => 'Test Staff ' . $suffix, 'department' => 0,
            'qualification' => 'n/a', 'designation' => 0, 'joining_date' => date('Y-m-d'),
            'birthday' => '1990-01-01', 'sex' => 'male', 'religion' => 'n/a', 'blood_group' => 'n/a',
            'present_address' => 'n/a', 'permanent_address' => 'n/a', 'mobileno' => '08000000000',
            'email' => 'staff-' . $suffix . '@example.test', 'branch_id' => $branchId,
        ));
        $username = 'teststaff_' . $suffix;
        $credentialId = $this->insert('login_credential', array(
            'user_id' => $staffId, 'username' => $username, 'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $roleId, 'active' => 1,
        ));
        return array('staff_id' => $staffId, 'login_credential_id' => $credentialId, 'username' => $username, 'password' => $password, 'role_id' => $roleId);
    }

    /** Assigns a staff member as homeroom teacher for a class/section (teacher_allocation) - role_id=3 (Teacher) is a real system role, but this only grants a literal role check in Attendance.php, never a staff_privileges row, so it's safe to use directly (see createStaff()). */
    public function createTeacherAllocation($branchId, $teacherId, $classId, $sectionId, $sessionId)
    {
        return $this->insert('teacher_allocation', array(
            'class_id' => $classId, 'section_id' => $sectionId, 'teacher_id' => $teacherId,
            'session_id' => $sessionId, 'branch_id' => $branchId,
        ));
    }

    /** A login (role 6 = parent) for an existing `parent` row - tracked/cleaned up like every other fixture. */
    public function createParentLogin($parentId, $password = 'TestPass123')
    {
        $suffix = $this->randomSuffix();
        $username = 'testparent_' . $suffix;
        $credentialId = $this->insert('login_credential', array(
            'user_id' => $parentId, 'username' => $username, 'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 6, 'active' => 1,
        ));
        return array('login_credential_id' => $credentialId, 'username' => $username, 'password' => $password);
    }

    /** Grants a permission to a *test-only* role_id (must be testRoleId() or higher) - never call with a real system role id. */
    public function grantPermission($roleId, $permissionPrefix, array $can = array('is_view' => 1, 'is_add' => 1))
    {
        if ($roleId < self::testRoleId()) throw new \InvalidArgumentException('Refusing to grant staff_privileges to a real system role_id.');
        $db = self::db();
        $permission = $db->query("SELECT id FROM permission WHERE prefix = '" . $db->real_escape_string($permissionPrefix) . "' LIMIT 1")->fetch_assoc();
        if (!$permission) throw new \RuntimeException("No permission row for prefix $permissionPrefix");
        $this->insert('staff_privileges', array_merge(array(
            'role_id' => $roleId, 'permission_id' => (int)$permission['id'],
            'is_add' => 0, 'is_edit' => 0, 'is_view' => 0, 'is_delete' => 0,
        ), $can));
    }

    public function createParent($branchId)
    {
        $suffix = $this->randomSuffix();
        return $this->insert('parent', array(
            'name' => 'Test Parent ' . $suffix, 'relation' => 'father', 'email' => 'parent-' . $suffix . '@example.test',
            'mobileno' => '08000000000', 'branch_id' => $branchId, 'active' => 1,
        ));
    }

    /** Student + enroll (+ optional linked parent/login credentials), all on one branch/class/section/year. */
    public function createStudent($branchId, $classId, $sectionId, $yearId, array $options = array())
    {
        $suffix = $this->randomSuffix();
        $parentId = $options['parent_id'] ?? null;
        $studentId = $this->insert('student', array(
            'register_no' => 'REG' . $suffix, 'first_name' => 'Test', 'last_name' => 'Student ' . $suffix,
            'gender' => 'male', 'parent_id' => $parentId, 'email' => 'student-' . $suffix . '@example.test',
            'active' => 1,
        ));
        $this->insert('enroll', array(
            'student_id' => $studentId, 'class_id' => $classId, 'section_id' => $sectionId,
            'roll' => 1, 'session_id' => $yearId, 'branch_id' => $branchId,
        ));

        $result = array('student_id' => $studentId);
        if (!empty($options['with_login'])) {
            $username = 'teststudent_' . $suffix;
            $password = $options['password'] ?? 'TestPass123';
            $credentialId = $this->insert('login_credential', array(
                'user_id' => $studentId, 'username' => $username, 'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 7, 'active' => 1,
            ));
            $result['login_credential_id'] = $credentialId;
            $result['username'] = $username;
            $result['password'] = $password;
        }
        return $result;
    }

    public function createBookCategory($branchId)
    {
        $suffix = $this->randomSuffix();
        return $this->insert('book_category', array('name' => 'Test Category ' . $suffix, 'branch_id' => $branchId));
    }

    public function createBook($branchId, $categoryId, array $overrides = array())
    {
        $suffix = $this->randomSuffix();
        return $this->insert('book', array_merge(array(
            'title' => 'Test Book ' . $suffix, 'author' => 'Test Author', 'isbn_no' => 'ISBN' . $suffix,
            'category_id' => $categoryId, 'publisher' => 'Test Publisher', 'edition' => '1st',
            'purchase_date' => date('Y-m-d'), 'description' => 'A test book.', 'price' => 10,
            'total_stock' => 5, 'issued_copies' => 0, 'branch_id' => $branchId,
        ), $overrides));
    }

    public function createSubject($branchId)
    {
        $suffix = $this->randomSuffix();
        return $this->insert('subject', array(
            'name' => 'Test Subject ' . $suffix, 'subject_code' => 'SUB' . $suffix,
            'subject_type' => 'theory', 'subject_author' => 'n/a', 'branch_id' => $branchId,
        ));
    }

    public function createExamHall($branchId)
    {
        $suffix = $this->randomSuffix();
        return $this->insert('exam_hall', array('hall_no' => 'Hall ' . $suffix, 'seats' => 30, 'branch_id' => $branchId));
    }

    public function createExam($branchId, $sessionId)
    {
        $suffix = $this->randomSuffix();
        return $this->insert('exam', array(
            'name' => 'Test Exam ' . $suffix, 'branch_id' => $branchId, 'session_id' => $sessionId,
            'type_id' => 0, 'remark' => '', 'mark_distribution' => '',
        ));
    }

    /** A `timetable_exam` row - the exam-date/time/hall schedule for one class+section+subject. */
    public function createExamSchedule($branchId, $sessionId, $examId, $classId, $sectionId, $subjectId, $hallId, array $overrides = array())
    {
        return $this->insert('timetable_exam', array_merge(array(
            'exam_id' => $examId, 'class_id' => $classId, 'section_id' => $sectionId, 'subject_id' => $subjectId,
            'time_start' => '09:00', 'time_end' => '11:00', 'hall_id' => $hallId,
            'exam_date' => date('Y-m-d', strtotime('+3 days')), 'mark_distribution' => '',
            'branch_id' => $branchId, 'session_id' => $sessionId,
        ), $overrides));
    }

    public function createHomework($branchId, $classId, $sectionId, $sessionId, $subjectId, $teacherId, array $overrides = array())
    {
        $suffix = $this->randomSuffix();
        return $this->insert('homework', array_merge(array(
            'class_id' => $classId, 'section_id' => $sectionId, 'session_id' => $sessionId, 'subject_id' => $subjectId,
            'date_of_homework' => date('Y-m-d'), 'date_of_submission' => date('Y-m-d', strtotime('+3 days')),
            'description' => 'Test homework ' . $suffix, 'created_by' => $teacherId, 'create_date' => date('Y-m-d'),
            'status' => '0', 'sms_notification' => 0, 'document' => '', 'evaluated_by' => 0, 'branch_id' => $branchId,
        ), $overrides));
    }

    public function createOnlineAdmissionStaging($branchId, $stagedByUserId, array $payload)
    {
        $suffix = $this->randomSuffix();
        $admissionId = $this->insert('online_admission', array(
            'reference_no' => 'ADM' . $suffix, 'first_name' => $payload['first_name'], 'last_name' => $payload['last_name'] ?? '',
            'payment_amount' => 0, 'payment_details' => '', 'branch_id' => $branchId,
            'class_id' => $payload['class_id'], 'section_id' => $payload['section_id'] ?? null,
            'apply_date' => date('Y-m-d H:i:s'), 'status' => 4,
        ));
        $stagingId = $this->insert('online_admission_staging', array(
            'online_admission_id' => $admissionId, 'branch_id' => $branchId, 'staged_by' => $stagedByUserId,
            'staged_payload' => json_encode($payload), 'status' => 1, 'staged_at' => date('Y-m-d H:i:s'),
        ));
        return array('online_admission_id' => $admissionId, 'staging_id' => $stagingId);
    }

    /**
     * Api_Controller::rateLimit() throttles auth/login to 10 requests/minute
     * per IP - a real, correct production control, but this suite's own
     * repeated logins from 127.0.0.1 trip it long before any real client
     * would. Clearing the counter (keyed only by IP+URI+minute-bucket, no
     * tenant data involved) before every fixture login keeps that control
     * intact for real traffic while not artificially failing the tests
     * exercising other behaviour.
     */
    private function resetLoginRateLimit()
    {
        self::db()->query('DELETE FROM mobile_rate_limits');
    }

    /** Logs a member in via the real endpoint and returns [membership_id, access_token, refresh_token]. */
    public function login($username, $password)
    {
        $this->resetLoginRateLimit();
        $response = Http::post('auth/login', array('username' => $username, 'password' => $password, 'platform' => 'test'));
        if ($response['status'] !== 200 || empty($response['body']['data']['tokens']['access_token'])) {
            throw new \RuntimeException('Fixture login failed: ' . json_encode($response));
        }
        return array(
            'access_token' => $response['body']['data']['tokens']['access_token'],
            'refresh_token' => $response['body']['data']['tokens']['refresh_token'],
            'membership' => $response['body']['data']['membership'],
        );
    }

    /**
     * mobile_memberships/mobile_refresh_tokens/mobile_devices/mobile_audit_log
     * rows are created by the app itself during login/token issuance/logging,
     * not by this class's own insert() calls, so they aren't in $this->created
     * and need their own sweep before the branch (and everything FK-tied to
     * it) is deleted. Safe to call multiple times; call before cleanup().
     */
    public function cleanupMobileAuthForBranch($branchId)
    {
        $db = self::db();
        $branchId = (int)$branchId;
        $membershipIds = array();
        $result = $db->query('SELECT id FROM mobile_memberships WHERE branch_id = ' . $branchId);
        while ($row = $result->fetch_assoc()) $membershipIds[] = (int)$row['id'];
        if ($membershipIds) {
            $inList = implode(',', $membershipIds);
            $db->query('DELETE FROM mobile_refresh_tokens WHERE membership_id IN (' . $inList . ')');
            $db->query('DELETE FROM mobile_devices WHERE membership_id IN (' . $inList . ')');
            $db->query('DELETE FROM mobile_notification_inbox WHERE membership_id IN (' . $inList . ')');
            $db->query('DELETE FROM mobile_notification_preferences WHERE membership_id IN (' . $inList . ')');
        }
        $db->query('DELETE FROM mobile_memberships WHERE branch_id = ' . $branchId);
        $db->query('DELETE FROM mobile_audit_log WHERE branch_id = ' . $branchId);
    }

    /** Deletes every row this instance created, in reverse-insertion (FK-safe) order. Safe to call multiple times. */
    public function cleanup()
    {
        $db = self::db();
        while ($row = array_pop($this->created)) {
            list($table, $id) = $row;
            $db->query('DELETE FROM `' . $table . '` WHERE id = ' . (int)$id);
        }
    }
}
