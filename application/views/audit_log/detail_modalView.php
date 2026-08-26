<header class="panel-heading">
	<h4 class="panel-title"><i class="fas fa-history"></i> <?=translate('audit_log')?> &mdash; #<?=intval($row['id'])?></h4>
</header>
<div class="panel-body">
	<div class="table-responsive">
		<table class="table borderless mb-none">
			<tbody>
				<tr><th width="140"><?=translate('date_time')?> :</th><td><?=_d($row['created_at']) . ' ' . date('h:i A', strtotime($row['created_at']))?></td></tr>
				<tr><th><?=translate('actor')?> :</th><td><?=html_escape($row['actor_name'])?></td></tr>
				<tr><th><?=translate('action')?> :</th><td><?=html_escape(ucfirst($row['action']))?></td></tr>
				<tr><th><?=translate('table')?> :</th><td><?=html_escape($row['table_name'])?></td></tr>
				<tr><th><?=translate('record_id')?> :</th><td><?=html_escape($row['record_id'])?></td></tr>
				<tr><th><?=translate('ip_address')?> :</th><td><?=html_escape($row['ip_address'])?></td></tr>
				<tr>
					<th><?=translate('previous_values')?> :</th>
					<td><pre style="white-space:pre-wrap; word-break:break-all; max-height:220px; overflow-y:auto;"><?=format_audit_json($row['old_values'])?></pre></td>
				</tr>
				<tr>
					<th><?=translate('new_values')?> :</th>
					<td><pre style="white-space:pre-wrap; word-break:break-all; max-height:220px; overflow-y:auto;"><?=format_audit_json($row['new_values'])?></pre></td>
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
