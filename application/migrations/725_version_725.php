<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_725 extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('payment_transactions')) return;
        $this->db->query(<<<'SQL'
CREATE TABLE payment_transactions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, branch_id INT NOT NULL, membership_id BIGINT UNSIGNED NULL, purpose VARCHAR(40) NOT NULL, resource_type VARCHAR(60) NULL, resource_id VARCHAR(80) NULL, gateway VARCHAR(30) NOT NULL, gateway_reference VARCHAR(150) NULL, idempotency_key VARCHAR(100) NOT NULL, amount DECIMAL(18,2) NOT NULL, currency CHAR(3) NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'created', failure_code VARCHAR(80) NULL, failure_message VARCHAR(255) NULL, gateway_payload LONGTEXT NULL, authorized_at DATETIME NULL, paid_at DATETIME NULL, failed_at DATETIME NULL, cancelled_at DATETIME NULL, refunded_at DATETIME NULL, created_at DATETIME NOT NULL, updated_at DATETIME NULL, UNIQUE KEY uq_payment_idempotency (branch_id,idempotency_key), UNIQUE KEY uq_payment_gateway_reference (gateway,gateway_reference), KEY idx_payment_state (branch_id,status,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL
        );
    }
}
