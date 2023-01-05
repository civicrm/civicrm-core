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
 * Upgrade logic for the 5.57.x series.
 *
 * Each minor version in the series is handled by either a `5.57.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_57_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFiftySeven extends CRM_Upgrade_Incremental_Base {

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev === '5.57.alpha1') {
      if (CRM_Core_DAO::singleValueQuery('SELECT COUNT(id) FROM civicrm_activity WHERE is_current_revision = 0')) {
        $preUpgradeMessage .= '<p>' . ts('Your database contains CiviCase activity revisions which are deprecated and will begin to appear as duplicates in SearchKit/api4/etc.<ul><li>For further instructions see this <a %1>Lab Snippet</a>.</li></ul>', [1 => 'target="_blank" href="https://lab.civicrm.org/-/snippets/85"']) . '</p>';
      }
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_57_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Fix dangerous delete cascade', 'fixDeleteCascade');
    $this->addExtensionTask('Enable SearchKit extension', ['org.civicrm.search_kit'], 1100);
    $this->addExtensionTask('Enable Flexmailer extension', ['org.civicrm.flexmailer']);
  }

  public static function fixDeleteCascade($ctx): bool {
    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_activity', 'FK_civicrm_activity_original_id');
    CRM_Core_DAO::executeQuery('ALTER TABLE `civicrm_activity` ADD CONSTRAINT `FK_civicrm_activity_original_id` FOREIGN KEY (`original_id`) REFERENCES `civicrm_activity` (`id`) ON DELETE SET NULL');
    return TRUE;
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_57_0($rev): void {
    $this->addTask('Fix broken quicksearch options', 'fixQuicksearchOptions');
  }

  public static function fixQuicksearchOptions($ctx): bool {
    $default_options = [
      0 => 'sort_name',
      1 => 'contact_id',
      2 => 'external_identifier',
      3 => 'first_name',
      4 => 'last_name',
      5 => 'email',
      6 => 'phone_numeric',
      7 => 'street_address',
      8 => 'city',
      9 => 'postal_code',
      10 => 'job_title',
    ];

    $opts = \Civi::settings()->get('quicksearch_options');
    if ($opts === NULL) {
      // Super-borked => just reset to defaults
      $opts = \Civi::settings()->set('quicksearch_options', $default_options);
    }
    elseif (is_string($opts)) {
      // Has the desired values but we need to put back in array format
      $opts = trim($opts, CRM_Core_DAO::VALUE_SEPARATOR);
      $opts = explode(CRM_Core_DAO::VALUE_SEPARATOR, $opts);
      if (empty($opts)) {
        // hmm, just reset to defaults
        \Civi::settings()->set('quicksearch_options', $default_options);
      }
      else {
        \Civi::settings()->set('quicksearch_options', $opts);
      }
    }
    return TRUE;
  }

}
