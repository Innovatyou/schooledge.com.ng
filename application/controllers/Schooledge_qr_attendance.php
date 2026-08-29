<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Schooledge_qr_attendance extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!moduleIsEnabled('attendance') || !in_array((int)loggedin_role_id(), array(1, 2, 3), true)) access_denied();
    }

    public function index()
    {
        $this->data['branch_id'] = $this->branchId(false);
        $this->data['title'] = 'SchoolEdge QR Attendance';
        $this->data['sub_page'] = 'schooledge_qr_attendance/index';
        $this->data['main_menu'] = 'attendance';
        $this->load->view('layout/index', $this->data);
    }

    public function scan()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $branch = $this->branchId(true);
        $claims = $this->verifyToken($this->input->post('token'));
        if (!$claims) return $this->respond(false, null, 'This QR pass has expired or is invalid.', 422);
        if ((int)$claims['bid'] !== $branch) return $this->respond(false, null, 'This QR pass belongs to another school.', 403);
        $row = $this->db->select('enroll.id,enroll.class_id,enroll.section_id,enroll.roll,student.id student_id,CONCAT_WS(" ",student.first_name,student.last_name) name,class.name class_name,section.name section_name')
            ->from('enroll')->join('student', 'student.id=enroll.student_id')->join('class', 'class.id=enroll.class_id', 'left')->join('section', 'section.id=enroll.section_id', 'left')
            ->where(array('enroll.id'=>(int)$claims['eid'],'enroll.branch_id'=>$branch,'enroll.is_alumni'=>0))->get()->row_array();
        if (!$row) return $this->respond(false, null, 'Student is not actively enrolled.', 404);
        if ((int)loggedin_role_id() === 3 && !$this->teacherCanScan($row, $branch)) return $this->respond(false, null, 'You are not assigned to this student’s class.', 403);
        $where = array('enroll_id'=>$row['id'],'date'=>date('Y-m-d'),'branch_id'=>$branch);
        $attendance = $this->db->where($where)->get('student_attendance')->row_array();
        $duplicate = $attendance && $attendance['status'] === 'P';
        if ($attendance) { if (!$duplicate) $this->db->where('id',$attendance['id'])->update('student_attendance',array('status'=>'P','remark'=>null)); }
        else $this->db->insert('student_attendance',$where + array('status'=>'P','remark'=>null));
        if ($this->db->table_exists('schooledge_qr_attendance_events')) $this->db->insert('schooledge_qr_attendance_events',array('branch_id'=>$branch,'enroll_id'=>$row['id'],'actor_user_id'=>(int)get_loggedin_user_id(),'actor_role_id'=>(int)loggedin_role_id(),'result'=>$duplicate?'duplicate':'marked','scanned_at'=>date('Y-m-d H:i:s')));
        return $this->respond(true,array('already_marked'=>$duplicate,'date'=>date('Y-m-d'),'student'=>$row),null,200);
    }

    private function teacherCanScan($row,$branch)
    {
        $where=array('teacher_id'=>(int)get_loggedin_user_id(),'branch_id'=>$branch,'class_id'=>$row['class_id'],'section_id'=>$row['section_id']);
        return $this->db->where($where)->count_all_results('teacher_allocation') || $this->db->where($where)->count_all_results('subject_assign');
    }
    private function verifyToken($token)
    {
        $parts=explode('.',trim((string)$token)); if(count($parts)!==2)return null;
        $key=hash('sha256','mobile-attendance-qr|'.(string)config_item('encryption_key'),true);
        $sig=rtrim(strtr(base64_encode(hash_hmac('sha256',$parts[0],$key,true)),'+/','-_'),'=');
        if(!hash_equals($sig,$parts[1]))return null;
        $claims=json_decode(base64_decode(strtr($parts[0],'-_','+/')),true);
        return is_array($claims)&&!empty($claims['eid'])&&!empty($claims['bid'])&&!empty($claims['exp'])&&(int)$claims['exp']>=time()?$claims:null;
    }
    private function branchId($post){if(!is_superadmin_loggedin())return (int)get_loggedin_branch_id();$id=$post?$this->input->post('branch_id'):$this->input->get('branch_id');if(!$id){$r=$this->db->select('id')->where('status',1)->get('branch')->row_array();$id=$r?$r['id']:0;}return (int)$id;}
    private function respond($ok,$data,$error,$status){return $this->output->set_status_header($status)->set_content_type('application/json')->set_output(json_encode(array('success'=>$ok,'data'=>$data,'error'=>$error)));}
}
