<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Ramom school management system
 * @version : 5.0
 * @developed by : RamomCoder
 * @support : ramomcoder@yahoo.com
 * @author url : http://codecanyon.net/user/RamomCoder
 * @filename : Cron_api.php
 * @copyright : Reserved RamomCoder Team
 */

class Cron_api extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('fees_model');
        $this->load->model('sms_model');
        $this->load->model('sendsmsmail_model');
        $this->api_key = $this->data['global_config']['cron_secret_key'];
    }

    public function index()
    {
        if (!is_loggedin() || !get_permission('cron_job', 'is_view')) {
            access_denied();
        }

        if ($_POST) {
            if (!get_permission('cron_job', 'is_edit')) {
                access_denied();
            }
            $this->db->where('id', 1);
            $this->db->update('global_settings', array('cron_secret_key' => generate_encryption_key()));
            set_alert('success', "Successfully Created The New Secret Key.");
            redirect(current_url());
        }

        $this->data['title'] = translate('cron_job');
        $this->data['sub_page'] = 'cron_api/index';
        $this->data['main_menu'] = 'settings';
        $this->load->view('layout/index', $this->data);
    }

    public function send_smsemail_command($api_key = '')
    {
        if ($api_key != "" && $this->api_key != $api_key) {
            echo "API Key is required or API Key does not match.";
            exit();
        }

        $sql = "SELECT * FROM bulk_sms_email WHERE posting_status = 1 AND schedule_time < NOW() ORDER BY schedule_time ASC";
        $bulkArray = $this->db->query($sql)->result_array();
        foreach ($bulkArray as $key => $row) {
            $this->db->where('id', $row['id']);
            $this->db->update('bulk_sms_email', array('posting_status' => 0));
            $sCount = 0;
            $usersList = json_decode($row['additional'], true);
            foreach ($usersList as $key => $user) {
                if ($row['message_type'] == 1) {
                    $response = $this->sendsmsmail_model->sendSMS($user['mobileno'], $row['message'], $user['name'], $user['email'], $row['sms_gateway']);
                } else {
                    $response = $this->sendsmsmail_model->sendEmail($user['email'], $row['message'], $user['name'], $user['mobileno'], $row['email_subject']);
                }
                if ($response == true) {
                    $sCount++;
                }
            }
            $this->db->where('id', $row['id']);
            $this->db->update('bulk_sms_email', array('additional' => "", 'successfully_sent' => $sCount, 'posting_status' => 2));
        }
    }

    public function homework_command($api_key = '')
    {
        if ($api_key != "" && $this->api_key != $api_key) {
            echo "API Key is required or API Key does not match.";
            exit();
        }
        $sql = "SELECT * FROM homework WHERE status = 1 AND date(schedule_date) = CURDATE() ORDER BY schedule_date ASC";
        $homeworkArray = $this->db->query($sql)->result_array();
        foreach ($homeworkArray as $key => $row) {
            $this->db->where('id', $row['id']);
            $this->db->update('homework', array('status' => 0));
            //send homework sms notification
            if ($row['sms_notification'] == 1) {
                $stuList = $this->application_model->getStudentListByClassSection($row['class_id'], $row['section_id'], $row['branch_id']);
                foreach ($stuList as $stuRow) {
                    $stuRow['date_of_homework'] = $row['date_of_homework'];
                    $stuRow['date_of_submission'] = $row['date_of_submission'];
                    $stuRow['subject_id'] = $row['subject_id'];
                    $this->sms_model->sendHomework($stuRow);
                }
            }
        }
    }

    public function fees_reminder_command($api_key = '')
    {
        if ($api_key != "" && $this->api_key != $api_key) {
            echo "API Key is required or API Key does not match.";
            exit();
        }
        $feesArray = $this->db->get('fees_reminder')->result_array();
        foreach ($feesArray as $key => $row) {
            $studentList = array();
            $days = $row['days'];
            if ($row['frequency'] == 'before') {
                $date = date('Y-m-d', strtotime("+ $days days"));
            } elseif ($row['frequency'] == 'after') {
                $date = date('Y-m-d', strtotime("- $days days"));
            }
            $getFeeTypes = $this->fees_model->getFeeReminderByDate($date, $row['branch_id']);
            foreach ($getFeeTypes as $type_key => $type_value) {
                $getStuDetails = $this->fees_model->getStudentsListReminder($type_value['fee_groups_id'], $type_value['fee_type_id']);
                foreach ($getStuDetails as $stu_key => $stu_value) {
                    $stu_value['due_date'] = _d($type_value['due_date']);
                    $stu_value['type_name'] = $type_value['name'];
                    $stu_value['total_amount'] = (float) $type_value['amount'];
                    $stu_value['balance_amount'] = (float) ($type_value['amount'] - ($stu_value['payment']['total_paid'] + $stu_value['payment']['total_discount']));
                    unset($stu_value['payment']);
                    if ($stu_value['balance_amount'] > 0) {
                        $studentList[] = $stu_value;
                    }
                }
            }
            foreach ($studentList as $stuRow) {
                $this->sms_model->feeReminder($stuRow, $row);
            }
        }
    }

    /**
     * Resolves Veltrix wallet top-ups (school_wallet_transaction, channel
     * 'topup') left 'pending' by Veltrixwallet::paystack(). Necessary
     * because this SchoolEdge Paystack account's webhook URL is already
     * pointed at a different platform sharing the account, so
     * Veltrixwebhook::paystack() never fires here -- if the customer's
     * browser never makes it back to verify_paystack_payment() (closed tab,
     * dropped connection, ...), this cron is the only thing that will ever
     * notice Paystack actually processed the charge.
     */
    public function veltrix_reconcile_command($api_key = '')
    {
        if ($api_key != "" && $this->api_key != $api_key) {
            echo "API Key is required or API Key does not match.";
            exit();
        }
        $this->load->model('veltrix_wallet_model');

        $secret = (string) ($this->db->where('branch_id', 9999)->get('payment_config')->row_array()['paystack_secret_key'] ?? '');
        if ($secret === '') {
            echo "Paystack is not configured.";
            exit();
        }

        // 10-minute grace period so this never races a customer who is
        // still mid-checkout in their browser.
        $pending = $this->db->where('channel', 'topup')
            ->where('status', 'pending')
            ->where('created_at <', date('Y-m-d H:i:s', strtotime('-10 minutes')))
            ->get('school_wallet_transaction')->result_array();

        foreach ($pending as $row) {
            $ch = curl_init('https://api.paystack.co/transaction/verify/' . rawurlencode($row['reference']));
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_HTTPHEADER     => array('Authorization: Bearer ' . $secret),
            ));
            $request = curl_exec($ch);
            curl_close($ch);
            $result = $request ? json_decode($request, true) : null;

            $status       = $result['data']['status'] ?? '';
            $paidKobo     = (int) ($result['data']['amount'] ?? -1);
            $expectedKobo = (int) round($row['amount'] * 100);

            if ($status === 'success' && $paidKobo === $expectedKobo) {
                $this->veltrix_wallet_model->completePending($row['branch_id'], (float) $row['amount'], $row['reference'], $row['description']);
                echo "Completed {$row['reference']}\n";
            } elseif (in_array($status, array('failed', 'abandoned'), true) || strtotime($row['created_at']) < strtotime('-24 hours')) {
                // Genuinely failed on Paystack's side, or old enough that
                // there's no realistic chance it's still coming through.
                $this->veltrix_wallet_model->markFailed($row['branch_id'], $row['reference']);
                echo "Failed {$row['reference']}\n";
            }
            // Anything else (still "pending"/"ongoing" on Paystack's side, or
            // a transient API error) is left alone for the next run.
        }
    }
}
