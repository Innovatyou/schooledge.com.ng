<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * Mobile "online class" API on top of the existing Live_class module. Joining a
 * Zoom meeting only needs the public https://zoom.us/j/{id}?pwd=... link (the
 * stored Zoom API key/secret in Zoom_lib.php is only used to CREATE meetings on the
 * web side, never to join one), so this needs no OAuth dance for students/parents.
 * BigBlueButton mirrors Userrole::livejoin()'s existing web flow. Plain external
 * links (live_class_method 3) are returned as-is from the stored `bbb` JSON.
 */
class Liveclasses extends Api_Controller
{
    public function index()
    {
        $membership = $this->requireAuth();
        $roleId = (int)$membership['role_id'];
        // Resolve enrollment (its own query) BEFORE starting the live_class query
        // below - see the comment in Events::index() for why order matters here.
        $enrollment = null;
        if ($roleId === 6 || $roleId === 7) {
            $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
        } elseif ($roleId !== 3) {
            $this->fail('role_not_supported', 'Live classes are available to teachers, students and linked parents.', 403);
        }

        $this->db->select('live_class.id,live_class.title,live_class.live_class_method,live_class.date,live_class.start_time,live_class.end_time,live_class.status,live_class.class_id,class.name as class_name')
            ->from('live_class')
            ->join('class', 'class.id = live_class.class_id', 'left')
            ->where('live_class.branch_id', $membership['branch_id']);

        if ($roleId === 3) {
            $this->db->where('live_class.created_by', $membership['user_id']);
        } else {
            $this->db->where('live_class.class_id', $enrollment['class_id']);
            $this->db->like('live_class.section_id', '"' . (int)$enrollment['section_id'] . '"');
        }

        $from = $this->input->get('from') ?: date('Y-m-d');
        $this->db->where('live_class.date >=', $from);
        $rows = $this->db->order_by('live_class.date', 'asc')->order_by('live_class.start_time', 'asc')->limit(100)->get()->result_array();
        $this->ok(array_map(array($this, 'payload'), $rows));
    }

    public function join($id)
    {
        $membership = $this->requireAuth();
        $roleId = (int)$membership['role_id'];
        $liveClass = $this->db->where(array('id' => (int)$id, 'branch_id' => $membership['branch_id']))->get('live_class')->row_array();
        if (!$liveClass) $this->fail('live_class_not_found', 'Live class not found.', 404);

        if ($roleId === 3) {
            if ((int)$liveClass['created_by'] !== (int)$membership['user_id']) $this->fail('live_class_not_found', 'Live class not found.', 404);
        } elseif ($roleId === 6 || $roleId === 7) {
            $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
            $sections = json_decode($liveClass['section_id'], true) ?: array();
            if ((int)$liveClass['class_id'] !== (int)$enrollment['class_id'] || !in_array($enrollment['section_id'], $sections)) {
                $this->fail('live_class_not_found', 'Live class not found.', 404);
            }
        } else {
            $this->fail('role_not_supported', 'Live classes are available to teachers, students and linked parents.', 403);
        }

        $joinInfo = $this->resolveJoinUrl($liveClass, $membership);
        if (empty($joinInfo['join_url'])) $this->fail('live_class_unavailable', 'This class cannot be joined right now.', 409);

        if ($roleId === 7) {
            $branch = $this->db->select('is_demo')->where('id', $membership['branch_id'])->get('branch')->row_array();
            if (empty($branch['is_demo'])) $this->recordAttendance($liveClass['id'], $membership['user_id']);
        }
        $this->logAudit('live_class.join', $membership, 'live_class', $liveClass['id']);
        $this->ok($joinInfo);
    }

    private function resolveJoinUrl(array $liveClass, array $membership)
    {
        $method = (int)$liveClass['live_class_method'];
        if ($method === 1) {
            $url = 'https://zoom.us/j/' . rawurlencode($liveClass['meeting_id']);
            if (!empty($liveClass['meeting_password'])) $url .= '?pwd=' . rawurlencode($liveClass['meeting_password']);
            return array('method' => 'zoom', 'join_url' => $url, 'meeting_id' => $liveClass['meeting_id']);
        }

        $bbb = json_decode($liveClass['bbb'], true) ?: array();
        if ($method === 3) {
            return array('method' => 'external', 'join_url' => $bbb['join_url'] ?? null);
        }

        $config = $this->db->where('branch_id', $liveClass['branch_id'])->get('live_class_config')->row_array();
        if (!$config || empty($config['bbb_server_base_url'])) return array('method' => 'bbb', 'join_url' => null);
        $this->load->library('bigbluebutton_lib', array('bbb_security_salt' => $config['bbb_salt_key'], 'bbb_server_base_url' => $config['bbb_server_base_url']));
        $this->load->model('authentication_model');
        $user = $this->authentication_model->getUserNameByRoleID($membership['role_id'], $membership['user_id']);
        $url = $this->bigbluebutton_lib->joinMeeting(array(
            'meeting_id' => $liveClass['meeting_id'],
            'title' => $liveClass['title'],
            'attendee_password' => $bbb['attendee_password'] ?? '',
            'presen_name' => $user['name'] ?? 'Student',
        ));
        return array('method' => 'bbb', 'join_url' => is_string($url) ? $url : null);
    }

    private function recordAttendance($liveClassId, $studentId)
    {
        $existing = $this->db->where(array('live_class_id' => $liveClassId, 'student_id' => $studentId))->get('live_class_reports')->row();
        if ($existing) {
            $this->db->where('id', $existing->id)->update('live_class_reports', array('created_at' => date('Y-m-d H:i:s')));
        } else {
            $this->db->insert('live_class_reports', array('live_class_id' => $liveClassId, 'student_id' => $studentId, 'created_at' => date('Y-m-d H:i:s')));
        }
    }

    private function payload(array $row)
    {
        $method = (int)$row['live_class_method'];
        return array(
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'method' => $method === 1 ? 'zoom' : ($method === 2 ? 'bbb' : 'external'),
            'class_name' => $row['class_name'],
            'date' => $row['date'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'status' => (int)$row['status'],
        );
    }
}
