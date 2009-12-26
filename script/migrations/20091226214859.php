#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/../../config/global.inc.php');
$container = create_container();
$db = $container->create('PDO');
$db->exec('drop table if exists dependencies');
$db->exec('
create table dependencies (
  project_id bigint not null,
  channel varchar(255) not null,
  version varchar(64),
  primary key (project_id, channel),
  index (project_id),
  foreign key (project_id) references projects(id)
)

');

