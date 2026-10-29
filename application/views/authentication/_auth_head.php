<?php
// Shared <head> for the login / forgot / reset pages. Param: $pageTitle
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta content="width=device-width,initial-scale=1" name="viewport">
	<meta name="description" content="<?php echo $global_config['institute_name'] ?>">
	<meta name="author" content="<?php echo $global_config['institute_name'] ?>">
	<title><?php echo $pageTitle . ' - ' . $global_config['institute_name']; ?></title>
	<link rel="shortcut icon" href="<?php echo base_url('assets/images/favicon.png');?>">

	<!-- Same theme key as the marketing site, so a visitor's light/dark choice carries over -->
	<script type="text/javascript">
		(function () {
			var t;
			try { t = localStorage.getItem('skoolos-theme'); } catch (e) {}
			if (t !== 'light' && t !== 'dark') {
				t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
			}
			document.documentElement.setAttribute('data-theme', t);
		})();
	</script>

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap">
	<link rel="stylesheet" href="<?php echo base_url('assets/vendor/font-awesome/css/all.min.css'); ?>">
	<script src="<?php echo base_url('assets/vendor/jquery/jquery.js');?>"></script>

	<!-- sweetalert js/css -->
	<link rel="stylesheet" href="<?php echo base_url('assets/vendor/sweetalert/sweetalert-custom.css');?>">
	<script src="<?php echo base_url('assets/vendor/sweetalert/sweetalert.min.js');?>"></script>
	<link rel="stylesheet" href="<?php echo base_url('assets/login_page/css/style-skoolos.css?v=' . filemtime(FCPATH . 'assets/login_page/css/style-skoolos.css'));?>">
	<script type="text/javascript">
		var base_url = '<?php echo base_url() ?>';
	</script>
</head>
