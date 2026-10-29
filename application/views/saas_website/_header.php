<?php
// Shared marketing-site header. $currentPage is 'home' (in-page anchors that
// smooth-scroll) or anything else (anchors link back to the landing page).
$onLanding = isset($currentPage) && $currentPage === 'home';
$anchor = $onLanding ? '' : base_url();
$scrollClass = $onLanding ? ' class="page-scroll"' : '';
$brandName = $global_config['institute_name'];
?>
    <!-- Preloader -->
    <div class="preloader">
        <div class="preloader-core"><span></span><span></span><span></span></div>
    </div>

    <!-- Start Header Area -->
    <header class="header navbar-area<?php echo $onLanding ? '' : ' solid'; ?>">
        <div class="container-xl">
            <nav class="navbar navbar-expand-lg">
                <a class="navbar-brand" href="<?php echo base_url() ?>">
                    <img src="<?php echo base_url('assets/frontend/images/skoolos/logo-light.svg') ?>" alt="<?php echo $brandName ?>" width="188" height="41">
                </a>
                <div class="collapse navbar-collapse sub-menu-bar" id="navbarSupportedContent">
                    <button type="button" class="mobile-menu-close" aria-label="Close menu">
                        <i class="fas fa-times"></i>
                    </button>
                    <ul id="nav" class="navbar-nav mx-auto">
                        <li class="nav-item"><a href="<?php echo $anchor ?>#home"<?php echo $onLanding ? ' class="page-scroll active"' : '' ?>><?php echo translate('home'); ?></a></li>
                        <li class="nav-item"><a href="<?php echo $anchor ?>#modules"<?php echo $scrollClass ?>>Modules</a></li>
                        <li class="nav-item"><a href="<?php echo $anchor ?>#features"<?php echo $scrollClass ?>><?php echo translate('features'); ?></a></li>
                        <li class="nav-item"><a href="<?php echo $anchor ?>#portals"<?php echo $scrollClass ?>>Portals</a></li>
                        <li class="nav-item"><a href="<?php echo $anchor ?>#faq"<?php echo $scrollClass ?>><?php echo translate('faq'); ?></a></li>
                        <li class="nav-item"><a href="<?php echo $anchor ?>#contact"<?php echo $scrollClass ?>><?php echo translate('contact'); ?></a></li>
                    </ul>
                </div>
                <div class="header-btn">
                    <button type="button" class="theme-toggle" aria-label="Switch to dark mode" title="Toggle dark mode">
                        <i class="fas fa-moon"></i><i class="fas fa-sun"></i>
                    </button>
                    <a href="<?php echo base_url('saas_website/demo') ?>" class="btn-ghost d-none d-sm-inline-flex<?php echo (isset($currentPage) && $currentPage === 'demo') ? ' is-current' : '' ?>"><?php echo translate('live_demo'); ?></a>
                    <?php if (!is_loggedin()) { ?>
                    <a href="<?php echo base_url('authentication/index') ?>" class="btn-glow"><?php echo translate('login'); ?></a>
                    <?php } else { ?>
                    <a href="<?php echo base_url('dashboard/index') ?>" class="btn-glow"><?php echo translate('dashboard'); ?></a>
                    <?php } ?>
                    <button class="navbar-toggler mobile-menu-btn" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="toggler-icon"></span>
                        <span class="toggler-icon"></span>
                        <span class="toggler-icon"></span>
                    </button>
                </div>
            </nav>
            <?php if ($this->session->flashdata('website_expired_msg')) { ?>
            <div class="alert alert-danger alert-dismissible fade show mt-2">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $getSettings->expired_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php } ?>
        </div>
    </header>
    <!-- End Header Area -->
