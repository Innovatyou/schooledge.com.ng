    <!-- Slim brand bar for checkout-style pages (payment, purchase complete) -->
    <header class="flow-bar">
        <div class="container-xl">
            <a href="<?php echo base_url() ?>" class="flow-brand">
                <img src="<?php echo base_url('assets/frontend/images/skoolos/logo-light.svg') ?>" alt="<?php echo $global_config['institute_name'] ?>" width="165" height="36">
            </a>
            <div class="flow-actions">
                <span class="flow-secure d-none d-sm-inline-flex"><i class="fas fa-lock"></i> Secure checkout</span>
                <button type="button" class="theme-toggle" aria-label="Switch to dark mode" title="Toggle dark mode">
                    <i class="fas fa-moon"></i><i class="fas fa-sun"></i>
                </button>
            </div>
        </div>
    </header>
