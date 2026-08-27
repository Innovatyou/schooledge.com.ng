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
            if ($existing) {
                $this->db->where('id', $existing['id'])->update('student_attendance', array('status' => $status, 'remark' => $remark));
            } else {
                $this->db->insert('student_attendance', array('enroll_id' => $enrollId, 'date' => $date, 'status' => $status, 'remark' => $remark, 'branch_id' => $membership['branch_id']));
            }
            $saved++;
        }
        $this->logAudit('attendance.capture', $membership, 'class', $classId . '-' . $sectionId . '-' . $date);
        $this->ok(array('saved' => $saved, 'date' => $date));
    }

    private function teacherClasses(array $membership)
    {
        $rows = $this->db->select('teacher_allocation.class_id,teacher_allocation.section_id,class.name as class_name,section.name as section_name')
            ->from('teacher_allocation')
            ->join('class', 'class.id = teacher_allocation.class_id', 'inner')
            ->join('section', 'section.id = teacher_allocation.section_id', 'inner')
            ->where(array('teacher_allocation.teacher_id' => $membership['user_id'], 'teacher_allocation.branch_id' => $membership['branch_id']))
            ->get()->result_array();
        $rows = array_merge($rows, $this->db->select('subject_assign.class_id,subject_assign.section_id,class.name as class_name,section.name as section_name')
            ->from('subject_assign')
            ->join('class', 'class.id = subject_assign.class_id', 'inner')
            ->join('section', 'section.id = subject_assign.section_id', 'inner')
            ->where(array('subject_assign.teacher_id' => $membership['user_id'], 'subject_assign.branch_id' => $membership['branch_id']))
            ->get()->result_array());

        $seen = array();
        $unique = array();
        foreach ($rows as $row) {
            $key = $row['class_id'] . '-' . $row['section_id'];
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $unique[] = array('class_id' => (int)$row['class_id'], 'section_id' => (int)$row['section_id'], 'class_name' => $row['class_name'], 'section_name' => $row['section_name']);
        }
        return $unique;
    }

    private function assertTeacherOwnsClass(array $membership, $classId, $sectionId)
    {
        foreach ($this->teacherClasses($membership) as $row) {
            if ($row['class_id'] === (int)$classId && $row['section_id'] === (int)$sectionId) return;
        }
        $this->fail('class_not_assigned', 'You are not assigned to this class.', 403);
    }
}
