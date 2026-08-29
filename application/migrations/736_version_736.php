<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_736 extends CI_Migration
{
    public function up()
    {
        // Lets Chat::voiceNote() authorize an oversight-viewing teacher/admin
        // without a Firestore call - the classroom a voice note belongs to,
        // captured at upload time.
        if (!$this->db->field_exists('classroom_key', 'schooledge_chat_voice_notes')) {
            $this->db->query("ALTER TABLE schooledge_chat_voice_notes ADD COLUMN classroom_key VARCHAR(40) NULL AFTER conversation_id");
        }
    }
}
