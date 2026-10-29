<?php
// Shared marketing-site footer + scripts. Set $withThree = true on pages that
// render the WebGL hero; other pages skip the ~600 KB Three.js download.
$onLanding = isset($currentPage) && $currentPage === 'home';
$anchor = $onLanding ? '' : base_url();
$brandName = $global_config['institute_name'];
?>
    <!-- Start Footer Area -->
    <footer class="footer3d">
        <div class="container-xl">
            <div class="footer-grid">
                <div class="f-about">
                    <a href="<?php echo base_url() ?>" class="f-logo"><img src="<?php echo base_url('assets/frontend/images/skoolos/logo-light.svg') ?>" alt="<?php echo $brandName ?>" width="220" height="48"></a>
                    <p><?php echo $getSettings->footer_about; ?></p>
                    <ul class="social">
                        <li><a href="<?php echo $global_config['facebook_url'] ?>" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a></li>
                        <li><a href="<?php echo $global_config['twitter_url'] ?>" aria-label="Twitter"><i class="fab fa-twitter"></i></a></li>
                        <li><a href="<?php echo $global_config['instagram_url'] ?>" aria-label="Instagram"><i class="fab fa-instagram"></i></a></li>
                        <li><a href="<?php echo $global_config['linkedin_url'] ?>" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a></li>
                        <li><a href="<?php echo $global_config['youtube_url'] ?>" aria-label="YouTube"><i class="fab fa-youtube"></i></a></li>
                    </ul>
                </div>
                <div class="f-col">
                    <h4>Product</h4>
                    <ul>
                        <li><a href="<?php echo $anchor ?>#modules">Modules</a></li>
                        <li><a href="<?php echo $anchor ?>#features"><?php echo translate('features'); ?></a></li>
                        <li><a href="<?php echo $anchor ?>#portals">Portals</a></li>
                        <li><a href="<?php echo base_url('saas_website/demo') ?>"><?php echo translate('live_demo'); ?></a></li>
                    </ul>
                </div>
                <div class="f-col">
                    <h4>Company</h4>
                    <ul>
                        <li><a href="<?php echo $anchor ?>#faq"><?php echo translate('faq'); ?></a></li>
                        <li><a href="<?php echo $anchor ?>#contact"><?php echo translate('contact'); ?></a></li>
                        <?php if ($getSettings->terms_status == 1) { ?>
                        <li><a href="javascript:void(0)" id="termsFooter"><?php echo translate('terms_&_conditions'); ?></a></li>
                        <?php } ?>
                    </ul>
                </div>
                <div class="f-col">
                    <h4><?php echo translate('address'); ?></h4>
                    <ul class="f-contact">
                        <li><i class="fas fa-map-marker-alt"></i> <?php echo $global_config['address'] ?></li>
                        <li><i class="fas fa-phone-alt"></i> <?php echo $global_config['mobileno'] ?></li>
                        <li><i class="fas fa-envelope"></i> <?php echo $global_config['institute_email'] ?></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <span><?php echo $global_config['footer_text']; ?></span>
                <img src="<?php echo base_url('assets/frontend/images/saas/' . $getSettings->payment_logo); ?>" alt="Payment methods">
            </div>
        </div>
    </footer>
    <!--/ End Footer Area -->

    <a href="#" class="scroll-top" aria-label="Back to top"><i class="fas fa-chevron-up"></i></a>

    <?php if ($getSettings->terms_status == 1) { ?>
    <div class="modal fade" id="termsModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header p-2">
                    <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>

    <!-- ========================= JS here ========================= -->
    <script src="<?php echo base_url('assets/frontend/js/bootstrap.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/frontend/js/wow.min.js'); ?>"></script>
    <script src="<?php echo base_url('assets/frontend/js/saas_main.js?v=' . filemtime(FCPATH . 'assets/frontend/js/saas_main.js')); ?>"></script>
    <script src="<?php echo base_url('assets/vendor/sweetalert/sweetalert.min.js');?>"></script>
    <?php if (!empty($withThree)) { ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <?php } ?>
    <script src="<?php echo base_url('assets/frontend/js/saas-3d.js?v=' . filemtime(FCPATH . 'assets/frontend/js/saas-3d.js')); ?>"></script>
