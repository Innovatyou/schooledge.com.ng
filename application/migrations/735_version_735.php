<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_735 extends CI_Migration
{
    public function up()
    {
        // Classmate chat (Chat.php): message content/typing/presence live in
        // Firestore, not here - these tables are the server-authoritative
        // pieces (blocks, reports, voice-note file ownership) that must
        // never be client-writable, plus the permission gating who can view
        // a classroom's chat oversight.
        if (!$this->db->table_exists('schooledge_chat_blocks')) {
            $this->db->query("CREATE TABLE schooledge_chat_blocks (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,branch_id INT NOT NULL,blocker_membership_id BIGINT UNSIGNED NOT NULL,blocked_membership_id BIGINT UNSIGNED NOT NULL,created_at DATETIME NOT NULL,UNIQUE KEY uniq_block_pair(blocker_membership_id,blocked_membership_id),KEY idx_branch(branch_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!$this->db->table_exists('schooledge_chat_reports')) {
            $this->db->query("CREATE TABLE schooledge_chat_reports (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,branch_id INT NOT NULL,reporter_membership_id BIGINT UNSIGNED NOT NULL,reported_membership_id BIGINT UNSIGNED NOT NULL,conversation_id VARCHAR(60) NOT NULL,message_excerpt VARCHAR(500) NULL,status VARCHAR(20) NOT NULL DEFAULT 'open',created_at DATETIME NOT NULL,KEY idx_branch_created(branch_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (!$this->db->table_exists('schooledge_chat_voice_notes')) {
            $this->db->query("CREATE TABLE schooledge_chat_voice_notes (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,branch_id INT NOT NULL,membership_id BIGINT UNSIGNED NOT NULL,conversation_id VARCHAR(60) NOT NULL,stored_file VARCHAR(255) NOT NULL,original_name VARCHAR(255) NULL,duration_ms INT NULL,created_at DATETIME NOT NULL,KEY idx_conversation(conversation_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        // permission: chat_oversight (module 607, standalone - same pattern as
        // 606's safety_alerts), view-only, admin-granted by default; a
        // teacher's oversight access is via assertTeacherOwnsClass() instead.
        $this->db->where('prefix', 'chat_oversight');
        $existing = $this->db->get('permission')->row();
        if (empty($existing)) {
            $this->db->insert('permission', array(
                'module_id' => 607,
                'name' => 'Chat Oversight',
                'prefix' => 'chat_oversight',
                'show_view' => 1,
                'show_add' => 0,
                'show_edit' => 0,
                'show_delete' => 0,
            ));
            $chatOversightPermissionId = $this->db->insert_id();
        } else {
            $chatOversightPermissionId = $existing->id;
        }
        $chatOversightGrants = array(
            2 => array(1, 0, 0, 0), // Admin
            3 => array(0, 0, 0, 0), // Teacher - sees oversight via class assignment instead, not this permission
            4 => array(0, 0, 0, 0), // Accountant
            5 => array(0, 0, 0, 0), // Librarian
            6 => array(0, 0, 0, 0), // Parent
            7 => array(0, 0, 0, 0), // Student
            8 => array(0, 0, 0, 0), // Receptionist
        );
        foreach ($chatOversightGrants as $roleId => $grant) {
            $this->db->where(array('role_id' => $roleId, 'permission_id' => $chatOversightPermissionId));
            $exists = $this->db->get('staff_privileges')->row();
            if (empty($exists)) {
                $this->db->insert('staff_privileges', array(
                    'role_id' => $roleId,
                    'permission_id' => $chatOversightPermissionId,
                    'is_view' => $grant[0],
                    'is_add' => $grant[1],
                    'is_edit' => $grant[2],
                    'is_delete' => $grant[3],
                ));
            }
        }
    }
}
