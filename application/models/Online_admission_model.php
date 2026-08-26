<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Online_admission_model extends MY_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    // moderator student all information
    public function save($data = array(), $getBranch = array())
    {
        $existStudent_photo = $this->input->post('exist_student_photo');
        $existGuardian_photo = $this->input->post('exist_guardian_photo');
        if (empty($existStudent_photo)) {
            $studentPhoto = $this->uploadImage('student', 'student_photo');
        } else {
            $studentPhoto = $existStudent_photo;
        }
        if (empty($existGuardian_photo)) {
            $guardianPhoto = $this->uploadImage('parent', 'guardian_photo');
        } else {
            $guardianPhoto = $existGuardian_photo;
        }

        $hostelID = empty($data['hostel_id']) ? 0 : $data['hostel_id'];
        $roomID = empty($data['room_id']) ? 0 : $data['room_id'];
        $previous_details = array(
            'school_name' => $this->input->post('school_name'),
            'qualification' => $this->input->post('qualification'),
            'remarks' => $this->input->post('previous_remarks'),
        );
        if (empty($previous_details)) {
            $previous_details = "";
        } else {
            $previous_details = json_encode($previous_details);
        }
        $inser_data1 = array(
            'register_no' => $this->input->post('register_no'),
            'admission_date' => (isset($data['admission_date']) ? date("Y-m-d", strtotime($data['admission_date'])) : ""),
            'first_name' => $this->input->post('first_name'),
            'last_name' => $this->input->post('last_name'),
            'gender' => $this->input->post('gender'),
            'birthday' => (isset($data['birthday']) ? date("Y-m-d", strtotime($data['birthday'])) : ""),
            'religion' => $this->input->post('religion'),
            'caste' => $this->input->post('caste'),
            'blood_group' => $this->input->post('blood_group'),
            'mother_tongue' => $this->input->post('mother_tongue'),
            'current_address' => $this->input->post('current_address'),
            'permanent_address' => $this->input->post('permanent_address'),
            'city' => $this->input->post('city'),
            'state' => $this->input->post('state'),
            'mobileno' => $this->input->post('mobileno'),
            'category_id' => (isset($data['category_id']) ? $data['category_id'] : 0),
            'email' => $this->input->post('email'),
            'parent_id' => "",
            'route_id' => $this->input->post('route_id'),
            'vehicle_id' => $this->input->post('vehicle_id'),
            'hostel_id' => $hostelID,
            'room_id' => $roomID,
            'previous_details' => $previous_details,
            'photo' => $studentPhoto,
        );

        // add new guardian all information in db
        if (!empty($data['grd_name']) || !empty($data['father_name'])) {
            $arrayParent = array(
                'name' => $this->input->post('grd_name'),
                'relation' => $this->input->post('grd_relation'),
                'father_name' => $this->input->post('father_name'),
                'mother_name' => $this->input->post('mother_name'),
                'occupation' => $this->input->post('grd_occupation'),
                'income' => $this->input->post('grd_income'),
                'education' => $this->input->post('grd_education'),
                'email' => $this->input->post('grd_email'),
                'mobileno' => $this->input->post('grd_mobileno'),
                'address' => $this->input->post('grd_address'),
                'city' => $this->input->post('grd_city'),
                'state' => $this->input->post('grd_state'),
                'branch_id' => $getBranch['id'],
                'photo' => $guardianPhoto,
            );
            $this->db->insert('parent', $arrayParent);
            $parentID = $this->db->insert_id();
            // save guardian login credential information in the database
            if ($getBranch['grd_generate'] == 1) {
                $grd_username = $getBranch['grd_username_prefix'] . $parentID;
                $grd_password = $getBranch['grd_default_password'];
            } else {
                $grd_username = $this->input->post('grd_username');
                $grd_password = $this->input->post('grd_password');
            }
            $parent_credential = array(
                'username' => $grd_username,
                'role' => 6,
                'user_id' => $parentID,
                'password' => $this->app_lib->pass_hashed($grd_password),
            );
            $this->db->insert('login_credential', $parent_credential);

            // insert student all information in the database
            $inser_data1['parent_id'] = $parentID;
        } else {
            $inser_data1['parent_id'] = 0;     
        }

        $this->db->insert('student', $inser_data1);
        $student_id = $this->db->insert_id();
        // save student login credential information in the database
        if ($getBranch['stu_generate'] == 1) {
            $stu_username = $getBranch['stu_username_prefix'] . $student_id;
            $stu_password = $getBranch['stu_default_password'];
        } else {
            $stu_username = $this->input->post('username');
            $stu_password = $this->input->post('password');
        }
        $inser_data2 = array(
            'user_id' => $student_id,
            'username' => $stu_username,
            'role' => 7,
            'password' => $this->app_lib->pass_hashed($stu_password),
        );
        $this->db->insert('login_credential', $inser_data2);

        // return student information
        $studentData = array(
            'student_id' => $student_id,
            'email' => $this->input->post('email'),
            'username' => $stu_username,
            'password' => $stu_password,
        );

        if (!empty($data['grd_name']) || !empty($data['father_name'])) {
            // send parent account activate email
            $emailData = array(
                'name' => $this->input->post('grd_name'),
                'username' => $grd_username,
                'password' => $grd_password,
                'user_role' => 6,
                'email' => $this->input->post('grd_email'),
            );
            $this->email_model->sentStaffRegisteredAccount($emailData);
        }
        return $studentData;
    }

    // reversible encryption for a manually-typed password staged ahead of checker
    // approval -- never stored as plaintext, decrypted only once (to email the new
    // account's real credentials) at the moment the checker finalizes the admission.
    // Not used for auto-generated credentials, whose password is always just the
    // branch's own known default and needs no staging at all.
    public function encryptStagedSecret($plain)
    {
        if ($plain === null || $plain === '') {
            return null;
        }
        $key = hash('sha256', config_item('encryption_key'));
        $iv = openssl_random_pseudo_bytes(16);
        $cipher = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipher);
    }

    public function decryptStagedSecret($encoded)
    {
        if (empty($encoded)) {
            return null;
        }
        $key = hash('sha256', config_item('encryption_key'));
        $data = base64_decode($encoded);
        $iv = substr($data, 0, 16);
        $cipher = substr($data, 16);
        return openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }

    // checker-approval-time version of save(): takes every value from $data (the
    // staged payload) instead of reading $_POST/$_FILES directly, since there is no
    // live form submission at this point -- photos were already uploaded and their
    // filenames staged, and any manually-typed password was staged encrypted (never
    // hashed at staging time, so it can still be emailed to the new account here).
    public function finalizeSave($data, $getBranch)
    {
        $studentPhoto = !empty($data['student_photo_filename']) ? $data['student_photo_filename'] : 'defualt.png';
        $guardianPhoto = !empty($data['guardian_photo_filename']) ? $data['guardian_photo_filename'] : 'defualt.png';

        $previous_details = json_encode(array(
            'school_name' => isset($data['school_name']) ? $data['school_name'] : '',
            'qualification' => isset($data['qualification']) ? $data['qualification'] : '',
            'remarks' => isset($data['previous_remarks']) ? $data['previous_remarks'] : '',
        ));

        $hostelID = empty($data['hostel_id']) ? 0 : $data['hostel_id'];
        $roomID = empty($data['room_id']) ? 0 : $data['room_id'];

        $inser_data1 = array(
            'register_no' => isset($data['register_no']) ? $data['register_no'] : '',
            'admission_date' => (!empty($data['admission_date']) ? date("Y-m-d", strtotime($data['admission_date'])) : ""),
            'first_name' => isset($data['first_name']) ? $data['first_name'] : '',
            'last_name' => isset($data['last_name']) ? $data['last_name'] : '',
            'gender' => isset($data['gender']) ? $data['gender'] : '',
            'birthday' => (!empty($data['birthday']) ? date("Y-m-d", strtotime($data['birthday'])) : ""),
            'religion' => isset($data['religion']) ? $data['religion'] : '',
            'caste' => isset($data['caste']) ? $data['caste'] : '',
            'blood_group' => isset($data['blood_group']) ? $data['blood_group'] : '',
            'mother_tongue' => isset($data['mother_tongue']) ? $data['mother_tongue'] : '',
            'current_address' => isset($data['current_address']) ? $data['current_address'] : '',
            'permanent_address' => isset($data['permanent_address']) ? $data['permanent_address'] : '',
            'city' => isset($data['city']) ? $data['city'] : '',
            'state' => isset($data['state']) ? $data['state'] : '',
            'mobileno' => isset($data['mobileno']) ? $data['mobileno'] : '',
            'category_id' => (isset($data['category_id']) ? $data['category_id'] : 0),
            'email' => isset($data['email']) ? $data['email'] : '',
            'parent_id' => "",
            'route_id' => isset($data['route_id']) ? $data['route_id'] : null,
            'vehicle_id' => isset($data['vehicle_id']) ? $data['vehicle_id'] : null,
            'hostel_id' => $hostelID,
            'room_id' => $roomID,
            'previous_details' => $previous_details,
            'photo' => $studentPhoto,
        );

        $grd_username = null;
        $grd_password_plain = null;
        if (!empty($data['grd_name']) || !empty($data['father_name'])) {
            $arrayParent = array(
                'name' => isset($data['grd_name']) ? $data['grd_name'] : '',
                'relation' => isset($data['grd_relation']) ? $data['grd_relation'] : '',
                'father_name' => isset($data['father_name']) ? $data['father_name'] : '',
                'mother_name' => isset($data['mother_name']) ? $data['mother_name'] : '',
                'occupation' => isset($data['grd_occupation']) ? $data['grd_occupation'] : '',
                'income' => isset($data['grd_income']) ? $data['grd_income'] : '',
                'education' => isset($data['grd_education']) ? $data['grd_education'] : '',
                'email' => isset($data['grd_email']) ? $data['grd_email'] : '',
                'mobileno' => isset($data['grd_mobileno']) ? $data['grd_mobileno'] : '',
                'address' => isset($data['grd_address']) ? $data['grd_address'] : '',
                'city' => isset($data['grd_city']) ? $data['grd_city'] : '',
                'state' => isset($data['grd_state']) ? $data['grd_state'] : '',
                'branch_id' => $getBranch['id'],
                'photo' => $guardianPhoto,
            );
            $this->db->insert('parent', $arrayParent);
            $parentID = $this->db->insert_id();

            if ($getBranch['grd_generate'] == 1) {
                $grd_username = $getBranch['grd_username_prefix'] . $parentID;
                $grd_password_plain = $getBranch['grd_default_password'];
            } else {
                $grd_username = $data['grd_username'];
                $grd_password_plain = $this->decryptStagedSecret($data['grd_password_encrypted']);
            }
            $this->db->insert('login_credential', array(
                'username' => $grd_username,
                'role' => 6,
                'user_id' => $parentID,
                'password' => $this->app_lib->pass_hashed($grd_password_plain),
            ));
            $inser_data1['parent_id'] = $parentID;
        } else {
            $inser_data1['parent_id'] = 0;
        }

        $this->db->insert('student', $inser_data1);
        $student_id = $this->db->insert_id();

        if ($getBranch['stu_generate'] == 1) {
            $stu_username = $getBranch['stu_username_prefix'] . $student_id;
            $stu_password_plain = $getBranch['stu_default_password'];
        } else {
            $stu_username = $data['username'];
            $stu_password_plain = $this->decryptStagedSecret($data['password_encrypted']);
        }
        $this->db->insert('login_credential', array(
            'user_id' => $student_id,
            'username' => $stu_username,
            'role' => 7,
            'password' => $this->app_lib->pass_hashed($stu_password_plain),
        ));

        $studentData = array(
            'student_id' => $student_id,
            'email' => isset($data['email']) ? $data['email'] : '',
            'username' => $stu_username,
            'password' => $stu_password_plain,
        );

        if (!empty($data['grd_name']) || !empty($data['father_name'])) {
            $emailData = array(
                'name' => isset($data['grd_name']) ? $data['grd_name'] : '',
                'username' => $grd_username,
                'password' => $grd_password_plain,
                'user_role' => 6,
                'email' => isset($data['grd_email']) ? $data['grd_email'] : '',
            );
            $this->email_model->sentStaffRegisteredAccount($emailData);
        }
        return $studentData;
    }

    // approval queue / detail lookup for staged admissions
    public function getStagingList($where = array(), $single = false)
    {
        $this->db->select('oas.*, oa.first_name, oa.last_name, oa.reference_no, oa.class_id, oa.section_id, c.name as class_name, se.name as section_name');
        $this->db->from('online_admission_staging as oas');
        $this->db->join('online_admission as oa', 'oa.id = oas.online_admission_id', 'left');
        $this->db->join('class as c', 'c.id = oa.class_id', 'left');
        $this->db->join('section as se', 'se.id = oa.section_id', 'left');
        if (!is_superadmin_loggedin()) {
            $this->db->where('oas.branch_id', get_loggedin_branch_id());
        }
        if (!empty($where)) {
            $this->db->where($where);
        }
        if ($single == false) {
            $this->db->order_by('oas.id', 'DESC');
            return $this->db->get()->result_array();
        } else {
            return $this->db->get()->row_array();
        }
    }

    // re-check uniqueness constraints that could have gone stale between staging
    // and final approval (e.g. a different admission took the same username/roll
    // in the meantime) -- returns a list of human-readable conflict messages,
    // empty when clear to proceed.
    public function checkStagedUniqueness($payload, $branchId)
    {
        $conflicts = array();
        if (!empty($payload['username'])) {
            $this->db->where('username', $payload['username']);
            if ($this->db->get('login_credential')->num_rows() > 0) {
                $conflicts[] = translate('username') . ' "' . $payload['username'] . '" ' . translate('already_taken');
            }
        }
        if (!empty($payload['register_no'])) {
            $this->db->where('register_no', $payload['register_no']);
            if ($this->db->get('student')->num_rows() > 0) {
                $conflicts[] = translate('register_no') . ' "' . $payload['register_no'] . '" ' . translate('already_taken');
            }
        }
        if (!empty($payload['roll'])) {
            $schoolSettings = $this->get('branch', array('id' => $branchId), true, false, 'unique_roll');
            $uniqueRoll = $schoolSettings['unique_roll'];
            if (!empty($uniqueRoll)) {
                $this->db->where('roll', $payload['roll']);
                $this->db->where('class_id', $payload['class_id']);
                if ($uniqueRoll == 2) {
                    $this->db->where('section_id', $payload['section_id']);
                }
                $this->db->where('branch_id', $branchId);
                if ($this->db->get('enroll')->num_rows() > 0) {
                    $conflicts[] = translate('roll') . ' "' . $payload['roll'] . '" ' . translate('already_taken');
                }
            }
        }
        return $conflicts;
    }

    public function getOnlineAdmission($class_id = '', $branch_id = '')
    {
        $this->db->select('oa.*,c.name as class_name,se.name as section_name');
        $this->db->from('online_admission as oa');
        $this->db->join('class as c', 'oa.class_id = c.id', 'left');
        $this->db->join('section as se', 'oa.section_id = se.id', 'left');
        $this->db->where('oa.class_id', $class_id);
        $this->db->where('oa.branch_id', $branch_id);
        $this->db->order_by('oa.id', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function regSerNumber($school_id = '')
    {
        $registerNoPrefix = '';
        if (!empty($school_id)) {
            $schoolconfig = $this->db->select('reg_prefix_enable,reg_start_from,institution_code,reg_prefix_digit')->where(array('id' => $school_id))->get('branch')->row();
            if ($schoolconfig->reg_prefix_enable == 1) {
                $registerNoPrefix = $schoolconfig->institution_code . $schoolconfig->reg_start_from;
                $last_registerNo = $this->app_lib->studentLastRegID($school_id);
                if (!empty($last_registerNo)) {
                    $last_registerNo_digit = str_replace($schoolconfig->institution_code, "", $last_registerNo->register_no);
                    if (!is_numeric($last_registerNo_digit)) {
                        $last_registerNo_digit = $schoolconfig->reg_start_from;
                    } else {
                        $last_registerNo_digit = $last_registerNo_digit + 1;
                    }
                    $registerNoPrefix = $schoolconfig->institution_code . sprintf("%0" . $schoolconfig->reg_prefix_digit . "d", $last_registerNo_digit);
                } else {
                    $registerNoPrefix = $schoolconfig->institution_code . sprintf("%0" . $schoolconfig->reg_prefix_digit . "d", $schoolconfig->reg_start_from);
                }
            }
            return $registerNoPrefix;
        } else {
            $config = $this->db->select('institution_code,reg_prefix')->where(array('id' => 1))->get('global_settings')->row();
            if ($config->reg_prefix == 'on') {
                $prefix = $config->institution_code;
            }
            $result = $this->db->select("max(id) as id")->get('student')->row_array();
            $id = $result["id"];
            if (!empty($id)) {
                $maxNum = str_pad($id + 1, 5, '0', STR_PAD_LEFT);
            } else {
                $maxNum = '00001';
            }
            return ($prefix . $maxNum);
        }
    }
}
