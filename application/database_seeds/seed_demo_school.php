<?php
/**
 * Demo school seeder for SchoolEdge (schooledge.ng).
 * Safely re-runnable: deletes and recreates the "SchoolEdge Demo Academy" branch and all its data.
 * Powers the public demo page at Saas_website::demo() (base_url('saas_website/demo')).
 *
 * Run from the command line: php application/database_seeds/seed_demo_school.php
 */

define('BASEPATH', 'x'); // satisfy the helper file's direct-access guard; we only call DB-free functions from it
define('ENVIRONMENT', 'production'); // satisfy config/database.php's reference to this constant
require __DIR__ . '/../helpers/general_helper.php';
require __DIR__ . '/../config/database.php'; // defines $db['default'] with the real (gitignored) credentials — never hardcode them here

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$dbConfig = $db['default'];
$m = new mysqli($dbConfig['hostname'], $dbConfig['username'], $dbConfig['password'], $dbConfig['database']);
$m->set_charset('utf8');

$BRANCH_NAME = 'SchoolEdge Demo Academy';
$SESSION_ID = 4; // confirmed active session, school_year "2025-2026"
$SCHOOL_YEAR = '2025-2026';
$DEMO_PASSWORD = 'Demo@2026';
mt_srand(42); // reproducible demo data across resets

function esc($m, $v) { return $m->real_escape_string($v); }

// ---------------------------------------------------------------------
// STEP 0: tear down any previous demo branch (idempotent reset)
// ---------------------------------------------------------------------
function resetDemoBranch($m, $branchName)
{
    $res = $m->query("SELECT id FROM branch WHERE name = '" . esc($m, $branchName) . "'");
    if ($res->num_rows === 0) {
        echo "No existing demo branch found, nothing to reset.\n";
        return;
    }
    $branchId = $res->fetch_assoc()['id'];
    echo "Resetting existing demo branch id=$branchId...\n";

    $studentIds = [];
    $res = $m->query("SELECT DISTINCT student_id FROM enroll WHERE branch_id = $branchId");
    while ($r = $res->fetch_assoc()) { $studentIds[] = $r['student_id']; }

    $staffIds = [];
    $res = $m->query("SELECT id FROM staff WHERE branch_id = $branchId");
    while ($r = $res->fetch_assoc()) { $staffIds[] = $r['id']; }

    $parentIds = [];
    $res = $m->query("SELECT id FROM parent WHERE branch_id = $branchId");
    while ($r = $res->fetch_assoc()) { $parentIds[] = $r['id']; }

    if ($studentIds) { $m->query("DELETE FROM login_credential WHERE role = 7 AND user_id IN (" . implode(',', $studentIds) . ")"); }
    if ($staffIds) { $m->query("DELETE FROM login_credential WHERE role IN (2,3,4,5,8) AND user_id IN (" . implode(',', $staffIds) . ")"); }
    if ($parentIds) { $m->query("DELETE FROM login_credential WHERE role = 6 AND user_id IN (" . implode(',', $parentIds) . ")"); }

    $m->query("DELETE FROM homework_evaluation WHERE homework_id IN (SELECT id FROM homework WHERE branch_id = $branchId)");
    $m->query("DELETE FROM homework_submit WHERE homework_id IN (SELECT id FROM homework WHERE branch_id = $branchId)");
    $m->query("DELETE FROM fee_payment_history WHERE allocation_id IN (SELECT id FROM fee_allocation WHERE branch_id = $branchId)");
    $m->query("DELETE FROM fee_groups_details WHERE fee_groups_id IN (SELECT id FROM fee_groups WHERE branch_id = $branchId)");
    foreach (['mark', 'psychomotor_rating', 'timetable_exam', 'timetable_class', 'exam', 'exam_term', 'exam_mark_distribution', 'grade', 'exam_hall', 'subject_assign', 'subject', 'student_attendance', 'enroll', 'homework', 'book_issues', 'book', 'book_category', 'event', 'event_types', 'fee_allocation', 'fee_groups', 'fees_type', 'hostel_room', 'hostel', 'hostel_category', 'transport_assign', 'transport_stoppage_point', 'transport_stoppage', 'transport_route', 'transport_vehicle'] as $t) {
        $m->query("DELETE FROM `$t` WHERE branch_id = $branchId");
    }
    $m->query("DELETE FROM sections_allocation WHERE class_id IN (SELECT id FROM class WHERE branch_id = $branchId)");
    foreach (['class', 'section', 'student_category', 'staff_department', 'staff_designation', 'card_templete', 'certificates_templete', 'marksheet_template'] as $t) {
        $m->query("DELETE FROM `$t` WHERE branch_id = $branchId");
    }
    if ($studentIds) { $m->query("DELETE FROM student WHERE id IN (" . implode(',', $studentIds) . ")"); }
    if ($parentIds) { $m->query("DELETE FROM parent WHERE id IN (" . implode(',', $parentIds) . ")"); }
    if ($staffIds) { $m->query("DELETE FROM staff WHERE id IN (" . implode(',', $staffIds) . ")"); }
    $m->query("DELETE FROM branch WHERE id = $branchId");
    echo "Reset complete.\n";
}
resetDemoBranch($m, $BRANCH_NAME);

