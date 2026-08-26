<?php $widget = (is_superadmin_loggedin() ? 3 : 4); ?>
<div class="row">
	<div class="col-md-12">
		<section class="panel">
		<?php echo form_open($this->uri->uri_string(), array('class' => 'validate'));?>
			<header class="panel-heading">
				<h4 class="panel-title"><?=translate('select_ground')?></h4>
			</header>
			<div class="panel-body">
				<div class="row mb-sm">
					<?php if (is_superadmin_loggedin()): ?>
					<div class="col-md-3 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('branch')?> <span class="required">*</span></label>
							<?php
								$arrayBranch = $this->app_lib->getSelectList('branch');
								echo form_dropdown("branch_id", $arrayBranch, set_value('branch_id'), "class='form-control' id='branch_id'
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
							?>
						</div>
					</div>
					<?php endif; ?>
					<div class="col-md-<?php echo $widget; ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('exam')?> <span class="required">*</span></label>
							<?php
								if (isset($branch_id)) {
									$arrayExam = array("" => translate('select'));
									$exams = $this->db->get_where('exam', array('branch_id' => $branch_id, 'session_id' => get_session_id()))->result();
									foreach ($exams as $row) {
										$arrayExam[$row->id] = $this->application_model->exam_name_by_id($row->id);
									}
								} else {
									$arrayExam = array("" => translate('select_branch_first'));
								}
								echo form_dropdown("exam_id", $arrayExam, set_value('exam_id'), "class='form-control' id='exam_id' required data-plugin-selectTwo
								data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
						</div>
					</div>
					<div class="col-md-3 mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('class')?> <span class="required">*</span></label>
							<?php
								$arrayClass = $this->app_lib->getClass($branch_id);
								echo form_dropdown("class_id", $arrayClass, set_value('class_id'), "class='form-control' id='class_id' onchange='getSectionByClass(this.value,0)'
								required data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
						</div>
					</div>
					<div class="col-md-<?php echo $widget; ?> mb-sm">
						<div class="form-group">
							<label class="control-label"><?=translate('section')?> <span class="required">*</span></label>
							<?php
								$arraySection = $this->app_lib->getSections(set_value('class_id'), false);
								echo form_dropdown("section_id", $arraySection, set_value('section_id'), "class='form-control' id='section_id' required
								data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
							?>
						</div>
					</div>
				</div>
			</div>
			<footer class="panel-footer">
				<div class="row">
					<div class="col-md-offset-10 col-md-2">
						<button type="submit" name="search" value="1" class="btn btn btn-default btn-block"> <i class="fas fa-filter"></i> <?=translate('filter')?></button>
					</div>
				</div>
			</footer>
			<?php echo form_close();?>
		</section>

		<?php if (isset($student)): ?>
		<section class="panel appear-animation" data-appear-animation="<?php echo $global_config['animations'];?>" data-appear-animation-delay="100">
			<?php echo form_open('exam/psychomotor_save', array('class' => 'frm-submit-msg'));
				$data = array(
					'class_id' => $class_id,
					'section_id' => $section_id,
					'exam_id' => $exam_id,
					'branch_id' => $branch_id,
				);
				echo form_hidden($data);
			?>
			<header class="panel-heading">
				<h4 class="panel-title"><i class="fas fa-users"></i> <?=translate('psychomotor_rating')?></h4>
			</header>
			<div class="panel-body">
				<?php if (!empty($student)) { ?>
				<div class="table-responsive mt-md mb-lg">
					<table class="table table-bordered table-condensed mb-none">
						<thead>
							<tr>
								<th><?=translate('sl')?></th>
								<th><?=translate('student_name')?></th>
								<th><?=translate('register_no')?></th>
								<th><?=translate('roll')?></th>
							<?php foreach (psychomotor_traits() as $traitKey => $label) { ?>
								<th><?=$label?></th>
							<?php } ?>
							</tr>
						</thead>
						<tbody>
							<?php
							$count = 1;
							foreach ($student as $key => $row):
								?>
							<tr>
								<input type="hidden" name="rating[<?=$key?>][student_id]" value="<?=$row['student_id']?>">
								<input type="hidden" name="rating[<?=$key?>][enroll_id]" value="<?=$row['enroll_id']?>">
								<td><?php echo $count++; ?></td>
								<td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
								<td><?php echo $row['register_no']; ?></td>
								<td><?php echo $row['roll']; ?></td>
							<?php foreach (psychomotor_traits() as $traitKey => $label) {
								$currentRating = isset($row['ratings'][$traitKey]) ? $row['ratings'][$traitKey] : '';
								?>
								<td class="min-w-sm">
									<?php
										echo form_dropdown("rating[{$key}][{$traitKey}]", array('' => translate('select')) + psychomotor_rating_scale(), $currentRating, "class='form-control'");
									?>
								</td>
							<?php } ?>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<?php } else { echo '<div class="alert alert-subl mt-md text-center">' . translate('no_information_available') . '</div>'; } ?>
			</div>
			<div class="panel-footer">
				<div class="row">
					<div class="col-md-offset-10 col-md-2">
						<button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
							<i class="fas fa-plus-circle"></i> <?=translate('save')?>
						</button>
					</div>
				</div>
			</div>
			<?php echo form_close(); ?>
		</section>
		<?php endif; ?>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function () {
		$('#branch_id').on('change', function() {
			var branchID = $(this).val();
			getClassByBranch(branchID);
			getExamByBranch(branchID);
		});
	});
</script>
