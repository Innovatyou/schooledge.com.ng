<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * Mobile announcements/calendar API backed by the existing `event` module (there is
 * no separate "notice" table - Event IS the announcement/calendar feature). Targeting
 * mirrors Event_model: audition 1 = everybody, 2 = specific classes, 3 = specific
 * class+section pairs, stored in `selected_list` as a JSON array of strings.
 */
class Events extends Api_Controller
{
    public function index()
    {
        $membership = $this->requireAuth();
        // Resolve enrollment (its own query) BEFORE starting the event query below -
        // CI3's query builder holds one pending chain per call, so interleaving two
        // unfinished chains silently merges their WHERE/JOIN clauses together.
        $audience = $this->resolveAudience($membership);

        $this->db->select('event.id,event.title,event.remark,event.type,event.start_date,event.end_date,event.image,event_types.name as type_name,event_types.icon')
            ->from('event')
            ->join('event_types', 'event_types.id = event.type', 'left')
            ->where('event.branch_id', $membership['branch_id'])
            ->where('event.status', 1);
        $this->applyAudienceFilter($audience);

        $from = $this->input->get('from');
        if ($from) $this->db->where('event.end_date >=', $from);

        $rows = $this->db->order_by('event.start_date', 'asc')->limit(100)->get()->result_array();
        $this->ok(array_map(array($this, 'payload'), $rows));
    }

    public function show($id)
    {
        $membership = $this->requireAuth();
        $audience = $this->resolveAudience($membership);

        $this->db->select('event.id,event.title,event.remark,event.type,event.start_date,event.end_date,event.image,event_types.name as type_name,event_types.icon')
            ->from('event')
            ->join('event_types', 'event_types.id = event.type', 'left')
            ->where(array('event.id' => (int)$id, 'event.branch_id' => $membership['branch_id'], 'event.status' => 1));
        $this->applyAudienceFilter($audience);
        $row = $this->db->get()->row_array();
        if (!$row) $this->fail('event_not_found', 'Event not found.', 404);
        $this->ok($this->payload($row));
    }

    /** Returns null for staff roles (no audience restriction), or the owned enrollment for parent/student. */
    private function resolveAudience(array $membership)
    {
        $roleId = (int)$membership['role_id'];
        if ($roleId !== 6 && $roleId !== 7) return null;
        return $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
    }

    private function applyAudienceFilter($enrollment)
    {
        if ($enrollment === null) return; // staff roles see every branch event
        $classNeedle = '"' . (int)$enrollment['class_id'] . '"';
        $sectionNeedle = '"' . (int)$enrollment['class_id'] . '-' . (int)$enrollment['section_id'] . '"';
        $this->db->group_start()
            ->where('event.audition', 1)
            ->or_where('event.type', 'holiday')
            ->or_group_start()->where('event.audition', 2)->like('event.selected_list', $classNeedle)->group_end()
            ->or_group_start()->where('event.audition', 3)->like('event.selected_list', $sectionNeedle)->group_end()
            ->group_end();
    }

    private function payload(array $row)
    {
        return array(
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'body' => $row['remark'],
            'type' => $row['type_name'] ?: ($row['type'] === 'holiday' ? 'Holiday' : null),
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'image_url' => !empty($row['image']) ? base_url('uploads/frontend/events/' . $row['image']) : null,
        );
    }
}
