<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Settings_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function addPayment($data = '')
    {
		$this->db->where('branch_id', $data['branch_id']);
		$q = $this->db->get('payment_config');
		if ($q->num_rows() == 0) {
			$this->db->insert('payment_config', $data);
			audit_log('create', 'payment_config', $this->db->insert_id(), null, audit_redact($data));
		} else {
			$old = $q->row_array();
			$this->db->where('id', $q->row()->id);
			$this->db->update('payment_config', $data);
			audit_log('update', 'payment_config', $q->row()->id, audit_redact($old), audit_redact($data));
		}
    }
}