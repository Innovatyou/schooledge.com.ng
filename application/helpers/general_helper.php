<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// return translation
function translate($word = '')
{
    $CI = &get_instance();
    if ($CI->session->has_userdata('set_lang')) {
        $set_lang = $CI->session->userdata('set_lang');
    } else {
        $set_lang = get_global_setting('translation');
    }

    if ($set_lang == '') {
        $set_lang = 'english';
    }

    $sql = "SELECT `english`,`" . $set_lang . "` FROM `languages` WHERE `word` = '$word'";
    $query = $CI->db->query($sql);
    if ($query->num_rows() > 0) {
        if (isset($query->row()->$set_lang) && $query->row()->$set_lang != '') {
            return $query->row()->$set_lang;
        } else {
            return $query->row()->english;
        }
    } else {
        $arrayData = array(
            'word' => $word,
            'english' => ucwords(str_replace('_', ' ', $word)),
        );
        $CI->db->insert('languages', $arrayData);
        return ucwords(str_replace('_', ' ', $word));
    }
}

function moduleIsEnabled($prefix)
{
    $ci = &get_instance();
    $role_id = $ci->session->userdata('loggedin_role_id');
    $branchID = $ci->session->userdata('loggedin_branch');
    if ($role_id == 1) {
        return 1;
    }
    $sql = "SELECT IF(`oaf`.`isEnabled` is NULL, 1, `oaf`.`isEnabled`) as `status` FROM `permission_modules` LEFT JOIN `modules_manage` as `oaf` ON `oaf`.`modules_id` = `permission_modules`.`id` AND `oaf`.`branch_id` = " . $ci->db->escape($branchID) . " WHERE `permission_modules`.`prefix` = " . $ci->db->escape($prefix);
    $result = $ci->db->query($sql)->row();
    if (empty($result)) {
        return 1;
    } else {
        return $result->status;
    }
}

function checkSaasLimit($prefix)
{
    $ci = &get_instance();
    $role_id = $ci->session->userdata('loggedin_role_id');
    $branchID = $ci->session->userdata('loggedin_branch');
    if ($role_id == 1) {
        return 1;
    }

    $ci = &get_instance();
    $sql = "SELECT `sb`.`expire_date`, `sb`.`school_id`, `student_limit`, `staff_limit`, `teacher_limit`, `parents_limit` FROM `branch` as `b` LEFT JOIN `saas_subscriptions` AS `sb` ON `sb`.`school_id` = `b`.`id` LEFT JOIN `saas_package` as `sp` ON `sp`.`id` = `sb`.`package_id` WHERE `sb`.`school_id` = " . $ci->db->escape($branchID);
    $row = $ci->db->query($sql)->row();
    if (empty($row)) {
        return 1;
    }

    if ($prefix == 'student') {
        $ci->db->where('branch_id', $branchID);
        $ci->db->group_by('student_id');
        $total_student = $ci->db->count_all_results('enroll');
        if ($total_student > $row->student_limit) {
            return 0;
        } else {
            return 1;
        }
    }

    if ($prefix == 'staff' || $prefix == 'teacher') {
        $ci->db->select('IFNULL(COUNT(staff.id), 0) as snumber');
        $ci->db->from('staff');
        $ci->db->join('login_credential', 'login_credential.user_id = staff.id', 'inner');
        if ($prefix == 'teacher') {
            $ci->db->where('login_credential.role', 3);
        } else {
            $ci->db->where_not_in('login_credential.role', array(1, 3, 6, 7));
        }
        $ci->db->where('staff.branch_id', $branchID);
        $total_staff = $ci->db->get()->row()->snumber;

        if ($prefix == 'teacher') {
            $limit = $row->teacher_limit;
        } else {
            $limit = $row->staff_limit;
        }
        if ($total_staff > $limit) {
            return 0;
        } else {
            return 1;
        }
    }

    if ($prefix == 'parent') {
        $ci->db->where('branch_id', $branchID);
        $total_parents = $ci->db->count_all_results('parent');
        if ($total_parents > $row->parents_limit) {
            return 0;
        } else {
            return 1;
        }
    }
}

function isEnabledSubscription($schoolID = '')
{
    $ci = &get_instance();
    $row = $ci->db->select('id')->where('school_id', $schoolID)->get('saas_subscriptions')->row();
    if (empty($row)) {
        return false;
    } else {
        return true;
    }
}

function is_demo_readonly()
{
    $ci = &get_instance();
    return $ci->session->userdata('is_demo_branch') == 1;
}

function get_permission($permission, $can = '')
{
    if ($can != '' && $can != 'is_view' && is_demo_readonly()) {
        return false;
    }
    $ci = &get_instance();
    $role_id = $ci->session->userdata('loggedin_role_id');
    if ($role_id == 1) {
        return true;
    }
    $permissions = get_staff_permissions($role_id);
    foreach ($permissions as $permObject) {
        if ($permObject->permission_prefix == $permission && $permObject->$can == '1') {
            return true;
        }
    }
    return false;
}

