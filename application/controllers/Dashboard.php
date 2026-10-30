<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Ramom school management system
 * @version : 7.0
 * @developed by : RamomCoder
 * @support : ramomcoder@yahoo.com
 * @author url : http://codecanyon.net/user/RamomCoder
 * @filename : Dashboard.php
 * @copyright : Reserved RamomCoder Team
 */

class Dashboard extends Dashboard_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('dashboard_model');
    }

    public function index()
    {
        if (is_student_loggedin() || is_parent_loggedin()) {
            $studentID = 0;
            if (is_student_loggedin()) {
                $this->data['title'] = translate('welcome_to') . " " . $this->session->userdata('name');
                $studentID = get_loggedin_user_id();
            }elseif (is_parent_loggedin()) {
                $studentID = $this->session->userdata('myChildren_id');
                if (!empty($studentID)) {
                    $this->data['title'] = get_type_name_by_id('student', $studentID, 'first_name') . " - " . translate('dashboard');
                } else {
                    $this->data['title'] = translate('welcome_to') . " " . $this->session->userdata('name');
                }
                $this->data['children'] = $this->myChildren();
            }
            $schoolID = get_loggedin_branch_id();
            $this->data['student_id'] = $studentID;
            $this->data['school_id'] = $schoolID;
            if (!empty($studentID)) {
                $this->studentPanels($studentID, $schoolID);
            }
            $this->data['sub_page'] = 'userrole/dashboard';
        } else {
            if (is_superadmin_loggedin()) {
                if ($this->input->get('school_id')) {
                    $schoolID = $this->input->get('school_id');
                    $this->data['title'] = get_type_name_by_id('branch', $schoolID) . " " . translate('branch_dashboard');
                } else {
                    $this->data['title'] = translate('all_branch_dashboard');
                    $schoolID = "";
                }
            } else {
                $schoolID = get_loggedin_branch_id();
                $this->data['title'] = get_type_name_by_id('branch', $schoolID) . " " . translate('branch_dashboard');
            }
            $getSQLMode = $this->application_model->getSQLMode();
            $this->data['school_id'] = $schoolID;
            $this->data['sqlMode'] = $getSQLMode;
            if ($getSQLMode == false) {
                $this->data['fees_summary'] = $this->dashboard_model->annualFeessummaryCharts($schoolID);
            } else {
                $this->data['fees_summary'] = array(
                    'total_fee' => array_fill(0, 12, 0),
                    'total_paid' => array_fill(0, 12, 0),
                    'total_due' => array_fill(0, 12, 0),
                );
            }
            $this->data['student_by_class'] = $this->dashboard_model->getStudentByClass($schoolID);
            $this->data['income_vs_expense'] = $this->dashboard_model->getIncomeVsExpense($schoolID);
            $this->data['weekend_attendance'] = $this->dashboard_model->getWeekendAttendance($schoolID);
            $this->data['get_monthly_admission'] = $this->dashboard_model->getMonthlyAdmission($schoolID);
            $this->data['get_voucher'] = $this->dashboard_model->getVoucher($schoolID);
            $this->data['get_transport_route'] = $this->dashboard_model->get_transport_route($schoolID);
            $this->data['get_total_student'] = $this->dashboard_model->get_total_student($schoolID);
            $this->data['upcoming_events'] = $this->dashboard_model->getUpcomingEvents($schoolID);
            // teachers get their own day on top of whatever widgets their role allows
            if (loggedin_role_id() == 3) {
                $staffID = get_loggedin_user_id();
                $this->data['teacher_summary'] = $this->dashboard_model->getTeacherSummary($staffID);
                $this->data['today_classes'] = $this->dashboard_model->getTodayClasses(array('teacher_id' => $staffID));
            }
            $this->data['sub_page'] = 'dashboard/index';
        }
        $language = 'en';
        $jsArray = array(
            'vendor/chartjs/chart.min.js',
            'vendor/echarts/echarts.common.min.js',
            'vendor/moment/moment.js',
            'vendor/fullcalendar/fullcalendar.js',
        );
        if ($this->session->userdata('set_lang') != 'english') {
            $language = $this->dashboard_model->languageShortCodes($this->session->userdata('set_lang'));
            $jsArray[] = "vendor/fullcalendar/locale/$language.js";
        }
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/fullcalendar/fullcalendar.css',
            ),
            'js' => $jsArray
        );
        $this->data['language'] = $language;
        $this->data['main_menu'] = 'dashboard';
        $this->load->view('layout/index', $this->data);
    }

    // everything a student (or a parent looking at one child) sees
    private function studentPanels($studentID, $schoolID)
    {
        $enroll = $this->dashboard_model->getStudentEnroll($studentID);
        $this->data['enroll'] = $enroll;
        $this->data['fees_summary'] = $this->dashboard_model->annualFeessummaryCharts($schoolID, $studentID);
        $this->data['fee_totals'] = $this->dashboard_model->getStudentFeeTotals($studentID);
        $this->data['books_out'] = $this->dashboard_model->getBooksOut($studentID);
        $this->data['upcoming_events'] = $this->dashboard_model->getUpcomingEvents($schoolID);
        if (!empty($enroll)) {
            $this->data['attendance'] = $this->dashboard_model->getAttendanceSummary($enroll->enroll_id);
            $this->data['today_classes'] = $this->dashboard_model->getTodayClasses(array('class_id' => $enroll->class_id, 'section_id' => $enroll->section_id));
            $this->data['open_homework'] = $this->dashboard_model->getOpenHomework($enroll->class_id, $enroll->section_id);
            $this->data['upcoming_exams'] = $this->dashboard_model->getUpcomingExams($enroll->class_id, $enroll->section_id);
        }
    }

    // a parent's children enrolled this session
    private function myChildren()
    {
        $this->db->select('s.id,s.first_name,s.last_name,s.photo,s.register_no,s.birthday,e.id as enroll_id,e.roll,c.name as class_name,se.name as section_name');
        $this->db->from('enroll as e');
        $this->db->join('student as s', 'e.student_id = s.id', 'inner');
        $this->db->join('class as c', 'e.class_id = c.id', 'left');
        $this->db->join('section as se', 'e.section_id = se.id', 'left');
        $this->db->where('s.parent_id', get_loggedin_user_id());
        $this->db->where('e.session_id', get_session_id());
        $this->db->order_by('e.id');
        return $this->db->get()->result();
    }
}
