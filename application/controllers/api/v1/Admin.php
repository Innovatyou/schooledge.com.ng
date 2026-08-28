<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * Mobile admin dashboard: summary stats, a lookup search, broadcast
 * announcements, and the expense/fee-collection/online-admission maker-checker
 * approval queues (reusing the exact same model methods -
 * Accounting_model::approveExpenseRequest(), Online_admission_model::finalizeSave()
 * etc. - the web app's own approval screens call, so the side-effects - ledger
 * postings, real student/parent/login_credential rows, credential emails - are
 * identical, not a parallel reimplementation).
 */
class Admin extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('accounting_model');
        $this->load->model('fees_model');
        $this->load->model('online_admission_model');
        $this->load->model('email_model');
        $this->load->model('sms_model');
    }

    public function summary()
    {
        $membership = $this->requireAuth();
        $this->requireAdminAccess($membership);
        $branchId = $membership['branch_id'];

        $studentCount = $this->db->where('branch_id', $branchId)->where('is_alumni', 0)->count_all_results('enroll');
        $staffCount = $this->db->where('branch_id', $branchId)->count_all_results('staff');
        $parentCount = $this->db->where('branch_id', $branchId)->count_all_results('parent');

        $today = date('Y-m-d');
        $attendanceRows = $this->db->select('status,COUNT(*) as total')
            ->from('student_attendance')->where(array('branch_id' => $branchId, 'date' => $today))
            ->group_by('status')->get()->result_array();
        $present = 0;
        $totalMarked = 0;
        foreach ($attendanceRows as $row) {
            $totalMarked += (int)$row['total'];
            if ($row['status'] === 'P') $present = (int)$row['total'];
        }

        $pendingExpenses = $this->hasPermission($membership['role_id'], 'expense_approve', 'is_view')
            ? $this->db->where(array('branch_id' => $branchId, 'status' => 1))->count_all_results('expense_requests') : 0;
        $pendingFees = $this->hasPermission($membership['role_id'], 'collect_fees_approve', 'is_view')
            ? $this->db->where(array('branch_id' => $branchId, 'status' => 1))->count_all_results('fee_collection_requests') : 0;

        $this->ok(array(
            'students' => $studentCount, 'staff' => $staffCount, 'parents' => $parentCount,
            'attendance_today' => array('present' => $present, 'marked' => $totalMarked, 'percent' => $totalMarked > 0 ? round($present / $totalMarked * 100, 1) : null),
            'pending_approvals' => $pendingExpenses + $pendingFees,
        ));
    }

    public function approvals()
    {
        $membership = $this->requireAuth();
        $this->requireAdminAccess($membership);
        $items = array();

        if ($this->hasPermission($membership['role_id'], 'expense_approve', 'is_view')) {
            $this->bridgeLegacySession($membership);
            foreach ($this->accounting_model->getExpenseRequestList(array('er.status' => 1)) as $row) {
                $items[] = array(
                    'type' => 'expense', 'id' => (int)$row['id'],
                    'title' => ($row['v_head'] ?? 'Expense') . ' — ' . $row['ac_name'],
                    'subtitle' => $row['description'], 'amount' => (float)$row['amount'], 'date' => $row['date'],
                );
            }
        }
        if ($this->hasPermission($membership['role_id'], 'collect_fees_approve', 'is_view')) {
            $this->bridgeLegacySession($membership);
            foreach ($this->fees_model->getFeeCollectionRequestList(array('fcr.status' => 1)) as $row) {
                $items[] = array(
                    'type' => 'fees', 'id' => (int)$row['id'],
                    'title' => ($row['fullname'] ?? 'Student') . ' (' . $row['class_name'] . ' ' . $row['section_name'] . ')',
                    'subtitle' => 'Fee collection', 'amount' => (float)$row['total_amount'], 'date' => $row['date'],
                );
            }
        }
        if ($this->hasPermission($membership['role_id'], 'online_admission_approve', 'is_view')) {
            $this->bridgeLegacySession($membership);
            foreach ($this->online_admission_model->getStagingList(array('oas.branch_id' => $membership['branch_id'], 'oas.status' => 1)) as $row) {
                $items[] = array(
                    'type' => 'admission', 'id' => (int)$row['id'],
                    'title' => trim($row['first_name'] . ' ' . $row['last_name']) . ' (' . $row['class_name'] . ' ' . $row['section_name'] . ')',
                    'subtitle' => 'New admission — ' . $row['reference_no'], 'amount' => null, 'date' => $row['staged_at'],
                );
            }
        }
        $this->ok($items);
    }

    /** Staged-admission summary for the review screen, with a live uniqueness re-check (same conflicts that block approval). */
    public function admission_detail($id)
    {
        $membership = $this->requireAuth();
        if (!$this->hasPermission($membership['role_id'], 'online_admission_approve', 'is_view')) $this->fail('permission_denied', 'You do not have permission to view this.', 403);
        $this->bridgeLegacySession($membership);
        $staging = $this->online_admission_model->getStagingList(array('oas.id' => (int)$id, 'oas.branch_id' => $membership['branch_id']), true);
        if (empty($staging)) $this->fail('request_not_found', 'Request not found.', 404);
        $payload = json_decode($staging['staged_payload'], true) ?: array();
        $conflicts = $staging['status'] == 1 ? $this->online_admission_model->checkStagedUniqueness($payload, $membership['branch_id']) : array();
        $this->ok(array(
            'id' => (int)$staging['id'], 'status' => (int)$staging['status'],
            'first_name' => $staging['first_name'], 'last_name' => $staging['last_name'],
            'reference_no' => $staging['reference_no'], 'class_name' => $staging['class_name'], 'section_name' => $staging['section_name'],
            'staged_at' => $staging['staged_at'], 'comments' => $staging['comments'],
            'guardian_name' => $payload['grd_name'] ?? ($payload['father_name'] ?? null),
            'mobileno' => $payload['mobileno'] ?? null, 'email' => $payload['email'] ?? null,
            'can_approve' => $staging['status'] == 1 && (int)$staging['staged_by'] !== (int)$membership['user_id'],
            'conflicts' => $conflicts,
        ));
    }

    /**
     * Checker approves a staged admission - mirrors Online_admission::admission_approval_save()'s
     * status===2 branch exactly (same order: saas limit -> uniqueness re-check ->
     * finalizeSave -> enroll -> custom fields -> credential email/sms -> staging row
     * updated last), since that is where the real student/parent/login_credential
     * rows actually get created and any manually-typed staged password is decrypted
     * and emailed - nothing here should drift from that path.
     */
    public function approve_admission($id)
    {
        $membership = $this->requireAuth();
        if (!$this->hasPermission($membership['role_id'], 'online_admission_approve', 'is_add')) $this->fail('permission_denied', 'You do not have permission to do this.', 403);
        $this->blockIfDemoReadonly($membership['branch_id']);
        $branchId = (int)$membership['branch_id'];
        $this->bridgeLegacySession($membership);
        $staging = $this->online_admission_model->getStagingList(array('oas.id' => (int)$id, 'oas.branch_id' => $branchId), true);
        if (empty($staging) || $staging['status'] != 1) $this->fail('request_not_found', 'Request not found or already actioned.', 404);
        if ((int)$staging['staged_by'] === (int)$membership['user_id']) $this->fail('self_approval_not_allowed', 'You cannot approve your own submission.', 403);

        if ($this->app_lib->isExistingAddon('saas') && !checkSaasLimit('student')) {
            $this->fail('saas_limit_reached', 'This school has reached its student limit for the current package.', 422);
        }

        $payload = json_decode($staging['staged_payload'], true) ?: array();
        $conflicts = $this->online_admission_model->checkStagedUniqueness($payload, $branchId);
        if (!empty($conflicts)) $this->fail('validation_error', implode(' ', $conflicts), 422, $conflicts);

        $getBranch = $this->db->where('id', $branchId)->get('branch')->row_array();
        $studentData = $this->online_admission_model->finalizeSave($payload, $getBranch);
        $studentId = $studentData['student_id'];
        if (!$studentId) $this->fail('server_error', 'The student record could not be created. Nothing was approved - try again or contact support.', 500);

        $arrayEnroll = array(
            'student_id' => $studentId,
            'class_id' => $payload['class_id'] ?? null,
            'section_id' => $payload['section_id'] ?? 0,
            'roll' => $payload['roll'] ?? 0,
            'session_id' => $payload['year_id'] ?? null,
            'branch_id' => $branchId,
        );
        $this->db->insert('enroll', $arrayEnroll);

        $this->db->where('id', $staging['online_admission_id'])->update('online_admission', array('status' => 2));

        if (!empty($payload['custom_fields_student'])) {
            $this->load->helper('custom_fields');
            saveCustomFields($payload['custom_fields_student'], $studentId);
        }

        $this->email_model->studentAdmission($studentData);
        $this->sms_model->send_sms($arrayEnroll, 1);

        $comments = (string)($this->body()['comments'] ?? '');
        $this->db->where('id', $staging['id'])->update('online_admission_staging', array(
            'status' => 2, 'reviewed_by' => $membership['user_id'], 'comments' => $comments, 'reviewed_at' => date('Y-m-d H:i:s'),
        ));
        audit_log('approve', 'online_admission_staging', $staging['id'], array('status' => 1), array('status' => 2, 'student_id' => $studentId));
        $this->logAudit('admin.admission_approved', $membership, 'online_admission_staging', $staging['id']);
        $this->ok(array('approved' => true, 'student_id' => $studentId));
    }

    public function reject_admission($id)
    {
        $membership = $this->requireAuth();
        if (!$this->hasPermission($membership['role_id'], 'online_admission_approve', 'is_add')) $this->fail('permission_denied', 'You do not have permission to do this.', 403);
        $this->blockIfDemoReadonly($membership['branch_id']);
        $this->bridgeLegacySession($membership);
        $staging = $this->online_admission_model->getStagingList(array('oas.id' => (int)$id, 'oas.branch_id' => $membership['branch_id']), true);
        if (empty($staging) || $staging['status'] != 1) $this->fail('request_not_found', 'Request not found or already actioned.', 404);
        if ((int)$staging['staged_by'] === (int)$membership['user_id']) $this->fail('self_approval_not_allowed', 'You cannot reject your own submission.', 403);

        $comments = (string)($this->body()['comments'] ?? '');
        $this->db->where('id', $staging['id'])->update('online_admission_staging', array(
            'status' => 3, 'reviewed_by' => $membership['user_id'], 'comments' => $comments, 'reviewed_at' => date('Y-m-d H:i:s'),
        ));
        $this->db->where('id', $staging['online_admission_id'])->update('online_admission', array('status' => 3));
        audit_log('reject', 'online_admission_staging', $staging['id'], array('status' => 1), array('status' => 3, 'comments' => $comments));
        $this->logAudit('admin.admission_rejected', $membership, 'online_admission_staging', $staging['id']);
        $this->ok(array('rejected' => true));
    }

    public function approve_expense($id)
    {
        $membership = $this->requireAuth();
        $this->requirePermission($membership, 'expense_approve');
        $request = $this->db->where(array('id' => (int)$id, 'branch_id' => $membership['branch_id'], 'status' => 1))->get('expense_requests')->row_array();
        if (!$request) $this->fail('request_not_found', 'Request not found or already actioned.', 404);
        if ((int)$request['requested_by'] === (int)$membership['user_id']) $this->fail('self_approval_not_allowed', 'You cannot approve your own request.', 403);
        $this->bridgeLegacySession($membership);
        $transactionId = $this->accounting_model->approveExpenseRequest($request['id'], (string)($this->body()['comments'] ?? ''));
        $this->logAudit('admin.expense_approved', $membership, 'expense_request', $request['id']);
        $this->ok(array('approved' => true, 'transaction_id' => $transactionId));
    }

    public function reject_expense($id)
    {
        $membership = $this->requireAuth();
        $this->requirePermission($membership, 'expense_approve');
        $request = $this->db->where(array('id' => (int)$id, 'branch_id' => $membership['branch_id'], 'status' => 1))->get('expense_requests')->row_array();
        if (!$request) $this->fail('request_not_found', 'Request not found or already actioned.', 404);
        if ((int)$request['requested_by'] === (int)$membership['user_id']) $this->fail('self_approval_not_allowed', 'You cannot reject your own request.', 403);
        $this->bridgeLegacySession($membership);
        $this->accounting_model->rejectExpenseRequest($request['id'], (string)($this->body()['comments'] ?? ''));
        $this->logAudit('admin.expense_rejected', $membership, 'expense_request', $request['id']);
        $this->ok(array('rejected' => true));
    }

    public function approve_fees($id)
    {
        $membership = $this->requireAuth();
        $this->requirePermission($membership, 'collect_fees_approve');
        $request = $this->db->where(array('id' => (int)$id, 'branch_id' => $membership['branch_id'], 'status' => 1))->get('fee_collection_requests')->row_array();
        if (!$request) $this->fail('request_not_found', 'Request not found or already actioned.', 404);
        if ((int)$request['collected_by'] === (int)$membership['user_id']) $this->fail('self_approval_not_allowed', 'You cannot approve your own request.', 403);
        $this->bridgeLegacySession($membership);
        $paymentIds = $this->fees_model->approveFeeCollectionRequest($request['id'], (string)($this->body()['comments'] ?? ''));
        $this->logAudit('admin.fees_approved', $membership, 'fee_collection_request', $request['id']);
        $this->ok(array('approved' => true, 'payment_ids' => $paymentIds));
    }

    public function reject_fees($id)
    {
        $membership = $this->requireAuth();
        $this->requirePermission($membership, 'collect_fees_approve');
        $request = $this->db->where(array('id' => (int)$id, 'branch_id' => $membership['branch_id'], 'status' => 1))->get('fee_collection_requests')->row_array();
        if (!$request) $this->fail('request_not_found', 'Request not found or already actioned.', 404);
        if ((int)$request['collected_by'] === (int)$membership['user_id']) $this->fail('self_approval_not_allowed', 'You cannot reject your own request.', 403);
        $this->bridgeLegacySession($membership);
        $this->fees_model->rejectFeeCollectionRequest($request['id'], (string)($this->body()['comments'] ?? ''));
        $this->logAudit('admin.fees_rejected', $membership, 'fee_collection_request', $request['id']);
        $this->ok(array('rejected' => true));
    }

    /** A branch-wide announcement (event.audition = 1 = everybody), for emergency/general broadcasts. */
    public function broadcast()
    {
        $membership = $this->requireAuth();
        if ((int)$membership['role_id'] !== 2 && (int)$membership['role_id'] !== 1) $this->fail('role_not_supported', 'Broadcasts can only be sent by a school administrator.', 403);
        $this->blockIfDemoReadonly($membership['branch_id']);
        $input = $this->body();
        $title = trim((string)($input['title'] ?? ''));
        $body = trim((string)($input['body'] ?? ''));
        if ($title === '' || $body === '') $this->fail('validation_error', 'title and body are required.', 422);

        $this->db->insert('event', array(
            'title' => $title, 'remark' => $body, 'type' => null, 'audition' => 1, 'selected_list' => '',
            'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d'), 'branch_id' => $membership['branch_id'],
            'status' => 1, 'show_web' => 0,
        ));
        $eventId = $this->db->insert_id();
        $this->logAudit('admin.broadcast', $membership, 'event', $eventId);
        $this->ok(array('id' => $eventId));
    }

    public function lookup()
    {
        $membership = $this->requireAuth();
        $this->requireAdminAccess($membership);
        $query = trim((string)$this->input->get('q'));
        if (mb_strlen($query) < 2) $this->fail('validation_error', 'Enter at least 2 characters to search.', 422);
        $branchId = $membership['branch_id'];
        $results = array();

        $students = $this->db->select('student.id,CONCAT_WS(" ",student.first_name,student.last_name) as name,student.register_no')
            ->from('student')->join('enroll', 'enroll.student_id = student.id', 'inner')
            ->where('enroll.branch_id', $branchId)->group_start()->like('student.first_name', $query)->or_like('student.last_name', $query)->or_like('student.register_no', $query)->group_end()
            ->group_by('student.id')->limit(15)->get()->result_array();
        foreach ($students as $row) $results[] = array('type' => 'student', 'id' => (int)$row['id'], 'name' => $row['name'], 'detail' => $row['register_no']);

        $staff = $this->db->select('id,name,staff_id,department')->where('branch_id', $branchId)->group_start()->like('name', $query)->or_like('staff_id', $query)->group_end()->limit(15)->get('staff')->result_array();
        foreach ($staff as $row) $results[] = array('type' => 'staff', 'id' => (int)$row['id'], 'name' => $row['name'], 'detail' => $row['department']);

        $parents = $this->db->select('id,name,mobileno')->where('branch_id', $branchId)->like('name', $query)->limit(15)->get('parent')->result_array();
        foreach ($parents as $row) $results[] = array('type' => 'parent', 'id' => (int)$row['id'], 'name' => $row['name'], 'detail' => $row['mobileno']);

        $this->ok($results);
    }

    private function requireAdminAccess(array $membership)
    {
        if (!in_array((int)$membership['role_id'], array(1, 2, 4), true)) $this->fail('role_not_supported', 'This is only available to school administrators.', 403);
    }

    private function requirePermission(array $membership, $prefix)
    {
        if (!$this->hasPermission($membership['role_id'], $prefix, 'is_add')) $this->fail('permission_denied', 'You do not have permission to do this.', 403);
    }
}
