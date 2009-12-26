<h2>Projects</h2>

<ul>
<?php foreach ($projects as $entry): ?>
<li><a href="<?php e(url($entry->name())); ?>"><?php e($entry->displayName()); ?></a></li>
<?php endforeach; ?>
</ul>
<p>
  <a href="<?php e(url('', array('new'))); ?>">New entry</a>
</p>

<?php echo krudt_paginate($projects); ?>

