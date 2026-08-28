<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_722 extends CI_Migration
{
    public function up()
    {
        $tables = array();
        $tables['mobile_refresh_tokens'] = <<<'SQL'
CREATE TABLE mobile_refresh_tokens (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, membership_id BIGINT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL, family_id CHAR(36) NOT NULL, device_id BIGINT UNSIGNED NULL, expires_at DATETIME NOT NULL, last_used_at DATETIME NULL, revoked_at DATETIME NULL, replaced_by_id BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL, created_ip VARCHAR(45) NULL, UNIQUE KEY uq_mobile_refresh_hash (token_hash), KEY idx_mobile_refresh_family (family_id), KEY idx_mobile_refresh_expiry (membership_id,expires_at,revoked_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;
        $tables['mobile_devices'] = <<<'SQL'
CREATE TABLE mobile_devices (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, membership_id BIGINT UNSIGNED NOT NULL, installation_id VARCHAR(100) NOT NULL, platform VARCHAR(20) NOT NULL, push_token VARCHAR(255) NULL, app_version VARCHAR(30) NULL, device_name VARCHAR(100) NULL, locale VARCHAR(15) NULL, push_enabled TINYINT(1) NOT NULL DEFAULT 0, last_seen_at DATETIME NULL, revoked_at DATETIME NULL, created_at DATETIME NOT NULL, updated_at DATETIME NULL, UNIQUE KEY uq_mobile_device_installation (membership_id,installation_id), KEY idx_mobile_device_push (push_token)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;
        foreach ($tables as $table => $sql) if (!$this->db->table_exists($table)) $this->db->query($sql);
    }
}
