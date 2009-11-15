<?php
require_once 'thirdparty/krudt/lib/krudt.inc.php';
require_once 'contacts.inc.php';

class components_contacts_Entry extends k_Component {
  protected $templates;
  protected $contacts;
  protected $contact;
  function __construct(k_TemplateFactory $templates, Contacts $contacts) {
    $this->templates = $templates;
    $this->contacts = $contacts;
  }
  function execute() {
    $this->templates->loadViewHelper(new krudt_view_ViewHelper());
    return parent::execute();
  }
  function dispatch() {
    $this->contact = $this->contacts->fetch(array('slug' => $this->name()));
    if (!$this->contact) {
      throw new k_PageNotFound();
    }
    return parent::dispatch();
  }
  function renderHtml() {
    $this->document->setTitle($this->contact->slug());
    $t = $this->templates->create("contacts/show");
    return $t->render($this, array('contact' => $this->contact));
  }
  function renderJson() {
    return $this->contact->getArrayCopy();
  }
  function renderXml() {
    $xml = new XmlWriter();
    $xml->openMemory();
    $xml->startElement('contact');
    foreach ($this->contact->getArrayCopy() as $key => $value) {
      $xml->writeElement(str_replace('_', '-', $key), $value);
    }
    $xml->endElement();
    return $xml->outputMemory();
  }
  function renderHtmlEdit() {
    $this->document->setTitle("Edit " . $this->contact->display_name());
    $t = $this->templates->create("contacts/edit");
    return $t->render($this, array('contact' => $this->contact));
  }
  function putForm() {
    if ($this->processUpdate()) {
      return new k_SeeOther($this->url('../' . $this->contact->slug()));
    }
    return $this->render();
  }
  function processUpdate() {
    $this->contact = new Contact(
      array(
        'id' => $this->contact->id(),
        'slug' => $this->body('slug')));
    return $this->contacts->update($this->contact);
  }
  function renderHtmlDelete() {
    $this->document->setTitle("Delete " . $this->contact->display_name());
    $t = $this->templates->create("contacts/delete");
    return $t->render($this, array('contact' => $this->contact));
  }
  function DELETE() {
    if ($this->contacts->delete($this->contact)) {
      return new k_SeeOther($this->url('..'));
    }
    return $this->render();
  }
}
