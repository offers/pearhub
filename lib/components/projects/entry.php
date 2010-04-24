<?php
require_once 'projects.inc.php';
require_once 'repo.inc.php';

class components_projects_Entry extends k_Component {
  protected $templates;
  protected $projects;
  protected $maintainers;
  protected $db;
  protected $repo_probe;
  protected $project;
  function __construct(k_TemplateFactory $templates, ProjectGateway $projects, MaintainerGateway $maintainers, PDO $db, RepoProbe $repo_probe) {
    $this->templates = $templates;
    $this->projects = $projects;
    $this->maintainers = $maintainers;
    $this->db = $db;
    $this->repo_probe = $repo_probe;
  }
  function map($name) {
    if ($name === 'releases') {
      return 'components_Releases';
    }
    return parent::map($name);
  }
  function forward($class_name, $namespace = "") {
    $this->document->addCrumb($this->project->name(), $this->url());
    return parent::forward($class_name, $namespace);
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
    $this->document->addCrumb($this->project->name(), $this->url());
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
    if (!$this->canEdit()) {
      throw new k_Forbidden();
    }
    $this->document->addScript($this->url('/res/form.js'));
    $this->document->setTitle("Edit " . $this->project->displayName());
    $this->document->addCrumb($this->project->displayName(), $this->url());
    $this->document->addCrumb("edit", $this->url('', array('edit')));
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
    if (!$this->canEdit()) {
      throw new k_Forbidden();
    }
    $body = $this->body();

    foreach ($this->project->projectMaintainers() as $old_maintainer) {
      if ($old_maintainer->maintainer()->owner() != $this->identity()->user()) {
        $uniqid = uniqid();
        $body['maintainers'][$uniqid]['user'] = $old_maintainer->maintainer()->user();
        $body['maintainers'][$uniqid]['name'] = $old_maintainer->maintainer()->name();
        $body['maintainers'][$uniqid]['email'] = $old_maintainer->maintainer()->email();
        $body['maintainers'][$uniqid]['type'] = $old_maintainer->type();
      }
    }
    $this->project->unmarshal($body);
    if (!$this->project->unmarshalMaintainers($body, $this->identity()->user(), $this->maintainers)) {
      return false;
    }
    /*
    try {
      $this->repo_probe->getRepositoryTypeAndCache($this->project->repositoryLocation(), true);
    } catch (Exception $ex) {
      $this->project->errors['repository'] = "Unable to detect repository. Please check that the URL is valid.";
      return false;
    }
    */
    $this->db->beginTransaction();
    try {
      if (!$this->projects->update($this->project)) {
        $this->db->rollback();
        return false;
      }
      foreach ($this->project->projectMaintainers() as $m) {
        $this->maintainers->delete(array('user' => $m->maintainer()->user()));
        $this->maintainers->insert($m->maintainer());
      }
      $this->db->commit();
    } catch (Exception $ex) {
      $this->db->rollback();
      $this->debug($ex);
      $this->project->errors[] = $ex->getMessage();
      return false;
    }
    return true;
  }
  function renderHtmlDelete() {
    if ($this->identity()->anonymous()) {
      throw new k_NotAuthorized();
    }
    if (!$this->canDelete()) {
      throw new k_Forbidden();
    }
    $this->document->setTitle("Delete " . $this->project->displayName());
    $this->document->addCrumb($this->project->displayName(), $this->url());
    $this->document->addCrumb("delete", $this->url('', array('delete')));
    $t = $this->templates->create("projects/delete");
    return $t->render($this, array('project' => $this->project));
  }
  function DELETE() {
    if ($this->identity()->anonymous()) {
      throw new k_NotAuthorized();
    }
    if (!$this->canDelete()) {
      throw new k_Forbidden();
    }
    if ($this->projects->delete(array('id' => $this->project->id()))) {
      return new k_SeeOther($this->url('..'));
    }
    return $this->render();
  }
  function getProject() {
    return $this->project;
  }
  function canEdit() {
    if ($this->identity()->anonymous()) {
      return false;
    }
    return $this->project->owner() == $this->identity()->user();
  }
  function canDelete() {
    if ($this->identity()->anonymous()) {
      return false;
    }
    if ($this->project->owner() != $this->identity()->user()) {
      return false;
    }
    // Allow deleting a project for up to 24h
    return strtotime($this->project->created()) + 86400 > time();
  }
}
