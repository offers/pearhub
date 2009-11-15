<h2>Delete <?php e($contact->display_name()); ?></h2>
<?php print $this->html_form_tag('delete', url('', array('delete'))); ?>
<?php print $this->form_footer(); ?>
</form>
