<?php
// $starters: array of starter definitions (from starter_templates(), already filtered to one applies_to)
// $target: 'card' | 'certificate' | 'marksheet' - tells the JS which fields/editors to fill
if (empty($starters)) {
    return;
}
?>
<link rel="stylesheet" href="<?=base_url('assets/css/document-templates.css?v=' . version_combine())?>">
<style type="text/css">
	.starter-picker { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; }
	.starter-picker .starter-card { width: 240px; border: 1px solid #e4e7ed; border-radius: 6px; background: #fff; padding: 12px; }
	.starter-picker .starter-stage { height: 200px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #f7f7f9; border-radius: 4px; margin-bottom: 10px; }
	.starter-picker .starter-stage .document-template { background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.12); }
	.starter-picker .starter-title { font-weight: 600; font-size: 13px; margin-bottom: 2px; }
	.starter-picker .starter-desc { font-size: 11px; color: #838d99; margin-bottom: 10px; min-height: 32px; }
</style>
<div class="form-group">
	<label class="control-label col-md-3"><?=translate('starter_templates')?></label>
	<div class="col-md-8">
		<div class="starter-picker">
			<?php foreach ($starters as $key => $starter): ?>
			<?php
				$isMarksheet = isset($starter['header_content']);
				$previewContent = $isMarksheet
					? render_starter_preview($starter['header_content'] . $starter['footer_content'])
					: render_starter_preview($starter['content']);
				$widthMm = isset($starter['layout_width']) ? $starter['layout_width'] : ($isMarksheet ? 200 : 210);
				$heightMm = isset($starter['layout_height']) ? $starter['layout_height'] : ($isMarksheet ? 280 : 148);
				$scale = min(1, 190 / $widthMm, 180 / $heightMm);
				$designClass = document_template_class($starter['design_style']);
			?>
			<div class="starter-card" data-user-type="<?=isset($starter['user_type']) ? $starter['user_type'] : ''?>">
				<div class="starter-stage">
					<div class="<?=$designClass?>" style="width: <?=$widthMm?>mm; height: <?=$heightMm?>mm; transform: scale(<?=$scale?>); position: relative; overflow: hidden; flex-shrink: 0;">
						<?=$previewContent?>
					</div>
				</div>
				<div class="starter-title"><?=$starter['label']?></div>
				<div class="starter-desc"><?=$starter['description']?></div>
				<button type="button" class="btn btn-default btn-block btn-xs starter-use-btn"
					data-target="<?=$target?>"
					data-settings='<?=json_encode(array(
						'layout_width' => isset($starter['layout_width']) ? $starter['layout_width'] : null,
						'layout_height' => isset($starter['layout_height']) ? $starter['layout_height'] : null,
						'page_layout' => isset($starter['page_layout']) ? $starter['page_layout'] : null,
						'photo_style' => isset($starter['photo_style']) ? $starter['photo_style'] : null,
						'photo_size' => isset($starter['photo_size']) ? $starter['photo_size'] : null,
						'spacing' => isset($starter['spacing']) ? $starter['spacing'] : null,
						'design_style' => isset($starter['design_style']) ? $starter['design_style'] : null,
					))?>'>
					<i class="fas fa-magic"></i> <?=translate('use_this_template')?>
				</button>
				<?php if ($isMarksheet): ?>
					<textarea class="starter-raw-header" style="display:none;"><?=htmlspecialchars($starter['header_content'], ENT_QUOTES)?></textarea>
					<textarea class="starter-raw-footer" style="display:none;"><?=htmlspecialchars($starter['footer_content'], ENT_QUOTES)?></textarea>
				<?php else: ?>
					<textarea class="starter-raw-content" style="display:none;"><?=htmlspecialchars($starter['content'], ENT_QUOTES)?></textarea>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
