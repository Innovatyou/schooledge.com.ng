<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * On-demand location share + SOS panic button - deliberately NOT continuous
 * background tracking (one GPS fix captured client-side per action, sent
 * once). Senders: students and teachers only (role 3/7) - a parent isn't
 * physically on campus to need an on-campus SOS.
 *
 * Visibility is enforced HERE, server-side, on every read - never left to
 * the client to filter: (1) the sender's own linked parent(s) (role 6),
 * (2) admin/superadmin/anyone holding the safety_alerts view permission,
 * (3) the student's currently-assigned teacher(s) (role 3, via
 * teacherClasses() - the same "only your own assigned classes" rule
 * Attendance/Timetable already enforce). A sender always sees their own
 * alerts too.
 */
class Safety extends Api_Controller
{
    public function submit()
    {
        $membership = $this->requireAuth();
        $roleId = (int)$membership['role_id'];
        if ($roleId !== 3 && $roleId !== 7) $this->fail('role_not_supported', 'Safety alerts can be sent by students and teachers.', 403);
        $this->blockIfDemoReadonly($membership['branch_id']);

        $input = $this->body();
        $alertType = (string)($input['alert_type'] ?? '');
        if (!in_array($alertType, array('share', 'sos'), true)) $this->fail('validation_error', "alert_type must be 'share' or 'sos'.", 422);
        $latitude = isset($input['latitude']) ? (float)$input['latitude'] : null;
        $longitude = isset($input['longitude']) ? (float)$input['longitude'] : null;
        if ($latitude === null || $longitude === null) $this->fail('validation_error', 'latitude and longitude are required.', 422);
        $accuracy = isset($input['accuracy_meters']) ? (float)$input['accuracy_meters'] : null;
        $note = isset($input['note']) ? trim((string)$input['note']) : null;

        $this->db->insert('schooledge_safety_alerts', array(
            'branch_id' => $membership['branch_id'], 'sender_membership_id' => $membership['id'],
            'sender_role_id' => $roleId, 'sender_user_id' => $membership['user_id'], 'alert_type' => $alertType,
            'latitude' => $latitude, 'longitude' => $longitude, 'accuracy_meters' => $accuracy, 'note' => $note,
            'status' => 'open', 'created_at' => date('Y-m-d H:i:s'),
        ));
        $alertId = (int)$this->db->insert_id();
        $this->logAudit('safety.alert_submitted', $membership, 'schooledge_safety_alerts', $alertId);

        $this->notifyRecipients($membership, $roleId, $alertType, $alertId);

        $this->ok(array('submitted' => true, 'alert_id' => $alertId, 'status' => 'open'));
    }

    public function index()
    {
        $membership = $this->requireAuth();
        $roleId = (int)$membership['role_id'];

        // Resolve the visibility predicate COMPLETELY first (every call here
        // runs and finishes its own query via get()) before touching the
        // schooledge_safety_alerts chain below - CI3's query builder holds
        // one pending chain per call, so starting that chain any earlier
        // (including calling hasPermission() after a where() is already
        // pending) would silently merge their WHERE clauses together (see
        // Events.php's resolveAudience() for the same pitfall documented
        // there).
        $canViewAll = $this->hasPermission($roleId, 'safety_alerts', 'is_view');
        $childIds = array();
        $studentIds = array();
        if (!$canViewAll) {
            if ($roleId === 6) {
                $childIds = array_map('intval', array_column($this->db->select('id')->where('parent_id', $membership['user_id'])->get('student')->result_array(), 'id'));
                if (!$childIds) {
                    $this->ok(array('alerts' => array()));
                    return;
                }
            } elseif ($roleId === 3) {
                $studentIds = $this->studentIdsInTeachersClasses($membership);
            } elseif ($roleId !== 7) {
                $this->fail('role_not_supported', 'Safety alerts are not available for this role.', 403);
                return;
            }
        }

        // Nothing above this line may run after the chain below starts.
        $this->db->where('branch_id', $membership['branch_id']);
        if ($canViewAll) {
            // full branch visibility, no extra filter
        } elseif ($roleId === 6) {
            $this->db->where('sender_role_id', 7)->where_in('sender_user_id', $childIds);
        } elseif ($roleId === 3) {
            $this->db->group_start()->where('sender_membership_id', $membership['id']);
            if ($studentIds) {
                $this->db->or_group_start()->where('sender_role_id', 7)->where_in('sender_user_id', $studentIds)->group_end();
            }
            $this->db->group_end();
        } else {
            $this->db->where('sender_membership_id', $membership['id']);
        }
        $rows = $this->db->order_by('created_at', 'desc')->limit(100)->get('schooledge_safety_alerts')->result_array();

        $this->ok(array('alerts' => $this->payloadForRows($rows)));
    }

