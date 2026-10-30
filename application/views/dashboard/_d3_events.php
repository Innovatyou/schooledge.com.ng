<?php
// Upcoming events list. Params: $events (getUpcomingEvents)
if (empty($events)) { ?>
<div class="d3-empty"><i class="far fa-calendar"></i> Nothing scheduled in the next 30 days.</div>
<?php } else { ?>
<ul class="d3-list">
	<?php foreach ($events as $i => $e) {
		$days = (int) floor((strtotime($e->start_date) - strtotime(date('Y-m-d'))) / 86400); ?>
	<li class="tone-<?php echo array('violet', 'cyan', 'amber', 'mint')[$i % 4]; ?>">
		<span class="d3-when"><?php echo date('j', strtotime($e->start_date)); ?><small><?php echo date('M', strtotime($e->start_date)); ?></small></span>
		<span class="d3-what">
			<b><?php echo html_escape($e->title); ?></b>
			<small><?php echo $days === 0 ? 'Today' : ($days === 1 ? 'Tomorrow' : 'In ' . $days . ' days'); ?><?php echo $e->end_date && $e->end_date !== $e->start_date ? ' &middot; until ' . date('j M', strtotime($e->end_date)) : ''; ?></small>
		</span>
		<span class="d3-dot"></span>
	</li>
	<?php } ?>
</ul>
<?php }
