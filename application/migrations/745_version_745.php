<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_745 extends CI_Migration
{
    public function up()
    {
        // Gives the demo school a complete public website on the Aurora 3D
        // template (see database_seeds/demo_website_content.php). Limited to
        // the demo branch the seeder creates; real schools are untouched.
        if (!$this->db->field_exists('is_demo', 'branch')) {
            return;
        }
        require_once APPPATH . 'database_seeds/demo_website_content.php';
        $demoBranches = $this->db->select('id')->where(array('is_demo' => 1, 'name' => 'SkoolOS Demo Academy'))->get('branch')->result_array();
        foreach ($demoBranches as $branch) {
            seed_demo_website($this->db->conn_id, $branch['id']);
        }
    }
}
