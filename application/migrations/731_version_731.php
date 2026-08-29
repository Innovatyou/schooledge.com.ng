<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_731 extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('schooledge_safety_alerts')) $this->db->query("CREATE TABLE schooledge_safety_alerts (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,branch_id INT NOT NULL,sender_membership_id BIGINT UNSIGNED NOT NULL,sender_role_id TINYINT NOT NULL,sender_user_id INT NOT NULL,alert_type VARCHAR(10) NOT NULL,latitude DECIMAL(10,7) NOT NULL,longitude DECIMAL(10,7) NOT NULL,accuracy_meters FLOAT NULL,note VARCHAR(255) NULL,status VARCHAR(20) NOT NULL DEFAULT 'open',acknowledged_by_membership_id BIGINT UNSIGNED NULL,acknowledged_at DATETIME NULL,created_at DATETIME NOT NULL,KEY idx_branch_created(branch_id,created_at),KEY idx_sender(sender_role_id,sender_user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
