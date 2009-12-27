<?php

class krudt_Document extends k_Document {
  protected $crumbtrail = array();
  function crumbtrail() {
    return $this->crumbtrail;
  }
  function addCrumb($title, $url) {
    return $this->crumbtrail[] = array('title' => $title, 'url' => $url);
  }
}