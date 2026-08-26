<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_715 extends CI_Migration
{
    public function up()
    {
        $field = array(
            'is_demo' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'null' => false,
            ),
        );
        if ($this->db->table_exists('branch') && !$this->db->field_exists('is_demo', 'branch')) {
            $this->dbforge->add_column('branch', $field);
        }
    }
}
