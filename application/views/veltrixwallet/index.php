<section class="panel">
	<header class="panel-heading">
		<h4 class="panel-title"><i class="icons icon-wallet"></i> SMS/Email Wallet</h4>
	</header>
	<div class="panel-body">
		<h3 class="mb-md">Balance: <?=currencyFormat($balance)?></h3>

		<?php if (get_permission('veltrix_wallet', 'is_add')) { ?>
		<?php echo form_open('veltrixwallet/topup', array('class' => 'form-inline mb-lg')); ?>
			<div class="form-group">
				<input type="number" step="0.01" min="1" name="amount" class="form-control" placeholder="Amount" required>
			</div>
			<button type="submit" class="btn btn-default"><i class="fas fa-plus-circle"></i> Top Up via Paystack</button>
		<?php echo form_close(); ?>
		<?php } ?>

		<?php if (is_superadmin_loggedin()) { ?>
		<div class="alert alert-warning mb-md">
			<strong>Manual adjustment (superadmin only).</strong> Use this only to reconcile a payment confirmed successful on Paystack that isn't reflected here, or to correct a mistaken credit. The reason is required and stored on the transaction.
		</div>
		<?php echo form_open('veltrixwallet/admin_adjust', array('class' => 'form-inline mb-lg')); ?>
			<input type="hidden" name="branch_id" value="<?=$branch_id?>">
			<div class="form-group">
				<select name="type" class="form-control" required>
					<option value="credit">Credit</option>
					<option value="debit">Debit</option>
				</select>
			</div>
			<div class="form-group">
				<input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="Amount" required>
			</div>
			<div class="form-group">
				<input type="text" name="note" class="form-control" placeholder="Reason, e.g. Paystack ref VWALLET-..." style="width:320px" required>
			</div>
			<button type="submit" class="btn btn-warning" onclick="return confirm('Manually adjust this school&#39;s wallet balance?');"><i class="fas fa-balance-scale"></i> Apply Adjustment</button>
		<?php echo form_close(); ?>
		<?php } ?>

		<div class="table-responsive">
			<table class="table table-bordered table-condensed table-hover mb-none">
				<thead>
					<tr>
						<th>Date</th>
						<th>Type</th>
						<th>Channel</th>
						<th>Status</th>
						<th>Amount</th>
						<th>Balance After</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$statusClass = array('completed' => 'success', 'pending' => 'warning', 'failed' => 'danger');
					?>
					<?php foreach ($transactions as $row): ?>
					<?php $status = $row['status'] ?? 'completed'; ?>
					<tr>
						<td><?=_d($row['created_at'])?></td>
						<td><span class="label label-<?=$row['type'] === 'credit' ? 'success' : 'default'?>"><?=ucfirst($row['type'])?></span></td>
						<td><?=ucfirst($row['channel'])?></td>
						<td><span class="label label-<?=$statusClass[$status] ?? 'default'?>"><?=ucfirst($status)?></span></td>
						<td><?=currencyFormat($row['amount'])?></td>
						<td><?=$status === 'completed' ? currencyFormat($row['balance_after']) : '-'?></td>
						<td><?=htmlspecialchars($row['description'] ?? '')?></td>
					</tr>
					<?php endforeach; ?>
					<?php if (empty($transactions)): ?>
					<tr><td colspan="7" class="text-center"><?=translate('no_data_found')?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</section>
