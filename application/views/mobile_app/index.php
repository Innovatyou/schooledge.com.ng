<section class="panel">
	<div class="panel-body">
		<h3 class="mt-0"><?=translate('mobile_app')?></h3>
		<p class="text-muted">
			Give staff, parents and students on-the-go access to the portal from their phones &mdash; attendance, fees, homework, results and messages, all from the SchoolEdge app.
		</p>

		<div class="row" style="margin-top:25px;">
			<div class="col-md-6">
				<div class="callout callout-info" style="border-left:4px solid #4099ff;background:#f4f8ff;padding:25px;border-radius:4px;">
					<h4 class="mt-0"><i class="fab fa-android" style="color:#3ddc84;"></i> Android</h4>
					<?php if ($apk_available) { ?>
						<p>Download the SchoolEdge APK and install it directly on any Android phone or tablet.</p>
						<p class="text-muted mb-lg"><?=$apk_size?> MB &middot; Android 7.0 and above</p>
						<a href="<?=$apk_url?>" class="btn btn-primary" download>
							<i class="fas fa-download"></i> <?=translate('download_for_android')?>
						</a>
						<p class="text-muted mt-lg mb-0" style="font-size:12px;">
							<i class="fas fa-info-circle"></i> Your phone may warn about installing from outside the Play Store &mdash; open the downloaded file and allow installation from this source to continue.
						</p>
					<?php } else { ?>
						<p class="text-muted mb-0">The Android app isn't available for download yet. Please check back shortly.</p>
					<?php } ?>
				</div>
			</div>
			<div class="col-md-6">
				<div class="callout" style="border-left:4px solid #ccc;background:#f7f7f7;padding:25px;border-radius:4px;">
					<h4 class="mt-0"><i class="fab fa-apple"></i> iOS</h4>
					<p class="text-muted mb-lg">The iPhone/iPad app is on its way.</p>
					<button class="btn btn-default" disabled>
						<i class="fas fa-clock"></i> Coming Soon
					</button>
				</div>
			</div>
		</div>
	</div>
</section>
