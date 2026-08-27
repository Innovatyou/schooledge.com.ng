<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * Mobile learning-resources API on the existing `attachments` table (the "Learning
 * resources" / study-material module - module key attachments_book). Mirrors the
 * ownership check the web Attachments::download() fix already applies: a resource
 * targets either every class ('unfiltered') or one specific class_id.
 */
class Resources extends Api_Controller
{
    public function index()
    {
        $membership = $this->requireAuth();
        // Resolve enrollment (its own query) BEFORE starting the attachments query
        // below - CI3's query builder holds one pending chain per call, so
        // interleaving two unfinished chains silently merges their WHERE/JOIN
        // clauses together (see the same fix in Events.php/Liveclasses.php).
        $classId = $this->ownedClassId($membership);

        $this->db->select('attachments.id,attachments.title,attachments.remarks,attachments.date,attachments.class_id,subject.name as subject_name,attachments.file_name')
            ->from('attachments')
            ->join('subject', 'subject.id = attachments.subject_id', 'left')
            ->where('attachments.branch_id', $membership['branch_id']);
        if ($classId !== null) {
            $this->db->group_start()->where('attachments.class_id', 'unfiltered')->or_where('attachments.class_id', $classId)->group_end();
        }
        $rows = $this->db->order_by('attachments.date', 'desc')->limit(100)->get()->result_array();
        $this->ok(array_map(function ($r) {
            return array(
                'id' => (int)$r['id'], 'title' => $r['title'], 'remarks' => $r['remarks'],
                'subject' => $r['subject_name'], 'date' => $r['date'],
                'extension' => strtolower(pathinfo($r['file_name'], PATHINFO_EXTENSION)),
            );
        }, $rows));
    }

    public function download($id)
    {
        $membership = $this->requireAuth();
        $classId = $this->ownedClassId($membership);

        $this->db->where(array('id' => (int)$id, 'branch_id' => $membership['branch_id']));
        if ($classId !== null) {
            $this->db->group_start()->where('class_id', 'unfiltered')->or_where('class_id', $classId)->group_end();
        }
        $attachment = $this->db->get('attachments')->row_array();
        if (!$attachment) $this->fail('resource_not_found', 'Resource not found.', 404);
        $path = FCPATH . 'uploads/attachments/' . $attachment['enc_name'];
        if (!is_file($path)) $this->fail('resource_not_found', 'Resource not found.', 404);

        $this->logAudit('resource.download', $membership, 'attachment', $attachment['id']);
        $this->output->set_content_type('application/octet-stream')
            ->set_header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_. -]+/', '-', $attachment['file_name']) . '"')
            ->set_header('Cache-Control: private, no-store')
            ->set_output(file_get_contents($path));
        $this->output->_display();
        exit;
    }

    /** null for staff roles (no class restriction); the owned enrollment's class_id for parent/student. */
    private function ownedClassId(array $membership)
    {
        $roleId = (int)$membership['role_id'];
        if ($roleId !== 6 && $roleId !== 7) return null;
        $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
        return $enrollment['class_id'];
    }
}
