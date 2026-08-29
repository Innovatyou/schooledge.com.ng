<?php $this->load->view('home/layout/page_header'); ?>
<div class="se-modern">
<!-- Main Container Starts -->
        <div class="container px-md-0 main-container">
            <h2 class="main-heading2 nomargin-top"><?php echo $gallery['title']; ?></h2>
            <div class="gallery-grid text-center">
                <div class="row">
                <!-- Gallery Image #1 Starts -->
                <?php
                $elem = $gallery['elements'];
                if (!empty($elem)) {
                $elem = json_decode($elem, TRUE);
                foreach ($elem as $key => $row) {
                    $url = "";
                    $isVideo = false;
                    $icon = "";
                    if ($row['type'] == 2) {
                        $isVideo = true;
                        $icon = 'fab fa-youtube';
                        $url = $row['video_url'];
                    } else {
                        $icon = 'fa fa-image';
                        $url = $this->gallery_model->get_image_url($row['image']);
                    }
                 ?>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="hover-content se-card se-tilt">
                            <img src="<?php echo $this->gallery_model->get_image_url($row['image']); ?>" alt="Gallery Image" class="img-fluid">
                            <div class="overlay">
                                <a href="<?php echo $url; ?>" class="btn btn-1 glightbox" data-gallery="gallery-<?php echo $gallery['id']; ?>" <?php if ($isVideo): ?>data-type="video"<?php endif; ?>><i class="<?=$icon?>"></i></a>
                            </div>
                        </div>
                    </div>
                <?php } } ?>
                </div>
            </div>
</div>
<!-- Main Container Ends -->
</div>
