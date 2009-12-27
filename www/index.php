<?php
require_once(dirname(__FILE__) . '/../config/global.inc.php');
require_once('document.php');
$container = create_container();
k()
  // Use container for wiring of components
->setComponentCreator(new k_InjectorAdapter($container, new krudt_Document()))
  ->setIdentityLoader($container->get('k_IdentityLoader'))
  // Location of debug logging
  ->setLog($debug_log_path)
  // Enable/disable in-browser debugging
  ->setDebug($debug_enabled)
  // Dispatch request
  ->run('components_Root')
  ->out();
