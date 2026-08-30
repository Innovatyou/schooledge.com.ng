<div class="row">
	<div class="col-md-12">
		<section class="panel appear-animation" data-appear-animation="<?=$global_config['animations'] ?>" data-appear-animation-delay="100">
			<header class="panel-heading">
				<h4 class="panel-title"><i class="fas fa-history" aria-hidden="true"></i> <?=translate('audit_log')?></h4>
			</header>
			<div class="panel-body">
				<div class="export_title">Audit Log</div>
				<table class="table table-bordered table-hover table-condensed" id="auditLogTable" cellpadding="0" cellspacing="0" width="100%">
					<thead>
						<tr>
<?php if (is_superadmin_loggedin()): ?>
							<th><?=translate('branch')?></th>
<?php endif; ?>
							<th><?=translate('date_time')?></th>
							<th><?=translate('actor')?></th>
							<th><?=translate('role')?></th>
							<th><?=translate('action')?></th>
							<th><?=translate('table')?></th>
							<th><?=translate('record_id')?></th>
							<th class="no-sort"><?=translate('details')?></th>
						</tr>
					</thead>
				</table>
			</div>
		</section>
	</div>
</div>

<div class="zoom-anim-dialog modal-block modal-block-lg mfp-hide" id="modal">
	<section class="panel" id="quick_view"></section>
</div>

<script type="text/javascript">
	$(document).ready(function () {
		initDatatable('#auditLogTable', 'audit_log/getAuditLogListDT');
	});

	function getAuditLogDetail(id) {
		$.ajax({
			url: base_url + 'audit_log/getAuditLogDetail',
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
