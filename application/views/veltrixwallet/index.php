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

		<div class="table-responsive">
			<table class="table table-bordered table-condensed table-hover mb-none">
				<thead>
					<tr>
						<th>Date</th>
						<th>Type</th>
						<th>Channel</th>
						<th>Amount</th>
						<th>Balance After</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($transactions as $row): ?>
					<tr>
						<td><?=_d($row['created_at'])?></td>
						<td><span class="label label-<?=$row['type'] === 'credit' ? 'success' : 'default'?>"><?=ucfirst($row['type'])?></span></td>
						<td><?=ucfirst($row['channel'])?></td>
						<td><?=currencyFormat($row['amount'])?></td>
						<td><?=currencyFormat($row['balance_after'])?></td>
						<td><?=htmlspecialchars($row['description'] ?? '')?></td>
					</tr>
					<?php endforeach; ?>
					<?php if (empty($transactions)): ?>
					<tr><td colspan="6" class="text-center"><?=translate('no_data_found')?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</section>
