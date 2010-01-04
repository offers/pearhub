#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/../../config/global.inc.php');
$container = create_container();
$db = $container->create('PDO');
$db->exec(
    '
alter table projects add column description text default NULL after summary
'
);
$db->exec(
    '
alter table projects change column summary summary varchar(200) default NULL
'
);


