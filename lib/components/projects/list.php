<?php
require_once 'thirdparty/krudt/lib/krudt.inc.php';
require_once 'projects.inc.php';

class components_projects_List extends k_Component {
  protected $templates;
  protected $projects;
  protected $maintainers;
  protected $db;
  protected $project;
  protected $url_init = array('sort' => 'id', 'direction' => 'asc', 'page' => 1);
  function __construct(k_TemplateFactory $templates, ProjectGateway $projects, MaintainersGateway $maintainers, PDO $db) {
    $this->templates = $templates;
    $this->projects = $projects;
    $this->maintainers = $maintainers;
    $this->db = $db;
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
    if ($this->identity()->anonymous()) {
      throw new k_NotAuthorized();
    }
    if (!$this->project) {
      $this->project = new Project();
    }
    $this->document->addScript($this->url('/res/form.js'));
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
    $q = pdoext_query('maintainers');
    $q->addColumn('user');
    $q->addColumn('name');
    $q->addColumn('email');
    $q->addCriterion('user', $this->query('q') . '%', 'like');
    $q->setLimit(10);
    $this->debug($q->toSql(new pdoext_DummyConnection()));
    $result = array();
    foreach ($this->db->query($q) as $row) {
        $result[] = $row;
    }
    return $result;
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
    $this->project = new Project();
    $this->projects->unmarshalInto($this->body(), $this->project);
    $this->project->setOwner($this->identity()->user());

    foreach ($this->body('maintainers') as $row) {
      $m = $this->maintainers->fetch(array('user' => $row['user']));
      if ($m) {
        if ($m->owner() == $this->identity()->user()) {
          $m->setName($row['name']);
          $m->setEmail($row['email']);
        }
      } else {
        $m = new Maintainer(
          array(
            'user' => $row['user'],
            'name' => $row['name'],
            'email' => $row['email'],
            'owner' => $this->identity()->user()));
      }
      $this->project->addProjectMaintainer(new ProjectMaintainer($m, $row['type']));
    }

    $this->db->beginTransaction();
    try {
      $this->projects->insert($this->project);
      foreach ($this->project->projectMaintainers() as $m) {
        $this->maintainers->delete(array('user' => $m->maintainer()->user()));
        $this->maintainers->insert($m->maintainer());
      }
      $this->db->commit();
    } catch (Exception $ex) {
      $this->db->rollback();
      $this->project->errors[] = $ex->getMessage();
      return false;
    }
    return true;
  }
}
