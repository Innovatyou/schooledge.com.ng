<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_748 extends CI_Migration
{
    public function up()
    {
        // Move the demo school into the platform's current academic session, so
        // its students, classes, exams and fees show again after a new session
        // starts (see database_seeds/demo_session_sync.php).
        if (!$this->db->field_exists('is_demo', 'branch')) {
            return;
        }
        require_once APPPATH . 'database_seeds/demo_session_sync.php';
        $demoBranches = $this->db->select('id')->where(array('is_demo' => 1, 'name' => 'SkoolOS Demo Academy'))->get('branch')->result_array();
        foreach ($demoBranches as $branch) {
            sync_demo_session($this->db->conn_id, $branch['id']);
        }
    }
}
