<?php
function console($replacement = null) {
  static $instance = null;
  if ($replacement) {
    $instance = $replacement;
  }
  if (!$instance) {
    $instance = new baselib_ConsoleInputParser();
  }
  return $instance;
}

class baselib_ConsoleInputParser {
  protected $boolean_true = array('yes', 'on', 'true', '1');
  protected $boolean_false = array('no', 'off', 'false', '0');

  protected $script_file_name;
  protected $options;
  protected $arguments;

  function __construct($argv = null) {
    if (is_null($argv)) {
      $argv = $_SERVER['argv'];
    }
    $this->script_file_name = $argv[0];
    $this->options = array();
    $this->arguments = array();
    foreach (array_slice($argv, 1) as $arg) {
      if (preg_match('~^--([^=]+)=(.*)~', $arg, $reg)) {
        $this->options[$reg[1]] = $reg[2];
      } else if (preg_match('~^--([a-zA-Z0-9]+)$~', $arg, $reg)) {
        $this->options[$reg[1]] = true;
      } else if (preg_match('~^-([a-zA-Z]+)$~', $arg, $reg)) {
        foreach (str_split($reg[1]) as $option) {
          $this->options[$option] = true;
        }
      } else {
        $this->arguments[] = $arg;
      }
    }
  }

  function script_filename() {
    return $this->script_file_name;
  }

  function user_name() {
    $user = posix_getpwuid(posix_getuid());
    return $user['name'];
  }

  function option($names, $default = null) {
    if (is_string($names)) {
      $names = array($names);
    }
    foreach ($names as $name) {
      if (isset($this->options[$name])) {
        return $this->options[$name];
      }
    }
    return $default;
  }

  function argument($num = 0, $default = null) {
    if (isset($this->arguments[$num])) {
      return $this->arguments[$num];
    }
    return $default;
  }

  function count_arguments() {
    return count($this->arguments);
  }

  function arguments_as_array() {
    return $this->arguments;
  }

  function arguments_as_string() {
    return implode(" ", $this->get_arguments_as_array());
  }

  function option_boolean($name, $default = false) {
    $value = $this->getOption($name, (boolean) $default);
    if (is_bool($value)) {
      return $value;
    }
    if (in_array(strtolower($value), $this->boolean_true)) {
      return true;
    }
    if (in_array(strtolower($value), $this->boolean_false)) {
      return false;
    }
    return null;
  }

  function read_input() {
    file_get_contents('php://input');
  }
}
