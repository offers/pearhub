<div id="search">
  <?php echo html_form_tag('get'); ?>
    <a href="<?php e(url()) ?>">All projects</a>
    <?php echo html_text_field('q', query('q'), array('id' => 'q')); ?>
    <input type="submit" value="Search" class="submit" />
  </form>
</div>

<ul class="list">
<?php foreach ($projects as $entry): ?>
  <li>
<a href="<?php e(url($entry->name())) ?>" title="<?php e($entry->description()); ?>">
<?php e($entry->displayName()) ?>
<br/>
<span class="summary"><?php e($entry->summary()); ?></span>
</a>
  </li>
<?php endforeach; ?>
</ul>
<p>
  <a href="<?php e(url('', array('new'))); ?>">Add new project</a>
</p>

<?php echo krudt_paginate($projects, array('q')); ?>

