<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * Mobile fees & payments API. Reuses Fees_model's existing balance/invoice logic
 * (the same methods the web app's Userrole::invoice() calls) so mobile and web
 * always agree on what a student owes, then adds what the web app never had for
 * students/parents: a real payment_transactions state machine with idempotency,
 * server-side gateway verification, and PDF invoice/receipt downloads.
 */
class Fees extends Api_Controller
{
    /** Gateways this endpoint actually knows how to initialize + verify today. */
    private $supportedGateways = array('paystack');

    public function __construct()
    {
        parent::__construct();
        $this->load->model('fees_model');
        $this->load->model('authentication_model');
    }

    public function summary()
    {
        $membership = $this->requireAuth();
        $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
        $this->ok($this->buildSummary($membership, $enrollment));
    }

    public function history()
    {
        $membership = $this->requireAuth();
        $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
        $requestedAllocation = $this->input->get('allocation_id');
        $allocationIds = $requestedAllocation ? array((int)$requestedAllocation) : $this->allocationIdsFor($enrollment['id']);

        $rows = array();
        foreach ($allocationIds as $allocationId) {
            foreach ($this->fees_model->getPaymentHistory($allocationId, null) as $row) {
                $rows[] = array(
                    'id' => (int)$row['id'],
                    'date' => $row['date'],
                    'fee_type' => $row['name'],
                    'amount' => (float)$row['amount'],
                    'discount' => (float)$row['discount'],
                    'fine' => (float)$row['fine'],
                    'pay_via' => $row['payvia'],
                    'remarks' => $row['remarks'],
                );
            }
        }
        usort($rows, function ($a, $b) { return strcmp($b['date'], $a['date']) ?: $b['id'] - $a['id']; });
        $this->ok($rows);
    }

    public function gateways()
    {
        $membership = $this->requireAuth();
        $this->ok($this->enabledGateways($membership['branch_id']));
    }

    public function invoice_download()
    {
        $membership = $this->requireAuth();
        $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
        $summary = $this->buildSummary($membership, $enrollment);
        $branch = $this->db->select('school_name,address')->where('id', $membership['branch_id'])->get('branch')->row_array();

        $rows = '';
        foreach ($summary['items'] as $item) {
            $rows .= '<tr><td>' . htmlspecialchars($item['name']) . '</td><td>' . htmlspecialchars($item['due_date']) . '</td>'
                . '<td align="right">' . number_format($item['amount'], 2) . '</td><td align="right">' . number_format($item['paid'] + $item['discount'], 2) . '</td>'
                . '<td align="right">' . number_format($item['balance'], 2) . '</td></tr>';
        }
        if ($summary['transport']) {
            $t = $summary['transport'];
            $rows .= '<tr><td>Transport</td><td>-</td><td align="right">' . number_format($t['amount'], 2) . '</td>'
                . '<td align="right">' . number_format($t['paid'] + $t['discount'], 2) . '</td><td align="right">' . number_format($t['balance'], 2) . '</td></tr>';
        }

        $html = '<h2>' . htmlspecialchars($branch['school_name'] ?? '') . '</h2>'
            . '<p>' . htmlspecialchars($branch['address'] ?? '') . '</p>'
            . '<h3>Fee Invoice #' . htmlspecialchars($summary['invoice_no']) . '</h3>'
            . '<p><strong>Student:</strong> ' . htmlspecialchars($summary['student']['name']) . '</p>'
            . '<table border="1" cellpadding="6" cellspacing="0" width="100%">'
            . '<thead><tr><th>Fee Type</th><th>Due Date</th><th>Amount</th><th>Paid</th><th>Balance</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody></table>'
            . '<p style="margin-top:16px;"><strong>Total due:</strong> ' . number_format($summary['total_due'], 2)
            . ' &nbsp; <strong>Total paid:</strong> ' . number_format($summary['total_paid'] + $summary['total_discount'], 2)
            . ' &nbsp; <strong>Balance:</strong> ' . number_format($summary['balance'], 2) . '</p>';

        $this->streamPdf($html, 'invoice-' . $summary['invoice_no'] . '.pdf');
        $this->audit('fees.invoice_download', $membership, $enrollment['student_id']);
    }

    public function receipt_download($paymentId)
    {
        $membership = $this->requireAuth();
        $payment = $this->db->select('fee_payment_history.*,fee_allocation.student_id as enroll_id,fee_allocation.branch_id,fees_type.name as fee_type_name')
            ->from('fee_payment_history')
            ->join('fee_allocation', 'fee_allocation.id = fee_payment_history.allocation_id', 'left')
            ->join('fees_type', 'fees_type.id = fee_payment_history.type_id', 'left')
            ->where('fee_payment_history.id', (int)$paymentId)
            ->get()->row_array();
        if (!$payment || (int)$payment['branch_id'] !== (int)$membership['branch_id']) $this->fail('receipt_not_found', 'Receipt not found.', 404);

        $requestedStudentId = $this->input->get('student_id');
        $enrollment = $this->resolveOwnedEnrollment($membership, $requestedStudentId);
        if ((int)$payment['enroll_id'] !== (int)$enrollment['id']) $this->fail('receipt_not_found', 'Receipt not found.', 404);

        $branch = $this->db->select('school_name,address')->where('id', $membership['branch_id'])->get('branch')->row_array();
        $html = '<h2>' . htmlspecialchars($branch['school_name'] ?? '') . '</h2>'
            . '<p>' . htmlspecialchars($branch['address'] ?? '') . '</p>'
            . '<h3>Payment Receipt #' . (int)$payment['id'] . '</h3>'
            . '<p><strong>Student:</strong> ' . htmlspecialchars($enrollment['student_name']) . '</p>'
            . '<p><strong>Date:</strong> ' . htmlspecialchars($payment['date']) . '</p>'
            . '<table border="1" cellpadding="6" cellspacing="0" width="100%">'
            . '<tr><th>Fee Type</th><td>' . htmlspecialchars($payment['fee_type_name'] ?: 'Transport') . '</td></tr>'
            . '<tr><th>Amount</th><td>' . number_format((float)$payment['amount'], 2) . '</td></tr>'
            . '<tr><th>Discount</th><td>' . number_format((float)$payment['discount'], 2) . '</td></tr>'
            . '<tr><th>Fine</th><td>' . number_format((float)$payment['fine'], 2) . '</td></tr>'
            . '<tr><th>Remarks</th><td>' . htmlspecialchars($payment['remarks']) . '</td></tr>'
            . '</table>';

        $this->streamPdf($html, 'receipt-' . $payment['id'] . '.pdf');
        $this->audit('fees.receipt_download', $membership, $enrollment['student_id']);
    }

    public function checkout()
    {
        $membership = $this->requireAuth();
        $this->blockIfDemoReadonly($membership['branch_id']);
        $input = $this->body();
        if (empty($input['idempotency_key'])) $this->fail('validation_error', 'An idempotency_key is required.', 422, array('idempotency_key' => 'required'));
        $enrollment = $this->resolveOwnedEnrollment($membership, $input['student_id'] ?? null);

        $amount = isset($input['amount']) ? (float)$input['amount'] : 0;
        if ($amount <= 0) $this->fail('validation_error', 'A positive amount is required.', 422, array('amount' => 'required'));

        $isTransport = !empty($input['transport_fee_details_id']);
        if ($isTransport) {
            $transportId = (int)$input['transport_fee_details_id'];
            $owned = $this->db->where(array('id' => $transportId, 'enroll_id' => $enrollment['id']))->get('transport_fee_details')->row();
            if (!$owned) $this->fail('resource_not_found', 'Transport fee item not found for this student.', 404);
            $balance = $this->transportBalance($transportId)['balance'];
            $allocationId = null;
            $typeId = null;
        } else {
            $allocationId = (int)($input['allocation_id'] ?? 0);
            $typeId = (int)($input['type_id'] ?? 0);
            if (!$allocationId || !$typeId) $this->fail('validation_error', 'allocation_id and type_id (or transport_fee_details_id) are required.', 422);
            $owned = $this->db->where(array('id' => $allocationId, 'student_id' => $enrollment['id']))->get('fee_allocation')->row();
            if (!$owned) $this->fail('resource_not_found', 'Fee allocation not found for this student.', 404);
            $balance = $this->fees_model->getBalance($allocationId, $typeId)['balance'];
            $transportId = null;
        }
        if ($amount - $balance > 0.01) $this->fail('amount_exceeds_balance', 'The amount exceeds the outstanding balance.', 422, array('balance' => round($balance, 2)));

        $gateway = strtolower((string)($input['gateway'] ?? ''));
        if (!in_array($gateway, $this->supportedGateways, true)) $this->fail('gateway_not_configured', 'This payment method is not available yet.', 409);
        $config = $this->db->where('branch_id', $membership['branch_id'])->get('payment_config')->row_array();
        if (!$config || empty($config[$gateway . '_status'])) $this->fail('gateway_not_configured', 'This payment method is not available for your school.', 409);

        $existing = $this->db->where(array('branch_id' => $membership['branch_id'], 'idempotency_key' => $input['idempotency_key']))->get('payment_transactions')->row_array();
        if ($existing) {
            $this->ok(array('transaction_id' => (int)$existing['id'], 'status' => $existing['status'], 'gateway' => $existing['gateway'], 'reference' => $existing['gateway_reference'], 'checkout_url' => null));
            return;
        }

        $reference = 'MOB' . strtoupper(bin2hex(random_bytes(10)));
        $this->db->insert('payment_transactions', array(
            'branch_id' => $membership['branch_id'], 'membership_id' => $membership['id'], 'purpose' => 'fee_payment',
            'resource_type' => $isTransport ? 'transport_fee_details' : 'fee_allocation',
            'resource_id' => $isTransport ? (string)$transportId : $allocationId . ':' . $typeId,
            'gateway' => $gateway, 'gateway_reference' => $reference, 'idempotency_key' => $input['idempotency_key'],
            'amount' => $amount, 'currency' => $this->branchCurrency($membership['branch_id']), 'status' => 'created',
            'created_at' => date('Y-m-d H:i:s'),
        ));
        $transactionId = $this->db->insert_id();

        $checkoutUrl = $this->initGateway($gateway, $config, $reference, $amount, $membership);
        if (!$checkoutUrl) {
            $this->db->where('id', $transactionId)->update('payment_transactions', array('status' => 'failed', 'failure_message' => 'Gateway initialization failed', 'failed_at' => date('Y-m-d H:i:s')));
            $this->fail('gateway_error', 'Unable to start the payment. Try again shortly.', 502);
        }
        $this->audit('payment.checkout_started', $membership, $transactionId);
        $this->ok(array('transaction_id' => $transactionId, 'gateway' => $gateway, 'reference' => $reference, 'checkout_url' => $checkoutUrl));
    }

    public function verify($transactionId)
    {
        $membership = $this->requireAuth();
        $this->blockIfDemoReadonly($membership['branch_id']);
        $transaction = $this->db->where(array('id' => (int)$transactionId, 'branch_id' => $membership['branch_id'], 'membership_id' => $membership['id']))->get('payment_transactions')->row_array();
        if (!$transaction) $this->fail('transaction_not_found', 'Payment transaction not found.', 404);

        if ($transaction['status'] === 'success') {
            $this->ok(array('status' => 'success', 'summary' => $this->summaryForTransaction($membership, $transaction)));
            return;
        }
        if (in_array($transaction['status'], array('failed', 'cancelled', 'refunded'), true)) {
            $this->ok(array('status' => $transaction['status'], 'message' => $transaction['failure_message']));
            return;
        }

        $config = $this->db->where('branch_id', $membership['branch_id'])->get('payment_config')->row_array();
        list($verified, $terminal, $failureMessage) = $this->verifyWithGateway($transaction, $config);

        if (!$verified) {
            // Only persist a permanent 'failed' for outcomes the gateway itself
            // will never move off of (e.g. Paystack's "failed"/"reversed"). A
            // still-in-progress reference ("pending"/"processing"/"queued"/
            // "abandoned" before its own timeout) must stay re-checkable - the
            // user may tap "I've paid" again before finishing, or after a slow
            // bank confirmation, and a premature 'failed' would wrongly block
            // that same reference from ever being verified as paid.
            if ($terminal) {
                $this->db->where('id', $transaction['id'])->update('payment_transactions', array('status' => 'failed', 'failure_message' => $failureMessage, 'failed_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')));
                $this->ok(array('status' => 'failed', 'message' => $failureMessage));
            } else {
                $this->ok(array('status' => 'pending', 'message' => $failureMessage));
            }
            return;
        }

        // Guarded by the current status in the WHERE clause: only one concurrent
        // request can win this update, so the fee_payment_history insert below can
        // never run twice for the same transaction even under a replayed verify call.
        $this->db->where(array('id' => $transaction['id'], 'status' => $transaction['status']))->update('payment_transactions', array('status' => 'success', 'paid_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')));
        if ($this->db->affected_rows() === 1) {
            $this->recordFeePayment($membership, $transaction);
            $this->audit('payment.success', $membership, $transaction['id']);
        }
        $this->ok(array('status' => 'success', 'summary' => $this->summaryForTransaction($membership, $transaction)));
    }

    /** Public landing page Paystack redirects the user's browser to after checkout. No auth: the app never trusts this, it only tells the user to return and re-verifies independently via verify(). */
    public function checkout_complete()
    {
        $this->output->set_content_type('text/html')->set_output(
            '<html><body style="font-family:sans-serif;text-align:center;padding:48px;">'
            . '<h2>Payment received</h2><p>You can close this window and return to the SchoolEdge app.</p></body></html>'
        );
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function buildSummary(array $membership, array $enrollment)
    {
        $this->bridgeLegacySession($membership); // getInvoiceStatus/getInvoiceDetails/feeFineCalculation need get_session_id()
        $enrollId = $enrollment['id'];
        $invoiceStatus = $this->fees_model->getInvoiceStatus($enrollId);
        $details = $this->fees_model->getInvoiceDetails($enrollId);

        $items = array();
        $seen = array();
        $totalDue = 0.0;
        $totalPaid = 0.0;
        $totalDiscount = 0.0;
        $totalAccruingFine = 0.0;
        foreach ($details as $row) {
            if (empty($row['allocation_id']) || empty($row['fee_type_id'])) continue;
            $key = $row['allocation_id'] . '-' . $row['fee_type_id'];
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            $paidInfo = $this->fees_model->getStudentFeeDeposit($row['allocation_id'], $row['fee_type_id']);
            $balanceInfo = $this->fees_model->getBalance($row['allocation_id'], $row['fee_type_id']);
            $accruingFine = (float)$this->fees_model->feeFineCalculation($row['allocation_id'], $row['fee_type_id']);
            $amount = (float)$row['amount'];

            $items[] = array(
                'allocation_id' => (int)$row['allocation_id'],
                'type_id' => (int)$row['fee_type_id'],
                'name' => $row['name'],
                'amount' => round($amount, 2),
                'due_date' => $row['due_date'],
                'paid' => round((float)$paidInfo['total_amount'], 2),
                'discount' => round((float)$paidInfo['total_discount'], 2),
                'fine_paid' => round((float)$paidInfo['total_fine'], 2),
                'accruing_fine' => round($accruingFine, 2),
                'balance' => round((float)$balanceInfo['balance'], 2),
            );
            $totalDue += $amount;
            $totalPaid += (float)$paidInfo['total_amount'];
            $totalDiscount += (float)$paidInfo['total_discount'];
            $totalAccruingFine += $accruingFine;
        }

        $transport = $this->transportSummary($enrollId);
        if ($transport) {
            $totalDue += $transport['amount'];
            $totalPaid += $transport['paid'];
            $totalDiscount += $transport['discount'];
        }

        $branch = $this->db->select('currency,symbol,symbol_position')->where('id', $membership['branch_id'])->get('branch')->row_array();

        return array(
            'student' => array('id' => (int)$enrollment['student_id'], 'name' => $enrollment['student_name'], 'enroll_id' => (int)$enrollId),
            'invoice_no' => $invoiceStatus['invoice_no'],
            'status' => $invoiceStatus['status'],
            'currency' => array('code' => $branch['currency'] ?? 'NGN', 'symbol' => $branch['symbol'] ?? '₦', 'symbol_position' => (int)($branch['symbol_position'] ?? 1)),
            'total_due' => round($totalDue, 2),
            'total_paid' => round($totalPaid, 2),
            'total_discount' => round($totalDiscount, 2),
            'accruing_fine' => round($totalAccruingFine, 2),
            'balance' => round($totalDue - $totalPaid - $totalDiscount + $totalAccruingFine, 2),
            'items' => $items,
            'transport' => $transport,
        );
    }

    /**
     * verify() has no reliable student_id to resolve ownership from (a parent
     * membership needs one, but the client never resends it here) - so instead of
     * requiring it, derive the enrollment straight from the transaction's own
     * resource_id. Ownership was already checked once, when checkout() created
     * this transaction against that same membership_id.
     */
    private function summaryForTransaction(array $membership, array $transaction)
    {
        $enrollId = $transaction['resource_type'] === 'transport_fee_details'
            ? $this->db->select('enroll_id')->where('id', (int)$transaction['resource_id'])->get('transport_fee_details')->row()->enroll_id
            : $this->db->select('student_id')->where('id', (int)explode(':', $transaction['resource_id'])[0])->get('fee_allocation')->row()->student_id;

        $enrollment = $this->db->select('enroll.*,CONCAT_WS(" ",student.first_name,student.last_name) as student_name')
            ->from('enroll')->join('student', 'student.id = enroll.student_id', 'inner')
            ->where('enroll.id', $enrollId)->get()->row_array();
        return $this->buildSummary($membership, $enrollment);
    }

    private function transportSummary($enrollId)
    {
        $row = $this->db->select('transport_fee_details.id,transport_stoppage_point.route_fare')
            ->from('transport_fee_details')
            ->join('transport_stoppage_point', 'transport_stoppage_point.id = transport_fee_details.stoppage_point_id', 'inner')
            ->where('transport_fee_details.enroll_id', $enrollId)
            ->get()->row_array();
        if (!$row) return null;
        $balance = $this->transportBalance($row['id']);
        return array(
            'transport_fee_details_id' => (int)$row['id'],
            'amount' => round((float)$row['route_fare'], 2),
            'paid' => $balance['paid'],
            'discount' => $balance['discount'],
            'balance' => $balance['balance'],
        );
    }

    private function transportBalance($transportFeeDetailsId)
    {
        $row = $this->db->select('transport_stoppage_point.route_fare')
            ->from('transport_fee_details')
            ->join('transport_stoppage_point', 'transport_stoppage_point.id = transport_fee_details.stoppage_point_id', 'inner')
            ->where('transport_fee_details.id', $transportFeeDetailsId)
            ->get()->row_array();
        $paid = $this->db->select('IFNULL(SUM(amount),0) as amount,IFNULL(SUM(discount),0) as discount')
            ->from('fee_payment_history')->where('transport_fee_details_id', $transportFeeDetailsId)->get()->row_array();
        $amount = (float)($row['route_fare'] ?? 0);
        return array(
            'paid' => round((float)$paid['amount'], 2),
            'discount' => round((float)$paid['discount'], 2),
            'balance' => round($amount - (float)$paid['amount'] - (float)$paid['discount'], 2),
        );
    }

    private function allocationIdsFor($enrollId)
    {
        $rows = $this->db->select('id')->where('student_id', $enrollId)->get('fee_allocation')->result_array();
        return array_map(function ($r) { return (int)$r['id']; }, $rows);
    }

    private function enabledGateways($branchId)
    {
        $config = $this->db->where('branch_id', $branchId)->get('payment_config')->row_array();
        if (!$config) return array();
        $labels = array('paystack' => 'Paystack');
        $out = array();
        foreach ($this->supportedGateways as $code) {
            if (!empty($config[$code . '_status'])) $out[] = array('code' => $code, 'name' => $labels[$code] ?? ucfirst($code));
        }
        return $out;
    }

    private function branchCurrency($branchId)
    {
        $row = $this->db->select('currency')->where('id', $branchId)->get('branch')->row();
        return ($row && $row->currency) ? $row->currency : 'NGN';
    }

    private function initGateway($gateway, array $config, $reference, $amount, array $membership)
    {
        if ($gateway === 'paystack') {
            if (empty($config['paystack_secret_key'])) return null;
            $user = $this->authentication_model->getUserNameByRoleID($membership['role_id'], $membership['user_id']);
            $payload = array(
                'email' => !empty($user['email']) ? $user['email'] : 'no-reply@schooledge.ng',
                'amount' => (int)round($amount * 100),
                'reference' => $reference,
                'callback_url' => site_url('api/v1/mobile/fees/checkout/complete'),
            );
            $ch = curl_init('https://api.paystack.co/transaction/initialize');
            curl_setopt_array($ch, array(
                CURLOPT_POST => 1, CURLOPT_POSTFIELDS => json_encode($payload), CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $config['paystack_secret_key'], 'Content-Type: application/json'),
                CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_TIMEOUT => 20,
            ));
            $response = curl_exec($ch);
            curl_close($ch);
            $result = $response ? json_decode($response, true) : null;
            return $result['data']['authorization_url'] ?? null;
        }
        return null;
    }

    /** @return array{0: bool, 1: bool, 2: ?string} [verified, terminal, message] */
    private function verifyWithGateway(array $transaction, $config)
    {
        if ($transaction['gateway'] === 'paystack') {
            if (empty($config['paystack_secret_key'])) return array(false, true, 'Gateway not configured');
            $ch = curl_init('https://api.paystack.co/transaction/verify/' . rawurlencode($transaction['gateway_reference']));
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $config['paystack_secret_key']),
                CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_TIMEOUT => 20,
            ));
            $response = curl_exec($ch);
            curl_close($ch);
            $result = $response ? json_decode($response, true) : null;
            $data = $result['data'] ?? null;
            if ($data && $data['status'] === 'success'
                && hash_equals((string)$transaction['gateway_reference'], (string)$data['reference'])
                && (int)round(((float)$transaction['amount']) * 100) === (int)$data['amount']
            ) {
                return array(true, true, null);
            }
            // Paystack statuses that will never turn into "success" for this
            // reference; everything else (pending/processing/queued/ongoing,
            // "abandoned" before its own timeout, or no response at all) is
            // still in flight and must stay re-checkable.
            $terminal = $data && in_array($data['status'] ?? '', array('failed', 'reversed'), true);
            return array(false, $terminal, $data['gateway_response'] ?? 'The transaction has not been completed yet.');
        }
        return array(false, true, 'Unsupported gateway');
    }

    private function recordFeePayment(array $membership, array $transaction)
    {
        $data = array(
            'collect_by' => 'mobile-app', 'amount' => $transaction['amount'], 'discount' => 0, 'fine' => 0,
            'pay_via' => $this->payViaIdFor($transaction['gateway']),
            'remarks' => 'Fees deposit via mobile app - ' . ucfirst($transaction['gateway']) . ' Ref: ' . $transaction['gateway_reference'],
            'date' => date('Y-m-d'),
        );
        if ($transaction['resource_type'] === 'transport_fee_details') {
            $data['transport_fee_details_id'] = (int)$transaction['resource_id'];
        } else {
            list($allocationId, $typeId) = explode(':', $transaction['resource_id']);
            $data['allocation_id'] = (int)$allocationId;
            $data['type_id'] = (int)$typeId;
        }
        $this->db->insert('fee_payment_history', $data);

        $links = $this->db->where('branch_id', $membership['branch_id'])->get('transactions_links')->row_array();
        if ($links && !empty($links['status']) && !empty($links['deposit'])) {
            $this->bridgeLegacySession($membership);
            $this->fees_model->saveTransaction(array('account_id' => $links['deposit'], 'amount' => $transaction['amount'], 'date' => date('Y-m-d')));
        }
    }

    private function payViaIdFor($gateway)
    {
        $labels = array('paystack' => 'Paystack');
        $row = $this->db->where('name', $labels[$gateway] ?? ucfirst($gateway))->get('payment_types')->row_array();
        return $row ? (int)$row['id'] : null;
    }

    private function streamPdf($html, $filename)
    {
        $this->load->library('html2pdf');
        foreach (array(FCPATH . 'assets/vendor/bootstrap/css/bootstrap.min.css', FCPATH . 'assets/css/custom-style.css') as $stylesheet) {
            if (is_file($stylesheet)) $this->html2pdf->mpdf->WriteHTML(file_get_contents($stylesheet), 1);
        }
        $this->html2pdf->mpdf->WriteHTML($html);
        $this->html2pdf->mpdf->SetDisplayMode('fullpage');
        $pdf = $this->html2pdf->mpdf->Output('', 'S');
        $this->output->set_content_type('application/pdf')
            ->set_header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_.-]+/', '-', $filename) . '"')
            ->set_header('Cache-Control: private, no-store')
            ->set_output($pdf);
        $this->output->_display();
        exit;
    }

    private function audit($action, array $membership, $resourceId = null)
    {
        $this->db->insert('mobile_audit_log', array(
            'membership_id' => $membership['id'], 'branch_id' => $membership['branch_id'], 'action' => $action,
            'resource_id' => $resourceId, 'ip_address' => $this->input->ip_address(),
            'user_agent' => substr((string)$this->input->user_agent(), 0, 255), 'created_at' => date('Y-m-d H:i:s'),
        ));
    }
}
