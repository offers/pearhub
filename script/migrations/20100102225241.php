#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/../../config/global.inc.php');
$container = create_container();
$container = create_container();
$db = $container->create('PDO');
$db->exec(
    '
alter table projects add column release_policy enum("manual", "auto") not null default "auto" after php_version
'
);

