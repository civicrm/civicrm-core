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
 * Upgrade logic for the 5.75.x series.
 *
 * Each minor version in the series is handled by either a `5.75.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_75_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSeventyFive extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_75_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Replace mem_join_date smarty token in offline membership template',
      'updateMessageToken', 'membership_offline_receipt', '$mem_join_date', 'membership.join_date|crmDate:"Full"', $rev
    );
    $this->addTask('Replace mem_start_date smarty token in offline membership template',
      'updateMessageToken', 'membership_offline_receipt', '$mem_start_date', 'membership.start_date|crmDate:"Full"', $rev
    );
    $this->addTask('Replace mem_end_date smarty token in offline membership template',
      'updateMessageToken', 'membership_offline_receipt', '$mem_end_date', 'membership.end_date|crmDate:"Full"', $rev
    );
    $this->addTask('Replace membership_name smarty token in offline membership template',
      'updateMessageToken', 'membership_offline_receipt', '$membership_name', 'membership.membership_type_id:name', $rev
    );
    $this->addTask('Replace mem_status smarty token in offline membership template',
      'updateMessageToken', 'membership_offline_receipt', '$membership_status', 'membership.status_id:name', $rev
    );
    $this->addTask('Replace contributionStatus smarty token in offline membership template',
      'updateMessageToken', 'membership_offline_receipt', '$contributionStatus', 'contribution.contribution_status_id:name', $rev
    );
    $this->addTask('Replace contributionStatusID smarty token in offline membership template',
      'updateMessageToken', 'membership_offline_receipt', '$contributionStatusID', 'contribution.contribution_status_id', $rev
    );
    $this->addTask('Replace receive_date smarty token in offline membership template',
      'updateMessageToken', 'membership_offline_receipt', '$receive_date', 'contribution.receive_date', $rev
    );
    $this->addTask('Replace formValues.paidBy smarty token in offline membership template',
      'updateMessageToken', 'membership_offline_receipt', '$formValues.paidBy', 'contribution.payment_instrument_id:label', $rev
    );
    $this->addTask('Replace formValues.paidBy smarty token in offline membership template',
      'updateMessageToken', 'membership_offline_receipt', '$currency', 'contribution.currency', $rev
    );
    $this->addTask('Replace membership_name smarty token in online membership template',
      'updateMessageToken', 'membership_online_receipt', '$membership_name', 'membership.membership_type_id:name', $rev
    );
    $this->addTask('Replace mem_status smarty token in online membership template',
      'updateMessageToken', 'membership_online_receipt', '$membership_status', 'membership.status_id:name', $rev
    );
    $this->addTask('Replace formValues.paidBy smarty token in online membership template',
      'updateMessageToken', 'membership_online_receipt', '$currency', 'contribution.currency', $rev
    );
    $this->addTask('Replace receive_date smarty token in online membership template',
      'updateMessageToken', 'membership_online_receipt', '$receive_date', 'contribution.receive_date', $rev
    );
    CRM_Core_BAO_SchemaHandler::dropIndexIfExists('civicrm_line_item', 'UI_line_item_value');
    $this->addTask(ts('Disable financial ACL extension if unused'), 'disableFinancialAcl');
    $this->addTask('Install tellafriend extension', 'installTellafriend');
  }

  public static function disableFinancialAcl($rev): bool {
    $setting = CRM_Core_DAO::singleValueQuery('SELECT value FROM civicrm_setting WHERE name = "acl_financial_type"');
    if ($setting) {
      $setting = unserialize($setting);
    }
    if (!$setting) {
      CRM_Core_DAO::executeQuery('UPDATE civicrm_extension SET is_active = 0 WHERE full_name = "financialacls"');
    }
    return TRUE;
  }

  /**
   * Install tellafriend extension.
   *
   * This feature is restructured as a core extension - which is primarily a code cleanup step.
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function installTellafriend(CRM_Queue_TaskContext $ctx) {
    // Based on the instructions for the FiveThirty financialacls upgrade step
    // Install via direct SQL manipulation. Note that:
    // (1) This extension has no activation logic as of 5.75 (the DB tables are still in core)
    // (2) This extension is not enabled on new installs.
    // (3) Caches are flushed at the end of the upgrade.
    // ($) Over long term, upgrade steps are more reliable in SQL. API/BAO sometimes don't work mid-upgrade.
    $active = CRM_Core_DAO::singleValueQuery('SELECT is_active FROM civicrm_tell_friend WHERE is_active = 1 LIMIT 1');
    if ($active) {
      $insert = CRM_Utils_SQL_Insert::into('civicrm_extension')->row([
        'type' => 'module',
        'full_name' => 'tellafriend',
        'name' => 'tellafriend',
        'label' => 'Tell a Friend',
        'file' => 'tellafriend',
        'schema_version' => NULL,
        'is_active' => 1,
      ]);
      CRM_Core_DAO::executeQuery($insert->usingReplace()->toSQL());
    }
    return TRUE;
  }

}
