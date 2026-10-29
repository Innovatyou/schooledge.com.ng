<?php $this->load->view('authentication/_auth_head', array('pageTitle' => translate('password_restoration'))); ?>
<?php $this->load->view('authentication/_auth_shell_open'); ?>
				<span class="sk-icon-badge"><i class="fas fa-fingerprint"></i></span>
				<h2 class="sk-title"><?php echo translate('password_restoration'); ?></h2>
				<p class="sk-sub">Enter your username and we'll email you a link to reset your password.</p>

				<?php
				if ($this->session->flashdata('reset_res')) {
					if ($this->session->flashdata('reset_res') == 'true') {
						echo '<div class="sk-note sk-note-ok"><i class="fas fa-check-circle"></i> Password reset email sent successfully. Check your email.</div>';
					} elseif ($this->session->flashdata('reset_res') == 'false') {
						echo '<div class="sk-note sk-note-err"><i class="fas fa-exclamation-circle"></i> You entered the wrong email address.</div>';
					}
				}
				?>

				<?php echo form_open($this->uri->uri_string(), array('class' => 'sk-form')); ?>
					<div class="sk-field <?php if (form_error('username')) echo 'has-error'; ?>">
						<label for="sk-username"><?php echo translate('username'); ?></label>
						<div class="sk-input">
							<i class="far fa-user"></i>
							<input type="text" id="sk-username" name="username" value="<?php echo set_value('username'); ?>" autocomplete="username" autocapitalize="off" spellcheck="false" placeholder="<?php echo translate('username'); ?>" />
						</div>
						<span class="error"><?php echo form_error('username'); ?></span>
					</div>
					<button type="submit" id="btn_submit" class="sk-submit">
						<span><?php echo translate('forgot'); ?></span> <i class="far fa-paper-plane"></i>
					</button>
					<a class="sk-back" href="<?php echo base_url("{$this->authentication_model->getSegment(1)}authentication"); ?>"><i class="fas fa-arrow-left"></i> <?php echo translate('back_to_login'); ?></a>
				<?php echo form_close(); ?>
<?php $this->load->view('authentication/_auth_shell_close'); ?>
