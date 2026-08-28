<?php
/**
 * One-time schema sync for migrations 710-717 (design_style / available_all_branches /
 * term_position / psychomotor_rating / next_term_begins / branch.is_demo / FAQ content /
 * saas_email_templates redesign / email_templates default_subject+default_body).
 *
 * Safe to run more than once — every change is guarded by an existence check first (the
 * migration 716/717 email template updates are plain overwrites of fixed-id rows, so
 * re-running them just re-applies the same content), mirroring exactly what each
 * application/migrations/71{0..7}_version_71{0..7}.php file does through CodeIgniter's
 * migration library. This script exists because this app has
 * no mechanism that actually runs CI's migration runner (migration_enabled is FALSE and
 * nothing in the codebase ever calls $this->load->library('migration')) — schema changes
 * have always had to be applied manually.
 *
 * Run once on each environment that needs to catch up (e.g. production), from the app root:
 *   php application/database_seeds/apply_pending_migrations.php
 *
 * Afterwards, run application/database_seeds/seed_demo_school.php to create the actual
 * demo school data (that script depends on the tables/columns this one adds).
 */

define('BASEPATH', 'x');
define('ENVIRONMENT', 'production');
require __DIR__ . '/../config/database.php'; // defines $db['default'] with the real (gitignored) credentials

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$dbConfig = $db['default'];
$m = new mysqli($dbConfig['hostname'], $dbConfig['username'], $dbConfig['password'], $dbConfig['database']);
$m->set_charset('utf8');

function columnExists($m, $table, $column)
{
    $res = $m->query("SHOW COLUMNS FROM `$table` LIKE '" . $m->real_escape_string($column) . "'");
    return $res->num_rows > 0;
}

function tableExists($m, $table)
{
    $res = $m->query("SHOW TABLES LIKE '" . $m->real_escape_string($table) . "'");
    return $res->num_rows > 0;
}

// ---------------------------------------------------------------------
// Migration 710: design_style on the 3 document-template tables
// ---------------------------------------------------------------------
foreach (['card_templete', 'certificates_templete', 'marksheet_template'] as $table) {
    if (tableExists($m, $table) && !columnExists($m, $table, 'design_style')) {
        $m->query("ALTER TABLE `$table` ADD COLUMN `design_style` VARCHAR(20) NOT NULL DEFAULT 'classic' AFTER `name`");
        echo "710: added $table.design_style\n";
    }
}

