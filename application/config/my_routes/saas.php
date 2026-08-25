<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once( BASEPATH .'database/DB.php');
$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$url = rtrim($url, '/');
$domain =  parse_url($url, PHP_URL_HOST);
if (substr($domain, 0, 4) == 'www.') {
	$domain = str_replace('www.', '', $domain);
}
$db =& DB();
$saas_default = false;
if ($db->table_exists("custom_domain")) {
	$getURL = $db->select('count(id) as cid')->get_where('custom_domain', array('status' => 1, 'url' => $domain))->row()->cid;
	if($getURL > 0 ) {
		$route['authentication'] = 'authentication/index/$1';
		$route['forgot'] = 'authentication/forgot/$1';
		$route['teachers'] = 'home/teachers';
		$route['events'] = 'home/events';
		$route['news'] = 'home/news/';
		$route['about'] = 'home/about';
		$route['faq'] = 'home/faq';
		$route['admission'] = 'home/admission';
		$route['gallery'] = 'home/gallery';
		$route['contact'] = 'home/contact';
		$route['admit_card'] = 'home/admit_card';
		$route['exam_results'] = 'home/exam_results';
		$route['certificates'] = 'home/certificates';
		$route['page/(:any)'] = 'home/page/$1';
		$route['gallery_view/(:any)'] = 'home/gallery_view/$1';
		$route['event_view/(:num)'] = 'home/event_view/$1';
		$route['news_view/(:any)'] = 'home/news_view/$1';
		$route['default_controller'] = 'home/index';
	} else {
		$saas_default = true;
	}
} else {
	$saas_default = true;
}
$route['subscription_review/(:num)'] = 'saas_website/purchase_complete/$1';