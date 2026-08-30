<header class="panel-heading">
	<h4 class="panel-title"><i class="icons icon-wallet"></i> <?=$student['fullname']?> (<?=$student['register_no']?>)</h4>
</header>
<div class="panel-body">
	<h3 class="mb-md"><?=translate('balance')?>: <b><?=currencyFormat($wallet['balance'])?></b></h3>

	<?php if ($can_add): ?>
	<section class="panel pg-fw">
		<div class="panel-body">
			<h5 class="chart-title mb-xs"><?=translate('credit')?> <?=translate('wallet')?></h5>
			<?php echo form_open('wallet/credit', array('class' => 'form-inline')); ?>
				<input type="hidden" name="student_id" value="<?=$student['id']?>">
				<div class="form-group mr-xs">
					<input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="<?=translate('amount')?>" required>
				</div>
				<div class="form-group mr-xs">
					<input type="text" name="remarks" class="form-control" placeholder="<?=translate('remarks')?>" required>
				</div>
				<button type="submit" class="btn btn-success"><i class="fas fa-plus-circle"></i> <?=translate('credit')?></button>
			<?php echo form_close(); ?>
		</div>
	</section>
	<?php endif; ?>

	<?php if ($can_edit): ?>
	<section class="panel pg-fw">
		<div class="panel-body">
			<h5 class="chart-title mb-xs"><?=translate('debit')?> <?=translate('wallet')?></h5>
			<?php echo form_open('wallet/debit', array('class' => 'form-inline')); ?>
				<input type="hidden" name="student_id" value="<?=$student['id']?>">
				<div class="form-group mr-xs">
					<input type="number" step="0.01" min="0.01" max="<?=$wallet['balance']?>" name="amount" class="form-control" placeholder="<?=translate('amount')?>" required>
				</div>
				<div class="form-group mr-xs">
					<input type="text" name="remarks" class="form-control" placeholder="<?=translate('remarks')?>" required>
				</div>
				<button type="submit" class="btn btn-danger"><i class="fas fa-minus-circle"></i> <?=translate('debit')?></button>
			<?php echo form_close(); ?>
		</div>
	</section>
	<?php endif; ?>

	<h5 class="chart-title mb-xs mt-lg"><?=translate('wallet_transactions')?></h5>
	<div class="table-responsive">
		<table class="table table-bordered table-condensed mb-none">
			<thead>
				<tr>
					<th><?=translate('date')?></th>
					<th><?=translate('type')?></th>
					<th><?=translate('amount')?></th>
					<th><?=translate('balance')?></th>
					<th><?=translate('source')?></th>
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
					<td><?=translate($row->source)?></td>
					<td><?=$row->remarks?></td>
				</tr>
				<?php endforeach; ?>
				<?php if (empty($transactions)): ?>
				<tr><td colspan="6" class="text-center"><?=translate('no_data_found')?></td></tr>
				<?php endif; ?>
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
