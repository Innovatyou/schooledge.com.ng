<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_749 extends CI_Migration
{
    public function up()
    {
        // Which parts of a school website are hidden (JSON list of website_sections() keys).
        if ($this->db->table_exists('front_cms_setting') && !$this->db->field_exists('hidden_sections', 'front_cms_setting')) {
            $this->db->query("ALTER TABLE `front_cms_setting` ADD `hidden_sections` TEXT NULL");
        }
    }
}
