<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Api_Controller.php';

/**
 * Classmate 1:1 chat. Message content, conversation docs, and typing status
 * live in Firestore (written directly by the Flutter client, secured by
 * Security Rules keyed off a custom Firebase Auth token this controller
 * mints) - that's what gives instant delivery/typing without a PHP
 * round-trip. Everything that must be server-authoritative (who can start a
 * conversation, blocks, reports, voice-note file ownership, moderation
 * access) stays here in MySQL, exactly like the rest of this app.
 *
 * conversation_id is always the two participants' mobile_memberships.id,
 * sorted ascending and joined with "_" (e.g. "12_47") - deterministic, so
 * both sides resolve to the same Firestore document with no lookup, and this
 * controller can recover the two participants from the id alone without
 * touching Firestore (see ownVoiceNoteConversation()).
 */
class Chat extends Api_Controller
{
    public function token()
    {
        $membership = $this->requireAuth();
        if ((int)$membership['role_id'] !== 7) $this->fail('role_not_supported', 'Classmate chat is available to students only.', 403);
        // Minting a token is the gateway to every subsequent Firestore write
        // (messages, typing status) - none of which MySQL's blockIfDemoReadonly()
        // can reach once issued, so the read-only guarantee for a demo branch
        // has to be enforced HERE, not just on the block/unblock/report/
        // voice-note endpoints that do touch MySQL.
        $this->blockIfDemoReadonly($membership['branch_id']);
        $enrollment = $this->resolveOwnedEnrollment($membership, null);

        $this->load->library('firebase_auth_token');
        if (!$this->firebase_auth_token->isConfigured()) $this->fail('firebase_not_configured', 'Chat is not available yet.', 503);

        $classroomKey = $membership['branch_id'] . '_' . $enrollment['class_id'] . '_' . $enrollment['section_id'];
        $uid = $membership['branch_id'] . '-7-' . $membership['user_id'];
        $token = $this->firebase_auth_token->mint($uid, array(
            'classroomKey' => $classroomKey,
            'membershipId' => (string)$membership['id'],
        ));
        if (!$token) $this->fail('firebase_not_configured', 'Chat is not available yet.', 503);
        $this->ok(array('firebase_token' => $token, 'classroom_key' => $classroomKey, 'membership_id' => (int)$membership['id']));
    }

    public function classmates()
    {
        $membership = $this->requireAuth();
        if ((int)$membership['role_id'] !== 7) $this->fail('role_not_supported', 'Classmate chat is available to students only.', 403);
        $enrollment = $this->resolveOwnedEnrollment($membership, null);

        // Only classmates with an active mobile membership of their own can
        // actually be chatted with (there's no membership id to build a
        // conversation_id from otherwise) - an inner join naturally excludes
        // anyone who's never signed into the mobile app.
        $rows = $this->db->select('student.id as student_id, CONCAT_WS(" ",student.first_name,student.last_name) as name, mm.id as membership_id')
            ->from('enroll')
            ->join('student', 'student.id = enroll.student_id', 'inner')
            ->join('mobile_memberships as mm', "mm.user_id = student.id AND mm.branch_id = enroll.branch_id AND mm.role_id = 7 AND mm.status = 'active'", 'inner')
            ->where(array('enroll.branch_id' => $membership['branch_id'], 'enroll.class_id' => $enrollment['class_id'], 'enroll.section_id' => $enrollment['section_id'], 'enroll.is_alumni' => 0))
            ->where('student.id !=', $enrollment['student_id'])
            ->order_by('student.first_name', 'asc')->get()->result_array();

        $this->ok(array_map(function ($row) use ($membership) {
            return array(
                'membership_id' => (int)$row['membership_id'],
                'name' => $row['name'],
                'conversation_id' => $this->conversationId((int)$membership['id'], (int)$row['membership_id']),
            );
        }, $rows));
    }

