#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/../../config/global.inc.php');
$container = create_container();
$db = $container->create('PDO');
$db->exec('drop table if exists projects');
$db->exec('create table projects (
  id serial,
  name varchar(255) not null,
  owner varchar(255) not null,
  created datetime not null,
  repository varchar(255) not null,
  summary text,
  href varchar(255),
  license_title varchar(255),
  license_href varchar(255),
  php_version varchar(10),
  index (name),
  unique (name)
)
');
$db->exec('drop table if exists maintainers');
$db->exec(
    '
create table maintainers (
  user varchar(64) not null primary key,
  name varchar(255),
  email varchar(255),
  owner varchar(255) not null
)
'
);
$db->exec('drop table if exists project_maintainers');
$db->exec(
    '
create table project_maintainers (
  project_id bigint not null,
  user varchar(64) not null,
  type varchar(64) not null,
  primary key (project_id, user),
  index (project_id),
  index (user),
  foreign key (project_id) references projects(id),
  foreign key (user) references maintainers(user)
)
'
);
$db->exec('drop table if exists filespecs');
$db->exec(
    '
create table filespecs (
  project_id bigint not null,
  path varchar(255) not null,
  type enum("src", "doc", "bin") not null default "src",
  primary key (project_id, path),
  index (project_id),
  foreign key (project_id) references projects(id)
)
'
);
$db->exec('drop table if exists file_ignores');
$db->exec(
    '
create table file_ignores (
  project_id bigint not null,
  pattern varchar(255) not null primary key,
  index (project_id),
  foreign key (project_id) references projects(id)
)
'
);
