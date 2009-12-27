<div class="form full">
  <label for="field-summary">summary</label>
  <?php echo html_text_area("summary", $project->summary(), array('id' => "field-summary")); ?>
  <?php echo krudt_errors_for($project, 'summary'); ?>
</div>

<div class="form full">
  <label for="field-repository">repository</label>
  <?php echo html_text_field("repository", $project->repository(), array('id' => "field-repository")); ?>
  <?php echo krudt_errors_for($project, 'repository'); ?>
</div>

<div class="form">
  <label for="field-php-version">php-version</label>
  <?php echo html_text_field("php-version", $project->phpVersion(), array('id' => "field-php-version")); ?>
  <?php echo krudt_errors_for($project, 'php-version'); ?>
</div>

<div class="form">
  <h2>license</h2>
  <div class="container">
    <label><span>title:</span><?php echo html_text_field("license-title", $project->licenseTitle(), array('id' => "field-license-title")); ?></label>
    <?php echo krudt_errors_for($project, 'license-title'); ?>

    <label><span>href:</span><?php echo html_text_field("license-href", $project->licenseHref(), array('id' => "field-license-href")); ?></label>
    <?php echo krudt_errors_for($project, 'license-href'); ?>
  </div>
</div>

<div class="form">
  <h2>filespec</h2>
  <div id="filespec-container" class="container">
<?php foreach ($project->filespec() as $spec): ?>
    <div class="filespec-fieldset fieldset">
      <a href="#" class="remove" title="Click to remove this filespec">&#8855;</a>
<?php $unique = uniqid(); ?>
      <label><span>path:</span><input type="text" name="filespec[<?=$unique?>][path]" value="<?=e($spec['path'])?>" /></label>
      <label><span>type:</span><?php echo html_select("filespec[$unique][type]", array('src', 'doc', 'bin'), $spec['type']); ?></label>
    </div>
<?php endforeach; ?>
  </div>
  <div class="append-wrapper">
    <a href="#" id="filespec-append" class="append" title="Click to add a filespec">Add filespec</a>
  </div>
  <?php echo krudt_errors_for($project, 'filespec'); ?>
</div>

<div class="form">
  <h2>ignore</h2>
  <div id="ignore-container" class="container">
<?php foreach ($project->ignore() as $pattern): ?>
    <div class="ignore-fieldset fieldset">
      <a href="#" class="remove" title="Click to remove this ignore">&#8855;</a>
      <label><span>pattern:</span><input type="text" name="ignore[]" value="<?=e($pattern)?>" /></label>
    </div>
<?php endforeach; ?>
  </div>
  <div class="append-wrapper">
    <a href="#" id="ignore-append" class="append" title="Click to add an ignore rule">Add ignore</a>
  </div>
  <?php echo krudt_errors_for($project, 'ignore'); ?>
</div>

<div class="form">
  <h2>maintainers</h2>
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
  <div class="append-wrapper">
    <a href="#" id="maintainers-append" class="append" title="Click to add a maintainer">Add maintainer</a>
  </div>
  <?php echo krudt_errors_for($project, 'maintainers'); ?>
</div>

<div class="form">
  <h2>dependencies</h2>
  <div id="dependencies-container" class="container">
<?php foreach ($project->dependencies() as $dep): ?>
    <div class="dependencies-fieldset fieldset">
      <a href="#" class="remove" title="Click to remove this dependency">&#8855;</a>
      <?php $unique = uniqid(); ?>
      <label><span>channel:</span><?php echo html_text_field("dependencies[".$unique."][channel]", $dep['channel']); ?></label>
      <label><span>version:</span><?php echo html_text_field("dependencies[".$unique."][version]", $dep['version']); ?></label>
    </div>
<?php endforeach; ?>
  </div>
  <div class="append-wrapper">
    <a href="#" id="dependencies-append" class="append" title="Click to add a dependency">Add Dependency</a>
  </div>
  <?php echo krudt_errors_for($project, 'dependencies'); ?>
</div>

<div id="maintainers-autocomplete">
</div>

<?php add_onload('URL_AUTOCOMPLETE_MAINTAINERS = "'. url('/projects?maintainers') .'";'); ?>
<?php add_onload('init();'); ?>

