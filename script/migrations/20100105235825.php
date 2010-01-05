#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/../../config/global.inc.php');
$container = create_container();
$db = $container->create('PDO');
$db->exec(
    '
create table repository_types (
  url varchar(255) not null,
  type varchar(20) not null,
  primary key (url, type),
  index (url, type)
)
'
);
