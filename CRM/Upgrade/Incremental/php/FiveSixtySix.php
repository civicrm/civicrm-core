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
 * Upgrade logic for the 5.66.x series.
 *
 * Each minor version in the series is handled by either a `5.66.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_66_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSixtySix extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_66_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Make Contribution.tax_amount required', 'alterColumn', 'civicrm_contribution', 'tax_amount', "decimal(20,2) DEFAULT 0 NOT NULL COMMENT 'Total tax amount of this contribution.',");
    $this->addTask('Make Contribution.tax_amount required', 'alterColumn', 'civicrm_line_item', 'tax_amount', "decimal(20,2) DEFAULT 0 NOT NULL COMMENT 'tax of each item''");
    // These run after the sql file
    $this->addTask('Make Discount.entity_table required', 'alterColumn', 'civicrm_discount', 'entity_table', "varchar(64) NOT NULL COMMENT 'Name of the action(reminder)'");
    $this->addTask('Make ActionSchedule.name required', 'alterColumn', 'civicrm_action_schedule', 'name', "varchar(64) NOT NULL COMMENT 'physical tablename for entity being joined to discount, e.g. civicrm_event'");
    $this->addTask(ts('Create index %1', [1 => 'civicrm_action_schedule.UI_name']), 'addIndex', 'civicrm_action_schedule', 'name', 'UI');
    $this->addTask('Add fields to civicrm_mail_settings to allow more flexibility for email to activity', 'addMailSettingsFields');
  }

  /**
   * Add fields to civicrm_mail_settings to allow more flexibility for email to activity
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function addMailSettingsFields(CRM_Queue_TaskContext $ctx) {
    $ctx->log->info('Adding field is_active');
    self::addColumn($ctx, 'civicrm_mail_settings', 'is_active', 'tinyint NOT NULL DEFAULT 1 COMMENT "Ignored for bounce processing, only for email-to-activity"');
    $ctx->log->info('Adding field activity_type_id');
    self::addColumn($ctx, 'civicrm_mail_settings', 'activity_type_id', 'int unsigned COMMENT "Implicit FK to civicrm_option_value where option_group = activity_type"');
    $ctx->log->info('Adding field campaign_id');
    self::addColumn($ctx, 'civicrm_mail_settings', 'campaign_id', 'int unsigned DEFAULT NULL COMMENT "Foreign key to the Campaign."');
    $ctx->log->info('Adding field activity_source');
    self::addColumn($ctx, 'civicrm_mail_settings', 'activity_source', 'varchar(4) COMMENT "Which email recipient to add as the activity source (from, to, cc, bcc)."');
    $ctx->log->info('Adding field activity_targets');
    self::addColumn($ctx, 'civicrm_mail_settings', 'activity_targets', 'varchar(16) COMMENT "Which email recipients to add as the activity targets (from, to, cc, bcc)."');
    $ctx->log->info('Adding field activity_assignees');
    self::addColumn($ctx, 'civicrm_mail_settings', 'activity_assignees', 'varchar(16) COMMENT "Which email recipients to add as the activity assignees (from, to, cc, bcc)."');

    $ctx->log->info('Adding FK_civicrm_mail_settings_campaign_id');
    if (!self::checkFKExists('civicrm_mail_settings', 'FK_civicrm_mail_settings_campaign_id')) {
      CRM_Core_DAO::executeQuery("
        ALTER TABLE `civicrm_mail_settings`
        ADD CONSTRAINT `FK_civicrm_mail_settings_campaign_id`
        FOREIGN KEY (`campaign_id`) REFERENCES `civicrm_campaign`(`id`)
        ON DELETE SET NULL;
      ");
    }

    $ctx->log->info('Setting default activity_source');
    CRM_Core_DAO::executeQuery('UPDATE civicrm_mail_settings SET `activity_source` = "from" WHERE `activity_source` IS NULL;');
    $ctx->log->info('Setting default activity_targets');
    CRM_Core_DAO::executeQuery('UPDATE civicrm_mail_settings SET `activity_targets` = "to,cc,bcc" WHERE `activity_targets` IS NULL;');
    $ctx->log->info('Setting default activity_assignees');
    CRM_Core_DAO::executeQuery('UPDATE civicrm_mail_settings SET `activity_assignees` = "from" WHERE `activity_assignees` IS NULL;');
    $ctx->log->info('Setting default activity_type_id');
    $inboundEmailActivity = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Inbound Email');
    if ($inboundEmailActivity) {
      CRM_Core_DAO::executeQuery('UPDATE civicrm_mail_settings SET `activity_type_id` = ' . $inboundEmailActivity . ' WHERE `activity_type_id` IS NULL;');
    }
    return TRUE;
  }

}
