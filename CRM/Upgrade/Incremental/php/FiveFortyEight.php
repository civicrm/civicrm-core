<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Upgrade logic for the 5.48.x series.
 *
 * Each minor version in the series is handled by either a `5.48.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_48_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFortyEight extends CRM_Upgrade_Incremental_Base {

  use CRM_Upgrade_Incremental_php_TimezoneRevertTrait;

  /**
   * Compute any messages which should be displayed beforeupgrade.
   *
   * Note: This function is called iteratively for each incremental upgrade step.
   * There must be a concrete step (eg 'X.Y.Z.mysql.tpl' or 'upgrade_X_Y_Z()').
   *
   * @param string $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'.
   * @param null $currentVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL): void {
    if ($rev === '5.48.beta2') {
      $preUpgradeMessage .= $this->createEventTzPreUpgradeMessage();
    }
  }

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * Note: This function is called iteratively for each incremental upgrade step.
   * There must be a concrete step (eg 'X.Y.Z.mysql.tpl' or 'upgrade_X_Y_Z()').
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev): void {
    if ($rev === '5.48.beta2') {
      $postUpgradeMessage .= $this->createEventTzPostUpgradeMessage();
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_48_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add "runner" to "civicrm_queue"', 'addColumn', 'civicrm_queue', 'runner',
      "varchar(64) NULL COMMENT 'Name of the task runner'"
    );
    $this->addTask('Convert "is_autorun" to "runner"', 'convertAutorun');
    $this->addTask('Drop "is_autorun" from "civicrm_queue"', 'dropColumn', 'civicrm_queue', 'is_autorun');
    $this->addTask('Add "batch_limit" to "civicrm_queue"', 'addColumn', 'civicrm_queue', 'batch_limit',
      "int unsigned NOT NULL DEFAULT 1 COMMENT 'Maximum number of items in a batch.'"
    );
    $this->addTask('Add "lease_time" to "civicrm_queue"', 'addColumn', 'civicrm_queue', 'lease_time',
      "int unsigned NOT NULL DEFAULT 3600 COMMENT 'When claiming an item (or batch of items) for work, how long should the item(s) be reserved. (Seconds)'"
    );
    $this->addTask('Add "retry_limit" to "civicrm_queue"', 'addColumn', 'civicrm_queue', 'retry_limit',
      "int NOT NULL DEFAULT 0 COMMENT 'Number of permitted retries. Set to zero (0) to disable.'"
    );
    $this->addTask('Add "retry_interval" to "civicrm_queue"', 'addColumn', 'civicrm_queue', 'retry_interval',
      "int NULL COMMENT 'Number of seconds to wait before retrying a failed execution.'"
    );
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_48_beta2($rev): void {
    // $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addEventTzTasks();
  }

  /**
   * The `is_autorun` column was introduced in 5.47,  but we didn't finish adding the
   * additional changes to use, so there shouldn't be any real usage. But just to be
   * paranoid, we'll convert to 5.48's `runner`.
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function convertAutorun(CRM_Queue_TaskContext $ctx) {
    CRM_Core_DAO::executeQuery('UPDATE civicrm_queue SET runner = "task" WHERE is_autorun = 1');
    return TRUE;
  }

}
