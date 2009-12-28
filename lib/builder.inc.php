<?php
class ManifestCompiler {
  protected $manifest;
  protected $files;
  function build($local_copy, $project, $version) {
    $this->files = array();
    $this->manifest = new XmlWriter();
    $this->manifest->openMemory();
    $this->manifest->setIndent(true);
    $this->manifest->setIndentString('  ');
    $this->manifest->startDocument('1.0', 'UTF-8');
    $this->writeHeader();
    $this->writeDetails($project, $version);
    $this->writeContents($local_copy, $project);
    $this->writeDependencies($project);
    $this->writeFilelist($local_copy, $project);
    $this->writeMaintainers($project);
    $this->manifest->endElement();
    $this->manifest->endDocument();
    return $this->manifest->outputMemory();
  }
  function writeHeader() {
    $this->manifest->startElement('package');
    // $this->manifest->writeAttribute("packagerversion", "1.7.1");
    $this->manifest->writeAttribute("version", "2.0");
    $this->manifest->writeAttribute("xmlns", "http://pear.php.net/dtd/package-2.0");
    $this->manifest->writeAttributeNS(
      'xmlns',
      'tasks',
      'http://pear.php.net/dtd/package-2.0',
      'http://pear.php.net/dtd/tasks-1.0');
    $this->manifest->writeAttributeNS(
      'xmlns',
      'xsi',
      'http://pear.php.net/dtd/package-2.0',
      'http://www.w3.org/2001/XMLSchema-instance');
    $this->manifest->writeAttributeNS(
      'xsi',
      'schemaLocation',
      'http://www.w3.org/2001/XMLSchema-instance',
      'http://pear.php.net/dtd/tasks-1.0 http://pear.php.net/dtd/tasks-1.0.xsd http://pear.php.net/dtd/package-2.0 http://pear.php.net/dtd/package-2.0.xsd');
  }
  function writeDetails($project, $version) {
    $this->manifest->writeElement("name", $project->name());
    $this->manifest->writeElement("uri", "http://www.example.org/"); // TODO
    $this->manifest->writeElement("summary", $project->summary());
    $this->manifest->writeElement("description", "TODO"); // TODO
    $this->manifest->startElement("license");
    if ($project->licenseHref()) {
      $this->manifest->writeAttribute("uri", $project->licenseHref());
    }
    $this->manifest->text($project->licenseTitle());
    $this->manifest->endElement();
    $this->manifest->writeElement("date", date("Y-m-d"));
    $this->manifest->writeElement("time", date("H:i:s"));
    $this->manifest->startElement("version");
    $this->manifest->writeElement("release", $version);
    $this->manifest->writeElement("api", $version);
    $this->manifest->endElement();
    $this->manifest->startElement("stability");
    $this->manifest->writeElement("release", "stable");
    $this->manifest->writeElement("api", "stable");
    $this->manifest->endElement();
  }
  function writeDependencies($project) {
    $this->manifest->startElement("dependencies");
    $this->manifest->startElement("required");
    $this->manifest->startElement("php");
    $this->manifest->writeElement("min", $project->phpVersion());
    $this->manifest->endElement();
    foreach ($project->dependencies() as $dp) {
      $this->manifest->startElement("package");
      $this->manifest->writeElement("name", preg_replace('~^.*/([^/]+)$~', '\1', $dp['channel']));
      $this->manifest->writeElement("channel", preg_replace('~^(.*/)[^/]+$~', '\1', $dp['channel']));
      if ($dp['version']) {
        $this->manifest->writeElement("min", $dp['version']);
      }
      $this->manifest->endElement();
    }
    $this->manifest->endElement();
    $this->manifest->endElement();
  }
  function writeMaintainers($project) {
    foreach (array('lead', 'developer', 'contributor', 'helper') as $type) {
      $this->writeMaintainerType($project, $type);
    }
  }
  function writeMaintainerType($project, $type) {
    foreach ($project->projectMaintainers() as $pm) {
      if ($pm->type() === $type) {
        $m = $pm->maintainer();
        $this->manifest->startElement($type);
        $this->manifest->writeElement("user", $m->user());
        if ($m->name()) {
          $this->manifest->writeElement("name", $m->name());
        }
        if ($m->email()) {
          $this->manifest->writeElement("email", $m->email());
        }
        $this->manifest->endElement();
      }
    }
  }
  function writeFilelist($local_copy, $project) {
    $this->manifest->startElement("phprelease");
    $this->manifest->startElement("filelist");
    $finder = new FileFinder(
      $local_copy->getPath(),
      array($this, 'filelistFile'));
    foreach ($project->files() as $file) {
      $finder->traverse($file['path'], $file['ignore'], $file['destination']);
    }
    $this->manifest->endElement();
    $this->manifest->endElement();
  }
  function filelistFile($path, $destination) {
    $this->manifest->startElement("install");
    $this->manifest->writeAttribute("name", $path);
    if ($destination) {
      $this->manifest->writeAttribute("as", $destination);
    }
    $this->manifest->endElement();
  }
  function writeContents($local_copy, $project) {
    $this->manifest->startElement("contents");
    $finder = new FileFinder(
      $local_copy->getPath(),
      array($this, 'contentFile'),
      array($this, 'beginDir'),
      array($this, 'endDir'));
    foreach ($project->files() as $file) {
      $finder->traverse($file['path'], $file['ignore'], $file['destination']);
    }
    $this->manifest->endElement();
  }
  function beginDir($path) {
    $this->manifest->startElement('dir');
    $this->manifest->writeAttribute("name", $path);
    $this->manifest->writeAttribute("baseinstalldir", '/');
  }
  function endDir() {
    $this->manifest->endElement();
  }
  function contentFile($path, $destination) {
    $this->manifest->startElement('file');
    $this->manifest->writeAttribute("name", $path);
    $this->manifest->writeAttribute("role", 'php');
    $this->manifest->endElement();
  }
}

