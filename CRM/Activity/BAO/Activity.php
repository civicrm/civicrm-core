<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class is for activity functions
 *
 */
class CRM_Activity_BAO_Activity extends CRM_Activity_DAO_Activity {

  /**
   * static field for all the activity information that we can potentially export
   *
   * @var array
   * @static
   */
  static $_exportableFields = NULL;

  /**
   * static field for all the activity information that we can potentially import
   *
   * @var array
   * @static
   */
  static $_importableFields = NULL;

  /**
   * Check if there is absolute minimum of data to add the object
   *
   * @param array  $params         (reference ) an assoc array of name/value pairs
   *
   * @return boolean
   * @access public
   */
  public static function dataExists(&$params) {
    if (!empty($params['source_contact_id']) || !empty($params['id'])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @internal param string $activityType activity type
   *
   * @return object CRM_Core_BAO_Meeting object
   * @access public
   */
  public static function retrieve(&$params, &$defaults) {
    $activity = new CRM_Activity_DAO_Activity();
    $activity->copyValues($params);

    if ($activity->find(TRUE)) {
      $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
      $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
      $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
      $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);

      // TODO: at some stage we'll have to deal
      // TODO: with multiple values for assignees and targets, but
      // TODO: for now, let's just fetch first row
      $defaults['assignee_contact'] = CRM_Activity_BAO_ActivityContact::retrieveContactIdsByActivityId($activity->id, $assigneeID);
      $assignee_contact_names = CRM_Activity_BAO_ActivityContact::getNames($activity->id, $assigneeID);
      $defaults['assignee_contact_value'] = implode('; ', $assignee_contact_names);
      $sourceContactId = self::getActivityContact($activity->id, $sourceID);
      if ($activity->activity_type_id != CRM_Core_OptionGroup::getValue('activity_type', 'Bulk Email', 'name')) {
        $defaults['target_contact'] = CRM_Activity_BAO_ActivityContact::retrieveContactIdsByActivityId($activity->id, $targetID);
        $target_contact_names = CRM_Activity_BAO_ActivityContact::getNames($activity->id, $targetID);
        $defaults['target_contact_value'] = implode('; ', $target_contact_names);
      }
      elseif (CRM_Core_Permission::check('access CiviMail') ||
        (CRM_Mailing_Info::workflowEnabled() &&
          CRM_Core_Permission::check('create mailings')
        )
      ) {
        $defaults['mailingId'] = CRM_Utils_System::url('civicrm/mailing/report',
          "mid={$activity->source_record_id}&reset=1&atype={$activity->activity_type_id}&aid={$activity->id}&cid={$sourceContactId}&context=activity"
        );
      }
      else {
        $defaults['target_contact_value'] = ts('(recipients)');
      }

      $sourceContactId = self::getActivityContact($activity->id, $sourceID);
      $defaults['source_contact_id'] = $sourceContactId;

      if ($sourceContactId &&
        !CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $sourceContactId,
          'is_deleted'
        )
      ) {
        $defaults['source_contact'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $sourceContactId,
          'sort_name'
        );
      }

      //get case subject
      $defaults['case_subject'] = CRM_Case_BAO_Case::getCaseSubject($activity->id);

      CRM_Core_DAO::storeValues($activity, $defaults);

      return $activity;
    }
    return NULL;
  }

