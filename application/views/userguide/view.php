<?php
// One guide: steps to tick off, tips, common questions and related guides.
$block = $guide['block'];
$tone = guide_group_tone($guide['group']);
$storeKey = 'skoolos-guide:' . $audience . ':' . $guide['key'];
$firstUrl = '';
foreach ($block['steps'] as $s) {
	if (!empty($s[2])) { $firstUrl = $s[2]; break; }
}
$related = array_filter($guides, function ($g) use ($guide) { return $g['group'] === $guide['group'] && $g['key'] !== $guide['key']; });
?>
<?php $this->load->view('dashboard/_d3_assets'); ?>
<link rel="stylesheet" href="<?php echo base_url('assets/css/guides.css?v=' . filemtime(FCPATH . 'assets/css/guides.css')); ?>">
<script src="<?php echo base_url('assets/js/guides.js?v=' . filemtime(FCPATH . 'assets/js/guides.js')); ?>"></script>

<div class="d3 gd">
	<a class="gd-back" href="<?php echo base_url('userguide'); ?>"><i class="fas fa-arrow-left"></i> All guides</a>

	<section class="d3-hero d3-reveal tone-<?php echo $tone; ?>" style="min-height: 0;">
		<div class="d3-hero-copy">
			<span class="d3-eyebrow"><i class="fas fa-book-open"></i> <?php echo html_escape($guide['group']); ?> &middot; <?php echo count($block['steps']); ?> steps</span>
			<div class="d3-hero-id">
				<span class="gd-card-icon" style="width: 64px; height: 64px; border-radius: 20px; font-size: 24px; margin: 0;"><i class="fas <?php echo $guide['icon']; ?>"></i></span>
				<h1 style="margin: 0;"><?php echo html_escape($guide['title']); ?></h1>
			</div>
			<p class="d3-hero-meta" style="margin-top: 14px; max-width: 720px; font-size: 15px; line-height: 1.6;"><?php echo $block['summary']; ?></p>
			<?php if ($firstUrl) { ?>
			<div class="d3-actions">
				<a class="d3-btn is-primary" href="<?php echo base_url($firstUrl); ?>"><i class="fas fa-play"></i> Start with step 1</a>
			</div>
			<?php } ?>
		</div>
	</section>

	<div class="gd-layout">
		<section class="gd-panel d3-reveal">
			<h3><i class="fas fa-list-ol"></i> Step by step</h3>
			<?php $this->load->view('userguide/_steps', array('guide' => $guide, 'storeKey' => $storeKey)); ?>
		</section>

		<aside>
			<?php if (!empty($block['tips'])) { ?>
			<section class="gd-panel d3-reveal" style="--i: 1">
				<h3><i class="fas fa-lightbulb"></i> Good to know</h3>
				<ul class="gd-tips">
					<?php foreach ($block['tips'] as $tip) { ?><li><?php echo $tip; ?></li><?php } ?>
				</ul>
			</section>
			<?php } ?>
			<?php if (!empty($block['faq'])) { ?>
			<section class="gd-panel gd-faq d3-reveal" style="--i: 2">
				<h3><i class="fas fa-question-circle"></i> Common questions</h3>
				<?php foreach ($block['faq'] as $qa) { ?>
				<details><summary><?php echo $qa[0]; ?></summary><p><?php echo $qa[1]; ?></p></details>
				<?php } ?>
			</section>
			<?php } ?>
			<?php if ($related) { ?>
			<section class="gd-panel d3-reveal" style="--i: 3">
				<h3><i class="fas fa-layer-group"></i> More in <?php echo html_escape($guide['group']); ?></h3>
				<ul class="gd-related">
					<?php foreach ($related as $r) { ?>
					<li class="tone-<?php echo $tone; ?>"><a href="<?php echo base_url('userguide/view/' . $r['key']); ?>"><span><i class="fas <?php echo $r['icon']; ?>"></i></span> <?php echo html_escape($r['title']); ?></a></li>
					<?php } ?>
				</ul>
			</section>
			<?php } ?>
		</aside>
	</div>
</div>
