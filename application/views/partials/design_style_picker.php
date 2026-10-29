<?php
/**
 * Visual picker for a document template's design_style.
 *
 * Params:
 *   $selected  current style key (defaults to 'classic')
 *   $kind      'report' (marksheets) or 'card' (ID / admit cards, certificates)
 *
 * Each tile renders a miniature document using the real preset classes from
 * assets/css/document-templates.css, so the preview is exactly what prints.
 * Posts the same "design_style" field the old dropdown did.
 */
$selected = document_template_style(isset($selected) ? $selected : 'classic');
$kind = (isset($kind) && $kind === 'card') ? 'card' : 'report';
$pickerId = 'dsp' . substr(md5(uniqid('', true)), 0, 6);
?>
<link rel="stylesheet" href="<?=base_url('assets/css/document-templates.css?v=' . version_combine())?>">
<style type="text/css">
	.dsp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(168px, 1fr)); gap: 12px; }
	.dsp-tile { position: relative; display: block; margin: 0; cursor: pointer; border-radius: 12px; border: 2px solid #e3e6ee; background: #fff; overflow: hidden; transition: border-color .2s, box-shadow .2s, transform .2s; font-weight: normal; }
	.dsp-tile:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(20, 24, 60, .1); }
	.dsp-tile input { position: absolute; opacity: 0; pointer-events: none; }
	.dsp-tile.is-active, .dsp-tile:has(input:checked) { border-color: #7c5cff; box-shadow: 0 0 0 3px rgba(124, 92, 255, .18); }
	.dsp-tile input:focus-visible + .dsp-stage { outline: 2px solid #7c5cff; outline-offset: -2px; }
	.dsp-check { position: absolute; top: 8px; right: 8px; z-index: 5; display: none; width: 22px; height: 22px; border-radius: 50%; background: #7c5cff; color: #fff; font-size: 11px; line-height: 22px; text-align: center; }
	.dsp-tile.is-active .dsp-check, .dsp-tile:has(input:checked) .dsp-check { display: block; }
	/* spans (valid inside <label>) need explicit block display */
	.dsp-stage, .dsp-meta, .dsp-canvas, .dsp-card-wrap { display: block; }
	.dsp-stage { position: relative; height: 132px; overflow: hidden; background: #eef0f6; }
	/* draw the preview at full size, then scale it down so the real paddings/fonts look right */
	.dsp-canvas { position: absolute; top: 10px; left: 50%; width: 300px; transform: translateX(-50%) scale(.5); transform-origin: top center; }
	.dsp-doc { padding: 16px 16px 10px !important; background: #fff; min-height: 236px; font-size: 13px; }
	.dsp-doc .table { margin-bottom: 8px; font-size: 12px; }
	.dsp-doc .table > thead > tr > th, .dsp-doc .table > tbody > tr > td, .dsp-doc .table > tbody > tr > th { padding: 4px 6px !important; }
	.dsp-head { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
	.dsp-logo { width: 34px; height: 34px; border-radius: 50%; background: #d6dae5; flex: 0 0 34px; }
	.dsp-head b { display: block; font-size: 14px; }
	.dsp-head i { display: block; width: 110px; height: 6px; margin-top: 5px; border-radius: 3px; background: #e2e5ee; }
	.dsp-card-wrap { position: absolute; top: 12px; left: 50%; width: 220px; transform: translateX(-50%) scale(.75); transform-origin: top center; }
	.dsp-card { width: 220px; height: 140px; padding: 14px !important; border-radius: 10px; display: flex; gap: 12px; background-color: #fff; }
	.dsp-card .dsp-photo { width: 58px; height: 70px; border-radius: 8px; background: #d6dae5; flex: 0 0 58px; }
	.dsp-card .dsp-lines b { display: block; font-size: 13px; margin-bottom: 8px; }
	.dsp-card .dsp-lines i { display: block; height: 6px; margin-bottom: 7px; border-radius: 3px; background: #e2e5ee; }
	.dsp-card .dsp-bar { position: absolute; left: 14px; right: 14px; bottom: 12px; height: 16px; background: repeating-linear-gradient(90deg, #444 0 2px, transparent 2px 5px); opacity: .5; }
	.dsp-meta { padding: 9px 11px 11px; border-top: 1px solid #eef0f5; }
	.dsp-meta strong { display: flex; align-items: center; gap: 7px; font-size: 13px; color: #1d2440; }
	.dsp-meta strong span { display: inline-flex; }
	.dsp-meta strong span i { width: 10px; height: 10px; border-radius: 50%; margin-right: -3px; border: 1.5px solid #fff; box-shadow: 0 0 0 1px rgba(0, 0, 0, .08); }
	.dsp-meta small { display: block; margin-top: 3px; color: #6b7287; font-size: 11.5px; line-height: 1.35; }
</style>
<div class="dsp-grid" id="<?=$pickerId?>" role="radiogroup" aria-label="<?=translate('design_style')?>">
<?php foreach (document_template_style_meta() as $key => $meta) {
	$active = ($key === $selected);
	$docClass = document_template_class($key);
	?>
	<label class="dsp-tile<?=$active ? ' is-active' : ''?>">
		<input type="radio" name="design_style" value="<?=html_escape($key)?>"<?=$active ? ' checked' : ''?>>
		<span class="dsp-stage" aria-hidden="true">
			<span class="dsp-check"><i class="fas fa-check"></i></span>
		<?php if ($kind === 'report') { ?>
			<span class="dsp-canvas">
				<div class="mark-container dsp-doc <?=$docClass?>">
					<div class="dsp-head"><span class="dsp-logo"></span><div><b>Your School</b><i></i></div></div>
					<table class="table table-condensed table-bordered">
						<thead><tr><th>Subject</th><th>CA</th><th>Exam</th><th>Grade</th></tr></thead>
						<tbody>
							<tr><td>Mathematics</td><td>28</td><td>54</td><td>A</td></tr>
							<tr><td>English</td><td>25</td><td>49</td><td>B</td></tr>
							<tr><td>Basic Science</td><td>27</td><td>51</td><td>A</td></tr>
							<tr class="text-weight-semibold"><td>Total</td><td colspan="3">234 / 300</td></tr>
						</tbody>
					</table>
					<div class="marksheet-summary-panel">
						<table class="table table-bordered table-condensed">
							<tbody><tr><th colspan="2">Attendance</th></tr><tr><th>Present</th><td>58 / 60</td></tr></tbody>
						</table>
					</div>
				</div>
			</span>
		<?php } else { ?>
			<span class="dsp-card-wrap">
				<div class="certificate dsp-card <?=$docClass?>">
					<span class="dsp-photo"></span>
					<span class="dsp-lines"><b>Student Name</b><i style="width:100px"></i><i style="width:80px"></i><i style="width:90px"></i></span>
					<span class="dsp-bar"></span>
				</div>
			</span>
		<?php } ?>
		</span>
		<span class="dsp-meta">
			<strong><span><i style="background:<?=$meta['colors'][0]?>"></i><i style="background:<?=$meta['colors'][1]?>"></i></span> <?=html_escape($meta['label'])?></strong>
			<small><?=html_escape($meta['desc'])?></small>
		</span>
	</label>
<?php } ?>
</div>
<script type="text/javascript">
	// :has() isn't in every browser yet, so mirror the checked state onto the tile
	(function () {
		var grid = document.getElementById('<?=$pickerId?>');
		if (!grid) return;
		var sync = function () {
			grid.querySelectorAll('.dsp-tile').forEach(function (tile) {
				tile.classList.toggle('is-active', tile.querySelector('input').checked);
			});
		};
		// jQuery's .trigger('change') (used by the starter-template picker) doesn't reach
		// native listeners, so listen through jQuery when it's on the page
		if (window.jQuery) {
			jQuery(grid).on('change', 'input[name="design_style"]', sync);
		} else {
			grid.addEventListener('change', sync);
		}
	})();
</script>
