<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Userguide extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->data['sub_page'] = 'userguide/index';
        $this->data['main_menu'] = 'userguide';
        $this->data['title'] = translate('user_guide');
        $this->load->view('layout/index', $this->data);
    }
}
