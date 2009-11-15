<?php
require_once 'thirdparty/krudt/lib/krudt.inc.php';
require_once 'contacts.inc.php';

class components_contacts_List extends k_Component {
  protected $templates;
  protected $contacts;
  protected $contact;
  protected $url_init = array('sort' => 'id', 'direction' => 'asc', 'page' => 1);
  function __construct(k_TemplateFactory $templates, Contacts $contacts) {
    $this->templates = $templates;
    $this->contacts = $contacts;
  }
  function execute() {
    $this->templates->loadViewHelper(new krudt_view_ViewHelper());
    return parent::execute();
  }
  function map($name) {
    return 'components_contacts_Entry';
  }
  function renderHtml() {
    $this->document->setTitle("Contacts");
    $t = $this->templates->create('contacts/list');
    return $t->render(
      $this,
      array(
        'contacts' => $this->contacts));
  }
  function wrapHtml($content) {
    $t = $this->templates->create('contacts/wrapper');
    return $t->render(
      $this,
      array(
        'contacts' => $this->contacts,
        'content' => $content));
  }
  function renderHtmlNew() {
    if (!$this->contact) {
      $this->contact = new Contact();
    }
    $this->document->setTitle("New contact");
    $t = $this->templates->create('contacts/new');
    return $this->wrapHtml($t->render($this, array('contact' => $this->contact)));
  }
  function postForm() {
    if ($this->processNew()) {
      return new k_SeeOther($this->url($this->contact->slug()));
    }
    return $this->render();
  }
  function processNew() {
    $this->contact = new Contact(
      array(
        'slug' => $this->body('slug')));
    return $this->contacts->insert($this->contact);
  }
}
