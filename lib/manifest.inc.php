<?php

class Manifest {
  protected $name = "";
  protected $summary = "";
  protected $href = "";
  protected $license_title = "";
  protected $license_href = "";
  protected $php_version = 5;
  protected $file_source = array();
  protected $file_documentation = array();
  protected $file_bin = array();
  protected $file_ignore = array();
  protected $maintainers = array();
  function __construct($row = array()) {
    foreach (get_object_vars($this) as $key => $value) {
      if (isset($row[$key])) {
        $this->$key = $row[$key];
      }
    }
  }
  function getArrayCopy() {
    $result = array();
    foreach (get_object_vars($this) as $key => $value) {
      if (is_scalar($value)) {
        $result[$key] = $value;
      }
    }
    return $result;
  }
  function name() {
    return $this->name;
  }
  function summary() {
    return $this->summary;
  }
  function licenseTitle() {
    return $this->license_title;
  }
  function licenseHref() {
    return $this->license_href;
  }
  function phpVersion() {
    return $this->php_version;
  }
  function sourceFiles() {
    return $this->file_source;
  }
  function documentationFiles() {
    return $this->file_documentation;
  }
  function binFiles() {
    return $this->file_bin;
  }
  function ignore() {
    return $this->file_ignore;
  }
  function maintainers() {
    return $this->maintainers;
  }
  function addSourceFile($file) {
    $this->file_source[] = $file;
    return $file;
  }
  function addDocumentationFile($file) {
    $this->file_documentation[] = $file;
    return $file;
  }
  function addBinFile($file) {
    $this->file_bin[] = $file;
    return $file;
  }
  function addIgnore($file) {
    $this->file_ignore[] = $file;
    return $file;
  }
  function addMaintainer(Maintainer $maintainer) {
    $maintainer->setProjectId($this->id());
    $this->maintainers[] = $maintainer;
    return $maintainer;
  }
}

class ManifestXmlParser {
  function parseString($xmlstr) {
    return $this->parse(new SimpleXMLElement($xmlstr));
  }
  function parseFile($path) {
    return $this->parse(simplexml_load_file($path));
  }
  function parse($doc) {
    $manifest = new Manifest(
      array_filter(
        array_map(
          'trim',
          array(
            'name' => $doc->package->name,
            'summary' => $doc->package->summary,
            'href' => $doc->package->href,
            'license_title' => $doc->package->license->title,
            'license_href' => $doc->package->license->href,
            'php_version' => $doc->package->{"php-version"},
          ))));
    foreach ($doc->maintainer as $m) {
      $manifest->addMaintainer(
        new Maintainer(
          array_filter(
            array_map(
              'trim',
              array(
                'type' => $m->type,
                'user' => $m->user,
                'name' => $m->name,
                'email' => $m->email,
              )))));
    }
    foreach ($doc->files->source as $s) {
      $manifest->addSourceFile(trim($s));
    }
    foreach ($doc->files->documentation as $d) {
      $manifest->addDocumentationFile(trim($d));
    }
    foreach ($doc->files->bin as $b) {
      $manifest->addBinFile(trim($b));
    }
    foreach ($doc->files->ignore as $i) {
      $manifest->addIgnore(trim($i));
    }
    return $manifest;
  }
}

/*
  $m = new Maintainer(
  array(
  'type' => 'lead',
  'user' => 'troelskn',
  'name' => 'Troels Knak-Nielsen',
  'email' => 'troelskn@gmail.com',
  ));
  var_dump($m);
*/

/*
  $p = new XmlParser();
  var_dump($p->parseString(file_get_contents('MANIFEST.xml')));
*/

/*
  $p = new XmlParser();
  var_dump($p->parseFile('MANIFEST.xml'));
*/
