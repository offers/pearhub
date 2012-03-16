<?php
require_once 'projects.inc.php';
require_once 'repo.inc.php';

class components_projects_List extends k_Component {
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
    return 'components_projects_Entry';
  }
  function renderHtml() {
    $this->document->setTitle("Projects");
    $this->document->addCrumb('projects', $this->url());
    $t = $this->templates->create('projects/list');
    $selection = $this->projects->selectPaginated($this->query('page'));
    if ($this->query('q')) {
      $selection->setConjunctionOr();
      $selection->addCriterion('name', '%' . $this->query('q') . '%', 'like');
      $selection->addCriterion('summary', '%' . $this->query('q') . '%', 'like');
      $selection->addCriterion('description', '%' . $this->query('q') . '%', 'like');
    } elseif ($this->query('author')) {
        $selection->addCriterion('owner', urldecode($this->query('author')), '=');
    }
    return $t->render(
      $this,
      array(
        'projects' => $selection));
  }
  function forward($class_name, $namespace = "") {
    $this->document->addCrumb('projects', $this->url());
    return parent::forward($class_name, $namespace);
  }
  function renderHtmlNew() {
    if ($this->identity()->anonymous()) {
      throw new k_NotAuthorized();
    }
    if (!$this->canCreate()) {
      throw new k_Forbidden();
    }
    if (!$this->project) {
      $this->project = new Project();
    }
    $this->document->addScript($this->url('/res/form.js'));
    $this->document->setTitle("New project");
    $this->document->addCrumb('projects', $this->url());
    $this->document->addCrumb('new', $this->url('', array('new')));
    $t = $this->templates->create('projects/new');
    return $t->render($this, array('project' => $this->project));
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
    $q->addColumn('owner');
    $q->addCriterion('user', $this->query('q') . '%', 'like');
    $q->setLimit(10);
    $result = array();
    foreach ($this->db->query($q) as $row) {
      $row['is_locked'] = $row['owner'] !== $this->identity()->user();
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
    if (!$this->canCreate()) {
      throw new k_Forbidden();
    }
    $this->project = new Project();
    $this->project->unmarshal($this->body());
    $this->project->unmarshalMaintainers($this->body(), $this->identity()->user(), $this->maintainers);
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
      $this->project->setOwner($this->identity()->user());
      if (!$this->projects->insert($this->project)) {
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
      $this->project->errors[] = $ex->getMessage();
      return false;
    }
    if (!empty($GLOBALS['EMAIL_NOTIFY'])) {
      mail(
        $GLOBALS['EMAIL_NOTIFY'],
        "[pearhub] New project registered",
        var_export($this->body(), true));
    }
    return true;
  }
  function canCreate() {
    if ($this->identity()->isAuthorized() === false)  {
      return false;
    }
    return true;
  }
}
