<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_719 extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('fee_collection_requests')) {
            $this->dbforge->add_field(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'auto_increment' => true),
                'branch_id' => array('type' => 'INT', 'constraint' => 11),
                'student_enroll_id' => array('type' => 'INT', 'constraint' => 11),
                'collected_by' => array('type' => 'INT', 'constraint' => 11),
                'date' => array('type' => 'DATE'),
                'pay_via' => array('type' => 'VARCHAR', 'constraint' => 20, 'null' => true),
                'account_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true),
                'remarks' => array('type' => 'TEXT', 'null' => true),
                'guardian_sms' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 0),
                'total_amount' => array('type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0.00),
                'total_discount' => array('type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0.00),
                'total_fine' => array('type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0.00),
                'status' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'comment' => '1=pending,2=approved,3=rejected'),
                'approved_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true),
                'comments' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
                'submit_date' => array('type' => 'DATETIME', 'null' => true),
                'approve_date' => array('type' => 'DATETIME', 'null' => true),
                'created_at' => array('type' => 'TIMESTAMP', 'default' => 'CURRENT_TIMESTAMP'),
            ));
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('fee_collection_requests');
            $this->db->query('ALTER TABLE `fee_collection_requests` ADD INDEX `idx_branch` (`branch_id`)');
            $this->db->query('ALTER TABLE `fee_collection_requests` ADD INDEX `idx_enroll` (`student_enroll_id`)');
        }

        if (!$this->db->table_exists('fee_collection_request_items')) {
            $this->dbforge->add_field(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'auto_increment' => true),
                'request_id' => array('type' => 'INT', 'constraint' => 11),
                'allocation_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true),
                'type_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true),
                'transport_fee_details_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true),
                'amount' => array('type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0.00),
                'discount' => array('type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0.00),
                'fine' => array('type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0.00),
                'date' => array('type' => 'DATE', 'null' => true),
                'pay_via' => array('type' => 'VARCHAR', 'constraint' => 20, 'null' => true),
                'account_id' => array('type' => 'INT', 'constraint' => 11, 'null' => true),
                'remarks' => array('type' => 'TEXT', 'null' => true),
            ));
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('fee_collection_request_items');
            $this->db->query('ALTER TABLE `fee_collection_request_items` ADD INDEX `idx_request` (`request_id`)');
        }

        // permission: collect_fees_approve (module 16, Student Accounting)
        $this->db->where('prefix', 'collect_fees_approve');
        $existing = $this->db->get('permission')->row();
        if (empty($existing)) {
            $this->db->insert('permission', array(
                'module_id' => 16,
                'name' => 'Collect Fees Approve',
                'prefix' => 'collect_fees_approve',
                'show_view' => 1,
                'show_add' => 1,
                'show_edit' => 0,
                'show_delete' => 0,
            ));
            $collectFeesApproveId = $this->db->insert_id();
        } else {
            $collectFeesApproveId = $existing->id;
        }
        $grants = array(
            2 => array(1, 1, 0, 0), // Admin
            3 => array(0, 0, 0, 0), // Teacher
            4 => array(1, 1, 0, 0), // Accountant
            5 => array(0, 0, 0, 0), // Librarian
            6 => array(0, 0, 0, 0), // Parent
            7 => array(0, 0, 0, 0), // Student
            8 => array(0, 0, 0, 0), // Receptionist
        );
        foreach ($grants as $roleId => $grant) {
            $this->db->where(array('role_id' => $roleId, 'permission_id' => $collectFeesApproveId));
            $exists = $this->db->get('staff_privileges')->row();
            if (empty($exists)) {
                $this->db->insert('staff_privileges', array(
                    'role_id' => $roleId,
                    'permission_id' => $collectFeesApproveId,
                    'is_view' => $grant[0],
                    'is_add' => $grant[1],
                    'is_edit' => $grant[2],
                    'is_delete' => $grant[3],
                ));
            }
        }
    }
}
