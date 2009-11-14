<?php

class components_Account extends k_Component {
  function dispatch() {
    if ($this->identity()->anonymous()) {
      throw new k_NotAuthorized();
    }
    return parent::dispatch();
  }
  function renderHtml() {
    return
      sprintf("<p>Hello %s, anon=%s</p>", htmlspecialchars($this->identity()->user()), $this->identity()->anonymous() ? 't' : 'f') .
      sprintf("<form method='post' action='%s'><p><input type='submit' value='Log out' /></p></form>", htmlspecialchars($this->url('/logout')));
  }
}
