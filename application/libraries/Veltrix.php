<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared Veltrix (mailwizz) account integration used by every school on this
 * platform. One master API key authenticates every call -- a school never
 * sees or holds it. Credentials live in payment_config (branch_id 9999,
 * migration 742), the same global row every other gateway secret in this
 * app already lives in, edited from Saas::settings_payment() -- not a
 * server config file a deploy could silently overwrite. Per-school spending
 * is metered against that school's own wallet (Veltrix_wallet_model) BEFORE
 * calling Veltrix, since Veltrix only ever sees this one consolidated
 * account.
 */
class Veltrix
{
    private $ci;
    private $apiBase;
    private $apiKey;
    private $smsPrice;
    private $emailPrice;
    private $defaultSenderId;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->model('veltrix_wallet_model');

        $config = $this->ci->db->where('branch_id', 9999)->get('payment_config')->row_array();

        $this->apiBase         = rtrim((string) ($config['veltrix_api_base'] ?? ''), '/');
        $this->apiKey          = (string) ($config['veltrix_api_key'] ?? '');
        $this->smsPrice        = (float) ($config['veltrix_sms_price'] ?? 4.00);
        $this->emailPrice      = (float) ($config['veltrix_email_price'] ?? 1.00);
        $this->defaultSenderId = (string) ($config['veltrix_default_sender_id'] ?? 'SCHLEDGE');
    }

    /**
     * Send one SMS on behalf of $branchId, billed to that school's wallet.
     * Debits upfront and refunds on failure, mirroring how Veltrix bills its
     * own customers.
     *
     * @return bool
     */
    public function sendSmsForBranch($branchId, $to, $message)
    {
        if (!$this->ci->veltrix_wallet_model->hasBalance($branchId, $this->smsPrice)) {
            return false;
        }

        $reference = 'SCHEDGE-SMS-' . $branchId . '-' . uniqid();
        if (!$this->ci->veltrix_wallet_model->debit($branchId, $this->smsPrice, 'sms', $reference, $message)) {
            return false; // lost the race to a concurrent send
        }

        $senderId = $this->resolveSenderId($branchId);
        $result   = $this->request('POST', '/sms-tools/quick-send', array(
            'sender'     => $senderId,
            'message'    => $message,
            'recipients' => $to,
        ));

        $sent = is_array($result) && ($result['status'] ?? '') === 'success' && (int) ($result['data']['sent'] ?? 0) >= 1;
        if (!$sent) {
            $this->ci->veltrix_wallet_model->refund($branchId, $this->smsPrice, 'sms', $reference, 'SMS send failed');
        }
        return $sent;
    }

    /**
     * Send one transactional email on behalf of $branchId, billed to that
     * school's wallet. $fromEmail should be an address on a sending domain
     * the school has verified on the Veltrix account, or deliverability will
     * suffer.
     *
     * @param array{content?:string,name?:string,mime?:string}|null $attachment
     *        A single file to attach (e.g. a marksheet/fee-invoice PDF) --
     *        'content' is the raw (not base64) file bytes already in memory,
     *        matching how Mailer::send()'s $data['file'] is passed around
     *        elsewhere in this app. Null for none.
     * @return bool
     */
    public function sendEmailForBranch($branchId, $to, $subject, $body, $fromEmail, $fromName = '', ?array $attachment = null)
    {
        if (!$this->ci->veltrix_wallet_model->hasBalance($branchId, $this->emailPrice)) {
            return false;
        }

        $reference = 'SCHEDGE-EMAIL-' . $branchId . '-' . uniqid();
        if (!$this->ci->veltrix_wallet_model->debit($branchId, $this->emailPrice, 'email', $reference, $subject)) {
            return false;
        }

        $payload = array(
            'to_email'   => $to,
            'subject'    => $subject,
            'body'       => $body,
            'from_email' => $fromEmail,
            'from_name'  => $fromName,
        );

        if ($attachment !== null && !empty($attachment['content'])) {
            $payload['attachments'] = array(array(
                'filename' => $attachment['name'] ?? 'attachment.pdf',
                'mime'     => $attachment['mime'] ?? 'application/pdf',
                'content'  => base64_encode($attachment['content']),
            ));
        }

        $result = $this->request('POST', '/transactional-emails', $payload);

        $sent = is_array($result) && ($result['status'] ?? '') === 'success';
        if (!$sent) {
            $this->ci->veltrix_wallet_model->refund($branchId, $this->emailPrice, 'email', $reference, 'Email send failed');
        }
        return $sent;
    }

    /**
     * A school's own approved Corporate Sender ID, or the shared default.
     * field_one holds the requested sender ID text, field_two the locally
     * tracked approval status -- an unapproved/rejected/pending ID is never
     * used for an actual send, only the shared default is.
     */
    private function resolveSenderId($branchId)
    {
        $row = $this->ci->db->get_where('sms_credential', array(
            'sms_api_id' => 9,
            'branch_id'  => $branchId,
        ))->row_array();

        $approved = !empty($row) && ($row['field_two'] ?? '') === 'approved' && !empty($row['field_one']);
        return $approved ? $row['field_one'] : $this->defaultSenderId;
    }

    /**
     * Apply for a school's own Corporate Sender ID on the shared Veltrix
     * account. This is a Veltrix-side regulatory application (CAC/telco
     * approval), not instant -- the requested ID is stored locally as
     * 'pending' and is only used for actual sends once checkSenderIdStatus()
     * reports it approved; the shared default sender ID covers sends made
     * in the meantime.
     *
     * @return array{success:bool,error:?string}
     */
    public function createSenderId($branchId, $senderId, $organizationName, $sampleMessage, $useCase = '')
    {
        $result = $this->request('POST', '/sender-ids', array(
            'sender_id'         => $senderId,
            'organization_name' => $organizationName,
            'sample_message'    => $sampleMessage,
            'use_case'          => $useCase,
        ));

        if (!is_array($result) || ($result['status'] ?? '') !== 'success') {
            return array('success' => false, 'error' => $result['error'] ?? 'Could not reach Veltrix.');
        }

        $veltrixId = (int) ($result['data']['record']['id'] ?? 0);
        $existing  = $this->ci->db->get_where('sms_credential', array('sms_api_id' => 9, 'branch_id' => $branchId))->row_array();
        $row       = array(
            'field_one'   => $senderId,
            'field_two'   => 'pending',
            'field_three' => (string) $veltrixId,
            'field_four'  => $organizationName,
            'branch_id'   => $branchId,
            'sms_api_id'  => 9,
        );
        if ($existing) {
            $this->ci->db->where('id', $existing['id'])->update('sms_credential', $row);
        } else {
            $this->ci->db->insert('sms_credential', $row);
        }

        return array('success' => true, 'error' => null);
    }

    /**
     * Poll Veltrix for this school's Corporate Sender ID approval status and
     * update the locally tracked status. Safe to call whether or not one has
     * ever been applied for.
     *
     * @return array{success:bool,status:?string,message:?string,error:?string}
     */
    public function checkSenderIdStatus($branchId)
    {
        $row = $this->ci->db->get_where('sms_credential', array('sms_api_id' => 9, 'branch_id' => $branchId))->row_array();
        $veltrixId = (int) ($row['field_three'] ?? 0);
        if (empty($row) || $veltrixId <= 0) {
            return array('success' => false, 'status' => null, 'message' => null, 'error' => 'No Sender ID application on file.');
        }

        $result = $this->request('POST', "/sender-ids/{$veltrixId}/check-status", array());
        if (!is_array($result) || ($result['status'] ?? '') !== 'success') {
            return array('success' => false, 'status' => $row['field_two'], 'message' => null, 'error' => $result['error'] ?? 'Could not reach Veltrix.');
        }

        $newStatus = (string) ($result['data']['record']['status'] ?? $row['field_two']);
        if ($newStatus !== $row['field_two']) {
            $this->ci->db->where('id', $row['id'])->update('sms_credential', array('field_two' => $newStatus));
        }

        return array(
            'success' => true,
            'status'  => $newStatus,
            'message' => $result['data']['message'] ?? null,
            'error'   => null,
        );
    }

    /**
     * Register a sending domain for $branchId on the shared Veltrix account
     * and return the DNS TXT record the school must publish. Self-service --
     * no approval needed, only DNS verification (see verifySendingDomain()).
     *
     * @return array{success:bool,dns_host:?string,dns_value:?string,error:?string}
     */
    public function createSendingDomain($branchId, $domainName)
    {
        $result = $this->request('POST', '/sending-domains', array('name' => $domainName));
        if (!is_array($result) || ($result['status'] ?? '') !== 'success') {
            return array('success' => false, 'dns_host' => null, 'dns_value' => null, 'error' => $result['error'] ?? 'Could not reach Veltrix.');
        }

        $record  = $result['data']['record'] ?? [];
        $dns     = $record['dns_records'][0] ?? [];
        $dnsHost = (string) ($dns['host'] ?? '');
        $dnsValue = (string) ($dns['value'] ?? '');

        $this->ci->db->where('branch_id', $branchId)->delete('school_veltrix_domain');
        $this->ci->db->insert('school_veltrix_domain', array(
            'branch_id'  => $branchId,
            'domain_uid' => (string) ($record['domain_uid'] ?? ''),
            'name'       => $domainName,
            'dns_host'   => $dnsHost,
            'dns_value'  => $dnsValue,
            'verified'   => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ));

        return array('success' => true, 'dns_host' => $dnsHost, 'dns_value' => $dnsValue, 'error' => null);
    }

    /**
     * Check the DNS TXT record for $branchId's pending sending domain and
     * mark it verified locally if Veltrix confirms it.
     *
     * @return array{success:bool,verified:bool,error:?string}
     */
    public function verifySendingDomain($branchId)
    {
        $row = $this->ci->db->get_where('school_veltrix_domain', array('branch_id' => $branchId))->row_array();
        if (empty($row) || empty($row['domain_uid'])) {
            return array('success' => false, 'verified' => false, 'error' => 'No sending domain on file.');
        }

        $result = $this->request('POST', "/sending-domains/{$row['domain_uid']}/verify", array());
        if (!is_array($result) || ($result['status'] ?? '') !== 'success') {
            return array('success' => false, 'verified' => false, 'error' => $result['error'] ?? 'DNS record not found yet -- allow up to 48h to propagate.');
        }

        $this->ci->db->where('id', $row['id'])->update('school_veltrix_domain', array(
            'verified'   => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ));

        return array('success' => true, 'verified' => true, 'error' => null);
    }

    /** @return array|null decoded JSON body, or null on a transport failure */
    private function request($method, $path, array $body)
    {
        $ch = curl_init($this->apiBase . $path);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => array(
                'Content-Type: application/json',
                'X-MW-PUBLIC-KEY: ' . $this->apiKey,
            ),
        ));
        $raw = curl_exec($ch);
        curl_close($ch);

        if ($raw === false) {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
