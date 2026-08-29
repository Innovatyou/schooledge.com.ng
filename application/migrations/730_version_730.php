<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_730 extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('schooledge_points_ledger')) $this->db->query("CREATE TABLE schooledge_points_ledger (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,branch_id INT NOT NULL,enroll_id INT NOT NULL,points INT NOT NULL,reason_code VARCHAR(40) NOT NULL,reason_label VARCHAR(150) NOT NULL,related_type VARCHAR(40) NOT NULL,related_id INT NOT NULL,created_at DATETIME NOT NULL,UNIQUE KEY uniq_award(enroll_id,reason_code,related_type,related_id),KEY idx_branch_enroll(branch_id,enroll_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        if (!$this->db->table_exists('schooledge_badges')) {
            $this->db->query("CREATE TABLE schooledge_badges (id INT AUTO_INCREMENT PRIMARY KEY,branch_id INT NULL,code VARCHAR(60) NOT NULL,name VARCHAR(120) NOT NULL,description VARCHAR(255) NOT NULL DEFAULT '',icon VARCHAR(60) NOT NULL DEFAULT '',UNIQUE KEY uniq_code(code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $this->db->query("INSERT INTO schooledge_badges (branch_id,code,name,description,icon) VALUES
                (NULL,'streak_5','5-Day Streak','Present for 5 attendance records in a row.','local_fire_department'),
                (NULL,'streak_10','10-Day Streak','Present for 10 attendance records in a row.','whatshot'),
                (NULL,'streak_20','20-Day Streak','Present for 20 attendance records in a row.','military_tech'),
                (NULL,'homework_ontime','On Time','Submitted a homework on or before its due date.','check_circle')");
        }

        if (!$this->db->table_exists('schooledge_student_badges')) $this->db->query("CREATE TABLE schooledge_student_badges (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,branch_id INT NOT NULL,enroll_id INT NOT NULL,badge_id INT NOT NULL,awarded_at DATETIME NOT NULL,UNIQUE KEY uniq_student_badge(enroll_id,badge_id),KEY idx_branch_enroll(branch_id,enroll_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
