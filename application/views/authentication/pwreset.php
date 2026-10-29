<?php $this->load->view('authentication/_auth_head', array('pageTitle' => 'Reset Password')); ?>
<?php $this->load->view('authentication/_auth_shell_open'); ?>
				<span class="sk-icon-badge"><i class="fas fa-key"></i></span>
				<h2 class="sk-title">Choose a new password</h2>
				<p class="sk-sub">Use at least 4 characters. You'll sign in with it from now on.</p>

				<?php // no action attribute: the form posts back to this URL, keeping the ?key= token ?>
				<form class="sk-form" method="post" accept-charset="utf-8">
					<?php echo $this->app_lib->generateCSRF(); ?>
					<div class="sk-field <?php if (form_error('password')) echo 'has-error'; ?>">
						<label for="sk-new">New password</label>
						<div class="sk-input">
							<i class="fas fa-key"></i>
							<input type="password" id="sk-new" name="password" value="" placeholder="New password" autocomplete="new-password" />
							<button type="button" class="sk-reveal" aria-label="Show password"><i class="far fa-eye"></i></button>
						</div>
						<span class="error"><?php echo form_error('password'); ?></span>
					</div>
					<div class="sk-field <?php if (form_error('c_password')) echo 'has-error'; ?>">
						<label for="sk-confirm">Confirm new password</label>
						<div class="sk-input">
							<i class="fas fa-key"></i>
							<input type="password" id="sk-confirm" name="c_password" value="" placeholder="Confirm new password" autocomplete="new-password" />
							<button type="button" class="sk-reveal" aria-label="Show password"><i class="far fa-eye"></i></button>
						</div>
						<span class="error"><?php echo form_error('c_password'); ?></span>
					</div>
					<button type="submit" id="btn_submit" class="sk-submit">
						<span>Confirm</span> <i class="far fa-check-circle"></i>
					</button>
				</form>
<?php $this->load->view('authentication/_auth_shell_close'); ?>
