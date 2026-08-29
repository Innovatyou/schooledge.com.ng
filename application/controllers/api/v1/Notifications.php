<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * In-app notification inbox (mobile_notification_inbox). There is no push
 * delivery here (no Firebase project configured for this install yet) - rows
 * are written by other controllers via Api_Controller::notifyMembership()/
 * notifyIdentity() when something relevant happens (a message, a payment).
 */
class Notifications extends Api_Controller
{
    public function index()
    {
        $membership = $this->requireAuth();
        $rows = $this->db->where('membership_id', $membership['id'])
            ->order_by('created_at', 'desc')->limit(100)->get('mobile_notification_inbox')->result_array();
        $this->ok(array_map(function ($r) {
            return array(
                'id' => (int)$r['id'], 'category' => $r['category'], 'title' => $r['title'], 'body' => $r['body'],
                'data' => $r['data_json'] ? json_decode($r['data_json'], true) : null,
                'read' => $r['read_at'] !== null, 'created_at' => $r['created_at'],
            );
        }, $rows));
    }

    public function unread_count()
    {
        $membership = $this->requireAuth();
        $count = $this->db->where(array('membership_id' => $membership['id'], 'read_at' => null))->count_all_results('mobile_notification_inbox');
        $this->ok(array('count' => $count));
    }

    public function mark_read($id)
    {
        $membership = $this->requireAuth();
        $this->db->where(array('id' => (int)$id, 'membership_id' => $membership['id']))->update('mobile_notification_inbox', array('read_at' => date('Y-m-d H:i:s')));
        $this->ok(array('read' => true));
    }

    public function mark_all_read()
    {
        $membership = $this->requireAuth();
        $this->db->where(array('membership_id' => $membership['id'], 'read_at' => null))->update('mobile_notification_inbox', array('read_at' => date('Y-m-d H:i:s')));
        $this->ok(array('read' => true));
    }

    public function preferences()
    {
        $membership = $this->requireAuth();
        $rows = $this->db->where('membership_id', $membership['id'])->get('mobile_notification_preferences')->result_array();
        $saved = array();
        foreach ($rows as $row) $saved[$row['category']] = $row;
        $categories = array('message', 'homework', 'payment', 'announcement', 'live_class', 'safety');
        $this->ok(array_map(function ($category) use ($saved) {
            $row = $saved[$category] ?? null;
            return array(
                'category' => $category,
                'inbox_enabled' => $row ? (bool)$row['inbox_enabled'] : true,
                'push_enabled' => $row ? (bool)$row['push_enabled'] : true,
                'email_enabled' => $row ? (bool)$row['email_enabled'] : false,
            );
        }, $categories));
    }

    public function update_preference()
    {
        $membership = $this->requireAuth();
        $input = $this->body();
        $category = (string)($input['category'] ?? '');
        if ($category === '') $this->fail('validation_error', 'category is required.', 422);
        $data = array(
            'inbox_enabled' => !empty($input['inbox_enabled']) ? 1 : 0,
            'push_enabled' => !empty($input['push_enabled']) ? 1 : 0,
            'email_enabled' => !empty($input['email_enabled']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        );
        $existing = $this->db->where(array('membership_id' => $membership['id'], 'category' => $category))->get('mobile_notification_preferences')->row_array();
        if ($existing) {
            $this->db->where('id', $existing['id'])->update('mobile_notification_preferences', $data);
        } else {
            $data['membership_id'] = $membership['id'];
            $data['category'] = $category;
            $this->db->insert('mobile_notification_preferences', $data);
        }
        $this->ok(array('saved' => true));
    }
}
