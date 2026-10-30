<?php
// Today's periods. Params: $rows (getTodayClasses), $who ('class' shows the class, 'teacher' the teacher)
$now = date('H:i:s');
if (empty($rows)) { ?>
<div class="d3-empty"><i class="fas fa-mug-hot"></i> No classes on the timetable today.</div>
<?php } else { ?>
<ol class="d3-list">
	<?php foreach ($rows as $r) {
		$state = ($now >= $r->time_start && $now < $r->time_end) ? 'is-now' : ($now >= $r->time_end ? 'is-done' : '');
		if ($r->break) $state .= ' is-break'; ?>
	<li class="<?php echo trim($state); ?>">
		<time><?php echo date('g:i', strtotime($r->time_start)); ?><small><?php echo date('A', strtotime($r->time_start)); ?></small></time>
		<span class="d3-what">
			<?php if ($r->break) { ?>
			<b><?php echo translate('break'); ?></b>
			<small><?php echo date('g:i', strtotime($r->time_start)) . ' – ' . date('g:i A', strtotime($r->time_end)); ?></small>
			<?php } else { ?>
			<b><?php echo html_escape($r->subject_name ?: translate('class')); ?></b>
			<small><?php
				$bits = array($who === 'teacher' ? html_escape($r->teacher_name) : html_escape($r->class_name . ' ' . $r->section_name));
				if (!empty($r->class_room)) $bits[] = translate('class_room') . ' ' . html_escape($r->class_room);
				$bits[] = date('g:i', strtotime($r->time_start)) . ' – ' . date('g:i A', strtotime($r->time_end));
				echo implode(' &middot; ', array_filter($bits));
			?></small>
			<?php } ?>
		</span>
	</li>
	<?php } ?>
</ol>
<?php }
