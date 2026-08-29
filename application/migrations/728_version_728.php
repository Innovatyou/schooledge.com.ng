<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_728 extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('is_lost', 'book_issues')) {
            $this->db->query('ALTER TABLE book_issues ADD COLUMN is_lost TINYINT(1) NOT NULL DEFAULT 0 AFTER status');
        }
        if (!$this->db->field_exists('lost_fine_amount', 'book_issues')) {
            $this->db->query('ALTER TABLE book_issues ADD COLUMN lost_fine_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER fine_amount');
        }
    }
}
