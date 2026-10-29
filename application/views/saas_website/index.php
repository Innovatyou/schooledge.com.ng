<?php
$brand = $global_config['institute_name'];
$currency_symbol = $global_config['currency_symbol'];
// The marketing site always shows the product brand, not a tenant school's logo
$logoLight = base_url('assets/frontend/images/skoolos/logo-light.svg');
$logoMark = base_url('assets/frontend/images/skoolos/mark.svg');

// Highlight the last two words of the CMS hero title (plain-text titles only)
$heroTitle = $getSettings->slider_title;
if ($heroTitle === strip_tags($heroTitle)) {
    $words = preg_split('/\s+/', trim($heroTitle));
    if (count($words) > 3) {
        $tail = array_splice($words, -2);
        $heroTitle = html_escape(implode(' ', $words)) . ' <span class="grad-text">' . html_escape(implode(' ', $tail)) . '</span>';
    }
}

// Icon + one-line pitch per module prefix; unknown modules fall back to a generic tile
$moduleMeta = array(
    'alumni'             => array('fa-user-graduate', 'Stay connected with past students and reunions.'),
    'attachments_book'   => array('fa-paperclip', 'Share notes, syllabi and files class by class.'),
    'attendance'         => array('fa-clipboard-check', 'Daily, subject-wise and QR attendance in seconds.'),
    'bulk_sms_and_email' => array('fa-paper-plane', 'Reach every parent by SMS and email at once.'),
    'card_management'    => array('fa-id-card', 'Design and print ID cards and admit cards.'),
    'certificate'        => array('fa-certificate', 'Generate certificates and testimonials instantly.'),
    'custom_domain'      => array('fa-globe-africa', 'Run the portal on your own school domain.'),
    'events'             => array('fa-calendar-alt', 'Plan and publish the school calendar.'),
    'homework'           => array('fa-book-open', 'Set, collect and grade homework online.'),
    'hostel'             => array('fa-bed', 'Rooms, bed allocation and boarding fees.'),
    'human_resource'     => array('fa-users-cog', 'Staff records, leave and payroll.'),
    'inventory'          => array('fa-boxes', 'Track stores, stock and suppliers.'),
    'library'            => array('fa-book', 'Catalogue, issue and return books.'),
    'live_class'         => array('fa-video', 'Teach live classes from anywhere.'),
    'multi_class'        => array('fa-layer-group', 'Enrol one student in several classes.'),
    'office_accounting'  => array('fa-calculator', 'Income, expenses and school ledgers.'),
    'online_exam'        => array('fa-laptop-code', 'CBT exams with automatic marking.'),
    'reception'          => array('fa-concierge-bell', 'Visitors, enquiries, calls and complaints.'),
    'student_accounting' => array('fa-wallet', 'Fee invoices, online payment and receipts.'),
    'transport'          => array('fa-bus', 'Routes, vehicles and transport fees.'),
    'two_fa'             => array('fa-shield-alt', 'Two-factor login protects every account.'),
    'website'            => array('fa-desktop', 'A ready-made public website for your school.'),
);
$moduleCount = count($modules);
?>
<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title><?php echo $getSettings->seo_title; ?></title>
    <meta name="keyword" content="<?php echo $getSettings->seo_keyword; ?>" />
    <meta name="description" content="<?php echo $getSettings->seo_description; ?>" />
    <meta name="author" content="<?php echo $brand ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:title" content="<?php echo html_escape($getSettings->seo_title); ?>" />
    <meta property="og:description" content="<?php echo html_escape($getSettings->seo_description); ?>" />
    <meta property="og:type" content="website" />
    <?php $this->load->view('saas_website/_head'); ?>

    <?php if ($getSettings->pwa_enable == 1) {  ?>
    <!-- Web Application Manifest -->
    <link rel="manifest" href="./manifest.json">
    <meta name="theme-color" content="#0b0f1f">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="<?php echo $brand ?>">
    <link rel="icon" sizes="512x512" href="<?php echo base_url('uploads/appIcons/icon-512x512.png')?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="<?php echo $brand ?>">
    <link rel="apple-touch-icon" href="<?php echo base_url('uploads/appIcons/icon-512x512.png')?>">

    <script type="text/javascript">
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/serviceworker.js', {
                scope: '/'
            }).then(function (registration) {
            }, function (err) {
                console.log('Service Worker registration failed: ', err);
            });
        }
    </script>
    <?php } ?>
