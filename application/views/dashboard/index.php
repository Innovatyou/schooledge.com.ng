<?php
// 3D dashboard for super admins, school admins, teachers and other staff.
// Every block still follows the role's widget permissions.
$d3 = function ($name, $vars = array()) {
	extract($vars);
	include APPPATH . 'views/dashboard/_d3_' . $name . '.php';
};
$can = function ($perm) { return get_permission($perm, 'is_view'); };
$symbol = $global_config['currency_symbol'];
$isTeacher = !empty($teacher_summary);

// headline numbers
$feeBilled = array_sum($fees_summary['total_fee']);
$feePaid = array_sum($fees_summary['total_paid']);
$feeDue = max(0, array_sum($fees_summary['total_due']));
$feeRate = $feeBilled > 0 ? (int) round($feePaid / $feeBilled * 100) : 0;
$todayPresent = 0;
if (!empty($weekend_attendance['student_att'])) {
	$last = end($weekend_attendance['student_att']);
	$todayPresent = (int) $last['y'];
}
$attRate = $get_total_student > 0 ? min(100, (int) round($todayPresent / $get_total_student * 100)) : 0;
$staffCount = $this->dashboard_model->getstaffcounter('', $school_id)['snumber'];
$teacherCount = $this->dashboard_model->getstaffcounter(3, $school_id)['snumber'];
if (!empty($school_id)) $this->db->where('branch_id', $school_id);
$parentCount = $this->db->select('id')->get('parent')->num_rows();

$firstName = explode(' ', trim($this->session->userdata('name')))[0];
$roleName = get_type_name_by_id('roles', loggedin_role_id());
$schoolLabel = empty($school_id) ? translate('all_branch') : get_type_name_by_id('branch', $school_id, 'school_name');
$sessionLabel = get_type_name_by_id('schoolyear', get_session_id(), 'school_year');

// hero
$actions = array();
if ($isTeacher) {
	if ($can('student_attendance')) $actions[] = array(base_url('attendance/student_entry'), 'fa-clipboard-check', translate('attendance'), true);
	if ($can('exam_mark')) $actions[] = array(base_url('exam/mark_entry'), 'fa-pen-nib', translate('mark_entry'));
	if ($can('homework')) $actions[] = array(base_url('homework'), 'fa-book-open', translate('homework'));
} else {
	if (get_permission('student', 'is_view')) $actions[] = array(base_url('student/add'), 'fa-user-plus', translate('admission'), true);
	if ($can('invoice')) $actions[] = array(base_url('fees/invoice_list'), 'fa-file-invoice-dollar', translate('fees'));
	if ($can('student_attendance')) $actions[] = array(base_url('attendance/student_entry'), 'fa-clipboard-check', translate('attendance'));
	if ($can('exam_mark')) $actions[] = array(base_url('exam/mark_entry'), 'fa-pen-nib', translate('mark_entry'));
}
if ($isTeacher) {
	$floats = array(
		array('blue', 'fa-chalkboard-teacher', count($today_classes), 'Periods today'),
		array('violet', 'fa-user-graduate', $teacher_summary['students'], 'Students you teach'),
		array('amber', 'fa-tasks', $teacher_summary['to_mark'], 'Homework to mark'),
	);
} else {
	$floats = array(
		array('violet', 'fa-user-graduate', number_format($get_total_student), 'Students enrolled'),
		array('mint', 'fa-check-circle', $attRate . '%', 'Present today'),
		array('amber', 'fa-wallet', $feeRate . '%', 'Fees collected'),
	);
}
?>
<?php $this->load->view('dashboard/_d3_assets'); ?>
<div class="d3 dashboard-page">
<?php if ($sqlMode == true) { ?>
	<div class="d3-alert"><i class="fas fa-exclamation-triangle"></i> <span>This School management system may not work properly because "ONLY_FULL_GROUP_BY" is enabled. <strong>Strongly recommended</strong>: ask your hosting provider to disable "ONLY_FULL_GROUP_BY" in sql_mode.</span></div>
<?php } ?>
<?php if (!is_superadmin_loggedin() && !empty($this->saas_model->getSubscriptionsExpiredNotification())) { ?>
	<div class="d3-alert"><i class="fas fa-exclamation-circle"></i> <span><?php echo $this->saas_model->getSubscriptionsExpiredNotification(); ?></span></div>
<?php } ?>

<?php $d3('hero', array(
	'heading' => 'Welcome back, <span>' . html_escape($firstName) . '</span>',
	'meta' => array_filter(array(
		array('fa-school', html_escape($schoolLabel)),
		$sessionLabel ? array('fa-calendar-alt', translate('session') . ' ' . html_escape($sessionLabel)) : null,
		$roleName ? array('fa-id-badge', html_escape($roleName)) : null,
	)),
	'actions' => $actions,
	'floats' => $floats,
)); ?>

