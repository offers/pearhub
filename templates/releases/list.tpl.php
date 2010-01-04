<?php if ($project->releasePolicy() == 'manual'): ?>
<p>
  <a href="<?php e(url('', array('create'))) ?>">create new release</a>
</p>
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
