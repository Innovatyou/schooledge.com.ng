<?php
// Opens the auth layout: brand showcase on the left, form card on the right.
// Close it with authentication/_auth_shell_close. Param: $branch_id (optional)
$logoUrl = $this->application_model->getBranchImage(isset($branch_id) ? $branch_id : '', 'logo');
$schoolName = $global_config['institute_name'];
?>
<body class="sk-auth">
	<button type="button" class="sk-theme" aria-label="Switch to dark mode" title="Toggle dark mode">
		<i class="fas fa-moon"></i><i class="fas fa-sun"></i>
	</button>

	<main class="sk-shell">
		<!-- brand showcase -->
		<section class="sk-show" aria-label="<?php echo html_escape($schoolName); ?>">
			<div class="sk-orbit" aria-hidden="true">
				<span class="sk-ring sk-ring-1"><i></i></span>
				<span class="sk-ring sk-ring-2"><i></i></span>
				<span class="sk-ring sk-ring-3"><i></i></span>
			</div>
			<div class="sk-show-inner">
				<span class="sk-logo-tile"><img src="<?php echo $logoUrl; ?>" alt="<?php echo html_escape($schoolName); ?>"></span>
				<h1><?php echo translate('welcome_to'); ?> <span><?php echo $schoolName; ?></span></h1>
				<p class="sk-tagline">Results, fees, attendance and messages, all in one place.</p>
				<ul class="sk-perks">
					<li><i class="fas fa-poll"></i> Report cards and results</li>
					<li><i class="fas fa-wallet"></i> Fees and online payments</li>
					<li><i class="fas fa-bell"></i> Attendance and parent alerts</li>
				</ul>
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
			<div class="sk-card">
				<div class="sk-card-brand">
					<img src="<?php echo $logoUrl; ?>" alt="">
					<span><?php echo $schoolName; ?></span>
				</div>