/**
input:
  root -> absolute path
  path -> relative to root
  destination -> prefix to replace path. default to path
output:
  [(path, destination)]
 */
class FileFinder {
  protected $root;
  protected $callback_file;
  protected $callback_begin_dir;
  protected $callback_end_dir;
  protected $buffer = array();
  function __construct($root, $callback_file = null, $callback_begin_dir = null, $callback_end_dir = null) {
    $this->root = rtrim($root, '/');
    $this->callback_file = $callback_file;
    $this->callback_begin_dir = $callback_begin_dir;
    $this->callback_end_dir = $callback_end_dir;
  }
  function traverse($path, $ignore_pattern = null, $destination = null) {
    if (!$destination) {
      $destination = $path;
    }
    $this->buffer[] = array(false, $path);
    foreach (scandir($this->root . $path) as $child) {
      if ($child !== '.' && $child !== '..') {
        $full_path = $path . '/' . $child;
        if (!$ignore_pattern || !preg_match('/'.$ignore_pattern.'/', $path)) {
          if (is_dir($full_path)) {
            $this->traverse(
              $path . '/' . $child,
              $ignore_pattern,
              $destination ? ($destination . '/' . $child) : null);
          } elseif (is_file($full_path)) {
            for ($ii=0, $ll=count($this->buffer); $ii < $ll; $ii++) {
              if (!$this->buffer[$ii][0]) {
                $this->buffer[$ii][0] = true;
                $this->beginDir($this->buffer[$ii][1]);
              }
            }
            $this->file($path, $destination ? $destination : null);
          }
        }
      }
    }
    $tuple = array_pop($this->buffer);
    if ($tuple[0]) {
      $this->endDir();
    }
  }
  function beginDir($path) {
    if ($this->callback_begin_dir) {
      call_user_func($this->callback_begin_dir, $path);
    }
  }
  function endDir() {
    if ($this->callback_end_dir) {
      call_user_func($this->callback_end_dir, $path);
    }
  }
  function file($path, $destination = null) {
    if ($this->callback_file) {
      call_user_func($this->callback_file, $path, $destination);
    }
  }
}


require_once '../config/global.inc.php';
require_once 'repo.inc.php';
require_once 'projects.inc.php';

$db = new pdoext_Connection('mysql:host=localhost;dbname=pearhub', 'root');
$gateway = new ProjectGateway($db, new MaintainersGateway($db));
$project = $gateway->fetch(array('name' => 'konstrukt'));
$sh = new Shell();
$repo = new SvnStandardRepoInfo($project->repository(), $sh);
$tags = $repo->listTags();
$version = $tags[count($tags)-1];
$local_copy = $repo->exportTag($version);
// echo $local_copy, "\n";

$compiler = new ManifestCompiler($project);
echo $compiler->build($local_copy, $project, $version);
$local_copy->destroy($sh);
