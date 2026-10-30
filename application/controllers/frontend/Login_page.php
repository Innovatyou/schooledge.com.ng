<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Lets a school style its own sign-in page: layout, background photo,
// overlay strength, accent colour and the welcome copy.
class Login_page extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    // school being edited: a super admin picks one (0 = the platform's own page)
    private function branchID()
    {
        if (is_superadmin_loggedin()) {
            $branchID = $this->input->get('branch_id', true);
            return ($branchID === null || $branchID === '') ? null : (int) $branchID;
        }
        return (int) get_loggedin_branch_id();
    }

    public function index()
    {
        if (!get_permission('frontend_setting', 'is_view')) {
            access_denied();
        }
        $branchID = $this->branchID();
        if ($branchID !== null) {
            $this->data['setting'] = login_page_setting($branchID);
            $this->data['signin_url'] = $this->signinUrl($branchID);
            if ($branchID > 0) {
                $this->data['school_logo'] = $this->application_model->getBranchImage($branchID, 'logo');
                $this->data['school_name'] = get_type_name_by_id('branch', $branchID, 'school_name');
            } else {
                $this->data['school_logo'] = base_url('assets/images/favicon.png');
                $this->data['school_name'] = get_global_setting('institute_name');
            }
        }
        $this->data['branch_id'] = $branchID;
        $this->data['title'] = translate('sign_in_page');
        $this->data['sub_page'] = 'frontend/login_page';
        $this->data['main_menu'] = 'frontend';
        $this->load->view('layout/index', $this->data);
    }

    public function save()
    {
        $branchID = $this->branchID();
        if (!get_permission('frontend_setting', 'is_add') || $branchID === null || !$_POST) {
            access_denied();
        }
        $back = 'frontend/login_page' . (is_superadmin_loggedin() ? '?branch_id=' . $branchID : '');
        $current = login_page_setting($branchID);

        $accent = $this->input->post('accent', true);
        $data = array(
            'branch_id' => $branchID,
            'layout' => array_key_exists($this->input->post('layout'), login_page_layouts()) ? $this->input->post('layout') : 'split',
            'overlay' => max(0, min(90, (int) $this->input->post('overlay'))),
            'accent' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $accent) ? strtolower($accent) : '#7c5cff',
            'headline' => mb_substr(trim((string) $this->input->post('headline', true)), 0, 150),
            'tagline' => mb_substr(trim((string) $this->input->post('tagline', true)), 0, 255),
            'perks' => implode("\n", array_slice(array_filter(array_map('trim', explode("\n", (string) $this->input->post('perks', true))), 'strlen'), 0, 4)),
            'show_perks' => $this->input->post('show_perks') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        );

        // background: an upload wins, otherwise the picked preset / current photo / none
        $choice = (string) $this->input->post('bg_choice');
        if (in_array($choice, login_page_presets(), true) || ($choice !== '' && $choice === $current['bg_image'])) {
            $data['bg_image'] = $choice;
        } else {
            $data['bg_image'] = '';
        }
        if (!empty($_FILES['bg_upload']['name'])) {
            $file = $_FILES['bg_upload'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $isImage = $file['error'] === UPLOAD_ERR_OK && @getimagesize($file['tmp_name']) !== false;
            if (!in_array($ext, array('jpg', 'jpeg', 'png', 'webp'), true) || !$isImage) {
                set_alert('error', translate('sign_in_background_must_be_a_jpg_png_or_webp_image'));
                redirect(base_url($back));
            }
            if ($file['size'] > 5 * 1024 * 1024) {
                set_alert('error', translate('sign_in_background_must_be_under_5mb'));
                redirect(base_url($back));
            }
            $name = 'bg_' . $branchID . '_' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], FCPATH . 'uploads/login_image/' . $name)) {
                $data['bg_image'] = $name;
            }
        }
        // tidy up a school's own old upload once it is no longer used
        $old = $current['bg_image'];
        if ($old !== '' && $old !== $data['bg_image'] && strpos($old, 'bg_' . $branchID . '_') === 0 && is_file(FCPATH . 'uploads/login_image/' . $old)) {
            @unlink(FCPATH . 'uploads/login_image/' . $old);
        }

        if ($this->db->where('branch_id', $branchID)->count_all_results('login_page_setting') > 0) {
            $this->db->where('branch_id', $branchID)->update('login_page_setting', $data);
        } else {
            $this->db->insert('login_page_setting', $data);
        }
        set_alert('success', translate('information_has_been_saved_successfully'));
        redirect(base_url($back));
    }

    // where this school's people actually sign in
    private function signinUrl($branchID)
    {
        if ($branchID > 0) {
            $alias = $this->db->select('url_alias')->get_where('front_cms_setting', array('branch_id' => $branchID))->row();
            if (!empty($alias) && $alias->url_alias !== '') {
                return base_url($alias->url_alias . '/authentication');
            }
            return '';
        }
        return base_url('authentication');
    }
}
