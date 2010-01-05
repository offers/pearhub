<?php
class components_Root extends k_Component {
  protected $templates;
  function __construct(k_TemplateFactory $templates) {
    $this->templates = $templates;
  }
  protected function map($name) {
    switch ($name) {
    case 'projects':
      return 'components_projects_List';
    case 'login':
      return 'components_Login';
    case 'logout':
      return 'components_Logout';
    case 'faq':
      return 'components_Faq';
    }
  }
  function dispatch() {
    $this->document->addCrumb('pearhub.org', $this->url());
    return parent::dispatch();
  }
  function execute() {
    return $this->wrap(parent::execute());
  }
  function wrapHtml($content) {
    $this->document->addStyle($this->url('/res/reset.css'));
    $this->document->addStyle($this->url('/res/style.css'));
    $t = $this->templates->create("document");
    return
      $t->render(
        $this,
        array(
          'content' => $content,
          'title' => $this->document->title(),
          'crumbtrail' => $this->document->crumbtrail(),
          'scripts' => $this->document->scripts(),
          'styles' => $this->document->styles(),
          'onload' => $this->document->onload()));
  }
  function renderHtml() {
    $this->document->setTitle('');
    $t = $this->templates->create("root");
    return $t->render($this);
  }
}
