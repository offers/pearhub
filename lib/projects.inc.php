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
    $m = new Projects($row);
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
  maintainers.email
FROM
  projects
LEFT JOIN
  maintainers
ON projects.id = maintainers.project_id
WHERE
  projects.id = :id
'
      );
      $this->sql_load_aggregates->setFetchMode(PDO::FETCH_ASSOC);
    }
    foreach ($this->sql_load_aggregates->execute(array('id' => $project->id)) as $row) {
      if ($row['maintainer_id']) {
        $row['id'] = $row['maintainer_id'];
        $project->addMaintainer($this->maintainers->load($row));
      }
    }
    return $project;
  }
  function validate_update($project) {
    if (!$project->id()) {
      $project->errors[] = "Missing id";
    }
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
  protected $file_source = array();
  protected $file_documentation = array();
  protected $file_bin = array();
  protected $file_ignore = array();
  protected $maintainers = array();
  function __construct($row = array('id' => null, 'name' => null, 'owner' => null, 'created' => null, 'repository' => null)) {
    $this->row = $row;
  }
  function getArrayCopy() {
    return $this->row;
  }
  function displayName() {
    return $this->name();
  }
  function id() {
    return $this->row['id'];
  }
  function name() {
    return $this->row['name'];
  }
  function owner() {
    return $this->row['owner'];
  }
  function created() {
    return $this->row['created'];
  }
  function repository() {
    return $this->row['repository'];
  }
  function summary() {
    return $this->row['summary'];
  }
  function licenseTitle() {
    return $this->row['license_title'];
  }
  function licenseHref() {
    return $this->row['license_href'];
  }
  function phpVersion() {
    return $this->row['php_version'];
  }
  function sourceFiles() {
    return $this->file_source;
  }
  function documentationFiles() {
    return $this->file_documentation;
  }
  function binFiles() {
    return $this->file_bin;
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
    $this->file_source[] = $file;
    return $file;
  }
  function addDocumentationFile($file) {
    $this->file_documentation[] = $file;
    return $file;
  }
  function addBinFile($file) {
    $this->file_bin[] = $file;
    return $file;
  }
  function addIgnore($file) {
    $this->file_ignore[] = $file;
    return $file;
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
  function __construct($row = array('id' => null, 'project_id' => null, 'type' => null, 'user' => null, 'name' => null, 'email' => null)) {
    $this->row = $row;
  }
  function getArrayCopy() {
    return $this->row;
  }
  function displayName() {
    return $this->user();
  }
  function projectId() {
    return $this->row['project_id'];
  }
  function type() {
    return $this->row['type'];
  }
  function user() {
    return $this->row['user'];
  }
  function name() {
    return $this->row['name'];
  }
  function email() {
    return $this->row['email'];
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
}
