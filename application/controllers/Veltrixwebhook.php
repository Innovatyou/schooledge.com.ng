<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Paystack webhook -- the authoritative wallet-credit path for Veltrixwallet
 * top-ups, independent of the browser redirect callback
 * (Veltrixwallet::verify_paystack_payment), which never fires if the
 * customer closes the tab right after paying. Both paths funnel through
 * Veltrix_wallet_model::credit(), which is protected against a double
 * credit by the UNIQUE(branch_id, reference) constraint added in migration
 * 740 -- whichever path lands first wins, the other is a silent no-op.
 *
 * Not gated behind login (Paystack calls this server-to-server) and
 * excluded from CSRF checking in config.php, the same way the other
 * payment callback controllers (saas_payment/, feespayment/, ...) are.
 *
 * Configure this URL in the Paystack dashboard under Settings > API Keys &
 * Webhooks: {base_url}veltrixwebhook/paystack
 */
class Veltrixwebhook extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('veltrix_wallet_model');
    }

    public function paystack()
    {
        $raw       = file_get_contents('php://input');
        $signature = $this->input->get_request_header('X-Paystack-Signature', true);
        $secret    = (string) ($this->db->where('branch_id', 9999)->get('payment_config')->row_array()['paystack_secret_key'] ?? '');

        if ($raw === '' || $signature === null || $secret === '') {
            return $this->respond(400);
        }

        // HMAC over the exact raw request body -- this must run before any
        // JSON decoding, and the secret must never be logged alongside it.
        if (!hash_equals(hash_hmac('sha512', $raw, $secret), (string) $signature)) {
            log_message('error', 'Veltrixwebhook: invalid Paystack signature.');
            return $this->respond(400);
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload) || ($payload['event'] ?? '') !== 'charge.success') {
            return $this->respond(200); // signature valid, just nothing for us to act on
        }

        $reference = (string) ($payload['data']['reference'] ?? '');
        if (($payload['data']['status'] ?? '') !== 'success' || !preg_match('/^VWALLET-(\d+)-/', $reference, $m)) {
            // Either not a successful charge, or a Paystack transaction from a
            // different feature (subscriptions, fees, admissions, ...) on the
            // same shared account -- this webhook URL sees all of them.
            return $this->respond(200);
        }

        $branchId = (int) $m[1];
        $amount   = ((int) ($payload['data']['amount'] ?? 0)) / 100;
        if ($branchId > 0 && $amount > 0) {
            $this->veltrix_wallet_model->credit($branchId, $amount, 'topup', $reference, 'Wallet top-up via Paystack (webhook)');
            // A false return here just means this reference was already
            // credited (by the browser callback, or a Paystack retry of this
            // same webhook) -- not an error, nothing further to do.
        }

        return $this->respond(200);
    }

    private function respond($status)
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json')
            ->set_output(json_encode(array('received' => true)));
    }
}
