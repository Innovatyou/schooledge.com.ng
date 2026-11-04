<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Everything the school celebrates, in one place.
 *
 * Worked out automatically: today's student and staff birthdays, staff work
 * anniversaries, and the top three in each class of the latest published
 * exam. Picked by the school (celebration table): best teachers, star
 * students and school-wide celebrations, each shown between two dates.
 *
 * forViewer() picks the ones that are about the signed-in person (or a
 * parent's children) plus the school-wide ones, for the celebration
 * animation on the dashboard and in the mobile app.
 */
class Celebration_model extends MY_Model
{
    public $types = array(
        'best_teacher' => 'Best Teacher',
        'star_student' => 'Star Student',
        'custom' => 'School Celebration',
    );
    public $themes = array('fireworks' => 'Fireworks', 'balloons' => 'Balloons', 'confetti' => 'Confetti');

    public function __construct()
    {
        parent::__construct();
    }

    public function birthdays($branchID)
    {
        $sessionID = get_session_id(); // before the query: it runs a query of its own
        $students = $this->db->select('s.id, s.first_name, s.last_name, s.photo, s.birthday, MAX(c.name) as class_name, MAX(se.name) as section_name', false)
            ->from('enroll as e')
            ->join('student as s', 's.id = e.student_id', 'inner')
            ->join('class as c', 'c.id = e.class_id', 'left')
            ->join('section as se', 'se.id = e.section_id', 'left')
            ->where(array('e.branch_id' => $branchID, 'e.session_id' => $sessionID, 'e.is_alumni' => 0))
            ->where('MONTH(s.birthday)', date('m'))->where('DAY(s.birthday)', date('d'))
            ->group_by('s.id')->order_by('s.first_name')->get()->result_array();
        $staff = $this->db->select('st.id, st.name, st.photo, st.birthday, MAX(d.name) as designation', false)
            ->from('staff as st')
            ->join('staff_designation as d', 'd.id = st.designation', 'left')
            ->join('login_credential as l', 'l.user_id = st.id and l.role not in (1, 6, 7)', 'inner')
            ->where('st.branch_id', $branchID)->where('l.active', 1)
            ->where('MONTH(st.birthday)', date('m'))->where('DAY(st.birthday)', date('d'))
            ->group_by('st.id')->order_by('st.name')->get()->result_array();

        $list = array();
        foreach ($students as $s) {
            $list[] = array(
                'kind' => 'student', 'id' => (int) $s['id'],
                'name' => trim($s['first_name'] . ' ' . $s['last_name']),
                'detail' => trim($s['class_name'] . ' ' . $s['section_name']),
                'age' => $this->age($s['birthday']),
                'photo' => get_image_url('student', $s['photo']),
            );
        }
        foreach ($staff as $s) {
            $list[] = array(
                'kind' => 'staff', 'id' => (int) $s['id'],
                'name' => $s['name'],
                'detail' => (string) $s['designation'],
                'age' => null, // staff ages are not shown
                'photo' => get_image_url('staff', $s['photo']),
            );
        }
        return $list;
    }

    /* staff who joined on this day in an earlier year */
    public function anniversaries($branchID)
    {
        $rows = $this->db->select('st.id, st.name, st.photo, st.joining_date, MAX(d.name) as designation', false)
            ->from('staff as st')
            ->join('staff_designation as d', 'd.id = st.designation', 'left')
            ->join('login_credential as l', 'l.user_id = st.id and l.role not in (1, 6, 7)', 'inner')
            ->where('st.branch_id', $branchID)->where('l.active', 1)
            ->where('MONTH(st.joining_date)', date('m'))->where('DAY(st.joining_date)', date('d'))
            ->where('YEAR(st.joining_date) <', date('Y'))
            ->group_by('st.id')->get()->result_array();
        $list = array();
        foreach ($rows as $s) {
            $list[] = array(
                'id' => (int) $s['id'], 'name' => $s['name'], 'detail' => (string) $s['designation'],
                'years' => (int) date('Y') - (int) substr($s['joining_date'], 0, 4),
                'photo' => get_image_url('staff', $s['photo']),
            );
        }
        return $list;
    }

    /* the top three in each class of the latest exam whose results are published and ranked */
    public function topPerformers($branchID)
    {
        $sessionID = get_session_id();
        $exam = $this->db->select('x.id, x.name')->from('exam as x')
            ->join('exam_rank as r', 'r.exam_id = x.id', 'inner')
            ->where(array('x.branch_id' => $branchID, 'x.session_id' => $sessionID, 'x.publish_result' => 1))
            ->where('r.rank >', 0)
            ->order_by('x.id', 'desc')->limit(1)->get()->row_array();
        if (empty($exam)) {
            return null;
        }
        $rows = $this->db->select('r.rank, s.id as student_id, s.first_name, s.last_name, s.photo, e.class_id, e.section_id, c.name as class_name, se.name as section_name')
            ->from('exam_rank as r')
            ->join('enroll as e', 'e.id = r.enroll_id', 'inner')
            ->join('student as s', 's.id = e.student_id', 'inner')
            ->join('class as c', 'c.id = e.class_id', 'left')
            ->join('section as se', 'se.id = e.section_id', 'left')
            ->where(array('r.exam_id' => $exam['id'], 'e.branch_id' => $branchID))
            ->where('r.rank >', 0)->where('r.rank <=', 3)
            ->order_by('c.id, se.id, r.rank')->get()->result_array();
        $classes = array();
        foreach ($rows as $r) {
            $k = $r['class_id'] . '-' . $r['section_id'];
            if (!isset($classes[$k])) {
                $classes[$k] = array('class' => trim($r['class_name'] . ' ' . $r['section_name']), 'students' => array());
            }
            $classes[$k]['students'][] = array(
                'rank' => (int) $r['rank'], 'id' => (int) $r['student_id'],
                'name' => trim($r['first_name'] . ' ' . $r['last_name']),
                'photo' => get_image_url('student', $r['photo']),
            );
        }
        return array('exam_id' => (int) $exam['id'], 'exam' => $exam['name'], 'classes' => array_values($classes));
    }

    /* best teachers, star students and school celebrations showing today (or all of them, for the manage list) */
    public function picks($branchID, $activeOnly = true)
    {
        $sessionID = (int) get_session_id();
        $this->db->select('c.*, st.name as staff_name, st.photo as staff_photo, d.name as designation, s.first_name, s.last_name, s.photo as student_photo, MAX(cl.name) as class_name', false)
            ->from('celebration as c')
            ->join('staff as st', 'st.id = c.staff_id', 'left')
            ->join('staff_designation as d', 'd.id = st.designation', 'left')
            ->join('student as s', 's.id = c.student_id', 'left')
            ->join('enroll as e', 'e.student_id = s.id and e.session_id = ' . $sessionID, 'left')
            ->join('class as cl', 'cl.id = e.class_id', 'left')
            ->where('c.branch_id', $branchID);
        if ($activeOnly) {
            $this->db->where('c.start_date <=', date('Y-m-d'))->where('c.end_date >=', date('Y-m-d'));
        }
        $rows = $this->db->group_by('c.id')->order_by('c.start_date', 'desc')->order_by('c.id', 'desc')->get()->result_array();
        $list = array();
        foreach ($rows as $r) {
            $item = array(
                'id' => (int) $r['id'], 'type' => $r['type'], 'title' => $r['title'], 'message' => (string) $r['message'],
                'theme' => $r['theme'], 'start_date' => $r['start_date'], 'end_date' => $r['end_date'],
                'name' => '', 'detail' => '', 'photo' => null, 'staff_id' => (int) $r['staff_id'], 'student_id' => (int) $r['student_id'],
            );
            if ($r['type'] == 'best_teacher') {
                $item['name'] = (string) $r['staff_name'];
                $item['detail'] = (string) $r['designation'];
                $item['photo'] = get_image_url('staff', $r['staff_photo']);
            } elseif ($r['type'] == 'star_student') {
                $item['name'] = trim($r['first_name'] . ' ' . $r['last_name']);
                $item['detail'] = (string) $r['class_name'];
                $item['photo'] = get_image_url('student', $r['student_photo']);
            }
            $list[] = $item;
        }
        return $list;
    }

    /* the whole wall for a school */
    public function wall($branchID)
    {
        $picks = $this->picks($branchID);
        return array(
            'birthdays' => $this->birthdays($branchID),
            'anniversaries' => $this->anniversaries($branchID),
            'top_performers' => $this->topPerformers($branchID),
            'best_teachers' => array_values(array_filter($picks, function ($p) { return $p['type'] == 'best_teacher'; })),
            'star_students' => array_values(array_filter($picks, function ($p) { return $p['type'] == 'star_student'; })),
            'events' => array_values(array_filter($picks, function ($p) { return $p['type'] == 'custom'; })),
        );
    }

    /*
     * Celebrations to play an animation for: the ones about this person (a parent gets their children's)
     * and the school-wide ones. Each has a stable key so the page/app shows it once.
     * $role: 'student' | 'parent' | 'staff'
     */
    public function forViewer($branchID, $role, $userID, $wall = null)
    {
        $wall = $wall ?: $this->wall($branchID);
        $userID = (int) $userID;
        $today = date('Y-m-d');
        $students = array();
        if ($role == 'student') {
            $students = array($userID => 'you');
        } elseif ($role == 'parent') {
            $sessionID = get_session_id();
            $rows = $this->db->select('s.id, s.first_name')->from('student as s')
                ->join('enroll as e', 'e.student_id = s.id', 'inner')
                ->where(array('s.parent_id' => $userID, 'e.branch_id' => $branchID, 'e.session_id' => $sessionID))
                ->get()->result_array();
            foreach ($rows as $r) {
                $students[(int) $r['id']] = $r['first_name'];
            }
        }
        $isMe = function ($kind, $id) use ($role, $userID, $students) {
            return $kind == 'staff' ? ($role == 'staff' && $id == $userID) : isset($students[$id]);
        };
        // "you" for the person themself, the child's name for a parent
        $who = function ($id) use ($students) {
            return isset($students[$id]) ? $students[$id] : 'you';
        };

        $items = array();
        foreach ($wall['birthdays'] as $b) {
            if ($isMe($b['kind'], $b['id'])) {
                $mine = $role != 'parent';
                $items[] = array(
                    'key' => 'birthday-' . $b['kind'] . $b['id'] . '-' . $today, 'theme' => 'balloons', 'emoji' => '🎂',
                    'title' => $mine ? 'Happy Birthday, ' . strtok($b['name'], ' ') . '!' : 'It\'s ' . $who($b['id']) . '\'s birthday!',
                    'message' => $mine ? 'Everyone at school wishes you a happy ' . ($b['age'] ? $this->ordinal($b['age']) . ' ' : '') . 'birthday and a wonderful day. 🎉'
                        : 'Wish ' . $who($b['id']) . ' a happy ' . ($b['age'] ? $this->ordinal($b['age']) . ' ' : '') . 'birthday from all of us at school!',
                    'photo' => $b['photo'],
                );
            }
        }
        foreach ($wall['anniversaries'] as $a) {
            if ($isMe('staff', $a['id'])) {
                $items[] = array(
                    'key' => 'anniversary-' . $a['id'] . '-' . $today, 'theme' => 'fireworks', 'emoji' => '🏅',
                    'title' => 'Happy ' . $a['years'] . '-year work anniversary!',
                    'message' => 'Thank you for ' . $a['years'] . ' year' . ($a['years'] == 1 ? '' : 's') . ' of teaching, caring and building our school.',
                    'photo' => $a['photo'],
                );
            }
        }
        if (!empty($wall['top_performers'])) {
            $places = array(1 => '1st', 2 => '2nd', 3 => '3rd');
            foreach ($wall['top_performers']['classes'] as $class) {
                foreach ($class['students'] as $s) {
                    if ($isMe('student', $s['id'])) {
                        $place = $places[$s['rank']];
                        $items[] = array(
                            'key' => 'exam-' . $wall['top_performers']['exam_id'] . '-' . $s['id'], 'theme' => 'fireworks',
                            'emoji' => $s['rank'] == 1 ? '🏆' : ($s['rank'] == 2 ? '🥈' : '🥉'),
                            'title' => $role == 'parent' ? $who($s['id']) . ' came ' . $place . ' in ' . $class['class'] . '!' : 'You came ' . $place . ' in ' . $class['class'] . '!',
                            'message' => 'Outstanding work in the ' . $wall['top_performers']['exam'] . '. ' . ($role == 'parent' ? 'We are so proud of ' . $who($s['id']) . '.' : 'Keep shining!'),
                            'photo' => $s['photo'],
                        );
                    }
                }
            }
        }
        foreach (array_merge($wall['best_teachers'], $wall['star_students']) as $p) {
            $kind = $p['type'] == 'best_teacher' ? 'staff' : 'student';
            $id = $kind == 'staff' ? $p['staff_id'] : $p['student_id'];
            if ($isMe($kind, $id)) {
                $items[] = array(
                    'key' => 'pick-' . $p['id'], 'theme' => $p['theme'], 'emoji' => $kind == 'staff' ? '🍎' : '⭐',
                    'title' => ($role == 'parent' ? $who($id) . ' is ' : 'You are ') . $p['title'] . '!',
                    'message' => $p['message'] !== '' ? $p['message'] : 'Congratulations from everyone at school!',
                    'photo' => $p['photo'],
                );
            }
        }
        foreach ($wall['events'] as $p) {
            $items[] = array(
                'key' => 'pick-' . $p['id'], 'theme' => $p['theme'], 'emoji' => '🎉',
                'title' => $p['title'], 'message' => $p['message'], 'photo' => null,
            );
        }
        return $items;
    }

    public function save($data, $branchID)
    {
        $row = array(
            'branch_id' => $branchID,
            'session_id' => get_session_id(),
            'type' => $data['type'],
            'title' => $data['title'],
            'message' => $data['message'],
            'staff_id' => $data['type'] == 'best_teacher' ? (int) $data['staff_id'] : null,
            'student_id' => $data['type'] == 'star_student' ? (int) $data['student_id'] : null,
            'theme' => isset($this->themes[$data['theme']]) ? $data['theme'] : 'fireworks',
            'start_date' => date('Y-m-d', strtotime($data['start_date'])),
            'end_date' => date('Y-m-d', strtotime($data['end_date'])),
            'created_by' => (int) get_loggedin_user_id(),
            'created_at' => date('Y-m-d H:i:s'),
        );
        $this->db->insert('celebration', $row);
        return $this->db->insert_id();
    }

    /* teachers and other staff of the school, for the Best Teacher picker */
    public function staffList($branchID)
    {
        $rows = $this->db->select('st.id, st.name, l.role')->from('staff as st')
            ->join('login_credential as l', 'l.user_id = st.id and l.role not in (1, 6, 7)', 'inner')
            ->where('st.branch_id', $branchID)->where('l.active', 1)
            ->order_by('l.role = 3', 'desc', false)->order_by('st.name')->get()->result_array();
        $list = array('' => translate('select'));
        foreach ($rows as $r) {
            $list[$r['id']] = $r['name'] . ($r['role'] == 3 ? '' : ' (staff)');
        }
        return $list;
    }

    private function age($birthday)
    {
        $year = (int) substr((string) $birthday, 0, 4);
        return $year > 1900 ? (int) date('Y') - $year : null;
    }

    private function ordinal($n)
    {
        $s = array('th', 'st', 'nd', 'rd');
        $v = $n % 100;
        return $n . (isset($s[($v - 20) % 10]) ? $s[($v - 20) % 10] : (isset($s[$v]) ? $s[$v] : $s[0]));
    }
}
