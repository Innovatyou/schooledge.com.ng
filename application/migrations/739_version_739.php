<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_739 extends CI_Migration
{
    public function up()
    {
        // One verified Veltrix sending domain per school (self-service domain
        // setup, migration 737's Veltrix integration). One row per branch --
        // mirrors email_config's one-row-per-branch shape.
        if (!$this->db->table_exists('school_veltrix_domain')) {
            $this->db->query("CREATE TABLE school_veltrix_domain (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, branch_id INT NOT NULL, domain_uid VARCHAR(20) NOT NULL, name VARCHAR(255) NOT NULL, dns_host VARCHAR(255) NOT NULL, dns_value TEXT NOT NULL, verified TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, updated_at DATETIME NULL, UNIQUE KEY uniq_branch (branch_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        $this->db->where('prefix', 'veltrix_wallet');
        $existing = $this->db->get('permission')->row();
        if (empty($existing)) {
            $this->db->insert('permission', array(
                'module_id' => 16,
                'name' => 'SMS/Email Wallet',
                'prefix' => 'veltrix_wallet',
                'show_view' => 1,
                'show_add' => 1,
                'show_edit' => 0,
                'show_delete' => 0,
            ));
            $walletPermissionId = $this->db->insert_id();
        } else {
            $walletPermissionId = $existing->id;
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
