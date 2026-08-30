<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Every currently-enrolled student's wallet balance, joined in even for
 * students who have never had a wallet row created yet (IFNULL to 0) so
 * staff can find and credit a student's wallet for the first time.
 */
class Wallet_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getWalletList($branchID, $search = '')
    {
        $this->db->select('e.student_id,e.class_id,e.section_id,CONCAT_WS(" ",s.first_name,s.last_name) as fullname,s.register_no,c.name as class_name,se.name as section_name,IFNULL(w.balance,0) as balance,w.currency');
        $this->db->from('enroll as e');
        $this->db->join('student as s', 's.id = e.student_id', 'inner');
        $this->db->join('class as c', 'c.id = e.class_id', 'left');
        $this->db->join('section as se', 'se.id = e.section_id', 'left');
        $this->db->join('student_wallets as w', 'w.student_id = e.student_id and w.branch_id = e.branch_id', 'left');
        $this->db->where(array('e.branch_id' => $branchID, 'e.session_id' => get_session_id(), 's.active' => 1));
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('s.first_name', $search);
            $this->db->or_like('s.last_name', $search);
            $this->db->or_like('s.register_no', $search);
            $this->db->group_end();
        }
        $this->db->order_by('s.first_name', 'asc');
        return $this->db->get()->result();
    }

    public function getWallet($branchID, $studentID)
    {
        return $this->db->where(array('branch_id' => $branchID, 'student_id' => $studentID))->get('student_wallets')->row_array();
    }

    public function getOrCreateWallet($branchID, $studentID)
    {
        $wallet = $this->getWallet($branchID, $studentID);
        if ($wallet) {
            return $wallet;
        }
        $now = date('Y-m-d H:i:s');
        $this->db->insert('student_wallets', array(
            'branch_id' => $branchID, 'student_id' => $studentID, 'balance' => 0,
            'currency' => 'NGN', 'created_at' => $now, 'updated_at' => $now,
        ));
        return $this->getWallet($branchID, $studentID);
    }

    public function getTransactions($branchID, $studentID, $limit = 100)
    {
        return $this->db->where(array('branch_id' => $branchID, 'student_id' => $studentID))
            ->order_by('id', 'desc')->limit($limit)
            ->get('student_wallet_transactions')->result();
    }

    /**
     * Race-safe credit/debit: the wallet row is created first (if missing),
     * then the balance change is applied with a guarded UPDATE - for a debit,
     * `balance >= amount` in the WHERE clause means a concurrent request that
     * would overdraw the wallet simply updates 0 rows instead of racing past
     * the check, the same idiom Fees::verify() uses for payment_transactions.
     * Returns true on success, false if a debit couldn't be covered.
     */
    public function adjustBalance($branchID, $studentID, $type, $amount, $source, $remarks = null, $actorRoleID = null, $actorUserID = null, $referenceType = null, $referenceID = null)
    {
        $wallet = $this->getOrCreateWallet($branchID, $studentID);
        $amount = round((float)$amount, 2);
        if ($amount <= 0) {
            return false;
        }

        if ($type === 'debit') {
            $this->db->where(array('id' => $wallet['id'], 'branch_id' => $branchID))->where('balance >=', $amount);
            $this->db->set('balance', 'balance - ' . (float)$amount, false);
        } else {
            $this->db->where(array('id' => $wallet['id'], 'branch_id' => $branchID));
            $this->db->set('balance', 'balance + ' . (float)$amount, false);
        }
        $this->db->set('updated_at', date('Y-m-d H:i:s'));
        $this->db->update('student_wallets');
        if ($this->db->affected_rows() !== 1) {
            return false;
        }

        $balanceAfter = $this->getWallet($branchID, $studentID)['balance'];
        $this->db->insert('student_wallet_transactions', array(
            'branch_id' => $branchID, 'student_id' => $studentID, 'type' => $type,
            'amount' => $amount, 'balance_after' => $balanceAfter, 'source' => $source,
            'reference_type' => $referenceType, 'reference_id' => $referenceID,
            'actor_role_id' => $actorRoleID, 'actor_user_id' => $actorUserID,
            'remarks' => $remarks, 'created_at' => date('Y-m-d H:i:s'),
        ));
        return true;
    }
}
