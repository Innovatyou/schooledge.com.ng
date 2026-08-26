<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_717 extends CI_Migration
{
    public function up()
    {
        if ($this->db->table_exists('email_templates')) {
            if (!$this->db->field_exists('default_subject', 'email_templates')) {
                $this->dbforge->add_column('email_templates', array(
                    'default_subject' => array('type' => 'VARCHAR', 'constraint' => 255, 'null' => true),
                ));
            }
            if (!$this->db->field_exists('default_body', 'email_templates')) {
                $this->dbforge->add_column('email_templates', array(
                    'default_body' => array('type' => 'TEXT', 'null' => true),
                ));
            }

            $defaults = array(
                1 => array(
                    'default_subject' => "Your {institute_name} Account Has Been Created",
                    'default_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Your {institute_name} account has been created.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<tr><td style="background-color:#0c4f00; padding:26px 40px; text-align:center;">
<span style="color:#ffffff; font-size:19px; font-weight:800; letter-spacing:0.3px;">{institute_name}</span>
</td></tr>
<tr><td style="padding:36px 40px 4px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;"><tr><td style="width:48px; height:48px; border-radius:50%; background-color:#e7f3e4; text-align:center; vertical-align:middle; font-size:22px; color:#0c4f00; font-weight:800;">&#10003;</td></tr></table>
</td></tr>
<tr><td style="padding:0 40px 4px; text-align:center;"><p style="margin:0 0 4px; font-size:18px; font-weight:800; color:#1f2937;">Account Created</p></td></tr>
<tr><td style="padding:28px 40px 0;"><p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#1f2937;">Dear {name},</p><p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">An account has been created for you at <strong>{institute_name}</strong> as a <strong>{user_role}</strong>. You can log in using the credentials below.</p></td></tr>
<tr><td style="padding:0 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:18px 22px;">
<p style="margin:0 0 10px; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Login credentials</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Username</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{login_username}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Password</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{password}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Role</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{user_role}</td></tr>
</table>
</td></tr>
</table>
</td></tr>
<tr><td style="padding:24px 40px 0; text-align:center;">
<a href="{login_url}" style="display:inline-block; background-color:#0c4f00; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none; padding:13px 32px; border-radius:6px;">Log In to Your Account</a>
</td></tr>
<tr><td style="padding:20px 40px 28px;"><p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">For security, please change your password after your first login. If you did not expect this account, contact your school administrator.</p></td></tr>

<tr><td style="background-color:#f7f8fa; padding:22px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0; font-size:12px; color:#6b7280;">This is an automated message from <strong style="color:#1f2937;">{institute_name}</strong>. Please do not reply directly to this email.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
EOT,
                ),
                2 => array(
                    'default_subject' => "Reset Your Password – {institute_name}",
                    'default_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Reset your {institute_name} account password.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<tr><td style="background-color:#0c4f00; padding:26px 40px; text-align:center;">
<span style="color:#ffffff; font-size:19px; font-weight:800; letter-spacing:0.3px;">{institute_name}</span>
</td></tr>
<tr><td style="padding:36px 40px 4px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;"><tr><td style="width:48px; height:48px; border-radius:50%; background-color:#f7f8fa; text-align:center; vertical-align:middle; font-size:22px; color:#1f2937; font-weight:800;">&#128274;</td></tr></table>
</td></tr>
<tr><td style="padding:0 40px 4px; text-align:center;"><p style="margin:0 0 4px; font-size:18px; font-weight:800; color:#1f2937;">Reset Your Password</p></td></tr>
<tr><td style="padding:28px 40px 0;"><p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#1f2937;">Dear {name},</p><p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">We received a request to reset the password for the account linked to <strong>{email}</strong> (username: <strong>{username}</strong>) at <strong>{institute_name}</strong>.</p></td></tr>
<tr><td style="padding:24px 40px 0; text-align:center;">
<a href="{reset_url}" style="display:inline-block; background-color:#0c4f00; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none; padding:13px 32px; border-radius:6px;">Reset Password</a>
</td></tr>
<tr><td style="padding:20px 40px 28px;"><p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">This link will expire soon for your security. If you did not request a password reset, you can safely ignore this email &mdash; your password will not be changed.</p></td></tr>

<tr><td style="background-color:#f7f8fa; padding:22px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0; font-size:12px; color:#6b7280;">This is an automated message from <strong style="color:#1f2937;">{institute_name}</strong>. Please do not reply directly to this email.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
EOT,
                ),
                3 => array(
                    'default_subject' => "Your Password Has Been Changed – {institute_name}",
                    'default_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Your {institute_name} account password was changed.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<tr><td style="background-color:#0c4f00; padding:26px 40px; text-align:center;">
<span style="color:#ffffff; font-size:19px; font-weight:800; letter-spacing:0.3px;">{institute_name}</span>
</td></tr>
<tr><td style="padding:36px 40px 4px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;"><tr><td style="width:48px; height:48px; border-radius:50%; background-color:#e7f3e4; text-align:center; vertical-align:middle; font-size:22px; color:#0c4f00; font-weight:800;">&#10003;</td></tr></table>
</td></tr>
<tr><td style="padding:0 40px 4px; text-align:center;"><p style="margin:0 0 4px; font-size:18px; font-weight:800; color:#1f2937;">Password Changed</p></td></tr>
<tr><td style="padding:28px 40px 0;"><p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#1f2937;">Dear {name},</p><p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">This confirms that the password for your account (<strong>{email}</strong>) at <strong>{institute_name}</strong> was just changed.</p></td></tr>
<tr><td style="padding:0 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:18px 22px;">
<p style="margin:0 0 10px; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Details</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">New Password</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{password}</td></tr>
</table>
</td></tr>
</table>
</td></tr>
<tr><td style="padding:20px 40px 28px;"><p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">If you did not make this change, please contact your school administrator immediately to secure your account.</p></td></tr>

<tr><td style="background-color:#f7f8fa; padding:22px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0; font-size:12px; color:#6b7280;">This is an automated message from <strong style="color:#1f2937;">{institute_name}</strong>. Please do not reply directly to this email.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
EOT,
                ),
                4 => array(
                    'default_subject' => "You Have a New Message – {institute_name}",
                    'default_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">You have a new message at {institute_name}.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<tr><td style="background-color:#0c4f00; padding:26px 40px; text-align:center;">
<span style="color:#ffffff; font-size:19px; font-weight:800; letter-spacing:0.3px;">{institute_name}</span>
</td></tr>
<tr><td style="padding:36px 40px 4px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;"><tr><td style="width:48px; height:48px; border-radius:50%; background-color:#f7f8fa; text-align:center; vertical-align:middle; font-size:22px; color:#1f2937; font-weight:800;">&#9993;</td></tr></table>
</td></tr>
<tr><td style="padding:0 40px 4px; text-align:center;"><p style="margin:0 0 4px; font-size:18px; font-weight:800; color:#1f2937;">New Message</p></td></tr>
<tr><td style="padding:28px 40px 0;"><p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#1f2937;">Dear {recipient},</p><p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">You have received a new message at <strong>{institute_name}</strong>:</p></td></tr>
<tr><td style="padding:0 40px 0;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;"><tr><td style="padding:16px 20px; font-size:14px; color:#374151; font-style:italic;">&ldquo;{message}&rdquo;</td></tr></table></td></tr>
<tr><td style="padding:24px 40px 0; text-align:center;">
<a href="{message_url}" style="display:inline-block; background-color:#0c4f00; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none; padding:13px 32px; border-radius:6px;">View Message</a>
</td></tr>
<tr><td style="padding:20px 40px 28px;"><p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">Log in to your dashboard to read and reply to this message.</p></td></tr>

<tr><td style="background-color:#f7f8fa; padding:22px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0; font-size:12px; color:#6b7280;">This is an automated message from <strong style="color:#1f2937;">{institute_name}</strong>. Please do not reply directly to this email.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
EOT,
                ),
                5 => array(
                    'default_subject' => "Your Payslip for {month_year} is Ready – {institute_name}",
                    'default_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Your payslip for {month_year} is ready.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<tr><td style="background-color:#0c4f00; padding:26px 40px; text-align:center;">
<span style="color:#ffffff; font-size:19px; font-weight:800; letter-spacing:0.3px;">{institute_name}</span>
</td></tr>
<tr><td style="padding:36px 40px 4px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;"><tr><td style="width:48px; height:48px; border-radius:50%; background-color:#f7f8fa; text-align:center; vertical-align:middle; font-size:22px; color:#1f2937; font-weight:800;">&#128196;</td></tr></table>
</td></tr>
<tr><td style="padding:0 40px 4px; text-align:center;"><p style="margin:0 0 4px; font-size:18px; font-weight:800; color:#1f2937;">Payslip Available</p></td></tr>
<tr><td style="padding:28px 40px 0;"><p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#1f2937;">Dear {username},</p><p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">Your payslip for <strong>{month_year}</strong> has been generated and is ready for you to view.</p></td></tr>
<tr><td style="padding:24px 40px 0; text-align:center;">
<a href="{payslip_url}" style="display:inline-block; background-color:#0c4f00; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none; padding:13px 32px; border-radius:6px;">View Payslip</a>
</td></tr>
<tr><td style="padding:20px 40px 28px;"><p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">If you have any questions about your payslip, please contact your school&rsquo;s accounts office.</p></td></tr>

<tr><td style="background-color:#f7f8fa; padding:22px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0; font-size:12px; color:#6b7280;">This is an automated message from <strong style="color:#1f2937;">{institute_name}</strong>. Please do not reply directly to this email.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
EOT,
                ),
                6 => array(
                    'default_subject' => "Congratulations – You’ve Received an Award!",
                    'default_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">You have received an award from {institute_name}.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<tr><td style="background-color:#0c4f00; padding:26px 40px; text-align:center;">
<span style="color:#ffffff; font-size:19px; font-weight:800; letter-spacing:0.3px;">{institute_name}</span>
</td></tr>
<tr><td style="padding:36px 40px 4px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;"><tr><td style="width:48px; height:48px; border-radius:50%; background-color:#e7f3e4; text-align:center; vertical-align:middle; font-size:22px; color:#0c4f00; font-weight:800;">&#9733;</td></tr></table>
</td></tr>
<tr><td style="padding:0 40px 4px; text-align:center;"><p style="margin:0 0 4px; font-size:18px; font-weight:800; color:#1f2937;">Congratulations, {winner_name}!</p></td></tr>
<tr><td style="padding:28px 40px 0;"><p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#1f2937;">Dear {winner_name},</p><p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">We are delighted to inform you that you have received the following award from <strong>{institute_name}</strong>.</p></td></tr>
<tr><td style="padding:0 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:18px 22px;">
<p style="margin:0 0 10px; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Award details</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Award</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{award_name}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Gift</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{gift_item}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Reason</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{award_reason}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Date</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{given_date}</td></tr>
</table>
</td></tr>
</table>
</td></tr>
<tr><td style="padding:20px 40px 28px;"><p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">Congratulations once again on this well-deserved recognition!</p></td></tr>

<tr><td style="background-color:#f7f8fa; padding:22px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0; font-size:12px; color:#6b7280;">This is an automated message from <strong style="color:#1f2937;">{institute_name}</strong>. Please do not reply directly to this email.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
EOT,
                ),
                7 => array(
                    'default_subject' => "Your Leave Request Has Been Approved – {institute_name}",
                    'default_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Your leave request has been approved.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<tr><td style="background-color:#0c4f00; padding:26px 40px; text-align:center;">
<span style="color:#ffffff; font-size:19px; font-weight:800; letter-spacing:0.3px;">{institute_name}</span>
</td></tr>
<tr><td style="padding:36px 40px 4px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;"><tr><td style="width:48px; height:48px; border-radius:50%; background-color:#e7f3e4; text-align:center; vertical-align:middle; font-size:22px; color:#0c4f00; font-weight:800;">&#10003;</td></tr></table>
</td></tr>
<tr><td style="padding:0 40px 4px; text-align:center;"><p style="margin:0 0 4px; font-size:18px; font-weight:800; color:#1f2937;">Leave Request Approved</p></td></tr>
<tr><td style="padding:28px 40px 0;"><p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#1f2937;">Dear {applicant_name},</p><p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">Your leave request at <strong>{institute_name}</strong> has been <strong>approved</strong>.</p></td></tr>
<tr><td style="padding:0 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:18px 22px;">
<p style="margin:0 0 10px; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Leave details</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Start Date</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{start_date}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">End Date</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{end_date}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Comments</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{comments}</td></tr>
</table>
</td></tr>
</table>
</td></tr>
<tr><td style="padding:20px 40px 28px;"><p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">Please coordinate with your department ahead of your leave dates.</p></td></tr>

<tr><td style="background-color:#f7f8fa; padding:22px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0; font-size:12px; color:#6b7280;">This is an automated message from <strong style="color:#1f2937;">{institute_name}</strong>. Please do not reply directly to this email.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
EOT,
                ),
                8 => array(
                    'default_subject' => "Your Leave Request Was Not Approved – {institute_name}",
                    'default_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Your leave request was not approved.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<tr><td style="background-color:#0c4f00; padding:26px 40px; text-align:center;">
<span style="color:#ffffff; font-size:19px; font-weight:800; letter-spacing:0.3px;">{institute_name}</span>
</td></tr>
<tr><td style="padding:36px 40px 4px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;"><tr><td style="width:48px; height:48px; border-radius:50%; background-color:#fdecea; text-align:center; vertical-align:middle; font-size:22px; color:#b42318; font-weight:800;">!</td></tr></table>
</td></tr>
<tr><td style="padding:0 40px 4px; text-align:center;"><p style="margin:0 0 4px; font-size:18px; font-weight:800; color:#1f2937;">Leave Request Not Approved</p></td></tr>
<tr><td style="padding:28px 40px 0;"><p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#1f2937;">Dear {applicant_name},</p><p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">Your leave request at <strong>{institute_name}</strong> was <strong>not approved</strong>.</p></td></tr>
<tr><td style="padding:0 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:18px 22px;">
<p style="margin:0 0 10px; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Leave details</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Start Date</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{start_date}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">End Date</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{end_date}</td></tr>
</table>
</td></tr>
</table>
</td></tr>
<tr><td style="padding:16px 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fdecea; border:1px solid #f5c2bb; border-radius:8px;">
<tr><td style="padding:14px 20px;">
<p style="margin:0 0 4px; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#b42318;">Comments</p>
<p style="margin:0; font-size:14px; color:#7a271a;">{comments}</p>
</td></tr>
</table>
</td></tr>
<tr><td style="padding:20px 40px 28px;"><p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">If you have questions about this decision, please speak with your department head or HR.</p></td></tr>

<tr><td style="background-color:#f7f8fa; padding:22px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0; font-size:12px; color:#6b7280;">This is an automated message from <strong style="color:#1f2937;">{institute_name}</strong>. Please do not reply directly to this email.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
EOT,
                ),
                9 => array(
                    'default_subject' => "Your Advance Salary Request Has Been Approved – {institute_name}",
                    'default_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Your advance salary request has been approved.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<tr><td style="background-color:#0c4f00; padding:26px 40px; text-align:center;">
<span style="color:#ffffff; font-size:19px; font-weight:800; letter-spacing:0.3px;">{institute_name}</span>
</td></tr>
<tr><td style="padding:36px 40px 4px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;"><tr><td style="width:48px; height:48px; border-radius:50%; background-color:#e7f3e4; text-align:center; vertical-align:middle; font-size:22px; color:#0c4f00; font-weight:800;">&#10003;</td></tr></table>
</td></tr>
<tr><td style="padding:0 40px 4px; text-align:center;"><p style="margin:0 0 4px; font-size:18px; font-weight:800; color:#1f2937;">Advance Salary Approved</p></td></tr>
<tr><td style="padding:28px 40px 0;"><p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#1f2937;">Dear {applicant_name},</p><p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">Your advance salary request at <strong>{institute_name}</strong> has been <strong>approved</strong>.</p></td></tr>
<tr><td style="padding:0 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:18px 22px;">
<p style="margin:0 0 10px; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Request details</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Amount</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">&#8358;{amount}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Deduction Month</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{deduct_motnh}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Comments</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{comments}</td></tr>
</table>
</td></tr>
</table>
</td></tr>
<tr><td style="padding:20px 40px 28px;"><p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">The approved amount will be deducted from your salary as scheduled above.</p></td></tr>

<tr><td style="background-color:#f7f8fa; padding:22px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0; font-size:12px; color:#6b7280;">This is an automated message from <strong style="color:#1f2937;">{institute_name}</strong>. Please do not reply directly to this email.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
EOT,
                ),
                10 => array(
                    'default_subject' => "Your Advance Salary Request Was Not Approved – {institute_name}",
                    'default_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Your advance salary request was not approved.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<tr><td style="background-color:#0c4f00; padding:26px 40px; text-align:center;">
<span style="color:#ffffff; font-size:19px; font-weight:800; letter-spacing:0.3px;">{institute_name}</span>
</td></tr>
<tr><td style="padding:36px 40px 4px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;"><tr><td style="width:48px; height:48px; border-radius:50%; background-color:#fdecea; text-align:center; vertical-align:middle; font-size:22px; color:#b42318; font-weight:800;">!</td></tr></table>
</td></tr>
<tr><td style="padding:0 40px 4px; text-align:center;"><p style="margin:0 0 4px; font-size:18px; font-weight:800; color:#1f2937;">Advance Salary Request Not Approved</p></td></tr>
<tr><td style="padding:28px 40px 0;"><p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#1f2937;">Dear {applicant_name},</p><p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">Your advance salary request at <strong>{institute_name}</strong> was <strong>not approved</strong>.</p></td></tr>
<tr><td style="padding:0 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:18px 22px;">
<p style="margin:0 0 10px; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Request details</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Amount Requested</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">&#8358;{amount}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Deduction Month</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{deduct_motnh}</td></tr>
</table>
</td></tr>
</table>
</td></tr>
<tr><td style="padding:16px 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fdecea; border:1px solid #f5c2bb; border-radius:8px;">
<tr><td style="padding:14px 20px;">
<p style="margin:0 0 4px; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#b42318;">Comments</p>
<p style="margin:0; font-size:14px; color:#7a271a;">{comments}</p>
</td></tr>
</table>
</td></tr>
<tr><td style="padding:20px 40px 28px;"><p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">If you have questions about this decision, please speak with your accounts office.</p></td></tr>

<tr><td style="background-color:#f7f8fa; padding:22px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0; font-size:12px; color:#6b7280;">This is an automated message from <strong style="color:#1f2937;">{institute_name}</strong>. Please do not reply directly to this email.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
EOT,
                ),
                11 => array(
                    'default_subject' => "Admission Application Received – {institute_name}",
                    'default_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Your admission application to {institute_name} was received.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<tr><td style="background-color:#0c4f00; padding:26px 40px; text-align:center;">
<span style="color:#ffffff; font-size:19px; font-weight:800; letter-spacing:0.3px;">{institute_name}</span>
</td></tr>
<tr><td style="padding:36px 40px 4px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;"><tr><td style="width:48px; height:48px; border-radius:50%; background-color:#e7f3e4; text-align:center; vertical-align:middle; font-size:22px; color:#0c4f00; font-weight:800;">&#10003;</td></tr></table>
</td></tr>
<tr><td style="padding:0 40px 4px; text-align:center;"><p style="margin:0 0 4px; font-size:18px; font-weight:800; color:#1f2937;">Application Received</p></td></tr>
<tr><td style="padding:28px 40px 0;"><p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#1f2937;">Dear {applicant_name},</p><p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">Thank you for applying to <strong>{institute_name}</strong>. We have received your admission application.</p></td></tr>
<tr><td style="padding:0 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:18px 22px;">
<p style="margin:0 0 10px; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Application details</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Reference No.</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{reference_no}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Mobile</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{applicant_mobile}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Class Applied For</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{class} ({section})</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Applied On</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{apply_date}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Amount Paid</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">&#8358;{paid_amount}</td></tr>
</table>
</td></tr>
</table>
</td></tr>
<tr><td style="padding:24px 40px 0; text-align:center;">
<a href="{payment_url}" style="display:inline-block; background-color:#0c4f00; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none; padding:13px 32px; border-radius:6px;">Pay Application Fee</a>
</td></tr>
<tr><td style="padding:14px 40px 0; text-align:center;"><a href="{admission_copy_url}" style="color:#0c4f00; font-size:13px; font-weight:600; text-decoration:none;">Download Application Copy</a></td></tr>
<tr><td style="padding:20px 40px 28px;"><p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">Please keep your reference number for tracking. We will contact you with next steps.</p></td></tr>

<tr><td style="background-color:#f7f8fa; padding:22px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0; font-size:12px; color:#6b7280;">This is an automated message from <strong style="color:#1f2937;">{institute_name}</strong>. Please do not reply directly to this email.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
EOT,
                ),
                12 => array(
                    'default_subject' => "Welcome to {institute_name} – Admission Confirmed",
                    'default_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Your admission at {institute_name} is confirmed.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<tr><td style="background-color:#0c4f00; padding:26px 40px; text-align:center;">
<span style="color:#ffffff; font-size:19px; font-weight:800; letter-spacing:0.3px;">{institute_name}</span>
</td></tr>
<tr><td style="padding:36px 40px 4px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;"><tr><td style="width:48px; height:48px; border-radius:50%; background-color:#e7f3e4; text-align:center; vertical-align:middle; font-size:22px; color:#0c4f00; font-weight:800;">&#10003;</td></tr></table>
</td></tr>
<tr><td style="padding:0 40px 4px; text-align:center;"><p style="margin:0 0 4px; font-size:18px; font-weight:800; color:#1f2937;">Admission Confirmed</p></td></tr>
<tr><td style="padding:28px 40px 0;"><p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#1f2937;">Dear {student_name},</p><p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">Welcome to <strong>{institute_name}</strong>! We are pleased to confirm the admission below.</p></td></tr>
<tr><td style="padding:0 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:18px 22px;">
<p style="margin:0 0 10px; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Admission details</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Admission No.</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{admission_no}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Roll No.</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{roll}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Class</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{class} ({section})</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Category</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{category}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Academic Year</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{academic_year}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Admission Date</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{admission_date}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Mobile</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{student_mobile}</td></tr>
</table>
</td></tr>
</table>
</td></tr>
<tr><td style="padding:0 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:18px 22px;">
<p style="margin:0 0 10px; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Login credentials</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Username</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{login_username}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Password</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{password}</td></tr>
</table>
</td></tr>
</table>
</td></tr>
<tr><td style="padding:24px 40px 0; text-align:center;">
<a href="{login_url}" style="display:inline-block; background-color:#0c4f00; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none; padding:13px 32px; border-radius:6px;">Log In to Your Portal</a>
</td></tr>
<tr><td style="padding:20px 40px 28px;"><p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">We look forward to a great academic journey together. Please change your password after your first login.</p></td></tr>

<tr><td style="background-color:#f7f8fa; padding:22px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0; font-size:12px; color:#6b7280;">This is an automated message from <strong style="color:#1f2937;">{institute_name}</strong>. Please do not reply directly to this email.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
EOT,
                ),
                13 => array(
                    'default_subject' => "{exam_name} Marksheet – {institute_name}",
                    'default_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Your {exam_name} marksheet from {institute_name} is attached.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<tr><td style="background-color:#0c4f00; padding:26px 40px; text-align:center;">
<span style="color:#ffffff; font-size:19px; font-weight:800; letter-spacing:0.3px;">{institute_name}</span>
</td></tr>
<tr><td style="padding:36px 40px 4px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;"><tr><td style="width:48px; height:48px; border-radius:50%; background-color:#f7f8fa; text-align:center; vertical-align:middle; font-size:22px; color:#1f2937; font-weight:800;">&#128196;</td></tr></table>
</td></tr>
<tr><td style="padding:0 40px 4px; text-align:center;"><p style="margin:0 0 4px; font-size:18px; font-weight:800; color:#1f2937;">Marksheet Attached</p></td></tr>
<tr><td style="padding:28px 40px 0;"><p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#1f2937;">Dear {student_name},</p><p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">Please find attached the marksheet for <strong>{exam_name}</strong> from <strong>{institute_name}</strong>.</p></td></tr>
<tr><td style="padding:0 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:18px 22px;">
<p style="margin:0 0 10px; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Student details</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Register No.</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{register_no}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Roll No.</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{roll}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Class</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{class} ({section})</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Academic Year</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{academic_year}</td></tr>
</table>
</td></tr>
</table>
</td></tr>
<tr><td style="padding:20px 40px 28px;"><p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">The marksheet is attached to this email as a PDF. Contact the school office if you have any questions about the results.</p></td></tr>

<tr><td style="background-color:#f7f8fa; padding:22px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0; font-size:12px; color:#6b7280;">This is an automated message from <strong style="color:#1f2937;">{institute_name}</strong>. Please do not reply directly to this email.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
EOT,
                ),
                14 => array(
                    'default_subject' => "Fee Invoice – {institute_name}",
                    'default_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Your fee invoice from {institute_name} is attached.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<tr><td style="background-color:#0c4f00; padding:26px 40px; text-align:center;">
<span style="color:#ffffff; font-size:19px; font-weight:800; letter-spacing:0.3px;">{institute_name}</span>
</td></tr>
<tr><td style="padding:36px 40px 4px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;"><tr><td style="width:48px; height:48px; border-radius:50%; background-color:#f7f8fa; text-align:center; vertical-align:middle; font-size:22px; color:#1f2937; font-weight:800;">&#128196;</td></tr></table>
</td></tr>
<tr><td style="padding:0 40px 4px; text-align:center;"><p style="margin:0 0 4px; font-size:18px; font-weight:800; color:#1f2937;">Fee Invoice Attached</p></td></tr>
<tr><td style="padding:28px 40px 0;"><p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#1f2937;">Dear {student_name},</p><p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">Please find attached the fee invoice from <strong>{institute_name}</strong>, dated {today_date}.</p></td></tr>
<tr><td style="padding:0 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:18px 22px;">
<p style="margin:0 0 10px; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Student details</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Register No.</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{register_no}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Roll No.</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{roll}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Class</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{class} ({section})</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Academic Year</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{academic_year}</td></tr>
</table>
</td></tr>
</table>
</td></tr>
<tr><td style="padding:20px 40px 28px;"><p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">The invoice is attached to this email as a PDF. Contact the school&rsquo;s accounts office if you have any questions.</p></td></tr>

<tr><td style="background-color:#f7f8fa; padding:22px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0; font-size:12px; color:#6b7280;">This is an automated message from <strong style="color:#1f2937;">{institute_name}</strong>. Please do not reply directly to this email.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
EOT,
                ),
                15 => array(
                    'default_subject' => "New Online Exam Published – {exam_title}",
                    'default_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">{exam_title} has been published at {institute_name}.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<tr><td style="background-color:#0c4f00; padding:26px 40px; text-align:center;">
<span style="color:#ffffff; font-size:19px; font-weight:800; letter-spacing:0.3px;">{institute_name}</span>
</td></tr>
<tr><td style="padding:36px 40px 4px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;"><tr><td style="width:48px; height:48px; border-radius:50%; background-color:#f7f8fa; text-align:center; vertical-align:middle; font-size:22px; color:#1f2937; font-weight:800;">&#128221;</td></tr></table>
</td></tr>
<tr><td style="padding:0 40px 4px; text-align:center;"><p style="margin:0 0 4px; font-size:18px; font-weight:800; color:#1f2937;">Online Exam Published</p></td></tr>
<tr><td style="padding:28px 40px 0;"><p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#1f2937;">Dear {student_name},</p><p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">A new online exam has been published at <strong>{institute_name}</strong>.</p></td></tr>
<tr><td style="padding:0 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:18px 22px;">
<p style="margin:0 0 10px; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Exam details</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Exam</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{exam_title}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Class</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{class} ({section})</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Start Time</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{start_time}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">End Time</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{end_time}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Duration</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{time_duration}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Attempts Allowed</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{attempt}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Passing Mark</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{passing_mark}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Exam Fee</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">&#8358;{exam_fee}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Register No.</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{register_no}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Roll No.</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{roll}</td></tr>
</table>
</td></tr>
</table>
</td></tr>
<tr><td style="padding:20px 40px 28px;"><p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">Log in to your student portal to take the exam before it closes.</p></td></tr>

<tr><td style="background-color:#f7f8fa; padding:22px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0; font-size:12px; color:#6b7280;">This is an automated message from <strong style="color:#1f2937;">{institute_name}</strong>. Please do not reply directly to this email.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
EOT,
                ),
                600 => array(
                    'default_subject' => "Your Verification Code – {institute_name}",
                    'default_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Your {institute_name} verification code.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<tr><td style="background-color:#0c4f00; padding:26px 40px; text-align:center;">
<span style="color:#ffffff; font-size:19px; font-weight:800; letter-spacing:0.3px;">{institute_name}</span>
</td></tr>
<tr><td style="padding:36px 40px 4px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;"><tr><td style="width:48px; height:48px; border-radius:50%; background-color:#f7f8fa; text-align:center; vertical-align:middle; font-size:22px; color:#1f2937; font-weight:800;">&#128274;</td></tr></table>
</td></tr>
<tr><td style="padding:0 40px 4px; text-align:center;"><p style="margin:0 0 4px; font-size:18px; font-weight:800; color:#1f2937;">Verification Code</p></td></tr>
<tr><td style="padding:28px 40px 0;"><p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#1f2937;">Dear {name},</p><p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">Use the code below to complete your sign-in to <strong>{institute_name}</strong>.</p></td></tr>
<tr><td style="padding:20px 40px 0; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;"><tr><td style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px; padding:16px 32px;">
<span style="font-size:26px; font-weight:800; letter-spacing:6px; color:#1f2937; font-family:ui-monospace,Consolas,monospace;">{verification_code}</span>
</td></tr></table>
</td></tr>
<tr><td style="padding:24px 40px 28px;"><p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">This code will expire shortly. If you did not attempt to sign in, please secure your account immediately.</p></td></tr>

<tr><td style="background-color:#f7f8fa; padding:22px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0; font-size:12px; color:#6b7280;">This is an automated message from <strong style="color:#1f2937;">{institute_name}</strong>. Please do not reply directly to this email.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
EOT,
                ),
                601 => array(
                    'default_subject' => "Two-Factor Authentication Enabled – {institute_name}",
                    'default_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Two-factor authentication was enabled on your account.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<tr><td style="background-color:#0c4f00; padding:26px 40px; text-align:center;">
<span style="color:#ffffff; font-size:19px; font-weight:800; letter-spacing:0.3px;">{institute_name}</span>
</td></tr>
<tr><td style="padding:36px 40px 4px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;"><tr><td style="width:48px; height:48px; border-radius:50%; background-color:#e7f3e4; text-align:center; vertical-align:middle; font-size:22px; color:#0c4f00; font-weight:800;">&#10003;</td></tr></table>
</td></tr>
<tr><td style="padding:0 40px 4px; text-align:center;"><p style="margin:0 0 4px; font-size:18px; font-weight:800; color:#1f2937;">Two-Factor Authentication Enabled</p></td></tr>
<tr><td style="padding:28px 40px 0;"><p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#1f2937;">Dear {name},</p><p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">Two-factor authentication has just been enabled on your <strong>{institute_name}</strong> account.</p></td></tr>
<tr><td style="padding:0 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:18px 22px;">
<p style="margin:0 0 10px; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Details</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Device IP</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{device_IP}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Browser</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{browser}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Setup Code</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{app_code}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Time</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{time}</td></tr>
</table>
</td></tr>
</table>
</td></tr>
<tr><td style="padding:20px 40px 28px;"><p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">If you did not make this change, please contact your school administrator immediately.</p></td></tr>

<tr><td style="background-color:#f7f8fa; padding:22px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0; font-size:12px; color:#6b7280;">This is an automated message from <strong style="color:#1f2937;">{institute_name}</strong>. Please do not reply directly to this email.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
EOT,
                ),
                602 => array(
                    'default_subject' => "Two-Factor Authentication Disabled – {institute_name}",
                    'default_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Two-factor authentication was disabled on your account.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
<tr><td style="background-color:#0c4f00; padding:26px 40px; text-align:center;">
<span style="color:#ffffff; font-size:19px; font-weight:800; letter-spacing:0.3px;">{institute_name}</span>
</td></tr>
<tr><td style="padding:36px 40px 4px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 14px;"><tr><td style="width:48px; height:48px; border-radius:50%; background-color:#fdecea; text-align:center; vertical-align:middle; font-size:22px; color:#b42318; font-weight:800;">!</td></tr></table>
</td></tr>
<tr><td style="padding:0 40px 4px; text-align:center;"><p style="margin:0 0 4px; font-size:18px; font-weight:800; color:#1f2937;">Two-Factor Authentication Disabled</p></td></tr>
<tr><td style="padding:28px 40px 0;"><p style="margin:0 0 16px; font-size:15px; font-weight:700; color:#1f2937;">Dear {name},</p><p style="margin:0 0 16px; font-size:14px; line-height:22px; color:#374151;">Two-factor authentication has just been disabled on your <strong>{institute_name}</strong> account.</p></td></tr>
<tr><td style="padding:0 40px 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:18px 22px;">
<p style="margin:0 0 10px; font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Details</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Device IP</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{device_IP}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Browser</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{browser}</td></tr>
<tr><td style="padding:6px 0; font-size:13px; color:#6b7280; width:42%;">Time</td><td style="padding:6px 0; font-size:13px; font-weight:700; color:#1f2937;">{time}</td></tr>
</table>
</td></tr>
</table>
</td></tr>
<tr><td style="padding:20px 40px 28px;"><p style="margin:0; font-size:13px; line-height:20px; color:#6b7280;">If you did not make this change, please contact your school administrator immediately and re-enable two-factor authentication.</p></td></tr>

<tr><td style="background-color:#f7f8fa; padding:22px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0; font-size:12px; color:#6b7280;">This is an automated message from <strong style="color:#1f2937;">{institute_name}</strong>. Please do not reply directly to this email.</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
EOT,
                ),
            );

            foreach ($defaults as $id => $tpl) {
                $this->db->where('id', $id)->update('email_templates', $tpl);
            }
        }
    }
}
