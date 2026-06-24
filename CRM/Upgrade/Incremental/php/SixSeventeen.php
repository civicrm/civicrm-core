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
 * Upgrade logic for the 6.17.x series.
 *
 * Each minor version in the series is handled by either a `6.17.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_17_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixSeventeen extends CRM_Upgrade_Incremental_Base {

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev === '6.17.alpha1') {
      if (Civi::settings()->get('search_mysql_fts')) {
        $settingUrl = (string) \Civi::url('civicrm/admin/setting/search', 'h')->addQuery(['reset' => 1]);
        $preUpgradeMessage .= '<p>' . ts("This upgrade will add a new Full Text Search index on the `civicrm_contact` table. If you have lots of contacts, this may take a while and use a lot of space on your database server. If you don't want this, turn off Use Mysql Full Text Search in <a %1>Search Preferences</a> before running the upgrade.", [1 => ('href="' . $settingUrl . '"')]) . '</p>';
      }
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_17_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add column "SavedSearch.timeout"', 'alterSchemaField', 'SavedSearch', 'timeout', [
      'title' => ts('Query Timeout'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => ts('Maximum query execution time in seconds. Overrides the site-wide SearchKit timeout. 0 = no timeout. NULL = use site default.'),
      'add' => '6.17',
      'default' => NULL,
      'input_attrs' => [
        'label' => ts('Query Timeout'),
        'min' => 0,
      ],
    ]);
    $this->addTask(ts('Create Mysql Full Text Search indices if active'), 'createMissingFtsIndices');
    $from = '{$participant_status}';
    $to = '{participant.status_id:label}';
    $template = 'event_offline_receipt';
    $this->addTask('Replace . ' . $from . ' with ' . $to . ' in ' . $template,
      'updateMessageToken', $template, $from, $to, $rev
    );
  }

}
