<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Guide Center: step-by-step help for every module, written separately for the
// school, teachers, students and parents (content lives in config/module_guides.php).
class Userguide extends Dashboard_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->data['guides'] = module_guides();
        $this->data['audience'] = guide_audience();
        $this->data['checklist'] = $this->setupChecklist();
        $this->data['sub_page'] = 'userguide/index';
        $this->data['main_menu'] = 'userguide';
        $this->data['title'] = translate('user_guide');
        $this->load->view('layout/index', $this->data);
    }

    public function view($key = '')
    {
        $guides = module_guides();
        if (!isset($guides[$key])) {
            redirect(base_url('userguide'));
        }
        $this->data['guide'] = $guides[$key];
        $this->data['guides'] = $guides;
        $this->data['audience'] = guide_audience();
        $this->data['sub_page'] = 'userguide/view';
        $this->data['main_menu'] = 'userguide';
        $this->data['title'] = translate('user_guide') . ' - ' . $guides[$key]['title'];
        $this->load->view('layout/index', $this->data);
    }

    // What a school admin has and hasn't set up yet. Only for staff who manage school settings.
    private function setupChecklist()
    {
        $branchID = (int) get_loggedin_branch_id();
        if (guide_audience() !== 'school' || !$branchID || !get_permission('school_settings', 'is_view')) {
            return array();
        }
        $sessionID = get_session_id();
        $count = function ($table, $where) {
            if (!$this->db->table_exists($table)) {
                return 0;
            }
            return $this->db->where($where)->count_all_results($table);
        };
        $branch = $this->db->select('address,mobileno,email')->get_where('branch', array('id' => $branchID))->row();
        $teachers = $this->db->from('staff')
            ->join('login_credential', 'login_credential.user_id = staff.id AND login_credential.role = 3', 'inner')
            ->where('staff.branch_id', $branchID)->count_all_results();
        $templates = $this->db->where('(branch_id = ' . $branchID . ' OR available_all_branches = 1)', null, false)->count_all_results('marksheet_template');
        $website = $count('front_cms_setting', array('branch_id' => $branchID, 'cms_active' => 1));

        $items = array(
            array('School profile', 'Address, phone and email filled in', !empty($branch) && $branch->address !== '' && $branch->mobileno !== '', 'school_settings', 'school_settings'),
            array('Classes & sections', 'At least one class with sections', $count('class', array('branch_id' => $branchID)) > 0 && $count('section', array('branch_id' => $branchID)) > 0, 'academic', 'classes'),
            array('Subjects assigned', 'Subjects linked to classes and teachers', $count('subject_assign', array('branch_id' => $branchID, 'session_id' => $sessionID)) > 0, 'academic', 'subject/class_assign'),
            array('Teachers', 'Teaching staff with logins', $teachers > 0, 'employees', 'employee/add'),
            array('Students', 'Students admitted this session', $count('enroll', array('branch_id' => $branchID, 'session_id' => $sessionID)) > 0, 'admission', 'student/add'),
            array('Timetable', 'Class periods for the week', $count('timetable_class', array('branch_id' => $branchID, 'session_id' => $sessionID)) > 0, 'timetable', 'timetable/viewclass'),
            array('Fees', 'A fee group allocated to students', $count('fee_allocation', array('branch_id' => $branchID, 'session_id' => $sessionID)) > 0, 'fees', 'fees/group'),
            array('Grades', 'Your grading scale', $count('grade', array('branch_id' => $branchID)) > 0, 'exam_setup', 'exam/grade'),
            array('Exams', 'An exam term and exam this session', $count('exam_term', array('branch_id' => $branchID, 'session_id' => $sessionID)) > 0 && $count('exam', array('branch_id' => $branchID, 'session_id' => $sessionID)) > 0, 'exam_setup', 'exam'),
            array('Report card design', 'A report card template', $templates > 0, 'results', 'marksheet_template/index'),
            array('School website', 'Public website switched on', $website > 0, 'website', 'frontend/setting'),
        );
        $guides = module_guides();
        $list = array();
        foreach ($items as $item) {
            $list[] = array(
                'label' => $item[0],
                'hint' => $item[1],
                'done' => (bool) $item[2],
                'guide' => isset($guides[$item[3]]) ? $item[3] : '',
                'url' => $item[4],
            );
        }
        return $list;
    }
}
