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
 * Upgrade logic for the 6.22.x series.
 *
 * Each minor version in the series is handled by either a `6.22.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_22_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixTwentyTwo extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_22_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Set "group_multiple_parents" setting if used', 'setMultipleGroupParents');
    $this->addTask(ts('Create index %1', [1 => 'civicrm_mailing_recipients.index_mailing_id_contact_id']), 'addIndex', 'civicrm_mailing_recipients', [['mailing_id', 'contact_id']], 'index');
    // The cache clear no longer TRUNCATEs civicrm_prevnext_cache, so its AUTO_INCREMENT is no longer
    // reset and an int id could run out on busy sites. Empty the cache first so the ALTER is instant.
    $this->addTask('Empty civicrm_prevnext_cache', 'emptyPrevNextCache');
    $this->addTask('Change PrevNextCache.id to bigint', 'alterSchemaField', 'PrevNextCache', 'id', [
      'title' => ts('Prev Next Cache ID'),
      'sql_type' => 'bigint unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'add' => '3.4',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ]);
  }

  /**
   * Empty the prev/next cache, so changing its id column does not copy rows.
   *
   * It only holds cached search results, which are rebuilt on the next search.
   *
   * @return bool
   */
  public static function emptyPrevNextCache(): bool {
    CRM_Core_DAO::executeQuery('TRUNCATE TABLE civicrm_prevnext_cache');
    return TRUE;
  }

  /**
   * Set "group_multiple_parents" setting if used.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function setMultipleGroupParents(): bool {
    $sql = "SELECT COUNT(*)
      FROM `civicrm_group`
      WHERE `parents` LIKE '%,%'";
    $groupFoundWithMultipleParents = (bool) CRM_Core_DAO::singleValueQuery($sql);
    Civi::settings()->set('group_multiple_parents', $groupFoundWithMultipleParents);
    return TRUE;
  }

}
