<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Staff-facing student wallet admin: view every enrolled student's balance,
 * manually credit/debit (cash/bank-transfer top-ups, corrections). Parents
 * and students manage their own wallet through Userrole::wallet() instead -
 * this controller is staff-only, gated by the 'wallet' permission.
 */
class Wallet extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('wallet_model');
    }

    public function index()
    {
        if (!get_permission('wallet', 'is_view')) {
            access_denied();
        }
        $branchID = $this->application_model->get_branch_id();
        $search = $this->input->post('search');
        $this->data['wallets'] = $this->wallet_model->getWalletList($branchID, $search);
        $this->data['search'] = $search;
        $this->data['title'] = translate('wallet');
        $this->data['sub_page'] = 'wallet/index';
        $this->data['main_menu'] = 'wallet';
        $this->load->view('layout/index', $this->data);
    }

    /* wallet detail + ledger modal */
    public function view()
    {
        if (!get_permission('wallet', 'is_view')) {
            ajax_access_denied();
        }
        $studentID = (int)$this->input->post('student_id');
        $branchID = $this->application_model->get_branch_id();
        if (!$this->wallet_model->studentInBranch($branchID, $studentID)) {
            ajax_access_denied();
        }
        $this->data['student'] = $this->db->select('s.id,CONCAT_WS(" ",s.first_name,s.last_name) as fullname,s.register_no')
            ->from('student as s')->where('s.id', $studentID)->get()->row_array();
        $this->data['wallet'] = $this->wallet_model->getOrCreateWallet($branchID, $studentID);
        $this->data['transactions'] = $this->wallet_model->getTransactions($branchID, $studentID);
        $this->data['can_add'] = get_permission('wallet', 'is_add');
        $this->data['can_edit'] = get_permission('wallet', 'is_edit');
        echo $this->load->view('wallet/view_modal', $this->data, true);
    }

    public function credit()
    {
        if (!get_permission('wallet', 'is_add')) {
            access_denied();
        }
        if (is_demo_readonly()) {
            access_denied();
        }
        $studentID = (int)$this->input->post('student_id');
        $amount = (float)$this->input->post('amount');
        $remarks = trim((string)$this->input->post('remarks'));
        $branchID = $this->application_model->get_branch_id();
        if ($studentID && $amount > 0 && $remarks !== '' && $this->wallet_model->studentInBranch($branchID, $studentID)) {
            $ok = $this->wallet_model->adjustBalance($branchID, $studentID, 'credit', $amount, 'topup_manual', $remarks, loggedin_role_id(), get_loggedin_user_id());
            if ($ok) {
                audit_log('add', 'student_wallets', $studentID, null, array('type' => 'credit', 'amount' => $amount));
                set_alert('success', translate('information_has_been_saved_successfully'));
            } else {
                set_alert('error', translate('something_went_wrong'));
            }
        } else {
            set_alert('error', translate('all_fields_are_required'));
        }
        redirect(base_url('wallet'));
    }

    public function debit()
    {
        if (!get_permission('wallet', 'is_edit')) {
            access_denied();
        }
        if (is_demo_readonly()) {
            access_denied();
        }
        $studentID = (int)$this->input->post('student_id');
        $amount = (float)$this->input->post('amount');
        $remarks = trim((string)$this->input->post('remarks'));
        $branchID = $this->application_model->get_branch_id();
        if ($studentID && $amount > 0 && $remarks !== '' && $this->wallet_model->studentInBranch($branchID, $studentID)) {
            $ok = $this->wallet_model->adjustBalance($branchID, $studentID, 'debit', $amount, 'adjustment', $remarks, loggedin_role_id(), get_loggedin_user_id());
            if ($ok) {
                audit_log('edit', 'student_wallets', $studentID, null, array('type' => 'debit', 'amount' => $amount));
                set_alert('success', translate('information_has_been_updated_successfully'));
            } else {
                set_alert('error', translate('the_amount_exceeds_the_wallet_balance'));
            }
        } else {
            set_alert('error', translate('all_fields_are_required'));
        }
        redirect(base_url('wallet'));
    }
}
