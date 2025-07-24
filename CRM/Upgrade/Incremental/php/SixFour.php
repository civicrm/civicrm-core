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
 * Upgrade logic for the 6.4.x series.
 *
 * Each minor version in the series is handled by either a `6.4.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_4_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixFour extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_4_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Rename multisite_is_enabled setting', 'renameMultisiteSetting');
    $this->addTask('Remove Foreign Key References from cache tables', 'removeForeignKeyReferencesCacheTables');
    $this->addTask('Increase length of MailingEventBounce.bounce_reason field', 'alterSchemaField', 'MailingEventBounce', 'bounce_reason', [
      'title' => ts('Bounce Reason'),
      'sql_type' => 'varchar(512)',
      'input_type' => 'Text',
      'description' => ts('The reason the email bounced.'),
    ]);
  }

  public static function removeForeignKeyReferencesCacheTables(): bool {
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_group_contact_cache', 'FK_civicrm_group_contact_cache_contact_id');
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_group_contact_cache', 'FK_civicrm_group_contact_cache_group_id');
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_acl_cache', 'FK_civicrm_acl_cache_acl_id');
    return TRUE;
  }

  public static function renameMultisiteSetting(): bool {
    CRM_Core_DAO::executeQuery('UPDATE civicrm_setting SET name = "multisite_is_enabled" WHERE name = "is_enabled"');
    return TRUE;
  }

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if (!function_exists('civi_wp')) {
      //exit
    }
    elseif ($rev == '6.4.alpha1') {
      $preUpgradeMessage .= '<p>' . ts('Beginning in CiviCRM for WordPress version 6.4.0 a setting has been added to control if a user is automatically logged in after account creation. <a %1>Details about this change</a>.', [1 => 'href="https://lab.civicrm.org/dev/wordpress/-/issues/154" target="_blank"']) . '</p>';
    }
  }

  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    if (!function_exists('civi_wp')) {
      //exit
    }
    elseif ($rev == '6.4.alpha1') {
      $postUpgradeMessage .= '<p>' . ts('Beginning in CiviCRM for WordPress version 6.4.0 a setting has been added to control if a user is automatically logged in after account creation. <a %1>Details about this change</a>.', [1 => 'href="https://lab.civicrm.org/dev/wordpress/-/issues/154" target="_blank"']) . '</p>';
    }
  }

}
