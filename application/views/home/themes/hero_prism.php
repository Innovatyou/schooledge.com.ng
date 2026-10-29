<?php
// Prism Kids: centred, colourful hero over a slowly turning 3D carousel of photos
$photos = $hero['photos'];
// the carousel needs six faces; repeat the available photos to fill it
$faces = array();
for ($i = 0; $i < 6; $i++) {
	$faces[] = !empty($photos) ? $photos[$i % count($photos)] : '';
}
$faceIcons = array('fa-book-open', 'fa-palette', 'fa-futbol', 'fa-music', 'fa-flask', 'fa-puzzle-piece');
?>
<section class="st-hero st-hero-prism">
	<div class="st-blobs" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
	<div class="st-bubbles" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
	<div class="container">
		<div class="st-hero-copy st-center">
			<span class="st-chip st-reveal"><i class="fas fa-heart"></i> <?php echo html_escape($hero['school']); ?></span>
			<h1 class="st-reveal"><?php echo $hero['title']; ?></h1>
			<p class="st-reveal"><?php echo $hero['text']; ?></p>
			<div class="st-actions st-reveal">
				<a class="st-btn st-btn-primary" href="<?php echo $hero['btn1'][1]; ?>"><?php echo $hero['btn1'][0]; ?> <i class="fas fa-arrow-right"></i></a>
				<a class="st-btn st-btn-ghost" href="<?php echo $hero['btn2'][1]; ?>"><?php echo $hero['btn2'][0]; ?></a>
			</div>
		</div>
		<div class="st-carousel-stage st-reveal" aria-hidden="true">
			<div class="st-carousel">
				<?php foreach ($faces as $i => $src) { ?>
				<figure class="st-face" style="--n:<?php echo $i; ?>">
					<?php if ($src) { ?><img src="<?php echo $src; ?>" alt=""><?php } ?>
					<span class="st-face-icon"><i class="fas <?php echo $faceIcons[$i]; ?>"></i></span>
				</figure>
				<?php } ?>
			</div>
		</div>
	</div>
	<svg class="st-wave" viewBox="0 0 1440 90" preserveAspectRatio="none" aria-hidden="true"><path d="M0 40c160 40 320 50 480 30S800 0 960 10s320 60 480 40v40H0z"/></svg>
</section>
