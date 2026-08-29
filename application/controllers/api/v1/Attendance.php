<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * Mobile attendance API. Students/parents get their own daily record from
 * `student_attendance` (keyed by enroll_id, status P/A/L - confirmed against
 * views/attendance/student_entries.php). Teachers capture attendance only for
 * classes they're actually assigned to (homeroom via teacher_allocation, or any
 * subject via subject_assign), never an arbitrary class - "teachers can modify
 * only assigned classes" is enforced here, not left to client-supplied ids.
 */
class Attendance extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('gamification_model');
    }

    public function summary()
    {
        $membership = $this->requireAuth();
        $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));

        $counts = $this->db->select('status, COUNT(*) as total')
            ->where(array('enroll_id' => $enrollment['id'], 'branch_id' => $membership['branch_id']))
            ->group_by('status')->get('student_attendance')->result_array();
        $stats = array('P' => 0, 'A' => 0, 'L' => 0);
        foreach ($counts as $row) {
            if (isset($stats[$row['status']])) $stats[$row['status']] = (int)$row['total'];
        }
        $total = array_sum($stats);

        $recent = $this->db->select('id,date,status,remark')
            ->where(array('enroll_id' => $enrollment['id'], 'branch_id' => $membership['branch_id']))
            ->order_by('date', 'desc')->limit(30)->get('student_attendance')->result_array();

        $this->ok(array(
            'student' => array('id' => (int)$enrollment['student_id'], 'name' => $enrollment['student_name']),
            'present' => $stats['P'], 'absent' => $stats['A'], 'late' => $stats['L'], 'total' => $total,
            'present_percent' => $total > 0 ? round($stats['P'] / $total * 100, 1) : 0,
            'recent' => array_map(function ($r) {
                return array('id' => (int)$r['id'], 'date' => $r['date'], 'status' => $r['status'], 'remark' => $r['remark']);
            }, $recent),
        ));
    }

    public function classes()
    {
        $membership = $this->requireAuth();
        if ((int)$membership['role_id'] !== 3) $this->fail('role_not_supported', 'Attendance capture is available to teachers.', 403);
        $this->ok($this->teacherClasses($membership));
    }

    public function roster($classId, $sectionId)
    {
        $membership = $this->requireAuth();
        if ((int)$membership['role_id'] !== 3) $this->fail('role_not_supported', 'Attendance capture is available to teachers.', 403);
        $this->assertTeacherOwnsClass($membership, $classId, $sectionId);

        $date = $this->input->get('date') ?: date('Y-m-d');
        $rows = $this->db->select('enroll.id as enroll_id,student.id as student_id,CONCAT_WS(" ",student.first_name,student.last_name) as name,enroll.roll,sa.status,sa.remark')
            ->from('enroll')
            ->join('student', 'student.id = enroll.student_id', 'inner')
            ->join('student_attendance as sa', 'sa.enroll_id = enroll.id AND sa.date = ' . $this->db->escape($date), 'left')
            ->where(array('enroll.branch_id' => $membership['branch_id'], 'enroll.class_id' => (int)$classId, 'enroll.section_id' => (int)$sectionId, 'enroll.is_alumni' => 0))
            ->order_by('enroll.roll', 'asc')->get()->result_array();

        $this->ok(array(
            'date' => $date,
            'students' => array_map(function ($r) {
                return array('enroll_id' => (int)$r['enroll_id'], 'student_id' => (int)$r['student_id'], 'name' => $r['name'], 'roll' => $r['roll'], 'status' => $r['status'], 'remark' => $r['remark']);
            }, $rows),
        ));
    }

    public function capture()
    {
        $membership = $this->requireAuth();
        if ((int)$membership['role_id'] !== 3) $this->fail('role_not_supported', 'Attendance capture is available to teachers.', 403);
        $this->blockIfDemoReadonly($membership['branch_id']);
        $input = $this->body();
        $classId = (int)($input['class_id'] ?? 0);
        $sectionId = (int)($input['section_id'] ?? 0);
        $date = (string)($input['date'] ?? '');
        $entries = is_array($input['entries'] ?? null) ? $input['entries'] : array();
        if (!$classId || !$sectionId || !$date || !$entries) $this->fail('validation_error', 'class_id, section_id, date and entries are required.', 422);
        $this->assertTeacherOwnsClass($membership, $classId, $sectionId);

        $validEnrollIds = array_column($this->db->select('id')->where(array('branch_id' => $membership['branch_id'], 'class_id' => $classId, 'section_id' => $sectionId))->get('enroll')->result_array(), 'id');
        $saved = 0;
        foreach ($entries as $entry) {
            $enrollId = (int)($entry['enroll_id'] ?? 0);
            $status = (string)($entry['status'] ?? '');
            if (!in_array($enrollId, array_map('intval', $validEnrollIds), true)) continue; // never trust a client-supplied enroll id outside this class
            if (!in_array($status, array('P', 'A', 'L'), true)) continue;
            $remark = isset($entry['remark']) ? (string)$entry['remark'] : null;

            $existing = $this->db->where(array('enroll_id' => $enrollId, 'date' => $date, 'branch_id' => $membership['branch_id']))->get('student_attendance')->row_array();
            $wasPresent = $existing && $existing['status'] === 'P';
            if ($existing) {
                $this->db->where('id', $existing['id'])->update('student_attendance', array('status' => $status, 'remark' => $remark));
                $attendanceId = (int)$existing['id'];
            } else {
                $this->db->insert('student_attendance', array('enroll_id' => $enrollId, 'date' => $date, 'status' => $status, 'remark' => $remark, 'branch_id' => $membership['branch_id']));
                $attendanceId = (int)$this->db->insert_id();
            }
            $saved++;
            if ($status === 'P' && !$wasPresent) {
                $this->gamification_model->onAttendancePresent($membership['branch_id'], $enrollId, $attendanceId);
            }
        }
        $this->logAudit('attendance.capture', $membership, 'class', $classId . '-' . $sectionId . '-' . $date);
        $this->ok(array('saved' => $saved, 'date' => $date));
    }

    /**
     * A student's (or a parent's linked child's) own rotating attendance
     * pass - a signed, short-lived token binding enroll_id+branch_id, no DB
     * row involved (nothing to look up or invalidate, it just expires).
     * Never a bare id in the QR the way the old unfinished ID-card "QR
     * attendance" addon option would have been (application/models/
     * Card_manage_model.php's base64("s-{enroll_id}") - unsigned, no
     * expiry, never actually consumed anywhere) - the whole point of
     * signing + a ~20s expiry is that a photo of the screen goes stale
     * almost immediately, so the client re-fetches a fresh token every
     * ~15s to keep the on-screen code rotating.
     */
    public function qr_token()
    {
        $membership = $this->requireAuth();
        $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
        $exp = time() + 20;
        $token = $this->signQrClaims(array('eid' => (int)$enrollment['id'], 'bid' => (int)$membership['branch_id'], 'exp' => $exp));
        $this->ok(array(
            'token' => $token, 'expires_at' => date('c', $exp),
            'student' => array('id' => (int)$enrollment['student_id'], 'name' => $enrollment['student_name']),
        ));
    }

    /**
     * Teacher scans a student's rotating QR pass to mark them present for
     * today, without needing to pick a class/section first - the class is
     * derived from the scanned enrollment itself, then checked against
     * assertTeacherOwnsClass() exactly like capture() does, so a teacher
     * can never mark a student outside their own assigned classes just by
     * scanning a code (the token proves *which student*, never *permission
     * to mark them* - that's still re-derived server-side every time).
     */
    public function scan()
    {
        $membership = $this->requireAuth();
        if ((int)$membership['role_id'] !== 3) $this->fail('role_not_supported', 'Scanning attendance QR codes is available to teachers.', 403);
        $this->blockIfDemoReadonly($membership['branch_id']);
        $input = $this->body();
        $claims = $this->verifyQrToken((string)($input['token'] ?? ''));
        if (!$claims) $this->fail('invalid_qr', 'This QR code has expired or is invalid. Ask the student to refresh it and try again.', 422);
        if ((int)$claims['bid'] !== (int)$membership['branch_id']) $this->fail('student_not_found', 'This QR code belongs to a different school.', 404);

        $enrollment = $this->db->select('enroll.id,enroll.class_id,enroll.section_id,student.id as student_id,CONCAT_WS(" ",student.first_name,student.last_name) as name,enroll.roll')
            ->from('enroll')->join('student', 'student.id = enroll.student_id', 'inner')
            ->where(array('enroll.id' => (int)$claims['eid'], 'enroll.branch_id' => $membership['branch_id'], 'enroll.is_alumni' => 0))
            ->get()->row_array();
        if (!$enrollment) $this->fail('student_not_found', 'This student is no longer enrolled here.', 404);
        $this->assertTeacherOwnsClass($membership, $enrollment['class_id'], $enrollment['section_id']);

        $date = date('Y-m-d');
        $existing = $this->db->where(array('enroll_id' => $enrollment['id'], 'date' => $date, 'branch_id' => $membership['branch_id']))->get('student_attendance')->row_array();
        $alreadyMarked = $existing && $existing['status'] === 'P';
        if ($existing) {
            if (!$alreadyMarked) $this->db->where('id', $existing['id'])->update('student_attendance', array('status' => 'P', 'remark' => null));
            $attendanceId = (int)$existing['id'];
        } else {
            $this->db->insert('student_attendance', array('enroll_id' => $enrollment['id'], 'date' => $date, 'status' => 'P', 'remark' => null, 'branch_id' => $membership['branch_id']));
            $attendanceId = (int)$this->db->insert_id();
        }
        if (!$alreadyMarked) {
            $this->gamification_model->onAttendancePresent($membership['branch_id'], $enrollment['id'], $attendanceId);
        }
        $this->logAudit('attendance.qr_scan', $membership, 'enroll', $enrollment['id']);
        $this->ok(array(
            'marked' => true, 'already_marked' => $alreadyMarked, 'date' => $date,
            'student' => array('id' => (int)$enrollment['student_id'], 'name' => $enrollment['name'], 'roll' => $enrollment['roll']),
        ));
    }

    private function signQrClaims(array $claims)
    {
        $payload = $this->b64url(json_encode($claims));
        return $payload . '.' . $this->b64url(hash_hmac('sha256', $payload, $this->qrTokenKey(), true));
    }

    private function verifyQrToken($token)
    {
        $parts = explode('.', (string)$token);
        if (count($parts) !== 2) return null;
        if (!hash_equals($this->b64url(hash_hmac('sha256', $parts[0], $this->qrTokenKey(), true)), $parts[1])) return null;
        $claims = json_decode($this->unb64url($parts[0]), true);
        if (!is_array($claims) || empty($claims['exp']) || empty($claims['eid']) || empty($claims['bid'])) return null;
        return $claims['exp'] >= time() ? $claims : null;
    }

    /** Domain-separated from Api_Controller::tokenKey() (real access tokens) so a QR pass can never be substituted for a bearer token or vice versa. */
    private function qrTokenKey()
    {
        return hash('sha256', 'mobile-attendance-qr|' . (string)config_item('encryption_key'), true);
    }

    private function b64url($value) { return rtrim(strtr(base64_encode($value), '+/', '-_'), '='); }
    private function unb64url($value) { return base64_decode(strtr($value, '-_', '+/')); }
}