<?php if ($isTeacher) { ?>
	<!-- the teacher's own day -->
	<div class="d3-kpis">
		<?php
		$d3('kpi', array('tone' => 'blue', 'icon' => 'fa-chalkboard', 'value' => $teacher_summary['classes'], 'label' => translate('class') . ' & ' . translate('section'), 'foot' => 'You teach or lead this session', 'i' => 0));
		$d3('kpi', array('tone' => 'violet', 'icon' => 'fa-book', 'value' => $teacher_summary['subjects'], 'label' => translate('subject'), 'foot' => 'Assigned to you', 'i' => 1));
		$d3('kpi', array('tone' => 'cyan', 'icon' => 'fa-user-graduate', 'value' => $teacher_summary['students'], 'label' => translate('students'), 'foot' => 'Across your classes', 'i' => 2));
		$d3('kpi', array('tone' => 'amber', 'icon' => 'fa-tasks', 'value' => $teacher_summary['to_mark'], 'label' => 'Homework to mark', 'foot' => $teacher_summary['homework'] . ' set this session', 'i' => 3, 'href' => $can('homework') ? base_url('homework') : ''));
		?>
	</div>
	<div class="d3-grid">
		<section class="d3-card d3-span-7 d3-reveal">
			<header class="d3-card-head">
				<div><h3>Your classes today</h3><p><?php echo date('l, j F'); ?></p></div>
				<span class="d3-chip tone-blue"><i class="fas fa-clock"></i> <?php echo count($today_classes); ?> periods</span>
			</header>
			<div class="d3-card-body">
				<?php $d3('timeline', array('rows' => $today_classes, 'who' => 'class')); ?>
			</div>
		</section>
		<section class="d3-card d3-span-5 d3-reveal" style="--i: 1">
			<header class="d3-card-head">
				<div><h3>Coming up</h3><p>Events in the next 30 days</p></div>
			</header>
			<div class="d3-card-body">
				<?php $d3('events', array('events' => $upcoming_events)); ?>
			</div>
		</section>
	</div>
<?php } ?>

<?php
// people
$people = array();
if ($can('employee_count_widget')) $people[] = array('tone' => 'violet', 'icon' => 'fa-users', 'value' => $staffCount, 'label' => translate('employee'), 'foot' => translate('total_strength'));
if ($can('student_count_widget')) $people[] = array('tone' => 'cyan', 'icon' => 'fa-user-graduate', 'value' => $get_total_student, 'label' => translate('students'), 'foot' => translate('total_strength'));
if ($can('parent_count_widget')) $people[] = array('tone' => 'amber', 'icon' => 'fa-user-friends', 'value' => $parentCount, 'label' => translate('parents'), 'foot' => translate('total_strength'));
if ($can('teacher_count_widget')) $people[] = array('tone' => 'mint', 'icon' => 'fa-chalkboard-teacher', 'value' => $teacherCount, 'label' => translate('teachers'), 'foot' => translate('total_strength'));
if ($people) { ?>
	<div class="d3-kpis">
		<?php foreach ($people as $i => $k) { $d3('kpi', $k + array('i' => $i)); } ?>
	</div>
<?php } ?>

<?php
$showFees = $can('annual_student_fees_summary_chart');
if ($showFees) { ?>
	<div class="d3-grid">
		<section class="d3-card d3-span-8 d3-reveal">
			<header class="d3-card-head">
				<div><h3><?php echo translate('annual_fee_summary'); ?></h3><p>Billed, collected and outstanding by due month, <?php echo date('Y'); ?></p></div>
				<span class="d3-chip tone-violet"><i class="fas fa-chart-area"></i> <?php echo date('Y'); ?></span>
			</header>
			<div class="d3-card-body"><div class="d3-chart"><canvas id="fees_graph"></canvas></div></div>
		</section>
		<section class="d3-card d3-span-4 d3-reveal" style="--i: 1">
			<header class="d3-card-head">
				<div><h3>Fee collection</h3><p>Share of this year's fees paid</p></div>
			</header>
			<div class="d3-card-body tone-mint">
				<div class="d3-ring" style="--p: <?php echo $feeRate; ?>">
					<svg viewBox="0 0 120 120"><circle class="trk" cx="60" cy="60" r="50"/><circle class="bar" cx="60" cy="60" r="50" pathLength="100"/></svg>
					<div class="d3-ring-val"><b data-count="<?php echo $feeRate; ?>" data-suffix="%">0%</b><small><?php echo translate('collected'); ?></small></div>
				</div>
				<div class="d3-ring-row">
					<div class="tone-violet"><b><?php echo $symbol . number_format($feeBilled); ?></b><small>Billed</small></div>
					<div class="tone-mint"><b><?php echo $symbol . number_format($feePaid); ?></b><small><?php echo translate('collected'); ?></small></div>
					<div class="tone-rose"><b><?php echo $symbol . number_format($feeDue); ?></b><small>Outstanding</small></div>
				</div>
			</div>
		</section>
	</div>
<?php } ?>

