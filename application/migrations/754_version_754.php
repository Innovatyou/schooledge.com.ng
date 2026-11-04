<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_754 extends CI_Migration
{
    public function up()
    {
        // Celebrations: best teachers, star students and school-wide celebrations picked by the school.
        // Birthdays, work anniversaries and top exam performers are worked out automatically (Celebration_model).
        // The live sites run application/database_seeds/apply_pending_migrations.php, which does the same.
        if (!$this->db->table_exists('celebration')) {
            $this->db->query("CREATE TABLE celebration (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, branch_id INT NOT NULL, session_id INT NOT NULL DEFAULT 0, type VARCHAR(20) NOT NULL, title VARCHAR(255) NOT NULL, message TEXT NULL, staff_id INT NULL, student_id INT NULL, theme VARCHAR(20) NOT NULL DEFAULT 'fireworks', start_date DATE NOT NULL, end_date DATE NOT NULL, created_by INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, KEY idx_branch_dates (branch_id, start_date, end_date)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        $existing = $this->db->where('prefix', 'celebrations')->get('permission')->row();
        if (empty($existing)) {
            $this->db->insert('permission', array('module_id' => 1, 'name' => 'Celebrations', 'prefix' => 'celebrations', 'show_view' => 1, 'show_add' => 1, 'show_edit' => 0, 'show_delete' => 1));
            $permissionId = $this->db->insert_id();
        } else {
            $permissionId = $existing->id;
        }
        // everyone can see the wall; admins pick best teachers and add celebrations
        $grants = array(2 => array(1, 1, 0, 1), 3 => array(1, 0, 0, 0), 4 => array(1, 0, 0, 0), 5 => array(1, 0, 0, 0), 6 => array(1, 0, 0, 0), 7 => array(1, 0, 0, 0), 8 => array(1, 0, 0, 0));
        foreach ($grants as $roleId => $grant) {
            if (!$this->db->where(array('role_id' => $roleId, 'permission_id' => $permissionId))->get('staff_privileges')->row()) {
                $this->db->insert('staff_privileges', array('role_id' => $roleId, 'permission_id' => $permissionId, 'is_view' => $grant[0], 'is_add' => $grant[1], 'is_edit' => $grant[2], 'is_delete' => $grant[3]));
            }
        }

        // the demo schools show a best teacher, a star student and a school celebration
        if ($this->db->field_exists('is_demo', 'branch')) {
            foreach ($this->db->where('is_demo', 1)->get('branch')->result() as $branch) {
                if ($this->db->where('branch_id', $branch->id)->count_all_results('celebration') > 0) {
                    continue;
                }
                $teacher = $this->db->select('staff.id')->from('staff')->join('login_credential as l', 'l.user_id = staff.id and l.role = 3', 'inner')->where('staff.branch_id', $branch->id)->order_by('staff.id')->limit(1)->get()->row();
                $student = $this->db->select('s.id')->from('enroll as e')->join('student as s', 's.id = e.student_id', 'inner')->where(array('e.branch_id' => $branch->id, 'e.is_alumni' => 0))->order_by('s.id')->limit(1)->get()->row();
                $sessionID = (int) $this->db->select('session_id')->where('id', 1)->get('global_settings')->row()->session_id;
                $rows = array();
                if ($teacher) {
                    $rows[] = array('type' => 'best_teacher', 'title' => 'Teacher of the Term', 'message' => 'For outstanding results in class and going the extra mile for every student.', 'staff_id' => $teacher->id, 'student_id' => null, 'theme' => 'fireworks');
                }
                if ($student) {
                    $rows[] = array('type' => 'star_student', 'title' => 'Star Student of the Week', 'message' => 'For kindness, leadership and helping classmates with their studies.', 'staff_id' => null, 'student_id' => $student->id, 'theme' => 'fireworks');
                }
                $rows[] = array('type' => 'custom', 'title' => "Founders' Day", 'message' => 'Happy Founders\' Day to our whole school family! Thank you for making our school a great place to learn.', 'staff_id' => null, 'student_id' => null, 'theme' => 'balloons');
                foreach ($rows as $row) {
                    $row['branch_id'] = $branch->id;
                    $row['session_id'] = $sessionID;
                    $row['start_date'] = date('Y-m-d');
                    $row['end_date'] = date('Y-m-d', strtotime('+1 year'));
                    $row['created_at'] = date('Y-m-d H:i:s');
                    $this->db->insert('celebration', $row);
                }
            }
        }
    }
}
