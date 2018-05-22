<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Case_XMLProcessor_Process extends CRM_Case_XMLProcessor {
  protected $defaultAssigneeOptionsValues = [];

  /**
   * Run.
   *
   * @param string $caseType
   * @param array $params
   *
   * @return bool
   * @throws Exception
   */
  public function run($caseType, &$params) {
    $xml = $this->retrieve($caseType);

    if ($xml === FALSE) {
      $docLink = CRM_Utils_System::docURL2("user/case-management/set-up");
      CRM_Core_Error::fatal(ts("Configuration file could not be retrieved for case type = '%1' %2.",
        array(1 => $caseType, 2 => $docLink)
      ));
      return FALSE;
    }

    $xmlProcessorProcess = new CRM_Case_XMLProcessor_Process();
    $this->_isMultiClient = $xmlProcessorProcess->getAllowMultipleCaseClients();

    $this->process($xml, $params);
  }

  /**
   * @param $caseType
   * @param $fieldSet
   * @param bool $isLabel
   * @param bool $maskAction
   *
   * @return array|bool|mixed
   * @throws Exception
   */
  public function get($caseType, $fieldSet, $isLabel = FALSE, $maskAction = FALSE) {
    $xml = $this->retrieve($caseType);
    if ($xml === FALSE) {
      $docLink = CRM_Utils_System::docURL2("user/case-management/set-up");
      CRM_Core_Error::fatal(ts("Unable to load configuration file for the referenced case type: '%1' %2.",
        array(1 => $caseType, 2 => $docLink)
      ));
      return FALSE;
    }

    switch ($fieldSet) {
      case 'CaseRoles':
        return $this->caseRoles($xml->CaseRoles);

      case 'ActivitySets':
        return $this->activitySets($xml->ActivitySets);

      case 'ActivityTypes':
        return $this->activityTypes($xml->ActivityTypes, FALSE, $isLabel, $maskAction);
    }
  }

  /**
   * @param $xml
   * @param array $params
   *
   * @throws Exception
   */
  public function process($xml, &$params) {
    $standardTimeline = CRM_Utils_Array::value('standardTimeline', $params);
    $activitySetName = CRM_Utils_Array::value('activitySetName', $params);
    $activityTypeName = CRM_Utils_Array::value('activityTypeName', $params);

    if ('Open Case' == CRM_Utils_Array::value('activityTypeName', $params)) {
      // create relationships for the ones that are required
      foreach ($xml->CaseRoles as $caseRoleXML) {
        foreach ($caseRoleXML->RelationshipType as $relationshipTypeXML) {
          if ((int ) $relationshipTypeXML->creator == 1) {
            if (!$this->createRelationships((string ) $relationshipTypeXML->name,
              $params
            )
            ) {
              CRM_Core_Error::fatal();
              return FALSE;
            }
          }
        }
      }
    }

    if ('Change Case Start Date' == CRM_Utils_Array::value('activityTypeName', $params)) {
      // delete all existing activities which are non-empty
      $this->deleteEmptyActivity($params);
    }

    foreach ($xml->ActivitySets as $activitySetsXML) {
      foreach ($activitySetsXML->ActivitySet as $activitySetXML) {
        if ($standardTimeline) {
          if ((boolean ) $activitySetXML->timeline) {
            return $this->processStandardTimeline($activitySetXML,
              $params
            );
          }
        }
        elseif ($activitySetName) {
          $name = (string ) $activitySetXML->name;
          if ($name == $activitySetName) {
            return $this->processActivitySet($activitySetXML,
              $params
            );
          }
        }
      }
    }
  }

  /**
   * @param $activitySetXML
   * @param array $params
   */
  public function processStandardTimeline($activitySetXML, &$params) {
    if ('Change Case Type' == CRM_Utils_Array::value('activityTypeName', $params)
      && CRM_Utils_Array::value('resetTimeline', $params, TRUE)
    ) {
      // delete all existing activities which are non-empty
      $this->deleteEmptyActivity($params);
    }

    foreach ($activitySetXML->ActivityTypes as $activityTypesXML) {
      foreach ($activityTypesXML as $activityTypeXML) {
        $this->createActivity($activityTypeXML, $params);
      }
    }
  }

  /**
   * @param $activitySetXML
   * @param array $params
   */
  public function processActivitySet($activitySetXML, &$params) {
    foreach ($activitySetXML->ActivityTypes as $activityTypesXML) {
      foreach ($activityTypesXML as $activityTypeXML) {
        $this->createActivity($activityTypeXML, $params);
      }
    }
  }

  /**
   * @param $caseRolesXML
   * @param bool $isCaseManager
   *
   * @return array|mixed
   */
  public function &caseRoles($caseRolesXML, $isCaseManager = FALSE) {
    $relationshipTypes = &$this->allRelationshipTypes();

    $result = array();
    foreach ($caseRolesXML as $caseRoleXML) {
      foreach ($caseRoleXML->RelationshipType as $relationshipTypeXML) {
        $relationshipTypeName = (string ) $relationshipTypeXML->name;
        $relationshipTypeID = array_search($relationshipTypeName,
          $relationshipTypes
        );
        if ($relationshipTypeID === FALSE) {
          continue;
        }

        if (!$isCaseManager) {
          $result[$relationshipTypeID] = $relationshipTypeName;
        }
        elseif ($relationshipTypeXML->manager) {
          return $relationshipTypeID;
        }
      }
    }
    return $result;
  }

  /**
   * @param string $relationshipTypeName
   * @param array $params
   *
   * @return bool
   * @throws Exception
   */
  public function createRelationships($relationshipTypeName, &$params) {
    $relationshipTypes = &$this->allRelationshipTypes();
    // get the relationship id
    $relationshipTypeID = array_search($relationshipTypeName, $relationshipTypes);

    if ($relationshipTypeID === FALSE) {
      $docLink = CRM_Utils_System::docURL2("user/case-management/set-up");
      CRM_Core_Error::fatal(ts('Relationship type %1, found in case configuration file, is not present in the database %2',
        array(1 => $relationshipTypeName, 2 => $docLink)
      ));
      return FALSE;
    }

    $client = $params['clientID'];
    if (!is_array($client)) {
      $client = array($client);
    }

    foreach ($client as $key => $clientId) {
      $relationshipParams = array(
        'relationship_type_id' => $relationshipTypeID,
        'contact_id_a' => $clientId,
        'contact_id_b' => $params['creatorID'],
        'is_active' => 1,
        'case_id' => $params['caseID'],
        'start_date' => date("Ymd"),
        'end_date' => CRM_Utils_Array::value('relationship_end_date', $params),
      );

      if (!$this->createRelationship($relationshipParams)) {
        CRM_Core_Error::fatal();
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * @param array $params
   *
   * @return bool
   */
  public function createRelationship(&$params) {
    $dao = new CRM_Contact_DAO_Relationship();
    $dao->copyValues($params);
    // only create a relationship if it does not exist
    if (!$dao->find(TRUE)) {
      $dao->save();
    }
    return TRUE;
  }

  /**
   * @param $activityTypesXML
   * @param bool $maxInst
   * @param bool $isLabel
   * @param bool $maskAction
   *
   * @return array
   */
  public function activityTypes($activityTypesXML, $maxInst = FALSE, $isLabel = FALSE, $maskAction = FALSE) {
    $activityTypes = &$this->allActivityTypes(TRUE, TRUE);
    $result = array();
    foreach ($activityTypesXML as $activityTypeXML) {
      foreach ($activityTypeXML as $recordXML) {
        $activityTypeName = (string ) $recordXML->name;
        $maxInstances = (string ) $recordXML->max_instances;
        $activityTypeInfo = CRM_Utils_Array::value($activityTypeName, $activityTypes);

        if ($activityTypeInfo['id']) {
          if ($maskAction) {
            if ($maskAction == 'edit' && '0' === (string ) $recordXML->editable) {
              $result[$maskAction][] = $activityTypeInfo['id'];
            }
          }
          else {
            if (!$maxInst) {
              //if we want,labels of activities should be returned.
              if ($isLabel) {
                $result[$activityTypeInfo['id']] = $activityTypeInfo['label'];
              }
              else {
                $result[$activityTypeInfo['id']] = $activityTypeName;
              }
            }
            else {
              if ($maxInstances) {
                $result[$activityTypeName] = $maxInstances;
              }
            }
          }
        }
      }
    }

    // call option value hook
    CRM_Utils_Hook::optionValues($result, 'case_activity_type');

    return $result;
  }

  /**
   * @param SimpleXMLElement $caseTypeXML
   *
   * @return array<string> symbolic activity-type names
   */
  public function getDeclaredActivityTypes($caseTypeXML) {
    $result = array();

    if (!empty($caseTypeXML->ActivityTypes) && $caseTypeXML->ActivityTypes->ActivityType) {
      foreach ($caseTypeXML->ActivityTypes->ActivityType as $activityTypeXML) {
        $result[] = (string) $activityTypeXML->name;
      }
    }

    if (!empty($caseTypeXML->ActivitySets) && $caseTypeXML->ActivitySets->ActivitySet) {
      foreach ($caseTypeXML->ActivitySets->ActivitySet as $activitySetXML) {
        if ($activitySetXML->ActivityTypes && $activitySetXML->ActivityTypes->ActivityType) {
          foreach ($activitySetXML->ActivityTypes->ActivityType as $activityTypeXML) {
            $result[] = (string) $activityTypeXML->name;
          }
        }
      }
    }

    $result = array_unique($result);
    sort($result);
    return $result;
  }

  /**
   * @param SimpleXMLElement $caseTypeXML
   *
   * @return array<string> symbolic relationship-type names
   */
  public function getDeclaredRelationshipTypes($caseTypeXML) {
    $result = array();

    if (!empty($caseTypeXML->CaseRoles) && $caseTypeXML->CaseRoles->RelationshipType) {
      foreach ($caseTypeXML->CaseRoles->RelationshipType as $relTypeXML) {
        $result[] = (string) $relTypeXML->name;
      }
    }

    $result = array_unique($result);
    sort($result);
    return $result;
  }

  /**
   * @param array $params
   */
  public function deleteEmptyActivity(&$params) {
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    $query = "
DELETE a
FROM   civicrm_activity a
INNER JOIN civicrm_activity_contact t ON t.activity_id = a.id
INNER JOIN civicrm_case_activity ca on ca.activity_id = a.id
WHERE  t.contact_id = %1
AND    t.record_type_id = $targetID
AND    a.is_auto = 1
AND    a.is_current_revision = 1
AND    ca.case_id = %2
";
    $sqlParams = array(1 => array($params['clientID'], 'Integer'), 2 => array($params['caseID'], 'Integer'));
    CRM_Core_DAO::executeQuery($query, $sqlParams);
  }

  /**
   * @param array $params
   *
   * @return bool
   */
  public function isActivityPresent(&$params) {
    $query = "
SELECT     count(a.id)
FROM       civicrm_activity a
INNER JOIN civicrm_case_activity ca on ca.activity_id = a.id
WHERE      a.activity_type_id  = %1
AND        ca.case_id = %2
AND        a.is_deleted = 0
";

    $sqlParams = array(
      1 => array($params['activityTypeID'], 'Integer'),
      2 => array($params['caseID'], 'Integer'),
    );
    $count = CRM_Core_DAO::singleValueQuery($query, $sqlParams);

    // check for max instance
    $caseType = CRM_Case_BAO_Case::getCaseType($params['caseID'], 'name');
    $maxInstance = self::getMaxInstance($caseType, $params['activityTypeName']);

    return $maxInstance ? ($count < $maxInstance ? FALSE : TRUE) : FALSE;
  }

  /**
   * @param $activityTypeXML
   * @param array $params
   *
   * @return bool
   * @throws CRM_Core_Exception
   * @throws Exception
   */
  public function createActivity($activityTypeXML, &$params) {
    $activityTypeName = (string) $activityTypeXML->name;
    $activityTypes = &$this->allActivityTypes(TRUE, TRUE);
    $activityTypeInfo = CRM_Utils_Array::value($activityTypeName, $activityTypes);

    if (!$activityTypeInfo) {
      $docLink = CRM_Utils_System::docURL2("user/case-management/set-up");
      CRM_Core_Error::fatal(ts('Activity type %1, found in case configuration file, is not present in the database %2',
        array(1 => $activityTypeName, 2 => $docLink)
      ));
      return FALSE;
    }

    $activityTypeID = $activityTypeInfo['id'];

    if (isset($activityTypeXML->status)) {
      $statusName = (string) $activityTypeXML->status;
    }
    else {
      $statusName = 'Scheduled';
    }

    $client = (array) $params['clientID'];

    //set order
    $orderVal = '';
    if (isset($activityTypeXML->order)) {
      $orderVal = (string) $activityTypeXML->order;
    }

    if ($activityTypeName == 'Open Case') {
      $activityParams = array(
        'activity_type_id' => $activityTypeID,
        'source_contact_id' => $params['creatorID'],
        'is_auto' => FALSE,
        'is_current_revision' => 1,
        'subject' => CRM_Utils_Array::value('subject', $params) ? $params['subject'] : $activityTypeName,
        'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', $statusName),
        'target_contact_id' => $client,
        'medium_id' => CRM_Utils_Array::value('medium_id', $params),
        'location' => CRM_Utils_Array::value('location', $params),
        'details' => CRM_Utils_Array::value('details', $params),
        'duration' => CRM_Utils_Array::value('duration', $params),
        'weight' => $orderVal,
      );
    }
    else {
      $activityParams = array(
        'activity_type_id' => $activityTypeID,
        'source_contact_id' => $params['creatorID'],
        'is_auto' => TRUE,
        'is_current_revision' => 1,
        'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', $statusName),
        'target_contact_id' => $client,
        'weight' => $orderVal,
      );
    }

    $activityParams['assignee_contact_id'] = $this->getDefaultAssigneeForActivity($activityParams, $activityTypeXML);

    //parsing date to default preference format
    $params['activity_date_time'] = CRM_Utils_Date::processDate($params['activity_date_time']);

    if ($activityTypeName == 'Open Case') {
      // we don't set activity_date_time for auto generated
      // activities, but we want it to be set for open case.
      $activityParams['activity_date_time'] = $params['activity_date_time'];
      if (array_key_exists('custom', $params) && is_array($params['custom'])) {
        $activityParams['custom'] = $params['custom'];
      }

      // Add parameters for attachments

      $numAttachments = Civi::settings()->get('max_attachments');
      for ($i = 1; $i <= $numAttachments; $i++) {
        $attachName = "attachFile_$i";
        if (isset($params[$attachName]) && !empty($params[$attachName])) {
          $activityParams[$attachName] = $params[$attachName];
        }
      }
    }
    else {
      $activityDate = NULL;
      //get date of reference activity if set.
      if ($referenceActivityName = (string) $activityTypeXML->reference_activity) {

        //we skip open case as reference activity.CRM-4374.
        if (!empty($params['resetTimeline']) && $referenceActivityName == 'Open Case') {
          $activityDate = $params['activity_date_time'];
        }
        else {
          $referenceActivityInfo = CRM_Utils_Array::value($referenceActivityName, $activityTypes);
          if ($referenceActivityInfo['id']) {
            $caseActivityParams = array('activity_type_id' => $referenceActivityInfo['id']);

            //if reference_select is set take according activity.
            if ($referenceSelect = (string) $activityTypeXML->reference_select) {
              $caseActivityParams[$referenceSelect] = 1;
            }

            $referenceActivity = CRM_Case_BAO_Case::getCaseActivityDates($params['caseID'], $caseActivityParams, TRUE);

            if (is_array($referenceActivity)) {
              foreach ($referenceActivity as $aId => $details) {
                $activityDate = CRM_Utils_Array::value('activity_date', $details);
                break;
              }
            }
          }
        }
      }
      if (!$activityDate) {
        $activityDate = $params['activity_date_time'];
      }
      list($activity_date, $activity_time) = CRM_Utils_Date::setDateDefaults($activityDate);
      $activityDateTime = CRM_Utils_Date::processDate($activity_date, $activity_time);
      //add reference offset to date.
      if ((int) $activityTypeXML->reference_offset) {
        $activityDateTime = CRM_Utils_Date::intervalAdd('day', (int) $activityTypeXML->reference_offset,
          $activityDateTime
        );
      }

      $activityParams['activity_date_time'] = CRM_Utils_Date::format($activityDateTime);
    }

    // if same activity is already there, skip and dont touch
    $params['activityTypeID'] = $activityTypeID;
    $params['activityTypeName'] = $activityTypeName;
    if ($this->isActivityPresent($params)) {
      return TRUE;
    }
    $activityParams['case_id'] = $params['caseID'];
    if (!empty($activityParams['is_auto'])) {
      $activityParams['skipRecentView'] = TRUE;
    }

    // @todo - switch to using api & remove the parameter pre-wrangling above.
    $activity = CRM_Activity_BAO_Activity::create($activityParams);

    if (!$activity) {
      CRM_Core_Error::fatal();
      return FALSE;
    }

    // create case activity record
    $caseParams = array(
      'activity_id' => $activity->id,
      'case_id' => $params['caseID'],
    );
    CRM_Case_BAO_Case::processCaseActivity($caseParams);
    return TRUE;
  }

  /**
   * Return the default assignee contact for the activity.
   *
   * @param array $activityParams
   * @param object $activityTypeXML
   *
   * @return int|null the ID of the default assignee contact or null if none.
   */
  protected function getDefaultAssigneeForActivity($activityParams, $activityTypeXML) {
    if (!isset($activityTypeXML->default_assignee_type)) {
      return NULL;
    }

    $defaultAssigneeOptionsValues = $this->getDefaultAssigneeOptionValues();

    switch ($activityTypeXML->default_assignee_type) {
      case $defaultAssigneeOptionsValues['BY_RELATIONSHIP']:
        return $this->getDefaultAssigneeByRelationship($activityParams, $activityTypeXML);

      break;
      case $defaultAssigneeOptionsValues['SPECIFIC_CONTACT']:
        return $this->getDefaultAssigneeBySpecificContact($activityTypeXML);

      break;
      case $defaultAssigneeOptionsValues['USER_CREATING_THE_CASE']:
        return $activityParams['source_contact_id'];

      break;
      case $defaultAssigneeOptionsValues['NONE']:
      default:
        return NULL;
    }
  }

  /**
   * Fetches and caches the activity's default assignee options.
   *
   * @return array
   */
  protected function getDefaultAssigneeOptionValues() {
    if (!empty($this->defaultAssigneeOptionsValues)) {
      return $this->defaultAssigneeOptionsValues;
    }

    $defaultAssigneeOptions = civicrm_api3('OptionValue', 'get', [
      'option_group_id' => 'activity_default_assignee',
      'options' => [ 'limit' => 0 ]
    ]);

    foreach ($defaultAssigneeOptions['values'] as $option) {
      $this->defaultAssigneeOptionsValues[$option['name']] = $option['value'];
    }

    return $this->defaultAssigneeOptionsValues;
  }

  /**
   * Returns the default assignee for the activity by searching for the target's
   * contact relationship type defined in the activity's details.
   *
   * @param array $activityParams
   * @param object $activityTypeXML
   *
   * @return int|null the ID of the default assignee contact or null if none.
   */
  protected function getDefaultAssigneeByRelationship($activityParams, $activityTypeXML) {
    $isDefaultRelationshipDefined = isset($activityTypeXML->default_assignee_relationship)
      && preg_match('/\d+_[ab]_[ab]/', $activityTypeXML->default_assignee_relationship);

    if (!$isDefaultRelationshipDefined) {
      return NULL;
    }

    $targetContactId = is_array($activityParams['target_contact_id'])
      ? CRM_Utils_Array::first($activityParams['target_contact_id'])
      : $activityParams['target_contact_id'];
    list($relTypeId, $a, $b) = explode('_', $activityTypeXML->default_assignee_relationship);

    $params = [
      'relationship_type_id' => $relTypeId,
      "contact_id_$b" => $targetContactId,
      'is_active' => 1,
    ];

    if ($this->isBidirectionalRelationshipType($relTypeId)) {
      $params["contact_id_$a"] = $targetContactId;
      $params['options']['or'] = [['contact_id_a', 'contact_id_b']];
    }

    $relationships = civicrm_api3('Relationship', 'get', $params);

    if ($relationships['count']) {
      $relationship = CRM_Utils_Array::first($relationships['values']);

      // returns the contact id on the other side of the relationship:
      return (int) $relationship['contact_id_a'] === (int) $targetContactId
        ? $relationship['contact_id_b']
        : $relationship['contact_id_a'];
    }
    else {
      return NULL;
    }
  }

  /**
   * Determines if the given relationship type is bidirectional or not by
   * comparing their labels.
   *
   * @return bool
   */
  protected function isBidirectionalRelationshipType($relationshipTypeId) {
    $relationshipTypeResult = civicrm_api3('RelationshipType', 'get', [
      'id' => $relationshipTypeId,
      'options' => ['limit' => 1]
    ]);

    if ($relationshipTypeResult['count'] === 0) {
      return FALSE;
    }

    $relationshipType = CRM_Utils_Array::first($relationshipTypeResult['values']);

    return $relationshipType['label_b_a'] === $relationshipType['label_a_b'];
  }

  /**
   * Returns the activity's default assignee for a specific contact if the contact exists,
   * otherwise returns null.
   *
   * @param object $activityTypeXML
   *
   * @return int|null
   */
  protected function getDefaultAssigneeBySpecificContact($activityTypeXML) {
    if (!$activityTypeXML->default_assignee_contact) {
      return NULL;
    }

    $contact = civicrm_api3('Contact', 'get', [
      'id' => $activityTypeXML->default_assignee_contact
    ]);

    if ($contact['count'] == 1) {
      return $activityTypeXML->default_assignee_contact;
    }

    return NULL;
  }

  /**
   * @param $activitySetsXML
   *
   * @return array
   */
  public static function activitySets($activitySetsXML) {
    $result = array();
    foreach ($activitySetsXML as $activitySetXML) {
      foreach ($activitySetXML as $recordXML) {
        $activitySetName = (string ) $recordXML->name;
        $activitySetLabel = (string ) $recordXML->label;
        $result[$activitySetName] = $activitySetLabel;
      }
    }

    return $result;
  }

  /**
   * @param $caseType
   * @param null $activityTypeName
   *
   * @return array|bool|mixed
   * @throws Exception
   */
  public function getMaxInstance($caseType, $activityTypeName = NULL) {
    $xml = $this->retrieve($caseType);

    if ($xml === FALSE) {
      CRM_Core_Error::fatal();
      return FALSE;
    }

    $activityInstances = $this->activityTypes($xml->ActivityTypes, TRUE);
    return $activityTypeName ? CRM_Utils_Array::value($activityTypeName, $activityInstances) : $activityInstances;
  }

  /**
   * @param $caseType
   *
   * @return array|mixed
   */
  public function getCaseManagerRoleId($caseType) {
    $xml = $this->retrieve($caseType);
    return $this->caseRoles($xml->CaseRoles, TRUE);
  }

  /**
   * @param string $caseType
   *
   * @return array<\Civi\CCase\CaseChangeListener>
   */
  public function getListeners($caseType) {
    $xml = $this->retrieve($caseType);
    $listeners = array();
    if ($xml->Listeners && $xml->Listeners->Listener) {
      foreach ($xml->Listeners->Listener as $listenerXML) {
        $class = (string) $listenerXML;
        $listeners[] = new $class();
      }
    }
    return $listeners;
  }

  /**
   * @return int
   */
  public function getRedactActivityEmail() {
    return $this->getBoolSetting('civicaseRedactActivityEmail', 'RedactActivityEmail');
  }

  /**
   * Retrieves AllowMultipleCaseClients setting.
   *
   * @return string
   *   1 if allowed, 0 if not
   */
  public function getAllowMultipleCaseClients() {
    return $this->getBoolSetting('civicaseAllowMultipleClients', 'AllowMultipleCaseClients');
  }

  /**
   * Retrieves NaturalActivityTypeSort setting.
   *
   * @return string
   *   1 if natural, 0 if alphabetic
   */
  public function getNaturalActivityTypeSort() {
    return $this->getBoolSetting('civicaseNaturalActivityTypeSort', 'NaturalActivityTypeSort');
  }

  /**
   * @param string $settingKey
   * @param string $xmlTag
   * @param mixed $default
   *
   * @return int
   */
  private function getBoolSetting($settingKey, $xmlTag, $default = 0) {
    $setting = Civi::settings()->get($settingKey);
    if ($setting !== 'default') {
      return (int) $setting;
    }
    if ($xml = $this->retrieve("Settings")) {
      return (string) $xml->{$xmlTag} ? 1 : 0;
    }
    return $default;
  }

}