function get_staff_permissions($id)
{
    $ci = &get_instance();
    $sql = "SELECT `staff_privileges`.*, `permission`.`id` as `permission_id`, `permission`.`prefix` as `permission_prefix` FROM `staff_privileges` JOIN `permission` ON `permission`.`id`=`staff_privileges`.`permission_id` WHERE `staff_privileges`.`role_id` = " . $ci->db->escape($id);
    $result = $ci->db->query($sql)->result();
    return $result;
}

function get_session_id()
{
    $CI = &get_instance();
    if ($CI->session->has_userdata('set_session_id')) {
        $session_id = $CI->session->userdata('set_session_id');
    } else {
        $session_id = get_global_setting('session_id');
    }
    return $session_id;
}

function is_secure($url)
{
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) {
        $val = 'https://' . $url;
    } else {
        $val = 'http://' . $url;
    }
    return $val;
}

function get_global_setting($name = '')
{
    $CI = &get_instance();
    $name = trim($name);
    $CI->db->where('id', 1);
    $CI->db->select($name);
    $query = $CI->db->get('global_settings');

    if ($query->num_rows() > 0) {
        $row = $query->row();
        return $row->$name;
    }
}

// is superadmin logged in @return boolean
function is_superadmin_loggedin()
{
    $CI = &get_instance();
    if ($CI->session->userdata('loggedin_role_id') == 1) {
        return true;
    }
    return false;
}

// is admin logged in @return boolean
function is_admin_loggedin()
{
    $CI = &get_instance();
    if ($CI->session->userdata('loggedin_role_id') == 2) {
        return true;
    }
    return false;
}

// is teacher logged in @return boolean
function is_teacher_loggedin()
{
    $CI = &get_instance();
    if ($CI->session->userdata('loggedin_role_id') == 3) {
        return true;
    }
    return false;
}

// is accountant logged in @return boolean
function is_accountant_loggedin()
{
    $CI = &get_instance();
    if ($CI->session->userdata('loggedin_role_id') == 4) {
        return true;
    }
    return false;
}

// is librarian logged in @return boolean
function is_librarian_loggedin()
{
    $CI = &get_instance();
    if ($CI->session->userdata('loggedin_role_id') == 5) {
        return true;
    }
    return false;
}

// is parent logged in @return boolean
function is_parent_loggedin()
{
    $CI = &get_instance();
    if ($CI->session->userdata('loggedin_role_id') == 6) {
        return true;
    }
    return false;
}

// is parent logged in @return boolean
function is_student_loggedin()
{
    $CI = &get_instance();
    if ($CI->session->userdata('loggedin_role_id') == 7) {
        return true;
    }
    return false;
}

// get logged in user id - login credential DB id
function get_loggedin_id()
{
    $ci = &get_instance();
    return $ci->session->userdata('loggedin_id');
}

// get staff db id
function get_loggedin_user_id()
{
    $ci = &get_instance();
    return $ci->session->userdata('loggedin_userid');
}

// get session loggedin
function is_loggedin()
{
    $CI = &get_instance();
    if ($CI->session->has_userdata('loggedin')) {
        return true;
    }
    return false;
}

// get loggedin role name
function loggedin_role_name()
{
    $CI = &get_instance();
    $roleID = $CI->session->userdata('loggedin_role_id');
    return $CI->db->select('name')->where('id', $roleID)->get('roles')->row()->name;
}

function loggedin_role_id()
{
    $ci = &get_instance();
    return $ci->session->userdata('loggedin_role_id');
}

// get logged in user type
function get_loggedin_user_type()
{
    $CI = &get_instance();
    return $CI->session->userdata('loggedin_type');
}

// get logged in user type
function get_loggedin_branch_id()
{
    $CI = &get_instance();
    return $CI->session->userdata('loggedin_branch');
}

// get parent selected active children Id
function get_activeChildren_id()
{
    $CI = &get_instance();
    return $CI->session->userdata('myChildren_id');
}

// get table name by type and id
function get_type_name_by_id($table, $type_id = '', $field = 'name')
{
    $CI = &get_instance();
    $get = $CI->db->select($field)->from($table)->where('id', $type_id)->limit(1)->get()->row_array();
    return $get[$field];
}

// set session alert / flashdata
function set_alert($type, $message)
{
    $CI = &get_instance();
    $CI->session->set_flashdata('alert-message-' . $type, $message);
}

// generate md5 hash
function app_generate_hash()
{
    return md5(rand() . microtime() . time() . uniqid());
}

// generate encryption key
function generate_encryption_key()
{
    $CI = &get_instance();
    // In case accessed from my_functions_helper.php
    $CI->load->library('encryption');
    $key = bin2hex($CI->encryption->create_key(16));
    return $key;
}

