<div class="row">
	<div class="col-md-12">
		<section class="panel appear-animation" data-appear-animation="<?=$global_config['animations'] ?>" data-appear-animation-delay="100">
			<header class="panel-heading">
				<h4 class="panel-title"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i> <?=translate('expense_approvals')?></h4>
			</header>
			<div class="panel-body">
				<table class="table table-bordered table-condensed table-hover mb-none table-export">
					<thead>
						<tr>
							<th><?=translate('sl')?></th>
<?php if (is_superadmin_loggedin()): ?>
							<th><?=translate('branch')?></th>
<?php endif; ?>
							<th><?=translate('account')?></th>
							<th><?=translate('voucher') . " " . translate('head')?></th>
							<th><?=translate('ref_no')?></th>
							<th><?=translate('amount')?></th>
							<th><?=translate('date')?></th>
							<th><?=translate('requested_by')?></th>
							<th class="no-sort"><?=translate('status')?></th>
							<th><?=translate('action')?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$count = 1;
						if (count($requestlist)) {
							foreach ($requestlist as $row) {
								$isRequester = ($row['requested_by'] == get_loggedin_user_id());
								?>
						<tr>
							<td><?php echo $count++; ?></td>
<?php if (is_superadmin_loggedin()): ?>
							<td><?php echo get_type_name_by_id('branch', $row['branch_id']); ?></td>
<?php endif; ?>
							<td><?php echo (!empty($row['attachments']) ? '<i class="fas fa-paperclip"></i> ' : '') . $row['ac_name']; ?></td>
							<td><?php echo $row['v_head']; ?></td>
							<td><?php echo $row['ref_no']; ?></td>
							<td><?php echo currencyFormat($row['amount']); ?></td>
							<td><?php echo _d($row['date']); ?></td>
							<td><?php echo get_type_name_by_id('staff', $row['requested_by']); ?></td>
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
<?php if ($row['status'] == 1 && get_permission('expense_approve', 'is_add') && !$isRequester) { ?>
								<a href="javascript:void(0);" class="btn btn-circle icon btn-default" onclick="getExpenseApprovalDetails('<?=$row['id']?>')" data-toggle="tooltip" data-original-title="<?=translate('review')?>">
									<i class="fas fa-bars"></i>
								</a>
<?php } elseif ($row['status'] == 3 && $isRequester && get_permission('expense', 'is_add')) { ?>
								<a href="javascript:void(0);" class="btn btn-circle icon btn-default" onclick="getExpenseApprovalDetails('<?=$row['id']?>')" data-toggle="tooltip" data-original-title="<?=translate('edit_and_resubmit')?>">
									<i class="fas fa-redo"></i>
								</a>
<?php } else { ?>
								<a href="javascript:void(0);" class="btn btn-circle icon btn-default" onclick="getExpenseApprovalDetails('<?=$row['id']?>')" data-toggle="tooltip" data-original-title="<?=translate('view')?>">
									<i class="fas fa-eye"></i>
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
	function getExpenseApprovalDetails(id) {
		$.ajax({
			url: base_url + 'accounting/getExpenseApprovalDetails',
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
