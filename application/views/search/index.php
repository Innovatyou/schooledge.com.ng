<?php $hasAnyResult = !empty($schools) || !empty($students) || !empty($staff) || !empty($pages); ?>
<?php if (empty($query)): ?>
	<section class="panel">
		<div class="panel-body">
			<p class="text-muted mb-none"><?=translate('type_something_to_search')?></p>
		</div>
	</section>
<?php elseif (!$hasAnyResult): ?>
	<section class="panel">
		<div class="panel-body">
			<p class="text-muted mb-none"><?=translate('no_results_found_for')?> "<?=html_escape($query)?>"</p>
		</div>
	</section>
<?php endif; ?>

<?php if (!empty($schools)): ?>
	<section class="panel appear-animation" data-appear-animation="<?=$global_config['animations']?>" data-appear-animation-delay="100">
		<header class="panel-heading">
			<h4 class="panel-title"><i class="fa-solid fa-building-columns"></i> <?=translate('school')?></h4>
		</header>
		<div class="panel-body mb-md">
			<table class="table table-bordered table-condensed table-hover table-export">
				<thead>
					<tr>
						<th><?=translate('sl')?></th>
						<th><?=translate('branch_name')?></th>
						<th><?=translate('school_name')?></th>
						<th><?=translate('email')?></th>
						<th><?=translate('mobile_no')?></th>
						<th><?=translate('admin')?> <?=translate('username')?></th>
						<th class="no-sort"><?=translate('action')?></th>
					</tr>
				</thead>
				<tbody>
					<?php $count = 1; foreach ($schools as $row): ?>
					<tr>
						<td><?php echo $count++; ?></td>
						<td><?php echo html_escape($row->name); ?></td>
						<td><?php echo html_escape($row->school_name); ?></td>
						<td><?php echo html_escape($row->email); ?></td>
						<td><?php echo html_escape($row->mobileno); ?></td>
						<td><?php echo !empty($row->admin_username) ? html_escape($row->admin_username) : '—'; ?></td>
						<td class="min-w-c">
							<?php if ($this->app_lib->isExistingAddon('saas')): ?>
							<a href="<?=base_url('saas/school_details/' . $row->id)?>" class="btn btn-default btn-circle icon" data-toggle="tooltip" data-original-title="<?=translate('details')?>"><i class="fas fa-eye"></i></a>
							<?php endif; ?>
							<a href="<?=base_url('branch/edit/' . $row->id)?>" class="btn btn-default btn-circle icon" data-toggle="tooltip" data-original-title="<?=translate('edit')?>"><i class="fas fa-pen-nib"></i></a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</section>
<?php endif; ?>

<?php if (!empty($students)): ?>
	<section class="panel appear-animation" data-appear-animation="<?=$global_config['animations']?>" data-appear-animation-delay="100">
		<header class="panel-heading">
			<h4 class="panel-title"><i class="fas fa-user-graduate"></i> <?=translate('student_list')?></h4>
		</header>
		<div class="panel-body mb-md">
			<table class="table table-bordered table-condensed table-hover table-export">
				<thead>
					<tr>
						<th><?=translate('sl')?></th>
						<th><?=translate('name')?></th>
						<th><?=translate('register_no')?></th>
						<th><?=translate('class')?></th>
						<th><?=translate('section')?></th>
						<th><?=translate('email')?></th>
						<th class="no-sort"><?=translate('action')?></th>
					</tr>
				</thead>
				<tbody>
					<?php $count = 1; foreach ($students as $row): ?>
					<tr>
						<td><?php echo $count++; ?></td>
						<td><?php echo html_escape($row->first_name . ' ' . $row->last_name); ?></td>
						<td><?php echo html_escape($row->register_no); ?></td>
						<td><?php echo html_escape($row->class_name); ?></td>
						<td><?php echo html_escape($row->section_name); ?></td>
						<td><?php echo html_escape($row->email); ?></td>
						<td class="min-w-c">
							<?php if (get_permission('student', 'is_edit')): ?>
							<a href="<?=base_url('student/profile/' . $row->id)?>" class="btn btn-default btn-circle icon" data-toggle="tooltip" data-original-title="<?=translate('details')?>"><i class="far fa-arrow-alt-circle-right"></i></a>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</section>
<?php endif; ?>

<?php if (!empty($staff)): ?>
	<section class="panel appear-animation" data-appear-animation="<?=$global_config['animations']?>" data-appear-animation-delay="100">
		<header class="panel-heading">
			<h4 class="panel-title"><i class="fas fa-users"></i> <?=translate('staff')?></h4>
		</header>
		<div class="panel-body mb-md">
			<table class="table table-bordered table-condensed table-hover table-export">
				<thead>
					<tr>
						<th><?=translate('sl')?></th>
						<th><?=translate('name')?></th>
						<th><?=translate('designation')?></th>
						<th><?=translate('username')?></th>
						<th><?=translate('email')?></th>
						<th class="no-sort"><?=translate('action')?></th>
					</tr>
				</thead>
				<tbody>
					<?php $count = 1; foreach ($staff as $row): ?>
					<tr>
						<td><?php echo $count++; ?></td>
						<td><?php echo html_escape($row->name); ?></td>
						<td><?php echo html_escape($row->designation); ?></td>
						<td><?php echo html_escape($row->username); ?></td>
						<td><?php echo html_escape($row->email); ?></td>
						<td class="min-w-c">
							<a href="<?=base_url('employee/profile/' . $row->id)?>" class="btn btn-default btn-circle icon" data-toggle="tooltip" data-original-title="<?=translate('details')?>"><i class="far fa-arrow-alt-circle-right"></i></a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</section>
<?php endif; ?>

<?php if (!empty($pages)): ?>
	<section class="panel appear-animation" data-appear-animation="<?=$global_config['animations']?>" data-appear-animation-delay="100">
		<header class="panel-heading">
			<h4 class="panel-title"><i class="fas fa-sliders"></i> <?=translate('settings')?></h4>
		</header>
		<div class="panel-body mb-md">
			<ul class="list-unstyled list-search-pages mb-none">
				<?php foreach ($pages as $page): ?>
				<li class="mb-sm">
					<a href="<?=$page['url']?>"><i class="fas fa-caret-right"></i> <?php echo html_escape($page['label']); ?></a>
					<?php if (!empty($page['section'])): ?><small class="text-muted"> &mdash; <?php echo html_escape($page['section']); ?></small><?php endif; ?>
				</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
<?php endif; ?>
