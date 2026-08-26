<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_720 extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('online_admission_staging')) {
            $this->dbforge->add_field(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'auto_increment' => true),
                'online_admission_id' => array('type' => 'INT', 'constraint' => 11),
                'branch_id' => array('type' => 'INT', 'constraint' => 11),
                'staged_by' => array('type' => 'INT', 'constraint' => 11),
                'staged_payload' => array('type' => 'LONGTEXT'),
                'status' => array('type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'comment' => '1=pending checker,2=approved,3=rejected'),
                'reviewed_by' => array('type' => 'INT', 'constraint' => 11, 'null' => true),
                'comments' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
                'staged_at' => array('type' => 'DATETIME', 'null' => true),
                'reviewed_at' => array('type' => 'DATETIME', 'null' => true),
                'created_at' => array('type' => 'TIMESTAMP', 'default' => 'CURRENT_TIMESTAMP'),
            ));
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('online_admission_staging');
            $this->db->query('ALTER TABLE `online_admission_staging` ADD INDEX `idx_admission` (`online_admission_id`)');
            $this->db->query('ALTER TABLE `online_admission_staging` ADD INDEX `idx_branch` (`branch_id`)');
        }

        // permission: online_admission_approve (module 2, same module as online_admission)
        $this->db->where('prefix', 'online_admission_approve');
        $existing = $this->db->get('permission')->row();
        if (empty($existing)) {
            $this->db->insert('permission', array(
                'module_id' => 2,
                'name' => 'Online Admission Approve',
                'prefix' => 'online_admission_approve',
                'show_view' => 1,
                'show_add' => 1,
                'show_edit' => 0,
                'show_delete' => 0,
            ));
            $permId = $this->db->insert_id();
        } else {
            $permId = $existing->id;
        }
        $grants = array(
            2 => array(1, 1, 0, 0), // Admin
            3 => array(0, 0, 0, 0), // Teacher
            4 => array(0, 0, 0, 0), // Accountant
            5 => array(0, 0, 0, 0), // Librarian
            6 => array(0, 0, 0, 0), // Parent
            7 => array(0, 0, 0, 0), // Student
            8 => array(0, 0, 0, 0), // Receptionist
        );
        foreach ($grants as $roleId => $grant) {
            $this->db->where(array('role_id' => $roleId, 'permission_id' => $permId));
            $exists = $this->db->get('staff_privileges')->row();
            if (empty($exists)) {
                $this->db->insert('staff_privileges', array(
                    'role_id' => $roleId,
                    'permission_id' => $permId,
                    'is_view' => $grant[0],
                    'is_add' => $grant[1],
                    'is_edit' => $grant[2],
                    'is_delete' => $grant[3],
                ));
            }
        }
    }
}
