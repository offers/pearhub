<div class="form">
  <label for="field-name">name</label>
  <input type="text" id="field-name" name="name" value="<?=e($project->name())?>" />
<?php if (isset($project->errors['name'])): ?>
    <span style="display:block;color:red">
      <?= e(implode(', ', $project->errors['name'])) ?>
    </span>
<?php endif; ?>
</div>

<div class="form">
  <label for="field-summary">summary</label>
  <textarea id="field-summary" name="summary"><?=e($project->summary())?></textarea>
<?php if (isset($project->errors['summary'])): ?>
    <span style="display:block;color:red">
      <?= e(implode(', ', $project->errors['summary'])) ?>
    </span>
<?php endif; ?>
</div>

<div class="form">
  <label for="field-license-title">license-title</label>
  <input type="text" id="field-license-title" name="license-title" value="<?=e($project->licenseTitle())?>" />
<?php if (isset($project->errors['license-title'])): ?>
    <span style="display:block;color:red">
      <?= e(implode(', ', $project->errors['license-title'])) ?>
    </span>
<?php endif; ?>
</div>

<div class="form">
  <label for="field-license-href">license-href</label>
  <input type="text" id="field-license-href" name="license-href" value="<?=e($project->licenseHref())?>" />
<?php if (isset($project->errors['license-href'])): ?>
    <span style="display:block;color:red">
      <?= e(implode(', ', $project->errors['license-href'])) ?>
    </span>
<?php endif; ?>
</div>

<div class="form">
  <label for="field-php-version">php-version</label>
  <input type="text" id="field-php-version" name="php-version" value="<?=e($project->phpVersion())?>" />
<?php if (isset($project->errors['php-version'])): ?>
    <span style="display:block;color:red">
      <?= e(implode(', ', $project->errors['php-version'])) ?>
    </span>
<?php endif; ?>
</div>

<div class="form">
  <label for="field-repository">repository</label>
  <input type="text" id="field-repository" name="repository" value="<?=e($project->repository())?>" />
<?php if (isset($project->errors['repository'])): ?>
    <span style="display:block;color:red">
      <?= e(implode(', ', $project->errors['repository'])) ?>
    </span>
<?php endif; ?>
</div>

<div class="form">
  <label for="field-filespec">filespec</label>
  <div id="filespec-container" class="container">
<?php foreach ($project->filespec() as $spec): ?>
    <div class="filespec-fieldset fieldset">
      <span class="remove" title="Click to remove this filespec">&#8855;</span>
<?php $unique = uniqid(); ?>
      <input type="text" name="filespec[<?=$unique?>][path]" value="<?=e($spec['path'])?>" />
      <select name="filespec[<?=$unique?>][type]">
<?php foreach (array('src', 'doc', 'bin') as $type): ?>
   <option<?php if ($spec['type'] == $type) echo ' selected="selected"' ?>><?=e($type)?></option>
<?php endforeach; ?>
      </select>
    </div>
<?php endforeach; ?>
  </div>
  <div id="filespec-append" class="append">Add filespec</div>
<?php if (isset($project->errors['filespec'])): ?>
    <span style="display:block;color:red">
      <?= e(implode(', ', $project->errors['filespec'])) ?>
    </span>
<?php endif; ?>
</div>

<div class="form">
  <label>ignore</label>
  <div id="ignore-container" class="container">
<?php foreach ($project->ignore() as $pattern): ?>
    <div class="ignore-fieldset fieldset">
      <span class="remove" title="Click to remove this ignore">&#8855;</span>
      <input type="text" name="ignore[]" value="<?=e($pattern)?>" />
    </div>
<?php endforeach; ?>
  </div>
  <div id="ignore-append" class="append">Add ignore</div>
<?php if (isset($project->errors['ignore'])): ?>
    <span style="display:block;color:red">
      <?= e(implode(', ', $project->errors['ignore'])) ?>
    </span>
<?php endif; ?>
</div>

<div class="form">
  <label for="field-maintainers">maintainers</label>
  <div id="maintainers-container" class="container">
<?php foreach ($project->projectMaintainers() as $m): ?>
    <div class="maintainers-fieldset fieldset">
      <span class="remove" title="Click to remove this maintainer">&#8855;</span>
<?php $unique = uniqid(); ?>
      <label><span>user:</span><input type="text" name="maintainers[<?=$unique?>][user]" value="<?=e($m->maintainer()->user())?>" /></label>
      <label><span>name:</span><input type="text" name="maintainers[<?=$unique?>][name]" value="<?=e($m->maintainer()->name())?>" /></label>
      <label><span>email:</span><input type="text" name="maintainers[<?=$unique?>][email]" value="<?=e($m->maintainer()->email())?>" /></label>
      <label>
        <span>type:</span>
        <select name="maintainers[<?=$unique?>][type]">
<?php
foreach (array('lead', 'helper') as $t):
   if ($t == $m->type()):
     echo '<option selected="selected">';
   else:
     echo '<option>';
   endif;
   e($t);
   echo '</option>';
endforeach;
?>
        </select>
      </label>
    </div>
<?php endforeach; ?>
  </div>
  <div id="maintainers-append" class="append">Add maintainer</div>
<?php if (isset($project->errors['maintainers'])): ?>
    <span style="display:block;color:red">
      <?= e(implode(', ', $project->errors['maintainers'])) ?>
    </span>
<?php endif; ?>
</div>

<div id="maintainers-autocomplete">
</div>

<script>//<![CDATA[
   URL_AUTOCOMPLETE_MAINTAINERS = "<?= url('/projects?maintainers'); ?>";
   init();
//]]></script>

<div class="form-footer">
  <a href="<?= e(url()) ?>">Cancel</a>
  :
  <input type="submit" value="OK" />
</div>

