<?php 
$appStatus = 0;
if ($getAuthentication->two_factor_authentication == 1 && $getAuthentication->two_fa_type == 'app' && !empty($getAuthentication->two_fa_code)) { 
    $appStatus = 1;
}
$emailStatus = 0;
if ($getAuthentication->two_factor_authentication == 1 && $getAuthentication->two_fa_type == 'email') { 
    $emailStatus = 1;
}
?>
<section class="panel">
    <header class="panel-heading">
        <h4 class="panel-title"><i class="fas fa-unlock"></i> <?=translate('my_2FA_setup')?></h4>
    <?php if ($tfa_config->show_remember == 1) { ?>
        <div class="panel-btn">
            <button type="button" id="saveBrowserbtn" class="btn btn-default btn-circle"  data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
            <i class="fas fa-shield-halved"></i> <?php echo translate('saved_login') ?>
            </button>
        </div>
    <?php } ?>
    </header>
    <div class="panel-body">
    <?php if ($appStatus == 1 || $emailStatus == 1) {
        $checkBackupCode = $this->two_fa_model->checkBackupCode();
        ?>
        <section class="panel pg-fw mt-md">
            <div class="panel-body">
                <h5 class="chart-title mb-xs"><i class="fas fa-file-export text-success"></i> <?php echo translate('download_backup_codes') ?></h5>
                <div class="mt-lg">
                    <div class="form-group">
                        <div class="col-xs-12 mb-md">
                            <p>These are important codes that you will need to log in to your account if you don't have access to an authenticator APP / Email.</p>
                        <?php if (!empty($checkBackupCode->created_at)) {
                            ?>
                            <p class="text-success mb-none"><strong>You downloaded the backup code on : <?php echo _d($checkBackupCode->created_at) . " at " . date("g:i A", strtotime($checkBackupCode->created_at)) ?>.</strong></p>
                        <?php if ($checkBackupCode->status_count < 10) { ?>
                            <p class="mb-none">You have used <strong class="text-dark"><?php echo str_pad((10 - $checkBackupCode->status_count), 2, "0", STR_PAD_LEFT) ?></strong> backup codes.</p>
                        <?php } ?>
                            <p class="text-danger">* If you regenerate the backup code, previously downloaded codes will become invalid.</p>
                            <button class="btn btn-default btn_bc_download" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing"><i class="fas fa-repeat"></i> <?php echo translate('regenerate') ?></button>
                        <?php } else { ?>
                            <button class="btn btn-default btn_bc_download" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing"><i class="fas fa-download"></i> <?php echo translate('download') ?></button>
                        <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php } ?>
        <div class="row">
            <div class="col-md-6">
                <section class="panel pg-fw">
                    <div class="panel-body">
                        <h5 class="chart-title mb-xs"><?php echo $appStatus == 1 ? '<i class="fas fa-circle-check text-success"></i>' : ''; ?> <?php echo translate('two_factor_authentication') ?> (APP)</h5>
                        <div class="mt-lg text-center">
                            <div class="form-group mt-md">
                                <?php
                                $qrCode = $this->ciqrcode->generate($qrconfig);
                                echo '<img class="img-responsive" style="margin: 0 auto;" src="' . base_url($qrCode . "?s=" . $secret) . '">';
                                ?>
                            </div>
                            <div class="form-group">
                                <div class="col-xs-12 mt-lg">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="authentication_code"  id="authenticationCode" autocomplete="off" readonly="" value="<?php echo $secret ?>">
                                        <span class="input-group-addon">
                                            <span class="input-group-text">
                                                <a style="text-decoration: none;" href="javascript:void(0);" id="textCopy"><i class="fas fa-copy"></i></a>
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-xs-12 mb-md">
                                <?php if ($appStatus == 1) {  ?>
                                    <p class="text-success"><i class="fas fa-circle-check"></i> You have two factor authentication enabled.</p>
                                <?php } ?>
                                    <button type="button" class="btn <?php echo ($appStatus == 1 ? "btn-danger" : "btn-default" ) ?> " id="authenticator_btn" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
                                        <?php echo ($appStatus == 1 ? translate('disable') : translate('enable') ) . " " . translate('two_factor_authentication') ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <div class="col-md-6">
                <section class="panel pg-fw">
                    <div class="panel-body">
                        <h5 class="chart-title mb-xs"><?php echo $emailStatus == 1 ? '<i class="fas fa-circle-check text-success"></i>' : ''; ?>  <?php echo translate('two_factor_authentication') ?> (Email)</h5>
                        <div class="mt-lg">
                            <div class="form-group">
                                <div class="col-md-12">
                                    <label  class="control-label"><?=translate('email')?> <span class="required">*</span></label>
                                    <input type="text" class="form-control" readonly name="email" value="<?php echo $this->session->userdata('loggedin_email') ?>" />
                                    <span class="error"></span>
                                </div>
                            </div>

                            <div class="form-group text-center">
                                <div class="col-xs-12 mb-md">
                                <?php if ($emailStatus == 1) {  ?>
                                    <p class="text-success"><i class="fas fa-circle-check"></i> You have two factor authentication enabled.</p>
                                <?php } ?>
                                    <button type="button" class="btn <?php echo ($emailStatus == 1 ? "btn-danger" : "btn-default" ) ?> " id="email_authenticator_btn" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
                                        <?php echo ($emailStatus == 1 ? translate('disable') : translate('enable') ) . " " . translate('two_factor_authentication') ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</section>

<div class="zoom-anim-dialog modal-block modal-block-primary mfp-hide" id="appModal">
    <section class="panel">
        <header class="panel-heading">
            <h4 class="panel-title">
                <i class="fas fa-fingerprint"></i> <?php echo translate('two_factor_authentication') ?> (APP)
            </h4>
        </header>
        <?php echo form_open(base_url('two_factor_authentication/twoStepAPPEnable'), array('class' => 'frm-submit')); ?>
            <div class="panel-body">
            <?php if ($emailStatus == 1) { ?>
                <h4 class="text-danger text-center">First Disable Two Factor Authentication by Email.</h4>
            <?php } else { ?>
                <input type="hidden" name="secret_key" id="secretKey" value="<?php echo $appStatus == 1 ? $getAuthentication->two_fa_code :  $secret ?>" />
                <input type="hidden" name="app_2fa_status" value="<?php echo $appStatus ?>" />
                <div class="form-group mb-lg mt-md">
                    <input type="text" class="form-control" name="authenticator_code" id="authenticatorCode" placeholder="Enter verification code from your Authenticator APP" autocomplete="off" value="" />
                    <span class="error"></span>
                </div>
            <?php } ?>
            </div>
            <footer class="panel-footer">
                <div class="row">
                    <div class="col-md-12 text-right">
                    <?php if ($emailStatus == 0) { ?>
                        <button type="submit" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing" class="btn btn-default mr-xs"><?php echo translate('verify'); ?></button>
                    <?php } ?>
                        <button type="button" class="btn btn-default modal-dismiss"><?php echo translate('close'); ?></button>
                    </div>
                </div>
            </footer>
        <?php echo form_close(); ?>
    </section>
</div>

<div class="zoom-anim-dialog modal-block modal-block-primary mfp-hide" id="emailModal">
    <section class="panel">
        <header class="panel-heading">
            <h4 class="panel-title">
                <i class="fas fa-fingerprint"></i> <?php echo translate('two_factor_authentication') ?> (Email)
            </h4>
        </header>
        <?php echo form_open(base_url('two_factor_authentication/twoStepEmailEnable'), array('class' => 'frm-submit')); ?>
            <div class="panel-body">
            <?php if ($appStatus == 1) { ?>
                <h4 class="text-danger text-center">First Disable Two Factor Authentication by APP.</h4>
            <?php } else { ?>
                <input type="hidden" name="email_2fa_status" value="<?php echo $emailStatus ?>" />
                <div class="form-group mb-lg mt-md">
                    <input type="text" class="form-control" name="verification_code" id="verification_code" placeholder="Enter the verification code sent to the Email" autocomplete="off" value="" />
                    <span class="error"></span>
                </div>
            <?php } ?>
            </div>
            <footer class="panel-footer">
                <div class="row">
                    <div class="col-md-12 text-right">
                    <?php if ($appStatus == 0) { ?>
                        <button type="submit" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing" class="btn btn-default mr-xs"><?php echo translate('verify'); ?></button>
                    <?php } ?>
                        <button type="button" class="btn btn-default modal-dismiss"><?php echo translate('close'); ?></button>
                    </div>
                </div>
            </footer>
        <?php echo form_close(); ?>
    </section>
</div>

<div class="zoom-anim-dialog modal-block modal-block-full mfp-hide" id="saveBrowserModal">
    <section class="panel">
        <header class="panel-heading">
            <h4 class="panel-title">
                <i class="fas fa-shield-halved"></i> Login Saved Browser List
            </h4>
        </header>
        <?php echo form_open(base_url('two_factor_authentication/twoStepEmailEnable'), array('class' => 'frm-submit')); ?>
            <div class="panel-body" id="browserTable">
            </div>
            <footer class="panel-footer">
                <div class="row">
                    <div class="col-md-12 text-right">
                        <button type="button" class="btn btn-default modal-dismiss"><?php echo translate('close'); ?></button>
                    </div>
                </div>
            </footer>
    </section>
</div>

<script type="text/javascript">
<?php if ($tfa_config->show_remember) { ?>
    $("#saveBrowserbtn").on( "click", function() {
        var btn = $(this);
        $.ajax({
            url: base_url + "two_factor_authentication/browser_savedlogin_ajax",
            beforeSend: function () {
                btn.button('loading');
            },
            dataType: "html",
            success: function (data) {
                $('#browserTable').html(data);
                mfp_modal('#saveBrowserModal');
            },
            error: function () {
                btn.button('reset');
            },
            complete: function () {
                btn.button('reset');

            }
        });
    });
<?php } ?>

    $("#authenticator_btn").on( "click", function() {
        $(".error").html("");
        $("#authenticatorCode").val("");
        mfp_modal('#appModal');
    });

    $("#email_authenticator_btn").on( "click", function() {
        $(".error").html("");
        $("#verification_code").val("");
        var emailStatus ="<?php echo $emailStatus ?>";
        var btn = $(this);
        $.ajax({
            url: base_url + "two_factor_authentication/ajax2FASend",
            type: 'POST',
            beforeSend: function () {
                btn.button('loading');
            },
            data: {
                'status': emailStatus
            },
            dataType: "json",
            success: function (data) {
                if(data.status == true) {
                    mfp_modal('#emailModal');
                } else {
                    btn.button('reset');
                    popupMsg(data.msg, 'error');
                }
            },
            error: function () {
                btn.button('reset');
            },
             complete: function () {
                btn.button('reset');

            }
        });
    });

    $("#textCopy").on("click", function() {
        var copyText = document.getElementById("authenticationCode");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value);
        popupMsg('Copied : ' + copyText.value);
    });

    $(document).on("click",".btn_bc_download",function() {
        var btn = $(this);
        $.ajax({
            url: base_url + "two_factor_authentication/download_backup_codes",
            dataType: "json",
            cache: false,
            beforeSend: function () {
                btn.button('loading');
            },
            success: function (res, jqXHR, response) {
                if(res.status == 'success') {
                    var blob =  new Blob([res.data], {type: 'plain/text'});
                    var link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = res.title;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            },
            error: function () {
                btn.button('reset');
            },
            complete: function () {
                window.location.reload();
            }
        });
    });
</script>