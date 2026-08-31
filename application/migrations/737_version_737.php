<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_737 extends CI_Migration
{
    public function up()
    {
        // Veltrix SMS/email gateway option + per-school wallet ledger.
        if ($this->db->table_exists('sms_api') && !$this->db->where('id', 9)->get('sms_api')->row()) {
            $this->db->query("INSERT INTO sms_api (id, name) VALUES (9, 'veltrix')");
        }

        if (!$this->db->table_exists('school_wallet')) {
            $this->db->query("CREATE TABLE school_wallet (branch_id INT NOT NULL PRIMARY KEY, balance DECIMAL(12,2) NOT NULL DEFAULT 0.00, updated_at DATETIME NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!$this->db->table_exists('school_wallet_transaction')) {
            $this->db->query("CREATE TABLE school_wallet_transaction (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, branch_id INT NOT NULL, type VARCHAR(10) NOT NULL, channel VARCHAR(10) NOT NULL, amount DECIMAL(12,2) NOT NULL, balance_after DECIMAL(12,2) NOT NULL, reference VARCHAR(60) NULL, description VARCHAR(255) NULL, created_at DATETIME NOT NULL, KEY idx_branch_created (branch_id, created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }
}
