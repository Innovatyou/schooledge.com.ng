<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * Mobile self-service profile: view/edit contact info, change password, and
 * manage signed-in devices (mobile_devices + mobile_refresh_tokens - see
 * Mobile.php's newTokenPair(), which only creates a device row when the client
 * sends an installation_id).
 */
class Profile extends Api_Controller
{
    private $tables = array(6 => 'parent', 7 => 'student');

    public function __construct()
    {
        parent::__construct();
        $this->load->model('authentication_model');
    }

    public function show()
    {
        $membership = $this->requireAuth();
        $user = $this->authentication_model->getUserNameByRoleID($membership['role_id'], $membership['user_id']);
        $credential = $this->db->select('username')->where('id', $membership['login_credential_id'])->get('login_credential')->row();
        $this->ok(array(
            'name' => $user['name'] ?? null, 'email' => $user['email'] ?? null,
            'mobileno' => $user['mobileno'] ?? null, 'photo' => $user['photo'] ?? null,
            'photo_url' => get_image_url($this->photoRole($membership['role_id']), $user['photo'] ?? null),
            'username' => $credential->username,
        ));
    }

    public function update()
    {
        $membership = $this->requireAuth();
        $this->blockIfDemoReadonly($membership['branch_id']);
        $input = $this->body();
        $data = array();
        if (isset($input['email'])) {
            $email = trim((string)$input['email']);
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $this->fail('validation_error', 'Enter a valid email address.', 422, array('email' => 'invalid'));
            $data['email'] = $email;
        }
        if (isset($input['mobileno'])) $data['mobileno'] = trim((string)$input['mobileno']);
        if (!$data) $this->fail('validation_error', 'Nothing to update.', 422);

        $table = (int)$membership['role_id'] === 7
            ? 'student'
            : ((int)$membership['role_id'] === 6 ? 'parent' : 'staff');
        $this->db->where('id', $membership['user_id'])->update($table, $data);
        $this->logAudit('profile.update', $membership);
        $this->ok(array('updated' => true));
    }

    public function change_password()
    {
        $membership = $this->requireAuth();
        $this->blockIfDemoReadonly($membership['branch_id']);
        $input = $this->body();
        $current = (string)($input['current_password'] ?? '');
        $new = (string)($input['new_password'] ?? '');
        if ($current === '' || strlen($new) < 8) $this->fail('validation_error', 'Enter your current password and a new password of at least 8 characters.', 422);

        $credential = $this->db->where('id', $membership['login_credential_id'])->get('login_credential')->row();
        if (!password_verify($current, $credential->password)) $this->fail('invalid_password', 'Your current password is incorrect.', 422);

        $this->db->where('id', $membership['login_credential_id'])->update('login_credential', array('password' => password_hash($new, PASSWORD_DEFAULT)));
        $this->logAudit('profile.password_changed', $membership);
        $this->ok(array('changed' => true));
    }

