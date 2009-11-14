<?php
class components_Account extends k_Component {
  protected $templates;
  function __construct(k_TemplateFactory $templates) {
    $this->templates = $templates;
  }
  function dispatch() {
    if ($this->identity()->anonymous()) {
      throw new k_NotAuthorized();
    }
    return parent::dispatch();
  }
  function renderHtml() {
    $this->document->setTitle('Account');
    $t = $this->templates->create("account");
    return $t->render($this);
  }
}
