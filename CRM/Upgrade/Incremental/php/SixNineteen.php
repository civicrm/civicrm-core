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
 * Upgrade logic for the 6.19.x series.
 *
 * Each minor version in the series is handled by either a `6.19.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_19_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixNineteen extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_19_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Drop OptionValue.domain_id column', 'dropColumn', 'civicrm_option_value', 'domain_id');
    $this->addTask('Update Localization menu label', 'updateLocalizationMenuLabels');
    $this->addTask('Update UFGroup.is_cms_user', 'alterSchemaField', 'UFGroup', 'is_cms_user', [
      'title' => ts('User account registration'),
      'sql_type' => 'tinyint',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Should we create a cms user for this profile'),
      'add' => '1.8',
      'default' => 0,
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'profileUserRegistrationMode'],
      ],
    ]);
  }

  /**
   * Rename Localization > 'Date Formats' to 'Date and Time'
   * And 'Languages, Currency, Locations' to 'Language and Region'
   */
  public static function updateLocalizationMenuLabels(CRM_Queue_TaskContext $ctx): bool {
    $changes = [
      'Date Formats' => 'Date and Time',
      'Languages, Currency, Locations' => 'Language and Region',
    ];
    foreach ($changes as $old => $new) {
      CRM_Core_DAO::executeQuery('UPDATE civicrm_navigation SET label = %1 WHERE name = %2 AND label = %3', [
        1 => [$new, 'String'],
        2 => [$old, 'String'],
        3 => [$old, 'String'],
      ]);
    }
    return TRUE;
  }

}
