<h2>Edit <?php e($project->displayName()); ?></h2>
 <?php echo html_form_tag('put', url('', array('edit')), array('id' => 'project-form')); ?>
<?php echo krudt_errors($project); ?>
<?php include('form.tpl.php'); ?>
</form>
