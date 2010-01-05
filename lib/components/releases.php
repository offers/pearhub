<?php
require_once 'projects.inc.php';

class components_Releases extends k_Component {
  protected $templates;
  protected $releases;
  protected $db;
  protected $error = null;
  function __construct(k_TemplateFactory $templates, ReleaseGateway $releases, PDO $db) {
    $this->templates = $templates;
    $this->releases = $releases;
    $this->db = $db;
  }
  function renderHtml() {
    $project = $this->context->getProject();
    $selection = $this->releases->selectByProject($project);
    $this->document->setTitle('Releases for ' . $project->displayName());
    $this->document->addCrumb('releases', $this->url());
    $t = $this->templates->create("releases/list");
    return $t->render(
      $this,
      array(
        'project' => $project,
        'releases' => $selection));
  }
  function renderHtmlCreate() {
    if ($this->identity()->anonymous()) {
      throw new k_NotAuthorized();
    }
    $project = $this->context->getProject();
    if (!$this->canCreate()) {
      throw new k_Forbidden();
    }
    if ($this->body('version')) {
      $version = $this->body('version');
    } else {
      $last_release = $this->releases->lastReleaseFor($project);
      if ($last_release) {
        list($major, $minor, $patch) = explode('.', $last_release->version());
        $version = $major . '.' . $minor . '.' . ($patch + 1);
      } else {
        $version = '0.0.1';
      }
    }
    $this->document->setTitle("Create release for " . $project->displayName());
    $this->document->addCrumb('releases', $this->url());
    $this->document->addCrumb("create", $this->url());
    $t = $this->templates->create("releases/create");
    return $t->render(
      $this,
      array(
        'project' => $project,
        'version' => $version,
        'error' => $this->error));
  }
  function postForm() {
    if (!$this->canCreate()) {
      throw new k_Forbidden();
    }
    if ($this->processCreate()) {
      return new k_SeeOther($this->url());
    }
    return $this->renderHtmlCreate();
  }
  function processCreate() {
    if (!preg_match('/^\d+\.\d+\.\d+$/', $this->body('version'))) {
      $this->error = "Format of version must be X.X.X";
      return false;
    }
    $project = $this->context->getProject();
    $release = new Release(
      array(
        'project_id' => $project->id(),
        'version' => $this->body('version'),
        'status' => 'building',
        'mode' => 'manual'));
    $this->db->beginTransaction();
    try {
      $this->releases->delete(
        array('project_id' => $project->id(), 'version' => $this->body('version')));
      $this->releases->insert($release);
      $this->db->commit();
    } catch (Exception $ex) {
      $this->db->rollback();
      $this->error = $ex->getMessage();
      return false;
    }
    return true;
  }
  function canCreate() {
    if ($this->identity()->anonymous()) {
      return false;
    }
    $project = $this->context->getProject();
    return $project->owner() == $this->identity()->user();
  }
}