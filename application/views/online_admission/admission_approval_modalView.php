<?php
$row = $this->online_admission_model->getStagingList(array('oas.id' => $staging_id), true);
$payload = json_decode($row['staged_payload'], true);
$isMaker = ($row['staged_by'] == get_loggedin_user_id());
$canApprove = (get_permission('online_admission_approve', 'is_add') && $row['status'] == 1 && !$isMaker);

$admissionField = function ($key) use ($payload) {
    return isset($payload[$key]) && $payload[$key] !== '' ? html_escape($payload[$key]) : '&mdash;';
};
?>
<header class="panel-heading">
	<h4 class="panel-title"><i class="fas fa-user-graduate"></i> <?=translate('admission_review')?> &mdash; <?=html_escape($payload['first_name'] . ' ' . (isset($payload['last_name']) ? $payload['last_name'] : ''))?></h4>
</header>

<div class="panel-body">
	<div class="table-responsive">
		<table class="table borderless mb-none">
			<tbody>
				<tr><th width="180"><?=translate('staged_by')?> :</th><td><?=get_type_name_by_id('staff', $row['staged_by'])?></td></tr>
				<tr><th><?=translate('name')?> :</th><td><?=$admissionField('first_name')?> <?=$admissionField('last_name')?></td></tr>
				<tr><th><?=translate('register_no')?> :</th><td><?=$admissionField('register_no')?></td></tr>
				<tr><th><?=translate('class')?> :</th><td><?=html_escape($row['class_name'])?> (<?=html_escape($row['section_name'])?>) &mdash; <?=translate('roll')?>: <?=$admissionField('roll')?></td></tr>
				<tr><th><?=translate('admission_date')?> :</th><td><?=$admissionField('admission_date')?></td></tr>
				<tr><th><?=translate('email')?> :</th><td><?=$admissionField('email')?></td></tr>
				<tr><th><?=translate('mobile_no')?> :</th><td><?=$admissionField('mobileno')?></td></tr>
<?php if (!empty($payload['grd_name']) || !empty($payload['father_name'])) { ?>
				<tr><th><?=translate('guardian')?> :</th><td><?=$admissionField('grd_name')?> (<?=$admissionField('grd_relation')?>)</td></tr>
<?php } ?>
				<tr>
					<th><?=translate('login_credentials')?> :</th>
					<td>
<?php if (!empty($payload['username'])) { ?>
						<?=translate('username')?>: <?=html_escape($payload['username'])?> (<?=translate('manually_set')?>)
<?php } else { ?>
						<?=translate('will_be_auto_generated_on_approval')?>
<?php } ?>
					</td>
				</tr>
<?php if (!empty($payload['student_photo_filename']) && $payload['student_photo_filename'] != 'defualt.png') { ?>
				<tr><th><?=translate('photo')?> :</th><td><img src="<?=base_url('uploads/images/student/' . $payload['student_photo_filename'])?>" style="max-height:80px; border-radius:6px;"></td></tr>
<?php } ?>
			</tbody>
		</table>
	</div>

<?php if ($canApprove) {
	$conflicts = $this->online_admission_model->checkStagedUniqueness($payload, $row['branch_id']);
	if (!empty($conflicts)) { ?>
	<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?=implode('<br>', $conflicts)?></div>
<?php } ?>
	<?php echo form_open('online_admission/admission_approval_save'); ?>
	<input type="hidden" name="id" value="<?=$row['id']?>">
	<div class="table-responsive">
		<table class="table borderless mb-none">
			<tbody>
				<tr>
					<th width="180"><?=translate('status')?> :</th>
					<th>
						<div class="radio-custom radio-inline">
							<input type="radio" id="admApprove" name="status" value="2" checked<?php echo !empty($conflicts) ? ' disabled' : ''; ?>>
							<label for="admApprove"><?=translate('approved')?></label>
						</div>
						<div class="radio-custom radio-inline">
							<input type="radio" id="admReject" name="status" value="3"<?php echo !empty($conflicts) ? ' checked' : ''; ?>>
							<label for="admReject"><?=translate('reject')?></label>
						</div>
					</th>
				</tr>
				<tr><th><?=translate('comments')?> :</th><td><textarea class="form-control" name="comments" rows="2"></textarea></td></tr>
			</tbody>
		</table>
	</div>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-12 text-right">
				<button class="btn btn-default mr-xs" type="submit" id="admissionDecisionSubmit" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
					<i class="fas fa-check-circle"></i> <?=translate('submit')?>
				</button>
				<button class="btn btn-default modal-dismiss"><?=translate('close')?></button>
			</div>
		</div>
	</footer>
	<?php echo form_close(); ?>
<?php } else { ?>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-12 text-right">
				<button class="btn btn-default modal-dismiss"><?=translate('close')?></button>
			</div>
		</div>
	</footer>
<?php } ?>
