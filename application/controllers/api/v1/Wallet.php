<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * Mobile student wallet API: balance, ledger, and Paystack top-up. Mirrors
 * Fees.php's checkout()/verify() state-machine exactly, reusing the same
 * payment_transactions table (purpose='wallet_topup') instead of a new one.
 * Spending the wallet against a fee happens on Fees::pay_with_wallet()
 * instead, since that action needs both wallet and fee-allocation context.
 */
class Wallet extends Api_Controller
{
    private $supportedGateways = array('paystack');

    public function __construct()
    {
        parent::__construct();
        $this->load->model('wallet_model');
        $this->load->model('authentication_model');
    }

    public function summary()
    {
        $membership = $this->requireAuth();
        $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
        $wallet = $this->wallet_model->getOrCreateWallet($membership['branch_id'], $enrollment['student_id']);
        $branch = $this->db->select('currency,symbol,symbol_position')->where('id', $membership['branch_id'])->get('branch')->row_array();
        $this->ok(array(
            'student' => array('id' => (int)$enrollment['student_id'], 'name' => $enrollment['student_name']),
            'balance' => round((float)$wallet['balance'], 2),
            'currency' => array('code' => $branch['currency'] ?? 'NGN', 'symbol' => $branch['symbol'] ?? '₦', 'symbol_position' => (int)($branch['symbol_position'] ?? 1)),
        ));
    }

    public function history()
    {
        $membership = $this->requireAuth();
        $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
        $rows = $this->wallet_model->getTransactions($membership['branch_id'], $enrollment['student_id']);
        $this->ok(array_map(function ($row) {
            return array(
                'id' => (int)$row->id, 'date' => $row->created_at, 'type' => $row->type,
                'amount' => (float)$row->amount, 'balance_after' => (float)$row->balance_after,
                'source' => $row->source, 'remarks' => $row->remarks,
            );
        }, $rows));
    }

    public function topup_checkout()
    {
        $membership = $this->requireAuth();
        $this->blockIfDemoReadonly($membership['branch_id']);
        $input = $this->body();
        if (empty($input['idempotency_key'])) $this->fail('validation_error', 'An idempotency_key is required.', 422, array('idempotency_key' => 'required'));
        $enrollment = $this->resolveOwnedEnrollment($membership, $input['student_id'] ?? null);

        $amount = isset($input['amount']) ? (float)$input['amount'] : 0;
        if ($amount <= 0) $this->fail('validation_error', 'A positive amount is required.', 422, array('amount' => 'required'));

        $gateway = strtolower((string)($input['gateway'] ?? ''));
        if (!in_array($gateway, $this->supportedGateways, true)) $this->fail('gateway_not_configured', 'This payment method is not available yet.', 409);
        $config = $this->db->where('branch_id', $membership['branch_id'])->get('payment_config')->row_array();
        if (!$config || empty($config[$gateway . '_status'])) $this->fail('gateway_not_configured', 'This payment method is not available for your school.', 409);

        $existing = $this->db->where(array('branch_id' => $membership['branch_id'], 'purpose' => 'wallet_topup', 'idempotency_key' => $input['idempotency_key']))->get('payment_transactions')->row_array();
        if ($existing) {
            $this->ok(array('transaction_id' => (int)$existing['id'], 'status' => $existing['status'], 'gateway' => $existing['gateway'], 'reference' => $existing['gateway_reference'], 'checkout_url' => null));
            return;
        }

        $reference = 'WLT' . strtoupper(bin2hex(random_bytes(10)));
        $this->db->insert('payment_transactions', array(
            'branch_id' => $membership['branch_id'], 'membership_id' => $membership['id'], 'purpose' => 'wallet_topup',
            'resource_type' => 'student_wallet', 'resource_id' => (string)$enrollment['student_id'],
            'gateway' => $gateway, 'gateway_reference' => $reference, 'idempotency_key' => $input['idempotency_key'],
            'amount' => $amount, 'currency' => $this->branchCurrency($membership['branch_id']), 'status' => 'created',
            'created_at' => date('Y-m-d H:i:s'),
        ));
        $transactionId = $this->db->insert_id();

        $checkoutUrl = $this->initGateway($gateway, $config, $reference, $amount, $membership);
        if (!$checkoutUrl) {
            $this->db->where('id', $transactionId)->update('payment_transactions', array('status' => 'failed', 'failure_message' => 'Gateway initialization failed', 'failed_at' => date('Y-m-d H:i:s')));
            $this->fail('gateway_error', 'Unable to start the payment. Try again shortly.', 502);
        }
        $this->audit('wallet.topup_started', $membership, $transactionId);
        $this->ok(array('transaction_id' => $transactionId, 'gateway' => $gateway, 'reference' => $reference, 'checkout_url' => $checkoutUrl));
    }

