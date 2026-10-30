<?php
/**
 * Home page for the 3D website templates (aurora / campus / prism / heritage).
 * One section-based page: the hero comes from home/themes/hero_<theme>, and every
 * section below is shared markup that site-themes.css styles per template. Sections
 * only render when the school has content for them, mirroring the classic page.
 */
$alias = $cms_setting['url_alias'];
$schoolName = $cms_setting['application_title'];
$siteUrl = function ($path = '') use ($alias) {
	return base_url($alias . ($path === '' ? '' : '/' . $path));
};

// hero content: first active slider, with sensible fallbacks
$slides = array();
foreach ((array) $sliders as $slider) {
	$el = !empty($slider['elements']) ? json_decode($slider['elements'], true) : array();
	$slides[] = array(
		'title' => $slider['title'],
		'text' => strip_tags($slider['description']),
		'image' => !empty($el['image']) ? base_url('uploads/frontend/slider/' . $el['image']) : '',
		'btn1' => !empty($el['button_text1']) ? array($el['button_text1'], $el['button_url1']) : null,
		'btn2' => !empty($el['button_text2']) ? array($el['button_text2'], $el['button_url2']) : null,
	);
}
$lead = !empty($slides) ? $slides[0] : array('title' => '', 'text' => '', 'image' => '', 'btn1' => null, 'btn2' => null);
$admissionOpen = !empty($cms_setting['online_admission']);

// up to three photos for the 3D hero visuals: slider images, then the welcome image, then gallery
$heroPhotos = array();
foreach ($slides as $s) {
	if ($s['image']) $heroPhotos[] = $s['image'];
}
$welcomeEl = !empty($wellcome['elements']) ? json_decode($wellcome['elements'], true) : array();
$welcomeImage = !empty($welcomeEl['image']) ? base_url('uploads/frontend/home_page/' . $welcomeEl['image'] . img_reload()) : '';
if ($welcomeImage) $heroPhotos[] = $welcomeImage;
foreach ((array) $home_gallery as $g) {
	$heroPhotos[] = $this->gallery_model->get_image_url($g['thumb_image']);
}
$heroPhotos = array_slice(array_values(array_unique($heroPhotos)), 0, 3);

// live counters from the statistics section (also used as hero badges)
$counters = array();
if (!empty($statistics)) {
	$statEl = json_decode($statistics['elements'], true);
	for ($i = 1; $i < 5; $i++) {
		if (empty($statEl['type_' . $i])) continue;
		$counters[] = array(
			'title' => $statEl['widget_title_' . $i],
			'icon' => $statEl['widget_icon_' . $i],
			'value' => (int) $this->home_model->getStatisticsCounter($statEl['type_' . $i], $branchID),
		);
	}
}

