<?php
require_once 'pdoext.inc.php';
require_once 'pdoext/connection.inc.php';
require_once 'pdoext/query.inc.php';
require_once 'pdoext/tablegateway.php';

class Contacts extends pdoext_TableGateway {
  function __construct(pdoext_Connection $db) {
    parent::__construct('contacts', $db);
  }
  function load($row = array()) {
    return new Contact($row);
  }
  function validate($contact) {
    // TODO: Make validations here
  }
  function validate_update($contact) {
    if (!$contact->id()) {
      $contact->errors[] = "Missing id";
    }
  }
}

class Contact {
  public $errors = array();
  function __construct($row = array('id' => null, 'slug' => null)) {
    $this->row = $row;
  }
  function getArrayCopy() {
    return $this->row;
  }
  function display_name() {
    return "Contact#" . $this->id();
  }
  function id() {
    return $this->row['id'];
  }
  function slug() {
    return $this->row['slug'];
  }
}
