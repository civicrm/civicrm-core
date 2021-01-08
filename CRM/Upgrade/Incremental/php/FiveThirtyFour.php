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
    if ($rev === '5.34.alpha1') {
      $xoauth2Value = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_MailSettings', 'protocol', 'IMAP_XOAUTH2');
      if (!empty($xoauth2Value)) {
        if ($this->isXOAUTH2InUse($xoauth2Value)) {
          $preUpgradeMessage .= '<p>' . $this->getXOAuth2Warning() . '</p>';
        }
      }
    }
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
    if ($rev === '5.34.alpha1') {
      $xoauth2Value = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_MailSettings', 'protocol', 'IMAP_XOAUTH2');
      if (!empty($xoauth2Value)) {
        if ($this->isXOAUTH2InUse($xoauth2Value)) {
          $postUpgradeMessage .= '<div class="crm-error"><ul><li>' . $this->getXOAuth2Warning() . '</li></ul></div>';
        }
      }
    }
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

    $this->addTask('Remove never used IMAP_XOAUTH2 option value', 'removeUnusedXOAUTH2');
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

  /**
   * This option value was never used, but check anyway if someone happens
   * to be using it and then ask them to report what they're doing with it.
   * There's no way to send a message to the user during the task, so we have
   * to check it here and also as a pre/post upgrade message.
   * Similar to removeGooglePlusOption from 5.23 except there we know some
   * people would have used it.
   */
  public static function removeUnusedXOAUTH2(CRM_Queue_TaskContext $ctx) {
    $xoauth2Value = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_MailSettings', 'protocol', 'IMAP_XOAUTH2');
    if (!empty($xoauth2Value)) {
      if (!self::isXOAUTH2InUse($xoauth2Value)) {
        CRM_Core_DAO::executeQuery("DELETE ov FROM civicrm_option_value ov
INNER JOIN civicrm_option_group og
ON (og.name = 'mail_protocol' AND ov.option_group_id = og.id)
WHERE ov.value = %1",
          [1 => [$xoauth2Value, 'Positive']]);
      }
    }
    return TRUE;
  }

  /**
   * Determine if option value is enabled or used in mail settings.
   * @return bool
   */
  private static function isXOAUTH2InUse($xoauth2Value) {
    $enabled = (bool) CRM_Core_DAO::SingleValueQuery("SELECT ov.is_active FROM civicrm_option_value ov
INNER JOIN civicrm_option_group og
ON (og.name = 'mail_protocol' AND ov.option_group_id = og.id)
WHERE ov.value = %1",
      [1 => [$xoauth2Value, 'Positive']]);
    $usedInMailSettings = (bool) CRM_Core_DAO::SingleValueQuery("SELECT id FROM civicrm_mail_settings WHERE protocol = %1", [1 => [$xoauth2Value, 'Positive']]);
    return $enabled || $usedInMailSettings;
  }

  /**
   * @return string
   */
  private function getXOAuth2Warning():string {
    // Leaving out ts() since it's unlikely this message will ever
    // be displayed to anyone.
    return strtr(
      'This system has enabled "IMAP_XOAUTH2" which was experimentally declared in CiviCRM v5.24. CiviCRM v5.33+ includes a supported replacement ("oauth-client"), and the experimental "IMAP_XOAUTH2" should be removed. Please visit %1 to discuss.',
      [
        '%1' => '<a target="_blank" href="https://lab.civicrm.org/dev/core/-/issues/2264">dev/core#2264</a>',
      ]
    );
  }

}
