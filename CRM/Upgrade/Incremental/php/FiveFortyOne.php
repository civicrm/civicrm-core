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
 * Upgrade logic for FiveFortyOne
 */
class CRM_Upgrade_Incremental_php_FiveFortyOne extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * Note: This function is called iteratively for each incremental upgrade step.
   * There must be a concrete step (eg 'X.Y.Z.mysql.tpl' or 'upgrade_X_Y_Z()').
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev): void {
    if ($rev === '5.41.alpha1') {
      $postUpgradeMessage .= '<br /><br />' . ts('A token has been updated in the %1 template. Check the system checks page to see if any action is required.', [1 => 'invoice']);
    }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_41_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Install legacy custom search extension', 'installCustomSearches');
    $this->addTask('Replace legacy displayName smarty token in Invoice workflow template',
      'updateMessageToken', 'contribution_invoice_receipt', '$display_name', 'contact.display_name', $rev
    );
    $this->addTask('Replace contribution status token in action schedule',
      'updateActionScheduleToken', 'contribution.status', 'contribution.contribution_status_id:label', $rev
    );
    $this->addTask('Replace contribution cancel_date token in action schedule',
      'updateActionScheduleToken', 'contribution.contribution_cancel_date', 'contribution.cancel_date', $rev
    );
    $this->addTask('Replace contribution source token in action schedule',
      'updateActionScheduleToken', 'contribution.contribution_source', 'contribution.source', $rev
    );
    $this->addTask('Replace contribution type token in action schedule',
      'updateActionScheduleToken', 'contribution.type', 'contribution.financial_type_id:label', $rev
    );
    $this->addTask('Replace contribution payment instrument token in action schedule',
      'updateActionScheduleToken', 'contribution.payment_instrument', 'contribution.payment_instrument_id:label', $rev
    );
    $this->addTask('Replace contribution page id token in action schedule',
      'updateActionScheduleToken', 'contribution.contribution_page_id', 'contribution.contribution_page_id:label', $rev
    );
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_41_beta1($rev) {
    $this->addTask('Ensure non-English installs have a default financial type for the membership price set.',
      'ensureDefaultMembershipFinancialType');
  }

  public static function ensureDefaultMembershipFinancialType(): bool {
    $membershipPriceSet = CRM_Core_DAO::executeQuery(
      'SELECT id, financial_type_id FROM civicrm_price_set WHERE name = "default_membership_type_amount"'
    );
    $membershipPriceSet->fetch();
    if (!is_numeric($membershipPriceSet->financial_type_id)) {
      $membershipFinancialTypeID = CRM_Core_DAO::singleValueQuery('
        SELECT id FROM civicrm_financial_type
        WHERE name = "Member Dues"
        OR name = %1', [1 => [ts('Member Dues'), 'String']]
      );
      if (!$membershipFinancialTypeID) {
        // This should be unreachable - but something is better than nothing
        // if we get to this point & 2 will be correct on 99.9% of installs.
        $membershipFinancialTypeID = 2;
      }
      CRM_Core_DAO::executeQuery("
        UPDATE civicrm_price_set
        SET financial_type_id = $membershipFinancialTypeID
        WHERE name = 'default_membership_type_amount'"
      );

    }
    return TRUE;
  }

  /**
   * Install CustomSearches extension.
   *
   * This feature is restructured as a core extension - which is primarily a code cleanup step.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  public static function installCustomSearches(CRM_Queue_TaskContext $ctx) {
    // Install via direct SQL manipulation. Note that:
    // (1) This extension has no activation logic.
    // (2) On new installs, the extension is activated purely via default SQL INSERT.
    // (3) Caches are flushed at the end of the upgrade.
    // ($) Over long term, upgrade steps are more reliable in SQL. API/BAO sometimes don't work mid-upgrade.
    $insert = CRM_Utils_SQL_Insert::into('civicrm_extension')->row([
      'type' => 'module',
      'full_name' => 'legacycustomsearches',
      'name' => 'Custom search framework',
      'label' => 'Custom search framework',
      'file' => 'legacycustomsearches',
      'schema_version' => NULL,
      'is_active' => 1,
    ]);
    CRM_Core_DAO::executeQuery($insert->usingReplace()->toSQL());
    return TRUE;
  }

}
