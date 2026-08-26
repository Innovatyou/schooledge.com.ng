<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Audit_log_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getAuditLogListDT()
    {
        $this->datatables->select('audit_log.id, audit_log.created_at, staff.name as actor_name, r.name as role_name, audit_log.action, audit_log.table_name, audit_log.record_id, audit_log.branch_id, b.name as branch_name');
        $this->datatables->from('audit_log');
        $this->datatables->join('staff', 'staff.id = audit_log.actor_user_id', 'left');
        $this->datatables->join('roles as r', 'r.id = audit_log.actor_role_id', 'left');
        $this->datatables->join('branch as b', 'b.id = audit_log.branch_id', 'left');
        if (!is_superadmin_loggedin()) {
            $this->datatables->where('audit_log.branch_id', get_loggedin_branch_id());
        }
        $this->datatables->search_value('staff.name,audit_log.table_name,audit_log.action');
        $this->datatables->column_order('audit_log.created_at,staff.name,r.name,audit_log.action,audit_log.table_name,audit_log.record_id,b.name');
        $this->datatables->order_by('audit_log.id', 'desc');
        return $this->datatables->generate();
    }

    // safely-escaped detail view for one audit_log row (old/new values may contain user-supplied text)
    public function getAuditLogDetail($id)
    {
        $this->db->select('audit_log.*, staff.name as actor_name');
        $this->db->from('audit_log');
        $this->db->join('staff', 'staff.id = audit_log.actor_user_id', 'left');
        $this->db->where('audit_log.id', $id);
        if (!is_superadmin_loggedin()) {
            $this->db->where('audit_log.branch_id', get_loggedin_branch_id());
        }
        return $this->db->get()->row_array();
    }
}
