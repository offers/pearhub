<h2><?php e($contact->display_name()); ?></h2>
<dl>
  <dt>First Name</dt>
  <dd><?php e($contact->first_name()); ?></dd>
</dl>
<p>
  <a href="<?php e(url('', array('edit'))); ?>">Edit contact</a>
  :
  <a href="<?php e(url('', array('delete'))); ?>">Delete contact</a>
</p>
