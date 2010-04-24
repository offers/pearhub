<div class="form full">
  <label for="field-summary">summary</label>
  <?php echo html_text_field("summary", $project->summary(), array('id' => "field-summary")); ?>
  <?php echo form_errors_for($project, 'summary'); ?>
</div>

<div class="form full">
  <label for="field-description">description</label>
  <?php echo html_text_area("description", $project->description(), array('id' => "field-description")); ?>
  <?php echo form_errors_for($project, 'description'); ?>
</div>

<div class="form full">
  <label for="field-repository">repository</label>
  <?php echo html_text_field("repository", $project->repository(), array('id' => "field-repository")); ?>
  <?php echo form_errors_for($project, 'repository'); ?>
</div>

<div class="form">
  <div class="container">
    <label><span>username:</span>
    <?php echo html_text_field("repository-username", $project->repositoryUsername(), array('id' => "field-repository-username")); ?>
    </label>
    <?php echo form_errors_for($project, 'repository-username'); ?>
    <label><span>password:</span>
    <?php echo html_text_field("repository-password", $project->repositoryPassword(), array('id' => "field-repository-password")); ?>
    </label>
    <?php echo form_errors_for($project, 'repository-password'); ?>
  </div>
</div>

<h2>files</h2>

<div class="form">
  <div class="container">
    <label><span>path:</span>
    <?php echo html_text_field("path", $project->path(), array('id' => "field-path")); ?>
    </label>
    <?php echo form_errors_for($project, 'path'); ?>
    <label><span>destination:</span>
    <?php echo html_text_field("destination", $project->destination(), array('id' => "field-destination")); ?>
    </label>
    <?php echo form_errors_for($project, 'destination'); ?>
    <label><span>ignore:</span>
    <?php echo html_text_field("ignore", $project->ignore(), array('id' => "field-ignore")); ?>
    </label>
    <?php echo form_errors_for($project, 'ignore'); ?>
  </div>
</div>

<h2>maintainers</h2>

<div class="form">
  <div id="maintainers-container" class="container">
<?php foreach ($project->projectMaintainers() as $m): ?>
<?php $is_locked = $m->maintainer()->owner() !== $context->identity()->user(); ?>
<?php $more = $is_locked ? ' disabled="true"' : '' ; ?>
    <div class="maintainers-fieldset fieldset">
      <a href="#" class="remove" title="Click to remove this maintainer">&#8855;</a>
<?php $unique = uniqid(); ?>
      <label><span>user:</span><input type="text" name="maintainers[<?php e($unique)?>][user]" value="<?php e($m->maintainer()->user())?>"<?php echo $more?> /></label>
      <label><span>name:</span><input type="text" name="maintainers[<?php e($unique)?>][name]" value="<?php e($m->maintainer()->name())?>"<?php echo $more?> /></label>
      <label><span>email:</span><input type="text" name="maintainers[<?php e($unique)?>][email]" value="<?php e($m->maintainer()->email())?>"<?php echo $more?> /></label>
      <label>
        <span>type:</span>
        <?php if ($is_locked) $options = array('disabled' => 'true'); else $options = array(); ?>
        <?php echo html_select("maintainers[".htmlspecialchars($unique)."][type]", array('lead', 'developer', 'contributor', 'helper'), $m->type(), $options); ?>
      </label>
    </div>
<?php endforeach; ?>
  </div>
  <div class="append-wrapper">
    <a href="#" id="maintainers-append" class="append" title="Click to add a maintainer">Add maintainer</a>
  </div>
  <?php echo form_errors_for($project, 'maintainers'); ?>
</div>

<h2>dependencies</h2>

<div class="form">
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
  <?php echo form_errors_for($project, 'dependencies'); ?>
</div>

<h2>details</h2>

<div class="form">
  <label><span>web-site:</span><?php echo html_text_field("href", $project->href(), array('id' => "field-href")); ?></label>
  <?php echo form_errors_for($project, 'href'); ?>
</div>

<div class="form">
  <label><span>php-version:</span><?php echo html_text_field("php-version", $project->phpVersion(), array('id' => "field-php-version")); ?></label>
  <?php echo form_errors_for($project, 'php-version'); ?>
</div>

<h2>license</h2>

<div class="form">
  <div class="container">
    <label><span>title:</span><?php echo html_text_field("license-title", $project->licenseTitle(), array('id' => "field-license-title")); ?></label>

    <label><span>href:</span><?php echo html_text_field("license-href", $project->licenseHref(), array('id' => "field-license-href")); ?></label>
    <?php echo form_errors_for($project, 'license-title'); ?>
    <?php echo form_errors_for($project, 'license-href'); ?>
  </div>
</div>

<h2>release policy</h2>

<div class="form">
  <div class="container">
    <?php echo html_radio("release-policy", "auto", $project->releasePolicy() == "auto", array('id' => "field-release-policy-auto", 'label' => "Automatic")); ?>
    <p>
      Releases are automatically created whenever a new tag is created in the repository. This is the default and recommended way to manage releases.
    </p>

    <?php echo html_radio("release-policy", "manual", $project->releasePolicy() == "manual", array('id' => "field-release-policy-manual", 'label' => "Manual")); ?>
    <p>
      Releases are made by explicitly requesting a new release from this site. You can use this if you don't want to follow the standard repository layout or if you don't have control over the repository (<?php echo html_link(url('/faq'), 'See the FAQ on managing someone else\'s project'); ?>)
    </p>

  </div>
</div>

<div id="maintainers-autocomplete">
</div>
<div id="tooltip">
</div>

<?php add_onload('URL_AUTOCOMPLETE_MAINTAINERS = "'. url('/projects?maintainers') .'";'); ?>
<?php add_onload('init();'); ?>