// generate get image url
function get_image_url($role = '', $file_name = '')
{
    if ($file_name == 'defualt.png' || empty($file_name)) {
        $image_url = base_url('uploads/app_image/defualt.png');
    } else {
        if (file_exists('uploads/images/' . $role . '/' . $file_name)) {
            $image_url = base_url('uploads/images/' . $role . '/' . $file_name);
        } else {
            $image_url = base_url('uploads/app_image/defualt.png');
        }
    }
    return $image_url;
}

// get date format config
function _d($date)
{
    if ($date == '' || is_null($date) || $date == '0000-00-00') {
        return '';
    }
    $formats = 'Y-m-d';
    $get_format = get_global_setting('date_format');
    if ($get_format != '') {
        $formats = $get_format;
    }
    return date($formats, strtotime($date));
}

// delete url
function btn_delete($uri)
{
    return "<button type='button' class='btn btn-danger icon btn-circle' onclick=confirm_modal('" . base_url($uri) . "') ><i class='fas fa-trash-alt'></i></button>";
}

// delete url
function csrf_jquery_token()
{
    $csrf = [get_instance()->security->get_csrf_token_name() => get_instance()->security->get_csrf_hash()];
    return $csrf;
}

function check_hash_restrictions($table, $id, $hash)
{
    $CI = &get_instance();
    if (!$table || !$id || !$hash) {
        show_404();
    }

    $query = $CI->db->select('hash')->from($table)->where('id', $id)->get();
    if ($query->num_rows() > 0) {
        $get_hash = $query->row()->hash;
    } else {
        $get_hash = '';
    }
    if (empty($hash) || ($get_hash != $hash)) {
        show_404();
    }
}

function get_nicetime($date)
{
    $get_format = get_global_setting('date_format');
    if (empty($date)) {
        return "Unknown";
    }
    // Current time as MySQL DATETIME value
    $csqltime = date('Y-m-d H:i:s');
    // Current time as Unix timestamp
    $ptime = strtotime($date);
    $ctime = strtotime($csqltime);

    //Now calc the difference between the two
    $timeDiff = floor(abs($ctime - $ptime) / 60);

    //Now we need find out whether or not the time difference needs to be in
    //minutes, hours, or days
    if ($timeDiff < 2) {
        $timeDiff = "Just now";
    } elseif ($timeDiff > 2 && $timeDiff < 60) {
        $timeDiff = floor(abs($timeDiff)) . " minutes ago";
    } elseif ($timeDiff > 60 && $timeDiff < 120) {
        $timeDiff = floor(abs($timeDiff / 60)) . " hour ago";
    } elseif ($timeDiff < 1440) {
        $timeDiff = floor(abs($timeDiff / 60)) . " hours ago";
    } elseif ($timeDiff > 1440 && $timeDiff < 2880) {
        $timeDiff = floor(abs($timeDiff / 1440)) . " day ago";
    } elseif ($timeDiff > 2880) {
        $timeDiff = date($get_format, $ptime);
    }
    return $timeDiff;
}

function bytesToSize($path, $filesize = '')
{
    if (!is_numeric($filesize)) {
        $bytes = sprintf('%u', filesize($path));
    } else {
        $bytes = $filesize;
    }
    if ($bytes > 0) {
        $unit = intval(log($bytes, 1024));
        $units = [
            'B',
            'KB',
            'MB',
            'GB',
        ];
        if (array_key_exists($unit, $units) === true) {
            return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
        }
    }
    return $bytes;
}

function array_to_object($array)
{
    if (!is_array($array) && !is_object($array)) {
        return new stdClass();
    }
    return json_decode(json_encode((object) $array));
}

function access_denied()
{
    set_alert('error', translate('access_denied'));
    redirect(site_url('dashboard'));
}

function ajax_access_denied()
{
    set_alert('error', translate('access_denied'));
    $array = array('status' => 'access_denied');
    echo json_encode($array);
    exit();
}

function slugify($text)
{
    // replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '_', $text);

    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);

    // trim
    $text = trim($text, '_');

    // remove duplicated - symbols
    $text = preg_replace('~-+~', '_', $text);

    // lowercase
    $text = strtolower($text);
    return $text;
}

// website menu list
function web_menu_list($publish = '', $default = '', $branchID = '')
{
    $CI = &get_instance();
    if (empty($branchID)) {
        $branchID = $CI->home_model->getDefaultBranch();
    }
    $CI->db->select('*,if(front_cms_menu_visible.name is null, front_cms_menu.title, front_cms_menu_visible.name) as title, front_cms_menu_visible.invisible');
    $CI->db->from('front_cms_menu');
    $CI->db->join('front_cms_menu_visible', 'front_cms_menu_visible.menu_id = front_cms_menu.id and front_cms_menu_visible.branch_id = ' . $branchID, 'left');
    if ($publish != '') {
        $CI->db->where('front_cms_menu.publish', $publish);
    }
    if ($default != '') {
        $CI->db->where('front_cms_menu.system', $default);
    }
    $CI->db->order_by('front_cms_menu.ordering', 'asc');
    $CI->db->where_in('front_cms_menu.branch_id', array(0, $branchID));
    $result = $CI->db->get()->result_array();
    return $result;
}

