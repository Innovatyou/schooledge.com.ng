<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_743 extends CI_Migration
{
    public function up()
    {
        // Rebrand School Edge -> SkoolOS in user-visible content only.
        // Technical identifiers (schooledge_* tables, routes, the
        // Schooledge_qr_attendance controller, email/domain addresses, the
        // Firebase project and the mobile package id) keep their names.
        // MySQL REPLACE() is case-sensitive, so lowercase domains such as
        // info@schooledge.com.ng are left untouched.
        $pairs = array(
            'SchoolEdge.com.ng' => 'SkoolOS',
            'School Edge'       => 'SkoolOS',
            'SchoolEdge'        => 'SkoolOS',
            // the landing page no longer lists plans or self-registration
            'Review the pricing section or contact the SkoolOS team' => 'Contact the SkoolOS team',
            'Select the registration option on the website and follow the steps to create your school account. You can also contact the SkoolOS team for help with setup.' => 'Request a demo from our website or contact the SkoolOS team, and we will set up your school account with you.',
            'Register your school on SkoolOS or contact the SkoolOS team' => 'Contact the SkoolOS team',
        );

        $targets = array(
            'saas_settings'        => array('seo_title', 'seo_keyword', 'seo_description', 'slider_title', 'slider_description', 'feature_title', 'feature_description', 'price_plan_description', 'faq_title', 'faq_description', 'contact_title', 'contact_description', 'footer_about', 'expired_message', 'agree_checkbox_text'),
            'saas_cms_faq_list'    => array('title', 'description'),
            'saas_email_templates' => array('subject', 'template_body'),
            'global_settings'      => array('institute_name', 'footer_text'),
        );

        foreach ($targets as $table => $columns) {
            if (!$this->db->table_exists($table)) {
                continue;
            }
            foreach ($columns as $column) {
                if (!$this->db->field_exists($column, $table)) {
                    continue;
                }
                foreach ($pairs as $from => $to) {
                    $this->db->query("UPDATE `{$table}` SET `{$column}` = REPLACE(`{$column}`, ?, ?) WHERE `{$column}` LIKE ?", array($from, $to, '%' . $from . '%'));
                }
            }
        }

        // Both hero buttons pointed at the pricing section, which was removed
        if ($this->db->table_exists('saas_settings')) {
            $this->db->query("UPDATE saas_settings SET button_text_1 = ?, button_url_1 = '#contact' WHERE button_url_1 = '#pricing'", array("<i class='fas fa-calendar-check'></i> Request a Demo"));
            $this->db->query("UPDATE saas_settings SET button_text_2 = ?, button_url_2 = '#modules' WHERE button_url_2 = '#pricing'", array("<i class='fas fa-th-large'></i> Explore Modules"));
        }

        $this->db->query("UPDATE branch SET name = 'SkoolOS Demo Academy' WHERE name = 'SchoolEdge Demo Academy'");
    }
}
