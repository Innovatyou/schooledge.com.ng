<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Dashboard_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getMonthlyBookIssued($id = '')
    {
        $this->db->select('id');
        $this->db->from('leave_application');
        $this->db->where("start_date BETWEEN DATE_SUB(CURDATE() ,INTERVAL 1 MONTH) AND CURDATE() AND status = '2' AND role_id = '7' AND user_id = " . $this->db->escape($id));
        return $this->db->get()->num_rows();
    }

    public function getStaffCounter($role = '', $branchID = '')
    {
        $this->db->select('COUNT(staff.id) as snumber');
        $this->db->from('staff');
        $this->db->join('login_credential', 'login_credential.user_id = staff.id', 'inner');
        $this->db->where_not_in('login_credential.role', 1);
        if (!empty($role)) {
            $this->db->where('login_credential.role', $role);
        } else {
            $this->db->where_not_in('login_credential.role', array(1, 3, 6, 7));
        }
        if (!empty($branchID)) {
            $this->db->where('staff.branch_id', $branchID);
        }
        return $this->db->get()->row_array();
    }

    public function getMonthlyPayment($id = '')
    {
        $this->db->select('IFNULL(sum(h.amount),0) as amount');
        $this->db->from('fee_allocation as fa');
        $this->db->join('fee_payment_history as h', 'h.allocation_id = fa.id', 'left');
        $this->db->where("h.date BETWEEN DATE_SUB(CURDATE(),INTERVAL 1 MONTH) AND CURDATE() AND fa.student_id = " . $this->db->escape($id) . " AND fa.session_id = " . $this->db->escape(get_session_id()));
        return $this->db->get()->row()->amount;
    }

    /* annual academic fees summary charts */
    public function annualFeessummaryCharts($branchID = '', $studentID = '')
    {
        $total_fee = array();
        $total_paid = array();
        $total_due = array();
        $year = date("Y");
        for ($month = 1; $month <= 12; $month++) {
            $sql = "SELECT `fa`.`id` as `allocation_id`,`gd`.`fee_type_id`,`gd`.`amount` FROM `fee_allocation` as `fa` INNER JOIN `fee_groups_details` as `gd` ON `gd`.`fee_groups_id` = `fa`.`group_id` WHERE MONTH(`gd`.`due_date`) = " . $this->db->escape($month) . " AND YEAR(`gd`.`due_date`) = '$year' AND `fa`.`session_id` = " . $this->db->escape(get_session_id());
            if (!empty($branchID)) {
                $sql .= " AND `fa`.`branch_id` = " . $this->db->escape($branchID);
            }
            if (!empty($studentID)) {
                $sql .= " AND `fa`.`student_id` = " . $this->db->escape($studentID);
            }
            $total_amount = 0;
            $totalpaid = 0;
            $total_discount = 0;
            $result = $this->db->query($sql)->result();
            foreach ($result as $row) {
                $total_amount += $row->amount;
                $sql = "SELECT SUM(`h`.`amount`) AS `total_paid`, SUM(`h`.`discount`) AS `total_discount` FROM `fee_payment_history` as `h` WHERE `h`.`allocation_id` = " . $this->db->escape($row->allocation_id) . " AND  `h`.`type_id` = " . $this->db->escape($row->fee_type_id);
                $r = $this->db->query($sql)->row();
                $totalpaid += $r->total_paid;
                $total_discount += $r->total_discount;
            }
            $total_fee[] = floatval($total_amount);
            $total_paid[] = floatval($totalpaid);
            $total_due[] = floatval($total_amount - ($totalpaid + $total_discount));
        };
        return array(
            'total_fee' => $total_fee,
            'total_paid' => $total_paid,
            'total_due' => $total_due,
        );
    }

    /* student annual attendance charts */
    public function getStudentAttendance($studentID = '')
    {
        $total_present = array();
        $total_absent = array();
        $total_late = array();
        $enrollID = $this->db->select('id')->where(['student_id' => $studentID, 'session_id' => get_session_id()])->get('enroll')->row()->id;
        for ($month = 1; $month <= 12; $month++):
            $total_present[] = $this->db->query("SELECT id FROM student_attendance WHERE MONTH(date) = " . $this->db->escape($month) . " AND YEAR(date) = YEAR(CURDATE()) AND status = 'P' AND enroll_id = " . $this->db->escape($enrollID))->num_rows();
            $total_absent[] = $this->db->query("SELECT id FROM student_attendance WHERE MONTH(date) = " . $this->db->escape($month) . " AND YEAR(date) = YEAR(CURDATE()) AND status = 'A' AND enroll_id = " . $this->db->escape($enrollID))->num_rows();
            $total_late[] = $this->db->query("SELECT id FROM student_attendance WHERE MONTH(date) = " . $this->db->escape($month) . " AND YEAR(date) = YEAR(CURDATE()) AND status = 'L' AND enroll_id = " . $this->db->escape($enrollID))->num_rows();
        endfor;
        return array(
            'total_present' => $total_present,
            'total_absent' => $total_absent,
            'total_late' => $total_late,
        );
    }

    public function get_monthly_attachments($id = '')
    {
        $branchID = get_loggedin_branch_id();
        $classID = $this->db->select('class_id')->where('student_id', $id)->get('enroll')->row()->class_id;
        $this->db->select('id');
        $this->db->from('attachments');
        $this->db->where("date BETWEEN DATE_SUB(CURDATE() ,INTERVAL 1 MONTH) AND CURDATE() AND (class_id = " . $this->db->escape($classID) . " OR class_id = 'unfiltered') AND branch_id = " . $this->db->escape($branchID));
        return $this->db->get()->num_rows();
    }

    /* annual academic fees summary charts */
    public function getWeekendAttendance($branchID = '')
    {
        $days = array();
        $employee_att = array();
        $student_att = array();
        $now = new DateTime("6 days ago");
        $interval = new DateInterval('P1D'); // 1 Day interval
        $period = new DatePeriod($now, $interval, 6); // 7 Days
        foreach ($period as $day) {
            $days[] = $day->format("d-M");
            $this->db->select('id');
            if (!empty($branchID)) {
                $this->db->where('branch_id', $branchID);
            }

            $this->db->where('date = "' . $day->format('Y-m-d') . '" AND (status = "P" OR status = "L")');
            $student_att[]['y'] = $this->db->get('student_attendance')->num_rows();

            $this->db->select('id');
            if (!empty($branchID)) {
                $this->db->where('branch_id', $branchID);
            }

            $this->db->where('date = "' . $day->format('Y-m-d') . '" AND (status = "P" OR status = "L")');
            $employee_att[]['y'] = $this->db->get('staff_attendance')->num_rows();
        }
        return array(
            'days' => $days,
            'employee_att' => $employee_att,
            'student_att' => $student_att,
        );
    }

    /* monthly academic cash book transaction charts */
    public function getIncomeVsExpense($branchID = '')
    {
        $query = "SELECT IFNULL(SUM(dr),0) as dr, IFNULL(SUM(cr),0) as cr FROM transactions WHERE month(DATE) = MONTH(now()) AND year(DATE) = YEAR(now())";
        if (!empty($branchID)) {
            $query .= " AND branch_id = " . $this->db->escape($branchID);
        }
        $r = $this->db->query($query)->row_array();
        return array(['name' => translate("expense"), 'value' => $r['dr']], ['name' => translate("income"), 'value' => $r['cr']]);
    }

    /* total academic students strength classes divided into charts */
    public function getStudentByClass($branchID = '')
    {
        $this->db->select('IFNULL(COUNT(e.student_id), 0) as total_student, c.name as class_name');
        $this->db->from('enroll as e');
        $this->db->join('class as c', 'c.id = e.class_id', 'inner');
        $this->db->group_by('e.class_id');
        if (!empty($branchID)) {
            $this->db->where('e.branch_id', $branchID);
        }

        $query = $this->db->get();
        $data = array();
        if ($query->num_rows() > 0) {
            $students = $query->result();
            foreach ($students as $row) {
                $data[] = ['value' => floatval($row->total_student), 'name' => $row->class_name];
            }
        } else {
            $data[] = ['value' => 0, 'name' => translate('not_found_anything')];
        }
        return $data;
    }

    public function get_total_student($branchID = '')
    {
        $sessionID = get_session_id();
        $this->db->select('IFNULL(COUNT(enroll.id), 0) as total_student');
        $this->db->from('enroll');
        $this->db->join('student', 'student.id = enroll.student_id', 'inner');
        $this->db->where('enroll.session_id', $sessionID);
        if (!empty($branchID)) {
            $this->db->where('enroll.branch_id', $branchID);
        }
        return $this->db->get()->row()->total_student;
    }

    public function getMonthlyAdmission($branchID = '')
    {
        $this->db->select('s.id');
        $this->db->from('student as s');
        $this->db->join('enroll as e', 'e.student_id = s.id', 'inner');
        $this->db->where('s.admission_date BETWEEN DATE_SUB(CURDATE() ,INTERVAL 1 MONTH) AND CURDATE()');
        if (!empty($branchID)) {
            $this->db->where('e.branch_id', $branchID);
        }
        return $this->db->get()->num_rows();
    }

    public function getVoucher($branchID = '')
    {
        $this->db->select('id');
        if (!empty($branchID)) {
            $this->db->where('branch_id', $branchID);
        }
        $this->db->where('date BETWEEN DATE_SUB(CURDATE() ,INTERVAL 1 MONTH) AND CURDATE()');
        return $this->db->get('transactions')->num_rows();
    }

    public function get_transport_route($branchID = '')
    {
        if (!empty($branchID)) {
            $this->db->where('branch_id', $branchID);
        }
        return $this->db->get('transport_route')->num_rows();
    }


    public function languageShortCodes($lang='')
    {
        $codes = array (
          'english' => 'en',
          'bengali' => 'bn',
          'arabic' => 'ar',
          'french' => 'fr',
          'hindi' => 'hi',
          'indonesian' => 'id',
          'italian' => 'it',
          'japanese' => 'ja',
          'korean' => 'ko',
          'portuguese' => 'pt',
          'thai' => 'th',
          'turkish' => 'tr',
          'urdu' => 'ur',
          'chinese' => 'zh',
          'afrikaans' => 'af',
          'german' => 'de',
          'nepali' => 'ne',
          'russian' => 'ru',
          'danish' => 'da',
          'armenian' => 'hy',
          'georgian' => 'ka',
          'marathi' => 'mr',
          'malay' => 'ms',
          'tamil' => 'ta',
          'telugu' => 'te',
          'swedish' => 'sv',
          'dutch' => 'nl',
          'greek' => 'el',
          'spanish' => 'es',
          'punjabi' => 'pa',
        );
        return empty($codes[$lang]) ? '' : $codes[$lang];
    }
    /* ---------- 3D dashboard: per-person panels ---------- */

    // a teacher's (or a class's) periods for today, in time order
    public function getTodayClasses($where = array())
    {
        $this->db->select('t.time_start,t.time_end,t.class_room,t.break,c.name as class_name,se.name as section_name,sub.name as subject_name,st.name as teacher_name');
        $this->db->from('timetable_class as t');
        $this->db->join('class as c', 'c.id = t.class_id', 'left');
        $this->db->join('section as se', 'se.id = t.section_id', 'left');
        $this->db->join('subject as sub', 'sub.id = t.subject_id', 'left');
        $this->db->join('staff as st', 'st.id = t.teacher_id', 'left');
        $this->db->where('t.day', strtolower(date('l')));
        $this->db->where('t.session_id', get_session_id());
        foreach ($where as $field => $value) {
            $this->db->where('t.' . $field, $value);
        }
        $this->db->order_by('t.time_start', 'asc');
        return $this->db->get()->result();
    }

    // classes, subjects, students and homework a teacher looks after this session
    public function getTeacherSummary($staffID)
    {
        $sessionID = get_session_id();
        $mine = "SELECT class_id, section_id FROM subject_assign WHERE teacher_id = ? AND session_id = ?
            UNION SELECT class_id, section_id FROM teacher_allocation WHERE teacher_id = ? AND session_id = ?";
        $classes = $this->db->query("SELECT COUNT(*) AS n FROM ($mine) AS x", array($staffID, $sessionID, $staffID, $sessionID))->row()->n;
        $students = $this->db->query("SELECT COUNT(DISTINCT e.student_id) AS n FROM enroll e
            INNER JOIN ($mine) AS x ON x.class_id = e.class_id AND x.section_id = e.section_id
            WHERE e.session_id = ?", array($staffID, $sessionID, $staffID, $sessionID, $sessionID))->row()->n;
        $subjects = $this->db->query("SELECT COUNT(DISTINCT subject_id) AS n FROM subject_assign WHERE teacher_id = ? AND session_id = ?", array($staffID, $sessionID))->row()->n;
        $homework = $this->db->query("SELECT COUNT(*) AS n, IFNULL(SUM(evaluation_date IS NULL AND date_of_submission < CURDATE()), 0) AS to_mark
            FROM homework WHERE created_by = ? AND session_id = ?", array($staffID, $sessionID))->row();
        return array(
            'classes' => (int) $classes,
            'subjects' => (int) $subjects,
            'students' => (int) $students,
            'homework' => (int) $homework->n,
            'to_mark' => (int) $homework->to_mark,
        );
    }

    // a student's enrolment this session with class and section names
    public function getStudentEnroll($studentID)
    {
        $this->db->select('e.id as enroll_id,e.class_id,e.section_id,e.roll,s.first_name,s.last_name,s.photo,s.register_no,c.name as class_name,se.name as section_name');
        $this->db->from('enroll as e');
        $this->db->join('student as s', 's.id = e.student_id', 'inner');
        $this->db->join('class as c', 'c.id = e.class_id', 'left');
        $this->db->join('section as se', 'se.id = e.section_id', 'left');
        $this->db->where(array('e.student_id' => $studentID, 'e.session_id' => get_session_id()));
        return $this->db->get()->row();
    }

    // present / absent / late totals plus the last ten months that have records
    public function getAttendanceSummary($enrollID)
    {
        $rows = $this->db->query("SELECT DATE_FORMAT(date, '%Y-%m') AS ym, SUM(status = 'P') AS p, SUM(status = 'A') AS a, SUM(status = 'L') AS l
            FROM student_attendance WHERE enroll_id = ? GROUP BY ym ORDER BY ym DESC LIMIT 10", array($enrollID))->result();
        $out = array('labels' => array(), 'present' => array(), 'absent' => array(), 'late' => array(), 'p' => 0, 'a' => 0, 'l' => 0);
        foreach (array_reverse($rows) as $r) {
            $out['labels'][] = date('M Y', strtotime($r->ym . '-01'));
            $out['present'][] = (int) $r->p;
            $out['absent'][] = (int) $r->a;
            $out['late'][] = (int) $r->l;
            $out['p'] += $r->p;
            $out['a'] += $r->a;
            $out['l'] += $r->l;
        }
        $total = $out['p'] + $out['a'] + $out['l'];
        $out['rate'] = $total ? (int) round(($out['p'] + $out['l']) / $total * 100) : null;
        return $out;
    }

    // the whole session's fees for one student (not just this calendar year)
    public function getStudentFeeTotals($studentID)
    {
        $row = $this->db->query("SELECT IFNULL(SUM(gd.amount), 0) AS total,
                IFNULL(SUM((SELECT IFNULL(SUM(h.amount + h.discount), 0) FROM fee_payment_history h WHERE h.allocation_id = fa.id AND h.type_id = gd.fee_type_id)), 0) AS paid
            FROM fee_allocation fa INNER JOIN fee_groups_details gd ON gd.fee_groups_id = fa.group_id
            WHERE fa.student_id = ? AND fa.session_id = ?", array($studentID, get_session_id()))->row();
        $total = (float) $row->total;
        $paid = min($total, (float) $row->paid);
        return array('total' => $total, 'paid' => $paid, 'due' => $total - $paid, 'rate' => $total > 0 ? (int) round($paid / $total * 100) : null);
    }

    // homework for a class that is still open (due today or later)
    public function getOpenHomework($classID, $sectionID, $limit = 5)
    {
        $this->db->select('h.id,h.date_of_submission,sub.name as subject_name');
        $this->db->from('homework as h');
        $this->db->join('subject as sub', 'sub.id = h.subject_id', 'left');
        $this->db->where(array('h.class_id' => $classID, 'h.section_id' => $sectionID, 'h.session_id' => get_session_id()));
        $this->db->where('h.date_of_submission >=', date('Y-m-d'));
        $this->db->order_by('h.date_of_submission', 'asc');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    // next exam papers for a class
    public function getUpcomingExams($classID, $sectionID, $limit = 4)
    {
        $this->db->select('te.exam_date,te.time_start,sub.name as subject_name,ex.name as exam_name');
        $this->db->from('timetable_exam as te');
        $this->db->join('subject as sub', 'sub.id = te.subject_id', 'left');
        $this->db->join('exam as ex', 'ex.id = te.exam_id', 'left');
        $this->db->where(array('te.class_id' => $classID, 'te.section_id' => $sectionID, 'te.session_id' => get_session_id()));
        $this->db->where('te.exam_date >=', date('Y-m-d'));
        $this->db->order_by('te.exam_date', 'asc');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    // books a student has out right now
    public function getBooksOut($studentID)
    {
        return $this->db->where(array('user_id' => $studentID, 'role_id' => 7, 'status' => 1))->count_all_results('book_issues');
    }

    // events from today to 30 days ahead
    public function getUpcomingEvents($branchID = '', $limit = 4)
    {
        $this->db->select('id,title,start_date,end_date');
        $this->db->where('start_date >=', date('Y-m-d'));
        $this->db->where('start_date <=', date('Y-m-d', strtotime('+30 days')));
        if (!empty($branchID)) {
            $this->db->where('branch_id', $branchID);
        }
        $this->db->order_by('start_date', 'asc');
        $this->db->limit($limit);
        return $this->db->get('event')->result();
    }
}
