<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Bulk import of a school's current students from one spreadsheet.
 *
 * Unlike the per-class Multiple Import, one file holds every class and
 * section, so a school that has just joined can bring its whole register in
 * at once. Students go into the current session with logins made the same
 * way as a normal admission (School Settings rules), guardians are shared
 * between siblings by phone number, and blank admission and roll numbers are
 * filled in.
 */
class Student_bulk_import_model extends MY_Model
{
    // column => required
    public $columns = array(
        'AdmissionNo' => false,
        'FirstName' => true,
        'LastName' => true,
        'Gender' => false,
        'Birthday' => false,
        'Class' => true,
        'Section' => false,
        'Roll' => false,
        'AdmissionDate' => false,
        'Category' => false,
        'Phone' => false,
        'Email' => false,
        'Address' => false,
        'City' => false,
        'State' => false,
        'Religion' => false,
        'BloodGroup' => false,
        'GuardianName' => false,
        'GuardianRelation' => false,
        'GuardianPhone' => false,
        'GuardianEmail' => false,
        'GuardianOccupation' => false,
        'GuardianAddress' => false,
    );

    private $classes = array();

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

    /* the login, admission number and plan rules this school's import will follow, for the page */
    public function getRules($branchID)
    {
        $branch = $this->db->select('stu_generate, stu_username_prefix, stu_default_password, grd_generate, grd_username_prefix, grd_default_password, reg_prefix_enable, institution_code, unique_roll')
            ->where('id', $branchID)->get('branch')->row_array();
        $rules = array(
            'stu_prefix' => !empty($branch['stu_username_prefix']) ? $branch['stu_username_prefix'] : 'stu',
            'grd_prefix' => !empty($branch['grd_username_prefix']) ? $branch['grd_username_prefix'] : 'grd',
            'stu_password' => isset($branch['stu_default_password']) ? $branch['stu_default_password'] : '',
            'grd_password' => isset($branch['grd_default_password']) ? $branch['grd_default_password'] : '',
            'auto_admission_no' => !empty($branch['reg_prefix_enable']),
            'unique_roll' => isset($branch['unique_roll']) ? (int) $branch['unique_roll'] : 1,
            'student_places' => null,
            'parent_places' => null,
        );

        // places left on the school's plan (null = no limit)
        $plan = $this->db->select('sp.student_limit, sp.parents_limit')
            ->from('saas_subscriptions as sb')->join('saas_package as sp', 'sp.id = sb.package_id', 'left')
            ->where('sb.school_id', $branchID)->get()->row_array();
        if (!empty($plan) && is_superadmin_loggedin() == false) {
            $students = $this->db->select('student_id')->where(array('branch_id' => $branchID, 'is_alumni' => 0))
                ->group_by('student_id')->get('enroll')->num_rows();
            $parents = $this->db->where('branch_id', $branchID)->count_all_results('parent');
            $rules['student_places'] = max(0, (int) $plan['student_limit'] - $students);
            $rules['parent_places'] = max(0, (int) $plan['parents_limit'] - $parents);
        }
        return $rules;
    }

