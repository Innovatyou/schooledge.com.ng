<?php
// Campus Light: bright hero with a large 3D-tilted photo and layered cards floating in front of it
$photos = $hero['photos'];
?>
<section class="st-hero st-hero-campus">
	<div class="st-dots" aria-hidden="true"></div>
	<div class="container st-hero-grid">
		<div class="st-hero-copy">
			<span class="st-chip st-reveal"><i class="fas fa-star"></i> <?php echo $hero['admission'] ? 'Now enrolling for the new session' : html_escape($hero['school']); ?></span>
			<h1 class="st-reveal"><?php echo $hero['title']; ?></h1>
			<p class="st-reveal"><?php echo $hero['text']; ?></p>
			<div class="st-actions st-reveal">
				<a class="st-btn st-btn-primary" href="<?php echo $hero['btn1'][1]; ?>"><?php echo $hero['btn1'][0]; ?> <i class="fas fa-arrow-right"></i></a>
				<a class="st-btn st-btn-ghost" href="<?php echo $hero['btn2'][1]; ?>"><?php echo $hero['btn2'][0]; ?></a>
			</div>
			<ul class="st-ticks st-reveal">
				<li><i class="fas fa-check"></i> Qualified, caring teachers</li>
				<li><i class="fas fa-check"></i> Safe and modern campus</li>
				<li><i class="fas fa-check"></i> Results parents can follow online</li>
			</ul>
		</div>
		<div class="st-hero-visual" data-scene>
			<div class="st-layers">
				<figure class="st-layer main" data-depth="0.5">
					<?php if (!empty($photos[0])) { ?><img src="<?php echo $photos[0]; ?>" alt=""><?php } else { ?><i class="fas fa-school"></i><?php } ?>
				</figure>
				<?php if (!empty($photos[1])) { ?>
				<figure class="st-layer side" data-depth="1.2"><img src="<?php echo $photos[1]; ?>" alt=""></figure>
				<?php } ?>
				<div class="st-layer-card c1" data-depth="1.6">
					<span class="st-mini-icon"><i class="fas fa-user-graduate"></i></span>
					<span><b><?php echo !empty($hero['counters']) ? '<span class="st-count" data-count="' . $hero['counters'][0]['value'] . '">0</span>+' : 'Admissions'; ?></b><small><?php echo !empty($hero['counters']) ? $hero['counters'][0]['title'] : 'Open all year'; ?></small></span>
				</div>
				<div class="st-layer-card c2" data-depth="1.9">
					<span class="st-rating"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></span>
					<small>Loved by parents</small>
				</div>
			</div>
		</div>
	</div>
</section>
