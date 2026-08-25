<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Ramom Two Factor Authentication
 * @version : 1.5
 * @developed by : RamomCoder
 * @support : ramomcoder@yahoo.com
 * @author url : http://codecanyon.net/user/RamomCoder
 * @filename : Two_factor_authentication.php
 * @copyright : Reserved RamomCoder Team
 */

class Two_factor_authentication extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('two_fa_model');
        $this->load->library('user_agent');
        $this->load->model('frontend_model');
        $this->load->library('ciqrcode');
        if (!moduleIsEnabled('two_fa')) {
            access_denied();
        }
    }

    public function settings()
    {
        // check access permission
        if (!get_permission('two_fa_settings', 'is_view')) {
            access_denied();
        }
        $branchID = $this->frontend_model->getBranchID();
        if ($_POST) {
            $branch_id = $this->input->post('branch_id');
            redirect(base_url('two_factor_authentication/settings?branch_id=' . $branch_id));
        }
        $this->data['branch_id'] = $branchID;
        $this->data['setting'] = $this->two_fa_model->get('two_factor_authentication', array('branch_id' => $branchID), true);
        $this->data['title'] = translate('two_factor_authentication');
        $this->data['sub_page'] = 'two_factor_authentication/settings';
        $this->data['main_menu'] = 'tfa';
        $this->load->view('layout/index', $this->data);
    }

    public function settings_save()
    {
        // check access permission
        if (!get_permission('two_fa_settings', 'is_add')) {
            ajax_access_denied();
        }

        if ($_POST) {
            $branchID = $this->frontend_model->getBranchID();
            $tfa_show_remember = $this->input->post('2fa_show_remember');
            $this->form_validation->set_rules('two_factor_authentication', translate('two_factor_authentication'), 'trim|required');
            $this->form_validation->set_rules('2fa_show_remember', translate('2fa_show_remember'), 'trim|required');
            if ($tfa_show_remember == 1) {
                $this->form_validation->set_rules('2fa_cookie_expiry', translate('2fa_cookie_expiry'), 'trim|required');
            }

            if ($this->form_validation->run() == true) {
                $authentication_setting = array(
                    'branch_id' => $branchID,
                    'status' => $this->input->post('two_factor_authentication'),
                    'show_remember' => $tfa_show_remember,
                    'email_instruction' => $this->input->post('email_instruction'),
                    'app_instruction' => $this->input->post('app_instruction'),
                );

                if ($tfa_show_remember == 1) {
                    $authentication_setting['cookie_expiry'] = $this->input->post('2fa_cookie_expiry');
                }

                // update all information in the database
                $this->db->where(array('branch_id' => $branchID));
                $get = $this->db->get('two_factor_authentication');
                if ($get->num_rows() > 0) {
                    $this->db->where('id', $get->row()->id);
                    $this->db->update('two_factor_authentication', $authentication_setting);
                } else {
                    $this->db->insert('two_factor_authentication', $authentication_setting);
                }

                set_alert('success', translate('information_has_been_saved_successfully'));
                $array = array('status' => 'success');
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
        }
    }

    public function setup_tfa()
    {
        $loggedinID = get_loggedin_id();
        $userName = $this->session->userdata('name');
        $get2FA_config = get2FA_config();
        if (!$get2FA_config->status) {
            access_denied();
        }
        $issuerName = $_SERVER['HTTP_HOST'];
        $secret = $this->u_2f_authenticator->createSecret();
        $getAuthenticationType = $this->two_fa_model->getAuthenticationType($loggedinID);
        $config['savename'] = 'uploads/u2fa_qrcode/' . U2FA . "-" . md5($loggedinID) . '.png';
        $config['level'] = 'M';
        $config['size'] = 5;
        $config['data'] = "otpauth://totp/{$userName}?secret={$secret}&issuer={$issuerName}";
        $this->data['tfa_config'] = $get2FA_config;
        $this->data['qrconfig'] = $config;
        $this->data['secret'] = $secret;
        $this->data['loggedin_id'] = $loggedinID;
        $this->data['getAuthentication'] = $getAuthenticationType->row();
        $this->data['title'] = translate('two_factor_authentication');
        $this->data['sub_page'] = 'two_factor_authentication/setup_tfa';
        $this->data['main_menu'] = 'tfa';
        $this->load->view('layout/index', $this->data);
    }

    public function twoStepAPPEnable()
    {
        if ($_POST) {
            $this->form_validation->set_rules('authenticator_code', translate('verification_code'), 'trim|required');
            if ($this->form_validation->run() == true) {
                $secret = $this->input->post('secret_key');
                $app_2fa_status = $this->input->post('app_2fa_status');
                $authenticator_code = $this->input->post('authenticator_code');
                $checkResult = $this->u_2f_authenticator->verifyCode($secret, $authenticator_code, 1);
                if (!$checkResult) {
                    $error = (object) array('authenticator_code' => translate('invalid_verification_code'));
                    echo json_encode(array('status' => 'fail', 'error' => $error));
                    exit;
                } else {
                    $loggedinID = get_loggedin_id();
                    $emailData = array(
                        'branch_id' => get_loggedin_branch_id(),
                        'email' => $this->session->userdata('loggedin_email'),
                        'app_code' => $secret
                    );
                    if ($app_2fa_status == 1) {
                        $data = ['two_factor_authentication' => 0, 'two_fa_code' => "", 'two_fa_type' => 'app'];
                        set_alert('success', "2FA Has Been Disabled.");
                        $this->two_fa_model->twoStepDisable_Email($emailData);
                    } else {
                        $data = ['two_factor_authentication' => 1, 'two_fa_code' => $secret, 'two_fa_type' => 'app'];
                        set_alert('success', "2FA Has Been Enabled.");
                        $this->two_fa_model->twoStepEnable_Email($emailData);
                    }
                    $this->db->where('id', $loggedinID);
                    $this->db->update('login_credential', $data);
                    $array = array('status' => 'success');
                }
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
        }
    }

    public function twoStepEmailEnable()
    {
        if ($_POST) {
            $this->form_validation->set_rules('verification_code', translate('verification_code'), 'trim|required');
            if ($this->form_validation->run() == true) {
                $email_2fa_status = $this->input->post('email_2fa_status');
                $verification_code = $this->input->post('verification_code');
                $loggedinID = get_loggedin_id();
                $query = $this->two_fa_model->getAuthenticationType($loggedinID);
                if ($query->row()->two_fa_code != $verification_code) {
                    $error = (object) array('verification_code' => translate('invalid_verification_code'));
                    echo json_encode(array('status' => 'fail', 'error' => $error));
                    exit;
                } else {
                    $emailData = array(
                        'branch_id' => get_loggedin_branch_id(),
                        'email' => $this->session->userdata('loggedin_email'),
                        'app_code' => ""
                    );
                    if ($email_2fa_status == 1) {
                        $data = ['two_factor_authentication' => 0, 'two_fa_code' => "", 'two_fa_type' => 'email'];
                        set_alert('success', "2FA Has Been Disabled.");
                        $this->two_fa_model->twoStepDisable_Email($emailData);
                    } else {
                        $data = ['two_factor_authentication' => 1, 'two_fa_code' => "", 'two_fa_type' => 'email'];
                        set_alert('success', "2FA Has Been Enabled.");
                        $this->two_fa_model->twoStepEnable_Email($emailData);
                    }
                    $this->db->where('id', $loggedinID);
                    $this->db->update('login_credential', $data);
                    $array = array('status' => 'success');
                }
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
        }
    }

    public function browser_savedlogin_ajax()
    {
        $loggedinID= get_loggedin_id();
        $this->db->where('loggedin_id', $loggedinID);
        $this->data['list'] = $this->db->get('two_fa_remember')->result();
        echo $this->load->view('two_factor_authentication/browser_saved_login', $this->data, true);
    }

    public function delete_browser($id=''){
        if (!empty($id)) {
            $loggedinID= get_loggedin_id();
            $this->db->where('loggedin_id', $loggedinID);
            $this->db->where('id', $id);
            $this->db->delete('two_fa_remember');
        }
    }

    public function ajax2FASend()
    {
        if ($_POST) {
            if (is_superadmin_loggedin()) {
                $array = array('status' => false, 'msg' => "Email authentication is disabled for SuperAdmin.");
            } else {
                $loggedinID = get_loggedin_id();
                $code = random_string('numeric', 6);
                $email = $this->session->userdata('loggedin_email');
                $arrayEmail = array(
                    'branch_id' => get_loggedin_branch_id(),
                    'name' => $this->session->userdata('name'),
                    'email' => $email,
                    'verification_code' => $code,
                );
                if (!$this->two_fa_model->u_2FA_Email($arrayEmail)) {
                    $array = array('status' => false, 'msg' => "Something wrong.");
                } else {
                    $this->two_fa_model->add_email_token($loggedinID, $code);
                    $array = array('status' => true, 'msg' => "");
                }
            }

            echo json_encode($array);
        }
    }

    public function download_backup_codes()
    {
        $loggedinID = get_loggedin_id();
        $userName = $this->session->userdata('name');
        $get2FA_config = get2FA_config();
        $issuerName = $_SERVER['HTTP_HOST'];
        if (!$get2FA_config->status) {
            ajax_access_denied();
        }
        $getAuthenticationType = $this->two_fa_model->getAuthenticationType($loggedinID)->row();
        if ($getAuthenticationType->two_factor_authentication == 1 && ($getAuthenticationType->two_fa_type == 'email' || $getAuthenticationType->two_fa_type == 'app')) {
            $this->load->helper('file');
            $this->load->helper('download');
            $backuptext = "# Save these code securely, Store a secure place that only you can access."  . PHP_EOL . 
            "# Don’t share these code, If someone gains access to them, they can potentially access your account." . PHP_EOL .
            "# You can only use each backup code once." . "\r\n\r\n";

            // function to generate a random backup code
            $numCodes = 10;
            $codeLength = 6;
            $backup_codes = [];
            for ($i = 0; $i < $numCodes; $i++) {
                
                do {
                    $code = bin2hex(random_bytes($codeLength));
                    $backup_code = strtoupper($code);
                    $backup_code_status = $this->two_fa_model->verifyingExistingBackupCode($backup_code);
                } while ($backup_code_status);

                $backup_codes[] = ['loggedin_id' => $loggedinID, 'code' => $backup_code];
                $backuptext .= str_pad(($i + 1), 2, "0", STR_PAD_LEFT) . ". " . $backup_code . PHP_EOL;
            }
            $backuptext .= "\r\n* These codes were generated on: " . date("F j, Y,  g:i A") . " for: " . $userName;
            $this->db->where('loggedin_id', $loggedinID);
            $this->db->delete('two_fa_backup_codes');

            $this->db->insert_batch('two_fa_backup_codes', $backup_codes);
            echo json_encode(['status' => 'success', 'title' => "2FA-backup-codes-$userName.txt", 'data' =>  $backuptext]);
        } else {
            ajax_access_denied();
        }
    }
}
