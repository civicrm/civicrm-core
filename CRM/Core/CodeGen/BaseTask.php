<?php

/**
 * Class CRM_Core_CodeGen_BaseTask
 */
abstract class CRM_Core_CodeGen_BaseTask implements CRM_Core_CodeGen_ITask {
  /**
   * @var CRM_Core_CodeGen_Main
   */
  protected $config;

  protected $tables;

  /**
   * @param CRM_Core_CodeGen_Main $config
   */
  public function __construct($config) {
    $this->setConfig($config);
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
