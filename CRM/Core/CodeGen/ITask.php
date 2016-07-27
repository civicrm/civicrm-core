<?php

/**
 * Implemented by CG tasks
 */
interface CRM_Core_CodeGen_ITask {

  /**
   * Perform the task.
   */
  public function run();

  /**
   * @return bool
   *   TRUE if an update is needed.
   */
  public function needsUpdate();

}
