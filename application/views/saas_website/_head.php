    <link rel="shortcut icon" type="image/x-icon" href="<?php echo base_url('assets/images/favicon.png');?>" />

    <?php $this->load->view('saas_website/_theme_boot'); ?>

    <!-- CSS here -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sora:wght@500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?php echo base_url('assets/frontend/css/bootstrap.min.css'); ?>" />
    <link rel="stylesheet" href="<?php echo base_url('assets/vendor/font-awesome/css/all.min.css');?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/vendor/sweetalert/sweetalert-custom.css');?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/frontend/css/saas-3d.css?v=' . filemtime(FCPATH . 'assets/frontend/css/saas-3d.css')); ?>" />

    <!-- Google Analytics -->
    <?php echo $getSettings->google_analytics; ?>

    <script src="<?php echo base_url('assets/vendor/jquery/jquery.min.js');?>"></script>
    <script type="text/javascript">
        var base_url = '<?php echo base_url(); ?>';
        var csrfData = <?php echo json_encode(csrf_jquery_token()); ?>;
        $(function($) {
            $.ajaxSetup({
                cache: false,
                data: csrfData
            });
        });
    </script>