  /**
   * Function to delete the activity
   *
   * @param array $params associated array
   *
   * @param bool $moveToTrash
   *
   * @return void
   * @access public
   */
  public static function deleteActivity(&$params, $moveToTrash = FALSE) {
    // CRM-9137
    if (!empty($params['id']) && !is_array($params['id'])) {
      CRM_Utils_Hook::pre('delete', 'Activity', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('delete', 'Activity', NULL, $params);
    }

    $transaction = new CRM_Core_Transaction();
    if (is_array(CRM_Utils_Array::value('source_record_id', $params))) {
      $sourceRecordIds = implode(',', $params['source_record_id']);
    }
    else {
      $sourceRecordIds = CRM_Utils_Array::value('source_record_id', $params);
    }

    $result = NULL;
    if (!$moveToTrash) {
      if (!isset($params['id'])) {
        if (is_array($params['activity_type_id'])) {
          $activityTypes = implode(',', $params['activity_type_id']);
        }
        else {
          $activityTypes = $params['activity_type_id'];
        }

        $query = "DELETE FROM civicrm_activity WHERE source_record_id IN ({$sourceRecordIds}) AND activity_type_id IN ( {$activityTypes} )";
        $dao = CRM_Core_DAO::executeQuery($query);
      }
      else {
        $activity = new CRM_Activity_DAO_Activity();
        $activity->copyValues($params);
        $result = $activity->delete();

        // CRM-8708
        $activity->case_id = CRM_Case_BAO_Case::getCaseIdByActivityId($activity->id);

        // CRM-13994 delete activity entity_tag
        $query = "DELETE FROM civicrm_entity_tag WHERE entity_table = 'civicrm_activity' AND entity_id = {$activity->id}";
        $dao = CRM_Core_DAO::executeQuery($query);
      }
    }
    else {
      $activity = new CRM_Activity_DAO_Activity();
      $activity->copyValues($params);

      $activity->is_deleted = 1;
      $result = $activity->save();

      // CRM-4525 log activity delete
      $logMsg = 'Case Activity deleted for';
      $msgs = array();

      $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
      $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
      $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
      $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
      $sourceContactId = self::getActivityContact($activity->id, $sourceID);
      if ($sourceContactId) {
        $msgs[] = " source={$sourceContactId}";
      }

      //get target contacts.
      $targetContactIds = CRM_Activity_BAO_ActivityContact::getNames($activity->id, $targetID);
      if (!empty($targetContactIds)) {
        $msgs[] = " target =" . implode(',', array_keys($targetContactIds));
      }
      //get assignee contacts.
      $assigneeContactIds = CRM_Activity_BAO_ActivityContact::getNames($activity->id, $assigneeID);
      if (!empty($assigneeContactIds)) {
        $msgs[] = " assignee =" . implode(',', array_keys($assigneeContactIds));
      }

      $logMsg .= implode(', ', $msgs);

      self::logActivityAction($activity, $logMsg);
    }

    // delete the recently created Activity
    if ($result) {
      $activityRecent = array(
        'id' => $activity->id,
        'type' => 'Activity',
      );
      CRM_Utils_Recent::del($activityRecent);
    }

    $transaction->commit();
    if (isset($activity)) {
      // CRM-8708
      $activity->case_id = CRM_Case_BAO_Case::getCaseIdByActivityId($activity->id);
      CRM_Utils_Hook::post('delete', 'Activity', $activity->id, $activity);
    }

    return $result;
  }

  /**
   * Delete activity assignment record
   *
   * @param $activityId
   * @param null $recordTypeID
   *
   * @internal param int $id activity id
   *
   * @return null
   * @access public
   */
  public static function deleteActivityContact($activityId, $recordTypeID = NULL) {
    $activityContact = new CRM_Activity_BAO_ActivityContact();
    $activityContact->activity_id = $activityId;
    if ($recordTypeID) {
      $activityContact->record_type_id = $recordTypeID;
    }
    $activityContact->delete();
  }

  /**
   * Function to process the activities
   *
   * @param array $params associated array of the submitted values
   *
   * @throws CRM_Core_Exception
   * @internal param object $form form object
   * @internal param array $ids array of ids
   * @internal param string $activityType activity Type
   * @internal param bool $record true if it is Record Activity
   * @access public
   *
   * @return $this|null|object
   */
  public static function create(&$params) {
    // check required params
    if (!self::dataExists($params)) {
      throw new CRM_Core_Exception('Not enough data to create activity object');
    }

    $activity = new CRM_Activity_DAO_Activity();

    if (isset($params['id']) && empty($params['id'])) {
      unset($params['id']);
    }

    if (empty($params['status_id']) && empty($params['activity_status_id']) && empty($params['id'])) {
      if (isset($params['activity_date_time']) &&
        strcmp($params['activity_date_time'], CRM_Utils_Date::processDate(date('Ymd')) == -1)
      ) {
        $params['status_id'] = 2;
      }
      else {
        $params['status_id'] = 1;
      }
    }

    //set priority to Normal for Auto-populated activities (for Cases)
    if (CRM_Utils_Array::value('priority_id', $params) === NULL &&
      // if not set and not 0
      !CRM_Utils_Array::value('id', $params)
    ) {
      $priority = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'priority_id');
      $params['priority_id'] = array_search('Normal', $priority);
    }

    if (!empty($params['target_contact_id']) && is_array($params['target_contact_id'])) {
      $params['target_contact_id'] = array_unique($params['target_contact_id']);
    }
    if (!empty($params['assignee_contact_id']) && is_array($params['assignee_contact_id'])) {
      $params['assignee_contact_id'] = array_unique($params['assignee_contact_id']);
    }

    // CRM-9137
    if (!empty($params['id'])) {
      CRM_Utils_Hook::pre('edit', 'Activity', $activity->id, $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'Activity', NULL, $params);
    }

    $activity->copyValues($params);
    if (isset($params['case_id'])) {
      // CRM-8708, preserve case ID even though it's not part of the SQL model
      $activity->case_id = $params['case_id'];
    }
    elseif (is_numeric($activity->id)) {
      // CRM-8708, preserve case ID even though it's not part of the SQL model
      $activity->case_id = CRM_Case_BAO_Case::getCaseIdByActivityId($activity->id);
    }

    // start transaction
    $transaction = new CRM_Core_Transaction();

    $result = $activity->save();

    if (is_a($result, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $result;
    }

    $activityId = $activity->id;
    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    if (isset($params['source_contact_id'])) {
      $acParams = array(
        'activity_id' => $activityId,
        'contact_id'  => $params['source_contact_id'],
        'record_type_id' => $sourceID
      );
      self::deleteActivityContact($activityId, $sourceID);
      CRM_Activity_BAO_ActivityContact::create($acParams);
    }

    // check and attach and files as needed
    CRM_Core_BAO_File::processAttachment($params, 'civicrm_activity', $activityId);

    // attempt to save activity assignment
    $resultAssignment = NULL;
    if (!empty($params['assignee_contact_id'])) {

      $assignmentParams = array('activity_id' => $activityId);

      if (is_array($params['assignee_contact_id'])) {
        if (CRM_Utils_Array::value('deleteActivityAssignment', $params, TRUE)) {
          // first delete existing assignments if any
          self::deleteActivityContact($activityId, $assigneeID);
        }

        $values = array();
        foreach ($params['assignee_contact_id'] as $acID) {
          if ($acID) {
            $values[] = "( $activityId, $acID, $assigneeID )";
          }
        }
        while (!empty($values)) {
          $input = array_splice($values, 0, CRM_Core_DAO::BULK_INSERT_COUNT);
          $str   = implode(',', $input);
          $sql   = "INSERT IGNORE INTO civicrm_activity_contact ( activity_id, contact_id, record_type_id ) VALUES $str;";
          CRM_Core_DAO::executeQuery($sql);
        }
      }
      else {
        $assignmentParams['contact_id'] = $params['assignee_contact_id'];
        $assignmentParams['record_type_id'] = $assigneeID;
        if (!empty($params['id'])) {
          $assignment = new CRM_Activity_BAO_ActivityContact();
          $assignment->activity_id = $activityId;
          $assignment->record_type_id = $assigneeID;
          $assignment->find(TRUE);

          if ($assignment->contact_id != $params['assignee_contact_id']) {
            $assignmentParams['id'] = $assignment->id;
            $resultAssignment = CRM_Activity_BAO_ActivityContact::create($assignmentParams);
          }
        }
        else {
          $resultAssignment = CRM_Activity_BAO_ActivityContact::create($assignmentParams);
        }
      }
    }
    else {
      if (CRM_Utils_Array::value('deleteActivityAssignment', $params, TRUE)) {
        self::deleteActivityContact($activityId, $assigneeID);
      }
    }

    if (is_a($resultAssignment, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $resultAssignment;
    }

    // attempt to save activity targets
    $resultTarget = NULL;
    if (!empty($params['target_contact_id'])) {

      $targetParams = array('activity_id' => $activityId);
      $resultTarget = array();
      if (is_array($params['target_contact_id'])) {
        if (CRM_Utils_Array::value('deleteActivityTarget', $params, TRUE)) {
          // first delete existing targets if any
          self::deleteActivityContact($activityId, $targetID );
        }

        $values = array();
        foreach ($params['target_contact_id'] as $tid) {
          if ($tid) {
            $values[] = "( $activityId, $tid,  $targetID )";
          }
        }

        while (!empty($values)) {
          $input = array_splice($values, 0, CRM_Core_DAO::BULK_INSERT_COUNT);
          $str   = implode(',', $input);
          $sql   = "INSERT IGNORE INTO civicrm_activity_contact ( activity_id, contact_id, record_type_id ) VALUES $str;";
          CRM_Core_DAO::executeQuery($sql);
        }
      }
      else {
        $targetParams['contact_id'] = $params['target_contact_id'];
        $targetParams['record_type_id'] = $targetID;
        if (!empty($params['id'])) {
          $target = new CRM_Activity_BAO_ActivityContact();
          $target->activity_id = $activityId;
          $target->record_type_id = $targetID;
          $target->find(TRUE);

          if ($target->contact_id != $params['target_contact_id']) {
            $targetParams['id'] = $target->id;
            $resultTarget = CRM_Activity_BAO_ActivityContact::create($targetParams);
          }
        }
        else {
          $resultTarget = CRM_Activity_BAO_ActivityContact::create($targetParams);
        }
      }
    }
    else {
      if (CRM_Utils_Array::value('deleteActivityTarget', $params, TRUE)) {
        self::deleteActivityContact($activityId, $targetID );
      }
    }

    // write to changelog before transaction is committed/rolled
    // back (and prepare status to display)
    if (!empty($params['id'])) {
      $logMsg = "Activity (id: {$result->id} ) updated with ";
    }
    else {
      $logMsg = "Activity created for ";
    }

    $msgs = array();
    if (isset($params['source_contact_id'])) {
      $msgs[] = "source={$params['source_contact_id']}";
    }

    if (!empty($params['target_contact_id'])) {
      if (is_array($params['target_contact_id']) && !CRM_Utils_array::crmIsEmptyArray($params['target_contact_id'])) {
        $msgs[] = "target=" . implode(',', $params['target_contact_id']);
        // take only first target
        // will be used for recently viewed display
        $t = array_slice($params['target_contact_id'], 0, 1);
        $recentContactId = $t[0];
      }
      //is array check fixes warning without degrading functionality but it seems this bit of code may no longer work
      // as it may always be an array
      elseif (isset($params['target_contact_id']) && !is_array($params['target_contact_id'])) {
        $msgs[] = "target={$params['target_contact_id']}";
        // will be used for recently viewed display
        $recentContactId = $params['target_contact_id'];
      }
    }
    else {
      // at worst, take source for recently viewed display
      $recentContactId = CRM_Utils_Array::value('source_contact_id',$params);
    }

    if (isset($params['assignee_contact_id'])) {
      if (is_array($params['assignee_contact_id'])) {
        $msgs[] = "assignee=" . implode(',', $params['assignee_contact_id']);
      }
      else {
        $msgs[] = "assignee={$params['assignee_contact_id']}";
      }
    }
    $logMsg .= implode(', ', $msgs);

    self::logActivityAction($result, $logMsg);

    if (!empty($params['custom']) &&
      is_array($params['custom'])
    ) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_activity', $result->id);
    }

    $transaction->commit();
    if (empty($params['skipRecentView'])) {
      $recentOther = array();
      if (!empty($params['case_id'])) {
        $caseContactID = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseContact', $params['case_id'], 'contact_id', 'case_id');
        $url = CRM_Utils_System::url('civicrm/case/activity/view',
          "reset=1&aid={$activity->id}&cid={$caseContactID}&caseID={$params['case_id']}&context=home"
        );
      }
      else {
        $q = "action=view&reset=1&id={$activity->id}&atype={$activity->activity_type_id}&cid=" . CRM_Utils_Array::value('source_contact_id', $params) . "&context=home";
        if ($activity->activity_type_id != CRM_Core_OptionGroup::getValue('activity_type', 'Email', 'name')) {
          $url = CRM_Utils_System::url('civicrm/activity', $q);
          if ($activity->activity_type_id == CRM_Core_OptionGroup::getValue('activity_type', 'Print PDF Letter', 'name')) {
            $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/activity/pdf/add',
              "action=update&reset=1&id={$activity->id}&atype={$activity->activity_type_id}&cid={$params['source_contact_id']}&context=home"
            );
          }
          else {
            $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/activity/add',
              "action=update&reset=1&id={$activity->id}&atype={$activity->activity_type_id}&cid=" . CRM_Utils_Array::value('source_contact_id', $params) . "&context=home"
            );
          }

          if (CRM_Core_Permission::check("delete activities")) {
            $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/activity',
              "action=delete&reset=1&id={$activity->id}&atype={$activity->activity_type_id}&cid=" . CRM_Utils_Array::value('source_contact_id', $params) . "&context=home"
            );
          }
        }
        else {
          $url = CRM_Utils_System::url('civicrm/activity/view', $q);
          if (CRM_Core_Permission::check('delete activities')) {
            $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/activity',
              "action=delete&reset=1&id={$activity->id}&atype={$activity->activity_type_id}&cid=" . CRM_Utils_Array::value('source_contact_id', $params) . "&context=home"
            );
          }
        }
      }

      if (!isset($activity->parent_id)) {
        $recentContactDisplay = CRM_Contact_BAO_Contact::displayName($recentContactId);
        // add the recently created Activity
        $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE);
        $activitySubject = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $activity->id, 'subject');

        $title = "";
        if (isset($activitySubject)) {
          $title = $activitySubject . ' - ';
        }

        $title = $title . $recentContactDisplay;
        if (!empty($activityTypes[$activity->activity_type_id])) {
          $title .= ' (' . $activityTypes[$activity->activity_type_id] . ')';
        }

        CRM_Utils_Recent::add($title,
          $url,
          $activity->id,
          'Activity',
          $recentContactId,
          $recentContactDisplay,
          $recentOther
        );
      }
    }

    // reset the group contact cache since smart groups might be affected due to this
    CRM_Contact_BAO_GroupContactCache::remove();

    if (!empty($params['id'])) {
      CRM_Utils_Hook::post('edit', 'Activity', $activity->id, $activity);
    }
    else {
      CRM_Utils_Hook::post('create', 'Activity', $activity->id, $activity);
    }

    // if the subject contains a ‘[case #…]’ string, file that activity on the related case (CRM-5916)
    $matches = array();
    if (preg_match('/\[case #([0-9a-h]{7})\]/', CRM_Utils_Array::value('subject', $params), $matches)) {
      $key        = CRM_Core_DAO::escapeString(CIVICRM_SITE_KEY);
      $hash       = $matches[1];
      $query      = "SELECT id FROM civicrm_case WHERE SUBSTR(SHA1(CONCAT('$key', id)), 1, 7) = '$hash'";
      $caseParams = array(
        'activity_id' => $activity->id,
        'case_id' => CRM_Core_DAO::singleValueQuery($query),
      );
      if ($caseParams['case_id']) {
        CRM_Case_BAO_Case::processCaseActivity($caseParams);
      }
      else {
        self::logActivityAction($activity, "unknown case hash encountered: $hash");
      }
    }

    return $result;
  }

  /**
   * @param $activity
   * @param null $logMessage
   *
   * @return bool
   */
  public static function logActivityAction($activity, $logMessage = NULL) {
    $session = CRM_Core_Session::singleton();
    $id = $session->get('userID');
    if (!$id) {
      $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
      $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
      $id = self::getActivityContact($activity->id. $sourceID);
    }
    $logParams = array(
      'entity_table' => 'civicrm_activity',
      'entity_id' => $activity->id,
      'modified_id' => $id,
      'modified_date' => date('YmdHis'),
      'data' => $logMessage,
    );
    CRM_Core_BAO_Log::add($logParams);
    return TRUE;
  }

  /**
   * function to get the list Activities
   *
   * @param array   $input            array of parameters
   *    Keys include
   *    - contact_id  int            contact_id whose activities we want to retrieve
   *    - offset      int            which row to start from ?
   *    - rowCount    int            how many rows to fetch
   *    - sort        object|array   object or array describing sort order for sql query.
   *    - admin       boolean        if contact is admin
   *    - caseId      int            case ID
   *    - context     string         page on which selector is build
   *    - activity_type_id int|string the activitiy types we want to restrict by
   *
   * @return array (reference)      $values the relevant data object values of open activities
   *
   * @access public
   * @static
   */
  static function &getActivities($input) {
    //step 1: Get the basic activity data
    $bulkActivityTypeID = CRM_Core_OptionGroup::getValue(
      'activity_type',
      'Bulk Email',
      'name'
    );

    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    $config = CRM_Core_Config::singleton();

    $randomNum = md5(uniqid());
    $activityTempTable = "civicrm_temp_activity_details_{$randomNum}";

    $tableFields = array(
      'activity_id' => 'int unsigned',
      'activity_date_time' => 'datetime',
      'source_record_id' => 'int unsigned',
      'status_id' => 'int unsigned',
      'subject' => 'varchar(255)',
      'source_contact_name' => 'varchar(255)',
      'activity_type_id' => 'int unsigned',
      'activity_type' => 'varchar(128)',
      'case_id' => 'int unsigned',
      'case_subject' => 'varchar(255)',
      'campaign_id' => 'int unsigned',
    );

    $sql = "CREATE TEMPORARY TABLE {$activityTempTable} ( ";
    $insertValueSQL = array();
    // The activityTempTable contains the sorted rows
    // so in order to maintain the sort order as-is we add an auto_increment
    // field; we can sort by this later to ensure the sort order stays correct.
    $sql .= " fixed_sort_order INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,";
    foreach ($tableFields as $name => $desc) {
      $sql .= "$name $desc,\n";
      $insertValueSQL[] = $name;
    }

    // add unique key on activity_id just to be sure
    // this cannot be primary key because we need that for the auto_increment
    // fixed_sort_order field
    $sql .= "
          UNIQUE KEY ( activity_id )
        ) ENGINE=HEAP DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci
        ";

    CRM_Core_DAO::executeQuery($sql);

    $insertSQL = "INSERT INTO {$activityTempTable} (" . implode(',', $insertValueSQL) . " ) ";

    $order = $limit = $groupBy = '';
    $groupBy = " GROUP BY tbl.activity_id ";

    if (!empty($input['sort'])) {
      if (is_a($input['sort'], 'CRM_Utils_Sort')) {
        $orderBy = $input['sort']->orderBy();
        if (!empty($orderBy)) {
          $order = " ORDER BY $orderBy";
        }
      }
      elseif (trim($input['sort'])) {
        $sort = CRM_Utils_Type::escape($input['sort'], 'String');
        $order = " ORDER BY $sort ";
      }
    }

    if (empty($order)) {
      // context = 'activity' in Activities tab.
      $order = (CRM_Utils_Array::value('context', $input) == 'activity') ? " ORDER BY tbl.activity_date_time desc " : " ORDER BY tbl.status_id asc, tbl.activity_date_time asc ";
    }

    if (!empty($input['rowCount']) &&
      $input['rowCount'] > 0
    ) {
      $limit = " LIMIT {$input['offset']}, {$input['rowCount']} ";
    }

    $input['count'] = FALSE;
    list($sqlClause, $params) = self::getActivitySQLClause($input);

    $query = "{$insertSQL}
       SELECT DISTINCT tbl.*  from ( {$sqlClause} )
