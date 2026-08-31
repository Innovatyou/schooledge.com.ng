<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/

spl_autoload_register(function($className) {
    if ( substr($className, -6) == "_Addon" ) {
        $file = APPPATH . 'core/' . $className . '.php';
        if ( file_exists($file) && is_file($file) ) {
            @include_once( $file );
        }
    }
});

$routes_path = APPPATH . 'config/my_routes/';
if (is_dir($routes_path)) {
	$routes = scandir($routes_path);
	foreach ($routes as $r_file)
	{
	    if ($r_file === '.' || $r_file === '..' || $r_file === 'index.html') {
	        continue;
	    }
        $route_path = $routes_path . $r_file;
        if (file_exists($route_path)) {
            @include_once $route_path; 
        }
	} 
}

$route['(:any)/authentication'] = 'authentication/index/$1';
$route['(:any)/forgot'] = 'authentication/forgot/$1';
$route['(:any)/two_fa_verification'] = 'two_fa_verification/index/$1';
$route['(:any)/teachers'] = 'home/teachers';
$route['(:any)/events'] = 'home/events';
$route['(:any)/news'] = 'home/news/';
$route['(:any)/about'] = 'home/about';
$route['(:any)/faq'] = 'home/faq';
$route['(:any)/admission'] = 'home/admission';
$route['(:any)/gallery'] = 'home/gallery';
$route['(:any)/contact'] = 'home/contact';
$route['(:any)/admit_card'] = 'home/admit_card';
$route['(:any)/exam_results'] = 'home/exam_results';
$route['(:any)/certificates'] = 'home/certificates';
$route['(:any)/page/(:any)'] = 'home/page/$2';
$route['(:any)/gallery_view/(:any)'] = 'home/gallery_view/$2';
$route['(:any)/event_view/(:num)'] = 'home/event_view/$2';
$route['(:any)/news_view/(:any)'] = 'home/news_view/$2';

$route['api/v1/mobile/auth/login']['post'] = 'api/v1/mobile/login';
$route['api/v1/mobile/auth/otp/verify']['post'] = 'api/v1/mobile/verify_otp';
$route['api/v1/mobile/auth/otp/resend']['post'] = 'api/v1/mobile/resend_otp';
$route['api/v1/mobile/auth/refresh']['post'] = 'api/v1/mobile/refresh';
$route['api/v1/mobile/auth/logout']['post'] = 'api/v1/mobile/logout';
$route['api/v1/mobile/me']['get'] = 'api/v1/mobile/me';
$route['api/v1/mobile/schools']['get'] = 'api/v1/mobile/schools';
$route['api/v1/mobile/memberships']['get'] = 'api/v1/mobile/memberships';
$route['api/v1/mobile/auth/switch-membership']['post'] = 'api/v1/mobile/switch_membership';
$route['api/v1/mobile/reports']['get'] = 'api/v1/reports/index';
$route['api/v1/mobile/reports/(:num)/download']['get'] = 'api/v1/reports/download/$1';

$route['api/v1/mobile/fees/summary']['get'] = 'api/v1/fees/summary';
$route['api/v1/mobile/fees/history']['get'] = 'api/v1/fees/history';
$route['api/v1/mobile/fees/gateways']['get'] = 'api/v1/fees/gateways';
$route['api/v1/mobile/fees/invoice/download']['get'] = 'api/v1/fees/invoice_download';
$route['api/v1/mobile/fees/receipt/(:num)/download']['get'] = 'api/v1/fees/receipt_download/$1';
$route['api/v1/mobile/fees/checkout']['post'] = 'api/v1/fees/checkout';
$route['api/v1/mobile/fees/checkout/(:num)/verify']['post'] = 'api/v1/fees/verify/$1';
$route['api/v1/mobile/fees/checkout/complete']['get'] = 'api/v1/fees/checkout_complete';
$route['api/v1/mobile/fees/pay-with-wallet']['post'] = 'api/v1/fees/pay_with_wallet';

$route['api/v1/mobile/wallet/summary']['get'] = 'api/v1/wallet/summary';
$route['api/v1/mobile/wallet/history']['get'] = 'api/v1/wallet/history';
$route['api/v1/mobile/wallet/topup/checkout']['post'] = 'api/v1/wallet/topup_checkout';
$route['api/v1/mobile/wallet/topup/(:num)/verify']['post'] = 'api/v1/wallet/topup_verify/$1';

$route['api/v1/mobile/library/books']['get'] = 'api/v1/library/books';
$route['api/v1/mobile/library/categories']['get'] = 'api/v1/library/categories';
$route['api/v1/mobile/library/issues']['get'] = 'api/v1/library/issues';
$route['api/v1/mobile/library/books/(:num)']['get'] = 'api/v1/library/show/$1';
$route['api/v1/mobile/library/books/(:num)/read']['get'] = 'api/v1/library/read/$1';
$route['api/v1/mobile/library/books/(:num)/listen']['get'] = 'api/v1/library/readAudio/$1';

