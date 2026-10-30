<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_746 extends CI_Migration
{
    public function up()
    {
        // Per-school sign-in page settings (layout, background photo, accent,
        // copy). branch_id 0 is the platform's own sign-in page.
        if (!$this->db->table_exists('login_page_setting')) {
            $this->db->query("CREATE TABLE `login_page_setting` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `branch_id` INT NOT NULL,
                `layout` VARCHAR(20) NOT NULL DEFAULT 'split',
                `bg_image` VARCHAR(255) NOT NULL DEFAULT '',
                `overlay` TINYINT UNSIGNED NOT NULL DEFAULT 55,
                `accent` VARCHAR(7) NOT NULL DEFAULT '#7c5cff',
                `headline` VARCHAR(150) NOT NULL DEFAULT '',
                `tagline` VARCHAR(255) NOT NULL DEFAULT '',
                `perks` TEXT NULL,
                `show_perks` TINYINT(1) NOT NULL DEFAULT 1,
                `updated_at` DATETIME NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_login_page_branch` (`branch_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        // the demo school shows off the photo layout
        if ($this->db->field_exists('is_demo', 'branch')) {
            // its display name was missed by the 743 rebrand
            $this->db->query("UPDATE branch SET school_name = 'SkoolOS Demo Academy' WHERE is_demo = 1 AND school_name = 'SchoolEdge Demo Academy'");
            $demoBranches = $this->db->select('id')->where(array('is_demo' => 1, 'name' => 'SkoolOS Demo Academy'))->get('branch')->result_array();
            foreach ($demoBranches as $branch) {
                if ($this->db->where('branch_id', $branch['id'])->count_all_results('login_page_setting') == 0) {
                    $this->db->insert('login_page_setting', array(
                        'branch_id' => $branch['id'],
                        'layout' => 'glass',
                        'bg_image' => 'demo-signin.jpg',
                        'overlay' => 45,
                        'accent' => '#7c5cff',
                        'headline' => 'Welcome back to SkoolOS Demo Academy',
                        'tagline' => 'Sign in to see results, fees, attendance and messages.',
                        'perks' => "Report cards and results\nFees and online payments\nAttendance and parent alerts",
                        'show_perks' => 1,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ));
                }
            }
        }
    }
}
