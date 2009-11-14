<?php
require_once 'openid.inc.php';

class components_Logout extends k_Component {
  protected $zend_auth;
  function __construct() {
    $this->zend_auth = Zend_Auth::getInstance();
  }
  function execute() {
    $this->url_state->init("continue", $this->url('/'));
    return parent::execute();
  }
  function postForm() {
    if ($this->zend_auth->hasIdentity()) {
      $this->zend_auth->clearIdentity();
    }
    $this->session()->set('identity', null);
    throw new k_SeeOther($this->query('continue'));
  }
}
