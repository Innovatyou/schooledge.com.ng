<?php
$brand = $global_config['institute_name'];
$demoRoles = array(
    array('key' => 'admin',   'label' => 'School Admin', 'icon' => 'fa-user-tie',          'tone' => 'violet', 'who' => $demoAdmin ? $demoAdmin->name : null,     'user' => $demoAdmin ? $demoAdmin->username : null,     'blurb' => 'Run admissions, fees, results, staff and every setting.'),
    array('key' => 'teacher', 'label' => 'Teacher',      'icon' => 'fa-chalkboard-teacher', 'tone' => 'blue',   'who' => $demoTeacher ? $demoTeacher->name : null, 'user' => $demoTeacher ? $demoTeacher->username : null, 'blurb' => 'Take attendance, enter CA and exam scores, set homework.'),
    array('key' => 'student', 'label' => 'Student',      'icon' => 'fa-user-graduate',      'tone' => 'cyan',   'who' => $demoStudent ? $demoStudent->first_name . ' ' . $demoStudent->last_name : null, 'user' => $demoStudent ? $demoStudent->username : null, 'blurb' => 'See timetables, results, homework and CBT exams.'),
    array('key' => 'parent',  'label' => 'Parent',       'icon' => 'fa-user-friends',       'tone' => 'amber',  'who' => $demoParent ? $demoParent->name : null,   'user' => $demoParent ? $demoParent->username : null,   'blurb' => 'Track a child\'s progress, attendance and fee balance.'),
);
$included = array(
    array('fa-users', '20 students across 3 classes, with parents and full profiles'),
    array('fa-chalkboard-teacher', '5 teachers and 5 non-teaching staff'),
    array('fa-id-card', 'Modern-styled student and employee ID cards'),
    array('fa-ticket-alt', 'Examination admit cards with subject timetables'),
    array('fa-poll', 'Three full terms of exam results, WAEC-graded'),
    array('fa-file-alt', 'Report cards with class position and psychomotor ratings'),
    array('fa-certificate', 'A sample Certificate of Achievement'),
    array('fa-clipboard-check', 'Real attendance records and CA/exam scoring'),
);
?>
<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title><?php echo translate('live_demo') . ' - ' . $getSettings->seo_title; ?></title>
    <meta name="description" content="Log in to a fully populated <?php echo html_escape($brand) ?> demo school as an admin, teacher, student or parent." />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php $this->load->view('saas_website/_head'); ?>
