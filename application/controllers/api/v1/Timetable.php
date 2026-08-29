<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * Mobile timetable API on `timetable_class` (day stored as a lowercase weekday
 * string, e.g. "monday" - confirmed against live data). A `break=1` row has no
 * real subject/teacher (both stored as 0) and is surfaced as a break period.
 * exams() reads the separate `timetable_exam` table - previously only ever
 * consumed server-side for mark computation (Reports.php/Results.php), never
 * exposed as a "when is my next exam" schedule.
 */
class Timetable extends Api_Controller
{
    public function index()
    {
        $membership = $this->requireAuth();
        $day = strtolower((string)($this->input->get('day') ?: date('l')));
        $roleId = (int)$membership['role_id'];

        if ($roleId === 6 || $roleId === 7) {
            $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
            $rows = $this->db->select('timetable_class.*,subject.name as subject_name')
                ->from('timetable_class')
                ->join('subject', 'subject.id = timetable_class.subject_id', 'left')
                ->where(array('timetable_class.branch_id' => $membership['branch_id'], 'timetable_class.class_id' => $enrollment['class_id'], 'timetable_class.section_id' => $enrollment['section_id'], 'timetable_class.session_id' => $enrollment['session_id'], 'timetable_class.day' => $day))
                ->order_by('timetable_class.time_start', 'asc')->get()->result_array();
        } elseif ($roleId === 3) {
            $sessionId = $this->currentSessionId();
            $rows = $this->db->select('timetable_class.*,subject.name as subject_name,class.name as class_name,section.name as section_name')
                ->from('timetable_class')
                ->join('subject', 'subject.id = timetable_class.subject_id', 'left')
                ->join('class', 'class.id = timetable_class.class_id', 'left')
                ->join('section', 'section.id = timetable_class.section_id', 'left')
                ->where(array('timetable_class.branch_id' => $membership['branch_id'], 'timetable_class.teacher_id' => $membership['user_id'], 'timetable_class.session_id' => $sessionId, 'timetable_class.day' => $day))
                ->order_by('timetable_class.time_start', 'asc')->get()->result_array();
        } else {
            $this->fail('role_not_supported', 'Timetable is available to teachers, students and linked parents.', 403);
            return;
        }

        $this->ok(array(
            'day' => $day,
            'periods' => array_map(function ($r) {
                $isBreak = !empty($r['break']);
                return array(
                    'id' => (int)$r['id'],
                    'is_break' => $isBreak,
                    'subject' => $isBreak ? 'Break' : ($r['subject_name'] ?? 'Subject'),
                    'class_name' => isset($r['class_name']) ? trim(($r['class_name'] ?? '') . ' ' . ($r['section_name'] ?? '')) : null,
                    'room' => $r['class_room'],
                    'time_start' => substr($r['time_start'], 0, 5),
                    'time_end' => substr($r['time_end'], 0, 5),
                );
            }, $rows),
        ));
    }

    private function currentSessionId()
    {
        $row = $this->db->select('session_id')->where('id', 1)->get('global_settings')->row();
        return $row ? $row->session_id : null;
    }

    /**
     * "When is my next exam" - reads timetable_exam, which today is only
     * ever consumed server-side (Reports.php/Results.php, for computing
     * marks) and never surfaced as a schedule. Role-aware the same way
     * index() is: a student/parent sees their own class+section only, a
     * teacher sees every class+section they're actually assigned to
     * (teacherClasses(), shared with Attendance via Api_Controller) - never
     * an arbitrary class.
     */
    public function exams()
    {
        $membership = $this->requireAuth();
        $roleId = (int)$membership['role_id'];

        if ($roleId === 6 || $roleId === 7) {
            $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
            $pairs = array(array('class_id' => (int)$enrollment['class_id'], 'section_id' => (int)$enrollment['section_id']));
            $rows = $this->examRows($membership['branch_id'], $enrollment['session_id'], $pairs);
        } elseif ($roleId === 3) {
            $rows = $this->examRows($membership['branch_id'], $this->currentSessionId(), $this->teacherClasses($membership));
        } else {
            $this->fail('role_not_supported', 'Exam calendar is available to teachers, students and linked parents.', 403);
            return;
        }

        $this->ok(array('exams' => $rows));
    }

    private function examRows($branchId, $sessionId, array $classSectionPairs)
    {
        if (!$classSectionPairs) return array();
        $this->db->select('timetable_exam.id,timetable_exam.exam_date,timetable_exam.time_start,timetable_exam.time_end,exam.name as exam_name,subject.name as subject_name,exam_hall.hall_no,class.name as class_name,section.name as section_name')
            ->from('timetable_exam')
            ->join('exam', 'exam.id = timetable_exam.exam_id', 'left')
            ->join('subject', 'subject.id = timetable_exam.subject_id', 'left')
            ->join('exam_hall', 'exam_hall.id = timetable_exam.hall_id', 'left')
            ->join('class', 'class.id = timetable_exam.class_id', 'left')
            ->join('section', 'section.id = timetable_exam.section_id', 'left')
            ->where(array('timetable_exam.branch_id' => (int)$branchId, 'timetable_exam.session_id' => $sessionId));

        $this->db->group_start();
        foreach ($classSectionPairs as $index => $pair) {
            if ($index === 0) {
                $this->db->group_start();
            } else {
                $this->db->or_group_start();
            }
            $this->db->where('timetable_exam.class_id', (int)$pair['class_id'])
                ->where('timetable_exam.section_id', (int)$pair['section_id'])
                ->group_end();
        }
        $this->db->group_end();

        $rows = $this->db->order_by('timetable_exam.exam_date', 'asc')
            ->order_by('timetable_exam.time_start', 'asc')->get()->result_array();

        return array_map(function ($r) {
            return array(
                'id' => (int)$r['id'],
                'exam_name' => $r['exam_name'] ?? 'Exam',
                'subject_name' => $r['subject_name'] ?? 'Subject',
                'exam_date' => $r['exam_date'],
                'time_start' => substr((string)$r['time_start'], 0, 5),
                'time_end' => substr((string)$r['time_end'], 0, 5),
                'hall_name' => $r['hall_no'],
                'class_name' => $r['class_name'],
                'section_name' => $r['section_name'],
            );
        }, $rows);
    }
}
