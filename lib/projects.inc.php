<?php
require_once 'pdoext.inc.php';
require_once 'pdoext/connection.inc.php';
require_once 'pdoext/query.inc.php';
require_once 'pdoext/tablegateway.php';

class Projects extends pdoext_TableGateway {
  function __construct(pdoext_Connection $db) {
    parent::__construct('projects', $db);
  }
  function load($row = array()) {
    return new Project($row);
  }
  function validate($project) {
    // TODO: Make validations here
  }
  function validate_update($project) {
    if (!$project->id()) {
      $project->errors[] = "Missing id";
    }
  }
}

class Project {
  public $errors = array();
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
  function setId($id) {
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
}
