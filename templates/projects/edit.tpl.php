<?php echo html_form_tag('put', url('', array('edit')), array('id' => 'project-form')); ?>
<?php echo krudt_errors($project); ?>
<?php include('form.tpl.php'); ?>
</form>

<div class="form-footer">
  <a href="<?php e(url()) ?>">Cancel</a>
  <input type="submit" value="Save Changes" />
</div>

