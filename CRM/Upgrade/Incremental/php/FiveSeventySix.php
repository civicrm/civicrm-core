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
 * Upgrade logic for the 5.76.x series.
 *
 * Each minor version in the series is handled by either a `5.76.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_76_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSeventySix extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_76_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add start_date to civicrm_mailing table', 'addColumn', 'civicrm_mailing', 'start_date', "timestamp NULL DEFAULT NULL COMMENT 'date on which this mailing was started.'");
    $this->addTask('Add end_date to civicrm_mailing table', 'addColumn', 'civicrm_mailing', 'end_date', "timestamp NULL DEFAULT NULL COMMENT 'date on which this mailing was completed.'");
    $this->addTask('Add status to civicrm_mailing table', 'addColumn', 'civicrm_mailing', 'status', "varchar(12) DEFAULT NULL COMMENT 'The status of this Mailing'");
    $this->addTask('Alter translation to make string non-required', 'alterColumn', 'civicrm_translation', 'string',
      "longtext NULL COMMENT 'Translated string'"
    );
    $this->addTask('Install SiteToken entity', 'createEntityTable', '5.76.alpha1.SiteToken.entityType.php');
    $this->addTask(ts('Create index %1', [1 => 'civicrm_site_token.UI_name_domain_id']), 'addIndex', 'civicrm_site_token', [['name', 'domain_id']], 'UI');
    $this->addTask('Create "message header" token', 'create_mesage_header_token');
    if (!CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_extension WHERE full_name = "eventcart"')) {
      $this->addTask('Remove data related to disabled even cart extension', 'removeEventCartAssets');
    }
  }

  public static function create_mesage_header_token() {
    $query = CRM_Core_DAO::executeQuery('SELECT id FROM civicrm_domain');
    $domains = $query->fetchAll();
    foreach ($domains as $domain) {
      CRM_Core_DAO::executeQuery(
        "INSERT IGNORE INTO civicrm_site_token (domain_id, name, label, body_html, body_text, is_reserved, is_active)
      VALUES(
       " . $domain['id'] . ",
       'message_header',
       '" . ts('Message Header') . "',
     '<!-- " . ts('This is the %1 token HTML content.', [1 => '{site.message_header}']) . " -->',
      '', 1, 1)"
      );
    }
    return TRUE;
  }

  /**
   * Drop tables, disable the message template as they relate to event carts.
   *
   * It would be nice to delete the message template but who knows there could be a gotcha.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function removeEventCartAssets(): bool {
    try {
      CRM_Core_DAO::executeQuery("UPDATE civicrm_msg_template SET is_active = 0 WHERE workflow_name = 'event_registration_receipt'");
      if (!CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_events_in_carts LIMIT 1')) {
        CRM_Core_DAO::executeQuery('DROP table civicrm_events_in_carts');
      }
      if (!CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_event_carts LIMIT 1')) {
        \CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_participant', 'FK_civicrm_participant_cart_id');
        CRM_Core_DAO::executeQuery('DROP table civicrm_event_carts');
      }
    }
    catch (CRM_Core_Exception $e) {
      // hmm what could possibly go wrong. A few stray artifacts is not as bad as a fail here I guess.
    }
    return TRUE;
  }

}
