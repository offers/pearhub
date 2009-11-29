<?php
require_once 'thirdparty/krudt/lib/krudt.inc.php';
require_once 'projects.inc.php';

class components_projects_List extends k_Component {
  protected $templates;
  protected $projects;
  protected $project;
  protected $url_init = array('sort' => 'id', 'direction' => 'asc', 'page' => 1);
  function __construct(k_TemplateFactory $templates, ProjectGateway $projects) {
    $this->templates = $templates;
    $this->projects = $projects;
  }
  function execute() {
    $this->templates->loadViewHelper(new krudt_view_ViewHelper());
    return parent::execute();
  }
  function map($name) {
    return 'components_projects_Entry';
  }
  function renderHtml() {
    $this->document->setTitle("Projects");
    $t = $this->templates->create('projects/list');
    return $t->render(
      $this,
      array(
        'projects' => $this->projects));
  }
  function wrapHtml($content) {
    $t = $this->templates->create('projects/wrapper');
    return $t->render(
      $this,
      array(
        'projects' => $this->projects,
        'content' => $content));
  }
  function renderHtmlNew() {
    $this->document->addScript($this->url('/res/form.js'));
    if ($this->identity()->anonymous()) {
      throw new k_NotAuthorized();
    }
    if (!$this->project) {
      $this->project = new Project();
    }
    $this->document->setTitle("New project");
    $t = $this->templates->create('projects/new');
    return $this->wrapHtml($t->render($this, array('project' => $this->project)));
  }
  function renderJson() {
    return null;
  }
  function renderJsonMaintainers() {
    if ($this->identity()->anonymous()) {
      throw new k_NotAuthorized();
    }
    // TODO: pull from db, from $this->query('q');
    return array(
      array('user' => 'troelskn', 'name' => 'Troels Knak-Nielsen', 'email' => 'troelskn@gmail.com'),
      array('user' => 'anders.ekdahl', 'name' => 'Anders Ekdahl', 'email' => null),
    );
  }
  function postForm() {
    if ($this->processNew()) {
      return new k_SeeOther($this->url($this->project->name()));
    }
    return $this->render();
  }
  function processNew() {
    if ($this->identity()->anonymous()) {
      throw new k_NotAuthorized();
    }
    $this->project = new Project(
      array(
        'name' => $this->body('name'),
        'owner' => $this->identity()->user(),
        'created' => date('Y-m-d H:i:s'),
        'repository' => $this->body('repository')));
    $filespec = json_decode($this->body('filespec'), true);
    if (!$filespec) {
      $this->project->errors['filespec'][] = 'Syntax error';
      return false;
    }
    $this->project->setFilespec($filespec);

    $ignore = json_decode($this->body('ignore'), true);
    if (!$ignore) {
      $this->project->errors['ignore'][] = 'Syntax error';
      return false;
    }
    $this->project->setIgnore($ignore);

    return $this->projects->insert($this->project);
  }
}
