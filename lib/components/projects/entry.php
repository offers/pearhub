<?php
require_once 'thirdparty/krudt/lib/krudt.inc.php';
require_once 'projects.inc.php';

class components_projects_Entry extends k_Component {
  protected $templates;
  protected $projects;
  protected $project;
  function __construct(k_TemplateFactory $templates, ProjectGateway $projects) {
    $this->templates = $templates;
    $this->projects = $projects;
  }
  function execute() {
    $this->templates->loadViewHelper(new krudt_view_ViewHelper());
    return parent::execute();
  }
  function dispatch() {
    $this->project = $this->projects->fetch(array('name' => $this->name()));
    if (!$this->project) {
      throw new k_PageNotFound();
    }
    return parent::dispatch();
  }
  function renderHtml() {
    $this->document->setTitle($this->project->name());
    $this->document->addScript($this->url('/res/form.js'));
    $t = $this->templates->create("projects/show");
    return $t->render($this, array('project' => $this->project));
  }
  function renderJson() {
    return $this->project->getArrayCopy();
  }
  function renderXml() {
    $xml = new XmlWriter();
    $xml->openMemory();
    $xml->startElement('project');
    foreach ($this->project->getArrayCopy() as $key => $value) {
      $xml->writeElement(str_replace('_', '-', $key), $value);
    }
    $xml->endElement();
    return $xml->outputMemory();
  }
  function renderHtmlEdit() {
    if ($this->identity()->anonymous()) {
      throw new k_NotAuthorized();
    }
    $this->document->addScript($this->url('/res/form.js'));
    $this->document->setTitle("Edit " . $this->project->displayName());
    $t = $this->templates->create("projects/edit");
    return $t->render($this, array('project' => $this->project));
  }
  function putForm() {
    if ($this->processUpdate()) {
      return new k_SeeOther($this->url('../' . $this->project->name()));
    }
    return $this->render();
  }
  function processUpdate() {
    if ($this->identity()->anonymous()) {
      throw new k_NotAuthorized();
    }
    $this->projects->unmarshalInto($this->body(), $this->project);
    return $this->projects->update($this->project);
  }
  function renderHtmlDelete() {
    if ($this->identity()->anonymous()) {
      throw new k_NotAuthorized();
    }
    $this->document->setTitle("Delete " . $this->project->displayName());
    $t = $this->templates->create("projects/delete");
    return $t->render($this, array('project' => $this->project));
  }
  function DELETE() {
    if ($this->identity()->anonymous()) {
      throw new k_NotAuthorized();
    }
    if ($this->projects->delete(array('id' => $this->project->id()))) {
      return new k_SeeOther($this->url('..'));
    }
    return $this->render();
  }
}
