<?php
class components_Faq extends k_Component {
  protected $templates;
  function __construct(k_TemplateFactory $templates) {
    $this->templates = $templates;
  }
  function dispatch() {
    $this->document->addCrumb('FAQ', $this->url());
    return parent::dispatch();
  }
  function renderHtml() {
    $this->document->setTitle('FAQ');
    $t = $this->templates->create("faq");
    return $t->render($this);
  }
}
