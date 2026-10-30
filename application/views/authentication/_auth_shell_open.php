<?php
// Opens the auth layout: brand showcase plus the form card. The school's own
// sign-in settings (Frontend > Sign-in Page) pick the layout, photo, accent and copy.
// Close it with authentication/_auth_shell_close. Param: $branch_id (optional)
$authBranch = isset($branch_id) ? (int) $branch_id : 0;
$logoUrl = $this->application_model->getBranchImage($authBranch ?: '', 'logo');
$schoolName = $global_config['institute_name'];
$lp = login_page_setting($authBranch);
$perkIcons = array('fa-poll', 'fa-wallet', 'fa-bell', 'fa-calendar-check');
$headline = $lp['headline'] !== '' ? html_escape($lp['headline']) : translate('welcome_to') . ' <span>' . $schoolName . '</span>';
if ($lp['headline'] !== '') {
	// colour the last two words with the accent gradient
	$words = explode(' ', $headline);
	$tail = count($words) > 2 ? array_splice($words, -2) : array_splice($words, -1);
	$headline = implode(' ', $words) . ' <span>' . implode(' ', $tail) . '</span>';
}
$bodyClass = 'sk-auth sk-layout-' . $lp['layout'] . ($lp['bg_url'] ? ' sk-has-bg' : '');
?>
<body class="<?php echo $bodyClass; ?>" style="--accent: <?php echo $lp['accent']; ?>; --accent-2: <?php echo login_page_accent_pair($lp['accent']); ?>; --ov: <?php echo $lp['overlay'] / 100; ?>;<?php echo $lp['bg_url'] ? " --bg: url('" . $lp['bg_url'] . "');" : ''; ?>">
	<?php if ($lp['bg_url']) { ?>
	<div class="sk-bg" aria-hidden="true"><span class="sk-bg-img"></span><span class="sk-bg-ov"></span></div>
	<?php } ?>
	<button type="button" class="sk-theme" aria-label="Switch to dark mode" title="Toggle dark mode">
		<i class="fas fa-moon"></i><i class="fas fa-sun"></i>
	</button>

	<main class="sk-shell">
		<!-- brand showcase -->
		<section class="sk-show" aria-label="<?php echo html_escape($schoolName); ?>">
			<div class="sk-scene" aria-hidden="true">
				<div class="sk-orbit">
					<span class="sk-ring sk-ring-1"><i></i></span>
					<span class="sk-ring sk-ring-2"><i></i></span>
					<span class="sk-ring sk-ring-3"><i></i></span>
				</div>
				<span class="sk-cube sk-cube-1"><b></b><b></b><b></b><b></b><b></b><b></b></span>
				<span class="sk-cube sk-cube-2"><b></b><b></b><b></b><b></b><b></b><b></b></span>
				<span class="sk-chip sk-chip-1"><i class="fas fa-award"></i> A1 &middot; Excellent</span>
				<span class="sk-chip sk-chip-2"><i class="fas fa-check-circle"></i> Present today</span>
			</div>
			<div class="sk-show-inner">
				<span class="sk-logo-tile"><img src="<?php echo $logoUrl; ?>" alt="<?php echo html_escape($schoolName); ?>"></span>
				<h1><?php echo $headline; ?></h1>
				<?php if ($lp['tagline'] !== '') { ?>
				<p class="sk-tagline"><?php echo html_escape($lp['tagline']); ?></p>
				<?php } ?>
				<?php if ($lp['show_perks'] && $lp['perk_list']) { ?>
				<ul class="sk-perks">
					<?php foreach ($lp['perk_list'] as $i => $perk) { ?>
					<li style="--i: <?php echo $i; ?>"><i class="fas <?php echo $perkIcons[$i % 4]; ?>"></i> <?php echo html_escape($perk); ?></li>
					<?php } ?>
				</ul>
				<?php } ?>
				<div class="sk-show-foot">
					<?php if (!empty($global_config['address'])) { ?>
					<p class="sk-address"><i class="fas fa-map-marker-alt"></i> <?php echo $global_config['address']; ?></p>
					<?php } ?>
					<div class="sk-social">
						<?php foreach (array('facebook_url' => 'fa-facebook-f', 'twitter_url' => 'fa-twitter', 'linkedin_url' => 'fa-linkedin-in', 'youtube_url' => 'fa-youtube') as $field => $icon) {
							if (empty($global_config[$field])) continue; ?>
						<a href="<?php echo $global_config[$field]; ?>" target="_blank" rel="noopener" aria-label="<?php echo ucfirst(str_replace('_url', '', $field)); ?>"><i class="fab <?php echo $icon; ?>"></i></a>
						<?php } ?>
					</div>
				</div>
			</div>
		</section>

		<!-- form -->
		<section class="sk-panel">
			<div class="sk-card-stage">
			<div class="sk-card">
				<span class="sk-card-glare" aria-hidden="true"></span>
				<div class="sk-card-brand">
					<img src="<?php echo $logoUrl; ?>" alt="">
					<span><?php echo $schoolName; ?></span>
				</div>
