#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/../../config/global.inc.php');
$container = create_container();
$db = $container->create('PDO');
$db->exec('drop table if exists authentication_cookies');
$db->exec('create table authentication_cookies (
  openid_identifier varchar(255) not null primary key,
  hash varchar(255) not null,
  user_agent varchar(255) not null,
  created datetime not null,
  unique(hash),
  index(hash)
)
');
