<?php $this->load->view('home/layout/page_header'); ?>
<div class="se-modern">
<!-- Main Container Starts -->
<div class="container px-md-0 main-container">
    <!-- Content Starts -->
    <h3 class="main-heading2 mt-0"><?php echo $page_data['title']; ?></h3>
    <?php echo $page_data['description']; ?>
    <!-- Content Ends -->
    <!-- FAQ's Accordions Starts -->
    <div class="accordion se-accordion" id="accordion-faqs">
        <?php 
            $faq_list = $this->db->where('branch_id', $branchID)->get('front_cms_faq_list')->result_array();
            foreach ($faq_list as $key => $value) {
            ?>
        <div class="card">
            <!-- Card Header Starts -->
            <div class="card-header" id="faq<?php echo $key; ?>">
                <h5 class="card-title" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $key; ?>" aria-expanded="false" aria-controls="collapse<?php echo $key; ?>">
                    <a><?php echo $value['title'] ?></a>
                </h5>
            </div>
            <div id="collapse<?php echo $key; ?>" class="collapse" aria-labelledby="faq<?php echo $key; ?>" data-parent="#accordion-faqs">
                <div class="card-body">
                    <?php echo $value['description']; ?>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
    <!-- Accordion #5 Ends -->
</div>
<!-- Main Container Ends -->
</div>