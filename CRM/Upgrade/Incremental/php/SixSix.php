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
 * Upgrade logic for the 6.6.x series.
 *
 * Each minor version in the series is handled by either a `6.6.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_6_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixSix extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_6_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Create TranslationSource table', 'createEntityTable', '6.6.alpha1.TranslationSource.entityType.php');
    $this->addExtensionTask('Enable Legacy Batch Entry extension', ['legacybatchentry']);
    $this->addTask('Update localization menu item', 'updateLocalizationMenuItem');
  }

  public static function updateLocalizationMenuItem(): bool {
    CRM_Core_DAO::executeQuery("UPDATE civicrm_navigation SET has_separator = 1 WHERE name = 'Preferred Language Options'");
    return TRUE;
  }

}
