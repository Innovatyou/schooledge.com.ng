<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * Mobile timetable API on `timetable_class` (day stored as a lowercase weekday
 * string, e.g. "monday" - confirmed against live data). A `break=1` row has no
 * real subject/teacher (both stored as 0) and is surfaced as a break period.
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
}
