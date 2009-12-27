#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/../../config/global.inc.php');
$container = create_container();
$db = $container->create('PDO');
$db->exec('drop table if exists file_ignores');
$db->exec('drop table if exists filespecs');
$db->exec('drop table if exists files');
$db->exec(
    '
create table files (
  project_id bigint not null,
  path varchar(255) not null,
  destination varchar(255) not null default "/",
  `ignore` varchar(255),
  primary key (project_id, path),
  index (project_id),
  foreign key (project_id) references projects(id)
)
'
);
