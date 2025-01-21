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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Case_XMLProcessor_Process extends CRM_Case_XMLProcessor {
  protected $defaultAssigneeOptionsValues = [];

  /**
   * Does Cases support Multiple Clients.
   * @var bool
   */
  public $_isMultiClient = FALSE;

  /**
   * Run.
   *
   * @param string $caseType
   * @param array $params
   *
   * @throws CRM_Core_Exception
   */
  public function run($caseType, &$params) {
    $xml = $this->retrieve($caseType);

    if ($xml === FALSE) {
      $docLink = CRM_Utils_System::docURL2("user/case-management/set-up");
      throw new CRM_Core_Exception(ts("Configuration file could not be retrieved for case type = '%1' %2.",
        [1 => $caseType, 2 => $docLink]
      ));
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
      throw new CRM_Core_Exception(ts("Unable to load configuration file for the referenced case type: '%1' %2.",
        [1 => $caseType, 2 => $docLink]
      ));
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
   * @param SimpleXMLElement $xml
   * @param array $params
   *
   * @throws Exception
   */
  public function process($xml, &$params) {
    $standardTimeline = $params['standardTimeline'] ?? NULL;
    $activitySetName = $params['activitySetName'] ?? NULL;

    if ('Open Case' == ($params['activityTypeName'] ?? '')) {
      // create relationships for the ones that are required
      foreach ($xml->CaseRoles as $caseRoleXML) {
        foreach ($caseRoleXML->RelationshipType as $relationshipTypeXML) {
          // simplexml treats node values differently than you'd expect,
          // e.g. as an array
          // Just using `if ($relationshipTypeXML->creator)` ends up always
          // being true, so you have to cast to int or somehow force evaluation
          // of the actual value. And casting to (bool) seems to behave
          // differently on these objects than casting to (int).
          if (!empty($relationshipTypeXML->creator)) {
            if (!$this->createRelationships($relationshipTypeXML,
              $params
            )
            ) {
              throw new CRM_Core_Exception('Unable to create case relationships');
            }
          }
        }
      }
    }

    if ('Change Case Start Date' == ($params['activityTypeName'] ?? '')) {
      // delete all existing activities which are non-empty
      $this->deleteEmptyActivity($params);
    }

    foreach ($xml->ActivitySets as $activitySetsXML) {
      foreach ($activitySetsXML->ActivitySet as $activitySetXML) {
        if ($standardTimeline) {
          if (!empty($activitySetXML->timeline)) {
            return $this->processStandardTimeline($activitySetXML, $params);
          }
        }
        elseif ($activitySetName) {
          $name = (string) $activitySetXML->name;
          if ($name == $activitySetName) {
            return $this->processActivitySet($activitySetXML, $params);
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
    if ('Change Case Type' == ($params['activityTypeName'] ?? '')
      && ($params['resetTimeline'] ?? TRUE)
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
    // Look up relationship types according to the XML convention (described
    // from perspective of non-client) but return the labels according to the UI
    // convention (described from perspective of client)
    $relationshipTypesToReturn = &$this->allRelationshipTypes(FALSE);

    $result = [];
    foreach ($caseRolesXML as $caseRoleXML) {
      foreach ($caseRoleXML->RelationshipType as $relationshipTypeXML) {
        [$relationshipTypeID] = $this->locateNameOrLabel($relationshipTypeXML);
        if ($relationshipTypeID === FALSE) {
          continue;
        }

        if (!$isCaseManager) {
          $result[$relationshipTypeID] = $relationshipTypesToReturn[$relationshipTypeID];
        }
        elseif ($relationshipTypeXML->manager == 1) {
          return $relationshipTypeID;
        }
      }
    }
    return $result;
  }

  /**
   * @param SimpleXMLElement $relationshipTypeXML
   * @param array $params
   *
   * @return bool
   * @throws CRM_Core_Exception
   */
  public function createRelationships($relationshipTypeXML, $params) {
    // get the relationship
    [$relationshipType, $relationshipTypeName] = $this->locateNameOrLabel($relationshipTypeXML);
    if ($relationshipType === FALSE) {
      $docLink = CRM_Utils_System::docURL2("user/case-management/set-up");
      throw new CRM_Core_Exception(ts('Relationship type %1, found in case configuration file, is not present in the database %2',
        [1 => $relationshipTypeName, 2 => $docLink]
      ));
    }

    $clients = (array) $params['clientID'];
    $relationshipValues = [];

    foreach ($clients as $clientId) {
      // $relationshipType string ends in either `_a_b` or `_b_a`
      $a = substr($relationshipType, -3, 1);
      $b = substr($relationshipType, -1);
      $relationshipValues[] = [
        'relationship_type_id' => substr($relationshipType, 0, -4),
        'is_active' => 1,
        'case_id' => $params['caseID'],
        'start_date' => date("Ymd"),
        'end_date' => $params['relationship_end_date'] ?? NULL,
        "contact_id_$a" => $clientId,
        "contact_id_$b" => $params['creatorID'],
      ];
    }

    //\Civi\Api4\Relationship::save(FALSE)
    //  ->setRecords($relationshipValues)
    //  ->setMatch(['case_id', 'relationship_type_id', 'contact_id_a', 'contact_id_b'])
    //  ->execute();
    // FIXME: The above api code would be better, but doesn't work
    // See discussion in https://github.com/civicrm/civicrm-core/pull/15030
    foreach ($relationshipValues as $params) {
      $dao = new CRM_Contact_DAO_Relationship();
      $dao->copyValues($params);
      // only create a relationship if it does not exist
      if (!$dao->find(TRUE)) {
        CRM_Contact_BAO_Relationship::add($params);
      }
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
    $activityTypes = CRM_Case_PseudoConstant::caseActivityType(TRUE, TRUE);
    $result = [];
    foreach ($activityTypesXML as $activityTypeXML) {
      foreach ($activityTypeXML as $recordXML) {
        $activityTypeName = (string) $recordXML->name;
        $maxInstances = (string) $recordXML->max_instances;
        $activityTypeInfo = $activityTypes[$activityTypeName] ?? NULL;

        if ($activityTypeInfo) {
          if ($maskAction) {
            if ($maskAction == 'edit' && '0' === (string) $recordXML->editable) {
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
    $result = [];

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
   * Relationships are straight from XML, described from perspective of non-client
   *
   * @param SimpleXMLElement $caseTypeXML
   *
   * @return array<string> symbolic relationship-type names
   */
  public function getDeclaredRelationshipTypes($caseTypeXML) {
    $result = [];

    if (!empty($caseTypeXML->CaseRoles) && $caseTypeXML->CaseRoles->RelationshipType) {
      foreach ($caseTypeXML->CaseRoles->RelationshipType as $relTypeXML) {
        [, $relationshipTypeMachineName] = $this->locateNameOrLabel($relTypeXML);
        $result[] = $relationshipTypeMachineName;
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
    $sqlParams = [1 => [$params['clientID'], 'Integer'], 2 => [$params['caseID'], 'Integer']];
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

    $sqlParams = [
      1 => [$params['activityTypeID'], 'Integer'],
      2 => [$params['caseID'], 'Integer'],
    ];
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
   */
  public function createActivity($activityTypeXML, &$params): bool {
    $activityTypeName = (string) $activityTypeXML->name;
    $activityTypes = CRM_Case_PseudoConstant::caseActivityType(TRUE, TRUE);
    $activityTypeInfo = $activityTypes[$activityTypeName] ?? NULL;

    if (!$activityTypeInfo) {
      $docLink = CRM_Utils_System::docURL2("user/case-management/set-up");
      throw new CRM_Core_Exception(ts('Activity type %1, found in case configuration file, is not present in the database %2',
        [1 => $activityTypeName, 2 => $docLink]
      ));
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
      $activityParams = [
        'activity_type_id' => $activityTypeID,
        'source_contact_id' => $params['creatorID'],
        'is_auto' => FALSE,
        'is_current_revision' => 1,
        'subject' => !empty($params['subject']) ? $params['subject'] : $activityTypeName,
        'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', $statusName),
        'target_contact_id' => $client,
        'medium_id' => $params['medium_id'] ?? NULL,
        'location' => $params['location'] ?? NULL,
        'details' => $params['details'] ?? NULL,
        'duration' => $params['duration'] ?? NULL,
        'weight' => $orderVal,
      ];
    }
    else {
      $activityParams = [
        'activity_type_id' => $activityTypeID,
        'source_contact_id' => $params['creatorID'],
        'is_auto' => TRUE,
        'is_current_revision' => 1,
        'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', $statusName),
        'target_contact_id' => $client,
        'weight' => $orderVal,
      ];
    }

    $activityParams['assignee_contact_id'] = $this->getDefaultAssigneeForActivity($activityParams, $activityTypeXML, $params['caseID']);

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
          $referenceActivityInfo = $activityTypes[$referenceActivityName] ?? NULL;
          if ($referenceActivityInfo['id']) {
            $caseActivityParams = ['activity_type_id' => $referenceActivityInfo['id']];

            //if reference_select is set take according activity.
            if ($referenceSelect = (string) $activityTypeXML->reference_select) {
              $caseActivityParams[$referenceSelect] = 1;
            }

            $referenceActivity = CRM_Case_BAO_Case::getCaseActivityDates($params['caseID'], $caseActivityParams, TRUE);

            if (is_array($referenceActivity)) {
              foreach ($referenceActivity as $aId => $details) {
                $activityDate = $details['activity_date'] ?? NULL;
                break;
              }
            }
          }
        }
      }
      if (!$activityDate) {
        $activityDate = $params['activity_date_time'];
      }
      [$activity_date, $activity_time] = CRM_Utils_Date::setDateDefaults($activityDate);
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
      throw new CRM_Core_Exception('Unable to create Activity');
    }
    return TRUE;
  }

  /**
   * Return the default assignee contact for the activity.
   *
   * @param array $activityParams
   * @param object $activityTypeXML
   * @param int $caseId
   *
   * @return int|null the ID of the default assignee contact or null if none.
   */
  protected function getDefaultAssigneeForActivity($activityParams, $activityTypeXML, $caseId) {
    if (!isset($activityTypeXML->default_assignee_type)) {
      return NULL;
    }

    $defaultAssigneeOptionsValues = $this->getDefaultAssigneeOptionValues();

    switch ($activityTypeXML->default_assignee_type) {
      case $defaultAssigneeOptionsValues['BY_RELATIONSHIP']:
        return $this->getDefaultAssigneeByRelationship($activityParams, $activityTypeXML, $caseId);

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
      'options' => ['limit' => 0],
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
   * @param int $caseId
   *
   * @return int|null the ID of the default assignee contact or null if none.
   */
  protected function getDefaultAssigneeByRelationship($activityParams, $activityTypeXML, $caseId) {
    $isDefaultRelationshipDefined = isset($activityTypeXML->default_assignee_relationship)
      && preg_match('/\d+_[ab]_[ab]/', $activityTypeXML->default_assignee_relationship);

    if (!$isDefaultRelationshipDefined) {
      return NULL;
    }

    $targetContactId = is_array($activityParams['target_contact_id'])
      ? CRM_Utils_Array::first($activityParams['target_contact_id'])
      : $activityParams['target_contact_id'];
    [$relTypeId, $a, $b] = explode('_', $activityTypeXML->default_assignee_relationship);

    $params = [
      'relationship_type_id' => $relTypeId,
      "contact_id_$b" => $targetContactId,
      'is_active' => 1,
      'case_id' => $caseId,
      'options' => ['limit' => 1],
    ];

    if ($this->isBidirectionalRelationshipType($relTypeId)) {
      $params["contact_id_$a"] = $targetContactId;
      $params['options']['or'] = [['contact_id_a', 'contact_id_b']];
    }

    $relationships = civicrm_api3('Relationship', 'get', $params);
    if (empty($relationships['count'])) {
      $params['case_id'] = ['IS NULL' => 1];
      $relationships = civicrm_api3('Relationship', 'get', $params);
    }

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
      'options' => ['limit' => 1],
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
      'id' => $activityTypeXML->default_assignee_contact,
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
    $result = [];
    foreach ($activitySetsXML as $activitySetXML) {
      foreach ($activitySetXML as $recordXML) {
        $activitySetName = (string) $recordXML->name;
        $activitySetLabel = (string) $recordXML->label;
        $result[$activitySetName] = $activitySetLabel;
      }
    }

    return $result;
  }

  /**
   * @param $caseType
   * @param string|null $activityTypeName
   *
   * @return array|bool|mixed
   * @throws CRM_Core_Exception
   */
  public function getMaxInstance($caseType, $activityTypeName = NULL) {
    $xml = $this->retrieve($caseType);

    if ($xml === FALSE) {
      throw new CRM_Core_Exception('Unable to locate xml definition for case type ' . $caseType);
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
    $listeners = [];
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

  /**
   * At some point name and label got mixed up for case roles.
   * Check against known machine name values, and then if no match check
   * against labels.
   * This is subject to some edge cases, but we catch those with a system
   * status check.
   * We do this to avoid requiring people to update their xml files which can
   * be stored in external files we can't/don't want to edit.
   *
   * @param SimpleXMLElement $xml
   *
   * @return array[bool|string,string]
   */
  public function locateNameOrLabel($xml) {
    $lookupString = (string) $xml->name;

    // Don't use pseudoconstant because we need everything both name and
    // label and disabled types.
    $relationshipTypes = civicrm_api3('RelationshipType', 'get', [
      'options' => ['limit' => 0],
    ])['values'];

    // First look and see if it matches a machine name in the system.
    // There are some edge cases here where we've actually been passed in a
    // display label and it happens to match the machine name for a different
    // db entry, but we have a system status check.
    // But, we do want to check against the a_b version first, because of the
    // way direction matters and that for bidirectional only one is present in
    // the list where this eventually gets used, so return that first.
    $relationshipTypeMachineNames = array_column($relationshipTypes, 'id', 'name_a_b');
    if (isset($relationshipTypeMachineNames[$lookupString])) {
      return ["{$relationshipTypeMachineNames[$lookupString]}_b_a", $lookupString];
    }
    $relationshipTypeMachineNames = array_column($relationshipTypes, 'id', 'name_b_a');
    if (isset($relationshipTypeMachineNames[$lookupString])) {
      return ["{$relationshipTypeMachineNames[$lookupString]}_a_b", $lookupString];
    }

    // Now at this point assume we've been passed a display label, so find
    // what it matches and return the associated machine name. This is a bit
    // trickier because suppose somebody has changed the display labels so
    // that they are now the same, but the machine names are different. We
    // don't know which to return and so while it's the right relationship type
    // it might be the backwards direction. We have to pick one to try first.

    $relationshipTypeDisplayLabels = array_column($relationshipTypes, 'id', 'label_a_b');
    if (isset($relationshipTypeDisplayLabels[$lookupString])) {
      return [
        "{$relationshipTypeDisplayLabels[$lookupString]}_b_a",
        $relationshipTypes[$relationshipTypeDisplayLabels[$lookupString]]['name_a_b'],
      ];
    }
    $relationshipTypeDisplayLabels = array_column($relationshipTypes, 'id', 'label_b_a');
    if (isset($relationshipTypeDisplayLabels[$lookupString])) {
      return [
        "{$relationshipTypeDisplayLabels[$lookupString]}_a_b",
        $relationshipTypes[$relationshipTypeDisplayLabels[$lookupString]]['name_b_a'],
      ];
    }

    // Just go with what we were passed in, even though it doesn't seem
    // to match *anything*. This was what it did before.
    return [FALSE, $lookupString];
  }

}
