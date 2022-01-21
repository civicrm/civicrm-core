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
 * Upgrade logic for FiveFortyFive
 */
class CRM_Upgrade_Incremental_php_FiveFortyFive extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_45_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add entity_modified_date column to civicrm_managed', 'addColumn',
      'civicrm_managed', 'entity_modified_date', "timestamp NULL DEFAULT NULL COMMENT 'When the managed entity was changed from its original settings.'"
    );
    $this->addTask('Update currency symbols for Ghana', 'updateCurrencyName', 'GHC', 'GHS');
    $this->addTask('Update currency symbols for Belarus', 'updateCurrencyName', 'BYR', 'BYN');
  }

  public function upgrade_5_45_2($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Install search kit if afform-admin is installed', 'installDependentExtension');
  }

  /**
   * @param CRM_Queue_TaskContext $ctx
   */
  public function installDependentExtension(CRM_Queue_TaskContext $ctx) {
    $is_form_builder_active = (bool) CRM_Core_DAO::singleValueQuery("SELECT is_active FROM civicrm_extension WHERE full_name = 'org.civicrm.afform_admin'");
    if ($is_form_builder_active) {
      $is_search_kit_active = (bool) CRM_Core_DAO::singleValueQuery("SELECT is_active FROM civicrm_extension WHERE full_name = 'org.civicrm.search_kit'");
      if (!$is_search_kit_active) {
        // Would like to avoid api but otherwise we need to create tables etc ourselves.
        civicrm_api3('Extension', 'install', ['keys' => 'org.civicrm.search_kit']);
      }
    }
    return TRUE;
  }

}
