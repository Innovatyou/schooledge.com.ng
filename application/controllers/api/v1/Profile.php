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
}
