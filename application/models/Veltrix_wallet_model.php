<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Per-school (per-branch) prepaid wallet for Veltrix-billed SMS/email.
 * Consumption billing, independent of the school's subscription plan.
 *
 * Not to be confused with Wallet_model (student_wallets) - that's a
 * per-student balance for fee payments, a completely different feature that
 * happens to also be called "wallet". Tables here are school_wallet /
 * school_wallet_transaction (migration 737).
 */
class Veltrix_wallet_model extends CI_Model
{
    public function getBalance($branchId)
    {
        $row = $this->db->where('branch_id', $branchId)->get('school_wallet')->row_array();
        return $row ? (float) $row['balance'] : 0.0;
    }

    public function hasBalance($branchId, $amount)
    {
        return $this->getBalance($branchId) >= $amount;
    }

    public function getTransactions($branchId, $limit = 50)
    {
        return $this->db->where('branch_id', $branchId)
            ->order_by('id', 'desc')->limit($limit)
            ->get('school_wallet_transaction')->result_array();
    }

    /**
     * Debit $amount from the branch wallet if (and only if) it has enough
     * balance, recording a ledger row. Row-locked so two concurrent sends
     * can't both read the same balance and double-spend it -- the lock only
     * bites once a wallet row exists, which is fine since a school must top
     * up (creating the row via credit()) before it can ever spend.
     *
     * @return bool true on success, false if balance was insufficient
     */
    public function debit($branchId, $amount, $channel, $reference = '', $description = '')
    {
        $this->db->trans_start();
        $row     = $this->db->query('SELECT balance FROM school_wallet WHERE branch_id = ? FOR UPDATE', array($branchId))->row_array();
        $balance = $row ? (float) $row['balance'] : 0.0;

        if ($balance < $amount) {
            $this->db->trans_complete();
            return false;
        }

        $this->applyBalance($branchId, $row, $balance - $amount);
        $this->logTransaction($branchId, 'debit', $channel, $amount, $balance - $amount, $reference, $description);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * @return bool
     */
    public function credit($branchId, $amount, $channel, $reference = '', $description = '')
    {
        $this->db->trans_start();
        $row        = $this->db->query('SELECT balance FROM school_wallet WHERE branch_id = ? FOR UPDATE', array($branchId))->row_array();
        $balance    = $row ? (float) $row['balance'] : 0.0;
        $newBalance = $balance + $amount;

        $this->applyBalance($branchId, $row, $newBalance);
        $this->logTransaction($branchId, 'credit', $channel, $amount, $newBalance, $reference, $description);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /** Credit back a previously debited amount, e.g. after a failed send. */
    public function refund($branchId, $amount, $channel, $reference = '', $description = '')
    {
        return $this->credit($branchId, $amount, $channel, $reference !== '' ? ('REFUND-' . $reference) : '', $description);
    }

    private function applyBalance($branchId, $existingRow, $newBalance)
    {
        if ($existingRow) {
            $this->db->where('branch_id', $branchId)->update('school_wallet', array(
                'balance'    => $newBalance,
                'updated_at' => date('Y-m-d H:i:s'),
            ));
        } else {
            $this->db->insert('school_wallet', array(
                'branch_id'  => $branchId,
                'balance'    => $newBalance,
                'updated_at' => date('Y-m-d H:i:s'),
            ));
        }
    }

    private function logTransaction($branchId, $type, $channel, $amount, $balanceAfter, $reference, $description)
    {
        $this->db->insert('school_wallet_transaction', array(
            'branch_id'     => $branchId,
            'type'          => $type,
            'channel'       => $channel,
            'amount'        => $amount,
            'balance_after' => $balanceAfter,
            'reference'     => $reference,
            'description'   => $description,
            'created_at'    => date('Y-m-d H:i:s'),
        ));
    }
}
