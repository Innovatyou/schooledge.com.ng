<?php
// Report Card Setup wizard (Exam::setup). Presets come from config/report_card_presets.php.
$canSave = get_permission('exam', 'is_add');
$typeLabels = array(3 => 'Scores and grades', 1 => 'Scores only', 2 => 'Grades only');
?>
<style>
	.rcs { --rc-card: #fff; --rc-soft: #f5f6fb; --rc-line: #e6e8f0; --rc-text: #1d2440; --rc-muted: #6b7290; --rc-accent: #7c5cff; --rc-accent-2: #4f8cff; color: var(--rc-text); font-family: 'Plus Jakarta Sans', 'Signika', system-ui, sans-serif; }
	html.dark .rcs { --rc-card: var(--pn-side, #1d1f24); --rc-soft: rgba(255, 255, 255, .04); --rc-line: rgba(255, 255, 255, .1); --rc-text: #eceef4; --rc-muted: #a4a9b8; }
	.rcs * { box-sizing: border-box; }
	.rcs-hero { position: relative; overflow: hidden; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 18px; padding: 26px 28px; margin-bottom: 22px; border-radius: 22px; color: #fff; background: radial-gradient(600px 300px at 90% 0%, rgba(34, 211, 238, .35), transparent 60%), linear-gradient(120deg, #3b2f8f, #5b4bdb 55%, #4f8cff); box-shadow: 0 18px 40px rgba(59, 47, 143, .25); }
	.rcs-hero h2 { margin: 0 0 6px; font: 800 24px/1.2 'Sora', system-ui, sans-serif; color: #fff; }
	.rcs-hero p { margin: 0; max-width: 640px; color: rgba(255, 255, 255, .85); font-size: 14px; line-height: 1.6; }
	.rcs-chips { display: flex; flex-wrap: wrap; gap: 8px; }
	.rcs-chip { display: inline-flex; align-items: center; gap: 7px; padding: 8px 13px; border-radius: 999px; background: rgba(255, 255, 255, .14); font-size: 12.5px; font-weight: 600; }
	.rcs-steps { display: flex; gap: 8px; margin-bottom: 18px; overflow-x: auto; padding-bottom: 2px; }
	.rcs-step { flex: 1 0 auto; display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1px solid var(--rc-line); border-radius: 14px; background: var(--rc-card); color: var(--rc-muted); font-weight: 600; font-size: 13px; cursor: pointer; transition: border-color .2s, color .2s, transform .2s; }
	.rcs-step b { display: grid; place-items: center; width: 26px; height: 26px; border-radius: 50%; background: var(--rc-soft); font-size: 12px; }
	.rcs-step.is-on { color: var(--rc-text); border-color: var(--rc-accent); transform: translateY(-2px); }
	.rcs-step.is-on b, .rcs-step.is-done b { color: #fff; background: linear-gradient(120deg, var(--rc-accent), var(--rc-accent-2)); }
	.rcs-card { padding: 24px; border: 1px solid var(--rc-line); border-radius: 20px; background: var(--rc-card); box-shadow: 0 10px 30px rgba(20, 24, 60, .05); }
	.rcs-card h3 { margin: 0 0 4px; font: 700 18px/1.3 'Sora', system-ui, sans-serif; color: var(--rc-text); }
	.rcs-card > p.rcs-sub { margin: 0 0 20px; color: var(--rc-muted); font-size: 13.5px; }
	.rcs-pane { display: none; }
	.rcs-pane.is-on { display: block; animation: rcsIn .3s ease; }
	@keyframes rcsIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
	.rcs-presets { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
	.rcs-preset { position: relative; display: block; padding: 16px; border: 1.5px solid var(--rc-line); border-radius: 16px; background: var(--rc-card); cursor: pointer; transition: border-color .2s, box-shadow .2s, transform .2s; }
	.rcs-preset:hover { transform: translateY(-3px); box-shadow: 0 12px 26px rgba(20, 24, 60, .08); }
	.rcs-preset input { position: absolute; opacity: 0; }
	.rcs-preset .flag { display: inline-grid; place-items: center; min-width: 42px; height: 30px; padding: 0 9px; border-radius: 9px; color: #fff; font: 800 13px/1 'Sora', system-ui, sans-serif; letter-spacing: .06em; background: linear-gradient(120deg, var(--rc-accent), var(--rc-accent-2)); box-shadow: 0 8px 18px rgba(124, 92, 255, .25); }
	.rcs-preset b { display: block; margin: 8px 0 4px; font-size: 15px; color: var(--rc-text); }
	.rcs-preset small { display: block; color: var(--rc-muted); font-size: 12px; line-height: 1.5; }
	.rcs-preset.is-on { border-color: var(--rc-accent); box-shadow: 0 0 0 4px rgba(124, 92, 255, .14); }
	.rcs-preset.is-on::after { content: "\f00c"; position: absolute; top: 12px; right: 12px; display: grid; place-items: center; width: 22px; height: 22px; border-radius: 50%; color: #fff; font: 900 11px 'Font Awesome 6 Free'; background: var(--rc-accent); }
	.rcs-list { display: grid; gap: 8px; }
	.rcs-row { display: grid; gap: 8px; align-items: center; padding: 8px; border: 1px solid var(--rc-line); border-radius: 12px; background: var(--rc-soft); }
	.rcs-row.term { grid-template-columns: 28px 1fr 36px; }
	.rcs-row.assess { grid-template-columns: 28px 1fr 110px 110px 36px; }
	.rcs-row.grade { grid-template-columns: 90px 80px 80px 80px 1fr 36px; }
	.rcs-row .num { display: grid; place-items: center; width: 28px; height: 28px; border-radius: 8px; background: var(--rc-card); color: var(--rc-muted); font-size: 12px; font-weight: 700; }
	.rcs-head { display: grid; gap: 8px; padding: 0 8px 4px; color: var(--rc-muted); font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
	.rcs input.form-control, .rcs select.form-control { height: 38px; border-radius: 10px; }
	.rcs-del { display: grid; place-items: center; width: 34px; height: 34px; border: 0; border-radius: 10px; color: #e2556b; background: transparent; }
	.rcs-del:hover { background: rgba(226, 85, 107, .1); }
	.rcs-add { margin-top: 10px; border: 1.5px dashed var(--rc-line); border-radius: 12px; background: transparent; color: var(--rc-accent); font-weight: 700; padding: 9px 14px; }
	.rcs-add:hover { border-color: var(--rc-accent); }
	.rcs-total { display: inline-flex; align-items: center; gap: 8px; margin-top: 12px; padding: 8px 12px; border-radius: 10px; font-size: 13px; font-weight: 600; background: rgba(52, 211, 153, .12); color: #0f9f6e; }
	.rcs-total.warn { background: rgba(251, 191, 36, .14); color: #b7791f; }
	.rcs-toggle { display: flex; align-items: flex-start; gap: 12px; padding: 14px; margin-top: 18px; border: 1px solid var(--rc-line); border-radius: 14px; cursor: pointer; }
	.rcs-toggle input { margin-top: 3px; width: 17px; height: 17px; accent-color: var(--rc-accent); }
	.rcs-toggle b { display: block; font-size: 14px; color: var(--rc-text); }
	.rcs-toggle small { display: block; color: var(--rc-muted); font-size: 12.5px; }
	.rcs-mid { display: grid; grid-template-columns: 1fr 120px 120px; gap: 8px; margin-top: 10px; }
	.rcs-scale-pick { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
	.rcs-scale-pick button { border: 1px solid var(--rc-line); border-radius: 999px; background: var(--rc-card); color: var(--rc-text); padding: 7px 14px; font-size: 13px; font-weight: 600; }
	.rcs-scale-pick button.is-on { border-color: var(--rc-accent); color: var(--rc-accent); background: rgba(124, 92, 255, .08); }
	.rcs-review { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
	.rcs-review > div { padding: 16px; border: 1px solid var(--rc-line); border-radius: 14px; background: var(--rc-soft); }
	.rcs-review h4 { margin: 0 0 8px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--rc-muted); }
	.rcs-review ul { margin: 0; padding-left: 18px; font-size: 13.5px; line-height: 1.7; }
	.rcs-note { padding: 12px 14px; margin-bottom: 16px; border-radius: 12px; background: rgba(79, 140, 255, .1); color: var(--rc-text); font-size: 13px; line-height: 1.6; }
	.rcs-note i { color: var(--rc-accent-2); margin-right: 6px; }
	.rcs-errors { display: none; padding: 12px 14px; margin-top: 16px; border-radius: 12px; background: rgba(226, 85, 107, .1); color: #c53b52; font-size: 13px; }
	.rcs-errors ul { margin: 0; padding-left: 18px; }
	.rcs-nav { display: flex; justify-content: space-between; gap: 10px; margin-top: 22px; }
	.rcs-btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 20px; border: 0; border-radius: 12px; font-weight: 700; font-size: 14px; }
	.rcs-btn.primary { color: #fff; background: linear-gradient(120deg, var(--rc-accent), var(--rc-accent-2)); box-shadow: 0 10px 22px rgba(124, 92, 255, .3); }
	.rcs-btn.ghost { color: var(--rc-text); background: var(--rc-soft); border: 1px solid var(--rc-line); }
	.rcs-btn[disabled] { opacity: .6; }
	.rcs-done { text-align: center; padding: 10px 0 0; }
	.rcs-done .big { display: grid; place-items: center; width: 72px; height: 72px; margin: 0 auto 14px; border-radius: 22px; color: #fff; font-size: 30px; background: linear-gradient(135deg, #34d399, #0ea5e9); box-shadow: 0 16px 34px rgba(14, 165, 233, .3); }
	.rcs-next { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 12px; margin-top: 22px; text-align: left; }
	.rcs-next a { display: flex; gap: 12px; padding: 14px; border: 1px solid var(--rc-line); border-radius: 14px; background: var(--rc-card); color: var(--rc-text) !important; text-decoration: none !important; transition: transform .2s, border-color .2s; }
	.rcs-next a:hover { transform: translateY(-3px); border-color: var(--rc-accent); }
	.rcs-next a > span { display: grid; place-items: center; flex: 0 0 34px; height: 34px; border-radius: 10px; color: #fff; font-weight: 800; background: linear-gradient(120deg, var(--rc-accent), var(--rc-accent-2)); }
	.rcs-next b { display: block; font-size: 14px; }
	.rcs-next small { color: var(--rc-muted); font-size: 12.5px; line-height: 1.5; }
	@media (max-width: 767px) {
		.rcs-hero { padding: 20px; }
		.rcs-card { padding: 16px; }
		.rcs-row.assess { grid-template-columns: 1fr 1fr 36px; }
		.rcs-row.assess .num { display: none; }
		.rcs-row.assess .a-name { grid-column: 1 / -1; }
		.rcs-row.grade { grid-template-columns: 1fr 1fr 1fr; }
		.rcs-row.grade .g-remark { grid-column: 1 / 3; }
		.rcs-head.grade { display: none; }
		.rcs-head.assess { grid-template-columns: 1fr 1fr 36px !important; }
		.rcs-head.assess span:nth-child(-n+2) { display: none; }
		.rcs-mid { grid-template-columns: 1fr 1fr; }
		.rcs-mid input:first-child { grid-column: 1 / -1; }
		.rcs-step span { display: none; }
	}
</style>

<div class="rcs">
<?php if (is_superadmin_loggedin()) { ?>
	<section class="panel">
		<header class="panel-heading"><h4 class="panel-title"><?=translate('select_ground')?></h4></header>
		<form method="get" action="<?=base_url('exam/setup')?>">
		<div class="panel-body">
			<div class="row"><div class="col-md-offset-3 col-md-6">
				<label class="control-label"><?=translate('branch')?> <span class="required">*</span></label>
				<?php echo form_dropdown('branch_id', $this->app_lib->getSelectList('branch'), $branch_id, "class='form-control' required data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'"); ?>
			</div></div>
		</div>
		<footer class="panel-footer"><div class="row"><div class="col-md-offset-10 col-md-2">
			<button type="submit" class="btn btn-default btn-block"><i class="fas fa-filter"></i> <?=translate('filter')?></button>
		</div></div></footer>
		</form>
	</section>
<?php } ?>

<?php if ($branch_id) {
	$have = array('terms' => count($state['terms']), 'exams' => count($state['exams']), 'grades' => count($state['grades']), 'columns' => count($state['distributions']));
	?>
	<div class="rcs-hero">
		<div>
			<h2><i class="fas fa-wand-magic-sparkles"></i> Report Card Setup</h2>
			<p>Get terms, mid-term tests, CA and exam columns and your grading scale ready in one go. Pick your country, change anything you like, and click <b>Create my setup</b>. It's safe to run again, because it only adds what's missing.</p>
		</div>
		<div class="rcs-chips">
			<span class="rcs-chip"><i class="far fa-calendar"></i> Session <?=html_escape($session_name)?></span>
			<span class="rcs-chip"><i class="fas fa-layer-group"></i> <?=$have['terms']?> terms</span>
			<span class="rcs-chip"><i class="fas fa-file-alt"></i> <?=$have['exams']?> exams</span>
			<span class="rcs-chip"><i class="fas fa-medal"></i> <?=$have['grades']?> grades</span>
		</div>
	</div>

	<div class="rcs-steps" role="tablist">
		<div class="rcs-step is-on" data-go="0"><b>1</b><span>Country</span></div>
		<div class="rcs-step" data-go="1"><b>2</b><span>Terms &amp; tests</span></div>
		<div class="rcs-step" data-go="2"><b>3</b><span>CA &amp; exam</span></div>
		<div class="rcs-step" data-go="3"><b>4</b><span>Grading</span></div>
		<div class="rcs-step" data-go="4"><b>5</b><span>Review</span></div>
	</div>

	<?php echo form_open('exam/setup_save', array('id' => 'rcsForm', 'autocomplete' => 'off')); ?>
	<input type="hidden" name="branch_id" value="<?=$branch_id?>">
	<div class="rcs-card">

		<div class="rcs-pane is-on" data-pane="0">
			<h3>Where is your school?</h3>
			<p class="rcs-sub">This fills in the usual terms, assessments and grading for that country. You can change all of it in the next steps.</p>
			<div class="rcs-presets">
				<?php $first = true; foreach ($presets as $key => $p) { ?>
				<label class="rcs-preset<?=$first ? ' is-on' : ''?>">
					<input type="radio" name="preset" value="<?=$key?>"<?=$first ? ' checked' : ''?>>
					<span class="flag"><?=html_escape($p['code'])?></span>
					<b><?=html_escape($p['label'])?></b>
					<small><?=html_escape($p['hint'])?></small>
				</label>
				<?php $first = false; } ?>
				<label class="rcs-preset">
					<input type="radio" name="preset" value="custom">
					<span class="flag"><i class="fas fa-earth-africa"></i></span>
					<b>Somewhere else</b>
					<small>Start from the Nigerian layout and rename the terms, columns and grades for your country.</small>
				</label>
			</div>
		</div>

		<div class="rcs-pane" data-pane="1">
			<h3>Terms in the session</h3>
			<p class="rcs-sub">One report card is produced for each term. Rename, add or remove terms to match your school calendar.</p>
			<div class="rcs-list" id="rcsTerms"></div>
			<button type="button" class="rcs-add" data-add="term"><i class="fas fa-plus"></i> Add a term</button>
			<label class="rcs-toggle">
				<input type="checkbox" name="midterm_on" value="1" id="rcsMidOn">
				<span><b>Add a mid-term test to every term</b><small>Creates a separate mid-term exam per term, so you can send a mid-term report card.</small></span>
			</label>
			<div class="rcs-mid" id="rcsMid">
				<input type="text" class="form-control" name="midterm_name" placeholder="Test name, e.g. Mid-Term Test">
				<input type="number" class="form-control" name="midterm_full" min="1" step="any" placeholder="Full mark">
				<input type="number" class="form-control" name="midterm_pass" min="0" step="any" placeholder="Pass mark">
			</div>
		</div>

		<div class="rcs-pane" data-pane="2">
			<h3>How each subject is scored</h3>
			<p class="rcs-sub">These columns appear on the mark entry sheet and the report card. The full and pass marks are filled in for you when you build the exam timetable.</p>
			<div class="rcs-head assess" style="grid-template-columns: 28px 1fr 110px 110px 36px"><span></span><span>Column</span><span>Full mark</span><span>Pass mark</span><span></span></div>
			<div class="rcs-list" id="rcsAssess"></div>
			<button type="button" class="rcs-add" data-add="assess"><i class="fas fa-plus"></i> Add a column</button>
			<div><span class="rcs-total" id="rcsTotal"></span></div>
		</div>

		<div class="rcs-pane" data-pane="3">
			<h3>Grading scale</h3>
			<p class="rcs-sub">The total score for a subject is turned into a grade and remark using this table.</p>
			<div class="rcs-scale-pick" id="rcsScalePick"></div>
			<div class="rcs-head grade" style="grid-template-columns: 90px 80px 80px 80px 1fr 36px"><span>Grade</span><span>Point</span><span>From</span><span>To</span><span>Remark</span><span></span></div>
			<div class="rcs-list" id="rcsGrades"></div>
			<button type="button" class="rcs-add" data-add="grade"><i class="fas fa-plus"></i> Add a grade</button>
			<?php if ($have['grades']) { ?>
			<label class="rcs-toggle">
				<input type="checkbox" name="replace_grades" value="1">
				<span><b>Replace my current grading scale (<?=$have['grades']?> grades)</b><small>Leave this off to keep the grades you already have.</small></span>
			</label>
			<?php } ?>
			<div class="form-group" style="margin-top:18px">
				<label class="control-label">What the report card shows</label>
				<?php echo form_dropdown('type_id', $typeLabels, 3, "class='form-control' style='max-width:320px'"); ?>
			</div>
		</div>

		<div class="rcs-pane" data-pane="4">
			<h3>Ready to create</h3>
			<p class="rcs-sub">Here's what will be set up for session <?=html_escape($session_name)?>.</p>
			<?php if ($have['exams']) { ?>
			<div class="rcs-note"><i class="fas fa-shield-halved"></i>You already have <?=$have['exams']?> exam(s) this session. Exams and terms with the same name are kept as they are. Only the missing ones are added.</div>
			<?php } ?>
			<div class="rcs-review" id="rcsReview"></div>
			<label class="rcs-toggle">
				<input type="checkbox" name="publish" value="1" checked>
				<span><b>Open the exams for mark entry</b><small>Teachers can start entering scores as soon as the exam timetable is added.</small></span>
			</label>
			<div class="rcs-errors" id="rcsErrors"></div>
		</div>

		<div class="rcs-pane" data-pane="5">
			<div class="rcs-done">
				<div class="big"><i class="fas fa-check"></i></div>
				<h3>Your report cards are set up</h3>
				<p class="rcs-sub" id="rcsDoneText" style="margin-top:6px"></p>
				<div class="rcs-next">
					<a href="<?=base_url('timetable/set_examwise')?>"><span>1</span><div><b>Add the exam timetable</b><small>Pick the class and exam. Full and pass marks are already filled in.</small></div></a>
					<a href="<?=base_url('exam/mark_entry')?>"><span>2</span><div><b>Enter scores</b><small>Teachers enter CA and exam marks per subject.</small></div></a>
					<a href="<?=base_url('exam/psychomotor_entry')?>"><span>3</span><div><b>Rate behaviour &amp; skills</b><small>Affective and psychomotor ratings for the report card.</small></div></a>
					<a href="<?=base_url('exam/class_position')?>"><span>4</span><div><b>Generate positions</b><small>Rank each class and add teacher and principal remarks.</small></div></a>
					<a href="<?=base_url('marksheet_template')?>"><span>5</span><div><b>Design the report card</b><small>Logo, signatures, next-term date and cumulative average.</small></div></a>
					<a href="<?=base_url('exam/marksheet')?>"><span>6</span><div><b>Print or send report cards</b><small>Per term, or the whole session from Exam Progress.</small></div></a>
				</div>
			</div>
		</div>

		<div class="rcs-nav" id="rcsNav">
			<button type="button" class="rcs-btn ghost" id="rcsBack"><i class="fas fa-arrow-left"></i> Back</button>
			<button type="button" class="rcs-btn primary" id="rcsNext">Next <i class="fas fa-arrow-right"></i></button>
		</div>
	</div>
	<?php echo form_close(); ?>
<?php } ?>
</div>

<?php if ($branch_id) { ?>
<script>
(function ($) {
	var presets = <?=json_encode($presets, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)?>;
	var canSave = <?=$canSave ? 'true' : 'false'?>;
	var step = 0, last = 4, scaleKey = null;
	var $form = $('#rcsForm');
	var esc = function (s) { return $('<div>').text(s == null ? '' : String(s)).html(); };

	function termRow(name) {
		return '<div class="rcs-row term"><span class="num"></span><input type="text" class="form-control" name="terms[]" value="' + esc(name) + '" placeholder="Term name"><button type="button" class="rcs-del" title="Remove"><i class="far fa-trash-alt"></i></button></div>';
	}
	function assessRow(a, i) {
		return '<div class="rcs-row assess"><span class="num"></span>' +
			'<input type="text" class="form-control a-name" name="assessments[' + i + '][name]" value="' + esc(a[0]) + '" placeholder="Column name, e.g. 1st CA">' +
			'<input type="number" class="form-control a-full" name="assessments[' + i + '][full]" value="' + esc(a[1]) + '" min="1" step="any" placeholder="Full">' +
			'<input type="number" class="form-control" name="assessments[' + i + '][pass]" value="' + esc(a[2]) + '" min="0" step="any" placeholder="Pass">' +
			'<button type="button" class="rcs-del" title="Remove"><i class="far fa-trash-alt"></i></button></div>';
	}
	function gradeRow(g, i) {
		return '<div class="rcs-row grade">' +
			'<input type="text" class="form-control" name="grades[' + i + '][name]" value="' + esc(g[0]) + '" placeholder="A1">' +
			'<input type="text" class="form-control" name="grades[' + i + '][point]" value="' + esc(g[1]) + '" placeholder="Point">' +
			'<input type="number" class="form-control" name="grades[' + i + '][low]" value="' + esc(g[2]) + '" min="0" max="100" placeholder="From">' +
			'<input type="number" class="form-control" name="grades[' + i + '][high]" value="' + esc(g[3]) + '" min="0" max="100" placeholder="To">' +
			'<input type="text" class="form-control g-remark" name="grades[' + i + '][remark]" value="' + esc(g[4]) + '" placeholder="Remark">' +
			'<button type="button" class="rcs-del" title="Remove"><i class="far fa-trash-alt"></i></button></div>';
	}
	var uid = 1000;
	function renumber() {
		$('#rcsTerms .num, #rcsAssess .num').each(function () { $(this).text($(this).closest('.rcs-row').index() + 1); });
		var total = 0;
		$('#rcsAssess .a-full').each(function () { total += parseFloat(this.value) || 0; });
		$('#rcsTotal').toggleClass('warn', total !== 100)
			.html(total === 100 ? '<i class="fas fa-check-circle"></i> Columns add up to 100' : '<i class="fas fa-exclamation-circle"></i> Columns add up to ' + total + '. Most schools use 100.');
	}
	function loadScale(p, key) {
		scaleKey = key;
		$('#rcsGrades').html($.map(p.scales[key].grades, gradeRow).join(''));
		$('#rcsScalePick button').removeClass('is-on').filter('[data-scale="' + key + '"]').addClass('is-on');
	}
	function loadPreset(key) {
		var p = presets[key] || presets.nigeria;
		$('#rcsTerms').html($.map(p.terms, termRow).join(''));
		$('#rcsAssess').html($.map(p.assessments, assessRow).join(''));
		$('#rcsMidOn').prop('checked', p.midterm.on);
		$form.find('[name=midterm_name]').val(p.midterm.name);
		$form.find('[name=midterm_full]').val(p.midterm.full);
		$form.find('[name=midterm_pass]').val(p.midterm.pass);
		$form.find('[name=type_id]').val(p.type);
		$('#rcsScalePick').html($.map(p.scales, function (s, k) { return '<button type="button" data-scale="' + k + '">' + esc(s.label) + '</button>'; }).join(''))
			.data('preset', p);
		loadScale(p, Object.keys(p.scales)[0]);
		$('#rcsMid').toggle(p.midterm.on);
		renumber();
	}
	function review() {
		var terms = $form.find('[name="terms[]"]').map(function () { return $.trim(this.value); }).get().filter(Boolean);
		var cols = $('#rcsAssess .rcs-row').map(function () { var v = $(this).find('input'); return $.trim(v.eq(0).val()) ? esc(v.eq(0).val()) + ' (' + esc(v.eq(1).val()) + ')' : null; }).get();
		var mid = $('#rcsMidOn').is(':checked') ? $.trim($form.find('[name=midterm_name]').val()) : '';
		var exams = [];
		$.each(terms, function (_, t) { if (mid) exams.push(esc(t + ' ' + mid)); exams.push(esc(t + ' Examination')); });
		var grades = $('#rcsGrades .rcs-row').map(function () { var v = $(this).find('input'); return $.trim(v.eq(0).val()) ? '<b>' + esc(v.eq(0).val()) + '</b> ' + esc(v.eq(2).val()) + '–' + esc(v.eq(3).val()) : null; }).get();
		var li = function (a) { return '<ul><li>' + a.join('</li><li>') + '</li></ul>'; };
		$('#rcsReview').html(
			'<div><h4>Terms</h4>' + li($.map(terms, esc)) + '</div>' +
			'<div><h4>Exams</h4>' + li(exams) + '</div>' +
			'<div><h4>Score columns</h4>' + li(cols.concat(mid ? [esc(mid) + ' (' + esc($form.find('[name=midterm_full]').val()) + ', mid-term only)'] : [])) + '</div>' +
			'<div><h4>Grades</h4><p style="margin:0;font-size:13.5px;line-height:1.8">' + grades.join(' &middot; ') + '</p></div>');
	}
	function go(n) {
		step = Math.max(0, Math.min(last, n));
		$('.rcs-pane').removeClass('is-on').filter('[data-pane="' + step + '"]').addClass('is-on');
		$('.rcs-step').each(function (i) { $(this).toggleClass('is-on', i === step).toggleClass('is-done', i < step); });
		$('#rcsBack').css('visibility', step ? 'visible' : 'hidden');
		$('#rcsNext').html(step === last ? (canSave ? '<i class="fas fa-wand-magic-sparkles"></i> Create my setup' : '<i class="fas fa-lock"></i> View only') : 'Next <i class="fas fa-arrow-right"></i>')
			.prop('disabled', step === last && !canSave);
		if (step === last) review();
		$('#rcsErrors').hide();
	}

	$(document).on('change', '.rcs-preset input', function () {
		$('.rcs-preset').removeClass('is-on');
		$(this).closest('.rcs-preset').addClass('is-on');
		loadPreset(this.value);
	});
	$(document).on('click', '#rcsScalePick button', function () { loadScale($('#rcsScalePick').data('preset'), $(this).data('scale')); });
	$(document).on('click', '.rcs-del', function () { $(this).closest('.rcs-row').remove(); renumber(); });
	$(document).on('input', '#rcsAssess .a-full', renumber);
	$('#rcsMidOn').on('change', function () { $('#rcsMid').toggle(this.checked); });
	$('[data-add]').on('click', function () {
		var t = $(this).data('add'), i = uid++;
		if (t === 'term') $('#rcsTerms').append(termRow(''));
		if (t === 'assess') $('#rcsAssess').append(assessRow(['', '', ''], i));
		if (t === 'grade') $('#rcsGrades').append(gradeRow(['', '', '', '', ''], i));
		renumber();
	});
	$('.rcs-step').on('click', function () { if (step <= last) go($(this).data('go')); });
	$('#rcsBack').on('click', function () { go(step - 1); });
	$('#rcsNext').on('click', function () {
		if (step < last) return go(step + 1);
		var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating…');
		$.post($form.attr('action'), $form.serialize(), null, 'json').done(function (r) {
			if (r && r.status === 'success') {
				var m = r.made, bits = [];
				if (m.terms) bits.push(m.terms + ' term' + (m.terms > 1 ? 's' : ''));
				if (m.exams) bits.push(m.exams + ' exam' + (m.exams > 1 ? 's' : ''));
				if (m.assessments) bits.push(m.assessments + ' score column' + (m.assessments > 1 ? 's' : ''));
				if (m.grades) bits.push(m.grades + ' grades');
				$('#rcsDoneText').text((bits.length ? 'Added ' + bits.join(', ') + '.' : 'Everything was already in place.') + (m.kept ? ' ' + m.kept + ' existing exam(s) were kept.' : ''));
				$('.rcs-pane').removeClass('is-on').filter('[data-pane="5"]').addClass('is-on');
				$('.rcs-step').addClass('is-done').removeClass('is-on');
				$('#rcsNav').hide();
			} else {
				var errs = (r && r.errors) ? r.errors : [r && r.status === 'access_denied' ? 'You do not have permission to change the exam setup.' : 'Something went wrong. Please try again.'];
				$('#rcsErrors').html('<ul><li>' + $.map(errs, esc).join('</li><li>') + '</li></ul>').show();
				$btn.prop('disabled', false).html('<i class="fas fa-wand-magic-sparkles"></i> Create my setup');
			}
		}).fail(function (x) {
			var msg = x.status === 403 || /access/i.test(x.responseText || '') ? 'You do not have permission to change the exam setup.' : 'Could not reach the server. Please try again.';
			$('#rcsErrors').html(esc(msg)).show();
			$btn.prop('disabled', false).html('<i class="fas fa-wand-magic-sparkles"></i> Create my setup');
		});
	});

	loadPreset('nigeria');
	go(0);
})(jQuery);
</script>
<?php } ?>
