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
  protected $debug = false;
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
      "select type from repository_types where url = :url",
      array(
        ':url' => $url));
    $row = $result->fetch(PDO::FETCH_ASSOC);
    if ($row) {
      return $row['type'];
    }
    $type = $this->getRepositoryType($url);
    if ($type) {
      $this->db->pexecute(
        "insert into repository_types (url, type) values (:url, :type)",
        array(
          ':url' => $url,
          ':type' => $type));
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
    try {
      return $this->shell->runVarArgs($args);
    } catch (ProcessExitException $ex) {
      if (preg_match('/Server certificate verification failed: issuer is not trusted/', $ex->stdout() . $ex->stderr())) {
        // throw new SslCertificateException("Server certificate verification failed for: " . $this->url);
        $this->shell->run('yes p | svn info %s', $this->url);
        return $this->shell->runVarArgs($args);
      }
      throw $ex;
    }
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
        if (preg_match('~^(v?([0-9]+)(\.([0-9]+))?(\.([0-9]+))?)/?$~', $line, $reg)) {
          $raw = $reg[1];
          $v = array(
            $reg[2],
            isset($reg[4]) ? $reg[4] : 0,
            isset($reg[6]) ? $reg[6] : 0);
          $this->tags[implode('.', $v)] = $raw;
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
    $raw = $this->tags[$tagname];
    $name = $this->shell->getTempname();
    $this->svn('export %s %s', $this->url . '/tags/' . $raw, $name);
    return new LocalCopy($name);
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
        if (preg_match('~^[0-9a-f]{40}\s+refs/tags/(v?([0-9]+)(\.([0-9]+))?(\.([0-9]+))?)$~', $line, $reg)) {
          $raw = $reg[1];
          $v = array(
            $reg[2],
            isset($reg[4]) ? $reg[4] : 0,
            isset($reg[6]) ? $reg[6] : 0);
          $this->tags[implode('.', $v)] = $raw;
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
    $raw = $this->tags[$tagname];
    $name = $this->shell->getTempname();
    $this->shell->run('git clone %s %s', $this->url, $name);
    $this->shell->run('cd %s && git checkout %s', $name, $raw);
    $this->shell->run('rm -rf %s', $name . '/.git');
    return new LocalCopy($name);
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
