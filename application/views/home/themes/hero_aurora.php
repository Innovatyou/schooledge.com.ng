<?php
// Aurora 3D: dark glass hero, perspective grid floor, a fanned stack of photos in 3D space
$photos = $hero['photos'];
?>
<section class="st-hero st-hero-aurora">
	<div class="st-mesh" aria-hidden="true"></div>
	<div class="st-floor" aria-hidden="true"></div>
	<div class="container st-hero-grid">
		<div class="st-hero-copy">
			<span class="st-chip st-reveal"><span class="st-pulse"></span> <?php echo $hero['admission'] ? 'Admissions now open' : html_escape($hero['school']); ?></span>
			<h1 class="st-reveal"><?php echo $hero['title']; ?></h1>
			<p class="st-reveal"><?php echo $hero['text']; ?></p>
			<div class="st-actions st-reveal">
				<a class="st-btn st-btn-primary" href="<?php echo $hero['btn1'][1]; ?>"><?php echo $hero['btn1'][0]; ?> <i class="fas fa-arrow-right"></i></a>
				<a class="st-btn st-btn-glass" href="<?php echo $hero['btn2'][1]; ?>"><?php echo $hero['btn2'][0]; ?></a>
			</div>
			<?php if (!empty($hero['counters'])) { ?>
			<div class="st-hero-stats st-reveal">
				<?php foreach (array_slice($hero['counters'], 0, 3) as $c) { ?>
				<div><b class="st-count" data-count="<?php echo $c['value']; ?>">0</b><small><?php echo $c['title']; ?></small></div>
				<?php } ?>
			</div>
			<?php } ?>
		</div>
		<div class="st-hero-visual" data-scene>
			<div class="st-stack">
				<span class="st-stack-ring" aria-hidden="true"></span>
				<?php if (empty($photos)) { ?>
				<figure class="st-card3d c1 st-card-empty"><i class="fas fa-school"></i></figure>
				<?php } foreach ($photos as $i => $src) { ?>
				<figure class="st-card3d c<?php echo $i + 1; ?>" data-depth="<?php echo 0.4 + $i * 0.35; ?>"><img src="<?php echo $src; ?>" alt=""></figure>
				<?php } ?>
				<div class="st-glass-badge b1" data-depth="1.4"><i class="fas fa-graduation-cap"></i><span><b>Learn. Grow. Lead.</b><small><?php echo html_escape($hero['school']); ?></small></span></div>
				<?php if (!empty($hero['phone'])) { ?>
				<div class="st-glass-badge b2" data-depth="1.1"><i class="fas fa-phone-alt"></i><span><b><?php echo html_escape($hero['phone']); ?></b><small>Talk to our office</small></span></div>
				<?php } ?>
			</div>
		</div>
	</div>
</section>
