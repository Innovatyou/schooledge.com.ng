<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_738 extends CI_Migration
{
    public function up()
    {
        // Student wallets - a per-student balance a parent can fund and spend
        // against any of that student's fees. Distinct from school_wallet/
        // school_wallet_transaction (migration 737, a per-branch SMS/email
        // credit balance) - two different kinds of "wallet" in this app.
        if (!$this->db->table_exists('student_wallets')) {
            $this->db->query("CREATE TABLE student_wallets (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, branch_id INT NOT NULL, student_id INT NOT NULL, balance DECIMAL(18,2) NOT NULL DEFAULT 0.00, currency VARCHAR(10) NOT NULL DEFAULT 'NGN', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE KEY uniq_branch_student (branch_id, student_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$this->db->table_exists('student_wallet_transactions')) {
            $this->db->query("CREATE TABLE student_wallet_transactions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, branch_id INT NOT NULL, student_id INT NOT NULL, type ENUM('credit','debit') NOT NULL, amount DECIMAL(18,2) NOT NULL, balance_after DECIMAL(18,2) NOT NULL, source VARCHAR(20) NOT NULL, reference_type VARCHAR(40) NULL, reference_id INT NULL, actor_role_id INT NULL, actor_user_id INT NULL, remarks VARCHAR(255) NULL, created_at DATETIME NOT NULL, KEY idx_branch_student (branch_id, student_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if ($this->db->table_exists('payment_types') && !$this->db->where('name', 'Wallet')->get('payment_types')->row()) {
            $this->db->insert('payment_types', array('name' => 'Wallet'));
        }

        $this->db->where('prefix', 'wallet');
        $existing = $this->db->get('permission')->row();
        if (empty($existing)) {
            $this->db->insert('permission', array(
                'module_id' => 16,
                'name' => 'Student Wallet',
                'prefix' => 'wallet',
                'show_view' => 1,
                'show_add' => 1,
                'show_edit' => 1,
                'show_delete' => 0,
            ));
            $walletPermissionId = $this->db->insert_id();
        } else {
            $walletPermissionId = $existing->id;
        }
        $walletGrants = array(
            2 => array(1, 1, 1, 0), // Admin
            3 => array(0, 0, 0, 0), // Teacher
            4 => array(1, 1, 1, 0), // Accountant
            5 => array(0, 0, 0, 0), // Librarian
            6 => array(0, 0, 0, 0), // Parent
            7 => array(0, 0, 0, 0), // Student
            8 => array(0, 0, 0, 0), // Receptionist
        );
        foreach ($walletGrants as $roleId => $grant) {
            $this->db->where(array('role_id' => $roleId, 'permission_id' => $walletPermissionId));
            $exists = $this->db->get('staff_privileges')->row();
            if (empty($exists)) {
                $this->db->insert('staff_privileges', array(
                    'role_id' => $roleId,
                    'permission_id' => $walletPermissionId,
                    'is_view' => $grant[0],
                    'is_add' => $grant[1],
                    'is_edit' => $grant[2],
                    'is_delete' => $grant[3],
                ));
            }
        }
    }
}
