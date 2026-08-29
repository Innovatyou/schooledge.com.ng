<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_732 extends CI_Migration
{
    public function up()
    {
        // permission: safety_alerts (module 606, standalone - not part of the
        // normal admin sidebar grouping, same pattern as module 600's
        // "2FA Settings"), view-only for now (Safety::acknowledge() checks
        // the same is_view grant - there is no separate is_edit action).
        $this->db->where('prefix', 'safety_alerts');
        $existing = $this->db->get('permission')->row();
        if (empty($existing)) {
            $this->db->insert('permission', array(
                'module_id' => 606,
                'name' => 'Safety Alerts',
                'prefix' => 'safety_alerts',
                'show_view' => 1,
                'show_add' => 0,
                'show_edit' => 0,
                'show_delete' => 0,
            ));
            $safetyAlertsPermissionId = $this->db->insert_id();
        } else {
            $safetyAlertsPermissionId = $existing->id;
        }
        $safetyAlertsGrants = array(
            2 => array(1, 0, 0, 0), // Admin
            3 => array(0, 0, 0, 0), // Teacher - sees alerts via class assignment instead, not this permission
            4 => array(0, 0, 0, 0), // Accountant
            5 => array(0, 0, 0, 0), // Librarian
            6 => array(0, 0, 0, 0), // Parent - sees alerts via linked-child instead, not this permission
            7 => array(0, 0, 0, 0), // Student
            8 => array(0, 0, 0, 0), // Receptionist
        );
        foreach ($safetyAlertsGrants as $roleId => $grant) {
            $this->db->where(array('role_id' => $roleId, 'permission_id' => $safetyAlertsPermissionId));
            $exists = $this->db->get('staff_privileges')->row();
            if (empty($exists)) {
                $this->db->insert('staff_privileges', array(
                    'role_id' => $roleId,
                    'permission_id' => $safetyAlertsPermissionId,
                    'is_view' => $grant[0],
                    'is_add' => $grant[1],
                    'is_edit' => $grant[2],
                    'is_delete' => $grant[3],
                ));
            }
        }
    }
}
