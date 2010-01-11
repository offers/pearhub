<?php
require_once 'shell.inc.php';
require_once 'pdoext.inc.php';
require_once 'pdoext/connection.inc.php';

/**
 * Detects repository type from a URL and returns an appropriate RepoInfo
 */
class RepoProbe {
  protected $shell;
  protected $db;
  public $debug = false;
  function __construct(Shell $shell, pdoext_Connection $db) {
    $this->shell = $shell;
    $this->db = $db;
  }
  function getRepositoryType($url) {
    try {
      $this->shell->run('git ls-remote --heads %s', $url);
      return 'git';
    } catch (ProcessExitException $ex) {
      /* squelch */
      if ($this->debug) {
        echo $ex;
      }
    }
    $svn_base_url = preg_replace('~/trunk(/?)$~', '', $url);
    try {
      $result = trim($this->shell->run('svn --non-interactive ls %s', $svn_base_url));
    } catch (ProcessExitException $ex) {
      if ($this->debug) {
        echo $ex;
      }
      if (preg_match('/Server certificate verification failed: issuer is not trusted/', $ex->stdout() . $ex->stderr())) {
        // throw new SslCertificateException("Server certificate verification failed for: " . $url);
        $this->shell->run('yes p | svn info %s', $url);
        $result = trim($this->shell->run('svn --non-interactive ls %s', $url));
      } else {
        throw $ex;
      }
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
    switch ($this->getRepositoryTypeAndCache($project->repository())) {
    case 'git':
      return new GitRepoInfo($project->repository(), $this->shell);
    case 'svn/standard':
      return new SvnStandardRepoInfo(preg_replace('~/trunk(/?)$~', '', $project->repository()), $this->shell);
    case 'svn/nonstandard':
      return new SvnRepoInfo($project->repository(), $this->shell);
    default:
      throw new Exception("Unable to determine repository type");
    }
  }
  function getRepositoryTypeAndCache($url) {
    $result = $this->db->pexecute(
      "select type from repository_types where url = :url and last_probe > date_add(now(), interval -1 hour)",
      array(
        ':url' => $url));
    $row = $result->fetch(PDO::FETCH_ASSOC);
    if ($row) {
      return $row['type'];
    }
    $type = $this->getRepositoryType($url);
    if ($type) {
      $this->db->pexecute(
        "insert into repository_types set url = :url, type = :type, last_probe = now()
on duplicate key update type = :type2, last_probe = now()",
        array(
          ':url' => $url,
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
  protected $url;
  protected $shell;
  protected $trunk;
  function __construct($url, $shell) {
    $this->url = rtrim($url, '/');
    $this->trunk = $this->url;
    $this->shell = $shell;
  }
  protected function svn($command /*, $args */) {
    $args = func_get_args();
    $args[0] = 'svn --non-interactive ' . $args[0];
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
  function __construct($url, $shell) {
    parent::__construct($url, $shell);
    $this->trunk = $this->url . '/trunk';
  }
  function listTags() {
    if ($this->tags === null) {
      $this->tags = array();
      $result = explode("\n", trim($this->svn('ls %s', $this->url . '/tags')));
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
    $this->svn('export %s %s', $this->url . '/tags/' . $tag->raw(), $name);
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
  protected $url;
  protected $shell;
  protected $tags;
  function __construct($url, $shell) {
    $this->url = $url;
    $this->shell = $shell;
  }
  function listTags() {
    if ($this->tags === null) {
      $this->tags = array();
      $result = explode("\n", trim($this->shell->run('git ls-remote --tags %s', $this->url)));
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
    $this->shell->run('git clone %s %s', $this->url, $name);
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
    $this->shell->run('git clone %s %s', $this->url, $name);
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
    $this->shell->run('git clone %s %s', $this->url, $name);
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
