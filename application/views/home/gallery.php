<?php $this->load->view('home/layout/page_header'); ?>
<div class="se-modern">
<div class="container gallery-album px-md-0 main-container">
    <ul id="doctors-filter" class="list-unstyled list-inline se-filter-pills">
        <li class="list-inline-item"><a href="#" class="active" data-group="all">All</a></li>
        <?php foreach ($category as $row) { ?>
        <li class="list-inline-item"><a href="#" data-group="<?php echo $row['category_id']; ?>"><?php echo $row['category_name']; ?></a></li>
        <?php } ?>
    </ul>
    <ul id="doctors-grid" class="row grid">
        <?php
        $school = $this->uri->segment(1);
        foreach ($galleryList as $row) { ?>
        <li class="doctors-grid col-lg-4 col-md-6" data-groups='["all", "<?php echo $row['category_id']; ?>"]'>
            <div class="bio-box se-card se-tilt se-staff-card">
                <div class="gallery-album-item">
                    <div class="gallery-album-img">
                        <img src="<?php echo $this->gallery_model->get_image_url($row['thumb_image']); ?>" alt="Image">
                    </div>
                    <div class="gallery-album-title">
                        <h3><?php echo $row['title'] ?></h3>
                        <a class="btn" href="<?php echo base_url("$school/gallery_view/" . $row['alias'] ); ?>"><i class="fas fa-photo-video"></i></a>
                    </div>
                    <div class="gallery-album-meta">
                        <p>By<a href=""><?php echo $row['staff_name'] ?></a></p>
                    </div>
                    <div class="gallery-album-text">
                        <p><?php echo $row['description']; ?></p>
                    </div>
                </div>
            </div>
        </li>
        <?php } ?>
    </ul>
</div>
</div>
