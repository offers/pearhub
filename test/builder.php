<?php
require_once(dirname(__FILE__) . '/../config/global.inc.php');
require_once 'repo.inc.php';
require_once 'builder.inc.php';
require_once 'projects.inc.php';

$db = new pdoext_Connection('mysql:host=localhost;dbname=pearhub', 'root');
$gateway = new ProjectGateway($db, new MaintainersGateway($db));
$project = $gateway->fetch(array('name' => 'konstrukt'));
$sh = new Shell();
$sh->temp_dir = $tmp_path;
$repo = new SvnStandardRepoInfo($project->repository(), $sh);
$tags = $repo->listTags();
$version = $tags[count($tags)-1];
$local_copy = $repo->exportTag($version);
echo $local_copy, "\n";

$compiler = new ManifestCompiler($project);
$files = new FileFinder($local_copy->getPath());
foreach ($project->files() as $file) {
  $files->traverse($file['path'], $file['ignore'], $file['destination']);
}

file_put_contents(
  $local_copy->getPath().'/package.xml',
  $compiler->build($files, $project, $version));
//echo $compiler->build($files, $project, $version);
//$local_copy->destroy($sh);
