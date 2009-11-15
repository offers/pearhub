#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/../../config/global.inc.php');
$container = create_container();
$db = $container->create('PDO');
$db->exec('drop table if exists projects');
$db->exec('drop table if exists manifests');
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
  index (name)
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
