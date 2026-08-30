<!doctype html>
<html>
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta content="width=device-width,initial-scale=1" name="viewport">
	<meta name="keywords" content="">
	<meta name="description" content="<?php echo $global_config['institute_name'] ?>">
	<meta name="author" content="<?php echo $global_config['institute_name'] ?>">
	<title><?php echo translate('login');?></title>
	<link rel="shortcut icon" href="<?php echo base_url('assets/images/favicon.png');?>">

    <!-- Web Fonts  -->
	<link href="<?php echo is_secure('fonts.googleapis.com/css?family=Signika:300,400,600,700');?>" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap/css/bootstrap.css');?>">
	<link rel="stylesheet" href="<?php echo base_url('assets/vendor/font-awesome/css/all.min.css'); ?>">
	<script src="<?php echo base_url('assets/vendor/jquery/jquery.js');?>"></script>

	<!-- sweetalert js/css -->
	<link rel="stylesheet" href="<?php echo base_url('assets/vendor/sweetalert/sweetalert-custom.css');?>">
	<script src="<?php echo base_url('assets/vendor/sweetalert/sweetalert.min.js');?>"></script>
	<!-- login page style css -->
	<link rel="stylesheet" href="<?php echo base_url('assets/login_page/css/style-modern.css?v=' . filemtime(FCPATH . 'assets/login_page/css/style-modern.css'));?>">
	<script type="text/javascript">
		var base_url = '<?php echo base_url() ?>';
	</script>
</head>
	<body class="se-login">
        <div class="se-login-aurora">
            <div class="se-login-orb se-login-orb-a"></div>
            <div class="se-login-orb se-login-orb-b"></div>
            <div class="se-login-shell">
                <!-- image and information -->
                <div class="se-login-info">
                    <div>
                        <img class="se-login-logo" src="<?=$this->application_model->getBranchImage($branch_id, 'logo')?>" height="52" alt="School">
                        <h1><?php echo translate('welcome_to');?> <?php echo $global_config['institute_name'];?></h1>
                        <p class="se-login-tagline">Your entire school day, beautifully connected.</p>
                        <div class="se-login-address">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo $global_config['address'];?></span>
                        </div>
                        <div class="se-login-social">
                            <a href="<?php echo $global_config['facebook_url'];?>" target="_blank" aria-label="Facebook">
                                <span class="fab fa-facebook-f"></span>
                            </a>
                            <a href="<?php echo $global_config['twitter_url'];?>" target="_blank" aria-label="Twitter">
                                <span class="fab fa-twitter"></span>
                            </a>
                            <a href="<?php echo $global_config['linkedin_url'];?>" target="_blank" aria-label="LinkedIn">
                                <span class="fab fa-linkedin-in"></span>
                            </a>
                            <a href="<?php echo $global_config['youtube_url'];?>" target="_blank" aria-label="YouTube">
                                <span class="fab fa-youtube"></span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Login -->
                <div class="se-login-card">
                    <div class="se-login-card-inner">
                        <img class="se-login-card-logo" src="<?=$this->application_model->getBranchImage($branch_id, 'logo')?>" height="46" alt="">
                        <h2><?php echo $global_config['institute_name'];?></h2>
                        <?php echo form_open($this->uri->uri_string()); ?>
                            <div class="se-input-group <?php if (form_error('email')) echo 'has-error'; ?>">
                                <i class="far fa-user"></i>
                                <input type="text" class="form-control" name="email" value="<?php echo set_value('email', $this->input->get('demo_user'));?>" placeholder="<?php echo translate('username');?>" />
                                <span class="error"><?php echo form_error('email'); ?></span>
                            </div>
                            <div class="se-input-group <?php if (form_error('password')) echo 'has-error'; ?>">
                                <i class="fas fa-lock"></i>
                                <input type="password" class="form-control" name="password" value="<?php echo set_value('password', $this->input->get('demo_pass'));?>" placeholder="<?php echo translate('password');?>" />
                                <span class="error"><?php echo form_error('password'); ?></span>
                            </div>

                            <div class="se-login-row">
                                <label><input type="checkbox" name="remember" id="remember"> <?php echo translate('remember');?></label>
                                <a href="<?php echo base_url("{$this->authentication_model->getSegment(1)}forgot"); ?>"><?php echo translate('lose_your_password');?></a>
                            </div>
                            <button type="submit" id="btn_submit" class="se-btn-login">
                                <i class="fas fa-sign-in-alt"></i> <?php echo translate('login');?>
                            </button>
                            <p class="se-login-footer"><?php echo $global_config['footer_text'];?></p>
                        <?php echo form_close();?>
                    </div>
                </div>
            </div>
        </div>

		<script src="<?php echo base_url('assets/vendor/bootstrap/js/bootstrap.js');?>"></script>

		<?php
		$alertclass = "";
		if($this->session->flashdata('alert-message-success')){
			$alertclass = "success";
		} else if ($this->session->flashdata('alert-message-error')){
			$alertclass = "error";
		} else if ($this->session->flashdata('alert-message-info')){
			$alertclass = "info";
		}
		if($alertclass != ''):
			$alert_message = $this->session->flashdata('alert-message-'. $alertclass);
		?>
			<script type="text/javascript">
				swal({
					toast: true,
					position: 'top-end',
					type: '<?php echo $alertclass;?>',
					title: '<?php echo $alert_message;?>',
					confirmButtonClass: 'btn btn-default',
					buttonsStyling: false,
					timer: 8000
				})
			</script>
		<?php endif; ?>
	</body>
</html>
