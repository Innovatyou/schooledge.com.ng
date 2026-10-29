<?php
// Heritage: navy and gold, serif type, the lead photo framed in layered 3D arches
$photos = $hero['photos'];
?>
<section class="st-hero st-hero-heritage">
	<div class="st-pattern" aria-hidden="true"></div>
	<div class="container st-hero-grid">
		<div class="st-hero-copy">
			<span class="st-chip st-reveal"><?php echo html_escape($hero['school']); ?></span>
			<h1 class="st-reveal"><?php echo $hero['title']; ?></h1>
			<span class="st-rule st-reveal" aria-hidden="true"><i></i></span>
			<p class="st-reveal"><?php echo $hero['text']; ?></p>
			<div class="st-actions st-reveal">
				<a class="st-btn st-btn-primary" href="<?php echo $hero['btn1'][1]; ?>"><?php echo $hero['btn1'][0]; ?></a>
				<a class="st-btn st-btn-outline" href="<?php echo $hero['btn2'][1]; ?>"><?php echo $hero['btn2'][0]; ?></a>
			</div>
			<p class="st-motto st-reveal">Excellence &middot; Character &middot; Service</p>
		</div>
		<div class="st-hero-visual" data-scene>
			<div class="st-arches">
				<span class="st-arch a3" data-depth="0.3"></span>
				<span class="st-arch a2" data-depth="0.6"></span>
				<figure class="st-arch a1" data-depth="1">
					<?php if (!empty($photos[0])) { ?><img src="<?php echo $photos[0]; ?>" alt=""><?php } else { ?><i class="fas fa-landmark"></i><?php } ?>
				</figure>
				<div class="st-seal" data-depth="1.5" aria-hidden="true">
					<svg viewBox="0 0 120 120"><defs><path id="stSealPath" d="M60 60m-44 0a44 44 0 1 1 88 0a44 44 0 1 1-88 0"/></defs><text><textPath href="#stSealPath">EXCELLENCE &#8226; CHARACTER &#8226; SERVICE &#8226; </textPath></text></svg>
					<i class="fas fa-crown"></i>
				</div>
			</div>
		</div>
	</div>
</section>
