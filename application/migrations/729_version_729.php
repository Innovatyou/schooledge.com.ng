<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_729 extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('schooledge_qr_attendance_events')) $this->db->query("CREATE TABLE schooledge_qr_attendance_events (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,branch_id INT NOT NULL,enroll_id INT NOT NULL,actor_user_id INT NOT NULL,actor_role_id TINYINT NOT NULL,result VARCHAR(20) NOT NULL,scanned_at DATETIME NOT NULL,KEY idx_branch_time(branch_id,scanned_at),KEY idx_enroll_time(enroll_id,scanned_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
