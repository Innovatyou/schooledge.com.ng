<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Payment_callback_verifier
{
    public function payumoney(array $post, $salt, $expectedTxn, $expectedAmount)
    {
        $required = array('status','txnid','amount','productinfo','firstname','email','key','hash');
        foreach ($required as $key) if (!isset($post[$key])) return false;
        if ($post['status'] !== 'success' || !hash_equals((string)$expectedTxn, (string)$post['txnid'])) return false;
        if (abs((float)$post['amount'] - (float)$expectedAmount) > 0.01) return false;
        $additional = isset($post['additionalCharges']) ? $post['additionalCharges'] . '|' : '';
        $sequence = $additional . $salt . '|' . $post['status'] . '|||||||||||' . $post['email'] . '|' . $post['firstname'] . '|' . $post['productinfo'] . '|' . $post['amount'] . '|' . $post['txnid'] . '|' . $post['key'];
        return hash_equals(strtolower((string)$post['hash']), strtolower(hash('sha512', $sequence)));
    }

    public function jazzcash(array $post, $salt, $expectedTxn, $expectedAmount)
    {
        if (empty($post['pp_SecureHash']) || ($post['pp_ResponseCode'] ?? null) !== '000') return false;
        if (!hash_equals((string)$expectedTxn, (string)($post['pp_TxnRefNo'] ?? ''))) return false;
        if ((int)round($expectedAmount * 100) !== (int)($post['pp_Amount'] ?? -1)) return false;
        $providedHash = $post['pp_SecureHash'];
        unset($post['pp_SecureHash']);
        ksort($post);
        $values = array($salt);
        foreach ($post as $value) if ($value !== '') $values[] = $value;
        return hash_equals(strtolower((string)$providedHash), strtolower(hash_hmac('sha256', implode('&', $values), $salt)));
    }

    public function midtrans($orderId, $expectedAmount)
    {
        try {
            $status = \Midtrans\Transaction::status($orderId);
            $accepted = in_array($status->transaction_status ?? '', array('capture','settlement'), true);
            return $accepted && ($status->fraud_status ?? 'accept') === 'accept'
                && hash_equals((string)$orderId, (string)($status->order_id ?? ''))
                && abs((float)($status->gross_amount ?? 0) - (float)$expectedAmount) <= 0.01 ? $status : false;
        } catch (Exception $exception) {
            log_message('error', 'Midtrans verification failed: ' . $exception->getMessage());
            return false;
        }
    }
}
