<div class="row">
	<div class="col-md-12">
<?php if (is_superadmin_loggedin() ): ?>
	<section class="panel">
		<header class="panel-heading">
			<h4 class="panel-title"><?=translate('select_ground')?></h4>
		</header>
		<?php echo form_open($this->uri->uri_string(), array('id' => 'frmsection', 'class' => 'validate'));?>
		<div class="panel-body">
			<div class="row mb-sm">
				<div class="col-md-offset-3 col-md-6">
					<div class="form-group">
						<label class="control-label"><?=translate('branch')?> <span class="required">*</span></label>
						<?php
							$arrayBranch = $this->app_lib->getSelectList('branch');
							echo form_dropdown("branch_id", $arrayBranch, $branch_id, "class='form-control' id='branch_id' required
							data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
						?>
					</div>
				</div>
			</div>
		</div>
		<footer class="panel-footer">
			<div class="row">
				<div class="col-md-offset-10 col-md-2">
					<button type="submit" class="btn btn-default btn-block"> <i class="fas fa-filter"></i> <?=translate('filter')?></button>
				</div>
			</div>
		</footer>
		<?php echo form_close();?>
	</section>
<?php endif; if (!empty($branch_id)): ?>
<?php if (is_superadmin_loggedin()): ?>
		<section class="panel appear-animation" data-appear-animation="<?=$global_config['animations'] ?>" data-appear-animation-delay="100">
<?php else: ?>
		<section class="panel">
<?php endif ?>
			<header class="panel-heading">
				<h4 class="panel-title"><i class="fas fa-fingerprint"></i> <?php echo translate('Two_factor_authentication') . " " . translate('settings'); ?></h4>
			</header>
            <?php echo form_open('two_factor_authentication/settings_save' . get_request_url(), array('class' => 'form-horizontal form-bordered frm-submit')); ?>
				<div class="panel-body">
					<div class="form-group mt-md">
						<label  class="col-md-3 control-label"><?php echo translate('two_factor_authentication'); ?> <span class="required">*</span></label>
						<div class="col-md-6">
							<?php
								$array = array(
									"" => translate('select'),
									"0" => translate("disable"),
									"1" => translate("enable")
								);
								echo form_dropdown("two_factor_authentication", $array, set_value('two_factor_authentication', $setting['status']), "class='form-control' data-plugin-selectTwo
								data-width='100%' id='captchaStatus' data-minimum-results-for-search='Infinity'");
							?>
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label  class="col-md-3 control-label"><?php echo translate('2fa_show_remember_browser'); ?> <span class="required">*</span></label>
						<div class="col-md-6">
							<?php
								$array = array(
									"0" => translate('no'),
									"1" => translate('yes')
								);
								echo form_dropdown("2fa_show_remember", $array, set_value('2fa_show_remember', $setting['show_remember']), "class='form-control' data-plugin-selectTwo id='2fa_show_remember'
								data-width='100%' data-minimum-results-for-search='Infinity'");
							?>
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label  class="col-md-3 control-label"><?php echo translate('2fa_cookie_expiry'); ?> <span class="required">*</span></label>
						<div class="col-md-6">
							<?php
							$sr_disabled = $setting['show_remember'] == 0 ? 'disabled' : '';
								$array = array(
									"" => translate('select'),
									"+30 minute" => "30 Minute",
									"+1 hour" => "1 Hour",
									"+6 hour" => "6 Hour",
									"+1 day" => "1 Day",
									"+15 day" => "15 Days",
									"+1 month" => "1 Month",
									"+6 month" => "6 Month",
									"+1 year" => "1 Year",
								);
								echo form_dropdown("2fa_cookie_expiry", $array, set_value('cookie_expiry', $setting['cookie_expiry']), "class='form-control' $sr_disabled id='2fa_cookie_expiry' data-plugin-selectTwo
								data-width='100%' data-minimum-results-for-search='Infinity'");
							?>
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label  class="col-md-3 control-label"><?php echo translate('email') . " " . translate('instruction'); ?></label>
						<div class="col-md-6">
							<textarea type="text" rows="3" class="form-control" name="email_instruction"><?php echo $setting['email_instruction'] ?></textarea>
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label  class="col-md-3 control-label"><?php echo translate('app') . " " . translate('instruction'); ?></label>
						<div class="col-md-6  mb-md">
							<textarea type="text" rows="3" class="form-control" name="app_instruction"><?php echo $setting['app_instruction'] ?></textarea>
							<span class="error"></span>
						</div>
					</div>
				</div>
				<div class="panel-footer">
					<div class="row">
						<div class="col-md-2 col-md-offset-3">
							<button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
								<i class="fas fa-plus-circle"></i> <?php echo translate('save'); ?>
							</button>
						</div>
					</div>
				</div>
			<?php echo form_close(); ?>
		</section>
	</div>
</div>

<script type="text/javascript">
    $('#2fa_show_remember').on('change', function(){
        if (this.value == 1) {
            $("#2fa_cookie_expiry").prop('disabled', false);
        } else {
            $("#2fa_cookie_expiry").prop('disabled', true);
        }
    });
</script>
<?php endif; ?>