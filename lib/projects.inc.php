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
  null as path,
  null as destination,
  null as `ignore`,
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
  path,
  destination,
  `ignore`,
  null,
  null
FROM
  files
WHERE
  files.project_id = :id2

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
  dependencies.project_id = :id3
'
      );
      $this->sql_load_aggregates->setFetchMode(PDO::FETCH_ASSOC);
    }
    $this->sql_load_aggregates->execute(
      array(
        'id1' => $project->id(),
        'id2' => $project->id(),
        'id3' => $project->id()));
    foreach ($this->sql_load_aggregates as $row) {
      if ($row['name']) {
        $project->addProjectMaintainer(new ProjectMaintainer($this->maintainers->load($row), $row['type'], $project->id()));
      } elseif ($row['path']) {
        $project->addFile($row['path'], $row['destination'], $row['ignore']);
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
    $insert_files = $this->db->prepare(
      'insert into files (project_id, path, destination, `ignore`) values (:project_id, :path, :destination, :ignore)');
    foreach ($project->files() as $file) {
      $insert_files->execute(
        array(
          ':project_id' => $project->id(),
          ':path' => $file['path'],
          ':destination' => $file['destination'],
          ':ignore' => $file['ignore']
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
      'delete from files where project_id = :project_id'
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
    if (!in_array($project->releasePolicy(), array('manual', 'auto'))) {
      $project->errors[] = "You must select a valid release policy";
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
    if (count($project->files()) === 0) {
      $project->errors['files'][] = "You must enter at least one file";
    }
    $paths = array();
    foreach ($project->files() as $file) {
      if (!trim($file['path'])) {
        $project->errors['files'][] = "File path is missing";
      } elseif (!trim($file['destination'])) {
        $project->errors['files'][] = "File destination is missing";
      }
      $paths[] = $file['path'];
    }
    if (count(array_unique($paths)) < count($paths)) {
      $project->errors['files'][] = "Each path can only be entered once";
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
  protected $files = array();
  protected $dependencies = array();
  protected $project_maintainers = array();
  function __construct($row = array('php_version' => '5.0.0')) {
    parent::__construct($row);
  }
  function displayName() {
    return $this->name();
  }
  function files() {
    return $this->files;
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
  function addFile($path, $destination, $ignore = null) {
    $this->files[] = array('path' => $path, 'destination' => $destination, 'ignore' => $ignore);
    return $path;
  }
  function setFiles($files) {
    foreach ($files as $file) {
      if (!isset($file['path'])) {
        throw new Exception("Missing property 'path'");
      }
      if (!isset($file['destination'])) {
        throw new Exception("Missing property 'destination'");
      }
    }
    return $this->files = $files;
  }
  function setDependencies($dependencies) {
    $this->dependencies = $dependencies;
  }
  function addDependency($channel, $version = null) {
    $this->dependencies[] = array('channel' => $channel, 'version' => $version);
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
      'php_version', 'release_policy');
    foreach ($fields as $field) {
      if (array_key_exists($field, $hash)) {
        $this->{"set$field"}($hash[$field]);
      }
    }
    $this->setFiles(array());
    if (isset($hash['files'])) {
      foreach ($hash['files'] as $row) {
        $this->addFile(
          $row['path'],
          isset($row['destination']) ? $row['destination'] : '/',
          isset($row['ignore']) ? $row['ignore'] : null);
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
