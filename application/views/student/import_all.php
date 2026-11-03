<?php $report = isset($report) ? $report : null; ?>
<div class="row">
	<div class="col-md-12">
		<section class="panel">
		<?php echo form_open_multipart($this->uri->uri_string(), array('class' => 'form-horizontal form-bordered validate')); ?>
			<header class="panel-heading">
				<h4 class="panel-title">
					<i class="fas fa-users"></i> <?=translate('import_all_students')?>
				</h4>
			</header>
			<div class="panel-body">
			<?php if ($report) { ?>
				<?php if (!empty($report['file_error'])) { ?>
				<div class="alert alert-danger"><i class="fas fa-times-circle"></i> <?=html_escape($report['file_error'])?></div>
				<?php } ?>
				<?php if (!empty($report['errors'])) { ?>
				<div class="alert alert-danger">
					<strong><i class="fas fa-times-circle"></i> Nothing was imported.</strong>
					<?=count($report['errors'])?> of <?=$report['total']?> rows need fixing. Correct them in your file and upload it again.
				</div>
				<div class="table-responsive mb-md" style="max-height: 360px; overflow: auto;">
					<table class="table table-bordered table-condensed mb-none">
						<thead><tr><th style="width: 90px;">Line</th><th>Problem</th></tr></thead>
						<tbody>
						<?php foreach ($report['errors'] as $line => $messages) { ?>
							<tr><td><?=$line?></td><td><?=html_escape(implode('; ', $messages))?></td></tr>
						<?php } ?>
						</tbody>
					</table>
				</div>
				<?php } elseif ($report['imported'] > 0) { ?>
				<div class="alert alert-success">
					<strong><i class="fas fa-check-circle"></i> <?=$report['imported']?> student<?=$report['imported'] == 1 ? '' : 's'?> imported.</strong>
					See them in <a href="<?=base_url('student/view')?>"><b>Student Details › Student List</b></a>. Print their usernames and passwords from
					<a href="<?=base_url('student/login_credential_reports')?>"><b>Reports › Student › Login Credential</b></a>.
				</div>
				<?php } ?>
				<?php if (!empty($report['skipped'])) { ?>
				<div class="alert alert-warning">
					<strong><?=count($report['skipped'])?> row<?=count($report['skipped']) == 1 ? '' : 's'?> skipped</strong> because the student is already in the school:
					<?=html_escape(implode(', ', array_slice($report['skipped'], 0, 10)))?><?=count($report['skipped']) > 10 ? ' and ' . (count($report['skipped']) - 10) . ' more' : ''?>.
				</div>
				<?php } ?>
			<?php } ?>
				<div class="form-group mt-md">
					<div class="col-md-12 mb-md">
						<a class="btn btn-default pull-right" href="<?=base_url('student/import_all_template')?>">
							<i class="fas fa-file-download"></i> Download Template
						</a>
						<p class="mb-none">Just joined SkoolOS? Bring in your whole school at once: every class and section in one spreadsheet, instead of admitting students one by one.</p>
					</div>
					<div class="col-md-12">
						<div class="alert alert-subl">
							<strong>How to fill the template :</strong><br/>
							1. Download the template and open it in Excel or Google Sheets. Replace the two example rows with your students, one per row.<br/>
							2. Only <b>FirstName</b>, <b>LastName</b> and <b>Class</b> are required<?=$rules['auto_admission_no'] ? '' : ', plus <b>AdmissionNo</b>'?>. Every other column can be left blank.<br/>
							3. <b>Class</b> and <b>Section</b> must match the names listed below. A blank Section means the class's first section.<br/>
							4. <?=$rules['auto_admission_no'] ? 'A blank <b>AdmissionNo</b> is given the next number automatically. A blank' : 'A blank'?> <b>Roll</b> is given the next number in the class.<br/>
							5. Write dates as DD/MM/YYYY, e.g. 14/03/2012. A blank AdmissionDate means today. <b>Gender</b> is Male or Female. <b>Category</b> is optional, e.g. Day or Boarding; new ones are created.<br/>
							6. Brothers and sisters with the same <b>GuardianPhone</b> share one parent account.<br/>
							7. Save as <b>CSV</b> (File › Save As › CSV) and upload it below. The whole file is checked first, so if any row has a problem nothing is saved and you'll see which lines to fix. Students already in the school (same admission number, or same name in the same class) are skipped, so you can safely upload the same file again.
						</div>
					</div>
				</div>
				<div class="form-group">
					<label class="control-label col-md-3">Logins</label>
					<div class="col-md-6">
						<p class="form-control-static">
							Students: username <b><?=html_escape($rules['stu_prefix'])?></b> + their ID, password <b><?=$rules['stu_password'] !== '' ? html_escape($rules['stu_password']) : 'their admission number'?></b>.<br/>
							Parents: username <b><?=html_escape($rules['grd_prefix'])?></b> + their ID, password <b><?=$rules['grd_password'] !== '' ? html_escape($rules['grd_password']) : 'their phone number'?></b>.<br/>
							<span class="text-muted">Change these under School Settings before importing.</span>
						</p>
					</div>
				</div>
			<?php if ($rules['student_places'] !== null) { ?>
				<div class="form-group">
					<label class="control-label col-md-3">Plan</label>
					<div class="col-md-6">
						<p class="form-control-static">Room for <b><?=$rules['student_places']?></b> more students and <b><?=$rules['parent_places']?></b> more parents on your plan.</p>
					</div>
				</div>
			<?php } ?>
			<?php if (is_superadmin_loggedin()) { ?>
				<div class="form-group">
					<label class="control-label col-md-3"><?=translate('branch')?> <span class="required">*</span></label>
					<div class="col-md-6">
						<?php
							$arrayBranch = $this->app_lib->getSelectList('branch');
							echo form_dropdown("branch_id", $arrayBranch, set_value('branch_id'), "class='form-control' data-plugin-selectTwo data-width='100%'");
						?>
						<span class="error"><?=form_error('branch_id')?></span>
					</div>
				</div>
			<?php } ?>
				<div class="form-group">
					<label class="control-label col-md-3">Select CSV File <span class="required">*</span></label>
					<div class="col-md-6 mb-lg">
						<input type="file" name="userfile" class="dropify" data-height="140" data-allowed-file-extensions="csv" />
						<?php echo form_error('userfile', '<label class="error">', '</label>'); ?>
					</div>
				</div>
			<?php if (!empty($classes)) { ?>
				<div class="form-group">
					<label class="control-label col-md-3">Class and section names</label>
					<div class="col-md-6">
						<div class="table-responsive" style="max-height: 260px; overflow: auto;">
							<table class="table table-bordered table-condensed mb-none">
								<thead><tr><th>Class</th><th>Sections</th></tr></thead>
								<tbody>
								<?php foreach ($classes as $class) { ?>
									<tr>
										<td><?=html_escape($class['name'])?></td>
										<td><?=empty($class['sections']) ? '<span class="text-danger">No section yet</span>' : html_escape(implode(', ', $class['sections']))?></td>
									</tr>
								<?php } ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			<?php } elseif (!is_superadmin_loggedin()) { ?>
				<div class="form-group">
					<div class="col-md-offset-3 col-md-6">
						<div class="alert alert-warning mb-none">Create your classes and sections first under <a href="<?=base_url('classes')?>"><b>Class & Section</b></a>; students are matched to them by name.</div>
					</div>
				</div>
			<?php } ?>
			</div>
			<footer class="panel-footer">
				<div class="row">
					<div class="col-md-offset-3 col-md-3">
						<button type="submit" name="save" value="1" class="btn btn-default btn-block">
							<i class="fas fa-file-import"></i> Check &amp; Import
						</button>
					</div>
				</div>
			</footer>
			<?php echo form_close(); ?>
		</section>
	</div>
</div>
