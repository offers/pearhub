<?php
class ManifestCompiler {
  protected $manifest;
  function build($files, $project, $version, $stability, $channel = "pearhub.org") {
    $this->manifest = new XmlWriter();
    $this->manifest->openMemory();
    $this->manifest->setIndent(true);
    $this->manifest->setIndentString('  ');
    $this->manifest->startDocument('1.0', 'UTF-8');
    $this->writeHeader();
    $this->writeDetails($project, $version, $stability, $channel);
    $this->writeContents($files, $project);
    $this->writeDependencies($project);
    $this->writeFilelist($files, $project);
    $this->manifest->endElement();
    $this->manifest->endDocument();
    return $this->manifest->outputMemory();
  }
  function writeHeader() {
    $this->manifest->startElement('package');
    $this->manifest->writeAttribute("version", "2.0");
    $this->manifest->writeAttribute("xmlns", "http://pear.php.net/dtd/package-2.0");
    $this->manifest->writeAttribute("xmlns:tasks", "http://pear.php.net/dtd/tasks-1.0");
    $this->manifest->writeAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
    $this->manifest->writeAttribute("xsi:schemaLocation", "http://pear.php.net/dtd/tasks-1.0 http://pear.php.net/dtd/tasks-1.0.xsd http://pear.php.net/dtd/package-2.0 http://pear.php.net/dtd/package-2.0.xsd");
  }
  function writeDetails($project, $version, $stability, $channel) {
    $this->manifest->writeElement("name", $project->name());
    $this->manifest->writeElement("channel", $channel);
    $this->manifest->writeElement("summary", $project->summary());
    $this->manifest->writeElement("description", $project->description());
    $this->writeMaintainers($project);
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
    $this->manifest->startElement("license");
    if ($project->licenseHref()) {
      $this->manifest->writeAttribute("uri", $project->licenseHref());
    }
    $this->manifest->text($project->licenseTitle());
    $this->manifest->endElement();
    $this->manifest->writeElement("notes", "version " . $version);
  }
  function writeDependencies($project) {
    $this->manifest->startElement("dependencies");
    $this->manifest->startElement("required");
    $this->manifest->startElement("php");
    $this->manifest->writeElement("min", $project->phpVersion());
    $this->manifest->endElement();
    $this->manifest->startElement("pearinstaller");
    $this->manifest->writeElement("min", "1.4.0");
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
        if ($m->name()) {
          $this->manifest->writeElement("name", $m->name());
        }
        $this->manifest->writeElement("user", $m->user());
        if ($m->email()) {
          $this->manifest->writeElement("email", $m->email());
        }
        $this->manifest->writeElement("active", "yes");
        $this->manifest->endElement();
      }
    }
  }
  function writeContents($files, $project) {
    $this->manifest->startElement("contents");
    $this->manifest->startElement("dir");
    $this->manifest->writeAttribute("name", "/");
    $this->manifest->writeAttribute("baseinstalldir", "/");
    foreach ($files->files() as $file) {
      $this->manifest->startElement("file");
      $this->manifest->writeAttribute("baseinstalldir", "/");
      $this->manifest->writeAttribute("md5sum", md5_file($file['fullpath']));
      $this->manifest->writeAttribute("name", $file['destination']);
      $this->manifest->writeAttribute("role", "php");
      $this->manifest->endElement();
    }
    $this->manifest->endElement();
    $this->manifest->endElement();
  }
  function writeFilelist($files, $project) {
    $this->manifest->startElement("phprelease");
    $this->manifest->startElement("filelist");
    foreach ($files->files() as $file) {
      $this->manifest->startElement("install");
      $this->manifest->writeAttribute("name", $file['destination']);
      $this->manifest->writeAttribute("as", $file['destination']);
      $this->manifest->endElement();
    }
    $this->manifest->endElement();
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
  protected $events = array();
  protected $buffer = array();
  function __construct($root) {
    $this->root = rtrim($root, '/');
  }
  function traverse($path, $ignore_pattern = null, $destination = null) {
    $path = ltrim($path, '/');
    $this->buffer[] = array(false, $path);
    foreach (scandir($this->root . '/' . $path) as $child) {
      if ($child !== '.' && $child !== '..') {
        $full_path = $this->root . '/' . $path . '/' . $child;
        $child_destination = $destination ? (ltrim($destination, '/') . '/' . $child) : null;
        if (!$ignore_pattern || !preg_match('/'.$ignore_pattern.'/', $path)) {
          if (is_dir($full_path)) {
            $this->traverse(
              $path . '/' . $child,
              $ignore_pattern,
              $child_destination);
          } elseif (is_file($full_path)) {
            for ($ii=0, $ll=count($this->buffer); $ii < $ll; $ii++) {
              if (!$this->buffer[$ii][0]) {
                $this->buffer[$ii][0] = true;
                $this->events[] = array(
                  'type' => 'beginDir',
                  'path' => $this->buffer[$ii][1],
                  'fullpath' => $this->root . $this->buffer[$ii][1]);
              }
            }
            $this->events[] = array(
              'type' => 'file',
              'path' => $path . '/' . $child,
              'fullpath' => $this->root . '/' . $path . '/' . $child,
              'destination' => $child_destination);
          }
        }
      }
    }
    $tuple = array_pop($this->buffer);
    if ($tuple[0]) {
      $this->events[] = array('type' => 'endDir');
    }
  }
  function events() {
    return $this->events;
  }
  function files() {
    $files = array();
    foreach ($this->events as $event) {
      if ($event['type'] === 'file') {
        $files[] = $event;
      }
    }
    return $files;
  }
}

