<?php
$row = $this->fees_model->getFeeCollectionRequestList(array('fcr.id' => $request_id), true);
$items = $this->fees_model->getFeeCollectionRequestItems($request_id);
$isMaker = ($row['collected_by'] == get_loggedin_user_id());
$canApprove = (get_permission('collect_fees_approve', 'is_add') && $row['status'] == 1 && !$isMaker);
$canResubmit = ($isMaker && $row['status'] == 3 && get_permission('collect_fees', 'is_add'));
$links = $this->fees_model->get('transactions_links', array('branch_id' => $branch_id), true);
?>
<header class="panel-heading">
	<h4 class="panel-title"><i class="fas fa-coins"></i> <?=translate('fee_collection_request_details')?> &mdash; <?=html_escape($row['fullname'])?></h4>
</header>

<?php if ($canResubmit) { ?>
<?php echo form_open('fees/collection_resubmit', array('class' => 'frm-submit-data')); ?>
<input type="hidden" name="request_id" value="<?=$row['id']?>">
	<div class="panel-body">
		<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> <?=translate('this_request_was_rejected')?><?php echo !empty($row['comments']) ? ': ' . html_escape($row['comments']) : ''; ?></div>
		<div class="table-responsive">
			<table class="table table-bordered">
				<thead>
					<tr>
						<th><?=translate('fees_type')?></th>
						<th><?=translate('date')?></th>
						<th><?=translate('amount')?></th>
						<th><?=translate('discount')?></th>
						<th><?=translate('fine')?></th>
						<th><?=translate('payment_method')?></th>
<?php if ($links['status'] == 1) { ?>
						<th><?=translate('account')?></th>
<?php } ?>
						<th><?=translate('remarks')?></th>
					</tr>
				</thead>
				<tbody>
<?php foreach ($items as $key => $item) { $isTransport = !empty($item['transport_fee_details_id']); ?>
					<tr>
						<input type="hidden" name="collect_fees[<?=$key?>][allocation_id]" value="<?=$item['allocation_id']?>">
						<input type="hidden" name="collect_fees[<?=$key?>][type_id]" value="<?=$item['type_id']?>">
						<input type="hidden" name="collect_fees[<?=$key?>][trans_fd_id]" value="<?=$item['transport_fee_details_id']?>">
						<input type="hidden" name="collect_fees[<?=$key?>][fee_type]" value="<?=$isTransport ? 'transport' : 'general'?>">
						<td><?=$isTransport ? translate('transport_fees') : html_escape($item['fee_type_name'])?></td>
						<td><input type="text" class="form-control" name="collect_fees[<?=$key?>][date]" value="<?=$item['date']?>" data-plugin-datepicker autocomplete="off" /></td>
						<td><input type="text" class="form-control" name="collect_fees[<?=$key?>][amount]" value="<?=($item['amount'] + $item['discount'])?>" autocomplete="off" /></td>
						<td><input type="text" class="form-control" name="collect_fees[<?=$key?>][discount_amount]" value="<?=$item['discount']?>" autocomplete="off" /></td>
						<td><input type="text" class="form-control" name="collect_fees[<?=$key?>][fine_amount]" value="<?=$item['fine']?>" autocomplete="off" /></td>
						<td>
							<?php
							$payvia_list = $this->app_lib->getSelectList('payment_types');
							echo form_dropdown("collect_fees[$key][pay_via]", $payvia_list, $item['pay_via'], "class='form-control'");
							?>
						</td>
<?php if ($links['status'] == 1) { ?>
						<td>
							<?php
							$accounts_list = $this->app_lib->getSelectByBranch('accounts', $branch_id);
							echo form_dropdown("collect_fees[$key][account_id]", $accounts_list, $item['account_id'], "class='form-control'");
							?>
						</td>
<?php } ?>
						<td><textarea name="collect_fees[<?=$key?>][remarks]" rows="1" class="form-control"><?=html_escape($item['remarks'])?></textarea></td>
					</tr>
<?php } ?>
				</tbody>
			</table>
		</div>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-12 text-right">
				<button class="btn btn-default mr-xs" type="submit" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
					<i class="fas fa-redo"></i> <?=translate('resubmit')?>
				</button>
				<button class="btn btn-default modal-dismiss"><?=translate('close')?></button>
			</div>
		</div>
	</footer>
<?php echo form_close(); ?>

<?php } elseif ($canApprove) { ?>
<?php echo form_open('fees/collection_approval_save'); ?>
<input type="hidden" name="id" value="<?=$row['id']?>">
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-bordered">
				<thead>
					<tr>
						<th><?=translate('fees_type')?></th>
						<th><?=translate('staged_amount')?></th>
						<th><?=translate('discount')?></th>
						<th><?=translate('fine')?></th>
						<th><?=translate('current_balance')?></th>
					</tr>
				</thead>
				<tbody>
<?php
$anyDrift = false;
foreach ($items as $item) {
	if (!empty($item['transport_fee_details_id'])) {
		$b = $this->fees_model->getTransportBalance($item['transport_fee_details_id']);
		$label = translate('transport_fees');
	} else {
		$b = $this->fees_model->getBalance($item['allocation_id'], $item['type_id']);
		$label = html_escape($item['fee_type_name']);
	}
	$staged = $item['amount'] + $item['discount'];
	$drift = ((float) $b['balance'] !== (float) $staged);
	if ($drift) {
		$anyDrift = true;
	}
?>
					<tr<?php echo $drift ? ' class="danger"' : ''; ?>>
						<td><?=$label?></td>
						<td><?=currencyFormat($staged)?></td>
						<td><?=currencyFormat($item['discount'])?></td>
						<td><?=currencyFormat($item['fine'])?></td>
						<td><?=currencyFormat($b['balance'])?><?php echo $drift ? ' <i class="fas fa-exclamation-triangle text-danger" data-toggle="tooltip" data-original-title="' . translate('balance_has_changed_since_this_was_submitted') . '"></i>' : ''; ?></td>
					</tr>
<?php } ?>
				</tbody>
			</table>
		</div>
<?php if ($anyDrift) { ?>
		<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> <?=translate('one_or_more_balances_have_changed_since_submission_review_before_approving')?></div>
<?php } ?>
		<div class="table-responsive">
			<table class="table borderless mb-none">
				<tbody>
					<tr><th width="140"><?=translate('collected_by')?> :</th><td><?=get_type_name_by_id('staff', $row['collected_by'])?></td></tr>
					<tr><th><?=translate('total')?> :</th><td><?=currencyFormat($row['total_amount'] + $row['total_fine'])?></td></tr>
					<tr>
						<th><?=translate('status')?> :</th>
						<th>
							<div class="radio-custom radio-inline">
								<input type="radio" id="colApprove" name="status" value="2" checked>
								<label for="colApprove"><?=translate('approved')?></label>
							</div>
							<div class="radio-custom radio-inline">
								<input type="radio" id="colReject" name="status" value="3">
								<label for="colReject"><?=translate('reject')?></label>
							</div>
						</th>
					</tr>
					<tr><th><?=translate('comments')?> :</th><td><textarea class="form-control" name="comments" rows="3"></textarea></td></tr>
				</tbody>
			</table>
		</div>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-12 text-right">
				<button class="btn btn-default mr-xs" type="submit">
					<i class="fas fa-check-circle"></i> <?=translate('submit')?>
				</button>
				<button class="btn btn-default modal-dismiss"><?=translate('close')?></button>
			</div>
		</div>
	</footer>
<?php echo form_close(); ?>

<?php } else { ?>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-bordered">
				<thead>
					<tr>
						<th><?=translate('fees_type')?></th>
						<th><?=translate('amount')?></th>
						<th><?=translate('discount')?></th>
						<th><?=translate('fine')?></th>
					</tr>
				</thead>
				<tbody>
<?php foreach ($items as $item) { ?>
					<tr>
						<td><?=!empty($item['transport_fee_details_id']) ? translate('transport_fees') : html_escape($item['fee_type_name'])?></td>
						<td><?=currencyFormat($item['amount'] + $item['discount'])?></td>
						<td><?=currencyFormat($item['discount'])?></td>
						<td><?=currencyFormat($item['fine'])?></td>
					</tr>
<?php } ?>
				</tbody>
			</table>
		</div>
		<div class="table-responsive">
			<table class="table borderless mb-none">
				<tbody>
					<tr><th width="140"><?=translate('collected_by')?> :</th><td><?=get_type_name_by_id('staff', $row['collected_by'])?></td></tr>
					<tr>
						<th><?=translate('status')?> :</th>
						<td>
<?php
							if ($row['status'] == 1) {
								echo translate('pending') . ' &mdash; ' . translate('awaiting_a_different_approver');
							} elseif ($row['status'] == 2) {
								echo translate('approved') . (!empty($row['approved_by']) ? ' ' . translate('by') . ' ' . get_type_name_by_id('staff', $row['approved_by']) : '');
							} else {
								echo translate('rejected') . (!empty($row['comments']) ? ': ' . html_escape($row['comments']) : '');
							}
?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-12 text-right">
				<button class="btn btn-default modal-dismiss"><?=translate('close')?></button>
			</div>
		</div>
	</footer>
<?php } ?>
