#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/../../config/global.inc.php');
$container = create_container();
$db = $container->create('PDO');
$db->exec(
    '
create table manifests (
  id serial,
  name varchar(64) not null,
  summary text,
  href varchar(255),
  license_title varchar(255),
  license_href varchar(255),
  php_version varchar(10),
  index (name)
)
'
);
$db->exec(
    '
create table maintainers (
  id serial,
  manifest_id bigint not null,
  type varchar(64) not null,
  user varchar(64),
  name varchar(255),
  email varchar(255),
  index (manifest_id),
  foreign key (manifest_id) references manifests(id)
)
'
);
