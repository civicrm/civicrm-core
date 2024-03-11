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
 * Upgrade logic for the 5.72.x series.
 *
 * Each minor version in the series is handled by either a `5.72.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_72_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSeventyTwo extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_72_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Remove localized suffixes from civicrm_mailing_group.entity_table', 'fixMailingGroupEntityTable');
    $this->addTask('Replace displayName smarty token in UFNotify subject',
      'updateMessageToken', 'uf_notify', 'ts 1=$displayName', 'ts 1=$userDisplayName', $rev
    );
    $this->addTask('Replace displayName smarty token in UFNotify',
      'updateMessageToken', 'uf_notify', '$displayName', 'contact.display_name', $rev
    );
    $this->addTask('Replace currentDate smarty token in UFNotify',
      'updateMessageToken', 'uf_notify', '$currentDate', 'domain.now|crmDate:"Full"', $rev
    );
    $this->addTask('Add last_run_end column to Job table', 'addColumn', 'civicrm_job', 'last_run_end',
      'timestamp NULL DEFAULT NULL COMMENT "When did this cron entry last finish running"');
  }

  /**
   * Remove unwanted dbLocale suffixes from values in civicrm_mailing_group.entity_table.
   *
   * @see https://github.com/civicrm/civicrm-core/pull/29366
   *
   * @return bool
   */
  public static function fixMailingGroupEntityTable(): bool {
    $updateQuery = 'UPDATE civicrm_mailing_group SET entity_table = "civicrm_mailing" WHERE entity_table LIKE "civicrm_mailing_%"';
    CRM_Core_DAO::executeQuery($updateQuery, [], TRUE, NULL, FALSE, FALSE);
    $updateQuery = 'UPDATE civicrm_mailing_group SET entity_table = "civicrm_group" WHERE entity_table LIKE "civicrm_group_%"';
    CRM_Core_DAO::executeQuery($updateQuery, [], TRUE, NULL, FALSE, FALSE);
    return TRUE;
  }

}
