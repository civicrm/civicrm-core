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
 * Upgrade logic for the 5.49.x series.
 *
 * Each minor version in the series is handled by either a `5.49.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_49_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFortyNine extends CRM_Upgrade_Incremental_Base {

  public function setpostUpgradeMessage(&$preUpgradeMessage, $rev) {
    if ($rev == '5.49.beta1') {
      if ($this->hasConfigBackendData()) {
        $postUpgradeMessage .= '<br/>' . ts("WARNING: The column \"<code>civicrm_action_schedule.limit_to</code>\" is now a required boolean field. Please ensure all the schedule reminders (especially with 'Also Include' option) are correct. For more detail please check <a href='%1' target='_blank'>here</a>.", [
          1 => 'https://lab.civicrm.org/dev/core/-/issues/3464',
        ]);
      }
    }
  }

  public static function findBooleanColumns(): array {
    $r = [];
    $files = CRM_Utils_File::findFiles(__DIR__ . '/FiveFortyNine', '*.bool.php');
    foreach ($files as $file) {
      $r = array_merge($r, require $file);
    }
    return $r;
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_49_alpha1($rev): void {
    $this->addTask('Add civicrm_contact_type.icon column', 'addColumn',
      'civicrm_contact_type', 'icon', "varchar(255) DEFAULT NULL COMMENT 'crm-i icon class representing this contact type'"
    );
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add civicrm_option_group.option_value_fields column', 'addColumn',
      'civicrm_option_group', 'option_value_fields', "varchar(128) DEFAULT \"name,label,description\" COMMENT 'Which optional columns from the option_value table are in use by this group.'");
    $this->addTask('Populate civicrm_option_group.option_value_fields column', 'fillOptionValueFields');
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_49_beta1($rev): void {
    foreach (static::findBooleanColumns() as $tableName => $columns) {
      foreach ($columns as $columnName => $defn) {
        $this->addTask("Update $tableName.$columnName to be NOT NULL", 'changeBooleanColumn', $tableName, $columnName, $defn);
      }
    }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_49_2($rev) {
    if (version_compare(CRM_Core_BAO_Domain::version(), '5.49', '>=') {
      $this->addtask('Revert civicrm_action_schedule.limit_to to be NULL', 'changeBooleanColumnLimitTo');
    }
  }

  /**
   * Revert boolean default civicrm_action_schedule.limit_to to be NULL
   */
  public static function changeBooleanColumnLimitTo() {
    CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_action_schedule` CHANGE `limit_to` `limit_to` tinyint NULL COMMENT 'Is this the recipient criteria limited to OR in addition to?'", [], TRUE, NULL, FALSE, FALSE);
    CRM_Core_DAO::executeQuery("UPDATE `civicrm_action_schedule` SET `limit_to` = NULL WHERE `group_id` IS NULL AND recipient_manual IS NULL", [], TRUE, NULL, FALSE, FALSE);
    return TRUE;
  }

  /**
   * Converts a boolean table column to be NOT NULL
   * @param CRM_Queue_TaskContext $ctx
   * @param string $tableName
   * @param string $columnName
   * @param string $defn
   */
  public static function changeBooleanColumn(CRM_Queue_TaskContext $ctx, $tableName, $columnName, $defn) {
    CRM_Core_DAO::executeQuery("UPDATE `$tableName` SET `$columnName` = 0 WHERE `$columnName` IS NULL", [], TRUE, NULL, FALSE, FALSE);
    CRM_Core_DAO::executeQuery("ALTER TABLE `$tableName` CHANGE `$columnName` `$columnName` tinyint NOT NULL $defn", [], TRUE, NULL, FALSE, FALSE);
    return TRUE;
  }

  public static function fillOptionValueFields(CRM_Queue_TaskContext $ctx) {
    // By default every option group uses 'name,description'
    // Note: description doesn't make sense for every group, but historically Civi has been lax
    // about restricting its use.
    CRM_Core_DAO::executeQuery("UPDATE `civicrm_option_group` SET `option_value_fields` = 'name,label,description'", [], TRUE, NULL, FALSE, FALSE);

    $groupsWithDifferentFields = [
      'name,label,description,color' => [
        'activity_status',
        'case_status',
      ],
      'name,label,description,icon' => [
        'activity_type',
      ],
    ];
    foreach ($groupsWithDifferentFields as $fields => $names) {
      $in = '"' . implode('","', $names) . '"';
      CRM_Core_DAO::executeQuery("UPDATE `civicrm_option_group` SET `option_value_fields` = '$fields' WHERE `name` IN ($in)", [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

}
