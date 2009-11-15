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
    $this->document->setTitle("Edit " . $this->project->display_name());
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
    $this->project = new Project(
      array(
        'id' => $this->project->id(),
        'name' => $this->body('name'),
        'owner' => $this->body('owner'),
        'created' => $this->body('created'),
        'repository' => $this->body('repository')));
    return $this->projects->update($this->project);
  }
  function renderHtmlDelete() {
    $this->document->setTitle("Delete " . $this->project->display_name());
    $t = $this->templates->create("projects/delete");
    return $t->render($this, array('project' => $this->project));
  }
  function DELETE() {
    if ($this->projects->delete($this->project)) {
      return new k_SeeOther($this->url('..'));
    }
    return $this->render();
  }
}
