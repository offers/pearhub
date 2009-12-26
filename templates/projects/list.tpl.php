<h2>Projects</h2>

<?php echo html_form_tag('get'); ?>
<?php echo html_text_field('q', query('q')); ?>
<input type="submit" value="Search" />
<a href="<?php e(url()) ?>">All projects</a>
</form>

Found <?php e($projects->count()) ?> projects.

<ul>
<?php foreach ($projects as $entry): ?>
<li><a href="<?php e(url($entry->name())); ?>"><?php e($entry->displayName()); ?></a></li>
<?php endforeach; ?>
</ul>
<p>
  <a href="<?php e(url(array('new'))); ?>">New entry</a>
</p>

<?php echo krudt_paginate($projects, array('q')); ?>

