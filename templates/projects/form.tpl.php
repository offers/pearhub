<div class="form full">
  <label for="field-name">name</label>
  <input type="text" id="field-name" name="name" value="<?=e($project->name())?>" />
  <?php echo krudt_errors_for($project, 'name'); ?>
</div>

<div class="form full">
  <label for="field-summary">summary</label>
  <textarea id="field-summary" name="summary"><?=e($project->summary())?></textarea>
  <?php echo krudt_errors_for($project, 'summary'); ?>
</div>

<div class="form full">
  <label for="field-license-title">license-title</label>
  <input type="text" id="field-license-title" name="license-title" value="<?=e($project->licenseTitle())?>" />
  <?php echo krudt_errors_for($project, 'license-title'); ?>
</div>

<div class="form full">
  <label for="field-license-href">license-href</label>
  <input type="text" id="field-license-href" name="license-href" value="<?=e($project->licenseHref())?>" />
  <?php echo krudt_errors_for($project, 'license-href'); ?>
</div>

<div class="form">
  <label for="field-php-version">php-version</label>
  <input type="text" id="field-php-version" name="php-version" value="<?=e($project->phpVersion())?>" />
  <?php echo krudt_errors_for($project, 'php-version'); ?>
</div>

<div class="form full">
  <label for="field-repository">repository</label>
  <input type="text" id="field-repository" name="repository" value="<?=e($project->repository())?>" />
  <?php echo krudt_errors_for($project, 'repository'); ?>
</div>

<div class="form">
  <label for="field-filespec">filespec</label>
  <div id="filespec-container" class="container">
<?php foreach ($project->filespec() as $spec): ?>
    <div class="filespec-fieldset fieldset">
      <a href="#" class="remove" title="Click to remove this filespec">&#8855;</a>
<?php $unique = uniqid(); ?>
      <input type="text" name="filespec[<?=$unique?>][path]" value="<?=e($spec['path'])?>"
      /><?php echo html_select("filespec[$unique][type]", array('src', 'doc', 'bin'), $spec['type']); ?>
    </div>
<?php endforeach; ?>
  </div>
  <a href="#" id="filespec-append" class="append" title="Click to add a filespec">Add filespec</a>
  <?php echo krudt_errors_for($project, 'filespec'); ?>
</div>

<div class="form">
  <label>ignore</label>
  <div id="ignore-container" class="container">
<?php foreach ($project->ignore() as $pattern): ?>
    <div class="ignore-fieldset fieldset">
      <a href="#" class="remove" title="Click to remove this ignore">&#8855;</a>
      <input type="text" name="ignore[]" value="<?=e($pattern)?>" />
    </div>
<?php endforeach; ?>
  </div>
  <a href="#" id="ignore-append" class="append" title="Click to add an ignore rule">Add ignore</a>
  <?php echo krudt_errors_for($project, 'ignore'); ?>
</div>

<div class="form">
  <label for="field-maintainers">maintainers</label>
  <div id="maintainers-container" class="container">
<?php foreach ($project->projectMaintainers() as $m): ?>
<?php $is_locked = $m->maintainer()->owner() !== $context->identity()->user(); ?>
<?php $more = $is_locked ? ' disabled="true"' : '' ; ?>
    <div class="maintainers-fieldset fieldset">
      <a href="#" class="remove" title="Click to remove this maintainer">&#8855;</a>
<?php $unique = uniqid(); ?>
      <label><span>user:</span><input type="text" name="maintainers[<?=$unique?>][user]" value="<?=e($m->maintainer()->user())?>"<?=$more?> /></label>
      <label><span>name:</span><input type="text" name="maintainers[<?=$unique?>][name]" value="<?=e($m->maintainer()->name())?>"<?=$more?> /></label>
      <label><span>email:</span><input type="text" name="maintainers[<?=$unique?>][email]" value="<?=e($m->maintainer()->email())?>"<?=$more?> /></label>
      <label>
        <span>type:</span>
        <?php echo html_select("maintainers[$unique][type]", array('lead', 'helper'), $m->type()); ?>
      </label>
    </div>
<?php endforeach; ?>
  </div>
  <a href="#" id="maintainers-append" class="append" title="Click to add a maintainer">Add maintainer</a>
  <?php echo krudt_errors_for($project, 'maintainers'); ?>
</div>

<div id="maintainers-autocomplete">
</div>

<?php add_onload('URL_AUTOCOMPLETE_MAINTAINERS = "'. url('/projects?maintainers') .'";'); ?>
<?php add_onload('init();'); ?>

<div class="form-footer">
  <a href="<?php e(url()) ?>">Cancel</a>
  :
  <input type="submit" value="OK" />
</div>

