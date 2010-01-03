<?php
class Shell {
  public $debug = false;
  public $temp_dir;
  function __construct() {
    $this->temp_dir = sys_get_temp_dir();
  }
  function run($command /*[, ...]*/) {
    $args = func_get_args();
    $args[0] .= ' 2>&1';
    $process = call_user_func_array(array($this, 'open'), $args);
    if ($this->debug) {
      echo "[Shell] ", implode(" ", $args), "\n";
    }
    $result = $process->exec();
    if ($result['result'] !== 0) {
      throw new ProcessExitException($result);
    }
    if ($this->debug && $result['stdout']) {
      echo $result['stdout'], "\n---\n";
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
    $temp = tempnam($this->temp_dir, 'php');
    if (file_exists($temp)) {
      unlink($temp);
      return $temp;
    }
    throw new Exception("Unable to reserve a temporary name");
  }
}

class ProcessExitException extends Exception {
  protected $result;
  function __construct($result) {
    parent::__construct("Child process exited with error (".$result['result'].")");
    $this->result = $result['stderr'];
  }
  function stderr() {
    return $this->result['stderr'];
  }
  function stdout() {
    return $this->result['stdout'];
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
