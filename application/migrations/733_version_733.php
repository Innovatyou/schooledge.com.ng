<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_733 extends CI_Migration
{
    public function up()
    {
        // Ties every per-student row inserted by Messages::broadcast() (a
        // teacher messaging their whole class in one action) back together
        // for the sender's own "sent" view. NULL for every ordinary 1:1
        // message.
        if (!$this->db->field_exists('broadcast_group_id', 'message')) {
            $this->db->query("ALTER TABLE message ADD COLUMN broadcast_group_id VARCHAR(40) NULL AFTER reciever");
        }
    }
}
