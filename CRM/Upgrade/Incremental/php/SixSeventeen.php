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
 * Upgrade logic for the 6.17.x series.
 *
 * Each minor version in the series is handled by either a `6.17.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_17_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixSeventeen extends CRM_Upgrade_Incremental_Base {

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev === '6.17.alpha1') {
      if (Civi::settings()->get('search_mysql_fts')) {
        $settingUrl = (string) \Civi::url('civicrm/admin/setting/search', 'h')->addQuery(['reset' => 1]);
        $preUpgradeMessage .= '<p>' . ts("This upgrade will add a new Full Text Search index on the `civicrm_contact` table. If you have lots of contacts, this may take a while and use a lot of space on your database server. If you don't want this, turn off Use Mysql Full Text Search in <a %1>Search Preferences</a> before running the upgrade.", [1 => ('href="' . $settingUrl . '"')]) . '</p>';
      }
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_17_alpha1($rev): void {
    $this->addTask(ts('Add relationship type weight column'), 'addRelationshipTypeWeight');
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask(ts('Create Mysql Full Text Search indices if active'), 'createMissingFtsIndices');
  }

  /**
   * Add weight to relationship types.
   *
   * @param CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function addRelationshipTypeWeight(CRM_Queue_TaskContext $ctx): bool {
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_relationship_type', 'weight')) {
      CRM_Core_DAO::executeQuery("
        ALTER TABLE civicrm_relationship_type
        ADD COLUMN weight int unsigned NOT NULL DEFAULT 0
          COMMENT 'Ordering of the relationship types.'
          AFTER is_active
      ");
    }

    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_relationship_type
      SET weight = id
      WHERE weight = 0
    ");

    return TRUE;
  }

}
