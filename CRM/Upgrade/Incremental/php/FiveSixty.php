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
 * Upgrade logic for the 5.60.x series.
 *
 * Each minor version in the series is handled by either a `5.60.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_60_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSixty extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_60_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add scheduled_reminder_smarty setting', 'addScheduledReminderSmartySetting');
    $this->addTask('Add column civicrm_custom_field.fk_entity', 'addColumn', 'civicrm_custom_field', 'fk_entity', "varchar(255) DEFAULT NULL COMMENT 'Name of entity being referenced.'");
    $this->addTask('Add foreign key from civicrm_job_log to civicrm_job', 'addJobLogForeignKey');
  }

  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev): void {
    if ($rev === '5.60.alpha1') {
      $postUpgradeMessage .= '<p>' . ts('You can now choose whether to use Smarty in Scheduled Reminders at <em>Administer >> CiviMail >> CiviMail Component Settings</em>. The setting is disabled by default on new installations but we have enabled it during this upgrade to preserve the existing behavior. More information <a %1>in this lab ticket</a>.', [1 => 'href="https://lab.civicrm.org/dev/core/-/issues/4100" target="_blank"']) . '<p>';
    }
  }

  public static function addScheduledReminderSmartySetting(): bool {
    Civi::settings()->set('scheduled_reminder_smarty', TRUE);
    return TRUE;
  }

  /**
   * Add FK to civicrm_job_log.job_id
   *
   * @return bool
   */
  public static function addJobLogForeignKey(): bool {
    // Update the comment for the job_id column
    $commentQuery = 'ALTER TABLE civicrm_job_log MODIFY COLUMN `job_id` int(10) unsigned DEFAULT NULL COMMENT \'FK to civicrm_job.id\'';
    CRM_Core_DAO::executeQuery($commentQuery);

    // Set job_id = NULL for any that don't have matching jobs (ie. job was deleted).
    $updateQuery = 'UPDATE civicrm_job_log job_log LEFT JOIN civicrm_job job ON job.id = job_log.job_id SET job_id = NULL WHERE job.id IS NULL';
    CRM_Core_DAO::executeQuery($updateQuery);

    // Add the foreign key
    if (!CRM_Core_BAO_SchemaHandler::checkFKExists('civicrm_job_log', 'FK_civicrm_job_log_job_id')) {
      $sql = CRM_Core_BAO_SchemaHandler::buildForeignKeySQL([
        'fk_table_name' => 'civicrm_job',
        'fk_field_name' => 'id',
        'name' => 'job_id',
        'fk_attributes' => ' ON DELETE SET NULL',
      ], "\n", " ADD ", 'civicrm_job_log');
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_job_log " . $sql, [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

}
