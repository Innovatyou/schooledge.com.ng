<?php
/**
 * Visual picker for the public website template (front_cms_setting.theme).
 * Param: $selected (a website_themes() key). Posts the "theme" field.
 * Each tile is a small CSS-drawn mock-up of that template's home page.
 */
$selected = website_theme(isset($selected) ? $selected : 'classic');
?>
<style type="text/css">
	.wtp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 12px; }
	.wtp-tile { position: relative; display: block; margin: 0; cursor: pointer; border-radius: 12px; border: 2px solid #e3e6ee; background: #fff; overflow: hidden; font-weight: normal; transition: border-color .2s, box-shadow .2s, transform .2s; }
	.wtp-tile:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(20, 24, 60, .1); }
	.wtp-tile input { position: absolute; opacity: 0; pointer-events: none; }
	.wtp-tile.is-active { border-color: #7c5cff; box-shadow: 0 0 0 3px rgba(124, 92, 255, .18); }
	.wtp-check { position: absolute; top: 8px; right: 8px; z-index: 5; display: none; width: 22px; height: 22px; border-radius: 50%; background: #7c5cff; color: #fff; font-size: 11px; line-height: 22px; text-align: center; }
	.wtp-tile.is-active .wtp-check { display: block; }
	.wtp-mock { position: relative; display: block; height: 120px; overflow: hidden; perspective: 400px; }
	.wtp-mock i, .wtp-mock b, .wtp-mock em { position: absolute; display: block; font-style: normal; }
	.wtp-meta { display: block; padding: 9px 11px 11px; border-top: 1px solid #eef0f5; }
	.wtp-meta strong { display: block; font-size: 13px; color: #1d2440; }
	.wtp-meta small { display: block; margin-top: 3px; color: #6b7287; font-size: 11.5px; line-height: 1.35; }
	/* classic */
	.wtp-classic { background: #fff; }
	.wtp-classic b { left: 10px; right: 10px; top: 10px; height: 8px; border-radius: 3px; background: #e7eaf2; }
	.wtp-classic i { left: 10px; top: 28px; width: 60%; height: 10px; border-radius: 3px; background: #577fee; }
	.wtp-classic em { left: 10px; right: 10px; top: 48px; bottom: 10px; border-radius: 6px; background: linear-gradient(135deg, #dbe4ff, #eef2ff); }
	/* aurora */
	.wtp-aurora { background: radial-gradient(120px 80px at 75% 40%, #5b46d6, transparent 70%), #0b0f1f; }
	.wtp-aurora b { left: 10px; top: 30px; width: 70px; height: 10px; border-radius: 3px; background: #fff; box-shadow: 0 16px 0 -2px rgba(255,255,255,.4); }
	.wtp-aurora i { width: 46px; height: 56px; border-radius: 8px; border: 1px solid rgba(255,255,255,.3); background: linear-gradient(135deg, #8b7bff, #22d3ee); transform: rotateY(-25deg); }
	.wtp-aurora i:nth-of-type(1) { right: 34px; top: 16px; }
	.wtp-aurora i:nth-of-type(3) { display: none; }
	.wtp-aurora i:nth-of-type(2) { right: 12px; top: 44px; width: 36px; height: 44px; transform: rotateY(-25deg) translateZ(30px); background: linear-gradient(135deg, #f472b6, #8b5cf6); }
	.wtp-aurora em { left: 0; right: 0; bottom: 0; height: 40px; background-image: linear-gradient(rgba(124,92,255,.5) 1px, transparent 1px), linear-gradient(90deg, rgba(124,92,255,.5) 1px, transparent 1px); background-size: 12px 12px; transform: rotateX(60deg); transform-origin: bottom; }
	/* campus */
	.wtp-campus { background: linear-gradient(180deg, #eef3ff, #f6f8fc); }
	.wtp-campus b { left: 10px; top: 32px; width: 64px; height: 10px; border-radius: 3px; background: #0f172a; box-shadow: 0 16px 0 -2px #cbd5e1; }
	.wtp-campus i { right: 10px; top: 14px; width: 80px; height: 80px; border-radius: 12px; background: linear-gradient(135deg, #93c5fd, #2563eb); transform: rotateY(-20deg); box-shadow: 0 10px 20px rgba(15,23,42,.2); }
	.wtp-campus em { right: 66px; bottom: 14px; width: 38px; height: 30px; border-radius: 7px; border: 3px solid #fff; background: linear-gradient(135deg, #fde68a, #f59e0b); box-shadow: 0 8px 16px rgba(15,23,42,.2); }
	/* prism */
	.wtp-prism { background: radial-gradient(60px 60px at 15% 20%, rgba(249,115,22,.55), transparent 70%), radial-gradient(70px 70px at 85% 30%, rgba(139,92,246,.55), transparent 70%), #fffaf3; }
	.wtp-prism b { left: 50%; top: 18px; width: 90px; margin-left: -45px; height: 10px; border-radius: 6px; background: #2b1d3f; box-shadow: 0 16px 0 -2px #d9cbe7; }
	.wtp-prism i { bottom: 12px; width: 34px; height: 40px; border-radius: 10px; border: 3px solid #fff; box-shadow: 0 6px 14px rgba(43,29,63,.2); }
	.wtp-prism i:nth-of-type(1) { left: 22px; background: #f97316; transform: rotateY(40deg); }
	.wtp-prism i:nth-of-type(2) { left: 50%; margin-left: -17px; bottom: 18px; background: #8b5cf6; }
	.wtp-prism i:nth-of-type(3) { right: 22px; background: #10b981; transform: rotateY(-40deg); }
	/* heritage */
	.wtp-heritage { background: #0f1e3d; }
	.wtp-heritage b { left: 10px; top: 30px; width: 66px; height: 10px; border-radius: 2px; background: #fff; box-shadow: 0 16px 0 -3px #c9a24d; }
	.wtp-heritage i { right: 22px; top: 14px; width: 54px; height: 82px; border-radius: 30px 30px 4px 4px; border: 3px solid #fff; background: linear-gradient(180deg, #5b7bb8, #20345f); }
	.wtp-heritage em { right: 14px; top: 20px; width: 60px; height: 86px; border-radius: 34px 34px 4px 4px; border: 1.5px solid #c9a24d; }
</style>
<div class="wtp-grid" id="websiteThemePicker" role="radiogroup" aria-label="Website template">
<?php foreach (website_themes() as $key => $meta) {
	$active = ($key === $selected); ?>
	<label class="wtp-tile<?php echo $active ? ' is-active' : ''; ?>">
		<input type="radio" name="theme" value="<?php echo html_escape($key); ?>"<?php echo $active ? ' checked' : ''; ?>>
		<span class="wtp-mock wtp-<?php echo html_escape($key); ?>" aria-hidden="true">
			<span class="wtp-check">&#10003;</span>
			<b></b><i></i><i></i><i></i><em></em>
		</span>
		<span class="wtp-meta">
			<strong><?php echo html_escape($meta['label']); ?></strong>
			<small><?php echo html_escape($meta['desc']); ?></small>
		</span>
	</label>
<?php } ?>
</div>
<script type="text/javascript">
	(function () {
		var grid = document.getElementById('websiteThemePicker');
		if (!grid) return;
		grid.addEventListener('change', function () {
			grid.querySelectorAll('.wtp-tile').forEach(function (tile) {
				tile.classList.toggle('is-active', tile.querySelector('input').checked);
			});
		});
	})();
</script>
