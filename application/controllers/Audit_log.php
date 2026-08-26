<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Audit_log extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('datatables');
        $this->load->model('audit_log_model');
    }

    public function index()
    {
        if (!get_permission('audit_log', 'is_view')) {
            access_denied();
        }
        $this->data['title'] = translate('audit_log');
        $this->data['sub_page'] = 'audit_log/index';
        $this->data['main_menu'] = 'settings';
        $this->load->view('layout/index', $this->data);
    }

    public function getAuditLogListDT()
    {
        if ($_POST) {
            if (!get_permission('audit_log', 'is_view')) {
                return;
            }
            $results = json_decode($this->audit_log_model->getAuditLogListDT());
            $data = array();
            if (!empty($results->data)) {
                foreach ($results->data as $val) {
                    $row = array();
                    if (is_superadmin_loggedin()) {
                        $row[] = html_escape($val->branch_name);
                    }
                    $row[] = _d($val->created_at) . ' ' . date('h:i A', strtotime($val->created_at));
                    $row[] = html_escape($val->actor_name);
                    $row[] = html_escape($val->role_name);
                    $row[] = html_escape(ucfirst($val->action));
                    $row[] = html_escape($val->table_name);
                    $row[] = html_escape($val->record_id);
                    $row[] = '<a href="javascript:void(0);" class="btn btn-circle btn-default icon" onclick="getAuditLogDetail(' . intval($val->id) . ')" data-toggle="tooltip" data-original-title="' . translate('view') . '"><i class="fas fa-eye"></i></a>';
                    $data[] = $row;
                }
            }
            $json_data = array(
                "draw" => intval($results->draw),
                "recordsTotal" => intval($results->recordsTotal),
                "recordsFiltered" => intval($results->recordsFiltered),
                "data" => $data,
            );
            echo json_encode($json_data);
        }
    }

    public function getAuditLogDetail()
    {
        if (!get_permission('audit_log', 'is_view')) {
            return;
        }
        $id = $this->input->post('id');
        $this->data['row'] = $this->audit_log_model->getAuditLogDetail($id);
        $this->load->view('audit_log/detail_modalView', $this->data);
    }
}
