#!/usr/bin/env php
<?php
set_include_path(
  PATH_SEPARATOR.get_include_path()
  .PATH_SEPARATOR.dirname(dirname(__FILE__))."/thirdparty/krudt/lib");

require_once 'generators/generatecomponents.php';

$command = new generators_GenerateComponents(dirname(dirname(__FILE__))."/thirdparty/krudt/resources");
$command->run();