/**
class PackageBuilder
  input -> localcopy, package.xml
  ouput -> package.tgz

 */
class PackageBuilder {
  protected $shell;
  protected $destination;
  function __construct(Shell $shell, $destination) {
    $this->shell = $shell;
    $this->destination = rtrim($destination, '/');
  }
  function build($local_copy, $files, $project, $version, $stability) {
    if (!is_dir($this->destination)) {
      throw new Exception("Destination doesn't exist");
    }
    $package_name = $project->name() . '-' . $version;
    if (is_file($this->destination . '/' . $package_name . '.tgz')) {
      $this->shell->run('rm %s', $this->destination . '/' . $package_name . '.tgz');
    }
    if (is_file($this->destination . '/' . $package_name . '.tar')) {
      $this->shell->run('rm %s', $this->destination . '/' . $package_name . '.tar');
    }
    $root = $this->shell->getTempname();
    $this->shell->run('mkdir -p %s', $root);
    $compiler = new ManifestCompiler($project);
    file_put_contents(
      $root . '/package.xml',
      $compiler->build($files, $project, $version, $stability));
    $package_dir = $root . '/' . $package_name;
    $this->shell->run('mkdir -p %s', $package_dir);
    foreach ($files->files() as $file) {
      $this->shell->run('mkdir -p %s', dirname($package_dir . '/' . $file['destination']));
      $this->shell->run('mv %s %s', $file['fullpath'], $package_dir . '/' . $file['destination']);
    }
    $this->shell->run('cd %s ; tar -zcvf %s %s package.xml', $root, $this->destination . '/' . $package_name . '.tgz', $package_name);
    $this->shell->run('cd %s ; gunzip -c %s >%s', $this->destination, $package_name . '.tgz', $package_name . '.tar');
  }
  function deletePackage($project, $version) {
    $package_name = $project->name() . '-' . $version;
    if (is_file($this->destination . '/' . $package_name . '.tgz')) {
      $this->shell->run('rm %s', $this->destination . '/' . $package_name . '.tgz');
    }
    if (is_file($this->destination . '/' . $package_name . '.tar')) {
      $this->shell->run('rm %s', $this->destination . '/' . $package_name . '.tar');
    }
  }
}
