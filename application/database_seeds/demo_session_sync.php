<?php
/**
 * Keeps the demo school in the platform's current academic session.
 *
 * Students, classes, exams, fees and timetables are all filed under a session,
 * while parents are not. When the platform moves to a new session (for example
 * 2025-2026 -> 2026-2027) the demo would show 20 parents but no students, so
 * this moves every session-scoped row of the demo branch into the current one.
 * It does nothing when the demo is already in the current session, so it is
 * safe to run on every deploy.
 *
 * Returns a one-line summary.
 */
function sync_demo_session(mysqli $m, $branchId)
{
    $branchId = (int) $branchId;
    $current = (int) $m->query("SELECT session_id FROM global_settings WHERE id = 1")->fetch_assoc()['session_id'];
    $row = $m->query("SELECT session_id, COUNT(*) AS n FROM enroll WHERE branch_id = $branchId GROUP BY session_id ORDER BY n DESC LIMIT 1")->fetch_assoc();
    if (!$current || empty($row)) {
        return "demo branch $branchId has no students to move";
    }
    $old = (int) $row['session_id'];
    if ($old === $current) {
        return "demo branch $branchId is already in the current session";
    }

    // every table that files rows by both school and session
    $tables = array();
    $res = $m->query("SELECT c.TABLE_NAME AS t FROM information_schema.COLUMNS c
        JOIN information_schema.COLUMNS b ON b.TABLE_SCHEMA = c.TABLE_SCHEMA AND b.TABLE_NAME = c.TABLE_NAME AND b.COLUMN_NAME = 'branch_id'
        WHERE c.TABLE_SCHEMA = DATABASE() AND c.COLUMN_NAME = 'session_id'");
    while ($t = $res->fetch_assoc()) {
        $tables[] = $t['t'];
    }
    $moved = 0;
    foreach ($tables as $table) {
        $m->query("UPDATE `$table` SET session_id = $current WHERE branch_id = $branchId AND session_id = $old");
        $moved += $m->affected_rows;
    }

    // the fee group is named after the school year
    $years = $m->query("SELECT id, school_year FROM schoolyear WHERE id IN ($old, $current)");
    $label = array();
    while ($y = $years->fetch_assoc()) {
        $label[(int) $y['id']] = $y['school_year'];
    }
    if (isset($label[$old], $label[$current])) {
        $stmt = $m->prepare("UPDATE fee_groups SET name = REPLACE(name, ?, ?) WHERE branch_id = ?");
        $stmt->bind_param('ssi', $label[$old], $label[$current], $branchId);
        $stmt->execute();
    }
    return "moved demo branch $branchId from session $old to $current ($moved rows)";
}
