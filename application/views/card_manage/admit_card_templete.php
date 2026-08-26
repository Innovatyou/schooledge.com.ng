
<section class="panel">
	<div class="tabs-custom">
		<ul class="nav nav-tabs">
			<li class="active">
                <a href="#list" data-toggle="tab">
                    <i class="fas fa-list-ul"></i> <?=translate('admit_card') ." ". translate('list')?>
                </a>
			</li>
<?php if (get_permission('admit_card_templete', 'is_add')): ?>
			<li>
                <a href="#add" data-toggle="tab">
                   <i class="far fa-edit"></i> <?=translate('add') . " ". translate('admit_card')?>
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
							<th><?=translate('admit_card') . " " . translate('name')?></th>
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
							<td><?php echo translate('width') . ' <strong>' . $row['layout_width'] . 'mm</strong> x ' . translate('height') . ' <strong>' . $row['layout_height'] . 'mm</strong>'; ?></td>
							<td>
								<?php
							        $imgPath = 'uploads/certificate/' . $row['background'];
							        if (file_exists($imgPath) && !empty($row['background'])) {
							            $imgPath = base_url($imgPath);
							        } else {
							            $imgPath = base_url('uploads/language_flags/defualt.png');
							        }

								 ?>
								<img class="" src="<?=$imgPath?>" height="50">
							</td>
							<td><?php echo _d($row['created_at']);?></td>
							<td class="min-w-c">
								<!-- view link -->
								<a href="javascript:void(0);" class="btn btn-circle btn-default icon" data-toggle="tooltip" data-original-title="<?=translate('view')?>" 
								onclick="getIDCard('<?=$row['id'] ?>');">
									<i class="fas fa-bars"></i>
								</a>
							<?php if (get_permission('admit_card_templete', 'is_edit')) { ?>
								<a href="<?=base_url('card_manage/admit_card_templete_edit/' . $row['id']);?>" class="btn btn-circle btn-default icon">
									<i class="fas fa-pen-nib"></i>
								</a>
							<?php } if (get_permission('admit_card_templete', 'is_delete')) { ?>
								<!-- deletion link -->
								<?php echo btn_delete('card_manage/admit_card_templete_delete/'.$row['id']);?>
							<?php } ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
<?php if (get_permission('admit_card_templete', 'is_add')): ?>
			<div class="tab-pane" id="add">
					<?php echo form_open($this->uri->uri_string(), array('class' => 'form-bordered form-horizontal frm-submit-data'));?>
					<?php
						$admitCardStarters = array_filter(starter_templates(), function ($t) { return $t['applies_to'] == 'admit_card'; });
						echo $this->load->view('partials/starter_template_picker', array('starters' => $admitCardStarters, 'target' => 'card'), true);
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
						<label class="col-md-3 control-label"><?=translate('admit_card') . " " . translate('name')?> <span class="required">*</span></label>
						<div class="col-md-8">
							<input type="text" class="form-control" name="card_name" value="" />
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-3">Page Layout <span class="required">*</span></label>
						<div class="col-md-8">
							<div class="row mb-sm">
								<div class="col-xs-12">
									<select class="form-control layout-preset" data-target="input[name=layout_width],input[name=layout_height]">
										<option value="" data-desc="Choose a common size, or enter a custom width/height below">Select a preset (optional)</option>
										<option value="105,148" data-desc="Small slip size, compact hall ticket">A6 (105 x 148mm)</option>
										<option value="148,210" data-desc="Half A4 sheet &mdash; common size for admit / hall tickets">A5 (148 x 210mm)</option>
										<option value="210,297" data-desc="Full page admit card, room for a subject timetable">A4 Full Page (210 x 297mm)</option>
									</select>
									<small class="preset-desc text-muted">Choose a common size, or enter a custom width/height below</small>
								</div>
							</div>
							<div class="row">
								<div class="col-xs-6">
									<input type="text" class="form-control" name="layout_width" value="" placeholder="Layout Width (mm)" />
									<span class="error"></span>
								</div>
								<div class="col-xs-6">
									<input type="text" class="form-control" name="layout_height" value="" placeholder="Layout Height (mm)" />
								</div>
							</div>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-3">QR Code Text <span class="required">*</span></label>
						<div class="col-md-8">
							<?php
								$arrayType = array(
									'' => translate('select'),
									'name' => translate('name'),
									'birthday' => translate('birthday'),
									'register_no' => translate('register_no'),
									'roll' => translate('roll'),

								);
								echo form_dropdown("stu_qr_code", $arrayType, set_value('qr_code'), "class='form-control' data-width='100%'
								data-plugin-selectTwo  data-minimum-results-for-search='Infinity'");
							?>
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
									<input type="text" class="form-control" name="photo_size" value="" placeholder="Photo Size (px)" />
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
						<label class="col-md-3 control-label"><?=translate('signature') . " " . translate('image')?></label>
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
										<input type="file" name="signature_file" />
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
						<label class="col-md-3 control-label">Certificate Content <span class="required">*</span></label>
						<div class="col-md-8">
							<textarea name="content" class="form-control" id="certificateConten" rows="10"></textarea>
							<span class="error"></span>
							<div class="studenttags">
							<?php 
							$tagsList = $this->card_manage_model->tagsList(1, true); 
							foreach ($tagsList as $key => $value) {
								?>
								<a data-value=" <?=$value?> " class="btn btn-default mt-sm btn-xs btn_tag"><?=$value?></a>
							<?php } ?>
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

<div class="zoom-anim-dialog modal-block modal-block-lg mfp-hide" id="modal">
	<section class="panel">
		<header class="panel-heading">
			<h4 class="panel-title"><i class="fas fa-id-card-alt"></i> <?php echo translate('admit_card') . " " . translate('view'); ?></h4>
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