    public function acknowledge($id)
    {
        $membership = $this->requireAuth();
        $alert = $this->db->where(array('id' => (int)$id, 'branch_id' => $membership['branch_id']))->get('schooledge_safety_alerts')->row_array();
        if (!$alert || !$this->isVisible($membership, (int)$membership['role_id'], $alert)) $this->fail('alert_not_found', 'Alert not found.', 404);

        $this->db->where('id', $alert['id'])->update('schooledge_safety_alerts', array(
            'status' => 'acknowledged', 'acknowledged_by_membership_id' => $membership['id'], 'acknowledged_at' => date('Y-m-d H:i:s'),
        ));
        $this->logAudit('safety.alert_acknowledged', $membership, 'schooledge_safety_alerts', $alert['id']);
        $this->ok(array('acknowledged' => true));
    }

    /** Same 3-tier rule as index(), evaluated against one already-fetched row (for acknowledge()). */
    private function isVisible(array $membership, $roleId, array $alert)
    {
        if ($this->hasPermission($roleId, 'safety_alerts', 'is_view')) return true;
        if ((int)$alert['sender_membership_id'] === (int)$membership['id']) return true;
        if ($roleId === 6 && (int)$alert['sender_role_id'] === 7) {
            $child = $this->db->where(array('id' => (int)$alert['sender_user_id'], 'parent_id' => $membership['user_id']))->get('student')->row_array();
            return (bool)$child;
        }
        if ($roleId === 3 && (int)$alert['sender_role_id'] === 7) {
            return in_array((int)$alert['sender_user_id'], $this->studentIdsInTeachersClasses($membership), true);
        }
        return false;
    }

    /** Every student currently enrolled in a class+section this teacher teaches (teacherClasses(), shared via Api_Controller). */
    private function studentIdsInTeachersClasses(array $membership)
    {
        $pairs = $this->teacherClasses($membership);
        if (!$pairs) return array();
        $this->db->select('student_id')->from('enroll')->where(array('branch_id' => $membership['branch_id'], 'is_alumni' => 0));
        $this->db->group_start();
        foreach ($pairs as $index => $pair) {
            if ($index === 0) $this->db->group_start(); else $this->db->or_group_start();
            $this->db->where('class_id', (int)$pair['class_id'])->where('section_id', (int)$pair['section_id'])->group_end();
        }
        $this->db->group_end();
        return array_map('intval', array_column($this->db->get()->result_array(), 'student_id'));
    }

