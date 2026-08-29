<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

class Mobile extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('authentication_model');
    }

    public function login()
    {
        $input = $this->body();
        if (empty($input['username']) || empty($input['password'])) $this->fail('validation_error', 'Username and password are required.', 422);
        try {
            $credential = $this->authentication_model->login_credential($input['username'], $input['password']);
            if (!$credential || !$credential->active) $this->fail('invalid_credentials', 'The supplied credentials are invalid.', 401);
            $memberships = $this->membershipsForCredential($credential);
            if (!$memberships) $this->fail('no_membership', 'No active school membership is available.', 403);
            $selected = $memberships[0];
            if (!empty($input['membership_id'])) foreach ($memberships as $membership) if ((int)$membership['id'] === (int)$input['membership_id']) $selected = $membership;
            if ($this->requiresTwoFactor($credential, $selected)) {
                $challenge = $this->beginTwoFactorChallenge($credential, $selected, $input['installation_id'] ?? null);
                $this->ok(array('requires_otp'=>true, 'challenge'=>$challenge), array(), 202);
            }
            $tokens = $this->newTokenPair($selected, null, $input['installation_id'] ?? null, $input['platform'] ?? null, $input['app_version'] ?? null);
            $this->audit('auth.login', $selected);
            $this->ok(array('tokens'=>$tokens, 'membership'=>$this->membershipPayload($selected), 'memberships'=>array_map(array($this, 'membershipPayload'), $memberships)));
        } catch (\Throwable $e) {
            // A raw PHP error/warning here would otherwise corrupt the JSON body
            // (or return an HTML error page) that the mobile client expects,
            // which the app can only surface as a dead-end "something went
            // wrong" - log the real cause server-side and still answer with a
            // well-formed error the client can parse and show sensibly.
            //
            // PHP's native error_log(), not CI3's log_message(): the latter
            // goes through Log::write_log()'s flock(LOCK_EX) (a *blocking*
            // exclusive lock, no LOCK_NB), which was found to hang every
            // request that hit it in this environment once log_threshold was
            // turned on (confirmed directly - the PHPUnit suite went from
            // ~60s to a 45-test timeout wall the moment logging was enabled).
            // error_log() has no such dependency and needs no config change.
            error_log('Mobile login failed for "' . $input['username'] . '": ' . $e->getMessage());
            $this->fail('server_error', 'An unexpected error occurred while signing you in. Please try again.', 500);
        }
    }

    public function verify_otp()
    {
        $input = $this->body();
        if (empty($input['challenge_token']) || empty($input['code'])) $this->fail('validation_error', 'Challenge token and verification code are required.', 422);
        $challenge = $this->activeChallenge($input['challenge_token']);
        if (!$challenge) $this->fail('invalid_challenge', 'The verification challenge is invalid or expired.', 401);
        if ((int)$challenge['attempts'] >= (int)$challenge['max_attempts']) $this->fail('challenge_locked', 'Too many incorrect verification attempts.', 429);
        $this->load->model('two_fa_model');
        if (!$this->two_fa_model->verify_authentication_code(trim((string)$input['code']), $challenge['login_credential_id'])) {
            $this->db->set('attempts', 'attempts + 1', false)->where('id', $challenge['id'])->update('mobile_auth_challenges');
            $remaining = max(0, (int)$challenge['max_attempts'] - (int)$challenge['attempts'] - 1);
            $this->fail('invalid_otp', 'The verification code is incorrect.', 422, array('attempts_remaining'=>$remaining));
        }
        $this->db->where('id', $challenge['id'])->update('mobile_auth_challenges', array('consumed_at'=>date('Y-m-d H:i:s')));
        $membership = $this->db->where(array('id'=>$challenge['membership_id'], 'status'=>'active'))->get('mobile_memberships')->row_array();
        if (!$membership) $this->fail('membership_inactive', 'The selected membership is no longer active.', 403);
        $tokens = $this->newTokenPair($membership, null, $challenge['installation_id'], $input['platform'] ?? null, $input['app_version'] ?? null);
        $rows = $this->db->where(array('login_credential_id'=>$challenge['login_credential_id'], 'status'=>'active'))->get('mobile_memberships')->result_array();
        $this->audit('auth.otp_verified', $membership);
        $this->ok(array('tokens'=>$tokens, 'membership'=>$this->membershipPayload($membership), 'memberships'=>array_map(array($this, 'membershipPayload'), $rows)));
    }

    public function resend_otp()
    {
        $input = $this->body();
        $challenge = !empty($input['challenge_token']) ? $this->activeChallenge($input['challenge_token']) : false;
        if (!$challenge) $this->fail('invalid_challenge', 'The verification challenge is invalid or expired.', 401);
        if ($challenge['challenge_type'] !== 'email') $this->fail('resend_not_supported', 'Authenticator codes cannot be resent.', 422);
        if ($challenge['last_sent_at'] && strtotime($challenge['last_sent_at']) > time() - 60) $this->fail('resend_throttled', 'Wait before requesting another code.', 429, array('retry_after'=>60 - (time() - strtotime($challenge['last_sent_at']))));
        $credential = $this->db->where('id', $challenge['login_credential_id'])->get('login_credential')->row();
        $membership = $this->db->where('id', $challenge['membership_id'])->get('mobile_memberships')->row_array();
        $this->sendEmailOtp($credential, $membership);
        $this->db->where('id', $challenge['id'])->update('mobile_auth_challenges', array('last_sent_at'=>date('Y-m-d H:i:s')));
        $this->ok(array('resent'=>true));
    }

    public function refresh()
    {
        $input = $this->body(); $plain = $input['refresh_token'] ?? '';
        $token = $this->db->where('token_hash', hash('sha256', $plain))->get('mobile_refresh_tokens')->row_array();
        if (!$token || $token['revoked_at'] || strtotime($token['expires_at']) <= time()) $this->fail('invalid_refresh_token', 'The refresh token is invalid or expired.', 401);
        $membership = $this->db->where(array('id'=>$token['membership_id'], 'status'=>'active'))->get('mobile_memberships')->row_array();
        if (!$membership) $this->fail('membership_inactive', 'The selected membership is no longer active.', 403);
        // Carry the installation_id forward from the token being rotated, so refreshing
        // keeps updating the SAME device row (last_seen_at) instead of losing the link.
        $installationId = $token['device_id'] ? $this->db->select('installation_id')->where('id', $token['device_id'])->get('mobile_devices')->row()->installation_id ?? null : null;
        $tokens = $this->newTokenPair($membership, $token['family_id'], $installationId);
        $replacement = $this->db->where('token_hash', hash('sha256', $tokens['refresh_token']))->get('mobile_refresh_tokens')->row_array();
        $this->db->where('id', $token['id'])->update('mobile_refresh_tokens', array('revoked_at'=>date('Y-m-d H:i:s'), 'last_used_at'=>date('Y-m-d H:i:s'), 'replaced_by_id'=>$replacement['id']));
        $this->ok(array('tokens'=>$tokens, 'membership'=>$this->membershipPayload($membership)));
    }

    public function logout()
    {
        $membership = $this->requireAuth(); $input = $this->body();
        if (!empty($input['refresh_token'])) $this->db->where(array('token_hash'=>hash('sha256', $input['refresh_token']), 'membership_id'=>$membership['id']))->update('mobile_refresh_tokens', array('revoked_at'=>date('Y-m-d H:i:s')));
        $this->audit('auth.logout', $membership); $this->ok(array('logged_out'=>true));
    }

    public function me()
    {
        $membership = $this->requireAuth();
        $credential = $this->db->where('id', $membership['login_credential_id'])->get('login_credential')->row();
        $user = $this->authentication_model->getUserNameByRoleID($membership['role_id'], $membership['user_id']);
        $this->ok(array(
            'id' => (int)$membership['user_id'], 'username' => $credential->username, 'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null, 'photo' => $user['photo'] ?? null,
            'photo_url' => get_image_url((int)$membership['role_id'] === 7 ? 'student' : ((int)$membership['role_id'] === 6 ? 'parent' : 'staff'), $user['photo'] ?? null),
            'membership' => $this->membershipPayload($membership),
            'children' => (int)$membership['role_id'] === 6 ? $this->linkedChildren($membership) : null,
        ));
    }

    /** A parent's own students within THIS branch, so the app can pick/show a child context for fees, events, and live classes. */
    private function linkedChildren(array $membership)
    {
        $rows = $this->db->select('student.id, CONCAT_WS(" ",student.first_name,student.last_name) as name')
            ->from('student')
            ->join('enroll', 'enroll.student_id = student.id', 'inner')
            ->where(array('student.parent_id' => $membership['user_id'], 'enroll.branch_id' => $membership['branch_id']))
            ->group_by('student.id')
            ->order_by('student.first_name', 'asc')
            ->get()->result_array();
        foreach ($rows as &$row) $row['id'] = (int)$row['id'];
        return $rows;
    }

    public function schools()
    {
        $query = trim((string)$this->input->get('q'));
        $this->db->select('branch.id,branch.school_name,branch.address,school_mobile_config.app_name,school_mobile_config.primary_color,school_mobile_config.logo_url')->from('branch')->join('school_mobile_config', 'school_mobile_config.branch_id = branch.id', 'left')->where('branch.status', 1);
        if ($query !== '') $this->db->group_start()->like('branch.school_name', $query)->or_like('branch.address', $query)->group_end();
        $rows = $this->db->limit(25)->get()->result_array();
        foreach ($rows as &$row) $row['id'] = (int)$row['id'];
        $this->ok($rows);
    }

    public function memberships()
    {
        $current = $this->requireAuth();
        $rows = $this->db->where(array('login_credential_id'=>$current['login_credential_id'], 'status'=>'active'))->get('mobile_memberships')->result_array();
        $this->ok(array_map(array($this, 'membershipPayload'), $rows));
    }

    /** Issues a fresh token pair for a DIFFERENT membership already owned by the same login - no password re-entry, since the credential was already verified for the current token. */
    public function switch_membership()
    {
        $current = $this->requireAuth();
        $input = $this->body();
        $targetId = (int)($input['membership_id'] ?? 0);
        if (!$targetId) $this->fail('validation_error', 'membership_id is required.', 422);
        $target = $this->db->where(array('id'=>$targetId, 'login_credential_id'=>$current['login_credential_id'], 'status'=>'active'))->get('mobile_memberships')->row_array();
        if (!$target) $this->fail('membership_not_found', 'That membership is not available for this account.', 404);
        $tokens = $this->newTokenPair($target, null, $this->apiClaims['iid'] ?? null);
        $this->audit('auth.switch_membership', $target);
        $this->ok(array('tokens'=>$tokens, 'membership'=>$this->membershipPayload($target)));
    }

    private function requiresTwoFactor($credential, $membership)
    {
        if (!$this->app_lib->isExistingAddon('two_fa') || !moduleIsEnabled('two_fa') || empty($credential->two_factor_authentication)) return false;
        $config = get2FA_config($membership['branch_id']);
        return !empty($config) && (int)$config->status === 1;
    }

    private function beginTwoFactorChallenge($credential, $membership, $installationId)
    {
        $type = $credential->two_fa_type === 'email' ? 'email' : 'app';
        $destination = null;
        if ($type === 'email') $destination = $this->sendEmailOtp($credential, $membership);
        $plain = rtrim(strtr(base64_encode(random_bytes(36)), '+/', '-_'), '=');
        $now = date('Y-m-d H:i:s');
        $this->db->where(array('login_credential_id'=>$credential->id, 'consumed_at'=>null))->update('mobile_auth_challenges', array('consumed_at'=>$now));
        $this->db->insert('mobile_auth_challenges', array(
            'challenge_hash'=>hash('sha256', $plain), 'login_credential_id'=>$credential->id, 'membership_id'=>$membership['id'], 'challenge_type'=>$type,
            'installation_id'=>$installationId, 'attempts'=>0, 'max_attempts'=>5, 'expires_at'=>date('Y-m-d H:i:s', time() + 600),
            'last_sent_at'=>$type === 'email' ? $now : null, 'created_ip'=>$this->input->ip_address(), 'created_at'=>$now,
        ));
        return array('token'=>$plain, 'type'=>$type, 'destination'=>$destination, 'expires_in'=>600, 'resend_after'=>$type === 'email' ? 60 : null);
    }

    private function sendEmailOtp($credential, $membership)
    {
        $this->load->model('two_fa_model');
        $user = $this->authentication_model->getUserNameByRoleID($credential->role, $credential->user_id);
        if (empty($user['email'])) $this->fail('otp_delivery_failed', 'No email address is configured for this account.', 503);
        $code = (string)random_int(100000, 999999);
        $sent = $this->two_fa_model->u_2FA_Email(array('branch_id'=>$membership['branch_id'], 'name'=>$user['name'], 'email'=>$user['email'], 'verification_code'=>$code));
        if (!$sent) $this->fail('otp_delivery_failed', 'The verification email could not be sent.', 503);
        $this->two_fa_model->add_email_token($credential->id, $code);
        $parts = explode('@', $user['email']);
        return substr($parts[0], 0, 2) . str_repeat('*', max(2, strlen($parts[0]) - 2)) . '@' . ($parts[1] ?? '');
    }

    private function activeChallenge($plain)
    {
        return $this->db->where('challenge_hash', hash('sha256', (string)$plain))->where('consumed_at IS NULL', null, false)->where('expires_at >=', date('Y-m-d H:i:s'))->get('mobile_auth_challenges')->row_array();
    }

    private function membershipsForCredential($credential)
    {
        $rows = $this->db->where(array('login_credential_id'=>$credential->id, 'status'=>'active'))->order_by('is_default', 'desc')->get('mobile_memberships')->result_array();
        if ($rows) return $rows;
        if ((int)$credential->role === 1) return array();
        $user = $this->authentication_model->getUserNameByRoleID($credential->role, $credential->user_id);
        if (empty($user['branch_id'])) return array();
        $branch = $this->db->where(array('id'=>$user['branch_id'], 'status'=>1))->get('branch')->row_array();
        if (!$branch) return array();
        $row = array('login_credential_id'=>$credential->id, 'user_id'=>$credential->user_id, 'branch_id'=>$user['branch_id'], 'role_id'=>$credential->role, 'status'=>'active', 'is_default'=>1, 'created_at'=>date('Y-m-d H:i:s'));
        $this->db->insert('mobile_memberships', $row); $row['id'] = $this->db->insert_id();
        return array($row);
    }

    private function newTokenPair(array $membership, $familyId = null, $installationId = null, $platform = null, $appVersion = null)
    {
        $plain = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        $familyId = $familyId ?: sprintf('%s-%s-%s-%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6)));
        $deviceId = null;
        if ($installationId) {
            $device = $this->db->where(array('membership_id'=>$membership['id'], 'installation_id'=>$installationId))->get('mobile_devices')->row_array();
            $now = date('Y-m-d H:i:s');
            if ($device) {
                $deviceId = $device['id'];
                $this->db->where('id', $deviceId)->update('mobile_devices', array_filter(array('platform'=>$platform, 'app_version'=>$appVersion, 'last_seen_at'=>$now), function ($v) { return $v !== null; }));
            } else {
                $this->db->insert('mobile_devices', array('membership_id'=>$membership['id'], 'installation_id'=>$installationId, 'platform'=>$platform ?: 'unknown', 'app_version'=>$appVersion, 'push_enabled'=>0, 'last_seen_at'=>$now, 'created_at'=>$now));
                $deviceId = $this->db->insert_id();
            }
        }
        $this->db->insert('mobile_refresh_tokens', array('membership_id'=>$membership['id'], 'token_hash'=>hash('sha256', $plain), 'family_id'=>$familyId, 'device_id'=>$deviceId, 'expires_at'=>date('Y-m-d H:i:s', time() + 2592000), 'created_at'=>date('Y-m-d H:i:s'), 'created_ip'=>$this->input->ip_address()));
        return array('access_token'=>$this->issueAccessToken($membership, $installationId), 'refresh_token'=>$plain, 'token_type'=>'Bearer', 'expires_in'=>900);
    }

    public function membershipPayload($membership)
    {
        $branch = $this->db->select('id,school_name,address,email,mobileno')->where('id', $membership['branch_id'])->get('branch')->row_array();
        $role = $this->db->select('id,name')->where('id', $membership['role_id'])->get('roles')->row_array();
        return array('id'=>(int)$membership['id'], 'status'=>$membership['status'], 'is_default'=>(bool)$membership['is_default'], 'school'=>$branch, 'role'=>$role);
    }

    private function audit($action, $membership)
    {
        $this->db->insert('mobile_audit_log', array('membership_id'=>$membership['id'], 'branch_id'=>$membership['branch_id'], 'action'=>$action, 'ip_address'=>$this->input->ip_address(), 'user_agent'=>substr((string)$this->input->user_agent(), 0, 255), 'created_at'=>date('Y-m-d H:i:s')));
    }
}
