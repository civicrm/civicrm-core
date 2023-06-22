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
 * Upgrade logic for the 5.64.x series.
 *
 * Each minor version in the series is handled by either a `5.64.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_64_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSixtyFour extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_64_alpha1($rev): void {
    $this->addTask('Add priority column onto ACL table', 'addColumn', 'civicrm_acl', 'priority', 'int NOT NULL DEFAULT 0');
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Update post_URL/cancel_URL in logging tables', 'updateLogging');
  }

  public static function updateLogging($ctx): bool {
    if (\Civi::settings()->get('logging')) {
      $dsn = defined('CIVICRM_LOGGING_DSN') ? CRM_Utils_SQL::autoSwitchDSN(CIVICRM_LOGGING_DSN) : CRM_Utils_SQL::autoSwitchDSN(CIVICRM_DSN);
      $dsn = DB::parseDSN($dsn);
      $table = '`' . $dsn['database'] . '`.`log_civicrm_uf_group`';
      CRM_Core_DAO::executeQuery("ALTER TABLE $table CHANGE `post_URL` `post_url` varchar(255) DEFAULT NULL COMMENT 'Redirect to URL on submit.',
CHANGE `cancel_URL` `cancel_url` varchar(255) DEFAULT NULL COMMENT 'Redirect to URL when Cancel button clicked.'");
    }
    return TRUE;
  }

}
