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

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev == '5.62.alpha1') {
      $rawComponentLists = static::findEffectiveComponentsByDomain()->execute()->fetchMap('value', 'value');
      $distinctComponentLists = array_unique(array_map(function(?string $serializedList) {
        $list = \CRM_Utils_String::unserialize($serializedList);
        sort($list);
        return implode(',', $list);
      }, $rawComponentLists));
      if (count($distinctComponentLists) > 1) {
        $message = ts('This site has multiple "Domains". The list of active "Components" is being consolidated across all "Domains". If you need different behavior in each "Domain", then consider updating the roles or permissions.');
        // If you're investigating this - then maybe you should implement hook_permission_check() to dynamically adjust feature visibility?
        // See also: https://lab.civicrm.org/dev/core/-/issues/3961
        $preUpgradeMessage .= "<p>{$message}</p>";
      }

      if (defined('CIVICRM_SETTINGS_PATH') && CIVICRM_SETTINGS_PATH) {
        $contents = file_get_contents(CIVICRM_SETTINGS_PATH);
        if (strpos($contents, 'auto_detect_line_endings') !== FALSE) {
          $preUpgradeMessage .= '<p>' . ts('Your civicrm.settings.php file contains a line to set the php variable `auto_detect_line_endings`. It is deprecated and the line should be removed from the file.') . '</p>';
        }
      }
    }
    elseif ($rev == '5.62.beta1') {
      // Copied from FiveFiftySeven, only display if we upgrading from version after 5.57.alpha1
      if (version_compare($currentVer, '5.57.alpha1', '>')) {
        $docUrl = 'https://civicrm.org/redirect/activities-5.57';
        $docAnchor = 'target="_blank" href="' . htmlentities($docUrl) . '"';
        if (CRM_Core_DAO::singleValueQuery('SELECT COUNT(id) FROM civicrm_activity WHERE is_current_revision = 0')) {
          // Text copied from FiveFifty Seven
          $preUpgradeMessage .= '<p>' . ts('Your database contains CiviCase activity revisions which are deprecated and will begin to appear as duplicates in SearchKit/api4/etc.<ul><li>For further instructions see this <a %1>Lab Snippet</a>.</li></ul>', [1 => $docAnchor]) . '</p>';
          // New text explaination as to why we show this again.
          $preUpgradeMessage .= '<p>' . ts('Note: You might have followed these steps already but unfortunately a previous upgrade which started ignoring the setting to create CiviCase Activity revisions failed to account for all the places this functionality existed. This means that either new Revisions have been added since you removed them, or not all were removed.') . '</p>';
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

  /**
   * Upgrade step; Required to ensure pre Upgrade runs.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_62_beta1($rev): void {
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
    // Flush the settings cache after updating the `enable_components` setting.
    \Civi\Core\Container::getBootService('settings_manager')->flush();

    return TRUE;
  }

  /**
   * @return array
   *   Ex: ['CiviEvent', 'CiviMail']
   */
  public static function findAllEnabledComponents(): array {
    $raw = static::findEffectiveComponentsByDomain()->execute()->fetchMap('domain_id', 'value');
    $all = [];
    foreach ($raw as $value) {
      $all = array_unique(array_merge($all, \CRM_Utils_String::unserialize($value)));
    }
    return array_values($all);
  }

  /**
   * @return \CRM_Utils_SQL_Select
   *   SQL Query. Each row has a `domain_id,value` with the de-facto value of the `enable_component`
   *   setting in that domain.
   */
  private static function findEffectiveComponentsByDomain(): CRM_Utils_SQL_Select {
    // Traditional list of components, as it existed circa 5.61.
    $defaults = ['CiviEvent', 'CiviContribute', 'CiviMember', 'CiviMail', 'CiviReport', 'CiviPledge'];

    return CRM_Utils_SQL_Select::from('civicrm_domain d')
      ->join('civicrm_setting s', 'LEFT JOIN civicrm_setting s ON (d.id = s.domain_id AND s.name = "enable_components")')
      ->select('d.id as domain_id, coalesce(s.value, @DEFAULT) as value')
      ->param(['DEFAULT' => \serialize($defaults)]);

  }

}
