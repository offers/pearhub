#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/../../config/global.inc.php');
$container = create_container();
$db = $container->create('PDO');
$db->exec(
  '
alter table projects add column path varchar(255) NOT NULL
'
);
$db->exec(
  '
alter table projects add column destination varchar(255) NOT NULL default "/"
'
);
$db->exec(
  '
alter table projects add column `ignore` varchar(255) default NULL
'
);
foreach ($db->pexecute('select * from files') as $row) {
  $db->pexecute(
    'update projects set path = :path, destination = :destination, `ignore` = :ignore where id = :project_id',
    array(
      ':path' => $row['path'],
      ':destination' => $row['destination'],
      ':ignore' => $row['ignore'],
      ':project_id' => $row['project_id']));
}
$db->exec('drop table files');
