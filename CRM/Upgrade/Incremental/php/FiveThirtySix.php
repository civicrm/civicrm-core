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
 * Upgrade logic for FiveThirtySix
 */
class CRM_Upgrade_Incremental_php_FiveThirtySix extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed beforeupgrade.
   *
   * Note: This function is called iteratively for each upcoming
   * revision to the database.
   *
   * @param string $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'.
   * @param null $currentVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    // Example: Generate a pre-upgrade message.
    // if ($rev == '5.12.34') {
    //   $preUpgradeMessage .= '<p>' . ts('A new permission, "%1", has been added. This permission is now used to control access to the Manage Tags screen.', array(1 => ts('manage tags'))) . '</p>';
    // }
    if ($rev === '5.36.alpha1') {
      if (empty(CRM_Utils_Constant::value('CIVICRM_SIGN_KEYS'))) {
        // NOTE: We don't re-encrypt automatically because the old "civicrm.settings.php" lacks a good key, and we don't keep the old encryption because the format is ambiguous.
        // The admin may forget to re-enable. That's OK -- this only affects 1 field, this is a secondary defense, and (in the future) we can remind the admin via status-checks.
        $preUpgradeMessage .= '<p>' . ts('CiviCRM v5.36 introduces a new configuration option to support digital signatures. You may <a href="%1" target="_blank">setup CIVICRM_SIGN_KEYS</a> before or after upgrading. The option is not critical in v5.36, but it may be required for extensions or future upgrades.', [
          1 => 'https://docs.civicrm.org/sysadmin/en/latest/upgrade/version-specific/#sign-key',
        ]) . '</p>';
      }
    }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_36_alpha1(string $rev): void {
    // Not used // $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);

    $this->addTask('dev/core#2422 - Add created_id to civicrm_saved_search', 'addColumn',
      'civicrm_saved_search', 'created_id', "int(10) unsigned DEFAULT NULL COMMENT 'FK to contact table.'");
    $this->addTask('dev/core#2422 - Add modified_id to civicrm_saved_search', 'addColumn',
      'civicrm_saved_search', 'modified_id', "int(10) unsigned DEFAULT NULL COMMENT 'FK to contact table.'");
    $this->addTask('dev/core#2422 - Add expires_date to civicrm_saved_search', 'addColumn',
      'civicrm_saved_search', 'expires_date', "timestamp NULL DEFAULT NULL COMMENT 'Optional date after which the search is not needed'");
    $this->addTask('dev/core#2422 - Add created_date to civicrm_saved_search', 'addColumn',
      'civicrm_saved_search', 'created_date', "timestamp NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'When the search was created.'");
    $this->addTask('dev/core#2422 - Add modified_date to civicrm_saved_search', 'addColumn',
      'civicrm_saved_search', 'modified_date', "timestamp NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When the search was last modified.'");
    $this->addTask('dev/core#2422 - Add description to civicrm_saved_search', 'addColumn',
      'civicrm_saved_search', 'description', "text DEFAULT NULL");

    $this->addTask('dev/core#2422 - Add constraints to civicrm_saved_search', 'taskAddConstraints');
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function taskAddConstraints(CRM_Queue_TaskContext $ctx): bool {
    if (!self::checkFKExists('civicrm_saved_search', 'FK_civicrm_saved_search_created_id')) {
      CRM_Core_DAO::executeQuery("
        ALTER TABLE `civicrm_saved_search`
          ADD CONSTRAINT `FK_civicrm_saved_search_created_id`
            FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact` (`id`)
            ON DELETE SET NULL;
      ");
    }

    if (!self::checkFKExists('civicrm_saved_search', 'FK_civicrm_saved_search_modified_id')) {
      CRM_Core_DAO::executeQuery("
        ALTER TABLE `civicrm_saved_search`
          ADD CONSTRAINT `FK_civicrm_saved_search_modified_id`
            FOREIGN KEY (`modified_id`) REFERENCES `civicrm_contact` (`id`)
            ON DELETE SET NULL;
      ");
    }

    return TRUE;
  }

}
