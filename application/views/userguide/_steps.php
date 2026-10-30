<?php
// Step timeline for one guide. Params: $guide (from module_guides()), $storeKey (localStorage key)
// Each step number is a button that ticks the step off; the tick is remembered in this browser.
?>
<div class="gd-progress" data-guide-progress="<?php echo $storeKey; ?>">
	<span class="gd-meter"><i></i></span>
	<span data-label>0 of <?php echo count($guide['block']['steps']); ?> done</span>
</div>
<ol class="gd-timeline" data-guide-store="<?php echo $storeKey; ?>">
	<?php foreach ($guide['block']['steps'] as $i => $step) { ?>
	<li>
		<button type="button" class="gd-num" aria-pressed="false" aria-label="Step <?php echo $i + 1; ?>. Mark as done"></button>
		<div class="gd-step">
			<h4><?php echo $step[0]; ?></h4>
			<p><?php echo $step[1]; ?></p>
			<?php if (!empty($step[2])) { ?>
			<a class="gd-open" href="<?php echo base_url($step[2]); ?>">Go there <i class="fas fa-arrow-right"></i></a>
			<?php } ?>
		</div>
	</li>
	<?php } ?>
</ol>
