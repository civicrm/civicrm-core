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
    $this->addTask('Add foreign keys from A/B tests to their mailings', 'addMailingABForeignKeys');
  }

  /**
   * Add FKs from Mailing A/B tests to Mailings, so a deleted mailing doesn't
   * break the A/B test report.
   *
   * First null any existing references to deleted mailings, then add the FKs.
   *
   * @return bool
   */
  public static function addMailingABForeignKeys(): bool {
    // Adding an FK copies the table, which strict SQL modes refuse if any row
    // holds a zero date (see dev/core#6701).
    $sqlModes = CRM_Utils_SQL::getSqlModes();
    CRM_Utils_SQL::setSqlModes(array_diff($sqlModes, ['NO_ZERO_DATE', 'NO_ZERO_IN_DATE']));
    try {
      foreach (['mailing_id_a', 'mailing_id_b', 'mailing_id_c'] as $fieldName) {
        CRM_Core_DAO::executeQuery("
          UPDATE `civicrm_mailing_abtest` ab
          LEFT JOIN `civicrm_mailing` m ON m.`id` = ab.`$fieldName`
          SET ab.`$fieldName` = NULL
          WHERE ab.`$fieldName` IS NOT NULL AND m.`id` IS NULL
        ", i18nRewrite: FALSE);
        Civi::schemaHelper()->createForeignKey('civicrm_mailing_abtest', $fieldName, [
          'entity_reference' => [
            'entity' => 'Mailing',
            'key' => 'id',
            'on_delete' => 'SET NULL',
          ],
        ]);
      }
    }
    finally {
      CRM_Utils_SQL::setSqlModes($sqlModes);
    }
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
