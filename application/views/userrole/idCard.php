<section class="panel">
	<header class="panel-heading">
		<h4 class="panel-title"><i class="fas fa-id-card"></i> <?=translate('id_card')?></h4>
		<?php if (!empty($template)) { ?>
		<div class="panel-btn">
			<button type="button" class="btn btn-default btn-circle" onclick="fn_printElem('myIdCard');">
				<i class="fas fa-print"></i> <?=translate('print')?>
			</button>
		</div>
		<?php } ?>
	</header>
	<div class="panel-body">
	<?php if (empty($template)) { ?>
		<div class="alert alert-subl mb-none text-center">
			<i class="fas fa-exclamation-triangle"></i> <?=translate('no_information_available')?>
		</div>
	<?php } else { ?>
		<div id="myIdCard">
			<?php echo $this->load->view('card_manage/idCardprintFn', array(
				'user_array' => array($userID),
				'user_type' => 1,
				'template' => $template,
				'print_date' => $print_date,
				'expiry_date' => $expiry_date,
			), true); ?>
		</div>
	<?php } ?>
	</div>
</section>
