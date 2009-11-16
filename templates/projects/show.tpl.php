<h2><?php e($project->displayName()); ?></h2>
<dl>
  <dt>Name</dt>
  <dd><?php e($project->name()); ?></dd>
  <dt>Owner</dt>
  <dd><?php e($project->owner()); ?></dd>
  <dt>Created</dt>
  <dd><?php e($project->created()); ?></dd>
  <dt>Repository</dt>
  <dd><?php e($project->repository()); ?></dd>
</dl>
<p>
  <a href="<?php e(url('', array('edit'))); ?>">Edit project</a>
  :
  <a href="<?php e(url('', array('delete'))); ?>">Delete project</a>
</p>
