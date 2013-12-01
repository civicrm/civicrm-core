<?php

/**
 * Implemented by CG tasks
 */
interface CRM_Core_CodeGen_ITask {
  /**
   * Make configuration object available to the task.
   *
   * @param $config is currently the CRM_Core_CodeGen_Main object.
   */
  function setConfig($config);

  /**
   * Perform the task.
   */
  function run();
}