$hero = array(
	'school' => $schoolName,
	'title' => $lead['title'] !== '' ? $lead['title'] : 'Welcome to ' . $schoolName,
	'text' => $lead['text'] !== '' ? $lead['text'] : strip_tags($cms_setting['footer_about_text']),
	'btn1' => $lead['btn1'] ? $lead['btn1'] : ($admissionOpen ? array('Apply for admission', $siteUrl('admission')) : array('Contact us', $siteUrl('contact'))),
	'btn2' => $lead['btn2'] ? $lead['btn2'] : array('About our school', $siteUrl('about')),
	'photos' => $heroPhotos,
	'counters' => website_section_on($cms_setting, 'hero_stats') ? $counters : array(),
	'admission' => $admissionOpen,
	'phone' => $cms_setting['mobile_no'],
	'url' => $siteUrl,
);
?>
<main class="st-home">
	<?php $this->load->view('home/themes/hero_' . $site_theme, array('hero' => $hero)); ?>

	<?php if (!empty($features)) { ?>
	<section class="st-section st-highlights">
		<div class="container">
			<div class="st-head st-reveal">
				<span class="st-kicker">Why families choose us</span>
				<h2>Learning that goes <em>beyond the classroom</em></h2>
			</div>
			<div class="st-feature-grid">
				<?php foreach (array_slice($features, 0, 4) as $i => $value) {
					$el = json_decode($value['elements'], true); ?>
				<article class="st-feature st-tilt st-reveal" style="--i:<?php echo $i; ?>">
					<span class="st-feature-icon"><i class="<?php echo html_escape($el['icon']); ?>"></i></span>
					<h3><?php echo $value['title']; ?></h3>
					<p><?php echo strip_tags($value['description']); ?></p>
					<?php if (!empty($el['button_text'])) { ?>
					<a href="<?php echo $el['button_url']; ?>"><?php echo $el['button_text']; ?> <i class="fas fa-arrow-right"></i></a>
					<?php } ?>
				</article>
				<?php } ?>
			</div>
		</div>
	</section>
	<?php } ?>

	<?php if (!empty($wellcome)) { ?>
	<section class="st-section st-about">
		<div class="container st-about-grid">
			<div class="st-about-visual st-reveal">
				<div class="st-frame st-tilt">
					<?php if ($welcomeImage) { ?><img src="<?php echo $welcomeImage; ?>" alt="<?php echo html_escape($schoolName); ?>"><?php } ?>
				</div>
				<div class="st-frame-accent" aria-hidden="true"></div>
				<?php if (!empty($counters)) { ?>
				<div class="st-float-card">
					<i class="<?php echo html_escape($counters[0]['icon']); ?>"></i>
					<span><b class="st-count" data-count="<?php echo $counters[0]['value']; ?>">0</b><small><?php echo $counters[0]['title']; ?></small></span>
				</div>
				<?php } ?>
			</div>
			<div class="st-about-copy st-reveal">
				<span class="st-kicker"><?php echo !empty($wellcome['subtitle']) ? $wellcome['subtitle'] : 'About us'; ?></span>
				<h2><?php echo $wellcome['title']; ?></h2>
				<div class="st-richtext"><?php echo nl2br($wellcome['description']); ?></div>
				<a class="st-btn st-btn-primary" href="<?php echo $siteUrl('about'); ?>">Discover our story <i class="fas fa-arrow-right"></i></a>
			</div>
		</div>
	</section>
	<?php } ?>

	<?php if (!empty($counters) && website_section_on($cms_setting, 'statistics')) { ?>
	<section class="st-stats">
		<div class="container">
			<div class="st-stats-grid">
				<?php foreach ($counters as $i => $c) { ?>
				<div class="st-stat st-reveal" style="--i:<?php echo $i; ?>">
					<i class="<?php echo html_escape($c['icon']); ?>"></i>
					<strong><span class="st-count" data-count="<?php echo $c['value']; ?>">0</span>+</strong>
					<small><?php echo $c['title']; ?></small>
				</div>
				<?php } ?>
			</div>
		</div>
	</section>
	<?php } ?>

	<?php if (!empty($services)) {
		$this->db->where('branch_id', $branchID);
		$serviceList = $this->db->get('front_cms_services_list')->result_array();
		if (!empty($serviceList)) { ?>
	<section class="st-section st-why">
		<div class="container">
			<div class="st-head st-center st-reveal">
				<span class="st-kicker">Our programmes</span>
				<h2><?php echo $services['title']; ?></h2>
				<p><?php echo strip_tags($services['description']); ?></p>
			</div>
			<div class="st-why-grid">
				<?php foreach ($serviceList as $i => $value) { ?>
				<div class="st-why-item st-tilt st-reveal" style="--i:<?php echo $i % 3; ?>">
					<span><i class="<?php echo html_escape($value['icon']); ?>"></i></span>
					<div><h4><?php echo $value['title']; ?></h4><p><?php echo $value['description']; ?></p></div>
				</div>
				<?php } ?>
			</div>
		</div>
	</section>
	<?php } } ?>

	<?php if (!empty($teachers) && !empty($home_teachers)) { ?>
	<section class="st-section st-team">
		<div class="container">
			<div class="st-head st-split st-reveal">
				<div>
					<span class="st-kicker">Meet the team</span>
					<h2><?php echo $teachers['title']; ?></h2>
				</div>
				<a class="st-btn st-btn-ghost" href="<?php echo $siteUrl('teachers'); ?>">All teachers <i class="fas fa-arrow-right"></i></a>
			</div>
			<div class="st-team-grid">
				<?php foreach ($home_teachers as $i => $t) { ?>
				<article class="st-teacher st-tilt st-reveal" style="--i:<?php echo $i; ?>">
					<div class="st-teacher-photo">
						<?php if (!empty($t['photo']) && is_file(FCPATH . 'uploads/images/staff/' . $t['photo'])) { ?>
						<img src="<?php echo get_image_url('staff', $t['photo']); ?>" alt="<?php echo html_escape($t['name']); ?>">
						<?php } else {
							// no photo yet: a coloured initials avatar instead of the grey placeholder
							$parts = preg_split('/\s+/', trim($t['name']));
							$initials = strtoupper(substr($parts[0], 0, 1) . (count($parts) > 1 ? substr(end($parts), 0, 1) : '')); ?>
						<span class="st-initials" style="--h:<?php echo (crc32($t['name']) % 360); ?>"><?php echo html_escape($initials); ?></span>
						<?php } ?>
					</div>
					<h4><?php echo $t['name']; ?></h4>
					<small><?php echo !empty($t['designation_name']) ? $t['designation_name'] : $t['department_name']; ?></small>
				</article>
				<?php } ?>
			</div>
		</div>
	</section>
	<?php } ?>

	<?php if (!empty($home_news) || !empty($home_events)) { ?>
	<section class="st-section st-updates">
		<div class="container">
			<div class="st-head st-split st-reveal">
				<div>
					<span class="st-kicker">What's happening</span>
					<h2>News &amp; upcoming events</h2>
				</div>
				<?php if (!empty($home_news)) { ?><a class="st-btn st-btn-ghost" href="<?php echo $siteUrl('news'); ?>">All news <i class="fas fa-arrow-right"></i></a><?php } ?>
			</div>
			<div class="st-updates-grid">
				<?php // with the events panel taking a column, two news cards keep it to one row
				foreach (array_slice((array) $home_news, 0, empty($home_events) ? 3 : 2) as $i => $n) { ?>
				<a class="st-news st-tilt st-reveal" style="--i:<?php echo $i; ?>" href="<?php echo $siteUrl('news_view/' . $n['alias']); ?>">
					<span class="st-news-img"><img src="<?php echo base_url('uploads/frontend/news/' . $n['image']); ?>" alt=""></span>
					<span class="st-news-body">
						<small><i class="far fa-calendar"></i> <?php echo _d($n['date']); ?></small>
						<b><?php echo $n['title']; ?></b>
					</span>
				</a>
				<?php } ?>
				<?php if (!empty($home_events)) { ?>
				<div class="st-events st-reveal">
					<h4><i class="far fa-calendar-check"></i> Upcoming events</h4>
					<?php foreach ($home_events as $e) {
						$start = strtotime($e['start_date']); ?>
					<a class="st-event" href="<?php echo $siteUrl('event_view/' . $e['id']); ?>">
						<span class="st-event-date"><b><?php echo date('d', $start); ?></b><small><?php echo date('M', $start); ?></small></span>
						<span><b><?php echo $e['title']; ?></b><small><?php echo _d($e['start_date']); ?><?php echo $e['end_date'] != $e['start_date'] ? ' - ' . _d($e['end_date']) : ''; ?></small></span>
					</a>
					<?php } ?>
				</div>
				<?php } ?>
			</div>
		</div>
	</section>
	<?php } ?>

	<?php if (!empty($home_gallery)) { ?>
	<section class="st-section st-gallery">
		<div class="container">
			<div class="st-head st-center st-reveal">
				<span class="st-kicker">Life at <?php echo $schoolName; ?></span>
				<h2>Moments from our school</h2>
			</div>
			<div class="st-gallery-stage st-reveal">
				<div class="st-gallery-grid">
					<?php foreach ($home_gallery as $i => $g) { ?>
					<a class="st-shot" href="<?php echo $siteUrl('gallery_view/' . $g['alias']); ?>" style="--i:<?php echo $i; ?>">
						<img src="<?php echo $this->gallery_model->get_image_url($g['thumb_image']); ?>" alt="<?php echo html_escape($g['title']); ?>">
						<span><?php echo $g['title']; ?></span>
					</a>
					<?php } ?>
				</div>
			</div>
		</div>
	</section>
	<?php } ?>

	<?php if (!empty($testimonial)) {
		$this->db->where('branch_id', $branchID);
		$voices = $this->db->get('front_cms_testimonial')->result_array();
		if (!empty($voices)) { ?>
	<section class="st-section st-voices">
		<div class="container">
			<div class="st-head st-center st-reveal">
				<span class="st-kicker">Parents &amp; students</span>
				<h2><?php echo $testimonial['title']; ?></h2>
				<p><?php echo strip_tags($testimonial['description']); ?></p>
			</div>
			<div class="st-voices-grid">
				<?php foreach (array_slice($voices, 0, 3) as $i => $v) { ?>
				<article class="st-voice st-tilt st-reveal" style="--i:<?php echo $i; ?>">
					<div class="st-stars"><?php for ($s = 1; $s < 6; $s++) echo '<i class="' . ($s <= $v['rank'] ? 'fas' : 'far') . ' fa-star"></i>'; ?></div>
					<blockquote><?php echo strip_tags($v['description']); ?></blockquote>
					<div class="st-person">
						<img src="<?php echo $this->testimonial_model->get_image_url($v['image']); ?>" alt="">
						<span><b><?php echo $v['name']; ?></b><small><?php echo $v['surname']; ?></small></span>
					</div>
				</article>
				<?php } ?>
			</div>
		</div>
	</section>
	<?php } } ?>

	<?php if (website_section_on($cms_setting, 'cta')) {
	$ctaEl = !empty($cta_box['elements']) ? json_decode($cta_box['elements'], true) : array(); ?>
	<section class="st-cta">
		<div class="container">
			<div class="st-cta-card st-reveal">
				<div class="st-cta-shapes" aria-hidden="true"><i></i><i></i><i></i></div>
				<div>
					<h2><?php echo !empty($cta_box['title']) ? $cta_box['title'] : 'Give your child the best start'; ?></h2>
					<p>
						<?php if (!empty($ctaEl['mobile_no'])) { ?><i class="fas fa-phone-alt"></i> Call us on <b><?php echo $ctaEl['mobile_no']; ?></b> or visit the school.<?php } else { ?>Speak to our admissions team or visit the school.<?php } ?>
					</p>
				</div>
				<a class="st-btn st-btn-light" href="<?php echo !empty($ctaEl['button_url']) ? $ctaEl['button_url'] : $hero['btn1'][1]; ?>"><?php echo !empty($ctaEl['button_text']) ? $ctaEl['button_text'] : $hero['btn1'][0]; ?> <i class="fas fa-arrow-right"></i></a>
			</div>
		</div>
	</section>
	<?php } ?>
</main>
