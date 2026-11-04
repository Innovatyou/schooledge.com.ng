<?php
// On the dashboard: a banner with today's celebrations, and the celebration
// pop-up (with animation) for anything about the viewer, shown once each.
if (!get_permission('celebrations', 'is_view') || is_superadmin_loggedin()) {
	return;
}
$CI = &get_instance();
$CI->load->model('celebration_model');
$branchID = get_loggedin_branch_id();
$wall = $CI->celebration_model->wall($branchID);
$role = is_student_loggedin() ? 'student' : (is_parent_loggedin() ? 'parent' : 'staff');
$items = $CI->celebration_model->forViewer($branchID, $role, get_loggedin_user_id(), $wall);

$bits = array();
if (count($wall['birthdays'])) {
	$bits[] = count($wall['birthdays']) . ' birthday' . (count($wall['birthdays']) == 1 ? '' : 's') . ' today';
}
foreach ($wall['best_teachers'] as $p) {
	$bits[] = $p['title'] . ': ' . $p['name'];
}
foreach ($wall['star_students'] as $p) {
	$bits[] = $p['title'] . ': ' . $p['name'];
}
foreach ($wall['events'] as $p) {
	$bits[] = $p['title'];
}
if (!empty($wall['top_performers']['classes'])) {
	$bits[] = 'Top performers of ' . $wall['top_performers']['exam'];
}
if (empty($bits) && empty($items)) {
	return;
}
?>
<link rel="stylesheet" href="<?=base_url('assets/css/celebrate.css?v=' . filemtime(FCPATH . 'assets/css/celebrate.css'))?>">
<?php if (!empty($bits)) { ?>
<a class="cb-banner" href="<?=base_url('celebration')?>">
	<span class="cb-banner-emoji">🎉</span>
	<span><b>Celebrations</b><small><?=html_escape(implode(' · ', array_slice($bits, 0, 3)))?><?=count($bits) > 3 ? ' and more' : ''?></small></span>
	<span class="cb-banner-go">See all <i class="fas fa-arrow-right"></i></span>
</a>
<?php } ?>
<?php if (!empty($items)) { ?>
<script src="<?=base_url('assets/js/celebrate.js?v=' . filemtime(FCPATH . 'assets/js/celebrate.js'))?>"></script>
<script>
	window.addEventListener('load', function () {
		// after the page loader has gone
		setTimeout(function () {
			SkoolCelebrate.show(<?=json_encode(array_values($items), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>, { once: true });
		}, 900);
	});
</script>
<?php } ?>
