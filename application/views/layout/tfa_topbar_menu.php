<?php 
if (moduleIsEnabled('two_fa')) { 
	if (get2FA_config()->status && (is_student_loggedin() || is_parent_loggedin())) {	
?>
	<li><a href="<?php echo base_url('two_factor_authentication/setup_tfa');?>"><i class="fas fa-fingerprint"></i> <?=translate('2_FA_security')?></a></li>
<?php } } ?>