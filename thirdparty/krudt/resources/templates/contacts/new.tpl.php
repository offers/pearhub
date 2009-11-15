<h2>New contact</h2>
<?php print $this->html_form_tag('post', url('', array('new'))); ?>
<?php print $this->errors($contact); ?>
<?php include('form.tpl.php'); ?>
</form>
