<?php

/**
 * Implemented by CG tasks
 */
interface CRM_Core_CodeGen_ITask {
  /**
   * Make configuration object available to the task.
   *
   * @param $config
   *   Is currently the CRM_Core_CodeGen_Main object.
   */
  public function setConfig($config);

  /**
   * Perform the task.
   */
  public function run();

}
