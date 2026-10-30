<?php
// 3D dashboard for students, and for parents looking at one of their children.
$d3 = function ($name, $vars = array()) {
	extract($vars);
	include APPPATH . 'views/dashboard/_d3_' . $name . '.php';
};
$symbol = $global_config['currency_symbol'];
$kidTones = array('violet', 'cyan', 'amber', 'mint', 'rose', 'blue');
?>
<?php $this->load->view('dashboard/_d3_assets'); ?>
<?php if (!$this->saas_model->checkSubscriptionValidity()) { ?>
<div class="d3">
	<div class="d3-alert"><i class="fas fa-exclamation-circle"></i> <span><?php echo $this->saas_model->getSubscriptionsExpiredNotification(); ?></span></div>
</div>
<?php } elseif (empty($student_id)) { ?>
<!-- parent with no child picked yet: choose one -->
<div class="d3 dashboard-page">
	<?php $d3('hero', array(
		'heading' => 'Hello, <span>' . html_escape(explode(' ', trim($this->session->userdata('name')))[0]) . '</span>',
		'meta' => array(array('fa-child', translate('my_children') . ': ' . count($children))),
		'actions' => array(),
		'floats' => array(),
	)); ?>
	<?php if (empty($children)) { ?>
	<div class="d3-card"><div class="d3-empty"><i class="fas fa-exclamation-triangle"></i> <?php echo translate('no_child_was_found'); ?></div></div>
	<?php } else { ?>
	<div class="d3-kids">
		<?php foreach ($children as $i => $row) { ?>
		<article class="d3-kid tone-<?php echo $kidTones[$i % 6]; ?> d3-reveal" style="--i: <?php echo $i; ?>" data-tilt="6">
			<span class="d3-kid-photo"><img src="<?php echo get_image_url('student', $row->photo); ?>" alt=""></span>
			<h3><?php echo html_escape($row->first_name . ' ' . $row->last_name); ?></h3>
			<p><?php echo html_escape($row->class_name . ' (' . $row->section_name . ')'); ?></p>
			<div class="d3-kid-facts">
				<div><small><?php echo translate('roll'); ?></small><b><?php echo html_escape($row->roll); ?></b></div>
				<div><small><?php echo translate('register_no'); ?></small><b><?php echo html_escape($row->register_no); ?></b></div>
			</div>
			<a href="<?php echo base_url('parents/select_child/' . $row->enroll_id); ?>" class="d3-go"><i class="fas fa-tachometer-alt"></i> <?php echo translate('dashboard'); ?></a>
		</article>
		<?php } ?>
	</div>
	<?php } ?>
</div>
<?php } else {
	$isParent = is_parent_loggedin();
	$name = $enroll ? $enroll->first_name : get_type_name_by_id('student', $student_id, 'first_name');
	$att = isset($attendance) ? $attendance : array('rate' => null, 'p' => 0, 'a' => 0, 'l' => 0, 'labels' => array(), 'present' => array(), 'absent' => array(), 'late' => array());
	$today = isset($today_classes) ? $today_classes : array();
	$homework = isset($open_homework) ? $open_homework : array();
	$exams = isset($upcoming_exams) ? $upcoming_exams : array();
	$nextExam = $exams ? $exams[0] : null;

	$meta = array();
	if ($enroll) {
		$meta[] = array('fa-school', html_escape($enroll->class_name . ' (' . $enroll->section_name . ')'));
		$meta[] = array('fa-award', translate('roll') . ' ' . html_escape($enroll->roll));
		$meta[] = array('fa-id-card', html_escape($enroll->register_no));
	}
	$sessionLabel = get_type_name_by_id('schoolyear', get_session_id(), 'school_year');
	if ($sessionLabel) $meta[] = array('fa-calendar-alt', translate('session') . ' ' . html_escape($sessionLabel));

	// parents with more than one child can hop between them from the banner
	$switch = '';
	if ($isParent && !empty($children) && count($children) > 1) {
		$switch .= '<div class="d3-switch">';
		foreach ($children as $kid) {
			$on = $enroll && $kid->enroll_id == $enroll->enroll_id;
			$switch .= '<a href="' . base_url('parents/select_child/' . $kid->enroll_id) . '"' . ($on ? ' class="is-on"' : '') . '><img src="' . get_image_url('student', $kid->photo) . '" alt=""> ' . html_escape($kid->first_name) . '</a>';
		}
		$switch .= '</div>';
	}

	$heading = $isParent
		? 'Hello, ' . html_escape(explode(' ', trim($this->session->userdata('name')))[0]) . '. Here\'s <span>' . html_escape($name) . '</span>'
		: 'Welcome back, <span>' . html_escape($name) . '</span>';
?>
<div class="d3 dashboard-page">
	<?php $d3('hero', array(
		'heading' => $heading,
		'avatar' => get_image_url('student', $enroll ? $enroll->photo : ''),
		'meta' => $meta,
		'actions' => array(
			array(base_url('userrole/report_card'), 'fa-file-alt', translate('report_card'), true),
			array(base_url('userrole/class_schedule'), 'fa-clock', translate('class_schedule')),
			array(base_url('userrole/homework'), 'fa-book-open', translate('homework')),
			array(base_url('userrole/invoice'), 'fa-wallet', translate('fees')),
		),
		'after' => $switch,
		'floats' => array(
			array('mint', 'fa-check-circle', $att['rate'] === null ? '—' : $att['rate'] . '%', 'Attendance this session'),
			array('violet', 'fa-wallet', $fee_totals['rate'] === null ? '—' : $fee_totals['rate'] . '%', 'Fees paid'),
			$nextExam
				? array('amber', 'fa-pen-nib', date('j M', strtotime($nextExam->exam_date)), 'Next paper: ' . html_escape($nextExam->subject_name))
				: array('amber', 'fa-book-open', count($homework), 'Homework due'),
		),
	)); ?>

	<div class="d3-kpis">
		<?php
		$d3('kpi', array('tone' => 'mint', 'icon' => 'fa-user-check', 'value' => (int) $att['rate'], 'suffix' => '%', 'label' => translate('attendance'), 'foot' => ($att['p'] + $att['l']) . ' of ' . ($att['p'] + $att['a'] + $att['l']) . ' days present', 'bar' => (int) $att['rate'], 'i' => 0, 'href' => base_url('userrole/attendance')));
		$d3('kpi', array('tone' => 'rose', 'icon' => 'fa-wallet', 'value' => $fee_totals['due'], 'prefix' => $symbol, 'label' => 'Fee balance', 'foot' => $symbol . number_format($fee_totals['paid']) . ' of ' . $symbol . number_format($fee_totals['total']) . ' paid', 'bar' => (int) $fee_totals['rate'], 'i' => 1, 'href' => base_url('userrole/invoice')));
		$d3('kpi', array('tone' => 'amber', 'icon' => 'fa-book-open', 'value' => count($homework), 'label' => translate('homework'), 'foot' => 'Still open', 'i' => 2, 'href' => base_url('userrole/homework')));
		$d3('kpi', array('tone' => 'violet', 'icon' => 'fa-book-reader', 'value' => $books_out, 'label' => translate('book_issued'), 'foot' => 'Out on loan now', 'i' => 3, 'href' => base_url('userrole/book')));
		?>
	</div>

	<div class="d3-grid">
		<section class="d3-card d3-span-7 d3-reveal">
			<header class="d3-card-head">
				<div><h3>Today's classes</h3><p><?php echo date('l, j F'); ?></p></div>
				<span class="d3-chip tone-blue"><i class="fas fa-clock"></i> <?php echo count(array_filter($today, function ($r) { return !$r->break; })); ?> lessons</span>
			</header>
			<div class="d3-card-body"><?php $d3('timeline', array('rows' => $today, 'who' => 'teacher')); ?></div>
		</section>
		<section class="d3-card d3-span-5 d3-reveal" style="--i: 1">
			<header class="d3-card-head">
				<div><h3><?php echo translate('attendance'); ?></h3><p>This session so far</p></div>
			</header>
			<div class="d3-card-body tone-mint">
				<?php if ($att['rate'] === null) { ?>
				<div class="d3-empty"><i class="fas fa-user-check"></i> No attendance has been taken yet.</div>
				<?php } else { ?>
				<div class="d3-ring" style="--p: <?php echo $att['rate']; ?>">
					<svg viewBox="0 0 120 120"><circle class="trk" cx="60" cy="60" r="50"/><circle class="bar" cx="60" cy="60" r="50" pathLength="100"/></svg>
					<div class="d3-ring-val"><b data-count="<?php echo $att['rate']; ?>" data-suffix="%">0%</b><small>present</small></div>
				</div>
				<div class="d3-ring-row">
					<div class="tone-mint"><b><?php echo $att['p']; ?></b><small><?php echo translate('present'); ?></small></div>
					<div class="tone-amber"><b><?php echo $att['l']; ?></b><small><?php echo translate('late'); ?></small></div>
					<div class="tone-rose"><b><?php echo $att['a']; ?></b><small><?php echo translate('absent'); ?></small></div>
				</div>
				<?php } ?>
			</div>
		</section>
	</div>

	<div class="d3-grid">
		<section class="d3-card d3-span-8 d3-reveal">
			<header class="d3-card-head">
				<div><h3><?php echo translate('attendance'); ?> by month</h3><p>Days present, late and absent</p></div>
			</header>
			<div class="d3-card-body">
				<?php if (empty($att['labels'])) { ?>
				<div class="d3-empty"><i class="fas fa-chart-bar"></i> The chart fills in once attendance is taken.</div>
				<?php } else { ?>
				<div class="d3-chart"><canvas id="attendance_overview"></canvas></div>
				<?php } ?>
			</div>
		</section>
		<div class="d3-span-4" style="display: grid; gap: 20px; align-content: start;">
			<section class="d3-card d3-reveal" style="--i: 1">
				<header class="d3-card-head"><div><h3>Upcoming exams</h3><p>Next papers on the timetable</p></div></header>
				<div class="d3-card-body">
					<?php if (empty($exams)) { ?>
					<div class="d3-empty"><i class="fas fa-pen-nib"></i> No exam papers scheduled.</div>
					<?php } else { ?>
					<ul class="d3-list">
						<?php foreach ($exams as $i => $x) { ?>
						<li class="tone-<?php echo $kidTones[$i % 6]; ?>">
							<span class="d3-when"><?php echo date('j', strtotime($x->exam_date)); ?><small><?php echo date('M', strtotime($x->exam_date)); ?></small></span>
							<span class="d3-what"><b><?php echo html_escape($x->subject_name); ?></b><small><?php echo html_escape($x->exam_name); ?><?php echo $x->time_start ? ' &middot; ' . date('g:i A', strtotime($x->time_start)) : ''; ?></small></span>
							<span class="d3-dot"></span>
						</li>
						<?php } ?>
					</ul>
					<?php } ?>
				</div>
			</section>
			<section class="d3-card d3-reveal" style="--i: 2">
				<header class="d3-card-head"><div><h3><?php echo translate('homework'); ?> due</h3><p>Submit before the date shown</p></div></header>
				<div class="d3-card-body">
					<?php if (empty($homework)) { ?>
					<div class="d3-empty"><i class="fas fa-check-double"></i> All caught up.</div>
					<?php } else { ?>
					<ul class="d3-list">
						<?php foreach ($homework as $i => $h) { ?>
						<li class="tone-amber">
							<span class="d3-when"><?php echo date('j', strtotime($h->date_of_submission)); ?><small><?php echo date('M', strtotime($h->date_of_submission)); ?></small></span>
							<span class="d3-what"><b><?php echo html_escape($h->subject_name); ?></b><small>Due <?php echo date('l', strtotime($h->date_of_submission)); ?></small></span>
							<span class="d3-dot"></span>
						</li>
						<?php } ?>
					</ul>
					<?php } ?>
				</div>
			</section>
		</div>
	</div>

	<div class="d3-grid">
		<section class="d3-card d3-span-8 d3-reveal">
			<header class="d3-card-head">
				<div><h3><?php echo translate('my_annual_fee_summary'); ?></h3><p>Billed, collected and outstanding by due month, <?php echo date('Y'); ?></p></div>
			</header>
			<div class="d3-card-body"><div class="d3-chart"><canvas id="fees_graph"></canvas></div></div>
		</section>
		<section class="d3-card d3-span-4 d3-reveal" style="--i: 1">
			<header class="d3-card-head"><div><h3>Coming up</h3><p>School events in the next 30 days</p></div></header>
			<div class="d3-card-body"><?php $d3('events', array('events' => $upcoming_events)); ?></div>
		</section>
	</div>

	<div class="d3-grid">
		<section class="d3-card d3-span-12 d3-reveal">
			<header class="d3-card-head"><div><h3><?php echo translate('event'); ?> <?php echo translate('calendar'); ?></h3><p>Click an event for details</p></div></header>
			<div class="d3-card-body"><div id="event_calendar"></div></div>
		</section>
	</div>
</div>

<div class="zoom-anim-dialog modal-block modal-block-primary mfp-hide" id="modal">
	<section class="panel">
		<header class="panel-heading">
			<h4 class="panel-title"><i class="fas fa-info-circle"></i> <?=translate('event_details')?></h4>
			<div class="panel-btn">
				<button id="print" class="btn btn-default btn-circle icon"><i class="fas fa-print"></i></button>
			</div>
		</header>
		<div class="panel-body">
			<div id="printResult" class="pt-sm pb-sm">
				<div class="table-responsive">
					<table class="table table-bordered table-condensed text-dark mb-sm tbr-top" id="ev_table"></table>
				</div>
			</div>
		</div>
		<footer class="panel-footer">
			<div class="row">
				<div class="col-md-12 text-right">
					<button class="btn btn-default modal-dismiss"><?=translate('close')?></button>
				</div>
			</div>
		</footer>
	</section>
</div>

<script type="application/javascript">
	(function($) {
		"use strict";
		var D3 = window.D3Dash;

		$('#event_calendar').fullCalendar({
			header: {
				left: 'prev,next,today',
				center: 'title',
				right: 'month,agendaWeek,listWeek'
			},
			firstDay: 1,
			height: 620,
			droppable: false,
			editable: true,
			events: {
				url: "<?=base_url('event/get_events_list');?>"
			},
			buttonText: {
				today: 'Today',
				month: 'Month',
				week: 'Week',
				day: 'Day',
				list: 'List'
			},
			eventRender: function(event, element) {
				$(element).on("click", function() {
					view_event(event.id);
				});
				if(event.icon){
					element.find(".fc-title").prepend("<i class='fas fa-"+event.icon+"'></i> ");
				}
			}
		});

		// own annual fee summary
		var feesCanvas = document.getElementById('fees_graph');
		new Chart(feesCanvas.getContext('2d'), {
			type: 'line',
			data: {
				labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
				datasets: [{
					label: '<?php echo translate("total"); ?>',
					data: <?php echo json_encode($fees_summary['total_fee']); ?>,
					borderColor: D3.colors.violet,
					backgroundColor: D3.fill(feesCanvas, D3.colors.violet, .35),
					borderWidth: 3, lineTension: .4, pointRadius: 0, pointHoverRadius: 6, pointHoverBorderWidth: 3, pointHoverBackgroundColor: '#fff'
				}, {
					label: '<?php echo translate("collected"); ?>',
					data: <?php echo json_encode($fees_summary['total_paid']); ?>,
					borderColor: D3.colors.cyan,
					backgroundColor: D3.fill(feesCanvas, D3.colors.cyan, .3),
					borderWidth: 3, lineTension: .4, pointRadius: 0, pointHoverRadius: 6, pointHoverBorderWidth: 3, pointHoverBackgroundColor: '#fff'
				}, {
					label: '<?php echo translate("remaining"); ?>',
					data: <?php echo json_encode($fees_summary['total_due']); ?>,
					borderColor: D3.colors.rose,
					backgroundColor: 'transparent',
					borderDash: [6, 5], borderWidth: 2, lineTension: .4, pointRadius: 0, pointHoverRadius: 5, pointHoverBackgroundColor: '#fff'
				}]
			},
			options: D3.options({
				tooltips: { callbacks: { label: function (t, d) { return ' ' + d.datasets[t.datasetIndex].label + ': <?php echo $symbol; ?>' + Number(t.yLabel).toLocaleString(); } } },
				scales: { yAxes: [{ ticks: { callback: function (v) { return '<?php echo $symbol; ?>' + Number(v).toLocaleString(); } } }] }
			})
		});

		// attendance month by month (stacked)
		var attCanvas = document.getElementById('attendance_overview');
		if (attCanvas) {
			new Chart(attCanvas.getContext('2d'), {
				type: 'bar',
				data: {
					labels: <?php echo json_encode($att['labels']); ?>,
					datasets: [{
						label: '<?php echo translate("total_present"); ?>',
						data: <?php echo json_encode($att['present']); ?>,
						backgroundColor: D3.fill(attCanvas, D3.colors.mint, .95, .55), hoverBackgroundColor: D3.colors.mint, borderWidth: 0
					}, {
						label: '<?php echo translate("total_late"); ?>',
						data: <?php echo json_encode($att['late']); ?>,
						backgroundColor: D3.alpha(D3.colors.amber, .85), hoverBackgroundColor: D3.colors.amber, borderWidth: 0
					}, {
						label: '<?php echo translate("total_absent"); ?>',
						data: <?php echo json_encode($att['absent']); ?>,
						backgroundColor: D3.alpha(D3.colors.rose, .85), hoverBackgroundColor: D3.colors.rose, borderWidth: 0
					}]
				},
				options: D3.options({ scales: { xAxes: [{ stacked: true, barPercentage: .55 }], yAxes: [{ stacked: true }] } })
			});
		}

		function view_event(id) {
			$.ajax({
				url: "<?=base_url('event/getDetails')?>",
				type: 'POST',
				data: {
					event_id: id
				},
				success: function (data) {
					$('#ev_table').html(data);
					mfp_modal('#modal');
				}
			});
		}
	})(jQuery);
</script>
<?php } ?>
