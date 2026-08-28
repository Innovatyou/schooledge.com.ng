<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_723 extends CI_Migration
{
    public function up()
    {
        $tables = array();
        $tables['mobile_notification_inbox'] = <<<'SQL'
CREATE TABLE mobile_notification_inbox (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, membership_id BIGINT UNSIGNED NOT NULL, branch_id INT NOT NULL, category VARCHAR(50) NOT NULL, title VARCHAR(180) NOT NULL, body TEXT NOT NULL, data_json TEXT NULL, read_at DATETIME NULL, created_at DATETIME NOT NULL, expires_at DATETIME NULL, KEY idx_mobile_inbox (membership_id,read_at,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;
        $tables['mobile_notification_preferences'] = <<<'SQL'
CREATE TABLE mobile_notification_preferences (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, membership_id BIGINT UNSIGNED NOT NULL, category VARCHAR(50) NOT NULL, push_enabled TINYINT(1) NOT NULL DEFAULT 1, inbox_enabled TINYINT(1) NOT NULL DEFAULT 1, email_enabled TINYINT(1) NOT NULL DEFAULT 0, updated_at DATETIME NOT NULL, UNIQUE KEY uq_mobile_notification_pref (membership_id,category)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;
        foreach ($tables as $table => $sql) if (!$this->db->table_exists($table)) $this->db->query($sql);
    }
}
