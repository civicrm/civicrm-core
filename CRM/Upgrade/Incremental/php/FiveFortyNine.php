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
 * Upgrade logic for the 5.49.x series.
 *
 * Each minor version in the series is handled by either a `5.49.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_49_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFortyNine extends CRM_Upgrade_Incremental_Base {

  /**
   * @var string[][]
   * Array (keyed by tableName) of boolean columns to make NOT NULL.
   * @see self::changeBooleanColumn
   */
  private $booleanColumns = [
    'civicrm_event' => [
      'is_public' => "DEFAULT 1 COMMENT 'Public events will be included in the iCal feeds. Access to private event information may be limited using ACLs.'",
      'is_online_registration' => "DEFAULT 0 COMMENT 'If true, include registration link on Event Info page.'",
      'is_monetary' => "DEFAULT 0 COMMENT 'If true, one or more fee amounts must be set and a Payment Processor must be configured for Online Event Registration.'",
      'is_map' => "DEFAULT 0 COMMENT 'Include a map block on the Event Information page when geocode info is available and a mapping provider has been specified?'",
      'is_active' => "DEFAULT 0 COMMENT 'Is this Event enabled or disabled/cancelled?'",
      'is_show_location' => "DEFAULT 1 COMMENT 'If true, show event location.'",
      'is_email_confirm' => "DEFAULT 0 COMMENT 'If true, confirmation is automatically emailed to contact on successful registration.'",
      'is_pay_later' => "DEFAULT 0 COMMENT 'if true - allows the user to send payment directly to the org later'",
      'is_partial_payment' => "DEFAULT 0 COMMENT 'is partial payment enabled for this event'",
      'is_multiple_registrations' => "DEFAULT 0 COMMENT 'if true - allows the user to register multiple participants for event'",
      'allow_same_participant_emails' => "DEFAULT 0 COMMENT 'if true - allows the user to register multiple registrations from same email address.'",
      'has_waitlist' => "DEFAULT 0 COMMENT 'Whether the event has waitlist support.'",
      'requires_approval' => "DEFAULT 0 COMMENT 'Whether participants require approval before they can finish registering.'",
      'allow_selfcancelxfer' => "DEFAULT 0 COMMENT 'Allow self service cancellation or transfer for event?'",
      'is_template' => "DEFAULT 0 COMMENT 'whether the event has template'",
      'is_share' => "DEFAULT 1 COMMENT 'Can people share the event through social media?'",
      'is_confirm_enabled' => "DEFAULT 1 COMMENT 'If false, the event booking confirmation screen gets skipped'",
      'is_billing_required' => "DEFAULT 0 COMMENT 'if true than billing block is required this event'",
    ],
    'civicrm_contribution' => [
      'is_test' => "DEFAULT 0",
      'is_pay_later' => "DEFAULT 0",
      'is_template' => "DEFAULT 0 COMMENT 'Shows this is a template for recurring contributions.'",
    ],
  ];

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_49_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    foreach ($this->booleanColumns as $tableName => $columns) {
      foreach ($columns as $columnName => $defn) {
        $this->addTask("Update $tableName.$columnName to be NOT NULL", 'changeBooleanColumn', $tableName, $columnName, $defn);
      }
    }
  }

  /**
   * Converts a boolean table column to be NOT NULL
   * @param CRM_Queue_TaskContext $ctx
   * @param string $tableName
   * @param string $columnName
   * @param string $defn
   */
  public static function changeBooleanColumn(CRM_Queue_TaskContext $ctx, $tableName, $columnName, $defn) {
    CRM_Core_DAO::executeQuery("UPDATE `$tableName` SET `$columnName` = 0 WHERE `$columnName` IS NULL", [], TRUE, NULL, FALSE, FALSE);
    CRM_Core_DAO::executeQuery("ALTER TABLE `$tableName` CHANGE `$columnName` `$columnName` tinyint NOT NULL $defn", [], TRUE, NULL, FALSE, FALSE);
    return TRUE;
  }

}
