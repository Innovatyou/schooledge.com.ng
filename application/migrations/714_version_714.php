<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_714 extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('psychomotor_rating')) {
            $this->dbforge->add_field(array(
                'id' => array('type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true),
                'branch_id' => array('type' => 'INT', 'constraint' => 11),
                'session_id' => array('type' => 'INT', 'constraint' => 11),
                'exam_id' => array('type' => 'INT', 'constraint' => 11),
                'student_id' => array('type' => 'INT', 'constraint' => 11),
                'enroll_id' => array('type' => 'INT', 'constraint' => 11),
                'trait_key' => array('type' => 'VARCHAR', 'constraint' => 50),
                'rating' => array('type' => 'TINYINT', 'constraint' => 1),
                'created_at' => array('type' => 'DATETIME', 'null' => true),
                'updated_at' => array('type' => 'DATETIME', 'null' => true),
            ));
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('psychomotor_rating');
            $this->db->query('ALTER TABLE `psychomotor_rating` ADD UNIQUE KEY `uniq_exam_student_trait` (`exam_id`, `student_id`, `trait_key`)');
        }

        $field = array(
            'next_term_begins' => array(
                'type' => 'DATE',
                'null' => true,
                'after' => 'name',
            ),
        );
        if ($this->db->table_exists('exam_term') && !$this->db->field_exists('next_term_begins', 'exam_term')) {
            $this->dbforge->add_column('exam_term', $field);
        }

        if ($this->db->table_exists('permission')) {
            $exists = $this->db->where('prefix', 'psychomotor_rating')->get('permission');
            if ($exists->num_rows() == 0) {
                $this->db->insert('permission', array(
                    'module_id' => 9,
                    'name' => 'Psychomotor Rating',
                    'prefix' => 'psychomotor_rating',
                    'show_view' => 1,
                    'show_add' => 1,
                    'show_edit' => 1,
                    'show_delete' => 1,
                ));
            }
        }
    }
}
