<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_713 extends CI_Migration
{
    public function up()
    {
        $field = array(
            'term_position' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'position',
            ),
        );

        if ($this->db->table_exists('marksheet_template') && !$this->db->field_exists('term_position', 'marksheet_template')) {
            $this->dbforge->add_column('marksheet_template', $field);
        }
    }
}
