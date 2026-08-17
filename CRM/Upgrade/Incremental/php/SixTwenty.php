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
 * Upgrade logic for the 6.20.x series.
 *
 * Each minor version in the series is handled by either a `6.20.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_20_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixTwenty extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_20_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Install Order Completion Metadata entity', 'createEntityTable', '6.20.alpha1.OrderCompletionMetadata.entityType.php');
    $this->addTask('Change case start date from date to datetime', 'alterSchemaField', 'Case', 'start_date', [
      'title' => ts('Case Start Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => ts('Date on which given case starts.'),
      'default' => 'CURRENT_TIMESTAMP',
      'add' => '1.8',
    ]);

    $this->addTask('Add time to existing cases based on time of open case activity ', 'backFillCaseStartTime');
  }

  /**
   * Backfill case start time.
   *
   * We are changing the case start_date from a date to a datetime field. This
   * query uses the time from any open case activities to back fill the time
   * for previously created cases, but only if the date matches.
   *
   */
  public static function backFillCaseStartTime(CRM_Queue_TaskContext $ctx): bool {
    $sql = "SELECT ov.value AS value
      FROM
        civicrm_option_value ov INNER JOIN civicrm_option_group og ON (ov.option_group_id = og.id AND og.name = 'activity_type')
      WHERE ov.name = 'Open Case'";

    $dao = \CRM_Core_DAO::executeQuery($sql);
    $dao->fetch();
    $activityTypeId = $dao->value;
    if (!$activityTypeId) {
      // Maybe cases are not enabled?
      return TRUE;
    }

    $sql = "
      UPDATE civicrm_case c
        JOIN civicrm_case_activity ca ON c.id = ca.case_id
        JOIN civicrm_activity a ON ca.activity_id = a.id
      SET
        c.start_date = a.activity_date_time
      WHERE
        DATE(a.activity_date_time) = DATE(c.start_date) AND
        a.is_deleted = 0 AND
        a.activity_type_id = %0
    ";
    $params = [0 => [$activityTypeId, "Integer"]];
    \CRM_Core_DAO::executeQuery($sql, $params);

    return TRUE;
  }

}