    public function submitVoiceNote()
    {
        $membership = $this->requireAuth();
        if ((int)$membership['role_id'] !== 7) $this->fail('role_not_supported', 'Classmate chat is available to students only.', 403);
        $this->blockIfDemoReadonly($membership['branch_id']);

        $conversationId = trim((string)$this->input->post('conversation_id'));
        $this->assertParticipant($membership, $conversationId);

        if (empty($_FILES['file']['name'])) $this->fail('validation_error', 'An audio file is required.', 422);
        $uploadPath = FCPATH . 'uploads/attachments/chat_voice/';
        $config = array('upload_path' => $uploadPath, 'encrypt_name' => true, 'allowed_types' => 'mp3|m4a|aac|wav|webm|3gp|ogg');
        $this->load->library('upload', $config);
        $this->upload->initialize($config);
        if (!$this->upload->do_upload('file')) $this->fail('upload_failed', $this->upload->display_errors('', ''), 422);

        $enrollment = $this->resolveOwnedEnrollment($membership, null);
        $data = array(
            'branch_id' => $membership['branch_id'], 'membership_id' => $membership['id'], 'conversation_id' => $conversationId,
            'classroom_key' => $membership['branch_id'] . '_' . $enrollment['class_id'] . '_' . $enrollment['section_id'],
            'stored_file' => $this->upload->data('file_name'), 'original_name' => $this->upload->data('orig_name'),
            'duration_ms' => (int)$this->input->post('duration_ms') ?: null, 'created_at' => date('Y-m-d H:i:s'),
        );
        $this->db->insert('schooledge_chat_voice_notes', $data);
        $this->ok(array('id' => $this->db->insert_id()));
    }

    public function voiceNote($id)
    {
        $membership = $this->requireAuth();
        $note = $this->db->where(array('id' => (int)$id, 'branch_id' => $membership['branch_id']))->get('schooledge_chat_voice_notes')->row_array();
        if (!$note) $this->fail('voice_note_not_found', 'Voice note not found.', 404);
        // Either a participant in the conversation, or an oversight-authorized
        // teacher/admin for the classroom it was sent in (so a voice note
        // referenced from Chat::oversight() can actually be played back).
        $isParticipant = $this->isParticipant($membership, $note['conversation_id']);
        if (!$isParticipant && !$this->hasOversightAccess($membership, $note['classroom_key'])) {
            $this->fail('voice_note_not_found', 'Voice note not found.', 404);
        }

        $path = FCPATH . 'uploads/attachments/chat_voice/' . $note['stored_file'];
        if (!is_file($path)) $this->fail('voice_note_not_found', 'Voice note not found.', 404);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = array('mp3' => 'audio/mpeg', 'm4a' => 'audio/mp4', 'aac' => 'audio/aac', 'wav' => 'audio/wav', 'webm' => 'audio/webm', 'ogg' => 'audio/ogg', '3gp' => 'audio/3gpp');
        $this->output->set_content_type($mime[$extension] ?? 'application/octet-stream')
            ->set_header('Content-Disposition: inline; filename="voice-note.' . $extension . '"')
            ->set_header('Cache-Control: private, no-store')
            ->set_output(file_get_contents($path));
        $this->output->_display();
        exit;
    }

    public function block()
    {
        $membership = $this->requireAuth();
        if ((int)$membership['role_id'] !== 7) $this->fail('role_not_supported', 'Classmate chat is available to students only.', 403);
        $this->blockIfDemoReadonly($membership['branch_id']);
        $blocked = $this->activeStudentMembership($membership['branch_id'], (int)$this->input->post('membership_id'));

        $existing = $this->db->where(array('blocker_membership_id' => $membership['id'], 'blocked_membership_id' => $blocked['id']))->get('schooledge_chat_blocks')->row_array();
        if (!$existing) {
            $this->db->insert('schooledge_chat_blocks', array(
                'branch_id' => $membership['branch_id'], 'blocker_membership_id' => $membership['id'],
                'blocked_membership_id' => $blocked['id'], 'created_at' => date('Y-m-d H:i:s'),
            ));
            $this->mirrorBlockToFirestore($membership['id'], $blocked['id'], true);
            $this->logAudit('chat.block', $membership, 'membership', $blocked['id']);
        }
        $this->ok(array('blocked' => true));
    }

