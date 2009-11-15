<?php
function shell($replacement = null) {
  static $instance = null;
  if ($replacement) {
    $instance = $replacement;
  }
  if (!$instance) {
    $instance = new baselib_Shell();
  }
  return $instance;
}

class baselib_Shell {
  protected $debug = false;

  function enable_debug() {
    $this->debug = true;
  }

  function disable_debug() {
    $this->debug = false;
  }

  /**
   * USAGE:
   *   exec('echo', 'Hello');
   * OR (like exec_bound):
   *   exec('foo -m :message :file', array(':message' => 'Hello', ':file' => 'test.txt'));
   */
  function exec($command /*, [...]*/) {
    $func_args = func_get_args();
    array_shift($func_args);
    if (count($func_args) === 1 && is_array($func_args[0])) {
      return $this->exec_bound($command, $func_args[0]);
    }
    $func_args = array_map('escapeshellarg', $func_args);
    array_unshift($func_args, escapeshellcmd($command));
    return $this->exec_raw(implode(" ", $func_args));
  }

  /**
   * USAGE:
   *   exec_bound('foo -m :message :file', array(':message' => 'Hello', ':file' => 'test.txt'));
   */
  function exec_bound($command, $arguments = array()) {
    $pattern = '/'.implode('|', array_map('preg_quote', array_keys($arguments))).'/';
    $tokens = array();
    foreach (preg_split($pattern, $command, PREG_SPLIT_DELIM_CAPTURE) as $token) {
      if (isset($arguments[$token])) {
        $tokens[] = escapeshellarg($arguments[$token]);
      } else {
        $tokens[] = $token;
      }
    }
    return $this->exec_raw(escapeshellcmd($command) . " " . implode('', $tokens));
  }

  function exec_raw($command) {
    if ($this->debug) {
      echo "[Shell] $command\n";
      $output = shell_exec($command);
      echo $output . "\n";
      return $output;
    }
    return shell_exec($command);
  }
}

class baselib_MockShell extends baselib_Shell {
  protected $mock = array();
  protected $commands = array();

  function mock($regexp, $output) {
    $this->mock[$regexp] = $output;
  }

  function commands_invoked() {
    return $this->commands;
  }

  function exec_raw($command) {
    $this->commands[] = $command;
    foreach ($this->mock as $regexp => $stub) {
      if (preg_match($regexp, $command)) {
        if ($this->debug) {
          echo "[MockShell] $command\n";
          echo "(mock) $stub\n";
        }
        return $stub;
      }
    }
    throw new Exception("No mock found for input: '$command'");
  }
}
