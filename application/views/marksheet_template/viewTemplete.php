<link rel="stylesheet" href="<?=base_url('assets/css/document-templates.css?v=' . version_combine())?>">
<style type="text/css">
	.mark-container {
		height: 100%;
		width: 100%;
		max-width: 1000px;
	    position: relative;
	    z-index: 2;
	    margin: 0 auto;
	    padding: <?=$marksheet_template['top_space'] . 'px ' . $marksheet_template['right_space'] . 'px ' . $marksheet_template['bottom_space'] . 'px ' . $marksheet_template['left_space'] . 'px'?>;
	}

	.mark-container  table {
		border-collapse: collapse;
		width: 100%;
	}

	@page {
		margin: -2px;
		size: A4 <?php echo $marksheet_template['page_layout'] == 1 ? 'portrait' : 'landscape'; ?>;
	}

	.mark-container .table > thead:first-child > tr:first-child > th,
	.mark-container .table-bordered > thead > tr > th,
	.mark-container .table-bordered > tbody > tr > th,
	.mark-container .table-bordered > tfoot > tr > th,
	.mark-container .table-bordered > thead > tr > td,
	.mark-container .table-bordered > tbody > tr > td,
	.mark-container .table-bordered > tfoot > tr > td {
	    border: 1px solid #000;
	    background: transparent !important;
	}

	.background {
		position: absolute;
		z-index: 0;
		width: 100%;
		height: 100%;
	<?php if (empty($marksheet_template['background'])) { ?>
		background: #fff;
	<?php } else { ?>
		background-image: url("<?=base_url('uploads/marksheet/' . $marksheet_template['background'])?>") !important;
		background-repeat: no-repeat !important;
		background-size: 100% 100% !important;
	<?php } ?>
	}
</style>
	<div style="position: relative; width: 100%; height: 100%;">
		<div class="background"></div>
		<div class="mark-container <?=document_template_class($marksheet_template)?>">
			<?=$marksheet_template['header_content']?>

			<table class="table table-condensed table-bordered mt-lg">
				<thead>
					<tr>
						<th>Subject</th>
						<th>First Term Examination</th>
						<th>Second Term Examination</th>
						<th>Third Term Examination</th>
<?php if ($marksheet_template['cumulative_average'] == 1) { ?>
					<th>Cumulative Average</th>
<?php } ?>
					<th>Grade</th>
<?php if ($marksheet_template['remark'] == 1) { ?>
					<th>Remark</th>
<?php } if ($marksheet_template['class_average'] == 1) { ?>				
					<th>Class Average</th>
<?php } if ($marksheet_template['subject_position'] == 1) { ?>
					<th>Subject Position</th>
<?php } ?>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td valign="middle" width="20%">Einglish</td>
						<td valign="middle">19 / 40</td>
						<td valign="middle">0 / 0</td>
						<td valign="middle">80 / 200</td>
<?php if ($marksheet_template['cumulative_average'] == 1) { ?>
						<td valign="middle">41.3%</td>
<?php } ?>
						<td valign="middle">C</td>
<?php if ($marksheet_template['remark'] == 1) { ?>
						<td valign="middle">Adequate</td>
<?php } if ($marksheet_template['class_average'] == 1) { ?>	
						<td valign="middle">44.00</td>
<?php } if ($marksheet_template['subject_position'] == 1) { ?>
						<td valign="middle">2nd</td>
<?php } ?>
					</tr>
					<tr>
						<td valign="middle" width="20%">Physics</td>
						<td valign="middle">34 / 100</td>
						<td valign="middle">0 / 0</td>
						<td valign="middle">80 / 200</td>
<?php if ($marksheet_template['cumulative_average'] == 1) { ?>
						<td valign="middle">38.0%</td>
<?php } ?>
						<td valign="middle">D</td>
<?php if ($marksheet_template['remark'] == 1) { ?>
						<td valign="middle">Poor</td>
<?php } if ($marksheet_template['class_average'] == 1) { ?>	
						<td valign="middle">57.71</td>
<?php } if ($marksheet_template['subject_position'] == 1) { ?>
						<td valign="middle">1st</td>
<?php } ?>
					</tr>
					<tr>
						<td valign="middle" width="20%">Mathematic</td>
						<td valign="middle">45 / 100</td>
						<td valign="middle">0 / 0</td>
						<td valign="middle">80 / 200</td>
<?php if ($marksheet_template['cumulative_average'] == 1) { ?>
						<td valign="middle">41.7%</td>
<?php } ?>
						<td valign="middle">C</td>
<?php if ($marksheet_template['remark'] == 1) { ?>
						<td valign="middle">Adequate</td>
<?php } if ($marksheet_template['class_average'] == 1) { ?>	
						<td valign="middle">52.20</td>
<?php } if ($marksheet_template['subject_position'] == 1) { ?>
						<td valign="middle">1st</td>
<?php } ?>
					</tr>
					<tr>
						<td valign="middle" width="20%">Chimistry</td>
						<td valign="middle">43 / 100</td>
						<td valign="middle">0 / 0</td>
						<td valign="middle">80 / 200</td>
<?php if ($marksheet_template['cumulative_average'] == 1) { ?>
						<td valign="middle">41.0%</td>
<?php } ?>
						<td valign="middle">C</td>
