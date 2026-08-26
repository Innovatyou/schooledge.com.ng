<link rel="stylesheet" href="<?=base_url('assets/css/document-templates.css?v=' . version_combine())?>">
<style type="text/css">
	.certificate{
	<?php if (empty($template['background'])) { ?>
			background: #fff;
	<?php } else { ?>
		background-image: url("<?=base_url('uploads/certificate/' . $template['background'])?>");
		background-repeat: no-repeat !important;
		background-size: 100% 100% !important;
	<?php } ?>
		padding: <?=$template['top_space'] . 'px ' . $template['right_space'] . 'px ' . $template['bottom_space'] . 'px ' . $template['left_space'] . 'px'?>;
		font-family: Arial;
		line-height: 30px;
	}
</style>
<div class="certificate <?=document_template_class($template)?>">
	<?=$template['content']?>
</div>
