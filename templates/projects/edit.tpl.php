<p>
  <?php echo html_link(url(), "Cancel"); ?>
<?php if ($context->canDelete()): ?>
  | <?php echo html_link(url('', array('delete')), "Delete project?"); ?>
<?php endif; ?>
</p>
<?php echo html_form_tag('put', url('', array('edit')), array('id' => 'project-form')); ?>
<?php echo form_errors($project); ?>
<?php include('form.tpl.php'); ?>
<div class="form-footer">
  <a href="<?php e(url()) ?>">Cancel</a>
  <input type="submit" value="Save Changes" />
</div>
</form>
