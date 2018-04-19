<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5  .alpha1                                         |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Upgrade logic for 4.6
 */
class CRM_Upgrade_Incremental_php_FourSix extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    if ($rev == '4.6.alpha1') {
      $postUpgradeMessage .= '<br /><br />' . ts('Default versions of the following System Workflow Message Templates have been modified to handle new functionality: <ul><li>Events - Registration Confirmation and Receipt (on-line)</li><li>Events - Registration Confirmation and Receipt (off-line)</li><li>Contributions - Receipt (on-line)</li><li>Contributions - Receipt (off-line)</li><li>Memberships - Receipt (on-line)</li><li>Memberships - Signup and Renewal Receipts (off-line)</li></ul> If you have modified these templates, please review the new default versions and implement updates as needed to your copies (Administer > Communications > Message Templates > System Workflow Messages).');
    }
    if ($rev == '4.6.alpha3') {
      $postUpgradeMessage .= '<br /><br />' . ts('A new permission has been added for editing message templates. Previously, users needed the "administer CiviCRM" permission. Now, users need the new permission called "edit message templates." Please check your CMS permissions to ensure that users who should be able to edit message templates are assigned this new permission.');
    }
  }

  /**
   * CRM-16846 - This function incorrectly omits running the 4.6.alpha3 sql file.
   *
   * Instead of correcting it here (which would not run again for sites already on 4.6),
   * the file is re-run conditionally during 4.6.6
   * @see upgrade_4_6_6
   *
   * @param string $rev
   */
  public function upgrade_4_6_alpha3($rev) {
    // Task to process sql.
    $this->addTask('Add and update reference_date column for Schedule Reminders', 'updateReferenceDate');
  }

  /**
   * Add new column reference_date to civicrm_action_log in order to track.
   *
   * CRM-15728, actual action_start_date for membership entity for only those schedule reminders which are not repeatable
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function updateReferenceDate(CRM_Queue_TaskContext $ctx) {
    //Add column civicrm_action_log.reference_date if not exists.
    $sql = "SELECT count(*) FROM information_schema.columns WHERE table_schema = database() AND table_name = 'civicrm_action_log' AND COLUMN_NAME = 'reference_date' ";
    $res = CRM_Core_DAO::singleValueQuery($sql);

    if ($res <= 0) {
      $query = "ALTER TABLE `civicrm_action_log`
 ADD COLUMN `reference_date` date COMMENT 'Stores the date from the entity which triggered this reminder action (e.g. membership.end_date for most membership renewal reminders)'";
      CRM_Core_DAO::executeQuery($query);
    }

    //Retrieve schedule reminders for membership entity and is not repeatable and no absolute date chosen.
    $query = "SELECT schedule.* FROM civicrm_action_schedule schedule
 INNER JOIN civicrm_action_mapping mapper ON mapper.id = schedule.mapping_id AND
   mapper.entity = 'civicrm_membership' AND
   schedule.is_repeat = 0 AND
   schedule.start_action_date IS NOT NULL";

    // construct basic where clauses
    $where = array(
      'reminder.action_date_time >= DATE_SUB(reminder.action_date_time, INTERVAL 9 MONTH)',
    ); //choose reminder older then 9 months
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {

      $referenceColumn = str_replace('membership_', "m.", $dao->start_action_date);
      $value = implode(', ', explode(CRM_Core_DAO::VALUE_SEPARATOR, trim($dao->entity_value, CRM_Core_DAO::VALUE_SEPARATOR)));
      if (!empty($value)) {
        $where[] = "m.membership_type_id IN ({$value})";
      }
      else {
        $where[] = "m.membership_type_id IS NULL";
      }

      //Create new action_log records where action_start_date changes and exclude reminders for additional contacts
      //and select contacts are active
      $sql = "UPDATE civicrm_action_log reminder
 LEFT JOIN civicrm_membership m
   ON reminder.entity_id = m.id AND
   reminder.entity_table = 'civicrm_membership' AND
   ( m.is_override IS NULL OR m.is_override = 0 )
 INNER JOIN civicrm_contact c
   ON c.id = m.contact_id AND
   c.is_deleted = 0 AND c.is_deceased = 0
 SET reminder.reference_date = {$referenceColumn}
 WHERE " . implode(" AND ", $where);
      CRM_Core_DAO::executeQuery($sql);
    }

    return TRUE;
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_6_1($rev) {
    // CRM-16289 - Fix invalid data in log_civicrm_case.case_type_id.
    $this->addTask('Cleanup case type id data in log table.', 'fixCaseLog');
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_4_6_6($rev) {
    // CRM-16846 - This sql file may have been previously skipped. Conditionally run it again if it doesn't appear to have run before.
    if (!CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_state_province WHERE abbreviation = '100' AND country_id = 1193")) {
      $this->addTask('Update Slovenian municipalities', 'task_4_6_x_runOnlySql', '4.6.alpha3');
    }
    // CRM-16846 - This sql file may have been previously skipped. No harm in running it again because it's just UPDATE statements.
    $this->addTask('State-province update from 4.4.7', 'task_4_6_x_runOnlySql', '4.4.7');

    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
  }

  /**
   * Remove special characters from case_type_id column in log_civicrm_case.
   *
   * CRM-16289 - If logging enabled and upgrading from 4.4 or earlier, log_civicrm_case.case_type_id will contain special characters.
   * This will cause ALTER TABLE to fail when changing this column to an INT
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function fixCaseLog(CRM_Queue_TaskContext $ctx) {
    $sql = "SELECT count(*) FROM information_schema.columns WHERE table_schema = database() AND table_name = 'log_civicrm_case'";
    $res = CRM_Core_DAO::singleValueQuery($sql);

    if ($res) {
      // executeQuery doesn't like running multiple engine changes in one pass, so have to break it up. dgg
      $query = "ALTER TABLE `log_civicrm_case` ENGINE = InnoDB;";
      CRM_Core_DAO::executeQuery($query);
      $query = "UPDATE log_civicrm_case SET case_type_id = replace(case_type_id, 0x01, '');";
      CRM_Core_DAO::executeQuery($query);
      $query = "ALTER TABLE `log_civicrm_case` ENGINE = ARCHIVE;";
      CRM_Core_DAO::executeQuery($query);
      $query = "ALTER TABLE log_civicrm_case MODIFY `case_type_id` int(10) unsigned DEFAULT NULL COMMENT 'FK to civicrm_case_type.id';";
      CRM_Core_DAO::executeQuery($query);
    }
    return TRUE;
  }

  /**
   * Queue Task Callback for CRM-16846
   *
   * Run a sql file without resetting locale to that version
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @param string $rev
   *
   * @return bool
   */
  public static function task_4_6_x_runOnlySql(CRM_Queue_TaskContext $ctx, $rev) {
    $upgrade = new CRM_Upgrade_Form();
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('domainID', CRM_Core_Config::domainID());

    $fileName = dirname(__DIR__) . "/sql/$rev.mysql.tpl";

    $upgrade->source($smarty->fetch($fileName), TRUE);

    return TRUE;
  }

}
