<div class="row">
	<div class="col-md-12">
		<section class="panel appear-animation" data-appear-animation="<?=$global_config['animations'] ?>" data-appear-animation-delay="100">
			<header class="panel-heading">
				<h4 class="panel-title"><i class="fas fa-coins" aria-hidden="true"></i> <?=translate('fee_collection_approvals')?></h4>
			</header>
			<div class="panel-body">
				<table class="table table-bordered table-condensed table-hover mb-none table-export">
					<thead>
						<tr>
							<th><?=translate('sl')?></th>
							<th><?=translate('student')?></th>
							<th><?=translate('class')?></th>
							<th><?=translate('amount')?></th>
							<th><?=translate('date')?></th>
							<th><?=translate('collected_by')?></th>
							<th class="no-sort"><?=translate('status')?></th>
							<th><?=translate('action')?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$count = 1;
						if (count($requestlist)) {
							foreach ($requestlist as $row) {
								$isMaker = ($row['collected_by'] == get_loggedin_user_id());
								?>
						<tr>
							<td><?php echo $count++; ?></td>
							<td><?php echo html_escape($row['fullname']); ?><br><small><?php echo html_escape($row['register_no']); ?></small></td>
							<td><?php echo html_escape($row['class_name']) . ' (' . html_escape($row['section_name']) . ')'; ?></td>
							<td><?php echo currencyFormat($row['total_amount']); ?></td>
							<td><?php echo _d($row['date']); ?></td>
							<td><?php echo get_type_name_by_id('staff', $row['collected_by']); ?></td>
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
<?php if ($row['status'] == 1 && get_permission('collect_fees_approve', 'is_add') && !$isMaker) { ?>
								<a href="javascript:void(0);" class="btn btn-circle icon btn-default" onclick="getCollectionApprovalDetails('<?=$row['id']?>')" data-toggle="tooltip" data-original-title="<?=translate('review')?>">
									<i class="fas fa-bars"></i>
								</a>
<?php } elseif ($row['status'] == 3 && $isMaker && get_permission('collect_fees', 'is_add')) { ?>
								<a href="javascript:void(0);" class="btn btn-circle icon btn-default" onclick="getCollectionApprovalDetails('<?=$row['id']?>')" data-toggle="tooltip" data-original-title="<?=translate('edit_and_resubmit')?>">
									<i class="fas fa-redo"></i>
								</a>
<?php } else { ?>
								<a href="javascript:void(0);" class="btn btn-circle icon btn-default" onclick="getCollectionApprovalDetails('<?=$row['id']?>')" data-toggle="tooltip" data-original-title="<?=translate('view')?>">
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

<div class="zoom-anim-dialog modal-block modal-block-full mfp-hide" id="modal">
	<section class="panel" id="quick_view"></section>
</div>

<script type="text/javascript">
	function getCollectionApprovalDetails(id) {
		$.ajax({
			url: base_url + 'fees/getCollectionApprovalDetails',
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
