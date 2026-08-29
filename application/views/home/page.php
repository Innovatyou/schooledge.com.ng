<?php $breadcrumb_items = array(array('label' => $page_data['menu_title'], 'url' => null)); ?>
<?php $this->load->view('home/layout/page_header'); ?>
<div class="se-modern">
<!-- Main Container Starts -->
<div class="container px-md-0 main-container">
    <?php echo $page_data['content']; ?>
</div>
</div>