<?php
defined('BASEPATH') or exit('No direct script access allowed');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
require_once(APPPATH . 'third_party/phpmailer/autoload.php');

class Mailer
{
    private $CI;
    public function __construct()
    {
        $this->CI = &get_instance();
    }

    public function send($data = array(), $err = false)
    {
        $getConfig = $this->CI->db->get_where('email_config', array('branch_id' => $data['branch_id']))->row();
        if (!empty($getConfig)) {
            $school_name = get_global_setting('institute_name');

            if ($getConfig->protocol == 'veltrix') {
                $attachment = !empty($data['file']) ? array(
                    'content' => $data['file'],
                    'name'    => $data['file_name'] ?? 'attachment.pdf',
                    'mime'    => 'application/pdf',
                ) : null;

                $this->CI->load->library('veltrix');
                $sent = $this->CI->veltrix->sendEmailForBranch(
                    $data['branch_id'],
                    $data['recipient'],
                    $data['subject'],
                    $data['message'],
                    $getConfig->email,
                    $school_name,
                    $attachment
                );
                return $sent ? true : ($err == false ? false : 'Veltrix email send failed (insufficient wallet balance or gateway error).');
            }

            $mail = new PHPMailer();
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            if ($getConfig->protocol == 'smtp') {
                $smtp_encryption = $getConfig->smtp_encryption;
                $mail->isSMTP();
                $mail->SMTPDebug = SMTP::DEBUG_OFF;
                // Do not let an unavailable SMTP server hold AJAX requests for
                // PHPMailer's 300-second default timeout. Registration emails
                // are best-effort and must not leave the frontend processing.
                $mail->Timeout = 10;
                $mail->Host = trim($getConfig->smtp_host);
                $mail->Port = trim($getConfig->smtp_port);
                if (!empty($getConfig->smtp_encryption)) {
                    $mail->SMTPSecure =  $getConfig->smtp_encryption;
                }
                $mail->SMTPAuth = $getConfig->smtp_auth;
                $mail->Username = trim($getConfig->smtp_user);
                $mail->Password = trim($getConfig->smtp_pass);
            } else {
                $mail->isSendmail();
            }

            if (!empty($data['file'])) {
               $mail->addStringAttachment($data['file'], $data['file_name']);
            }

            $mail->setFrom($getConfig->email, $school_name);
            $mail->addReplyTo($getConfig->email, $school_name);
            $mail->addAddress($data['recipient']);
            $mail->Subject = $data['subject'];
            $mail->isHTML(true);
            $mail->Body = $data['message'];
            $mail->AltBody = $mail->html2text($data['message']);
            if ($mail->send()) {
                return true;
            } else {
                if ($err == false) {
                    return false;
                } else {
                    return $mail->ErrorInfo;
                }
            }
        } else {
            return false;
        }
    }
}