    /** Fans the alert out to every authorized viewer - SOS bypasses each recipient's notification-preference toggle, 'share' respects it. */
    private function notifyRecipients(array $membership, $roleId, $alertType, $alertId)
    {
        $branchId = $membership['branch_id'];
        $urgent = $alertType === 'sos';
        $senderName = $this->senderName($roleId, $membership['user_id']);
        $title = $urgent ? 'SOS: ' . $senderName . ' needs help' : $senderName . ' shared their location';
        $body = $urgent ? 'An SOS alert was raised. Open the app to view the location.' : 'A location was shared with you. Open the app to view it.';
        $data = array('alert_id' => $alertId, 'alert_type' => $alertType);

        // 1) Everyone holding the safety_alerts view permission (admin/security), across every distinct staff role in this branch.
        $staffRoleIds = array_map('intval', array_column($this->db->distinct()->select('role_id')->where(array('branch_id' => $branchId, 'status' => 'active'))->get('mobile_memberships')->result_array(), 'role_id'));
        foreach ($staffRoleIds as $staffRoleId) {
            if (!$this->hasPermission($staffRoleId, 'safety_alerts', 'is_view')) continue;
            $memberships = $this->db->where(array('branch_id' => $branchId, 'role_id' => $staffRoleId, 'status' => 'active'))->get('mobile_memberships')->result_array();
            foreach ($memberships as $recipient) {
                $this->notifyMembership((int)$recipient['id'], $branchId, 'safety', $title, $body, $data, $urgent);
            }
        }

        if ($roleId !== 7) return; // teacher's own alert: admin/security above is the only recipient group

        // 2) The student's own linked parent(s).
        $student = $this->db->where('id', $membership['user_id'])->get('student')->row_array();
        if ($student && !empty($student['parent_id'])) {
            $this->notifyIdentity($branchId, '6-' . $student['parent_id'], 'safety', $title, $body, $data, $urgent);
        }

        // 3) The student's currently-assigned teacher(s).
        $enrollment = $this->db->where(array('student_id' => $membership['user_id'], 'branch_id' => $branchId))
            ->order_by('session_id', 'desc')->get('enroll')->row_array();
        if ($enrollment) {
            $teacherIds = array_unique(array_map('intval', array_merge(
                array_column($this->db->select('teacher_id')->where(array('class_id' => $enrollment['class_id'], 'section_id' => $enrollment['section_id'], 'branch_id' => $branchId))->get('teacher_allocation')->result_array(), 'teacher_id'),
                array_column($this->db->select('teacher_id')->where(array('class_id' => $enrollment['class_id'], 'section_id' => $enrollment['section_id'], 'branch_id' => $branchId))->get('subject_assign')->result_array(), 'teacher_id')
            )));
            foreach ($teacherIds as $teacherId) {
                $this->notifyIdentity($branchId, '3-' . $teacherId, 'safety', $title, $body, $data, $urgent);
            }
        }
    }

    private function senderName($roleId, $userId)
    {
        if ($roleId === 7) {
            $row = $this->db->select('CONCAT_WS(" ",first_name,last_name) as name')->where('id', $userId)->get('student')->row_array();
        } else {
            $row = $this->db->select('name')->where('id', $userId)->get('staff')->row_array();
        }
        return $row ? trim((string)$row['name']) : 'Someone';
    }

    private function payloadForRows(array $rows)
    {
        $studentIds = array_map('intval', array_column(array_filter($rows, fn ($r) => (int)$r['sender_role_id'] === 7), 'sender_user_id'));
        $staffIds = array_map('intval', array_column(array_filter($rows, fn ($r) => (int)$r['sender_role_id'] === 3), 'sender_user_id'));
        $studentNames = array();
        if ($studentIds) {
            foreach ($this->db->select('id,CONCAT_WS(" ",first_name,last_name) as name')->where_in('id', array_unique($studentIds))->get('student')->result_array() as $row) {
                $studentNames[(int)$row['id']] = $row['name'];
            }
        }
        $staffNames = array();
        if ($staffIds) {
            foreach ($this->db->select('id,name')->where_in('id', array_unique($staffIds))->get('staff')->result_array() as $row) {
                $staffNames[(int)$row['id']] = $row['name'];
            }
        }

        return array_map(function ($r) use ($studentNames, $staffNames) {
            $senderRoleId = (int)$r['sender_role_id'];
            $senderUserId = (int)$r['sender_user_id'];
            $name = $senderRoleId === 7 ? ($studentNames[$senderUserId] ?? 'Student') : ($staffNames[$senderUserId] ?? 'Teacher');
            return array(
                'id' => (int)$r['id'], 'alert_type' => $r['alert_type'], 'status' => $r['status'],
                'sender_name' => $name, 'sender_role' => $senderRoleId === 7 ? 'student' : 'teacher',
                'latitude' => (float)$r['latitude'], 'longitude' => (float)$r['longitude'],
                'accuracy_meters' => $r['accuracy_meters'] !== null ? (float)$r['accuracy_meters'] : null,
                'note' => $r['note'], 'created_at' => $r['created_at'],
                'acknowledged_at' => $r['acknowledged_at'],
            );
        }, $rows);
    }
}
