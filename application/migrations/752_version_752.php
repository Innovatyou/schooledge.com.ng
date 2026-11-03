<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_752 extends CI_Migration
{
    public function up()
    {
        // Default full / pass mark per assessment column (set by Report Card Setup, prefilled in the exam timetable).
        if ($this->db->table_exists('exam_mark_distribution') && !$this->db->field_exists('default_full_mark', 'exam_mark_distribution')) {
            $this->db->query("ALTER TABLE `exam_mark_distribution` ADD `default_full_mark` DECIMAL(6,2) NULL, ADD `default_pass_mark` DECIMAL(6,2) NULL");
        }
    }
}
