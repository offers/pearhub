<?php
require_once 'shell.inc.php';
require_once 'pdoext.inc.php';
require_once 'pdoext/connection.inc.php';

class RepoLocation {
  protected $url;
  protected $username;
  protected $password;
  function __construct($url, $username = null, $password = null) {
    $this->url = rtrim($url, '/');
    $this->username = $username;
    $this->password = $password;
  }
  function url() {
    return $this->url;
  }
  function baseUrl() {
    return preg_replace('~/trunk(/?)$~', '', $this->url());
  }
  function username() {
    return $this->username;
  }
  function password() {
    return $this->password;
  }
}

/**
 * Detects repository type from a RepoLocation and returns an appropriate RepoInfo
 */
class RepoProbe {
  protected $shell;
  protected $db;
  public $debug = false;
  function __construct(Shell $shell, pdoext_Connection $db) {
    $this->shell = $shell;
    $this->db = $db;
  }
  function getRepositoryType(RepoLocation $location) {
    try {
      $this->shell->run('git ls-remote --heads %s', $location->url());
      return 'git';
    } catch (ProcessExitException $ex) {
      /* squelch */
      if ($this->debug) {
        echo $ex;
      }
    }
    if ($location->username()) {
      $result = trim($this->shell->run('svn --username %s --password %s --non-interactive --trust-server-cert ls %s', $location->username(), $location->password(), $location->baseUrl()));
    } else {
      $result = trim($this->shell->run('svn --non-interactive --trust-server-cert ls %s', $location->baseUrl()));
    }
    if (preg_match('/^svn:/', $result)) {
      return null;
    }
    $lines = explode("\n", $result);
    if (in_array('trunk/', $lines) && in_array('tags/', $lines)) {
      return 'svn/standard';
    }
    return 'svn/nonstandard';
  }
  /**
   * @return RepoInfo
   */
  function getRepositoryAccess($project) {
    switch ($this->getRepositoryTypeAndCache($project->repositoryLocation())) {
    case 'git':
      return new GitRepoInfo($project->repositoryLocation(), $this->shell);
    case 'svn/standard':
      $location = $project->repositoryLocation();
      $location = new RepoLocation(
        preg_replace('~/trunk(/?)$~', '', $location->url()),
        $location->username(),
        $location->password());
      return new SvnStandardRepoInfo($location, $this->shell);
    case 'svn/nonstandard':
      return new SvnRepoInfo($project->repositoryLocation(), $this->shell);
    default:
      throw new Exception("Unable to determine repository type");
    }
  }
  function getRepositoryTypeAndCache(RepoLocation $location, $ignore_cache = false) {
    if (!$ignore_cache) {
      $result = $this->db->pexecute(
        "select type from repository_types where url = :url and last_probe > date_add(now(), interval -1 hour)",
        array(
          ':url' => $location->url()));
      $row = $result->fetch(PDO::FETCH_ASSOC);
      if ($row) {
        return $row['type'];
      }
    }
    $type = $this->getRepositoryType($location);
    if ($type) {
      $this->db->pexecute(
        "insert into repository_types set url = :url, type = :type, last_probe = now()
on duplicate key update type = :type2, last_probe = now()",
        array(
          ':url' => $location->url(),
          ':type' => $type,
          ':type2' => $type));
    }
    return $type;
  }
}

/**
 * Provides a uniform access to remote repositries.
 */
interface RepoInfo {
  function listTags();
  function latestTag();
  function exportTag($tagname);
  function validateRevision($revision);
  function exportRevision($revision);
}

class TagInfo {
  protected $raw;
  protected $major;
  protected $minor;
  protected $patch;
  protected $stability;
  function __construct($raw, $major, $minor, $patch, $stability) {
    $this->raw = $raw;
    $this->major = $major;
    $this->minor = $minor;
    $this->patch = $patch;
    if (preg_match('/(stable|beta|alpha|devel|snapshot)/', $stability, $reg)) {
      $this->stability = $reg[1];
    } else {
      $this->stability = 'stable';
    }
  }
  function raw() {
    return $this->raw;
  }
  function version() {
    return $this->major . '.' . $this->minor . '.' . $this->patch;
  }
  function stability() {
    return $this->stability;
  }
  function compare($tag_info) {
    return strcmp($this->version(), $tag_info->version());
  }
  function __toString() {
    throw new Exception("TagInfo to string conversion");
    //return $this->version();
  }
}

