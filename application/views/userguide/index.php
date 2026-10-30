<?php
// Guide Center: every guide this viewer can use, grouped, searchable, with the
// school's setup checklist on top for admins.
$audienceLabel = array('school' => 'your school team', 'teacher' => 'teachers', 'student' => 'students', 'parent' => 'parents', 'super' => 'platform admins');
$groups = array();
foreach ($guides as $g) {
	$groups[$g['group']][] = $g;
}
$doneCount = count(array_filter($checklist, function ($c) { return $c['done']; }));
$setupPct = $checklist ? (int) round($doneCount / count($checklist) * 100) : 0;
?>
<?php $this->load->view('dashboard/_d3_assets'); ?>
<link rel="stylesheet" href="<?php echo base_url('assets/css/guides.css?v=' . filemtime(FCPATH . 'assets/css/guides.css')); ?>">
<script src="<?php echo base_url('assets/js/guides.js?v=' . filemtime(FCPATH . 'assets/js/guides.js')); ?>"></script>

<div class="d3 gd gd-center">
	<section class="d3-hero d3-reveal">
		<div class="d3-hero-copy">
			<span class="d3-eyebrow"><i class="fas fa-life-ring"></i> Guide Center</span>
			<h1>Learn <span>SkoolOS</span>, one module at a time</h1>
			<p class="d3-hero-meta">
				<span><i class="fas fa-book-open"></i> <?php echo count($guides); ?> step-by-step guides</span>
				<span><i class="fas fa-user-check"></i> Written for <?php echo $audienceLabel[$audience]; ?></span>
			</p>
			<label class="gd-search">
				<span class="sr-only">Search the guides</span>
				<i class="fas fa-search"></i>
				<input type="search" id="gdSearch" placeholder="Search guides, e.g. report card, fees, homework" autocomplete="off">
				<kbd>/</kbd>
			</label>
		</div>
		<div class="d3-hero-art" aria-hidden="true">
			<div class="gd-book">
				<div class="gd-page is-done"><b><span>&#10003;</span> Create classes</b><i></i><i></i></div>
				<div class="gd-page is-done"><b><span>&#10003;</span> Admit students</b><i></i><i></i></div>
				<div class="gd-page"><b><span>3</span> Enter exam marks</b><i></i><i></i></div>
			</div>
		</div>
	</section>

	<?php if ($checklist) { ?>
	<section class="gd-setup d3-reveal" aria-label="School setup checklist">
		<div class="gd-setup-head">
			<div class="d3-ring tone-mint" style="--p: <?php echo $setupPct; ?>; width: 150px; height: 150px;">
				<svg viewBox="0 0 120 120"><circle class="trk" cx="60" cy="60" r="50"/><circle class="bar" cx="60" cy="60" r="50" pathLength="100"/></svg>
				<div class="d3-ring-val"><b data-count="<?php echo $setupPct; ?>" data-suffix="%">0%</b><small>set up</small></div>
			</div>
			<h2><?php echo $doneCount === count($checklist) ? 'Your school is ready' : 'Set up your school'; ?></h2>
			<p><?php echo $doneCount; ?> of <?php echo count($checklist); ?> done. Ticks update as you go.</p>
		</div>
		<ul class="gd-steps">
			<?php foreach ($checklist as $item) { ?>
			<li class="<?php echo $item['done'] ? 'is-done' : ''; ?>">
				<span class="gd-tick" aria-hidden="true"><i class="fas fa-check"></i></span>
				<span class="gd-what">
					<b><?php echo $item['label']; ?><span class="sr-only"><?php echo $item['done'] ? ' (done)' : ' (to do)'; ?></span></b>
					<small><?php echo $item['hint']; ?></small>
				</span>
				<span class="gd-links">
					<?php if ($item['guide']) { ?>
					<a class="gd-icon-btn" href="<?php echo base_url('userguide/view/' . $item['guide']); ?>" title="Show me how" aria-label="Show me how: <?php echo html_escape($item['label']); ?>"><i class="fas fa-book-open"></i></a>
					<?php } ?>
					<a class="gd-icon-btn" href="<?php echo base_url($item['url']); ?>" title="Open" aria-label="Open <?php echo html_escape($item['label']); ?>"><i class="fas fa-arrow-right"></i></a>
				</span>
			</li>
			<?php } ?>
		</ul>
	</section>
	<?php } ?>

	<?php if (count($groups) > 1) { ?>
	<div class="gd-filter" role="group" aria-label="Filter guides by topic">
		<button type="button" class="is-on" data-group="all" aria-pressed="true">All <span><?php echo count($guides); ?></span></button>
		<?php foreach ($groups as $name => $list) { ?>
		<button type="button" data-group="<?php echo html_escape($name); ?>" aria-pressed="false"><?php echo html_escape($name); ?> <span><?php echo count($list); ?></span></button>
		<?php } ?>
	</div>
	<?php } ?>

	<?php foreach ($groups as $name => $list) { $tone = guide_group_tone($name); ?>
	<section class="gd-group">
		<h2><?php echo html_escape($name); ?></h2>
		<div class="gd-cards">
			<?php foreach ($list as $i => $g) {
				$search = strtolower($g['title'] . ' ' . $g['group'] . ' ' . strip_tags($g['block']['summary']) . ' ' . implode(' ', array_map(function ($s) { return strip_tags($s[0] . ' ' . $s[1]); }, $g['block']['steps']))); ?>
			<a class="gd-card tone-<?php echo $tone; ?> d3-reveal" style="--i: <?php echo $i % 4; ?>" data-tilt="7"
				href="<?php echo base_url('userguide/view/' . $g['key']); ?>"
				data-group="<?php echo html_escape($g['group']); ?>"
				data-text="<?php echo html_escape($search); ?>"
				data-steps="<?php echo count($g['block']['steps']); ?>"
				data-store="skoolos-guide:<?php echo $audience . ':' . $g['key']; ?>">
				<span class="gd-card-icon"><i class="fas <?php echo $g['icon']; ?>"></i></span>
				<h3><?php echo html_escape($g['title']); ?></h3>
				<p><?php echo $g['block']['summary']; ?></p>
				<span class="gd-card-foot">
					<span><?php echo count($g['block']['steps']); ?> steps</span>
					<span class="gd-meter" aria-hidden="true"><i></i></span>
					<span class="gd-go">Open <i class="fas fa-arrow-right"></i></span>
				</span>
			</a>
			<?php } ?>
		</div>
	</section>
	<?php } ?>

	<div class="gd-empty" role="status"><i class="fas fa-search"></i> No guide matches that. Try a module name like "fees" or "attendance".</div>
</div>
