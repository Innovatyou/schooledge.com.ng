<section class="panel">
	<div class="tabs-custom">
		<ul class="nav nav-tabs">
			<li class="active">
                <a href="#list" data-toggle="tab">
                    <i class="fas fa-list-ul"></i> <?=translate('template') ." ". translate('list')?>
                </a>
			</li>
<?php if (get_permission('marksheet_template', 'is_add')): ?>
			<li>
                <a href="#add" data-toggle="tab">
                   <i class="far fa-edit"></i> <?=translate('add') . " ". translate('template')?>
                </a>
			</li>
<?php endif; ?>
		</ul>
		<div class="tab-content">
			<div class="tab-pane box active mb-md" id="list">
				<table class="table table-bordered table-hover mb-none table-condensed table-export">
					<thead>
						<tr>
							<th><?=translate('sl')?></th>
<?php if (is_superadmin_loggedin()): ?>
							<th><?=translate('branch')?></th>
<?php endif; ?>
							<th><?=translate('template') . " " . translate('name')?></th>
							<th><?=translate('page_layout')?></th>
							<th class="no-sort"><?=translate('background') . " " . translate('image')?></th>
							<th><?=translate('created_at')?></th>
							<th><?=translate('action')?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$count = 1;
						foreach ($certificatelist as $row):
						?>
						<tr>
							<td><?php echo $count++; ?></td>
<?php if (is_superadmin_loggedin()): ?>
							<td><?php echo $row['branchname'];?></td>
<?php endif; ?>
							<td><?php echo $row['name']; if (!empty($row['available_all_branches'])) { ?> <span class="label label-success">All Schools</span><?php } ?></td>
							<td><?php
							if ($row['page_layout'] == 1) {
								echo "Portrait";
							} else {
								echo "Landscape";
							} 
							?></td>
							<td>
								<?php
							        $imgPath = 'uploads/marksheet/' . $row['background'];
							        if (file_exists($imgPath) && !empty($row['background'])) {
							            $imgPath = base_url($imgPath);
							        } else {
							            $imgPath = base_url('uploads/language_flags/defualt.png');
							        }

								 ?>
								<img class="" src="<?=$imgPath?>" height="50">
							</td>
							<td><?php echo _d($row['created_at']);?></td>
							<td class="action">
								<!-- view link -->
								<a href="javascript:void(0);" class="btn btn-circle btn-default icon" data-toggle="tooltip" data-original-title="<?=translate('view')?>" 
								onclick="getMarksheet_template('<?=$row['id'] ?>');">
									<i class="fas fa-bars"></i>
								</a>
							<?php if (get_permission('marksheet_template', 'is_edit')) { ?>
								<a href="<?=base_url('marksheet_template/edit/' . $row['id']);?>" class="btn btn-circle btn-default icon">
									<i class="fas fa-pen-nib"></i>
								</a>
							<?php } if (get_permission('marksheet_template', 'is_delete')) { ?>
								<!-- deletion link -->
								<?php echo btn_delete('marksheet_template/delete/'.$row['id']);?>
							<?php } ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
