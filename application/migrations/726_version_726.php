<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_726 extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('mobile_auth_challenges')) return;
        $this->db->query(<<<'SQL'
CREATE TABLE mobile_auth_challenges (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, challenge_hash CHAR(64) NOT NULL, login_credential_id INT NOT NULL, membership_id BIGINT UNSIGNED NOT NULL, challenge_type VARCHAR(20) NOT NULL, installation_id VARCHAR(100) NULL, attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 5, expires_at DATETIME NOT NULL, last_sent_at DATETIME NULL, consumed_at DATETIME NULL, created_ip VARCHAR(45) NULL, created_at DATETIME NOT NULL, UNIQUE KEY uq_mobile_auth_challenge (challenge_hash), KEY idx_mobile_auth_challenge_expiry (login_credential_id,expires_at,consumed_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL
        );
    }
}