    public function unblock()
    {
        $membership = $this->requireAuth();
        if ((int)$membership['role_id'] !== 7) $this->fail('role_not_supported', 'Classmate chat is available to students only.', 403);
        $this->blockIfDemoReadonly($membership['branch_id']);
        $blockedMembershipId = (int)$this->input->post('membership_id');

        // Only the blocker can lift their own block - a blocked student must
        // never be able to unblock themselves.
        $this->db->where(array('blocker_membership_id' => $membership['id'], 'blocked_membership_id' => $blockedMembershipId))->delete('schooledge_chat_blocks');
        if ($this->db->affected_rows() > 0) {
            $this->mirrorBlockToFirestore($membership['id'], $blockedMembershipId, false);
            $this->logAudit('chat.unblock', $membership, 'membership', $blockedMembershipId);
        }
        $this->ok(array('blocked' => false));
    }

    public function report()
    {
        $membership = $this->requireAuth();
        if ((int)$membership['role_id'] !== 7) $this->fail('role_not_supported', 'Classmate chat is available to students only.', 403);
        $this->blockIfDemoReadonly($membership['branch_id']);

        $conversationId = trim((string)$this->input->post('conversation_id'));
        $this->assertParticipant($membership, $conversationId);
        $reportedMembershipId = (int)$this->input->post('reported_membership_id');
        $reported = $this->activeStudentMembership($membership['branch_id'], $reportedMembershipId);
        $excerpt = mb_substr(trim((string)$this->input->post('message_excerpt')), 0, 500);

        $this->db->insert('schooledge_chat_reports', array(
            'branch_id' => $membership['branch_id'], 'reporter_membership_id' => $membership['id'],
            'reported_membership_id' => $reported['id'], 'conversation_id' => $conversationId,
            'message_excerpt' => $excerpt !== '' ? $excerpt : null, 'status' => 'open', 'created_at' => date('Y-m-d H:i:s'),
        ));
        $reportId = $this->db->insert_id();
        $this->logAudit('chat.report', $membership, 'chat_report', $reportId);
        $this->notifyReportRecipients($membership, $reported);
        $this->ok(array('id' => $reportId));
    }

    /** Every class+section a teacher or admin can pick from to view oversight for - teachers get their own assigned classes, admins get every currently-enrolled class+section in the branch. */
    public function oversightClasses()
    {
        $membership = $this->requireAuth();
        $roleId = (int)$membership['role_id'];
        if ($roleId === 3) {
            $this->ok(array_map(function ($c) use ($membership) {
                return array(
                    'class_id' => $c['class_id'], 'section_id' => $c['section_id'],
                    'class_name' => $c['class_name'], 'section_name' => $c['section_name'],
                    'classroom_key' => $membership['branch_id'] . '_' . $c['class_id'] . '_' . $c['section_id'],
                );
            }, $this->teacherClasses($membership)));
            return;
        }
        if ($roleId === 1 || $roleId === 2) {
            if (!$this->hasPermission($roleId, 'chat_oversight', 'is_view')) $this->fail('forbidden', 'You do not have permission to view chat oversight.', 403);
            $rows = $this->db->distinct()->select('enroll.class_id, enroll.section_id, class.name as class_name, section.name as section_name')
                ->from('enroll')
                ->join('class', 'class.id = enroll.class_id', 'inner')
                ->join('section', 'section.id = enroll.section_id', 'inner')
                ->where(array('enroll.branch_id' => $membership['branch_id'], 'enroll.is_alumni' => 0))
                ->order_by('class.name', 'asc')->get()->result_array();
            $this->ok(array_map(function ($row) use ($membership) {
                return array(
                    'class_id' => (int)$row['class_id'], 'section_id' => (int)$row['section_id'],
                    'class_name' => $row['class_name'], 'section_name' => $row['section_name'],
                    'classroom_key' => $membership['branch_id'] . '_' . $row['class_id'] . '_' . $row['section_id'],
                );
            }, $rows));
            return;
        }
        $this->fail('role_not_supported', 'Chat oversight is available to teachers and admins.', 403);
    }

