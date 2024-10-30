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
 * Upgrade logic for FiveThirtySeven
 */
class CRM_Upgrade_Incremental_php_FiveThirtySeven extends CRM_Upgrade_Incremental_Base {

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
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    // Example: Generate a pre-upgrade message.
    // if ($rev == '5.12.34') {
    //   $preUpgradeMessage .= '<p>' . ts('A new permission, "%1", has been added. This permission is now used to control access to the Manage Tags screen.', array(1 => ts('manage tags'))) . '</p>';
    // }
    if ($rev === '5.37.alpha1') {
      $preUpgradeMessage .= '<p>' . ts('Some mail-merge tokens may display differently when used with Scheduled Reminders, Mosaico templates, or PDF letters. For details, see <a href="%1" target="_blank">upgrade notes</a>.',
          [1 => 'https://docs.civicrm.org/sysadmin/en/latest/upgrade/version-specific/#token-format']) . '</p>';
    }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_37_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('dev/core#1845 - Alter Foreign key on civicrm_group to delete when the associated group when the saved search is deleted', 'alterSavedSearchFK');
    $this->addTask('dev/core#2243 - Add note_date to civicrm_note', 'addColumn',
     'civicrm_note', 'note_date', "timestamp NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'Date attached to the note'");
    $this->addTask('dev/core#2243 - Add created_date to civicrm_note', 'addColumn',
     'civicrm_note', 'created_date', "timestamp NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'When the note was created'");
    $this->addTask('dev/core#2243 - Update existing note_date and created_date', 'updateNoteDates');
    $this->addTask('dev/core#2487 Add / alter defaults for civicrm_contribution_recur', 'updateDBDefaultsForContributionRecur');
    $this->addTask('Install reCAPTCHA extension', 'installReCaptchaExtension');
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function updateNoteDates(CRM_Queue_TaskContext $ctx): bool {
    CRM_Core_DAO::executeQuery("UPDATE civicrm_note SET note_date = modified_date, created_date = modified_date, modified_date = modified_date");
    return TRUE;
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function alterSavedSearchFK(CRM_Queue_TaskContext $ctx) {
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_group', 'FK_civicrm_group_saved_search_id');
    CRM_Core_DAO::executeQuery('DELETE civicrm_saved_search FROM civicrm_saved_search LEFT JOIN civicrm_group ON civicrm_saved_search.id = civicrm_group.saved_search_id WHERE civicrm_group.id IS NULL AND form_values IS NOT NULL and api_params IS NULL');
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_group ADD CONSTRAINT `FK_civicrm_group_saved_search_id` FOREIGN KEY (`saved_search_id`) REFERENCES `civicrm_saved_search`(`id`) ON DELETE CASCADE', [], TRUE, NULL, FALSE, FALSE);
    return TRUE;
  }

  /**
   * Update DB defaults for contribution recur.
   *
   * This adds default values for start_date, create_date, modified_date
   * and frequency_interval in line with what is in the UI (frequency_unit
   * already has 'month' as the default.
   *
   * The default of 'Pending' for contribution_recur_id will be updated as
   * appropriate as soon as a contribution is attached to it by BAO code.
   *
   * The core code does not rely on the defaults for any of these fields.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function updateDBDefaultsForContributionRecur(CRM_Queue_TaskContext $ctx): bool {
    $pendingID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionRecur', 'contribution_status_id', 'Pending');
    CRM_Core_DAO::executeQuery("UPDATE `civicrm_contribution_recur` SET `modified_date` = CURRENT_TIMESTAMP() WHERE `modified_date` IS NULL");
    CRM_Core_DAO::executeQuery("
      ALTER TABLE `civicrm_contribution_recur`
      MODIFY COLUMN `start_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'The date the first scheduled recurring contribution occurs.',
      MODIFY COLUMN `create_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this recurring contribution record was created.',
      MODIFY COLUMN `modified_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Last updated date for this record. mostly the last time a payment was received',
      MODIFY COLUMN `contribution_status_id` int(10) unsigned DEFAULT {$pendingID},
      MODIFY COLUMN `frequency_interval` int(10) unsigned NOT NULL DEFAULT 1 COMMENT 'Number of time units for recurrence of payment.';
    ");
    return TRUE;
  }

  /**
   * Install recaptcha extension.
   *
   * This feature is restructured as a core extension - which will eventually allow us to replace/remove the
   * reCAPTCHA implementation
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function installReCaptchaExtension(CRM_Queue_TaskContext $ctx) {
    // Install via direct SQL manipulation. Note that:
    // (1) This extension has no activation logic.
    // (2) On new installs, the extension is activated purely via default SQL INSERT.
    // (3) Caches are flushed at the end of the upgrade.
    // ($) Over long term, upgrade steps are more reliable in SQL. API/BAO sometimes don't work mid-upgrade.
    $insert = CRM_Utils_SQL_Insert::into('civicrm_extension')->row([
      'type' => 'module',
      'full_name' => 'recaptcha',
      'name' => 'reCAPTCHA',
      'label' => 'reCAPTCHA',
      'file' => 'recaptcha',
      'schema_version' => NULL,
      'is_active' => 1,
    ]);
    CRM_Core_DAO::executeQuery($insert->usingReplace()->toSQL());

    CRM_Core_DAO::executeQuery('
UPDATE civicrm_navigation
SET name="misc_admin_settings"
WHERE name="Misc (Undelete, PDFs, Limits, Logging, Captcha, etc.)"
');
    CRM_Core_DAO::executeQuery('
UPDATE civicrm_navigation
SET label="Misc (Undelete, PDFs, Limits, Logging, etc.)"
WHERE label="Misc (Undelete, PDFs, Limits, Logging, Captcha, etc.)"
');

    return TRUE;
  }

}
