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

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL): void {
    parent::setPreUpgradeMessage($preUpgradeMessage, $rev, $currentVer);
    if ($rev === '6.18.alpha1') {
      $preUpgradeMessage .= '<p>' .
        ts('The FormBuilder HTML Editor extension has been removed. Editing is now possible directly in FormBuilder; grant users the "FormBuilder: edit raw HTML markup" permission for access.') .
        '</p>';
      $customPHPDir = CRM_Core_Config::singleton()->customPHPPathDir;
      if (!empty($customPHPDir)) {
        if (file_exists(CRM_Utils_File::addTrailingSlash($customPHPDir) . 'civicrmHooks.php')) {
          $message = ts('This installation contains a legacy civicrmHooks.php file within the customPHPDir. This will no longer be used by CiviCRM, System Administrators should work on migrating the hooks into an extension');
          $preUpgradeMessage .= "<p>{$message}</p>";
        }
        $activityClassFound = FALSE;
        $activityTypes = CRM_Core_DAO::executeQuery("SELECT ov.name FROM civicrm_option_value ov INNER JOIN civicrm_option_group og ON og.id = ov.option_group_id WHERE og.name = 'activity_type'");
        while ($activityTypes->fetch()) {
          if (!$activityClassFound &&
            (file_exists(CRM_Utils_File::addTrailingSlash($customPHPDir) . "CRM/Activity/Form/Activity/{$activityTypes->name}.php")
              || file_exists(CRM_Utils_File::addTrailingSlash($customPHPDir) . "CRM/Case/Form/Activity/{$activityTypes->name}.php"))) {
            $activityClassFound = TRUE;
          }
        }
        if ($activityClassFound) {
          $message = ts('This installation contains custom code for Activity Type forms that are within a legacy custom PHP directory. They should be moved to a custom extension, using the same directory structure.');
          $preUpgradeMessage .= "<p>{$message}</p>";
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
    $this->addTask('Change column "Mailing.name" to varchar(255)', 'alterSchemaField', 'Mailing', 'name', [
      'sql_type' => 'varchar(255)',
      'description' => ts('Mailing Name.'),
    ]);
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

}
