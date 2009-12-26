<h2>New project</h2>
<?php echo html_form_tag('post', url('', array('new'))); ?>
<?php echo krudt_errors($project); ?>
<?php include('form.tpl.php'); ?>
</form>
