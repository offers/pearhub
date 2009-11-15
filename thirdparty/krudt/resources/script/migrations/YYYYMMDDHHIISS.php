#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/../../config/global.inc.php');
$container = create_container();
$db = $container->get('pdo');
$db->exec('drop table if exists contacts');
$db->exec('CREATE TABLE contacts (
  id SERIAL
)
');
