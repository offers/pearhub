<?php
require_once 'openid.inc.php';

class components_Login extends k_Component {
  protected $templates;
  protected $zend_auth;
  protected $auth_cookies;
  protected $errors;
  protected $user_factory;
  function __construct(k_TemplateFactory $templates, Zend_Auth $zend_auth, AuthenticationCookiesGateway $auth_cookies, UserFactory $user_factory) {
    $this->templates = $templates;
    $this->zend_auth = $zend_auth;
    $this->auth_cookies = $auth_cookies;
    $this->errors = array();
    $this->user_factory = $user_factory;
  }
  function execute() {
    $this->url_state->init("continue", $this->url('/'));
    return parent::execute();
  }
  function GET() {
    if ($this->query('openid_mode')) {
      $result = $this->authenticate();
      if ($result instanceOf k_Response) {
        return $result;
      }
    }
    return parent::GET();
  }
  function postForm() {
    $result = $this->authenticate();
    if ($result instanceOf k_Response) {
        return $result;
    }
    return $this->render();
  }
  function renderHtml() {
    $this->document->setTitle('Authentication required');
    $t = $this->templates->create("login");
    $response = new k_HtmlResponse(
      $t->render(
        $this,
        array(
          'errors' => $this->errors)));
    $response->setStatus(401);
    return $response;
  }
  protected function authenticate() {
    $open_id_adapter = new Zend_Auth_Adapter_OpenId($this->body('openid_identifier'));
    $open_id_adapter->setReturnTo($this->url('', array('remember' => $this->body('remember'))));
    $open_id_adapter->setResponse(new ZfControllerResponseAdapter());
    try {
      $result = $this->zend_auth->authenticate($open_id_adapter);
    } catch (ZfThrowableResponse $response) {
      return new k_SeeOther($response->getRedirect());
    }
    $this->errors = array();
    if ($result->isValid()) {
      $user = $this->selectUser($this->zend_auth->getIdentity());
      if ($user) {
        $this->session()->set('identity', $user);
        if ($this->query('remember')) {
            $auth = new AuthenticationCookie(
                array(
                    'openid_identifier' => $user->user(),
                    'user_agent' => $this->header('User-Agent'),
                    'created' => date('Y-m-d H:i:s')));
            $this->auth_cookies->delete(array('openid_identifier' => $user->user()));
            $this->auth_cookies->insert($auth);
            $this->cookie()->set('user', $auth->hash(), $auth->expire());
        }
        return new k_SeeOther($this->query('continue'));
      }
      $this->errors[] = "Auth OK, but no such user on this system.";
    }
    $this->session()->set('identity', null);
    if ($this->cookie('user')) {
        $this->auth_cookies->delete(array('hash' => $this->cookie('user')));
        $this->cookie()->set('user', null);
    }
    $this->zend_auth->clearIdentity();
    foreach ($result->getMessages() as $message) {
      $this->errors[] = $message;
    }
  }
  protected function selectUser($openid_identity) {
    return $this->user_factory->create($openid_identity);
  }
}
