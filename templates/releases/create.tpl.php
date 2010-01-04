<?php echo html_form_tag('post', url('', array('create'))); ?>
<?php if ($error): ?>
<p style="color:red">
  <?php e($error) ?>
</p>
<?php endif; ?>
<p>
  Create a new release for <?php e($project->displayName()) ?> from the repository at:
</p>
<p>
  <code><?php e($project->repository()) ?></code>
</p>

<p class="form">
  <label for="field-version">version:</label>
  <?php echo html_text_field('version', $version, array('id' => 'field-version')); ?>
</p>

<div class="form-footer">
  <a href="<?php e(url()) ?>">Cancel</a>
  <input type="submit" value="Create" />
</div>

</form>
