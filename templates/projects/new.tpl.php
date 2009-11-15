<h2>New project</h2>
<?php print $this->html_form_tag('post', url('', array('new'))); ?>
<?php print $this->errors($project); ?>
<?php include('form.tpl.php'); ?>
</form>
