<link rel="stylesheet" href="<?=base_url('assets/css/celebrate.css?v=' . filemtime(FCPATH . 'assets/css/celebrate.css'))?>">
<script src="<?=base_url('assets/js/celebrate.js?v=' . filemtime(FCPATH . 'assets/js/celebrate.js'))?>"></script>
<?php
$medals = array(1 => '🥇', 2 => '🥈', 3 => '🥉');
$mine = isset($mine) ? $mine : array();
?>
<?php if (empty($wall)) { ?>
<section class="panel">
	<div class="panel-body">
		<form method="get" class="form-inline">
			<?php echo form_dropdown('branch_id', $this->app_lib->getSelectList('branch'), $branch_id, "class='form-control' onchange='this.form.submit()'"); ?>
			<span class="text-muted ml-sm">Choose a school to see its celebrations.</span>
		</form>
	</div>
</section>
<?php } else { ?>
<div class="cb-wall">
	<div class="cb-hero">
		<h3>🎉 Celebrations</h3>
		<p><?=date('l, j F Y')?> &middot; birthdays, top performers, best teachers and everything worth cheering about.</p>
		<div class="cb-hero-actions">
		<?php if (!empty($mine)) { ?>
			<button type="button" class="cb-btn" id="cbMine">🎁 You have <?=count($mine)?> celebration<?=count($mine) == 1 ? '' : 's'?> — open</button>
		<?php } ?>
			<button type="button" class="cb-btn" onclick="SkoolCelebrate.play('fireworks')">🎆 Launch fireworks</button>
		</div>
	</div>

	<div class="cb-section">
		<h4>🎂 Birthdays today <small><?=count($wall['birthdays'])?></small></h4>
		<?php if (empty($wall['birthdays'])) { ?>
		<p class="cb-empty">No birthdays today.</p>
		<?php } else { ?>
		<div class="cb-people">
			<?php foreach ($wall['birthdays'] as $b) { ?>
			<div class="cb-person">
				<img src="<?=html_escape($b['photo'])?>" alt="" loading="lazy">
				<b title="<?=html_escape($b['name'])?>"><?=html_escape($b['name'])?></b>
				<small><?=html_escape($b['detail'] !== '' ? $b['detail'] : ($b['kind'] == 'staff' ? translate('employee') : translate('student')))?></small>
				<span class="cb-tag"><?=$b['age'] ? 'Turns ' . $b['age'] : 'Happy birthday!'?></span>
			</div>
			<?php } ?>
		</div>
		<?php } ?>
	</div>

	<div class="cb-section">
		<h4>🏆 Top performers <?php if (!empty($wall['top_performers'])) { ?><small><?=html_escape($wall['top_performers']['exam'])?></small><?php } ?></h4>
		<?php if (empty($wall['top_performers']['classes'])) { ?>
		<p class="cb-empty">The top three in each class appear here once exam results are published and positions are generated.</p>
		<?php } else { ?>
		<div class="cb-podiums">
			<?php foreach ($wall['top_performers']['classes'] as $class) { ?>
			<div class="cb-podium">
				<h5><?=html_escape($class['class'])?></h5>
				<ol>
					<?php foreach ($class['students'] as $s) { ?>
					<li><span class="cb-medal"><?=$medals[$s['rank']]?></span><img src="<?=html_escape($s['photo'])?>" alt="" loading="lazy"><span><?=html_escape($s['name'])?></span></li>
					<?php } ?>
				</ol>
			</div>
			<?php } ?>
		</div>
		<?php } ?>
	</div>

	<div class="cb-two">
		<div class="cb-section">
			<h4>🍎 Best teachers</h4>
			<?php if (empty($wall['best_teachers'])) { ?>
			<p class="cb-empty">No best teacher picked right now.</p>
			<?php } foreach ($wall['best_teachers'] as $p) { ?>
			<div class="cb-feature">
				<img src="<?=html_escape($p['photo'])?>" alt="" loading="lazy">
				<div>
					<div class="cb-kicker"><?=html_escape($p['title'])?></div>
					<b><?=html_escape($p['name'])?></b>
					<small><?=html_escape($p['detail'])?></small>
					<?php if ($p['message'] !== '') { ?><p><?=html_escape($p['message'])?></p><?php } ?>
				</div>
			</div>
			<?php } ?>
		</div>
		<div class="cb-section">
			<h4>⭐ Star students</h4>
			<?php if (empty($wall['star_students'])) { ?>
			<p class="cb-empty">No star student picked right now.</p>
			<?php } foreach ($wall['star_students'] as $p) { ?>
			<div class="cb-feature">
				<img src="<?=html_escape($p['photo'])?>" alt="" loading="lazy">
				<div>
					<div class="cb-kicker"><?=html_escape($p['title'])?></div>
					<b><?=html_escape($p['name'])?></b>
					<small><?=html_escape($p['detail'])?></small>
					<?php if ($p['message'] !== '') { ?><p><?=html_escape($p['message'])?></p><?php } ?>
				</div>
			</div>
			<?php } ?>
		</div>
	</div>

	<?php if (!empty($wall['events']) || !empty($wall['anniversaries'])) { ?>
	<div class="cb-section">
		<h4>🎈 School celebrations</h4>
		<?php foreach ($wall['events'] as $p) { ?>
		<div class="cb-feature">
			<div class="cb-feature-emoji">🎉</div>
			<div>
				<b><?=html_escape($p['title'])?></b>
				<?php if ($p['message'] !== '') { ?><p><?=html_escape($p['message'])?></p><?php } ?>
			</div>
		</div>
		<?php } foreach ($wall['anniversaries'] as $a) { ?>
		<div class="cb-feature">
			<img src="<?=html_escape($a['photo'])?>" alt="" loading="lazy">
			<div>
				<div class="cb-kicker">Work anniversary</div>
				<b><?=html_escape($a['name'])?></b>
				<small><?=$a['years']?> year<?=$a['years'] == 1 ? '' : 's'?> at our school<?=$a['detail'] !== '' ? ' &middot; ' . html_escape($a['detail']) : ''?></small>
			</div>
		</div>
		<?php } ?>
	</div>
	<?php } ?>

<?php if ($can_add) { ?>
	<section class="panel mb-none">
		<header class="panel-heading">
			<h4 class="panel-title"><i class="fas fa-award"></i> Pick a best teacher, star student or celebration</h4>
		</header>
		<?php echo form_open('celebration/save', array('class' => 'form-horizontal form-bordered frm-submit')); ?>
		<div class="panel-body">
			<div class="form-group">
				<label class="col-md-3 control-label"><?=translate('type')?> <span class="required">*</span></label>
				<div class="col-md-6">
					<?php echo form_dropdown('type', $types, 'best_teacher', "class='form-control' id='cbType' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'"); ?>
					<span class="error"></span>
				</div>
			</div>
			<div class="form-group cb-for cb-for-best_teacher">
				<label class="col-md-3 control-label"><?=translate('teacher')?> <span class="required">*</span></label>
				<div class="col-md-6">
					<?php echo form_dropdown('staff_id', $staff_list, '', "class='form-control' data-plugin-selectTwo data-width='100%'"); ?>
					<span class="error"></span>
				</div>
			</div>
			<div class="form-group cb-for cb-for-star_student" style="display: none;">
				<label class="col-md-3 control-label"><?=translate('class')?> <span class="required">*</span></label>
				<div class="col-md-6">
					<?php echo form_dropdown('class_id', $this->app_lib->getClass($branch_id), '', "class='form-control' id='cbClass' data-plugin-selectTwo data-width='100%'"); ?>
					<span class="error"></span>
				</div>
			</div>
			<div class="form-group cb-for cb-for-star_student" style="display: none;">
				<label class="col-md-3 control-label"><?=translate('student')?> <span class="required">*</span></label>
				<div class="col-md-6">
					<?php echo form_dropdown('student_id', array('' => translate('select_class_first')), '', "class='form-control' id='cbStudent' data-plugin-selectTwo data-width='100%'"); ?>
					<span class="error"></span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-3 control-label"><?=translate('title')?> <span class="required">*</span></label>
				<div class="col-md-6">
					<input type="text" class="form-control" name="title" id="cbTitle" value="Teacher of the Month" maxlength="255" />
					<span class="error"></span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-3 control-label"><?=translate('message')?></label>
				<div class="col-md-6">
					<textarea class="form-control" name="message" rows="2" placeholder="e.g. For excellent results and going the extra mile for every student."></textarea>
					<span class="error"></span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-3 control-label">Show from <span class="required">*</span></label>
				<div class="col-md-3">
					<input type="text" class="form-control" name="start_date" data-plugin-datepicker data-plugin-options='{"todayHighlight" : true}' value="<?=date('Y-m-d')?>" />
					<span class="error"></span>
				</div>
				<label class="col-md-1 control-label">to <span class="required">*</span></label>
				<div class="col-md-2">
					<input type="text" class="form-control" name="end_date" data-plugin-datepicker data-plugin-options='{"todayHighlight" : true}' value="<?=date('Y-m-t')?>" />
					<span class="error"></span>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-3 control-label">Animation</label>
				<div class="col-md-6">
					<?php foreach ($themes as $value => $label) { ?>
					<label class="radio-inline"><input type="radio" name="theme" value="<?=$value?>" <?=$value == 'fireworks' ? 'checked' : ''?>> <?=$label?></label>
					<?php } ?>
					<button type="button" class="btn btn-default btn-xs ml-sm" onclick="SkoolCelebrate.play($('input[name=theme]:checked').val())"><i class="fas fa-play"></i> Preview</button>
				</div>
			</div>
		</div>
		<footer class="panel-footer">
			<div class="row">
				<div class="col-md-offset-3 col-md-3">
					<button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
						<i class="fas fa-plus-circle"></i> <?=translate('save')?>
					</button>
				</div>
			</div>
		</footer>
		<?php echo form_close(); ?>
	</section>

	<?php if (!empty($picks)) { ?>
	<section class="panel">
		<header class="panel-heading"><h4 class="panel-title"><i class="fas fa-list"></i> All picks and celebrations</h4></header>
		<div class="panel-body">
			<div class="table-responsive">
				<table class="table table-bordered table-condensed table-hover mb-none">
					<thead><tr><th><?=translate('type')?></th><th><?=translate('title')?></th><th><?=translate('name')?></th><th>Showing</th><th class="text-center"><?=translate('action')?></th></tr></thead>
					<tbody>
					<?php foreach ($picks as $p) { $live = $p['start_date'] <= date('Y-m-d') && $p['end_date'] >= date('Y-m-d'); ?>
						<tr>
							<td><?=html_escape($types[$p['type']])?></td>
							<td><?=html_escape($p['title'])?></td>
							<td><?=html_escape($p['name'] !== '' ? $p['name'] : '—')?></td>
							<td><?=_d($p['start_date'])?> – <?=_d($p['end_date'])?> <?=$live ? '<span class="label label-success">Live</span>' : ''?></td>
							<td class="text-center"><?php if (get_permission('celebrations', 'is_delete')) { echo btn_delete('celebration/delete/' . $p['id']); } ?></td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
	</section>
	<?php } ?>
<?php } ?>
</div>
<?php } ?>

<script type="text/javascript">
	(function () {
		var mine = <?=json_encode(array_values($mine), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
		var button = document.getElementById('cbMine');
		if (button) {
			button.addEventListener('click', function () { SkoolCelebrate.show(mine); });
		}
	})();
<?php if ($can_add) { ?>
	$(document).ready(function () {
		var titles = { best_teacher: 'Teacher of the Month', star_student: 'Star Student of the Week', custom: '' };
		$('#cbType').on('change', function () {
			var type = $(this).val();
			$('.cb-for').hide();
			$('.cb-for-' + type).show();
			var title = $('#cbTitle');
			if (title.val() === '' || $.inArray(title.val(), $.map(titles, function (v) { return v; })) !== -1) {
				title.val(titles[type]).attr('placeholder', type === 'custom' ? "e.g. Founders' Day, Sports Day winners: Red House" : '');
			}
		});
		$('#cbClass').on('change', function () {
			$.ajax({
				url: base_url + 'ajax/getStudentByClass',
				type: 'POST',
				data: { class_id: $(this).val() },
				success: function (data) { $('#cbStudent').html(data); }
			});
		});
	});
<?php } ?>
</script>
