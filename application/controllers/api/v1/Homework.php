<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * Mobile homework API. `homework.document` stores the teacher's ORIGINAL filename
 * for display, but the actual file on disk is always `{homework_id}.{ext}` under
 * uploads/attachments/homework/ (confirmed in Homework_model.php's save()) - not the
 * enc_name/file_name pair pattern used elsewhere. Submissions (homework_submit) DO
 * use that enc_name/file_name pair, matching Userrole::assignment_upload().
 */
class Homework extends Api_Controller
{
    public function index()
    {
        $membership = $this->requireAuth();
        $roleId = (int)$membership['role_id'];

        if ($roleId === 6 || $roleId === 7) {
            $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
            $rows = $this->db->select('homework.*,subject.name as subject_name,hs.id as submission_id,hs.message as submission_message,he.status as evaluation_status,he.remark as evaluation_remark')
                ->from('homework')
                ->join('subject', 'subject.id = homework.subject_id', 'left')
                ->join('homework_submit as hs', 'hs.homework_id = homework.id AND hs.student_id = ' . $this->db->escape($enrollment['student_id']), 'left')
                ->join('homework_evaluation as he', 'he.homework_id = homework.id AND he.student_id = ' . $this->db->escape($enrollment['student_id']), 'left')
                ->where(array('homework.branch_id' => $membership['branch_id'], 'homework.class_id' => $enrollment['class_id'], 'homework.section_id' => $enrollment['section_id'], 'homework.session_id' => $enrollment['session_id'], 'homework.status' => 0))
                ->order_by('homework.date_of_submission', 'desc')->get()->result_array();
            $this->ok(array_map(function ($r) { return $this->studentPayload($r); }, $rows));
            return;
        }

        if ($roleId === 3) {
            $rows = $this->db->select('homework.*,subject.name as subject_name,class.name as class_name,section.name as section_name')
                ->from('homework')
                ->join('subject', 'subject.id = homework.subject_id', 'left')
                ->join('class', 'class.id = homework.class_id', 'left')
                ->join('section', 'section.id = homework.section_id', 'left')
                ->where(array('homework.branch_id' => $membership['branch_id'], 'homework.created_by' => $membership['user_id']))
                ->order_by('homework.date_of_homework', 'desc')->limit(100)->get()->result_array();
            $this->ok(array_map(function ($r) { return $this->teacherPayload($r); }, $rows));
            return;
        }
        $this->fail('role_not_supported', 'Homework is available to teachers, students and linked parents.', 403);
    }

    public function download($id)
    {
        $membership = $this->requireAuth();
        $homework = $this->ownedHomework($membership, $id);
        if (empty($homework['document'])) $this->fail('file_not_found', 'No attachment was uploaded for this homework.', 404);
        $extension = pathinfo($homework['document'], PATHINFO_EXTENSION);
        $path = FCPATH . 'uploads/attachments/homework/' . $homework['id'] . '.' . $extension;
        if (!is_file($path)) $this->fail('file_not_found', 'No attachment was uploaded for this homework.', 404);
        $this->streamFile($path, $homework['document']);
    }

    public function submit($id)
    {
        $membership = $this->requireAuth();
        if ((int)$membership['role_id'] !== 7) $this->fail('role_not_supported', 'Only students can submit homework.', 403);
        $this->blockIfDemoReadonly($membership['branch_id']);
        $enrollment = $this->resolveOwnedEnrollment($membership, null);
        $homework = $this->db->where(array('id' => (int)$id, 'branch_id' => $membership['branch_id'], 'class_id' => $enrollment['class_id'], 'section_id' => $enrollment['section_id']))->get('homework')->row_array();
        if (!$homework) $this->fail('homework_not_found', 'Homework not found.', 404);

        $message = trim((string)$this->input->post('message'));
        if ($message === '') $this->fail('validation_error', 'A message is required.', 422, array('message' => 'required'));
        $data = array('homework_id' => $homework['id'], 'student_id' => $enrollment['student_id'], 'message' => $message);

        if (!empty($_FILES['file']['name'])) {
            $uploadPath = FCPATH . 'uploads/attachments/homework_submit/';
            $config = array('upload_path' => $uploadPath, 'encrypt_name' => true, 'allowed_types' => '*');
            $this->load->library('upload', $config);
            $this->upload->initialize($config);
            if (!$this->upload->do_upload('file')) $this->fail('upload_failed', $this->upload->display_errors('', ''), 422);
            $data['file_name'] = $this->upload->data('orig_name');
            $data['enc_name'] = $this->upload->data('file_name');
        }

        $existing = $this->db->where(array('homework_id' => $homework['id'], 'student_id' => $enrollment['student_id']))->get('homework_submit')->row_array();
        if ($existing) {
            $this->db->where('id', $existing['id'])->update('homework_submit', $data);
        } else {
            $this->db->insert('homework_submit', $data);
        }
        $this->logAudit('homework.submit', $membership, 'homework', $homework['id']);
        $this->ok(array('submitted' => true));
    }

    public function submissions($id)
    {
        $membership = $this->requireAuth();
        if ((int)$membership['role_id'] !== 3) $this->fail('role_not_supported', 'Only teachers can view submissions.', 403);
        $homework = $this->db->where(array('id' => (int)$id, 'branch_id' => $membership['branch_id'], 'created_by' => $membership['user_id']))->get('homework')->row_array();
        if (!$homework) $this->fail('homework_not_found', 'Homework not found.', 404);

        $rows = $this->db->select('hs.id,hs.student_id,hs.message,hs.file_name,hs.enc_name,hs.created_at,CONCAT_WS(" ",student.first_name,student.last_name) as student_name,he.status as evaluation_status,he.remark as evaluation_remark')
            ->from('homework_submit as hs')
            ->join('student', 'student.id = hs.student_id', 'inner')
            ->join('homework_evaluation as he', 'he.homework_id = hs.homework_id AND he.student_id = hs.student_id', 'left')
            ->where('hs.homework_id', $homework['id'])->get()->result_array();
        $this->ok(array_map(function ($r) {
            return array(
                'submission_id' => (int)$r['id'], 'student_id' => (int)$r['student_id'], 'student_name' => $r['student_name'],
                'message' => $r['message'], 'has_file' => !empty($r['enc_name']), 'submitted_at' => $r['created_at'],
                'evaluation_status' => $r['evaluation_status'], 'evaluation_remark' => $r['evaluation_remark'],
            );
        }, $rows));
    }

    public function download_submission($id, $studentId)
    {
        $membership = $this->requireAuth();
        if ((int)$membership['role_id'] !== 3) $this->fail('role_not_supported', 'Only teachers can download submissions.', 403);
        $homework = $this->db->where(array('id' => (int)$id, 'branch_id' => $membership['branch_id'], 'created_by' => $membership['user_id']))->get('homework')->row_array();
        if (!$homework) $this->fail('homework_not_found', 'Homework not found.', 404);
        $submission = $this->db->where(array('homework_id' => $homework['id'], 'student_id' => (int)$studentId))->get('homework_submit')->row_array();
        if (!$submission || empty($submission['enc_name'])) $this->fail('file_not_found', 'No file was submitted.', 404);
        $path = FCPATH . 'uploads/attachments/homework_submit/' . $submission['enc_name'];
        if (!is_file($path)) $this->fail('file_not_found', 'No file was submitted.', 404);
        $this->streamFile($path, $submission['file_name']);
    }

    private function ownedHomework(array $membership, $id)
    {
        $roleId = (int)$membership['role_id'];
        if ($roleId === 3) {
            $homework = $this->db->where(array('id' => (int)$id, 'branch_id' => $membership['branch_id'], 'created_by' => $membership['user_id']))->get('homework')->row_array();
        } else {
            $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
            $homework = $this->db->where(array('id' => (int)$id, 'branch_id' => $membership['branch_id'], 'class_id' => $enrollment['class_id'], 'section_id' => $enrollment['section_id']))->get('homework')->row_array();
        }
        if (!$homework) $this->fail('homework_not_found', 'Homework not found.', 404);
        return $homework;
    }

    private function studentPayload(array $r)
    {
        return array(
            'id' => (int)$r['id'], 'subject' => $r['subject_name'], 'description' => $r['description'],
            'date_of_homework' => $r['date_of_homework'], 'due_date' => $r['date_of_submission'],
            'has_attachment' => !empty($r['document']),
            'submitted' => !empty($r['submission_id']), 'submission_message' => $r['submission_message'],
            'evaluation_status' => $r['evaluation_status'], 'evaluation_remark' => $r['evaluation_remark'],
        );
    }

    private function teacherPayload(array $r)
    {
        return array(
            'id' => (int)$r['id'], 'subject' => $r['subject_name'], 'class_name' => $r['class_name'], 'section_name' => $r['section_name'],
            'description' => $r['description'], 'date_of_homework' => $r['date_of_homework'], 'due_date' => $r['date_of_submission'],
            'has_attachment' => !empty($r['document']),
        );
    }

    private function streamFile($path, $displayName)
    {
        $this->output->set_content_type('application/octet-stream')
            ->set_header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_. -]+/', '-', $displayName) . '"')
            ->set_header('Cache-Control: private, no-store')
            ->set_output(file_get_contents($path));
        $this->output->_display();
        exit;
    }
}
