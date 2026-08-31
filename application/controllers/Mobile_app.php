<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mobile_app extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $apkPath = FCPATH . 'uploads/mobile_app/SchoolEdge.apk';
        $this->data['apk_available'] = is_file($apkPath);
        $this->data['apk_url'] = base_url('uploads/mobile_app/SchoolEdge.apk');
        $this->data['apk_size'] = $this->data['apk_available'] ? round(filesize($apkPath) / 1048576, 1) : null;
        $this->data['sub_page'] = 'mobile_app/index';
        $this->data['main_menu'] = 'mobile_app';
        $this->data['title'] = translate('mobile_app');
        $this->load->view('layout/index', $this->data);
    }
}