<?php if (get_permission('marksheet_template', 'is_add')): ?>
			<div class="tab-pane" id="add">
					<?php echo form_open($this->uri->uri_string(), array('class' => 'form-bordered form-horizontal frm-submit-data'));?>
					<?php
						$marksheetStarters = array_filter(starter_templates(), function ($t) { return $t['applies_to'] == 'marksheet'; });
						echo $this->load->view('partials/starter_template_picker', array('starters' => $marksheetStarters, 'target' => 'marksheet'), true);
					?>
					<?php if (is_superadmin_loggedin()): ?>
						<div class="form-group">
							<label class="control-label col-md-3"><?=translate('branch')?> <span class="required">*</span></label>
							<div class="col-md-8">
								<?php
									$arrayBranch = $this->app_lib->getSelectList('branch');
									echo form_dropdown("branch_id", $arrayBranch, set_value('branch_id'), "class='form-control' data-width='100%' onchange='getClassByBranch(this.value)'
									data-plugin-selectTwo  data-minimum-results-for-search='Infinity'");
								?>
								<span class="error"></span>
							</div>
						</div>
					<?php endif; ?>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('template') . " " . translate('name')?> <span class="required">*</span></label>
						<div class="col-md-8">
							<input type="text" class="form-control" name="marksheet_template_name" value="" />
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-3">Page Layout <span class="required">*</span></label>
						<div class="col-md-8">
							<select name="page_layout" class="form-control layout-preset" data-width='100%'
							data-plugin-selectTwo  data-minimum-results-for-search='Infinity'>
								<option value="" data-desc="Choose the report card orientation" <?=set_value('page_layout') == '' ? 'selected' : ''?>><?=translate('select')?></option>
								<option value="1" data-desc="Standard upright report card layout" <?=set_value('page_layout') == '1' ? 'selected' : ''?>>Portrait</option>
								<option value="2" data-desc="Wide layout &mdash; useful when there are many exam/subject columns" <?=set_value('page_layout') == '2' ? 'selected' : ''?>>Landscape</option>
							</select>
							<small class="preset-desc text-muted">Choose the report card orientation</small>
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-3"><?=translate('design_style')?> <span class="required">*</span></label>
						<div class="col-md-8">
							<?php
								echo form_dropdown("design_style", document_template_styles(), set_value('design_style', 'classic'), "class='form-control' data-width='100%'
								data-plugin-selectTwo  data-minimum-results-for-search='Infinity'");
							?>
							<span class="error"></span>
						</div>
					</div>
<?php if (is_superadmin_loggedin()): ?>
					<div class="form-group">
						<label class="control-label col-md-3">Availability</label>
						<div class="col-md-8">
							<div class="checkbox-replace">
								<label class="i-checks"><input type="checkbox" name="available_all_branches" value="1"><i></i> Available for all schools</label>
							</div>
							<span class="error"></span>
						</div>
					</div>
<?php endif; ?>
					<div class="form-group">
						<label class="col-md-3 control-label">User Photo Style <span class="required">*</span></label>
						<div class="col-md-8">
							<div class="row">
								<div class="col-xs-6">
									<?php
										$arrayType = array(
											'1' => "Square",
											'2' => "Round"
										);
										echo form_dropdown("photo_style", $arrayType, set_value('photo_style'), "class='form-control' data-width='100%'
										data-plugin-selectTwo  data-minimum-results-for-search='Infinity'");
									?>
								</div>
								<div class="col-xs-6">
									<input type="text" class="form-control" name="photo_size" value="<?php echo set_value('photo_size', 120) ?>" placeholder="Photo Size (px)" />
								</div>
							</div>
							<span class="error"></span>
						</div>
					</div>

					<div class="form-group">
						<label class="col-md-3 control-label">Layout Spacing <span class="required">*</span></label>
						<div class="col-md-8">
							<select class="form-control layout-preset" data-target="input[name=top_space],input[name=bottom_space],input[name=right_space],input[name=left_space]">
								<option value="" data-desc="Choose a preset, or enter custom spacing below">Select a preset (optional)</option>
								<option value="0,0,0,0" data-desc="No padding &mdash; content touches the edges">None (0px)</option>
								<option value="8,8,8,8" data-desc="Minimal breathing room">Tight (8px)</option>
								<option value="16,16,16,16" data-desc="Balanced spacing &mdash; a good default">Normal (16px)</option>
								<option value="30,30,30,30" data-desc="Generous margins for a spacious look">Wide (30px)</option>
							</select>
							<small class="preset-desc text-muted">Choose a preset, or enter custom spacing below</small>
							<div class="row mt-sm">
								<div class="col-xs-6">
									<input type="text" class="form-control" name="top_space" value="" placeholder="Top Space (px)" />
								</div>
								<div class="col-xs-6">
									<input type="text" class="form-control" name="bottom_space" value="" placeholder="Bottom Space (px)" />
								</div>
							</div>
						</div>
						<div class="mt-md col-md-offset-3 col-md-8">
							<div class="row">
								<div class="col-xs-6">
									<input type="text" class="form-control" name="right_space" value="" placeholder="Right Space (px)" />
								</div>
								<div class="col-xs-6">
									<input type="text" class="form-control" name="left_space" value="" placeholder="Left Space (px)" />
								</div>
							</div>
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('left') . " " . translate('signature')?></label>
						<div class="col-md-8">
							<div class="fileupload fileupload-new" data-provides="fileupload">
								<div class="input-append">
									<div class="uneditable-input">
										<i class="fas fa-file fileupload-exists"></i>
										<span class="fileupload-preview"></span>
									</div>
									<span class="btn btn-default btn-file">
										<span class="fileupload-exists">Change</span>
										<span class="fileupload-new">Select file</span>
										<input type="file" name="left_signature_file" />
									</span>
									<a href="#" class="btn btn-default fileupload-exists" data-dismiss="fileupload">Remove</a>
								</div>
							</div>
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('middle') . " " . translate('signature')?></label>
						<div class="col-md-8">
							<div class="fileupload fileupload-new" data-provides="fileupload">
								<div class="input-append">
									<div class="uneditable-input">
										<i class="fas fa-file fileupload-exists"></i>
										<span class="fileupload-preview"></span>
									</div>
									<span class="btn btn-default btn-file">
										<span class="fileupload-exists">Change</span>
										<span class="fileupload-new">Select file</span>
										<input type="file" name="middle_signature_file" />
									</span>
									<a href="#" class="btn btn-default fileupload-exists" data-dismiss="fileupload">Remove</a>
								</div>
							</div>
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('right') . " " . translate('signature')?></label>
						<div class="col-md-8">
							<div class="fileupload fileupload-new" data-provides="fileupload">
								<div class="input-append">
									<div class="uneditable-input">
										<i class="fas fa-file fileupload-exists"></i>
										<span class="fileupload-preview"></span>
									</div>
									<span class="btn btn-default btn-file">
										<span class="fileupload-exists">Change</span>
										<span class="fileupload-new">Select file</span>
										<input type="file" name="right_signature_file" />
									</span>
									<a href="#" class="btn btn-default fileupload-exists" data-dismiss="fileupload">Remove</a>
								</div>
							</div>
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('logo') . " " . translate('image')?></label>
						<div class="col-md-8">
							<div class="fileupload fileupload-new" data-provides="fileupload">
								<div class="input-append">
									<div class="uneditable-input">
										<i class="fas fa-file fileupload-exists"></i>
										<span class="fileupload-preview"></span>
									</div>
									<span class="btn btn-default btn-file">
										<span class="fileupload-exists">Change</span>
										<span class="fileupload-new">Select file</span>
										<input type="file" name="logo_file" />
									</span>
									<a href="#" class="btn btn-default fileupload-exists" data-dismiss="fileupload">Remove</a>
								</div>
							</div>
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('background') . " " . translate('image')?></label>
						<div class="col-md-8">
							<div class="fileupload fileupload-new" data-provides="fileupload">
								<div class="input-append">
									<div class="uneditable-input">
										<i class="fas fa-file fileupload-exists"></i>
										<span class="fileupload-preview"></span>
									</div>
									<span class="btn btn-default btn-file">
										<span class="fileupload-exists">Change</span>
										<span class="fileupload-new">Select file</span>
										<input type="file" name="background_file" />
									</span>
									<a href="#" class="btn btn-default fileupload-exists" data-dismiss="fileupload">Remove</a>
								</div>
							</div>
							<span class="error"></span>
						</div>
					</div>

					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('header') . " " . translate('content') ?> <span class="required">*</span></label>
						<div class="col-md-8">
							<textarea name="header_content" class="form-control texteEditor" id="texteEditor1" rows="10"></textarea>
							<span class="error"></span>
							<div class="studenttags">
							<?php 
							$tagsList = $this->marksheet_template_model->tagsList(); 
							foreach ($tagsList as $key => $value) {
								?>
								<a data-value=" <?=$value?> " class="btn btn-default mt-sm btn-xs btn_tag1"><?=$value?></a>
							<?php } ?>
							</div>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('footer') . " " . translate('content') ?> <span class="required">*</span></label>
						<div class="col-md-8">
							<textarea name="footer_content" class="form-control texteEditor" id="texteEditor2" rows="10"></textarea>
							<span class="error"></span>
							<div class="studenttags">
							<?php 
							foreach ($tagsList as $key => $value) {
								?>
								<a data-value=" <?=$value?> " class="btn btn-default mt-sm btn-xs btn_tag2"><?=$value?></a>
							<?php } ?>
							</div>
						</div>
					</div>

					<div class="form-group">
						<div class="col-md-offset-3 col-md-8">
							<div class="checkbox-replace">
								<label class="i-checks">
									<input type="checkbox" name="attendance_percentage" value="true" checked=""><i></i> <?php echo translate('attendance') . " " . translate('percentage'); ?>
								</label>
							</div>
							<div class="checkbox-replace mt-sm">
								<label class="i-checks">
									<input type="checkbox" name="grading_scale" value="true" checked=""><i></i> <?php echo translate('grading_scale'); ?>
								</label>
							</div>
							<div class="checkbox-replace mt-sm">
								<label class="i-checks">
									<input type="checkbox" name="position" value="true" checked=""><i></i> <?php echo translate('position'); ?>
								</label>
							</div>
							<div class="checkbox-replace mt-sm">
								<label class="i-checks">
									<input type="checkbox" name="term_position" value="true" checked=""><i></i> <?php echo translate('term_position'); ?>
								</label>
							</div>
							<div class="checkbox-replace mt-sm">
								<label class="i-checks">
									<input type="checkbox" name="cumulative_average" value="true" checked=""><i></i> <?php echo translate('cumulative') . " " . translate('average'); ?>
								</label>
							</div>
							<div class="checkbox-replace mt-sm">
								<label class="i-checks">
									<input type="checkbox" name="class_average" value="true" checked=""><i></i> <?php echo translate('class') . " " . translate('average'); ?>
								</label>
							</div>
							<div class="checkbox-replace mt-sm">
								<label class="i-checks">
									<input type="checkbox" name="subject_position" value="true" checked=""><i></i> <?php echo translate('subject') . " " . translate('position'); ?> 
								</label>
							</div>
							<div class="checkbox-replace mt-sm">
								<label class="i-checks">
									<input type="checkbox" name="remark" value="true" checked=""><i></i> <?php echo translate('remark'); ?>
								</label>
							</div>
							<div class="checkbox-replace mt-sm mb-lg">
								<label class="i-checks">
									<input type="checkbox" name="result" value="true" checked=""><i></i> <?php echo translate('result'); ?>
								</label>
							</div>
						</div>
					</div>

					<footer class="panel-footer">
						<div class="row">
							<div class="col-md-offset-3 col-md-2">
								<button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
									<i class="fas fa-plus-circle"></i> <?=translate('save')?>
								</button>
							</div>
						</div>
					</footer>
				<?php echo form_close(); ?>
			</div>
<?php endif; ?>
		</div>
	</div>
</section>

<div class="zoom-anim-dialog modal-block modal-block-lg mfp-hide payroll-t-modal" id="modal">
	<section class="panel">
		<header class="panel-heading">
			<h4 class="panel-title"><i class="fas fa-bars"></i> <?php echo translate('template') . " " . translate('view'); ?></h4>
		</header>
		<div class="panel-body">
			<div id="quick_view"></div>
		</div>
		<footer class="panel-footer">
			<div class="row">
				<div class="col-md-12 text-right">
					<button class="btn btn-default modal-dismiss"><?php echo translate('close'); ?></button>
				</div>
			</div>
		</footer>
	</section>
</div>

<script type="text/javascript">
	$(document).ready(function () {
		if ($(".texteEditor").length) {
			$('.texteEditor').summernote({
				fontNames: ['Arial', 'Arial Black', 'Consolas','Tahoma', 'Times New Roman', 'Great Vibes', 'Pinyon Script', 'Parisienne'],
				fontNamesIgnoreCheck: ['Great Vibes', 'Pinyon Script', 'Parisienne'],
				fontSizes: ['8', '9', '10', '11', '12', '14', '18', '24', '28', '36', '48' , '64', '82'],
				height: 220,
				toolbar: [
					["style", ["style"]],
					["name", ["fontname","fontsize","height"]],
					["font", ["bold","italic","underline", "clear"]],
					["color", ["color"]],
					["para", ["ul", "ol", "paragraph"]],
					["insert", ["link","table"]],
					["misc", ["fullscreen", "undo", "codeview"]]
				]
			});
		}

		$('.btn_tag1').on('click', function() {
			var txtToAdd = $(this).data("value");
			$('#texteEditor1').summernote('editor.insertText', txtToAdd);
		});
		$('.btn_tag2').on('click', function() {
			var txtToAdd = $(this).data("value");
			$('#texteEditor2').summernote('editor.insertText', txtToAdd);
		});
	});

	function getMarksheet_template(id) {
		$.ajax({
			url: base_url + 'marksheet_template/getCertificate',
			type: 'POST',
			data: {'id': id},
			dataType: "html",
			success: function (data) {
				$('#quick_view').html(data);
				mfp_modal('#modal');
			}
		});
	}
</script>