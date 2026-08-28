<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * Mobile results/marks API - a structured summary for the in-app Results screen
 * (subject-by-subject percentage bars, overall average, rank/comments), distinct
 * from Reports.php which only produces the downloadable PDF report card. Reuses
 * Exam_progress_model::getExamTotalMark() so the percentage math matches the PDF
 * report exactly (obtained/full marks per component, from timetable_exam's own
 * mark_distribution, not just summing the raw `mark` JSON blindly).
 */
class Results extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('exam_progress_model');
    }

    public function exams()
    {
        $membership = $this->requireAuth();
        $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
        $rows = $this->db->select('id,name,term_id')
            ->where(array('branch_id' => $membership['branch_id'], 'session_id' => $enrollment['session_id'], 'status' => 1, 'publish_result' => 1))
            ->order_by('id', 'desc')->get('exam')->result_array();
        $this->ok(array_map(function ($r) { return array('id' => (int)$r['id'], 'name' => $r['name']); }, $rows));
    }

    public function show($examId)
    {
        $membership = $this->requireAuth();
        $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
        $exam = $this->db->where(array('id' => (int)$examId, 'branch_id' => $membership['branch_id'], 'session_id' => $enrollment['session_id'], 'status' => 1, 'publish_result' => 1))
            ->get('exam')->row_array();
        if (!$exam) $this->fail('exam_not_found', 'This exam is not available for this student.', 404);

        $subjectIds = array_column($this->db->distinct()->select('subject_id')
            ->where(array('exam_id' => $exam['id'], 'student_id' => $enrollment['student_id'], 'class_id' => $enrollment['class_id'], 'section_id' => $enrollment['section_id']))
            ->get('mark')->result_array(), 'subject_id');

        $subjects = array();
        $grandObtained = 0;
        $grandFull = 0;
        foreach ($subjectIds as $subjectId) {
            $totals = $this->exam_progress_model->getExamTotalMark($enrollment['student_id'], $enrollment['session_id'], $subjectId, $exam['id'], $enrollment['class_id'], $enrollment['section_id']);
            $obtained = (float)$totals['grand_obtain_marks'];
            $full = (float)$totals['grand_full_marks'];
            $percent = $full > 0 ? round($obtained / $full * 100, 1) : null;
            $subjects[] = array(
                'subject_id' => (int)$subjectId,
                'name' => $this->subjectName($subjectId),
                'obtained' => $obtained,
                'full' => $full,
                'percent' => $percent,
                'grade' => $percent !== null ? $this->gradeFor($membership['branch_id'], $percent) : null,
            );
            $grandObtained += $obtained;
            $grandFull += $full;
        }
        usort($subjects, function ($a, $b) { return strcmp($a['name'], $b['name']); });

        $rank = $this->db->where(array('exam_id' => $exam['id'], 'enroll_id' => $enrollment['id']))->get('exam_rank')->row_array();

        $this->ok(array(
            'exam' => array('id' => (int)$exam['id'], 'name' => $exam['name']),
            'student' => array('id' => (int)$enrollment['student_id'], 'name' => $enrollment['student_name']),
            'average_percent' => $grandFull > 0 ? round($grandObtained / $grandFull * 100, 1) : null,
            'rank' => $rank ? $rank['rank'] : null,
            'teacher_comments' => $rank['teacher_comments'] ?? null,
            'principal_comments' => $rank['principal_comments'] ?? null,
            'subjects' => $subjects,
        ));
    }

    private function subjectName($subjectId)
    {
        $row = $this->db->select('name')->where('id', $subjectId)->get('subject')->row();
        return $row ? $row->name : 'Subject';
    }

    private function gradeFor($branchId, $percent)
    {
        $row = $this->db->where(array('branch_id' => $branchId))
            ->where('lower_mark <=', $percent)->where('upper_mark >=', $percent)
            ->get('grade')->row_array();
        return $row ? array('name' => $row['name'], 'remark' => $row['remark']) : null;
    }
}