</head>
<body class="x3d">
    <?php $this->load->view('saas_website/_header', array('currentPage' => 'demo')); ?>

    <section class="page-hero">
        <div class="page-hero-glow" aria-hidden="true"></div>
        <div class="hero-grid" aria-hidden="true"></div>
        <div class="container-xl">
            <span class="eyebrow reveal"><span class="pulse"></span> Live demo &middot; no sign-up needed</span>
            <h1 class="reveal">Take <span class="grad-text"><?php echo $brand ?></span> for a test drive</h1>
            <p class="lead reveal">A fully populated demo school with real classes, students and report cards. Pick a role below and see exactly what your school would see.</p>
        </div>
    </section>

    <?php if (!$demoBranch) { ?>
    <section class="section demo-section">
        <div class="container-xl">
            <div class="demo-empty glass-panel reveal">
                <i class="fas fa-tools"></i>
                <h3>The demo is being refreshed</h3>
                <p>We're resetting the demo school right now. Please check back in a few minutes.</p>
                <a href="<?php echo base_url() ?>#contact" class="btn-glow"><i class="fas fa-calendar-check"></i> Request a demo instead</a>
            </div>
        </div>
    </section>
    <?php } else { ?>

    <section class="demo-section">
        <div class="container-xl">
            <div class="role-grid">
                <?php foreach ($demoRoles as $i => $role) {
                    $loginUrl = base_url('authentication/index') . ($role['user'] ? '?demo_user=' . rawurlencode($role['user']) . '&demo_pass=' . rawurlencode($demoPassword) : '');
                    ?>
                <div class="tilt-card role-card tone-<?php echo $role['tone'] ?> reveal" style="--i:<?php echo $i ?>">
                    <div class="tilt-glare"></div>
                    <div class="role-top">
                        <span class="role-icon"><i class="fas <?php echo $role['icon'] ?>"></i></span>
                        <span class="role-badge"><?php echo $role['label'] ?></span>
                    </div>
                    <h3 class="role-name"><?php echo $role['who'] ? html_escape($role['who']) : '&mdash;' ?></h3>
                    <p class="role-blurb"><?php echo $role['blurb'] ?></p>
                    <div class="cred">
                        <span>Username</span>
                        <b><?php echo $role['user'] ? html_escape($role['user']) : 'n/a' ?></b>
                        <?php if ($role['user']) { ?>
                        <button type="button" class="copy-btn" data-copy="<?php echo html_escape($role['user']) ?>" aria-label="Copy username"><i class="far fa-copy"></i></button>
                        <?php } ?>
                    </div>
                    <div class="cred">
                        <span>Password</span>
                        <b><?php echo html_escape($demoPassword) ?></b>
                        <button type="button" class="copy-btn" data-copy="<?php echo html_escape($demoPassword) ?>" aria-label="Copy password"><i class="far fa-copy"></i></button>
                    </div>
                    <a href="<?php echo $loginUrl ?>" class="btn-glow w-100">Log in as <?php echo $role['label'] === 'School Admin' ? 'Admin' : $role['label'] ?> <i class="fas fa-arrow-right"></i></a>
                </div>
                <?php } ?>
            </div>
            <p class="demo-note reveal"><i class="fas fa-shield-alt"></i> This is a shared, read-only demo that is reset periodically. Please don't enter real personal information.</p>
        </div>
    </section>

    <section class="section demo-inside">
        <div class="container-xl">
            <div class="section-head center">
                <span class="tag">What's inside</span>
                <h2 class="reveal">A real school, <span class="grad-text">ready to explore</span></h2>
                <p class="reveal">Everything is pre-filled so you can click straight into results, report cards and ID cards without setting anything up.</p>
            </div>
            <div class="inside-grid">
                <?php foreach ($included as $i => $item) { ?>
                <div class="inside-item reveal" style="--i:<?php echo $i % 4 ?>">
                    <span><i class="fas <?php echo $item[0] ?>"></i></span>
                    <p><?php echo $item[1] ?></p>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>
    <?php } ?>

    <section class="demo-cta">
        <div class="container-xl">
            <div class="demo-panel reveal">
                <canvas id="ctaCanvas" aria-hidden="true"></canvas>
                <div class="demo-copy">
                    <h2>Ready to set up your own school?</h2>
                    <p>Tell us about your school and we'll walk you through <?php echo $brand ?> with your own workflows.</p>
                </div>
                <a href="<?php echo base_url() ?>#contact" class="btn-light btn-lg"><i class="fas fa-calendar-check"></i> Request a Demo</a>
            </div>
        </div>
    </section>

    <?php $this->load->view('saas_website/_footer', array('currentPage' => 'demo')); ?>
    <script type="text/javascript">
        document.querySelectorAll('.copy-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var text = btn.getAttribute('data-copy');
                var done = function () {
                    btn.classList.add('copied');
                    btn.innerHTML = '<i class="fas fa-check"></i>';
                    setTimeout(function () {
                        btn.classList.remove('copied');
                        btn.innerHTML = '<i class="far fa-copy"></i>';
                    }, 1600);
                };
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(done);
                } else {
                    var ta = document.createElement('textarea');
                    ta.value = text;
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    try { document.execCommand('copy'); done(); } catch (e) {}
                    document.body.removeChild(ta);
                }
            });
        });
    </script>
</body>
</html>
