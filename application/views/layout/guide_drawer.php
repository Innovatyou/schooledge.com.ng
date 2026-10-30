<?php
// "Guide" button + slide-in drawer on every panel page. Shows the guide for the
// page being viewed (longest matching path), or "Getting started" when none fits.
if (isset($main_menu) && $main_menu === 'userguide') return;
$pageGuide = guide_for_uri($this->uri->uri_string());
if (!$pageGuide) {
	$all = module_guides();
	$pageGuide = isset($all['getting_started']) ? $all['getting_started'] : null;
}
if (!$pageGuide) return;
$gAudience = guide_audience();
$gTone = guide_group_tone($pageGuide['group']);
$gStore = 'skoolos-guide:' . $gAudience . ':' . $pageGuide['key'];
?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Sora:wght@700;800&display=swap">
<link rel="stylesheet" href="<?php echo base_url('assets/css/guides.css?v=' . filemtime(FCPATH . 'assets/css/guides.css')); ?>">
<button type="button" class="gd-fab" data-guide="<?php echo $pageGuide['key']; ?>" aria-haspopup="dialog" aria-expanded="false" aria-controls="gdDrawer" title="How this page works (press ?)">
	<span class="gd-fab-icon"><i class="fas fa-question"></i></span><span class="gd-fab-label">Guide</span>
</button>
<div class="gd-scrim" aria-hidden="true"></div>
<aside class="gd-drawer" id="gdDrawer" role="dialog" aria-modal="true" aria-labelledby="gdDrawerTitle" aria-hidden="true">
	<header class="gd-drawer-head">
		<div class="gd-drawer-top">
			<span class="gd-pill"><i class="fas fa-book-open"></i> Guide &middot; <?php echo html_escape($pageGuide['group']); ?></span>
			<button type="button" class="gd-close" aria-label="Close guide"><i class="fas fa-times"></i></button>
		</div>
		<div class="gd-drawer-title">
			<span class="gd-card-icon tone-<?php echo $gTone; ?>"><i class="fas <?php echo $pageGuide['icon']; ?>"></i></span>
			<h2 id="gdDrawerTitle"><?php echo html_escape($pageGuide['title']); ?></h2>
		</div>
		<p><?php echo $pageGuide['block']['summary']; ?></p>
	</header>
	<div class="gd-drawer-body">
		<?php $this->load->view('userguide/_steps', array('guide' => $pageGuide, 'storeKey' => $gStore)); ?>
		<?php if (!empty($pageGuide['block']['tips'])) { ?>
		<h3>Good to know</h3>
		<ul class="gd-tips">
			<?php foreach ($pageGuide['block']['tips'] as $tip) { ?><li><?php echo $tip; ?></li><?php } ?>
		</ul>
		<?php } ?>
		<?php if (!empty($pageGuide['block']['faq'])) { ?>
		<h3>Common questions</h3>
		<div class="gd-faq">
			<?php foreach ($pageGuide['block']['faq'] as $qa) { ?>
			<details><summary><?php echo $qa[0]; ?></summary><p><?php echo $qa[1]; ?></p></details>
			<?php } ?>
		</div>
		<?php } ?>
	</div>
	<footer class="gd-drawer-foot">
		<a class="gd-btn" href="<?php echo base_url('userguide'); ?>"><i class="fas fa-th-large"></i> All guides</a>
		<a class="gd-btn is-primary" href="<?php echo base_url('userguide/view/' . $pageGuide['key']); ?>"><i class="fas fa-expand-alt"></i> Open full guide</a>
	</footer>
</aside>
<script src="<?php echo base_url('assets/js/guides.js?v=' . filemtime(FCPATH . 'assets/js/guides.js')); ?>"></script>
