<?php
// 3D welcome banner shared by every dashboard.
// Params: $heading (HTML), $meta [[icon, text]], $actions [[url, icon, label, primary?]],
//         $floats [[tone, icon, value, label]] (up to 3), $avatar (url, optional), $after (HTML, optional)
$hour = (int) date('G');
if ($hour < 12) {
	$greet = array('fa-sun', 'Good morning');
} elseif ($hour < 17) {
	$greet = array('fa-cloud-sun', 'Good afternoon');
} else {
	$greet = array('fa-moon', 'Good evening');
}
?>
<section class="d3-hero d3-reveal">
	<div class="d3-hero-copy">
		<div class="d3-hero-id">
			<?php if (!empty($avatar)) { ?>
			<span class="d3-avatar"><img src="<?php echo $avatar; ?>" alt=""></span>
			<?php } ?>
			<div>
				<span class="d3-eyebrow"><i class="fas <?php echo $greet[0]; ?>"></i> <?php echo $greet[1]; ?> &middot; <?php echo date('l, j F'); ?></span>
				<h1><?php echo $heading; ?></h1>
				<?php if (!empty($meta)) { ?>
				<p class="d3-hero-meta">
					<?php foreach ($meta as $m) { ?>
					<span><i class="fas <?php echo $m[0]; ?>"></i> <?php echo $m[1]; ?></span>
					<?php } ?>
				</p>
				<?php } ?>
			</div>
		</div>
		<?php if (!empty($actions)) { ?>
		<div class="d3-actions">
			<?php foreach ($actions as $a) { ?>
			<a class="d3-btn<?php echo !empty($a[3]) ? ' is-primary' : ''; ?>" href="<?php echo $a[0]; ?>"><i class="fas <?php echo $a[1]; ?>"></i> <?php echo $a[2]; ?></a>
			<?php } ?>
		</div>
		<?php } ?>
		<?php if (!empty($after)) echo $after; ?>
	</div>
	<?php if (!empty($floats)) { ?>
	<div class="d3-hero-art" aria-hidden="true">
		<div class="d3-stack">
			<span class="d3-orb"></span>
			<?php foreach (array_slice($floats, 0, 3) as $f) { ?>
			<div class="d3-float tone-<?php echo $f[0]; ?>">
				<span class="d3-float-icon"><i class="fas <?php echo $f[1]; ?>"></i></span>
				<span><b><?php echo $f[2]; ?></b><small><?php echo $f[3]; ?></small></span>
			</div>
			<?php } ?>
		</div>
	</div>
	<?php } ?>
</section>
