<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_747 extends CI_Migration
{
    public function up()
    {
        // App store links for the Mobile App page (set by the platform owner).
        $fields = array(
            'play_store_url' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'app_store_url' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'show_apk' => 'TINYINT(1) NOT NULL DEFAULT 1',
        );
        foreach ($fields as $name => $type) {
            if (!$this->db->field_exists($name, 'global_settings')) {
                $this->db->query("ALTER TABLE `global_settings` ADD `$name` $type");
            }
        }
        // Each school's 3D loading animation ('' follows the platform preloader setting).
        if ($this->db->table_exists('login_page_setting')) {
            if (!$this->db->field_exists('loader_style', 'login_page_setting')) {
                $this->db->query("ALTER TABLE `login_page_setting` ADD `loader_style` VARCHAR(20) NOT NULL DEFAULT '' AFTER `show_perks`");
            }
            if (!$this->db->field_exists('loader_text', 'login_page_setting')) {
                $this->db->query("ALTER TABLE `login_page_setting` ADD `loader_text` VARCHAR(60) NOT NULL DEFAULT '' AFTER `loader_style`");
            }
        }
    }
}
