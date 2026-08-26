<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_716 extends CI_Migration
{
    public function up()
    {
        $templates = array(
            1 => array(
                'name' => 'school_registered',
                'subject' => "Welcome to School Edge – Let’s Simplify Your School Management!",
                'tags' => "{institute_name}, {admin_name}, {login_username}, {password}, {school_name}, {plan_name}, {invoice_url}, {payment_url}, {reference_no}, {date}, {fees_amount}, {login_url}",
                'template_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Welcome to School Edge &ndash; your account is ready.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

<tr><td style="background-color:#0c4f00; padding:28px 40px; text-align:center;">
<span style="color:#ffffff; font-size:22px; font-weight:800; letter-spacing:0.5px;">SCHOOL EDGE</span>
</td></tr>

<tr><td style="padding:40px 40px 8px;">
<p style="margin:0 0 20px; font-size:16px; font-weight:700; color:#1f2937;">Dear {admin_name},</p>
<p style="margin:0 0 20px; font-size:15px; line-height:24px; color:#374151;">Welcome to <strong>{institute_name}</strong>! We're excited to have <strong>{school_name}</strong> join our platform and begin a journey toward smarter, easier, and more efficient school management.</p>
</td></tr>

<tr><td style="padding:0 40px 8px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:20px 24px;">
<p style="margin:0 0 12px; font-size:12px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Your account details</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280; width:40%;">Login Username</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">{login_username}</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280;">Temporary Password</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937; font-family:ui-monospace,Consolas,monospace;">{password}</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280;">Plan</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">{plan_name}</td></tr>
</table>
</td></tr>
</table>
</td></tr>

<tr><td style="padding:24px 40px 8px; text-align:center;">
<a href="{login_url}" style="display:inline-block; background-color:#0c4f00; color:#ffffff; font-size:15px; font-weight:700; text-decoration:none; padding:14px 36px; border-radius:6px;">Go to Your Dashboard</a>
</td></tr>

<tr><td style="padding:28px 40px 8px;">
<p style="margin:0 0 14px; font-size:12px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">What you can do with {institute_name}</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:14px; color:#374151; vertical-align:top; width:22px;"><span style="color:#0c4f00; font-weight:700;">&#10003;</span></td><td style="padding:6px 0; font-size:14px; color:#374151;">Manage student records, results, and attendance seamlessly</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#374151; vertical-align:top;"><span style="color:#0c4f00; font-weight:700;">&#10003;</span></td><td style="padding:6px 0; font-size:14px; color:#374151;">Automate billing and online fee payments</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#374151; vertical-align:top;"><span style="color:#0c4f00; font-weight:700;">&#10003;</span></td><td style="padding:6px 0; font-size:14px; color:#374151;">Conduct online classes and share e-learning resources</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#374151; vertical-align:top;"><span style="color:#0c4f00; font-weight:700;">&#10003;</span></td><td style="padding:6px 0; font-size:14px; color:#374151;">Communicate effortlessly with parents and students</td></tr>
</table>
</td></tr>

<tr><td style="padding:8px 40px 0;"><hr style="border:none; border-top:1px solid #e5e7eb; margin:16px 0;"></td></tr>

<tr><td style="padding:0 40px 8px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:20px 24px;">
<p style="margin:0 0 12px; font-size:12px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Your invoice</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280; width:40%;">Reference No.</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">{reference_no}</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280;">Amount Due</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">&#8358;{fees_amount}</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280;">Date</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">{date}</td></tr>
</table>
<p style="margin:16px 0 0;">
<a href="{payment_url}" style="display:inline-block; background-color:#0c4f00; color:#ffffff; font-size:13px; font-weight:700; text-decoration:none; padding:10px 20px; border-radius:6px; margin-right:8px;">Pay Now</a>
<a href="{invoice_url}" style="display:inline-block; background-color:#ffffff; color:#0c4f00; font-size:13px; font-weight:700; text-decoration:none; padding:10px 20px; border-radius:6px; border:1px solid #0c4f00;">View Invoice</a>
</p>
</td></tr>
</table>
</td></tr>

<tr><td style="padding:24px 40px 40px;">
<p style="margin:0 0 12px; font-size:14px; line-height:22px; color:#374151;">For assistance or onboarding help, contact us anytime at <a href="mailto:info@schooledge.com.ng" style="color:#0c4f00; font-weight:600;">info@schooledge.com.ng</a>.</p>
<p style="margin:0; font-size:14px; line-height:22px; color:#374151; font-weight:700;">Thank you for choosing {institute_name} &ndash; education made simple!</p>
</td></tr>

<tr><td style="background-color:#f7f8fa; padding:24px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0 0 4px; font-size:13px; color:#6b7280;">Best regards,<br><strong style="color:#1f2937;">The School Edge Team</strong></p>
<p style="margin:8px 0 0; font-size:12px;"><a href="https://www.schooledge.com.ng" style="color:#0c4f00; text-decoration:none;">www.schooledge.com.ng</a></p>
</td></tr>

</table>
</td></tr>
</table>
</body>
EOT,
            ),
            2 => array(
                'name' => 'school_subscription_payment_confirmation',
                'subject' => "Payment Confirmation – Thank You for Subscribing to School Edge",
                'tags' => "{institute_name}, {admin_name}, {school_name}, {plan_name}, {invoice_url}, {reference_no}, {date}, {paid_amount}, {login_url}",
                'template_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Your School Edge subscription payment was received.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

<tr><td style="background-color:#0c4f00; padding:28px 40px; text-align:center;">
<span style="color:#ffffff; font-size:22px; font-weight:800; letter-spacing:0.5px;">SCHOOL EDGE</span>
</td></tr>

<tr><td style="padding:40px 40px 8px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 16px;"><tr><td style="width:52px; height:52px; border-radius:50%; background-color:#e7f3e4; text-align:center; vertical-align:middle; font-size:26px; color:#0c4f00; font-weight:800;">&#10003;</td></tr></table>
<p style="margin:0 0 20px; font-size:19px; font-weight:800; color:#1f2937;">Payment Received</p>
</td></tr>

<tr><td style="padding:0 40px 8px;">
<p style="margin:0 0 20px; font-size:16px; font-weight:700; color:#1f2937;">Dear {admin_name},</p>
<p style="margin:0 0 20px; font-size:15px; line-height:24px; color:#374151;">Thank you for your payment! Your subscription to <strong>{institute_name}</strong> is now active, and you can enjoy full access to all our features.</p>
</td></tr>

<tr><td style="padding:0 40px 8px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:20px 24px;">
<p style="margin:0 0 12px; font-size:12px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Payment details</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280; width:40%;">School Name</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">{school_name}</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280;">Plan</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">{plan_name}</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280;">Amount Paid</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">&#8358;{paid_amount}</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280;">Reference No.</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">{reference_no}</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280;">Payment Date</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">{date}</td></tr>
</table>
</td></tr>
</table>
</td></tr>

<tr><td style="padding:24px 40px 8px; text-align:center;">
<a href="{login_url}" style="display:inline-block; background-color:#0c4f00; color:#ffffff; font-size:15px; font-weight:700; text-decoration:none; padding:14px 36px; border-radius:6px;">Go to Your Dashboard</a>
<p style="margin:14px 0 0;"><a href="{invoice_url}" style="color:#0c4f00; font-size:13px; font-weight:600; text-decoration:none;">View Invoice</a></p>
</td></tr>

<tr><td style="padding:24px 40px 40px;">
<p style="margin:0 0 12px; font-size:14px; line-height:22px; color:#374151;">For questions or support, email us at <a href="mailto:info@schooledge.com.ng" style="color:#0c4f00; font-weight:600;">info@schooledge.com.ng</a>.</p>
<p style="margin:0; font-size:14px; line-height:22px; color:#374151; font-weight:700;">We appreciate your trust in {institute_name}.</p>
</td></tr>

<tr><td style="background-color:#f7f8fa; padding:24px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0 0 4px; font-size:13px; color:#6b7280;">Best regards,<br><strong style="color:#1f2937;">The School Edge Team</strong></p>
<p style="margin:8px 0 0; font-size:12px;"><a href="https://www.schooledge.com.ng" style="color:#0c4f00; text-decoration:none;">www.schooledge.com.ng</a></p>
</td></tr>

</table>
</td></tr>
</table>
</body>
EOT,
            ),
            3 => array(
                'name' => 'school_subscription_approval_confirmation',
                'subject' => "Your School Edge Subscription Has Been Approved!",
                'tags' => "{institute_name}, {admin_name}, {login_username}, {password}, {school_name}, {plan_name}, {invoice_url}, {reference_no}, {subscription_start_date}, {subscription_expiry_date}, {paid_amount}, {login_url}",
                'template_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Your School Edge subscription has been approved.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

<tr><td style="background-color:#0c4f00; padding:28px 40px; text-align:center;">
<span style="color:#ffffff; font-size:22px; font-weight:800; letter-spacing:0.5px;">SCHOOL EDGE</span>
</td></tr>

<tr><td style="padding:40px 40px 8px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 16px;"><tr><td style="width:52px; height:52px; border-radius:50%; background-color:#e7f3e4; text-align:center; vertical-align:middle; font-size:26px; color:#0c4f00; font-weight:800;">&#10003;</td></tr></table>
<p style="margin:0 0 20px; font-size:19px; font-weight:800; color:#1f2937;">Subscription Approved</p>
</td></tr>

<tr><td style="padding:0 40px 8px;">
<p style="margin:0 0 20px; font-size:16px; font-weight:700; color:#1f2937;">Dear {admin_name},</p>
<p style="margin:0 0 20px; font-size:15px; line-height:24px; color:#374151;">Congratulations! Your subscription plan for <strong>{school_name}</strong> has been successfully approved and activated.</p>
</td></tr>

<tr><td style="padding:0 40px 8px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:20px 24px;">
<p style="margin:0 0 12px; font-size:12px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Subscription details</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280; width:40%;">School Name</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">{school_name}</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280;">Plan</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">{plan_name}</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280;">Amount Paid</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">&#8358;{paid_amount}</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280;">Start Date</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">{subscription_start_date}</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280;">Expiry Date</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">{subscription_expiry_date}</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280;">Reference No.</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">{reference_no}</td></tr>
</table>
</td></tr>
</table>
</td></tr>

<tr><td style="padding:20px 40px 8px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:20px 24px;">
<p style="margin:0 0 12px; font-size:12px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Your login credentials</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280; width:40%;">Login Username</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">{login_username}</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280;">Password</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937; font-family:ui-monospace,Consolas,monospace;">{password}</td></tr>
</table>
</td></tr>
</table>
</td></tr>

<tr><td style="padding:24px 40px 8px; text-align:center;">
<a href="{login_url}" style="display:inline-block; background-color:#0c4f00; color:#ffffff; font-size:15px; font-weight:700; text-decoration:none; padding:14px 36px; border-radius:6px;">Go to Your Dashboard</a>
<p style="margin:14px 0 0;"><a href="{invoice_url}" style="color:#0c4f00; font-size:13px; font-weight:600; text-decoration:none;">View Invoice</a></p>
</td></tr>

<tr><td style="padding:8px 40px 0;">
<p style="margin:0 0 14px; font-size:12px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Next steps</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:14px; color:#374151; vertical-align:top; width:22px;"><span style="color:#0c4f00; font-weight:700;">&#10003;</span></td><td style="padding:6px 0; font-size:14px; color:#374151;">Log in to your dashboard using the credentials above</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#374151; vertical-align:top;"><span style="color:#0c4f00; font-weight:700;">&#10003;</span></td><td style="padding:6px 0; font-size:14px; color:#374151;">Start managing student records, results, and online classes</td></tr>
</table>
</td></tr>

<tr><td style="padding:24px 40px 40px;">
<p style="margin:0; font-size:14px; line-height:22px; color:#374151;">Need help? Contact us anytime at <a href="mailto:info@schooledge.com.ng" style="color:#0c4f00; font-weight:600;">info@schooledge.com.ng</a>.</p>
</td></tr>

<tr><td style="background-color:#f7f8fa; padding:24px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0 0 4px; font-size:13px; color:#6b7280;">Best regards,<br><strong style="color:#1f2937;">The School Edge Team</strong></p>
<p style="margin:8px 0 0; font-size:12px;"><a href="https://www.schooledge.com.ng" style="color:#0c4f00; text-decoration:none;">www.schooledge.com.ng</a></p>
</td></tr>

</table>
</td></tr>
</table>
</body>
EOT,
            ),
            4 => array(
                'name' => 'school_subscription_reject',
                'subject' => "Subscription Payment Rejected – SchoolEdge.com.ng",
                'tags' => "{institute_name}, {admin_name}, {school_name}, {reference_no}, {reject_reason}",
                'template_body' => <<<'EOT'
<body style="margin:0; padding:0; background-color:#f2f4f3;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">We could not process your recent School Edge subscription payment.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f4f3; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

<tr><td style="background-color:#0c4f00; padding:28px 40px; text-align:center;">
<span style="color:#ffffff; font-size:22px; font-weight:800; letter-spacing:0.5px;">SCHOOL EDGE</span>
</td></tr>

<tr><td style="padding:40px 40px 8px; text-align:center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 16px;"><tr><td style="width:52px; height:52px; border-radius:50%; background-color:#fdecea; text-align:center; vertical-align:middle; font-size:26px; color:#b42318; font-weight:800;">!</td></tr></table>
<p style="margin:0 0 20px; font-size:19px; font-weight:800; color:#1f2937;">Payment Not Approved</p>
</td></tr>

<tr><td style="padding:0 40px 8px;">
<p style="margin:0 0 20px; font-size:16px; font-weight:700; color:#1f2937;">Dear {admin_name},</p>
<p style="margin:0 0 20px; font-size:15px; line-height:24px; color:#374151;">We regret to inform you that your recent subscription payment for <strong>{institute_name}</strong> could not be processed or approved due to an issue with the transaction.</p>
</td></tr>

<tr><td style="padding:0 40px 8px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fa; border:1px solid #e5e7eb; border-radius:8px;">
<tr><td style="padding:20px 24px;">
<p style="margin:0 0 12px; font-size:12px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">Payment details</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280; width:40%;">School Name</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">{school_name}</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#6b7280;">Reference No.</td><td style="padding:6px 0; font-size:14px; font-weight:700; color:#1f2937;">{reference_no}</td></tr>
</table>
</td></tr>
</table>
</td></tr>

<tr><td style="padding:16px 40px 8px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fdecea; border:1px solid #f5c2bb; border-radius:8px;">
<tr><td style="padding:16px 20px;">
<p style="margin:0 0 4px; font-size:12px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#b42318;">Reason</p>
<p style="margin:0; font-size:14px; color:#7a271a;">{reject_reason}</p>
</td></tr>
</table>
</td></tr>

<tr><td style="padding:20px 40px 8px;">
<p style="margin:0 0 14px; font-size:12px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280;">What should you do?</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:6px 0; font-size:14px; color:#374151; vertical-align:top; width:22px;"><span style="color:#0c4f00; font-weight:700;">&#10003;</span></td><td style="padding:6px 0; font-size:14px; color:#374151;">Verify your payment details with your bank or payment provider</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#374151; vertical-align:top;"><span style="color:#0c4f00; font-weight:700;">&#10003;</span></td><td style="padding:6px 0; font-size:14px; color:#374151;">If the payment was deducted, share your payment receipt or transaction reference with us at <a href="mailto:info@schooledge.com.ng" style="color:#0c4f00; font-weight:600;">info@schooledge.com.ng</a> for verification</td></tr>
<tr><td style="padding:6px 0; font-size:14px; color:#374151; vertical-align:top;"><span style="color:#0c4f00; font-weight:700;">&#10003;</span></td><td style="padding:6px 0; font-size:14px; color:#374151;">Retry your subscription payment through your dashboard</td></tr>
</table>
</td></tr>

<tr><td style="padding:24px 40px 40px;">
<p style="margin:0; font-size:14px; line-height:22px; color:#374151;">We apologize for any inconvenience this may have caused and are here to help resolve the issue promptly. Thank you for choosing {institute_name}.</p>
</td></tr>

<tr><td style="background-color:#f7f8fa; padding:24px 40px; text-align:center; border-top:1px solid #e5e7eb;">
<p style="margin:0 0 4px; font-size:13px; color:#6b7280;">Best regards,<br><strong style="color:#1f2937;">The School Edge Team</strong></p>
<p style="margin:8px 0 0; font-size:12px;"><a href="https://www.schooledge.com.ng" style="color:#0c4f00; text-decoration:none;">www.schooledge.com.ng</a></p>
</td></tr>

</table>
</td></tr>
</table>
</body>
EOT,
            ),
        );

        if ($this->db->table_exists('saas_email_templates')) {
            foreach ($templates as $id => $tpl) {
                $this->db->where('id', $id)->update('saas_email_templates', array(
                    'subject' => $tpl['subject'],
                    'template_body' => $tpl['template_body'],
                    'tags' => $tpl['tags'],
                ));
            }
        }
    }
}