as tbl ";

    //filter case activities - CRM-5761
    $components = self::activityComponents();
    if (!in_array('CiviCase', $components)) {
      $query .= "
LEFT JOIN  civicrm_case_activity ON ( civicrm_case_activity.activity_id = tbl.activity_id )
    WHERE  civicrm_case_activity.id IS NULL";
    }

    $query = $query . $groupBy . $order . $limit;

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    // step 2: Get target and assignee contacts for above activities
    // create temp table for target contacts
    $activityContactTempTable = "civicrm_temp_activity_contact_{$randomNum}";
    $query = "CREATE TEMPORARY TABLE {$activityContactTempTable} (
                activity_id int unsigned, contact_id int unsigned, record_type_id varchar(16),
                 contact_name varchar(255), is_deleted int unsigned, counter int unsigned, INDEX index_activity_id( activity_id ) )
                ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";

    CRM_Core_DAO::executeQuery($query);

    // note that we ignore bulk email for targets, since we don't show it in selector
    $query = "
INSERT INTO {$activityContactTempTable} ( activity_id, contact_id, record_type_id, contact_name, is_deleted )
SELECT     ac.activity_id,
           ac.contact_id,
           ac.record_type_id,
           c.sort_name,
           c.is_deleted
FROM       {$activityTempTable}
INNER JOIN civicrm_activity a ON ( a.id = {$activityTempTable}.activity_id )
INNER JOIN civicrm_activity_contact ac ON ( ac.activity_id = {$activityTempTable}.activity_id )
INNER JOIN civicrm_contact c ON c.id = ac.contact_id
WHERE ac.record_type_id != %1
";
    $params = array(1 => array($targetID, 'Integer'));
    CRM_Core_DAO::executeQuery($query, $params);

    // for each activity insert one target contact
    // if we load all target contacts the performance will suffer a lot for mass-activities;
    $query = "
INSERT INTO {$activityContactTempTable} ( activity_id, contact_id, record_type_id, contact_name, is_deleted, counter )
SELECT     ac.activity_id,
           ac.contact_id,
           ac.record_type_id,
           c.sort_name,
           c.is_deleted,
           count(ac.contact_id)
FROM       {$activityTempTable}
INNER JOIN civicrm_activity a ON ( a.id = {$activityTempTable}.activity_id )
INNER JOIN civicrm_activity_contact ac ON ( ac.activity_id = {$activityTempTable}.activity_id )
INNER JOIN civicrm_contact c ON c.id = ac.contact_id
WHERE ac.record_type_id = %1
GROUP BY ac.activity_id
";

    CRM_Core_DAO::executeQuery($query, $params);

    // step 3: Combine all temp tables to get final query for activity selector
    // sort by the original sort order, stored in fixed_sort_order
    $query = "
SELECT     {$activityTempTable}.*,
           {$activityContactTempTable}.contact_id,
           {$activityContactTempTable}.record_type_id,
           {$activityContactTempTable}.contact_name,
           {$activityContactTempTable}.is_deleted,
           {$activityContactTempTable}.counter
