<?php
// Sign-in page designer. Super admins pick a school first (or the platform page).
$branchOptions = array('' => translate('select'), '0' => 'Platform sign-in page (no school)');
foreach ($this->app_lib->getSelectList('branch') as $id => $name) {
	if ($id !== '' && $id !== 0 && $id !== '0') $branchOptions[$id] = $name;
}
?>
<style type="text/css">
	.lp-wrap { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr); gap: 22px; align-items: start; }
	@media (max-width: 1199px) { .lp-wrap { grid-template-columns: 1fr; } }
	.lp-block { margin-bottom: 22px; }
	.lp-block > h5 { margin: 0 0 10px; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: .06em; color: #8a91a8; }
	.lp-layouts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
	.lp-opt { position: relative; display: block; margin: 0; padding: 0; cursor: pointer; border-radius: 12px; border: 2px solid rgba(130, 140, 170, .25); overflow: hidden; font-weight: normal; transition: border-color .2s, transform .2s, box-shadow .2s; }
	.lp-opt:hover { transform: translateY(-2px); }
	.lp-opt input { position: absolute; opacity: 0; pointer-events: none; }
	.lp-opt.is-active { border-color: var(--lp-accent, #7c5cff); box-shadow: 0 0 0 3px rgba(124, 92, 255, .18); }
	.lp-opt .lp-cap { display: block; padding: 7px 9px 8px; font-size: 12px; line-height: 1.3; }
	.lp-opt .lp-cap b { display: block; font-size: 12.5px; }
	.lp-mini { position: relative; display: block; height: 70px; background: #0b0f1f; overflow: hidden; }
	.lp-mini i { position: absolute; display: block; border-radius: 4px; }
	.lp-mini-split i:nth-child(1) { left: 0; top: 0; bottom: 0; width: 52%; background: radial-gradient(40px 30px at 70% 30%, #3b3f9a, transparent), #0b0f1f; }
	.lp-mini-split i:nth-child(2) { right: 0; top: 0; bottom: 0; width: 48%; background: #f4f5fa; border-radius: 0; }
	.lp-mini-split i:nth-child(3) { right: 9%; top: 18%; width: 30%; height: 64%; background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,.15); }
	.lp-mini-glass { background: linear-gradient(135deg, #5a7bb6, #23365e); }
	.lp-mini-glass i:nth-child(1) { inset: 0; background: rgba(10, 14, 30, .35); border-radius: 0; }
	.lp-mini-glass i:nth-child(2) { left: 32%; top: 14%; width: 36%; height: 72%; background: rgba(255,255,255,.28); border: 1px solid rgba(255,255,255,.5); transform: perspective(120px) rotateY(-12deg); }
	.lp-mini-glass i:nth-child(3) { display: none; }
	.lp-mini-poster { background: linear-gradient(135deg, #8a6f4d, #2e2a3a); }
	.lp-mini-poster i:nth-child(1) { left: 8%; top: 30%; width: 44%; height: 9px; background: #fff; box-shadow: 0 13px 0 -2px rgba(255,255,255,.55); }
	.lp-mini-poster i:nth-child(2) { right: 0; top: 0; bottom: 0; width: 34%; background: rgba(255,255,255,.92); border-radius: 0; }
	.lp-mini-poster i:nth-child(3) { display: none; }
	.lp-bgs { display: grid; grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); gap: 10px; }
	.lp-bg { height: 64px; }
	.lp-bg .lp-thumb { display: block; height: 100%; background: center / cover no-repeat #20263f; }
	.lp-bg .lp-none { display: grid; place-items: center; height: 100%; color: #8a91a8; font-size: 12px; background: repeating-linear-gradient(45deg, rgba(130,140,170,.12) 0 8px, transparent 8px 16px); }
	.lp-bg .lp-up { display: grid; place-items: center; height: 100%; color: #8a91a8; font-size: 12px; text-align: center; line-height: 1.3; }
	.lp-bg .lp-up i { display: block; font-size: 18px; margin-bottom: 3px; }
	.lp-row { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; }
	.lp-row input[type=color] { width: 54px; height: 38px; padding: 2px; border-radius: 10px; border: 1px solid rgba(130,140,170,.35); background: none; cursor: pointer; }
	.lp-swatch { width: 26px; height: 26px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px rgba(0,0,0,.12); cursor: pointer; padding: 0; }
	.lp-range { flex: 1; min-width: 160px; }

	/* live preview */
	.lp-sticky { position: sticky; top: 80px; }
	.lp-stage { perspective: 1400px; }
	.lp-preview { --lp-accent: #7c5cff; --lp-accent-2: #a58dff; --lp-ov: .55; position: relative; height: 380px; border-radius: 18px; overflow: hidden; background: #0b0f1f center / cover no-repeat; box-shadow: 0 30px 60px rgba(15, 20, 50, .25); transform: rotateX(4deg) rotateY(-6deg); transform-style: preserve-3d; transition: transform .5s cubic-bezier(.2,.8,.2,1); font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
	.lp-stage:hover .lp-preview { transform: none; }
	.lp-preview .pv-ov { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(8, 11, 26, var(--lp-ov)), rgba(8, 11, 26, calc(var(--lp-ov) * .6))); }
	.lp-preview .pv-show { position: absolute; left: 0; top: 0; bottom: 0; width: 52%; padding: 34px 26px; color: #fff; }
	.lp-preview .pv-logo { display: inline-grid; place-items: center; width: 42px; height: 42px; padding: 6px; border-radius: 12px; background: #fff; margin-bottom: 14px; }
	.lp-preview .pv-logo img { max-width: 100%; max-height: 100%; }
	.lp-preview .pv-h { margin: 0 0 8px; font: 800 19px/1.15 Sora, system-ui, sans-serif; letter-spacing: -.02em; }
	.lp-preview .pv-h span { background: linear-gradient(120deg, var(--lp-accent), var(--lp-accent-2)); -webkit-background-clip: text; background-clip: text; color: transparent; }
	.lp-preview .pv-t { margin: 0 0 14px; font-size: 11.5px; line-height: 1.5; color: rgba(255,255,255,.75); }
	.lp-preview .pv-perks { list-style: none; margin: 0; padding: 0; display: grid; gap: 6px; }
	.lp-preview .pv-perks li { display: flex; align-items: center; gap: 8px; padding: 6px 9px; border-radius: 9px; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12); font-size: 10.5px; font-weight: 600; }
	.lp-preview .pv-perks li::before { content: ""; width: 14px; height: 14px; border-radius: 5px; background: linear-gradient(120deg, var(--lp-accent), var(--lp-accent-2)); }
	.lp-preview .pv-panel { position: absolute; right: 0; top: 0; bottom: 0; width: 48%; display: grid; place-items: center; background: #f4f5fa; }
	.lp-preview .pv-card { width: 74%; padding: 18px 16px; border-radius: 16px; background: #fff; box-shadow: 0 18px 40px rgba(15,20,50,.18); }
	.lp-preview .pv-card b { display: block; margin-bottom: 12px; color: #1d2440; font: 800 15px Sora, system-ui, sans-serif; }
	.lp-preview .pv-field { height: 26px; margin-bottom: 8px; border-radius: 8px; background: #f1f3f8; border: 1px solid #e3e6ef; }
	.lp-preview .pv-btn { height: 28px; margin-top: 12px; border-radius: 9px; background: linear-gradient(120deg, var(--lp-accent), var(--lp-accent-2)); box-shadow: 0 8px 18px rgba(0,0,0,.18); }
	/* split: photo only fills the showcase side */
	.lp-preview[data-layout=split] .pv-ov { right: 48%; }
	.lp-preview[data-layout=split] { background-size: 0 0; }
	.lp-preview[data-layout=split] .pv-show { background: radial-gradient(160px 120px at 80% 20%, #2a2f7a, transparent 70%), #0b0f1f; }
	.lp-preview[data-layout=split].has-bg .pv-show { background: var(--lp-bg) center / cover no-repeat; }
	.lp-preview[data-layout=split].has-bg .pv-show::before { content: ""; position: absolute; inset: 0; background: rgba(8, 11, 26, var(--lp-ov)); }
	.lp-preview[data-layout=split] .pv-show > * { position: relative; }
	/* glass: centred frosted card over the photo */
	.lp-preview[data-layout=glass] .pv-show { left: 0; right: 0; width: auto; top: 18px; padding: 0; text-align: center; }
	.lp-preview[data-layout=glass] .pv-t, .lp-preview[data-layout=glass] .pv-perks, .lp-preview[data-layout=glass] .pv-h { display: none; }
	.lp-preview[data-layout=glass] .pv-logo { margin: 0; }
	.lp-preview[data-layout=glass] .pv-panel { left: 0; width: auto; top: 50px; background: transparent; }
	.lp-preview[data-layout=glass] .pv-card { width: 46%; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.35); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
	.lp-preview[data-layout=glass] .pv-card b { color: #fff; text-align: center; }
	.lp-preview[data-layout=glass] .pv-field { background: rgba(255,255,255,.2); border-color: rgba(255,255,255,.3); }
	/* poster: big headline on the photo, form docked right */
	.lp-preview[data-layout=poster] .pv-show { width: 60%; padding-top: 60px; }
	.lp-preview[data-layout=poster] .pv-h { font-size: 25px; }
	.lp-preview[data-layout=poster] .pv-panel { width: 40%; background: rgba(255,255,255,.96); }
	.lp-preview[data-layout=poster] .pv-card { box-shadow: none; padding: 0; background: transparent; width: 78%; }
	.lp-preview[data-layout=poster] .pv-perks { grid-template-columns: 1fr 1fr; }
	html.dark .lp-opt .lp-cap { color: #c9cee0; }
</style>

<div class="row">
	<div class="col-md-12">
<?php if (is_superadmin_loggedin()): ?>
		<section class="panel">
			<header class="panel-heading">
				<h4 class="panel-title"><?php echo translate('select_ground'); ?></h4>
			</header>
			<form method="get" action="<?php echo base_url('frontend/login_page'); ?>">
			<div class="panel-body">
				<div class="row mb-sm">
					<div class="col-md-offset-3 col-md-6">
						<div class="form-group">
							<label class="control-label"><?php echo translate('branch'); ?> <span class="required">*</span></label>
							<?php echo form_dropdown('branch_id', $branchOptions, $branch_id === null ? '' : (string) $branch_id, "class='form-control' required data-plugin-selectTwo data-width='100%'"); ?>
						</div>
					</div>
				</div>
			</div>
			<footer class="panel-footer">
				<div class="row">
					<div class="col-md-offset-10 col-md-2">
						<button type="submit" class="btn btn-default btn-block"><i class="fas fa-filter"></i> <?php echo translate('filter'); ?></button>
					</div>
				</div>
			</footer>
			</form>
		</section>
<?php endif; if ($branch_id !== null): ?>
		<section class="panel">
			<header class="panel-heading">
				<?php if (!empty($signin_url)) { ?>
				<div class="panel-btn">
					<a href="<?php echo $signin_url; ?>" target="_blank" rel="noopener" class="btn btn-default btn-circle"><i class="fas fa-external-link-alt"></i> <?php echo translate('view'); ?></a>
				</div>
				<?php } ?>
				<h4 class="panel-title"><i class="fas fa-sign-in-alt"></i> <?php echo translate('sign_in_page'); ?></h4>
			</header>
			<?php echo form_open_multipart('frontend/login_page/save' . get_request_url(), array('id' => 'lpForm')); ?>
			<div class="panel-body">
				<?php if (empty($signin_url)) { ?>
				<div class="alert alert-info"><i class="fas fa-info-circle"></i> Set a website URL alias under Frontend &rsaquo; Setting to give this school its own sign-in address.</div>
				<?php } else { ?>
				<p class="text-muted mb-lg"><i class="fas fa-link"></i> Your sign-in address: <a href="<?php echo $signin_url; ?>" target="_blank" rel="noopener"><?php echo $signin_url; ?></a></p>
				<?php } ?>
				<div class="lp-wrap">
					<div>
						<div class="lp-block">
							<h5>Layout</h5>
							<div class="lp-layouts" data-group="layout">
							<?php foreach (login_page_layouts() as $key => $meta) { $on = $setting['layout'] === $key; ?>
								<label class="lp-opt<?php echo $on ? ' is-active' : ''; ?>">
									<input type="radio" name="layout" value="<?php echo $key; ?>"<?php echo $on ? ' checked' : ''; ?>>
									<span class="lp-mini lp-mini-<?php echo $key; ?>"><i></i><i></i><i></i></span>
									<span class="lp-cap"><b><?php echo $meta['label']; ?></b><?php echo $meta['desc']; ?></span>
								</label>
							<?php } ?>
							</div>
						</div>

						<div class="lp-block">
							<h5>Background photo</h5>
							<div class="lp-bgs" data-group="bg">
								<label class="lp-opt lp-bg<?php echo $setting['bg_image'] === '' ? ' is-active' : ''; ?>">
									<input type="radio" name="bg_choice" value=""<?php echo $setting['bg_image'] === '' ? ' checked' : ''; ?> data-url="">
									<span class="lp-none">No photo</span>
								</label>
								<?php
								$choices = login_page_presets();
								if ($setting['bg_image'] !== '' && !in_array($setting['bg_image'], $choices, true)) array_unshift($choices, $setting['bg_image']);
								foreach ($choices as $file) {
									if (!is_file(FCPATH . 'uploads/login_image/' . $file)) continue;
									$url = base_url('uploads/login_image/' . $file);
									$on = $setting['bg_image'] === $file; ?>
								<label class="lp-opt lp-bg<?php echo $on ? ' is-active' : ''; ?>">
									<input type="radio" name="bg_choice" value="<?php echo html_escape($file); ?>"<?php echo $on ? ' checked' : ''; ?> data-url="<?php echo $url; ?>">
									<span class="lp-thumb" style="background-image: url('<?php echo $url; ?>')"></span>
								</label>
								<?php } ?>
								<label class="lp-opt lp-bg" id="lpUploadTile">
									<input type="file" name="bg_upload" accept="image/jpeg,image/png,image/webp" id="lpUpload">
									<span class="lp-up"><span><i class="fas fa-cloud-upload-alt"></i>Upload your own</span></span>
								</label>
							</div>
							<p class="text-muted mt-sm mb-none" style="font-size: 12px;">JPG, PNG or WebP, up to 5 MB. A wide photo (at least 1600 px across) looks best.</p>
						</div>

						<div class="lp-block">
							<h5>Photo darkness</h5>
							<div class="lp-row">
								<input type="range" class="lp-range" name="overlay" id="lpOverlay" min="0" max="90" step="5" value="<?php echo (int) $setting['overlay']; ?>">
								<b id="lpOverlayVal"><?php echo (int) $setting['overlay']; ?>%</b>
							</div>
						</div>

						<div class="lp-block">
							<h5>Accent colour</h5>
							<div class="lp-row">
								<input type="color" name="accent" id="lpAccent" value="<?php echo $setting['accent']; ?>">
								<?php foreach (array('#7c5cff', '#2563eb', '#0ea5e9', '#10b981', '#f97316', '#e11d48', '#c9a24d', '#0f172a') as $sw) { ?>
								<button type="button" class="lp-swatch" style="background: <?php echo $sw; ?>" data-color="<?php echo $sw; ?>" aria-label="<?php echo $sw; ?>"></button>
								<?php } ?>
							</div>
						</div>

						<div class="lp-block">
							<h5>Welcome text</h5>
							<div class="form-group">
								<label class="control-label">Headline</label>
								<input type="text" class="form-control" name="headline" id="lpHeadline" maxlength="150" value="<?php echo html_escape($setting['headline']); ?>" placeholder="<?php echo html_escape(translate('welcome_to') . ' ' . $school_name); ?>">
							</div>
							<div class="form-group">
								<label class="control-label">Tagline</label>
								<input type="text" class="form-control" name="tagline" id="lpTagline" maxlength="255" value="<?php echo html_escape($setting['tagline']); ?>">
							</div>
							<div class="form-group">
								<label class="control-label">Highlights (one per line, up to 4)</label>
								<textarea class="form-control" name="perks" id="lpPerks" rows="4"><?php echo html_escape($setting['perks']); ?></textarea>
							</div>
							<div class="checkbox-replace">
								<label class="i-checks"><input type="checkbox" name="show_perks" id="lpShowPerks" value="1"<?php echo $setting['show_perks'] ? ' checked' : ''; ?>><i></i> Show the highlights</label>
							</div>
						</div>
					</div>

					<div class="lp-sticky">
						<div class="lp-block">
							<h5>Live preview</h5>
							<div class="lp-stage">
								<div class="lp-preview" id="lpPreview" data-layout="<?php echo $setting['layout']; ?>">
									<div class="pv-ov"></div>
									<div class="pv-show">
										<span class="pv-logo"><img src="<?php echo $school_logo; ?>" alt=""></span>
										<h3 class="pv-h"></h3>
										<p class="pv-t"></p>
										<ul class="pv-perks"></ul>
									</div>
									<div class="pv-panel">
										<div class="pv-card"><b>Sign in</b><div class="pv-field"></div><div class="pv-field"></div><div class="pv-btn"></div></div>
									</div>
								</div>
							</div>
							<p class="text-muted mt-md" style="font-size: 12px;"><i class="fas fa-mobile-alt"></i> On phones every layout stacks into a single column with the form on top of the photo.</p>
						</div>
					</div>
				</div>
			</div>
			<footer class="panel-footer">
				<div class="row">
					<div class="col-md-offset-9 col-md-3">
						<button type="submit" class="btn btn-default btn-block"><i class="fas fa-plus-circle"></i> <?php echo translate('save'); ?></button>
					</div>
				</div>
			</footer>
			<?php echo form_close(); ?>
		</section>
<?php endif; ?>
	</div>
</div>

<?php if ($branch_id !== null): ?>
<script type="text/javascript">
(function () {
	var form = document.getElementById('lpForm');
	var pv = document.getElementById('lpPreview');
	var defaultHead = <?php echo json_encode(translate('welcome_to') . ' ' . $school_name); ?>;
	var uploadUrl = '';

	function lighter(hex) {
		var n = parseInt(hex.slice(1), 16), out = '#';
		[16, 8, 0].forEach(function (s) {
			var c = (n >> s) & 255;
			c = Math.round(c + (255 - c) * 0.35);
			out += ('0' + c.toString(16)).slice(-2);
		});
		return out;
	}
	function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

	function render() {
		var layout = form.querySelector('input[name=layout]:checked').value;
		var bgInput = form.querySelector('input[name=bg_choice]:checked');
		var bg = uploadUrl || (bgInput ? bgInput.getAttribute('data-url') : '');
		var accent = document.getElementById('lpAccent').value;
		var ov = document.getElementById('lpOverlay').value;
		pv.setAttribute('data-layout', layout);
		pv.style.setProperty('--lp-accent', accent);
		pv.style.setProperty('--lp-accent-2', lighter(accent));
		pv.style.setProperty('--lp-ov', ov / 100);
		pv.style.backgroundImage = bg ? 'url("' + bg + '")' : '';
		pv.style.setProperty('--lp-bg', bg ? 'url("' + bg + '")' : 'none');
		pv.classList.toggle('has-bg', !!bg);
		document.getElementById('lpOverlayVal').textContent = ov + '%';

		var head = document.getElementById('lpHeadline').value.trim() || defaultHead;
		var words = head.split(' ');
		var last = words.length > 2 ? words.splice(-2).join(' ') : words.pop();
		pv.querySelector('.pv-h').innerHTML = esc(words.join(' ')) + ' <span>' + esc(last || '') + '</span>';
		pv.querySelector('.pv-t').textContent = document.getElementById('lpTagline').value;
		var perks = document.getElementById('lpShowPerks').checked ? document.getElementById('lpPerks').value.split('\n').filter(function (p) { return p.trim(); }).slice(0, 4) : [];
		pv.querySelector('.pv-perks').innerHTML = perks.map(function (p) { return '<li>' + esc(p.trim()) + '</li>'; }).join('');
		document.querySelector('.lp-wrap').style.setProperty('--lp-accent', accent);
	}

	form.addEventListener('change', function (e) {
		var group = e.target.closest('[data-group]');
		if (group && e.target.type === 'radio') {
			group.querySelectorAll('.lp-opt').forEach(function (o) { o.classList.toggle('is-active', !!o.querySelector('input:checked')); });
			if (group.getAttribute('data-group') === 'bg') {
				uploadUrl = '';
				document.getElementById('lpUpload').value = '';
				document.getElementById('lpUploadTile').classList.remove('is-active');
			}
		}
		render();
	});
	form.addEventListener('input', render);

	document.getElementById('lpUpload').addEventListener('change', function () {
		var f = this.files && this.files[0];
		if (!f) return;
		uploadUrl = URL.createObjectURL(f);
		var tile = document.getElementById('lpUploadTile');
		form.querySelectorAll('[data-group=bg] .lp-opt').forEach(function (o) { o.classList.remove('is-active'); });
		tile.classList.add('is-active');
		tile.querySelector('.lp-up').style.background = 'center / cover no-repeat url("' + uploadUrl + '")';
		tile.querySelector('.lp-up span').style.visibility = 'hidden';
		render();
	});

	document.querySelectorAll('.lp-swatch').forEach(function (b) {
		b.addEventListener('click', function () {
			document.getElementById('lpAccent').value = b.getAttribute('data-color');
			render();
		});
	});
	render();
})();
</script>
<?php endif; ?>
