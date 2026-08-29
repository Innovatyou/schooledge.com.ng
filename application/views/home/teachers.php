<?php $this->load->view('home/layout/page_header'); ?>
<div class="se-modern">
<!-- Main Container Starts -->
<div class="container px-md-0 main-container">
    <!-- Doctors Desigination Filters Starts -->
    <ul id="doctors-filter" class="list-unstyled list-inline se-filter-pills">
        <li class="list-inline-item"><a href="#" class="active" data-group="all">All Departments</a></li>
        <?php foreach ($departments as $row) { ?>
        <li class="list-inline-item"><a href="#" data-group="<?php echo $row['department_id']; ?>"><?php echo $row['department_name']; ?></a></li>
        <?php } ?>
    </ul>
    <!-- Doctors Desigination Filters Ends -->
    <!-- Doctors Bio List Starts -->
    <ul id="doctors-grid" class="row grid">
        <?php foreach ($doctor_list as $row) { ?>
        <li class="col-lg-3 col-md-6 col-sm-12 doctors-grid" data-groups='["all", "<?php echo $row['department']; ?>"]'>
            <div class="bio-box se-card se-tilt se-staff-card">
                <div class="profile-img">
                    <div class="dlab-border-left"></div>
                    <div class="dlab-border-right"></div>
                    <div class="dlab-media">
                        <img src="<?php echo get_image_url('staff', $row['photo']); ?>" alt="Doctor" class="img-fluid img-center-sm img-center-xs">
                    </div>
                    <div class="overlay">
                        <div class="overlay-txt">
                            <ul class="list-unstyled list-inline sm-links">
                                <li class="list-inline-item">
                                    <a href="<?php echo $row['facebook_url']; ?>"><i class="fab fa-facebook-f"></i></a>
                                </li>
                                <li class="list-inline-item">
                                    <a href="<?php echo $row['linkedin_url']; ?>"><i class="fab fa-linkedin-in"></i></a>
                                </li>
                                <li class="list-inline-item">
                                    <a href="<?php echo $row['twitter_url']; ?>"><i class="fab fa-twitter"></i></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="txt-holder txt-overflow">
                    <h5><?php echo $row['name']; ?></h5>
                    <p class="designation"><?php echo $row['department_name']; ?></p>
                </div>
            </div> 
        </li>
        <?php } ?>
    </ul>
    <!-- Doctors List Ends -->
</div>
<!-- Main Container Ends -->
</div>