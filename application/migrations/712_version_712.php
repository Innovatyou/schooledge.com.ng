<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_712 extends CI_Migration
{
    public function up()
    {
        $field = array(
            'available_all_branches' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'design_style',
            ),
        );

        foreach (array('card_templete', 'certificates_templete', 'marksheet_template') as $table) {
            if ($this->db->table_exists($table) && !$this->db->field_exists('available_all_branches', $table)) {
                $this->dbforge->add_column($table, $field);
            }
        }
    }
}
