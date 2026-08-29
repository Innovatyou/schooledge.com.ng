<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * v1 gamification rule set: points + badges for two signals the app already
 * tracks (attendance, on-time homework submission) - deliberately NOT a
 * generic rule engine, just this model directly implementing each check.
 * Distinct from Award.php/award table (a manual, one-off admin commendation)
 * - this is an automatic points ledger, the two never overlap.
 *
 * Every award goes through awardPoints()/awardBadge(), both idempotent via
 * INSERT IGNORE against a UNIQUE key, so a retried request (or, for
 * homework, an edge-case double-call) can never double-award.
 */
class Gamification_model extends CI_Model
{
    private $attendancePoints = 2;
    private $homeworkOnTimePoints = 10;
    private $streakMilestones = array(5 => 'streak_5', 10 => 'streak_10', 20 => 'streak_20');

    public function awardPoints($branchId, $enrollId, $points, $reasonCode, $reasonLabel, $relatedType, $relatedId)
    {
        $this->db->query('INSERT IGNORE INTO schooledge_points_ledger (branch_id,enroll_id,points,reason_code,reason_label,related_type,related_id,created_at) VALUES ('
            . (int)$branchId . ',' . (int)$enrollId . ',' . (int)$points . ','
            . $this->db->escape($reasonCode) . ',' . $this->db->escape($reasonLabel) . ','
            . $this->db->escape($relatedType) . ',' . (int)$relatedId . ','
            . $this->db->escape(date('Y-m-d H:i:s')) . ')');
    }

    private function awardBadge($branchId, $enrollId, $code)
    {
        $badge = $this->db->where('code', $code)->get('schooledge_badges')->row_array();
        if (!$badge) return;
        $this->db->query('INSERT IGNORE INTO schooledge_student_badges (branch_id,enroll_id,badge_id,awarded_at) VALUES ('
            . (int)$branchId . ',' . (int)$enrollId . ',' . (int)$badge['id'] . ','
            . $this->db->escape(date('Y-m-d H:i:s')) . ')');
    }

    /**
     * Called right after a student_attendance row is set to 'P' (from either
     * Attendance::capture() or Attendance::scan()). $attendanceId is that
     * row's id - the idempotency key, so re-marking the same day present
     * twice (already guarded upstream, but defense in depth) never
     * double-awards. Streak = how many of the most recent attendance
     * records in a row are 'P', scanning backwards until the first gap -
     * calendar-day gaps from weekends/holidays don't break it since there's
     * no attendance row on those days to begin with.
     */
    public function onAttendancePresent($branchId, $enrollId, $attendanceId)
    {
        $this->awardPoints($branchId, $enrollId, $this->attendancePoints, 'attendance_present', 'Present for the day', 'student_attendance', $attendanceId);

        $recent = $this->db->select('status')->where('enroll_id', $enrollId)
            ->order_by('date', 'desc')->limit(20)->get('student_attendance')->result_array();
        $streak = 0;
        foreach ($recent as $row) {
            if ($row['status'] !== 'P') break;
            $streak++;
        }
        if (isset($this->streakMilestones[$streak])) {
            $this->awardBadge($branchId, $enrollId, $this->streakMilestones[$streak]);
        }
    }

    /** Called right after a FIRST-time homework_submit insert (not a resubmit-edit). */
    public function onHomeworkSubmitted($branchId, $enrollId, $submitId, $submittedOnTime)
    {
        if (!$submittedOnTime) return;
        $this->awardPoints($branchId, $enrollId, $this->homeworkOnTimePoints, 'homework_ontime', 'Submitted homework on time', 'homework_submit', $submitId);
        $this->awardBadge($branchId, $enrollId, 'homework_ontime');
    }

    public function summaryForEnrollment($branchId, $enrollId, $classId, $sectionId)
    {
        $total = (int)($this->db->select_sum('points')->where(array('branch_id' => $branchId, 'enroll_id' => $enrollId))->get('schooledge_points_ledger')->row()->points ?? 0);
        $badges = $this->db->select('schooledge_badges.code,schooledge_badges.name,schooledge_badges.description,schooledge_badges.icon,schooledge_student_badges.awarded_at')
            ->from('schooledge_student_badges')
            ->join('schooledge_badges', 'schooledge_badges.id = schooledge_student_badges.badge_id', 'inner')
            ->where(array('schooledge_student_badges.branch_id' => $branchId, 'schooledge_student_badges.enroll_id' => $enrollId))
            ->order_by('schooledge_student_badges.awarded_at', 'desc')->get()->result_array();

        $rank = null;
        if ($classId && $sectionId) {
            $leaderboard = $this->leaderboard($branchId, $classId, $sectionId, 1000);
            foreach ($leaderboard as $index => $row) {
                if ((int)$row['enroll_id'] === (int)$enrollId) { $rank = $index + 1; break; }
            }
        }

        return array(
            'points_total' => $total,
            'rank_in_class' => $rank,
            'badges' => array_map(function ($b) {
                return array('code' => $b['code'], 'name' => $b['name'], 'description' => $b['description'], 'icon' => $b['icon'], 'awarded_at' => $b['awarded_at']);
            }, $badges),
        );
    }

    /** Hard-scoped to one class+section - never crosses class/school boundaries. */
    public function leaderboard($branchId, $classId, $sectionId, $limit = 20)
    {
        $rows = $this->db->select('enroll.id as enroll_id,CONCAT_WS(" ",student.first_name,student.last_name) as student_name,COALESCE(SUM(schooledge_points_ledger.points),0) as points_total,COUNT(DISTINCT schooledge_student_badges.id) as badge_count')
            ->from('enroll')
            ->join('student', 'student.id = enroll.student_id', 'inner')
            ->join('schooledge_points_ledger', 'schooledge_points_ledger.enroll_id = enroll.id', 'left')
            ->join('schooledge_student_badges', 'schooledge_student_badges.enroll_id = enroll.id', 'left')
            ->where(array('enroll.branch_id' => $branchId, 'enroll.class_id' => $classId, 'enroll.section_id' => $sectionId, 'enroll.is_alumni' => 0))
            ->group_by('enroll.id')
            ->order_by('points_total', 'desc')
            ->limit($limit)
            ->get()->result_array();
        return array_map(function ($r) {
            return array('enroll_id' => (int)$r['enroll_id'], 'student_name' => $r['student_name'], 'points_total' => (int)$r['points_total'], 'badge_count' => (int)$r['badge_count']);
        }, $rows);
    }
}
