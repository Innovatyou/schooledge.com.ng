<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

class Reports extends Api_Controller
{
    public function index()
    {
        $membership = $this->requireAuth();
        $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
        $this->db->select('exam.id,exam.name,exam.term_id');
        $this->db->from('timetable_exam');
        $this->db->join('exam', 'exam.id = timetable_exam.exam_id', 'inner');
        $this->db->where(array(
            'timetable_exam.class_id' => $enrollment['class_id'],
            'timetable_exam.section_id' => $enrollment['section_id'],
            'timetable_exam.session_id' => $enrollment['session_id'],
            'exam.branch_id' => $membership['branch_id'],
            'exam.status' => 1,
            'exam.publish_result' => 1,
        ));
        $rows = $this->db->group_by('exam.id')->order_by('exam.id', 'desc')->get()->result_array();
        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            $row['student_id'] = (int)$enrollment['student_id'];
            $row['student_name'] = $enrollment['student_name'];
        }
        $this->ok($rows);
    }

    public function download($examId)
    {
        $membership = $this->requireAuth();
        $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
        $exam = $this->db->select('exam.id,exam.name')->from('exam')
            ->join('timetable_exam', 'timetable_exam.exam_id = exam.id', 'inner')
            ->where(array(
                'exam.id' => (int)$examId,
                'exam.branch_id' => $membership['branch_id'],
                'exam.status' => 1,
                'exam.publish_result' => 1,
                'timetable_exam.class_id' => $enrollment['class_id'],
                'timetable_exam.section_id' => $enrollment['section_id'],
                'timetable_exam.session_id' => $enrollment['session_id'],
            ))->get()->row_array();
        if (!$exam) $this->fail('report_not_found', 'The requested published report is not available for this student.', 404);

        $this->load->model('marksheet_template_model');
        $this->load->model('exam_progress_model');
        $this->load->model('exam_model');
        $config = $this->app_lib->getSchoolConfig($membership['branch_id'], 'default_marksheet_temp');
        $templateId = $config ? $config->default_marksheet_temp : null;
        if (empty($templateId)) $this->fail('report_template_missing', 'The school has not configured a report template.', 409);
        $data = array(
            'student_array' => array($enrollment['student_id']),
            'print_date' => _d(date('Y-m-d')),
            'examID' => (int)$examId,
            'class_id' => $enrollment['class_id'],
            'section_id' => $enrollment['section_id'],
            'sessionID' => $enrollment['session_id'],
            'templateID' => $templateId,
            'branchID' => $membership['branch_id'],
            'marksheet_template' => $this->marksheet_template_model->getTemplate($templateId, $membership['branch_id']),
        );
        $html = $this->load->view('exam/reportCard_PDF', $data, true);
        $this->load->library('html2pdf');
        foreach ($this->stylesheets() as $stylesheet) {
            if (is_file($stylesheet)) $this->html2pdf->mpdf->WriteHTML(file_get_contents($stylesheet), 1);
        }
        $this->html2pdf->mpdf->WriteHTML($html);
        $this->html2pdf->mpdf->SetDisplayMode('fullpage');
        $pdf = $this->html2pdf->mpdf->Output('', 'S');
        $filename = preg_replace('/[^A-Za-z0-9_-]+/', '-', $exam['name']) . '-report.pdf';
        $this->auditDownload($membership, $examId);
        $this->output->set_content_type('application/pdf')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_header('Cache-Control: private, no-store')
            ->set_output($pdf);
        $this->output->_display();
        exit;
    }

    private function stylesheets()
    {
        return array(
            FCPATH . 'assets/vendor/bootstrap/css/bootstrap.min.css',
            FCPATH . 'assets/css/custom-style.css',
            FCPATH . 'assets/css/pdf-style.css',
            FCPATH . 'assets/css/document-templates.css',
        );
    }

    private function auditDownload($membership, $examId)
    {
        $this->db->insert('mobile_audit_log', array(
            'membership_id'=>$membership['id'], 'branch_id'=>$membership['branch_id'],
            'action'=>'report.download', 'resource_type'=>'exam', 'resource_id'=>$examId,
            'ip_address'=>$this->input->ip_address(),
            'user_agent'=>substr((string)$this->input->user_agent(), 0, 255),
            'created_at'=>date('Y-m-d H:i:s'),
        ));
    }
}
