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

use Civi\Api4\OptionValue;

/**
 * Upgrade logic for the 6.18.x series.
 *
 * Each minor version in the series is handled by either a `6.18.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_18_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixEighteen extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_18_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add column "RelationshipType.weight"', 'alterSchemaField', 'RelationshipType', 'weight', [
      'title' => ts('Order'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Ordering of the relationship types.'),
      'add' => '6.18',
      'default' => 0,
    ]);
    $this->addTask(ts('Initialize relationship type weights'), 'initializeRelationshipTypeWeights');
  }

  /**
   * Initialize relationship type weights.
   *
   * @param CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function initializeRelationshipTypeWeights(CRM_Queue_TaskContext $ctx): bool {
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_relationship_type
      SET weight = id
      WHERE weight = 0
    ");

    return TRUE;
  }

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL): void {
    if ($rev === '6.18.alpha1') {
      $customPHPDir = $config = CRM_Core_Config::singleton()->customPHPPathDir;
      if (!empty($customPHPDir)) {
        if (file_exists(CRM_Utils_File::addTrailingSlash($config->customPHPPathDir) . 'civicrmHooks.php')) {
          $message = ts('This instalation contains a legacy civicrmHooks.php file within the customPHPDir. This will no longer be used by CiviCRM, System Administrators should work on migrating the hooks into an extension');
          $preUpgradeMessage .= "<p>{$message}</p>";
        }
        $activityClassFound = FALSE;
        $activityTypes = OptionValue::get(FALSE)
          ->addWhere('option_group_id:name', '=', 'activity_type')
          ->execute()
          ->column('name');
        foreach ($activityTypes as $activityType) {
          if (!$activityClassFound &&
            (file_exists(CRM_Utils_File::addTrailingSlash($config->customPHPPathDir) . "CRM/Activity/Form/Activity/{$activityType}.php")
              || file_exists(CRM_Utils_File::addTrailingSlash($config->customPHPPathDir) . "CRM/Case/Form/Activity/{$activityType}.php"))) {
            $activityClassFound = TRUE;
          }
        }
        if ($activityClassFound) {
          $message = ts('This site contains Activity Type form classes that are within a legacy custom PHP Directory folder. THey should be moved to being within an extension in the same folder path');
          $preUpgradeMessage .= "<p>{$message}</p>";
        }
      }
    }
  }

}