    /* the sample file: the header row plus two example rows using this school's own class names */
    public function templateCsv($branchID)
    {
        $classes = array_values($this->getClassList($branchID));
        $first = isset($classes[0]) ? $classes[0] : array('name' => 'JSS 1', 'sections' => array('A'));
        $last = end($classes) ?: $first;

        $rows = array(
            array_keys($this->columns),
            array('', 'Chinedu', 'Okafor', 'Male', '14/03/2012', $first['name'], (string) reset($first['sections']), '1', '', '', '', '', '12 Allen Avenue, Ikeja', 'Ikeja', 'Lagos', 'Christianity', 'O+', 'Mr. Emeka Okafor', 'Father', '08029876543', 'emeka@example.com', 'Engineer', '12 Allen Avenue, Ikeja'),
            array('', 'Adaeze', 'Okafor', 'Female', '2009-07-21', $last['name'], '', '', '', '', '', '', '', '', 'Lagos', 'Christianity', '', 'Mr. Emeka Okafor', 'Father', '08029876543', '', '', ''),
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
        $known['registerno'] = 'AdmissionNo';
        $known['surname'] = 'LastName';
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
    public function validate($rows, $branchID, $rules)
    {
        $this->classes = $this->getClassList($branchID);
        $sessionID = get_session_id();
        $result = array('errors' => array(), 'skipped' => array(), 'ready' => array());

        // admission numbers this school already has, so a re-upload skips them instead of duplicating
        $existing = array();
        $query = $this->db->select('s.register_no')->from('student as s')
            ->join('enroll as e', 'e.student_id = s.id', 'inner')
            ->where('e.branch_id', $branchID)->group_by('s.id')->get();
        foreach ($query->result_array() as $r) {
            $existing[strtolower(trim((string) $r['register_no']))] = true;
        }

        // roll numbers already used this session, by class (or class + section), and who is in each class,
        // so a re-upload also recognises rows that had no admission number
        $rolls = array();
        $names = array();
        $query = $this->db->select('e.class_id, e.section_id, e.roll, s.first_name, s.last_name')->from('enroll as e')
            ->join('student as s', 's.id = e.student_id', 'inner')
            ->where(array('e.branch_id' => $branchID, 'e.session_id' => $sessionID, 'e.is_alumni' => 0))->get();
        foreach ($query->result_array() as $r) {
            $rolls[$this->rollKey($r['class_id'], $r['section_id'], $rules)][(int) $r['roll']] = 'already in the school';
            $names[$r['class_id'] . '|' . $this->key($r['first_name'] . $r['last_name'])] = true;
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
            if ($adm === '') {
                if (!$rules['auto_admission_no']) {
                    $errors[] = 'AdmissionNo is empty (fill it in, or switch on automatic admission numbers in School Settings)';
                }
            } elseif (isset($seen[$adm])) {
                $errors[] = 'AdmissionNo ' . $row['AdmissionNo'] . ' is also on line ' . $seen[$adm];
            } elseif (isset($existing[$adm])) {
                $result['skipped'][$line] = $row['FirstName'] . ' ' . $row['LastName'] . ' (' . $row['AdmissionNo'] . ')';
                continue;
            } else {
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

            if ($adm === '' && $classID && isset($names[$classID . '|' . $this->key($row['FirstName'] . $row['LastName'])])) {
                $result['skipped'][$line] = $row['FirstName'] . ' ' . $row['LastName'] . ' (already in ' . $this->classes[$classID]['name'] . ')';
                continue;
            }

            $roll = null;
            if ($row['Roll'] !== '') {
                if (!ctype_digit($row['Roll']) || (int) $row['Roll'] < 1) {
                    $errors[] = 'Roll "' . $row['Roll'] . '" should be a whole number';
                } elseif ($sectionID) {
                    $roll = (int) $row['Roll'];
                    $rk = $this->rollKey($classID, $sectionID, $rules);
                    if ($rules['unique_roll'] != 0 && isset($rolls[$rk][$roll])) {
                        $where = $rolls[$rk][$roll];
                        $errors[] = 'Roll ' . $roll . ' in ' . $this->classes[$classID]['name'] . ' is ' . (is_int($where) ? 'also on line ' . $where : $where);
                    } else {
                        $rolls[$rk][$roll] = $line;
                    }
                }
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
            $row['roll'] = $roll;
            $row['gender'] = $gender;
            $row['birthday'] = $dates['Birthday'];
            $row['admission_date'] = $dates['AdmissionDate'] !== '' ? $dates['AdmissionDate'] : date('Y-m-d');
            $result['ready'][$line] = $row;
        }

        // blank rolls take the next free number in their class, in file order
        foreach ($result['ready'] as $line => $row) {
            if ($row['roll'] === null) {
                $rk = $this->rollKey($row['class_id'], $row['section_id'], $rules);
                $next = empty($rolls[$rk]) ? 1 : max(array_keys($rolls[$rk])) + 1;
                $rolls[$rk][$next] = $line;
                $result['ready'][$line]['roll'] = $next;
            }
        }
        return $result;
    }

    /* the number of new guardian records the ready rows would add, for the plan check */
    public function countNewParents($ready, $branchID)
    {
        $phones = array();
        $count = 0;
        foreach ($ready as $row) {
            if ($row['GuardianName'] === '' && $row['GuardianPhone'] === '') {
                continue;
            }
            $phone = preg_replace('/\D/', '', $row['GuardianPhone']);
            if ($phone === '') {
                $count++;
            } elseif (!isset($phones[$phone])) {
                $phones[$phone] = true;
                if (!$this->findParent($row['GuardianPhone'], $phone, $branchID)) {
                    $count++;
                }
            }
        }
        return $count;
    }

    /* save the checked rows in one transaction; returns the number imported, or false */
    public function import($ready, $branchID, $rules)
    {
        $sessionID = get_session_id();
        $parents = array();
        $categories = array();
        $nextAdmission = $rules['auto_admission_no'] ? $this->admissionNumbers($branchID) : null;

        $this->db->trans_start();
        foreach ($ready as $row) {
            $parentID = $this->parentID($row, $branchID, $rules, $parents);
            $admissionNo = $row['AdmissionNo'] !== '' ? $row['AdmissionNo'] : $nextAdmission();

            $this->db->insert('student', array(
                'register_no' => $admissionNo,
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
                'category_id' => $this->categoryID($row['Category'], $branchID, $categories),
                'parent_id' => $parentID,
                'photo' => 'defualt.png',
            ));
            $studentID = $this->db->insert_id();

            $this->db->insert('login_credential', array(
                'username' => $this->freeUsername($rules['stu_prefix'] . $studentID),
                'role' => 7,
                'user_id' => $studentID,
                'password' => $this->app_lib->pass_hashed($rules['stu_password'] !== '' ? $rules['stu_password'] : $admissionNo),
            ));

            $this->db->insert('enroll', array(
                'student_id' => $studentID,
                'class_id' => $row['class_id'],
                'section_id' => $row['section_id'],
                'branch_id' => $branchID,
                'roll' => $row['roll'],
                'session_id' => $sessionID,
            ));
        }
        $this->db->trans_complete();
        return $this->db->trans_status() ? count($ready) : false;
    }

    /* children sharing a guardian phone number share one parent record and login */
    private function parentID($row, $branchID, $rules, &$parents)
    {
        if ($row['GuardianName'] === '' && $row['GuardianPhone'] === '') {
            return 0;
        }
        $phone = preg_replace('/\D/', '', $row['GuardianPhone']);
        if ($phone !== '') {
            if (isset($parents[$phone])) {
                return $parents[$phone];
            }
            $found = $this->findParent($row['GuardianPhone'], $phone, $branchID);
            if ($found) {
                return $parents[$phone] = $found;
            }
        }
        $this->db->insert('parent', array(
            'name' => $row['GuardianName'] !== '' ? $row['GuardianName'] : 'Guardian of ' . $row['FirstName'] . ' ' . $row['LastName'],
            'relation' => $row['GuardianRelation'],
            'occupation' => $row['GuardianOccupation'],
            'mobileno' => $row['GuardianPhone'],
            'email' => $row['GuardianEmail'],
            'address' => $row['GuardianAddress'],
            'branch_id' => $branchID,
            'photo' => 'defualt.png',
        ));
        $parentID = $this->db->insert_id();
        $this->db->insert('login_credential', array(
            'username' => $this->freeUsername($rules['grd_prefix'] . $parentID),
            'role' => 6,
            'user_id' => $parentID,
            'password' => $this->app_lib->pass_hashed($rules['grd_password'] !== '' ? $rules['grd_password'] : ($phone !== '' ? $phone : $rules['grd_prefix'] . $parentID)),
        ));
        if ($phone !== '') {
            $parents[$phone] = $parentID;
        }
        return $parentID;
    }

    private function findParent($raw, $digits, $branchID)
    {
        $found = $this->db->select('id')->where('branch_id', $branchID)
            ->group_start()->where('mobileno', $raw)->or_where('mobileno', $digits)->group_end()
            ->get('parent')->row();
        return $found ? $found->id : null;
    }

    /* categories are matched by name and created when new, e.g. Day / Boarding */
    private function categoryID($name, $branchID, &$categories)
    {
        $k = $this->key($name);
        if ($k === '') {
            return 0;
        }
        if (!isset($categories[$k])) {
            foreach ($this->db->where('branch_id', $branchID)->get('student_category')->result_array() as $c) {
                $categories[$this->key($c['name'])] = $c['id'];
            }
            if (!isset($categories[$k])) {
                $this->db->insert('student_category', array('branch_id' => $branchID, 'name' => $name));
                $categories[$k] = $this->db->insert_id();
            }
        }
        return $categories[$k];
    }

    /* returns a function giving the school's next free admission numbers (institution code + padded serial) */
    private function admissionNumbers($branchID)
    {
        $config = $this->db->select('reg_start_from, institution_code, reg_prefix_digit')->where('id', $branchID)->get('branch')->row();
        $code = (string) $config->institution_code;
        $taken = array();
        $max = 0;
        $query = $this->db->select('s.register_no')->from('student as s')->join('enroll as e', 'e.student_id = s.id', 'inner')
            ->where('e.branch_id', $branchID)->get();
        foreach ($query->result_array() as $r) {
            $no = (string) $r['register_no'];
            $taken[strtolower($no)] = true;
            $rest = $code === '' ? $no : (strpos($no, $code) === 0 ? substr($no, strlen($code)) : null);
            if ($rest !== null && ctype_digit($rest)) {
                $max = max($max, (int) $rest);
            }
        }
        $next = max($max + 1, (int) $config->reg_start_from);
        $digits = max(1, (int) $config->reg_prefix_digit);
        return function () use ($code, $digits, &$next, &$taken) {
            do {
                $no = $code . sprintf('%0' . $digits . 'd', $next++);
            } while (isset($taken[strtolower($no)]));
            $taken[strtolower($no)] = true;
            return $no;
        };
    }

    private function rollKey($classID, $sectionID, $rules)
    {
        return $rules['unique_roll'] == 1 ? $classID : $classID . '-' . $sectionID;
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
