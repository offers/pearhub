#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/../../config/global.inc.php');
$container = create_container();
$db = $container->create('PDO');
$db->exec(
  '
alter table projects add column repository_username varchar(255) default NULL
'
);
$db->exec(
  '
alter table projects add column repository_password varchar(255) default NULL
'
);
