<?php
class components_Login extends k_Component {
  protected $zend_auth;
  protected $errors;
  function __construct(Zend_Auth $zend_auth) {
    $this->zend_auth = $zend_auth;
    $this->errors = array();
  }
  function execute() {
    $this->url_state->init("continue", $this->url('/account'));
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
    $response = new k_HtmlResponse(
      "<html><head><title>Authentication required</title></head><body><form method='post' action='" . htmlspecialchars($this->url()) . "'>
  <h1>Authentication required</h1>
  <h2>OpenID Login</h2>
  <p>
" . implode("<br/>", array_map('htmlspecialchars', $this->errors)) . "
  </p>
  <p>
    <label>
      open-id url:
      <input type='text' name='openid_identifier' value='' />
    </label>
  </p>
  <p>
    <input type='submit' value='Login' />
  </p>
</form></body></html>");
    $response->setStatus(401);
    return $response;
  }
  protected function authenticate() {
    $open_id_adapter = new Zend_Auth_Adapter_OpenId($this->body('openid_identifier'));
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
        return new k_SeeOther($this->query('continue'));
      }
      $this->errors[] = "Auth OK, but no such user on this system.";
    }
    $this->session()->set('identity', null);
    $this->zend_auth->clearIdentity();
    foreach ($result->getMessages() as $message) {
      $this->errors[] = $message;
    }
  }
  protected function selectUser($openid_identity) {
    return new k_AuthenticatedUser($openid_identity);
  }
}
