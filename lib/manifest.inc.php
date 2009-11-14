<?php
require_once 'pdoext.inc.php';
require_once 'pdoext/connection.inc.php';
require_once 'pdoext/query.inc.php';
require_once 'pdoext/tablegateway.php';

class manifest_ManifestGateway extends pdoext_TableGateway {
    protected $sql_load_aggregates;
    protected $maintainers;
    function __construct(pdoext_Connection $db, manifest_MaintainersGateway $maintainers) {
        parent::__construct('manifests', $db);
        $this->maintainers = $maintainers;
    }
    function load($row = array()) {
        $m = new manifest_Manifests($row);
        if ($row['id']) {
            return $this->loadAggerates($m);
        }
        return $m;
    }
    function loadAggerates($manifest) {
        if (!$this->sql_load_aggregates) {
            $this->sql_load_aggregates = $this->db->prepare(
                '
SELECT
  manifests.id as manifest_id,
  maintainers.id as maintainer_id,
  maintainers.type,
  maintainers.user,
  maintainers.name,
  maintainers.email
FROM
  manifests
LEFT JOIN
  maintainers
ON manifests.id = maintainers.manifest_id
WHERE
  manifests.id = :id
'
            );
            $this->sql_load_aggregates->setFetchMode(PDO::FETCH_ASSOC);
        }
        foreach ($this->sql_load_aggregates->execute(array('id' => $manifest->id)) as $row) {
            if ($row['maintainer_id']) {
                $row['id'] = $row['maintainer_id'];
                $manifest->addMaintainer($this->maintainers->load($row));
            }
        }
        return $manifest;
    }
}

class manifest_MaintainersGateway extends pdoext_TableGateway {
    function __construct(pdoext_Connection $db) {
        parent::__construct('maintainers', $db);
    }
    function load($row = array()) {
        return new manifest_Maintainers($row);
    }
}

class SimpleEntity {
    protected $id = null;
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
    function id() {
        return $this->id;
    }
    function setId($id) {
        if ($id !== null) {
            throw new Exception("Can't change id");
        }
        return $this->id = $id;
    }
}

class manifest_Manifest extends SimpleEntity{
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
    function setId($id) {
        parent::setId($id);
        foreach ($this->maintainers as $maintainer) {
            $maintainer->setManifestId($id);
        }
    }
    function setName($name) {
        return $this->name = $name;
    }
    function setSummary($summary) {
        return $this->summary = $summary;
    }
    function setLicenseTitle($license_title) {
        return $this->license_title = $license_title;
    }
    function setLicenseHref($license_href) {
        return $this->license_href = $license_href;
    }
    function setPhpVersion($php_version) {
        return $this->php_version = $php_version;
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
    function addMaintainer(manifest_Maintainer $maintainer) {
        $maintainer->setManifestId($this->id());
        $this->maintainers[] = $maintainer;
        return $maintainer;
    }
}

class manifest_Maintainer extends SimpleEntity {
    protected $manifest_id;
    protected $type = "helper";
    protected $user = "";
    protected $name = "";
    protected $email = "";
    function manifestId() {
        return $this->manifest_id;
    }
    function type() {
        return $this->type;
    }
    function user() {
        return $this->user;
    }
    function name() {
        return $this->name;
    }
    function email() {
        return $this->email;
    }
    function setManifestId($id) {
        return $this->manifest_id = $id;
    }
    function setType($type) {
        if (!in_array($type, array('lead', 'helper'))) {
            throw new Exception("Illegal value for 'type'");
        }
        return $this->type = $type;
    }
    function setUser($user) {
        return $this->user = $user;
    }
    function setName($name) {
        return $this->name = $name;
    }
    function setEmail($email) {
        return $this->email = $email;
    }
}

class manifest_XmlParser {
    function parseString($xmlstr) {
        return $this->parse(new SimpleXMLElement($xmlstr));
    }
    function parseFile($path) {
        return $this->parse(simplexml_load_file($path));
    }
    function parse($doc) {
        $manifest = new manifest_Manifest(
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
                new manifest_Maintainer(
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
$m = new manifest_Maintainer(
    array(
        'type' => 'lead',
        'user' => 'troelskn',
        'name' => 'Troels Knak-Nielsen',
        'email' => 'troelskn@gmail.com',
    ));
var_dump($m);
*/

/*
$p = new manifest_XmlParser();
var_dump($p->parseString(file_get_contents('MANIFEST.xml')));
*/

/*
$p = new manifest_XmlParser();
var_dump($p->parseFile('MANIFEST.xml'));
*/
