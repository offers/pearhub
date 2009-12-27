<?php
require_once 'pdoext.inc.php';
require_once 'pdoext/connection.inc.php';
require_once 'pdoext/query.inc.php';
require_once 'pdoext/tablegateway.php';

class Accessor {
  public $errors = array();
  protected $row = array();
  function __construct($row = array()) {
    $this->row = $row;
  }
  function getArrayCopy() {
    return $this->row;
  }
  function __call($fn, $args) {
    if (preg_match('/^(g|s)et(.+)$/i', $fn, $reg)) {
      $mode = $reg[1];
      $camel_field = $reg[2];
    } else {
      $mode = 'g';
      $camel_field = $fn;
    }
    $colum = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $camel_field));
    if ($mode === 'g') {
      return isset($this->row[$colum]) ? $this->row[$colum] : null;
    } else {
      $this->row[$colum] = $args[0];
    }
  }
}

/*
$x = new Accessor();
$x->setFoo('fooval');
var_dump($x);
var_dump($x->foo());
*/

class ProjectGateway extends pdoext_TableGateway {
  protected $sql_load_aggregates;
  protected $maintainers;
  function __construct(pdoext_Connection $db, MaintainersGateway $maintainers) {
    parent::__construct('projects', $db);
    $this->maintainers = $maintainers;
  }
  function load($row = array()) {
    $p = new Project($row);
    if ($row['id']) {
      return $this->loadAggregates($p);
    }
    return $p;
  }
  function loadAggregates($project) {
    if (!$this->sql_load_aggregates) {
      $this->sql_load_aggregates = $this->db->prepare(
        '
SELECT
  project_maintainers.type,
  maintainers.owner,
  maintainers.user,
  maintainers.name,
  maintainers.email,
  null as filespec_type,
  null as filespec_path,
  null as pattern,
  null as channel,
  null as version
FROM
  project_maintainers
LEFT JOIN
  maintainers
ON project_maintainers.user = maintainers.user
WHERE
  project_maintainers.project_id = :id1

UNION

SELECT
  null,
  null,
  null,
  null,
  null,
  type as filespec_type,
  path as filespec_path,
  null,
  null,
  null
FROM
  filespecs
WHERE
  filespecs.project_id = :id2

UNION

SELECT
  null,
  null,
  null,
  null,
  null,
  null,
  null,
  pattern,
  null,
  null
FROM
  file_ignores
WHERE
  file_ignores.project_id = :id3

UNION

SELECT
  null,
  null,
  null,
  null,
  null,
  null,
  null,
  null,
  channel,
  version
FROM
  dependencies
WHERE
  dependencies.project_id = :id4
'
      );
      $this->sql_load_aggregates->setFetchMode(PDO::FETCH_ASSOC);
    }
    $this->sql_load_aggregates->execute(
      array(
        'id1' => $project->id(),
        'id2' => $project->id(),
        'id3' => $project->id(),
        'id4' => $project->id()));
    foreach ($this->sql_load_aggregates as $row) {
      if ($row['name']) {
        $project->addProjectMaintainer(new ProjectMaintainer($this->maintainers->load($row), $row['type'], $project->id()));
      } elseif ($row['filespec_path']) {
        $project->addFilespec($row['filespec_path'], $row['filespec_type']);
      } elseif ($row['pattern']) {
        $project->addIgnore($row['pattern']);
      } elseif ($row['channel']) {
          $project->addDependency($row['channel'], $row['version']);
      }
    }
    return $project;
  }
  function insertAggregates($project) {
    $insert_project_maintainer = $this->db->prepare(
      'insert into project_maintainers (project_id, user, type) values (:project_id, :user, :type)');
    foreach ($project->projectMaintainers() as $pm) {
      $insert_project_maintainer->execute(
        array(
          ':project_id' => $project->id(),
          ':user' => $pm->maintainer()->user(),
          ':type' => $pm->type()
        ));
    }
    $insert_filespec = $this->db->prepare(
      'insert into filespecs (project_id, path, type) values (:project_id, :path, :type)');
    foreach ($project->filespec() as $spec) {
      $insert_filespec->execute(
        array(
          ':project_id' => $project->id(),
          ':path' => $spec['path'],
          ':type' => $spec['type']
        ));
    }
    $insert_ignores = $this->db->prepare(
      'insert into file_ignores (project_id, pattern) values (:project_id, :pattern)');
    foreach ($project->ignore() as $pattern) {
      $insert_ignores->execute(
        array(
          ':project_id' => $project->id(),
          ':pattern' => $pattern
        ));
    }
    $insert_dependency = $this->db->prepare(
      'insert into dependencies (project_id, channel, version) values (:project_id, :channel, :version)');
    foreach ($project->dependencies() as $dep) {
      $insert_dependency->execute(
        array(
          ':project_id' => $project->id(),
          ':channel' => $dep['channel'],
          ':version' => $dep['version']
        ));
    }
  }
  function updateAggregates($project) {
    $this->db->prepare(
      'delete from project_maintainers where project_id = :project_id'
    )->execute(
        array(
          ':project_id' => $project->id()));
    $this->db->prepare(
      'delete from filespecs where project_id = :project_id'
    )->execute(
        array(
          ':project_id' => $project->id()));
    $this->db->prepare(
      'delete from file_ignores where project_id = :project_id'
    )->execute(
        array(
          ':project_id' => $project->id()));
    $this->db->prepare(
      'delete from dependencies where project_id = :project_id'
    )->execute(
        array(
          ':project_id' => $project->id()));
    $this->insertAggregates($project);
    $this->db->exec(
      'delete from maintainers where user not in (select user from project_maintainers)');
  }
  function validateUpdate($project) {
    if (!$project->id()) {
      $project->errors['id'][] = "Missing id";
    }
  }
  function validate($project) {
    if (!$project->name()) {
      $project->errors['name'][] = "Missing name";
    }
    if (!$project->repository()) {
      $project->errors['repository'][] = "Missing repository";
    }
    if (!preg_match('/^\d+\.\d+\.\d+$/', $project->phpVersion())) {
      $project->errors['php-version'][] = "Format of version must be X.X.X";
    }
    if (!$project->licenseTitle()) {
      $project->errors['license-title'][] = "Missing license";
    }
    $found = false;
    $names = array();
    foreach ($project->projectMaintainers() as $pm) {
      if ($pm->type() === 'lead') {
        $found = true;
      }
      if (!trim($pm->maintainer()->user())) {
        $project->errors['maintainers'][] = "Maintainer name is missing";
      }
      $names[] = $pm->maintainer()->user();
    }
    if (!$found) {
      $project->errors['maintainers'][] = "There must be at least one lead";
    }
    if (count(array_unique($names)) < count($names)) {
      $project->errors['maintainers'][] = "Each maintainer can only be entered once";
    }
    if (count($project->filespec()) === 0) {
      $project->errors['filespec'][] = "You must enter at least one filespec";
    }
    foreach ($project->filespec() as $spec) {
      if (!trim($spec['path'])) {
        $project->errors['filespec'][] = "Filespec path is missing";
      }
    }
    foreach ($project->ignore() as $pattern) {
      if (!trim($pattern)) {
        $project->errors['ignore'][] = "Ignore pattern is missing";
      }
    }
    foreach ($project->dependencies() as $dep) {
      if (!trim($dep['channel'])) {
        $project->errors['dependencies'][] = "Dependency channel is missing";
      }
      if ($dep['version'] && !preg_match('/^\d+\.\d+\.\d+$/', $dep['version'])) {
        $project->errors['dependencies'][] = "Format of version must be X.X.X";
      }
    }
  }
  function insert($project) {
    try {
      $id = parent::insert($project);
    } catch (PDOException $ex) {
      if (preg_match('/Integrity constraint violation: 1062 Duplicate entry .* for key 2/', $ex->getMessage())) {
        $project->errors['name'] = 'There is already a project registered with that name.';
      } else {
        $project->errors[] = $ex->getMessage();
      }
      return false;
    }
    if ($id) {
      $project->setId($id);
      $this->insertAggregates($project);
    }
    return $id;
  }
  function update($project, $condition = null) {
    $res = parent::update($project, $condition);
    if ($res) {
      $this->updateAggregates($project);
    }
    return $res;
  }
}

