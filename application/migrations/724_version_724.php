<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_724 extends CI_Migration
{
    public function up()
    {
        $tables = array();
        $tables['school_mobile_config'] = <<<'SQL'
CREATE TABLE school_mobile_config (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, branch_id INT NOT NULL, enabled TINYINT(1) NOT NULL DEFAULT 1, app_name VARCHAR(100) NULL, primary_color VARCHAR(10) NULL, logo_url VARCHAR(255) NULL, config_json TEXT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NULL, UNIQUE KEY uq_school_mobile_branch (branch_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;
        $tables['branded_app_config'] = <<<'SQL'
CREATE TABLE branded_app_config (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, branch_id INT NOT NULL, enabled TINYINT(1) NOT NULL DEFAULT 1, app_name VARCHAR(100) NULL, primary_color VARCHAR(10) NULL, logo_url VARCHAR(255) NULL, android_package VARCHAR(150) NULL, ios_bundle_id VARCHAR(150) NULL, status VARCHAR(20) NOT NULL DEFAULT 'draft', config_json TEXT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NULL, UNIQUE KEY uq_branded_app_branch (branch_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;
        $tables['mobile_audit_log'] = <<<'SQL'
CREATE TABLE mobile_audit_log (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, membership_id BIGINT UNSIGNED NULL, branch_id INT NULL, action VARCHAR(80) NOT NULL, resource_type VARCHAR(80) NULL, resource_id VARCHAR(80) NULL, metadata_json TEXT NULL, ip_address VARCHAR(45) NULL, user_agent VARCHAR(255) NULL, created_at DATETIME NOT NULL, KEY idx_mobile_audit (branch_id,membership_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;
        $tables['mobile_rate_limits'] = <<<'SQL'
CREATE TABLE mobile_rate_limits (rate_key CHAR(64) PRIMARY KEY, window_started_at DATETIME NOT NULL, request_count INT NOT NULL DEFAULT 1, expires_at DATETIME NOT NULL, KEY idx_mobile_rate_expiry (expires_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;
        foreach ($tables as $table => $sql) if (!$this->db->table_exists($table)) $this->db->query($sql);
    }
}
