<!doctype html>
<html>
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta content="width=device-width,initial-scale=1" name="viewport">
	<meta name="keywords" content="">
    <meta name="description" content="<?php echo $global_config['institute_name'] ?>">
    <meta name="author" content="<?php echo $global_config['institute_name'] ?>">
	<title><?php echo translate('two_factor_authentication');?> | <?php echo $global_config['institute_name'] ?></title>
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
	<link rel="stylesheet" href="<?php echo base_url('assets/login_page/css/style.css');?>">
	<script type="text/javascript">
		var base_url = '<?php echo base_url() ?>';
	</script>
    <style type="text/css">
        .checkbox-replace .i-checks {
            font-weight: normal;
        }

        .forgot-text {
            padding: 4px 10px;
        }

        .sign-hader h2 {
            margin-bottom: 0;
        }
    </style>
</head>
	<body>
        <div class="auth-main">
            <div class="container">
                <div class="slideIn">
                    <!-- image and information -->
                    <div class="col-lg-4 col-lg-offset-1 col-md-4 col-md-offset-1 col-sm-12 col-xs-12 no-padding fitxt-center">
                        <div class="image-area">
                        <div class="content">
                            <div class="image-hader">
                                <h2><?php echo translate('welcome_to');?></h2>
                            </div>
                            <div class="center img-hol-p">
                                <img src="<?=$this->application_model->getBranchImage($branch_id, 'logo')?>" height="60" alt="">
                            </div>
                            <div class="address">
                                <p><?php echo $global_config['address'];?></p>
                            </div>          
                            <div class="f-social-links center">
                                <a href="<?php echo $global_config['facebook_url'];?>" target="_blank">
                                    <span class="fab fa-facebook-f"></span>
                                </a>
                                <a href="<?php echo $global_config['twitter_url'];?>" target="_blank">
                                    <span class="fab fa-twitter"></span>
                                </a>
                                <a href="<?php echo $global_config['linkedin_url'];?>" target="_blank">
                                    <span class="fab fa-linkedin-in"></span>
                                </a>
                                <a href="<?php echo $global_config['youtube_url'];?>" target="_blank">
                                    <span class="fab fa-youtube"></span>
                                </a>
                            </div>
                        </div>
                        </div>
                    </div>

                    <!-- Login -->
                    <div class="col-lg-6 col-lg-offset-right-1 col-md-6 col-md-offset-right-1 col-sm-12 col-xs-12 no-padding">
                        <div class="sign-area">
                            <div class="sign-hader pt-md">
                                <img src="<?=$this->application_model->getBranchImage($branch_id, 'logo')?>" height="54" alt="" >
                                <h2><?=$global_config['institute_name']?></h2>
                            </div>
                        
                            <div class="forgot-header">
                                <h4><i class="fas fa-fingerprint"></i> <?php echo translate('two_factor_authentication');?></h4>
                                <?php if (!empty($get2FA_config->email_instruction) || !empty($get2FA_config->app_instruction)) {
                                    echo ($two_fa_type == 'app') ? $get2FA_config->app_instruction : $get2FA_config->email_instruction;
                             } ?>
                            </div>
                        
                                <?php echo form_open($this->uri->uri_string(), array('class' => 'form-2fa')); ?>
                                <div class="form-group <?php if (form_error('authentication_code')) echo 'has-error'; ?>" style="margin-bottom: 5px;">
                                    <div class="input-group input-group-icon">
                                        <span class="input-group-addon">
                                            <span class="icon">
                                                <i class="fas fa-unlock-alt"></i>
                                            </span>
                                        </span>
                                        <input type="text" class="form-control" name="authentication_code" value="<?=set_value('authentication_code')?>" autocomplete="off" placeholder="<?php echo translate('authentication_code');?>" />
                                    </div>
                                    <span class="error"><?php echo form_error('authentication_code'); ?></span>
                                </div>
                            <?php if ($get2FA_config->show_remember == 1) { ?>
                                <div class="forgot-text">
                                    <div class="checkbox-replace">
                                        <label class="i-checks"><input type="checkbox" name="remember" id="remember"><i></i> <?php echo translate('do_not_ask_again_on_this_browser.');?></label>
                                    </div>
                                </div>
                            <?php } ?>
                                <div class="form-group">
                                    <button type="submit" id="btn_submit" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing" class="btn btn-block ladda-button btn-round"><?php echo translate('verify');?></button>
                                </div>
                                <div class="sign-footer">
                                    <p><?php echo $global_config['footer_text'];?></p>
                                </div>
                            <?php echo form_close();?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

		<script src="<?php echo base_url('assets/vendor/bootstrap/js/bootstrap.js');?>"></script>
		<script src="<?php echo base_url('assets/vendor/jquery-placeholder/jquery-placeholder.js');?>"></script>
		<!-- backstretch js -->
		<script src="<?php echo base_url('assets/login_page/js/jquery.backstretch.min.js');?>"></script>
		<script src="<?php echo base_url('assets/login_page/js/custom.js');?>"></script>
        <script type="text/javascript">
            $("form.form-2fa").on('submit', function(e){
                e.preventDefault();
                var $this = $(this);
                var btn = $this.find('[type="submit"]');
                $.ajax({
                    url: $(this).attr('action'),
                    type: "POST",
                    data: $(this).serialize(),
                    dataType: 'json',
                    beforeSend: function () {
                        btn.button('loading');
                    },
                    success: function (data) {
                        // console.log(data);
                        $('.error').html("");
                        if (data.status == "fail") {
                            $.each(data.error, function (index, value) {
                                $this.find("[name='" + index + "']").parents('.form-group').find('.error').html(value);
                            });
                            btn.button('reset');
                        } else if (data.status == "success") {
                            if (data.url) {
                                window.location.href = data.url;
                            } else{
                                location.reload(true);
                            }
                        }
                    },
                    error: function () {
                        btn.button('reset');
                    }
                });
            });
        </script>
	</body>
</html>