</head>
<body class="x3d">
    <?php $this->load->view('saas_website/_header', array('currentPage' => 'home')); ?>

    <!-- Start Hero Area -->
    <section id="home" class="hero3d">
        <canvas id="heroCanvas" aria-hidden="true"></canvas>
        <div class="hero-fallback" aria-hidden="true"></div>
        <div class="hero-grid" aria-hidden="true"></div>
        <div class="container-xl hero-inner">
            <div class="hero-copy">
                <span class="eyebrow reveal"><span class="pulse"></span> The all-in-one school operating system</span>
                <h1 class="reveal"><?php echo $heroTitle; ?></h1>
                <p class="lead reveal"><?php echo $getSettings->slider_description; ?></p>
                <div class="hero-cta reveal">
                    <?php if (!empty($getSettings->button_text_1)) {
                        echo '<a href="' . $getSettings->button_url_1 . '" class="btn-glow btn-lg">' . $getSettings->button_text_1 . ' <i class="fas fa-arrow-right"></i></a>';
                    }
                    if (!empty($getSettings->button_text_2)) {
                        echo '<a href="' . $getSettings->button_url_2 . '" class="btn-ghost btn-lg">' . $getSettings->button_text_2 . '</a>';
                    }
                    ?>
                    <a href="<?php echo base_url('saas_website/demo') ?>" class="btn-ghost btn-lg"><i class="fas fa-play-circle"></i> <?php echo translate('try_live_demo'); ?></a>
                </div>
                <ul class="hero-proof reveal">
                    <li><i class="fas fa-check"></i> No installation, runs in the browser</li>
                    <li><i class="fas fa-check"></i> Admin, teacher, parent &amp; student portals</li>
                    <li><i class="fas fa-check"></i> Online fee payment built in</li>
                </ul>
            </div>

            <!-- floating glass cards orbiting the 3D core (illustrative dashboard preview) -->
            <div class="hero-float" aria-hidden="true">
                <div class="glass-card gc-1" data-depth="0.6">
                    <div class="gc-icon gc-mint"><i class="fas fa-qrcode"></i></div>
                    <div><small>Attendance today</small><strong>96.4%</strong></div>
                    <div class="gc-spark"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
                </div>
                <div class="glass-card gc-2" data-depth="1">
                    <div class="gc-icon gc-violet"><i class="fas fa-wallet"></i></div>
                    <div><small>Fees collected this term</small><strong><?php echo $currency_symbol ?>4.28m</strong></div>
                </div>
                <div class="glass-card gc-3" data-depth="0.8">
                    <div class="gc-icon gc-amber"><i class="fas fa-laptop-code"></i></div>
                    <div><small>CBT exam in progress</small><strong>JSS 2 &middot; Maths</strong></div>
                    <div class="gc-bar"><span style="width:68%"></span></div>
                </div>
                <div class="glass-card gc-4" data-depth="1.2">
                    <div class="gc-icon gc-cyan"><i class="fas fa-bell"></i></div>
                    <div><small>Parents notified</small><strong>1,240 SMS sent</strong></div>
                </div>
            </div>
        </div>
        <a href="#modules" class="scroll-cue page-scroll" aria-label="Scroll to modules"><span></span></a>
    </section>
    <!-- End Hero Area -->

    <!-- Start Stats Strip -->
    <section class="stats-strip">
        <div class="container-xl">
            <div class="stats-grid">
                <div class="stat reveal"><strong data-count="<?php echo $moduleCount ?>">0</strong><span>Integrated modules</span></div>
                <div class="stat reveal"><strong data-count="4">0</strong><span>Role-based portals</span></div>
                <div class="stat reveal"><strong data-count="100" data-suffix="%">0</strong><span>Cloud: nothing to install</span></div>
                <div class="stat reveal"><strong data-count="24" data-suffix="/7">0</strong><span>Access on web &amp; mobile</span></div>
            </div>
        </div>
        <div class="marquee" aria-hidden="true">
            <div class="marquee-track">
                <?php for ($loop = 0; $loop < 2; $loop++) {
                    foreach ($modules as $module) {
                        $meta = isset($moduleMeta[$module->prefix]) ? $moduleMeta[$module->prefix] : array('fa-cube', '');
                        echo '<span><i class="fas ' . $meta[0] . '"></i>' . html_escape($module->name) . '</span>';
                    }
                } ?>
            </div>
        </div>
    </section>

    <!-- Start Modules Orbit -->
    <section id="modules" class="modules3d">
        <div class="container-xl">
            <div class="solar-grid">
                <div class="solar-copy">
                    <span class="tag">Modules</span>
                    <h2 class="reveal">Every department. <span class="grad-text">One connected core.</span></h2>
                    <p class="reveal"><?php echo $moduleCount ?> modules orbit a single <?php echo $brand ?> core and share one database, so admissions, fees, results and messaging always stay in sync. Hover or tap any module to explore it.</p>
                    <div class="solar-detail glass reveal" id="solarDetail" aria-live="polite">
                        <div class="sd-icon"><i class="fas fa-cube" id="sdIcon"></i></div>
                        <div class="sd-body">
                            <small id="sdCount">01 / <?php echo str_pad($moduleCount, 2, '0', STR_PAD_LEFT) ?></small>
                            <h4 id="sdName">&nbsp;</h4>
                            <p id="sdDesc">&nbsp;</p>
                        </div>
                    </div>
                    <a href="#contact" class="btn-glow btn-lg page-scroll solar-cta reveal"><i class="fas fa-calendar-check"></i> Request a Demo</a>
                </div>
                <div class="solar-wrap">
                    <div class="solar" id="solar">
                        <div class="solar-plane" id="solarPlane">
                            <div class="solar-ring" data-ring="0"></div>
                            <div class="solar-ring" data-ring="1"></div>
                            <div class="solar-ring" data-ring="2"></div>
                        </div>
                        <div class="solar-core" aria-hidden="true">
                            <span class="core-pulse"></span><span class="core-pulse"></span>
                            <img src="<?php echo $logoMark ?>" alt="" width="128" height="128">
                        </div>
                        <?php
                        $ringSizes = array(5, 8);
                        foreach ($modules as $i => $module) {
                            $meta = isset($moduleMeta[$module->prefix]) ? $moduleMeta[$module->prefix] : array('fa-cube', 'Included with ' . $brand . '.');
                            $ring = $i < $ringSizes[0] ? 0 : ($i < $ringSizes[0] + $ringSizes[1] ? 1 : 2);
                            ?>
                        <button type="button" class="planet ring-<?php echo $ring ?>" data-ring="<?php echo $ring ?>" data-icon="<?php echo $meta[0] ?>" data-name="<?php echo html_escape($module->name) ?>" data-desc="<?php echo html_escape($meta[1]) ?>" aria-label="<?php echo html_escape($module->name) ?>">
                            <i class="fas <?php echo $meta[0] ?>"></i>
                            <span class="planet-label"><?php echo html_escape($module->name) ?></span>
                        </button>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- End Modules Orbit -->

    <!-- Start Features Area -->
    <section id="features" class="features3d section">
        <div class="container-xl">
            <div class="section-head center">
                <span class="tag"><?php echo translate('features'); ?></span>
                <h2 class="reveal"><?php echo $getSettings->feature_title; ?></h2>
                <p class="reveal"><?php echo $getSettings->feature_description; ?></p>
            </div>
            <div class="feature-grid">
                <?php foreach ($featureslist as $key => $feature) { ?>
                <div class="tilt-card feature-card reveal" style="--i:<?php echo $key % 3 ?>">
                    <div class="tilt-glare"></div>
                    <div class="feature-icon"><i class="<?php echo $feature->icon; ?>"></i></div>
                    <h3><?php echo $feature->title; ?></h3>
                    <p><?php echo $feature->description; ?></p>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>
    <!-- End Features Area -->

    <!-- Start Portals -->
    <section id="portals" class="portals section">
        <div class="container-xl">
            <div class="portals-grid">
                <div class="portals-copy">
                    <span class="tag">Built for everyone</span>
                    <h2 class="reveal">One login, <span class="grad-text">four tailored portals.</span></h2>
                    <p class="reveal">Each person sees exactly what they need. Admins run the school, teachers teach, and parents and students stay informed from any device.</p>
                    <div class="portal-tabs" role="tablist">
                        <button class="portal-tab active" role="tab" aria-selected="true" data-portal="0"><i class="fas fa-user-tie"></i><span><b>Admin</b><small>Run the whole school</small></span></button>
                        <button class="portal-tab" role="tab" aria-selected="false" data-portal="1"><i class="fas fa-chalkboard-teacher"></i><span><b>Teacher</b><small>Teach, mark and report</small></span></button>
                        <button class="portal-tab" role="tab" aria-selected="false" data-portal="2"><i class="fas fa-user-friends"></i><span><b>Parent</b><small>Pay fees and track progress</small></span></button>
                        <button class="portal-tab" role="tab" aria-selected="false" data-portal="3"><i class="fas fa-user-graduate"></i><span><b>Student</b><small>Learn, test and submit</small></span></button>
                    </div>
                </div>
                <div class="device-wrap" aria-hidden="true">
                    <div class="device" id="portalDevice">
                        <div class="device-bar"><i></i><i></i><i></i><span id="portalUrl">admin dashboard</span></div>
                        <div class="device-screen">
                            <div class="portal-view active">
                                <div class="pv-kpis"><div><small>Students</small><b>1,284</b></div><div><small>Fees paid</small><b>82%</b></div><div><small>Staff</small><b>76</b></div></div>
                                <div class="pv-chart"><i style="--h:45%"></i><i style="--h:62%"></i><i style="--h:54%"></i><i style="--h:78%"></i><i style="--h:70%"></i><i style="--h:88%"></i><i style="--h:94%"></i></div>
                                <ul class="pv-list"><li><span class="dot ok"></span>Second term results published<em>Today</em></li><li><span class="dot"></span>14 new admission applications<em>2h</em></li><li><span class="dot warn"></span>Payroll ready for approval<em>1d</em></li></ul>
                            </div>
                            <div class="portal-view">
                                <div class="pv-kpis"><div><small>My classes</small><b>5</b></div><div><small>Present</small><b>38/40</b></div><div><small>To mark</small><b>12</b></div></div>
                                <ul class="pv-list"><li><span class="dot ok"></span>QR attendance: JSS 2A<em>08:02</em></li><li><span class="dot"></span>Upload CA scores: Mathematics<em>Due Fri</em></li><li><span class="dot"></span>Live class: Algebra revision<em>11:00</em></li><li><span class="dot warn"></span>Homework submissions: 12 pending<em>Today</em></li></ul>
                            </div>
                            <div class="portal-view">
                                <div class="pv-kpis"><div><small>Balance</small><b><?php echo $currency_symbol ?>45,000</b></div><div><small>Attendance</small><b>97%</b></div><div><small>Average</small><b>74.5</b></div></div>
                                <div class="pv-pay"><span>Second term school fees</span><button tabindex="-1">Pay now</button></div>
                                <ul class="pv-list"><li><span class="dot ok"></span>Report card available<em>New</em></li><li><span class="dot"></span>PTA meeting: Saturday 10am<em>Event</em></li></ul>
                            </div>
                            <div class="portal-view">
                                <div class="pv-kpis"><div><small>Next exam</small><b>Biology</b></div><div><small>Homework</small><b>3</b></div><div><small>Position</small><b>4th</b></div></div>
                                <div class="pv-cbt"><small>CBT &middot; Question 7 of 40</small><b>Which organelle produces energy for the cell?</b><span>Mitochondria</span><span>Ribosome</span></div>
                                <ul class="pv-list"><li><span class="dot ok"></span>English essay submitted<em>Done</em></li></ul>
                            </div>
                        </div>
                    </div>
                    <div class="device-shadow"></div>
                </div>
            </div>
        </div>
    </section>
    <!-- End Portals -->

    <!-- Start Steps -->
    <section class="steps section">
        <div class="container-xl">
            <div class="section-head center">
                <span class="tag">Get started</span>
                <h2 class="reveal">Live in three steps</h2>
                <p class="reveal">No servers, no installation, no IT team required.</p>
            </div>
            <div class="steps-grid">
                <div class="step reveal"><span class="step-no">01</span><i class="fas fa-calendar-check"></i><h4>Book a demo</h4><p>Tell us about your school and we will walk you through <?php echo $brand ?> with your own workflows.</p></div>
                <div class="step reveal"><span class="step-no">02</span><i class="fas fa-school"></i><h4>We set you up</h4><p>We create your school account with your logo, classes, sessions and grading system.</p></div>
                <div class="step reveal"><span class="step-no">03</span><i class="fas fa-rocket"></i><h4>Go live</h4><p>Import students, invite staff and parents, and start collecting fees online.</p></div>
            </div>
        </div>
    </section>
    <!-- End Steps -->


    <!-- Start Demo CTA -->
    <section class="demo-cta">
        <div class="container-xl">
            <div class="demo-panel reveal">
                <canvas id="ctaCanvas" aria-hidden="true"></canvas>
                <div class="demo-copy">
                    <h2>See <?php echo $brand ?> running with real school data</h2>
                    <p>Log in to our demo school as an admin, teacher, parent or student. No sign-up needed.</p>
                </div>
                <a href="<?php echo base_url('saas_website/demo') ?>" class="btn-light btn-lg"><i class="fas fa-play-circle"></i> <?php echo translate('try_live_demo'); ?></a>
            </div>
        </div>
    </section>

    <!-- Start Faq Area -->
    <section class="faq3d section" id="faq">
        <div class="container-xl">
            <div class="section-head center">
                <span class="tag"><?php echo translate('faq'); ?></span>
                <h2 class="reveal"><?php echo $getSettings->faq_title; ?></h2>
                <p class="reveal"><?php echo $getSettings->faq_description; ?></p>
            </div>
            <div class="accordion" id="accordionExample">
                <?php
                $count = 1;
                foreach ($faqs as $key => $faq) {
                    ?>
                <div class="accordion-item reveal">
                    <h2 class="accordion-header" id="heading<?php echo $key ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?php echo $key ?>" aria-expanded="false" aria-controls="faq<?php echo $key ?>">
                            <span class="serial"><?php echo str_pad($count++, 2, '0', STR_PAD_LEFT); ?></span><span class="title"><?php echo $faq->title; ?></span><i class="fas fa-plus"></i>
                        </button>
                    </h2>
                    <div id="faq<?php echo $key ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $key ?>" data-bs-parent="#accordionExample">
                        <div class="accordion-body"><?php echo $faq->description; ?></div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>
    <!-- End faq Area -->

    <!-- Start Contact Area -->
    <section class="contact3d section" id="contact">
        <div class="container-xl">
            <div class="contact-grid">
                <div class="contact-info">
                    <span class="tag">Request a demo</span>
                    <h2 class="reveal"><?php echo $getSettings->contact_description; ?></h2>
                    <div class="contact-items">
                        <div class="contact-item reveal"><i class="fas fa-phone-alt"></i><div><small><?php echo translate('phone'); ?></small><p><?php echo $global_config['mobileno'] ?></p></div></div>
                        <div class="contact-item reveal"><i class="fas fa-envelope"></i><div><small><?php echo translate('email'); ?></small><p><?php echo $global_config['institute_email'] ?></p></div></div>
                        <div class="contact-item reveal"><i class="fas fa-map-marker-alt"></i><div><small><?php echo translate('address'); ?></small><p><?php echo $global_config['address'] ?></p></div></div>
                    </div>
                </div>
                <div class="contact-form glass reveal">
                    <?php echo form_open('saas_website/send_email', array('class' => 'contact-frm')); ?>
                    <h3><?php echo $getSettings->contact_title; ?></h3>
                    <?php if($this->session->flashdata('msg_success')): ?>
                    <div class="alert alert-success">
                        <i class="far fa-check-circle"></i> <?php echo $this->session->flashdata('msg_success'); ?>
                    </div>
                    <?php endif; ?>
                    <?php if($this->session->flashdata('msg_error')): ?>
                    <div class="alert alert-danger"><?php echo $this->session->flashdata('msg_error'); ?></div>
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-sm-6 form-group">
                            <input type="text" class="form-control" name="name" autocomplete="off" placeholder="Name *" value="">
                            <span class="error"></span>
                        </div>
                        <div class="col-sm-6 form-group">
                            <input type="text" class="form-control" name="email" autocomplete="off" placeholder="Email *" value="">
                            <span class="error"></span>
                        </div>
                        <div class="col-sm-6 form-group">
                            <input type="text" class="form-control" name="mobile" autocomplete="off" placeholder="Mobile *" value="">
                            <span class="error"></span>
                        </div>
                        <div class="col-sm-6 form-group">
                            <input type="text" class="form-control" name="subject" autocomplete="off" placeholder="Subject *" value="">
                            <span class="error"></span>
                        </div>
                        <div class="col-12 form-group">
                            <textarea rows="4" class="form-control" placeholder="Type Message *" name="message"></textarea>
                            <span class="error"></span>
                        </div>
                        <div class="col-12">
                            <button class="btn-glow w-100" type="submit" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing"><i class="fas fa-paper-plane"></i> <?php echo $getSettings->contact_button; ?></button>
                        </div>
                    </div>
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>
    </section>
    <!-- End Contact Area -->

    <?php $this->load->view('saas_website/_footer', array('currentPage' => 'home', 'withThree' => true)); ?>
</body>
</html>
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
            type: '<?php echo $alertclass?>',
            title: '<?php echo $alert_message?>',
            confirmButtonClass: 'btn btn-1',
            buttonsStyling: false,
            timer: 8000
        })
    </script>
<?php endif; ?>