FROM       {$activityTempTable}
INNER JOIN {$activityContactTempTable} on {$activityTempTable}.activity_id = {$activityContactTempTable}.activity_id
ORDER BY    fixed_sort_order
        ";

    $dao = CRM_Core_DAO::executeQuery($query);

    //CRM-3553, need to check user has access to target groups.
    $mailingIDs = CRM_Mailing_BAO_Mailing::mailingACLIDs();
    $accessCiviMail = (
      (CRM_Core_Permission::check('access CiviMail')) ||
      (CRM_Mailing_Info::workflowEnabled() &&
        CRM_Core_Permission::check('create mailings'))
    );

    //get all campaigns.
    $allCampaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);
    $values = array();
    while ($dao->fetch()) {
      $activityID = $dao->activity_id;
      $values[$activityID]['activity_id'] = $dao->activity_id;
      $values[$activityID]['source_record_id'] = $dao->source_record_id;
      $values[$activityID]['activity_type_id'] = $dao->activity_type_id;
      $values[$activityID]['activity_type'] = $dao->activity_type;
      $values[$activityID]['activity_date_time'] = $dao->activity_date_time;
      $values[$activityID]['status_id'] = $dao->status_id;
      $values[$activityID]['subject'] = $dao->subject;
      $values[$activityID]['campaign_id'] = $dao->campaign_id;

      if ($dao->campaign_id) {
        $values[$activityID]['campaign'] = $allCampaigns[$dao->campaign_id];
      }

      if (empty($values[$activityID]['assignee_contact_name'])) {
        $values[$activityID]['assignee_contact_name'] = array();
      }

      if (empty($values[$activityID]['target_contact_name'])) {
        $values[$activityID]['target_contact_name'] = array();
        $values[$activityID]['target_contact_counter'] = $dao->counter;
      }

      // if deleted, wrap in <del>
      if ( $dao->is_deleted ) {
        $dao->contact_name = "<del>{$dao->contact_name}</dao>";
      }

      if ($dao->record_type_id == $sourceID  && $dao->contact_id) {
        $values[$activityID]['source_contact_id'] = $dao->contact_id;
        $values[$activityID]['source_contact_name'] = $dao->contact_name;
      }

      if (!$bulkActivityTypeID || ($bulkActivityTypeID != $dao->activity_type_id)) {
        // build array of target / assignee names
        if ($dao->record_type_id == $targetID && $dao->contact_id) {
          $values[$activityID]['target_contact_name'][$dao->contact_id] = $dao->contact_name;
        }
        if ($dao->record_type_id == $assigneeID && $dao->contact_id) {
          $values[$activityID]['assignee_contact_name'][$dao->contact_id] = $dao->contact_name;
        }

        // case related fields
        $values[$activityID]['case_id'] = $dao->case_id;
        $values[$activityID]['case_subject'] = $dao->case_subject;
      }
      else {
        $values[$activityID]['recipients'] =  ts('(%1 recipients)', array(1 => $dao->counter));
        $values[$activityID]['mailingId'] = false;
        if (
          $accessCiviMail &&
          ($mailingIDs === TRUE || in_array($dao->source_record_id, $mailingIDs))
        ) {
          $values[$activityID]['mailingId'] = true;
        }
      }
    }

    return $values;
  }

  /**
   * Get the component id and name those are enabled and logged in
   * user has permission. To decide whether we are going to include
   * component related activities w/ core activity retrieve process.
   *
   * return an array of component id and name.
   * @static
   **/
  static function activityComponents() {
    $components = array();
    $compInfo = CRM_Core_Component::getEnabledComponents();
    foreach ($compInfo as $compObj) {
      if (!empty($compObj->info['showActivitiesInCore'])) {
        if ($compObj->info['name'] == 'CiviCampaign') {
          $componentPermission = "administer {$compObj->name}";
        }
        else {
          $componentPermission = "access {$compObj->name}";
        }
        if ($compObj->info['name'] == 'CiviCase') {
          if (CRM_Case_BAO_Case::accessCiviCase()) {
            $components[$compObj->componentID] = $compObj->info['name'];
          }
        }
        elseif (CRM_Core_Permission::check($componentPermission)) {
          $components[$compObj->componentID] = $compObj->info['name'];
        }
      }
    }

    return $components;
  }

  /**
   * function to get the activity Count
   *
   * @param array   $input            array of parameters
   *    Keys include
   *    - contact_id  int            contact_id whose activities we want to retrieve
   *    - admin       boolean        if contact is admin
   *    - caseId      int            case ID
   *    - context     string         page on which selector is build
   *    - activity_type_id int|string the activity types we want to restrict by
   *
   * @return int   count of activities
   *
   * @access public
   * @static
   */
  static function &getActivitiesCount($input) {
    $input['count'] = TRUE;
    list($sqlClause, $params) = self::getActivitySQLClause($input);

    //filter case activities - CRM-5761
    $components = self::activityComponents();
    if (!in_array('CiviCase', $components)) {
      $query = "
   SELECT   COUNT(DISTINCT(tbl.activity_id)) as count
     FROM   ( {$sqlClause} ) as tbl
LEFT JOIN   civicrm_case_activity ON ( civicrm_case_activity.activity_id = tbl.activity_id )
    WHERE   civicrm_case_activity.id IS NULL";
    }
    else {
      $query = "SELECT COUNT(DISTINCT(activity_id)) as count  from ( {$sqlClause} ) as tbl";
    }

    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * function to get the activity sql clause to pick activities
   *
   * @param array   $input            array of parameters
   *    Keys include
   *    - contact_id  int            contact_id whose activities we want to retrieve
   *    - admin       boolean        if contact is admin
   *    - caseId      int            case ID
   *    - context     string         page on which selector is build
   *    - count       boolean        are we interested in the count clause only?
   *    - activity_type_id int|string the activity types we want to restrict by
   *
   * @return int   count of activities
   *
   * @access public
   * @static
   */
  static function getActivitySQLClause($input) {
    $params = array();
    $sourceWhere = $targetWhere = $assigneeWhere = $caseWhere = 1;

    $config = CRM_Core_Config::singleton();
    if (!CRM_Utils_Array::value('admin', $input, FALSE)) {
      $sourceWhere   = ' ac.contact_id = %1 ';
      $caseWhere     = ' civicrm_case_contact.contact_id = %1 ';

      $params = array(1 => array($input['contact_id'], 'Integer'));
    }

    $commonClauses = array(
      "civicrm_option_group.name = 'activity_type'",
      "civicrm_activity.is_deleted = 0",
      "civicrm_activity.is_current_revision =  1",
      "civicrm_activity.is_test= 0",
    );

    if ($input['context'] != 'activity') {
      $commonClauses[] = "civicrm_activity.status_id = 1";
    }

    //Filter on component IDs.
    $components = self::activityComponents();
    if (!empty($components)) {
      $componentsIn = implode(',', array_keys($components));
      $commonClauses[] = "( civicrm_option_value.component_id IS NULL OR civicrm_option_value.component_id IN ( $componentsIn ) )";
    }
    else {
      $commonClauses[] = "civicrm_option_value.component_id IS NULL";
    }

    // activity type ID clause
    if (!empty($input['activity_type_id'])) {
      if (is_array($input['activity_type_id'])) {
        foreach ($input['activity_type_id'] as $idx => $value) {
          $input['activity_type_id'][$idx] = CRM_Utils_Type::escape($value, 'Positive');
        }
        $commonClauses[] = "civicrm_activity.activity_type_id IN ( " . implode(",", $input['activity_type_id']) . " ) ";
      }
      else {
        $activityTypeID = CRM_Utils_Type::escape($input['activity_type_id'], 'Positive');
        $commonClauses[] = "civicrm_activity.activity_type_id = $activityTypeID";
      }
    }

    // exclude by activity type clause
    if (!empty($input['activity_type_exclude_id'])) {
      if (is_array($input['activity_type_exclude_id'])) {
        foreach ($input['activity_type_exclude_id'] as $idx => $value) {
          $input['activity_type_exclude_id'][$idx] = CRM_Utils_Type::escape($value, 'Positive');
        }
        $commonClauses[] = "civicrm_activity.activity_type_id NOT IN ( " . implode(",", $input['activity_type_exclude_id']) . " ) ";
      }
      else {
        $activityTypeID = CRM_Utils_Type::escape($input['activity_type_exclude_id'], 'Positive');
        $commonClauses[] = "civicrm_activity.activity_type_id != $activityTypeID";
      }
    }

    $commonClause = implode(' AND ', $commonClauses);

    $includeCaseActivities = FALSE;
    if (in_array('CiviCase', $components)) {
      $includeCaseActivities = TRUE;
    }


    // build main activity table select clause
    $sourceSelect = '';

    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $sourceJoin = "
INNER JOIN civicrm_activity_contact ac ON ac.activity_id = civicrm_activity.id
INNER JOIN civicrm_contact contact ON ac.contact_id = contact.id
";

    if (!$input['count']) {
      $sourceSelect = ',
                civicrm_activity.activity_date_time,
                civicrm_activity.source_record_id,
                civicrm_activity.status_id,
                civicrm_activity.subject,
                contact.sort_name as source_contact_name,
                civicrm_option_value.value as activity_type_id,
                civicrm_option_value.label as activity_type,
                null as case_id, null as case_subject,
                civicrm_activity.campaign_id as campaign_id
            ';

      $sourceJoin .= "
LEFT JOIN civicrm_activity_contact src ON (src.activity_id = ac.activity_id AND src.record_type_id = {$sourceID} AND src.contact_id = contact.id)
";
    }

    $sourceClause = "
            SELECT civicrm_activity.id as activity_id
            {$sourceSelect}
            from civicrm_activity
            left join civicrm_option_value on
                civicrm_activity.activity_type_id = civicrm_option_value.value
            left join civicrm_option_group on
                civicrm_option_group.id = civicrm_option_value.option_group_id
            {$sourceJoin}
            where
                    {$sourceWhere}
                AND $commonClause
        ";

    // Build case clause
    // or else exclude Inbound Emails that have been filed on a case.
    $caseClause = '';

    if ($includeCaseActivities) {
      $caseSelect = '';
      if (!$input['count']) {
        $caseSelect = ',
                civicrm_activity.activity_date_time,
                civicrm_activity.source_record_id,
                civicrm_activity.status_id,
                civicrm_activity.subject,
                contact.sort_name as source_contact_name,
                civicrm_option_value.value as activity_type_id,
                civicrm_option_value.label as activity_type,
                null as case_id, null as case_subject,
                civicrm_activity.campaign_id as campaign_id';
      }

      $caseClause = "
                union all

                SELECT civicrm_activity.id as activity_id
                {$caseSelect}
                from civicrm_activity
                inner join civicrm_case_activity on
                    civicrm_case_activity.activity_id = civicrm_activity.id
                inner join civicrm_case on
                    civicrm_case_activity.case_id = civicrm_case.id
                inner join civicrm_case_contact on
                    civicrm_case_contact.case_id = civicrm_case.id and {$caseWhere}
                left join civicrm_option_value on
                    civicrm_activity.activity_type_id = civicrm_option_value.value
                left join civicrm_option_group on
                    civicrm_option_group.id = civicrm_option_value.option_group_id
                {$sourceJoin}
                where
                        {$caseWhere}
                    AND $commonClause
                        and  ( ( civicrm_case_activity.case_id IS NULL ) OR
                           ( civicrm_option_value.name <> 'Inbound Email' AND
                             civicrm_option_value.name <> 'Email' AND civicrm_case_activity.case_id
                             IS NOT NULL )
                         )
            ";
    }

    $returnClause = " {$sourceClause} {$caseClause} ";

    return array($returnClause, $params);
  }

  /**
   * send the message to all the contacts and also insert a
   * contact activity in each contacts record
   *
   * @param array $contactDetails the array of contact details to send the email
   * @param string $subject the subject of the message
   * @param $text
   * @param $html
   * @param string $emailAddress use this 'to' email address instead of the default Primary address
   * @param int $userID use this userID if set
   * @param string $from
   * @param array $attachments the array of attachments if any
   * @param string $cc cc recipient
   * @param string $bcc bcc recipient
   * @param array $contactIds contact ids
   * @param string $additionalDetails the additional information of CC and BCC appended to the activity Details
   *
   * @internal param string $message the message contents
   * @return array               ( sent, activityId) if any email is sent and activityId
   * @access public
   * @static
   */
  static function sendEmail(
    &$contactDetails,
    &$subject,
    &$text,
    &$html,
    $emailAddress,
    $userID      = NULL,
    $from        = NULL,
    $attachments = NULL,
    $cc          = NULL,
    $bcc         = NULL,
    $contactIds, // FIXME a param with no default shouldn't be last
    $additionalDetails = NULL
  ) {
    // get the contact details of logged in contact, which we set as from email
    if ($userID == NULL) {
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');
    }

    list($fromDisplayName, $fromEmail, $fromDoNotEmail) = CRM_Contact_BAO_Contact::getContactDetails($userID);
    if (!$fromEmail) {
      return array(count($contactDetails), 0, count($contactDetails));
    }
    if (!trim($fromDisplayName)) {
      $fromDisplayName = $fromEmail;
    }

    // CRM-4575
    // token replacement of addressee/email/postal greetings
    // get the tokens added in subject and message
    $subjectToken = CRM_Utils_Token::getTokens($subject);
    $messageToken = CRM_Utils_Token::getTokens($text);
    $messageToken = array_merge($messageToken, CRM_Utils_Token::getTokens($html));
    $allTokens = array_merge($messageToken, $subjectToken);

    if (!$from) {
      $from = "$fromDisplayName <$fromEmail>";
    }

    //create the meta level record first ( email activity )
    $activityTypeID = CRM_Core_OptionGroup::getValue('activity_type',
      'Email',
      'name'
    );

    // CRM-6265: save both text and HTML parts in details (if present)
    if ($html and $text) {
      $details = "-ALTERNATIVE ITEM 0-\n$html$additionalDetails\n-ALTERNATIVE ITEM 1-\n$text$additionalDetails\n-ALTERNATIVE END-\n";
    }
    else {
      $details = $html ? $html : $text;
      $details .= $additionalDetails;
    }

    $activityParams = array(
      'source_contact_id' => $userID,
      'activity_type_id' => $activityTypeID,
      'activity_date_time' => date('YmdHis'),
      'subject' => $subject,
      'details' => $details,
      // FIXME: check for name Completed and get ID from that lookup
      'status_id' => 2,
    );

    // CRM-5916: strip [case #…] before saving the activity (if present in subject)
    $activityParams['subject'] = preg_replace('/\[case #([0-9a-h]{7})\] /', '', $activityParams['subject']);

    // add the attachments to activity params here
    if ($attachments) {
      // first process them
      $activityParams = array_merge($activityParams,
        $attachments
      );
    }

    $activity = self::create($activityParams);

    // get the set of attachments from where they are stored
    $attachments = CRM_Core_BAO_File::getEntityFile('civicrm_activity',
      $activity->id
    );
    $returnProperties = array();
    if (isset($messageToken['contact'])) {
      foreach ($messageToken['contact'] as $key => $value) {
        $returnProperties[$value] = 1;
      }
    }

    if (isset($subjectToken['contact'])) {
      foreach ($subjectToken['contact'] as $key => $value) {
        if (!isset($returnProperties[$value])) {
          $returnProperties[$value] = 1;
        }
      }
    }


    // get token details for contacts, call only if tokens are used
    $details = array();
    if (!empty($returnProperties) || !empty($tokens) || !empty($allTokens)) {
      list($details) = CRM_Utils_Token::getTokenDetails(
        $contactIds,
        $returnProperties,
        NULL, NULL, FALSE,
        $allTokens,
        'CRM_Activity_BAO_Activity'
      );
    }

    // call token hook
    $tokens = array();
    CRM_Utils_Hook::tokens($tokens);
    $categories = array_keys($tokens);

    $escapeSmarty = FALSE;
    if (defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY) {
      $smarty = CRM_Core_Smarty::singleton();
      $escapeSmarty = TRUE;
    }

    $sent = $notSent = array();
    foreach ($contactDetails as $values) {
      $contactId = $values['contact_id'];
      $emailAddress = $values['email'];

      if (!empty($details) && is_array($details["{$contactId}"])) {
        // unset email from details since it always returns primary email address
        unset($details["{$contactId}"]['email']);
        unset($details["{$contactId}"]['email_id']);
        $values = array_merge($values, $details["{$contactId}"]);
      }

      $tokenSubject = CRM_Utils_Token::replaceContactTokens($subject, $values, FALSE, $subjectToken, FALSE, $escapeSmarty);
      $tokenSubject = CRM_Utils_Token::replaceHookTokens($tokenSubject, $values, $categories, FALSE, $escapeSmarty);

      //CRM-4539
      if ($values['preferred_mail_format'] == 'Text' || $values['preferred_mail_format'] == 'Both') {
        $tokenText = CRM_Utils_Token::replaceContactTokens($text, $values, FALSE, $messageToken, FALSE, $escapeSmarty);
        $tokenText = CRM_Utils_Token::replaceHookTokens($tokenText, $values, $categories, FALSE, $escapeSmarty);
      }
      else {
        $tokenText = NULL;
      }

      if ($values['preferred_mail_format'] == 'HTML' || $values['preferred_mail_format'] == 'Both') {
        $tokenHtml = CRM_Utils_Token::replaceContactTokens($html, $values, TRUE, $messageToken, FALSE, $escapeSmarty);
        $tokenHtml = CRM_Utils_Token::replaceHookTokens($tokenHtml, $values, $categories, TRUE, $escapeSmarty);
      }
      else {
        $tokenHtml = NULL;
      }

      if (defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY) {
        // also add the contact tokens to the template
        $smarty->assign_by_ref('contact', $values);

        $tokenSubject = $smarty->fetch("string:$tokenSubject");
        $tokenText    = $smarty->fetch("string:$tokenText");
        $tokenHtml    = $smarty->fetch("string:$tokenHtml");
      }

      $sent = FALSE;
      if (self::sendMessage(
          $from,
          $userID,
          $contactId,
          $tokenSubject,
          $tokenText,
          $tokenHtml,
          $emailAddress,
          $activity->id,
          $attachments,
          $cc,
          $bcc
        )) {
        $sent = TRUE;
      }
    }

    return array($sent, $activity->id);
  }

  /**
   * @param $contactDetails
   * @param $activityParams
   * @param array $smsParams
   * @param $contactIds
   * @param null $userID
   *
   * @return array
   * @throws CRM_Core_Exception
   */
  static function sendSMS(&$contactDetails,
    &$activityParams,
    &$smsParams = array(),
    &$contactIds,
    $userID = NULL
  ) {
    if ($userID == NULL) {
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');
    }

    $text = &$activityParams['sms_text_message'];

    // CRM-4575
    // token replacement of addressee/email/postal greetings
    // get the tokens added in subject and message
    $messageToken = CRM_Utils_Token::getTokens($text);

    //create the meta level record first ( sms activity )
    $activityTypeID = CRM_Core_OptionGroup::getValue('activity_type',
      'SMS',
      'name'
    );

    $details = $text;

    $activitySubject = $activityParams['activity_subject'];
    $activityParams = array(
      'source_contact_id' => $userID,
      'activity_type_id' => $activityTypeID,
      'activity_date_time' => date('YmdHis'),
      'subject' => $activitySubject,
      'details' => $details,
      // FIXME: check for name Completed and get ID from that lookup
      'status_id' => 2,
    );

    $activity = self::create($activityParams);
    $activityID = $activity->id;

    $returnProperties = array();

    if (isset($messageToken['contact'])) {
      foreach ($messageToken['contact'] as $key => $value) {
        $returnProperties[$value] = 1;
      }
    }

    // call token hook
    $tokens = array();
    CRM_Utils_Hook::tokens($tokens);
    $categories = array_keys($tokens);

    // get token details for contacts, call only if tokens are used
    $details = array();
    if (!empty($returnProperties) || !empty($tokens)) {
      list($details) = CRM_Utils_Token::getTokenDetails($contactIds,
        $returnProperties,
        NULL, NULL, FALSE,
        $messageToken,
        'CRM_Activity_BAO_Activity'
      );
    }

    $success = 0;
    $escapeSmarty = FALSE;
    $errMsgs = array();
    foreach ($contactDetails as $values) {
      $contactId = $values['contact_id'];

      if (!empty($details) && is_array($details["{$contactId}"])) {
        // unset email from details since it always returns primary email address
        unset($details["{$contactId}"]['email']);
        unset($details["{$contactId}"]['email_id']);
        $values = array_merge($values, $details["{$contactId}"]);
      }

      $tokenText = CRM_Utils_Token::replaceContactTokens($text, $values, FALSE, $messageToken, FALSE, $escapeSmarty);
      $tokenText = CRM_Utils_Token::replaceHookTokens($tokenText, $values, $categories, FALSE, $escapeSmarty);

      // Only send if the phone is of type mobile
      $phoneTypes = CRM_Core_OptionGroup::values('phone_type', TRUE, FALSE, FALSE, NULL, 'name');
      if ($values['phone_type_id'] == CRM_Utils_Array::value('Mobile', $phoneTypes)) {
        $smsParams['To'] = $values['phone'];
      }
      else {
        $smsParams['To'] = '';
      }

      $sendResult = self::sendSMSMessage(
        $contactId,
        $tokenText,
        $smsParams,
        $activityID,
        $userID
      );

      if (PEAR::isError($sendResult)) {
        // Collect all of the PEAR_Error objects
        $errMsgs[] = $sendResult;
      } else {
        $success++;
      }
    }

    // If at least one message was sent and no errors
    // were generated then return a boolean value of TRUE.
    // Otherwise, return FALSE (no messages sent) or
    // and array of 1 or more PEAR_Error objects.
    $sent = FALSE;
    if ($success > 0 && count($errMsgs) == 0) {
      $sent = TRUE;
    } elseif (count($errMsgs) > 0) {
      $sent = $errMsgs;
    }

    return array($sent, $activity->id, $success);
  }

  /**
   * send the sms message to a specific contact
   *
   * @param int $toID the contact id of the recipient
   * @param $tokenText
   * @param $tokenHtml
   * @param array $smsParams the params used for sending sms
   *
   * @param int $activityID the activity ID that tracks the message
   * @param null $userID
   *
   * @return mixed                    true on success or PEAR_Error object
   * @access public
   * @static
   */
  static function sendSMSMessage($toID,
    &$tokenText,
    $smsParams = array(),
    $activityID,
    $userID = null
  ) {
    $toDoNotSms = "";
    $toPhoneNumber = "";

    if ($smsParams['To']) {
      $toPhoneNumber = trim($smsParams['To']);
    }
    elseif ($toID) {
      $filters = array('is_deceased' => 0, 'is_deleted' => 0, 'do_not_sms' => 0);
      $toPhoneNumbers = CRM_Core_BAO_Phone::allPhones($toID, FALSE, 'Mobile', $filters);
      //to get primary mobile ph,if not get a first mobile ph
      if (!empty($toPhoneNumbers)) {
        $toPhoneNumerDetails = reset($toPhoneNumbers);
        $toPhoneNumber = CRM_Utils_Array::value('phone', $toPhoneNumerDetails);
        //contact allows to send sms
        $toDoNotSms = 0;
      }
    }

    // make sure both phone are valid
    // and that the recipient wants to receive sms
    if (empty($toPhoneNumber) or $toDoNotSms) {
      return PEAR::raiseError(
        'Recipient phone number is invalid or recipient does not want to receive SMS',
        null,
        PEAR_ERROR_RETURN
      );
    }

    $recipient = $smsParams['To'];
    $smsParams['contact_id'] = $toID;
    $smsParams['parent_activity_id'] = $activityID;

    $providerObj = CRM_SMS_Provider::singleton(array('provider_id' => $smsParams['provider_id']));
    $sendResult = $providerObj->send($recipient, $smsParams, $tokenText, NULL, $userID);
    if (PEAR::isError($sendResult)) {
      return $sendResult;
    }

    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);


    // add activity target record for every sms that is send
    $activityTargetParams = array(
      'activity_id' => $activityID,
      'contact_id'  => $toID,
      'record_type_id' => $targetID
    );
    CRM_Activity_BAO_ActivityContact::create($activityTargetParams);

    return TRUE;
  }

  /**
   * send the message to a specific contact
   *
   * @param string $from the name and email of the sender
   * @param $fromID
   * @param int $toID the contact id of the recipient
   * @param string $subject the subject of the message
   * @param $text_message
   * @param $html_message
   * @param string $emailAddress use this 'to' email address instead of the default Primary address
   * @param int $activityID the activity ID that tracks the message
   *
   * @param null $attachments
   * @param null $cc
   * @param null $bcc
   *
   * @internal param string $message the message contents
   * @return boolean             true if successfull else false.
   * @access public
   * @static
   */
  static function sendMessage($from,
    $fromID,
    $toID,
    &$subject,
    &$text_message,
    &$html_message,
    $emailAddress,
    $activityID,
    $attachments = NULL,
    $cc          = NULL,
    $bcc         = NULL
  ) {
    list($toDisplayName, $toEmail, $toDoNotEmail) = CRM_Contact_BAO_Contact::getContactDetails($toID);
    if ($emailAddress) {
      $toEmail = trim($emailAddress);
    }

    // make sure both email addresses are valid
    // and that the recipient wants to receive email
    if (empty($toEmail) or $toDoNotEmail) {
      return FALSE;
    }
    if (!trim($toDisplayName)) {
      $toDisplayName = $toEmail;
    }

    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    //$sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    //$assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    // create the params array
    $mailParams = array(
      'groupName' => 'Activity Email Sender',
      'from' => $from,
      'toName' => $toDisplayName,
      'toEmail' => $toEmail,
      'subject' => $subject,
      'cc' => $cc,
      'bcc' => $bcc,
      'text' => $text_message,
      'html' => $html_message,
      'attachments' => $attachments,
    );

    if (!CRM_Utils_Mail::send($mailParams)) {
      return FALSE;
    }

    // add activity target record for every mail that is send
    $activityTargetParams = array(
      'activity_id' => $activityID,
      'contact_id' => $toID,
      'record_type_id' => $targetID
    );
    CRM_Activity_BAO_ActivityContact::create($activityTargetParams);
    return TRUE;
  }

  /**
   * combine all the importable fields from the lower levels object
   *
   * The ordering is important, since currently we do not have a weight
   * scheme. Adding weight is super important and should be done in the
   * next week or so, before this can be called complete.
   *
   * @param bool $status
   *
   * @internal param $NULL
   *
   * @return array    array of importable Fields
   * @access public
   * @static
   */
  static function &importableFields($status = FALSE) {
    if (!self::$_importableFields) {
      if (!self::$_importableFields) {
        self::$_importableFields = array();
      }
      if (!$status) {
        $fields = array('' => array('title' => ts('- do not import -')));
      }
      else {
        $fields = array('' => array('title' => ts('- Activity Fields -')));
      }

      $tmpFields = CRM_Activity_DAO_Activity::import();
      $contactFields = CRM_Contact_BAO_Contact::importableFields('Individual', NULL);

      // Using new Dedupe rule.
      $ruleParams = array(
        'contact_type' => 'Individual',
        'used'         => 'Unsupervised',
      );
      $fieldsArray = CRM_Dedupe_BAO_Rule::dedupeRuleFields($ruleParams);

      $tmpConatctField = array();
      if (is_array($fieldsArray)) {
        foreach ($fieldsArray as $value) {
          $customFieldId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
            $value,
            'id',
            'column_name'
          );
          $value = $customFieldId ? 'custom_' . $customFieldId : $value;
          $tmpConatctField[trim($value)] = $contactFields[trim($value)];
          $tmpConatctField[trim($value)]['title'] = $tmpConatctField[trim($value)]['title'] . " (match to contact)";
        }
      }
      $tmpConatctField['external_identifier'] = $contactFields['external_identifier'];
      $tmpConatctField['external_identifier']['title'] = $contactFields['external_identifier']['title'] . " (match to contact)";
      $fields = array_merge($fields, $tmpConatctField);
      $fields = array_merge($fields, $tmpFields);
      $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Activity'));
      self::$_importableFields = $fields;
    }
    return self::$_importableFields;
  }

  /**
   * To get the Activities of a target contact
   *
   * @param $contactId    Integer  ContactId of the contact whose activities
   *                               need to find
   *
   * @return array    array of activity fields
   * @access public
   */
  static function getContactActivity($contactId) {
    $activities = array();
    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);


    // First look for activities where contactId is one of the targets
    $query = "
