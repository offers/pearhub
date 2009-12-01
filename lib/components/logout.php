<?php
require_once 'openid.inc.php';

class components_Logout extends k_Component {
  protected $zend_auth;
  protected $auth_cookies;
  function __construct(Zend_Auth $zend_auth, AuthenticationCookiesGateway $auth_cookies) {
    $this->zend_auth = $zend_auth;
    $this->auth_cookies = $auth_cookies;
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
    if ($this->cookie('user')) {
        $this->auth_cookies->delete(array('hash' => $this->cookie('user')));
        $this->cookie()->set('user', null);
    }
    return new k_SeeOther($this->query('continue'));
  }
}
