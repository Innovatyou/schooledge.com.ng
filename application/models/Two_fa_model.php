<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Two_fa_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('cookie');
        $this->load->helper('string');
        $this->load->model('email_model');
        $this->load->library('u_2f_authenticator');
    }

    public function manage_2fa($sessionData = [], $login_credential, $email = '')
    {
        $get2FA_config = get2FA_config($sessionData['loggedin_branch']);
        if (!empty($get2FA_config) && $get2FA_config->status == 1 && $login_credential->two_factor_authentication == 1) {
            $token = get_cookie(U2FA . md5($sessionData['loggedin_id']));
            if (!empty($token) && $get2FA_config->show_remember == 1) {
                if ($this->verify_user_remember_2fa($token, $sessionData['loggedin_id'])) {
                    return false;
                }
            }
            // email the authentication code
            if ($login_credential->two_fa_type == 'email') {
                $code = random_string('numeric', 6);
                if (empty($email)) {
                    return false;
                }
                $arrayEmail = array(
                    'branch_id' => $sessionData['loggedin_branch'],
                    'name' => $sessionData['name'],
                    'email' => $email,
                    'verification_code' => $code,
                );
                if (!$this->u_2FA_Email($arrayEmail)) {
                    return false;
                } else {
                    $this->add_email_token($sessionData['loggedin_id'], $code);
                }
            }
            return true;
        }
        return false;
    }

    public function verify_authentication_code($authentication_code, $loggedin_id)
    {
        $query = $this->getAuthenticationType($loggedin_id);
        if ($query->num_rows() > 0) {

            //function to check if backup code is valid
            $verifyBackupCode = $this->verifyBackupCode($loggedin_id, $authentication_code);
            if ($verifyBackupCode) {
                return true;
            }

            $row = $query->row();
            if ($row->two_fa_type == 'app') {
                $secret = $row->two_fa_code;
                $checkResult = $this->u_2f_authenticator->verifyCode($secret, $authentication_code, 1);
                if ($checkResult) {
                    return true;
                }
            } elseif ($row->two_fa_type == 'email') {
                if ($row->two_fa_code == $authentication_code) {
                    // rest email authentication code
                    $this->add_email_token($loggedin_id, "");
                    return true;
                }
            }
        }
        return false;
    }

    public function getAuthenticationType($loggedin_id)
    {
        $query = $this->db->select('id,two_factor_authentication,two_fa_type,two_fa_code')->where(['id' => $loggedin_id])->get('login_credential');
        return $query;
    }

    public function verify_user_remember_2fa($token, $loggedin_id)
    {
        $query = $this->db->select('count(id) as token')->where(['token' => $token, 'loggedin_id' => $loggedin_id])->get('two_fa_remember');
        if ($query->row()->token == 0) {
            return false;
        } else {
            return true;
        }
    }

    public function remember_user_2fa($token, $loggedin_id)
    {
        $ip_address = $this->input->ip_address();
        $arrayData = array(
            'token' => $token,
            'loggedin_id' => $loggedin_id,
            'browser' => $this->getBrowser(),
            'platform' => $this->agent->platform(),
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => ($ip_address == "::1" ? "127.0.0.1" : $ip_address),
        );
        $this->db->insert('two_fa_remember', $arrayData);
    }

    public function add_email_token($loggedin_id, $token)
    {
        $this->db->where('id', $loggedin_id);
        $this->db->update('login_credential', ['two_fa_code' => $token]);
    }

    public function u_2FA_Email($data = [])
    {
        $emailTemplate = $this->email_model->getEmailTemplates(600, $data['branch_id']);
        if ($emailTemplate['notified'] == 1) {
            if (!empty($data['email'])) {
                $message = $emailTemplate['template_body'];
                $message = str_replace("{institute_name}", get_type_name_by_id('branch', $data['branch_id']), $message);
                $message = str_replace("{name}", $data['name'], $message);
                $message = str_replace("{verification_code}", $data['verification_code'], $message);
                $msgData['recipient'] = $data['email'];
                $msgData['subject'] = $emailTemplate['subject'];
                $msgData['message'] = $message;
                $msgData['branch_id'] = $data['branch_id'];
                return $this->email_model->sendEmail($msgData);
            }
        }
        return false;
    }

    public function twoStepEnable_Email($data = [])
    {
        $emailTemplate = $this->email_model->getEmailTemplates(601, $data['branch_id']);
        if ($emailTemplate['notified'] == 1) {
            if (!empty($data['email'])) {
                $name = $this->session->userdata('name');
                $ip_address = $this->input->ip_address();
                $message = $emailTemplate['template_body'];
                $message = str_replace("{institute_name}", get_type_name_by_id('branch', $data['branch_id']), $message);
                $message = str_replace("{name}", $name, $message);
                $message = str_replace("{device_IP}", ($ip_address == "::1" ? "127.0.0.1" : $ip_address), $message);
                $message = str_replace("{browser}", $this->getBrowser(), $message);
                $message = str_replace("{app_code}", $data['app_code'], $message);
                $message = str_replace("{time}", _d(date('Y-m-d H:i:s')), $message);
                $msgData['recipient'] = $data['email'];
                $msgData['subject'] = $emailTemplate['subject'];
                $msgData['message'] = $message;
                $msgData['branch_id'] = $data['branch_id'];
                return $this->email_model->sendEmail($msgData);
            }
        }
        return false;
    }

    public function twoStepDisable_Email($data = [])
    {
        $emailTemplate = $this->email_model->getEmailTemplates(602, $data['branch_id']);
        if ($emailTemplate['notified'] == 1) {
            if (!empty($data['email'])) {
                $name = $this->session->userdata('name');
                $ip_address = $this->input->ip_address();
                $message = $emailTemplate['template_body'];
                $message = str_replace("{institute_name}", get_type_name_by_id('branch', $data['branch_id']), $message);
                $message = str_replace("{name}", $name, $message);
                $message = str_replace("{device_IP}", ($ip_address == "::1" ? "127.0.0.1" : $ip_address), $message);
                $message = str_replace("{browser}", $this->getBrowser(), $message);
                $message = str_replace("{time}", _d(date('Y-m-d H:i:s')), $message);
                $msgData['recipient'] = $data['email'];
                $msgData['subject'] = $emailTemplate['subject'];
                $msgData['message'] = $message;
                $msgData['branch_id'] = $data['branch_id'];
                return $this->email_model->sendEmail($msgData);
            }
        }
        return false;
    }

    public function getBrowser()
    {
        if ($this->agent->is_browser()) {
            $browser = $this->agent->browser() . ' ' . $this->agent->version();
        } elseif ($this->agent->is_robot()) {
            $browser = $this->agent->robot();
        } elseif ($this->agent->is_mobile()) {
            $browser = $this->agent->mobile();
        } else {
            $browser = 'Unknown';
        }
        return $browser;
    }

    public function verifyBackupCode($loggedin_id, $code)
    {
        $query = $this->db->query("SELECT `id` FROM `two_fa_backup_codes` WHERE `loggedin_id` = " . $this->db->escape($loggedin_id) . " AND `code` = " . $this->db->escape($code) . " AND `status` = 1 LIMIT 1");
        if ($query->num_rows() > 0) {
            $this->db->where('code', $code);
            $this->db->update('two_fa_backup_codes', ['status' => 0]);
            return true;
        }
        return false;
    }

    public function verifyingExistingBackupCode($code)
    {
        $this->db->select("id");
        $this->db->from('two_fa_backup_codes');
        $this->db->where("code", $code);
        $query = $this->db->get();
        $result = $query->row_array();
        if (!empty($result)) {
            return 1;
        } else {
            return 0;
        }
    }

    public function checkBackupCode()
    {
        $query = $this->db->query("SELECT `created_at`,IFNULL(SUM(`status`),0) as `status_count` FROM `two_fa_backup_codes` WHERE `loggedin_id` = " . $this->db->escape(get_loggedin_id()))->row();
        return $query;
    }
}