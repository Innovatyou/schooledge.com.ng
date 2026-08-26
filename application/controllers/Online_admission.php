<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Ramom school management system
 * @version : 5.8
 * @developed by : RamomCoder
 * @support : ramomcoder@yahoo.com
 * @author url : http://codecanyon.net/user/RamomCoder
 * @filename : Online_admission.php
 * @copyright : Reserved RamomCoder Team
 */

class Online_admission extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helpers('custom_fields');
        $this->load->model('online_admission_model');
        $this->load->model('student_fields_model');
        $this->load->model('email_model');
        $this->load->model('sms_model');
    }

    public function index()
    {
        // check access permission
        if (!get_permission('online_admission', 'is_view')) {
            access_denied();
        }

        $branchID = $this->application_model->get_branch_id();
        if (isset($_POST['search'])) {
            $classID = $this->input->post('class_id');
            $sectionID = $this->input->post('section_id');
            $this->data['students'] = $this->online_admission_model->getOnlineAdmission($classID, $branchID);
        }
        $this->data['branch_id'] = $branchID;
        $this->data['title'] = translate('student_list');
        $this->data['main_menu'] = 'admission';
        $this->data['sub_page'] = 'online_admission/index';
        $this->data['headerelements'] = array(
            'js' => array(
                'js/student.js',
            ),
        );
        $this->load->view('layout/index', $this->data);
    }

    // delete student from database
    public function delete($id)
    {
        if (get_permission('online_admission', 'is_delete')) {
            $branch_id = $this->db->select('branch_id')->where('id', $id)->get('online_admission')->row()->branch_id;

            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $this->db->where('id', $id);
            $this->db->delete('online_admission');
            if ($this->db->affected_rows() > 0) {
                $result = $this->db
                    ->where(array('form_to' => 'online_admission', 'branch_id' => $branch_id))
                    ->get('custom_field')->result_array();
                foreach ($result as $key => $value) {
                    $this->db->where('relid', $id);
                    $this->db->where('field_id', $value['id']);
                    $this->db->delete('custom_fields_values');
                }
            }
        }
    }

    public function decline($id)
    {
        if (get_permission('online_admission', 'is_add')) {
            if (!is_superadmin_loggedin()) {
                $this->db->where('branch_id', get_loggedin_branch_id());
            }
            $this->db->where('id', $id);
            $this->db->update('online_admission', array('status' => 3));
        }
    }

    public function approved($student_id = '')
    {
        // check access permission
        if (!get_permission('online_admission', 'is_add')) {
            access_denied();
        }

        // check saas student add limit
        if($this->app_lib->isExistingAddon('saas')) {
            if (!checkSaasLimit('student')) {
                set_alert('error', translate('update_your_package'));
                redirect(site_url('dashboard'));
            }
        }

        $stuDetails = $this->online_admission_model->get('online_admission', array('id' => $student_id, 'status !=' => 2), true, true);
        if (empty($stuDetails['id'])) {
            access_denied();
        }
        if ($stuDetails['status'] == 4) {
            // already staged and awaiting a checker's review -- don't let the maker
            // (or anyone else) re-enter and edit out from under that review
            access_denied();
        }

        $branchID = $stuDetails['branch_id'];
        $getBranch = $this->db->where('id', $branchID)->get('branch')->row_array();
        $guardian = false;

        if ($_POST) {
            $newStudent_photo = 0;
            $newGuardian_photo = 0;
            $existStudent_photo = $this->input->post('exist_student_photo');
            $existGuardian_photo = $this->input->post('exist_guardian_photo');
            if (isset($_FILES["student_photo"]) && empty($_FILES["student_photo"]['name'])) {
                $newStudent_photo = 1;
            }
            if (isset($_FILES["guardian_photo"]) && empty($_FILES["guardian_photo"]['name'])) {
                $newGuardian_photo = 1;
            }

            $this->form_validation->set_rules('first_name', translate('first_name'), 'trim|required');
            $this->form_validation->set_rules('year_id', translate('academic_year'), 'trim|required');
            $this->form_validation->set_rules('register_no', translate('register_no'), 'trim|required');
            $this->form_validation->set_rules('class_id', translate('class'), 'trim|required');
            $this->form_validation->set_rules('section_id', translate('section'), 'trim|required');

            // checking profile photo format
            $this->form_validation->set_rules('student_photo', translate('profile_picture'), 'callback_photoHandleUpload[student_photo]');
            $this->form_validation->set_rules('guardian_photo', translate('profile_picture'), 'callback_photoHandleUpload[guardian_photo]');

            // custom fields validation rules
            $customFields = getOnlineCustomFields('student', $branchID);
            foreach ($customFields as $fields_key => $fields_value) {
                if ($fields_value['required']) {
                    $fieldsID = $fields_value['id'];
                    $fieldLabel = $fields_value['field_label'];
                    $this->form_validation->set_rules("custom_fields[student][" . $fieldsID . "]", $fieldLabel, 'trim|required');
                }
            }

            // system fields validation rules
            $validArr = array();
            $validationArr = $this->student_fields_model->getStatusArr($branchID);
            foreach ($validationArr as $key => $value) {
                if ($value->status && $value->required) {
                    $validArr[$value->prefix] = 1;
                }
            }
            if (isset($validArr['admission_date'])) {
                $this->form_validation->set_rules('admission_date', translate('admission_date'), 'trim|required');
            }
            if (isset($validArr['student_photo'])) {
                if ($newStudent_photo == 1 && empty($existStudent_photo)) {
                    $this->form_validation->set_rules('student_photo', translate('profile_picture'), 'required');
                }
            }
            if (isset($validArr['roll'])) {
                $this->form_validation->set_rules('roll', translate('roll'), 'trim|numeric|required|callback_unique_roll');
            } else {
                $this->form_validation->set_rules('roll', translate('roll'), 'trim|numeric|callback_unique_roll');
            }
            if (isset($validArr['last_name'])) {
                $this->form_validation->set_rules('last_name', translate('last_name'), 'trim|required');
            }
            if (isset($validArr['gender'])) {
                $this->form_validation->set_rules('gender', translate('gender'), 'trim|required');
            }
            if (isset($validArr['birthday'])) {
                $this->form_validation->set_rules('birthday', translate('birthday'), 'trim|required');
            }
            if (isset($validArr['category'])) {
                $this->form_validation->set_rules('category_id', translate('category'), 'trim|required');
            }

            if (isset($validArr['religion'])) {
                $this->form_validation->set_rules('religion', translate('religion'), 'trim|required');
            }
            if (isset($validArr['caste'])) {
                $this->form_validation->set_rules('caste', translate('caste'), 'trim|required');
            }
            if (isset($validArr['blood_group'])) {
                $this->form_validation->set_rules('blood_group', translate('blood_group'), 'trim|required');
            }
            if (isset($validArr['mother_tongue'])) {
                $this->form_validation->set_rules('mother_tongue', translate('mother_tongue'), 'trim|required');
            }
            if (isset($validArr['present_address'])) {
                $this->form_validation->set_rules('current_address', translate('present_address'), 'trim|required');
            }
            if (isset($validArr['permanent_address'])) {
                $this->form_validation->set_rules('permanent_address', translate('permanent_address'), 'trim|required');
            }
            if (isset($validArr['city'])) {
                $this->form_validation->set_rules('city', translate('city'), 'trim|required');
            }
            if (isset($validArr['state'])) {
                $this->form_validation->set_rules('state', translate('state'), 'trim|required');
            }
            if (isset($validArr['student_email'])) {
                $this->form_validation->set_rules('email', translate('email'), 'trim|required|valid_email');
            }
            if (isset($validArr['student_mobile_no'])) {
                $this->form_validation->set_rules('mobileno', translate('mobile_no'), 'trim|required|numeric');
            }
            if (isset($validArr['previous_school_details'])) {
                $this->form_validation->set_rules('school_name', translate('school_name'), 'trim|required');
                $this->form_validation->set_rules('qualification', translate('qualification'), 'trim|required');
            }
            if (isset($validArr['guardian_name'])) {
                $this->form_validation->set_rules('grd_name', translate('name'), 'trim|required');
                $guardian = true;
            }
            if (isset($validArr['guardian_relation'])) {
                $this->form_validation->set_rules('grd_relation', translate('relation'), 'trim|required');
                $guardian = true;
            }
            if (isset($validArr['father_name'])) {
                $this->form_validation->set_rules('father_name', translate('father_name'), 'trim|required');
                $guardian = true;
            }
            if (isset($validArr['mother_name'])) {
                $this->form_validation->set_rules('mother_name', translate('mother_name'), 'trim|required');
                $guardian = true;
            }
            if (isset($validArr['guardian_occupation'])) {
                $this->form_validation->set_rules('grd_occupation', translate('occupation'), 'trim|required');
                $guardian = true;
            }
            if (isset($validArr['guardian_income'])) {
                $this->form_validation->set_rules('grd_income', translate('occupation'), 'trim|required|numeric');
                $guardian = true;
            }
            if (isset($validArr['guardian_education'])) {
                $this->form_validation->set_rules('grd_education', translate('education'), 'trim|required');
                $guardian = true;
            }
            if (isset($validArr['guardian_email'])) {
                $this->form_validation->set_rules('grd_email', translate('email'), 'trim|required');
                $guardian = true;
            }
            if (isset($validArr['guardian_mobile_no'])) {
                $this->form_validation->set_rules('grd_mobileno', translate('mobile_no'), 'trim|required|numeric');
                $guardian = true;
            }
            if (isset($validArr['guardian_address'])) {
                $this->form_validation->set_rules('grd_address', translate('address'), 'trim|required');
                $guardian = true;
            }
            if (isset($validArr['guardian_photo'])) {
                if ($newGuardian_photo == 1 && empty($existGuardian_photo)) {
                    $this->form_validation->set_rules('guardian_photo', translate('guardian_picture'), 'required');
                    $guardian = true;
                }
            }
            if (isset($validArr['guardian_city'])) {
                $this->form_validation->set_rules('grd_city', translate('city'), 'trim|required');
                $guardian = true;
            }
            if (isset($validArr['guardian_state'])) {
                $this->form_validation->set_rules('grd_state', translate('state'), 'trim|required');
                $guardian = true;
            }
            if ($getBranch['stu_generate'] == 0 || isset($_POST['student_id'])) {
                $this->form_validation->set_rules('username', translate('username'), 'trim|required|callback_unique_username');
                if (!isset($_POST['student_id'])) {
                    $this->form_validation->set_rules('password', translate('password'), 'trim|required|min_length[4]');
                    $this->form_validation->set_rules('retype_password', translate('retype_password'), 'trim|required|matches[password]');
                }
            }
            if ($getBranch['grd_generate'] == 0 && $guardian == true) {
                $this->form_validation->set_rules('grd_username', translate('username'), 'trim|required|callback_get_valid_guardian_username');
                $this->form_validation->set_rules('grd_password', translate('password'), 'trim|required');
                $this->form_validation->set_rules('grd_retype_password', translate('retype_password'), 'trim|required|matches[grd_password]');
            }

            // custom fields validation rules
            $class_slug = "student";
            $customFields = getCustomFields($class_slug, $branchID);
            foreach ($customFields as $fields_key => $fields_value) {
                if ($fields_value['required']) {
                    $fieldsID = $fields_value['id'];
                    $fieldLabel = $fields_value['field_label'];
                    $this->form_validation->set_rules("custom_fields[student][" . $fieldsID . "]", $fieldLabel, 'trim|required');
                }
            }

            if ($this->form_validation->run() == true) {
                $post = $this->input->post();

                // resolve photos now, while $_FILES is actually available -- the
                // checker's later approval has no file upload of its own
                $post['student_photo_filename'] = $this->online_admission_model->uploadImage('student', 'student_photo');
                $post['guardian_photo_filename'] = $this->online_admission_model->uploadImage('parent', 'guardian_photo');

                // credentials: auto-generated ones need nothing staged (the branch's
                // known default is resolved fresh at approval time); a manually-typed
                // password is captured now, reversibly encrypted so it is never left
                // as plaintext at rest, since there is no second form submission at
                // approval time to type it again.
                if ($getBranch['stu_generate'] == 0) {
                    $post['username'] = $this->input->post('username');
                    $post['password_encrypted'] = $this->online_admission_model->encryptStagedSecret($this->input->post('password'));
                }
                unset($post['password'], $post['retype_password']);
                if ($getBranch['grd_generate'] == 0 && $guardian == true) {
                    $post['grd_username'] = $this->input->post('grd_username');
                    $post['grd_password_encrypted'] = $this->online_admission_model->encryptStagedSecret($this->input->post('grd_password'));
                }
                unset($post['grd_password'], $post['grd_retype_password']);

                $customField = $this->input->post("custom_fields[student]");
                $post['custom_fields_student'] = !empty($customField) ? $customField : array();

                $this->db->insert('online_admission_staging', array(
                    'online_admission_id' => $stuDetails['id'],
                    'branch_id' => $branchID,
                    'staged_by' => get_loggedin_user_id(),
                    'staged_payload' => json_encode($post),
                    'status' => 1,
                    'staged_at' => date('Y-m-d H:i:s'),
                ));
                $stagingId = $this->db->insert_id();

                $this->db->where('id', $stuDetails['id']);
                $this->db->update('online_admission', array('status' => 4));

                audit_log('submit', 'online_admission_staging', $stagingId, null, array('online_admission_id' => $stuDetails['id'], 'first_name' => $post['first_name']));
                set_alert('success', translate('admission_submitted_for_approval'));
                $url = base_url('online_admission');
                $array = array('status' => 'success', 'url' => $url);
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'url' => '', 'error' => $error);
            }
            echo json_encode($array);
            exit();
        }
        $this->data['stuDetails'] = $stuDetails;
        $this->data['getBranch'] = $getBranch;
        $this->data['sub_page'] = 'online_admission/approved';
        $this->data['main_menu'] = 'admission';
        $this->data['register_id'] = $this->online_admission_model->regSerNumber($branchID);
        $this->data['title'] = translate('online_admission');
        $this->data['headerelements'] = array(
            'css' => array(
                'vendor/dropify/css/dropify.min.css',
            ),
            'js' => array(
                'js/student.js',
                'vendor/dropify/js/dropify.min.js',
            ),
        );
        $this->load->view('layout/index', $this->data);
    }

    // unique valid username verification is done here
    public function unique_username($username)
    {
        if ($this->input->post('student_id')) {
            $student_id = $this->input->post('student_id');
            $login_id = $this->app_lib->get_credential_id($student_id, 'student');
            $this->db->where_not_in('id', $login_id);
        }
        $this->db->where('username', $username);
        $query = $this->db->get('login_credential');
        if ($query->num_rows() > 0) {
            $this->form_validation->set_message("unique_username", translate('already_taken'));
            return false;
        } else {
            return true;
        }
    }

    /* unique valid guardian email address verification is done here */
    public function get_valid_guardian_username($username)
    {
        $this->db->where('username', $username);
        $query = $this->db->get('login_credential');
        if ($query->num_rows() > 0) {
            $this->form_validation->set_message("get_valid_guardian_username", translate('username_has_already_been_used'));
            return false;
        } else {
            return true;
        }
    }

    /* unique valid student roll verification is done here */
    public function unique_roll($roll)
    {
        if (empty($roll)) {
            return true;
        }
        $branchID = $this->application_model->get_branch_id();
        $schoolSettings = $this->online_admission_model->get('branch', array('id' => $branchID), true, false, 'unique_roll');
        $unique_roll = $schoolSettings['unique_roll'];
        if (empty($unique_roll) && $unique_roll == 0) {
            return true;
        }

        $classID = $this->input->post('class_id');
        $sectionID = $this->input->post('section_id');
        if ($this->uri->segment(3)) {
            $this->db->where_not_in('student_id', $this->uri->segment(3));
        }
        if ($unique_roll == 2) {
            $this->db->where('section_id', $sectionID);
        }
        $this->db->where(array('roll' => $roll, 'class_id' => $classID, 'branch_id' => $branchID));
        $q = $this->db->get('enroll')->num_rows();
        if ($q == 0) {
            return true;
        } else {
            $this->form_validation->set_message("unique_roll", translate('already_taken'));
            return false;
        }
    }


    /* unique valid register ID verification is done here */
    public function unique_registerid($register)
    {
        $branchID = $this->application_model->get_branch_id();
        if ($this->uri->segment(3)) {
            $this->db->where_not_in('id', $this->uri->segment(3));
        }
        $this->db->where('register_no', $register);
        $query = $this->db->get('student')->num_rows();
        if ($query == 0) {
            return true;
        } else {
            $this->form_validation->set_message("unique_registerid", translate('already_taken'));
            return false;
        }
    }

    public function download($id)
    {
        $this->load->helper('download');
        $filepath = "./uploads/online_ad_documents/" . $id;
        $data = file_get_contents($filepath);
        force_download($id, $data);
    }

    // staged-admission approval queue (checker) -- also lists a maker's own
    // pending/rejected staged reviews
    public function admission_approvals()
    {
        if (!get_permission('online_admission_approve', 'is_view') && !get_permission('online_admission', 'is_add')) {
            access_denied();
        }
        $this->data['title'] = translate('admission_approvals');
        $this->data['sub_page'] = 'online_admission/admission_approvals';
        $this->data['main_menu'] = 'admission';
        $this->data['stagedlist'] = $this->online_admission_model->getStagingList();
        $this->load->view('layout/index', $this->data);
    }

    // review modal: staged summary + live uniqueness re-check (approve/reject), or
    // a read-only view for anyone else
    public function getAdmissionApprovalDetails()
    {
        $id = $this->input->post('id');
        $this->data['staging_id'] = $id;
        $this->load->view('online_admission/admission_approval_modalView', $this->data);
    }

    // checker approves or rejects a staged admission. Approval is the point where
    // the real student/parent/enroll/login rows actually get created and
    // credentials emailed -- rejection creates nothing and emails nothing; the
    // maker can review again from scratch via the existing approved() action,
    // which already allows re-entry once status is back to declined (3), the same
    // path a straight decline() already used.
    public function admission_approval_save()
    {
        if ($_POST) {
            if (!get_permission('online_admission_approve', 'is_add')) {
                access_denied();
            }
            $id = $this->input->post('id');
            $status = $this->input->post('status');
            $comments = $this->input->post('comments');
            $staging = $this->online_admission_model->getStagingList(array('oas.id' => $id), true);
            if (empty($staging) || $staging['status'] != 1) {
                access_denied();
            }
            if ($staging['staged_by'] == get_loggedin_user_id()) {
                // self-approval is not allowed -- a different person must review
                access_denied();
            }

            if ($status == 2) {
                $branchID = $staging['branch_id'];
                $payload = json_decode($staging['staged_payload'], true);

                // check saas student add limit again -- time may have passed since staging
                if ($this->app_lib->isExistingAddon('saas')) {
                    if (!checkSaasLimit('student')) {
                        set_alert('error', translate('update_your_package'));
                        redirect(base_url('online_admission/admission_approvals'));
                    }
                }

                $conflicts = $this->online_admission_model->checkStagedUniqueness($payload, $branchID);
                if (!empty($conflicts)) {
                    set_alert('error', implode(' ', $conflicts) . ' ' . translate('please_ask_the_submitter_to_review_and_resubmit'));
                    redirect(base_url('online_admission/admission_approvals'));
                }

                $getBranch = $this->db->where('id', $branchID)->get('branch')->row_array();
                $studentData = $this->online_admission_model->finalizeSave($payload, $getBranch);
                $studentID = $studentData['student_id'];

                $arrayEnroll = array(
                    'student_id' => $studentID,
                    'class_id' => $payload['class_id'],
                    'section_id' => (isset($payload['section_id']) ? $payload['section_id'] : 0),
                    'roll' => (isset($payload['roll']) ? $payload['roll'] : 0),
                    'session_id' => $payload['year_id'],
                    'branch_id' => $branchID,
                );
                $this->db->insert('enroll', $arrayEnroll);

                $this->db->where('id', $staging['online_admission_id']);
                $this->db->update('online_admission', array('status' => 2));

                if (!empty($payload['custom_fields_student'])) {
                    saveCustomFields($payload['custom_fields_student'], $studentID);
                }

                $this->email_model->studentAdmission($studentData);
                $this->sms_model->send_sms($arrayEnroll, 1);

                $this->db->where('id', $id);
                $this->db->update('online_admission_staging', array(
                    'status' => 2,
                    'reviewed_by' => get_loggedin_user_id(),
                    'comments' => $comments,
                    'reviewed_at' => date('Y-m-d H:i:s'),
                ));
                audit_log('approve', 'online_admission_staging', $id, array('status' => 1), array('status' => 2, 'student_id' => $studentID));
                set_alert('success', translate('admission_has_been_approved'));
            } else {
                $this->db->where('id', $id);
                $this->db->update('online_admission_staging', array(
                    'status' => 3,
                    'reviewed_by' => get_loggedin_user_id(),
                    'comments' => $comments,
                    'reviewed_at' => date('Y-m-d H:i:s'),
                ));
                $this->db->where('id', $staging['online_admission_id']);
                $this->db->update('online_admission', array('status' => 3));
                audit_log('reject', 'online_admission_staging', $id, array('status' => 1), array('status' => 3, 'comments' => $comments));
                set_alert('success', translate('admission_has_been_rejected'));
            }
            redirect(base_url('online_admission/admission_approvals'));
        }
    }
}
