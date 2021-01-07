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
 * Upgrade logic for FiveThirtyFour */
class CRM_Upgrade_Incremental_php_FiveThirtyFour extends CRM_Upgrade_Incremental_Base {

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
  }

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    // Example: Generate a post-upgrade message.
    // if ($rev == '5.12.34') {
    //   $postUpgradeMessage .= '<br /><br />' . ts("By default, CiviCRM now disables the ability to import directly from SQL. To use this feature, you must explicitly grant permission 'import SQL datasource'.");
    // }
  }

  /*
   * Important! All upgrade functions MUST add a 'runSql' task.
   * Uncomment and use the following template for a new upgrade version
   * (change the x in the function name):
   */

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_34_alpha1(string $rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('core-issue#365 - Add created_date to civicrm_action_schedule', 'addColumn',
      'civicrm_action_schedule', 'created_date', "timestamp NULL  DEFAULT CURRENT_TIMESTAMP COMMENT 'When was the schedule reminder created.'");

    $this->addTask('core-issue#365 - Add modified_date to civicrm_action_schedule', 'addColumn',
      'civicrm_action_schedule', 'modified_date', "timestamp NULL  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'When was the schedule reminder created.'");

    $this->addTask('core-issue#365 - Add effective_start_date to civicrm_action_schedule', 'addColumn',
      'civicrm_action_schedule', 'effective_start_date', "timestamp NULL COMMENT 'Earliest date to consider start events from.'");

    $this->addTask('core-issue#365 - Add effective_end_date to civicrm_action_schedule', 'addColumn',
      'civicrm_action_schedule', 'effective_end_date', "timestamp NULL COMMENT 'Latest date to consider end events from.'");

    $this->addTask('Set defaults and required on financial type boolean fields', 'updateFinancialTypeTable');
    $this->addTask('Set defaults and required on pledge fields', 'updatePledgeTable');
  }

  /**
   * Update financial type table to reflect recent schema changes.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function updateFinancialTypeTable(CRM_Queue_TaskContext $ctx): bool {
    // Make sure there are no existing NULL values in the fields we are about to make required.
    CRM_Core_DAO::executeQuery('
      UPDATE civicrm_financial_type
      SET is_active = COALESCE(is_active, 0),
          is_reserved = COALESCE(is_reserved, 0),
          is_deductible = COALESCE(is_deductible, 0)
      WHERE is_reserved IS NULL OR is_active IS NULL OR is_deductible IS NULL
    ');
    CRM_Core_DAO::executeQuery("
      ALTER TABLE civicrm_financial_type
      MODIFY COLUMN `is_deductible` tinyint(4) DEFAULT 0 NOT NULL COMMENT 'Is this financial type tax-deductible? If true, contributions of this type may be fully OR partially deductible - non-deductible amount is stored in the Contribution record.',
      MODIFY COLUMN `is_reserved` tinyint(4) DEFAULT 0 NOT NULL COMMENT 'Is this a predefined system object?',
      MODIFY COLUMN `is_active` tinyint(4) DEFAULT 1 NOT NULL COMMENT 'Is this property active?'
    ");

    return TRUE;
  }

  /**
   * Update pledge table to reflect recent schema changes making fields required.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function updatePledgeTable(CRM_Queue_TaskContext $ctx): bool {
    // Make sure there are no existing NULL values in the fields we are about to make required.
    CRM_Core_DAO::executeQuery('
      UPDATE civicrm_pledge
      SET is_test = COALESCE(is_test, 0),
          frequency_unit = COALESCE(frequency_unit, "month"),
          # Cannot imagine this would be null but if it were...
          installments = COALESCE(installments, 0),
          # this does not seem plausible either.
          status_id = COALESCE(status_id, 1)
      WHERE is_test IS NULL OR frequency_unit IS NULL OR installments IS NULL OR status_id IS NULL
    ');
    CRM_Core_DAO::executeQuery("
      ALTER TABLE civicrm_pledge
      MODIFY COLUMN `frequency_unit` varchar(8) DEFAULT 'month' NOT NULL COMMENT 'Time units for recurrence of pledge payments.',
      MODIFY COLUMN `installments` int(10) unsigned DEFAULT 1 NOT NULL COMMENT 'Total number of payments to be made.',
      MODIFY COLUMN `status_id` int(10) unsigned NOT NULL COMMENT 'Implicit foreign key to civicrm_option_values in the pledge_status option group.',
      MODIFY COLUMN `is_test` tinyint(4) DEFAULT 0 NOT NULL
    ");
    return TRUE;
  }

}