<?php
$showAtt = $can('weekend_attendance_inspection_chart');
$showQty = $can('student_quantity_pie_chart');
if ($showAtt || $showQty) { ?>
	<div class="d3-grid">
		<?php if ($showAtt) { ?>
		<section class="d3-card <?php echo $showQty ? 'd3-span-8' : 'd3-span-12'; ?> d3-reveal">
			<header class="d3-card-head">
				<div><h3><?php echo translate('weekend_attendance_inspection'); ?></h3><p>Students and staff marked present over the last 7 days</p></div>
				<span class="d3-chip tone-mint"><i class="fas fa-check-circle"></i> <?php echo $todayPresent; ?> present today</span>
			</header>
			<div class="d3-card-body"><div class="d3-chart"><canvas id="weekend_attendance"></canvas></div></div>
		</section>
		<?php } if ($showQty) { ?>
		<section class="d3-card <?php echo $showAtt ? 'd3-span-4' : 'd3-span-12'; ?> d3-reveal" style="--i: 1">
			<header class="d3-card-head">
				<div><h3><?php echo translate('student_quantity'); ?></h3><p>Enrolment by class</p></div>
			</header>
			<div class="d3-card-body"><div class="d3-chart" id="student_strength"></div></div>
		</section>
		<?php } ?>
	</div>
<?php } ?>

<?php
// activity this month
$activity = array();
if ($can('admission_count_widget')) $activity[] = array('tone' => 'blue', 'icon' => 'far fa-address-card', 'value' => $get_monthly_admission, 'label' => translate('admission'), 'foot' => translate('interval_month'));
if ($can('voucher_count_widget')) $activity[] = array('tone' => 'rose', 'icon' => 'fa-money-check-alt', 'value' => $get_voucher, 'label' => translate('voucher'), 'foot' => translate('interval_month'));
if ($can('transport_count_widget') && moduleIsEnabled('transport')) $activity[] = array('tone' => 'amber', 'icon' => 'fa-bus', 'value' => $get_transport_route, 'label' => translate('transport'), 'foot' => translate('total_route'));
if ($can('hostel_count_widget') && moduleIsEnabled('hostel')) {
	if (!empty($school_id)) $this->db->where('branch_id', $school_id);
	$activity[] = array('tone' => 'slate', 'icon' => 'fa-warehouse', 'value' => $this->db->select('id')->get('hostel_room')->num_rows(), 'label' => translate('hostel'), 'foot' => translate('total_room'));
}
if ($activity) { ?>
	<div class="d3-kpis">
		<?php foreach ($activity as $i => $k) { $d3('kpi', $k + array('i' => $i)); } ?>
	</div>
<?php } ?>

<?php
$showCash = $can('monthly_income_vs_expense_chart');
$showStuBday = $can('student_birthday_widget');
$showStaffBday = $can('staff_birthday_widget');
?>
	<div class="d3-grid">
		<section class="d3-card d3-span-8 d3-reveal">
			<header class="d3-card-head">
				<div><h3><?php echo translate('event'); ?> <?php echo translate('calendar'); ?></h3><p>Click an event for details</p></div>
			</header>
			<div class="d3-card-body"><div id="event_calendar"></div></div>
		</section>
		<div class="d3-span-4" style="display: grid; gap: 20px; align-content: start;">
			<?php if ($showCash) { ?>
			<section class="d3-card d3-reveal" style="--i: 1">
				<header class="d3-card-head">
					<div><h3><?php echo translate('income_vs_expense_of') . ' ' . translate(strtolower(date('F'))); ?></h3><p>From the cash book</p></div>
				</header>
				<div class="d3-card-body"><div class="d3-chart is-short" id="cash_book_transaction"></div></div>
			</section>
			<?php } ?>
			<?php if (!$isTeacher) { ?>
			<section class="d3-card d3-reveal" style="--i: 2">
				<header class="d3-card-head">
					<div><h3>Coming up</h3><p>Events in the next 30 days</p></div>
				</header>
				<div class="d3-card-body"><?php $d3('events', array('events' => $upcoming_events)); ?></div>
			</section>
			<?php } ?>
			<?php if ($showStuBday || $showStaffBday) {
				$bdays = array();
				if ($showStuBday) {
					$this->db->select('student.id');
					$this->db->from('student');
					$this->db->join('enroll', 'enroll.student_id = student.id', 'inner');
					$this->db->where('enroll.session_id', get_session_id());
					if (!empty($school_id)) $this->db->where('branch_id', $school_id);
					$this->db->where('MONTH(student.birthday)', date('m'));
					$this->db->where('DAY(student.birthday)', date('d'));
					$this->db->group_by('student.id');
					$bdays[] = array('tone' => 'rose', 'icon' => 'fa-birthday-cake', 'value' => count($this->db->get()->result()), 'label' => translate('student') . ' ' . translate('today_birthday'), 'foot' => translate('view') . ' ' . translate('list'), 'href' => base_url('birthday/student'));
				}
				if ($showStaffBday) {
					$this->db->select('id');
					if (!empty($school_id)) $this->db->where('branch_id', $school_id);
					$this->db->where('MONTH(birthday)', date('m'));
					$this->db->where('DAY(birthday)', date('d'));
					$bdays[] = array('tone' => 'violet', 'icon' => 'fa-gift', 'value' => count($this->db->get('staff')->result()), 'label' => translate('employee') . ' ' . translate('today_birthday'), 'foot' => translate('view') . ' ' . translate('list'), 'href' => base_url('birthday/staff'));
				}
				foreach ($bdays as $i => $k) { $d3('kpi', $k + array('i' => $i + 3)); }
			} ?>
		</div>
	</div>
