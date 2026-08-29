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

    protected function issueAccessToken(array $membership, $installationId = null)
    {
        $now = time();
        $claims = array('sub'=>(int)$membership['login_credential_id'], 'mid'=>(int)$membership['id'], 'uid'=>(int)$membership['user_id'], 'bid'=>(int)$membership['branch_id'], 'rid'=>(int)$membership['role_id'], 'iat'=>$now, 'exp'=>$now + 900);
        if ($installationId) $claims['iid'] = $installationId;
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

    protected function logAudit($action, array $membership, $resourceType = null, $resourceId = null, $metadata = null)
    {
        $this->db->insert('mobile_audit_log', array(
            'membership_id' => $membership['id'], 'branch_id' => $membership['branch_id'], 'action' => $action,
            'resource_type' => $resourceType, 'resource_id' => $resourceId,
            'metadata_json' => $metadata !== null ? json_encode($metadata) : null,
            'ip_address' => $this->input->ip_address(), 'user_agent' => substr((string)$this->input->user_agent(), 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    /**
     * Drop a row in the recipient's notification inbox, if they have an active
     * mobile membership and haven't disabled this category, then best-effort push
     * it to every device that has opted in. Push delivery is fully optional - with
     * no Firebase service account file present (the default until one is dropped
     * in per mobile/docs/firebase-setup.md), Fcm_push::send() no-ops and only the
     * in-app inbox row is written, so this is always safe to call.
     */
    protected function notifyMembership($membershipId, $branchId, $category, $title, $body, $data = null, $bypassPreference = false)
    {
        $pref = $this->db->where(array('membership_id' => $membershipId, 'category' => $category))->get('mobile_notification_preferences')->row_array();
        if (!$bypassPreference && $pref && !$pref['inbox_enabled']) return;
        $this->db->insert('mobile_notification_inbox', array(
            'membership_id' => $membershipId, 'branch_id' => $branchId, 'category' => $category,
            'title' => $title, 'body' => $body, 'data_json' => $data !== null ? json_encode($data) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ));

        if (!$bypassPreference && $pref && !$pref['push_enabled']) return;
        $this->load->library('fcm_push');
        if (!$this->fcm_push->isConfigured()) return;
        $devices = $this->db->select('push_token')
            ->where(array('membership_id' => $membershipId, 'push_enabled' => 1, 'revoked_at' => null))
            ->where('push_token IS NOT NULL')
            ->get('mobile_devices')->result_array();
        foreach ($devices as $device) {
            $this->fcm_push->send($device['push_token'], $title, $body, array_merge(array('category' => $category), (array)$data));
        }
    }

    /**
     * Same as notifyMembership(), but resolves the recipient from a
     * "{role_id}-{user_id}" identity (e.g. a message's `reciever` column)
     * instead of a membership id directly. $bypassPreference is for SOS
     * alerts (Safety::submit()) - a muted-notifications viewer must not
     * silently miss a panic alert, unlike an ordinary "share my location"
     * notice which still respects it.
     */
    protected function notifyIdentity($branchId, $identity, $category, $title, $body, $data = null, $bypassPreference = false)
    {
        $parts = explode('-', (string)$identity, 2);
        if (count($parts) !== 2) return;
        $membership = $this->db->where(array('branch_id' => $branchId, 'role_id' => (int)$parts[0], 'user_id' => (int)$parts[1], 'status' => 'active'))->get('mobile_memberships')->row_array();
        if (!$membership) return; // this person has no active mobile membership to notify
        $this->notifyMembership($membership['id'], $branchId, $category, $title, $body, $data, $bypassPreference);
    }

    /**
     * Stateless equivalent of the web app's get_permission() (general_helper.php),
     * which reads loggedin_role_id off the session - re-derived here against
     * staff_privileges/permission directly since the mobile API has no session.
     * $can is one of 'is_view'/'is_add'/'is_edit'/'is_delete' and must only ever be
     * a literal the caller controls, never client input (it's interpolated as a
     * column name, not a bound value).
     */
    protected function hasPermission($roleId, $prefix, $can)
    {
        if ((int)$roleId === 1) return true;
        $row = $this->db->select('staff_privileges.' . $can . ' as allowed')
            ->from('staff_privileges')
            ->join('permission', 'permission.id = staff_privileges.permission_id', 'inner')
            ->where(array('staff_privileges.role_id' => (int)$roleId, 'permission.prefix' => $prefix))
            ->get()->row_array();
        return $row && (int)$row['allowed'] === 1;
    }

    /**
     * Every class+section a teacher actually teaches, via homeroom
     * (teacher_allocation) or any subject (subject_assign) - never an
     * arbitrary class a client claims. Shared by Attendance (capture/roster/
     * scan), Timetable (exam schedule), and Safety (alert visibility) so
     * "teachers only act on their own assigned classes" is enforced the same
     * way everywhere instead of re-derived per controller.
     */
    protected function teacherClasses(array $membership)
    {
        $rows = $this->db->select('teacher_allocation.class_id,teacher_allocation.section_id,class.name as class_name,section.name as section_name')
            ->from('teacher_allocation')
            ->join('class', 'class.id = teacher_allocation.class_id', 'inner')
            ->join('section', 'section.id = teacher_allocation.section_id', 'inner')
            ->where(array('teacher_allocation.teacher_id' => $membership['user_id'], 'teacher_allocation.branch_id' => $membership['branch_id']))
            ->get()->result_array();
        $rows = array_merge($rows, $this->db->select('subject_assign.class_id,subject_assign.section_id,class.name as class_name,section.name as section_name')
            ->from('subject_assign')
            ->join('class', 'class.id = subject_assign.class_id', 'inner')
            ->join('section', 'section.id = subject_assign.section_id', 'inner')
            ->where(array('subject_assign.teacher_id' => $membership['user_id'], 'subject_assign.branch_id' => $membership['branch_id']))
            ->get()->result_array());

        $seen = array();
        $unique = array();
        foreach ($rows as $row) {
            $key = $row['class_id'] . '-' . $row['section_id'];
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $unique[] = array('class_id' => (int)$row['class_id'], 'section_id' => (int)$row['section_id'], 'class_name' => $row['class_name'], 'section_name' => $row['section_name']);
        }
        return $unique;
    }

    protected function assertTeacherOwnsClass(array $membership, $classId, $sectionId)
    {
        foreach ($this->teacherClasses($membership) as $row) {
            if ($row['class_id'] === (int)$classId && $row['section_id'] === (int)$sectionId) return;
        }
        $this->fail('class_not_assigned', 'You are not assigned to this class.', 403);
    }

    /**
     * Every currently-enrolled student in one class+section, branch-scoped.
     * The canonical "who's in this class" query, previously duplicated ad
     * hoc in Messages::allowedContacts(), Attendance::roster(),
     * Gamification_model::leaderboard() and Safety's teacher-scope lookup -
     * centralized here so new callers (Messages::broadcast(), the classmate
     * chat controller) don't re-derive it a fifth time. Pass $excludeStudentId
     * to omit one student (e.g. the caller themself) from the result.
     */
    protected function classmatesOf($branchId, $classId, $sectionId, $excludeStudentId = null)
    {
        $this->db->select('student.id as student_id, CONCAT_WS(" ",student.first_name,student.last_name) as name')
            ->from('enroll')->join('student', 'student.id = enroll.student_id', 'inner')
            ->where(array('enroll.branch_id' => $branchId, 'enroll.class_id' => (int)$classId, 'enroll.section_id' => (int)$sectionId, 'enroll.is_alumni' => 0));
        if ($excludeStudentId !== null) $this->db->where('student.id !=', (int)$excludeStudentId);
        return $this->db->order_by('student.first_name', 'asc')->get()->result_array();
    }
}
