<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Ramom Two Factor Authentication
 * @version : 1.0
 * @developed by : RamomCoder
 * @support : ramomcoder@yahoo.com
 * @author url : http://codecanyon.net/user/RamomCoder
 * @filename : Two_fa_verification.php
 * @copyright : Reserved RamomCoder Team
 */

class Two_fa_verification extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('two_fa_model');
        $this->load->model('authentication_model');

        if (!moduleIsEnabled('two_fa')) {
            access_denied();
        }
    }

    public function index($url_alias = '')
    {
        if (is_loggedin()) {
            redirect(base_url('dashboard'), 'refresh');
        }

        $userData = $this->session->userdata('2FA');
        if (empty($userData)) {
            redirect(base_url('authentication'));
        }
        $get2FA_config = get2FA_config($userData['loggedin_branch']);
        if ($_POST) {
            if (empty($userData)) {
                $array = array('status' => 'success', 'url' => base_url('authentication'));
                echo json_encode($array);
                exit;
            }
            $config = array(
                array(
                    'field' => 'authentication_code',
                    'label' => translate('authentication_code'),
                    'rules' => 'trim|required',
                ),
            );
            $this->form_validation->set_rules($config);
            if ($this->form_validation->run()) {
                $token = app_generate_hash();
                $authentication_code = $this->input->post('authentication_code');

                if ($this->two_fa_model->verify_authentication_code($authentication_code, $userData['loggedin_id'])) {
                    $cb_remember = isset($_POST['remember']) ? true : false;
                    if ($cb_remember == true && $get2FA_config->show_remember) {
                        $timestamp = strtotime($get2FA_config->cookie_expiry);
                        set_cookie(U2FA . md5($userData['loggedin_id']), $token, $timestamp);
                        $this->two_fa_model->remember_user_2fa($token, $userData['loggedin_id']);
                    } else {
                        delete_cookie(U2FA . md5($userData['loggedin_id']));
                    }
                    $this->authentication_model->sessionSet($userData);
                    $this->session->unset_userdata('2FA');
                    $array = array('status' => 'success', 'url' => base_url('dashboard'));
                } else {
                    $error = (object) array('authentication_code' => translate('invalid_verification_code'));
                    $array = array('status' => 'fail', 'error' => $error);
                }
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'error' => $error);
            }
            echo json_encode($array);
            exit;
        }
        $this->data['two_fa_type'] = $this->two_fa_model->getAuthenticationType($userData['loggedin_id'])->row()->two_fa_type;
        $this->data['branch_id'] = $this->authentication_model->urlaliasToBranch($url_alias);
        $this->data['get2FA_config'] = $get2FA_config;
        $schoolDeatls = $this->authentication_model->getSchoolDeatls($url_alias);
        if (!empty($schoolDeatls) && is_object($schoolDeatls)) {
            $this->data['global_config']['institute_name'] = $schoolDeatls->school_name;
            $this->data['global_config']['address'] = $schoolDeatls->address;
            $this->data['global_config']['facebook_url'] = $schoolDeatls->facebook_url;
            $this->data['global_config']['twitter_url'] = $schoolDeatls->twitter_url;
            $this->data['global_config']['linkedin_url'] = $schoolDeatls->linkedin_url;
            $this->data['global_config']['youtube_url'] = $schoolDeatls->youtube_url;
        }
        $this->load->view('two_fa_verification/index', $this->data);
    }
}
