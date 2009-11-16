<p class="krudt-form">
  <label for="field-name">name</label>
  <input type="text" id="field-name" name="name" value="<?=e($project->name())?>" />
<?php if (isset($project->errors['name'])): ?>
    <span style="display:block;color:red">
      <?= e(implode(', ', $project->errors['name'])) ?>
    </span>
<?php endif; ?>
</p>

<p class="krudt-form">
  <label for="field-summary">summary</label>
  <textarea id="field-summary" name="summary"><?=e($project->summary())?></textarea>
<?php if (isset($project->errors['summary'])): ?>
    <span style="display:block;color:red">
      <?= e(implode(', ', $project->errors['summary'])) ?>
    </span>
<?php endif; ?>
</p>

<p class="krudt-form">
  <label for="field-license-title">license-title</label>
  <input type="text" id="field-license-title" name="license-title" value="<?=e($project->licenseTitle())?>" />
<?php if (isset($project->errors['license-title'])): ?>
    <span style="display:block;color:red">
      <?= e(implode(', ', $project->errors['license-title'])) ?>
    </span>
<?php endif; ?>
</p>

<p class="krudt-form">
  <label for="field-license-href">license-href</label>
  <input type="text" id="field-license-href" name="license-href" value="<?=e($project->licenseHref())?>" />
<?php if (isset($project->errors['license-href'])): ?>
    <span style="display:block;color:red">
      <?= e(implode(', ', $project->errors['license-href'])) ?>
    </span>
<?php endif; ?>
</p>

<p class="krudt-form">
  <label for="field-php-version">php-version</label>
  <input type="text" id="field-php-version" name="php-version" value="<?=e($project->phpVersion())?>" />
<?php if (isset($project->errors['php-version'])): ?>
    <span style="display:block;color:red">
      <?= e(implode(', ', $project->errors['php-version'])) ?>
    </span>
<?php endif; ?>
</p>

<p class="krudt-form">
  <label for="field-repository">repository</label>
  <input type="text" id="field-repository" name="repository" value="<?=e($project->repository())?>" />
<?php if (isset($project->errors['repository'])): ?>
    <span style="display:block;color:red">
      <?= e(implode(', ', $project->errors['repository'])) ?>
    </span>
<?php endif; ?>
</p>

<p class="krudt-form">
  <label for="field-filespec">filespec</label>
  <textarea id="field-filespec" name="filespec"><?=e(json_encode($project->filespec()))?></textarea>
<?php if (isset($project->errors['filespec'])): ?>
    <span style="display:block;color:red">
      <?= e(implode(', ', $project->errors['filespec'])) ?>
    </span>
<?php endif; ?>
</p>

<p class="krudt-form">
  <label for="field-ignore">ignore</label>
  <textarea id="field-ignore" name="ignore"><?=e(json_encode($project->ignore()))?></textarea>
<?php if (isset($project->errors['ignore'])): ?>
    <span style="display:block;color:red">
      <?= e(implode(', ', $project->errors['ignore'])) ?>
    </span>
<?php endif; ?>
</p>

<p class="krudt-form">
  <label for="field-maintainers">maintainers</label>
<?php
$maintainers = array();
foreach ($project->maintainers() as $s) {
  $maintainers[] = $s->toStruct();
}
?>
  <textarea id="field-maintainers" name="maintainers"><?=e(json_encode($maintainers))?></textarea>
<?php if (isset($project->errors['maintainers'])): ?>
    <span style="display:block;color:red">
      <?= e(implode(', ', $project->errors['maintainers'])) ?>
    </span>
<?php endif; ?>
</p>

<p class="form-footer">
  <a href="<?= e(url()) ?>">Cancel</a>
  :
  <input type="submit" value="OK" />
</p>

