<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * Points/badges earned automatically from things the app already tracks
 * (see Gamification_model - attendance streaks, on-time homework). Distinct
 * from the admin-only Award.php/award table (a manual one-off commendation) -
 * this is the automatic ledger, student/parent-facing only.
 */
class Gamification extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('gamification_model');
    }

    public function me()
    {
        $membership = $this->requireAuth();
        $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
        $summary = $this->gamification_model->summaryForEnrollment(
            $membership['branch_id'], $enrollment['id'], $enrollment['class_id'], $enrollment['section_id']
        );
        $this->ok($summary);
    }

    /** Hard-scoped to the requester's own class+section - never crosses class/school boundaries. */
    public function leaderboard()
    {
        $membership = $this->requireAuth();
        $enrollment = $this->resolveOwnedEnrollment($membership, $this->input->get('student_id'));
        $rows = $this->gamification_model->leaderboard($membership['branch_id'], $enrollment['class_id'], $enrollment['section_id']);
        $this->ok(array('leaderboard' => array_map(function ($row, $index) {
            $row['rank'] = $index + 1;
            return $row;
        }, $rows, array_keys($rows))));
    }
}
