<section class="panel">
	<div class="panel-body">
		<h3 class="mt-0"><?=translate('user_guide')?></h3>
		<p class="text-muted">
			<?php if (is_superadmin_loggedin()) { ?>
				A quick reference for running the platform: manage schools, subscriptions and system-wide settings from here, or open a school's own dashboard to see things from an administrator's point of view.
			<?php } else { ?>
				A quick reference for the everyday tasks of running your school on this system &mdash; admissions, academics, attendance, exams, fees and communication.
			<?php } ?>
		</p>

		<div class="callout callout-info" style="border-left:4px solid #4099ff;background:#f4f8ff;padding:15px 20px;margin-bottom:25px;border-radius:4px;">
			<h4 class="mt-0"><i class="fas fa-rocket"></i> Quick Start</h4>
			<ol class="mb-0">
				<?php if (is_superadmin_loggedin()) { ?>
				<li>Create a school under <strong>Branch</strong> and set its basic details.</li>
				<li>Set up global defaults under <strong>Settings &rarr; Global Settings</strong>.</li>
				<li>Create roles and assign permissions under <strong>Settings &rarr; Role &amp; Permission</strong>.</li>
				<li>Open a school from the <strong>Dashboard</strong> branch switcher to configure or support it directly.</li>
				<?php } else { ?>
				<li>Finish your school profile under <strong>Settings &rarr; School Settings</strong>.</li>
				<li>Add <strong>Classes</strong> and <strong>Sections</strong> under Academics.</li>
				<li>Add teaching and non-teaching staff under <strong>Employee</strong>.</li>
				<li>Admit students via <strong>Admission</strong>, or promote them from a previous session.</li>
				<li>Set up fee groups and types, then start collecting payments under <strong>Fees</strong>.</li>
				<li>Take daily <strong>Attendance</strong> and schedule <strong>Exams</strong> once classes are running.</li>
				<?php } ?>
			</ol>
		</div>

		<div class="panel-group" id="userguide-accordion">

			<div class="panel panel-accordion">
				<div class="panel-heading">
					<h4 class="panel-title">
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#userguide-accordion" href="#ug-admission">
							<i class="fas fa-user-graduate"></i> Admission &amp; Student Management
						</a>
					</h4>
				</div>
				<div id="ug-admission" class="accordion-body collapse in">
					<div class="panel-body">
						<ul>
							<li><strong>Reception</strong> logs enquiries and visitor follow-ups before a student formally applies.</li>
							<li><strong>Admission</strong> registers a new student, assigns them to a class/section and generates their admission number.</li>
							<li><strong>Student</strong> lists every enrolled student &mdash; use it to edit profiles, view documents, deactivate/transfer a student, or print ID cards.</li>
							<li><strong>Parents</strong> manages guardian accounts and which children are linked to them for portal access.</li>
							<li><strong>Student Promotion</strong> moves a whole class up to the next session in one action at year end.</li>
							<li><strong>Alumni</strong> keeps a record of students who have left or graduated.</li>
						</ul>
					</div>
				</div>
			</div>

			<div class="panel panel-accordion">
				<div class="panel-heading">
					<h4 class="panel-title">
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#userguide-accordion" href="#ug-hr">
							<i class="fas fa-users"></i> Employees &amp; HR
						</a>
					</h4>
				</div>
				<div id="ug-hr" class="accordion-body collapse">
					<div class="panel-body">
						<ul>
							<li><strong>Employee</strong> holds every staff profile, their role, department and login access.</li>
							<li><strong>Payroll</strong>, <strong>Advance Salary</strong> and <strong>Leave</strong> handle monthly pay runs, salary advances and leave requests/approvals.</li>
							<li><strong>Award</strong> records staff or student recognitions.</li>
							<li><strong>Certificate</strong> and <strong>Card Manage</strong> generate ID cards and certificates from templates.</li>
						</ul>
					</div>
				</div>
			</div>

			<div class="panel panel-accordion">
				<div class="panel-heading">
					<h4 class="panel-title">
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#userguide-accordion" href="#ug-academics">
							<i class="fas fa-chalkboard-teacher"></i> Academics
						</a>
					</h4>
				</div>
				<div id="ug-academics" class="accordion-body collapse">
					<div class="panel-body">
						<ul>
							<li><strong>Classes</strong>, <strong>Sections</strong> and <strong>Subject</strong> define the structure every other module (attendance, exams, fees, timetable) is built on &mdash; set these up first.</li>
							<li><strong>Timetable</strong> assigns subjects and teachers to periods for each class/section.</li>
							<li><strong>Live Class</strong> schedules and links online classes.</li>
							<li><strong>Homework</strong> lets teachers assign and grade take-home work; <strong>Attachments</strong> shares study material and notices with a class.</li>
							<li><strong>Transfer</strong> moves a student between classes/sections mid-session.</li>
						</ul>
					</div>
				</div>
			</div>

			<div class="panel panel-accordion">
				<div class="panel-heading">
					<h4 class="panel-title">
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#userguide-accordion" href="#ug-attendance">
							<i class="fas fa-calendar-check"></i> Attendance
						</a>
					</h4>
				</div>
				<div id="ug-attendance" class="accordion-body collapse">
					<div class="panel-body">
						<p class="mb-0">Mark daily student attendance by class and section, or staff attendance separately. Attendance feeds directly into the Attendance Report and can trigger automatic SMS/email alerts to parents when enabled.</p>
					</div>
				</div>
			</div>

			<div class="panel panel-accordion">
				<div class="panel-heading">
					<h4 class="panel-title">
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#userguide-accordion" href="#ug-exam">
							<i class="fas fa-file-alt"></i> Examinations
						</a>
					</h4>
				</div>
				<div id="ug-exam" class="accordion-body collapse">
					<div class="panel-body">
						<ul>
							<li><strong>Exam</strong> defines exam terms, exam halls/seating and marksheet templates, then records marks.</li>
							<li><strong>Online Exam</strong> builds question banks and runs exams students take directly on the portal, graded automatically.</li>
							<li>Once marks are entered, mark sheets and report cards can be generated and published from the same module.</li>
						</ul>
					</div>
				</div>
			</div>

			<div class="panel panel-accordion">
				<div class="panel-heading">
					<h4 class="panel-title">
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#userguide-accordion" href="#ug-fees">
							<i class="fas fa-hand-holding-usd"></i> Fees, Accounting &amp; Wallet
						</a>
					</h4>
				</div>
				<div id="ug-fees" class="accordion-body collapse">
					<div class="panel-body">
						<ul>
							<li><strong>Fees</strong> defines fee groups/types per class and collects payments; <strong>Offline Payments</strong> records cash/bank payments made outside the online gateway.</li>
							<li><strong>Accounting</strong> tracks general income and expenses beyond student fees, with its own reports.</li>
							<li><strong>Wallet</strong> is the school's prepaid balance used to send SMS and email through the platform &mdash; top it up before running large broadcasts.</li>
						</ul>
					</div>
				</div>
			</div>

			<div class="panel panel-accordion">
				<div class="panel-heading">
					<h4 class="panel-title">
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#userguide-accordion" href="#ug-comms">
							<i class="fas fa-comments"></i> Communication
						</a>
					</h4>
				</div>
				<div id="ug-comms" class="accordion-body collapse">
					<div class="panel-body">
						<ul>
							<li><strong>Send SMS/Mail</strong> broadcasts a message to selected students, parents or staff, drawing on the school's wallet balance.</li>
							<li><strong>Message</strong> is the internal inbox for direct messages between users on the portal.</li>
							<li><strong>Event</strong> publishes school events and notices to the calendar.</li>
						</ul>
					</div>
				</div>
			</div>

			<div class="panel panel-accordion">
				<div class="panel-heading">
					<h4 class="panel-title">
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#userguide-accordion" href="#ug-facilities">
							<i class="fas fa-school"></i> Facilities
						</a>
					</h4>
				</div>
				<div id="ug-facilities" class="accordion-body collapse">
					<div class="panel-body">
						<ul>
							<li><strong>Library</strong> catalogues books and tracks who has what checked out.</li>
							<li><strong>Hostels</strong> manages hostel rooms and student allocations.</li>
							<li><strong>Transport</strong> manages routes, vehicles and student pickup assignments.</li>
						</ul>
					</div>
				</div>
			</div>

			<div class="panel panel-accordion">
				<div class="panel-heading">
					<h4 class="panel-title">
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#userguide-accordion" href="#ug-reports">
							<i class="fas fa-chart-bar"></i> Reports
						</a>
					</h4>
				</div>
				<div id="ug-reports" class="accordion-body collapse">
					<div class="panel-body">
						<p>The Reports menu brings together read-only, filterable views for every module: student, fees, accounting, attendance, payroll, leave, exam and inventory reports. Use these instead of the module list views when you need totals, date-range filters, or an export.</p>
					</div>
				</div>
			</div>

			<div class="panel panel-accordion">
				<div class="panel-heading">
					<h4 class="panel-title">
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#userguide-accordion" href="#ug-settings">
							<i class="fas fa-cogs"></i> Settings
						</a>
					</h4>
				</div>
				<div id="ug-settings" class="accordion-body collapse">
					<div class="panel-body">
						<ul>
							<?php if (is_superadmin_loggedin()) { ?>
							<li><strong>Global Settings</strong> control platform-wide defaults: site identity, mail/SMS gateways, payment gateways and language.</li>
							<li><strong>Role &amp; Permission</strong> defines what each staff role can see and do, per module.</li>
							<li><strong>Session Settings</strong> manages the academic sessions schools operate under.</li>
							<?php } else { ?>
							<li><strong>School Settings</strong> control this school's identity, session, and preferences.</li>
							<?php } ?>
							<li><strong>Custom Field</strong> adds extra fields to student/employee forms without any code changes.</li>
							<li><strong>Translations</strong> edits or adds language wording used throughout the portal.</li>
							<li><strong>Backup</strong> creates and restores full database backups.</li>
							<li><strong>Audit Log</strong> shows a history of who changed what, for accountability.</li>
						</ul>
					</div>
				</div>
			</div>

		</div>

		<p class="text-muted mt-lg mb-0">
			<i class="fas fa-info-circle"></i> Use the search box in the top bar to jump straight to a student, staff member or page by name. If a menu item mentioned here isn't visible to you, your role may not have permission for it &mdash; check with your administrator.
		</p>
	</div>
</section>
