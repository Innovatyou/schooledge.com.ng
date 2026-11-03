<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_750 extends CI_Migration
{
    public function up()
    {
        // Phone badge in the welcome banner: its own number (blank = the school's mobile no) and label.
        if ($this->db->table_exists('front_cms_setting') && !$this->db->field_exists('hero_phone', 'front_cms_setting')) {
            $this->db->query("ALTER TABLE `front_cms_setting` ADD `hero_phone` VARCHAR(50) NULL, ADD `hero_phone_label` VARCHAR(100) NULL");
        }
    }
}