$route['api/v1/mobile/events']['get'] = 'api/v1/events/index';
$route['api/v1/mobile/events/(:num)']['get'] = 'api/v1/events/show/$1';

$route['api/v1/mobile/live-classes']['get'] = 'api/v1/liveclasses/index';
$route['api/v1/mobile/live-classes/(:num)/join']['get'] = 'api/v1/liveclasses/join/$1';

$route['api/v1/mobile/attendance/summary']['get'] = 'api/v1/attendance/summary';
$route['api/v1/mobile/attendance/classes']['get'] = 'api/v1/attendance/classes';
$route['api/v1/mobile/attendance/roster/(:num)/(:num)']['get'] = 'api/v1/attendance/roster/$1/$2';
$route['api/v1/mobile/attendance/capture']['post'] = 'api/v1/attendance/capture';
$route['api/v1/mobile/attendance/qr-token']['get'] = 'api/v1/attendance/qr_token';
$route['api/v1/mobile/attendance/scan']['post'] = 'api/v1/attendance/scan';

$route['api/v1/mobile/results/exams']['get'] = 'api/v1/results/exams';
$route['api/v1/mobile/results/exams/(:num)']['get'] = 'api/v1/results/show/$1';

$route['api/v1/mobile/homework']['get'] = 'api/v1/homework/index';
$route['api/v1/mobile/homework/(:num)/download']['get'] = 'api/v1/homework/download/$1';
$route['api/v1/mobile/homework/(:num)/submit']['post'] = 'api/v1/homework/submit/$1';
$route['api/v1/mobile/homework/(:num)/submissions']['get'] = 'api/v1/homework/submissions/$1';
$route['api/v1/mobile/homework/(:num)/submissions/(:num)/download']['get'] = 'api/v1/homework/download_submission/$1/$2';
$route['api/v1/mobile/gamification/me']['get'] = 'api/v1/gamification/me';
$route['api/v1/mobile/gamification/leaderboard']['get'] = 'api/v1/gamification/leaderboard';
$route['api/v1/mobile/safety/alerts']['get'] = 'api/v1/safety/index';
$route['api/v1/mobile/safety/alerts']['post'] = 'api/v1/safety/submit';
$route['api/v1/mobile/safety/alerts/(:num)/acknowledge']['post'] = 'api/v1/safety/acknowledge/$1';

$route['api/v1/mobile/messages']['get'] = 'api/v1/messages/index';
$route['api/v1/mobile/messages/contacts']['get'] = 'api/v1/messages/contacts';
$route['api/v1/mobile/messages/(:num)']['get'] = 'api/v1/messages/show/$1';
$route['api/v1/mobile/messages']['post'] = 'api/v1/messages/compose';
$route['api/v1/mobile/messages/(:num)/reply']['post'] = 'api/v1/messages/reply/$1';
$route['api/v1/mobile/messages/broadcast']['post'] = 'api/v1/messages/broadcast';

$route['api/v1/mobile/chat/token']['post'] = 'api/v1/chat/token';
$route['api/v1/mobile/chat/classmates']['get'] = 'api/v1/chat/classmates';
$route['api/v1/mobile/chat/voice-notes']['post'] = 'api/v1/chat/submitVoiceNote';
$route['api/v1/mobile/chat/voice-notes/(:num)']['get'] = 'api/v1/chat/voiceNote/$1';
$route['api/v1/mobile/chat/block']['post'] = 'api/v1/chat/block';
$route['api/v1/mobile/chat/unblock']['post'] = 'api/v1/chat/unblock';
$route['api/v1/mobile/chat/reports']['post'] = 'api/v1/chat/report';
$route['api/v1/mobile/chat/oversight/classes']['get'] = 'api/v1/chat/oversightClasses';
$route['api/v1/mobile/chat/oversight/(:any)']['get'] = 'api/v1/chat/oversight/$1';

$route['api/v1/mobile/timetable']['get'] = 'api/v1/timetable/index';
$route['api/v1/mobile/timetable/exams']['get'] = 'api/v1/timetable/exams';

$route['api/v1/mobile/resources']['get'] = 'api/v1/resources/index';
$route['api/v1/mobile/resources/(:num)/download']['get'] = 'api/v1/resources/download/$1';

$route['api/v1/mobile/profile']['get'] = 'api/v1/profile/show';
$route['api/v1/mobile/profile']['patch'] = 'api/v1/profile/update';
$route['api/v1/mobile/profile/photo']['post'] = 'api/v1/profile/upload_photo';
$route['api/v1/mobile/profile/change-password']['post'] = 'api/v1/profile/change_password';
$route['api/v1/mobile/profile/sessions']['get'] = 'api/v1/profile/sessions';
$route['api/v1/mobile/profile/sessions/(:num)/revoke']['post'] = 'api/v1/profile/revoke_session/$1';
$route['api/v1/mobile/profile/push-token']['patch'] = 'api/v1/profile/register_push_token';
$route['api/v1/mobile/profile/id-card']['get'] = 'api/v1/profile/id_card';

