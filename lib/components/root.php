<?php
class components_Root extends k_Component {
  protected $templates;
  function __construct(k_TemplateFactory $templates) {
    $this->templates = $templates;
  }
  protected function map($name) {
    switch ($name) {
    case 'account':
      return 'components_Account';
    case 'projects':
      return 'components_projects_List';
    case 'login':
      return 'components_Login';
    case 'logout':
      return 'components_Logout';
    }
  }
  function execute() {
    return $this->wrap(parent::execute());
  }
  function wrapHtml($content) {
    $this->document->addStyle($this->url('/res/style.css'));
    $t = $this->templates->create("document");
    return
      $t->render(
        $this,
        array(
          'content' => $content,
          'title' => $this->document->title(),
          'scripts' => $this->document->scripts(),
          'styles' => $this->document->styles(),
          'onload' => $this->document->onload()));
  }
  function renderHtml() {
    $t = $this->templates->create("root");
    return $t->render($this);
  }
}
