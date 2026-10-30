<?php
/**
 * Public website content for the demo school (Frontend CMS).
 *
 * seed_demo_website() wipes the given branch's website rows and writes a complete,
 * realistic school website: settings (on the Aurora 3D template), home sections,
 * programmes, testimonials, news, events, a gallery, FAQ and every inner page's
 * banner/SEO row. Safe to run repeatedly. Used by:
 *   - database_seeds/seed_demo_school.php (fresh demo school)
 *   - database_seeds/apply_pending_migrations.php and migration 745 (existing demo schools)
 *
 * Photos are stock images bundled under uploads/frontend/ with a demo- prefix.
 * Takes a raw mysqli connection so the seeders and CI (db->conn_id) can share it.
 */

if (!function_exists('seed_demo_website')) {
    function seed_demo_website(mysqli $m, $branchId)
    {
        $branchId = (int) $branchId;
        $q = function ($v) use ($m) {
            return $v === null ? 'NULL' : "'" . $m->real_escape_string((string) $v) . "'";
        };
        $insert = function ($table, array $row) use ($m, $q) {
            $cols = '`' . implode('`,`', array_keys($row)) . '`';
            $vals = implode(',', array_map($q, array_values($row)));
            $m->query("INSERT INTO `$table` ($cols) VALUES ($vals)");
            return $m->insert_id;
        };

        $branch = $m->query("SELECT name, email, mobileno, address FROM branch WHERE id = $branchId")->fetch_assoc();
        if (!$branch) {
            return 'skipped: branch ' . $branchId . ' not found';
        }
        $school = $branch['name'];
        $phone = $branch['mobileno'] ?: '0800 000 0000';
        $address = $branch['address'] ?: 'Lekki Phase 1, Lagos';

        // URL alias: 'demo_academy'. When an older or emptier demo school (or a
        // school that no longer exists) holds it, it moves to demo_academy_<id> so
        // /demo_academy always shows the demo with real students. A real school
        // that owns the name keeps it, and this demo falls back to demo_academy_<id>.
        $alias = 'demo_academy';
        $students = function ($id) use ($m) {
            return (int) $m->query("SELECT COUNT(*) AS n FROM enroll WHERE branch_id = " . (int) $id)->fetch_assoc()['n'];
        };
        $owner = $m->query("SELECT fs.branch_id, b.id AS still_there, b.is_demo FROM front_cms_setting fs
            LEFT JOIN branch b ON b.id = fs.branch_id
            WHERE fs.url_alias = " . $q($alias) . " AND fs.branch_id <> $branchId")->fetch_assoc();
        if ($owner) {
            $stale = empty($owner['still_there']) || ($owner['is_demo'] == 1 && $students($owner['branch_id']) < $students($branchId));
            if ($stale) {
                $m->query("UPDATE front_cms_setting SET url_alias = " . $q($alias . '_' . $owner['branch_id']) . " WHERE branch_id = " . (int) $owner['branch_id']);
            } else {
                $alias .= '_' . $branchId;
            }
        }
        $site = function ($path = '') use ($alias) {
            return '/' . $alias . ($path === '' ? '' : '/' . $path);
        };

        // ---- wipe this branch's existing website content ----
        $tables = array('front_cms_setting', 'front_cms_home', 'front_cms_home_seo', 'front_cms_about', 'front_cms_teachers',
            'front_cms_events', 'front_cms_gallery', 'front_cms_news', 'front_cms_faq', 'front_cms_admission', 'front_cms_contact',
            'front_cms_exam_results', 'front_cms_admitcard', 'front_cms_certificates', 'front_cms_services', 'front_cms_services_list',
            'front_cms_testimonial', 'front_cms_news_list', 'front_cms_gallery_category', 'front_cms_gallery_content', 'front_cms_faq_list');
        foreach ($tables as $t) {
            $m->query("DELETE FROM `$t` WHERE branch_id = $branchId");
        }
        $m->query("DELETE FROM event WHERE branch_id = $branchId AND image LIKE 'demo-event-%'");

        // ---- settings ----
        $insert('front_cms_setting', array(
            'application_title' => $school, 'url_alias' => $alias, 'cms_active' => 1, 'online_admission' => 1, 'theme' => 'aurora',
            'captcha_status' => 'disable', 'recaptcha_site_key' => '', 'recaptcha_secret_key' => '',
            'address' => $address, 'mobile_no' => $phone, 'fax' => $phone,
            'receive_contact_email' => 'info@schooledge.com.ng', 'email' => $branch['email'] ?: 'info@schooledge.com.ng',
            'copyright_text' => '&copy; ' . date('Y') . ' ' . $school . '. A SkoolOS demo school.',
            'fav_icon' => 'demo-favicon.png', 'logo' => 'demo-logo.png',
            'footer_about_text' => $school . ' is a co-educational nursery, primary and secondary school where every child is known, stretched and supported to become a confident, kind and capable young person.',
            'working_hours' => 'Mon - Fri: 7:30 AM - 4:00 PM', 'google_analytics' => '',
            'primary_color' => '#7c5cff', 'menu_color' => '#ffffff', 'hover_color' => '#5b3fd6', 'text_color' => '#1d2440',
            'text_secondary_color' => '#646d87', 'footer_background_color' => '#0b0f1f', 'footer_text_color' => '#c8cee8',
            'copyright_bg_color' => '#070a16', 'copyright_text_color' => '#9aa3c4', 'border_radius' => '12',
            'facebook_url' => 'https://facebook.com', 'twitter_url' => 'https://x.com', 'youtube_url' => 'https://youtube.com',
            'google_plus' => '', 'linkedin_url' => '', 'pinterest_url' => '', 'instagram_url' => 'https://instagram.com',
            'branch_id' => $branchId,
        ));

        // ---- home sections ----
        $home = function ($type, $title, $description, $elements = '', $subtitle = null) use ($insert, $branchId) {
            $insert('front_cms_home', array('title' => $title, 'subtitle' => $subtitle, 'item_type' => $type, 'description' => $description,
                'elements' => is_array($elements) ? json_encode($elements) : $elements, 'color1' => null, 'color2' => null, 'branch_id' => $branchId, 'active' => 1));
        };
        $home('slider', 'Where curious minds grow into confident leaders',
            'A warm, ambitious school community in the heart of Lekki: strong academics, rich co-curricular life and teachers who know every child by name.',
            array('position' => 'c-left', 'button_text1' => 'Apply for admission', 'button_url1' => $site('admission'), 'button_text2' => 'Book a school tour', 'button_url2' => $site('contact'), 'image' => 'demo-slider-1.jpg'));
        $home('slider', 'Excellence in every classroom',
            'Small classes, modern labs and a broad curriculum from Nursery to SS3.',
            array('position' => 'c-left', 'button_text1' => 'Explore our school', 'button_url1' => $site('about'), 'button_text2' => 'Contact us', 'button_url2' => $site('contact'), 'image' => 'demo-slider-2.jpg'));
        foreach (array(
            array('Academic excellence', 'A rigorous, broad curriculum with outstanding WAEC and NECO results year after year.', 'fas fa-graduation-cap'),
            array('Caring teachers', 'Qualified, passionate teachers and small class sizes, so every child gets attention.', 'fas fa-chalkboard-teacher'),
            array('Safe, modern campus', 'Secure grounds, science and ICT labs, a library and plenty of space to play.', 'fas fa-shield-alt'),
            array('Parents in the loop', 'Results, attendance and fees online, plus instant SMS updates from the school.', 'fas fa-mobile-alt'),
        ) as $f) {
            $home('features', $f[0], $f[1], array('button_text' => 'Learn more', 'button_url' => $site('about'), 'icon' => $f[2]));
        }
        $home('wellcome', 'A school where every child is known',
            "Since our founding, " . $school . " has combined high academic standards with genuine care.\nOur teachers know each pupil by name, nurture their strengths and help them grow in confidence, character and curiosity.\n\nFrom our nursery class to SS3, children learn in bright classrooms, explore in modern labs and discover new passions in sport, music, art and clubs.",
            array('image' => 'demo-welcome.jpg'), 'Welcome to ' . $school);
        $home('teachers', 'Experienced, caring teachers', 'Our teachers are qualified specialists who love what they do.', array('teacher_start' => '0', 'image' => 'featured-parallax1.jpg'));
        $home('services', 'A complete education, nursery to secondary',
            'Rich programmes at every stage, delivered by specialists and supported by technology.');
        $home('statistics', 'Our school in numbers', 'Growing every term.',
            array('image' => 'counter-parallax1.jpg',
                'widget_title_1' => 'Students', 'widget_icon_1' => 'fas fa-user-graduate', 'type_1' => 'student',
                'widget_title_2' => 'Teachers & staff', 'widget_icon_2' => 'fas fa-chalkboard-teacher', 'type_2' => 'employees',
                'widget_title_3' => 'Classes', 'widget_icon_3' => 'fas fa-school', 'type_3' => 'class',
                'widget_title_4' => 'Subjects', 'widget_icon_4' => 'fas fa-book-open', 'type_4' => 'subject'));
        $home('testimonial', 'What our parents and students say', 'Families tell us what makes our school special.');
        $home('cta', 'Admissions are open for the new session', '',
            array('mobile_no' => $phone, 'button_text' => 'Apply for admission', 'button_url' => $site('admission')));

        // ---- programmes ----
        $insert('front_cms_services', array('title' => 'Why choose us', 'subtitle' => 'A complete education', 'parallax_image' => 'service_parallax1.jpg', 'branch_id' => $branchId));
        foreach (array(
            array('Early Years (Nursery)', 'Play-based learning that builds early literacy, numeracy and social skills.', 'fas fa-shapes'),
            array('Primary School', 'Strong foundations in English, Maths and Science with phonics and reading programmes.', 'fas fa-book-reader'),
            array('Secondary School', 'JSS and SSS with sciences, arts and commercial subjects, and WAEC/NECO preparation.', 'fas fa-flask'),
            array('ICT & Coding', 'Computer lab sessions, coding clubs and digital skills from Primary 1 upwards.', 'fas fa-laptop-code'),
            array('Sports & Clubs', 'Football, athletics, music, drama, debate and over a dozen after-school clubs.', 'fas fa-futbol'),
            array('Pastoral Care', 'Form tutors, counselling and a strong anti-bullying culture keep children happy and safe.', 'fas fa-hands-helping'),
        ) as $s) {
            $insert('front_cms_services_list', array('title' => $s[0], 'description' => $s[1], 'icon' => $s[2], 'branch_id' => $branchId));
        }

        // ---- testimonials ----
        foreach (array(
            array('Mrs. Adaeze Okafor', 'Parent, Primary 4', 'My daughter has blossomed here. The teachers genuinely care, and I can see her results and attendance on my phone.'),
            array('Mr. Tunde Bakare', 'Parent, JSS 2', 'Communication is excellent. I get an SMS the moment anything important happens, and fees are easy to pay online.'),
            array('Chiamaka E.', 'SS3 student', 'Great teachers, a brilliant science lab and so many clubs. I feel ready for my exams and for university.'),
        ) as $t) {
            $insert('front_cms_testimonial', array('name' => $t[0], 'surname' => $t[1], 'image' => '', 'description' => $t[2], 'rank' => 5, 'branch_id' => $branchId, 'created_by' => 1));
        }

        // ---- news ----
        foreach (array(
            array('Our students win the Lagos Inter-School Science Fair', 'demo-news-1.jpg', -6, 'Our SS2 team took first place at this year\'s Lagos Inter-School Science Fair with a low-cost water purification project.'),
            array('New library and reading corner opens', 'demo-news-2.jpg', -14, 'Over 2,000 new books, quiet study spaces and a cosy reading corner for our youngest readers are now open to all classes.'),
            array('Second term resumption: what parents need to know', 'demo-news-3.jpg', -21, 'Timetables, fees and important dates for the new term are now available on the parent portal.'),
        ) as $i => $n) {
            $insert('front_cms_news_list', array('title' => $n[0], 'description' => '<p>' . $n[3] . '</p>', 'image' => $n[1],
                'alias' => 'demo-news-' . ($i + 1), 'date' => date('Y-m-d', strtotime($n[2] . ' day')), 'show_web' => 1, 'branch_id' => $branchId, 'created_at' => date('Y-m-d H:i:s')));
        }

        // ---- events (the website lists events running within the next week, so these span a term) ----
        $session = (int) ($m->query("SELECT session_id FROM global_settings LIMIT 1")->fetch_assoc()['session_id'] ?? 0);
        $admin = $m->query("SELECT staff.id FROM staff JOIN login_credential lc ON lc.user_id = staff.id AND lc.role = 2 WHERE staff.branch_id = $branchId LIMIT 1")->fetch_assoc();
        $eventType = function ($name) use ($m, $q, $branchId) {
            $row = $m->query("SELECT id FROM event_types WHERE branch_id = $branchId AND name = " . $q($name) . " LIMIT 1")->fetch_assoc();
            return $row ? $row['id'] : 'holiday';
        };
        foreach (array(
            array('Open Day and School Tours', 'Meet our teachers, tour the campus and see classes in action. Every Saturday this term.', 'demo-event-1.jpg', 0, 90, 'Cultural'),
            array('Inter-House Sports Season', 'Weekly heats leading up to our Inter-House Sports Day finale.', 'demo-event-2.jpg', 2, 75, 'Sports'),
            array('Science and Coding Club Showcase', 'Students present robotics, coding and science projects to parents.', 'demo-event-3.jpg', 5, 60, 'Academic'),
        ) as $e) {
            $insert('event', array('title' => $e[0], 'remark' => $e[1], 'status' => 1, 'type' => $eventType($e[5]), 'audition' => 1, 'selected_list' => '',
                'start_date' => date('Y-m-d', strtotime($e[3] . ' day')), 'end_date' => date('Y-m-d', strtotime($e[4] . ' day')),
                'image' => $e[2], 'created_by' => $admin ? $admin['id'] : '1', 'session_id' => $session, 'branch_id' => $branchId, 'show_web' => 1));
        }

        // ---- gallery ----
        $categoryId = $insert('front_cms_gallery_category', array('name' => 'School life', 'branch_id' => $branchId));
        foreach (array('In the classroom', 'Learning together', 'Our students', 'ICT lab', 'Teachers and pupils', 'Proud moments') as $i => $title) {
            $file = 'demo-gallery-' . ($i + 1) . '.jpg';
            $insert('front_cms_gallery_content', array('title' => $title, 'alias' => 'demo-gallery-' . ($i + 1), 'description' => $title . ' at ' . $school . '.',
                'thumb_image' => $file, 'date' => date('Y-m-d'), 'category_id' => $categoryId, 'added_by' => $admin ? $admin['id'] : 1, 'file_type' => '1',
                'elements' => json_encode(array('1' => array('type' => 1, 'image' => $file))), 'show_web' => 1, 'branch_id' => $branchId, 'created_at' => date('Y-m-d')));
        }

        // ---- FAQ ----
        $insert('front_cms_faq', array('title' => 'Frequently asked questions', 'description' => 'Answers to the questions parents ask us most.', 'page_title' => 'FAQ', 'banner_image' => 'faq1.jpg', 'meta_description' => '', 'meta_keyword' => '', 'branch_id' => $branchId));
        foreach (array(
            array('What ages do you admit?', 'We admit children from Nursery 1 (age 3) through to SS1, subject to an entrance assessment.'),
            array('How do I apply?', 'Apply online through the Admission page, or visit the school office for a paper form and a tour.'),
            array('Can I follow my child\'s progress online?', 'Yes. Parents get a portal and app to see results, attendance, homework and fees.'),
            array('Do you offer school transport?', 'Yes, we run buses on several routes across Lekki, Ajah and Victoria Island.'),
        ) as $f) {
            $insert('front_cms_faq_list', array('title' => $f[0], 'description' => '<p>' . $f[1] . '</p>', 'branch_id' => $branchId));
        }

        // ---- inner pages ----
        $insert('front_cms_home_seo', array('page_title' => 'Home', 'meta_keyword' => 'school, Lekki, nursery, primary, secondary', 'meta_description' => $school . ' - nursery, primary and secondary school in Lekki, Lagos.', 'branch_id' => $branchId));
        $insert('front_cms_about', array('title' => 'About ' . $school, 'subtitle' => 'Our story', 'page_title' => 'About Us',
            'content' => '<p>' . $school . ' was founded on a simple belief: every child can thrive when they are known, challenged and cared for.</p><p>Today we serve families across Lekki with a broad curriculum, specialist teachers and a strong sense of community.</p><ul><li>Small class sizes</li><li>Specialist science, ICT and language teachers</li><li>Rich sports, arts and clubs programme</li></ul>',
            'banner_image' => 'about1.jpg', 'about_image' => 'about1.jpg',
            'elements' => json_encode(array('cta_title' => 'Come and see us in person', 'button_text' => 'Book a tour', 'button_url' => 'contact')),
            'meta_description' => '', 'meta_keyword' => '', 'branch_id' => $branchId));
        $insert('front_cms_teachers', array('page_title' => 'Our Teachers', 'banner_image' => 'teachers1.jpg', 'meta_description' => '', 'meta_keyword' => '', 'branch_id' => $branchId));
        $insert('front_cms_events', array('title' => 'Upcoming events', 'description' => 'What is happening at school this term.', 'page_title' => 'Events', 'banner_image' => 'event1.jpg', 'meta_description' => '', 'meta_keyword' => '', 'branch_id' => $branchId));
        $insert('front_cms_gallery', array('page_title' => 'Gallery', 'banner_image' => 'gallery1.jpg', 'meta_description' => '', 'meta_keyword' => '', 'branch_id' => $branchId));
        $insert('front_cms_news', array('title' => 'School news', 'description' => 'The latest from our classrooms and community.', 'page_title' => 'News', 'banner_image' => 'news1.jpg', 'meta_description' => '', 'meta_keyword' => '', 'branch_id' => $branchId));
        $insert('front_cms_admission', array('title' => 'Apply for admission', 'description' => '<p>Complete the form below to apply. Our admissions team will contact you to arrange an assessment and a school tour.</p>',
            'page_title' => 'Online Admission', 'terms_conditions_title' => 'Terms and conditions', 'terms_conditions_description' => '<p>This is a demo school. Applications submitted here are for demonstration only.</p>',
            'fee_elements' => '[]', 'banner_image' => 'admission1.jpg', 'meta_description' => '', 'meta_keyword' => '', 'application_form_name' => '', 'application_form_file' => '', 'branch_id' => $branchId));
        $insert('front_cms_contact', array('box_title' => 'We would love to hear from you', 'box_description' => 'Questions about admissions, fees or visiting? Send us a message or call the school office.',
            'box_image' => 'contact-info-box1.jpg', 'form_title' => 'Send us a message', 'address' => $address, 'phone' => $phone, 'email' => $branch['email'] ?: 'info@schooledge.com.ng',
            'submit_text' => 'Send message', 'map_iframe' => '<iframe src="https://www.google.com/maps?q=Lekki+Phase+1+Lagos&output=embed" width="100%" height="380" style="border:0" loading="lazy"></iframe>',
            'page_title' => 'Contact Us', 'banner_image' => 'contact1.jpg', 'meta_description' => '', 'meta_keyword' => '', 'branch_id' => $branchId));
        $insert('front_cms_exam_results', array('page_title' => 'Exam Results', 'grade_scale' => 1, 'attendance' => 1, 'banner_image' => 'exam_results1.jpg', 'description' => 'Check results with a student\'s register number.', 'meta_description' => '', 'meta_keyword' => '', 'branch_id' => $branchId));
        $admitTemplate = $m->query("SELECT id FROM card_templete WHERE branch_id = $branchId AND card_type = 2 LIMIT 1")->fetch_assoc();
        $insert('front_cms_admitcard', array('page_title' => 'Admit Card', 'templete_id' => $admitTemplate ? $admitTemplate['id'] : 0, 'banner_image' => 'admit_card1.jpg', 'description' => 'Print your examination admit card.', 'meta_description' => '', 'meta_keyword' => '', 'branch_id' => $branchId));
        $insert('front_cms_certificates', array('page_title' => 'Certificates', 'banner_image' => 'certificates1.jpg', 'description' => 'Download issued certificates.', 'meta_description' => '', 'meta_keyword' => '', 'branch_id' => $branchId));

        return 'demo website ready at /' . $alias;
    }
}
