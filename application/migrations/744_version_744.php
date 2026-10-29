<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_744 extends CI_Migration
{
    public function up()
    {
        // Moves the demo school's ID cards onto the redesigned starter badges
        // (starter_templates() in general_helper). Limited to demo branches
        // (branch.is_demo = 1) and the two templates the demo seeder creates,
        // so no real school's saved card design is touched.
        if (!$this->db->field_exists('is_demo', 'branch')) {
            return;
        }
        $starters = starter_templates();
        $targets = array(
            'Student ID Card' => $starters['id_card_student'],
            'Employee ID Card' => $starters['id_card_employee'],
        );
        $demoBranches = $this->db->select('id')->where('is_demo', 1)->get('branch')->result_array();
        foreach ($demoBranches as $branch) {
            foreach ($targets as $name => $starter) {
                $this->db->where(array('branch_id' => $branch['id'], 'card_type' => 1, 'name' => $name));
                $this->db->update('card_templete', array(
                    'content' => $starter['content'],
                    'design_style' => $starter['design_style'],
                    'photo_size' => $starter['photo_size'],
                    'top_space' => $starter['spacing'][0],
                    'bottom_space' => $starter['spacing'][1],
                    'right_space' => $starter['spacing'][2],
                    'left_space' => $starter['spacing'][3],
                ));
            }
        }
    }
}
