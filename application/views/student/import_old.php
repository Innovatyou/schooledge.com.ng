<?php $report = isset($report) ? $report : null; ?>
<div class="row">
	<div class="col-md-12">
		<section class="panel">
		<?php echo form_open_multipart($this->uri->uri_string(), array('class' => 'form-horizontal form-bordered validate')); ?>
			<header class="panel-heading">
				<h4 class="panel-title">
					<i class="fas fa-user-graduate"></i> <?=translate('import_old_students')?>
				</h4>
			</header>
			<div class="panel-body">
			<?php if ($report) { ?>
				<?php if (!empty($report['file_error'])) { ?>
				<div class="alert alert-danger"><i class="fas fa-times-circle"></i> <?=html_escape($report['file_error'])?></div>
				<?php } elseif (!empty($report['errors'])) { ?>
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
					<strong><i class="fas fa-check-circle"></i> <?=$report['imported']?> old student<?=$report['imported'] == 1 ? '' : 's'?> imported.</strong>
					Find them under <a href="<?=base_url('alumni/index')?>"><b>Alumni › Manage Alumni</b></a> by choosing their class and last session.
				</div>
				<?php } ?>
				<?php if (!empty($report['skipped'])) { ?>
				<div class="alert alert-warning">
					<strong><?=count($report['skipped'])?> row<?=count($report['skipped']) == 1 ? '' : 's'?> skipped</strong> because the admission number is already in the school:
					<?=html_escape(implode(', ', array_slice($report['skipped'], 0, 10)))?><?=count($report['skipped']) > 10 ? ' and ' . (count($report['skipped']) - 10) . ' more' : ''?>.
				</div>
				<?php } ?>
			<?php } ?>
				<div class="form-group mt-md">
					<div class="col-md-12 mb-md">
						<a class="btn btn-default pull-right" href="<?=base_url('student/import_old_template')?>">
							<i class="fas fa-file-download"></i> Download Template
						</a>
						<p class="mb-none">Bring in all your former students at once: every class and every past session in one file. They are added as <b>alumni</b>, so they don't appear in current class lists, attendance or fees, and don't count towards your plan's student limit.</p>
					</div>
					<div class="col-md-12">
						<div class="alert alert-subl">
							<strong>How to fill the template :</strong><br/>
							1. Download the template and open it in Excel or Google Sheets. Replace the two example rows with your students, one per row.<br/>
							2. <b>AdmissionNo</b>, <b>FirstName</b>, <b>LastName</b>, <b>Class</b> and <b>LastSession</b> are required. Every other column can be left blank.<br/>
							3. <b>Class</b> and <b>Section</b> are the class the student was last in. Use the names listed below. A blank Section means the class's first section.<br/>
							4. <b>LastSession</b> is the student's final session, e.g. <?=(date('Y') - 2) . '-' . (date('Y') - 1)?> (2019/2020 or just 2020 also work).<br/>
							5. <b>Status</b> is Graduated or Left. Blank means Graduated.<br/>
							6. Write dates as DD/MM/YYYY, e.g. 14/03/2003. <b>Gender</b> is Male or Female.<br/>
							7. Students who share a <b>GuardianPhone</b> are linked to the same guardian.<br/>
							8. Save as <b>CSV</b> (File › Save As › CSV) and upload it below. The whole file is checked first, so if any row has a problem nothing is saved and you'll see which lines to fix. Admission numbers already in the school are skipped, so you can safely upload the same file again.<br/>
							9. Old students and their guardians get no working login. Their records are kept for history, transcripts and alumni contact only.
						</div>
					</div>
				</div>
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
						<div class="alert alert-warning mb-none">Create your classes first under <a href="<?=base_url('classes')?>"><b>Class & Section</b></a>; old students are matched to them by name.</div>
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
