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
 * Upgrade logic for the 6.10.x series.
 *
 * Each minor version in the series is handled by either a `6.10.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_10_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixTen extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_10_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Ensure custom fields all have a name', 'ensureCustomFieldsHaveName');
    $this->addTask('Set custom field name as required', 'alterSchemaField', 'CustomField', 'name', [
      'title' => ts('Custom Field Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Variable name/programmatic handle for this field.'),
      'add' => '3.3',
    ]);

  }

  public static function ensureCustomFieldsHaveName() {
    $sql = 'SELECT id, label FROM civicrm_custom_field WHERE name IS NULL OR name = ""';
    $customFields = CRM_Core_DAO::executeQuery($sql);
    while ($customFields->fetch()) {
      $customField = new CRM_Core_DAO_CustomField();
      $customField->id = $customFields->id;
      $customField->name = CRM_Utils_String::munge($customFields->label);
      try {
        $customField->save();
      }
      catch (Exception $e) {
        // Already a field with that name; append ID to make it unique
        $customField->name = substr($customField->name, 0, 63 - strlen($customField->id)) . "_{$customField->id}";
        $customField->save();
      }
    }
    return TRUE;
  }

}