function get_request_url()
{
    $url = $_SERVER['QUERY_STRING'];
    $url = (!empty($url) ? '?' . $url : '');
    return $url;
}

function delete_dir($dirPath)
{
    if (!is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            delete_dir($file);
        } else {
            unlink($file);
        }
    }
    if (rmdir($dirPath)) {
        return true;
    }
    return false;
}

function currencyFormat($amount = 0)
{
    $CI = &get_instance();
    $array              = $CI->data['global_config'];
    $currency           = $array['currency'];
    $currency_symbol    = $array['currency_symbol'];
    $currency_formats   = $array['currency_formats'];
    $symbol_position    = $array['symbol_position'];

    $amount = empty($amount) ? 0 : $amount;
    $value = $amount;
    if ($currency_formats == 1) {
        $value = number_format($amount, 2, '.', '');
    } elseif ($currency_formats == 2) {
        $value = moneyFormatIndia($amount);
    } elseif ($currency_formats == 3) {
        $value = number_format($amount, 3, '.', ',');
    } elseif ($currency_formats == 4) {
        $value = number_format($amount, 2, ',', '.');
    } elseif ($currency_formats == 5) {
        $value = number_format($amount, 2, '.', ',');
    } elseif ($currency_formats == 6) {
        $value = number_format($amount, 2, ',', ' ');
    } elseif ($currency_formats == 7) {
        $value = number_format($amount, 2, '.', ' ');
    } elseif ($currency_formats == 8) {
        $value = $amount;
    }

    if ($symbol_position == 1) {
        $value = $currency_symbol . $value; 
    } elseif ($symbol_position == 2) {
        $value = $value . $currency_symbol;
    } elseif ($symbol_position == 3) {
        $value = $currency_symbol . " " . $value;
    } elseif ($symbol_position == 4) {
        $value = $value . " " . $currency_symbol;
    } elseif ($symbol_position == 5) {
        $value = $currency . " " . $value;
    } elseif ($symbol_position == 6) {
        $value = $value . " " . $currency;
    }
    return $value;
}

function moneyFormatIndia($num)
{
    $explrestunits = "" ;
    $num = preg_replace('/,+/', '', $num);
    $words = explode(".", $num);
    $des = "00";
    if(count($words)<=2){
        $num=$words[0];
        if(count($words)>=2){$des=$words[1];}
        if(strlen($des)<2){$des="$des";}else{$des=substr($des,0,2);}
    }
    if(strlen($num)>3){
        $lastthree = substr($num, strlen($num)-3, strlen($num));
        $restunits = substr($num, 0, strlen($num)-3); // extracts the last three digits
        $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits; // explodes the remaining digits in 2's formats, adds a zero in the beginning to maintain the 2's grouping.
        $expunit = str_split($restunits, 2);
        for($i=0; $i<sizeof($expunit); $i++){
            // creates each of the 2's group and adds a comma to the end
            if($i==0)
            {
                $explrestunits .= (int)$expunit[$i].","; // if is first value , convert into integer
            }else{
                $explrestunits .= $expunit[$i].",";
            }
        }
        $thecash = $explrestunits.$lastthree;
    } else {
        $thecash = $num;
    }
    return "$thecash.$des"; // writes the final format where $currency is the currency symbol.
}

function getEnrollToStudentID($enroll_id = '')
{
    $CI = &get_instance();
    $get = $CI->db->select('student_id')->from('enroll')->where('id', $enroll_id)->limit(1)->get()->row()->student_id;
    return $get;
}

function version_combine()
{
    return md5(APP_VERSION); 
}

function img_reload()
{
    return "?src=" . time();
}

// list of design_style presets supported by card_templete, certificates_templete and marksheet_template
function document_template_styles()
{
    return array(
        'classic' => 'Classic',
        'modern' => 'Modern 3D',
        'premium' => 'Premium 3D',
    );
}

// resolve a template row (or raw value) to a safe, whitelisted design_style
// falls back to 'classic' when the migration hasn't run yet or the value is missing/invalid
function document_template_style($template = '')
{
    $style = is_array($template) ? (isset($template['design_style']) ? $template['design_style'] : '') : $template;
    $allowed = array_keys(document_template_styles());
    return in_array($style, $allowed, true) ? $style : 'classic';
}

// build the "document-template template-<style>" class attribute value for a template row
function document_template_class($template = '')
{
    return 'document-template template-' . html_escape(document_template_style($template));
}

// fixed set of psychomotor / affective-domain traits rated per student per exam
function psychomotor_traits()
{
    return array(
        'punctuality' => 'Punctuality',
        'attendance' => 'Attendance',
        'neatness' => 'Neatness',
        'politeness' => 'Politeness',
        'honesty' => 'Honesty',
        'relationship_with_peers' => 'Relationship with Peers',
        'relationship_with_teachers' => 'Relationship with Teachers',
        'leadership' => 'Leadership',
        'sports_handwork' => 'Sports / Handwork',
    );
}

