<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title><?php echo translate('live_demo') . ' - ' . $getSettings->seo_title; ?></title>
    <meta name="description" content="Explore a fully populated demo school before you subscribe." />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo base_url('assets/images/favicon.png');?>" />

    <link rel="stylesheet" href="<?php echo base_url('assets/frontend/css/bootstrap.min.css'); ?>" />
    <link rel="stylesheet" href="<?php echo base_url('assets/frontend/css/LineIcons.2.0.css'); ?>" />
    <link rel="stylesheet" href="<?php echo base_url('assets/vendor/font-awesome/css/all.min.css');?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/frontend/css/animate.css'); ?>" />
    <link rel="stylesheet" href="<?php echo base_url('assets/frontend/css/saas_main.css?v=' . filemtime(FCPATH . 'assets/frontend/css/saas_main.css')); ?>" />
    <link rel="stylesheet" href="<?php echo base_url('assets/frontend/css/saas-modern.css?v=' . filemtime(FCPATH . 'assets/frontend/css/saas-modern.css')); ?>" />

    <script type="text/javascript">
        document.documentElement.style.setProperty('--thm-primary', '<?php echo $getSettings->primary_color ?>');
        document.documentElement.style.setProperty('--thm-header-text', '<?php echo $getSettings->heading_text_color ?>');
        document.documentElement.style.setProperty('--thm-text', '<?php echo $getSettings->text_color ?>');
        document.documentElement.style.setProperty('--thm-menu-bg', '<?php echo $getSettings->menu_bg_color ?>');
        document.documentElement.style.setProperty('--thm-menu-color', '<?php echo $getSettings->menu_text_color ?>');
        document.documentElement.style.setProperty('--thm-footer-bg', '<?php echo $getSettings->footer_bg_color ?>');
        document.documentElement.style.setProperty('--thm-footer-text', '<?php echo $getSettings->footer_text_color ?>');
    </script>

    <script src="<?php echo base_url('assets/vendor/jquery/jquery.min.js');?>"></script>

    <style type="text/css">
        .demo-hero { padding: 160px 0 80px; text-align: center; }
        .demo-hero h1 { font-size: 40px; font-weight: 700; margin-bottom: 18px; }
        .demo-hero p { font-size: 17px; max-width: 640px; margin: 0 auto 10px; opacity: .85; }
        .demo-included { padding: 40px 0 20px; }
        .demo-included ul { list-style: none; padding: 0; max-width: 760px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 14px 30px; }
        .demo-included li { font-size: 15px; }
        .demo-included li i { color: var(--thm-primary, #4b7bec); margin-right: 8px; }
        .demo-credentials { padding: 30px 0 80px; }
        .demo-credentials .row { justify-content: center; }
        .credential-card { background: #fff; border: 1px solid #eceef2; border-radius: 10px; padding: 28px 24px; text-align: center; box-shadow: 0 6px 24px rgba(20,30,60,.06); height: 100%; }
        .credential-card .role-badge { display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #fff; background: var(--thm-primary, #4b7bec); border-radius: 20px; padding: 4px 14px; margin-bottom: 14px; }
        .credential-card .person-name { font-weight: 600; font-size: 15px; margin-bottom: 14px; min-height: 20px; }
        .credential-card .cred-row { text-align: left; background: #f7f8fa; border-radius: 6px; padding: 8px 12px; margin-bottom: 8px; font-size: 13px; }
        .credential-card .cred-row span { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #9aa1ac; }
        .credential-card .cred-row b { font-family: ui-monospace, Consolas, monospace; }
        .credential-card .btn { display: inline-block; margin-top: 14px; width: 100%; padding: 12px 20px; background-color: var(--thm-primary, #4b7bec); color: #fff; border: none; border-radius: 5px; font-size: 14px; font-weight: 600; text-transform: capitalize; text-decoration: none; transition: 0.2s; }
        .credential-card .btn:hover { background-color: #081828; color: #fff; }
        .demo-disclaimer { max-width: 700px; margin: 30px auto 0; text-align: center; font-size: 13px; color: #9aa1ac; }
        .demo-empty { max-width: 600px; margin: 0 auto; text-align: center; padding: 60px 20px; }
        @media (max-width: 767px) {
            .demo-hero { padding: 120px 0 50px; }
            .demo-hero h1 { font-size: 28px; }
            .demo-hero p { font-size: 15px; }
            .demo-included { padding: 20px 0 10px; }
            .demo-included ul { grid-template-columns: 1fr; gap: 12px; }
            .demo-credentials { padding: 20px 0 60px; }
            .credential-card { padding: 22px 18px; }
        }
    </style>
</head>
<body>
    <header class="header navbar-area">
        <div class="container-md">
            <div class="row align-items-center">
                <div class="col-lg-12">
                    <div class="nav-inner">
                        <nav class="navbar navbar-expand-lg">
                            <a class="navbar-brand" href="<?php echo base_url() ?>">
                                <img src="<?=$this->application_model->getBranchImage(get_loggedin_branch_id(), 'logo-small')?>" alt="Logo">
                            </a>
                            <div class="collapse navbar-collapse sub-menu-bar" id="navbarSupportedContent">
                                <button type="button" class="mobile-menu-close" aria-label="Close menu">
                                    <i class="fas fa-times"></i>
                                </button>
                                <ul id="nav" class="navbar-nav ms-auto">
                                    <li class="nav-item"><a href="<?php echo base_url() ?>#home" aria-label="Toggle navigation"><?php echo translate('home'); ?></a></li>
                                    <li class="nav-item"><a href="<?php echo base_url() ?>#features" aria-label="Toggle navigation"><?php echo translate('features'); ?></a></li>
                                    <li class="nav-item"><a href="<?php echo base_url() ?>#pricing" aria-label="Toggle navigation"><?php echo translate('pricing'); ?></a></li>
                                    <li class="nav-item"><a href="<?php echo base_url('saas_website/demo') ?>" class="active" aria-label="Toggle navigation"><?php echo translate('live_demo'); ?></a></li>
                                    <li class="nav-item"><a href="<?php echo base_url() ?>#contact" aria-label="Toggle navigation"><?php echo translate('contact'); ?></a></li>
                                </ul>
                            </div>
                            <div class="header-btn">
                                <div class="button add-list-button">
                                    <?php if (!is_loggedin()) { ?>
                                    <a href="<?php echo base_url('authentication/index') ?>" class="btn"><?php echo translate('login'); ?></a>
                                    <?php } else { ?>
                                    <a href="<?php echo base_url('dashboard/index') ?>" class="btn"><?php echo translate('dashboard'); ?></a>
                                    <?php } ?>
                                </div>
                                <button class="navbar-toggler mobile-menu-btn" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                                    <span class="toggler-icon"></span>
                                    <span class="toggler-icon"></span>
                                    <span class="toggler-icon"></span>
                                </button>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section class="demo-hero">
        <div class="container">
            <h1>See it running before you sign up</h1>
            <p>A fully populated demo school &mdash; real classes, real students, real report cards. Log in with
               any of the accounts below and explore exactly what your school would see.</p>
        </div>
    </section>

    <?php if (!$demoBranch) { ?>
    <section class="demo-empty">
        <i class="fas fa-tools" style="font-size: 32px; opacity: .4;"></i>
        <p style="margin-top: 16px; opacity: .7;">The live demo is being refreshed right now &mdash; please check back shortly.</p>
    </section>
    <?php } else { ?>

    <section class="demo-included">
        <div class="container">
            <ul>
                <li><i class="fas fa-check-circle"></i> 20 students across 3 classes, with parents and full profiles</li>
                <li><i class="fas fa-check-circle"></i> 5 teachers and 5 non-teaching staff</li>
                <li><i class="fas fa-check-circle"></i> Modern-styled Student &amp; Employee ID cards</li>
                <li><i class="fas fa-check-circle"></i> Examination admit cards with subject timetables</li>
                <li><i class="fas fa-check-circle"></i> Three full terms of exam results, WAEC-graded</li>
                <li><i class="fas fa-check-circle"></i> Report cards with class position and psychomotor ratings</li>
                <li><i class="fas fa-check-circle"></i> A sample Certificate of Achievement</li>
                <li><i class="fas fa-check-circle"></i> Real attendance records and CA/Exam scoring</li>
            </ul>
        </div>
    </section>

    <section class="demo-credentials">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="credential-card">
                        <span class="role-badge">School Admin</span>
                        <div class="person-name"><?php echo $demoAdmin ? html_escape($demoAdmin->name) : '&mdash;'; ?></div>
                        <div class="cred-row"><span>Username</span><b><?php echo $demoAdmin ? html_escape($demoAdmin->username) : 'n/a'; ?></b></div>
                        <div class="cred-row"><span>Password</span><b><?php echo html_escape($demoPassword); ?></b></div>
                        <a href="<?php echo base_url('authentication/index') . ($demoAdmin ? '?demo_user=' . rawurlencode($demoAdmin->username) . '&demo_pass=' . rawurlencode($demoPassword) : ''); ?>" class="btn">Login as Admin</a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="credential-card">
                        <span class="role-badge">Teacher</span>
                        <div class="person-name"><?php echo $demoTeacher ? html_escape($demoTeacher->name) : '&mdash;'; ?></div>
                        <div class="cred-row"><span>Username</span><b><?php echo $demoTeacher ? html_escape($demoTeacher->username) : 'n/a'; ?></b></div>
                        <div class="cred-row"><span>Password</span><b><?php echo html_escape($demoPassword); ?></b></div>
                        <a href="<?php echo base_url('authentication/index') . ($demoTeacher ? '?demo_user=' . rawurlencode($demoTeacher->username) . '&demo_pass=' . rawurlencode($demoPassword) : ''); ?>" class="btn">Login as Teacher</a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="credential-card">
                        <span class="role-badge">Student</span>
                        <div class="person-name"><?php echo $demoStudent ? html_escape($demoStudent->first_name . ' ' . $demoStudent->last_name) : '&mdash;'; ?></div>
                        <div class="cred-row"><span>Username</span><b><?php echo $demoStudent ? html_escape($demoStudent->username) : 'n/a'; ?></b></div>
                        <div class="cred-row"><span>Password</span><b><?php echo html_escape($demoPassword); ?></b></div>
                        <a href="<?php echo base_url('authentication/index') . ($demoStudent ? '?demo_user=' . rawurlencode($demoStudent->username) . '&demo_pass=' . rawurlencode($demoPassword) : ''); ?>" class="btn">Login as Student</a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="credential-card">
                        <span class="role-badge">Parent</span>
                        <div class="person-name"><?php echo $demoParent ? html_escape($demoParent->name) : '&mdash;'; ?></div>
                        <div class="cred-row"><span>Username</span><b><?php echo $demoParent ? html_escape($demoParent->username) : 'n/a'; ?></b></div>
                        <div class="cred-row"><span>Password</span><b><?php echo html_escape($demoPassword); ?></b></div>
                        <a href="<?php echo base_url('authentication/index') . ($demoParent ? '?demo_user=' . rawurlencode($demoParent->username) . '&demo_pass=' . rawurlencode($demoPassword) : ''); ?>" class="btn">Login as Parent</a>
                    </div>
                </div>
            </div>
            <p class="demo-disclaimer">This is a shared demo environment &mdash; data may be reset periodically, so
               please don't enter any real personal information while exploring. Ready to set up your own school?
               <a href="<?php echo base_url() ?>#pricing">See plans &amp; pricing</a>.</p>
        </div>
    </section>
    <?php } ?>

    <footer class="footer">
        <div class="footer-copyright">
            <div class="container d-flex justify-content-between align-items-center">
                <div class="copyright-text">
                    <div class="footer-copyright__content">
                        <span><?php echo $global_config['footer_text']; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <a href="#" class="scroll-top"><i class="lni lni-chevron-up"></i></a>

    <script src="<?php echo base_url('assets/frontend/js/bootstrap.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/frontend/js/wow.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/frontend/js/saas_main.js?v=' . filemtime(FCPATH . 'assets/frontend/js/saas_main.js')); ?>"></script>
</body>
</html>
