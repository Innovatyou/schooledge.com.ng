<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * The school's celebrations wall plus the ones about the signed-in member
 * (a parent gets their children's), for the celebration pop-up in the app.
 * Same data as the web Celebrations page - see Celebration_model.
 */
class Celebrations extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('celebration_model');
    }

    public function index()
    {
        $membership = $this->requireAuth();
        $roleId = (int) $membership['role_id'];
        $role = $roleId === 7 ? 'student' : ($roleId === 6 ? 'parent' : 'staff');
        $wall = $this->celebration_model->wall($membership['branch_id']);
        $forMe = $this->celebration_model->forViewer($membership['branch_id'], $role, $membership['user_id'], $wall);

        // only what the app shows; ids of the people stay on the server
        $people = function ($list, $fields) {
            return array_map(function ($row) use ($fields) {
                return array_intersect_key($row, array_flip($fields));
            }, $list);
        };
        $this->ok(array(
            'for_me' => $forMe,
            'birthdays' => $people($wall['birthdays'], array('kind', 'name', 'detail', 'age', 'photo')),
            'anniversaries' => $people($wall['anniversaries'], array('name', 'detail', 'years', 'photo')),
            'top_performers' => $wall['top_performers'] ? array(
                'exam' => $wall['top_performers']['exam'],
                'classes' => array_map(function ($class) use ($people) {
                    return array('class' => $class['class'], 'students' => $people($class['students'], array('rank', 'name', 'photo')));
                }, $wall['top_performers']['classes']),
            ) : null,
            'best_teachers' => $people($wall['best_teachers'], array('title', 'message', 'name', 'detail', 'photo', 'theme')),
            'star_students' => $people($wall['star_students'], array('title', 'message', 'name', 'detail', 'photo', 'theme')),
            'events' => $people($wall['events'], array('title', 'message', 'theme', 'start_date', 'end_date')),
        ));
    }
}