</div>

<div class="zoom-anim-dialog modal-block modal-block-primary mfp-hide" id="modal">
	<section class="panel">
		<header class="panel-heading">
			<div class="panel-btn">
				<button onclick="fn_printElem('printResult')" class="btn btn-default btn-circle icon" ><i class="fas fa-print"></i></button>
			</div>
			<h4 class="panel-title"><i class="fas fa-info-circle"></i> <?=translate('event_details')?></h4>
		</header>
		<div class="panel-body">
			<div id="printResult" class=" pt-sm pb-sm">
				<div class="table-responsive">
					<table class="table table-bordered table-condensed text-dark tbr-top" id="ev_table"></table>
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
	var D3 = window.D3Dash;

	$('#event_calendar').fullCalendar({
		header: {
			left: 'prev,next,today',
			center: 'title',
			right: 'month,agendaWeek,listWeek'
		},
		firstDay: 1,
		height: 640,
		droppable: false,
		editable: true,
		timezone: 'UTC',
		lang: '<?php echo $language ?>',
		events: {
			url: "<?=base_url('event/get_events_list/'. $school_id)?>"
		},
		eventRender: function(event, element) {
			$(element).on("click", function() {
				viewEvent(event.id);
			});
			if(event.icon){
				element.find(".fc-title").prepend("<i class='fas fa-"+event.icon+"'></i> ");
			}
		}
	});

<?php if ($showFees) { ?>
	// annual fee summary: soft gradient areas
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
<?php } ?>

<?php if ($showAtt) { ?>
	// last 7 days of attendance
	var attCanvas = document.getElementById('weekend_attendance');
	var pluckY = function (rows) { return rows.map(function (r) { return r.y; }); };
	new Chart(attCanvas.getContext('2d'), {
		type: 'bar',
		data: {
			labels: <?php echo json_encode($weekend_attendance['days']); ?>,
			datasets: [{
				label: '<?php echo translate("student"); ?>',
				data: pluckY(<?php echo json_encode($weekend_attendance['student_att']); ?>),
				backgroundColor: D3.fill(attCanvas, D3.colors.violet, .95, .45),
				hoverBackgroundColor: D3.colors.violet,
				borderWidth: 0
			}, {
				label: '<?php echo translate("employee"); ?>',
				data: pluckY(<?php echo json_encode($weekend_attendance['employee_att']); ?>),
				backgroundColor: D3.fill(attCanvas, D3.colors.cyan, .95, .45),
				hoverBackgroundColor: D3.colors.cyan,
				borderWidth: 0
			}]
		},
		options: D3.options({ scales: { xAxes: [{ barPercentage: .7, categoryPercentage: .55 }] } })
	});
<?php } ?>

<?php if ($showQty) { ?>
	D3.donut(document.getElementById('student_strength'), <?php echo json_encode($student_by_class); ?>, { centerLabel: '<?php echo translate("students"); ?>' });
<?php } ?>

<?php if ($showCash) { ?>
	D3.donut(document.getElementById('cash_book_transaction'), <?php echo json_encode($income_vs_expense); ?>, {
		colors: [D3.colors.rose, D3.colors.mint],
		centerText: '<?php echo date('M'); ?>',
		centerLabel: '<?php echo translate("income_vs_expense"); ?>',
		formatter: '{b}: <?php echo $symbol; ?>{c} ({d}%)'
	});
<?php } ?>
})(jQuery);
</script>