    public function upload_photo()
    {
        $membership = $this->requireAuth();
        $this->blockIfDemoReadonly($membership['branch_id']);
        if (empty($_FILES['user_photo']) || $_FILES['user_photo']['error'] !== UPLOAD_ERR_OK) {
            $this->fail('photo_required', 'Choose a profile picture to upload.', 422);
        }
        $file = $_FILES['user_photo'];
        if ((int)$file['size'] > 2097152) $this->fail('photo_too_large', 'Profile pictures must be 2 MB or smaller.', 422);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = array('image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp');
        if (!isset($extensions[$mime])) $this->fail('invalid_photo', 'Use a JPG, PNG, or WebP image.', 422);

        $role = $this->photoRole($membership['role_id']);
        $table = $role === 'student' ? 'student' : ($role === 'parent' ? 'parent' : 'staff');
        $directory = FCPATH . 'uploads/images/' . $role . '/';
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) $this->fail('upload_failed', 'Profile picture storage is unavailable.', 500);
        $name = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . $name)) $this->fail('upload_failed', 'The profile picture could not be saved.', 500);

        $old = $this->db->select('photo')->where('id', $membership['user_id'])->get($table)->row_array();
        if (!$this->db->where('id', $membership['user_id'])->update($table, array('photo'=>$name))) {
            @unlink($directory . $name);
            $this->fail('upload_failed', 'The profile picture could not be updated.', 500);
        }
        if (!empty($old['photo']) && $old['photo'] !== 'defualt.png' && is_file($directory . basename($old['photo']))) {
            @unlink($directory . basename($old['photo']));
        }
        $this->logAudit('profile.photo_updated', $membership, $table, $membership['user_id']);
        $this->ok(array('photo'=>$name, 'photo_url'=>get_image_url($role, $name)));
    }

    public function sessions()
    {
        $membership = $this->requireAuth();
        $rows = $this->db->select('id,installation_id,platform,device_name,app_version,last_seen_at,created_at')
            ->where(array('membership_id' => $membership['id'], 'revoked_at' => null))
            ->order_by('last_seen_at', 'desc')->get('mobile_devices')->result_array();
        $currentInstallation = $this->apiClaims['iid'] ?? null;
        $this->ok(array_map(function ($r) use ($currentInstallation) {
            return array(
                'id' => (int)$r['id'], 'platform' => $r['platform'], 'device_name' => $r['device_name'],
                'app_version' => $r['app_version'], 'last_seen_at' => $r['last_seen_at'], 'created_at' => $r['created_at'],
                'is_current' => $currentInstallation !== null && $r['installation_id'] === $currentInstallation,
            );
        }, $rows));
    }

    public function revoke_session($id)
    {
        $membership = $this->requireAuth();
        $device = $this->db->where(array('id' => (int)$id, 'membership_id' => $membership['id']))->get('mobile_devices')->row_array();
        if (!$device) $this->fail('device_not_found', 'Device not found.', 404);
        $this->db->where('id', $device['id'])->update('mobile_devices', array('revoked_at' => date('Y-m-d H:i:s')));
        $this->db->where(array('membership_id' => $membership['id'], 'device_id' => $device['id']))->update('mobile_refresh_tokens', array('revoked_at' => date('Y-m-d H:i:s')));
        $this->logAudit('profile.session_revoked', $membership, 'device', $device['id']);
        $this->ok(array('revoked' => true));
    }

    /**
     * Stores this installation's FCM token so Api_Controller::notifyMembership()
     * can push to it. Identifies the device row by installation_id (the 'iid'
     * claim embedded in the access token at login, see Mobile::newTokenPair()) -
     * the same row Mobile.php already created/updated for this session, never a
     * new one, since one installation should only ever have one push token.
     */
    public function register_push_token()
    {
        $membership = $this->requireAuth();
        $installationId = $this->apiClaims['iid'] ?? null;
        if (!$installationId) $this->fail('installation_id_required', 'This session has no device installation to register. Sign in again.', 422);
        $input = $this->body();
        $token = trim((string)($input['push_token'] ?? ''));
        $enabled = !isset($input['push_enabled']) || !!$input['push_enabled'];
        $device = $this->db->where(array('membership_id' => $membership['id'], 'installation_id' => $installationId))->get('mobile_devices')->row_array();
        if (!$device) $this->fail('device_not_found', 'No device session found for this installation.', 404);
        $this->db->where('id', $device['id'])->update('mobile_devices', array(
            'push_token' => $token !== '' ? $token : null,
            'push_enabled' => ($token !== '' && $enabled) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ));
        $this->ok(array('registered' => true));
    }

    /**
     * The in-app digital ID card: the same fields the admin's printable ID
     * card template uses (see Card_manage_model::getStudent()), assembled
     * here as JSON via resolveOwnedEnrollment() instead of that model's
     * admin-scoped query, so a student/parent can only ever fetch their own
     * card. Deliberately carries no QR of its own - the card embeds the
     * existing rotating attendance pass (GET attendance/qr-token) instead of
     * a second, static code, since a static ID-card QR would reintroduce the
     * exact spoofing risk that token was built to prevent (see the docblock
     * on Attendance::qr_token()).
     */
    public function id_card()
    {
        $membership = $this->requireAuth();
        $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
        $row = $this->db->select('student.first_name,student.last_name,student.register_no,student.blood_group,student.photo,student_category.name as category_name,class.name as class_name,section.name as section_name,branch.school_name,branch.address,branch.mobileno')
            ->from('enroll')
            ->join('student', 'student.id = enroll.student_id', 'inner')
            ->join('class', 'class.id = enroll.class_id', 'left')
            ->join('section', 'section.id = enroll.section_id', 'left')
            ->join('student_category', 'student_category.id = student.category_id', 'left')
            ->join('branch', 'branch.id = enroll.branch_id', 'left')
            ->where('enroll.id', $enrollment['id'])
            ->get()->row_array();

        $this->ok(array(
            'enroll_id' => (int)$enrollment['id'],
            'name' => trim($row['first_name'] . ' ' . $row['last_name']),
            'photo_url' => get_image_url('student', $row['photo']),
            'class_name' => $row['class_name'],
            'section_name' => $row['section_name'],
            'roll' => $enrollment['roll'],
            'register_no' => $row['register_no'],
            'blood_group' => $row['blood_group'],
            'category' => $row['category_name'],
            'school' => array(
                'name' => $row['school_name'],
                'address' => $row['address'],
                'mobileno' => $row['mobileno'],
            ),
        ));
    }

    private function photoRole($roleId)
    {
        return (int)$roleId === 7 ? 'student' : ((int)$roleId === 6 ? 'parent' : 'staff');
    }
}
