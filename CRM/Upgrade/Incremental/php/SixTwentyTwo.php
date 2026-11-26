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
 * Upgrade logic for the 6.22.x series.
 *
 * Each minor version in the series is handled by either a `6.22.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_22_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixTwentyTwo extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_22_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Set "group_multiple_parents" setting if used', 'setMultipleGroupParents');
    $this->addTask(ts('Create index %1', [1 => 'civicrm_mailing_recipients.index_mailing_id_contact_id']), 'addIndex', 'civicrm_mailing_recipients', [['mailing_id', 'contact_id']], 'index');
    $this->addTask(ts('Install PaymentprocessorWebhook entity'), 'installPaymentprocessorWebhookEntity', '6.22.alpha1.PaymentprocessorWebhook.entityType.php');
  }

  /**
   * Set "group_multiple_parents" setting if used.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function setMultipleGroupParents(): bool {
    $sql = "SELECT COUNT(*)
      FROM `civicrm_group`
      WHERE `parents` LIKE '%,%'";
    $groupFoundWithMultipleParents = (bool) CRM_Core_DAO::singleValueQuery($sql);
    Civi::settings()->set('group_multiple_parents', $groupFoundWithMultipleParents);
    return TRUE;
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
