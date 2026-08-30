<section class="panel">
	<header class="panel-heading">
		<h4 class="panel-title"><i class="icons icon-wallet"></i> <?=translate('wallet')?></h4>
	</header>
	<div class="panel-body">
		<?php echo form_open($this->uri->uri_string(), array('class' => 'form-inline mb-md')); ?>
			<div class="form-group">
				<input type="text" name="search" class="form-control" value="<?=htmlspecialchars($search ?? '')?>" placeholder="<?=translate('search')?> <?=translate('student_name')?> / <?=translate('register_no')?>">
			</div>
			<button type="submit" class="btn btn-default"><i class="fas fa-search"></i> <?=translate('search')?></button>
		<?php echo form_close(); ?>

		<div class="table-responsive">
			<table class="table table-bordered table-condensed table-hover mb-none">
				<thead>
					<tr>
						<th><?=translate('student_name')?></th>
						<th><?=translate('register_no')?></th>
						<th><?=translate('class')?></th>
						<th><?=translate('balance')?></th>
						<th><?=translate('action')?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($wallets as $row): ?>
					<tr>
						<td><?=$row->fullname?></td>
						<td><?=$row->register_no?></td>
						<td><?=$row->class_name . ' (' . $row->section_name . ')'?></td>
						<td><b><?=currencyFormat($row->balance)?></b></td>
						<td>
							<button type="button" class="btn btn-circle icon btn-default" data-loading-text="<i class='fas fa-spinner fa-spin'></i>" onclick="viewWallet('<?=$row->student_id?>', this)">
								<i class="fas fa-bars"></i>
							</button>
						</td>
					</tr>
					<?php endforeach; ?>
					<?php if (empty($wallets)): ?>
					<tr><td colspan="5" class="text-center"><?=translate('no_data_found')?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</section>

<!-- wallet detail modal -->
<div class="zoom-anim-dialog modal-block modal-block-lg mfp-hide" id="modal">
	<section class="panel" id="quick_view"></section>
</div>

<script type="text/javascript">
	function viewWallet(studentID, elem) {
		var btn = $(elem);
		$.ajax({
			url: base_url + 'wallet/view',
			type: 'POST',
			data: {'student_id': studentID},
			dataType: 'html',
			beforeSend: function () {
				btn.button('loading');
			},
			success: function (data) {
				$('#quick_view').html(data);
				mfp_modal('#modal');
			},
			complete: function () {
				btn.button('reset');
			}
		});
	}
</script>