    /**
     * A classroom's recent chat activity, fetched live from Firestore on
     * demand - not a continuous MySQL copy, no Cloud Function involved. Every
     * view is itself audited (chat.oversight_viewed), independent of whether
     * anything was ever reported, so "full audit logging for admin/teachers"
     * holds without duplicating message content into this database.
     */
    public function oversight($classroomKey)
    {
        $membership = $this->requireAuth();
        $this->assertOversightAccess($membership, $classroomKey);

        $this->load->library('firestore_client');
        if (!$this->firestore_client->isConfigured()) $this->fail('firebase_not_configured', 'Chat is not available yet.', 503);

        // The REST client is intentionally minimal (no structured/:runQuery
        // support) - filtering by classroomKey happens here in PHP rather
        // than server-side in Firestore. Fine at this feature's expected
        // scale (occasional manual moderation, not a hot path); would need a
        // structured query if a branch's total conversation volume grows
        // large enough for a flat listDocuments() page to miss results.
        $conversations = array_values(array_filter(
            $this->firestore_client->listDocuments('conversations', 100, 'lastMessageAt'),
            function ($doc) use ($classroomKey) { return ($doc['fields']['classroomKey'] ?? null) === (string)$classroomKey; }
        ));
        $membershipIds = array();
        foreach ($conversations as &$conversation) {
            $conversation['messages'] = $this->firestore_client->listDocuments('conversations/' . $conversation['id'] . '/messages', 200, 'createdAt');
            foreach ((array)($conversation['fields']['participantIds'] ?? array()) as $participantId) $membershipIds[] = (int)$participantId;
        }
        unset($conversation);

        // Firestore only ever stores membership ids - resolve real names here
        // so oversight is actually readable, not a list of numbers.
        $names = array();
        if ($membershipIds) {
            $rows = $this->db->select('mm.id, CONCAT_WS(" ", student.first_name, student.last_name) as name')
                ->from('mobile_memberships as mm')
                ->join('student', 'student.id = mm.user_id', 'inner')
                ->where('mm.role_id', 7)
                ->where_in('mm.id', array_values(array_unique($membershipIds)))
                ->get()->result_array();
            foreach ($rows as $row) $names[(string)$row['id']] = $row['name'];
        }
        foreach ($conversations as &$conversation) {
            $conversation['participant_names'] = array_map(
                function ($participantId) use ($names) { return $names[(string)$participantId] ?? 'Unknown'; },
                (array)($conversation['fields']['participantIds'] ?? array())
            );
            foreach ($conversation['messages'] as &$message) {
                $message['sender_name'] = $names[(string)($message['fields']['senderId'] ?? '')] ?? 'Unknown';
            }
            unset($message);
        }
        unset($conversation);

        $this->logAudit('chat.oversight_viewed', $membership, 'classroom', $classroomKey);
        $this->ok(array('conversations' => $conversations));
    }

    private function conversationId($membershipIdA, $membershipIdB)
    {
        $ids = array((int)$membershipIdA, (int)$membershipIdB);
        sort($ids);
        return $ids[0] . '_' . $ids[1];
    }

    private function isParticipant(array $membership, $conversationId)
    {
        $parts = explode('_', (string)$conversationId);
        return count($parts) === 2 && in_array((string)$membership['id'], $parts, true);
    }

    /** Recovers the two participants straight from conversation_id's own "{min}_{max}" shape - no Firestore call needed to authorize. */
    private function assertParticipant(array $membership, $conversationId, $failCode = 'conversation_not_found')
    {
        if (!$this->isParticipant($membership, $conversationId)) $this->fail($failCode, 'Conversation not found.', 404);
    }

