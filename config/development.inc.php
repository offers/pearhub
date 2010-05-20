<?php
// Put default application configuration in this file.
// Individual sites (servers) can override it.
require_once 'applicationfactory.php';
require_once 'bucket.inc.php';
date_default_timezone_set('Europe/Paris');

//$debug_log_path = dirname(dirname(__FILE__)).'/log/debug.log';
//$sql_log_path = dirname(dirname(__FILE__)).'/log/pdoext.log';
//$debug_enabled = true;

function create_container() {
  $factory = new ApplicationFactory();
  $container = new bucket_Container($factory);
  $factory->template_dir = realpath(dirname(__FILE__) . '/../templates');
  $factory->pdo_dsn = 'mysql:host=localhost;dbname=pearhub';
  $factory->pdo_username = 'root';
  $factory->pdo_password = 'password';
  if (isset($GLOBALS['sql_log_path'])) {
    $factory->pdo_log_target = $GLOBALS['sql_log_path'];
  }
  $factory->temp_dir = realpath(dirname(__FILE__) . '/../var/tmp');
  $factory->package_dir = realpath(dirname(__FILE__) . '/../var/channel/get');
  $factory->pirum_channel_dir = dirname(__FILE__).'/../var/channel';
  $container->registerImplementation('PDO', 'pdoext_Connection');
  $container->registerImplementation('k_DefaultNotAuthorizedComponent', 'NotAuthorizedComponent');
  $container->registerImplementation('k_IdentityLoader', 'CookieIdentityLoader');
  return $container;
}
