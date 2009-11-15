<?php
/**
  * Provides class dependency wiring for this application
  */
class ApplicationFactory {
  public $template_dir;
  public $pdo_dsn;
  public $pdo_username;
  public $pdo_password;
  function new_pdoext_Connection($c) {
    return new pdoext_Connection($this->pdo_dsn, $this->pdo_username, $this->pdo_password);
  }
  function new_k_TemplateFactory($c) {
    return new k_DefaultTemplateFactory($this->template_dir);
  }
  function new_Zend_Auth($c) {
    return Zend_Auth::getInstance();
  }
}