$route['api/v1/mobile/notifications']['get'] = 'api/v1/notifications/index';
$route['api/v1/mobile/notifications/unread-count']['get'] = 'api/v1/notifications/unread_count';
$route['api/v1/mobile/notifications/(:num)/read']['post'] = 'api/v1/notifications/mark_read/$1';
$route['api/v1/mobile/notifications/read-all']['post'] = 'api/v1/notifications/mark_all_read';
$route['api/v1/mobile/notifications/preferences']['get'] = 'api/v1/notifications/preferences';
$route['api/v1/mobile/notifications/preferences']['put'] = 'api/v1/notifications/update_preference';

$route['api/v1/mobile/admin/summary']['get'] = 'api/v1/admin/summary';
$route['api/v1/mobile/admin/approvals']['get'] = 'api/v1/admin/approvals';
$route['api/v1/mobile/admin/approvals/expense/(:num)/approve']['post'] = 'api/v1/admin/approve_expense/$1';
$route['api/v1/mobile/admin/approvals/expense/(:num)/reject']['post'] = 'api/v1/admin/reject_expense/$1';
$route['api/v1/mobile/admin/approvals/fees/(:num)/approve']['post'] = 'api/v1/admin/approve_fees/$1';
$route['api/v1/mobile/admin/approvals/fees/(:num)/reject']['post'] = 'api/v1/admin/reject_fees/$1';
$route['api/v1/mobile/admin/approvals/admission/(:num)']['get'] = 'api/v1/admin/admission_detail/$1';
$route['api/v1/mobile/admin/approvals/admission/(:num)/approve']['post'] = 'api/v1/admin/approve_admission/$1';
$route['api/v1/mobile/admin/approvals/admission/(:num)/reject']['post'] = 'api/v1/admin/reject_admission/$1';
$route['api/v1/mobile/admin/broadcast']['post'] = 'api/v1/admin/broadcast';
$route['api/v1/mobile/admin/lookup']['get'] = 'api/v1/admin/lookup';

$route['dashboard'] = 'dashboard/index';
$route['branch'] = 'branch/index';
$route['attachments'] = 'attachments/index';
$route['homework'] = 'homework/index';
$route['onlineexam'] = 'onlineexam/index';
$route['hostels'] = 'hostels/index';
$route['event'] = 'event/index';
$route['accounting'] = 'accounting/index';
$route['school_settings'] = 'school_settings/index';
$route['role'] = 'role/index';
$route['sessions'] = 'sessions/index';
$route['translations'] = 'translations/index';
$route['cron_api'] = 'cron_api/index';
$route['modules'] = 'modules/index';
$route['system_student_field'] = 'system_student_field/index';
$route['custom_field'] = 'custom_field/index';
$route['backup'] = 'backup/index';
$route['advance_salary'] = 'advance_salary/index';
$route['system_update'] = 'system_update/index';
$route['certificate'] = 'certificate/index';
$route['payroll'] = 'payroll/index';
$route['leave'] = 'leave/index';
$route['award'] = 'award/index';
$route['classes'] = 'classes/index';
$route['student_promotion'] = 'student_promotion/index';
$route['live_class'] = 'live_class/index';
$route['exam'] = 'exam/index';
$route['profile'] = 'profile/index';
$route['sections'] = 'sections/index';

$route['authentication'] = 'authentication/index';
$route['home'] = 'home/index';
// Without this, /search falls through to the catch-all (:any) route below
// (the public-site branch-slug resolver) instead of reaching Search::index() -
// same reason every other single-segment admin controller above is listed here.
$route['search'] = 'search/index';
// Same missing-route bug as /search above, just never caught until now:
// /audit_log had no explicit entry either, so it never reached
// Audit_log::index() - it fell through to the branch-slug catch-all, which
// is why it looked like "redirects to dashboard/landing page" rather than
// an access-denied error from inside the controller itself.
$route['audit_log'] = 'audit_log/index';
// Same missing-route bug as /search and /audit_log above - /wallet needs an
// explicit entry too or it falls through to the branch-slug catch-all.
$route['wallet'] = 'wallet/index';
// Same bug again - /veltrixwallet (bare, no method segment) falls through to
// the branch-slug catch-all and renders the public homepage instead of
// reaching Veltrixwallet::index(). Two-segment paths like
// veltrixwallet/topup and veltrixwebhook/paystack already route correctly
// without an explicit entry, same as every other multi-segment controller.
$route['veltrixwallet'] = 'veltrixwallet/index';
$route['404_override'] = 'errors';
if (!empty($saas_default) && $saas_default == true) {
	$route['default_controller'] = 'saas_website/index';
} else {
	$route['default_controller'] = 'home';
}
$route['(:any)'] = 'home/index/$1';
$route['translate_uri_dashes'] = FALSE;
