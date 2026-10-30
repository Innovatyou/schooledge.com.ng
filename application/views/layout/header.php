<head>
	<!-- a person's own light / dark choice wins over the school's default skin; runs before paint -->
	<script>
		(function () {
			var t;
			try { t = localStorage.getItem('skoolos-theme'); } catch (e) {}
			if (t !== 'light' && t !== 'dark') return;
			var h = document.documentElement;
			h.classList.toggle('dark', t === 'dark');
			h.classList.toggle('sidebar-light', t !== 'dark');
		})();
	</script>
	<meta charset="UTF-8">
	<meta name="keywords" content="<?php echo $global_config['institute_name'] ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="description" content="<?php echo $global_config['institute_name'] ?>">
	<meta name="author" content="<?php echo $global_config['institute_name'] ?>">
	<title><?php echo $title;?></title>
    <link rel="shortcut icon" href="<?php echo base_url('assets/images/favicon.png');?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<!-- include stylesheet -->
	<?php include 'stylesheet.php';?>

	<?php
	if(isset($headerelements)) {
		foreach ($headerelements as $type => $element) {
			if($type == 'css') {
				if(count($element)) {
					foreach ($element as $keycss => $css) {
						echo '<link rel="stylesheet" href="'. base_url('assets/' . $css) . '">' . "\n";
					}
				}
			} elseif($type == 'js') {
				if(count($element)) {
					foreach ($element as $keyjs => $js) {
						echo '<script type="text/javascript" src="' . base_url('assets/' . $js). '"></script>' . "\n";
					}
				}
			}
		}
	}
	?>
	<!-- ramom css -->
	<link rel="stylesheet" href="<?php echo base_url('assets/css/ramom.css?v=' . version_combine());?>">
	<?php if ($theme_config["border_mode"] == 'false'): ?>
		<link rel="stylesheet" href="<?php echo base_url('assets/css/skins/square-borders.css?v=' . version_combine());?>">
	<?php endif; ?>

	<!-- 3D menu, header and title bar -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Sora:wght@700;800&display=swap">
	<link rel="stylesheet" href="<?php echo base_url('assets/css/panel-3d.css?v=' . filemtime(FCPATH . 'assets/css/panel-3d.css'));?>">

	<!-- If user have enabled CSRF proctection this function will take care of the ajax requests and append custom header for CSRF -->
	<script type="text/javascript">
		var base_url = '<?php echo base_url(); ?>';
		var isRTLenabled = '<?php echo $this->app_lib->isRTLenabled(); ?>';
		var csrfData = <?php echo json_encode(csrf_jquery_token()); ?>;
		$(function($) {
			$.ajaxSetup({
				cache: false,
				data: csrfData
			});
		});
	</script>
</head>