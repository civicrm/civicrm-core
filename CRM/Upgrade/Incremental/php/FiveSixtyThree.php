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
 * Upgrade logic for the 5.63.x series.
 *
 * Each minor version in the series is handled by either a `5.63.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_63_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSixtyThree extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function upgrade_5_63_alpha1(string $rev): void {
    $this->addTask('Add name column  to civicrm_contribution_page', 'addColumn', 'civicrm_contribution_page',
      'name', "varchar(255) NULL COMMENT 'Unique name for identifying contribution page'");
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);

    // Campaign indexes
    $this->addTask(ts('Drop index %1', [1 => 'civicrm_campaign.UI_campaign_type_id']), 'dropIndex', 'civicrm_campaign', 'UI_campaign_type_id');
    $this->addTask(ts('Create index %1', [1 => 'civicrm_campaign.index_campaign_type_id']), 'addIndex', 'civicrm_campaign', 'campaign_type_id', 'index');
    $this->addTask(ts('Drop index %1', [1 => 'civicrm_campaign.UI_campaign_status_id']), 'dropIndex', 'civicrm_campaign', 'UI_campaign_status_id');
    $this->addTask(ts('Create index %1', [1 => 'civicrm_campaign.index_status_id']), 'addIndex', 'civicrm_campaign', 'status_id', 'index');
    $this->addTask('Add default value to civicrm_campaign.created_date', 'alterColumn', 'civicrm_campaign', 'created_date', "datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Date and time that Campaign was created.'");

    $enabledComponents = Civi::settings()->get('enable_components');
    $extensions = array_map(['CRM_Utils_String', 'convertStringToSnakeCase'], $enabledComponents);
    $this->addExtensionTask('Enable component extensions', $extensions);

    $this->addTask('Make ContributionPage.name required', 'alterColumn', 'civicrm_contribution_page', 'name', "varchar(255) NOT NULL COMMENT 'Unique name for identifying contribution page'");
    $this->addTask('Make ContributionPage.title required', 'alterColumn', 'civicrm_contribution_page', 'title', "varchar(255) NOT NULL COMMENT 'Contribution Page title. For top of page display'", TRUE);
    $this->addTask('Make ContributionPage.frontend_title required', 'alterColumn', 'civicrm_contribution_page', 'frontend_title', "varchar(255) NOT NULL COMMENT 'Contribution Page Public title'", TRUE);
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function upgrade_5_63_1(string $rev): void {
    $this->addTask('Fix double json encoding of accepted_credit_cards field in payment processor table', 'fixDoubleEscapingPaymentProcessorCreditCards');
  }

  /**
   * Fix any double json encoding in Payment Processor accepted_credit_cards field
   */
  public static function fixDoubleEscapingPaymentProcessorCreditCards() {
    $paymentProcessors = CRM_Core_DAO::executeQuery("SELECT id, accepted_credit_cards FROM civicrm_payment_processor");
    while ($paymentProcessors->fetch()) {
      if (!empty($paymentProcessors->accepted_credit_cards)) {
        $accepted_credit_cards = json_decode($paymentProcessors->accepted_credit_cards, TRUE);
        if (is_numeric(array_keys($accepted_credit_cards)[0])) {
          CRM_Core_DAO::executeQuery("UPDATE civicrm_payment_processor SET accepted_credit_cards = %1 WHERE id = %2", [
            1 => [$accepted_credit_cards[0], 'String'],
            2 => [$paymentProcessors->id, 'Positive'],
          ]);
        }
      }
    }
    return TRUE;
  }

}