SELECT activity_id, record_type_id
FROM   civicrm_activity_contact
WHERE  contact_id = $contactId
";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      if ($dao->record_type_id == $targetID ) {
        $activities[$dao->activity_id]['targets'][$contactId] = $contactId;
      }
      else if ($dao->record_type_id == $assigneeID) {
        $activities[$dao->activity_id]['asignees'][$contactId] = $contactId;
      }
      else {
        // do source stuff here
        $activities[$dao->activity_id]['source_contact_id'] = $contactId;
      }
    }

    $activityIds = array_keys($activities);
    if (count($activityIds) < 1) {
      return array();
    }

    $activityIds = implode(',', $activityIds);
    $query = "
SELECT     activity.id as activity_id,
           activity_type_id,
           subject, location, activity_date_time, details, status_id
FROM       civicrm_activity activity
WHERE      activity.id IN ($activityIds)";

    $dao = CRM_Core_DAO::executeQuery($query);

    $activityTypes = CRM_Core_OptionGroup::values('activity_type');
    $activityStatuses = CRM_Core_OptionGroup::values('activity_status');

    while ($dao->fetch()) {
      $activities[$dao->activity_id]['id'] = $dao->activity_id;
      $activities[$dao->activity_id]['activity_type_id'] = $dao->activity_type_id;
      $activities[$dao->activity_id]['subject'] = $dao->subject;
      $activities[$dao->activity_id]['location'] = $dao->location;
      $activities[$dao->activity_id]['activity_date_time'] = $dao->activity_date_time;
      $activities[$dao->activity_id]['details'] = $dao->details;
      $activities[$dao->activity_id]['status_id'] = $dao->status_id;
      $activities[$dao->activity_id]['activity_name'] = $activityTypes[$dao->activity_type_id];
      $activities[$dao->activity_id]['status'] = $activityStatuses[$dao->status_id];

      // set to null if not set
      if (!isset($activities[$dao->activity_id]['source_contact_id'])) {
        $activities[$dao->activity_id]['source_contact_id'] = NULL;
      }
    }
    return $activities;
  }

  /**
   * Function to add activity for Membership/Event/Contribution
   *
   * @param object $activity (reference) particular component object
   * @param string $activityType for Membership Signup or Renewal
   *
   *
   * @param null $targetContactID
   *
   * @return bool
   * @static
   * @access public
   */
  static function addActivity(&$activity,
    $activityType = 'Membership Signup',
    $targetContactID = NULL
  ) {
    if ($activity->__table == 'civicrm_membership') {
      $membershipType = CRM_Member_PseudoConstant::membershipType($activity->membership_type_id);

      if (!$membershipType) {
        $membershipType = ts('Membership');
      }

      $subject = "{$membershipType}";

      if (!empty($activity->source) && $activity->source != 'null') {
        $subject .= " - {$activity->source}";
      }

      if ($activity->owner_membership_id) {
        $query = "
SELECT  display_name
  FROM  civicrm_contact, civicrm_membership
 WHERE  civicrm_contact.id    = civicrm_membership.contact_id
   AND  civicrm_membership.id = $activity->owner_membership_id
";
        $displayName = CRM_Core_DAO::singleValueQuery($query);
        $subject .= " (by {$displayName})";
      }

      $subject .= " - Status: " . CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus', $activity->status_id);
      // CRM-72097 changed from start date to today
      $date = date('YmdHis');
      $component = 'Membership';
    }
    elseif ($activity->__table == 'civicrm_participant') {
      $event = CRM_Event_BAO_Event::getEvents(1, $activity->event_id, TRUE, FALSE);

      $roles = CRM_Event_PseudoConstant::participantRole();
      $status = CRM_Event_PseudoConstant::participantStatus();

      $subject = $event[$activity->event_id];
      if (!empty($roles[$activity->role_id])) {
        $subject .= ' - ' . $roles[$activity->role_id];
      }
      if (!empty($status[$activity->status_id])) {
        $subject .= ' - ' . $status[$activity->status_id];
      }
      $date = date('YmdHis');
      if ($activityType != 'Email') {
        $activityType = 'Event Registration';
      }
      $component = 'Event';
    }
    elseif ($activity->__table == 'civicrm_contribution') {
      //create activity record only for Completed Contributions
      if ($activity->contribution_status_id != 1) {
        return;
      }

      $subject = NULL;

      $subject .= CRM_Utils_Money::format($activity->total_amount, $activity->currency);
      if (!empty($activity->source) && $activity->source != 'null') {
        $subject .= " - {$activity->source}";
      }
      $date = CRM_Utils_Date::isoToMysql($activity->receive_date);
      $activityType = $component = 'Contribution';
    }
    $activityParams = array(
      'source_contact_id' => $activity->contact_id,
      'source_record_id' => $activity->id,
      'activity_type_id' => CRM_Core_OptionGroup::getValue('activity_type',
        $activityType,
        'name'
      ),
      'subject' => $subject,
      'activity_date_time' => $date,
      'is_test' => $activity->is_test,
      'status_id' => CRM_Core_OptionGroup::getValue('activity_status',
        'Completed',
        'name'
      ),
      'skipRecentView' => TRUE,
      'campaign_id' => $activity->campaign_id,
    );

    // create activity with target contacts
    $session = CRM_Core_Session::singleton();
    $id = $session->get('userID');
    if ($id) {
      $activityParams['source_contact_id'] = $id;
      $activityParams['target_contact_id'][] = $activity->contact_id;
    }

    // CRM-14945
    if (property_exists($activity, 'details')) {
      $activityParams['details'] = $activity->details;
    }
    //CRM-4027
    if ($targetContactID) {
      $activityParams['target_contact_id'][] = $targetContactID;
    }
    if (is_a(self::create($activityParams), 'CRM_Core_Error')) {
      CRM_Core_Error::fatal("Failed creating Activity for $component of id {$activity->id}");
      return FALSE;
    }
  }

  /**
   * Function to get Parent activity for currently viewed activity
   *
   * @param int  $activityId   current activity id
   *
   * @return int $parentId  Id of parent activity otherwise false.
   * @access public
   */
  static function getParentActivity($activityId) {
    static $parentActivities = array();

    $activityId = CRM_Utils_Type::escape($activityId, 'Integer');

    if (!array_key_exists($activityId, $parentActivities)) {
      $parentActivities[$activityId] = array();

      $parentId = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity',
        $activityId,
        'parent_id'
      );

      $parentActivities[$activityId] = $parentId ? $parentId : FALSE;
    }

    return $parentActivities[$activityId];
  }

  /**
   * Function to get total count of prior revision of currently viewd activity
   *
   * @param $activityID
   *
   * @internal param int $activityId current activity id
   *
   * @return int $params  count of prior activities otherwise false.
   * @access public
   */
  static function getPriorCount($activityID) {
    static $priorCounts = array();

    $activityID = CRM_Utils_Type::escape($activityID, 'Integer');

    if (!array_key_exists($activityID, $priorCounts)) {
      $priorCounts[$activityID] = array();
      $originalID = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity',
        $activityID,
        'original_id'
      );
      $count = 0;
      if ($originalID) {
        $query = "
SELECT count( id ) AS cnt
FROM civicrm_activity
WHERE ( id = {$originalID} OR original_id = {$originalID} )
AND is_current_revision = 0
AND id < {$activityID}
";
        $params = array(1 => array($originalID, 'Integer'));
        $count = CRM_Core_DAO::singleValueQuery($query, $params);
      }
      $priorCounts[$activityID] = $count ? $count : 0;
    }

    return $priorCounts[$activityID];
  }

  /**
   * Function to get all prior activities of currently viewe
   * d activity
   *
   * @param $activityID
   * @param bool $onlyPriorRevisions
   *
   * @internal param int $activityId current activity id
   *
   * @return array $result  prior activities info.
   * @access public
   */
  static function getPriorAcitivities($activityID, $onlyPriorRevisions = FALSE) {
    static $priorActivities = array();

    $activityID = CRM_Utils_Type::escape($activityID, 'Integer');
    $index = $activityID . '_' . (int) $onlyPriorRevisions;

    if (!array_key_exists($index, $priorActivities)) {
      $priorActivities[$index] = array();

      $originalID = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity',
        $activityID,
        'original_id'
      );
      if ($originalID) {
        $query = "
SELECT c.display_name as name, cl.modified_date as date, ca.id as activityID
FROM civicrm_log cl, civicrm_contact c, civicrm_activity ca
WHERE (ca.id = %1 OR ca.original_id = %1)
AND cl.entity_table = 'civicrm_activity'
AND cl.entity_id    = ca.id
AND cl.modified_id  = c.id
";
        if ($onlyPriorRevisions) {
          $query .= " AND ca.id < {$activityID}";
        }
        $query .= " ORDER BY ca.id DESC";

        $params = array(1 => array($originalID, 'Integer'));
        $dao = CRM_Core_DAO::executeQuery($query, $params);

        while ($dao->fetch()) {
          $priorActivities[$index][$dao->activityID]['id'] = $dao->activityID;
          $priorActivities[$index][$dao->activityID]['name'] = $dao->name;
          $priorActivities[$index][$dao->activityID]['date'] = $dao->date;
        }
        $dao->free();
      }
    }
    return $priorActivities[$index];
  }

  /**
   * Function to find the latest revision of a given activity
   *
   * @param $activityID
   *
   * @internal param int $activityId prior activity id
   *
   * @return int $params  current activity id.
   * @access public
   */
  static function getLatestActivityId($activityID) {
    static $latestActivityIds = array();

    $activityID = CRM_Utils_Type::escape($activityID, 'Integer');

    if (!array_key_exists($activityID, $latestActivityIds)) {
      $latestActivityIds[$activityID] = array();

      $originalID = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity',
        $activityID,
        'original_id'
      );
      if ($originalID) {
        $activityID = $originalID;
      }
      $params = array(1 => array($activityID, 'Integer'));
      $query = "SELECT id from civicrm_activity where original_id = %1 and is_current_revision = 1";

      $latestActivityIds[$activityID] = CRM_Core_DAO::singleValueQuery($query, $params);
    }

    return $latestActivityIds[$activityID];
  }

  /**
   * Function to create a follow up a given activity
   *
   * @activityId int activity id of parent activity
   *
   * @param $activityId
   * @param $params
   *
   * @return $this|null|object
   * @internal param array $activity details
   *
   * @access public
   */
  static function createFollowupActivity($activityId, $params) {
    if (!$activityId) {
      return;
    }

    $session = CRM_Core_Session::singleton();

    $followupParams = array();
    $followupParams['parent_id'] = $activityId;
    $followupParams['source_contact_id'] = $session->get('userID');
    $followupParams['status_id'] = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name');

    $followupParams['activity_type_id'] = $params['followup_activity_type_id'];
    // Get Subject of Follow-up Activiity, CRM-4491
    $followupParams['subject'] = CRM_Utils_Array::value('followup_activity_subject', $params);
    $followupParams['assignee_contact_id'] = CRM_Utils_Array::value('followup_assignee_contact_id', $params);

    //create target contact for followup
    if (!empty($params['target_contact_id'])) {
      $followupParams['target_contact_id'] = $params['target_contact_id'];
    }

    $followupParams['activity_date_time'] = CRM_Utils_Date::processDate($params['followup_date'],
      $params['followup_date_time']
    );
    $followupActivity = self::create($followupParams);

    return $followupActivity;
  }

  /**
   * Function to get Activity specific File according activity type Id.
   *
   * @param int $activityTypeId activity id
   *
   * @param string $crmDir
   *
   * @return if file exists returns $activityTypeFile activity filename otherwise false.
   *
   * @static
   */
  static function getFileForActivityTypeId($activityTypeId, $crmDir = 'Activity') {
    $activityTypes = CRM_Case_PseudoConstant::caseActivityType(FALSE, TRUE);

    if ($activityTypes[$activityTypeId]['name']) {
      $activityTypeFile = CRM_Utils_String::munge(ucwords($activityTypes[$activityTypeId]['name']), '', 0);
    }
    else {
      return FALSE;
    }

    global $civicrm_root;
    $config = CRM_Core_Config::singleton();
    if (!file_exists(rtrim($civicrm_root, '/') . "/CRM/{$crmDir}/Form/Activity/{$activityTypeFile}.php")) {
      if (empty($config->customPHPPathDir)) {
        return FALSE;
      }
      elseif (!file_exists(rtrim($config->customPHPPathDir, '/') . "/CRM/{$crmDir}/Form/Activity/{$activityTypeFile}.php")) {
        return FALSE;
      }
    }

    return $activityTypeFile;
  }

  /**
   * Function to restore the activity
   *
   * @param array  $params  associated array
   *
   * @return void
   * @access public
   *
   */
  public static function restoreActivity(&$params) {
    $activity = new CRM_Activity_DAO_Activity();
    $activity->copyValues($params);

    $activity->is_deleted = 0;
    $result = $activity->save();

    return $result;
  }

  /**
   * Get the exportable fields for Activities
   *
   * @param string $name if it is called by case $name = Case else $name = Activity
   *
   * @return array array of exportable Fields
   * @access public
   * @static
   */
  static function &exportableFields($name = 'Activity') {
    if (!isset(self::$_exportableFields[$name])) {
      self::$_exportableFields[$name] = array();

      // TO DO, ideally we should retrieve all fields from xml, in this case since activity processing is done
      // my case hence we have defined fields as case_*
      if ($name == 'Activity') {
        $exportableFields = CRM_Activity_DAO_Activity::export();
        $exportableFields['source_contact_id']['title'] = ts('Source Contact ID');
        $exportableFields['source_contact'] = array(
          'title' => ts('Source Contact'),
          'type' => CRM_Utils_Type::T_STRING,
        );


        $Activityfields = array(
          'activity_type' => array('title' => ts('Activity Type'), 'type' => CRM_Utils_Type::T_STRING),
          'activity_status' => array('title' => ts('Activity Status'), 'type' => CRM_Utils_Type::T_STRING),
        );
        $fields = array_merge($Activityfields, $exportableFields);
      }
      else {
        //set title to activity fields
        $fields = array(
          'case_activity_subject' => array('title' => ts('Activity Subject'), 'type' => CRM_Utils_Type::T_STRING),
          'case_source_contact_id' => array('title' => ts('Activity Reporter'), 'type' => CRM_Utils_Type::T_STRING),
          'case_recent_activity_date' => array('title' => ts('Activity Actual Date'), 'type' => CRM_Utils_Type::T_DATE),
          'case_scheduled_activity_date' => array('title' => ts('Activity Scheduled Date'), 'type' => CRM_Utils_Type::T_DATE),
          'case_recent_activity_type' => array('title' => ts('Activity Type'), 'type' => CRM_Utils_Type::T_STRING),
          'case_activity_status' => array('title' => ts('Activity Status'), 'type' => CRM_Utils_Type::T_STRING),
          'case_activity_duration' => array('title' => ts('Activity Duration'), 'type' => CRM_Utils_Type::T_INT),
          'case_activity_medium_id' => array('title' => ts('Activity Medium'), 'type' => CRM_Utils_Type::T_INT),
          'case_activity_details' => array('title' => ts('Activity Details'), 'type' => CRM_Utils_Type::T_TEXT),
          'case_activity_is_auto' => array('title' => ts('Activity Auto-generated?'), 'type' => CRM_Utils_Type::T_BOOLEAN),
        );

        // add custom data for cases
        $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Case'));
      }

      // add custom data for case activities
      $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Activity'));

      self::$_exportableFields[$name] = $fields;
    }
    return self::$_exportableFields[$name];
  }

  /**
   * Get the allowed profile fields for Activities
   *
   * @return array array of activity profile Fields
   * @access public
   */
  static function getProfileFields() {
    $exportableFields = self::exportableFields('Activity');
    $skipFields = array(
      'activity_id',
      'activity_type',
      'source_contact_id',
      'source_contact',
      'activity_campaign',
      'activity_is_test',
      'is_current_revision',
      'activity_is_deleted',
    );
    $config = CRM_Core_Config::singleton();
    if (!in_array('CiviCampaign', $config->enableComponents)) {
      $skipFields[] = 'activity_engagement_level';
    }

    foreach ($skipFields as $field) {
      if (isset($exportableFields[$field])) {
        unset($exportableFields[$field]);
      }
    }

    // hack to use 'activity_type_id' instead of 'activity_type'
    $exportableFields['activity_status_id'] = $exportableFields['activity_status'];
    unset($exportableFields['activity_status']);

    return $exportableFields;
  }

  /**
   * This function deletes the activity record related to contact record,
   * when there are no target and assignee record w/ other contact.
   *
   * @param  int $contactId contactId
   *
   * @return true/null
   * @access public
   */
  public static function cleanupActivity($contactId) {
    $result = NULL;
    if (!$contactId) {
      return $result;
    }
    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);

    $transaction = new CRM_Core_Transaction();

    // delete activity if there is no record in civicrm_activity_contact
    // pointing to any other contact record
    $activityContact = new CRM_Activity_DAO_ActivityContact();
    $activityContact->contact_id = $contactId;
    $activityContact->record_type_id = $sourceID;
    $activityContact->find();

    while ($activityContact->fetch()) {
      // delete activity_contact record for the deleted contact
      $activityContact->delete();

      $activityContactOther = new CRM_Activity_DAO_ActivityContact();
      $activityContactOther->activity_id = $activityContact->activity_id;

      // delete activity only if no other contacts connected
      if ( ! $activityContactOther->find(TRUE) ) {
        $activityParams = array('id' => $activityContact->activity_id);
        $result = self::deleteActivity($activityParams);
      }

      $activityContactOther->free();
    }

    $activityContact->free();
    $transaction->commit();

    return $result;
  }

  /**
   * Does user has sufficient permission for view/edit activity record.
   *
   * @param  int   $activityId activity record id.
   * @param  int   $action     edit/view
   *
   * @return boolean $allow true/false
   * @access public
   */
  public static function checkPermission($activityId, $action) {
    $allow = FALSE;
    if (!$activityId ||
      !in_array($action, array(CRM_Core_Action::UPDATE, CRM_Core_Action::VIEW))
    ) {
      return $allow;
    }

    $activity = new CRM_Activity_DAO_Activity();
    $activity->id = $activityId;
    if (!$activity->find(TRUE)) {
      return $allow;
    }

    //component related permissions.
    $compPermissions = array(
      'CiviCase' => array('administer CiviCase',
        'access my cases and activities',
        'access all cases and activities',
      ),
      'CiviMail' => array('access CiviMail'),
      'CiviEvent' => array('access CiviEvent'),
      'CiviGrant' => array('access CiviGrant'),
      'CiviPledge' => array('access CiviPledge'),
      'CiviMember' => array('access CiviMember'),
      'CiviReport' => array('access CiviReport'),
      'CiviContribute' => array('access CiviContribute'),
      'CiviCampaign' => array('administer CiviCampaign'),
    );

    //return early when it is case activity.
    $isCaseActivity = CRM_Case_BAO_Case::isCaseActivity($activityId);
    //check for civicase related permission.
    if ($isCaseActivity) {
      $allow = FALSE;
      foreach ($compPermissions['CiviCase'] as $per) {
        if (CRM_Core_Permission::check($per)) {
          $allow = TRUE;
          break;
        }
      }

      //check for case specific permissions.
      if ($allow) {
        $oper = 'view';
        if ($action == CRM_Core_Action::UPDATE) {
          $oper = 'edit';
        }
        $allow = CRM_Case_BAO_Case::checkPermission($activityId,
          $oper,
          $activity->activity_type_id
        );
      }

      return $allow;
    }

    //first check the component permission.
    $sql = "
    SELECT  component_id
      FROM  civicrm_option_value val
INNER JOIN  civicrm_option_group grp ON ( grp.id = val.option_group_id AND grp.name = %1 )
     WHERE  val.value = %2";
    $params = array(1 => array('activity_type', 'String'),
      2 => array($activity->activity_type_id, 'Integer'),
    );
    $componentId = CRM_Core_DAO::singleValueQuery($sql, $params);

    if ($componentId) {
      $componentName = CRM_Core_Component::getComponentName($componentId);
      $compPermission = CRM_Utils_Array::value($componentName, $compPermissions);

      //here we are interesting in any single permission.
      if (is_array($compPermission)) {
        foreach ($compPermission as $per) {
          if (CRM_Core_Permission::check($per)) {
            $allow = TRUE;
            break;
          }
        }
      }
    }

    //check for this permission related to contact.
    $permission = CRM_Core_Permission::VIEW;
    if ($action == CRM_Core_Action::UPDATE) {
      $permission = CRM_Core_Permission::EDIT;
    }

    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    //check for source contact.
    if (!$componentId || $allow) {
      $sourceContactId = self::getActivityContact($activity->id, $sourceID);
      //account for possibility of activity not having a source contact (as it may have been deleted)
      if ( $sourceContactId ) {
        $allow = CRM_Contact_BAO_Contact_Permission::allow($sourceContactId, $permission);
      }
    }

    //check for target and assignee contacts.
    if ($allow) {
      //first check for supper permission.
      $supPermission = 'view all contacts';
      if ($action == CRM_Core_Action::UPDATE) {
        $supPermission = 'edit all contacts';
      }
      $allow = CRM_Core_Permission::check($supPermission);

      //user might have sufficient permission, through acls.
      if (!$allow) {
        $allow = TRUE;
        //get the target contacts.
        $targetContacts = CRM_Activity_BAO_ActivityContact::retrieveContactIdsByActivityId($activity->id, $targetID);
        foreach ($targetContacts as $cnt => $contactId) {
          if (!CRM_Contact_BAO_Contact_Permission::allow($contactId, $permission)) {
            $allow = FALSE;
            break;
          }
        }

        //get the assignee contacts.
        if ($allow) {
          $assigneeContacts = CRM_Activity_BAO_ActivityContact::retrieveContactIdsByActivityId($activity->id, $assigneeID);
          foreach ($assigneeContacts as $cnt => $contactId) {
            if (!CRM_Contact_BAO_Contact_Permission::allow($contactId, $permission)) {
              $allow = FALSE;
              break;
            }
          }
        }
      }
    }

    return $allow;
  }

  /**
   * This function is a wrapper for ajax activity selector
   *
   * @param  array   $params associated array for params record id.
   *
   * @return array   $contactActivities associated array of contact activities
   * @access public
   */
  public static function getContactActivitySelector(&$params) {
    // format the params
    $params['offset']   = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];
    $params['sort']     = CRM_Utils_Array::value('sortBy', $params);
    $params['caseId']   = NULL;
    $context            = CRM_Utils_Array::value('context', $params);

    // get contact activities
    $activities = CRM_Activity_BAO_Activity::getActivities($params);

    // add total
    $params['total'] = CRM_Activity_BAO_Activity::getActivitiesCount($params);

    // format params and add links
    $contactActivities = array();

    if (!empty($activities)) {
      $activityStatus = CRM_Core_PseudoConstant::activityStatus();

      // check logged in user for permission
      $page = new CRM_Core_Page();
      CRM_Contact_Page_View::checkUserPermission($page, $params['contact_id']);
      $permissions = array($page->_permission);
      if (CRM_Core_Permission::check('delete activities')) {
        $permissions[] = CRM_Core_Permission::DELETE;
      }

      $mask = CRM_Core_Action::mask($permissions);

      foreach ($activities as $activityId => $values) {
        $contactActivities[$activityId]['activity_type'] = $values['activity_type'];
        $contactActivities[$activityId]['subject'] = $values['subject'];
        if ($params['contact_id'] == $values['source_contact_id']) {
          $contactActivities[$activityId]['source_contact'] = $values['source_contact_name'];
        }
        elseif ($values['source_contact_id']) {
          $contactActivities[$activityId]['source_contact'] = CRM_Utils_System::href($values['source_contact_name'],
            'civicrm/contact/view', "reset=1&cid={$values['source_contact_id']}");
        }
        else {
          $contactActivities[$activityId]['source_contact'] = '<em>n/a</em>';
        }

        if (isset($values['mailingId']) && !empty($values['mailingId'])) {
          $contactActivities[$activityId]['target_contact'] = CRM_Utils_System::href($values['recipients'],
            'civicrm/mailing/report/event',
            "mid={$values['source_record_id']}&reset=1&event=queue&cid={$params['contact_id']}&context=activitySelector");
        }
        elseif (!empty($values['recipients'])) {
          $contactActivities[$activityId]['target_contact'] = $values['recipients'];
        }
        elseif (isset($values['target_contact_counter']) && $values['target_contact_counter']) {
          foreach ($values['target_contact_name'] as $tcID => $tcName) {
            $contactActivities[$activityId]['target_contact'] .= CRM_Utils_System::href($tcName,
              'civicrm/contact/view', "reset=1&cid={$tcID}");
          }

          if ($extraCount = $values['target_contact_counter'] - 1) {
            $contactActivities[$activityId]['target_contact'] .= ";<br />" . "(" . ts('%1 more', array(1 => $extraCount)) . ")";
          }
        }
        elseif (!$values['target_contact_name']) {
          $contactActivities[$activityId]['target_contact'] = '<em>n/a</em>';
        }

        if (empty($values['assignee_contact_name'])) {
          $contactActivities[$activityId]['assignee_contact'] = '<em>n/a</em>';
        }
        elseif (!empty($values['assignee_contact_name'])) {
          $count = 0;
          $contactActivities[$activityId]['assignee_contact'] = '';
          foreach ($values['assignee_contact_name'] as $acID => $acName) {
            if ($acID && $count < 5) {
              $contactActivities[$activityId]['assignee_contact'] .= CRM_Utils_System::href($acName, 'civicrm/contact/view', "reset=1&cid={$acID}");
              $count++;
              if ($count) {
                $contactActivities[$activityId]['assignee_contact'] .= ";&nbsp;";
              }

              if ($count == 4) {
                $contactActivities[$activityId]['assignee_contact'] .= "(" . ts('more') . ")";
                break;
              }
            }
          }
        }

        $contactActivities[$activityId]['activity_date'] = CRM_Utils_Date::customFormat($values['activity_date_time']);
        $contactActivities[$activityId]['status'] = $activityStatus[$values['status_id']];

        // add class to this row if overdue
        $contactActivities[$activityId]['class'] = '';
        if (CRM_Utils_Date::overdue(CRM_Utils_Array::value('activity_date_time', $values))
          && CRM_Utils_Array::value('status_id', $values) == 1
        ) {
          $contactActivities[$activityId]['class'] = 'status-overdue';
        }
        else {
          $contactActivities[$activityId]['class'] = 'status-ontime';
        }

        // build links
        $contactActivities[$activityId]['links'] = '';
        $accessMailingReport = FALSE;
        if (!empty($values['mailingId'])) {
          $accessMailingReport = TRUE;
        }

        $actionLinks = CRM_Activity_Selector_Activity::actionLinks(
          CRM_Utils_Array::value('activity_type_id', $values),
          CRM_Utils_Array::value('source_record_id', $values),
          $accessMailingReport,
          CRM_Utils_Array::value('activity_id', $values)
        );

        $actionMask = array_sum(array_keys($actionLinks)) & $mask;

        $contactActivities[$activityId]['links'] = CRM_Core_Action::formLink($actionLinks,
          $actionMask,
          array(
            'id' => $values['activity_id'],
            'cid' => $params['contact_id'],
            'cxt' => $context,
            'caseid' => CRM_Utils_Array::value('case_id', $values),
          ),
          ts('more'),
          FALSE,
          'activity.tab.row',
          'Activity',
          $values['activity_id']
        );
      }
    }

    return $contactActivities;
  }

  /*
   * Used to copy custom fields and attachments from an existing activity to another.
   * see CRM_Case_Page_AJAX::_convertToCaseActivity() for example
   */
  /**
   * @param $params
   */
  static function copyExtendedActivityData($params) {
    // attach custom data to the new activity
    $customParams = $htmlType = array();
    $customValues = CRM_Core_BAO_CustomValueTable::getEntityValues($params['activityID'], 'Activity');

    if (!empty($customValues)) {
      $fieldIds = implode(', ', array_keys($customValues));
      $sql      = "SELECT id FROM civicrm_custom_field WHERE html_type = 'File' AND id IN ( {$fieldIds} )";
      $result   = CRM_Core_DAO::executeQuery($sql);

      while ($result->fetch()) {
        $htmlType[] = $result->id;
      }

      foreach ($customValues as $key => $value) {
        if ($value !== NULL) { // CRM-10542
          if (in_array($key, $htmlType)) {
            $fileValues = CRM_Core_BAO_File::path($value, $params['activityID']);
            $customParams["custom_{$key}_-1"] = array(
              'name' => $fileValues[0],
              'path' => $fileValues[1],
            );
          }
          else {
            $customParams["custom_{$key}_-1"] = $value;
          }
        }
      }
      CRM_Core_BAO_CustomValueTable::postProcess($customParams, CRM_Core_DAO::$_nullArray, 'civicrm_activity',
        $params['mainActivityId'], 'Activity'
      );
    }

    // copy activity attachments ( if any )
    CRM_Core_BAO_File::copyEntityFile('civicrm_activity', $params['activityID'], 'civicrm_activity', $params['mainActivityId']);
  }

  /**
   * @param $activityId
   * @param null $recordTypeID
   * @param string $column
   *
   * @return null
   */
  public static function getActivityContact($activityId, $recordTypeID = NULL, $column = 'contact_id') {
    $activityContact = new CRM_Activity_BAO_ActivityContact();
    $activityContact->activity_id = $activityId;
    if ($recordTypeID) {
      $activityContact->record_type_id = $recordTypeID;
    }
    if ($activityContact->find(TRUE)) {
      return $activityContact->$column;
    }
    return NULL;
  }

  /**
   * @param $activityId
   *
   * @return null
   */
  public static function getSourceContactID($activityId) {
    static $sourceID = NULL;
    if (!$sourceID) {
      $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
      $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    }

    return self::getActivityContact($activityId, $sourceID);
  }

  /**
   * @param $params
   */
  function setApiFilter(&$params) {
    if (CRM_Utils_Array::value('target_contact_id', $params)) {
      $this->selectAdd();
      $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
      $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
      $obj = new CRM_Activity_BAO_ActivityContact();
      $params['return.target_contact_id'] = 1;
      $this->joinAdd($obj, 'LEFT');
      $this->selectAdd('civicrm_activity.*');
      $this->whereAdd(" civicrm_activity_contact.contact_id = {$params['target_contact_id']} AND civicrm_activity_contact.record_type_id = {$targetID}");
    }
  }

}

