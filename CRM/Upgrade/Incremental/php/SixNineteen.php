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
 * Upgrade logic for the 6.19.x series.
 *
 * Each minor version in the series is handled by either a `6.19.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_19_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixNineteen extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_19_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Drop OptionValue.domain_id column', 'dropColumn', 'civicrm_option_value', 'domain_id');
    $this->addTask(ts('Install PaymentprocessorWebhook entity'), 'installPaymentprocessorWebhookEntity', '6.19.alpha1.PaymentprocessorWebhook.entityType.php');
  }

  /**
   * Install the PaymentprocessorWebhook table, unless it already exists.
   *
   * The mjwshared extension has shipped this same table (same name and
   * columns) for several years, so sites that have it installed already own
   * the data - adopt the existing table rather than creating a fresh one,
   * so upgrading core doesn't clobber it (and doesn't error out on a
   * pre-existing table of the same name).
   *
   * Uses Civi::schemaHelper()->createEntityTable() rather than the base
   * class's own createEntityTable(), because the latter runs the CREATE
   * TABLE through CRM_Core_DAO::executeQuery() with i18nRewrite left at its
   * default of TRUE - on a multilingual site that rewrites the FK's
   * REFERENCES clause to the locale-suffixed view (e.g.
   * civicrm_payment_processor_en_US), which MySQL rejects because a foreign
   * key can't reference a view.
   *
   * @param $ctx
   * @param string $fileName
   * @return bool
   */
  public static function installPaymentprocessorWebhookEntity($ctx, string $fileName): bool {
    if (CRM_Core_DAO::checkTableExists('civicrm_paymentprocessor_webhook')) {
      return TRUE;
    }
    return \Civi::schemaHelper()->createEntityTable('CRM/Upgrade/Incremental/schema/' . $fileName);
  }

}
