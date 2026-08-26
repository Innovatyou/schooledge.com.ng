<div class="row">
	<div class="col-md-12">
		<section class="panel appear-animation" data-appear-animation="<?=$global_config['animations'] ?>" data-appear-animation-delay="100">
			<header class="panel-heading">
				<h4 class="panel-title"><i class="fas fa-user-graduate" aria-hidden="true"></i> <?=translate('admission_approvals')?></h4>
			</header>
			<div class="panel-body">
				<table class="table table-bordered table-condensed table-hover mb-none table-export">
					<thead>
						<tr>
							<th><?=translate('sl')?></th>
							<th><?=translate('applicant')?></th>
							<th><?=translate('class')?></th>
							<th><?=translate('staged_by')?></th>
							<th><?=translate('staged_at')?></th>
							<th class="no-sort"><?=translate('status')?></th>
							<th><?=translate('action')?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$count = 1;
						if (count($stagedlist)) {
							foreach ($stagedlist as $row) {
								$isMaker = ($row['staged_by'] == get_loggedin_user_id());
								?>
						<tr>
							<td><?php echo $count++; ?></td>
							<td><?php echo html_escape($row['first_name'] . ' ' . $row['last_name']); ?><br><small><?php echo html_escape($row['reference_no']); ?></small></td>
							<td><?php echo html_escape($row['class_name']) . ' (' . html_escape($row['section_name']) . ')'; ?></td>
							<td><?php echo get_type_name_by_id('staff', $row['staged_by']); ?></td>
							<td><?php echo _d($row['staged_at']); ?></td>
							<td>
								<?php
								if ($row['status'] == 1) {
									echo '<span class="label label-warning-custom text-xs">' . translate('pending') . '</span>';
								} elseif ($row['status'] == 2) {
									echo '<span class="label label-success-custom text-xs">' . translate('approved') . '</span>';
								} else {
									echo '<span class="label label-danger-custom text-xs">' . translate('rejected') . '</span>';
								}
								?>
							</td>
							<td>
<?php if ($row['status'] == 1 && get_permission('online_admission_approve', 'is_add') && !$isMaker) { ?>
								<a href="javascript:void(0);" class="btn btn-circle icon btn-default" onclick="getAdmissionApprovalDetails('<?=$row['id']?>')" data-toggle="tooltip" data-original-title="<?=translate('review')?>">
									<i class="fas fa-bars"></i>
								</a>
<?php } else { ?>
								<a href="javascript:void(0);" class="btn btn-circle icon btn-default" onclick="getAdmissionApprovalDetails('<?=$row['id']?>')" data-toggle="tooltip" data-original-title="<?=translate('view')?>">
									<i class="fas fa-eye"></i>
								</a>
<?php } ?>
<?php if ($row['status'] == 3 && $isMaker) { ?>
								<a href="<?php echo base_url('online_admission/approved/' . $row['online_admission_id']); ?>" class="btn btn-circle icon btn-default" data-toggle="tooltip" data-original-title="<?=translate('review_again')?>">
									<i class="fas fa-redo"></i>
								</a>
<?php } ?>
							</td>
						</tr>
						<?php
							}
						}
						?>
					</tbody>
				</table>
			</div>
		</section>
	</div>
</div>

<div class="zoom-anim-dialog modal-block modal-block-lg mfp-hide" id="modal">
	<section class="panel" id="quick_view"></section>
</div>

<script type="text/javascript">
	function getAdmissionApprovalDetails(id) {
		$.ajax({
			url: base_url + 'online_admission/getAdmissionApprovalDetails',
			type: 'POST',
			data: {'id': id},
			dataType: "html",
			success: function (data) {
				$('#quick_view').html(data);
				mfp_modal('#modal');
			}
		});
	}
</script>