// ---------------------------------------------------------------------
// STEP 1: branch
// ---------------------------------------------------------------------
$m->query("INSERT INTO branch
    (name, school_name, email, mobileno, currency, symbol, currency_formats, symbol_position, city, state, address,
     stu_username_prefix, stu_default_password, grd_username_prefix, grd_default_password, reg_prefix_digit,
     translation, timezone, weekends, student_login, parent_login, teacher_mobile_visible, teacher_email_visible, status, unique_roll, is_demo)
    VALUES
    ('$BRANCH_NAME', '$BRANCH_NAME', 'demo@schooledge.ng', '08000000000', 'NGN', '₦', 1, 1, 'Lagos', 'Lagos',
     '15 Demo Close, Lekki Phase 1, Lagos', 'DEMO', 'Demo@2026', 'DEMOP', 'Demo@2026', 4,
     'english', 'Africa/Lagos', '1', 1, 1, 1, 1, 1, 1, 1)");
$branchId = $m->insert_id;
echo "Created branch id=$branchId\n";

// ---------------------------------------------------------------------
// STEP 2: student category, staff department/designation, exam hall
// ---------------------------------------------------------------------
$m->query("INSERT INTO student_category (branch_id, name) VALUES ($branchId, 'General')");
$categoryId = $m->insert_id;

$m->query("INSERT INTO staff_department (name, branch_id) VALUES ('Academic', $branchId)");
$deptAcademic = $m->insert_id;
$m->query("INSERT INTO staff_department (name, branch_id) VALUES ('Administration', $branchId)");
$deptAdmin = $m->insert_id;

$designations = [];
foreach (['Class Teacher', 'Subject Teacher', 'Accountant', 'Librarian', 'Receptionist', 'Head Teacher'] as $name) {
    $m->query("INSERT INTO staff_designation (name, branch_id) VALUES ('" . esc($m, $name) . "', $branchId)");
    $designations[$name] = $m->insert_id;
}

$m->query("INSERT INTO exam_hall (hall_no, seats, branch_id) VALUES ('Hall A', 60, $branchId)");
$hallId = $m->insert_id;

// ---------------------------------------------------------------------
// STEP 3: classes + one shared section "A" + allocation
// ---------------------------------------------------------------------
$m->query("INSERT INTO section (name, capacity, branch_id) VALUES ('A', '40', $branchId)");
$sectionId = $m->insert_id;

$classIds = [];
foreach (['JSS 1' => 1, 'JSS 2' => 2, 'JSS 3' => 3] as $name => $numeric) {
    $m->query("INSERT INTO class (name, name_numeric, branch_id) VALUES ('" . esc($m, $name) . "', '$numeric', $branchId)");
    $classIds[$name] = $m->insert_id;
    $m->query("INSERT INTO sections_allocation (class_id, section_id) VALUES ({$classIds[$name]}, $sectionId)");
}
echo "Created 3 classes, 1 section, allocations.\n";

// ---------------------------------------------------------------------
// STEP 4: subjects + assignment (teacher assigned after teachers are created, see below)
// ---------------------------------------------------------------------
$subjectDefs = [
    ['Mathematics', 'MTH'], ['English Language', 'ENG'], ['Basic Science', 'BSC'],
    ['Social Studies', 'SOS'], ['Basic Technology', 'BTC'], ['Business Studies', 'BUS'],
    ['Civic Education', 'CIV'],
];
$subjectIds = [];
foreach ($subjectDefs as $s) {
    list($name, $code) = $s;
    $m->query("INSERT INTO subject (name, subject_code, subject_type, subject_author, branch_id)
        VALUES ('" . esc($m, $name) . "', '$code', 'Theory', 'SchoolEdge Demo', $branchId)");
    $subjectIds[$name] = $m->insert_id;
}
echo "Created " . count($subjectIds) . " subjects.\n";

// ---------------------------------------------------------------------
// STEP 5: people - names
// ---------------------------------------------------------------------
$maleFirst = ['Chidera', 'Emeka', 'Tunde', 'Chukwuemeka', 'Segun', 'Kelechi', 'Femi', 'Obinna', 'Ayodele', 'Ikenna', 'Damilare', 'Uche', 'Adewale', 'Chinedu', 'Yakubu'];
$femaleFirst = ['Amaka', 'Bisi', 'Ngozi', 'Yetunde', 'Chioma', 'Aisha', 'Blessing', 'Funke', 'Nkechi', 'Halima', 'Temitope', 'Ijeoma', 'Adaeze', 'Zainab', 'Folake'];
$surnames = ['Okafor', 'Adeyemi', 'Balogun', 'Chukwu', 'Okonkwo', 'Eze', 'Bello', 'Musa', 'Abubakar', 'Nwosu', 'Afolabi', 'Adewale', 'Ibrahim', 'Yusuf', 'Okoro', 'Ogunleye', 'Adebayo', 'Danjuma', 'Uche', 'Nnamdi', 'Suleiman', 'Okeke', 'Adigun', 'Mohammed', 'Onyekwere'];
$bloodGroups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+'];
$religions = ['Christianity', 'Islam'];

function pick($arr) { return $arr[array_rand($arr)]; }
function randomPhone() { return '0' . mt_rand(700, 909) . mt_rand(1000000, 9999999); }
function randomDate($minYearsAgo, $maxYearsAgo) {
    $y = date('Y') - mt_rand($minYearsAgo, $maxYearsAgo);
    return sprintf('%04d-%02d-%02d', $y, mt_rand(1, 12), mt_rand(1, 28));
}

function createLogin($m, $userId, $username, $role, $password)
{
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $m->prepare("INSERT INTO login_credential (user_id, username, password, role, active) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param('issi', $userId, $username, $hash, $role);
    $stmt->execute();
}

// ---------------------------------------------------------------------
// STEP 6: staff - 1 admin, 5 teachers, 5 non-teaching
// ---------------------------------------------------------------------
$teacherIds = [];

function insertStaff($m, $branchId, $deptId, $designationId, $name, $sex, $bloodGroups, $religions)
{
    $staffId = 'STF-' . mt_rand(1000, 9999);
    $email = strtolower(str_replace(' ', '.', $name)) . mt_rand(1, 999) . '@example.com';
    $joining = randomDate(1, 6);
    $birthday = randomDate(25, 50);
    $address = 'Lagos, Nigeria';
    $mobile = randomPhone();
    $religion = pick($religions);
    $bg = pick($bloodGroups);
    $sql = "INSERT INTO staff
        (staff_id, name, department, qualification, designation, joining_date, birthday, sex, religion, blood_group,
         present_address, permanent_address, mobileno, email, branch_id)
        VALUES ('" . esc($m, $staffId) . "', '" . esc($m, $name) . "', $deptId, 'B.Ed', $designationId,
         '$joining', '$birthday', '" . esc($m, $sex) . "', '" . esc($m, $religion) . "', '$bg',
         '$address', '$address', '$mobile', '" . esc($m, $email) . "', $branchId)";
    $m->query($sql);
    return $m->insert_id;
}

// admin
$adminName = pick($maleFirst) . ' ' . pick($surnames);
$stmt = $m->prepare("INSERT INTO staff
    (staff_id, name, department, qualification, designation, joining_date, birthday, sex, religion, blood_group,
     present_address, permanent_address, mobileno, email, branch_id)
    VALUES (?, ?, ?, 'M.Ed', ?, ?, ?, 'Male', ?, ?, 'Lagos, Nigeria', 'Lagos, Nigeria', ?, ?, ?)");
$staffIdStr = 'STF-' . mt_rand(1000, 9999);
$joining = randomDate(2, 5);
$birthday = randomDate(35, 55);
$religion = pick($religions);
$bg = pick($bloodGroups);
$mobile = randomPhone();
$adminEmail = 'demo.admin@schooledge.ng';
$stmt->bind_param('ssisssssssi', $staffIdStr, $adminName, $deptAdmin, $designations['Head Teacher'], $joining, $birthday, $religion, $bg, $mobile, $adminEmail, $branchId);
$stmt->execute();
$adminStaffId = $m->insert_id;
createLogin($m, $adminStaffId, $adminEmail, 2, $DEMO_PASSWORD);
echo "Created admin: $adminName ($adminEmail)\n";

// 5 teachers
$teacherEmails = [];
for ($i = 0; $i < 5; $i++) {
    $sex = ($i % 2 === 0) ? 'Male' : 'Female';
    $name = ($sex === 'Male' ? pick($maleFirst) : pick($femaleFirst)) . ' ' . pick($surnames);
    $tid = insertStaff($m, $branchId, $deptAcademic, $designations['Subject Teacher'], $name, $sex, $bloodGroups, $religions);
    $teacherIds[] = $tid;
    $email = 'teacher' . ($i + 1) . '.demo@example.com';
    $m->query("UPDATE staff SET email = '" . esc($m, $email) . "' WHERE id = $tid");
    $teacherEmails[$tid] = $email;
    createLogin($m, $tid, $email, 3, $DEMO_PASSWORD);
    echo "Created teacher: $name ($email)\n";
}

// 5 non-teaching staff (mixed roles)
$nonTeachingDesignations = ['Accountant', 'Accountant', 'Librarian', 'Receptionist', 'Receptionist'];
$nonTeachingRoles = [4, 4, 5, 8, 8];
for ($i = 0; $i < 5; $i++) {
    $sex = ($i % 2 === 0) ? 'Female' : 'Male';
    $name = ($sex === 'Male' ? pick($maleFirst) : pick($femaleFirst)) . ' ' . pick($surnames);
    $sid = insertStaff($m, $branchId, $deptAdmin, $designations[$nonTeachingDesignations[$i]], $name, $sex, $bloodGroups, $religions);
    $email = 'staff' . ($i + 1) . '.demo@example.com';
    $m->query("UPDATE staff SET email = '" . esc($m, $email) . "' WHERE id = $sid");
    createLogin($m, $sid, $email, $nonTeachingRoles[$i], $DEMO_PASSWORD);
    echo "Created staff: $name ($email) role={$nonTeachingRoles[$i]}\n";
}

// now that teachers exist, assign one per subject (cycling) to each class
$classSubjectTeacher = [];
foreach ($classIds as $className => $cid) {
    $ti = 0;
    foreach ($subjectIds as $sName => $subId) {
        $teacherId = $teacherIds[$ti % count($teacherIds)];
        $m->query("INSERT INTO subject_assign (class_id, section_id, subject_id, teacher_id, branch_id, session_id)
            VALUES ($cid, $sectionId, $subId, $teacherId, $branchId, $SESSION_ID)");
        $classSubjectTeacher[$cid][$subId] = $teacherId;
        $ti++;
    }
}
echo "Assigned subjects to all classes.\n";

// ---------------------------------------------------------------------
// STEP 6b: weekly class timetable (Monday-Friday, 7 periods + 1 mid-morning break)
// ---------------------------------------------------------------------
$periods = [
    ['08:00:00', '08:40:00', false],
    ['08:40:00', '09:20:00', false],
    ['09:20:00', '10:00:00', false],
    ['10:00:00', '10:20:00', true], // break
    ['10:20:00', '11:00:00', false],
    ['11:00:00', '11:40:00', false],
    ['11:40:00', '12:20:00', false],
    ['12:20:00', '13:00:00', false],
];
$weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
$subjectIdList = array_values($subjectIds);
$timetableRows = 0;
foreach ($classIds as $className => $cid) {
    $subjectCursor = 0;
    foreach ($weekDays as $day) {
        foreach ($periods as $period) {
            list($start, $end, $isBreak) = $period;
            if ($isBreak) {
                $m->query("INSERT INTO timetable_class (class_id, section_id, `break`, subject_id, teacher_id, class_room, time_start, time_end, day, session_id, branch_id)
                    VALUES ($cid, $sectionId, '1', 0, 0, '', '$start', '$end', '$day', $SESSION_ID, $branchId)");
            } else {
                $subId = $subjectIdList[$subjectCursor % count($subjectIdList)];
                $teacherId = $classSubjectTeacher[$cid][$subId];
                $room = $className . ' Block';
                $m->query("INSERT INTO timetable_class (class_id, section_id, `break`, subject_id, teacher_id, class_room, time_start, time_end, day, session_id, branch_id)
                    VALUES ($cid, $sectionId, '0', $subId, $teacherId, '" . esc($m, $room) . "', '$start', '$end', '$day', $SESSION_ID, $branchId)");
                $subjectCursor++;
            }
            $timetableRows++;
        }
    }
}
echo "Created $timetableRows weekly timetable periods across 3 classes.\n";

// ---------------------------------------------------------------------
// STEP 7: 20 parents + 20 students, spread across the 3 classes (7/7/6), enrolled
// ---------------------------------------------------------------------
$classNames = array_keys($classIds); // JSS 1, JSS 2, JSS 3
$classSplit = [7, 7, 6];
$studentRecords = [];
$rollByClass = [];

$studentIndex = 0;
foreach ($classNames as $ci => $className) {
    $cid = $classIds[$className];
    $rollByClass[$cid] = 1;
    for ($n = 0; $n < $classSplit[$ci]; $n++) {
        $sex = (mt_rand(0, 1) === 0) ? 'male' : 'female';
        $first = ($sex === 'male') ? pick($maleFirst) : pick($femaleFirst);
        $last = pick($surnames);
        $studentName = "$first $last";

        // parent first (so we can link parent_id)
        $fatherName = pick($maleFirst) . ' ' . $last;
        $motherName = pick($femaleFirst) . ' ' . $last;
        $parentName = $fatherName;
        $parentEmail = 'parent' . ($studentIndex + 1) . '.demo@example.com';
        $parentMobile = randomPhone();
        $m->query("INSERT INTO parent (name, relation, father_name, mother_name, occupation, email, mobileno, address, city, state, branch_id, active)
            VALUES ('" . esc($m, $parentName) . "', 'Father', '" . esc($m, $fatherName) . "', '" . esc($m, $motherName) . "',
            'Trader', '" . esc($m, $parentEmail) . "', '$parentMobile', 'Lagos, Nigeria', 'Lagos', 'Lagos', $branchId, 1)");
        $parentId = $m->insert_id;
        createLogin($m, $parentId, $parentEmail, 6, $DEMO_PASSWORD);

        $registerNo = 'DEMO/' . $className[4] . '/' . str_pad($studentIndex + 1, 3, '0', STR_PAD_LEFT);
        $registerNo = str_replace(' ', '', $registerNo);
        $birthday = randomDate(10, 15);
        $admissionDate = randomDate(0, 3);
        $religion = pick($religions);
        $bg = pick($bloodGroups);
        $studentEmail = 'student' . ($studentIndex + 1) . '.demo@example.com';
        $studentMobile = randomPhone();

        $m->query("INSERT INTO student
            (register_no, admission_date, first_name, last_name, gender, birthday, religion, caste, blood_group, mother_tongue,
             current_address, permanent_address, city, state, mobileno, category_id, email, parent_id, active)
            VALUES ('" . esc($m, $registerNo) . "', '$admissionDate', '" . esc($m, $first) . "', '" . esc($m, $last) . "', '$sex',
             '$birthday', '" . esc($m, $religion) . "', 'N/A', '$bg', 'English',
             'Lagos, Nigeria', 'Lagos, Nigeria', 'Lagos', 'Lagos', '$studentMobile', $categoryId, '" . esc($m, $studentEmail) . "', $parentId, 1)");
        $studentId = $m->insert_id;
        createLogin($m, $studentId, $registerNo, 7, $DEMO_PASSWORD);

        $roll = $rollByClass[$cid]++;
        $m->query("INSERT INTO enroll (student_id, class_id, section_id, roll, session_id, branch_id, default_login)
            VALUES ($studentId, $cid, $sectionId, $roll, $SESSION_ID, $branchId, 1)");
        $enrollId = $m->insert_id;

        $studentRecords[] = [
            'studentId' => $studentId, 'enrollId' => $enrollId, 'classId' => $cid, 'className' => $className,
            'name' => $studentName, 'registerNo' => $registerNo, 'studentEmail' => $studentEmail,
        ];
        $studentIndex++;
    }
}
echo "Created " . count($studentRecords) . " students with parents and enrollments.\n";

// pick a representative student for the featured demo login: first student of JSS 2
$demoStudent = null;
foreach ($studentRecords as $sr) { if ($sr['className'] === 'JSS 2') { $demoStudent = $sr; break; } }
if (!$demoStudent) { $demoStudent = $studentRecords[0]; }
echo "Featured demo student: {$demoStudent['name']} ({$demoStudent['registerNo']})\n";

// ---------------------------------------------------------------------
// STEP 8: mark distribution (1st CA / 2nd CA / Exam) + WAEC grade scale
// ---------------------------------------------------------------------
$distIds = [];
foreach (['1st CA', '2nd CA', 'Exam'] as $dName) {
    $m->query("INSERT INTO exam_mark_distribution (name, branch_id) VALUES ('" . esc($m, $dName) . "', $branchId)");
    $distIds[$dName] = $m->insert_id;
}
$distFullMark = ['1st CA' => 20, '2nd CA' => 20, 'Exam' => 60];
$distPassMark = ['1st CA' => 10, '2nd CA' => 10, 'Exam' => 30];
echo "Created mark distribution: " . implode(', ', array_keys($distIds)) . "\n";

$waecGrades = [
    ['A1', 9, 75, 100, 'Excellent'], ['B2', 8, 70, 74, 'Very Good'], ['B3', 7, 65, 69, 'Good'],
    ['C4', 6, 60, 64, 'Credit'], ['C5', 5, 55, 59, 'Credit'], ['C6', 4, 50, 54, 'Credit'],
    ['D7', 3, 45, 49, 'Pass'], ['E8', 2, 40, 44, 'Pass'], ['F9', 1, 0, 39, 'Fail'],
];
foreach ($waecGrades as $g) {
    list($name, $point, $lo, $hi, $remark) = $g;
    $m->query("INSERT INTO grade (name, grade_point, lower_mark, upper_mark, remark, branch_id)
        VALUES ('$name', $point, $lo, $hi, '$remark', $branchId)");
}
echo "Seeded WAEC grading scale.\n";

// ---------------------------------------------------------------------
// STEP 9: 3 terms + 3 exams, timetable_exam per subject/class, marks, ranks, psychomotor, attendance
// ---------------------------------------------------------------------
$termDefs = [
    ['First Term', '2025-09-01', '2026-01-11'],
    ['Second Term', '2026-01-12', '2026-04-19'],
    ['Third Term', '2026-04-20', null],
];
$examIds = [];
$termIds = [];
foreach ($termDefs as $t) {
    list($tName, $startDate, $nextBegins) = $t;
    $nextSql = $nextBegins ? "'$nextBegins'" : 'NULL';
    $m->query("INSERT INTO exam_term (name, next_term_begins, branch_id, session_id) VALUES ('" . esc($m, $tName) . "', $nextSql, $branchId, $SESSION_ID)");
    $termId = $m->insert_id;
    $termIds[$tName] = $termId;

    $examName = "$tName Examination";
    $markDistJson = esc($m, json_encode(array_values($distIds)));
    $m->query("INSERT INTO exam (name, term_id, type_id, session_id, branch_id, remark, mark_distribution, status, publish_result, rank_generated)
        VALUES ('" . esc($m, $examName) . "', $termId, 3, $SESSION_ID, $branchId, '', '$markDistJson', 1, 1, 1)");
    $examIds[$tName] = $m->insert_id;
    echo "Created exam: $examName (id={$examIds[$tName]})\n";
}

// per-subject full/pass mark distribution JSON, shared by every timetable_exam row
$tteMarkDist = [];
foreach ($distIds as $dName => $did) {
    $tteMarkDist[$did] = ['full_mark' => $distFullMark[$dName], 'pass_mark' => $distPassMark[$dName]];
}
$tteMarkDistJson = esc($m, json_encode($tteMarkDist));

// timetable_exam: one row per exam x class x subject (section is shared across classes here)
$examDateCursor = ['First Term' => '2025-12-08', 'Second Term' => '2026-04-06', 'Third Term' => '2026-07-06'];
foreach ($termDefs as $t) {
    $tName = $t[0];
    $examId = $examIds[$tName];
    $dayOffset = 0;
    foreach ($classIds as $className => $cid) {
        foreach ($subjectIds as $sName => $subId) {
            $examDate = date('Y-m-d', strtotime($examDateCursor[$tName] . " +$dayOffset day"));
            $dayOffset++;
            $m->query("INSERT INTO timetable_exam (exam_id, class_id, section_id, subject_id, time_start, time_end, mark_distribution, hall_id, exam_date, branch_id, session_id)
                VALUES ($examId, $cid, $sectionId, $subId, '09:00', '11:00', '$tteMarkDistJson', $hallId, '$examDate', $branchId, $SESSION_ID)");
        }
    }
}
echo "Created timetable_exam rows for all 3 terms x 3 classes x 7 subjects.\n";

// marks: per exam, per student, per subject - with a per-student ability factor for realistic variance
$abilities = [];
foreach ($studentRecords as $sr) { $abilities[$sr['studentId']] = mt_rand(45, 96) / 100; }

foreach ($termDefs as $t) {
    $tName = $t[0];
    $examId = $examIds[$tName];
    $totalsByStudent = [];
    foreach ($studentRecords as $sr) {
        $ability = $abilities[$sr['studentId']];
        $studentTotal = 0;
        foreach ($subjectIds as $sName => $subId) {
            $variance = mt_rand(-8, 8) / 100;
            $effectiveAbility = max(0.15, min(0.99, $ability + $variance));
            $ca1 = (int) round($distFullMark['1st CA'] * min(1, $effectiveAbility + mt_rand(-5, 5) / 100));
            $ca2 = (int) round($distFullMark['2nd CA'] * min(1, $effectiveAbility + mt_rand(-5, 5) / 100));
            $exm = (int) round($distFullMark['Exam'] * min(1, $effectiveAbility + mt_rand(-5, 5) / 100));
            $ca1 = max(0, min($distFullMark['1st CA'], $ca1));
            $ca2 = max(0, min($distFullMark['2nd CA'], $ca2));
            $exm = max(0, min($distFullMark['Exam'], $exm));
            $markJson = esc($m, json_encode([$distIds['1st CA'] => $ca1, $distIds['2nd CA'] => $ca2, $distIds['Exam'] => $exm]));
            $m->query("INSERT INTO mark (student_id, subject_id, class_id, section_id, exam_id, mark, absent, session_id, branch_id)
                VALUES ({$sr['studentId']}, $subId, {$sr['classId']}, $sectionId, $examId, '$markJson', '', $SESSION_ID, $branchId)");
            $studentTotal += $ca1 + $ca2 + $exm;
        }
        $totalsByStudent[$sr['studentId']] = $studentTotal;
    }

    // rank within each class separately
    foreach ($classIds as $className => $cid) {
        $classStudents = array_filter($studentRecords, function ($sr) use ($cid) { return $sr['classId'] === $cid; });
        usort($classStudents, function ($a, $b) use ($totalsByStudent) {
            return $totalsByStudent[$b['studentId']] <=> $totalsByStudent[$a['studentId']];
        });
        $rank = 1;
        foreach ($classStudents as $sr) {
            $m->query("INSERT INTO exam_rank (exam_id, enroll_id, `rank`, teacher_comments, principal_comments)
                VALUES ($examId, {$sr['enrollId']}, $rank, 'A pleasure to teach, keeps improving.', 'Well done, keep it up.')");
            $rank++;
        }
    }
    echo "Marks + ranks done for $tName.\n";
}

// psychomotor ratings - First Term only
$traits = array_keys(psychomotor_traits());
foreach ($studentRecords as $sr) {
    foreach ($traits as $traitKey) {
        $rating = mt_rand(2, 4);
        $m->query("INSERT INTO psychomotor_rating (branch_id, session_id, exam_id, student_id, enroll_id, trait_key, rating, created_at, updated_at)
            VALUES ($branchId, $SESSION_ID, {$examIds['First Term']}, {$sr['studentId']}, {$sr['enrollId']}, '$traitKey', $rating, NOW(), NOW())");
    }
}
echo "Seeded psychomotor ratings for First Term.\n";

// attendance - modest realistic set dated in 2025 (matches schoolyear "2025-2026" -> year(date) grouping)
foreach ($studentRecords as $sr) {
    $day = 0;
    for ($i = 0; $i < 45; $i++) {
        $date = date('Y-m-d', strtotime("2025-09-08 +$day day"));
        $day++;
        $status = (mt_rand(1, 100) <= 92) ? 'P' : 'A';
        $m->query("INSERT INTO student_attendance (enroll_id, date, status, branch_id) VALUES ({$sr['enrollId']}, '$date', '$status', $branchId)");
    }
}
echo "Seeded attendance records.\n";

// ---------------------------------------------------------------------
// STEP 10: document templates, built from this session's starter_templates()
// ---------------------------------------------------------------------
$starters = starter_templates();

$idStudent = $starters['id_card_student'];
$m->query("INSERT INTO card_templete
    (card_type, name, design_style, user_type, content, layout_width, layout_height, photo_style, photo_size,
     top_space, bottom_space, right_space, left_space, qr_code, branch_id)
    VALUES (1, 'Student ID Card', '{$idStudent['design_style']}', {$idStudent['user_type']}, '" . esc($m, $idStudent['content']) . "',
     {$idStudent['layout_width']}, {$idStudent['layout_height']}, {$idStudent['photo_style']}, {$idStudent['photo_size']},
     {$idStudent['spacing'][0]}, {$idStudent['spacing'][1]}, {$idStudent['spacing'][2]}, {$idStudent['spacing'][3]}, 'register_no', $branchId)");
$studentIdCardTempId = $m->insert_id;

$idEmployee = $starters['id_card_employee'];
$m->query("INSERT INTO card_templete
    (card_type, name, design_style, user_type, content, layout_width, layout_height, photo_style, photo_size,
     top_space, bottom_space, right_space, left_space, qr_code, branch_id)
    VALUES (1, 'Employee ID Card', '{$idEmployee['design_style']}', {$idEmployee['user_type']}, '" . esc($m, $idEmployee['content']) . "',
     {$idEmployee['layout_width']}, {$idEmployee['layout_height']}, {$idEmployee['photo_style']}, {$idEmployee['photo_size']},
     {$idEmployee['spacing'][0]}, {$idEmployee['spacing'][1]}, {$idEmployee['spacing'][2]}, {$idEmployee['spacing'][3]}, 'staff_id', $branchId)");

$admit = $starters['admit_card'];
$m->query("INSERT INTO card_templete
    (card_type, name, design_style, user_type, content, layout_width, layout_height, photo_style, photo_size,
     top_space, bottom_space, right_space, left_space, qr_code, branch_id)
    VALUES (2, 'Examination Admit Card', '{$admit['design_style']}', {$admit['user_type']}, '" . esc($m, $admit['content']) . "',
     {$admit['layout_width']}, {$admit['layout_height']}, {$admit['photo_style']}, {$admit['photo_size']},
     {$admit['spacing'][0]}, {$admit['spacing'][1]}, {$admit['spacing'][2]}, {$admit['spacing'][3]}, 'register_no', $branchId)");
$admitCardTempId = $m->insert_id;

$cert = $starters['certificate'];
$m->query("INSERT INTO certificates_templete
    (name, design_style, user_type, signature, content, page_layout, photo_style, photo_size,
     top_space, bottom_space, right_space, left_space, qr_code, branch_id)
    VALUES ('Certificate of Achievement', '{$cert['design_style']}', {$cert['user_type']}, '', '" . esc($m, $cert['content']) . "',
     {$cert['page_layout']}, {$cert['photo_style']}, {$cert['photo_size']},
     {$cert['spacing'][0]}, {$cert['spacing'][1]}, {$cert['spacing'][2]}, {$cert['spacing'][3]}, '', $branchId)");
$certTempId = $m->insert_id;

$msheet = $starters['marksheet'];
$m->query("INSERT INTO marksheet_template
    (name, design_style, left_signature, header_content, footer_content, attendance_percentage, grading_scale, position, term_position,
     cumulative_average, class_average, result, subject_position, remark, page_layout, photo_style, photo_size,
     top_space, bottom_space, right_space, left_space, branch_id)
    VALUES ('Termly Report Card', '{$msheet['design_style']}', '', '" . esc($m, $msheet['header_content']) . "', '" . esc($m, $msheet['footer_content']) . "',
     1, 1, 1, 1, 1, 1, 1, 1, 1, {$msheet['page_layout']}, {$msheet['photo_style']}, {$msheet['photo_size']},
     {$msheet['spacing'][0]}, {$msheet['spacing'][1]}, {$msheet['spacing'][2]}, {$msheet['spacing'][3]}, $branchId)");
$marksheetTempId = $m->insert_id;

$m->query("UPDATE branch SET default_admitcard_temp = $admitCardTempId, default_marksheet_temp = $marksheetTempId WHERE id = $branchId");
echo "Created templates: Student ID($studentIdCardTempId), Employee ID, Admit Card($admitCardTempId), Certificate($certTempId), Marksheet($marksheetTempId).\n";
echo "Branch default_admitcard_temp/default_marksheet_temp set.\n";

// ---------------------------------------------------------------------
// STEP 11: Library - categories, books, and a mix of active/returned issues
// ---------------------------------------------------------------------
$m->query("INSERT INTO book_category (name, branch_id) VALUES ('Fiction', $branchId)");
$catFiction = $m->insert_id;
$m->query("INSERT INTO book_category (name, branch_id) VALUES ('Science', $branchId)");
$catScience = $m->insert_id;
$m->query("INSERT INTO book_category (name, branch_id) VALUES ('Textbook', $branchId)");
$catTextbook = $m->insert_id;

$bookDefs = [
    ['Things Fall Apart', 'Chinua Achebe', $catFiction, 5],
    ['Half of a Yellow Sun', 'Chimamanda Ngozi Adichie', $catFiction, 4],
    ['New General Mathematics JSS', 'M.F. Macrae', $catTextbook, 10],
    ['Basic Science for Junior Secondary Schools', 'STAN', $catScience, 8],
    ['A First Course in Civic Education', 'NERDC', $catTextbook, 6],
    ['Purple Hibiscus', 'Chimamanda Ngozi Adichie', $catFiction, 3],
];
$bookIds = [];
foreach ($bookDefs as $b) {
    list($title, $author, $catId, $stock) = $b;
    $isbn = '978-' . mt_rand(1000000000, 1999999999);
    $price = 500 + mt_rand(0, 2000);
    $purchaseDate = randomDate(0, 2);
    $m->query("INSERT INTO book (title, author, isbn_no, category_id, publisher, edition, purchase_date, description, price, total_stock, issued_copies, branch_id)
        VALUES ('" . esc($m, $title) . "', '" . esc($m, $author) . "', '$isbn', $catId, 'SchoolEdge Press', '1st', '$purchaseDate', '', $price, $stock, 0, $branchId)");
    $bookIds[] = $m->insert_id;
}
echo "Created " . count($bookIds) . " library books.\n";

$issueSample = array_slice($studentRecords, 0, 10);
foreach ($issueSample as $i => $sr) {
    $bookId = $bookIds[$i % count($bookIds)];
    $daysAgo = mt_rand(3, 25);
    $issueDate = date('Y-m-d', strtotime("-$daysAgo day"));
    $expiryDate = date('Y-m-d', strtotime("$issueDate +14 day"));
    $returned = ($i % 3 != 0);
    if ($returned) {
        $returnDate = date('Y-m-d', strtotime("$issueDate +" . mt_rand(5, 14) . " day"));
        $returnSql = "'$returnDate'";
        $status = 1;
    } else {
        $returnSql = 'NULL';
        $status = 0;
        $m->query("UPDATE book SET issued_copies = issued_copies + 1 WHERE id = $bookId");
    }
    $m->query("INSERT INTO book_issues (book_id, user_id, role_id, date_of_issue, date_of_expiry, return_date, fine_amount, status, issued_by, session_id, branch_id)
        VALUES ($bookId, {$sr['studentId']}, 7, '$issueDate', '$expiryDate', $returnSql, 0.00, $status, '$adminStaffId', $SESSION_ID, $branchId)");
}
echo "Issued " . count($issueSample) . " books to students (mix of active/returned).\n";

// ---------------------------------------------------------------------
// STEP 12: Events - a mix of upcoming and past events
// ---------------------------------------------------------------------
$m->query("INSERT INTO event_types (name, icon, branch_id) VALUES ('Sports', 'fas fa-futbol', $branchId)");
$typeSports = $m->insert_id;
$m->query("INSERT INTO event_types (name, icon, branch_id) VALUES ('Cultural', 'fas fa-masks-theater', $branchId)");
$typeCultural = $m->insert_id;
$m->query("INSERT INTO event_types (name, icon, branch_id) VALUES ('Academic', 'fas fa-graduation-cap', $branchId)");
$typeAcademic = $m->insert_id;

$eventDefs = [
    ['Inter-House Sports Competition', $typeSports, 'A day of athletics and team competitions across all houses.', 20, 20],
    ['Cultural Day Celebration', $typeCultural, "Students showcase Nigeria's diverse cultures through dance, food, and dress.", 35, 35],
    ['Mid-Term Academic Quiz', $typeAcademic, 'Inter-class quiz competition covering all core subjects.', -10, -10],
    ['Career Day', $typeAcademic, 'Guest speakers from various professions share career guidance with students.', 45, 45],
];
foreach ($eventDefs as $e) {
    list($title, $typeId, $remark, $startOffset, $endOffset) = $e;
    $start = date('Y-m-d', strtotime("$startOffset day"));
    $end = date('Y-m-d', strtotime("$endOffset day"));
    $m->query("INSERT INTO event (title, remark, status, type, audition, selected_list, start_date, end_date, image, created_by, session_id, branch_id, show_web)
        VALUES ('" . esc($m, $title) . "', '" . esc($m, $remark) . "', 1, '$typeId', 1, '', '$start', '$end', '', '$adminStaffId', $SESSION_ID, $branchId, 1)");
}
echo "Created " . count($eventDefs) . " events.\n";

// ---------------------------------------------------------------------
// STEP 13: Homework - one live (upcoming) and one past (evaluated) per class
// ---------------------------------------------------------------------
$homeworkTeacher = $teacherIds[0];
$mathSubjectId = $subjectIds['Mathematics'];
$englishSubjectId = $subjectIds['English Language'];
$homeworkCount = 0;
$evaluatedCount = 0;
foreach ($classIds as $className => $cid) {
    $classStudents = array_filter($studentRecords, function ($sr) use ($cid) { return $sr['classId'] === $cid; });

    // live homework - not yet due, no submissions
    $liveHomeworkDate = date('Y-m-d', strtotime('-3 day'));
    $liveSubmissionDate = date('Y-m-d', strtotime('+5 day'));
    $m->query("INSERT INTO homework (class_id, section_id, session_id, subject_id, date_of_homework, date_of_submission, description, created_by, create_date, status, sms_notification, document, evaluated_by, branch_id)
        VALUES ($cid, $sectionId, $SESSION_ID, $mathSubjectId, '$liveHomeworkDate', '$liveSubmissionDate', 'Complete exercises 1 to 10 from Chapter 4 and show all working.', $homeworkTeacher, '$liveHomeworkDate', 0, 0, '', $homeworkTeacher, $branchId)");
    $homeworkCount++;

    // past homework - due, submitted, mostly evaluated
    $pastHomeworkDate = date('Y-m-d', strtotime('-20 day'));
    $pastSubmissionDate = date('Y-m-d', strtotime('-10 day'));
    $evaluationDate = date('Y-m-d', strtotime('-8 day'));
    $m->query("INSERT INTO homework (class_id, section_id, session_id, subject_id, date_of_homework, date_of_submission, description, created_by, create_date, status, sms_notification, document, evaluation_date, evaluated_by, branch_id)
        VALUES ($cid, $sectionId, $SESSION_ID, $englishSubjectId, '$pastHomeworkDate', '$pastSubmissionDate', 'Write a one-page essay on My Favourite Nigerian Festival.', $homeworkTeacher, '$pastHomeworkDate', 0, 0, '', '$evaluationDate', $homeworkTeacher, $branchId)");
    $pastHomeworkId = $m->insert_id;
    $homeworkCount++;

    foreach ($classStudents as $sr) {
        $m->query("INSERT INTO homework_submit (homework_id, student_id, message, enc_name, file_name, created_at)
            VALUES ($pastHomeworkId, {$sr['studentId']}, 'Submitted as instructed.', '', '', '$pastSubmissionDate 09:00:00')");
        if (mt_rand(1, 100) <= 70) {
            $rank = mt_rand(3, 5);
            $m->query("INSERT INTO homework_evaluation (homework_id, student_id, status, `rank`, remark, date)
                VALUES ($pastHomeworkId, {$sr['studentId']}, 'c', $rank, 'Well done, keep it up.', '$evaluationDate')");
            $evaluatedCount++;
        }
    }
}
echo "Created $homeworkCount homework assignments across 3 classes, with submissions and $evaluatedCount evaluations.\n";

// ---------------------------------------------------------------------
// STEP 14: Fees - types, one termly fee group, allocation to every student,
// and a realistic mix of fully paid / partly paid / unpaid payment history
// ---------------------------------------------------------------------
$feeTypeDefs = [
    ['Tuition Fee', 'tuition-fee', 50000],
    ['Development Levy', 'development-levy', 5000],
    ['Examination Fee', 'examination-fee', 3000],
    ['PTA Fee', 'pta-fee', 2000],
    ['Sports Fee', 'sports-fee', 1500],
];
$feeTypeIds = [];
$feeTypeAmounts = [];
foreach ($feeTypeDefs as $ft) {
    list($name, $code, $amount) = $ft;
    $m->query("INSERT INTO fees_type (name, fee_code, description, branch_id, `system`) VALUES ('" . esc($m, $name) . "', '$code', '', $branchId, 0)");
    $feeTypeIds[$name] = $m->insert_id;
    $feeTypeAmounts[$name] = $amount;
}
echo "Created " . count($feeTypeIds) . " fee types.\n";

$m->query("INSERT INTO fee_groups (name, description, session_id, `system`, branch_id) VALUES ('Session $SCHOOL_YEAR Fees', 'Standard termly fees for all students', $SESSION_ID, 0, $branchId)");
$feeGroupId = $m->insert_id;
$dueDate = date('Y-m-d', strtotime('+20 day'));
foreach ($feeTypeDefs as $ft) {
    list($name, $code, $amount) = $ft;
    $m->query("INSERT INTO fee_groups_details (fee_groups_id, fee_type_id, amount, due_date) VALUES ($feeGroupId, {$feeTypeIds[$name]}, $amount, '$dueDate')");
}
$groupTotal = array_sum(array_column($feeTypeDefs, 2));
echo "Created fee group (total {$groupTotal}) with " . count($feeTypeDefs) . " fee types.\n";

$allocationIds = [];
foreach ($studentRecords as $sr) {
    $m->query("INSERT INTO fee_allocation (student_id, group_id, branch_id, session_id, prev_due) VALUES ({$sr['enrollId']}, $feeGroupId, $branchId, $SESSION_ID, 0.00)");
    $allocationIds[$sr['enrollId']] = $m->insert_id;
}
echo "Allocated fees to " . count($allocationIds) . " students.\n";

$paidCount = 0;
$partialCount = 0;
foreach ($studentRecords as $i => $sr) {
    $allocationId = $allocationIds[$sr['enrollId']];
    $bucket = $i % 10;
    $paymentDate = date('Y-m-d', strtotime('-' . mt_rand(2, 15) . ' day'));
    if ($bucket < 4) {
        // fully paid - one payment row per fee type
        foreach ($feeTypeAmounts as $name => $amount) {
            $m->query("INSERT INTO fee_payment_history (allocation_id, type_id, collect_by, amount, discount, fine, pay_via, remarks, date)
                VALUES ($allocationId, {$feeTypeIds[$name]}, '$adminStaffId', $amount, 0.00, 0.00, 1, 'Paid in full.', '$paymentDate')");
        }
        $paidCount++;
    } elseif ($bucket < 7) {
        // partly paid - only Tuition Fee and Development Levy settled
        foreach (['Tuition Fee', 'Development Levy'] as $name) {
            $m->query("INSERT INTO fee_payment_history (allocation_id, type_id, collect_by, amount, discount, fine, pay_via, remarks, date)
                VALUES ($allocationId, {$feeTypeIds[$name]}, '$adminStaffId', {$feeTypeAmounts[$name]}, 0.00, 0.00, 1, 'Part payment received.', '$paymentDate')");
        }
        $partialCount++;
    }
    // remaining students (bucket >= 7) are left fully unpaid - no payment_history rows
}
echo "Fee payments: $paidCount fully paid, $partialCount partly paid, " . (count($studentRecords) - $paidCount - $partialCount) . " unpaid.\n";

// ---------------------------------------------------------------------
// STEP 15: Hostel - one hostel with 3 rooms, a handful of students assigned
// ---------------------------------------------------------------------
$m->query("INSERT INTO hostel_category (name, description, branch_id, type) VALUES ('Standard', 'Standard shared occupancy', $branchId, '')");
$hostelCatId = $m->insert_id;
$m->query("INSERT INTO hostel (name, category_id, address, watchman, remarks, branch_id) VALUES ('Unity Hostel', $hostelCatId, '3 Unity Close, Lekki Phase 1, Lagos', 'Mr. Bala Ibrahim', 'Boarding accommodation for demo students.', $branchId)");
$hostelId = $m->insert_id;

$hostelRoomIds = [];
foreach (['Room 101', 'Room 102', 'Room 103'] as $roomName) {
    $m->query("INSERT INTO hostel_room (name, hostel_id, no_beds, category_id, bed_fee, remarks, branch_id) VALUES ('" . esc($m, $roomName) . "', $hostelId, 4, $hostelCatId, 15000.00, '', $branchId)");
    $hostelRoomIds[] = $m->insert_id;
}
$hostelAssignSample = array_slice($studentRecords, 0, 6);
foreach ($hostelAssignSample as $i => $sr) {
    $roomId = $hostelRoomIds[$i % count($hostelRoomIds)];
    $m->query("UPDATE student SET hostel_id = $hostelId, room_id = $roomId WHERE id = {$sr['studentId']}");
}
echo "Created hostel with " . count($hostelRoomIds) . " rooms, assigned " . count($hostelAssignSample) . " students.\n";

// ---------------------------------------------------------------------
// STEP 16: Transport - one route, one vehicle, stoppages, a few students assigned
// ---------------------------------------------------------------------
$m->query("INSERT INTO transport_route (name, start_place, remarks, stop_place, branch_id) VALUES ('Route A - Ikeja Axis', 'Ikeja', '', 'School Campus', $branchId)");
$routeId = $m->insert_id;

$m->query("INSERT INTO transport_vehicle (vehicle_no, capacity, insurance_renewal, driver_name, driver_phone, driver_license, branch_id) VALUES ('LND-234-XY', '30', '" . date('Y-m-d', strtotime('+8 month')) . "', 'Musa Ibrahim', '08034567890', 'DL-2024-88213', $branchId)");
$vehicleId = $m->insert_id;

$m->query("INSERT INTO transport_assign (route_id, vehicle_id, branch_id) VALUES ($routeId, $vehicleId, $branchId)");

$stoppageDefs = [
    ['Ikeja Along', '06:30:00', 2000],
    ['Opebi Junction', '06:45:00', 1500],
    ['School Gate', '07:00:00', 500],
];
$stoppagePointIds = [];
$order = 1;
foreach ($stoppageDefs as $sd) {
    list($stopPosition, $stopTime, $fare) = $sd;
    $m->query("INSERT INTO transport_stoppage (stop_position, stop_time, route_fare, branch_id) VALUES ('" . esc($m, $stopPosition) . "', '$stopTime', $fare, $branchId)");
    $stoppageId = $m->insert_id;
    $m->query("INSERT INTO transport_stoppage_point (route_id, stoppage_id, route_fare, stop_time, order_no, branch_id, session_id) VALUES ($routeId, $stoppageId, $fare, '$stopTime', $order, $branchId, $SESSION_ID)");
    $stoppagePointIds[] = $m->insert_id;
    $order++;
}
$transportAssignSample = array_slice($studentRecords, 0, 6);
foreach ($transportAssignSample as $i => $sr) {
    $stoppagePointId = $stoppagePointIds[$i % count($stoppagePointIds)];
    $m->query("UPDATE student SET route_id = $routeId, vehicle_id = $vehicleId, stoppage_point_id = $stoppagePointId WHERE id = {$sr['studentId']}");
}
echo "Created transport route with " . count($stoppagePointIds) . " stoppages, assigned " . count($transportAssignSample) . " students.\n";

// ---------------------------------------------------------------------
// Featured demo credentials (shown on the public demo page)
// ---------------------------------------------------------------------
$demoTeacherEmail = $teacherEmails[$teacherIds[0]];
$demoParentEmail = null;
foreach ($studentRecords as $sr) {
    if ($sr['studentId'] === $demoStudent['studentId']) {
        $res = $m->query("SELECT email FROM parent WHERE id = (SELECT parent_id FROM student WHERE id = {$sr['studentId']})");
        $demoParentEmail = $res->fetch_assoc()['email'];
        break;
    }
}

echo "\n================= DEMO CREDENTIALS =================\n";
echo "Admin    | $adminEmail | $DEMO_PASSWORD\n";
echo "Teacher  | $demoTeacherEmail | $DEMO_PASSWORD\n";
echo "Student  | {$demoStudent['registerNo']} | $DEMO_PASSWORD  (name: {$demoStudent['name']})\n";
echo "Parent   | $demoParentEmail | $DEMO_PASSWORD\n";
echo "======================================================\n";
echo "These are also queried live and shown on: " . "/saas_website/demo\n";
