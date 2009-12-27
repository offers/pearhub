<?php

/**
 * This contains code for accessing repositories.
 */

class Shell {
  function run($command /*[, ...]*/) {
    $args = func_get_args();
    $args[0] .= ' 2>&1';
    $process = call_user_func_array(array($this, 'open'), $args);
    $result = $process->exec();
    if ($result['result'] !== 0) {
      throw new Exception("Child process exited with error (".$result['result'].")");
    }
    return $result['stdout'];
  }
  function open($command /*[, ...]*/) {
    $args = func_get_args();
    array_shift($args);
    $tokens = array();
    foreach (preg_split('/(%s)/', $command, -1 , PREG_SPLIT_DELIM_CAPTURE) as $token) {
      if ($token === '%s') {
        if (count($args) === 0) {
          throw new Exception("Argument number mismatch");
        }
        $tokens[] = escapeshellarg(array_shift($args));
      } else {
        $tokens[] = $token;
      }
    }
    if (count($args) !== 0) {
      throw new Exception("Argument number mismatch");
    }
    return new ShellProcess(implode($tokens));
  }
  function getTempname() {
    $temp = tempnam(sys_get_temp_dir(), 'php');
    if (file_exists($temp)) {
      unlink($temp);
      return $temp;
    }
    throw new Exception("Unable to reserve a temporary name");
  }
}

class ShellProcess {
  protected $command;
  function __construct($command) {
    $this->command = $command;
  }
  function exec($in = null, $cwd = null, $env = null) {
    $descriptorspec = array(
      0 => array("pipe", "r"),
      1 => array("pipe", "w"),
      2 => array("pipe", "w")
    );
    $cwd = $cwd ? $cwd : getcwd();
    $env = $env ? $env : $_ENV;
    $process = proc_open($this->command, $descriptorspec, $pipes, $cwd, $env);
    if (is_resource($process)) {
      if ($in) {
        fwrite($pipes[0], $in);
      }
      fclose($pipes[0]);
      $out = stream_get_contents($pipes[1]);
      fclose($pipes[1]);
      $err = stream_get_contents($pipes[2]);
      fclose($pipes[2]);
      $return_value = proc_close($process);
      return array(
        'stdout' => $out,
        'stderr' => $err,
        'result' => $return_value,
      );
    } else {
      throw new Exception("Can't open sub-process");
    }
  }
}

class SvnRepoInfo {
  protected $root;
  protected $shell;
  protected $trunk;
  function __construct($root, $shell) {
    $this->root = rtrim($root, '/');
    $this->trunk = $this->root;
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

class SvnStandardRepoInfo extends SvnRepoInfo {
  function __construct($root, $shell) {
    parent::__construct($root, $shell);
    $this->trunk = $this->root . '/trunk';
  }
  function listTags() {
    $tags = array();
    $result = explode("\n", trim($this->shell->run('svn ls %s', $this->root . '/tags')));
    foreach ($result as $line) {
      if (preg_match('~^[0-9]+(\.[0-9]+)?(\.[0-9]+)?\/$~', $line)) {
        $tags[] = rtrim($line, '/');
      }
    }
    return $tags;
  }
  function exportTag($tagname) {
    $name = $this->shell->getTempname();
    $this->shell->run('svn export %s %s', $this->root . '/tags/' . $tagname, $name);
    return new LocalCopy($name);
  }
}

class GitRepoInfo {
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

class LocalCopy {
  protected $root;
  function __construct($root) {
    $this->root = $root;
  }
  function destroy($shell) {
    $this->shell->run('rm -rf %s', $this->root);
  }
  function getPath() {
    return $this->root;
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
