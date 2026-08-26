<?php
/**
 * One-time schema sync for migrations 710-715 (design_style / available_all_branches /
 * term_position / psychomotor_rating / next_term_begins / branch.is_demo / FAQ content).
 *
 * Safe to run more than once — every change is guarded by an existence check first,
 * mirroring exactly what each application/migrations/71{0..5}_version_71{0..5}.php file
 * does through CodeIgniter's migration library. This script exists because this app has
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

echo "\nSchema sync complete. Now run: php application/database_seeds/seed_demo_school.php\n";