// fixed 4-point psychomotor rating scale
function psychomotor_rating_scale()
{
    return array(
        4 => 'Excellent',
        3 => 'Good',
        2 => 'Fair',
        1 => 'Poor',
    );
}

// ready-to-use starter designs for the document-template system. content uses the same
// {tag} placeholders documented in Card_manage_model/Certificate_model/Marksheet_template_model
// tagsList() - nothing here is resolved until a school actually saves it as their own template.
function starter_templates()
{
    $studentIdContent = <<<'HTML'
<div style="position:relative;width:100%;height:100%;font-family:Arial,sans-serif;">
<table style="width:100%;height:100%;border-collapse:collapse;">
<tr><td style="background:#16233F;padding:8px 6px 6px;text-align:center;border-bottom:3px solid #B08D3E;">
<div style="color:#F4EFE3;font-size:10px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;line-height:1.25;">{institute_name}</div>
</td></tr>
<tr><td style="text-align:center;padding:10px 0 4px;">{student_photo}</td></tr>
<tr><td style="text-align:center;padding:3px 8px 0;">
<div style="font-size:14px;font-weight:bold;color:#16233F;line-height:1.2;">{name}</div>
<div style="font-size:10px;color:#5B6B84;margin-top:2px;font-weight:600;">{class} &middot; {section}</div>
</td></tr>
<tr><td style="padding:10px 12px 0;">
<table style="width:100%;font-size:9px;color:#16233F;border-collapse:collapse;">
<tr><td style="padding:2px 0;color:#5B6B84;">Reg. No</td><td style="padding:2px 0;text-align:right;font-weight:bold;">{register_no}</td></tr>
<tr><td style="padding:2px 0;color:#5B6B84;">D.O.B</td><td style="padding:2px 0;text-align:right;font-weight:bold;">{birthday}</td></tr>
<tr><td style="padding:2px 0;color:#5B6B84;">Blood Group</td><td style="padding:2px 0;text-align:right;font-weight:bold;">{blood_group}</td></tr>
</table>
</td></tr>
<tr><td style="text-align:center;padding:8px 0 6px;">{qr_code}</td></tr>
<tr><td style="text-align:center;padding:0 6px 10px;border-top:1px solid #E4DFD1;">
<div style="font-size:7px;color:#5B6B84;padding-top:5px;line-height:1.35;">If found, please return to {institute_name} &middot; {institute_mobile_no}</div>
</td></tr>
</table>
</div>
HTML;

    $employeeIdContent = <<<'HTML'
<div style="position:relative;width:100%;height:100%;font-family:Arial,sans-serif;">
<table style="width:100%;height:100%;border-collapse:collapse;">
<tr><td style="background:#3A2E12;padding:8px 6px 6px;text-align:center;border-bottom:3px solid #B08D3E;">
<div style="color:#F4EFE3;font-size:10px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;line-height:1.25;">{institute_name} &middot; Staff</div>
</td></tr>
<tr><td style="text-align:center;padding:10px 0 4px;">{staff_photo}</td></tr>
<tr><td style="text-align:center;padding:3px 8px 0;">
<div style="font-size:14px;font-weight:bold;color:#16233F;line-height:1.2;">{name}</div>
<div style="font-size:10px;color:#5B6B84;margin-top:2px;font-weight:600;">{designation}</div>
</td></tr>
<tr><td style="padding:10px 12px 0;">
<table style="width:100%;font-size:9px;color:#16233F;border-collapse:collapse;">
<tr><td style="padding:2px 0;color:#5B6B84;">Department</td><td style="padding:2px 0;text-align:right;font-weight:bold;">{department}</td></tr>
<tr><td style="padding:2px 0;color:#5B6B84;">Joined</td><td style="padding:2px 0;text-align:right;font-weight:bold;">{joining_date}</td></tr>
<tr><td style="padding:2px 0;color:#5B6B84;">Blood Group</td><td style="padding:2px 0;text-align:right;font-weight:bold;">{blood_group}</td></tr>
</table>
</td></tr>
<tr><td style="text-align:center;padding:8px 0 6px;">{qr_code}</td></tr>
<tr><td style="text-align:center;padding:0 6px 10px;border-top:1px solid #E4DFD1;">
<div style="font-size:7px;color:#5B6B84;padding-top:5px;line-height:1.35;">Property of {institute_name} &middot; Valid for current session</div>
</td></tr>
</table>
</div>
HTML;

    $admitCardContent = <<<'HTML'
<div style="position:relative;width:100%;height:100%;font-family:Arial,sans-serif;padding:14px 16px;">
<table style="width:100%;border-collapse:collapse;border-bottom:2px solid #16233F;padding-bottom:10px;">
<tr>
<td style="width:40px;vertical-align:middle;">{logo}</td>
<td style="vertical-align:middle;padding-left:10px;">
<div style="font-size:14px;font-weight:bold;color:#16233F;">{institute_name}</div>
<div style="font-size:9px;color:#5B6B84;">{institute_address} &middot; {institute_mobile_no}</div>
</td>
</tr>
</table>
<div style="text-align:center;margin:14px 0 12px;">
<div style="display:inline-block;font-size:16px;font-weight:bold;letter-spacing:1px;color:#16233F;border:1px solid #B08D3E;border-radius:20px;padding:5px 20px;">Examination Admit Card</div>
<div style="font-size:10px;color:#5B6B84;margin-top:8px;">{exam_name}</div>
</div>
<table style="width:100%;background:#F7F3EA;border-radius:10px;border-collapse:collapse;">
<tr>
<td style="width:60px;padding:10px;vertical-align:top;">{student_photo}</td>
<td style="padding:10px 10px 10px 0;vertical-align:top;">
<table style="width:100%;font-size:10px;color:#16233F;border-collapse:collapse;">
<tr><td style="padding:2px 0;color:#5B6B84;width:34%;">Name</td><td style="padding:2px 0;font-weight:bold;">{name}</td></tr>
<tr><td style="padding:2px 0;color:#5B6B84;">Class</td><td style="padding:2px 0;font-weight:bold;">{class} &middot; {section}</td></tr>
<tr><td style="padding:2px 0;color:#5B6B84;">Reg. No</td><td style="padding:2px 0;font-weight:bold;">{register_no}</td></tr>
</table>
</td>
</tr>
</table>
<div style="margin-top:14px;">{subject_list_table}</div>
<table style="width:100%;margin-top:20px;">
<tr>
<td style="font-size:9px;color:#5B6B84;border-top:1px solid #16233F;padding-top:4px;width:120px;">Invigilator's Signature</td>
<td style="text-align:right;">{qr_code}</td>
</tr>
</table>
</div>
HTML;

    $certificateContent = <<<'HTML'
<div style="position:relative;height:100%;font-family:Arial,sans-serif;">
<div style="position:absolute;top:0;right:0;bottom:0;left:0;border:2px solid #B08D3E;pointer-events:none;"></div>
<div style="position:absolute;top:6px;right:6px;bottom:6px;left:6px;border:1px solid #E4DFD1;pointer-events:none;"></div>
<div style="position:relative;height:100%;text-align:center;padding:30px 60px 0;">
{logo}
<div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#5B6B84;margin-top:10px;font-weight:600;">{institute_name}</div>
<div style="font-family:Georgia,serif;font-size:36px;font-weight:bold;color:#16233F;margin-top:18px;letter-spacing:1px;">Certificate of Achievement</div>
<div style="font-size:13px;color:#5B6B84;margin-top:18px;">This certificate is proudly presented to</div>
<div style="font-family:Georgia,serif;font-size:28px;font-style:italic;color:#16233F;margin-top:12px;border-bottom:2px solid #B08D3E;display:inline-block;padding-bottom:8px;">{name}</div>
<div style="font-size:13px;color:#5B6B84;margin-top:18px;max-width:480px;margin-left:auto;margin-right:auto;line-height:1.6;">
for outstanding performance in {class} {section}, {print_date}.
</div>
<table style="width:100%;max-width:520px;margin:40px auto 0;">
<tr>
<td style="text-align:center;width:33%;"><div style="border-top:1px solid #16233F;padding-top:6px;font-size:11px;color:#5B6B84;">Class Teacher</div></td>
<td style="text-align:center;width:33%;">{signature}<div style="border-top:1px solid #16233F;padding-top:6px;font-size:11px;color:#5B6B84;">Principal</div></td>
<td style="text-align:center;width:33%;"><div style="border-top:1px solid #16233F;padding-top:6px;font-size:11px;color:#5B6B84;">{print_date}</div></td>
</tr>
</table>
</div>
</div>
HTML;

    $marksheetHeader = <<<'HTML'
<table style="width:100%;border-collapse:collapse;border-bottom:2px solid #16233F;padding-bottom:10px;">
<tr>
<td style="width:50px;vertical-align:middle;">{logo}</td>
<td style="vertical-align:middle;padding-left:10px;">
<div style="font-size:16px;font-weight:bold;color:#16233F;">{institute_name}</div>
<div style="font-size:10px;color:#5B6B84;">{institute_address} &middot; {institute_mobile_no}</div>
</td>
<td style="text-align:right;vertical-align:middle;">
<div style="display:inline-block;font-size:13px;font-weight:bold;letter-spacing:1px;color:#16233F;border:1px solid #B08D3E;border-radius:16px;padding:4px 16px;">Report Card</div>
</td>
</tr>
</table>
<table style="width:100%;margin-top:14px;background:#F7F3EA;border-radius:8px;border-collapse:collapse;">
<tr>
<td style="width:70px;padding:10px;vertical-align:top;">{student_photo}</td>
<td style="padding:10px 10px 10px 0;vertical-align:top;">
<table style="width:100%;font-size:10px;color:#16233F;border-collapse:collapse;">
<tr><td style="padding:2px 0;color:#5B6B84;width:16%;">Name</td><td style="padding:2px 0;font-weight:bold;width:34%;">{name}</td>
<td style="padding:2px 0;color:#5B6B84;width:16%;">Class</td><td style="padding:2px 0;font-weight:bold;">{class} &middot; {section}</td></tr>
<tr><td style="padding:2px 0;color:#5B6B84;">Reg. No</td><td style="padding:2px 0;font-weight:bold;">{register_no}</td>
<td style="padding:2px 0;color:#5B6B84;">Session</td><td style="padding:2px 0;font-weight:bold;">{academic_session}</td></tr>
<tr><td style="padding:2px 0;color:#5B6B84;">Exam</td><td style="padding:2px 0;font-weight:bold;" colspan="3">{exam_name}</td></tr>
</table>
</td>
</tr>
</table>
HTML;

    $marksheetFooter = <<<'HTML'
<div style="margin-top:14px;">{psychomotor_ratings}</div>
<table style="width:100%;margin-top:10px;border-collapse:collapse;font-size:9px;color:#16233F;">
<tr><td style="padding:4px 0;color:#5B6B84;width:20%;vertical-align:top;">Teacher's Comment</td><td style="padding:4px 0;" colspan="3">{teacher_comments}</td></tr>
<tr><td style="padding:4px 0;color:#5B6B84;vertical-align:top;">Principal's Comment</td><td style="padding:4px 0;" colspan="3">{principal_comments}</td></tr>
</table>
<table style="width:100%;margin-top:24px;">
<tr>
<td style="text-align:center;width:33%;">{left_signature}<div style="border-top:1px solid #16233F;padding-top:6px;font-size:9px;color:#5B6B84;">Class Teacher</div></td>
<td style="text-align:center;width:33%;">{middle_signature}<div style="border-top:1px solid #16233F;padding-top:6px;font-size:9px;color:#5B6B84;">Principal</div></td>
<td style="text-align:center;width:33%;">{right_signature}<div style="border-top:1px solid #16233F;padding-top:6px;font-size:9px;color:#5B6B84;">Parent/Guardian</div></td>
</tr>
</table>
<table style="width:100%;margin-top:12px;font-size:9px;color:#5B6B84;">
<tr>
<td>Print Date: {print_date}</td>
<td style="text-align:right;">Next Term Begins: {next_term_begins}</td>
</tr>
</table>
HTML;

    return array(
        'id_card_student' => array(
            'applies_to' => 'id_card',
            'user_type' => 1,
            'label' => 'Student ID Card',
            'description' => 'Portrait badge sized for a standard lanyard holder.',
            'layout_width' => 54,
            'layout_height' => 86,
            'photo_style' => 1,
            'photo_size' => 90,
            'spacing' => array(8, 8, 8, 8),
            'design_style' => 'premium',
            'content' => $studentIdContent,
        ),
        'id_card_employee' => array(
            'applies_to' => 'id_card',
            'user_type' => 2,
            'label' => 'Employee ID Card',
            'description' => 'Same badge system, staff palette, role and department in place of class and section.',
            'layout_width' => 54,
            'layout_height' => 86,
            'photo_style' => 1,
            'photo_size' => 90,
            'spacing' => array(8, 8, 8, 8),
            'design_style' => 'modern',
            'content' => $employeeIdContent,
        ),
        'admit_card' => array(
            'applies_to' => 'admit_card',
            'user_type' => 1,
            'label' => 'Examination Admit Card',
            'description' => 'A6 hall ticket with the exam timetable built in.',
            'layout_width' => 105,
            'layout_height' => 148,
            'photo_style' => 2,
            'photo_size' => 100,
            'spacing' => array(0, 0, 0, 0),
            'design_style' => 'premium',
            'content' => $admitCardContent,
        ),
        'certificate' => array(
            'applies_to' => 'certificate',
            'user_type' => 1,
            'label' => 'Certificate of Achievement',
            'description' => 'Landscape A4 with a printed border and a gold rule under the recipient\'s name.',
            'page_layout' => 2,
            'photo_style' => 1,
            'photo_size' => 90,
            'spacing' => array(16, 16, 16, 16),
            'design_style' => 'premium',
            'content' => $certificateContent,
        ),
        'marksheet' => array(
            'applies_to' => 'marksheet',
            'label' => 'Termly Report Card',
            'description' => 'School header, student info block, psychomotor ratings and signature block ready to go.',
            'page_layout' => 1,
            'photo_style' => 1,
            'photo_size' => 90,
            'spacing' => array(16, 16, 16, 16),
            'design_style' => 'premium',
            'header_content' => $marksheetHeader,
            'footer_content' => $marksheetFooter,
        ),
    );
}

