<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_751 extends CI_Migration
{
    public function up()
    {
        // Search-friendly landing copy, applied once (landing_copy_version), plus FAQ de-duplication.
        // The live sites run application/database_seeds/apply_pending_migrations.php, which does the same.
        if ($this->db->table_exists('saas_cms_faq_list')) {
            $this->db->query("DELETE f FROM saas_cms_faq_list f JOIN saas_cms_faq_list k ON k.title = f.title AND k.id < f.id");
        }
        if (!$this->db->table_exists('saas_settings')) {
            return;
        }
        if (!$this->db->field_exists('landing_copy_version', 'saas_settings')) {
            $this->db->query("ALTER TABLE `saas_settings` ADD `landing_copy_version` TINYINT NOT NULL DEFAULT 0");
        }
        $row = $this->db->where('id', 1)->get('saas_settings')->row_array();
        if (empty($row) || (int) $row['landing_copy_version'] >= 1) {
            return;
        }
        $copy = require APPPATH . 'database_seeds/landing_copy.php';
        $settings = array_intersect_key($copy['settings'], $row);
        $settings['landing_copy_version'] = 1;
        $this->db->where('id', 1)->update('saas_settings', $settings);
        foreach ($copy['faqs'] as $title => $description) {
            if (!$this->db->where('title', $title)->count_all_results('saas_cms_faq_list')) {
                $this->db->insert('saas_cms_faq_list', array('title' => $title, 'description' => $description));
            }
        }
    }
}
