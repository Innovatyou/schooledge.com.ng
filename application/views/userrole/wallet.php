<section class="panel">
	<header class="panel-heading">
		<h4 class="panel-title"><i class="icons icon-wallet"></i> <?=translate('wallet')?></h4>
	</header>
	<div class="panel-body">
		<div class="alert alert-info">
			<h3 class="mb-none"><?=translate('balance')?>: <b><?=currencyFormat($wallet['balance'])?></b></h3>
		</div>
		<p class="text-muted">To top up this wallet, pay through the SchoolEdge mobile app or pay the school directly - the office will credit it here once received.</p>

		<h5 class="chart-title mb-xs mt-lg"><?=translate('wallet_transactions')?></h5>
		<div class="table-responsive">
			<table class="table table-bordered table-condensed table-hover mb-none table-export">
				<thead>
					<tr>
						<th><?=translate('date')?></th>
						<th><?=translate('type')?></th>
						<th><?=translate('amount')?></th>
						<th><?=translate('balance')?></th>
						<th><?=translate('remarks')?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($transactions as $row): ?>
					<tr>
						<td><?=_d($row->created_at)?></td>
						<td>
							<?php if ($row->type == 'credit'): ?>
								<span class="value label label-success-custom"><?=translate('credit')?></span>
							<?php else: ?>
								<span class="value label label-danger-custom"><?=translate('debit')?></span>
							<?php endif; ?>
						</td>
						<td><?=currencyFormat($row->amount)?></td>
						<td><?=currencyFormat($row->balance_after)?></td>
						<td><?=$row->remarks?></td>
					</tr>
					<?php endforeach; ?>
					<?php if (empty($transactions)): ?>
					<tr><td colspan="5" class="text-center"><?=translate('no_data_found')?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</section>
