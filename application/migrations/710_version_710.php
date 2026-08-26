<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_710 extends CI_Migration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function up()
    {
        $field = array(
            'design_style' => array(
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'classic',
                'after' => 'name',
            ),
        );

        foreach (array('card_templete', 'certificates_templete', 'marksheet_template') as $table) {
            if ($this->db->table_exists($table) && !$this->db->field_exists('design_style', $table)) {
                $this->dbforge->add_column($table, $field);
            }
        }
    }
}
