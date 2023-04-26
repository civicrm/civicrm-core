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
      $distinctComponentLists = CRM_Core_DAO::executeQuery('SELECT value, count(*) c FROM civicrm_setting WHERE name = "enable_components" GROUP BY value')
        ->fetchMap('value', 'c');
      if (count($distinctComponentLists) > 1) {
        $message = ts('This site has multiple "Domains". The list of active "Components" is being consolidated across all "Domains". If you need different behavior in each "Domain", then consider updating the roles or permissions.');
        // If you're investigating this - then maybe you should implement hook_permission_check() to dynamically adjust feature visibility?
        // See also: https://lab.civicrm.org/dev/core/-/issues/3961
        $preUpgradeMessage .= "<p>{$message}</p>";
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

  public static function consolidateComponents($ctx): bool {
    $final = static::findAllEnabledComponents();
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
