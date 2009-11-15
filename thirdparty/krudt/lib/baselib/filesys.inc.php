<?php
function filesys($replacement = null) {
  static $instance = null;
  if ($replacement) {
    $instance = $replacement;
  }
  if (!$instance) {
    $instance = new baselib_Filesys();
  }
  return $instance;
}

class baselib_Filesys {
  protected $debug = false;

  function enable_debug() {
    $this->debug = true;
  }

  function disable_debug() {
    $this->debug = false;
  }

  function get_contents($path) {
    return file_get_contents($path);
  }

  function put_contents($path, $content) {
    $this->debug && print("[Filesys] file_put_contents($path, ".strlen($content)." bytes)\n");
    return file_put_contents($path, $content);
  }

  function is_dir($path) {
    return is_dir($path);
  }

  function is_file($path) {
    return is_file($path);
  }

  function file_exists($path) {
    return file_exists($path);
  }

  function mkdir_p($path) {
    if (!$this->is_dir($path)) {
      $this->mkdir($path, 0777, true);
    }
  }

  function mkdir($path, $mode = 0777, $recursive = false) {
    $this->debug && print "[Filesys] mkdir($path, $mode, $recursive)\n";
    return mkdir($path, $mode, $recursive);
  }

  function rm($path) {
    $this->debug && print "[Filesys] rm($path)\n";
    return unlink($path);
  }

  function unlink($path) {
    return $this->rm($path);
  }

  function delete($path) {
    return $this->rm($path);
  }

  function rmdir($path) {
    $this->debug && print "[Filesys] rmdir($path)\n";
    return rmdir($path);
  }

  function rm_rf($path) {
    $this->debug && print "[Filesys] rm_rf($path)\n";
    foreach ($this->scandir($path) as $node) {
      if ($node !== '.' && $node !== '..') {
        $node_path = $path . '/' . $node;
        if ($this->is_dir($node_path)) {
          $this->rm_rf($node_path);
        } else {
          $this->rm($node_path);
        }
      }
    }
    $this->rmdir($path);
  }

  function scandir($path) {
    return scandir($path);
  }

  function chdir($path) {
    $this->debug && print "[Filesys] chdir($path)\n";
    return chdir($path);
  }

  function chmod($filename, $mode) {
    $this->debug && print "[Filesys] chmod($filename, $mode)\n";
    return chmod($filename, $mode);
  }
}

class baselib_ReadonlyFilesys extends baselib_Filesys {
  function put_contents($path, $content) {
    $this->debug && print "[Filesys] file_put_contents($path, ".strlen($content)." bytes)\n";
    return true;
  }

  function mkdir($path, $mode = 0777, $recursive = false) {
    $this->debug && print "[Filesys] mkdir($path, $mode, $recursive)\n";
    return true;
  }

  function rm($path) {
    $this->debug && print "[Filesys] rm($path)\n";
    return true;
  }

  function rmdir($path) {
    $this->debug && print "[Filesys] rmdir($path)\n";
    return true;
  }

  function chmod($filename, $mode) {
    $this->debug && print "[Filesys] chmod($filename, $mode)\n";
    return true;
  }
}
