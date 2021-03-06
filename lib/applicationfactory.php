<?php
/**
  * Provides class dependency wiring for this application
  */
class ApplicationFactory {
  public $template_dir;
  public $pdo_dsn;
  public $pdo_username;
  public $pdo_password;
  public $pdo_log_target;
  public $temp_dir;
  public $package_dir;
  public $pirum_channel_dir;
  public $channel;
  function new_pdoext_Connection($c) {
    $conn = new pdoext_Connection($this->pdo_dsn, $this->pdo_username, $this->pdo_password);
    if ($this->pdo_log_target) {
      $conn->setLogTarget($this->pdo_log_target);
    }
    return $conn;
  }
  function new_k_TemplateFactory($c) {
    return new k_DefaultTemplateFactory($this->template_dir);
  }
  function new_Zend_Auth($c) {
    return Zend_Auth::getInstance();
  }
  function new_Shell($c) {
    $shell = new Shell();
    if ($this->temp_dir) {
      $shell->temp_dir = $this->temp_dir;
    }
    return $shell;
  }
  function new_PackageBuilder($c) {
    $builder = new PackageBuilder($c->get('Shell'), $this->package_dir);
	$builder->setChannel($this->channel);
	return $builder;
  }
  function new_Pearhub_PirumBuilder($c) {
    return new Pearhub_PirumBuilder(
      $this->pirum_channel_dir,
      new Pirum_CLI_Formatter());
  }

}
