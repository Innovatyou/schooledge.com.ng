<?php
$row = $this->accounting_model->getExpenseRequestList(array('er.id' => $request_id), true);
$isRequester = ($row['requested_by'] == get_loggedin_user_id());
$canApprove = (get_permission('expense_approve', 'is_add') && $row['status'] == 1 && !$isRequester);
$canResubmit = ($isRequester && $row['status'] == 3 && get_permission('expense', 'is_add'));
?>
<header class="panel-heading">
	<h4 class="panel-title"><i class="fas fa-file-invoice-dollar"></i> <?=translate('expense_request_details')?></h4>
</header>

<?php if ($canResubmit) { ?>
<?php echo form_open_multipart('accounting/expense_resubmit', array('class' => 'form-horizontal frm-submit-data')); ?>
<input type="hidden" name="request_id" value="<?=$row['id']?>">
	<div class="panel-body">
		<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> <?=translate('this_request_was_rejected')?><?php echo !empty($row['comments']) ? ': ' . html_escape($row['comments']) : ''; ?></div>
		<div class="form-group">
			<label class="col-md-3 control-label"><?=translate('account')?> <span class="required">*</span></label>
			<div class="col-md-9">
				<?php
					$accounts_list = $this->app_lib->getSelectByBranch('accounts', $branch_id);
					echo form_dropdown("account_id", $accounts_list, $row['account_id'], "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
				?>
				<span class="error"></span>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-3 control-label"><?=translate('voucher') . " " . translate('head')?> <span class="required">*</span></label>
			<div class="col-md-9">
				<?php
					$arrayVoucherHead = $this->app_lib->getSelectByBranch('voucher_head', $branch_id, false, array('type' => 'expense'));
					echo form_dropdown("voucher_head_id", $arrayVoucherHead, $row['voucher_head_id'], "class='form-control' data-plugin-selectTwo data-width='100%'");
				?>
				<span class="error"></span>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-3 control-label"><?=translate('ref')?></label>
			<div class="col-md-9">
				<input type="text" class="form-control" name="ref_no" value="<?=html_escape($row['ref_no'])?>" />
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-3 control-label"><?=translate('amount')?> <span class="required">*</span></label>
			<div class="col-md-9">
				<input type="text" class="form-control" name="amount" autocomplete="off" value="<?=$row['amount']?>" />
				<span class="error"></span>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-3 control-label"><?=translate('date')?> <span class="required">*</span></label>
			<div class="col-md-9">
				<input type="text" class="form-control" name="date" value="<?=$row['date']?>" data-plugin-datepicker autocomplete="off"
				data-plugin-options='{ "todayHighlight" : true, "endDate": "+0d" }' />
				<span class="error"></span>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-3 control-label"><?=translate('pay_via')?></label>
			<div class="col-md-9">
				<?php
					$payvia_list = $this->app_lib->getSelectList('payment_types');
					echo form_dropdown("pay_via", $payvia_list, $row['pay_via'], "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
				?>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-3 control-label"><?=translate('description')?></label>
			<div class="col-md-9">
				<textarea class="form-control" name="description" rows="3"><?=html_escape($row['description'])?></textarea>
			</div>
		</div>
		<div class="form-group">
			<label class="col-md-3 control-label"><?=translate('attachment')?></label>
			<div class="col-md-9">
				<input type="file" name="attachment_file" class="dropify" data-height="70" />
			</div>
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
<?php echo form_open('accounting/expense_approval_save'); ?>
<input type="hidden" name="id" value="<?=$row['id']?>">
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table borderless mb-none">
				<tbody>
					<tr><th width="140"><?=translate('requested_by')?> :</th><td><?=get_type_name_by_id('staff', $row['requested_by'])?></td></tr>
					<tr><th><?=translate('account')?> :</th><td><?=$row['ac_name']?></td></tr>
					<tr><th><?=translate('voucher') . " " . translate('head')?> :</th><td><?=$row['v_head']?></td></tr>
					<tr><th><?=translate('ref_no')?> :</th><td><?=$row['ref_no']?></td></tr>
					<tr><th><?=translate('amount')?> :</th><td><?=currencyFormat($row['amount'])?></td></tr>
					<tr><th><?=translate('date')?> :</th><td><?=_d($row['date'])?></td></tr>
					<tr><th><?=translate('pay_via')?> :</th><td><?=$row['via_name']?></td></tr>
					<tr><th><?=translate('description')?> :</th><td><?=html_escape($row['description'])?></td></tr>
<?php if (!empty($row['attachments'])) { ?>
					<tr><th><?=translate('attachment')?> :</th><td><a class="btn btn-default btn-sm" target="_blank" href="<?=base_url('accounting/expense_request_download/?id=' . $row['id'])?>"><i class="far fa-arrow-alt-circle-down"></i> <?=translate('download')?></a></td></tr>
<?php } ?>
<?php
	$currentAccount = $this->app_lib->get_table('accounts', $row['account_id'], true);
	$currentBalance = !empty($currentAccount) ? $currentAccount['balance'] : 0;
	$projectedBalance = $currentBalance - $row['amount'];
?>
					<tr>
						<th><?=translate('account_current_balance')?> :</th>
						<td><?=currencyFormat($currentBalance)?> <span class="text-muted">&rarr; <?=currencyFormat($projectedBalance)?> <?=translate('if_approved')?></span></td>
					</tr>
<?php if ($projectedBalance < 0) { ?>
					<tr>
						<td colspan="2"><div class="alert alert-warning mb-none"><i class="fas fa-exclamation-triangle"></i> <?=translate('this_would_take_the_account_balance_below_zero')?></div></td>
					</tr>
<?php } ?>
					<tr>
						<th><?=translate('status')?> :</th>
						<th>
							<div class="radio-custom radio-inline">
								<input type="radio" id="expApprove" name="status" value="2" checked>
								<label for="expApprove"><?=translate('approved')?></label>
							</div>
							<div class="radio-custom radio-inline">
								<input type="radio" id="expReject" name="status" value="3">
								<label for="expReject"><?=translate('reject')?></label>
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
			<table class="table borderless mb-none">
				<tbody>
					<tr><th width="140"><?=translate('requested_by')?> :</th><td><?=get_type_name_by_id('staff', $row['requested_by'])?></td></tr>
					<tr><th><?=translate('account')?> :</th><td><?=$row['ac_name']?></td></tr>
					<tr><th><?=translate('voucher') . " " . translate('head')?> :</th><td><?=$row['v_head']?></td></tr>
					<tr><th><?=translate('amount')?> :</th><td><?=currencyFormat($row['amount'])?></td></tr>
					<tr><th><?=translate('date')?> :</th><td><?=_d($row['date'])?></td></tr>
					<tr><th><?=translate('description')?> :</th><td><?=html_escape($row['description'])?></td></tr>
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
