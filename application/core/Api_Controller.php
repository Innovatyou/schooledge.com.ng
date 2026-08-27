<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Api_Controller extends CI_Controller
{
    protected $apiMembership;
    protected $apiClaims;

    public function __construct()
    {
        parent::__construct();
        $this->output->set_content_type('application/json');
        $this->rateLimit();
    }

    protected function ok($data = null, $meta = array(), $status = 200)
    {
        return $this->respond(array('success'=>true, 'data'=>$data, 'meta'=>(object)$meta), $status);
    }

    protected function fail($code, $message, $status = 400, $details = null)
    {
        return $this->respond(array('success'=>false, 'error'=>array('code'=>$code, 'message'=>$message, 'details'=>$details)), $status);
    }

    protected function body()
    {
        $data = json_decode($this->input->raw_input_stream, true);
        return is_array($data) ? $data : $this->input->post();
    }

    protected function requireAuth()
    {
        $header = $this->input->get_request_header('Authorization', true);
        if (!preg_match('/^Bearer\s+(\S+)$/i', (string)$header, $match)) $this->fail('unauthorized', 'A bearer token is required.', 401);
        $claims = $this->decodeAccessToken($match[1]);
        if (!$claims) $this->fail('invalid_token', 'The access token is invalid or expired.', 401);
        $membership = $this->db->where(array('id'=>$claims['mid'], 'branch_id'=>$claims['bid'], 'status'=>'active'))->get('mobile_memberships')->row_array();
        if (!$membership) $this->fail('membership_inactive', 'The selected membership is no longer active.', 403);
        $this->apiClaims = $claims;
        $this->apiMembership = $membership;
        return $membership;
    }

    protected function issueAccessToken(array $membership)
    {
        $now = time();
        $claims = array('sub'=>(int)$membership['login_credential_id'], 'mid'=>(int)$membership['id'], 'uid'=>(int)$membership['user_id'], 'bid'=>(int)$membership['branch_id'], 'rid'=>(int)$membership['role_id'], 'iat'=>$now, 'exp'=>$now + 900);
        $payload = $this->b64(json_encode($claims));
        return $payload . '.' . $this->b64(hash_hmac('sha256', $payload, $this->tokenKey(), true));
    }

    private function decodeAccessToken($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2 || !hash_equals($this->b64(hash_hmac('sha256', $parts[0], $this->tokenKey(), true)), $parts[1])) return false;
        $claims = json_decode($this->unb64($parts[0]), true);
        return is_array($claims) && isset($claims['exp']) && $claims['exp'] >= time() ? $claims : false;
    }

    private function tokenKey()
    {
        $key = (string)config_item('encryption_key');
        if (strlen($key) < 16) $this->fail('server_misconfigured', 'API token signing is not configured.', 500);
        return hash('sha256', 'mobile-api|' . $key, true);
    }

    private function b64($value) { return rtrim(strtr(base64_encode($value), '+/', '-_'), '='); }
    private function unb64($value) { return base64_decode(strtr($value, '-_', '+/')); }

    private function rateLimit()
    {
        if (!$this->db->table_exists('mobile_rate_limits')) return;
        $bucket = floor(time() / 60); $key = hash('sha256', $this->input->ip_address() . '|' . $this->uri->uri_string() . '|' . $bucket);
        $row = $this->db->where('rate_key', $key)->get('mobile_rate_limits')->row_array();
        $limit = strpos($this->uri->uri_string(), '/auth/login') !== false ? 10 : 60;
        if ($row && (int)$row['request_count'] >= $limit) $this->fail('rate_limited', 'Too many requests. Try again shortly.', 429);
        if ($row) $this->db->set('request_count', 'request_count + 1', false)->where('rate_key', $key)->update('mobile_rate_limits');
        else $this->db->insert('mobile_rate_limits', array('rate_key'=>$key, 'window_started_at'=>date('Y-m-d H:i:s', $bucket * 60), 'request_count'=>1, 'expires_at'=>date('Y-m-d H:i:s', ($bucket + 2) * 60)));
    }

    private function respond($payload, $status)
    {
        $this->output->set_status_header($status)->set_output(json_encode($payload));
        $this->output->_display();
        exit;
    }

    /**
     * Resolve the enrollment a membership is allowed to act on, mirroring the
     * ownership rule every mobile endpoint needs: students may only see their own
     * enrollment, parents only a student whose `parent_id` matches their own user id.
     * Centralized here so every new controller enforces it the same way instead of
     * re-deriving it (and risking an IDOR) per file.
     */
    protected function resolveOwnedEnrollment(array $membership, $requestedStudentId)
    {
        $roleId = (int)$membership['role_id'];
        if ($roleId !== 6 && $roleId !== 7) $this->fail('role_not_supported', 'This resource is available to students and linked parents.', 403);
        $studentId = $roleId === 7 ? (int)$membership['user_id'] : (int)$requestedStudentId;
        if ($roleId === 6 && !$studentId) $this->fail('student_required', 'Select a linked student.', 422);
        $this->db->select('enroll.*,CONCAT_WS(" ",student.first_name,student.last_name) as student_name');
        $this->db->from('enroll')->join('student', 'student.id = enroll.student_id', 'inner');
        $this->db->where(array('enroll.student_id'=>$studentId, 'enroll.branch_id'=>$membership['branch_id']));
        if ($roleId === 6) $this->db->where('student.parent_id', $membership['user_id']);
        $row = $this->db->order_by('enroll.session_id', 'desc')->get()->row_array();
        if (!$row) $this->fail('student_not_found', 'No owned student enrollment exists in this school.', 404);
        return $row;
    }

    /**
     * The web app blocks every mutating action for the read-only demo branch via
     * is_demo_readonly(), which reads it off the CI session. The mobile API is
     * stateless (bearer tokens, no session), so it re-checks branch.is_demo directly
     * for the same guarantee: demo-school logins can browse but never write.
     */
    protected function blockIfDemoReadonly($branchId)
    {
        $branch = $this->db->select('is_demo')->where('id', $branchId)->get('branch')->row_array();
        if (!empty($branch['is_demo'])) $this->fail('demo_readonly', 'This is a read-only demo school. This action is disabled.', 403);
    }

    /**
     * A handful of legacy *_model methods (e.g. Fees_model::saveTransaction()) read
     * branch/role/user context off the CI session instead of taking it as a
     * parameter, because they were only ever called from session-authenticated web
     * controllers. The mobile API is stateless bearer-token auth with no real
     * session, so this stages the same userdata keys those methods expect, scoped
     * to the current request only (nothing persists back to the client, which
     * never sends a session cookie). Call it only when about to invoke legacy code
     * that needs it — new code here should keep taking branch/user explicitly.
     */
    protected function bridgeLegacySession(array $membership)
    {
        // Also bridge set_session_id: several *_model methods (e.g.
        // Fees_model::feeFineCalculation()) call get_session_id() as a live argument
        // while mid-way through building an unrelated query. On the session-backed
        // web app that helper never touches $this->db (has_userdata('set_session_id')
        // is already true from login), so the bug is dormant there; without this
        // bridge it falls through to a DB lookup that steps on the pending query
        // builder state and corrupts it. Setting it here keeps this code on the same
        // safe path real web sessions already use.
        $currentSessionId = $this->db->select('session_id')->where('id', 1)->get('global_settings')->row()->session_id ?? null;
        $this->session->set_userdata(array(
            'loggedin_role_id' => (int)$membership['role_id'],
            'loggedin_branch' => (int)$membership['branch_id'],
            'loggedin_id' => (int)$membership['login_credential_id'],
            'loggedin_userid' => (int)$membership['user_id'],
            'set_session_id' => $currentSessionId,
        ));
    }

    protected function logAudit($action, array $membership, $resourceType = null, $resourceId = null)
    {
        $this->db->insert('mobile_audit_log', array(
            'membership_id' => $membership['id'], 'branch_id' => $membership['branch_id'], 'action' => $action,
            'resource_type' => $resourceType, 'resource_id' => $resourceId,
            'ip_address' => $this->input->ip_address(), 'user_agent' => substr((string)$this->input->user_agent(), 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }
}
