
<li class="nav-parent <?php if ($main_menu == 'tfa') echo 'nav-expanded nav-active';?>">
    <a>
        <i class="fas fa-fingerprint"></i><span><?=translate('2_FA_security')?></span>
    </a>
    <ul class="nav nav-children">
    <?php if (get2FA_config()->status) { ?>
        <li class="<?php if ($sub_page == 'two_factor_authentication/setup_tfa') echo 'nav-active';?>">
            <a href="<?=base_url('two_factor_authentication/setup_tfa')?>">
                <span><i class="fas fa-caret-right" aria-hidden="true"></i><?=translate('my_2FA_setup')?></span>
            </a>
        </li>
    <?php } 
    if(get_permission('two_fa_settings', 'is_view')) {  ?>
        <li class="<?php if ($sub_page == 'two_factor_authentication/settings') echo 'nav-active';?>">
            <a href="<?=base_url('two_factor_authentication/settings')?>">
                <span><i class="fas fa-caret-right" aria-hidden="true"></i><?=translate('settings')?></span>
            </a>
        </li>
    <?php } ?>
    </ul>
</li>
