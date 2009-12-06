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
}
