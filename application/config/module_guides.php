<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * In-app guides, one per module (read through module_guides() in general_helper).
 *
 *   title, icon, group   how the guide is listed in the Guide Center
 *   match                URL paths the guide explains; the "Guide" button on a
 *                        page opens the guide with the longest matching path
 *   perms                staff see the guide when they can view any of these
 *   module               hidden when that module is switched off (moduleIsEnabled)
 *   school / teacher / student / parent / super
 *                        one block per audience: summary, steps [title, text, url],
 *                        tips [], faq [[question, answer]]
 *
 * Teachers fall back to the school block when a guide has no teacher block, so a
 * teacher with extra permissions still gets help. Step text is trusted HTML.
 */
return array(

    /* ------------------------------------------------------------------ */
    'getting_started' => array(
        'title' => 'Getting started',
        'icon' => 'fa-rocket',
        'group' => 'Getting started',
        'match' => array('dashboard', 'userguide'),
        'perms' => array('school_settings'),
        'school' => array(
            'summary' => 'Set your school up in this order. Each step unlocks the next, so classes come before students and exams come before report cards.',
            'steps' => array(
                array('Finish the school profile', 'Add your logo, address, currency, timezone and weekends under <b>Settings › School Settings</b>. Turn on payment, SMS and email there too if you plan to use them.', 'school_settings'),
                array('Check the academic session', 'Make sure the current session (for example 2025–2026) is set under <b>Settings › Session Settings</b>. Everything you add is filed under that session.', 'sessions'),
                array('Create classes and sections', 'Add each class (JSS 1, Primary 4…) and its sections (A, B, Gold…) under <b>Academic › Class › Control Classes</b>.', 'classes'),
                array('Add subjects and assign them', 'Create subjects under <b>Academic › Subject</b>, then link them to each class and teacher with <b>Class Assign</b>.', 'subject/class_assign'),
                array('Add your staff', 'Create departments and designations, then add each teacher and staff member under <b>Employee › Add Employee</b>. Each gets a login.', 'employee/add'),
                array('Admit students', 'Admit one student at a time under <b>Admission › Create Admission</b>, or upload a spreadsheet with <b>Multiple Import</b>. Parents are created at the same time.', 'student/add'),
                array('Build the timetable', 'Set each class\'s periods under <b>Academic › Class Schedule</b>. Teachers and students then see their day on the dashboard.', 'timetable/viewclass'),
                array('Set up fees', 'Create fee types, group them, then allocate the group to students under <b>Student Accounting</b>.', 'fees/type'),
                array('Prepare exams', 'Add exam terms, grades and mark distribution, then create the exam under <b>Exam Master</b>. Teachers can then enter marks.', 'exam/term'),
            ),
            'tips' => array(
                'The setup checklist at the top of the Guide Center ticks itself off as you go.',
                'Every page has a <b>Guide</b> button in the bottom corner that opens the steps for that page.',
                'Give staff only the access they need under <b>Settings › Role Permission</b>.',
            ),
        ),
        'super' => array(
            'summary' => 'You run the whole platform. Create schools, look after subscriptions, and open any school to help it set up.',
            'steps' => array(
                array('Set platform defaults', 'Check the platform name, logo, currency and default language under <b>Settings › Global Settings</b>.', 'settings/universal'),
                array('Create a school', 'Add a school under <b>Branch</b>. Each school keeps its own classes, students, fees and website.', 'branch'),
                array('Set roles and permissions', 'Decide what each role (admin, teacher, accountant, librarian…) can see and change under <b>Settings › Role Permission</b>.', 'role'),
                array('Open a school\'s dashboard', 'Use the school switcher on the dashboard to see things exactly as that school\'s admin does.', 'dashboard'),
            ),
            'tips' => array('Each school\'s admin can follow the "Getting started" steps themselves once their account exists.'),
        ),
        'teacher' => array(
            'summary' => 'Your dashboard shows today\'s classes, the students you teach and the homework waiting to be marked.',
            'steps' => array(
                array('Check your day', 'Open the <b>Dashboard</b> to see today\'s periods. The class happening now is highlighted.', 'dashboard'),
                array('Take attendance', 'Mark each class present, late or absent under <b>Attendance › Student</b>.', 'attendance/student_entry'),
                array('Set and mark homework', 'Post homework for a class and grade it after the due date under <b>Homework</b>.', 'homework'),
                array('Enter exam scores', 'Enter CA and exam marks for your subjects under <b>Exam Master › Marks › Mark Entries</b>.', 'exam/mark_entry'),
                array('Message students and parents', 'Use <b>Message</b> to talk to students, parents and colleagues inside the app.', 'communication/mailbox/inbox'),
            ),
            'tips' => array('Change your password and photo from the profile menu at the top right.'),
        ),
        'student' => array(
            'summary' => 'Everything about your school day is in one place: classes, homework, results, fees and messages.',
            'steps' => array(
                array('Look at your dashboard', 'See today\'s classes, your attendance rate, homework due, upcoming exams and your fee balance.', 'dashboard'),
                array('Do your homework', 'Open <b>Academic › Homework</b>, read the task and upload your answer before the due date.', 'userrole/homework'),
                array('Check your results', 'Open <b>Exam Master › Report Card</b> once your school publishes results.', 'userrole/report_card'),
                array('Take online exams', 'CBT exams appear under <b>Exam Master › Online Exam</b> when they open.', 'userrole/online_exam'),
                array('Keep your profile up to date', 'Check your details and change your password under <b>Profile</b>.', 'profile'),
            ),
        ),
        'parent' => array(
            'summary' => 'Follow each child\'s attendance, homework, results and fees, and pay fees online.',
            'steps' => array(
                array('Pick a child', 'Open <b>Dashboard › My Children</b> and choose a child. Every page then shows that child\'s information.', 'parents/my_children'),
                array('Read the dashboard', 'See the child\'s attendance, fee balance, homework, timetable and upcoming exams at a glance.', 'dashboard'),
                array('Pay fees', 'Open <b>Fees History</b> to see invoices and pay online, or record a bank payment for the school to confirm.', 'userrole/invoice'),
                array('See results', 'Open <b>Exam Master › Report Card</b> after the school publishes results.', 'userrole/report_card'),
                array('Talk to the school', 'Send a message to a teacher or the office under <b>Message</b>.', 'communication/mailbox/inbox'),
            ),
            'tips' => array('With more than one child, switch between them from the photos on the dashboard banner.'),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'school_settings' => array(
        'title' => 'School settings',
        'icon' => 'fa-cogs',
        'group' => 'Getting started',
        'match' => array('school_settings', 'settings', 'sessions', 'role', 'modules', 'translations', 'custom_field', 'system_student_field', 'cron_api', 'backup'),
        'perms' => array('school_settings', 'global_settings', 'translations', 'custom_field', 'backup'),
        'school' => array(
            'summary' => 'Settings control how the whole school behaves: its details, payment and messaging providers, who can do what, and which modules are switched on.',
            'steps' => array(
                array('School details', 'Under <b>Settings › School Settings › School Details</b>, set the school name, logo, address, currency, timezone and weekends. Student and parent username rules are set here too.', 'school_settings'),
                array('Payment settings', 'Add your payment gateway keys (Paystack, Flutterwave and others) under the <b>Payment Settings</b> tab so parents can pay fees online.', 'school_settings'),
                array('SMS, email and WhatsApp', 'Connect your SMS provider and email account in their tabs, then switch on the messages you want sent automatically (admission, fee payment, absence…).', 'school_settings'),
                array('Attendance type', 'Choose <b>day-wise</b> (one register a day) or <b>subject-wise</b> (a register every period) under the <b>Attendance Type</b> tab.', 'school_settings'),
                array('Session', 'Create next year\'s session and switch to it under <b>Settings › Session Settings</b> when the new year starts.', 'sessions'),
                array('Roles and permissions', 'For each role, tick what it can view, add, edit and delete under <b>Settings › Role Permission</b>.', 'role'),
                array('Modules', 'Switch off modules you don\'t use (hostel, transport, inventory…) so menus stay short.', 'modules'),
                array('Custom fields', 'Add extra fields to admission or staff forms (for example State of Origin) under <b>Settings › Custom Field</b>.', 'custom_field'),
            ),
            'tips' => array(
                'Take a <b>Database Backup</b> before big changes such as promoting students.',
                'Translations let you rename any word in the app, for example "Section" to "Arm".',
            ),
            'faq' => array(
                array('Parents say they can\'t log in.', 'Check that parent login is enabled under <b>School Settings › Student & Parent Panel</b>, then check the parent\'s account under <b>Parents › Login Deactivate</b>.'),
                array('Online payment button is missing for parents.', 'Add and enable a gateway under <b>Payment Settings</b>. The button only appears once a gateway is active.'),
            ),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'academic' => array(
        'title' => 'Classes, sections & subjects',
        'icon' => 'fa-chalkboard',
        'group' => 'Academics',
        'match' => array('classes', 'sections', 'subject', 'student_promotion', 'userrole/subject', 'userrole/teacher', 'multiclass'),
        'perms' => array('classes', 'subject', 'subject_class_assign', 'assign_class_teacher', 'student_promotion'),
        'school' => array(
            'summary' => 'Classes and sections are the backbone of the school. Admissions, timetables, attendance, exams and fees all hang off them.',
            'steps' => array(
                array('Create sections', 'Add the section names you use (A, B, Gold, Diamond…) under <b>Academic › Class › Control Classes</b>. One section can be shared by many classes.', 'classes'),
                array('Create classes', 'Add each class with its numeric order (JSS 1 = 7, JSS 2 = 8…) and tick the sections it has. The order is used when promoting.', 'classes'),
                array('Assign class teachers', 'Pick a form teacher for every class and section under <b>Assign Class Teacher</b>. They can then take its attendance and see its students.', 'classes/teacher_allocation'),
                array('Add subjects', 'Create each subject with a code and type (theory, practical, optional) under <b>Academic › Subject</b>.', 'subject/index'),
                array('Assign subjects to classes', 'Under <b>Class Assign</b>, choose a class and section, tick its subjects and choose the subject teacher for each.', 'subject/class_assign'),
                array('Promote at year end', 'When the session ends, move students to their next class in one go under <b>Academic › Promotion</b>. You can hold back or graduate individual students.', 'student_promotion'),
            ),
            'tips' => array(
                'Set up next year\'s session before you promote, because promotion moves students into it.',
                'A teacher only sees classes in Mark Entry and Homework once a subject in that class is assigned to them.',
            ),
            'faq' => array(
                array('A teacher can\'t see a class.', 'Assign them a subject in that class under <b>Class Assign</b>, or make them its class teacher.'),
            ),
        ),
        'teacher' => array(
            'summary' => 'Your classes and subjects are set by the school admin. Once assigned, they appear in attendance, homework and mark entry.',
            'steps' => array(
                array('See what you teach', 'Your dashboard counts your classes, subjects and students. Your weekly periods are under <b>Academic › Class Schedule › Teacher</b>.', 'timetable/teacherview'),
                array('Missing a class?', 'Ask your admin to assign you the subject under <b>Academic › Subject › Class Assign</b>.', ''),
            ),
        ),
        'student' => array(
            'summary' => 'See the subjects you take and who teaches them.',
            'steps' => array(
                array('Your subjects', 'Open <b>Academic › Subject</b> for your subjects and their teachers.', 'userrole/subject'),
                array('Your teachers', 'Open <b>Teachers</b> to see your teachers\' names and contacts.', 'userrole/teacher'),
            ),
        ),
        'parent' => array(
            'summary' => 'See your child\'s subjects and teachers.',
            'steps' => array(
                array('Subjects', 'Open <b>Academic › Subject</b> with your child selected.', 'userrole/subject'),
                array('Teachers', 'Open <b>Teachers</b> to see who teaches your child and how to reach them.', 'userrole/teacher'),
            ),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'timetable' => array(
        'title' => 'Class timetable',
        'icon' => 'fa-clock',
        'group' => 'Academics',
        'match' => array('timetable/viewclass', 'timetable/teacherview', 'timetable/set_classwise', 'timetable', 'userrole/class_schedule'),
        'perms' => array('class_timetable', 'teacher_timetable'),
        'school' => array(
            'summary' => 'The timetable sets each class\'s periods for every day of the week. It powers "Today\'s classes" on teacher and student dashboards.',
            'steps' => array(
                array('Add a class schedule', 'Open <b>Academic › Class Schedule › Class</b>, pick a class and section, choose the day and add each period with its subject, teacher, room and times.', 'timetable/viewclass'),
                array('Add breaks', 'Tick <b>Break</b> on a period to add break time. Breaks show as striped rows on dashboards.', 'timetable/viewclass'),
                array('Check each teacher', 'Open <b>Class Schedule › Teacher</b> to see one teacher\'s week and spot clashes.', 'timetable/teacherview'),
            ),
            'tips' => array('Add subjects to the class under Class Assign first, or they won\'t appear in the subject list.'),
        ),
        'teacher' => array(
            'summary' => 'See where you need to be and when.',
            'steps' => array(
                array('Today', 'Your dashboard lists today\'s periods and highlights the one happening now.', 'dashboard'),
                array('The whole week', 'Open <b>Academic › Class Schedule › Teacher</b> for your full timetable.', 'timetable/teacherview'),
            ),
        ),
        'student' => array(
            'summary' => 'Know what lesson is next.',
            'steps' => array(
                array('Today', 'Your dashboard shows today\'s lessons and highlights the current one.', 'dashboard'),
                array('The whole week', 'Open <b>Academic › Class</b> for the full timetable.', 'userrole/class_schedule'),
            ),
        ),
        'parent' => array(
            'summary' => 'See your child\'s lessons for the week.',
            'steps' => array(array('Timetable', 'Open <b>Academic › Class</b> with your child selected.', 'userrole/class_schedule')),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'admission' => array(
        'title' => 'Admission',
        'icon' => 'fa-user-plus',
        'group' => 'Students & parents',
        'match' => array('student/add', 'student/csv_import', 'student/category', 'online_admission'),
        'perms' => array('student', 'online_admission', 'multiple_import', 'student_category'),
        'school' => array(
            'summary' => 'Admit students one at a time, in bulk from a spreadsheet, or through the online admission form on your website.',
            'steps' => array(
                array('Student categories (optional)', 'Create groups such as Day, Boarding or Scholarship under <b>Admission › Category</b>. They help with fees and reports.', 'student/category'),
                array('Admit a student', 'Open <b>Admission › Create Admission</b>. Choose the class and section, fill in the student\'s details and photo, and add the parent or pick an existing one.', 'student/add'),
                array('Logins are created for you', 'The student and parent each get a username and password using the rules in School Settings. Print them from <b>Reports › Student › Login Credential</b>.', 'student/login_credential_reports'),
                array('Import many students', 'Download the sample file under <b>Multiple Import</b>, fill it in Excel, and upload it for a whole class at once.', 'student/csv_import'),
                array('Online admission', 'Switch on online admission in <b>Frontend › Setting</b>. Applications arrive under <b>Admission › Online Admission</b>; review them and approve to admit.', 'online_admission/index'),
            ),
            'tips' => array(
                'Siblings: pick the existing parent when admitting the second child so both appear in one parent account.',
                'The register number is generated automatically using your prefix from School Settings.',
            ),
            'faq' => array(
                array('The import fails.', 'Use the sample file exactly as downloaded. Dates must be in the format shown, and the class and section must already exist.'),
            ),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'students' => array(
        'title' => 'Students',
        'icon' => 'fa-user-graduate',
        'group' => 'Students & parents',
        'match' => array('student/view', 'student/profile', 'student/disable_authentication', 'student/disable_reason', 'userrole/idCard'),
        'perms' => array('student', 'student_disable_authentication'),
        'school' => array(
            'summary' => 'The student list is where you find, update, deactivate and print anything about a student.',
            'steps' => array(
                array('Find a student', 'Open <b>Student Details › Student List</b>, pick a class and section, or search by name or register number.', 'student/view'),
                array('Open the profile', 'Click a student to edit details, upload documents, see fees, attendance and results, and reset their password.', 'student/view'),
                array('Deactivate a login', 'Stop a student signing in (for example while fees are owed) under <b>Login Deactivate</b>. Their records are kept.', 'student/disable_authentication'),
                array('Students who leave', 'Record why under <b>Deactivate Reason</b>, then deactivate them. Move graduates to <b>Alumni</b>.', 'student/disable_reason'),
            ),
        ),
        'student' => array(
            'summary' => 'Keep your details correct and your password safe.',
            'steps' => array(
                array('Your profile', 'Open <b>Profile</b> to check your details and photo.', 'profile'),
                array('Change your password', 'Use the profile menu at the top right and choose <b>Password</b>.', ''),
                array('Your ID card', 'Open <b>Id Card</b> to view or print your school ID card.', 'userrole/idCard'),
            ),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'parents' => array(
        'title' => 'Parents',
        'icon' => 'fa-user-friends',
        'group' => 'Students & parents',
        'match' => array('parents/view', 'parents/add', 'parents/edit', 'parents/disable_authentication', 'parents/my_children'),
        'perms' => array('parent', 'parent_disable_authentication'),
        'school' => array(
            'summary' => 'Each parent account can hold several children, so one login follows all of them.',
            'steps' => array(
                array('Parents are usually made at admission', 'When admitting a student, add the parent there or choose an existing one for siblings.', 'student/add'),
                array('Add a parent on their own', 'Use <b>Parents › Add Parent</b>, then link children by choosing this parent on each student\'s profile.', 'parents/add'),
                array('Help a parent sign in', 'Open the parent in <b>Parents List</b> to see their username and reset the password.', 'parents/view'),
                array('Block a login', 'Turn a parent login off or back on under <b>Login Deactivate</b>.', 'parents/disable_authentication'),
            ),
        ),
        'parent' => array(
            'summary' => 'One login covers all your children at this school.',
            'steps' => array(
                array('Choose a child', 'Open <b>Dashboard › My Children</b> and pick one. Every page then shows that child.', 'parents/my_children'),
                array('Switch child', 'Use the child photos on the dashboard banner, or go back to My Children.', 'dashboard'),
                array('A child is missing', 'Ask the school office to link the child to your account.', ''),
            ),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'employees' => array(
        'title' => 'Employees',
        'icon' => 'fa-users',
        'group' => 'Staff & HR',
        'match' => array('employee'),
        'perms' => array('employee', 'department', 'designation', 'employee_disable_authentication'),
        'school' => array(
            'summary' => 'Add teaching and non-teaching staff. Their role decides what they can see and do.',
            'steps' => array(
                array('Departments and designations', 'Create departments (Sciences, Admin…) and designations (Principal, Bursar…) under <b>Employee</b>.', 'employee/department'),
                array('Add an employee', 'Open <b>Employee › Add Employee</b>, choose their role (Teacher, Accountant, Librarian…), fill in details, and set their username and password.', 'employee/add'),
                array('Give the right access', 'Fine-tune what each role can do under <b>Settings › Role Permission</b>.', 'role'),
                array('Teachers need classes', 'Assign each teacher subjects under <b>Academic › Subject › Class Assign</b> and, where needed, a class to lead.', 'subject/class_assign'),
                array('Block a login', 'Turn a staff login off when they leave under <b>Login Deactivate</b>. Their records stay.', 'employee/disable_authentication'),
            ),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'attendance' => array(
        'title' => 'Attendance',
        'icon' => 'fa-clipboard-check',
        'group' => 'Academics',
        'match' => array('attendance', 'attendance_period', 'userrole/attendance', 'userrole/subject_wise_attendance', 'schooledge_qr_attendance'),
        'perms' => array('student_attendance', 'employee_attendance', 'exam_attendance', 'student_attendance_report'),
        'school' => array(
            'summary' => 'Take student, staff and exam attendance, and send parents an alert when a child is absent.',
            'steps' => array(
                array('Choose how you take attendance', 'Pick day-wise or subject-wise under <b>School Settings › Attendance Type</b>.', 'school_settings'),
                array('Take the student register', 'Open <b>Attendance › Student</b>, pick class, section and date, then mark each student present, late, absent or on holiday and save.', 'attendance/student_entry'),
                array('Subject-wise registers', 'With subject-wise attendance, teachers mark each period under <b>Attendance › Subject Wise</b>.', 'attendance_period/index'),
                array('Staff and exams', 'Record staff attendance under <b>Attendance › Employee</b> and exam-day attendance under <b>Exam</b>.', 'attendance/employees_entry'),
                array('Absence alerts', 'Switch on the absence SMS or email under School Settings so parents hear the same day.', 'school_settings'),
                array('Reports', 'See registers by student, class or month under <b>Reports › Attendance</b>.', 'attendance/studentwise_report'),
            ),
            'tips' => array('Attendance for today feeds the "Present today" figure on the admin dashboard.'),
        ),
        'teacher' => array(
            'summary' => 'Take your class register in under a minute.',
            'steps' => array(
                array('Open the register', 'Go to <b>Attendance › Student</b> and choose your class, section and today\'s date.', 'attendance/student_entry'),
                array('Mark everyone', 'Everyone starts as present. Change only those who are late or absent, add a remark if needed, then <b>Save</b>.', 'attendance/student_entry'),
                array('Made a mistake?', 'Open the same date again, correct it and save. The newest entry wins.', ''),
            ),
        ),
        'student' => array(
            'summary' => 'See every day you were present, late or absent.',
            'steps' => array(
                array('Your rate', 'The attendance ring on your dashboard shows your rate this session.', 'dashboard'),
                array('Day by day', 'Open <b>Supervision › Attendance</b> for the full calendar.', 'userrole/attendance'),
            ),
        ),
        'parent' => array(
            'summary' => 'Know when your child was absent or late.',
            'steps' => array(
                array('At a glance', 'The dashboard shows the child\'s attendance rate and a month-by-month chart.', 'dashboard'),
                array('Full record', 'Open <b>Supervision › Attendance</b> for every day.', 'userrole/attendance'),
                array('Report an absence', 'Send a leave application before the day under <b>Academic › Leave Application</b>.', 'userrole/leave_request'),
            ),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'homework' => array(
        'title' => 'Homework',
        'icon' => 'fa-book-open',
        'group' => 'Academics',
        'match' => array('homework', 'userrole/homework'),
        'perms' => array('homework', 'evaluation_report'),
        'school' => array(
            'summary' => 'Teachers post homework with an optional file. Students submit online, and teachers grade and comment.',
            'steps' => array(
                array('Post homework', 'Open <b>Homework</b> › Add. Choose class, section and subject, set the homework and submission dates, write the task and attach a file if you like.', 'homework'),
                array('Schedule it (optional)', 'Set a publish date to release it later, and tick SMS to tell parents.', 'homework'),
                array('Evaluate', 'After the due date, open the homework, see who submitted, download answers, and give each student a grade and remark.', 'homework'),
                array('Report', 'See submission and evaluation rates under <b>Homework › Evaluation Report</b>.', 'homework/report'),
            ),
        ),
        'teacher' => array(
            'summary' => 'Set homework, collect answers and grade them in one place.',
            'steps' => array(
                array('Post homework', 'Open <b>Homework</b>, click Add, pick class, section and subject, set dates, write the task and save.', 'homework'),
                array('Mark submissions', 'After the due date, open the homework and click <b>Evaluate</b>. Grade each student and add a remark.', 'homework'),
                array('Keep up', 'Your dashboard counts homework still waiting to be marked.', 'dashboard'),
            ),
        ),
        'student' => array(
            'summary' => 'See what\'s due and hand in your work online.',
            'steps' => array(
                array('See what\'s due', 'Your dashboard lists open homework. Open <b>Academic › Homework</b> for all of it.', 'userrole/homework'),
                array('Submit', 'Click a homework, write your answer or attach a file, then press <b>Submit</b>. You can re-submit until it\'s marked.', 'userrole/homework'),
                array('Read feedback', 'When your teacher marks it, the grade and remark appear on the same page.', 'userrole/homework'),
            ),
        ),
        'parent' => array(
            'summary' => 'See your child\'s homework, due dates and the teacher\'s feedback.',
            'steps' => array(array('Homework', 'Open <b>Academic › Homework</b> with your child selected.', 'userrole/homework')),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'exam_setup' => array(
        'title' => 'Exam setup',
        'icon' => 'fa-clipboard-list',
        'group' => 'Exams & results',
        'match' => array('exam/term', 'exam/hall', 'exam/mark_distribution', 'exam/grade', 'exam', 'timetable/viewexam', 'timetable/set_examwise', 'userrole/exam_schedule'),
        'perms' => array('exam', 'exam_term', 'exam_hall', 'mark_distribution', 'exam_grade', 'exam_timetable'),
        'school' => array(
            'summary' => 'Set exams up once per session. After that, teachers enter marks and the system builds report cards for you.',
            'steps' => array(
                array('Exam terms', 'Add the terms (First Term, Second Term…) and the next-term resumption date under <b>Exam Master › Exam › Exam Term</b>.', 'exam/term'),
                array('Grades', 'Set your grading scale (A1 75–100, B2 70–74…) with remarks under <b>Marks › Grades Range</b>. WAEC-style grades work well.', 'exam/grade'),
                array('Mark distribution', 'Create the parts each subject is scored in, for example CA 1 (20), CA 2 (20) and Exam (60), under <b>Distribution</b>.', 'exam/mark_distribution'),
                array('Exam halls (optional)', 'Add halls and seat numbers if you print admit cards.', 'exam/hall'),
                array('Create the exam', 'Under <b>Exam Setup</b>, create the exam, link it to a term and choose which mark distributions it uses.', 'exam'),
                array('Exam timetable', 'Add each paper\'s date, time, hall and full marks per class under <b>Exam › Add</b> (schedule).', 'timetable/set_examwise'),
            ),
            'tips' => array('Teachers can only enter marks once the exam and its schedule exist for their class.'),
        ),
        'student' => array(
            'summary' => 'Know when each paper is.',
            'steps' => array(
                array('Next papers', 'Your dashboard lists the next exam papers.', 'dashboard'),
                array('Full timetable', 'Open <b>Exam Master › Exam</b> for the schedule and print your admit card if your school uses them.', 'userrole/exam_schedule'),
            ),
        ),
        'parent' => array(
            'summary' => 'See your child\'s exam timetable.',
            'steps' => array(array('Exam schedule', 'Open <b>Exam Master › Exam</b> with your child selected.', 'userrole/exam_schedule')),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'results' => array(
        'title' => 'Marks & report cards',
        'icon' => 'fa-file-alt',
        'group' => 'Exams & results',
        'match' => array('exam/mark_entry', 'exam/class_position', 'exam/psychomotor_entry', 'exam/marksheet', 'exam/tabulation_sheet', 'exam_progress', 'marksheet_template', 'userrole/report_card'),
        'perms' => array('exam_mark', 'generate_position', 'psychomotor_rating', 'report_card', 'tabulation_sheet', 'marksheet_template', 'progress_reports'),
        'school' => array(
            'summary' => 'Marks go in, positions are worked out, results are published, and report cards print in the style you choose.',
            'steps' => array(
                array('Choose a report card design', 'Under <b>Exam › Marksheet</b>, create a template, pick one of the design styles (Classic, Modern, Premium…) and choose what it shows: position, class average, psychomotor and remarks.', 'marksheet_template/index'),
                array('Enter marks', 'Teachers open <b>Marks › Mark Entries</b>, choose the exam, class and subject, type each part\'s score and save.', 'exam/mark_entry'),
                array('Psychomotor and affective ratings', 'Rate punctuality, neatness, sports and so on under <b>Psychomotor Rating</b> if your report card shows them.', 'exam/psychomotor_entry'),
                array('Generate positions', 'When all marks are in, run <b>Generate Position</b> for each class to work out positions and teacher remarks.', 'exam/class_position'),
                array('Publish', 'Tick <b>Publish Result</b> on the exam under Exam Setup. Students and parents can only see results after this.', 'exam'),
                array('Print report cards', 'Print or download PDFs for a whole class under <b>Reports › Examination › Report Card</b>. The tabulation sheet gives the class broadsheet.', 'exam/marksheet'),
            ),
            'tips' => array(
                'Re-run Generate Position after correcting any mark.',
                'Keep results unpublished until the principal has checked the broadsheet.',
            ),
            'faq' => array(
                array('A subject is missing from mark entry.', 'Check the subject is assigned to that class and teacher, and that the exam schedule includes it.'),
                array('Parents can\'t see results.', 'Make sure Publish Result is ticked on that exam.'),
            ),
        ),
        'teacher' => array(
            'summary' => 'Enter scores for your subjects. The rest is calculated for you.',
            'steps' => array(
                array('Open mark entry', 'Go to <b>Exam Master › Marks › Mark Entries</b> and choose the exam, class, section and subject.', 'exam/mark_entry'),
                array('Type the scores', 'Enter each part (CA, exam…). Leave a box empty if the student was absent, then <b>Save</b>.', 'exam/mark_entry'),
                array('Class teachers: ratings', 'If you lead a class, rate each student\'s behaviour and skills under <b>Psychomotor Rating</b>.', 'exam/psychomotor_entry'),
            ),
            'tips' => array('You can save part-way and come back. Nothing is shown to parents until the school publishes.'),
        ),
        'student' => array(
            'summary' => 'See your results once your school publishes them.',
            'steps' => array(array('Report card', 'Open <b>Exam Master › Report Card</b>, choose the exam and view or download your report card.', 'userrole/report_card')),
            'faq' => array(array('My report card is empty.', 'Results appear only after the school publishes them. Check back later.')),
        ),
        'parent' => array(
            'summary' => 'Download your child\'s report card for every exam.',
            'steps' => array(array('Report card', 'Choose your child, open <b>Exam Master › Report Card</b>, pick the exam and download.', 'userrole/report_card')),
            'faq' => array(array('The report card won\'t open.', 'Results appear only after the school publishes them. Some schools also require fees to be paid first.')),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'online_exam' => array(
        'title' => 'Online exams (CBT)',
        'icon' => 'fa-laptop',
        'group' => 'Exams & results',
        'match' => array('onlineexam', 'userrole/online_exam', 'userrole/onlineexam'),
        'perms' => array('online_exam', 'question_bank', 'question_group', 'exam_result'),
        'school' => array(
            'summary' => 'Run computer-based tests: build a question bank, set a timed exam and let the system mark it.',
            'steps' => array(
                array('Question groups', 'Organise questions by topic under <b>Online Exam › Question Group</b>.', 'onlineexam/question_group'),
                array('Question bank', 'Add multiple-choice, true/false or written questions with their marks under <b>Question Bank</b>.', 'onlineexam/question'),
                array('Create the exam', 'Under <b>Online Exam</b>, set the class, subject, start and end time, duration, pass mark and whether to shuffle questions. Then add questions.', 'onlineexam'),
                array('Publish', 'Publish the exam so students see it when it opens. Publish the result when you\'re ready.', 'onlineexam'),
                array('Results and positions', 'Review scores under <b>Exam Result</b> and rank students under <b>Position</b>.', 'onlineexam/result'),
            ),
        ),
        'student' => array(
            'summary' => 'Take timed tests in your browser.',
            'steps' => array(
                array('Find the exam', 'Open <b>Exam Master › Online Exam</b>. Exams appear when their start time arrives.', 'userrole/online_exam'),
                array('Take it', 'Click <b>Take Exam</b>. The timer starts straight away, so answer every question and submit before time runs out.', 'userrole/online_exam'),
                array('Results', 'Your score shows on the same page once the school publishes it.', 'userrole/online_exam'),
            ),
            'tips' => array('Use a stable internet connection and don\'t refresh the page during the exam.'),
        ),
        'parent' => array(
            'summary' => 'See your child\'s CBT scores.',
            'steps' => array(array('Online exam results', 'Open <b>Exam Master › Online Exam</b> with your child selected.', 'userrole/online_exam')),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'live_class' => array(
        'title' => 'Live classes',
        'icon' => 'fa-video',
        'group' => 'Academics',
        'match' => array('live_class', 'userrole/live_class', 'userrole/livejoin'),
        'perms' => array('live_class'),
        'school' => array(
            'summary' => 'Teach online with Zoom, Google Meet or the built-in video room.',
            'steps' => array(
                array('Connect a provider', 'Add your Zoom or other keys under <b>School Settings › Live Class</b>.', 'school_settings'),
                array('Schedule a class', 'Open <b>Live Class Rooms</b>, add a class with the date, time, duration and the classes and sections invited.', 'live_class'),
                array('Start it', 'At class time, click <b>Start</b>. Students see a Join button.', 'live_class'),
                array('Attendance', 'See who joined under <b>Live Class › Reports</b>.', 'live_class/reports'),
            ),
        ),
        'student' => array(
            'summary' => 'Join online lessons from your browser or phone.',
            'steps' => array(array('Join', 'Open <b>Academic › Live Class Rooms</b> and click <b>Join</b> when the class has started.', 'userrole/live_class')),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'attachments' => array(
        'title' => 'Study materials',
        'icon' => 'fa-paperclip',
        'group' => 'Academics',
        'match' => array('attachments', 'userrole/attachments'),
        'perms' => array('attachments', 'attachment_type'),
        'school' => array(
            'summary' => 'Share notes, past questions, videos and syllabuses with a class.',
            'steps' => array(
                array('Material types', 'Create types (Notes, Past Questions, Syllabus…) under <b>Attachments Book › Attachment Type</b>.', 'attachments/type'),
                array('Upload', 'Under <b>Upload Content</b>, add a title, type, class and subject, and attach the file or a video link.', 'attachments'),
            ),
        ),
        'student' => array(
            'summary' => 'Download notes and materials your teachers share.',
            'steps' => array(array('Materials', 'Open <b>Academic › Attachments Book</b> and download or watch.', 'userrole/attachments')),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'fees' => array(
        'title' => 'Fees',
        'icon' => 'fa-wallet',
        'group' => 'Finance',
        'match' => array('fees', 'offline_payments', 'wallet', 'userrole/invoice', 'userrole/wallet', 'userrole/collect'),
        'perms' => array('fees_type', 'fees_group', 'fees_allocation', 'invoice', 'collect_fees', 'due_invoice', 'fees_reminder', 'fees_reports'),
        'school' => array(
            'summary' => 'Define what you charge, assign it to students, collect payments in cash, by transfer or online, and chase what\'s owed.',
            'steps' => array(
                array('Fee types', 'List every charge (Tuition, Uniform, Bus, PTA…) under <b>Student Accounting › Fees Type</b>.', 'fees/type'),
                array('Fee groups', 'Bundle types with amounts and due dates into a group, for example "JSS 1 First Term", under <b>Fees Group</b>.', 'fees/group'),
                array('Late fines (optional)', 'Set a fine for payments made after the due date under <b>Fine Setup</b>.', 'fees/fine_setup'),
                array('Allocate to students', 'Under <b>Fees Allocation</b>, choose the class and tick the students who pay that group.', 'fees/allocation'),
                array('Collect payments', 'Open a student under <b>Payments History</b>, choose what they\'re paying, the amount and the method, and save. A receipt is generated.', 'fees/invoice_list'),
                array('Online and bank payments', 'Parents pay online once a gateway is set in School Settings. Bank transfers they report wait under <b>Fee Collection Approvals</b> for you to confirm.', 'fees/collection_approvals'),
                array('Chase what\'s owed', 'List debtors under <b>Due Fees Invoice</b> and send automatic reminders under <b>Fees Reminder</b>.', 'fees/due_invoice'),
                array('Reports', 'Fee, receipt and due reports are under <b>Reports › Fees Reports</b>.', 'fees/student_fees_report'),
            ),
            'tips' => array(
                'Fee payments also post to Office Accounting when accounting links are set in School Settings.',
                'Create one group per class per term so allocation is a single step.',
            ),
            'faq' => array(
                array('A student shows no fees.', 'The fee group hasn\'t been allocated to them. Use Fees Allocation.'),
                array('A parent paid online but the balance hasn\'t changed.', 'Check the gateway transaction status; if it succeeded, the payment shows in Payments History within a few minutes.'),
            ),
        ),
        'student' => array(
            'summary' => 'See what you owe and every payment made.',
            'steps' => array(
                array('Balance', 'Your dashboard shows your fee balance and how much is paid.', 'dashboard'),
                array('Invoices and receipts', 'Open <b>Fees History</b> for each fee, payments made and receipts to print.', 'userrole/invoice'),
            ),
        ),
        'parent' => array(
            'summary' => 'Pay school fees online and keep every receipt.',
            'steps' => array(
                array('See the balance', 'Pick your child. The dashboard shows the balance and how much is paid.', 'dashboard'),
                array('Pay online', 'Open <b>Fees History</b>, choose the fees to pay, click <b>Pay Now</b> and complete the card or bank payment.', 'userrole/invoice'),
                array('Paid by bank transfer?', 'Use the <b>Offline Payments</b> tab to upload the teller or transfer receipt. The school confirms it.', 'userrole/invoice'),
                array('Wallet', 'Top up the <b>Wallet</b> and pay fees from it in one click.', 'userrole/wallet'),
                array('Receipts', 'Print a receipt for any payment from the Payment History tab.', 'userrole/invoice'),
            ),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'accounting' => array(
        'title' => 'Office accounting',
        'icon' => 'fa-calculator',
        'group' => 'Finance',
        'match' => array('accounting'),
        'perms' => array('account', 'deposit', 'expense', 'all_transactions', 'voucher_head', 'accounting_reports'),
        'school' => array(
            'summary' => 'Record money in and out of the school\'s accounts and see where it goes.',
            'steps' => array(
                array('Accounts', 'Add each bank account and cash box with its opening balance under <b>Office Accounting › Account</b>.', 'accounting'),
                array('Voucher heads', 'Create income and expense categories (Salaries, Diesel, Donations…) under <b>Voucher Head</b>.', 'accounting/voucher_head'),
                array('Record money in', 'Add income under <b>New Deposit</b>.', 'accounting/voucher_deposit'),
                array('Record money out', 'Add spending under <b>New Expense</b>. Expenses above your limit wait for approval under <b>Expense Approvals</b>.', 'accounting/voucher_expense'),
                array('Link fees', 'Under <b>School Settings › Accounting Links</b>, choose the account fee payments go into.', 'school_settings'),
                array('Reports', 'Account statements, income, expense, balance sheet and income vs expense are under <b>Reports › Financial Reports</b>.', 'accounting/account_statement'),
            ),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'payroll' => array(
        'title' => 'Payroll & salary',
        'icon' => 'fa-money-check-alt',
        'group' => 'Staff & HR',
        'match' => array('payroll', 'advance_salary', 'award'),
        'perms' => array('salary_template', 'salary_assign', 'salary_payment', 'advance_salary_manage', 'advance_salary_request', 'award'),
        'school' => array(
            'summary' => 'Pay staff monthly from salary templates, handle advances and record awards.',
            'steps' => array(
                array('Salary templates', 'Create a template per grade with basic pay, allowances and deductions under <b>Payroll › Salary Template</b>.', 'payroll/salary_template'),
                array('Assign templates', 'Give each employee their template under <b>Salary Assign</b>.', 'payroll/salary_assign'),
                array('Pay salaries', 'Each month, open <b>Salary Payment</b>, choose the month, and pay each employee. A payslip is generated.', 'payroll'),
                array('Advances', 'Staff request advances under <b>Advance Salary › My Application</b>; approve them under <b>Manage Application</b>. They come off the next salary.', 'advance_salary'),
            ),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'leave' => array(
        'title' => 'Leave',
        'icon' => 'fa-plane-departure',
        'group' => 'Staff & HR',
        'match' => array('leave', 'userrole/leave_request'),
        'perms' => array('leave_category', 'leave_request', 'leave_manage'),
        'school' => array(
            'summary' => 'Staff and students apply for leave; approvers accept or reject it.',
            'steps' => array(
                array('Leave categories', 'Create types (Sick, Casual, Maternity…) with yearly allowances per role under <b>Leave › Category</b>.', 'leave/category'),
                array('Apply', 'Staff apply under <b>Leave › My Application</b>.', 'leave/request'),
                array('Approve', 'Review and approve or reject requests from staff and students under <b>Manage Application</b>.', 'leave'),
            ),
        ),
        'teacher' => array(
            'summary' => 'Ask for time off and track the answer.',
            'steps' => array(array('Apply', 'Open <b>Leave › My Application</b>, choose the type and dates, give a reason and submit.', 'leave/request')),
        ),
        'student' => array(
            'summary' => 'Tell the school in advance when you\'ll be away.',
            'steps' => array(array('Apply', 'Open <b>Academic › Leave Application</b>, choose the dates, give a reason and attach a note if you have one.', 'userrole/leave_request')),
        ),
        'parent' => array(
            'summary' => 'Excuse your child\'s absence before it happens.',
            'steps' => array(array('Apply', 'Choose your child, open <b>Academic › Leave Application</b>, enter the dates and reason, and submit.', 'userrole/leave_request')),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'cards' => array(
        'title' => 'ID cards, admit cards & certificates',
        'icon' => 'fa-id-card',
        'group' => 'Students & parents',
        'match' => array('card_manage', 'certificate'),
        'perms' => array('id_card_templete', 'generate_student_idcard', 'generate_employee_idcard', 'admit_card_templete', 'generate_admit_card', 'certificate_templete', 'generate_student_certificate'),
        'school' => array(
            'summary' => 'Design cards and certificates once, then print them for a whole class.',
            'steps' => array(
                array('Design an ID card', 'Under <b>Card Management › Id Card</b>, add a template. Pick a starter design, choose a style and insert fields like {name} and {class}.', 'card_manage/id_card_templete'),
                array('Print student cards', 'Under <b>Card Management › Student</b>, choose the template and class, tick students and print.', 'card_manage/generate_student_idcard'),
                array('Admit cards', 'Design an admit card template, then generate it per exam and class under <b>Admit Card › Generate</b>.', 'card_manage/generate_student_admitcard'),
                array('Certificates', 'Design a certificate under <b>Certificate</b> and print it for selected students or staff.', 'certificate'),
            ),
            'tips' => array('Upload clear student photos at admission; cards print them automatically.'),
        ),
        'student' => array(
            'summary' => 'Your school ID card, always on hand.',
            'steps' => array(array('ID card', 'Open <b>Id Card</b> to view or print it.', 'userrole/idCard')),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'library' => array(
        'title' => 'Library',
        'icon' => 'fa-book-reader',
        'group' => 'Facilities',
        'match' => array('library', 'userrole/book', 'userrole/book_request'),
        'perms' => array('book', 'book_category', 'book_manage', 'book_request'),
        'school' => array(
            'summary' => 'Catalogue books, lend them out and get them back on time.',
            'steps' => array(
                array('Categories', 'Add categories (Fiction, Science…) under <b>Library › Books Category</b>.', 'library/category'),
                array('Add books', 'Add each title with author, ISBN, copies and price under <b>Library › Books</b>.', 'library/book'),
                array('Issue and return', 'Issue a book to a student or staff member with a return date, and mark it returned (with any fine) under <b>Book Manage</b>.', 'library/book_manage'),
                array('Requests', 'Students can request books online; approve them in Book Manage.', 'library/book_manage'),
            ),
        ),
        'student' => array(
            'summary' => 'Browse the library and request a book.',
            'steps' => array(
                array('Browse', 'Open <b>Library › Books</b> to see what\'s available.', 'userrole/book'),
                array('Request', 'Request a book under <b>Book Request</b>; collect it once the librarian approves.', 'userrole/book_request'),
            ),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'transport' => array(
        'title' => 'Transport',
        'icon' => 'fa-bus',
        'group' => 'Facilities',
        'module' => 'transport',
        'match' => array('transport', 'userrole/route'),
        'perms' => array('transport_route', 'transport_vehicle', 'transport_stoppage', 'transport_assign', 'transport_allocation'),
        'school' => array(
            'summary' => 'Plan bus routes and know which child rides which bus.',
            'steps' => array(
                array('Routes', 'Add each route with start and end points under <b>Transport › Route Master</b>.', 'transport/route'),
                array('Vehicles', 'Add buses with driver, capacity and registration under <b>Vehicle Master</b>.', 'transport/vehicle'),
                array('Stoppages', 'Add pick-up points with their time and fare under <b>Stoppage</b>.', 'transport/stoppage'),
                array('Assign', 'Link vehicles to routes under <b>Assign Vehicle</b>, then add students to a route and stop from their profile.', 'transport/vehicle_assign'),
                array('Report', 'See who rides where under <b>Allocation Report</b>.', 'transport/report'),
            ),
        ),
        'student' => array(
            'summary' => 'Your bus, driver and stop.',
            'steps' => array(array('Transport', 'Open <b>Supervision › Transport</b>.', 'userrole/route')),
        ),
        'parent' => array(
            'summary' => 'Your child\'s bus, driver and stop.',
            'steps' => array(array('Transport', 'Open <b>Supervision › Transport</b> with your child selected.', 'userrole/route')),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'hostel' => array(
        'title' => 'Hostel',
        'icon' => 'fa-bed',
        'group' => 'Facilities',
        'module' => 'hostel',
        'match' => array('hostels', 'userrole/hostels'),
        'perms' => array('hostel', 'hostel_room', 'hostel_category', 'hostel_allocation'),
        'school' => array(
            'summary' => 'Manage boarding houses, rooms and who sleeps where.',
            'steps' => array(
                array('Categories', 'Add room categories (Standard, Prefect…) under <b>Hostel › Category</b>.', 'hostels/category'),
                array('Hostels', 'Add each house with its warden under <b>Hostel Master</b>.', 'hostels'),
                array('Rooms', 'Add rooms with beds and cost under <b>Hostel Room</b>.', 'hostels/room'),
                array('Allocate', 'Assign a student a hostel and room from their profile; see everyone under <b>Allocation Report</b>.', 'hostels/allocation_report'),
            ),
        ),
        'student' => array(
            'summary' => 'Your house and room.',
            'steps' => array(array('Hostel', 'Open <b>Supervision › Hostel</b>.', 'userrole/hostels')),
        ),
        'parent' => array(
            'summary' => 'Where your child boards.',
            'steps' => array(array('Hostel', 'Open <b>Supervision › Hostel</b> with your child selected.', 'userrole/hostels')),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'events' => array(
        'title' => 'Events & calendar',
        'icon' => 'fa-calendar-alt',
        'group' => 'Communication',
        'match' => array('event', 'userrole/event', 'birthday'),
        'perms' => array('event', 'event_type'),
        'school' => array(
            'summary' => 'Put sports days, PTA meetings and holidays on everyone\'s calendar and your website.',
            'steps' => array(
                array('Event types', 'Create types (Holiday, Meeting, Sports…) with an icon under <b>Events › Event Type</b>.', 'event/types'),
                array('Add an event', 'Under <b>Events</b>, set the title, dates, who it\'s for (everyone, a class, or a role) and a picture.', 'event'),
                array('Show on the website', 'Tick <b>Show on website</b> to list it on your school website too.', 'event'),
            ),
            'tips' => array('Events appear on every dashboard\'s calendar and in "Coming up".'),
        ),
        'student' => array(
            'summary' => 'Never miss a school event.',
            'steps' => array(array('Events', 'See "Coming up" on your dashboard, or open <b>Events</b> for the full list.', 'userrole/event')),
        ),
        'parent' => array(
            'summary' => 'Know about every school event ahead of time.',
            'steps' => array(array('Events', 'See "Coming up" on the dashboard or open <b>Events</b>.', 'userrole/event')),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'communication' => array(
        'title' => 'SMS, email & messages',
        'icon' => 'fa-comments',
        'group' => 'Communication',
        'match' => array('sendsmsmail', 'communication'),
        'perms' => array('sendsmsmail', 'sendsmsmail_template'),
        'school' => array(
            'summary' => 'Reach parents, students and staff by SMS, email or in-app message.',
            'steps' => array(
                array('Connect providers', 'Add your SMS gateway and email account under <b>School Settings</b> (SMS Settings and Email Settings tabs).', 'school_settings'),
                array('Templates', 'Save messages you send often under <b>Bulk Sms And Email › Sms / Email</b> templates.', 'sendsmsmail/template/sms'),
                array('Send', 'Under <b>Send</b>, choose SMS or email, pick recipients by role, class or individual, and send now or schedule it.', 'sendsmsmail/sms'),
                array('Check delivery', 'See what was sent and to whom under <b>Report</b>.', 'sendsmsmail/campaign_reports'),
                array('In-app messages', 'Use <b>Message</b> for private conversations with staff, parents or students.', 'communication/mailbox/inbox'),
            ),
        ),
        'teacher' => array(
            'summary' => 'Message students, parents and colleagues inside the app.',
            'steps' => array(array('Messages', 'Open <b>Message</b>, click Compose, choose the role and person, and send.', 'communication/mailbox/inbox')),
        ),
        'student' => array(
            'summary' => 'Talk to your teachers inside the app.',
            'steps' => array(array('Messages', 'Open <b>Message</b>, click Compose and choose a teacher.', 'communication/mailbox/inbox')),
        ),
        'parent' => array(
            'summary' => 'Write to your child\'s teachers or the office.',
            'steps' => array(array('Messages', 'Open <b>Message</b>, click Compose, choose the teacher or staff member and send.', 'communication/mailbox/inbox')),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'reception' => array(
        'title' => 'Front office',
        'icon' => 'fa-concierge-bell',
        'group' => 'Communication',
        'match' => array('reception', 'reception_config'),
        'perms' => array('enquiry', 'postal_record', 'call_log', 'visitor_log', 'complaint'),
        'school' => array(
            'summary' => 'Log enquiries, visitors, calls, post and complaints at the front desk.',
            'steps' => array(
                array('Set up lists', 'Add references, sources and purposes under <b>Reception › Config</b>.', 'reception_config/reference'),
                array('Admission enquiries', 'Record prospective parents and follow-up dates under <b>Admission Enquiry</b>.', 'reception/enquiry'),
                array('Daily logs', 'Log visitors, phone calls and post under their pages.', 'reception/visitor_log'),
                array('Complaints', 'Record complaints, assign them and track resolution under <b>Complaint</b>.', 'reception/complaint'),
            ),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'inventory' => array(
        'title' => 'Inventory',
        'icon' => 'fa-boxes',
        'group' => 'Facilities',
        'match' => array('inventory'),
        'perms' => array('product', 'product_category', 'product_store', 'product_purchase', 'product_sales', 'product_issue'),
        'school' => array(
            'summary' => 'Track uniforms, books, stationery and other stock: what you buy, sell and issue.',
            'steps' => array(
                array('Basics', 'Add categories, units, stores and suppliers under <b>Inventory</b>.', 'inventory/category'),
                array('Products', 'Add each product with its buying and selling price under <b>Product</b>.', 'inventory/product'),
                array('Purchases', 'Record stock coming in under <b>Purchase</b>.', 'inventory/purchase'),
                array('Sales and issues', 'Record items sold (uniforms to parents) or issued (chalk to staff) under <b>Sales</b> and <b>Issue</b>.', 'inventory/sales'),
                array('Reports', 'See stock levels and movements under <b>Reports › Inventory</b>.', 'inventory/stockreport'),
            ),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'website' => array(
        'title' => 'School website & sign-in page',
        'icon' => 'fa-globe',
        'group' => 'Website & branding',
        'match' => array('frontend'),
        'perms' => array('frontend_setting', 'frontend_menu', 'frontend_section', 'manage_page', 'frontend_slider', 'frontend_news', 'frontend_gallery'),
        'school' => array(
            'summary' => 'Every school gets its own public website and sign-in page. Choose a 3D template, add your content and share the link.',
            'steps' => array(
                array('Turn the website on', 'Under <b>Frontend › Setting</b>, set the website title and its address (URL alias), switch the website on and fill in contact details.', 'frontend/setting'),
                array('Pick a template', 'On the same page, choose a website template (Classic, Aurora 3D, Campus Light, Prism Kids or Heritage) and your colours.', 'frontend/setting'),
                array('Home page', 'Add slides under <b>Slider</b>, then features, services, testimonials and FAQs.', 'frontend/slider'),
                array('Pages and menu', 'Edit About, Admission, Contact and other pages under <b>Page</b> and <b>Manage</b>, and arrange the menu under <b>Menu</b>.', 'frontend/section/index'),
                array('News and gallery', 'Post news and photo albums so the site stays fresh.', 'frontend/news/index'),
                array('Sign-in page', 'Under <b>Frontend › Sign-in Page</b>, choose a 3D layout, your own background photo, accent colour and welcome text. The live preview shows it as you go.', 'frontend/login_page'),
            ),
            'tips' => array(
                'Share your sign-in address (shown on the Sign-in Page screen) with parents and staff.',
                'Events ticked "Show on website" appear on the site automatically.',
            ),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'reports' => array(
        'title' => 'Reports',
        'icon' => 'fa-chart-pie',
        'group' => 'Finance',
        'match' => array('student/login_credential_reports', 'student/admission_reports', 'student/classsection_reports', 'student/sibling_report', 'leave/reports'),
        'perms' => array('fees_reports', 'accounting_reports', 'student_attendance_report', 'report_card', 'salary_summary_report'),
        'school' => array(
            'summary' => 'Every report in one menu: students, fees, finance, attendance, HR and exams.',
            'steps' => array(
                array('Student reports', 'Login credentials, admissions, class lists and siblings under <b>Reports › Student</b>.', 'student/admission_reports'),
                array('Fees', 'Fees, receipts, dues and fines under <b>Reports › Fees Reports</b>.', 'fees/student_fees_report'),
                array('Finance', 'Statements, income, expense and balance sheet under <b>Financial Reports</b>.', 'accounting/account_statement'),
                array('Attendance', 'By student, class, subject and month under <b>Attendance Reports</b>.', 'attendance/studentwise_report'),
                array('Exams', 'Report cards, tabulation sheets and progress reports under <b>Examination</b>.', 'exam/marksheet'),
            ),
            'tips' => array('Most reports export to Excel, PDF or print from the buttons above the table.'),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'alumni' => array(
        'title' => 'Alumni',
        'icon' => 'fa-user-graduate',
        'group' => 'Students & parents',
        'match' => array('alumni'),
        'perms' => array('manage_alumni', 'alumni_events'),
        'school' => array(
            'summary' => 'Stay in touch with past students and invite them to reunions.',
            'steps' => array(
                array('Add alumni', 'Move graduating students to <b>Alumni › Manage Alumni</b>, with their current job and contact.', 'alumni/index'),
                array('Alumni events', 'Plan reunions and send invitations under <b>Alumni › Events</b>.', 'alumni/event'),
            ),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'mobile_app' => array(
        'title' => 'Mobile app',
        'icon' => 'fa-mobile-alt',
        'group' => 'Getting started',
        'match' => array('mobile_app'),
        'perms' => array(),
        'school' => array(
            'summary' => 'Staff, students and parents can use the SkoolOS app with the same username and password.',
            'steps' => array(
                array('Get the app', 'Open <b>Mobile App</b> for download links and QR codes to share with your school.', 'mobile_app'),
                array('Sign in', 'Users sign in with their usual username and password. Parents get push notifications for attendance, results and fees.', ''),
            ),
        ),
        'student' => array(
            'summary' => 'Use SkoolOS on your phone.',
            'steps' => array(array('Install', 'Download the SkoolOS app from your app store and sign in with the same username and password.', '')),
        ),
        'parent' => array(
            'summary' => 'Get alerts about your child on your phone.',
            'steps' => array(array('Install', 'Download the SkoolOS app, sign in with your parent username and password, and allow notifications.', '')),
        ),
    ),

    /* ------------------------------------------------------------------ */
    'platform' => array(
        'title' => 'Schools & subscriptions',
        'icon' => 'fa-building',
        'group' => 'Getting started',
        'match' => array('branch', 'saas', 'custom_domain', 'subscription'),
        'perms' => array(),
        'super' => array(
            'summary' => 'Create schools, give each one a plan and help them get going.',
            'steps' => array(
                array('Add a school', 'Create the school under <b>Branch</b>, then add its first admin under Employee with the Admin role.', 'branch'),
                array('Plans and subscriptions', 'Set up plans and see each school\'s subscription and renewals under <b>Subscription</b>.', 'saas/school'),
                array('Custom domain', 'Point a school\'s own domain at its website under <b>Custom Domain</b>.', 'custom_domain/list'),
                array('Support a school', 'Open the school from the dashboard switcher and follow its "Getting started" checklist with them.', 'dashboard'),
            ),
        ),
    ),
);
