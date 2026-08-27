<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * Mobile admin dashboard: summary stats, a lookup search, broadcast
 * announcements, and the expense/fee-collection maker-checker approval queues
 * (reusing the exact same model methods - Accounting_model::approveExpenseRequest()
 * etc. - the web app's own approval screens call, so the ledger side-effects are
 * identical). Online-admission approval is deliberately NOT included yet: it
 * creates real parent/student/login_credential rows and decrypts staged secrets,
 * and is left as a web-only action for now rather than risk a subtly different
 * mobile reimplementation of that specific flow.
 */
class Admin extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('accounting_model');
        $this->load->model('fees_model');
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
        $this->ok($items);
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