    public function topup_verify($transactionId)
    {
        $membership = $this->requireAuth();
        $this->blockIfDemoReadonly($membership['branch_id']);
        $transaction = $this->db->where(array('id' => (int)$transactionId, 'branch_id' => $membership['branch_id'], 'membership_id' => $membership['id'], 'purpose' => 'wallet_topup'))->get('payment_transactions')->row_array();
        if (!$transaction) $this->fail('transaction_not_found', 'Wallet top-up transaction not found.', 404);

        if ($transaction['status'] === 'success') {
            $this->ok(array('status' => 'success', 'balance' => $this->currentBalance($membership['branch_id'], (int)$transaction['resource_id'])));
            return;
        }
        if (in_array($transaction['status'], array('failed', 'cancelled', 'refunded'), true)) {
            $this->ok(array('status' => $transaction['status'], 'message' => $transaction['failure_message']));
            return;
        }

        $config = $this->db->where('branch_id', $membership['branch_id'])->get('payment_config')->row_array();
        list($verified, $terminal, $failureMessage) = $this->verifyWithGateway($transaction, $config);

        if (!$verified) {
            if ($terminal) {
                $this->db->where('id', $transaction['id'])->update('payment_transactions', array('status' => 'failed', 'failure_message' => $failureMessage, 'failed_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')));
                $this->ok(array('status' => 'failed', 'message' => $failureMessage));
            } else {
                $this->ok(array('status' => 'pending', 'message' => $failureMessage));
            }
            return;
        }

        // Guarded by the current status in the WHERE clause - same idempotency
        // trick Fees::verify() uses, so the wallet credit below can never
        // apply twice for the same transaction even under a replayed call.
        $this->db->where(array('id' => $transaction['id'], 'status' => $transaction['status']))->update('payment_transactions', array('status' => 'success', 'paid_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')));
        if ($this->db->affected_rows() === 1) {
            $studentId = (int)$transaction['resource_id'];
            $this->wallet_model->adjustBalance(
                $membership['branch_id'], $studentId, 'credit', $transaction['amount'], 'topup_gateway',
                'Wallet top-up via ' . ucfirst($transaction['gateway']) . ' Ref: ' . $transaction['gateway_reference'],
                $membership['role_id'], $membership['user_id'], 'payment_transactions', $transaction['id']
            );
            $this->audit('wallet.topup_success', $membership, $transaction['id']);
            $this->notifyMembership($membership['id'], $membership['branch_id'], 'payment', 'Wallet top-up received', 'Your wallet top-up of ' . $transaction['currency'] . ' ' . number_format((float)$transaction['amount'], 2) . ' was received.', array('transaction_id' => $transaction['id']));
        }
        $this->ok(array('status' => 'success', 'balance' => $this->currentBalance($membership['branch_id'], (int)$transaction['resource_id'])));
    }

    private function currentBalance($branchId, $studentId)
    {
        $wallet = $this->wallet_model->getOrCreateWallet($branchId, $studentId);
        return round((float)$wallet['balance'], 2);
    }

    private function branchCurrency($branchId)
    {
        $row = $this->db->select('currency')->where('id', $branchId)->get('branch')->row();
        return ($row && $row->currency) ? $row->currency : 'NGN';
    }

    private function initGateway($gateway, array $config, $reference, $amount, array $membership)
    {
        if ($gateway === 'paystack') {
            if (empty($config['paystack_secret_key'])) return null;
            $user = $this->authentication_model->getUserNameByRoleID($membership['role_id'], $membership['user_id']);
            $payload = array(
                'email' => !empty($user['email']) ? $user['email'] : 'no-reply@schooledge.ng',
                'amount' => (int)round($amount * 100),
                'reference' => $reference,
                'callback_url' => site_url('api/v1/mobile/fees/checkout/complete'),
            );
            $ch = curl_init('https://api.paystack.co/transaction/initialize');
            curl_setopt_array($ch, array(
                CURLOPT_POST => 1, CURLOPT_POSTFIELDS => json_encode($payload), CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $config['paystack_secret_key'], 'Content-Type: application/json'),
                CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_TIMEOUT => 20,
            ));
            $response = curl_exec($ch);
            curl_close($ch);
            $result = $response ? json_decode($response, true) : null;
            return $result['data']['authorization_url'] ?? null;
        }
        return null;
    }

    /** @return array{0: bool, 1: bool, 2: ?string} [verified, terminal, message] */
    private function verifyWithGateway(array $transaction, $config)
    {
        if ($transaction['gateway'] === 'paystack') {
            if (empty($config['paystack_secret_key'])) return array(false, true, 'Gateway not configured');
            $ch = curl_init('https://api.paystack.co/transaction/verify/' . rawurlencode($transaction['gateway_reference']));
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $config['paystack_secret_key']),
                CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_TIMEOUT => 20,
            ));
            $response = curl_exec($ch);
            curl_close($ch);
            $result = $response ? json_decode($response, true) : null;
            $data = $result['data'] ?? null;
            if ($data && $data['status'] === 'success'
                && hash_equals((string)$transaction['gateway_reference'], (string)$data['reference'])
                && (int)round(((float)$transaction['amount']) * 100) === (int)$data['amount']
            ) {
                return array(true, true, null);
            }
            $terminal = $data && in_array($data['status'] ?? '', array('failed', 'reversed'), true);
            return array(false, $terminal, $data['gateway_response'] ?? 'The transaction has not been completed yet.');
        }
        return array(false, true, 'Unsupported gateway');
    }

    private function audit($action, array $membership, $resourceId = null)
    {
        $this->db->insert('mobile_audit_log', array(
            'membership_id' => $membership['id'], 'branch_id' => $membership['branch_id'], 'action' => $action,
            'resource_id' => $resourceId, 'ip_address' => $this->input->ip_address(),
            'user_agent' => substr((string)$this->input->user_agent(), 0, 255), 'created_at' => date('Y-m-d H:i:s'),
        ));
    }
}