<?php if ($marksheet_template['remark'] == 1) { ?>
						<td valign="middle">Adequate</td>
<?php } if ($marksheet_template['class_average'] == 1) { ?>	
						<td valign="middle">51.40</td>
<?php } if ($marksheet_template['subject_position'] == 1) { ?>	
						<td valign="middle">1st</td>
<?php } ?>
					</tr>
					<tr>
						<td valign="middle" width="20%">Biology</td>
						<td valign="middle">40 / 100</td>
						<td valign="middle">0 / 0</td>
						<td valign="middle">0 / 0</td>
<?php if ($marksheet_template['cumulative_average'] == 1) { ?>
						<td valign="middle">40.0%</td>
<?php } ?>
						<td valign="middle">C</td>
<?php if ($marksheet_template['remark'] == 1) { ?>
						<td valign="middle">Adequate</td>
<?php } if ($marksheet_template['class_average'] == 1) { ?>	
						<td valign="middle">40.25</td>
<?php } if ($marksheet_template['subject_position'] == 1) { ?>
						<td valign="middle">2nd</td>
<?php } ?>
					</tr>
						<tr class="text-weight-semibold">
						<td valign="top">GRAND TOTAL :</td>
						<td valign="top" colspan="8">501/1240, Average : 40.40%</td>
					</tr>
						<tr class="text-weight-semibold">
						<td valign="top">GRAND TOTAL IN WORDS :</td>
						<td valign="top" colspan="8">Five Hundred One</td>
					</tr>
						<tr class="text-weight-semibold">
						<td valign="top">GPA :</td>
						<td valign="top" colspan="8">2.40%</td>
					</tr>
<?php if ($marksheet_template['result'] == 1) { ?>
					<tr class="text-weight-semibold">
						<td valign="top">RESULT :</td>
						<td valign="top" colspan="13">Pass</td>
					</tr>
<?php } if ($marksheet_template['position'] == 1) { ?>
					<tr class="text-weight-semibold">
						<td valign="top">Position :</td>
						<td valign="top" colspan="13"> 1 out of 45</td>
					</tr>
<?php } ?>
				</tbody>
			</table>

		<div style="width: 100%; display: flex;">
<?php if ($marksheet_template['attendance_percentage'] == 1) { ?>
			<div class="marksheet-summary-panel" style="width: 50%; padding-right: 15px;">
				<table class="table table-bordered table-condensed">
					<tbody>
						<tr>
							<th colspan="2" class="text-center">Attendance</th>
						</tr>
						<tr>
							<th style="width: 65%;">Times School Opened</th>
							<td>100</td>
						</tr>
						<tr>
							<th style="width: 65%;">Times Present</th>
							<td>75</td>
						</tr>
						<tr>
							<th style="width: 65%;">Times Absent</th>
							<td>25</td>
						</tr>
						<tr>
							<th style="width: 65%;">Attendance Percentage</th>
							<td>75.00%</td>
						</tr>
					</tbody>
				</table>
			</div>
<?php } ?>
<?php if ($marksheet_template['grading_scale'] == 1) { ?>
			<div class="marksheet-summary-panel" style="width: 50%; padding-left: 15px;">
				<table class="table table-condensed table-bordered">
					<tbody>
						<tr>
							<th colspan="4" class="text-center">Grading Scale</th>
						</tr>
						<tr>
							<th>Grade</th>
							<th>Min Percentage</th>
							<th>Max Percentage</th>
							<th>Remark</th>
						</tr>
						<tr>
							<td style="width: 25%;">A1</td>
							<td style="width: 25%;">75%</td>
							<td style="width: 25%;">100%</td>
							<td style="width: 25%;">Excellent</td>
						</tr>
						<tr>
							<td style="width: 25%;">B2</td>
							<td style="width: 25%;">70%</td>
							<td style="width: 25%;">74%</td>
							<td style="width: 25%;">Very Good</td>
						</tr>
						<tr>
							<td style="width: 25%;">B3</td>
							<td style="width: 25%;">65%</td>
							<td style="width: 25%;">69%</td>
							<td style="width: 25%;">Good</td>
						</tr>
						<tr>
							<td style="width: 25%;">C4</td>
							<td style="width: 25%;">60%</td>
							<td style="width: 25%;">64%</td>
							<td style="width: 25%;">Credit</td>
						</tr>
						<tr>
							<td style="width: 25%;">D7</td>
							<td style="width: 25%;">45%</td>
							<td style="width: 25%;">49%</td>
							<td style="width: 25%;">Pass</td>
						</tr>
						<tr>
							<td style="width: 25%;">F9</td>
							<td style="width: 25%;">0%</td>
							<td style="width: 25%;">39%</td>
							<td style="width: 25%;">Fail</td>
						</tr>
					</tbody>
				</table>
			</div>
<?php } ?>
		</div>
<?php if ($marksheet_template['term_position'] == 1) { ?>
		<div class="marksheet-summary-panel" style="margin-top: 15px;">
			<table class="table table-condensed table-bordered">
				<tbody>
					<tr>
						<th colspan="3" class="text-center">Position Per Term</th>
					</tr>
					<tr>
						<th class="text-center">First Term</th>
						<th class="text-center">Second Term</th>
						<th class="text-center">Third Term</th>
					</tr>
					<tr>
						<td class="text-center">3 / 45</td>
						<td class="text-center">1 / 45</td>
						<td class="text-center">2 / 45</td>
					</tr>
				</tbody>
			</table>
		</div>
<?php } ?>
		<?=$marksheet_template['footer_content']?>
		</div>
	</div>
