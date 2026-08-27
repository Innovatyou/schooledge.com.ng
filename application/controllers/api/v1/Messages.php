<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * Mobile messaging API on the existing `message`/`message_reply` tables. This is
 * an email-style inbox (subject + body, with a reply chain), not a chat log -
 * identities are composite "{role_id}-{user_id}" strings (Communication_model.php's
 * own convention), with no `branch_id` column on `message` itself, so every query
 * here scopes by identity match rather than branch (matching existing web behaviour).
 */
class Messages extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('authentication_model');
    }

    public function index()
    {
        $membership = $this->requireAuth();
        $identity = $this->identityFor($membership);
        $rows = $this->db->where("(reciever = " . $this->db->escape($identity) . " AND trash_inbox = 0) OR (sender = " . $this->db->escape($identity) . " AND trash_sent = 0)", null, false)
            ->order_by('created_at', 'desc')->limit(100)->get('message')->result_array();

        $this->ok(array_map(function ($row) use ($identity) {
            $mine = $row['sender'] === $identity;
            $otherIdentity = $mine ? $row['reciever'] : $row['sender'];
            return array(
                'id' => (int)$row['id'], 'subject' => $row['subject'],
                'snippet' => mb_substr(strip_tags((string)$row['body']), 0, 140),
                'with' => $this->nameForIdentity($otherIdentity),
                'direction' => $mine ? 'sent' : 'received',
                'read' => (bool)$row['read_status'],
                'has_reply' => (bool)$row['reply_status'],
                'created_at' => $row['created_at'],
            );
        }, $rows));
    }

    public function show($id)
    {
        $membership = $this->requireAuth();
        $identity = $this->identityFor($membership);
        $message = $this->db->where('id', (int)$id)->get('message')->row_array();
        if (!$message || ($message['sender'] !== $identity && $message['reciever'] !== $identity)) $this->fail('message_not_found', 'Message not found.', 404);

        if ($message['reciever'] === $identity && !$message['read_status']) {
            $this->db->where('id', $message['id'])->update('message', array('read_status' => 1, 'updated_at' => date('Y-m-d H:i:s')));
        }

        $replies = $this->db->where('message_id', $message['id'])->order_by('created_at', 'asc')->get('message_reply')->result_array();
        $mine = $message['sender'] === $identity; // am I the one who started this thread?
        $this->ok(array(
            'id' => (int)$message['id'], 'subject' => $message['subject'], 'body' => $message['body'],
            'with' => $this->nameForIdentity($mine ? $message['reciever'] : $message['sender']),
            'direction' => $mine ? 'sent' : 'received',
            'attachment' => !empty($message['enc_name']) ? array('name' => $message['file_name']) : null,
            'created_at' => $message['created_at'],
            // message_reply.identity: 1 = written by the thread's original sender, 0 = by the receiver
            'replies' => array_map(function ($r) use ($mine) {
                $writtenByOriginalSender = (int)$r['identity'] === 1;
                return array(
                    'id' => (int)$r['id'], 'body' => $r['body'], 'mine' => $writtenByOriginalSender === $mine,
                    'created_at' => $r['created_at'], 'attachment' => !empty($r['enc_name']) ? array('name' => $r['file_name']) : null,
                );
            }, $replies),
        ));
    }

    public function compose()
    {
        $membership = $this->requireAuth();
        $this->blockIfDemoReadonly($membership['branch_id']);
        $roleId = (int)$this->input->post('role_id');
        $receiverId = (int)$this->input->post('receiver_id');
        $subject = trim((string)$this->input->post('subject'));
        $body = trim((string)$this->input->post('message'));
        if (!$roleId || !$receiverId || $subject === '' || $body === '') $this->fail('validation_error', 'role_id, receiver_id, subject and message are required.', 422);
        if (!$this->isValidContact($membership, $roleId, $receiverId)) $this->fail('contact_not_allowed', 'You cannot message this person.', 403);

        $data = array('sender' => $this->identityFor($membership), 'reciever' => $roleId . '-' . $receiverId, 'subject' => $subject, 'body' => $body, 'created_at' => date('Y-m-d H:i:s'));
        $this->attachIfPresent($data);
        $this->db->insert('message', $data);
        // capture insert_id() immediately - logAudit() below runs its own INSERT,
        // which would otherwise overwrite it before this response reads it back
        $messageId = $this->db->insert_id();
        $this->logAudit('message.send', $membership, 'message', $messageId);
        $this->notifyIdentity($membership['branch_id'], $data['reciever'], 'message', 'New message: ' . $subject, $body, array('message_id' => $messageId));
        $this->ok(array('id' => $messageId));
    }

    public function reply($id)
    {
        $membership = $this->requireAuth();
        $this->blockIfDemoReadonly($membership['branch_id']);
        $identity = $this->identityFor($membership);
        $message = $this->db->where('id', (int)$id)->get('message')->row_array();
        if (!$message || ($message['sender'] !== $identity && $message['reciever'] !== $identity)) $this->fail('message_not_found', 'Message not found.', 404);

        $body = trim((string)$this->input->post('message'));
        if ($body === '') $this->fail('validation_error', 'A message is required.', 422);

        $isSender = $message['sender'] === $identity;
        $this->db->where('id', $message['id'])->update('message', $isSender ? array('read_status' => 0) : array('reply_status' => 1));
        $data = array('message_id' => $message['id'], 'identity' => $isSender ? 1 : 0, 'body' => $body, 'created_at' => date('Y-m-d H:i:s'));
        $this->attachIfPresent($data);
        $this->db->insert('message_reply', $data);
        $this->logAudit('message.reply', $membership, 'message', $message['id']);
        $otherParty = $isSender ? $message['reciever'] : $message['sender'];
        $this->notifyIdentity($membership['branch_id'], $otherParty, 'message', 'New reply: ' . $message['subject'], $body, array('message_id' => $message['id']));
        $this->ok(array('replied' => true));
    }

    public function contacts()
    {
        $membership = $this->requireAuth();
        $this->ok($this->allowedContacts($membership));
    }

    /** Everyone can message school staff; a teacher can also message the students in their own classes. */
    private function allowedContacts(array $membership)
    {
        $contacts = array();
        $staffRows = $this->db->select('staff.id,staff.name,lc.role')
            ->from('staff')->join('login_credential as lc', 'lc.user_id = staff.id AND lc.role NOT IN (6,7)', 'inner')
            ->where(array('staff.branch_id' => $membership['branch_id'], 'lc.active' => 1))
            ->get()->result_array();
        foreach ($staffRows as $row) {
            if ((int)$row['role'] === (int)$membership['role_id'] && (int)$row['id'] === (int)$membership['user_id']) continue;
            $contacts[] = array('role_id' => (int)$row['role'], 'user_id' => (int)$row['id'], 'name' => $row['name']);
        }

        if ((int)$membership['role_id'] === 3) {
            $classPairs = array_unique(array_map(function ($c) { return $c['class_id'] . '-' . $c['section_id']; },
                array_merge(
                    $this->db->select('class_id,section_id')->where(array('teacher_id' => $membership['user_id'], 'branch_id' => $membership['branch_id']))->get('teacher_allocation')->result_array(),
                    $this->db->select('class_id,section_id')->where(array('teacher_id' => $membership['user_id'], 'branch_id' => $membership['branch_id']))->get('subject_assign')->result_array()
                )));
            foreach ($classPairs as $pair) {
                list($classId, $sectionId) = explode('-', $pair);
                $students = $this->db->select('student.id,CONCAT_WS(" ",student.first_name,student.last_name) as name')
                    ->from('enroll')->join('student', 'student.id = enroll.student_id', 'inner')
                    ->where(array('enroll.branch_id' => $membership['branch_id'], 'enroll.class_id' => (int)$classId, 'enroll.section_id' => (int)$sectionId))
                    ->get()->result_array();
                foreach ($students as $student) {
                    $contacts[] = array('role_id' => 7, 'user_id' => (int)$student['id'], 'name' => $student['name']);
                }
            }
        }
        return $contacts;
    }

    private function isValidContact(array $membership, $roleId, $userId)
    {
        foreach ($this->allowedContacts($membership) as $contact) {
            if ($contact['role_id'] === (int)$roleId && $contact['user_id'] === (int)$userId) return true;
        }
        return false;
    }

    private function identityFor(array $membership)
    {
        return $membership['role_id'] . '-' . $membership['user_id'];
    }

    private function nameForIdentity($identity)
    {
        $parts = explode('-', (string)$identity, 2);
        if (count($parts) !== 2) return 'Unknown';
        $user = $this->authentication_model->getUserNameByRoleID((int)$parts[0], (int)$parts[1]);
        return $user['name'] ?? 'Unknown';
    }

    private function attachIfPresent(array &$data)
    {
        if (empty($_FILES['file']['name'])) return;
        $config = array('upload_path' => FCPATH . 'uploads/attachments/', 'encrypt_name' => true, 'allowed_types' => '*');
        $this->load->library('upload', $config);
        $this->upload->initialize($config);
        if ($this->upload->do_upload('file')) {
            $data['file_name'] = $this->upload->data('orig_name');
            $data['enc_name'] = $this->upload->data('file_name');
        }
    }
}
