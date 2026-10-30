<?php
// Get the SkoolOS app: store badges, scan-to-install QR codes and, for the
// platform owner, where the store links are set.
$hasPlay = $play_store_url !== '';
$hasIos = $app_store_url !== '';
$anyLink = $hasPlay || $hasIos || $apk_available;
$schoolName = get_type_name_by_id('branch', get_loggedin_branch_id(), 'school_name') ?: $global_config['institute_name'];
?>
<?php $this->load->view('dashboard/_d3_assets'); ?>
<style type="text/css">
	.ma-badges { display: flex; flex-wrap: wrap; gap: 12px; }
	.ma-badge { display: inline-flex; align-items: center; gap: 12px; min-width: 186px; padding: 10px 18px 10px 14px; border-radius: 14px; background: #05070f; border: 1px solid rgba(255, 255, 255, .25); color: #fff !important; text-decoration: none !important; box-shadow: 0 14px 28px rgba(0, 0, 0, .35), inset 0 1px 0 rgba(255, 255, 255, .15); transition: transform .25s, box-shadow .25s; }
	.ma-badge:hover { transform: translateY(-3px); box-shadow: 0 20px 36px rgba(0, 0, 0, .45); }
	.ma-badge i { font-size: 26px; width: 28px; text-align: center; }
	.ma-badge .fa-google-play { background: linear-gradient(135deg, #00d7fe, #00f076 40%, #ffcf00 70%, #ff3a44); -webkit-background-clip: text; background-clip: text; color: transparent; }
	.ma-badge .fa-android { color: #3ddc84; }
	.ma-badge small { display: block; font-size: 10.5px; letter-spacing: .04em; text-transform: uppercase; opacity: .8; }
	.ma-badge b { display: block; font: 700 18px/1.1 Sora, system-ui, sans-serif; }
	.ma-badge.is-off { opacity: .45; pointer-events: none; }

	/* the 3D phone */
	.ma-phone-wrap { position: relative; height: 330px; perspective: 1100px; }
	.ma-phone { position: absolute; left: 50%; top: 4px; width: 168px; height: 330px; margin-left: -84px; padding: 10px; border-radius: 34px; background: linear-gradient(145deg, #2a2f52, #0c1028); box-shadow: 0 40px 70px rgba(0, 0, 0, .5), inset 0 0 0 2px rgba(255, 255, 255, .12); transform: rotateY(calc(-22deg + var(--mx, 0) * 24deg)) rotateX(calc(10deg + var(--my, 0) * -14deg)); transform-style: preserve-3d; transition: transform .6s cubic-bezier(.2, .8, .2, 1); }
	.ma-screen { position: relative; height: 100%; overflow: hidden; border-radius: 26px; background: linear-gradient(180deg, #f4f5fb, #e9ecf7); }
	.ma-screen::before { content: ""; position: absolute; top: 7px; left: 50%; width: 52px; height: 14px; margin-left: -26px; border-radius: 10px; background: #0c1028; }
	.ma-app-head { height: 92px; padding: 30px 12px 0; background: linear-gradient(135deg, #7c5cff, #4f8cff); color: #fff; font: 700 11px/1.3 'Plus Jakarta Sans', sans-serif; }
	.ma-app-head b { display: block; font: 800 14px Sora, sans-serif; }
	.ma-tile { margin: 9px 10px 0; padding: 9px 10px; border-radius: 12px; background: #fff; box-shadow: 0 6px 14px rgba(20, 24, 60, .08); font: 700 9.5px 'Plus Jakarta Sans', sans-serif; color: #1d2440; display: flex; align-items: center; gap: 8px; }
	.ma-tile span { flex: 0 0 22px; height: 22px; border-radius: 7px; background: linear-gradient(135deg, var(--tone-2), var(--tone)); }
	.ma-pop { position: absolute; display: flex; align-items: center; gap: 9px; padding: 10px 13px; border-radius: 14px; background: linear-gradient(150deg, rgba(255, 255, 255, .22), rgba(255, 255, 255, .08)); border: 1px solid rgba(255, 255, 255, .28); backdrop-filter: blur(12px); color: #fff; font-size: 12px; font-weight: 700; box-shadow: 0 18px 34px rgba(0, 0, 0, .3); animation: d3Bob 6s ease-in-out infinite; }
	.ma-pop i { color: #fbbf24; }
	.ma-pop.p1 { left: 2%; top: 46px; }
	.ma-pop.p2 { right: 0; top: 170px; animation-delay: -3s; }
	.ma-pop.p2 i { color: #34d399; }

	/* download cards */
	.ma-qr { display: grid; place-items: center; width: 150px; height: 150px; margin: 0 auto 16px; padding: 10px; border-radius: 18px; background: #fff; box-shadow: 0 12px 26px rgba(20, 24, 60, .12); }
	.ma-qr img { width: 100%; height: 100%; image-rendering: pixelated; }
	.ma-qr.is-empty { color: #a4a9b8; font-size: 30px; background: var(--d3-soft); box-shadow: none; }
	.ma-card-body { text-align: center; }
	.ma-card-body p { color: var(--d3-muted); font-size: 13px; line-height: 1.55; }
	.ma-copy { margin-top: 10px; border: 0; background: none; color: var(--violet); font-weight: 700; font-size: 12.5px; cursor: pointer; }
	.ma-form label { font-weight: 700; }
	@media (max-width: 991px) { .ma-phone-wrap { height: 300px; } }
	@media (prefers-reduced-motion: reduce) { .ma-phone { transform: rotateY(-18deg) rotateX(8deg); } .ma-pop { animation: none; } }
</style>

<div class="d3">
	<section class="d3-hero d3-reveal">
		<div class="d3-hero-copy">
			<span class="d3-eyebrow"><i class="fas fa-mobile-alt"></i> <?php echo translate('mobile_app'); ?></span>
			<h1><?php echo html_escape($schoolName); ?>, <span>in your pocket</span></h1>
			<p class="d3-hero-meta" style="max-width: 560px; font-size: 14.5px; line-height: 1.6;">
				Attendance, results, homework, fees and messages on your phone, with alerts the moment something changes. Sign in with the same username and password you use here.
			</p>
			<div class="ma-badges">
				<a class="ma-badge<?php echo $hasPlay ? '' : ' is-off'; ?>" href="<?php echo $hasPlay ? html_escape($play_store_url) : '#'; ?>" target="_blank" rel="noopener"<?php echo $hasPlay ? '' : ' aria-disabled="true" tabindex="-1"'; ?>>
					<i class="fab fa-google-play"></i><span><small><?php echo $hasPlay ? 'Get it on' : 'Coming soon to'; ?></small><b>Google Play</b></span>
				</a>
				<a class="ma-badge<?php echo $hasIos ? '' : ' is-off'; ?>" href="<?php echo $hasIos ? html_escape($app_store_url) : '#'; ?>" target="_blank" rel="noopener"<?php echo $hasIos ? '' : ' aria-disabled="true" tabindex="-1"'; ?>>
					<i class="fab fa-apple"></i><span><small><?php echo $hasIos ? 'Download on the' : 'Coming soon to the'; ?></small><b>App Store</b></span>
				</a>
				<?php if ($apk_available) { ?>
				<a class="ma-badge" href="<?php echo $apk_url; ?>" download>
					<i class="fab fa-android"></i><span><small>Direct download &middot; <?php echo $apk_size; ?> MB</small><b>Android APK</b></span>
				</a>
				<?php } ?>
			</div>
		</div>
		<div class="ma-phone-wrap" aria-hidden="true">
			<span class="ma-pop p1"><i class="fas fa-award"></i> Results published</span>
			<div class="ma-phone">
				<div class="ma-screen">
					<div class="ma-app-head">Good morning<b>SkoolOS</b></div>
					<div class="ma-tile tone-mint"><span></span> Present today</div>
					<div class="ma-tile tone-violet"><span></span> Report card ready</div>
					<div class="ma-tile tone-amber"><span></span> 2 homework due</div>
					<div class="ma-tile tone-rose"><span></span> Fees balance paid</div>
					<div class="ma-tile tone-cyan"><span></span> Sports day on Friday</div>
				</div>
			</div>
			<span class="ma-pop p2"><i class="fas fa-check-circle"></i> Fee payment received</span>
		</div>
	</section>

	<div class="d3-grid">
		<?php
		$cards = array(
			array('Google Play', 'fab fa-google-play', 'tone-mint', $play_store_url, $qr['play'], 'Scan with an Android phone to open the Play Store listing.', 'Not on Google Play yet.'),
			array('App Store', 'fab fa-apple', 'tone-slate', $app_store_url, $qr['ios'], 'Scan with an iPhone or iPad camera to open the App Store.', 'Not on the App Store yet.'),
		);
		if ($apk_available) {
			$cards[] = array('Android APK', 'fab fa-android', 'tone-cyan', $apk_url, $qr['apk'], 'For phones without Play Store access. Allow installs from this source when asked.', '');
		}
		$span = 'd3-span-' . (12 / count($cards));
		foreach ($cards as $i => $c) { ?>
		<section class="d3-card <?php echo $span; ?> d3-reveal" style="--i: <?php echo $i; ?>">
			<header class="d3-card-head">
				<div><h3><i class="<?php echo $c[1]; ?>"></i> <?php echo $c[0]; ?></h3><p><?php echo $c[3] !== '' ? 'Scan to install' : 'Coming soon'; ?></p></div>
			</header>
			<div class="d3-card-body ma-card-body">
				<?php if ($c[4]) { ?>
				<span class="ma-qr"><img src="<?php echo $c[4]; ?>" alt="QR code for <?php echo $c[0]; ?>"></span>
				<p><?php echo $c[5]; ?></p>
				<button type="button" class="ma-copy" data-copy="<?php echo html_escape($c[3]); ?>"><i class="far fa-copy"></i> Copy link to share</button>
				<?php } else { ?>
				<span class="ma-qr is-empty"><i class="<?php echo $c[1]; ?>"></i></span>
				<p><?php echo $c[6]; ?></p>
				<?php } ?>
			</div>
		</section>
		<?php } ?>
	</div>

	<?php if (is_superadmin_loggedin()) { ?>
	<section class="d3-card d3-reveal" style="margin-bottom: 20px;">
		<header class="d3-card-head">
			<div><h3><i class="fas fa-store"></i> App store links</h3><p>Only you (the platform owner) can see this. Every school's Mobile App page uses these links.</p></div>
		</header>
		<div class="d3-card-body">
			<?php if (!$store_fields) { ?>
			<div class="alert alert-warning mb-none">Run <code>php application/database_seeds/apply_pending_migrations.php</code> to enable store links.</div>
			<?php } else { ?>
			<?php echo form_open('mobile_app/save', array('class' => 'ma-form')); ?>
				<div class="row">
					<div class="col-md-6 form-group">
						<label for="maPlay"><i class="fab fa-google-play"></i> Google Play link</label>
						<input type="url" class="form-control" id="maPlay" name="play_store_url" value="<?php echo html_escape($play_store_url); ?>" placeholder="https://play.google.com/store/apps/details?id=...">
					</div>
					<div class="col-md-6 form-group">
						<label for="maIos"><i class="fab fa-apple"></i> App Store link</label>
						<input type="url" class="form-control" id="maIos" name="app_store_url" value="<?php echo html_escape($app_store_url); ?>" placeholder="https://apps.apple.com/app/...">
					</div>
				</div>
				<div class="checkbox-replace mb-md">
					<label class="i-checks"><input type="checkbox" name="show_apk" value="1"<?php echo $show_apk ? ' checked' : ''; ?>><i></i> Also offer the Android APK download<?php echo $apk_size ? ' (' . $apk_size . ' MB file found)' : ' (no APK uploaded to uploads/mobile_app/SchoolEdge.apk)'; ?></label>
				</div>
				<p class="text-muted" style="font-size: 12.5px;">Leave a link empty to show "Coming soon". Links must start with https://.</p>
				<button type="submit" class="btn btn-default"><i class="fas fa-save"></i> <?php echo translate('save'); ?></button>
			<?php echo form_close(); ?>
			<?php } ?>
		</div>
	</section>
	<?php } ?>
</div>
<script type="text/javascript">
	document.querySelectorAll('.ma-copy').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var text = btn.getAttribute('data-copy');
			var done = function () { btn.innerHTML = '<i class="fas fa-check"></i> Link copied'; setTimeout(function () { btn.innerHTML = '<i class="far fa-copy"></i> Copy link to share'; }, 1800); };
			if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(text).then(done); return; }
			var ta = document.createElement('textarea'); ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
			document.body.appendChild(ta); ta.select(); try { document.execCommand('copy'); done(); } catch (e) {} document.body.removeChild(ta);
		});
	});
</script>
