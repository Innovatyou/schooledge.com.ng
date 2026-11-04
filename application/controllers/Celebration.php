<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Celebrations wall (everyone) and the best teacher / star student / school
 * celebration picker (admins). See Celebration_model for what is worked out
 * automatically.
 */
class Celebration extends Dashboard_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('celebration_model');
    }

    public function index()
    {
        if (!get_permission('celebrations', 'is_view')) {
            access_denied();
        }
        $branchID = $this->viewerBranch();
        $this->data['branch_id'] = $branchID;
        $this->data['wall'] = empty($branchID) ? null : $this->celebration_model->wall($branchID);
        if (!empty($branchID)) {
            // the viewer's own celebrations, so "Celebrate again" can replay them
            $this->data['mine'] = $this->celebration_model->forViewer($branchID, $this->viewerRole(), get_loggedin_user_id(), $this->data['wall']);
        }
        $this->data['can_add'] = get_permission('celebrations', 'is_add') && !is_student_loggedin() && !is_parent_loggedin();
        if ($this->data['can_add']) {
            $this->data['picks'] = empty($branchID) ? array() : $this->celebration_model->picks($branchID, false);
            $this->data['staff_list'] = empty($branchID) ? array() : $this->celebration_model->staffList($branchID);
            $this->data['types'] = $this->celebration_model->types;
            $this->data['themes'] = $this->celebration_model->themes;
        }
        $this->data['title'] = translate('celebrations');
        $this->data['sub_page'] = 'celebration/index';
        $this->data['main_menu'] = 'celebrations';
        $this->load->view('layout/index', $this->data);
    }

    public function save()
    {
        if (!$_POST) {
            redirect(base_url('celebration'));
        }
        if (!get_permission('celebrations', 'is_add') || is_student_loggedin() || is_parent_loggedin()) {
            ajax_access_denied();
        }
        $type = $this->input->post('type');
        $this->form_validation->set_rules('type', translate('type'), 'trim|required|in_list[best_teacher,star_student,custom]');
        $this->form_validation->set_rules('title', translate('title'), 'trim|required|max_length[255]');
        $this->form_validation->set_rules('start_date', translate('start_date'), 'trim|required');
        $this->form_validation->set_rules('end_date', translate('end_date'), 'trim|required|callback_end_after_start');
        if ($type == 'best_teacher') {
            $this->form_validation->set_rules('staff_id', translate('teacher'), 'trim|required|numeric');
        } elseif ($type == 'star_student') {
            $this->form_validation->set_rules('student_id', translate('student'), 'trim|required|numeric');
        }
        if ($this->form_validation->run() == true) {
            $branchID = $this->application_model->get_branch_id();
            $data = $this->input->post();
            // the person picked must belong to this school
            if ($type == 'best_teacher' && !$this->db->where(array('id' => $data['staff_id'], 'branch_id' => $branchID))->count_all_results('staff')) {
                ajax_access_denied();
            }
            if ($type == 'star_student' && !$this->db->where(array('student_id' => $data['student_id'], 'branch_id' => $branchID))->count_all_results('enroll')) {
                ajax_access_denied();
            }
            $this->celebration_model->save($data, $branchID);
            set_alert('success', translate('information_has_been_saved_successfully'));
            $array = array('status' => 'success');
        } else {
            $array = array('status' => 'fail', 'error' => $this->form_validation->error_array());
        }
        echo json_encode($array);
    }

    public function end_after_start($end)
    {
        if (strtotime($end) < strtotime($this->input->post('start_date'))) {
            $this->form_validation->set_message('end_after_start', 'The end date must be on or after the start date.');
            return false;
        }
        return true;
    }

    public function delete($id = '')
    {
        if (get_permission('celebrations', 'is_delete') && !is_student_loggedin() && !is_parent_loggedin()) {
            $this->db->where(array('id' => $id, 'branch_id' => $this->application_model->get_branch_id()));
            $this->db->delete('celebration');
        }
    }

    private function viewerBranch()
    {
        return is_superadmin_loggedin() ? $this->input->get('branch_id') : get_loggedin_branch_id();
    }

    private function viewerRole()
    {
        if (is_student_loggedin()) {
            return 'student';
        }
        return is_parent_loggedin() ? 'parent' : 'staff';
    }
}
