<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * School-facing top-up for the Veltrix SMS/email wallet (migration 737).
 * Reuses the platform's own Paystack account (payment_config, branch_id
 * 9999 -- the same "global payment settings" row Saas_payment.php charges
 * school subscriptions against), since a wallet top-up is SchoolEdge
 * revenue and never touches Veltrix's own billing.
 */
class Veltrixwallet extends Admin_Controller
{
    private $globalPaymentID = 9999;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('school_model');
        $this->load->model('veltrix_wallet_model');
    }

    public function index()
    {
        if (!get_permission('veltrix_wallet', 'is_view')) {
            access_denied();
        }
        $branchID = $this->school_model->getBranchID();
        $this->data['branch_id']    = $branchID;
        $this->data['balance']      = $this->veltrix_wallet_model->getBalance($branchID);
        $this->data['transactions'] = $this->veltrix_wallet_model->getTransactions($branchID, 50);
        $this->data['title']        = 'SMS/Email Wallet';
        $this->data['sub_page']     = 'veltrixwallet/index';
        $this->data['main_menu']    = 'school_m';
        $this->load->view('layout/index', $this->data);
    }

    public function topup()
    {
        if (!get_permission('veltrix_wallet', 'is_add')) {
            access_denied();
        }
        $this->form_validation->set_rules('amount', 'Amount', 'trim|required|numeric|greater_than[0]');
        if ($this->form_validation->run() === false) {
            set_alert('error', 'Enter a valid amount.');
            redirect(base_url('veltrixwallet'));
        }

        $branchID = $this->school_model->getBranchID();
        $amount   = round((float) $this->input->post('amount'), 2);
        $ref      = 'VWALLET-' . $branchID . '-' . app_generate_hash();

        $this->session->set_userdata('veltrix_topup_params', array(
            'branch_id' => $branchID,
            'amount'    => $amount,
            'reference' => $ref,
        ));

        redirect(base_url('veltrixwallet/paystack'));
    }

    public function paystack()
    {
        $config = $this->getPaymentConfig();
        $params = $this->session->userdata('veltrix_topup_params');
        if (empty($params) || empty($config['paystack_secret_key'])) {
            set_alert('error', 'Paystack is not configured. Contact support.');
            redirect(base_url('veltrixwallet'));
        }

        $branch = $this->db->select('email')->where('id', $params['branch_id'])->get('branch')->row_array();

        $postdata = array(
            'email'        => !empty($branch['email']) ? $branch['email'] : 'billing@schooledge.com.ng',
            'amount'       => (int) round($params['amount'] * 100),
            'reference'    => $params['reference'],
            'callback_url' => base_url('veltrixwallet/verify_paystack_payment/' . $params['reference']),
        );

        $ch = curl_init('https://api.paystack.co/transaction/initialize');
        curl_setopt_array($ch, array(
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => json_encode($postdata),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER     => array(
                'Authorization: Bearer ' . $config['paystack_secret_key'],
                'Content-Type: application/json',
            ),
        ));
        $request = curl_exec($ch);
        curl_close($ch);

        $result = $request ? json_decode($request, true) : null;
        if (empty($result['data']['authorization_url'])) {
            set_alert('error', 'Could not start the Paystack payment. Please try again.');
            redirect(base_url('veltrixwallet'));
        }

        // Record the attempt now, before handing off to Paystack: this
        // account's webhook URL is claimed by another platform, so the
        // browser callback below is the only path that can normally
        // complete this reference. Without a pending row here, a customer
        // who pays but never makes it back to that callback leaves no trace
        // anywhere in SchoolEdge.
        $this->veltrix_wallet_model->recordPending($params['branch_id'], $params['amount'], $params['reference'], 'Wallet top-up via Paystack');

        header('Location: ' . $result['data']['authorization_url']);
        exit;
    }

    public function verify_paystack_payment($ref)
    {
        $config = $this->getPaymentConfig();
        $params = $this->session->userdata('veltrix_topup_params');
        $this->session->set_userdata('veltrix_topup_params', '');

        if (empty($params) || $params['reference'] !== $ref || empty($config['paystack_secret_key'])) {
            set_alert('error', 'Transaction could not be verified.');
            redirect(base_url('veltrixwallet'));
        }

        // Idempotency: a refreshed callback page (or the reconciliation cron
        // beating us to it) must never credit the same reference twice.
        $existing = $this->db->where(array('branch_id' => $params['branch_id'], 'reference' => $ref))
            ->get('school_wallet_transaction')->row_array();
        if ($existing && $existing['status'] !== 'pending') {
            if ($existing['status'] === 'completed') {
                set_alert('success', 'Payment already credited.');
            } else {
                set_alert('error', 'Payment verification failed.');
            }
            redirect(base_url('veltrixwallet'));
        }

        $ch = curl_init('https://api.paystack.co/transaction/verify/' . rawurlencode($ref));
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER     => array('Authorization: Bearer ' . $config['paystack_secret_key']),
        ));
        $request = curl_exec($ch);
        curl_close($ch);
        $result = $request ? json_decode($request, true) : null;

        $paidKobo     = (int) ($result['data']['amount'] ?? -1);
        $expectedKobo = (int) round($params['amount'] * 100);
        $success = !empty($result['data']) && ($result['data']['status'] ?? '') === 'success'
            && $paidKobo === $expectedKobo
            && ($result['data']['reference'] ?? '') === $ref;

        if ($success) {
            $this->veltrix_wallet_model->completePending($params['branch_id'], $params['amount'], $ref, 'Wallet top-up via Paystack');
            set_alert('success', 'Wallet funded successfully.');
        } else {
            // Do NOT mark this failed here -- a curl blip or a Paystack
            // status that isn't "success" yet doesn't mean the charge
            // failed. Leave it 'pending' so the reconciliation cron
            // (Cron_api::veltrix_reconcile_command) can check Paystack
            // directly and resolve it either way.
            set_alert('info', 'We could not confirm this payment yet. It will be checked automatically shortly -- refresh this page in a few minutes.');
        }
        redirect(base_url('veltrixwallet'));
    }

    /**
     * Superadmin-only manual balance correction, for cases like a Paystack
     * charge confirmed successful (checked directly against Paystack) that
     * still isn't reflected here -- e.g. it predates a pending row ever
     * being recorded, or the reconciliation cron hasn't caught up yet.
     * Deliberately gated on is_superadmin_loggedin() directly rather than
     * the 'veltrix_wallet' permission a school's own admin can hold --
     * a school must never be able to grant itself free wallet balance.
     * Reuses credit()/debit() (channel 'manual'), the same model methods
     * the reconciliation cron itself calls, so this is the same trusted
     * code path Paystack verification uses, just triggered by a human.
     */
    public function admin_adjust()
    {
        if (!is_superadmin_loggedin()) {
            access_denied();
        }

        $this->form_validation->set_rules('branch_id', 'School', 'trim|required|numeric');
        $this->form_validation->set_rules('type', 'Type', 'trim|required|in_list[credit,debit]');
        $this->form_validation->set_rules('amount', 'Amount', 'trim|required|numeric|greater_than[0]');
        $this->form_validation->set_rules('note', 'Reason', 'trim|required');
        if ($this->form_validation->run() === false) {
            set_alert('error', strip_tags(validation_errors()));
            redirect(base_url('veltrixwallet?branch_id=' . (int) $this->input->post('branch_id')));
        }

        $branchId = (int) $this->input->post('branch_id');
        $type     = $this->input->post('type');
        $amount   = round((float) $this->input->post('amount'), 2);
        $note     = trim($this->input->post('note'));
        $admin    = $this->session->userdata('name') ?: 'Superadmin';
        $reference    = 'MANUAL-' . $branchId . '-' . time();
        $description  = "Manual $type by $admin: $note";

        if ($type === 'credit') {
            $this->veltrix_wallet_model->credit($branchId, $amount, 'manual', $reference, $description);
            set_alert('success', 'Wallet credited.');
        } else {
            $ok = $this->veltrix_wallet_model->debit($branchId, $amount, 'manual', $reference, $description);
            set_alert($ok ? 'success' : 'error', $ok ? 'Wallet debited.' : 'Insufficient balance to debit that amount.');
        }

        redirect(base_url('veltrixwallet?branch_id=' . $branchId));
    }

    private function getPaymentConfig()
    {
        return $this->db->where('branch_id', $this->globalPaymentID)->get('payment_config')->row_array();
    }
}
