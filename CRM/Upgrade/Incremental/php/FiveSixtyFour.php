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
 * Upgrade logic for the 5.64.x series.
 *
 * Each minor version in the series is handled by either a `5.64.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_64_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSixtyFour extends CRM_Upgrade_Incremental_Base {

  /**
   * How many activities before the queries used here are slow. Guessing.
   */
  const ACTIVITY_THRESHOLD = 1000000;

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev === '5.64.beta1') {
      // The ON DELETE constraint drop+recreate can be slow, so tell people what to do at their convenience if db is large.
      if (CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_activity') >= self::ACTIVITY_THRESHOLD) {
        $preUpgradeMessage .= '<p>' . ts('<strong>ACTION REQUIRED</strong>: You will need to apply a <strong>manual update</strong> because your civicrm_activity table is large and the update will run slowly. Please read about <a %1>how to apply this update manually</a>.', [1 => 'target="_blank" href="https://civicrm.org/redirect/activities-parentid-cascade"']) . '</p>';
      }
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_64_alpha1($rev): void {
    $this->addTask('Add priority column onto ACL table', 'addColumn', 'civicrm_acl', 'priority', 'int NOT NULL DEFAULT 0');
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Drop unused civicrm_action_mapping table', 'dropTable', 'civicrm_action_mapping');
    $this->addTask('Update post_URL/cancel_URL in logging tables', 'updateLogging');
    $this->addTask('Add in Everybody ACL Role option value', 'addEveryBodyAclOptionValue');
    $this->addTask('Fix double json encoding of accepted_credit_cards field in payment processor table', 'fixDoubleEscapingPaymentProcessorCreditCards');
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_64_beta1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    if (CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_activity') < self::ACTIVITY_THRESHOLD) {
      $this->addTask('Fix dangerous delete cascade', 'fixDeleteCascade');
    }
  }

  public static function updateLogging($ctx): bool {
    if (\Civi::settings()->get('logging')) {
      $dsn = defined('CIVICRM_LOGGING_DSN') ? CRM_Utils_SQL::autoSwitchDSN(CIVICRM_LOGGING_DSN) : CRM_Utils_SQL::autoSwitchDSN(CIVICRM_DSN);
      $dsn = DB::parseDSN($dsn);
      $table = '`' . $dsn['database'] . '`.`log_civicrm_uf_group`';
      CRM_Core_DAO::executeQuery("ALTER TABLE $table CHANGE `post_URL` `post_url` varchar(255) DEFAULT NULL COMMENT 'Redirect to URL on submit.',
CHANGE `cancel_URL` `cancel_url` varchar(255) DEFAULT NULL COMMENT 'Redirect to URL when Cancel button clicked.'", [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

  public static function addEverybodyAclOptionValue($ctx): bool {
    \CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'label' => 'Everybody',
      'value' => 0,
      'option_group_id' => 'acl_role',
      'is_active' => 1,
      'name' => 'Everybody',
      'is_reserved' => 1,
    ]);
    return TRUE;
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

  /**
   * Fix DELETE CASCADE that can lead to loss of data.
   */
  public static function fixDeleteCascade($ctx): bool {
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_activity', 'FK_civicrm_activity_parent_id');
    CRM_Core_DAO::executeQuery('ALTER TABLE `civicrm_activity` ADD CONSTRAINT `FK_civicrm_activity_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `civicrm_activity` (`id`) ON DELETE SET NULL');
    return TRUE;
  }

}