/**
 * Baseclass for svn repository access.
 * You probably want to use the subclass `SvnStandardRepoInfo` instead,
 * if your repo has a standard layout (`/trunk` and `/tags`)
 */
class SvnRepoInfo implements RepoInfo {
  protected $location;
  protected $shell;
  protected $trunk;
  function __construct(RepoLocation $location, $shell) {
    $this->location = $location;
    $this->trunk = $this->location->url();
    $this->shell = $shell;
  }
  protected function svn($command /*, $args */) {
    $args = func_get_args();
    if ($this->location->username()) {
      $result = trim($this->shell->run('svn --username %s --password %s --non-interactive --trust-server-cert ls %s', $this->location->username(), $this->location->password(), $this->location->baseUrl()));
      $command = '--username %s --password %s --non-interactive --trust-server-cert ' . $command;
      array_shift($args);
      array_unshift($args, $this->location->password());
      array_unshift($args, $this->location->username());
      array_unshift($args, $command);
    } else {
      $args[0] = '--non-interactive --trust-server-cert ' . $args[0];
    }
    $args[0] = 'svn ' . $args[0];
    return $this->shell->runVarArgs($args);
  }
  function listTags() {
    throw new Exception("Unable to list tags for non-standard svn repo");
  }
  function latestTag() {
    throw new Exception("Unable to list tags for non-standard svn repo");
  }
  function exportTag($tagname) {
    throw new Exception("Unable to export tag for non-standard svn repo");
  }
  function validateRevision($revision) {
    if (!preg_match('/^[0-9]+$/', $revision)) {
      return false;
    }
    $url = $this->trunk . '@' . $revision;
    $result = $this->svn('info %s', $url);
    return trim($result) !== "$url:  (Not a valid URL)";
  }
  function exportRevision($revision) {
    $name = $this->shell->getTempname();
    $this->svn('export %s --revision=%s %s', $this->trunk, $revision, $name);
    return new LocalCopy($name);
  }
}

/**
 * Standard svn repository access.
 */
class SvnStandardRepoInfo extends SvnRepoInfo {
  protected $tags;
  function __construct(RepoLocation $location, $shell) {
    parent::__construct($location, $shell);
    $this->trunk = $this->location->url() . '/trunk';
  }
  function listTags() {
    if ($this->tags === null) {
      $this->tags = array();
      $result = explode("\n", trim($this->svn('ls %s', $this->location->url() . '/tags')));
      foreach ($result as $line) {
        if (preg_match('~^(([a-zA-Z_-]+)?([0-9]+)(\.([0-9]+))?(\.([0-9]+))?([A-Za-z]+[0-9A-Za-z-]*)?)/?$~', $line, $reg)) {
          $tag_info = new TagInfo(
            $reg[1],
            $reg[3],
            isset($reg[5]) ? $reg[5] : 0,
            isset($reg[7]) ? $reg[7] : 0,
            isset($reg[8]) ? $reg[8] : 'stable'
          );
          $this->tags[$tag_info->version()] = $tag_info;
        }
      }
    }
    return array_keys($this->tags);
  }
  function latestTag() {
    $tags = $this->listTags();
    if (count($tags) > 0) {
      sort($tags);
      return $tags[count($tags) - 1];
    }
  }
  function exportTag($tagname) {
    $this->listTags();
    $tag = $this->getTaginfo($tagname);
    $name = $this->shell->getTempname();
    $this->svn('export %s %s', $this->location->url() . '/tags/' . $tag->raw(), $name);
    return new LocalCopy($name);
  }
  function getTaginfo($tagname) {
    $this->listTags();
    return $this->tags[$tagname];
  }
}

/**
 * git repository access.
 */
