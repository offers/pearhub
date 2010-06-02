<?php
require_once 'Zend/Auth.php';
require_once 'Zend/Auth/Adapter/OpenId.php';
require_once 'Zend/Controller/Response/Abstract.php';
require_once 'Zend/Session.php';

// ZF is a monolith
Zend_Session::$_unitTestEnabled = true;

class ZfControllerResponseAdapter extends Zend_Controller_Response_Abstract {
  public function canSendHeaders($throw = false) {
    return true;
  }
  public function sendResponse() {
    $headers = $this->_headersRaw;
    foreach ($this->_headers as $header) {
      $headers[] = $header['name'] . ': ' . $header['value'];
    }
    throw new ZfThrowableResponse(
      $this->_httpResponseCode,
      implode("", $this->_body),
      $headers);
  }
}

class ZfThrowableResponse extends Exception {
  function __construct($status, $body, $headers) {
    $this->status = $status;
    $this->body = $body;
    $this->headers = $headers;
  }
  function status() {
    return $this->status;
  }
  function body() {
    return $this->body;
  }
  function headers() {
    return $this->headers;
  }
  function getRedirect() {
    foreach ($this->headers as $header) {
      if (preg_match('/^location: (.*)$/i', $header, $reg)) {
        return $reg[1];
      }
    }
  }
}

class SessionIdentityLoader implements k_IdentityLoader {
  function load(k_Context $context) {
    if ($context->session('identity')) {
      return $context->session('identity');
    }
    return new k_Anonymous();
  }
}

class NotAuthorizedComponent extends k_Component {
  function dispatch() {
    // redirect to login-page
    return new k_TemporaryRedirect($this->url('/login', array('continue' => $this->requestUri())));
  }
}

class CookieIdentityLoader implements k_IdentityLoader {
  protected $gateway;
  protected $user_factory;
  function __construct(AuthenticationCookiesGateway $gateway, UserFactory $user_factory) {
    $this->gateway = $gateway;
    $this->user_factory = $user_factory;
  }
  function load(k_Context $context) {
    if ($context->session('identity')) {
      return $context->session('identity');
    }
    if ($context->cookie('user')) {
      $auth = $this->gateway->fetchByHash($context->cookie('user'));
      if ($auth && $auth->validateContext($context)) {
        $user = $this->user_factory->create($auth->openidIdentifier());
        $context->session()->set('identity', $user);
        return $user;
      }
    }
    return new k_Anonymous();
  }
}

class UserFactory {
  public function create($username) {
    return new k_AuthenticatedUser($username);
  }
}

class AuthenticatedUser extends k_AuthenticatedUser {
  function isAuthorized() {
    return false;
  }
}

class AuthenticationCookiesGateway extends pdoext_TableGateway {
  function __construct(pdoext_Connection $db) {
    parent::__construct('authentication_cookies', $db);
  }
  function load($row = array()) {
    return new AuthenticationCookie($row);
  }
  function fetchByHash($hash) {
    return $this->fetch(array('hash' => $hash));
  }
}

/*
openid_identifier
hash
user_agent
created
*/
class AuthenticationCookie  {
  const SALT = "35d4gb6d4g63d4v3d4vd64fvd34v34xd34v4r54d3f4vv4df35v4d34vxd648fv4d";
  protected $row;
  function __construct($row = array()) {
    $this->row = $row;
  }
  function validateContext(k_Context $context) {
    if ($this->expire() < time()) {
      return false;
    }
    return $context->header('User-Agent') == $this->userAgent();
  }
  function getArrayCopy() {
    $this->generateHash();
    return $this->row;
  }
  function openidIdentifier() {
    return $this->row['openid_identifier'];
  }
  function userAgent() {
    return $this->row['user_agent'];
  }
  function created() {
    return $this->row['created'];
  }
  function expire() {
    $t = strtotime($this->created());
    return $t + 2592000; // 30 days
  }
  function hash() {
    return $this->generateHash();
  }
  protected function generateHash() {
    $this->row['hash'] = md5(self::SALT . $this->openidIdentifier());
    return $this->row['hash'];
  }
}
