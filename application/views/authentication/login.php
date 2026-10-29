<?php $this->load->view('authentication/_auth_head', array('pageTitle' => translate('login'))); ?>
<?php $this->load->view('authentication/_auth_shell_open'); ?>
				<h2 class="sk-title">Sign in</h2>
				<p class="sk-sub">Use the username and password your school gave you.</p>

				<?php if ($this->input->get('demo_user')) { ?>
				<div class="sk-note"><i class="fas fa-magic"></i><span>Demo details are filled in. Just press <b><?php echo translate('login'); ?></b>.</span></div>
				<?php } ?>

				<?php echo form_open($this->uri->uri_string(), array('class' => 'sk-form', 'novalidate' => 'novalidate')); ?>
					<div class="sk-field <?php if (form_error('email')) echo 'has-error'; ?>">
						<label for="sk-username"><?php echo translate('username'); ?></label>
						<div class="sk-input">
							<i class="far fa-user"></i>
							<input type="text" id="sk-username" name="email" value="<?php echo set_value('email', $this->input->get('demo_user')); ?>" placeholder="<?php echo translate('username'); ?>" autocomplete="username" autocapitalize="off" spellcheck="false" />
						</div>
						<span class="error"><?php echo form_error('email'); ?></span>
					</div>
					<div class="sk-field <?php if (form_error('password')) echo 'has-error'; ?>">
						<label for="sk-password"><?php echo translate('password'); ?></label>
						<div class="sk-input">
							<i class="fas fa-lock"></i>
							<input type="password" id="sk-password" name="password" value="<?php echo set_value('password', $this->input->get('demo_pass')); ?>" placeholder="<?php echo translate('password'); ?>" autocomplete="current-password" />
							<button type="button" class="sk-reveal" aria-label="Show password"><i class="far fa-eye"></i></button>
						</div>
						<span class="error"><?php echo form_error('password'); ?></span>
					</div>

					<div class="sk-row">
						<label class="sk-check"><input type="checkbox" name="remember" id="remember"><span></span> <?php echo translate('remember'); ?></label>
						<a href="<?php echo base_url("{$this->authentication_model->getSegment(1)}forgot"); ?>"><?php echo translate('lose_your_password'); ?></a>
					</div>
					<button type="submit" id="btn_submit" class="sk-submit">
						<span><?php echo translate('login'); ?></span> <i class="fas fa-arrow-right"></i>
					</button>
				<?php echo form_close(); ?>
<?php $this->load->view('authentication/_auth_shell_close'); ?>
