<h2>Delete <?php e($project->displayName()); ?></h2>
<?php echo html_form_tag('delete', url('', array('delete'))); ?>

<div class="form-footer">
  <a href="<?php e(url()) ?>">Cancel</a>
  :
  <input type="submit" value="OK" />
</div>

</form>
