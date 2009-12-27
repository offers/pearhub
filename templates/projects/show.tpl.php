<p>
  <a href="<?php e(url('', array('edit'))); ?>">edit</a>
  <a href="<?php e(url('', array('delete'))); ?>">delete</a>
</p>
<dl>
  <dt>Name</dt>
  <dd><?php e($project->name()); ?></dd>
  <dt>Created</dt>
  <dd><?php e($project->created()); ?></dd>
  <dt>Owner</dt>
  <dd><?php e($project->owner()); ?></dd>
  <dt>Repository</dt>
  <dd><?php e($project->repository()); ?></dd>
  <dt>Required php version</dt>
  <dd><?php e($project->phpVersion()); ?></dd>
  <dt>License</dt>
  <dd><?php e($project->licenseTitle()); ?> <?php $project->licenseHref() ? html_link($project->licenseHref()) : e($project->licenseHref()); ?></dd>
</dl>

<h2>Maintainers</h2>

<?php foreach ($project->projectMaintainers() as $m): ?>
<dl>
  <dt>user</dt>
  <dd><?php e($m->maintainer()->user()) ?></dd>
  <dt>role</dt>
  <dd><?php e($m->type()) ?></dd>
<?php if ($m->maintainer()->name()) : ?>
  <dt>name</dt>
  <dd><?php e($m->maintainer()->name()) ?></dd>
<?php endif; ?>
<?php if ($m->maintainer()->email()) : ?>
  <dt>email</dt>
  <dd><?php e($m->maintainer()->email()) ?></dd>
<?php endif; ?>
</dl>
<?php endforeach; ?>

<h2>Dependencies</h2>

<ul>
<?php foreach ($project->dependencies() as $dep): ?>
  <li><?php e($dep['channel']) ?> <?php e($dep['version']) ?></li>
<?php endforeach; ?>
</ul>