    private function hasOversightAccess(array $membership, $classroomKey)
    {
        $parts = explode('_', (string)$classroomKey);
        if (count($parts) !== 3) return false;
        list($branchId, $classId, $sectionId) = array_map('intval', $parts);
        if ($branchId !== (int)$membership['branch_id']) return false;

        $roleId = (int)$membership['role_id'];
        if ($roleId === 3) {
            foreach ($this->teacherClasses($membership) as $row) {
                if ($row['class_id'] === $classId && $row['section_id'] === $sectionId) return true;
            }
            return false;
        }
        if ($roleId === 1 || $roleId === 2) return $this->hasPermission($roleId, 'chat_oversight', 'is_view');
        return false;
    }

    private function assertOversightAccess(array $membership, $classroomKey)
    {
        $parts = explode('_', (string)$classroomKey);
        if (count($parts) !== 3) $this->fail('validation_error', 'Invalid classroom.', 422);
        if ((int)$parts[0] !== (int)$membership['branch_id']) $this->fail('classroom_not_found', 'Classroom not found.', 404);
        $roleId = (int)$membership['role_id'];
        if ($roleId !== 1 && $roleId !== 2 && $roleId !== 3) $this->fail('role_not_supported', 'Chat oversight is available to teachers and admins.', 403);
        if (!$this->hasOversightAccess($membership, $classroomKey)) {
            $failCode = $roleId === 3 ? 'class_not_assigned' : 'forbidden';
            $failMessage = $roleId === 3 ? 'You are not assigned to this class.' : 'You do not have permission to view chat oversight.';
            $this->fail($failCode, $failMessage, 403);
        }
    }

    private function activeStudentMembership($branchId, $membershipId)
    {
        $row = $this->db->where(array('id' => $membershipId, 'branch_id' => $branchId, 'role_id' => 7, 'status' => 'active'))->get('mobile_memberships')->row_array();
        if (!$row) $this->fail('membership_not_found', 'Student not found.', 404);
        return $row;
    }

    private function mirrorBlockToFirestore($blockerMembershipId, $blockedMembershipId, $blocked)
    {
        $this->load->library('firestore_client');
        if (!$this->firestore_client->isConfigured()) return; // degrades like every other Firebase-dependent call in this app until configured
        $pairKey = $this->conversationId($blockerMembershipId, $blockedMembershipId);
        if ($blocked) {
            $this->firestore_client->createDocument('blocks', $pairKey, array('blockedBy' => (string)$blockerMembershipId, 'createdAt' => date(DATE_ATOM)));
        } else {
            $this->firestore_client->deleteDocument('blocks/' . $pairKey);
        }
    }

    private function notifyReportRecipients(array $reporterMembership, array $reportedMembership)
    {
        $reportedEnrollment = $this->db->where(array('student_id' => $reportedMembership['user_id'], 'branch_id' => $reportedMembership['branch_id']))
            ->order_by('session_id', 'desc')->get('enroll')->row_array();
        if ($reportedEnrollment) {
            $teacherIds = $this->db->select('teacher_id')->where(array('branch_id' => $reportedMembership['branch_id'], 'class_id' => $reportedEnrollment['class_id'], 'section_id' => $reportedEnrollment['section_id']))
                ->get('teacher_allocation')->result_array();
            foreach ($teacherIds as $row) {
                $this->notifyIdentity($reportedMembership['branch_id'], '3-' . $row['teacher_id'], 'chat_report', 'A chat report was filed', 'A student in your class was reported in classmate chat.', array('report' => true));
            }
        }
        $admins = $this->db->where(array('branch_id' => $reportedMembership['branch_id'], 'status' => 'active'))->where_in('role_id', array(1, 2))->get('mobile_memberships')->result_array();
        foreach ($admins as $admin) {
            if (!$this->hasPermission((int)$admin['role_id'], 'chat_oversight', 'is_view')) continue;
            $this->notifyMembership($admin['id'], $reportedMembership['branch_id'], 'chat_report', 'A chat report was filed', 'A classmate chat report needs review.', array('report' => true));
        }
    }
}
