<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Unified topbar search. Replaces the old hardcoded "students only" search
 * (still kept at Student::search() for any existing bookmarks/links) with a
 * single box whose results depend on who's looking:
 *  - Super Admin has no single branch, so student/staff search is
 *    meaningless to them - they get registered schools instead.
 *  - Everyone else keeps student search, plus staff and a matching list of
 *    settings/admin pages so "fee settings" or "audit log" jumps straight
 *    to the right screen instead of requiring the sidebar to be hunted
 *    through.
 * Every result is filtered by a live get_permission()/is_superadmin_loggedin()
 * check at render time - config/search_index.php is only a candidate list,
 * never the actual access control.
 */
class Search extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('student_model');
        $this->load->config('search_index', false, true);
    }

    public function index()
    {
        $query = trim((string) ($this->input->post('search_text') ?: $this->input->get('q')));
        $this->data['query'] = $query;
        $this->data['schools'] = array();
        $this->data['students'] = array();
        $this->data['staff'] = array();
        $this->data['pages'] = array();

        if ($query !== '') {
            if (is_superadmin_loggedin()) {
                $this->data['schools'] = $this->searchSchools($query);
            } else {
                $this->data['students'] = $this->student_model->getSearchStudentList($query)->result();
                if (get_permission('employee', 'is_view')) {
                    $this->data['staff'] = $this->searchStaff($query);
                }
            }
            $this->data['pages'] = $this->searchPages($query);
        }

        $this->data['title'] = translate('searching_results');
        $this->data['sub_page'] = 'search/index';
        $this->data['main_menu'] = '';
        $this->load->view('layout/index', $this->data);
    }

    private function searchSchools($query)
    {
        // MAX() rather than a bare column: a branch can have more than one role=2
        // staff row, and this server runs with ONLY_FULL_GROUP_BY - any non-aggregated,
        // non-grouped-by column here would be a SQL error, not just a lint nitpick.
        $this->db->select('branch.id,branch.name,branch.school_name,branch.email,branch.mobileno,branch.city,branch.state,branch.address,MAX(login_credential.username) as admin_username,MAX(staff.email) as admin_email');
        $this->db->from('branch');
        $this->db->join('staff', 'staff.branch_id = branch.id', 'left');
        $this->db->join('login_credential', "login_credential.user_id = staff.id AND login_credential.role = 2", 'left');
        $this->db->group_start();
        $this->db->like('branch.name', $query);
        $this->db->or_like('branch.school_name', $query);
        $this->db->or_like('branch.email', $query);
        $this->db->or_like('branch.mobileno', $query);
        $this->db->or_like('branch.address', $query);
        $this->db->or_like('login_credential.username', $query);
        $this->db->group_end();
        $this->db->group_by('branch.id');
        $this->db->order_by('branch.id', 'desc');
        return $this->db->get()->result();
    }

    private function searchStaff($query)
    {
        $this->db->select('staff.id,staff.name,staff.designation,staff.email,staff.mobileno,login_credential.username,login_credential.role');
        $this->db->from('staff');
        // login_credential.user_id is only unique *within* a role - it also holds
        // student/parent ids, which collide with staff ids on the same number. The
        // role IN(...) here is what keeps this join scoped to staff accounts only.
        $this->db->join('login_credential', 'login_credential.user_id = staff.id AND login_credential.role IN (2,3,4,5,8)', 'left');
        $this->db->where('staff.branch_id', get_loggedin_branch_id());
        $this->db->group_start();
        $this->db->like('staff.name', $query);
        $this->db->or_like('staff.email', $query);
        $this->db->or_like('staff.mobileno', $query);
        $this->db->or_like('login_credential.username', $query);
        $this->db->group_end();
        $this->db->order_by('staff.id', 'desc');
        return $this->db->get()->result();
    }

    /** Matches the query against config/search_index.php's static page list, re-checking access live. */
    private function searchPages($query)
    {
        $index = $this->config->item('search_index');
        if (empty($index)) return array();
        $needle = mb_strtolower($query);
        $matches = array();
        foreach ($index as $entry) {
            if (!$this->pageAllowed($entry)) continue;
            $label = translate($entry['label']);
            $haystack = mb_strtolower($label . ' ' . $entry['label']);
            if (mb_strpos($haystack, $needle) === false) continue;
            $matches[] = array(
                'label' => $label,
                'url' => base_url($entry['url']),
                'section' => !empty($entry['section']) ? translate($entry['section']) : '',
            );
        }
        return $matches;
    }

    private function pageAllowed($entry)
    {
        $permission = isset($entry['permission']) ? $entry['permission'] : null;
        if ($permission === null) return true;
        if ($permission === 'superadmin_only') return is_superadmin_loggedin();
        if (is_superadmin_loggedin()) return true;
        // A single array('prefix','can') pair, or a list of such pairs meaning "any of these".
        if (isset($permission[0]) && is_string($permission[0])) $permission = array($permission);
        foreach ($permission as $pair) {
            if (!empty($pair[0]) && !empty($pair[1]) && get_permission($pair[0], $pair[1])) return true;
        }
        return false;
    }
}
