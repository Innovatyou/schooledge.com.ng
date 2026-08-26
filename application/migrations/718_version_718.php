<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_718 extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('audit_log')) {
            $this->dbforge->add_field(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'actor_user_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true),
                'actor_role_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true),
                'branch_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true),
                'action' => array('type' => 'VARCHAR', 'constraint' => 30),
                'table_name' => array('type' => 'VARCHAR', 'constraint' => 100),
                'record_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true),
                'old_values' => array('type' => 'TEXT', 'null' => true),
                'new_values' => array('type' => 'TEXT', 'null' => true),
                'ip_address' => array('type' => 'VARCHAR', 'constraint' => 45, 'null' => true),
                'request_url' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
                'created_at' => array('type' => 'DATETIME'),
            ));
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('audit_log');
            $this->db->query('ALTER TABLE `audit_log` ADD INDEX `idx_table_record` (`table_name`, `record_id`)');
            $this->db->query('ALTER TABLE `audit_log` ADD INDEX `idx_actor` (`actor_user_id`)');
            $this->db->query('ALTER TABLE `audit_log` ADD INDEX `idx_branch_created` (`branch_id`, `created_at`)');
        }

        if (!$this->db->table_exists('expense_requests')) {
            $this->dbforge->add_field(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'auto_increment' => true),
                'branch_id' => array('type' => 'INT', 'constraint' => 11),
                'account_id' => array('type' => 'INT', 'constraint' => 11),
                'voucher_head_id' => array('type' => 'INT', 'constraint' => 11),
                'ref_no' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
                'amount' => array('type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0.00),
                'date' => array('type' => 'DATE'),
                'pay_via' => array('type' => 'VARCHAR', 'constraint' => 20, 'null' => true),
                'description' => array('type' => 'TEXT', 'null' => true),
                'attachments' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
                'requested_by' => array('type' => 'INT', 'constraint' => 11),
                'status' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'comment' => '1=pending,2=approved,3=rejected'),
                'approved_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true),
                'comments' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
                'submit_date' => array('type' => 'DATETIME', 'null' => true),
                'approve_date' => array('type' => 'DATETIME', 'null' => true),
                'transaction_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true, 'comment' => 'transactions.id once approved/posted'),
                'created_at' => array('type' => 'TIMESTAMP', 'default' => 'CURRENT_TIMESTAMP'),
            ));
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('expense_requests');
            $this->db->query('ALTER TABLE `expense_requests` ADD INDEX `idx_branch` (`branch_id`)');
        }

        // permission: expense_approve (module 17, Office Accounting)
        $this->db->where('prefix', 'expense_approve');
        $existing = $this->db->get('permission')->row();
        if (empty($existing)) {
            $this->db->insert('permission', array(
                'module_id' => 17,
                'name' => 'Expense Approve',
                'prefix' => 'expense_approve',
                'show_view' => 1,
                'show_add' => 1,
                'show_edit' => 0,
                'show_delete' => 0,
            ));
            $expenseApprovePermissionId = $this->db->insert_id();
        } else {
            $expenseApprovePermissionId = $existing->id;
        }
        $expenseApproveGrants = array(
            2 => array(1, 1, 0, 0), // Admin
            3 => array(0, 0, 0, 0), // Teacher
            4 => array(1, 1, 0, 0), // Accountant
            5 => array(0, 0, 0, 0), // Librarian
            6 => array(0, 0, 0, 0), // Parent
            7 => array(0, 0, 0, 0), // Student
            8 => array(0, 0, 0, 0), // Receptionist
        );
        foreach ($expenseApproveGrants as $roleId => $grant) {
            $this->db->where(array('role_id' => $roleId, 'permission_id' => $expenseApprovePermissionId));
            $exists = $this->db->get('staff_privileges')->row();
            if (empty($exists)) {
                $this->db->insert('staff_privileges', array(
                    'role_id' => $roleId,
                    'permission_id' => $expenseApprovePermissionId,
                    'is_view' => $grant[0],
                    'is_add' => $grant[1],
                    'is_edit' => $grant[2],
                    'is_delete' => $grant[3],
                ));
            }
        }

        // permission: audit_log (module 18, Settings), view-only
        $this->db->where('prefix', 'audit_log');
        $existing = $this->db->get('permission')->row();
        if (empty($existing)) {
            $this->db->insert('permission', array(
                'module_id' => 18,
                'name' => 'Audit Log',
                'prefix' => 'audit_log',
                'show_view' => 1,
                'show_add' => 0,
                'show_edit' => 0,
                'show_delete' => 0,
            ));
            $auditLogPermissionId = $this->db->insert_id();
        } else {
            $auditLogPermissionId = $existing->id;
        }
        $auditLogGrants = array(
            2 => array(1, 0, 0, 0), // Admin
            3 => array(0, 0, 0, 0), // Teacher
            4 => array(1, 0, 0, 0), // Accountant
            5 => array(0, 0, 0, 0), // Librarian
            6 => array(0, 0, 0, 0), // Parent
            7 => array(0, 0, 0, 0), // Student
            8 => array(0, 0, 0, 0), // Receptionist
        );
        foreach ($auditLogGrants as $roleId => $grant) {
            $this->db->where(array('role_id' => $roleId, 'permission_id' => $auditLogPermissionId));
            $exists = $this->db->get('staff_privileges')->row();
            if (empty($exists)) {
                $this->db->insert('staff_privileges', array(
                    'role_id' => $roleId,
                    'permission_id' => $auditLogPermissionId,
                    'is_view' => $grant[0],
                    'is_add' => $grant[1],
                    'is_edit' => $grant[2],
                    'is_delete' => $grant[3],
                ));
            }
        }
    }
}
