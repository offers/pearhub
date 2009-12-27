<?php echo html_form_tag('delete', url('', array('delete'))); ?>

<p>
  Delete the project?
</p>

<div class="form-footer">
  <a href="<?php e(url()) ?>">Cancel</a>
  <input type="submit" value="Delete" class="warning" />
</div>

</form>