// ---------------------------------------------------------------------
// Migration 711: SchoolEdge FAQ content
// ---------------------------------------------------------------------
if (tableExists($m, 'saas_cms_faq_list')) {
    $faqs = [
        'What is SchoolEdge.com.ng?' => 'SchoolEdge.com.ng is a school management platform that helps schools manage academic, administrative, financial, and communication activities in one place.',
        'Who can use SchoolEdge?' => 'School owners, administrators, teachers, accountants, parents, and students can use SchoolEdge. Each user receives access to the tools and information relevant to their role.',
        'What features does SchoolEdge offer?' => 'SchoolEdge supports student and staff management, attendance tracking, fees and payment records, results and report cards, class management, parent communication, and school reporting.',
        'How do I register my school?' => 'Select the registration option on the website and follow the steps to create your school account. You can also contact the SchoolEdge team for help with setup.',
        'Is SchoolEdge suitable for every type of school?' => "Yes. SchoolEdge can support nursery, primary, and secondary schools of different sizes. Available features can be configured to match each school's operations.",
        "Can parents view their children's information?" => 'Yes. Parents can securely access approved information such as results, attendance, fee status, announcements, and other school updates.',
        'Can teachers enter scores and prepare results online?' => "Yes. Teachers can record assessments and examination scores, while SchoolEdge calculates results and generates report cards according to the school's grading system.",
        'Does SchoolEdge support school-fee management?' => 'Yes. Schools can assign fees, record payments, monitor outstanding balances, issue receipts, and review payment reports.',
        "Is our school's information secure?" => 'SchoolEdge uses access controls and security measures to protect school data. Users can only access information permitted for their assigned roles.',
        'Can we use SchoolEdge on a mobile phone?' => 'Yes. SchoolEdge can be accessed through supported web browsers on smartphones, tablets, laptops, and desktop computers.',
        'Do we need to install any software?' => 'No. SchoolEdge is web-based, so users can access it online without installing traditional desktop software.',
        'What subscription plans are available?' => "Subscription options vary according to the school's size and required features. Review the pricing section or contact the SchoolEdge team for current plans and inclusions.",
        'Is training available for school staff?' => 'Yes. Onboarding guidance and training can be provided to help administrators and staff use the platform effectively.',
        'Can SchoolEdge be customized for our school?' => 'Schools can configure sessions, terms, classes, subjects, grading systems, fees, and user permissions. Additional customization may depend on the selected plan.',
        'What happens if we need technical support?' => 'The SchoolEdge support team can assist with account setup, platform usage, and technical issues through the available support channels.',
        'How can our school get started?' => 'Register your school on SchoolEdge.com.ng or contact the SchoolEdge team to discuss your requirements and choose a suitable subscription plan.',
    ];
    $faqCount = 0;
    foreach ($faqs as $title => $description) {
        $stmt = $m->prepare("SELECT id FROM saas_cms_faq_list WHERE title = ?");
        $stmt->bind_param('s', $title);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        if ($existing) {
            $stmt2 = $m->prepare("UPDATE saas_cms_faq_list SET description = ? WHERE id = ?");
            $stmt2->bind_param('si', $description, $existing['id']);
            $stmt2->execute();
        } else {
            $stmt2 = $m->prepare("INSERT INTO saas_cms_faq_list (title, description) VALUES (?, ?)");
            $stmt2->bind_param('ss', $title, $description);
            $stmt2->execute();
        }
        $faqCount++;
    }
    if (tableExists($m, 'saas_settings')) {
        $m->query("UPDATE saas_settings SET
            faq_title = 'Frequently Asked Questions',
            faq_description = 'Find answers to the most common questions about SchoolEdge.com.ng. From subscription details to feature explanations, our FAQ section is designed to help you quickly understand how our platform works and how it can benefit your school.'");
    }
    echo "711: synced $faqCount FAQ entries\n";
}

// ---------------------------------------------------------------------
// Migration 712: available_all_branches on the 3 document-template tables
// ---------------------------------------------------------------------
foreach (['card_templete', 'certificates_templete', 'marksheet_template'] as $table) {
    if (tableExists($m, $table) && !columnExists($m, $table, 'available_all_branches')) {
        $m->query("ALTER TABLE `$table` ADD COLUMN `available_all_branches` TINYINT(1) NOT NULL DEFAULT 0 AFTER `design_style`");
        echo "712: added $table.available_all_branches\n";
    }
}

// ---------------------------------------------------------------------
// Migration 713: term_position on marksheet_template
// ---------------------------------------------------------------------
if (tableExists($m, 'marksheet_template') && !columnExists($m, 'marksheet_template', 'term_position')) {
    $m->query("ALTER TABLE `marksheet_template` ADD COLUMN `term_position` TINYINT(1) NOT NULL DEFAULT 0 AFTER `position`");
    echo "713: added marksheet_template.term_position\n";
}

// ---------------------------------------------------------------------
// Migration 714: psychomotor_rating table + exam_term.next_term_begins + permission row
// ---------------------------------------------------------------------
if (!tableExists($m, 'psychomotor_rating')) {
    $m->query("CREATE TABLE `psychomotor_rating` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `branch_id` INT(11) NOT NULL,
        `session_id` INT(11) NOT NULL,
        `exam_id` INT(11) NOT NULL,
        `student_id` INT(11) NOT NULL,
        `enroll_id` INT(11) NOT NULL,
        `trait_key` VARCHAR(50) NOT NULL,
        `rating` TINYINT(1) NOT NULL,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_exam_student_trait` (`exam_id`, `student_id`, `trait_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    echo "714: created psychomotor_rating table\n";
}
if (tableExists($m, 'exam_term') && !columnExists($m, 'exam_term', 'next_term_begins')) {
    $m->query("ALTER TABLE `exam_term` ADD COLUMN `next_term_begins` DATE NULL AFTER `name`");
    echo "714: added exam_term.next_term_begins\n";
}
if (tableExists($m, 'permission')) {
    $res = $m->query("SELECT id FROM permission WHERE prefix = 'psychomotor_rating'");
    if ($res->num_rows === 0) {
        $m->query("INSERT INTO permission (module_id, name, prefix, show_view, show_add, show_edit, show_delete)
            VALUES (9, 'Psychomotor Rating', 'psychomotor_rating', 1, 1, 1, 1)");
        echo "714: inserted psychomotor_rating permission row\n";
    }
}

// ---------------------------------------------------------------------
// Migration 715: is_demo on branch
// ---------------------------------------------------------------------
if (tableExists($m, 'branch') && !columnExists($m, 'branch', 'is_demo')) {
    $m->query("ALTER TABLE `branch` ADD COLUMN `is_demo` TINYINT(1) NOT NULL DEFAULT 0");
    echo "715: added branch.is_demo\n";
}

// ---------------------------------------------------------------------
// Migration 716: modern HTML redesign for the 4 SaaS notification emails
// (also fixes mojibake in the subject lines -- see Mailer.php CharSet fix)
// ---------------------------------------------------------------------
if (tableExists($m, 'saas_email_templates')) {
    $emailTemplates = array(
        1 => array(
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
    foreach ($emailTemplates as $tplId => $tpl) {
        $stmt = $m->prepare("UPDATE saas_email_templates SET subject = ?, template_body = ?, tags = ? WHERE id = ?");
        $stmt->bind_param('sssi', $tpl['subject'], $tpl['template_body'], $tpl['tags'], $tplId);
        $stmt->execute();
    }
    echo "716: redesigned 4 saas_email_templates rows\n";
}

// ---------------------------------------------------------------------
// Migration 718: audit_log + expense_requests tables, expense_approve
// and audit_log permissions (maker/checker for Expenses, phase 1)
// ---------------------------------------------------------------------
if (!tableExists($m, 'audit_log')) {
    $m->query("CREATE TABLE `audit_log` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `actor_user_id` INT NULL,
        `actor_role_id` INT NULL,
        `branch_id` INT NULL,
        `action` VARCHAR(30) NOT NULL,
        `table_name` VARCHAR(100) NOT NULL,
        `record_id` INT NULL,
        `old_values` TEXT NULL,
        `new_values` TEXT NULL,
        `ip_address` VARCHAR(45) NULL,
        `request_url` VARCHAR(255) NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_table_record` (`table_name`, `record_id`),
        KEY `idx_actor` (`actor_user_id`),
        KEY `idx_branch_created` (`branch_id`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    echo "718: created audit_log table\n";
}

if (!tableExists($m, 'expense_requests')) {
    $m->query("CREATE TABLE `expense_requests` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `branch_id` INT NOT NULL,
        `account_id` INT NOT NULL,
        `voucher_head_id` INT NOT NULL,
        `ref_no` VARCHAR(255) NULL,
        `amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
        `date` DATE NOT NULL,
        `pay_via` VARCHAR(20) NULL,
        `description` TEXT NULL,
        `attachments` VARCHAR(255) NULL,
        `requested_by` INT NOT NULL,
        `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=pending,2=approved,3=rejected',
        `approved_by` INT NULL,
        `comments` VARCHAR(255) NULL,
        `submit_date` DATETIME NULL,
        `approve_date` DATETIME NULL,
        `transaction_id` INT NULL COMMENT 'transactions.id once approved/posted',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_branch` (`branch_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    echo "718: created expense_requests table\n";
}

function seedPermission($m, $moduleId, $name, $prefix, $showView, $showAdd, $showEdit, $showDelete)
{
    $stmt = $m->prepare("SELECT id FROM permission WHERE prefix = ?");
    $stmt->bind_param('s', $prefix);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        return (int) $row['id'];
    }
    $stmt2 = $m->prepare("INSERT INTO permission (module_id, name, prefix, show_view, show_add, show_edit, show_delete) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt2->bind_param('issiiii', $moduleId, $name, $prefix, $showView, $showAdd, $showEdit, $showDelete);
    $stmt2->execute();
    return $m->insert_id;
}

function seedStaffPrivilege($m, $roleId, $permissionId, $isView, $isAdd, $isEdit, $isDelete)
{
    $stmt = $m->prepare("SELECT id FROM staff_privileges WHERE role_id = ? AND permission_id = ?");
    $stmt->bind_param('ii', $roleId, $permissionId);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        return;
    }
    $stmt2 = $m->prepare("INSERT INTO staff_privileges (role_id, permission_id, is_view, is_add, is_edit, is_delete) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt2->bind_param('iiiiii', $roleId, $permissionId, $isView, $isAdd, $isEdit, $isDelete);
    $stmt2->execute();
}

$expenseApproveId = seedPermission($m, 17, 'Expense Approve', 'expense_approve', 1, 1, 0, 0);
$expenseApproveGrants = array(2 => [1, 1, 0, 0], 3 => [0, 0, 0, 0], 4 => [1, 1, 0, 0], 5 => [0, 0, 0, 0], 6 => [0, 0, 0, 0], 7 => [0, 0, 0, 0], 8 => [0, 0, 0, 0]);
foreach ($expenseApproveGrants as $roleId => $grant) {
    seedStaffPrivilege($m, $roleId, $expenseApproveId, $grant[0], $grant[1], $grant[2], $grant[3]);
}

$auditLogId = seedPermission($m, 18, 'Audit Log', 'audit_log', 1, 0, 0, 0);
$auditLogGrants = array(2 => [1, 0, 0, 0], 3 => [0, 0, 0, 0], 4 => [1, 0, 0, 0], 5 => [0, 0, 0, 0], 6 => [0, 0, 0, 0], 7 => [0, 0, 0, 0], 8 => [0, 0, 0, 0]);
foreach ($auditLogGrants as $roleId => $grant) {
    seedStaffPrivilege($m, $roleId, $auditLogId, $grant[0], $grant[1], $grant[2], $grant[3]);
}
echo "718: seeded expense_approve and audit_log permissions\n";

// ---------------------------------------------------------------------
// Migration 719: fee_collection_requests + fee_collection_request_items
// tables, collect_fees_approve permission (maker/checker for Fee Collection)
// ---------------------------------------------------------------------
if (!tableExists($m, 'fee_collection_requests')) {
    $m->query("CREATE TABLE `fee_collection_requests` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `branch_id` INT NOT NULL,
        `student_enroll_id` INT NOT NULL,
        `collected_by` INT NOT NULL,
        `date` DATE NOT NULL,
        `pay_via` VARCHAR(20) NULL,
        `account_id` INT NULL,
        `remarks` TEXT NULL,
        `guardian_sms` TINYINT(1) NOT NULL DEFAULT 0,
        `total_amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
        `total_discount` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
        `total_fine` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
        `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=pending,2=approved,3=rejected',
        `approved_by` INT NULL,
        `comments` VARCHAR(255) NULL,
        `submit_date` DATETIME NULL,
        `approve_date` DATETIME NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_branch` (`branch_id`),
        KEY `idx_enroll` (`student_enroll_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    echo "719: created fee_collection_requests table\n";
}

if (!tableExists($m, 'fee_collection_request_items')) {
    $m->query("CREATE TABLE `fee_collection_request_items` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `request_id` INT NOT NULL,
        `allocation_id` INT NULL,
        `type_id` INT NULL,
        `transport_fee_details_id` INT NULL,
        `amount` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
        `discount` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
        `fine` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
        `date` DATE NULL,
        `pay_via` VARCHAR(20) NULL,
        `account_id` INT NULL,
        `remarks` TEXT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_request` (`request_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    echo "719: created fee_collection_request_items table\n";
}

$collectFeesApproveId = seedPermission($m, 16, 'Collect Fees Approve', 'collect_fees_approve', 1, 1, 0, 0);
$collectFeesApproveGrants = array(2 => [1, 1, 0, 0], 3 => [0, 0, 0, 0], 4 => [1, 1, 0, 0], 5 => [0, 0, 0, 0], 6 => [0, 0, 0, 0], 7 => [0, 0, 0, 0], 8 => [0, 0, 0, 0]);
foreach ($collectFeesApproveGrants as $roleId => $grant) {
    seedStaffPrivilege($m, $roleId, $collectFeesApproveId, $grant[0], $grant[1], $grant[2], $grant[3]);
}
echo "719: seeded collect_fees_approve permission\n";

// ---------------------------------------------------------------------
// Migration 720: online_admission_staging table, online_admission_approve
// permission (maker/checker for Admissions)
// ---------------------------------------------------------------------
if (!tableExists($m, 'online_admission_staging')) {
    $m->query("CREATE TABLE `online_admission_staging` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `online_admission_id` INT NOT NULL,
        `branch_id` INT NOT NULL,
        `staged_by` INT NOT NULL,
        `staged_payload` LONGTEXT NOT NULL,
        `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=pending checker,2=approved,3=rejected',
        `reviewed_by` INT NULL,
        `comments` VARCHAR(255) NULL,
        `staged_at` DATETIME NULL,
        `reviewed_at` DATETIME NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_admission` (`online_admission_id`),
        KEY `idx_branch` (`branch_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    echo "720: created online_admission_staging table\n";
}

$onlineAdmissionApproveId = seedPermission($m, 2, 'Online Admission Approve', 'online_admission_approve', 1, 1, 0, 0);
$onlineAdmissionApproveGrants = array(2 => [1, 1, 0, 0], 3 => [0, 0, 0, 0], 4 => [0, 0, 0, 0], 5 => [0, 0, 0, 0], 6 => [0, 0, 0, 0], 7 => [0, 0, 0, 0], 8 => [0, 0, 0, 0]);
foreach ($onlineAdmissionApproveGrants as $roleId => $grant) {
    seedStaffPrivilege($m, $roleId, $onlineAdmissionApproveId, $grant[0], $grant[1], $grant[2], $grant[3]);
}
echo "720: seeded online_admission_approve permission\n";


// ---------------------------------------------------------------------
// Migration 717: default_subject/default_body on email_templates -- modern
// fallback content shown to schools that haven't customized a template yet
// ---------------------------------------------------------------------
if (tableExists($m, 'email_templates')) {
    if (!columnExists($m, 'email_templates', 'default_subject')) {
        $m->query("ALTER TABLE `email_templates` ADD COLUMN `default_subject` VARCHAR(255) NULL");
        echo "717: added email_templates.default_subject\n";
    }
    if (!columnExists($m, 'email_templates', 'default_body')) {
        $m->query("ALTER TABLE `email_templates` ADD COLUMN `default_body` TEXT NULL");
        echo "717: added email_templates.default_body\n";
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

    foreach ($defaults as $tplId => $tpl) {
        $stmt = $m->prepare("UPDATE email_templates SET default_subject = ?, default_body = ? WHERE id = ?");
        $stmt->bind_param('ssi', $tpl['default_subject'], $tpl['default_body'], $tplId);
        $stmt->execute();
    }
    echo "717: populated 18 default email_templates rows\n";
}

// ---------------------------------------------------------------------
// Migrations 721-726: mobile API foundation (memberships, tokens, devices,
// notification inbox/preferences, per-branch/branded app config, audit log,
// rate limiting, a real payment state machine, and OTP challenges)
// ---------------------------------------------------------------------
if (!tableExists($m, 'mobile_memberships')) {
    $m->query("CREATE TABLE mobile_memberships (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, login_credential_id INT NOT NULL, user_id INT NOT NULL, branch_id INT NOT NULL, role_id INT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'active', is_default TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, updated_at DATETIME NULL, UNIQUE KEY uq_mobile_membership (login_credential_id,branch_id,role_id), KEY idx_mobile_membership_user (user_id,status), KEY idx_mobile_membership_branch (branch_id,status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "721: created mobile_memberships table\n";
}

if (!tableExists($m, 'mobile_refresh_tokens')) {
    $m->query("CREATE TABLE mobile_refresh_tokens (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, membership_id BIGINT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL, family_id CHAR(36) NOT NULL, device_id BIGINT UNSIGNED NULL, expires_at DATETIME NOT NULL, last_used_at DATETIME NULL, revoked_at DATETIME NULL, replaced_by_id BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL, created_ip VARCHAR(45) NULL, UNIQUE KEY uq_mobile_refresh_hash (token_hash), KEY idx_mobile_refresh_family (family_id), KEY idx_mobile_refresh_expiry (membership_id,expires_at,revoked_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "722: created mobile_refresh_tokens table\n";
}
if (!tableExists($m, 'mobile_devices')) {
    $m->query("CREATE TABLE mobile_devices (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, membership_id BIGINT UNSIGNED NOT NULL, installation_id VARCHAR(100) NOT NULL, platform VARCHAR(20) NOT NULL, push_token VARCHAR(255) NULL, app_version VARCHAR(30) NULL, device_name VARCHAR(100) NULL, locale VARCHAR(15) NULL, push_enabled TINYINT(1) NOT NULL DEFAULT 0, last_seen_at DATETIME NULL, revoked_at DATETIME NULL, created_at DATETIME NOT NULL, updated_at DATETIME NULL, UNIQUE KEY uq_mobile_device_installation (membership_id,installation_id), KEY idx_mobile_device_push (push_token)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "722: created mobile_devices table\n";
}

if (!tableExists($m, 'mobile_notification_inbox')) {
    $m->query("CREATE TABLE mobile_notification_inbox (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, membership_id BIGINT UNSIGNED NOT NULL, branch_id INT NOT NULL, category VARCHAR(50) NOT NULL, title VARCHAR(180) NOT NULL, body TEXT NOT NULL, data_json TEXT NULL, read_at DATETIME NULL, created_at DATETIME NOT NULL, expires_at DATETIME NULL, KEY idx_mobile_inbox (membership_id,read_at,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "723: created mobile_notification_inbox table\n";
}
if (!tableExists($m, 'mobile_notification_preferences')) {
    $m->query("CREATE TABLE mobile_notification_preferences (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, membership_id BIGINT UNSIGNED NOT NULL, category VARCHAR(50) NOT NULL, push_enabled TINYINT(1) NOT NULL DEFAULT 1, inbox_enabled TINYINT(1) NOT NULL DEFAULT 1, email_enabled TINYINT(1) NOT NULL DEFAULT 0, updated_at DATETIME NOT NULL, UNIQUE KEY uq_mobile_notification_pref (membership_id,category)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "723: created mobile_notification_preferences table\n";
}

if (!tableExists($m, 'school_mobile_config')) {
    $m->query("CREATE TABLE school_mobile_config (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, branch_id INT NOT NULL, enabled TINYINT(1) NOT NULL DEFAULT 1, app_name VARCHAR(100) NULL, primary_color VARCHAR(10) NULL, logo_url VARCHAR(255) NULL, config_json TEXT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NULL, UNIQUE KEY uq_school_mobile_branch (branch_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "724: created school_mobile_config table\n";
}
if (!tableExists($m, 'branded_app_config')) {
    $m->query("CREATE TABLE branded_app_config (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, branch_id INT NOT NULL, enabled TINYINT(1) NOT NULL DEFAULT 1, app_name VARCHAR(100) NULL, primary_color VARCHAR(10) NULL, logo_url VARCHAR(255) NULL, android_package VARCHAR(150) NULL, ios_bundle_id VARCHAR(150) NULL, status VARCHAR(20) NOT NULL DEFAULT 'draft', config_json TEXT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NULL, UNIQUE KEY uq_branded_app_branch (branch_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "724: created branded_app_config table\n";
}
if (!tableExists($m, 'mobile_audit_log')) {
    $m->query("CREATE TABLE mobile_audit_log (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, membership_id BIGINT UNSIGNED NULL, branch_id INT NULL, action VARCHAR(80) NOT NULL, resource_type VARCHAR(80) NULL, resource_id VARCHAR(80) NULL, metadata_json TEXT NULL, ip_address VARCHAR(45) NULL, user_agent VARCHAR(255) NULL, created_at DATETIME NOT NULL, KEY idx_mobile_audit (branch_id,membership_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "724: created mobile_audit_log table\n";
}
if (!tableExists($m, 'mobile_rate_limits')) {
    $m->query("CREATE TABLE mobile_rate_limits (rate_key CHAR(64) PRIMARY KEY, window_started_at DATETIME NOT NULL, request_count INT NOT NULL DEFAULT 1, expires_at DATETIME NOT NULL, KEY idx_mobile_rate_expiry (expires_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "724: created mobile_rate_limits table\n";
}

if (!tableExists($m, 'payment_transactions')) {
    $m->query("CREATE TABLE payment_transactions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, branch_id INT NOT NULL, membership_id BIGINT UNSIGNED NULL, purpose VARCHAR(40) NOT NULL, resource_type VARCHAR(60) NULL, resource_id VARCHAR(80) NULL, gateway VARCHAR(30) NOT NULL, gateway_reference VARCHAR(150) NULL, idempotency_key VARCHAR(100) NOT NULL, amount DECIMAL(18,2) NOT NULL, currency CHAR(3) NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'created', failure_code VARCHAR(80) NULL, failure_message VARCHAR(255) NULL, gateway_payload LONGTEXT NULL, authorized_at DATETIME NULL, paid_at DATETIME NULL, failed_at DATETIME NULL, cancelled_at DATETIME NULL, refunded_at DATETIME NULL, created_at DATETIME NOT NULL, updated_at DATETIME NULL, UNIQUE KEY uq_payment_idempotency (branch_id,idempotency_key), UNIQUE KEY uq_payment_gateway_reference (gateway,gateway_reference), KEY idx_payment_state (branch_id,status,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "725: created payment_transactions table\n";
}

if (!tableExists($m, 'mobile_auth_challenges')) {
    $m->query("CREATE TABLE mobile_auth_challenges (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, challenge_hash CHAR(64) NOT NULL, login_credential_id INT NOT NULL, membership_id BIGINT UNSIGNED NOT NULL, challenge_type VARCHAR(20) NOT NULL, installation_id VARCHAR(100) NULL, attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 5, expires_at DATETIME NOT NULL, last_sent_at DATETIME NULL, consumed_at DATETIME NULL, created_ip VARCHAR(45) NULL, created_at DATETIME NOT NULL, UNIQUE KEY uq_mobile_auth_challenge (challenge_hash), KEY idx_mobile_auth_challenge_expiry (login_credential_id,expires_at,consumed_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "726: created mobile_auth_challenges table\n";
}

// ---------------------------------------------------------------------
// Migration 727: digital library - ebook file on `book`, so a student can read a
// PDF copy independent of physical stock/issue status
// ---------------------------------------------------------------------
if (tableExists($m, 'book')) {
    if (!columnExists($m, 'book', 'ebook_file')) {
        $m->query("ALTER TABLE `book` ADD COLUMN `ebook_file` VARCHAR(255) NULL AFTER `cover`");
        echo "727: added book.ebook_file\n";
    }
    if (!columnExists($m, 'book', 'ebook_original_name')) {
        $m->query("ALTER TABLE `book` ADD COLUMN `ebook_original_name` VARCHAR(255) NULL AFTER `ebook_file`");
        echo "727: added book.ebook_original_name\n";
    }
    if (!columnExists($m, 'book', 'ebook_uploaded_at')) {
        $m->query("ALTER TABLE `book` ADD COLUMN `ebook_uploaded_at` DATETIME NULL AFTER `ebook_original_name`");
        echo "727: added book.ebook_uploaded_at\n";
    }
}

echo "\nSchema sync complete. Now run: php application/database_seeds/seed_demo_school.php\n";