// fixed demo values used ONLY to render a realistic-looking preview of a starter template
// in the picker gallery - never saved, never used for the actual production tagsReplace()
function starter_template_demo_values()
{
    $avatar = '<svg width="60" height="60" viewBox="0 0 60 60" style="border-radius:50%;background:#EEF1F6;"><circle cx="30" cy="23" r="11" fill="#AEB9CC"/><path d="M9 53c3-12 13-17 21-17s18 5 21 17" fill="#AEB9CC"/></svg>';
    $logo = '<svg width="36" height="36" viewBox="0 0 36 36"><circle cx="18" cy="18" r="17" fill="#16233F"/><text x="18" y="24" font-family="Georgia,serif" font-size="16" fill="#F4EFE3" text-anchor="middle">S</text></svg>';
    $qr = '<svg width="40" height="40" viewBox="0 0 34 34"><rect width="34" height="34" fill="#fff"/><rect x="2" y="2" width="8" height="8" fill="#16233F"/><rect x="24" y="2" width="8" height="8" fill="#16233F"/><rect x="2" y="24" width="8" height="8" fill="#16233F"/><rect x="13" y="2" width="3" height="3" fill="#16233F"/><rect x="19" y="6" width="3" height="3" fill="#16233F"/><rect x="13" y="13" width="8" height="8" fill="#16233F"/><rect x="24" y="14" width="3" height="3" fill="#16233F"/><rect x="14" y="24" width="3" height="8" fill="#16233F"/><rect x="20" y="27" width="6" height="3" fill="#16233F"/></svg>';
    $signature = '<div style="height:26px;"></div>';
    $subjectTable = '<table style="width:100%;border-collapse:collapse;font-size:9px;"><thead><tr style="background:#16233F;color:#F4EFE3;"><th style="padding:5px 6px;text-align:left;">Subject</th><th style="padding:5px 6px;text-align:left;">Date</th><th style="padding:5px 6px;text-align:left;">Time</th><th style="padding:5px 6px;text-align:left;">Hall</th></tr></thead><tbody>'
        . '<tr style="border-bottom:1px solid #E4DFD1;"><td style="padding:5px 6px;">Mathematics</td><td style="padding:5px 6px;">12 Dec</td><td style="padding:5px 6px;">9:00-11:00</td><td style="padding:5px 6px;">Hall A</td></tr>'
        . '<tr><td style="padding:5px 6px;">English Language</td><td style="padding:5px 6px;">13 Dec</td><td style="padding:5px 6px;">9:00-11:00</td><td style="padding:5px 6px;">Hall A</td></tr>'
        . '</tbody></table>';
    $psychomotor = '<table style="width:100%;border-collapse:collapse;font-size:9px;"><tr><th colspan="2" style="text-align:center;background:#F7F3EA;padding:4px;">Psychomotor / Affective Rating</th></tr>'
        . '<tr><td style="padding:3px 0;color:#5B6B84;">Punctuality</td><td style="padding:3px 0;text-align:right;font-weight:bold;">Excellent</td></tr>'
        . '<tr><td style="padding:3px 0;color:#5B6B84;">Neatness</td><td style="padding:3px 0;text-align:right;font-weight:bold;">Good</td></tr>'
        . '</table>';

    return array(
        '{name}' => 'Chidinma A. Okonkwo',
        '{student_photo}' => $avatar,
        '{staff_photo}' => $avatar,
        '{logo}' => $logo,
        '{qr_code}' => $qr,
        '{signature}' => $signature,
        '{left_signature}' => $signature,
        '{middle_signature}' => $signature,
        '{right_signature}' => $signature,
        '{class}' => 'JSS 2',
        '{section}' => 'Gold',
        '{register_no}' => 'LU/2024/0142',
        '{roll}' => '14',
        '{birthday}' => '14 Mar 2012',
        '{blood_group}' => 'O+',
        '{institute_name}' => 'LANDUP Schools',
        '{institute_address}' => '14 Unity Crescent, Lekki, Lagos',
        '{institute_mobile_no}' => '0803 000 0000',
        '{designation}' => 'Mathematics Teacher',
        '{department}' => 'Sciences',
        '{joining_date}' => '12 Sep 2019',
        '{exam_name}' => 'First Term Examination',
        '{subject_list_table}' => $subjectTable,
        '{print_date}' => _d(date('Y-m-d')),
        '{academic_session}' => '2025/2026',
        '{teacher_comments}' => 'A diligent and attentive learner.',
        '{principal_comments}' => 'Keep up the good work.',
        '{next_term_begins}' => '12 Jan 2026',
        '{psychomotor_ratings}' => $psychomotor,
    );
}

// substitute demo data into a starter's {tag} content for the picker preview only
function render_starter_preview($content)
{
    $preview = str_replace(array_keys(starter_template_demo_values()), array_values(starter_template_demo_values()), $content);
    // anything left over (institute_email etc.) shouldn't show as a raw {tag} in the preview
    return preg_replace('/\{[a-z_]+\}/', '', $preview);
}