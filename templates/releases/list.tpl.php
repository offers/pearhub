<?php if ($project->releasePolicy() === 'auto'): ?>
<p>
  This project is on automatic release policy.
<?php if ($context->canCreate()): ?>
  To roll a new release just make a tag in the repository. Having trouble? Check the <?php echo html_link(url('/faq'), 'FAQ'); ?>.
<?php else: ?>
  New releases will appear here, shortly after they are tagged in the projects repository.
<?php endif; ?>
</p>
<?php else: ?>
<p>
  This project is on manual release policy.
<?php if ($context->canCreate()): ?>
  <?php echo html_link(url('', array('create')), "You can create a new release right now"); ?>.
<?php endif; ?>
</p>
<?php endif; ?>

<?php if (count($releases) === 0): ?>
<p>
  There are no releases yet.
</p>
<?php else: ?>
<p>
  To install a particular version, use:
</p>
<pre>$ pear install pearhub/<?php e($project->name()) ?>-X.X.X</pre>

<?php endif; ?>

<ul id="releases">
<?php foreach ($releases as $release): ?>
<?php $package_name = $project->name() . '-' . $release->version() . '.tgz'; ?>
  <li>
    <h3><?php e($release->version()) ?></h3>
    <p>
      created: <?php e($release->created()) ?>
      <br/>
      mode: <?php e($release->mode()) ?>
      <br/>
      status: <?php e($release->status()) ?>
    </p>
<?php if ($release->status() == 'completed'): ?>
    <p>
      Download: <a href="<?php e(url('/get/' . $package_name)) ?>"><?php e($package_name) ?></a> (For manual installation only)
    </p>
<?php endif; ?>
  </li>
<?php endforeach; ?>
</ul>
