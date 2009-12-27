<?php

/**
 * Filespec handler. Finds files that match a filespec pattern, in a directory.
 * Implemented as a visitor, taking a handler as subject.
 */
class Filespec {
  protected $root;
  protected $content = array();
  protected $ignore = array();
  protected $ignore_pattern;
  protected $location = array();
  function __construct($root) {
    $this->root = $root;
  }
  function addContent($pattern, $role) {
    $this->content['~^'.str_replace("\\*", ".*", preg_quote($pattern, '~')).'$~'] = $role;
  }
  function addIgnore($pattern) {
    // glob pattern -> preg pattern
    $this->ignore[] = str_replace("\\*", ".*", preg_quote($pattern, '~'));
    $this->ignore_pattern = '~^('.implode('|', $this->ignore).')$~';
  }
  function addLocation($path, $location) {
    $this->location[$path] = $location;
  }
  function run($handler) {
    $handler->beginDir('/');
    foreach (scandir($this->root) as $child) {
      if ($child !== '.' && $child !== '..') {
        $this->traverse($handler, $this->root.'/'.$child);
      }
    }
    $handler->endDir();
  }
  function traverse($handler, $path) {
    $relpath = substr($path, strlen($this->root));
    if ($this->ignore_pattern && preg_match($this->ignore_pattern, $relpath)) {
      return;
    }
    $role = null;
    foreach ($this->content as $pattern => $r) {
      if (preg_match($pattern, $relpath)) {
        $role = $r;
      }
    }
    $baseinstalldir = isset($this->location[$relpath]) ? $this->location[$relpath] : null;
    if (is_dir($path)) {
      $handler->beginDir(basename($path), $baseinstalldir);
      foreach (scandir($path) as $child) {
        if ($child !== '.' && $child !== '..') {
          $this->traverse($handler, $path.'/'.$child);
        }
      }
      $handler->endDir();
    } elseif (is_file($path)) {
      if ($role) {
        $handler->file(basename($path), $role);
      }
    } else {
      throw new Exception("File not found: $path");
    }
  }
}

/**
 * Filter that buffers and skips empty directories.
 */
class FileHandlerDirBuffer {
  protected $handler;
  protected $buffer = array();
  function __construct($handler) {
    $this->handler = $handler;
  }
  function beginDir($path, $baseinstalldir = null) {
    $this->buffer[] = array(false, $path, $baseinstalldir);
  }
  function endDir() {
    $tuple = array_pop($this->buffer);
    if ($tuple[0]) {
      $this->handler->endDir();
    }
  }
  function file($path, $role) {
    for ($ii=0, $ll=count($this->buffer); $ii < $ll; $ii++) {
      if (!$this->buffer[$ii][0]) {
        $this->buffer[$ii][0] = true;
        $this->handler->beginDir($this->buffer[$ii][1], $this->buffer[$ii][2]);
      }
    }
    $this->handler->file($path, $role);
  }
}

/**
 * Useful for debugging.
 */
class PrintFileHandler {
  protected $level = 0;
  function beginDir($path, $baseinstalldir = null) {
    print str_repeat('  ', $this->level) . '<dir name="'.htmlspecialchars($path).'"';
    if ($baseinstalldir) {
      print ' baseinstalldir="'.htmlspecialchars($baseinstalldir).'"';
    }
    print ">\n";
    $this->level++;
  }
  function endDir() {
    $this->level--;
    print str_repeat('  ', $this->level) . "</dir>\n";
  }
  function file($path, $role) {
    print str_repeat('  ', $this->level) . '<file name="'.htmlspecialchars($path).'" role="'.htmlspecialchars($role).'"/>' . "\n";
  }
}

/*
  $spec = new Filespec('/home/tkn/public/konstrukt-troelskn/trunk');
  $spec->addLocation('/lib/konstrukt', '/konstrukt');
  $spec->addContent('/lib*', 'php');
  $spec->addContent('/docs/txt*', 'doc');
  $spec->addIgnore('*.svn');
  $spec->run(new FileHandlerDirBuffer(new PrintFileHandler()));
*/