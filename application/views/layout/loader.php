<?php
// 3D page-loading animation built from the school's logo.
// Params: $loader (from panel_loader()), $preview (true inside the settings preview tiles)
// The panel's LoadingOverlay plugin hides .loading-overlay when the page has loaded.
$preview = !empty($preview);
$logo = '<img src="' . $loader['logo'] . '" alt="">';
$text = $loader['text'] !== '' ? $loader['text'] : 'Loading';
?>
<?php if (!defined('SK_LOADER_CSS')) { define('SK_LOADER_CSS', 1); ?>
<style type="text/css">
	.sk-loader { --ld-a: #7c5cff; --ld-b: #a58dff; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 30px; background: radial-gradient(520px 380px at 50% 42%, color-mix(in srgb, var(--ld-a) 38%, transparent), transparent 70%), radial-gradient(900px 600px at 50% 120%, rgba(6, 182, 212, .18), transparent 60%), #0b0f24; font-family: 'Plus Jakarta Sans', 'Signika', system-ui, sans-serif; }
	.sk-loader.is-preview { position: relative; height: 150px; gap: 14px; border-radius: 12px 12px 0 0; opacity: 1; visibility: visible; }
	.ldr-stage { position: relative; display: grid; place-items: center; width: 150px; height: 150px; perspective: 600px; }
	.is-preview .ldr-stage { transform: scale(.55); margin: -34px; }
	.ldr-logo, .ldr-face { display: grid; place-items: center; width: 74px; height: 74px; padding: 12px; border-radius: 22px; background: #fff; box-shadow: 0 18px 40px rgba(0, 0, 0, .45), 0 0 0 1px rgba(255, 255, 255, .5) inset; }
	.ldr-logo img, .ldr-face img { max-width: 100%; max-height: 100%; object-fit: contain; }
	.ldr-text { margin: 0; color: rgba(255, 255, 255, .85); font-size: 14px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
	.ldr-text::after { content: ""; display: inline-block; width: 1.2em; text-align: left; animation: ldrDots 1.4s steps(4) infinite; }
	@keyframes ldrDots { 0% { content: ""; } 25% { content: "."; } 50% { content: ".."; } 75% { content: "..."; } }
	.ldr-bar { position: relative; width: 160px; height: 4px; border-radius: 4px; background: rgba(255, 255, 255, .12); overflow: hidden; }
	.ldr-bar i { position: absolute; top: 0; bottom: 0; left: -40%; width: 40%; border-radius: inherit; background: linear-gradient(90deg, var(--ld-a), var(--ld-b)); animation: ldrBar 1.3s ease-in-out infinite; }
	@keyframes ldrBar { to { left: 100%; } }
	.is-preview .ldr-text { font-size: 10px; }
	.is-preview .ldr-bar { width: 90px; }

	/* orbit: logo floating inside three tilted rings */
	.ldr-is-orbit .ldr-logo { position: relative; z-index: 2; animation: ldrFloat 2.4s ease-in-out infinite; }
	.ldr-ring { position: absolute; inset: 0; margin: auto; border-radius: 50%; border: 2px solid color-mix(in srgb, var(--ld-a) 70%, transparent); transform-style: preserve-3d; }
	.ldr-ring::after { content: ""; position: absolute; top: -5px; left: 50%; width: 10px; height: 10px; margin-left: -5px; border-radius: 50%; background: var(--ld-b); box-shadow: 0 0 14px var(--ld-b); }
	.ldr-ring.r1 { width: 150px; height: 150px; animation: ldrSpinA 2.2s linear infinite; }
	.ldr-ring.r2 { width: 128px; height: 128px; border-color: rgba(34, 211, 238, .6); animation: ldrSpinB 2.8s linear infinite; }
	.ldr-ring.r2::after { background: #22d3ee; box-shadow: 0 0 14px #22d3ee; }
	.ldr-ring.r3 { width: 106px; height: 106px; border-color: rgba(251, 191, 36, .55); animation: ldrSpinC 3.4s linear infinite; }
	.ldr-ring.r3::after { background: #fbbf24; box-shadow: 0 0 14px #fbbf24; }
	@keyframes ldrSpinA { from { transform: rotateX(70deg) rotateZ(0); } to { transform: rotateX(70deg) rotateZ(360deg); } }
	@keyframes ldrSpinB { from { transform: rotateY(65deg) rotateZ(0); } to { transform: rotateY(65deg) rotateZ(-360deg); } }
	@keyframes ldrSpinC { from { transform: rotateX(-55deg) rotateY(30deg) rotateZ(0); } to { transform: rotateX(-55deg) rotateY(30deg) rotateZ(360deg); } }
	@keyframes ldrFloat { 50% { transform: translateY(-8px); } }

	/* flip: logo card turning over */
	.ldr-card { position: relative; width: 74px; height: 74px; transform-style: preserve-3d; animation: ldrFlip 2.4s cubic-bezier(.6, 0, .4, 1) infinite; }
	.ldr-card .ldr-face { position: absolute; inset: 0; backface-visibility: hidden; -webkit-backface-visibility: hidden; }
	.ldr-card .ldr-face.back { transform: rotateY(180deg); background: linear-gradient(135deg, var(--ld-a), var(--ld-b)); }
	.ldr-card .ldr-face.back img { filter: brightness(0) invert(1); }
	.ldr-is-flip .ldr-stage::after { content: ""; position: absolute; bottom: 18px; width: 70px; height: 12px; border-radius: 50%; background: rgba(0, 0, 0, .45); filter: blur(6px); animation: ldrShadow 1.2s ease-in-out infinite alternate; }
	@keyframes ldrFlip { 0% { transform: rotateY(0) rotateX(10deg); } 50% { transform: rotateY(180deg) rotateX(-10deg); } 100% { transform: rotateY(360deg) rotateX(10deg); } }
	@keyframes ldrShadow { to { transform: scaleX(.6); opacity: .5; } }

	/* cube: logo on every face */
	.ldr-cube { position: relative; width: 74px; height: 74px; transform-style: preserve-3d; animation: ldrCube 3.6s cubic-bezier(.6, 0, .4, 1) infinite; }
	.ldr-cube .ldr-face { position: absolute; inset: 0; border-radius: 14px; box-shadow: 0 0 0 1px rgba(255, 255, 255, .6) inset, 0 0 30px color-mix(in srgb, var(--ld-a) 40%, transparent) inset; }
	.ldr-cube .ldr-face:nth-child(1) { transform: translateZ(37px); }
	.ldr-cube .ldr-face:nth-child(2) { transform: rotateY(90deg) translateZ(37px); }
	.ldr-cube .ldr-face:nth-child(3) { transform: rotateY(180deg) translateZ(37px); }
	.ldr-cube .ldr-face:nth-child(4) { transform: rotateY(-90deg) translateZ(37px); }
	.ldr-cube .ldr-face:nth-child(5) { transform: rotateX(90deg) translateZ(37px); background: linear-gradient(135deg, var(--ld-a), var(--ld-b)); }
	.ldr-cube .ldr-face:nth-child(6) { transform: rotateX(-90deg) translateZ(37px); background: linear-gradient(135deg, var(--ld-b), var(--ld-a)); }
	@keyframes ldrCube { 0% { transform: rotateX(-24deg) rotateY(0); } 25% { transform: rotateX(-24deg) rotateY(90deg); } 50% { transform: rotateX(-24deg) rotateY(180deg); } 75% { transform: rotateX(-24deg) rotateY(270deg); } 100% { transform: rotateX(-24deg) rotateY(360deg); } }

	/* pulse: logo on ripples */
	.ldr-is-pulse .ldr-logo { position: relative; z-index: 2; animation: ldrBeat 1.4s ease-in-out infinite; }
	.ldr-wave { position: absolute; inset: 0; margin: auto; width: 74px; height: 74px; border-radius: 22px; border: 2px solid var(--ld-b); transform: rotateX(60deg); animation: ldrWave 2.1s ease-out infinite; }
	.ldr-wave:nth-child(2) { animation-delay: .7s; }
	.ldr-wave:nth-child(3) { animation-delay: 1.4s; }
	@keyframes ldrWave { from { opacity: .9; transform: rotateX(60deg) scale(1); } to { opacity: 0; transform: rotateX(60deg) scale(2.4); } }
	@keyframes ldrBeat { 50% { transform: translateY(-6px) scale(1.06); } }

	@media (prefers-reduced-motion: reduce) {
		.sk-loader *, .sk-loader *::after { animation-duration: 6s !important; }
	}
</style>
<?php } ?>
<div class="loading-overlay sk-loader ldr-is-<?php echo $loader['style']; ?><?php echo $preview ? ' is-preview' : ''; ?>" style="--ld-a: <?php echo $loader['accent']; ?>; --ld-b: <?php echo $loader['accent2']; ?>;"<?php echo $preview ? ' aria-hidden="true"' : ' role="status" aria-live="polite"'; ?>>
	<div class="ldr-stage">
	<?php if ($loader['style'] === 'flip') { ?>
		<span class="ldr-card"><span class="ldr-face front"><?php echo $logo; ?></span><span class="ldr-face back"><?php echo $logo; ?></span></span>
	<?php } elseif ($loader['style'] === 'cube') { ?>
		<span class="ldr-cube"><?php for ($f = 0; $f < 6; $f++) echo '<span class="ldr-face">' . ($f < 4 ? $logo : '') . '</span>'; ?></span>
	<?php } elseif ($loader['style'] === 'pulse') { ?>
		<span class="ldr-wave"></span><span class="ldr-wave"></span><span class="ldr-wave"></span>
		<span class="ldr-logo"><?php echo $logo; ?></span>
	<?php } else { ?>
		<span class="ldr-ring r1"></span><span class="ldr-ring r2"></span><span class="ldr-ring r3"></span>
		<span class="ldr-logo"><?php echo $logo; ?></span>
	<?php } ?>
	</div>
	<p class="ldr-text" data-ldr-text><?php echo html_escape($text); ?></p>
	<span class="ldr-bar"><i></i></span>
</div>