class MaintainersGateway extends pdoext_TableGateway {
  function __construct(pdoext_Connection $db) {
    parent::__construct('maintainers', $db);
  }
  function load($row = array()) {
    return new Maintainer($row);
  }
}

class Project extends Accessor {
  protected $filespec = array();
  protected $file_ignore = array();
  protected $dependencies = array();
  protected $project_maintainers = array();
  function __construct($row = array('php_version' => '5.0.0')) {
    parent::__construct($row);
  }
  function displayName() {
    return $this->name();
  }
  function filespec() {
    return $this->filespec;
  }
  function ignore() {
    return $this->file_ignore;
  }
  function dependencies() {
    return $this->dependencies;
  }
  function projectMaintainers() {
    return $this->project_maintainers;
  }
  function setId($id) {
    if ($this->id() !== null) {
      throw new Exception("Can't change id");
    }
    foreach ($this->project_maintainers as $project_maintainer) {
      $project_maintainer->setProjectId($id);
    }
    return $this->row['id'] = $id;
  }
  function addSourceFile($file) {
    $this->filespec[] = array('path' => $file, 'type' => 'src');
    return $file;
  }
  function addDocumentationFile($file) {
    $this->filespec[] = array('path' => $file, 'type' => 'doc');
    return $file;
  }
  function addBinFile($file) {
    $this->filespec[] = array('path' => $file, 'type' => 'bin');
    return $file;
  }
  function setFilespec($specs) {
    foreach ($specs as $spec) {
      if (!isset($spec['path'])) {
        throw new Exception("Missing property 'path'");
      }
      if (!isset($spec['type'])) {
        throw new Exception("Missing property 'type'");
      }
      if (!in_array($spec['type'], array('src', 'doc', 'bin'))) {
        throw new Exception("Unknown file type ". $spec['type']);
      }
    }
    return $this->filespec = $specs;
  }
  function addFilespec($path, $type) {
    if (!in_array($type, array('src', 'doc', 'bin'))) {
      throw new Exception("Unknown file type ". $type);
    }
    $this->filespec[] = array('path' => $path, 'type' => $type);
  }
  function setDependencies($dependencies) {
    $this->dependencies = $dependencies;
  }
  function addDependency($channel, $version = null) {
    $this->dependencies[] = array('channel' => $channel, 'version' => $version);
  }
  function addIgnore($pattern) {
    $this->file_ignore[] = $pattern;
    return $pattern;
  }
  function setIgnore($ignore) {
    $this->file_ignore = $ignore;
  }
  function addProjectMaintainer(ProjectMaintainer $project_maintainer) {
    $project_maintainer->setProjectId($this->id());
    $this->project_maintainers[] = $project_maintainer;
    return $project_maintainer;
  }
  function setProjectMaintainers($project_maintainers = array()) {
    $this->project_maintainers = array();
    foreach ($project_maintainers as $pm) {
      $this->addProjectMaintainer($pm);
    }
  }
  function unmarshal($hash) {
    $h = array();
    foreach ($hash as $k => $v) {
      $h[str_replace('-', '_', $k)] = $v;
    }
    $hash = $h;
    $fields = array(
      'name', 'owner', 'created', 'repository',
      'summary', 'href', 'license_title', 'license_href',
      'php_version');
    foreach ($fields as $field) {
      if (array_key_exists($field, $hash)) {
        $this->{"set$field"}($hash[$field]);
      }
    }
    $this->setFilespec(array());
    if (isset($hash['filespec'])) {
      foreach ($hash['filespec'] as $row) {
        $this->addFilespec($row['path'], $row['type']);
      }
    }
    $this->setIgnore(array());
    if (isset($hash['ignore'])) {
      foreach ($hash['ignore'] as $pattern) {
        $this->addIgnore($pattern);
      }
    }
    $this->setDependencies(array());
    if (isset($hash['dependencies'])) {
      foreach ($hash['dependencies'] as $row) {
          $this->addDependency(
              $row['channel'],
              isset($row['version']) ? $row['version'] : null);
      }
    }
  }
  function unmarshalMaintainers($body, $user, $maintainers) {
    $this->setProjectMaintainers(array());
    if (isset($body['maintainers'])) {
      foreach ($body['maintainers'] as $row) {
        $m = $maintainers->fetch(array('user' => $row['user']));
        if ($m) {
          if ($m->owner() == $user) {
            $m->setName($row['name']);
            $m->setEmail($row['email']);
          } elseif ($row['name'] !== $m->name() || $row['email'] !== $m->email()) {
            $this->errors['maintainers'][] = "You're not allowed to change details of " . $row['user'] . ".";
          }
        } else {
          $m = new Maintainer(
            array(
              'user' => $row['user'],
              'name' => $row['name'],
              'email' => $row['email'],
              'owner' => $user));
        }
        $this->addProjectMaintainer(new ProjectMaintainer($m, $row['type']));
      }
    }
    return empty($this->errors['maintainers']);
  }
}

class ProjectMaintainer {
  protected $project_id;
  protected $maintainer;
  protected $type;
  function __construct($maintainer, $type, $project_id = null) {
    if (!in_array($type, array('lead', 'developer', 'contributor', 'helper'))) {
      throw new Exception("Illegal value for 'type'");
    }
    $this->maintainer = $maintainer;
    $this->type = $type;
    $this->project_id = $project_id;
  }
  function setProjectId($project_id) {
    if ($this->projectId() !== null && $project_id !== $this->projectId()) {
      throw new Exception("Can't change project_id");
    }
    $this->project_id = $project_id;
  }
  function projectId() {
    return $this->project_id;
  }
  function maintainer() {
    return $this->maintainer;
  }
  function type() {
    return $this->type;
  }
}

class Maintainer extends Accessor {
  function displayName() {
    return $this->user();
  }
  function setId($id) {
    if ($this->id() !== null) {
      throw new Exception("Can't change id");
    }
    return $this->row['id'] = $id;
  }
}
