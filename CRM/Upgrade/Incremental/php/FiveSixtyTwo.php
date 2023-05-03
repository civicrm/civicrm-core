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
 * Upgrade logic for the 5.62.x series.
 *
 * Each minor version in the series is handled by either a `5.62.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_62_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSixtyTwo extends CRM_Upgrade_Incremental_Base {


  /**
   * How many activities before the queries used here are slow. Guessing.
   */
  const ACTIVITY_THRESHOLD = 1000000;

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev == '5.62.alpha1') {

      $docUrl = 'https://civicrm.org/redirect/activities-5.57';
      $docAnchor = 'target="_blank" href="' . htmlentities($docUrl) . '"';
      $activityCount = CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_activity');
      $common_msg = '<p>' . ts('A previous upgrade which removed CiviCase Activity revisions failed to account for all the places this functionality existed. Unfortunately this means that new Revisions have been added. You will need to repeat the action taking in the 5.57.x upgrade.') . '</p>';

      if ($activityCount < self::ACTIVITY_THRESHOLD && CRM_Core_DAO::singleValueQuery('SELECT COUNT(id) FROM civicrm_activity WHERE is_current_revision = 0')) {

        $preUpgradeMessage .= $common_msg . '<p>' . ts('Your database contains CiviCase activity revisions which are deprecated and will begin to appear as duplicates in SearchKit/api4/etc.<ul><li>For further instructions see this <a %1>Lab Snippet</a>.</li></ul>', [1 => $docAnchor]) . '</p>';
      }
      // Similarly the original_id ON DELETE drop+recreate is slow, so if we
      // don't add the task farther down below, then tell people what to do at
      // their convenience.
      elseif ($activityCount >= self::ACTIVITY_THRESHOLD) {
        $preUpgradeMessage .= $common_msg . '<p>' . ts('The activity table <strong>will not update automatically</strong> because it contains too many records. You will need to apply a <strong>manual update</strong>. Please read about <a %1>how to clean data from the defunct "Embedded Activity Revisions" setting</a>.', [1 => $docAnchor]) . '</p>';
      }
      if (defined('CIVICRM_SETTINGS_PATH') && CIVICRM_SETTINGS_PATH) {
        $contents = file_get_contents(CIVICRM_SETTINGS_PATH);
        if (strpos($contents, 'auto_detect_line_endings') !== FALSE) {
          $preUpgradeMessage .= '<p>' . ts('Your civicrm.settings.php file contains a line to set the php variable `auto_detect_line_endings`. It is deprecated and the line should be removed from the file.') . '</p>';
        }
      }
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_62_alpha1($rev): void {
    $this->addTask('Make civicrm_setting.domain_id optional', 'alterColumn', 'civicrm_setting', 'domain_id', "int unsigned DEFAULT NULL COMMENT 'Which Domain does this setting belong to'");
    $this->addTask('Consolidate the list of components', 'consolidateComponents');
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    if (CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_activity') < self::ACTIVITY_THRESHOLD) {
      $this->addTask('Fix dangerous delete cascade', 'fixDeleteCascade');
    }
    $this->addTask('Make civicrm_mapping.name required', 'alterColumn', 'civicrm_mapping', 'name', "varchar(64) NOT NULL COMMENT 'Unique name of Mapping'");
    $this->addTask(ts('Drop index %1', [1 => 'civicrm_mapping.UI_name']), 'dropIndex', 'civicrm_mapping', 'UI_name');
    $this->addTask(ts('Create index %1', [1 => 'civicrm_mapping.UI_name']), 'addIndex', 'civicrm_mapping', [['name']], 'UI');

    $this->addTask(
      'Add option group for file_type_id in file table',
      'addOptionGroup',
      [
        'name' => 'file_type',
        'title' => ts('File Type'),
        'data_type' => 'Integer',
        'is_reserved' => 1,
      ],
      []
    );
  }

  public static function consolidateComponents($ctx): bool {
    $final = static::findAllEnabledComponents();
    // Ensure CiviGrant is removed from the setting, as this may have been incomplete in a previous upgrade.
    // @see FiveFortySeven::migrateCiviGrant
    $final = array_values(array_diff($final, ['CiviGrant']));

    $lowestDomainId = CRM_Core_DAO::singleValueQuery('SELECT min(domain_id) FROM civicrm_setting WHERE name = "enable_components"');
    if (!is_numeric($lowestDomainId)) {
      return TRUE;
    }

    CRM_Core_DAO::executeQuery('UPDATE civicrm_setting SET domain_id = NULL, value = %3 WHERE domain_id = %1 AND name = %2', [
      1 => [$lowestDomainId, 'Positive'],
      2 => ['enable_components', 'String'],
      3 => [serialize($final), 'String'],
    ]);

    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_setting WHERE domain_id > %1 AND name = %2', [
      1 => [$lowestDomainId, 'Positive'],
      2 => ['enable_components', 'String'],
    ]);

    return TRUE;
  }

  /**
   * @return array
   *   Ex: ['CiviEvent', 'CiviMail']
   */
  public static function findAllEnabledComponents(): array {
    $raw = CRM_Core_DAO::executeQuery('SELECT domain_id, value FROM civicrm_setting WHERE name = "enable_components"')
      ->fetchMap('domain_id', 'value');
    $all = [];
    foreach ($raw as $value) {
      $all = array_unique(array_merge($all, \CRM_Utils_String::unserialize($value)));
    }
    return array_values($all);
  }

}
