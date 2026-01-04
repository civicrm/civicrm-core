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

use Civi\Api4\ActivityContact;
use Civi\Api4\Contribution;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class is for activity functions.
 */
class CRM_Activity_BAO_Activity extends CRM_Activity_DAO_Activity {

  /**
   * Activity status types
   */
  const
    INCOMPLETE = 0,
    COMPLETED = 1,
    CANCELLED = 2;

  /**
   * Static field for all the activity information that we can potentially export.
   *
   * @var array
   */
  public static $_exportableFields = NULL;

  /**
   * Check if there is absolute minimum of data to add the object.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return bool
   */
  public static function dataExists(&$params) {
    if (!empty($params['source_contact_id']) || !empty($params['id'])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @deprecated
   *
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Activity_DAO_Activity
   */
  public static function retrieve(&$params, &$defaults) {
    // this will bypass acls - use the api instead.
    // @todo add deprecation logging to this function.
    $activity = new CRM_Activity_DAO_Activity();
    $activity->copyValues($params);

    if ($activity->find(TRUE)) {
      $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
      $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
      $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
      $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);

      // TODO: at some stage we'll have to deal
      //       with multiple values for assignees and targets, but
      //       for now, let's just fetch first row.
      $defaults['assignee_contact'] = CRM_Activity_BAO_ActivityContact::retrieveContactIdsByActivityId($activity->id, $assigneeID);
      $assignee_contact_names = CRM_Activity_BAO_ActivityContact::getNames($activity->id, $assigneeID);
      $defaults['assignee_contact_value'] = implode('; ', $assignee_contact_names);
      $sourceContactId = self::getActivityContact($activity->id, $sourceID);
      if ($activity->activity_type_id != CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Bulk Email')) {
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

      // Get case subject.
      $defaults['case_subject'] = CRM_Case_BAO_Case::getCaseSubject($activity->id);

      CRM_Core_DAO::storeValues($activity, $defaults);

      return $activity;
    }
    return NULL;
  }

  /**
   * Delete the activity.
   *
   * @param array $params
   * @param bool $moveToTrash
   *
   * @return mixed
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
    $sqlWhereParams = $where = [];
    if (isset($params['source_record_id']) && is_array($params['source_record_id'])) {
      $sourceRecordIds = implode(',', $params['source_record_id']);
    }
    else {
      $sourceRecordIds = $params['source_record_id'] ?? NULL;
    }

    if ($sourceRecordIds) {
      $where[] = 'source_record_id IN ( %1 )';
      $sqlWhereParams[1] = [$sourceRecordIds, 'CommaSeparatedIntegers'];
    }
    $result = NULL;
    if (!$moveToTrash) {
      if (!isset($params['id'])) {
        if (!empty($params['activity_type_id'])) {
          $where[] = 'activity_type_id IN ( %2 )';
          $sqlWhereParams[2] = [implode(',', (array) $params['activity_type_id']), 'CommaSeparatedIntegers'];
        }
        $query = "DELETE FROM civicrm_activity WHERE " . implode(' AND ', $where);
        $dao = CRM_Core_DAO::executeQuery($query, $sqlWhereParams);
      }
      else {
        $activity = new CRM_Activity_DAO_Activity();
        $activity->copyValues($params);
        $result = $activity->delete();

        // CRM-8708
        $activity->case_id = CRM_Case_BAO_Case::getCaseIdByActivityId($activity->id);

        // CRM-13994 delete activity entity_tag
        $query = "DELETE FROM civicrm_entity_tag WHERE entity_table = 'civicrm_activity' AND entity_id = %1";
        $dao = CRM_Core_DAO::executeQuery($query, [1 => [$activity->id, 'Positive']]);

        CRM_Core_BAO_File::deleteEntityFile('civicrm_activity', $activity->id);
      }
    }
    else {
      $activity = new CRM_Activity_DAO_Activity();
      $activity->copyValues($params);

      $activity->is_deleted = 1;
      $result = $activity->save();

      // CRM-4525 log activity delete
      $logMsg = 'Case Activity deleted for';
      $msgs = [];

      $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
      $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
      $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
      $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
      $sourceContactId = self::getActivityContact($activity->id, $sourceID);
      if ($sourceContactId) {
        $msgs[] = " source={$sourceContactId}";
      }

      // get target contacts.
      $targetContactIds = CRM_Activity_BAO_ActivityContact::getNames($activity->id, $targetID);
      if (!empty($targetContactIds)) {
        $msgs[] = " target =" . implode(',', array_keys($targetContactIds));
      }
      // get assignee contacts.
      $assigneeContactIds = CRM_Activity_BAO_ActivityContact::getNames($activity->id, $assigneeID);
      if (!empty($assigneeContactIds)) {
        $msgs[] = " assignee =" . implode(',', array_keys($assigneeContactIds));
      }

      $logMsg .= implode(', ', $msgs);

      self::logActivityAction($activity, $logMsg);
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
   * Delete activity assignment record.
   *
   * @param int $activityId
   * @param int $recordTypeID
   */
  public static function deleteActivityContact($activityId, $recordTypeID = NULL) {
    $activityContact = new CRM_Activity_BAO_ActivityContact();
    $activityContact->activity_id = $activityId;
    if ($recordTypeID) {
      $activityContact->record_type_id = $recordTypeID;
    }

    // Let's check if activity contact record exits and then delete.
    // Looks like delete leads to deadlock when multiple simultaneous
    // requests are done. CRM-15470
    if ($activityContact->find()) {
      $activityContact->delete();
    }
  }

  /**
   * Process the activities.
   *
   * @param array $params
   *   Associated array of the submitted values.
   *
   * @return CRM_Activity_DAO_Activity
   * @throws CRM_Core_Exception
   */
  public static function create(array &$params) {
    // CRM-20958 - These fields are managed by MySQL triggers. Watch out for clients resaving stale timestamps.
    unset($params['created_date']);
    unset($params['modified_date']);

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
        $params['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Completed');
      }
      else {
        $params['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Scheduled');
      }
    }

    // Set the default priority for Auto-populated activities (for Cases)
    if (!isset($params['priority_id']) && empty($params['id'])) {
      $params['priority_id'] = CRM_Core_OptionGroup::getDefaultValue('priority');
    }

    if (!empty($params['target_contact_id']) && is_array($params['target_contact_id'])) {
      $params['target_contact_id'] = array_unique($params['target_contact_id']);
    }
    if (!empty($params['assignee_contact_id']) && is_array($params['assignee_contact_id'])) {
      $params['assignee_contact_id'] = array_unique($params['assignee_contact_id']);
    }

    $action = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($action, 'Activity', $params['id'] ?? NULL, $params);

    $activity->copyValues($params);
    if (isset($params['case_id'])) {
      // CRM-8708, preserve case ID even though it's not part of the SQL model
      $activity->case_id = $params['case_id'];
    }
    elseif ($action === 'edit' && CRM_Core_Component::isEnabled('CiviCase')) {
      // CRM-8708, preserve case ID even though it's not part of the SQL model
      $activity->case_id = CRM_Case_BAO_Case::getCaseIdByActivityId($activity->id);
    }

    // start transaction
    $transaction = new CRM_Core_Transaction();
    $result = $activity->save();

    $activityId = $activity->id;
    $activityRecordTypes = [
      'source_contact_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Source'),
      'assignee_contact_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Assignees'),
      'target_contact_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Targets'),
    ];

    $activityContacts = [];
    // Cast to an array if we just have an integer. Index by record type id.
    foreach ($activityRecordTypes as $key => $recordTypeID) {
      if (isset($params[$key])) {
        if (empty($params[$key])) {
          $activityContacts[$recordTypeID] = [];
        }
        else {
          foreach ((array) $params[$key] as $contactID) {
            $activityContacts[$recordTypeID][$contactID] = (int) $contactID;
          }
        }
      }
    }

    if ($action === 'edit' && !empty($activityContacts)) {
      $wheres = [];
      foreach ($activityContacts as $recordTypeID => $contactIDs) {
        if (!empty($contactIDs)) {
          $wheres[$key] = "(record_type_id = $recordTypeID AND contact_id IN (" . implode(',', $contactIDs) . '))';
        }
      }
      $existingArray = empty($wheres) ? [] : CRM_Core_DAO::executeQuery("
        SELECT id, contact_id, record_type_id
        FROM civicrm_activity_contact
        WHERE activity_id = %1
          AND (" . implode(' OR ', $wheres) . ')',
      [1 => [$params['id'], 'Integer']])->fetchAll();

      $recordsToKeep = [];
      $wheres = [['activity_id', '=', $params['id']], ['record_type_id', 'IN', array_keys($activityContacts)]];

      foreach ($existingArray as $existingRecords) {
        $recordsToKeep[$existingRecords['id']] = ['contact_id' => $existingRecords['contact_id'], 'record_type_id' => $existingRecords['record_type_id']];
        unset($activityContacts[$recordTypeID][$existingRecords['contact_id']]);
        if (empty($activityContacts[$recordTypeID])) {
          // If we just removed the last one to update then also unset the key.
          unset($activityContacts[$recordTypeID]);
        }
      }

      if (!empty($recordsToKeep)) {
        $wheres[] = ['id', 'NOT IN', array_keys($recordsToKeep)];
      }

      // Delete all existing records for the types to be updated. Do a quick check to make sure there
      // is at least one to avoid a delete query if not necessary (delete queries are more likely to cause contention).
      if (ActivityContact::get($params['check_permissions'] ?? FALSE)->setLimit(1)->setWhere($wheres)->selectRowCount()->execute()) {
        ActivityContact::delete($params['check_permissions'] ?? FALSE)->setWhere($wheres)->execute();
      }
    }

    $activityContactApiValues = [];
    foreach ($activityContacts as $recordTypeID => $contactIDs) {
      foreach ($contactIDs as $contactID) {
        $activityContactApiValues[] = ['record_type_id' => $recordTypeID, 'contact_id' => $contactID];
      }
    }

    if (!empty($activityContactApiValues)) {
      ActivityContact::save($params['check_permissions'] ?? FALSE)->addDefault('activity_id', $activityId)
        ->setRecords($activityContactApiValues)->execute();
    }

    // check and attach and files as needed
    CRM_Core_BAO_File::processAttachment($params, 'civicrm_activity', $activityId);

    // write to changelog before transaction is committed/rolled
    // back (and prepare status to display)
    if (!empty($params['id'])) {
      $logMsg = "Activity (id: {$result->id} ) updated with ";
    }
    else {
      $logMsg = "Activity created for ";
    }

    $msgs = [];
    if (isset($params['source_contact_id'])) {
      $msgs[] = "source={$params['source_contact_id']}";
    }

    if (!empty($params['target_contact_id'])) {
      if (is_array($params['target_contact_id']) && !CRM_Utils_Array::crmIsEmptyArray($params['target_contact_id'])) {
        $msgs[] = "target=" . implode(',', $params['target_contact_id']);
        // take only first target
        // will be used for recently viewed display
        $t = array_slice($params['target_contact_id'], 0, 1);
        $recentContactId = $t[0];
      }
      // Is array check fixes warning without degrading functionality but it seems this bit of code may no longer work
      // as it may always be an array
      elseif (isset($params['target_contact_id']) && !is_array($params['target_contact_id'])) {
        $msgs[] = "target={$params['target_contact_id']}";
        // will be used for recently viewed display
        $recentContactId = $params['target_contact_id'];
      }
    }
    else {
      // at worst, take source for recently viewed display
      $recentContactId = $params['source_contact_id'] ?? NULL;
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
      $recentOther = [];
      if (!empty($params['case_id'])) {
        $caseId = CRM_Utils_Array::first((array) $params['case_id']);
        $caseContactID = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseContact', $caseId, 'contact_id', 'case_id');
        $url = CRM_Utils_System::url('civicrm/case/activity/view',
          "reset=1&aid={$activity->id}&cid={$caseContactID}&caseID={$caseId}&context=home"
        );
      }
      else {
        $q = "action=view&reset=1&id={$activity->id}&atype={$activity->activity_type_id}&cid=" . ($params['source_contact_id'] ?? '') . "&context=home";
        if ($activity->activity_type_id != CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Email')) {
          $url = CRM_Utils_System::url('civicrm/activity', $q);
          if ($activity->activity_type_id == CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Print PDF Letter')) {
            $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/activity/pdf/add',
              "action=update&reset=1&id={$activity->id}&atype={$activity->activity_type_id}&cid={$params['source_contact_id']}&context=home"
            );
          }
          else {
            $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/activity/add',
              "action=update&reset=1&id={$activity->id}&atype={$activity->activity_type_id}&cid=" . ($params['source_contact_id'] ?? '') . "&context=home"
            );
          }

          if (CRM_Core_Permission::check("delete activities")) {
            $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/activity',
              "action=delete&reset=1&id={$activity->id}&atype={$activity->activity_type_id}&cid=" . ($params['source_contact_id'] ?? '') . "&context=home"
            );
          }
        }
        else {
          $url = CRM_Utils_System::url('civicrm/activity/view', $q);
          if (CRM_Core_Permission::check('delete activities')) {
            $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/activity',
              "action=delete&reset=1&id={$activity->id}&atype={$activity->activity_type_id}&cid=" . ($params['source_contact_id'] ?? '') . "&context=home"
            );
          }
        }
      }

      if (!isset($activity->parent_id)) {
        $recentContactDisplay = CRM_Contact_BAO_Contact::displayName($recentContactId);
        // add the recently created Activity
        $activityTypes = CRM_Activity_BAO_Activity::buildOptions('activity_type_id');
        $activitySubject = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $activity->id, 'subject');

        $title = "";
        if (isset($activitySubject)) {
          $title = $activitySubject . ' - ';
        }

        $title .= $recentContactDisplay;
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

    CRM_Contact_BAO_GroupContactCache::opportunisticCacheFlush();

    CRM_Utils_Hook::post($action, 'Activity', $activity->id, $activity, $params);
    return $result;
  }

  /**
   * Adds an entry to the log table about an activity
   *
   * @param CRM_Activity_DAO_Activity $activity
   * @param string $logMessage
   *
   * @return bool
   */
  public static function logActivityAction($activity, $logMessage = NULL) {
    $id = CRM_Core_Session::getLoggedInContactID();
    if (!$id) {
      $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
      $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
      $id = self::getActivityContact($activity->id, $sourceID);
    }
    $logParams = [
      'entity_table' => 'civicrm_activity',
      'entity_id' => $activity->id,
      'modified_id' => $id,
      'modified_date' => date('YmdHis'),
      'data' => $logMessage,
    ];
    CRM_Core_BAO_Log::add($logParams);
    return TRUE;
  }

  /**
   * Get the list Activities.
   *
   * @param array $params
   *   Array of parameters.
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
   * @return array
   *   Relevant data object values of open activities
   * @throws \CRM_Core_Exception
   */
  public static function getActivities($params) {
    $activities = [];

    // Activity.Get API params
    $activityParams = self::getActivityParamsForDashboardFunctions($params);

    if (!empty($params['rowCount']) &&
      $params['rowCount'] > 0
    ) {
      $activityParams['options']['limit'] = $params['rowCount'];
    }

    if (!empty($params['sort'])) {
      if (is_a($params['sort'], 'CRM_Utils_Sort')) {
        $order = $params['sort']->orderBy();
      }
      elseif (trim($params['sort'])) {
        $order = CRM_Utils_Type::escape($params['sort'], 'String');
      }
    }

    $activityParams['options']['sort'] = empty($order) ? "activity_date_time DESC" : str_replace('activity_type ', 'activity_type_id.label ', $order);

    $activityParams['return'] = [
      'activity_date_time',
      'source_record_id',
      'source_contact_id',
      'source_contact_name',
      'assignee_contact_id',
      'assignee_contact_name',
      'status_id',
      'subject',
      'activity_type_id',
      'activity_type',
      'case_id',
      'campaign_id',
    ];
    // Q. What does the code below achieve? case_id and campaign_id are already
    // in the array, defined above, and this code adds them in again if their
    // component is enabled? @fixme remove case_id and campaign_id from the array above?
    foreach (['case_id' => 'CiviCase', 'campaign_id' => 'CiviCampaign'] as $attr => $component) {
      if (in_array($component, self::activityComponents())) {
        $activityParams['return'][] = $attr;
      }
    }
    $result = civicrm_api3('Activity', 'Get', $activityParams)['values'];

    $bulkActivityTypeID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Bulk Email');
    $allCampaigns = CRM_Campaign_BAO_Campaign::getCampaigns(NULL, NULL, FALSE, FALSE, FALSE, TRUE);

    // CRM-3553, need to check user has access to target groups.
    $mailingIDs = CRM_Mailing_BAO_Mailing::mailingACLIDs();
    $accessCiviMail = ((CRM_Core_Permission::check('access CiviMail')) ||
      (CRM_Mailing_Info::workflowEnabled() && CRM_Core_Permission::check('create mailings'))
    );

    // @todo - get rid of this & just handle in the array declaration like we do with 'subject' etc.
    $mappingParams = [
      'source_record_id' => 'source_record_id',
      'activity_type_id' => 'activity_type_id',
      'status_id' => 'status_id',
      'campaign_id' => 'campaign_id',
      'case_id' => 'case_id',
    ];

    if (empty($result)) {
      $targetCount = [];
    }
    else {
      $targetCount = CRM_Core_DAO::executeQuery('
      SELECT activity_id, count(*) as target_contact_count
      FROM civicrm_activity_contact
      INNER JOIN civicrm_contact c ON contact_id = c.id AND c.is_deleted = 0
      WHERE activity_id IN (' . implode(',', array_keys($result)) . ')
      AND record_type_id = %1
      GROUP BY activity_id', [
        1 => [
          CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Targets'),
          'Integer',
        ],
      ])->fetchAll();
    }
    foreach ($targetCount as $activityTarget) {
      $result[$activityTarget['activity_id']]['target_contact_count'] = $activityTarget['target_contact_count'];
    }
    // Iterate through & do basic mappings & determine which ones we want to retrieve target count for.
    foreach ($result as $id => $activity) {
      $activities[$id] = [
        'activity_id' => $activity['id'],
        'activity_date_time' => $activity['activity_date_time'] ?? NULL,
        'subject' => $activity['subject'] ?? NULL,
        'assignee_contact_name' => $activity['assignee_contact_sort_name'] ?? [],
        'source_contact_id' => $activity['source_contact_id'] ?? NULL,
        'source_contact_name' => $activity['source_contact_sort_name'] ?? NULL,
      ];
      $activities[$id]['activity_type_name'] = CRM_Core_PseudoConstant::getName('CRM_Activity_BAO_Activity', 'activity_type_id', $activity['activity_type_id']);
      $activities[$id]['activity_type'] = CRM_Core_PseudoConstant::getLabel('CRM_Activity_BAO_Activity', 'activity_type_id', $activity['activity_type_id']);
      $activities[$id]['target_contact_count'] = $activity['target_contact_count'] ?? 0;
      if (!empty($activity['target_contact_count'])) {
        $displayedTarget = civicrm_api3('ActivityContact', 'get', [
          'activity_id' => $id,
          'check_permissions' => TRUE,
          'options' => ['limit' => 1],
          'record_type_id' => 'Activity Targets',
          'return' => ['contact_id.sort_name', 'contact_id'],
          'sequential' => 1,
        ])['values'];
        if (empty($displayedTarget[0])) {
          $activities[$id]['target_contact_name'] = [];
        }
        else {
          $activities[$id]['target_contact_name'] = [$displayedTarget[0]['contact_id'] => $displayedTarget[0]['contact_id.sort_name']];
        }
      }
      if ($activities[$id]['activity_type_name'] === 'Bulk Email') {
        $bulkActivities[] = $id;
        // Get the total without permissions being passed but only display names after permissioning.
        $activities[$id]['recipients'] = ts('(%1 recipients)', [1 => $activities[$id]['target_contact_count']]);
      }
    }

    // Eventually this second iteration should just handle the target contacts. It's a bit muddled at
    // the moment as the bulk activity stuff needs unravelling & test coverage.
    $caseIds = [];
    foreach ($result as $id => $activity) {
      $isBulkActivity = (!$bulkActivityTypeID || ($bulkActivityTypeID === $activity['activity_type_id']));
      foreach ($mappingParams as $apiKey => $expectedName) {
        if (in_array($apiKey, ['target_contact_name'])) {

          if ($isBulkActivity) {
            // @todo  - how is this used? Couldn't we use 'is_bulk' or something clearer?
            // or the calling function could handle
            $activities[$id]['mailingId'] = FALSE;
            if ($accessCiviMail &&
              ($mailingIDs === TRUE || in_array($activity['source_record_id'], $mailingIDs))
            ) {
              $activities[$id]['mailingId'] = TRUE;
            }
          }
        }
        // case related fields
        elseif ($apiKey == 'case_id' && !$isBulkActivity) {
          $activities[$id][$expectedName] = $activity[$apiKey] ?? NULL;

          // fetch case subject for case ID found
          if (!empty($activity['case_id'])) {
            // Store cases; we'll look them up in one query below. We convert
            // to int here so we can trust it for SQL.
            $caseIds[$id] = (int) current($activity['case_id']);
          }
        }
        else {
          // @todo this generic assign could just be handled in array declaration earlier.
          $activities[$id][$expectedName] = $activity[$apiKey] ?? NULL;
          if ($apiKey == 'campaign_id') {
            $activities[$id]['campaign'] = $allCampaigns[$activities[$id][$expectedName]] ?? NULL;
          }
        }
      }
      $activities[$id]['is_recurring_activity'] = CRM_Core_BAO_RecurringEntity::getParentFor($id, 'civicrm_activity');
    }

    // Look up any case subjects we need in a single query and add them in the relevant activities under 'case_subject'
    if ($caseIds) {
      $subjects = CRM_Core_DAO::executeQuery('SELECT id, subject FROM civicrm_case WHERE id IN (' . implode(',', array_unique($caseIds)) . ')')
        ->fetchMap('id', 'subject');
      foreach ($caseIds as $activityId => $caseId) {
        $result[$activityId]['case_subject'] = $subjects[$caseId];
      }
    }

    return $activities;
  }

  /**
   * Filter the activity types to only return the ones we actually asked for
   * Uses params['activity_type_id'] and params['activity_type_exclude_id']
   *
   * @param array $params
   * @return array|null (Use in Activity.get API activity_type_id)
   */
  public static function filterActivityTypes($params) {
    $activityTypes = [];

    // If no activity types are specified, get all the active ones
    if (empty($params['activity_type_id'])) {
      $activityTypes = CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'get');
    }

    // If no activity types are specified or excluded, return the list of all active ones
    if (empty($params['activity_type_id']) && empty($params['activity_type_exclude_id'])) {
      if (!empty($activityTypes)) {
        return ['IN' => array_keys($activityTypes)];
      }
      return NULL;
    }

    // If we have specified activity types, build a list to return, excluding the ones we don't want.
    if (!empty($params['activity_type_id'])) {
      if (!is_array($params['activity_type_id'])) {
        // Turn it into array if only one specified, so we don't duplicate processing below
        $params['activity_type_id'] = [$params['activity_type_id'] => $params['activity_type_id']];
      }
      foreach ($params['activity_type_id'] as $value) {
        // Add each activity type that was specified to list
        $value = CRM_Utils_Type::escape($value, 'Positive');
        $activityTypes[$value] = $value;
      }
    }

    // Build the list of activity types to exclude (from $params['activity_type_exclude_id'])
    if (!empty($params['activity_type_exclude_id'])) {
      if (!is_array($params['activity_type_exclude_id'])) {
        // Turn it into array if only one specified, so we don't duplicate processing below
        $params['activity_type_exclude_id'] = [$params['activity_type_exclude_id'] => $params['activity_type_exclude_id']];
      }
      foreach ($params['activity_type_exclude_id'] as $value) {
        // Remove each activity type from list if it should be excluded
        $value = CRM_Utils_Type::escape($value, 'Positive');
        if (array_key_exists($value, $activityTypes)) {
          unset($activityTypes[$value]);
        }
      }
    }

    return ['IN' => array_keys($activityTypes)];
  }

  /**
   * @param string|null $entityName
   * @param int|null $userId
   * @param array $conditions
   * @inheritDoc
   */
  public function addSelectWhereClause(?string $entityName = NULL, ?int $userId = NULL, array $conditions = []): array {
    $clauses = [];
    $permittedActivityTypeIDs = self::getPermittedActivityTypes();
    if (!empty($conditions['activity_type_id'])) {
      $allActivityTypes = (array) $conditions['activity_type_id'];
    }
    else {
      $allActivityTypes = self::buildOptions('activity_type_id', 'validate');
    }
    if (empty($permittedActivityTypeIDs)) {
      // This just prevents a mysql fail if they have no access - should be extremely edge case.
      $permittedActivityTypeIDs = [0];
    }
    if (array_diff($allActivityTypes, $permittedActivityTypeIDs)) {
      $clauses['activity_type_id'] = ['IN (' . implode(', ', $permittedActivityTypeIDs) . ')'];
    }

    $contactClause = CRM_Utils_SQL::mergeSubquery('Contact');
    if ($contactClause) {
      $contactClause = implode(' AND contact_id ', $contactClause);
      $clauses['id'][] = "IN (SELECT activity_id FROM civicrm_activity_contact WHERE contact_id $contactClause)";
    }
    CRM_Utils_Hook::selectWhereClause($this, $clauses, $userId, $conditions);
    return $clauses;
  }

  /**
   * Get an array of components that are accessible by the currenct user.
   *
   * This means checking if they are enabled and if the user has appropriate permission.
   *
   * For most components the permission is access component (e.g 'access CiviContribute').
   * Exceptions as CiviCampaign (administer CiviCampaign) and CiviCase
   * (accesses a case function which enforces edit all cases or edit my cases. Case
   * permissions are also handled on a per activity basis).
   *
   * Checks whether logged in user has permission to the component.
   *
   * @param bool $excludeComponentHandledActivities
   *   Should we exclude components whose display is handled in the components.
   *   In practice this means should we include CiviCase in the results. Presumbaly
   *   at the time it was decided case activities should be shown in the case framework and
   *   that this concept might be extended later. In practice most places that
   *   call this then re-add CiviCase in some way so it's all a bit... odd.
   *
   * @return array
   *   Array of component id and name.
   */
  public static function activityComponents($excludeComponentHandledActivities = TRUE) {
    $components = [];
    $compInfo = CRM_Core_Component::getEnabledComponents();
    foreach ($compInfo as $compObj) {
      $includeComponent = !$excludeComponentHandledActivities || !empty($compObj->info['showActivitiesInCore']);
      if ($includeComponent) {
        if ($compObj->info['name'] == 'CiviCampaign') {
          $componentPermission = "manage campaign";
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
   * Get the activity Count.
   *
   * @param array $input
   *   Array of parameters.
   *    Keys include
   *    - contact_id  int            contact_id whose activities we want to retrieve
   *    - admin       boolean        if contact is admin
   *    - caseId      int            case ID
   *    - context     string         page on which selector is build
   *    - activity_type_id int|string the activity types we want to restrict by
   *
   * @return int
   *   count of activities
   */
  public static function getActivitiesCount($input) {
    $activityParams = self::getActivityParamsForDashboardFunctions($input);
    return civicrm_api3('Activity', 'getcount', $activityParams);
  }

  /**
   * DO NOT USE.
   *
   * Deprecated from core - will be removed.
   *
   * @param int $sourceContactID
   *   The contact ID of the email "from".
   * @param string $subject
   * @param string $html
   * @param string $text
   * @param string $additionalDetails
   *   The additional information of CC and BCC appended to the activity details.
   * @param int $campaignID
   * @param array $attachments
   * @param int $caseID
   *
   * @deprecated
   *
   * @return int
   *   The created activity ID
   * @throws \CRM_Core_Exception
   */
  public static function createEmailActivity($sourceContactID, $subject, $html, $text, $additionalDetails, $campaignID, $attachments, $caseID) {
    $activityTypeID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Email');
    CRM_Core_Error::deprecatedFunctionWarning('none');
    // CRM-6265: save both text and HTML parts in details (if present)
    if ($html and $text) {
      $details = "-ALTERNATIVE ITEM 0-\n{$html}{$additionalDetails}\n-ALTERNATIVE ITEM 1-\n{$text}{$additionalDetails}\n-ALTERNATIVE END-\n";
    }
    else {
      $details = $html ?: $text;
      $details .= $additionalDetails;
    }

    $activityParams = [
      'source_contact_id' => $sourceContactID,
      'activity_type_id' => $activityTypeID,
      'activity_date_time' => date('YmdHis'),
      'subject' => $subject,
      'details' => $details,
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Completed'),
      'campaign_id' => $campaignID,
    ];
    if (!empty($caseID)) {
      $activityParams['case_id'] = $caseID;
    }

    // CRM-5916: strip [case #…] before saving the activity (if present in subject)
    $activityParams['subject'] = preg_replace('/\[case #([0-9a-h]{7})\] /', '', $activityParams['subject']);

    // add the attachments to activity params here
    if ($attachments) {
      // first process them
      $activityParams = array_merge($activityParams, $attachments);
    }

    $activity = civicrm_api3('Activity', 'create', $activityParams);

    return $activity['id'];
  }

  /**
   * DO NOT USE THIS FUNCTION - DEPRECATED.
   *
   * Also insert a contact activity in each contacts record.
   *
   * @param array $contactDetails
   *   The array of contact details to send the email.
   * @param string $subject
   *   The subject of the message.
   * @param string|null $text
   * @param string|null $html
   * @param string $emailAddress
   *   Use this 'to' email address instead of the default Primary address.
   * @param int|null $userID
   *   Use this userID if set.
   * @param string|null $from
   * @param array|null $attachments
   *   The array of attachments if any.
   * @param string|null $cc
   *   Cc recipient.
   * @param string|null $bcc
   *   Bcc recipient.
   * @param array|null $contactIds
   *   unused.
   * @param string|null $additionalDetails
   *   The additional information of CC and BCC appended to the activity Details.
   * @param array|null $contributionIds
   * @param int|null $campaignId
   * @param int|null $caseId
   *
   * @return array
   *   bool $sent FIXME: this only indicates the status of the last email sent.
   *   array $activityIds The activity ids created, one per "To" recipient.
   *
   * @deprecated
   *
   * @throws \CRM_Core_Exception
   */
  public static function sendEmail(
    $contactDetails,
    $subject,
    $text,
    $html,
    $emailAddress,
    $userID = NULL,
    $from = NULL,
    $attachments = NULL,
    $cc = NULL,
    $bcc = NULL,
    $contactIds = NULL,
    $additionalDetails = NULL,
    $contributionIds = NULL,
    $campaignId = NULL,
    $caseId = NULL
  ) {
    CRM_Core_Error::deprecatedFunctionWarning('none');
    if ($userID == NULL) {
      $userID = CRM_Core_Session::getLoggedInContactID();
    }

    [$fromDisplayName, $fromEmail, $fromDoNotEmail] = CRM_Contact_BAO_Contact::getContactDetails($userID);
    if (!$fromEmail) {
      return [count($contactDetails), 0, count($contactDetails)];
    }
    if (!trim($fromDisplayName)) {
      $fromDisplayName = $fromEmail;
    }

    if (!$from) {
      $from = "$fromDisplayName <$fromEmail>";
    }

    $contributionDetails = [];
    if (!empty($contributionIds)) {
      $contributionDetails = Contribution::get(FALSE)
        ->setSelect(['contact_id'])
        ->addWhere('id', 'IN', $contributionIds)
        ->execute()
        // Note that this indexing means that only the last
        // contribution per contact is resolved to tokens.
        // this is long-standing functionality, albeit possibly
        // not thought through.
        ->indexBy('contact_id');
    }

    $sent = $notSent = [];
    $attachmentFileIds = [];
    $activityIds = [];
    $firstActivityCreated = FALSE;
    foreach ($contactDetails as $values) {
      $tokenContext = $caseId ? ['caseId' => $caseId] : [];
      $contactId = $values['contact_id'];
      $emailAddress = $values['email'];

      if (!empty($contributionDetails)) {
        $tokenContext['contributionId'] = $contributionDetails[$contactId]['id'];
      }

      $tokenSubject = $subject;

      $renderedTemplate = CRM_Core_BAO_MessageTemplate::renderTemplate([
        'messageTemplate' => [
          'msg_text' => $text,
          'msg_html' => $html,
          'msg_subject' => $tokenSubject,
        ],
        'tokenContext' => $tokenContext,
        'contactId' => $contactId,
        'disableSmarty' => !CRM_Utils_Constant::value('CIVICRM_MAIL_SMARTY'),
      ]);

      $sent = FALSE;
      // To minimize storage requirements, only one copy of any file attachments uploaded to CiviCRM is kept,
      // even when multiple contacts will receive separate emails from CiviCRM.
      if (!empty($attachmentFileIds)) {
        $attachments = array_replace_recursive($attachments, $attachmentFileIds);
      }

      // Create email activity.
      $activityID = self::createEmailActivity($userID, $renderedTemplate['subject'], $renderedTemplate['html'], $renderedTemplate['text'], $additionalDetails, $campaignId, $attachments, $caseId);
      $activityIds[] = $activityID;

      if ($firstActivityCreated == FALSE && !empty($attachments)) {
        $attachmentFileIds = self::getAttachmentFileIds($activityID, $attachments);
        $firstActivityCreated = TRUE;
      }

      if (self::sendMessage(
        $from,
        $userID,
        $contactId,
        $renderedTemplate['subject'],
        $renderedTemplate['text'],
        $renderedTemplate['html'],
        $emailAddress,
        $activityID,
        // get the set of attachments from where they are stored
        CRM_Core_BAO_File::getEntityFile('civicrm_activity', $activityID),
        $cc,
        $bcc
      )
      ) {
        $sent = TRUE;
      }
    }

    return [$sent, $activityIds];
  }

  /**
   * Returns a array of attachment key with matching file ID.
   *
   * The function searches for all file Ids added for the activity and returns an array that
   * uses the attachment key as the key and the file ID in the database for that matching attachment
   * key by comparing the file URI for that attachment to the matching file URI fetched from the
   * database. Having the file id matched per attachment key helps not to create a new file entry
   * when a new activity with these attachments when the email activity is created.
   *
   * @param int $activityID
   *   Activity Id.
   * @param array $attachments
   *   Attachments.
   *
   * @internal
   *
   * @return array
   *   Array of attachment key versus file Id.
   */
  public static function getAttachmentFileIds($activityID, $attachments) {
    $queryParams = [1 => [$activityID, 'Positive'], 2 => [CRM_Activity_DAO_Activity::getTableName(), 'String']];
    $query = "SELECT file_id, uri FROM civicrm_entity_file INNER JOIN civicrm_file ON civicrm_entity_file.file_id = civicrm_file.id
WHERE entity_id =%1 AND entity_table = %2";
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);

    $fileDetails = [];
    while ($dao->fetch()) {
      $fileDetails[$dao->uri] = $dao->file_id;
    }

    $activityAttachments = [];
    foreach ($attachments as $attachmentKey => $attachment) {
      foreach ($fileDetails as $keyUri => $fileId) {
        $path = explode('/', $attachment['uri']);
        $filename = $path[count($path) - 1];
        if ($filename == $keyUri) {
          $activityAttachments[$attachmentKey]['id'] = $fileId;
        }
      }
    }

    return $activityAttachments;
  }

  /**
   * Send SMS.  Returns: bool $sent, int $activityId, int $success (number of sent SMS)
   *
   * @param array $contactDetails
   * @param array $activityParams
   * @param array $smsProviderParams
   * @param array $contactIds
   * @param int $sourceContactId This is the source contact Id
   *
   * @deprecated since 5.71.
   *
   * This function has no core usage. There is some non-core usage. At some point
   * we should add a suitable api & noisily deprecate this / set an tentative
   * removal date.
   *
   * @return array(bool $sent, int $activityId, int $success)
   * @throws CRM_Core_Exception
   */
  public static function sendSMS(
    &$contactDetails,
    &$activityParams,
    &$smsProviderParams = [],
    &$contactIds = NULL,
    $sourceContactId = NULL
  ) {
    if (!CRM_Core_Permission::check('send SMS')) {
      throw new CRM_Core_Exception("You do not have the 'send SMS' permission");
    }

    if (!isset($contactDetails) && !isset($contactIds)) {
      throw new CRM_Core_Exception('You must specify either $contactDetails or $contactIds');
    }
    // Populate $contactDetails and $contactIds if only one is set
    if (is_array($contactIds) && !empty($contactIds) && empty($contactDetails)) {
      foreach ($contactIds as $id) {
        try {
          $contactDetails[] = civicrm_api3('Contact', 'getsingle', ['contact_id' => $id]);
        }
        catch (Exception $e) {
          // Contact Id doesn't exist
        }
      }
    }
    elseif (is_array($contactDetails) && !empty($contactDetails) && empty($contactIds)) {
      foreach ($contactDetails as $contact) {
        $contactIds[] = $contact['contact_id'];
      }
    }

    // Get logged in User Id
    if (empty($sourceContactId)) {
      $sourceContactId = CRM_Core_Session::getLoggedInContactID();
    }

    $text = &$activityParams['sms_text_message'];

    // Create the meta level record first ( sms activity )
    $activityParams = [
      'source_contact_id' => $sourceContactId,
      'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'SMS'),
      'activity_date_time' => date('YmdHis'),
      'subject' => $activityParams['activity_subject'],
      'details' => $text,
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Completed'),
    ];
    $activity = self::create($activityParams);
    $activityID = $activity->id;

    $success = 0;
    $errMsgs = [];
    foreach ($contactDetails as $contact) {
      $contactId = $contact['contact_id'];
      $tokenText = CRM_Core_BAO_MessageTemplate::renderTemplate(['messageTemplate' => ['msg_text' => $text], 'contactId' => $contactId, 'disableSmarty' => TRUE])['text'];

      // Only send if the phone is of type mobile
      if ($contact['phone_type_id'] == CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Phone', 'phone_type_id', 'Mobile')) {
        $smsProviderParams['To'] = $contact['phone'];
      }
      else {
        $smsProviderParams['To'] = '';
      }

      $doNotSms = $contact['do_not_sms'] ?? 0;

      if ($doNotSms) {
        $errMsgs[] = PEAR::raiseError('Contact Does not accept SMS', NULL, PEAR_ERROR_RETURN);
      }
      else {
        try {
          $sendResult = self::sendSMSMessage(
            $contactId,
            $tokenText,
            $smsProviderParams,
            $activityID,
            $sourceContactId
          );
          $success++;
        }
        catch (CRM_Core_Exception $e) {
          $errMsgs[] = $e->getMessage();
        }
      }
    }

    // If at least one message was sent and no errors
    // were generated then return a boolean value of TRUE.
    // Otherwise, return FALSE (no messages sent) or
    // and array of 1 or more PEAR_Error objects.
    $sent = FALSE;
    if ($success > 0 && count($errMsgs) == 0) {
      $sent = TRUE;
    }
    elseif (count($errMsgs) > 0) {
      $sent = $errMsgs;
    }

    return [$sent, $activity->id, $success];
  }

  /**
   * Send the sms message to a specific contact.
   *
   * @param int $toID
   *   The contact id of the recipient.
   * @param string $tokenText
   * @param array $smsProviderParams
   *   The params used for sending sms.
   * @param int $activityID
   *   The activity ID that tracks the message.
   * @param int $sourceContactID
   * @param int|null $entityID
   *
   * @return bool true on success
   * @throws CRM_Core_Exception
   */
  public static function sendSMSMessage(
    $toID,
    &$tokenText,
    $smsProviderParams,
    $activityID,
    $sourceContactID = NULL,
    $entityID = NULL
  ) {
    $toPhoneNumber = NULL;
    if ($smsProviderParams['To']) {
      // If phone number is specified use it
      $toPhoneNumber = trim($smsProviderParams['To']);
    }
    elseif ($toID) {
      // No phone number specified, so find a suitable one for the contact
      $filters = ['is_deceased' => 0, 'is_deleted' => 0, 'do_not_sms' => 0];
      $toPhoneNumbers = CRM_Core_BAO_Phone::allPhones($toID, FALSE, 'Mobile', $filters);
      // To get primary mobile phonenumber, if not get the first mobile phonenumber
      if (!empty($toPhoneNumbers)) {
        $toPhoneNumberDetails = reset($toPhoneNumbers);
        $toPhoneNumber = $toPhoneNumberDetails['phone'] ?? NULL;
        // Contact allows to send sms
      }
    }

    // make sure both phone are valid
    // and that the recipient wants to receive sms
    if (empty($toPhoneNumber)) {
      throw new CRM_Core_Exception('Recipient phone number is invalid or recipient does not want to receive SMS');
    }

    $recipient = $toPhoneNumber;
    $smsProviderParams['contact_id'] = $toID;
    $smsProviderParams['parent_activity_id'] = $activityID;
    $smsProviderParams['entity_id'] = $entityID;

    $providerObj = CRM_SMS_Provider::singleton(['provider_id' => $smsProviderParams['provider_id']]);
    $sendResult = $providerObj->send($recipient, $smsProviderParams, $tokenText, NULL, $sourceContactID);

    // add activity target record for every sms that is sent
    $targetID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Targets');
    $activityTargetParams = [
      'activity_id' => $activityID,
      'contact_id' => $toID,
      'record_type_id' => $targetID,
    ];
    CRM_Activity_BAO_ActivityContact::create($activityTargetParams);

    return TRUE;
  }

  /**
   * DO Not use this function. Under deprecation, no active core use.
   *
   * Send the message to a specific contact.
   *
   * @param string $from
   *   The name and email of the sender.
   * @param int $fromID
   * @param int $toID
   *   The contact id of the recipient.
   * @param string $subject
   *   The subject of the message.
   * @param string|null $text_message
   * @param string|null $html_message
   * @param string $emailAddress
   *   Use this 'to' email address instead of the default Primary address.
   * @param int $activityID
   *   The activity ID that tracks the message.
   * @param null $attachments
   * @param null $cc
   * @param null $bcc
   *
   * @return bool
   *   TRUE if successful else FALSE.
   *
   * @deprecated
   */
  public static function sendMessage(
    $from,
    $fromID,
    $toID,
    &$subject,
    &$text_message,
    &$html_message,
    $emailAddress,
    $activityID,
    $attachments = NULL,
    $cc = NULL,
    $bcc = NULL
  ) {
    CRM_Core_Error::deprecatedFunctionWarning('none');
    [$toDisplayName, $toEmail, $toDoNotEmail] = CRM_Contact_BAO_Contact::getContactDetails($toID);
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

    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    // create the params array
    $mailParams = [
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
    ];

    if (!CRM_Utils_Mail::send($mailParams)) {
      return FALSE;
    }

    // add activity target record for every mail that is send
    $activityTargetParams = [
      'activity_id' => $activityID,
      'contact_id' => $toID,
      'record_type_id' => $targetID,
    ];
    CRM_Activity_BAO_ActivityContact::create($activityTargetParams);
    return TRUE;
  }

  /**
   * @deprecated - use the api instead.
   *
   * Get the Activities of a target contact.
   *
   * @param int $contactId
   *   Id of the contact whose activities need to find.
   *
   * @return array
   *   array of activity fields
   */
  public static function getContactActivity($contactId) {
    // @todo remove this function entirely.
    $activities = [];
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
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
      if ($dao->record_type_id == $targetID) {
        $activities[$dao->activity_id]['targets'][$contactId] = $contactId;
      }
      elseif ($dao->record_type_id == $assigneeID) {
        $activities[$dao->activity_id]['asignees'][$contactId] = $contactId;
      }
      else {
        // do source stuff here
        $activities[$dao->activity_id]['source_contact_id'] = $contactId;
      }
    }

    $activityIds = array_keys($activities);
    if (count($activityIds) < 1) {
      return [];
    }

    $activityIds = implode(',', $activityIds);
    $query = "
SELECT     activity.id as activity_id,
           activity_type_id,
           subject, location, activity_date_time, details, status_id
FROM       civicrm_activity activity
WHERE      activity.id IN ($activityIds)";

    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      $activities[$dao->activity_id]['id'] = $dao->activity_id;
      $activities[$dao->activity_id]['activity_type_id'] = $dao->activity_type_id;
      $activities[$dao->activity_id]['subject'] = $dao->subject;
      $activities[$dao->activity_id]['location'] = $dao->location;
      $activities[$dao->activity_id]['activity_date_time'] = $dao->activity_date_time;
      $activities[$dao->activity_id]['details'] = $dao->details;
      $activities[$dao->activity_id]['status_id'] = $dao->status_id;
      $activities[$dao->activity_id]['activity_name'] = CRM_Core_PseudoConstant::getLabel('CRM_Activity_BAO_Activity', 'activity_type_id', $dao->activity_type_id);
      $activities[$dao->activity_id]['status'] = CRM_Core_PseudoConstant::getLabel('CRM_Activity_BAO_Activity', 'activity_status_id', $dao->status_id);

      // set to null if not set
      if (!isset($activities[$dao->activity_id]['source_contact_id'])) {
        $activities[$dao->activity_id]['source_contact_id'] = NULL;
      }
    }
    return $activities;
  }

  /**
   * Add activity for Membership/Event/Contribution.
   *
   * @param object $activity
   *   particular component object.
   * @param string $activityType
   *   For Membership Signup or Renewal.
   * @param int $targetContactID
   * @param array $params
   *   Activity params to override.
   *
   * @return bool|NULL
   */
  public static function addActivity(
    $activity,
    $activityType,
    $targetContactID = NULL,
    $params = []
  ) {
    $date = date('YmdHis');
    if ($activity->__table == 'civicrm_contribution') {
      // create activity record only for Completed Contributions
      $contributionCompletedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
      if ($activity->contribution_status_id != $contributionCompletedStatusId) {
        //For onbehalf payments, create a scheduled activity.
        if (empty($params['on_behalf'])) {
          return NULL;
        }
        $params['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Scheduled');
      }

      // retrieve existing activity based on source_record_id and activity_type
      if (empty($params['id'])) {
        $params['id'] = CRM_Utils_Array::value('id', civicrm_api3('Activity', 'Get', [
          'source_record_id' => $activity->id,
          'activity_type_id' => $activityType,
        ]));
      }
      if (!empty($params['id'])) {
        // CRM-13237 : if activity record found, update it with campaign id of contribution
        $params['campaign_id'] = $activity->campaign_id;
      }

      $date = $activity->receive_date;
    }

    $activityParams = [
      'source_contact_id' => $activity->contact_id,
      'source_record_id' => $activity->id,
      'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', $activityType),
      'activity_date_time' => $date,
      'is_test' => $activity->is_test,
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed'),
      'skipRecentView' => TRUE,
      'campaign_id' => $activity->campaign_id,
    ];
    $activityParams = array_merge($activityParams, $params);

    if (empty($activityParams['subject'])) {
      $activityParams['subject'] = self::getActivitySubject($activity);
    }

    if (!empty($activity->activity_id)) {
      $activityParams['id'] = $activity->activity_id;
    }
    // create activity with target contacts
    $id = CRM_Core_Session::getLoggedInContactID();
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
      $activityParams['target_contact_id'] = array_unique($activityParams['target_contact_id'], SORT_NUMERIC);
    }
    // @todo - use api - remove lots of wrangling above. Remove deprecated fatal & let form layer
    // deal with any exceptions.
    if (is_a(self::create($activityParams), 'CRM_Core_Error')) {
      throw new CRM_Core_Exception("Failed creating Activity of type $activityType for entity id {$activity->id}");
    }
  }

  /**
   * Get activity subject on basis of component object.
   *
   * @param object $entityObj
   *   particular component object.
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public static function getActivitySubject($entityObj) {
    // @todo determine the subject on the appropriate entity rather than from the activity.
    switch ($entityObj->__table) {
      case 'civicrm_membership':
        $membershipType = CRM_Core_PseudoConstant::getLabel('CRM_Member_BAO_Membership', 'membership_type_id', $entityObj->membership_type_id);
        $subject = $membershipType ?: ts('Membership');

        if (!CRM_Utils_System::isNull($entityObj->source)) {
          $subject .= " - {$entityObj->source}";
        }

        if ($entityObj->owner_membership_id) {
          [$displayName] = CRM_Contact_BAO_Contact::getDisplayAndImage(CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $entityObj->owner_membership_id, 'contact_id'));
          $subject .= sprintf(' (by %s)', $displayName);
        }

        $subject .= ' - Status: ' . CRM_Core_PseudoConstant::getLabel('CRM_Member_BAO_Membership', 'status_id', $entityObj->status_id);
        return $subject;

      case 'civicrm_participant':
        $event = CRM_Event_BAO_Event::getEvents(1, $entityObj->event_id, TRUE, FALSE);
        $roles = CRM_Event_PseudoConstant::participantRole();
        $status = CRM_Event_PseudoConstant::participantStatus();
        $subject = $event[$entityObj->event_id];

        if (!empty($roles[$entityObj->role_id])) {
          $subject .= ' - ' . $roles[$entityObj->role_id];
        }
        if (!empty($status[$entityObj->status_id])) {
          $subject .= ' - ' . $status[$entityObj->status_id];
        }

        return $subject;

      case 'civicrm_contribution':
        $subject = CRM_Utils_Money::format($entityObj->total_amount, $entityObj->currency);
        if (!CRM_Utils_System::isNull($entityObj->source)) {
          $subject .= " - {$entityObj->source}";
        }

        // Amount and source could exceed max length of subject column.
        return CRM_Utils_String::ellipsify($subject, 255);
    }
  }

  /**
   * Get Parent activity for currently viewed activity.
   *
   * @param int $activityId
   *   Current activity id.
   *
   * @return int
   *   Id of parent activity otherwise false.
   * @throws \CRM_Core_Exception
   */
  public static function getParentActivity($activityId) {
    static $parentActivities = [];

    $activityId = CRM_Utils_Type::escape($activityId, 'Integer');

    if (!array_key_exists($activityId, $parentActivities)) {
      $parentActivities[$activityId] = [];

      $parentId = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity',
        $activityId,
        'parent_id'
      );

      $parentActivities[$activityId] = $parentId ?: FALSE;
    }

    return $parentActivities[$activityId];
  }

  /**
   * Get all prior activities of currently viewed activity.
   *
   * @param int $activityID
   *   Current activity id.
   * @param bool $onlyPriorRevisions
   *
   * @return array
   *   prior activities info.
   * @throws \CRM_Core_Exception
   */
  public static function getPriorAcitivities($activityID, $onlyPriorRevisions = FALSE) {
    static $priorActivities = [];

    $activityID = CRM_Utils_Type::escape($activityID, 'Integer');
    $index = $activityID . '_' . (int) $onlyPriorRevisions;

    if (!array_key_exists($index, $priorActivities)) {
      $priorActivities[$index] = [];

      $originalID = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity',
        $activityID,
        'original_id'
      );
      if (!$originalID) {
        $originalID = $activityID;
      }
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

        $params = [1 => [$originalID, 'Integer']];
        $dao = CRM_Core_DAO::executeQuery($query, $params);

        while ($dao->fetch()) {
          $priorActivities[$index][$dao->activityID]['id'] = $dao->activityID;
          $priorActivities[$index][$dao->activityID]['name'] = $dao->name;
          $priorActivities[$index][$dao->activityID]['date'] = $dao->date;
        }
      }
    }
    return $priorActivities[$index];
  }

  /**
   * Find the latest revision of a given activity.
   *
   * @param int $activityID
   *   Prior activity id.
   *
   * @return int
   *   current activity id.
   *
   * @throws \CRM_Core_Exception
   */
  public static function getLatestActivityId($activityID) {
    static $latestActivityIds = [];

    $activityID = CRM_Utils_Type::escape($activityID, 'Integer');

    if (!array_key_exists($activityID, $latestActivityIds)) {
      $latestActivityIds[$activityID] = [];

      $originalID = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity',
        $activityID,
        'original_id'
      );
      if ($originalID) {
        $activityID = $originalID;
      }
      $params = [1 => [$activityID, 'Integer']];
      $query = 'SELECT id from civicrm_activity where original_id = %1 and is_current_revision = 1';

      $latestActivityIds[$activityID] = CRM_Core_DAO::singleValueQuery($query, $params);
    }

    return $latestActivityIds[$activityID];
  }

  /**
   * Create a follow up a given activity.
   *
   * @param int $activityId
   *   activity id of parent activity.
   * @param array $params
   *
   * @return CRM_Activity_BAO_Activity|null|object
   *
   * @throws \CRM_Core_Exception
   */
  public static function createFollowupActivity($activityId, $params) {
    if (!$activityId) {
      return NULL;
    }

    $followupParams = [];
    $followupParams['parent_id'] = $activityId;
    $followupParams['source_contact_id'] = CRM_Core_Session::getLoggedInContactID();
    $followupParams['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Scheduled');

    $followupParams['activity_type_id'] = $params['followup_activity_type_id'];
    // Get Subject of Follow-up Activiity, CRM-4491
    $followupParams['subject'] = $params['followup_activity_subject'] ?? NULL;
    if (!is_array($params['followup_assignee_contact_id']) && !empty($params['followup_assignee_contact_id'])) {
      $params['followup_assignee_contact_id'] = explode(",", $params['followup_assignee_contact_id']);
    }
    $followupParams['assignee_contact_id'] = $params['followup_assignee_contact_id'] ?? NULL;

    // Create target contact for followup.
    if (!empty($params['target_contact_id'])) {
      $followupParams['target_contact_id'] = $params['target_contact_id'];
    }

    $followupParams['activity_date_time'] = $params['followup_date'];
    $followupActivity = self::create($followupParams);

    return $followupActivity;
  }

  /**
   * Get Activity specific File according activity type Id.
   *
   * @param int $activityTypeId
   *   Activity id.
   * @param string $crmDir
   *
   * @return string|bool
   *   if file exists returns $activityTypeFile activity filename otherwise false.
   */
  public static function getFileForActivityTypeId($activityTypeId, $crmDir = 'Activity') {
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
   * Restore the activity.
   *
   * @param array $params
   *
   * @return CRM_Activity_DAO_Activity
   */
  public static function restoreActivity(&$params) {
    $activity = new CRM_Activity_DAO_Activity();
    $activity->copyValues($params);

    $activity->is_deleted = 0;
    $result = $activity->save();

    return $result;
  }

  /**
   * Return list of activity statuses of a given type.
   *
   * Note: activity status options use the "grouping" field to distinguish status types.
   * Types are defined in class constants INCOMPLETE, COMPLETED, CANCELLED
   *
   * @param int $type
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function getStatusesByType($type) {
    if (!isset(Civi::$statics[__CLASS__][__FUNCTION__])) {
      $statuses = civicrm_api3('OptionValue', 'get', [
        'option_group_id' => 'activity_status',
        'return' => ['value', 'name', 'filter'],
        'options' => ['limit' => 0],
      ]);
      Civi::$statics[__CLASS__][__FUNCTION__] = $statuses['values'];
    }
    $ret = [];
    foreach (Civi::$statics[__CLASS__][__FUNCTION__] as $status) {
      if ($status['filter'] == $type) {
        $ret[$status['value']] = $status['name'];
      }
    }
    return $ret;
  }

  /**
   * Check if activity is overdue.
   *
   * @param array $activity
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function isOverdue($activity) {
    return array_key_exists($activity['status_id'], self::getStatusesByType(self::INCOMPLETE)) && CRM_Utils_Date::overdue($activity['activity_date_time']);
  }

  /**
   * Get the exportable fields for Activities.
   *
   * @param string $name
   *   If it is called by case $name = Case else $name = Activity.
   *
   * @return array
   *   array of exportable Fields
   */
  public static function exportableFields($name = 'Activity') {
    self::$_exportableFields[$name] = [];

    // TODO: ideally we should retrieve all fields from xml, in this case since activity processing is done
    $exportableFields = CRM_Activity_DAO_Activity::export();
    $exportableFields['source_contact_id'] = [
      'title' => ts('Source Contact ID'),
      'type' => CRM_Utils_Type::T_INT,
    ];
    $exportableFields['source_contact'] = [
      'title' => ts('Source Contact'),
      'type' => CRM_Utils_Type::T_STRING,
    ];

    // @todo - remove these - they are added by CRM_Core_DAO::appendPseudoConstantsToFields
    // below. That search label stuff is referenced in search builder but is likely just
    // a hack that duplicates, maybe differently, other functionality.
    $activityFields = [
      'activity_type' => [
        'title' => ts('Activity Type'),
        'name' => 'activity_type',
        'type' => CRM_Utils_Type::T_STRING,
        'searchByLabel' => TRUE,
      ],
      'activity_status' => [
        'title' => ts('Activity Status'),
        'name' => 'activity_status',
        'type' => CRM_Utils_Type::T_STRING,
        'searchByLabel' => TRUE,
      ],
      'activity_priority' => [
        'title' => ts('Activity Priority'),
        'name' => 'activity_priority',
        'type' => CRM_Utils_Type::T_STRING,
        'searchByLabel' => TRUE,
      ],
    ];
    $fields = array_merge($activityFields, $exportableFields);
    $fields['activity_priority_id'] = $fields['priority_id'];

    if ($name === 'Case') {
      // Now add "case_activity" fields
      // Set title to activity fields.
      $caseActivityFields = [
        'case_source_contact_id' => [
          'title' => ts('Activity Reporter'),
          'type' => CRM_Utils_Type::T_STRING,
        ],
        'case_activity_date_time' => [
          'title' => ts('Activity Date'),
          'type' => CRM_Utils_Type::T_DATE,
        ],
        'case_activity_type' => [
          'title' => ts('Activity Type'),
          'type' => CRM_Utils_Type::T_STRING,
        ],
        'case_activity_medium_id' => [
          'title' => ts('Activity Medium'),
          'type' => CRM_Utils_Type::T_INT,
          'pseudoconstant' => [
            'optionGroupName' => 'encounter_medium',
          ],
        ],
        'case_activity_is_auto' => [
          'title' => ts('Activity Auto-generated?'),
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ],
      ];
      $caseStandardFields = ['activity_subject', 'activity_status', 'activity_duration', 'activity_details'];
      foreach ($caseStandardFields as $key) {
        $caseActivityFields['case_' . $key] = $fields[$key];
      }
      $fields = $caseActivityFields;
    }
    // Add custom data
    $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Activity'));
    CRM_Core_DAO::appendPseudoConstantsToFields($fields);
    self::$_exportableFields[$name] = $fields;
    return self::$_exportableFields[$name];
  }

  /**
   * Get the allowed profile fields for Activities.
   *
   * @return array
   *   array of activity profile Fields
   */
  public static function getProfileFields() {
    $exportableFields = self::exportableFields('Activity');
    $skipFields = [
      'activity_id',
      'activity_type',
      'source_contact_id',
      'source_contact',
      'activity_campaign',
      'activity_is_test',
      'is_current_revision',
      'activity_is_deleted',
    ];
    if (!CRM_Core_Component::isEnabled('CiviCampaign')) {
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
   * This function deletes the activity record related to contact record.
   *
   * This is conditional on there being no target and assignee record
   * with other contacts.
   *
   * @param int $contactId
   *   ContactId.
   *
   * @return true/null
   */
  public static function cleanupActivity($contactId) {
    $result = NULL;
    if (!$contactId) {
      return $result;
    }
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
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
      if (!$activityContactOther->find(TRUE)) {
        $activityParams = ['id' => $activityContact->activity_id];
        $result = self::deleteActivity($activityParams);
      }

    }

    $transaction->commit();

    return $result;
  }

  /**
   * Does user has sufficient permission for view/edit activity record.
   *
   * @param int $activityId
   *   Activity record id.
   * @param int $action
   *   Edit/view.
   *
   * @return bool
   */
  public static function checkPermission($activityId, $action) {

    if (!$activityId ||
      !in_array($action, [CRM_Core_Action::UPDATE, CRM_Core_Action::VIEW])
    ) {
      return FALSE;
    }

    $activity = new CRM_Activity_DAO_Activity();
    $activity->id = $activityId;
    if (!$activity->find(TRUE)) {
      return FALSE;
    }

    if (!self::hasPermissionForActivityType($activity->activity_type_id)) {
      // this check is redundant for api access / anything that calls the selectWhereClause
      // to determine ACLs.
      return FALSE;
    }
    // Return early when it is case activity.
    // Check for CiviCase related permission.
    if (CRM_Case_BAO_Case::isCaseActivity($activityId)) {
      return self::isContactPermittedAccessToCaseActivity($activityId, $action, $activity->activity_type_id);
    }

    // Check for this permission related to contact.
    $permission = CRM_Core_Permission::VIEW;
    if ($action == CRM_Core_Action::UPDATE) {
      $permission = CRM_Core_Permission::EDIT;
    }

    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    // Check for source contact.
    $sourceContactId = self::getActivityContact($activity->id, $sourceID);
    // Account for possibility of activity not having a source contact (as it may have been deleted).
    $allow = $sourceContactId ? CRM_Contact_BAO_Contact_Permission::allow($sourceContactId, $permission) : TRUE;
    if (!$allow) {
      return FALSE;
    }

    // Check for target and assignee contacts.
    // First check for super permission.
    $supPermission = 'view all contacts';
    if ($action == CRM_Core_Action::UPDATE) {
      $supPermission = 'edit all contacts';
    }
    $allow = CRM_Core_Permission::check($supPermission);

    // User might have sufficient permission, through acls.
    if (!$allow) {
      $allow = TRUE;
      // Get the target contacts.
      $targetContacts = CRM_Activity_BAO_ActivityContact::retrieveContactIdsByActivityId($activity->id, $targetID);
      foreach ($targetContacts as $cnt => $contactId) {
        if (!CRM_Contact_BAO_Contact_Permission::allow($contactId, $permission)) {
          $allow = FALSE;
          break;
        }
      }

      // Get the assignee contacts.
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

    return $allow;
  }

  /**
   * Check if the logged in user has permission for the given case activity.
   *
   * @param int $activityId
   * @param int $action
   * @param int $activityTypeID
   *
   * @return bool
   */
  protected static function isContactPermittedAccessToCaseActivity($activityId, $action, $activityTypeID) {
    $oper = 'view';
    if ($action == CRM_Core_Action::UPDATE) {
      $oper = 'edit';
    }
    $allow = CRM_Case_BAO_Case::checkPermission($activityId,
      $oper,
      $activityTypeID
    );

    return $allow;
  }

  /**
   * Check if the logged in user has permission to access the given activity type.
   *
   * @param int $activityTypeID
   *
   * @return bool
   */
  protected static function hasPermissionForActivityType($activityTypeID) {
    $permittedActivityTypes = self::getPermittedActivityTypes();
    return isset($permittedActivityTypes[$activityTypeID]);
  }

  /**
   * Get the activity types the user is permitted to access.
   *
   * The types are filtered by the components they have access to. ie. a user
   * with access CiviContribute but not CiviMember will see contribution related
   * activities and activities with no component (e.g meetings) but not member related ones.
   *
   * @return array
   */
  protected static function getPermittedActivityTypes() {
    $userID = (int) CRM_Core_Session::getLoggedInContactID();
    if (!isset(Civi::$statics[__CLASS__]['permitted_activity_types'][$userID])) {
      $permittedActivityTypes = [];
      $components = self::activityComponents(FALSE);
      $componentClause = empty($components) ? '' : (' OR component_id IN (' . implode(', ', array_keys($components)) . ')');

      $types = CRM_Core_DAO::executeQuery(
        "
    SELECT  option_value.value activity_type_id
      FROM  civicrm_option_value option_value
INNER JOIN  civicrm_option_group grp ON (grp.id = option_group_id AND grp.name = 'activity_type')
     WHERE  component_id IS NULL $componentClause")->fetchAll();
      foreach ($types as $type) {
        $permittedActivityTypes[$type['activity_type_id']] = (int) $type['activity_type_id'];
      }
      asort($permittedActivityTypes);
      Civi::$statics[__CLASS__]['permitted_activity_types'][$userID] = $permittedActivityTypes;
    }
    return Civi::$statics[__CLASS__]['permitted_activity_types'][$userID];
  }

  /**
   * @param array $params
   * @return array
   */
  protected static function getActivityParamsForDashboardFunctions($params) {
    $activityParams = [
      'is_deleted' => 0,
      'is_current_revision' => 1,
      'is_test' => 0,
      'contact_id' => $params['contact_id'] ?? NULL,
      'activity_date_time' => $params['activity_date_time'] ?? NULL,
      'check_permissions' => 1,
      'options' => [
        'offset' => $params['offset'] ?? 0,
      ],
    ];

    if (!empty($params['activity_status_id'])) {
      $activityParams['activity_status_id'] = ['IN' => explode(',', $params['activity_status_id'])];
    }

    $activityParams['activity_type_id'] = self::filterActivityTypes($params);
    $enabledComponents = self::activityComponents();
    // @todo - this appears to be duplicating the activity api.
    if (!in_array('CiviCase', $enabledComponents)) {
      $activityParams['case_id'] = ['IS NULL' => 1];
    }
    return $activityParams;
  }

  /**
   * Checks if user has permissions to edit inbound e-mails, either basic info
   * or both basic information and content.
   *
   * @return bool
   */
  public static function checkEditInboundEmailsPermissions() {
    return CRM_Core_Permission::check([
      ['edit inbound email basic information', /* OR */ 'edit inbound email basic information and content'],
    ]);
  }

  /**
   * Get the list of view only activities
   *
   * @return array
   */
  public static function getViewOnlyActivityTypeIDs() {
    $viewOnlyActivities = [
      'Email' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Email'),
    ];
    if (!self::checkEditInboundEmailsPermissions()) {
      $viewOnlyActivities['Inbound Email'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Inbound Email');
    }
    return $viewOnlyActivities;
  }

  /**
   * Wrapper for ajax activity selector.
   *
   * @param array $params
   *   Associated array for params record id.
   *
   * @return array
   *   Associated array of contact activities
   */
  public static function getContactActivitySelector(&$params) {
    // Format the params.
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];
    $params['sort'] = $params['sortBy'] ?? NULL;
    $params['caseId'] = NULL;
    $context = $params['context'] ?? '';
    $showContactOverlay = !str_starts_with($context, "dashlet");
    $activityTypeInfo = civicrm_api3('OptionValue', 'get', [
      'option_group_id' => "activity_type",
      'options' => ['limit' => 0],
    ]);
    $activityIcons = [];
    foreach ($activityTypeInfo['values'] as $type) {
      if (!empty($type['icon'])) {
        $activityIcons[$type['value']] = $type['icon'];
      }
    }
    CRM_Utils_Date::convertFormDateToApiFormat($params, 'activity_date_time');

    // Get contact activities.
    $activities = CRM_Activity_BAO_Activity::getActivities($params);

    // Add total.
    $params['total'] = CRM_Activity_BAO_Activity::getActivitiesCount($params);

    // Format params and add links.
    $contactActivities = [];

    // View-only activity types
    $viewOnlyCaseActivityTypeIDs = array_flip(CRM_Activity_BAO_Activity::getViewOnlyActivityTypeIDs());

    if (!empty($activities)) {
      $activityStatus = CRM_Core_PseudoConstant::activityStatus();

      // Check logged in user for permission.
      $page = new CRM_Core_Page();
      CRM_Contact_Page_View::checkUserPermission($page, $params['contact_id']);
      $permissions = [$page->_permission];
      if (CRM_Core_Permission::check('delete activities')) {
        $permissions[] = CRM_Core_Permission::DELETE;
      }

      $mask = CRM_Core_Action::mask($permissions);

      foreach ($activities as $activityId => $values) {
        $activity = ['source_contact_name' => '', 'target_contact_name' => ''];
        $activity['DT_RowId'] = $activityId;
        // Add class to this row if overdue.
        $activity['DT_RowClass'] = "crm-entity status-id-{$values['status_id']}";
        if (self::isOverdue($values)) {
          $activity['DT_RowClass'] .= ' status-overdue';
        }
        else {
          $activity['DT_RowClass'] .= ' status-ontime';
        }

        $activity['DT_RowAttr'] = [];
        $activity['DT_RowAttr']['data-entity'] = 'activity';
        $activity['DT_RowAttr']['data-id'] = $activityId;

        $activity['activity_type'] = (!empty($activityIcons[$values['activity_type_id']]) ? '<span class="crm-i ' . $activityIcons[$values['activity_type_id']] . '" role="img" aria-hidden="true"></span> ' : '') . htmlentities($values['activity_type']);
        $activity['subject'] = $values['subject'];

        if ($params['contact_id'] == $values['source_contact_id']) {
          $activity['source_contact_name'] = htmlentities($values['source_contact_name']);
        }
        elseif ($values['source_contact_id']) {
          $srcTypeImage = "";
          if ($showContactOverlay) {
            $srcTypeImage = CRM_Contact_BAO_Contact_Utils::getImage(
              CRM_Contact_BAO_Contact::getContactType($values['source_contact_id']),
              FALSE,
              $values['source_contact_id']);
          }
          $activity['source_contact_name'] = $srcTypeImage . CRM_Utils_System::href(htmlentities($values['source_contact_name']),
              'civicrm/contact/view', "reset=1&cid={$values['source_contact_id']}");
        }
        else {
          $activity['source_contact_name'] = '<em>n/a</em>';
        }

        if (isset($values['mailingId']) && !empty($values['mailingId'])) {
          $activity['target_contact'] = CRM_Utils_System::href($values['recipients'],
            'civicrm/mailing/report/event',
            "mid={$values['source_record_id']}&reset=1&event=queue&cid={$params['contact_id']}&context=activitySelector");
        }
        elseif (!empty($values['recipients'])) {
          $activity['target_contact_name'] = $values['recipients'];
        }
        elseif (isset($values['target_contact_count']) && $values['target_contact_count']) {
          $activity['target_contact_name'] = '';
          $firstTargetName = reset($values['target_contact_name']);
          $firstTargetContactID = key($values['target_contact_name']);

          // The first target may not be accessable to the logged in user dev/core#1052
          if ($firstTargetName) {
            $targetLink = CRM_Utils_System::href(htmlentities($firstTargetName), 'civicrm/contact/view', "reset=1&cid={$firstTargetContactID}");
            if ($showContactOverlay) {
              $targetTypeImage = CRM_Contact_BAO_Contact_Utils::getImage(
                CRM_Contact_BAO_Contact::getContactType($firstTargetContactID),
                FALSE,
                $firstTargetContactID);
              $activity['target_contact_name'] .= "<div>$targetTypeImage  $targetLink";
            }
            else {
              $activity['target_contact_name'] .= $targetLink;
            }

            if ($extraCount = $values['target_contact_count'] - 1) {
              $activity['target_contact_name'] .= ";<br />" . "(" . ts('%1 more', [1 => $extraCount]) . ")";
            }
            if ($showContactOverlay) {
              $activity['target_contact_name'] .= "</div> ";
            }
          }
        }
        elseif (empty($values['target_contact_name'])) {
          $activity['target_contact_name'] = '<em>n/a</em>';
        }

        $activity['assignee_contact_name'] = '';
        if (empty($values['assignee_contact_name'])) {
          $activity['assignee_contact_name'] = '<em>n/a</em>';
        }
        elseif (!empty($values['assignee_contact_name'])) {
          $count = 0;
          $activity['assignee_contact_name'] = '';
          foreach ($values['assignee_contact_name'] as $acID => $acName) {
            if ($acID && $count < 5) {
              $assigneeTypeImage = "";
              $assigneeLink = CRM_Utils_System::href(htmlentities($acName), 'civicrm/contact/view', "reset=1&cid={$acID}");
              if ($showContactOverlay) {
                $assigneeTypeImage = CRM_Contact_BAO_Contact_Utils::getImage(
                  CRM_Contact_BAO_Contact::getContactType($acID),
                  FALSE,
                  $acID);
                $activity['assignee_contact_name'] .= "<div>$assigneeTypeImage $assigneeLink";
              }
              else {
                $activity['assignee_contact_name'] .= $assigneeLink;
              }

              $count++;
              if ($count) {
                $activity['assignee_contact_name'] .= ";&nbsp;";
              }
              if ($showContactOverlay) {
                $activity['assignee_contact_name'] .= "</div> ";
              }

              if ($count == 4) {
                $activity['assignee_contact_name'] .= "(" . ts('more') . ")";
                break;
              }
            }
          }
        }

        $activity['activity_date_time'] = CRM_Utils_Date::customFormat($values['activity_date_time']);
        $activity['status_id'] = $activityStatus[$values['status_id']];

        // Get action links.
        //
        // Note that $viewOnlyCaseActivityTypeIDs, while not a super-heavy
        // calculation, makes some sense to calculate outside the loop above.
        // We could recalculate it each time inside getActionLinks if we wanted
        // to avoid passing it along. Or use caching inside getAcionLinks.
        //   - Ditto $mask.
        $activity['links'] = self::getActionLinks(
          $values,
          $activityId,
          $params['contact_id'],
          isset($viewOnlyCaseActivityTypeIDs[$values['activity_type_id']]),
          $context,
          $mask,
          // I think this parameter should be ignored completely for the purpose
          // of generating a link but am leaving it as-is for now.
          (bool) Civi::settings()->get('civicaseShowCaseActivities')
        );

        if ($values['is_recurring_activity']) {
          $activity['is_recurring_activity'] = CRM_Core_BAO_RecurringEntity::getPositionAndCount($values['activity_id'], 'civicrm_activity');
        }

        array_push($contactActivities, $activity);
      }
    }

    $activitiesDT = [];
    $activitiesDT['data'] = $contactActivities;
    $activitiesDT['recordsTotal'] = $params['total'];
    $activitiesDT['recordsFiltered'] = $params['total'];

    return $activitiesDT;
  }

  /**
   * Get the right links depending on the activity type and other factors.
   *
   * @param array $values
   * @param int $activityId
   * @param int|null $contactId
   * @param bool $isViewOnly Is this a special type that shouldn't be edited
   * @param string|null $context
   * @param int|null $mask
   * @param bool $dontBreakCaseActivities
   *   Originally this function was
   *   part of another function that was only used on the contact's activity
   *   tab and this parameter would only be false when you're not displaying
   *   case activities anyway and so was effectively never used. And I'm not
   *   sure why for the purposes of links you would ever want a case activity
   *   to link to the regular form, so I think this can be removed, but am
   *   leaving it as-is for now.
   *
   * @return string HTML string
   */
  public static function getActionLinks(
    array $values,
    int $activityId,
    ?int $contactId,
    bool $isViewOnly,
    ?string $context,
    ?int $mask,
    bool $dontBreakCaseActivities = TRUE): string {

    $linksToReturn = '';
    // If this is a case activity, then we hand off to Case's actionLinks instead.
    if (!empty($values['case_id']) && $dontBreakCaseActivities) {
      // This activity belongs to a case.
      $caseId = current($values['case_id']);

      // Get the view and edit (update) links:
      $caseActionLinks =
        $actionLinks = array_intersect_key(
          CRM_Case_Selector_Search::actionLinks(),
          array_fill_keys([CRM_Core_Action::VIEW, CRM_Core_Action::UPDATE], NULL));

      // Create a Manage Case link (using ADVANCED as can't use two VIEW ones)
      $actionLinks[CRM_Core_Action::ADVANCED] = [
        "name"  => 'Manage Case',
        "url"   => 'civicrm/contact/view/case',
        'qs'    => 'reset=1&id=%%caseid%%&cid=%%cid%%&action=view&context=&selectedChild=case',
        "title" => ts('Manage Case %1', [1 => $caseId]),
        'class' => 'no-popup',
      ];

      $caseLinkValues = [
        'aid'    => $activityId,
        'caseid' => $caseId,
        'cid'    => current(CRM_Case_BAO_Case::getCaseClients($caseId) ?? []),
        // Unlike other 'context' params, this 'ctx' param is appended raw to the URL.
        'cxt'    => '',
      ];

      $caseActivityPermissions = CRM_Core_Action::VIEW | CRM_Core_Action::ADVANCED;
      // Allow Edit link if:
      // 1. Activity type is NOT view-only type. CRM-5871
      // 2. User has edit permission.
      if (!$isViewOnly
        && CRM_Case_BAO_Case::checkPermission($activityId, 'edit', $values['activity_type_id'], CRM_Core_Session::getLoggedInContactID())) {
        // We're allowed to edit.
        $caseActivityPermissions |= CRM_Core_Action::UPDATE;
      }

      $linksToReturn = CRM_Core_Action::formLink($actionLinks,
        $caseActivityPermissions,
        $caseLinkValues,
        ts('more'),
        FALSE,
        'activity.tab.row',
        'Activity',
        $values['activity_id']
      );
    }
    else {
      // Non-case activity
      $actionLinks = CRM_Activity_Selector_Activity::actionLinks(
        $values['activity_type_id'] ?? NULL,
        $values['source_record_id'] ?? NULL,
        !empty($values['mailingId']),
        $values['activity_id'] ?? NULL
      );
      $actionMask = array_sum(array_keys($actionLinks)) & $mask;

      $linksToReturn = CRM_Core_Action::formLink($actionLinks,
        $actionMask,
        [
          'id' => $values['activity_id'],
          'cid' => $contactId,
          'cxt' => $context,
          'caseid' => NULL,
        ],
        ts('more'),
        FALSE,
        'activity.tab.row',
        'Activity',
        $values['activity_id']
      );
    }
    return $linksToReturn;
  }

  /**
   * Copy custom fields and attachments from an existing activity to another.
   *
   * @see CRM_Case_Page_AJAX::_convertToCaseActivity()
   *
   * @param array $params
   */
  public static function copyExtendedActivityData($params) {
    // attach custom data to the new activity
    $customParams = $htmlType = [];
    $customValues = CRM_Core_BAO_CustomValueTable::getEntityValues($params['activityID'], 'Activity');

    if (!empty($customValues)) {
      $fieldIds = implode(', ', array_keys($customValues));
      $sql = "SELECT id FROM civicrm_custom_field WHERE html_type = 'File' AND id IN ( {$fieldIds} )";
      $result = CRM_Core_DAO::executeQuery($sql);

      while ($result->fetch()) {
        $htmlType[] = $result->id;
      }

      foreach ($customValues as $key => $value) {
        if ($value !== NULL) {
          // CRM-10542
          if (in_array($key, $htmlType)) {
            $fileValues = CRM_Core_BAO_File::path($value, $params['activityID']);
            $customParams["custom_{$key}_-1"] = [
              'name' => $fileValues[0],
              'type' => $fileValues[1],
            ];
          }
          else {
            $customParams["custom_{$key}_-1"] = $value;
          }
        }
      }
      CRM_Core_BAO_CustomValueTable::postProcess($customParams, 'civicrm_activity',
        $params['mainActivityId'], 'Activity'
      );
    }

    // copy activity attachments ( if any )
    CRM_Core_BAO_File::copyEntityFile('civicrm_activity', $params['activityID'], 'civicrm_activity', $params['mainActivityId']);
  }

  /**
   * Get activity contact.
   *
   * @param int $activityId
   * @param int $recordTypeID
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
   * Get source contact id.
   *
   * @param int $activityId
   *
   * @return null
   */
  public static function getSourceContactID($activityId) {
    static $sourceID = NULL;
    if (!$sourceID) {
      $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
      $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    }

    return self::getActivityContact($activityId, $sourceID);
  }

  /**
   * Set api filter.
   *
   * @todo Document what this is for.
   *
   * @param array $params
   */
  public function setApiFilter(&$params) {
    if (!empty($params['target_contact_id'])) {
      $this->selectAdd();
      $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
      $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
      $obj = new CRM_Activity_BAO_ActivityContact();
      $params['return.target_contact_id'] = 1;
      $this->joinAdd($obj, 'LEFT');
      $this->selectAdd('civicrm_activity.*');
      $this->whereAdd(" civicrm_activity_contact.contact_id = {$params['target_contact_id']} AND civicrm_activity_contact.record_type_id = {$targetID}");
    }
  }

  /**
   * Send activity as attachment.
   *
   * @param object $activity
   * @param array $mailToContacts
   * @param array $params
   *
   * @return bool
   */
  public static function sendToAssignee($activity, $mailToContacts, $params = []) {
    if (!CRM_Utils_Array::crmIsEmptyArray($mailToContacts)) {
      $clientID = $params['client_id'] ?? NULL;
      $caseID = $params['case_id'] ?? NULL;

      $ics = new CRM_Activity_BAO_ICalendar($activity);
      $attachments = CRM_Core_BAO_File::getEntityFile('civicrm_activity', $activity->id);
      $ics->addAttachment($attachments, $mailToContacts);

      $result = CRM_Case_BAO_Case::sendActivityCopy($clientID, $activity->id, $mailToContacts, $attachments, $caseID);
      $ics->cleanup();
      return $result;
    }
    return FALSE;
  }

  /**
   * @return array
   */
  public static function getEntityRefFilters() {
    return [
      ['key' => 'activity_type_id', 'value' => ts('Activity Type')],
      ['key' => 'status_id', 'value' => ts('Activity Status')],
    ];
  }

  /**
   * Get icon for a particular activity (based on type).
   *
   * Example: `CRM_Activity_BAO_Activity::getIcon('Activity', 123)`
   *
   * @param string $entityName
   *   Always "Activity".
   * @param int|null $entityId
   *   Id of the activity.
   * @throws CRM_Core_Exception
   */
  public static function getEntityIcon(string $entityName, ?int $entityId = NULL): ?string {
    $default = parent::getEntityIcon($entityName);
    if (!$entityId) {
      return $default;
    }
    $field = Civi\Api4\Activity::getFields(FALSE)
      ->addWhere('name', '=', 'activity_type_id')
      ->setLoadOptions(['id', 'label', 'icon'])
      ->execute()->single();
    $activityTypes = array_column($field['options'], NULL, 'id');
    $activityType = CRM_Core_DAO::getFieldValue(parent::class, $entityId, 'activity_type_id');
    return $activityTypes[$activityType]['icon'] ?? $default;
  }

}
