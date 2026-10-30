<?php
// One KPI tile. Params: $tone, $icon, $value (number), $label, $foot, $tag (optional),
// $i (stagger index), $bar (0-100, optional), $prefix/$suffix/$decimals (optional), $href (optional)
$tag = isset($href) && $href ? 'a' : 'div';
?>
<<?php echo $tag; ?> class="d3-kpi tone-<?php echo $tone; ?> d3-reveal" style="--i: <?php echo (int) $i; ?>" data-tilt="8"<?php echo $tag === 'a' ? ' href="' . $href . '"' : ''; ?>>
	<span class="d3-kpi-top">
		<span class="d3-kpi-icon"><i class="<?php echo strpos($icon, ' ') ? $icon : 'fas ' . $icon; ?>"></i></span>
		<?php if (!empty($tag_text)) { ?><span class="d3-kpi-tag"><?php echo $tag_text; ?></span><?php } ?>
	</span>
	<b class="d3-kpi-value" data-count="<?php echo (float) $value; ?>"<?php
		echo !empty($prefix) ? ' data-prefix="' . html_escape($prefix) . '"' : '';
		echo !empty($suffix) ? ' data-suffix="' . html_escape($suffix) . '"' : '';
		echo !empty($decimals) ? ' data-decimals="' . (int) $decimals . '"' : ''; ?>>0</b>
	<span class="d3-kpi-label"><?php echo $label; ?></span>
	<span class="d3-kpi-foot"><?php echo $foot; ?></span>
	<?php if (isset($bar) && $bar !== null) { ?>
	<span class="d3-kpi-bar"><i data-p="<?php echo max(0, min(100, (int) $bar)); ?>"></i></span>
	<?php } ?>
</<?php echo $tag; ?>>
