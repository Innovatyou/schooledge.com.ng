<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

function get2FA_config($branchID = '')
{
    $ci = &get_instance();
    if (empty($branchID)) {
        $branchID = $ci->session->userdata('loggedin_branch');
    }
    $branch_id = empty($branchID) ? 0 : $branchID;
    $query = $ci->db->where('branch_id', $branch_id)->get('two_factor_authentication');
    if ($query->num_rows() > 0) {
        return $query->row();
    } else {
        $arrayData = array(
            'show_remember' => 1,
            'cookie_expiry' => '+1 year',
            'email_instruction' => '',
            'app_instruction' => '',
            'status' => 1,
        );
        return (object)$arrayData;
    }
}

