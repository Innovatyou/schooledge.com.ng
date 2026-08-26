<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Records one audit trail entry. Never allowed to throw or otherwise
 * interrupt the caller -- a failed audit write is logged to the normal
 * PHP error log and swallowed, since the business action it accompanies
 * must always be allowed to complete.
 *
 * @param string      $action    e.g. 'submit', 'approve', 'reject', 'resubmit'
 * @param string      $table     table the action relates to, e.g. 'expense_requests'
 * @param int|string  $recordId  primary key of the affected row
 * @param array|null  $oldValues previous field values, if known
 * @param array|null  $newValues new/current field values, if known
 */
function audit_log($action, $table, $recordId, $oldValues = null, $newValues = null)
{
    try {
        $CI = &get_instance();
        $CI->load->helper('general');
        $data = array(
            'actor_user_id' => function_exists('get_loggedin_user_id') ? get_loggedin_user_id() : null,
            'actor_role_id' => function_exists('loggedin_role_id') ? loggedin_role_id() : null,
            'branch_id' => function_exists('get_loggedin_branch_id') ? get_loggedin_branch_id() : null,
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'old_values' => $oldValues !== null ? json_encode($oldValues) : null,
            'new_values' => $newValues !== null ? json_encode($newValues) : null,
            'ip_address' => $CI->input->ip_address(),
            'request_url' => uri_string(),
            'created_at' => date('Y-m-d H:i:s'),
        );
        $CI->db->insert('audit_log', $data);
    } catch (Exception $e) {
        log_message('error', 'audit_log write failed: ' . $e->getMessage());
    }
}

/**
 * Masks likely-sensitive fields (API keys/secrets/tokens/passwords) before an
 * array is handed to audit_log(), so credential values are never persisted
 * into the audit_log table itself -- only the fact that they changed is.
 */
function audit_redact($data)
{
    if (!is_array($data)) {
        return $data;
    }
    $redacted = array();
    foreach ($data as $key => $value) {
        if (preg_match('/key|secret|token|password|pin/i', $key)) {
            $redacted[$key] = empty($value) ? $value : '***redacted***';
        } else {
            $redacted[$key] = $value;
        }
    }
    return $redacted;
}

/**
 * HTML-escaped, pretty-printed rendering of an audit_log old_values/new_values
 * JSON column, safe to echo directly -- these columns can hold user-supplied
 * text (e.g. an expense description), so this must never be echoed raw.
 */
function format_audit_json($json)
{
    if (empty($json)) {
        return 'N/A';
    }
    $decoded = json_decode($json, true);
    if ($decoded === null) {
        return html_escape($json);
    }
    return html_escape(json_encode($decoded, JSON_PRETTY_PRINT));
}