class GitRepoInfo implements RepoInfo {
  protected $location;
  protected $shell;
  protected $tags;
  function __construct(RepoLocation $location, $shell) {
    $this->location = $location;
    $this->shell = $shell;
  }
  function listTags() {
    if ($this->tags === null) {
      $this->tags = array();
      $result = explode("\n", trim($this->shell->run('git ls-remote --tags %s', $this->location->url())));
      foreach ($result as $line) {
        if (preg_match('~^[0-9a-f]{40}\s+refs/tags/(([a-zA-Z_-]+)?([0-9]+)(\.([0-9]+))?(\.([0-9]+))?([A-Za-z]+[0-9A-Za-z-]*)?)$~', $line, $reg)) {
          $tag_info = new TagInfo(
            $reg[1],
            $reg[3],
            isset($reg[5]) ? $reg[5] : 0,
            isset($reg[7]) ? $reg[7] : 0,
            isset($reg[8]) ? $reg[8] : 'stable'
          );
          $this->tags[$tag_info->version()] = $tag_info;
        }
      }
    }
    return array_keys($this->tags);
  }
  function latestTag() {
    $tags = $this->listTags();
    if (count($tags) > 0) {
      sort($tags);
      return $tags[count($tags) - 1];
    }
  }
  function exportTag($tagname) {
    $this->listTags();
    $tag = $this->getTaginfo($tagname);
    $name = $this->shell->getTempname();
    $this->shell->run('git clone %s %s', $this->location->url(), $name);
    $this->shell->run('cd %s && git checkout %s', $name, $tag->raw());
    $this->shell->run('rm -rf %s', $name . '/.git');
    return new LocalCopy($name);
  }
  function getTaginfo($tagname) {
    $this->listTags();
    return $this->tags[$tagname];
  }
  function validateRevision($revision) {
    if (!preg_match('/^[0-9a-f]{40}$/', $revision)) {
      return false;
    }
    $name = $this->shell->getTempname();
    $this->shell->run('git clone %s %s', $this->location->url(), $name);
    $result = explode("\n", trim($this->shell->run('cd %s && git log --pretty=oneline', $name)));
    $this->shell->run('rm -rf %s', $name);
    $revisions = array();
    foreach ($result as $line) {
      if (preg_match('~^([0-9a-f]{40})\s+.*$~', $line, $reg)) {
        $revisions[] = $reg[1];
      }
    }
    return in_array($revision, $revisions);
  }
  function exportRevision($revision) {
    $name = $this->shell->getTempname();
    $this->shell->run('git clone %s %s', $this->location->url(), $name);
    $this->shell->run('cd %s && git checkout %s', $name, $revision);
    $this->shell->run('rm -rf %s', $name . '/.git');
    return new LocalCopy($name);
  }
}

/**
 * Transient object around a local copy (export) of a repository.
 * Be sure to destroy it after use.
 */
class LocalCopy {
  protected $root;
  function __construct($root) {
    $this->root = $root;
  }
  function destroy($shell) {
    $shell->run('rm -rf %s', $this->root);
  }
  function getPath() {
    return $this->root;
  }
  function __tostring() {
    return "[LocalCopy at " . $this->getPath() . "]";
  }
}

// $sh = new Shell();
// var_dump($sh->run('echo %s', 'foo%sbar'));

// $repo = new SvnStandardRepoInfo('https://konstrukt.googlecode.com/svn', new Shell());
// var_dump($repo->listTags());

// $repo = new GitRepoInfo('git@github.com:unwire/handsoap.git', new Shell());
// $tags = $repo->listTags();
// echo $repo->exportTag($tags[0]), "\n";

// $repo = new SvnStandardRepoInfo('https://konstrukt.googlecode.com/svn', new Shell());
// $tags = $repo->listTags();
// echo $repo->exportTag($tags[0]), "\n";

// $repo = new SvnStandardRepoInfo('https://konstrukt.googlecode.com/svn', new Shell());
// var_dump($repo->validateRevision(300));

// $repo = new GitRepoInfo('git@github.com:unwire/handsoap.git', new Shell());
// var_dump($repo->validateRevision('5241be51db06f621205c17bf5111e335b832fa20'));
