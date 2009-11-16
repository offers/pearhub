#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/../../config/global.inc.php');
$container = create_container();
$db = $container->create('PDO');
$db->exec('drop table if exists projects');
$db->exec('CREATE TABLE projects (
  id SERIAL,
  name varchar(255) NOT NULL,
  owner varchar(255) NOT NULL,
  created datetime NOT NULL,
  repository varchar(255) NOT NULL,
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
  id serial,
  project_id bigint not null,
  type varchar(64) not null,
  user varchar(64),
  name varchar(255),
  email varchar(255),
  index (project_id),
  foreign key (project_id) references projects(id)
)
'
);
$db->exec('drop table if exists filespecs');
$db->exec(
    '
create table filespecs (
  project_id bigint not null,
  path varchar(255) not null primary key,
  type enum("src", "doc", "bin") not null default "src",
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
