<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Bulk import of former students (graduated or left) from one spreadsheet.
 *
 * Unlike the per-class Multiple Import, one file can hold every class and
 * every past session. Each student is enrolled in the class and session they
 * were last in and marked as alumni, so they show under Alumni and in that
 * session's records but never in current class lists, attendance or fees.
 * Their logins (and any guardian's) are created switched off.
 */
class Old_student_import_model extends MY_Model
{
    // column => required
    public $columns = array(
        'AdmissionNo' => true,
        'FirstName' => true,
        'LastName' => true,
        'Gender' => false,
        'Birthday' => false,
        'Class' => true,
        'Section' => false,
        'LastSession' => true,
        'Status' => false,
        'AdmissionDate' => false,
        'Phone' => false,
        'Email' => false,
        'Address' => false,
        'City' => false,
        'State' => false,
        'Religion' => false,
        'BloodGroup' => false,
        'Profession' => false,
        'GuardianName' => false,
        'GuardianRelation' => false,
        'GuardianPhone' => false,
        'GuardianEmail' => false,
    );

    private $classes = array();
    private $sessions = array();

    public function __construct()
    {
        parent::__construct();
    }

    /* classes of the school with the sections each one has, for matching and for the help panel */
    public function getClassList($branchID)
    {
        $rows = $this->db->select('c.id, c.name, c.name_numeric, s.id as section_id, s.name as section_name')
            ->from('class as c')
            ->join('sections_allocation as sa', 'sa.class_id = c.id', 'left')
            ->join('section as s', 's.id = sa.section_id', 'left')
            ->where('c.branch_id', $branchID)
            ->order_by('c.id, s.id')
            ->get()->result_array();
        $list = array();
        foreach ($rows as $row) {
            if (!isset($list[$row['id']])) {
                $list[$row['id']] = array('id' => $row['id'], 'name' => $row['name'], 'name_numeric' => $row['name_numeric'], 'sections' => array());
            }
            if (!empty($row['section_id'])) {
                $list[$row['id']]['sections'][$row['section_id']] = $row['section_name'];
            }
        }
        return $list;
    }

    /* the sample file: the header row plus two example rows using this school's own class names */
    public function templateCsv($branchID)
    {
        $classes = array_values($this->getClassList($branchID));
        $first = isset($classes[0]) ? $classes[0] : array('name' => 'JSS 3', 'sections' => array('A'));
        $last = end($classes) ?: $first;
        $year = (int) date('Y');

        $rows = array(
            array_keys($this->columns),
            array('ADM/2015/001', 'Chinedu', 'Okafor', 'Male', '14/03/2003', $last['name'], (string) reset($last['sections']), ($year - 3) . '-' . ($year - 2), 'Graduated', '10/09/2015', '08031234567', 'chinedu@example.com', '12 Allen Avenue, Ikeja', 'Ikeja', 'Lagos', 'Christianity', 'O+', 'Engineer', 'Mr. Emeka Okafor', 'Father', '08029876543', ''),
            array('ADM/2017/045', 'Aisha', 'Bello', 'Female', '2006-07-21', $first['name'], '', ($year - 5) . '/' . ($year - 4), 'Left', '', '', '', '', '', 'Kano', 'Islam', '', '', 'Mrs. Hauwa Bello', 'Mother', '08051112233', ''),
        );

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF"); // so Excel opens it as UTF-8
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return $csv;
    }

    /* read the uploaded file into rows keyed by column name; returns a string on a bad file */
    public function readFile($path)
    {
        $handle = @fopen($path, 'r');
        if (!$handle) {
            return 'The file could not be read.';
        }
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return 'The file is empty.';
        }
        // Excel in some regions saves CSV with semicolons
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter);
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        // match headers loosely: "Admission No", "admission_no" and "AdmissionNo" are the same column
        $known = array();
        foreach (array_keys($this->columns) as $col) {
            $known[$this->key($col)] = $col;
        }
        $map = array();
        foreach ($header as $i => $name) {
            $k = $this->key($name);
            if (isset($known[$k])) {
                $map[$i] = $known[$k];
            }
        }
        $missing = array();
        foreach ($this->columns as $col => $required) {
            if ($required && !in_array($col, $map)) {
                $missing[] = $col;
            }
        }
        if (!empty($missing)) {
            fclose($handle);
            return 'This is not the import template. Missing column(s): ' . implode(', ', $missing) . '. Download the template and copy your data into it.';
        }

        $rows = array();
        $line = 1;
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if (count(array_filter($data, function ($v) { return trim((string) $v) !== ''; })) == 0) {
                continue; // blank line
            }
            $row = array_fill_keys(array_keys($this->columns), '');
            foreach ($map as $i => $col) {
                $row[$col] = isset($data[$i]) ? trim($this->utf8($data[$i])) : '';
            }
            $rows[$line] = $row;
        }
        fclose($handle);
        if (empty($rows)) {
            return 'The file has no students in it.';
        }
        return $rows;
    }

    /*
     * Check every row; nothing is saved unless the whole file is clean.
     * Returns array('errors' => [line => [messages]], 'skipped' => [line => message], 'ready' => [line => resolved row])
     */
    public function validate($rows, $branchID)
    {
        $this->classes = $this->getClassList($branchID);
        $result = array('errors' => array(), 'skipped' => array(), 'ready' => array());

        // admission numbers this school already has, so a re-upload skips them instead of duplicating
        $existing = array();
        $query = $this->db->select('s.register_no')->from('student as s')
            ->join('enroll as e', 'e.student_id = s.id', 'inner')
            ->where('e.branch_id', $branchID)->group_by('s.id')->get();
        foreach ($query->result_array() as $r) {
            $existing[strtolower(trim((string) $r['register_no']))] = true;
        }

        $seen = array();
        foreach ($rows as $line => $row) {
            $errors = array();
            foreach ($this->columns as $col => $required) {
                if ($required && $row[$col] === '') {
                    $errors[] = $col . ' is empty';
                }
            }

            $adm = strtolower($row['AdmissionNo']);
            if ($adm !== '') {
                if (isset($seen[$adm])) {
                    $errors[] = 'AdmissionNo ' . $row['AdmissionNo'] . ' is also on line ' . $seen[$adm];
                } elseif (isset($existing[$adm])) {
                    $result['skipped'][$line] = $row['FirstName'] . ' ' . $row['LastName'] . ' (' . $row['AdmissionNo'] . ') is already in the school';
                    continue;
                }
                $seen[$adm] = $line;
            }

            $classID = $sectionID = null;
            if ($row['Class'] !== '') {
                $classID = $this->matchClass($row['Class']);
                if (!$classID) {
                    $errors[] = 'Class "' . $row['Class'] . '" does not exist';
                } elseif (empty($this->classes[$classID]['sections'])) {
                    $errors[] = 'Class "' . $row['Class'] . '" has no section';
                } else {
                    $sections = $this->classes[$classID]['sections'];
                    if ($row['Section'] === '') {
                        $sectionID = key($sections);
                    } else {
                        foreach ($sections as $id => $name) {
                            if ($this->key($name) === $this->key($row['Section'])) {
                                $sectionID = $id;
                            }
                        }
                        if (!$sectionID) {
                            $errors[] = 'Section "' . $row['Section'] . '" is not in ' . $this->classes[$classID]['name'] . ' (use ' . implode(', ', $sections) . ')';
                        }
                    }
                }
            }

            $session = null;
            if ($row['LastSession'] !== '') {
                $session = $this->parseSession($row['LastSession']);
                if (!$session) {
                    $errors[] = 'LastSession "' . $row['LastSession'] . '" should look like ' . (date('Y') - 1) . '-' . date('Y');
                }
            }

            $status = strtolower($row['Status']);
            if ($status === '' || in_array($status, array('graduated', 'graduate', 'completed', 'passed out'))) {
                $status = 'graduated';
            } elseif (in_array($status, array('left', 'withdrawn', 'transferred', 'transfer'))) {
                $status = 'left';
            } else {
                $errors[] = 'Status "' . $row['Status'] . '" should be Graduated or Left';
            }

            $gender = strtolower($row['Gender']);
            if (in_array($gender, array('m', 'male', 'boy'))) {
                $gender = 'male';
            } elseif (in_array($gender, array('f', 'female', 'girl'))) {
                $gender = 'female';
            } elseif ($gender !== '') {
                $errors[] = 'Gender "' . $row['Gender'] . '" should be Male or Female';
            }

            $dates = array();
            foreach (array('Birthday', 'AdmissionDate') as $col) {
                $dates[$col] = '';
                if ($row[$col] !== '') {
                    $dates[$col] = $this->parseDate($row[$col]);
                    if (!$dates[$col]) {
                        $errors[] = $col . ' "' . $row[$col] . '" is not a date (use DD/MM/YYYY)';
                    }
                }
            }
            foreach (array('Email', 'GuardianEmail') as $col) {
                if ($row[$col] !== '' && !filter_var($row[$col], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = $col . ' "' . $row[$col] . '" is not a valid email';
                }
            }

            if (!empty($errors)) {
                $result['errors'][$line] = $errors;
                continue;
            }
            $row['class_id'] = $classID;
            $row['section_id'] = $sectionID;
            $row['session'] = $session;
            $row['status'] = $status;
            $row['gender'] = $gender;
            $row['birthday'] = $dates['Birthday'];
            $row['admission_date'] = $dates['AdmissionDate'];
            $result['ready'][$line] = $row;
        }
        return $result;
    }

    /* save the checked rows in one transaction; returns the number imported, or false */
    public function import($ready, $branchID)
    {
        $branch = $this->db->select('stu_username_prefix, grd_username_prefix')->where('id', $branchID)->get('branch')->row_array();
        $stuPrefix = !empty($branch['stu_username_prefix']) ? $branch['stu_username_prefix'] : 'stu';
        $grdPrefix = !empty($branch['grd_username_prefix']) ? $branch['grd_username_prefix'] : 'grd';
        $parents = array();

        $this->db->trans_start();
        foreach ($ready as $row) {
            $sessionID = $this->sessionID($row['session']);
            $parentID = $this->parentID($row, $branchID, $grdPrefix, $parents);

            $this->db->insert('student', array(
                'register_no' => $row['AdmissionNo'],
                'admission_date' => $row['admission_date'],
                'first_name' => $row['FirstName'],
                'last_name' => $row['LastName'],
                'gender' => $row['gender'],
                'birthday' => $row['birthday'],
                'religion' => $row['Religion'],
                'blood_group' => $row['BloodGroup'],
                'current_address' => $row['Address'],
                'permanent_address' => $row['Address'],
                'city' => $row['City'],
                'state' => $row['State'],
                'mobileno' => $row['Phone'],
                'email' => $row['Email'],
                'parent_id' => $parentID,
                'previous_details' => $row['status'] == 'left' ? 'Left the school (imported)' : 'Graduated (imported)',
                'photo' => 'defualt.png',
                'active' => 0,
            ));
            $studentID = $this->db->insert_id();

            // a login row has to exist for the student lists, but former students can't sign in
            $this->db->insert('login_credential', array(
                'username' => $this->freeUsername($stuPrefix . $studentID),
                'role' => 7,
                'user_id' => $studentID,
                'password' => $this->app_lib->pass_hashed(bin2hex(random_bytes(8))),
                'active' => 0,
            ));

            $this->db->insert('enroll', array(
                'student_id' => $studentID,
                'class_id' => $row['class_id'],
                'section_id' => $row['section_id'],
                'branch_id' => $branchID,
                'roll' => 0,
                'session_id' => $sessionID,
                'is_alumni' => 1,
            ));
            $enrollID = $this->db->insert_id();

            $this->db->insert('alumni_students', array(
                'enroll_id' => $enrollID,
                'email' => $row['Email'],
                'mobile_no' => $row['Phone'],
                'address' => $row['Address'],
                'profession' => $row['Profession'],
                'photo' => 'defualt.png',
            ));
        }
        $this->db->trans_complete();
        return $this->db->trans_status() ? count($ready) : false;
    }

    /* guardians are optional; children sharing a guardian phone number share one parent record */
    private function parentID($row, $branchID, $grdPrefix, &$parents)
    {
        if ($row['GuardianName'] === '' && $row['GuardianPhone'] === '') {
            return 0;
        }
        $phone = preg_replace('/\D/', '', $row['GuardianPhone']);
        if ($phone !== '') {
            if (isset($parents[$phone])) {
                return $parents[$phone];
            }
            $found = $this->db->select('id')->where('branch_id', $branchID)->where('mobileno', $row['GuardianPhone'])->get('parent')->row();
            if ($found) {
                return $parents[$phone] = $found->id;
            }
        }
        $this->db->insert('parent', array(
            'name' => $row['GuardianName'] !== '' ? $row['GuardianName'] : 'Guardian of ' . $row['FirstName'] . ' ' . $row['LastName'],
            'relation' => $row['GuardianRelation'],
            'mobileno' => $row['GuardianPhone'],
            'email' => $row['GuardianEmail'],
            'branch_id' => $branchID,
            'photo' => 'defualt.png',
        ));
        $parentID = $this->db->insert_id();
        $this->db->insert('login_credential', array(
            'username' => $this->freeUsername($grdPrefix . $parentID),
            'role' => 6,
            'user_id' => $parentID,
            'password' => $this->app_lib->pass_hashed(bin2hex(random_bytes(8))),
            'active' => 0,
        ));
        if ($phone !== '') {
            $parents[$phone] = $parentID;
        }
        return $parentID;
    }

    private function freeUsername($username)
    {
        $base = $username;
        $n = 1;
        while ($this->db->where('username', $username)->count_all_results('login_credential') > 0) {
            $username = $base . '-' . $n++;
        }
        return $username;
    }

    /* sessions are shared by every school, so an old one is added only if nobody has it yet */
    private function sessionID($year)
    {
        if (!isset($this->sessions[$year])) {
            $row = $this->db->select('id')->where('school_year', $year)->get('schoolyear')->row();
            if ($row) {
                $this->sessions[$year] = $row->id;
            } else {
                $this->db->insert('schoolyear', array('school_year' => $year, 'created_by' => (int) get_loggedin_user_id()));
                $this->sessions[$year] = $this->db->insert_id();
            }
        }
        return $this->sessions[$year];
    }

    private function matchClass($name)
    {
        $k = $this->key($name);
        foreach ($this->classes as $id => $class) {
            if ($this->key($class['name']) === $k) {
                return $id;
            }
        }
        foreach ($this->classes as $id => $class) {
            if ($class['name_numeric'] !== '' && $this->key($class['name_numeric']) === $k) {
                return $id;
            }
        }
        return null;
    }

    /* "2019-2020", "2019/2020", "2019/20" or just "2020" (the year the session ended) => "2019-2020" */
    public function parseSession($value)
    {
        $value = trim($value);
        if (preg_match('/^(\d{4})\s*[-\/–]\s*(\d{2}|\d{4})$/u', $value, $m)) {
            $start = (int) $m[1];
            $end = strlen($m[2]) == 2 ? (int) (substr($m[1], 0, 2) . $m[2]) : (int) $m[2];
        } elseif (preg_match('/^\d{4}$/', $value)) {
            $end = (int) $value;
            $start = $end - 1;
        } else {
            return null;
        }
        if ($end != $start + 1 || $start < 1950 || $end > (int) date('Y') + 1) {
            return null;
        }
        return $start . '-' . $end;
    }

    /* DD/MM/YYYY (how Nigerian schools write dates), YYYY-MM-DD, or an Excel serial number */
    public function parseDate($value)
    {
        $value = trim($value);
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $m)) {
            list(, $y, $mo, $d) = $m;
        } elseif (preg_match('/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/', $value, $m)) {
            list(, $d, $mo, $y) = $m;
        } elseif (preg_match('/^\d{5}$/', $value)) {
            return date('Y-m-d', ((int) $value - 25569) * 86400);
        } else {
            return null;
        }
        return checkdate((int) $mo, (int) $d, (int) $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : null;
    }

    private function key($text)
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower((string) $text));
    }

    private function utf8($text)
    {
        return mb_check_encoding($text, 'UTF-8') ? $text : mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
    }
}
