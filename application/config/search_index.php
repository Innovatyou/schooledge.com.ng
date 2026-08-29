<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Static candidate index of navigable sidebar links, extracted from
 * application/views/layout/sidebar.php, for the admin "search everything"
 * feature. This is only a CANDIDATE list for the search box — the
 * controller that serves search results MUST re-check get_permission()/
 * is_superadmin_loggedin() live (using the 'permission' field below)
 * before showing any result to the current user.
 */

$config['search_index'] = array(

    // ---------------------------------------------------------------
    // Dashboard / general
    // ---------------------------------------------------------------
    array(
        'label' => 'dashboard',
        'url'   => 'dashboard',
        'section' => 'dashboard',
        'permission' => null,
    ),
    array(
        'label' => 'branch',
        'url'   => 'branch',
        'section' => 'branch',
        'permission' => 'superadmin_only',
    ),
    array(
        'label' => 'message',
        'url'   => 'communication/mailbox/inbox',
        'section' => 'general',
        'permission' => null,
    ),
    array(
        'label' => 'addon_manager',
        'url'   => 'addons/manage',
        'section' => 'addon',
        'permission' => 'superadmin_only',
    ),

    // ---------------------------------------------------------------
    // Inventory
    // ---------------------------------------------------------------
    array(
        'label' => 'product',
        'url'   => 'inventory/product',
        'section' => 'inventory',
        'permission' => array('product', 'is_view'),
    ),
    array(
        'label' => 'category',
        'url'   => 'inventory/category',
        'section' => 'inventory',
        'permission' => array('product_category', 'is_view'),
    ),
    array(
        'label' => 'store',
        'url'   => 'inventory/store',
        'section' => 'inventory',
        'permission' => array('product_store', 'is_view'),
    ),
    array(
        'label' => 'supplier',
        'url'   => 'inventory/supplier',
        'section' => 'inventory',
        'permission' => array('product_supplier', 'is_view'),
    ),
    array(
        'label' => 'unit',
        'url'   => 'inventory/unit',
        'section' => 'inventory',
        'permission' => array('product_unit', 'is_view'),
    ),
    array(
        'label' => 'purchase',
        'url'   => 'inventory/purchase',
        'section' => 'inventory',
        'permission' => array('product_purchase', 'is_view'),
    ),
    array(
        'label' => 'sales',
        'url'   => 'inventory/sales',
        'section' => 'inventory',
        'permission' => array('product_sales', 'is_view'),
    ),
    array(
        'label' => 'issue',
        'url'   => 'inventory/issue',
        'section' => 'inventory',
        'permission' => array('product_issue', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Website / frontend
    // ---------------------------------------------------------------
    array(
        'label' => 'setting',
        'url'   => 'frontend/setting',
        'section' => 'website',
        'permission' => array('frontend_setting', 'is_view'),
    ),
    array(
        'label' => 'menu',
        'url'   => 'frontend/menu',
        'section' => 'website',
        'permission' => array('frontend_menu', 'is_view'),
    ),
    array(
        'label' => 'page_section', // translate('page') . " " . translate('section')
        'url'   => 'frontend/section/index',
        'section' => 'website',
        'permission' => array('frontend_section', 'is_view'),
    ),
    array(
        'label' => 'manage_page', // translate('manage') . " " . translate('page')
        'url'   => 'frontend/content/index',
        'section' => 'website',
        'permission' => array('manage_page', 'is_view'),
    ),
    array(
        'label' => 'slider',
        'url'   => 'frontend/slider',
        'section' => 'website',
        'permission' => array('frontend_slider', 'is_view'),
    ),
    array(
        'label' => 'features',
        'url'   => 'frontend/features',
        'section' => 'website',
        'permission' => array('frontend_features', 'is_view'),
    ),
    array(
        'label' => 'testimonial',
        'url'   => 'frontend/testimonial',
        'section' => 'website',
        'permission' => array('frontend_testimonial', 'is_view'),
    ),
    array(
        'label' => 'service',
        'url'   => 'frontend/services',
        'section' => 'website',
        'permission' => array('frontend_services', 'is_view'),
    ),
    array(
        'label' => 'faq',
        'url'   => 'frontend/faq/index',
        'section' => 'website',
        'permission' => array('frontend_faq', 'is_view'),
    ),
    array(
        'label' => 'gallery_category', // translate('gallery') . " " . translate('category')
        'url'   => 'frontend/gallery/category',
        'section' => 'website',
        'permission' => array('frontend_gallery_category', 'is_view'),
    ),
    array(
        'label' => 'gallery',
        'url'   => 'frontend/gallery/index',
        'section' => 'website',
        'permission' => array('frontend_gallery', 'is_view'),
    ),
    array(
        'label' => 'news',
        'url'   => 'frontend/news/index',
        'section' => 'website',
        'permission' => array('frontend_news', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Reception
    // ---------------------------------------------------------------
    array(
        'label' => 'admission_enquiry',
        'url'   => 'reception/enquiry',
        'section' => 'reception',
        'permission' => array('enquiry', 'is_view'),
    ),
    array(
        'label' => 'postal_record',
        'url'   => 'reception/postal',
        'section' => 'reception',
        'permission' => array('postal_record', 'is_view'),
    ),
    array(
        'label' => 'call_log',
        'url'   => 'reception/call_log',
        'section' => 'reception',
        'permission' => array('call_log', 'is_view'),
    ),
    array(
        'label' => 'visitor_log',
        'url'   => 'reception/visitor_log',
        'section' => 'reception',
        'permission' => array('visitor_log', 'is_view'),
    ),
    array(
        'label' => 'complaint',
        'url'   => 'reception/complaint',
        'section' => 'reception',
        'permission' => array('complaint', 'is_view'),
    ),
    array(
        'label' => 'Config Reception', // literal string, not a translate() call
        'url'   => 'reception_config/reference',
        'section' => 'reception',
        'permission' => array('config_reception', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Admission
    // ---------------------------------------------------------------
    array(
        'label' => 'create_admission',
        'url'   => 'student/add',
        'section' => 'admission',
        'permission' => array('student', 'is_add'),
    ),
    array(
        'label' => 'online_admission',
        'url'   => 'online_admission/index',
        'section' => 'admission',
        'permission' => array('online_admission', 'is_view'),
    ),
    array(
        'label' => 'admission_approvals',
        'url'   => 'online_admission/admission_approvals',
        'section' => 'admission',
        'permission' => array(array('online_admission_approve', 'is_view'), array('online_admission', 'is_add')),
    ),
    array(
        'label' => 'multi_class',
        'url'   => 'multiclass/index',
        'section' => 'admission',
        'permission' => array('multi_class', 'is_add'),
    ),
    array(
        'label' => 'multiple_import',
        'url'   => 'student/csv_import',
        'section' => 'admission',
        'permission' => array('multiple_import', 'is_add'),
    ),
    array(
        'label' => 'category',
        'url'   => 'student/category',
        'section' => 'admission',
        'permission' => array('student_category', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Student details
    // ---------------------------------------------------------------
    array(
        'label' => 'student_list',
        'url'   => 'student/view',
        'section' => 'student',
        'permission' => array('student', 'is_view'),
    ),
    array(
        'label' => 'login_deactivate',
        'url'   => 'student/disable_authentication',
        'section' => 'student',
        'permission' => array('student_disable_authentication', 'is_view'),
    ),
    array(
        'label' => 'deactivate_reason',
        'url'   => 'student/disable_reason',
        'section' => 'student',
        'permission' => array('disable_reason', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Parents
    // ---------------------------------------------------------------
    array(
        'label' => 'parents_list',
        'url'   => 'parents/view',
        'section' => 'parents',
        'permission' => array('parent', 'is_view'),
    ),
    array(
        'label' => 'add_parent',
        'url'   => 'parents/add',
        'section' => 'parents',
        'permission' => array('parent', 'is_add'),
    ),
    array(
        'label' => 'login_deactivate',
        'url'   => 'parents/disable_authentication',
        'section' => 'parents',
        'permission' => array('parent_disable_authentication', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Employees
    // ---------------------------------------------------------------
    array(
        'label' => 'employee_list',
        'url'   => 'employee/view',
        'section' => 'employee',
        'permission' => array('employee', 'is_view'),
    ),
    array(
        'label' => 'add_department',
        'url'   => 'employee/department',
        'section' => 'employee',
        'permission' => array(array('department', 'is_view'), array('department', 'is_add')),
    ),
    array(
        'label' => 'add_designation',
        'url'   => 'employee/designation',
        'section' => 'employee',
        'permission' => array(array('designation', 'is_view'), array('designation', 'is_add')),
    ),
    array(
        'label' => 'add_employee',
        'url'   => 'employee/add',
        'section' => 'employee',
        'permission' => array('employee', 'is_add'),
    ),
    array(
        'label' => 'login_deactivate',
        'url'   => 'employee/disable_authentication',
        'section' => 'employee',
        'permission' => array('employee_disable_authentication', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Card management
    // ---------------------------------------------------------------
    array(
        'label' => 'id_card_template', // translate('id_card') . " " . translate('template')
        'url'   => 'card_manage/id_card_templete',
        'section' => 'card_management',
        'permission' => array('id_card_templete', 'is_view'),
    ),
    array(
        'label' => 'student_id_card', // translate('student') . " " . translate('id_card')
        'url'   => 'card_manage/generate_student_idcard',
        'section' => 'card_management',
        'permission' => array('generate_student_idcard', 'is_view'),
    ),
    array(
        'label' => 'employee_id_card', // translate('employee') . " " . translate('id_card')
        'url'   => 'card_manage/generate_employee_idcard',
        'section' => 'card_management',
        'permission' => array('generate_employee_idcard', 'is_view'),
    ),
    array(
        'label' => 'admit_card_template', // translate('admit_card') . " " . translate('template')
        'url'   => 'card_manage/admit_card_templete',
        'section' => 'card_management',
        'permission' => array('admit_card_templete', 'is_view'),
    ),
    array(
        'label' => 'generate_admit_card', // translate('generate') . " " . translate('admit_card')
        'url'   => 'card_manage/generate_student_admitcard',
        'section' => 'card_management',
        'permission' => array('generate_admit_card', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Certificate
    // ---------------------------------------------------------------
    array(
        'label' => 'certificate_template', // translate('certificate') . " " . translate('template')
        'url'   => 'certificate',
        'section' => 'certificate',
        'permission' => array('certificate_templete', 'is_view'),
    ),
    array(
        'label' => 'generate_student', // translate('generate') . " " . translate('student')
        'url'   => 'certificate/generate_student',
        'section' => 'certificate',
        'permission' => array('generate_student_certificate', 'is_view'),
    ),
    array(
        'label' => 'generate_employee', // translate('generate') . " " . translate('employee')
        'url'   => 'certificate/generate_employee',
        'section' => 'certificate',
        'permission' => array('generate_employee_certificate', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // HRM (payroll, advance salary, leave, award)
    // ---------------------------------------------------------------
    array(
        'label' => 'salary_template',
        'url'   => 'payroll/salary_template',
        'section' => 'hrm',
        'permission' => array('salary_template', 'is_view'),
    ),
    array(
        'label' => 'salary_assign',
        'url'   => 'payroll/salary_assign',
        'section' => 'hrm',
        'permission' => array('salary_assign', 'is_view'),
    ),
    array(
        'label' => 'salary_payment',
        'url'   => 'payroll',
        'section' => 'hrm',
        'permission' => array('salary_payment', 'is_view'),
    ),
    array(
        'label' => 'my_application',
        'url'   => 'advance_salary/request',
        'section' => 'hrm',
        'permission' => array('advance_salary_request', 'is_view'),
    ),
    array(
        'label' => 'manage_application',
        'url'   => 'advance_salary',
        'section' => 'hrm',
        'permission' => array('advance_salary_manage', 'is_view'),
    ),
    array(
        'label' => 'category',
        'url'   => 'leave/category',
        'section' => 'hrm',
        'permission' => array('leave_category', 'is_view'),
    ),
    array(
        'label' => 'my_application',
        'url'   => 'leave/request',
        'section' => 'hrm',
        'permission' => array('leave_request', 'is_view'),
    ),
    array(
        'label' => 'manage_application',
        'url'   => 'leave',
        'section' => 'hrm',
        'permission' => array('leave_manage', 'is_view'),
    ),
    array(
        'label' => 'award',
        'url'   => 'award',
        'section' => 'hrm',
        'permission' => array('award', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Academic
    // ---------------------------------------------------------------
    array(
        'label' => 'control_classes',
        'url'   => 'classes',
        'note'  => 'dynamic: get_permission(\'classes\', \'is_view\') ? base_url(\'classes\') : base_url(\'sections\')',
        'section' => 'academic',
        'permission' => array(array('classes', 'is_view'), array('section', 'is_view')),
    ),
    array(
        'label' => 'assign_class_teacher',
        'url'   => 'classes/teacher_allocation',
        'section' => 'academic',
        'permission' => array('assign_class_teacher', 'is_view'),
    ),
    array(
        'label' => 'subject',
        'url'   => 'subject/index',
        'section' => 'academic',
        'permission' => array('subject', 'is_view'),
    ),
    array(
        'label' => 'class_assign',
        'url'   => 'subject/class_assign',
        'section' => 'academic',
        'permission' => array('subject_class_assign', 'is_view'),
    ),
    array(
        'label' => 'class_schedule', // translate('class') . " " . translate('schedule')
        'url'   => 'timetable/viewclass',
        'section' => 'academic',
        'permission' => array('class_timetable', 'is_view'),
    ),
    array(
        'label' => 'teacher_schedule', // translate('teacher') . " " . translate('schedule')
        'url'   => 'timetable/teacherview',
        'section' => 'academic',
        'permission' => array('teacher_timetable', 'is_view'),
    ),
    array(
        'label' => 'promotion',
        'url'   => 'student_promotion',
        'section' => 'academic',
        'permission' => array('student_promotion', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Live class rooms
    // ---------------------------------------------------------------
    array(
        'label' => 'live_class_rooms',
        'url'   => 'live_class',
        'section' => 'live_class',
        'permission' => array('live_class', 'is_view'),
    ),
    array(
        'label' => ' live_class_reports', // translate(' live_class_reports') -- key has leading space in source
        'url'   => 'live_class/reports',
        'section' => 'live_class',
        'permission' => array('live_class', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Attachments book
    // ---------------------------------------------------------------
    array(
        'label' => 'upload_content',
        'url'   => 'attachments',
        'section' => 'attachments',
        'permission' => array('attachments', 'is_view'),
    ),
    array(
        'label' => 'attachment_type',
        'url'   => 'attachments/type',
        'section' => 'attachments',
        'permission' => array('attachment_type', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Homework
    // ---------------------------------------------------------------
    array(
        'label' => 'homework',
        'url'   => 'homework',
        'section' => 'homework',
        'permission' => array('homework', 'is_view'),
    ),
    array(
        'label' => 'evaluation_report',
        'url'   => 'homework/report',
        'section' => 'homework',
        'permission' => array('evaluation_report', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Exam master
    // ---------------------------------------------------------------
    array(
        'label' => 'exam_term',
        'url'   => 'exam/term',
        'section' => 'exam',
        'permission' => array('exam_term', 'is_view'),
    ),
    array(
        'label' => 'exam_hall',
        'url'   => 'exam/hall',
        'section' => 'exam',
        'permission' => array('exam_hall', 'is_view'),
    ),
    array(
        'label' => 'distribution',
        'url'   => 'exam/mark_distribution',
        'section' => 'exam',
        'permission' => array('mark_distribution', 'is_view'),
    ),
    array(
        'label' => 'exam_setup',
        'url'   => 'exam',
        'section' => 'exam',
        'permission' => array('exam', 'is_view'),
    ),
    array(
        'label' => 'marksheet_template',
        'url'   => 'marksheet_template/index',
        'section' => 'exam',
        'permission' => array('marksheet_template', 'is_view'),
    ),
    array(
        'label' => 'schedule',
        'url'   => 'timetable/viewexam',
        'section' => 'exam',
        'permission' => array('exam_timetable', 'is_view'),
    ),
    array(
        'label' => 'add_schedule', // translate('add') . " " . translate('schedule')
        'url'   => 'timetable/set_examwise',
        'section' => 'exam',
        'permission' => array('exam_timetable', 'is_view'),
    ),
    array(
        'label' => 'mark_entries',
        'url'   => 'exam/mark_entry',
        'section' => 'exam',
        'permission' => array('exam_mark', 'is_view'),
    ),
    array(
        'label' => 'generate_position',
        'url'   => 'exam/class_position',
        'section' => 'exam',
        'permission' => array('generate_position', 'is_view'),
    ),
    array(
        'label' => 'psychomotor_rating',
        'url'   => 'exam/psychomotor_entry',
        'section' => 'exam',
        'permission' => array('psychomotor_rating', 'is_view'),
    ),
    array(
        'label' => 'grades_range',
        'url'   => 'exam/grade',
        'section' => 'exam',
        'permission' => array('exam_grade', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Online exam
    // ---------------------------------------------------------------
    array(
        'label' => 'online_exam',
        'url'   => 'onlineexam',
        'section' => 'online_exam',
        'permission' => array('online_exam', 'is_view'),
    ),
    array(
        'label' => 'question_bank',
        'url'   => 'onlineexam/question',
        'section' => 'online_exam',
        'permission' => array('question_bank', 'is_view'),
    ),
    array(
        'label' => 'question_group',
        'url'   => 'onlineexam/question_group',
        'section' => 'online_exam',
        'permission' => array('question_group', 'is_view'),
    ),
    array(
        'label' => 'position_generate', // translate('position') . " " . translate('generate')
        'url'   => 'onlineexam/position_generate',
        'section' => 'online_exam',
        'permission' => array('position_generate', 'is_view'),
    ),
    array(
        'label' => 'exam_result',
        'url'   => 'onlineexam/result',
        'section' => 'online_exam',
        'permission' => array('exam_result', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Supervision (hostel / transport)
    // ---------------------------------------------------------------
    array(
        'label' => 'hostel_master',
        'url'   => 'hostels',
        'section' => 'supervision',
        'permission' => array('hostel', 'is_view'),
    ),
    array(
        'label' => 'hostel_room',
        'url'   => 'hostels/room',
        'section' => 'supervision',
        'permission' => array('hostel_room', 'is_view'),
    ),
    array(
        'label' => 'category',
        'url'   => 'hostels/category',
        'section' => 'supervision',
        'permission' => array('hostel_category', 'is_view'),
    ),
    array(
        'label' => 'allocation_report',
        'url'   => 'hostels/allocation_report',
        'section' => 'supervision',
        'permission' => array('hostel_allocation', 'is_view'),
    ),
    array(
        'label' => 'route_master',
        'url'   => 'transport/route',
        'section' => 'supervision',
        'permission' => array('transport_route', 'is_view'),
    ),
    array(
        'label' => 'vehicle_master',
        'url'   => 'transport/vehicle',
        'section' => 'supervision',
        'permission' => array('transport_vehicle', 'is_view'),
    ),
    array(
        'label' => 'stoppage',
        'url'   => 'transport/stoppage',
        'section' => 'supervision',
        'permission' => array('transport_stoppage', 'is_view'),
    ),
    array(
        'label' => 'assign_vehicle',
        'url'   => 'transport/assign',
        'section' => 'supervision',
        'permission' => array('transport_assign', 'is_view'),
    ),
    array(
        'label' => 'allocation_report',
        'url'   => 'transport/report',
        'section' => 'supervision',
        'permission' => array('transport_allocation', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Attendance
    // ---------------------------------------------------------------
    array(
        'label' => 'student',
        'url'   => 'attendance/student_entry',
        'section' => 'attendance',
        'permission' => array('student_attendance', 'is_add'),
    ),
    array(
        'label' => 'subject_wise',
        'url'   => 'attendance_period/index',
        'section' => 'attendance',
        'permission' => array('student_attendance', 'is_add'),
    ),
    array(
        'label' => 'SchoolEdge QR Scanner', // literal string, not a translate() call
        'url'   => 'schooledge_qr_attendance',
        'section' => 'attendance',
        'permission' => array('employee_attendance', 'is_add'),
    ),
    array(
        'label' => 'employee',
        'url'   => 'attendance/employees_entry',
        'section' => 'attendance',
        'permission' => array('employee_attendance', 'is_add'),
    ),
    array(
        'label' => 'exam',
        'url'   => 'attendance/exam_entry',
        'section' => 'attendance',
        'permission' => array('exam_attendance', 'is_add'),
    ),

    // ---------------------------------------------------------------
    // Library
    // ---------------------------------------------------------------
    array(
        'label' => 'books',
        'url'   => 'library/book',
        'section' => 'library',
        'permission' => array('book', 'is_view'),
    ),
    array(
        'label' => 'books_category',
        'url'   => 'library/category',
        'section' => 'library',
        'permission' => array('book_category', 'is_view'),
    ),
    array(
        'label' => 'my_issued_book',
        'url'   => 'library/request',
        'section' => 'library',
        'permission' => array('book_request', 'is_view'),
    ),
    array(
        'label' => 'book_issue/return',
        'url'   => 'library/book_manage',
        'section' => 'library',
        'permission' => array('book_manage', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Events
    // ---------------------------------------------------------------
    array(
        'label' => 'event_type',
        'url'   => 'event/types',
        'section' => 'events',
        'permission' => array('event_type', 'is_view'),
    ),
    array(
        'label' => 'events',
        'url'   => 'event',
        'section' => 'events',
        'permission' => array('event', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Bulk SMS and Email
    // ---------------------------------------------------------------
    array(
        'label' => 'Send Sms / Email', // translate('send') . ' Sms / Email'
        'url'   => 'sendsmsmail/sms',
        'section' => 'communication',
        'permission' => array('sendsmsmail', 'is_add'),
    ),
    array(
        'label' => 'Sms / Email Report', // 'Sms / Email ' . translate('report')
        'url'   => 'sendsmsmail/campaign_reports',
        'section' => 'communication',
        'permission' => array('sendsmsmail', 'is_add'),
    ),
    array(
        'label' => 'sms_template', // translate('sms') . " " . translate('template')
        'url'   => 'sendsmsmail/template/sms',
        'section' => 'communication',
        'permission' => array('sendsmsmail_template', 'is_view'),
    ),
    array(
        'label' => 'email_template', // translate('email') . " " . translate('template')
        'url'   => 'sendsmsmail/template/email',
        'section' => 'communication',
        'permission' => array('sendsmsmail_template', 'is_view'),
    ),
    array(
        'label' => 'Student Birthday Wishes', // literal string, not a translate() call
        'url'   => 'birthday/student',
        'section' => 'communication',
        'permission' => array('student_birthday_wishes', 'is_view'),
    ),
    array(
        'label' => 'Staff Birthday Wishes', // literal string, not a translate() call
        'url'   => 'birthday/staff',
        'section' => 'communication',
        'permission' => array('staff_birthday_wishes', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Student accounting (fees)
    // ---------------------------------------------------------------
    array(
        'label' => 'payments_type', // translate('payments') . " " . translate('type')
        'url'   => 'offline_payments/type',
        'section' => 'fees',
        'permission' => array('offline_payments_type', 'is_view'),
    ),
    array(
        'label' => ' offline_payments', // translate(' offline_payments') . $getOfflinePaymentsTotal -- key has leading space in source
        'url'   => 'offline_payments/payments',
        'section' => 'fees',
        'permission' => array('offline_payments', 'is_view'),
    ),
    array(
        'label' => 'fees_type',
        'url'   => 'fees/type',
        'section' => 'fees',
        'permission' => array('fees_type', 'is_view'),
    ),
    array(
        'label' => 'fees_group',
        'url'   => 'fees/group',
        'section' => 'fees',
        'permission' => array('fees_group', 'is_view'),
    ),
    array(
        'label' => 'fine_setup',
        'url'   => 'fees/fine_setup',
        'section' => 'fees',
        'permission' => array('fees_fine_setup', 'is_view'),
    ),
    array(
        'label' => 'fees_allocation',
        'url'   => 'fees/allocation',
        'section' => 'fees',
        'permission' => array('fees_allocation', 'is_view'),
    ),
    array(
        'label' => 'payments_history',
        'url'   => 'fees/invoice_list',
        'section' => 'fees',
        'permission' => array('invoice', 'is_view'),
    ),
    array(
        'label' => 'due_fees_invoice',
        'url'   => 'fees/due_invoice',
        'section' => 'fees',
        'permission' => array('due_invoice', 'is_view'),
    ),
    array(
        'label' => 'fee_collection_approvals',
        'url'   => 'fees/collection_approvals',
        'section' => 'fees',
        'permission' => array(array('collect_fees_approve', 'is_view'), array('collect_fees', 'is_add')),
    ),
    array(
        'label' => 'fees_reminder',
        'url'   => 'fees/reminder',
        'section' => 'fees',
        'permission' => array('fees_reminder', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Office accounting
    // ---------------------------------------------------------------
    array(
        'label' => 'account',
        'url'   => 'accounting',
        'section' => 'accounting',
        'permission' => array('account', 'is_view'),
    ),
    array(
        'label' => 'new_deposit',
        'url'   => 'accounting/voucher_deposit',
        'section' => 'accounting',
        'permission' => array('deposit', 'is_view'),
    ),
    array(
        'label' => 'new_expense',
        'url'   => 'accounting/voucher_expense',
        'section' => 'accounting',
        'permission' => array('expense', 'is_view'),
    ),
    array(
        'label' => 'expense_approvals',
        'url'   => 'accounting/expense_approvals',
        'section' => 'accounting',
        'permission' => array(array('expense_approve', 'is_view'), array('expense', 'is_add')),
    ),
    array(
        'label' => 'all_transactions',
        'url'   => 'accounting/all_transactions',
        'section' => 'accounting',
        'permission' => array('all_transactions', 'is_view'),
    ),
    array(
        'label' => 'voucher_head', // translate('voucher') . " " . translate('head')
        'url'   => 'accounting/voucher_head',
        'section' => 'accounting',
        'permission' => array(array('voucher_head', 'is_view'), array('voucher_head', 'is_add')),
    ),

    // ---------------------------------------------------------------
    // Reports - student
    // ---------------------------------------------------------------
    array(
        'label' => 'login_credential',
        'url'   => 'student/login_credential_reports',
        'section' => 'reports',
        'permission' => array('student', 'is_view'),
    ),
    array(
        'label' => 'admission_report',
        'url'   => 'student/admission_reports',
        'section' => 'reports',
        'permission' => array('student', 'is_view'),
    ),
    array(
        'label' => 'class_&_section_report',
        'url'   => 'student/classsection_reports',
        'section' => 'reports',
        'permission' => array('student', 'is_view'),
    ),
    array(
        'label' => 'sibling_report',
        'url'   => 'student/sibling_report',
        'section' => 'reports',
        'permission' => array('student', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Reports - fees
    // ---------------------------------------------------------------
    array(
        'label' => 'fees_report',
        'url'   => 'fees/student_fees_report',
        'section' => 'reports',
        'permission' => array('fees_reports', 'is_view'),
    ),
    array(
        'label' => 'receipts_report',
        'url'   => 'fees/payment_history',
        'section' => 'reports',
        'permission' => array('fees_reports', 'is_view'),
    ),
    array(
        'label' => 'due_fees_report',
        'url'   => 'fees/due_report',
        'section' => 'reports',
        'permission' => array('fees_reports', 'is_view'),
    ),
    array(
        'label' => 'fine_report',
        'url'   => 'fees/fine_report',
        'section' => 'reports',
        'permission' => array('fees_reports', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Reports - financial (office accounting)
    // ---------------------------------------------------------------
    array(
        'label' => 'account_statement', // translate('account') . " " . translate('statement')
        'url'   => 'accounting/account_statement',
        'section' => 'reports',
        'permission' => array('accounting_reports', 'is_view'),
    ),
    array(
        'label' => 'income_repots', // translate('income') . " " . translate('repots')
        'url'   => 'accounting/income_repots',
        'section' => 'reports',
        'permission' => array('accounting_reports', 'is_view'),
    ),
    array(
        'label' => 'expense_repots', // translate('expense') . " " . translate('repots')
        'url'   => 'accounting/expense_repots',
        'section' => 'reports',
        'permission' => array('accounting_reports', 'is_view'),
    ),
    array(
        'label' => 'transitions_reports', // translate('transitions') . " " . translate('reports')
        'url'   => 'accounting/transitions_repots',
        'section' => 'reports',
        'permission' => array('accounting_reports', 'is_view'),
    ),
    array(
        'label' => 'balance_sheet', // translate('balance') . " " . translate('sheet')
        'url'   => 'accounting/balance_sheet',
        'section' => 'reports',
        'permission' => array('accounting_reports', 'is_view'),
    ),
    array(
        'label' => 'income_vs_expense',
        'url'   => 'accounting/incomevsexpense',
        'section' => 'reports',
        'permission' => array('accounting_reports', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Reports - attendance
    // ---------------------------------------------------------------
    array(
        'label' => 'student_reports', // translate('student') . ' ' . translate('reports')
        'url'   => 'attendance/studentwise_report',
        'section' => 'reports',
        'permission' => array('student_attendance_report', 'is_view'),
    ),
    array(
        'label' => 'student_daily_reports', // translate('student') . ' ' . translate('daily_reports')
        'url'   => 'attendance/student_classreport',
        'section' => 'reports',
        'permission' => array('student_attendance_report', 'is_view'),
    ),
    array(
        'label' => 'student_overview_reports', // translate('student') . ' ' . translate('overview_reports')
        'url'   => 'attendance/studentwise_overview',
        'section' => 'reports',
        'permission' => array('student_attendance_report', 'is_view'),
    ),
    array(
        'label' => 'subject_wise_reports',
        'url'   => 'attendance_period/reports',
        'section' => 'reports',
        'permission' => array('student_attendance_report', 'is_view'),
    ),
    array(
        'label' => 'subject_wise_by_day', // translate('subject_wise_by') . ' ' . translate('day')
        'url'   => 'attendance_period/reportsbydate',
        'section' => 'reports',
        'permission' => array('student_attendance_report', 'is_view'),
    ),
    array(
        'label' => 'subject_wise_by_month', // translate('subject_wise_by') . ' ' . translate('month')
        'url'   => 'attendance_period/reportbymonth',
        'section' => 'reports',
        'permission' => array('student_attendance_report', 'is_view'),
    ),
    array(
        'label' => 'employee_reports', // translate('employee') . ' ' . translate('reports')
        'url'   => 'attendance/employeewise_report',
        'section' => 'reports',
        'permission' => array('employee_attendance_report', 'is_view'),
    ),
    array(
        'label' => 'exam_reports', // translate('exam') . ' ' . translate('reports')
        'url'   => 'attendance/examwise_report',
        'section' => 'reports',
        'permission' => array('exam_attendance_report', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Reports - HRM
    // ---------------------------------------------------------------
    array(
        'label' => 'payroll_summary',
        'url'   => 'payroll/salary_statement',
        'section' => 'reports',
        'permission' => array('salary_summary_report', 'is_view'),
    ),
    array(
        'label' => 'leave_reports', // translate('leave') . " " . translate('reports')
        'url'   => 'leave/reports',
        'section' => 'reports',
        'permission' => array('leave_reports', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Reports - examination
    // ---------------------------------------------------------------
    array(
        'label' => 'report_card',
        'url'   => 'exam/marksheet',
        'section' => 'reports',
        'permission' => array('report_card', 'is_view'),
    ),
    array(
        'label' => 'tabulation_sheet',
        'url'   => 'exam/tabulation_sheet',
        'section' => 'reports',
        'permission' => array('tabulation_sheet', 'is_view'),
    ),
    array(
        'label' => 'progress_reports', // translate('progress') . " " . translate('reports')
        'url'   => 'exam_progress/marksheet',
        'section' => 'reports',
        'permission' => array('progress_reports', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Reports - inventory
    // ---------------------------------------------------------------
    array(
        'label' => 'stock_report', // translate('stock') . " " . translate('report')
        'url'   => 'inventory/stockreport',
        'section' => 'reports',
        'permission' => array('inventory_report', 'is_view'),
    ),
    array(
        'label' => 'purchase_report', // translate('purchase') . " " . translate('report')
        'url'   => 'inventory/purchase_report',
        'section' => 'reports',
        'permission' => array('inventory_report', 'is_view'),
    ),
    array(
        'label' => 'sales_report', // translate('sales') . " " . translate('report')
        'url'   => 'inventory/sales_report',
        'section' => 'reports',
        'permission' => array('inventory_report', 'is_view'),
    ),
    array(
        'label' => 'issues_report', // translate('issues') . " " . translate('report')
        'url'   => 'inventory/issues_report',
        'section' => 'reports',
        'permission' => array('inventory_report', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Alumni
    // ---------------------------------------------------------------
    array(
        'label' => 'manage_alumni',
        'url'   => 'alumni/index',
        'section' => 'alumni',
        'permission' => array('manage_alumni', 'is_view'),
    ),
    array(
        'label' => 'events',
        'url'   => 'alumni/event',
        'section' => 'alumni',
        'permission' => array('alumni_events', 'is_view'),
    ),

    // ---------------------------------------------------------------
    // Settings
    // ---------------------------------------------------------------
    array(
        'label' => 'global_settings',
        'url'   => 'settings/universal',
        'section' => 'settings',
        'permission' => array('global_settings', 'is_view'),
    ),
    array(
        'label' => 'school_settings',
        'url'   => 'school_settings',
        'section' => 'settings',
        'permission' => array(
            array('school_settings', 'is_view'),
            array('live_class_config', 'is_view'),
            array('payment_settings', 'is_view'),
            array('sms_settings', 'is_view'),
            array('email_settings', 'is_view'),
            array('accounting_links', 'is_view'),
        ),
    ),
    array(
        'label' => 'role_permission',
        'url'   => 'role',
        'section' => 'settings',
        'permission' => 'superadmin_only',
    ),
    array(
        'label' => 'session_settings',
        'url'   => 'sessions',
        'section' => 'settings',
        'permission' => 'superadmin_only',
    ),
    array(
        'label' => 'translations',
        'url'   => 'translations',
        'section' => 'settings',
        'permission' => array('translations', 'is_view'),
    ),
    array(
        'label' => 'cron_job',
        'url'   => 'cron_api',
        'section' => 'settings',
        'permission' => array('cron_job', 'is_view'),
    ),
    array(
        'label' => 'modules',
        'url'   => 'modules',
        'section' => 'settings',
        'permission' => 'superadmin_only',
    ),
    array(
        'label' => 'system_student_field',
        'url'   => 'system_student_field',
        'section' => 'settings',
        'permission' => array('system_student_field', 'is_view'),
    ),
    array(
        'label' => 'custom_field',
        'url'   => 'custom_field',
        'section' => 'settings',
        'permission' => array('custom_field', 'is_view'),
    ),
    array(
        'label' => 'database_backup',
        'url'   => 'backup',
        'section' => 'settings',
        'permission' => array('backup', 'is_view'),
    ),
    array(
        'label' => 'system_update',
        'url'   => 'system_update',
        'section' => 'settings',
        'permission' => array('system_update', 'is_add'),
    ),
    array(
        'label' => 'user_login_log',
        'url'   => 'user_login_log/index',
        'section' => 'settings',
        'permission' => array('user_login_log', 'is_view'),
    ),
    array(
        'label' => 'audit_log',
        'url'   => 'audit_log',
        'section' => 'settings',
        'permission' => array('audit_log', 'is_view'),
    ),

);
