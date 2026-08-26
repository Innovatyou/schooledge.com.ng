<section class="panel">
	<div class="tabs-custom">
		<ul class="nav nav-tabs">
			<li>
                <a href="<?=base_url('certificate')?>">
                    <i class="fas fa-list-ul"></i> <?=translate('certificate') ." ". translate('list')?>
                </a>
			</li>
			<li class="active">
                <a href="#edit" data-toggle="tab">
                   <i class="far fa-edit"></i> <?=translate('edit') . " " . translate('certificate')?>
                </a>
			</li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane active" id="edit">
					<?php echo form_open($this->uri->uri_string(), array('class' => 'form-bordered form-horizontal frm-submit-data'));?>
					<input type="hidden" name="certificate_id" value="<?=$certificate['id']?>">
					<?php if (is_superadmin_loggedin()): ?>
						<div class="form-group">
							<label class="control-label col-md-3"><?=translate('branch')?> <span class="required">*</span></label>
							<div class="col-md-8">
								<?php
									$arrayBranch = $this->app_lib->getSelectList('branch');
									echo form_dropdown("branch_id", $arrayBranch, $certificate['branch_id'], "class='form-control' data-width='100%' onchange='getClassByBranch(this.value)'
									data-plugin-selectTwo  data-minimum-results-for-search='Infinity'");
								?>
								<span class="error"></span>
							</div>
						</div>
					<?php endif; ?>
					<div class="form-group">
						<label class="col-md-3 control-label"><?=translate('Certificate') . " " . translate('name')?> <span class="required">*</span></label>
						<div class="col-md-8">
							<input type="text" class="form-control" name="certificate_name" value="<?=$certificate['name']?>" />
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-3"><?=translate('applicable_user')?> <span class="required">*</span></label>
						<div class="col-md-8">
							<?php
								$arrayType = array(
									'' => translate('select'),
									'1' => translate('student'),
									'2' => translate('employee')
								);
								echo form_dropdown("user_type", $arrayType, $certificate['user_type'], "class='form-control' data-width='100%' id='userType'
								data-plugin-selectTwo  data-minimum-results-for-search='Infinity'");
							?>
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-3">Page Layout <span class="required">*</span></label>
						<div class="col-md-8">
							<select name="page_layout" class="form-control layout-preset" data-width='100%'
							data-plugin-selectTwo  data-minimum-results-for-search='Infinity'>
								<option value="" data-desc="Choose the certificate orientation" <?=$certificate['page_layout'] == '' ? 'selected' : ''?>><?=translate('select')?></option>
								<option value="1" data-desc="210mm x 297mm &mdash; standard upright certificate, ideal for formal awards and diplomas" <?=$certificate['page_layout'] == 1 ? 'selected' : ''?>>A4 (Portrait)</option>
								<option value="2" data-desc="297mm x 210mm &mdash; wide layout, good for certificates with side-by-side signatures or seals" <?=$certificate['page_layout'] == 2 ? 'selected' : ''?>>A4 (Landscape)</option>
							</select>
							<small class="preset-desc text-muted"><?=$certificate['page_layout'] == 2 ? 'Wide layout, good for certificates with side-by-side signatures or seals' : 'Standard upright certificate, ideal for formal awards and diplomas'?></small>
							<span class="error"></span>
						</div>
					</div>

					<div class="form-group">
						<label class="control-label col-md-3"><?=translate('design_style')?> <span class="required">*</span></label>
						<div class="col-md-8">
							<?php
								echo form_dropdown("design_style", document_template_styles(), document_template_style($certificate), "class='form-control' data-width='100%'
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
								<label class="i-checks"><input type="checkbox" name="available_all_branches" value="1" <?=(isset($certificate['available_all_branches']) && $certificate['available_all_branches'] == 1) ? 'checked' : ''?>><i></i> Available for all schools</label>
							</div>
							<span class="error"></span>
						</div>
					</div>
<?php endif; ?>
					<div class="form-group studenttags" style="<?=$certificate['user_type'] == 1 ? '' : 'display: none;' ?>">
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
								echo form_dropdown("stu_qr_code", $arrayType, $certificate['qr_code'], "class='form-control' data-width='100%'
								data-plugin-selectTwo  data-minimum-results-for-search='Infinity'");
							?>
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group stafftag" style="<?=$certificate['user_type'] == 2 ? '' : 'display: none;' ?>">
						<label class="control-label col-md-3">QR Code Text <span class="required">*</span></label>
						<div class="col-md-8">
							<?php
								$arrayType = array(
									'' => translate('select'),
									'staff_id' => translate('staff_id'),
									'birthday' => translate('birthday'),
									'joining_date' => translate('joining_date'),
								);
								echo form_dropdown("emp_qr_code", $arrayType, $certificate['qr_code'], "class='form-control' data-width='100%'
								data-plugin-selectTwo  data-minimum-results-for-search='Infinity'");
							?>
							<span class="error"></span>
						</div>
					</div>
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
										echo form_dropdown("photo_style", $arrayType, $certificate['photo_style'], "class='form-control' data-width='100%'
										data-plugin-selectTwo  data-minimum-results-for-search='Infinity'");
									?>
								</div>
								<div class="col-xs-6">
									<input type="text" class="form-control" name="photo_size" value="<?=$certificate['photo_size']?>" placeholder="Photo Size (px)" />
								</div>
							</div>
							<span class="error"></span>
						</div>
					</div>

					<div class="form-group">
						<label class="col-md-3 control-label">Layout Spacing <span class="required">*</span></label>
						<div class="col-md-8">
							<select class="form-control layout-preset" data-target="input[name=top_space],input[name=bottom_space],input[name=right_space],input[name=left_space]">
								<option value="" data-desc="Choose a preset, or edit the custom spacing below">Select a preset (optional)</option>
								<option value="0,0,0,0" data-desc="No padding &mdash; content touches the edges">None (0px)</option>
								<option value="8,8,8,8" data-desc="Minimal breathing room">Tight (8px)</option>
								<option value="16,16,16,16" data-desc="Balanced spacing &mdash; a good default">Normal (16px)</option>
								<option value="30,30,30,30" data-desc="Generous margins for a spacious look">Wide (30px)</option>
							</select>
							<small class="preset-desc text-muted">Choose a preset, or edit the custom spacing below</small>
							<div class="row mt-sm">
								<div class="col-xs-6">
									<input type="text" class="form-control" name="top_space" value="<?=$certificate['top_space']?>" placeholder="Top Space (px)" />
								</div>
								<div class="col-xs-6">
									<input type="text" class="form-control" name="bottom_space" value="<?=$certificate['bottom_space']?>" placeholder="Bottom Space (px)" />
								</div>
							</div>
						</div>
						<div class="mt-md col-md-offset-3 col-md-8">
							<div class="row">
								<div class="col-xs-6">
									<input type="text" class="form-control" name="right_space" value="<?=$certificate['right_space']?>" placeholder="Right Space (px)" />
								</div>
								<div class="col-xs-6">
									<input type="text" class="form-control" name="left_space" value="<?=$certificate['left_space']?>" placeholder="Left Space (px)" />
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
							<input type="hidden" name="old_signature_file" value="<?=$certificate['signature']?>">
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
							<input type="hidden" name="old_logo_file" value="<?=$certificate['logo']?>">
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
							<input type="hidden" name="old_background_file" value="<?=$certificate['background']?>">
							<span class="error"></span>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label">Certificate Content <span class="required">*</span></label>
						<div class="col-md-8">
							<textarea name="content" class="form-control" id="certificateConten" rows="10"><?=$certificate['content']?></textarea>
							<span class="error"></span>
							<div class="studenttags" style="<?=$certificate['user_type'] == 1 ? '' : 'display: none;' ?>">
							<?php 
							$tagsList = $this->certificate_model->tagsList(1); 
							foreach ($tagsList as $key => $value) {
								?>
								<a data-value=" <?=$value?> " class="btn btn-default mt-sm btn-xs btn_tag"><?=$value?></a>
							<?php } ?>
							</div>
							<div class="stafftag" style="<?=$certificate['user_type'] == 2 ? '' : 'display: none;' ?>">
							<?php 
							$tagsList = $this->certificate_model->tagsList(2); 
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
									<i class="fas fa-plus-circle"></i> <?=translate('update')?>
								</button>
							</div>
						</div>
					</footer>
				<?php echo form_close(); ?>
			</div>
		</div>
	</div>
</section>