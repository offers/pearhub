<?php
// Put default application configuration in this file.
// Individual sites (servers) can override it.
require_once 'applicationfactory.php';
require_once 'bucket.inc.php';
date_default_timezone_set('Europe/Paris');

$debug_log_path = dirname(dirname(__FILE__)).'/log/debug.log';
$debug_enabled = true;

function create_container() {
  $factory = new ApplicationFactory();
  $container = new bucket_Container($factory);
  $factory->template_dir = realpath(dirname(__FILE__) . '/../templates');
  $factory->pdo_dsn = 'mysql:host=localhost;dbname=pearhub';
  $factory->pdo_username = 'root';
  $container->registerImplementation('k_DefaultNotAuthorizedComponent', 'NotAuthorizedComponent');
  $container->registerImplementation('k_IdentityLoader', 'k_SessionIdentityLoader');
  return $container;
}
