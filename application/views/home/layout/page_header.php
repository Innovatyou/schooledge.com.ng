<?php
/**
 * Shared modern page header for every "classic" inner page - replaces the
 * .main-banner + .breadcrumb block that used to be hand-duplicated across
 * about.php/contact.php/faq.php/teachers.php/page.php/etc (see
 * home/about.php's original markup for the pattern this supersedes).
 *
 * Expects $page_data['page_title'] (already set by every Home.php method
 * that renders through these pages). Optional $breadcrumb_items - an
 * array of ['label' => string, 'url' => string|null] - for a multi-level
 * trail; when omitted, the breadcrumb is just Home > the page title.
 */
$breadcrumbItems = isset($breadcrumb_items) ? $breadcrumb_items : array();
?>
<section class="se-page-header se-aurora">
	<div class="se-orb se-orb-a"></div>
	<div class="se-orb se-orb-b"></div>
	<div class="container px-md-0">
		<h1><?php echo $page_data['page_title']; ?></h1>
		<nav class="se-breadcrumb" aria-label="Breadcrumb">
			<a href="<?php echo base_url($cms_setting['url_alias']); ?>">Home</a>
			<?php if (empty($breadcrumbItems)): ?>
				<span class="se-crumb-sep">/</span>
				<span><?php echo $page_data['page_title']; ?></span>
			<?php else: ?>
				<?php foreach ($breadcrumbItems as $crumb): ?>
					<span class="se-crumb-sep">/</span>
					<?php if (!empty($crumb['url'])): ?>
						<a href="<?php echo $crumb['url']; ?>"><?php echo $crumb['label']; ?></a>
					<?php else: ?>
						<span><?php echo $crumb['label']; ?></span>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</nav>
	</div>
</section>
