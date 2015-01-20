<?php

/**
 * Class CRM_Core_CodeGen_BaseTask
 */
abstract class CRM_Core_CodeGen_BaseTask implements CRM_Core_CodeGen_ITask {
  protected $config;

  /**
   */
  public function __construct() {
  }

  /**
   * TODO: this is the most rudimentary possible hack.  CG config should
   * eventually be made into a first-class object.
   *
   * @param object $config
   */
  public function setConfig($config) {
    $this->config = $config;
    $this->tables = $this->config->tables;
  }

}
