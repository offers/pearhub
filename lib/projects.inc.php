<?php
require_once 'pdoext.inc.php';
require_once 'pdoext/connection.inc.php';
require_once 'pdoext/query.inc.php';
require_once 'pdoext/tablegateway.php';

class ProjectGateway extends pdoext_TableGateway {
  protected $sql_load_aggregates;
  protected $maintainers;
  function __construct(pdoext_Connection $db, MaintainersGateway $maintainers) {
    parent::__construct('projects', $db);
    $this->maintainers = $maintainers;
  }
  function load($row = array()) {
    $m = new Project($row);
    if ($row['id']) {
      return $this->loadAggerates($m);
    }
    return $m;
  }
  function loadAggerates($project) {
    if (!$this->sql_load_aggregates) {
      $this->sql_load_aggregates = $this->db->prepare(
        '
SELECT
  projects.id as project_id,
  maintainers.id as maintainer_id,
  maintainers.type,
  maintainers.user,
  maintainers.name,
  maintainers.email,
  filespecs.type as filespec_type,
  filespecs.path as filespec_path,
  file_ignores.pattern
FROM
  projects
LEFT JOIN
  maintainers
ON projects.id = maintainers.project_id
LEFT JOIN
  filespecs
ON projects.id = filespecs.project_id
LEFT JOIN
  file_ignores
ON projects.id = file_ignores.project_id
WHERE
  projects.id = :id
'
      );
      $this->sql_load_aggregates->setFetchMode(PDO::FETCH_ASSOC);
    }
    $this->sql_load_aggregates->execute(array('id' => $project->id()));
    foreach ($this->sql_load_aggregates as $row) {
      if ($row['maintainer_id']) {
        $row['id'] = $row['maintainer_id'];
        $project->addMaintainer($this->maintainers->load($row));
      } elseif ($row['filespec_path']) {
        $project->addFilespec($row['filespec_path'], $row['filespec_type']);
      } elseif ($row['pattern']) {
        $project->addIgnore($row['pattern']);
      }
    }
    return $project;
  }
  function insertAggregates($project) {
    foreach ($project->maintainers() as $maintainer) {
      $this->maintainers->insert($maintainer);
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
  }
  function updateAggregates($project) {
    $this->db->prepare(
      'delete from maintainers where project_id = :project_id'
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
    $this->insertAggregates($project);
  }
  function validate_update($project) {
    if (!$project->id()) {
      $project->errors['id'] = "Missing id";
    }
  }
  function validate($project) {
    if (!$project->name()) {
      $project->errors['name'] = "Missing name";
    }
    if (!$project->repository()) {
      $project->errors['repository'] = "Missing repository";
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
    return new Maintainers($row);
  }
}

class Project {
  public $errors = array();
  protected $row = array();
  protected $filespec = array();
  protected $file_ignore = array();
  protected $maintainers = array();
  function __construct($row = array('php_version' => '5.0.0')) {
    $this->row = $row;
  }
  function getArrayCopy() {
    return $this->row;
  }
  function displayName() {
    return $this->name();
  }
  function id() {
    return isset($this->row['id']) ? $this->row['id'] : null;
  }
  function name() {
    return isset($this->row['name']) ? $this->row['name'] : null;
  }
  function owner() {
    return isset($this->row['owner']) ? $this->row['owner'] : null;
  }
  function created() {
    return isset($this->row['created']) ? $this->row['created'] : null;
  }
  function repository() {
    return isset($this->row['repository']) ? $this->row['repository'] : null;
  }
  function summary() {
    return isset($this->row['summary']) ? $this->row['summary'] : null;
  }
  function licenseTitle() {
    return isset($this->row['license_title']) ? $this->row['license_title'] : null;
  }
  function licenseHref() {
    return isset($this->row['license_href']) ? $this->row['license_href'] : null;
  }
  function phpVersion() {
    return isset($this->row['php_version']) ? $this->row['php_version'] : null;
  }
  function filespec() {
    return $this->filespec;
  }
  function ignore() {
    return $this->file_ignore;
  }
  function maintainers() {
    return $this->maintainers;
  }
  function setId($id) {
    if ($this->id() !== null) {
      throw new Exception("Can't change id");
    }
    foreach ($this->maintainers as $maintainer) {
      $maintainer->setProjectId($id);
    }
    return $this->row['id'] = $id;
  }
  function setName($name) {
    return $this->row['name'] = $name;
  }
  function setOwner($owner) {
    return $this->row['owner'] = $owner;
  }
  function setCreated($created) {
    return $this->row['created'] = $created;
  }
  function setRepository($url) {
    return $this->row['repository'] = $url;
  }
  function setSummary($summary) {
    return $this->row['summary'] = $summary;
  }
  function setLicenseTitle($license_title) {
    return $this->row['license_title'] = $license_title;
  }
  function setLicenseHref($license_href) {
    return $this->row['license_href'] = $license_href;
  }
  function setPhpVersion($php_version) {
    return $this->row['php_version'] = $php_version;
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
  function addIgnore($pattern) {
    $this->file_ignore[] = $pattern;
    return $pattern;
  }
  function setIgnore($ignore) {
    $this->file_ignore = $ignore;
  }
  function addMaintainer(Maintainer $maintainer) {
    $maintainer->setProjectId($this->id());
    $this->maintainers[] = $maintainer;
    return $maintainer;
  }
}

class Maintainer {
  public $errors = array();
  protected $row = array();
  function __construct($row = array()) {
    $this->row = $row;
  }
  function getArrayCopy() {
    return $this->row;
  }
  function displayName() {
    return $this->user();
  }
  function id() {
    return isset($this->row['id']) ? $this->row['id'] : null;
  }
  function projectId() {
    return isset($this->row['project_id']) ? $this->row['project_id'] : null;
  }
  function type() {
    return isset($this->row['type']) ? $this->row['type'] : null;
  }
  function user() {
    return isset($this->row['user']) ? $this->row['user'] : null;
  }
  function name() {
    return isset($this->row['name']) ? $this->row['name'] : null;
  }
  function email() {
    return isset($this->row['email']) ? $this->row['email'] : null;
  }
  function setId($id) {
    if ($this->id() !== null) {
      throw new Exception("Can't change id");
    }
    return $this->row['id'] = $id;
  }
  function setProjectId($id) {
    return $this->row['project_id'] = $id;
  }
  function setType($type) {
    if (!in_array($type, array('lead', 'helper'))) {
      throw new Exception("Illegal value for 'type'");
    }
    return $this->row['type'] = $type;
  }
  function setUser($user) {
    return $this->row['user'] = $user;
  }
  function setName($name) {
    return $this->row['name'] = $name;
  }
  function setEmail($email) {
    return $this->row['email'] = $email;
  }
  function toStruct() {
    return array(
      'type' => $this->type(),
      'user' => $this->user(),
      'name' => $this->name(),
      'email' => $this->email());
  }
}
