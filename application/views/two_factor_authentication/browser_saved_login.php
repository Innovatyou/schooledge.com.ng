<style type="text/css">
	.swal2-container {
		z-index: 10001 !important;
	}
</style>
<div class="table-responsive">
	<table class="table table-bordered table-hover table-condensed mb-none">
		<thead>
			<tr>
				<th><?php echo translate('sl'); ?></th>
				<th>IP <?=translate('address')?></th>
				<th><?=translate('browser')?></th>
				<th><?=translate('platform')?></th>
				<th><?=translate('login_date_time')?></th>
				<th><?=translate('action')?></th>
		</thead>
		<tbody>
		<?php
		$count = 1;
		if (!empty($list)){
			foreach ($list as $row):
			?>
			<tr>
				<td><?php echo $count++; ?></td>
				<td><?php echo $row->ip; ?></td>
				<td><?php echo $row->browser; ?></td>
				<td><?php echo $row->platform; ?></td>
				<td><?php 
				if (!empty($row->timestamp)) {
					echo _d($row->timestamp) . " " . date('h:i:s A', strtotime($row->timestamp));
				} else {
					echo "-";
				} ?></td>
				<td>
					<button class="btn btn-danger btn-circle" onclick="confirm_modal('<?php echo base_url("two_factor_authentication/delete_browser/" . $row->id) ?>')"><i class="fas fa-trash-alt"></i> <?=translate('remove')?></button>
				</td>
			</tr>
		<?php
				endforeach;
			}else{
				echo '<tr><td colspan="6"><h5 class="text-danger text-center">' . translate('no_information_available') . '</td></tr>';
			}
		?>
		</tbody>
	</table>
</div>