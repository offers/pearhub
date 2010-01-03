#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/../../config/global.inc.php');
$container = create_container();
$db = $container->create('PDO');
$db->exec('drop table if exists releases');
$db->exec('
create table releases (
  project_id bigint not null,
  version varchar(64) not null,
  status enum("building","completed","failed") not null default "building",
  created datetime not null,
  mode enum("auto","manual") not null default "auto",
  primary key (project_id, version),
  index (project_id),
  index (version),
  foreign key (project_id) references projects(id)
)
');
