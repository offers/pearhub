<?php
require_once 'shell.inc.php';

/**
 * Provides a uniform access to remote repositries.
 */
interface RepoInfo {
  function listTags();
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
  function listTags() {
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
    $result = $this->shell->run('svn info %s', $url);
    return trim($result) !== "$url:  (Not a valid URL)";
  }
  function exportRevision($revision) {
    $name = $this->shell->getTempname();
    $this->shell->run('svn export %s --revision=%s %s', $this->trunk, $revision, $name);
    return new LocalCopy($name);
  }
}

/**
 * Standard svn repository access.
 */
class SvnStandardRepoInfo extends SvnRepoInfo {
  function __construct($url, $shell) {
    parent::__construct($url, $shell);
    $this->trunk = $this->url . '/trunk';
  }
  function listTags() {
    $tags = array();
    $result = explode("\n", trim($this->shell->run('svn ls %s', $this->url . '/tags')));
    foreach ($result as $line) {
      if (preg_match('~^[0-9]+(\.[0-9]+)?(\.[0-9]+)?\/$~', $line)) {
        $tags[] = rtrim($line, '/');
      }
    }
    return $tags;
  }
  function exportTag($tagname) {
    $name = $this->shell->getTempname();
    $this->shell->run('svn export %s %s', $this->url . '/tags/' . $tagname, $name);
    return new LocalCopy($name);
  }
}

/**
 * git repository access.
 */
class GitRepoInfo implements RepoInfo {
  protected $url;
  protected $shell;
  function __construct($url, $shell) {
    $this->url = $url;
    $this->shell = $shell;
  }
  function listTags() {
    $tags = array();
    $result = explode("\n", trim($this->shell->run('git ls-remote --tags %s', $this->url)));
    foreach ($result as $line) {
      if (preg_match('~^[0-9a-f]{40}\s+refs/tags/v([0-9]+(\.[0-9]+)?(\.[0-9]+))?$~', $line, $reg)) {
        $tags[] = $reg[1];
      }
    }
    return $tags;
  }
  function exportTag($tagname) {
    $name = $this->shell->getTempname();
    $this->shell->run('git clone %s %s', $this->url, $name);
    $this->shell->run('cd %s && git checkout %s', $name, 'v'.$tagname);
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
