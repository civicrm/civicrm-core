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

  /**
   * How many activities before the queries used here are slow. Guessing.
   */
  const ACTIVITY_THRESHOLD = 1000000;

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev === '5.57.alpha1') {
      $docUrl = 'https://civicrm.org/redirect/activities-5.57';
      $docAnchor = 'target="_blank" href="' . htmlentities($docUrl) . '"';

      // The query on is_current_revision is slow if there's a lot of activities. So limit when it gets run.
      $activityCount = CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_activity');
      if ($activityCount < self::ACTIVITY_THRESHOLD && CRM_Core_DAO::singleValueQuery('SELECT COUNT(id) FROM civicrm_activity WHERE is_current_revision = 0')) {
        $preUpgradeMessage .= '<p>' . ts('Your database contains CiviCase activity revisions which are deprecated and will begin to appear as duplicates in SearchKit/api4/etc.<ul><li>For further instructions see this <a %1>Lab Snippet</a>.</li></ul>', [1 => $docAnchor]) . '</p>';
      }
      // Similarly the original_id ON DELETE drop+recreate is slow, so if we
      // don't add the task farther down below, then tell people what to do at
      // their convenience.
      elseif ($activityCount >= self::ACTIVITY_THRESHOLD) {
        $preUpgradeMessage .= '<p>' . ts('The activity table <strong>will not update automatically</strong> because it contains too many records. You will need to apply a <strong>manual update</strong>. Please read about <a %1>how to clean data from the defunct "Embedded Activity Revisions" setting</a>.', [1 => $docAnchor]) . '</p>';
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
    if (CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_activity') < self::ACTIVITY_THRESHOLD) {
      $this->addTask('Fix dangerous delete cascade', 'fixDeleteCascade');
    }
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
  public function upgrade_5_57_beta1($rev): void {
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
