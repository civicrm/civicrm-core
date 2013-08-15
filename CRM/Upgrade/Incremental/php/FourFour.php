<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Upgrade_Incremental_php_FourFour {
  const BATCH_SIZE = 5000;

  function verifyPreDBstate(&$errors) {
    return TRUE;
  }

  /**
   * Compute any messages which should be displayed beforeupgrade
   *
   * Note: This function is called iteratively for each upcoming
   * revision to the database.
   *
   * @param $postUpgradeMessage string, alterable
   * @param $rev string, a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'
   * @return void
   */
  function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
  }

  /**
   * Compute any messages which should be displayed after upgrade
   *
   * @param $postUpgradeMessage string, alterable
   * @param $rev string, an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs
   * @return void
   */
  function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    if ($rev == '4.4.alpha1') {
      $config = CRM_Core_Config::singleton();
      if (!empty($config->useIDS)) {
        $postUpgradeMessage .= '<br />' . ts("The setting to skip IDS check has been deprecated. Please use the permission 'skip IDS check' to bypass the IDS system");
      }
    }
  }

  function upgrade_4_4_alpha1($rev) {
    // task to process sql
    $this->addTask(ts('Upgrade DB to 4.4.alpha1: SQL'), 'task_4_4_x_runSql', $rev);

    // Consolidate activity contacts CRM-12274.
    $this->addTask(ts('Consolidate activity contacts'), 'activityContacts');

    return TRUE;
  }

  /**
   * Update activity contacts CRM-12274
   *
   * @return bool TRUE for success
   */
  static function activityContacts(CRM_Queue_TaskContext $ctx) {
    $upgrade = new CRM_Upgrade_Form();

    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $ovValue[] = $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $ovValue[] = $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $ovValue[] = $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    $optionGroupID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'activity_contacts', 'id', 'name');
    if (!empty($ovValue)) {
      $ovValues = implode(', ', $ovValue);
      $query = "
UPDATE civicrm_option_value
SET    is_reserved = 1
WHERE  option_group_id = {$optionGroupID} AND value IN ($ovValues)";

      $dao = CRM_Core_DAO::executeQuery($query);
    }

    if (!$assigneeID) {
      $assigneeID = 1;
      $value[] = "({$optionGroupID}, 'Activity Assignees', 1, 'Activity Assignees', 1, 1, 1)";
    }
    if (!$sourceID) {
      $sourceID = 2;
      $value[] = "({$optionGroupID}, 'Activity Source', 2, 'Activity Source', 2, 1, 1)";
    }
    if (!$targetID) {
      $targetID = 3;
      $value[] = "({$optionGroupID}, 'Activity Targets', 3, 'Activity Targets', 3, 1, 1)";
    }

    if (!$assigneeID || !$sourceID || !$targetID ) {
      $insert =  "
INSERT INTO civicrm_option_value
(option_group_id, label, value, name, weight, is_reserved, is_active)
VALUES

";
      $values = implode(', ', $value);
      $query = $insert . $values;
      $dao = CRM_Core_DAO::executeQuery($query);
    }


    $query = "
CREATE TABLE IF NOT EXISTS civicrm_activity_contact (
  id int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Activity contact id',
  activity_id int(10) unsigned NOT NULL COMMENT 'Foreign key to the activity for this record.',
  contact_id int(10) unsigned NOT NULL COMMENT 'Foreign key to the contact for this record.',
  record_type_id int(10) unsigned DEFAULT NULL COMMENT 'The record type id for this row',
  PRIMARY KEY (id),
  UNIQUE KEY UI_activity_contact (contact_id,activity_id,record_type_id),
  KEY FK_civicrm_activity_contact_activity_id (activity_id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
";

    $dao = CRM_Core_DAO::executeQuery($query);

    $query = "
INSERT INTO civicrm_activity_contact (activity_id, contact_id, record_type_id)
SELECT      activity_id, target_contact_id, {$targetID} as record_type_id
FROM        civicrm_activity_target";

    $dao = CRM_Core_DAO::executeQuery($query);

    $query = "
INSERT INTO civicrm_activity_contact (activity_id, contact_id, record_type_id)
SELECT      activity_id, assignee_contact_id, {$assigneeID} as record_type_id
FROM        civicrm_activity_assignment";
    $dao = CRM_Core_DAO::executeQuery($query);

    $query = "
  INSERT INTO civicrm_activity_contact (activity_id, contact_id, record_type_id)
SELECT      id, source_contact_id, {$sourceID} as record_type_id
FROM        civicrm_activity
WHERE       source_contact_id IS NOT NULL";

    $dao = CRM_Core_DAO::executeQuery($query);

   $query = "DROP TABLE civicrm_activity_target";
   $dao = CRM_Core_DAO::executeQuery($query);

   $query = "DROP TABLE civicrm_activity_assignment";
   $dao = CRM_Core_DAO::executeQuery($query);

   $query = "ALTER  TABLE civicrm_activity
     DROP FOREIGN KEY FK_civicrm_activity_source_contact_id";

   $dao = CRM_Core_DAO::executeQuery($query);

   $query = "ALTER  TABLE civicrm_activity DROP COLUMN source_contact_id";
   $dao = CRM_Core_DAO::executeQuery($query);

   return TRUE;
  }

  /**
   * (Queue Task Callback)
   */
  static function task_4_4_x_runSql(CRM_Queue_TaskContext $ctx, $rev) {
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->processSQL($rev);

    return TRUE;
  }

  /**
   * Syntatic sugar for adding a task which (a) is in this class and (b) has
   * a high priority.
   *
   * After passing the $funcName, you can also pass parameters that will go to
   * the function. Note that all params must be serializable.
   */
  protected function addTask($title, $funcName) {
    $queue = CRM_Queue_Service::singleton()->load(array(
      'type' => 'Sql',
      'name' => CRM_Upgrade_Form::QUEUE_NAME,
    ));

    $args = func_get_args();
    $title = array_shift($args);
    $funcName = array_shift($args);
    $task = new CRM_Queue_Task(
      array(get_class($this), $funcName),
      $args,
      $title
    );
    $queue->createItem($task, array('weight' => -1));
  }
}
