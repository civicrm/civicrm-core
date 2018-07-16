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
class CRM_Case_XMLProcessor_Report extends CRM_Case_XMLProcessor {

  /**
   * The default variable defined.
   *
   * @var boolean
   */
  protected $_isRedact;

  /**
   */
  public function __construct() {
  }

  /**
   * @param int $clientID
   * @param int $caseID
   * @param string $activitySetName
   * @param array $params
   *
   * @return mixed
   */
  public function run($clientID, $caseID, $activitySetName, $params) {
    $contents = self::getCaseReport($clientID,
      $caseID,
      $activitySetName,
      $params,
      $this
    );

    return CRM_Case_Audit_Audit::run($contents, $clientID, $caseID);
  }

  public function getRedactionRules() {
    foreach (array(
               'redactionStringRules',
               'redactionRegexRules',
             ) as $key => $rule) {
      $$rule = CRM_Case_PseudoConstant::redactionRule($key);

      if (!empty($$rule)) {
        foreach ($$rule as & $val) {
          //suffixed with a randomly generated 4-digit number
          if ($key == 'redactionStringRules') {
            $val .= rand(10000, 100000);
          }
        }

        if (!empty($this->{'_' . $rule})) {
          $this->{'_' . $rule} = CRM_Utils_Array::crmArrayMerge($this->{'_' . $rule}, $$rule);
        }
        else {
          $this->{'_' . $rule} = $$rule;
        }
      }
    }
  }

  /**
   * @param int $clientID
   * @param int $caseID
   *
   * @return array
   */
  public function &caseInfo(
    $clientID,
    $caseID
  ) {
    $case = $this->_redactionRegexRules = array();

    if (empty($this->_redactionStringRules)) {
      $this->_redactionStringRules = array();
    }

    if ($this->_isRedact == 1) {
      $this->getRedactionRules();
    }

    $client = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $clientID, 'display_name');

    // add Client to the strings to be redacted across the case session
    if (!array_key_exists($client, $this->_redactionStringRules)) {
      $this->_redactionStringRules = CRM_Utils_Array::crmArrayMerge($this->_redactionStringRules,
        array($client => 'name_' . rand(10000, 100000))
      );
      $clientSortName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $clientID, 'sort_name');
      if (!array_key_exists($clientSortName, $this->_redactionStringRules)) {
        $this->_redactionStringRules[$clientSortName] = $this->_redactionStringRules[$client];
      }
    }

    $case['clientName'] = $this->redact($client);

    $dao = new CRM_Case_DAO_Case();
    $dao->id = $caseID;
    if ($dao->find(TRUE)) {
      $case['subject'] = $dao->subject;
      $case['start_date'] = $dao->start_date;
      $case['end_date'] = $dao->end_date;
      // FIXME: when we resolve if case_type_is single or multi-select
      if (strpos($dao->case_type_id, CRM_Core_DAO::VALUE_SEPARATOR) !== FALSE) {
        $caseTypeID = substr($dao->case_type_id, 1, -1);
      }
      else {
        $caseTypeID = $dao->case_type_id;
      }
      $caseTypeIDs = explode(CRM_Core_DAO::VALUE_SEPARATOR,
        $dao->case_type_id
      );

      $case['caseType'] = CRM_Case_BAO_Case::getCaseType($caseID);
      $case['caseTypeName'] = CRM_Case_BAO_Case::getCaseType($caseID, 'name');
      $case['status'] = CRM_Core_OptionGroup::getLabel('case_status', $dao->status_id, FALSE);
    }
    return $case;
  }

  /**
   * @param $xml
   * @param string $activitySetName
   *
   * @return array|bool
   */
  public function getActivityTypes($xml, $activitySetName) {
    foreach ($xml->ActivitySets as $activitySetsXML) {
      foreach ($activitySetsXML->ActivitySet as $activitySetXML) {
        if ((string ) $activitySetXML->name == $activitySetName) {
          $activityTypes = array();
          $allActivityTypes = &$this->allActivityTypes();
          foreach ($activitySetXML->ActivityTypes as $activityTypesXML) {
            foreach ($activityTypesXML as $activityTypeXML) {
              $activityTypeName = (string ) $activityTypeXML->name;
              $activityTypeInfo = CRM_Utils_Array::value($activityTypeName, $allActivityTypes);
              if ($activityTypeInfo) {
                $activityTypes[$activityTypeInfo['id']] = $activityTypeInfo;
              }
            }
          }
          return empty($activityTypes) ? FALSE : $activityTypes;
        }
      }
    }
    return FALSE;
  }

  /**
   * @param $xml
   * @param string $activitySetName
   *
   * @return null|string
   */
  public function getActivitySetLabel($xml, $activitySetName) {
    foreach ($xml->ActivitySets as $activitySetsXML) {
      foreach ($activitySetsXML->ActivitySet as $activitySetXML) {
        if ((string ) $activitySetXML->name == $activitySetName) {
          return (string ) $activitySetXML->label;
        }
      }
    }
    return NULL;
  }

  /**
   * @param int $clientID
   * @param int $caseID
   * @param $activityTypes
   * @param $activities
   */
  public function getActivities($clientID, $caseID, $activityTypes, &$activities) {
    // get all activities for this case that in this activityTypes set
    foreach ($activityTypes as $aType) {
      $map[$aType['id']] = $aType;
    }

    // get all core activities
    $coreActivityTypes = CRM_Case_PseudoConstant::caseActivityType(FALSE, TRUE);

    foreach ($coreActivityTypes as $aType) {
      $map[$aType['id']] = $aType;
    }

    $activityTypeIDs = implode(',', array_keys($map));
    $query = "
SELECT a.*, c.id as caseID
FROM   civicrm_activity a,
       civicrm_case     c,
       civicrm_case_activity ac
WHERE  a.is_current_revision = 1
AND    a.is_deleted =0
AND    a.activity_type_id IN ( $activityTypeIDs )
AND    c.id = ac.case_id
AND    a.id = ac.activity_id
AND    ac.case_id = %1
";

    $params = array(1 => array($caseID, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    while ($dao->fetch()) {
      $activityTypeInfo = $map[$dao->activity_type_id];
      $activities[] = $this->getActivity($clientID,
        $dao,
        $activityTypeInfo
      );
    }
  }

  /**
   * @param int $clientID
   * @param int $activityID
   * @param bool $anyActivity
   * @param int $redact
   *
   * @return mixed
   */
  public function &getActivityInfo($clientID, $activityID, $anyActivity = FALSE, $redact = 0) {
    static $activityInfos = array();
    if ($redact) {
      $this->_isRedact = 1;
      $this->getRedactionRules();
    }

    $index = $activityID . '_' . (int) $anyActivity;

    if ($clientID) {
      $index = $index . '_' . $clientID;
    }

    if (!array_key_exists($index, $activityInfos)) {
      $activityInfos[$index] = array();
      $selectCaseActivity = "";
      $joinCaseActivity = "";

      if ($clientID) {
        $selectCaseActivity = ", ca.case_id as caseID ";
        $joinCaseActivity = " INNER JOIN civicrm_case_activity ca ON a.id = ca.activity_id ";
      }

      $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
      $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
      $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);

      $query = "
SELECT     a.*, aa.contact_id as assigneeID, at.contact_id as targetID
{$selectCaseActivity}
FROM       civicrm_activity a
{$joinCaseActivity}
LEFT JOIN civicrm_activity_contact at ON a.id = at.activity_id AND at.record_type_id = $targetID
LEFT JOIN civicrm_activity_contact aa ON a.id = aa.activity_id AND aa.record_type_id = $assigneeID
WHERE      a.id = %1
    ";
      $params = array(1 => array($activityID, 'Integer'));
      $dao = CRM_Core_DAO::executeQuery($query, $params);

      if ($dao->fetch()) {
        //if activity type is email get info of all activities.
        if ($dao->activity_type_id == CRM_Core_PseudoConstant::getKey('CRM_Activity_DAO_Activity', 'activity_type_id', 'Email')) {
          $anyActivity = TRUE;
        }
        $activityTypes = CRM_Case_PseudoConstant::caseActivityType(FALSE, $anyActivity);
        $activityTypeInfo = NULL;

        if (isset($activityTypes[$dao->activity_type_id])) {
          $activityTypeInfo = $activityTypes[$dao->activity_type_id];
        }
        if ($activityTypeInfo) {
          $activityInfos[$index] = $this->getActivity($clientID, $dao, $activityTypeInfo);
        }
      }
    }

    return $activityInfos[$index];
  }

  /**
   * @param int $clientID
   * @param $activityDAO
   * @param $activityTypeInfo
   *
   * @return array
   */
  public function &getActivity($clientID, $activityDAO, &$activityTypeInfo) {
    if (empty($this->_redactionStringRules)) {
      $this->_redactionStringRules = array();
    }

    $activity = array();
    $activity['fields'] = array();
    if ($clientID) {
      $clientID = CRM_Utils_Type::escape($clientID, 'Integer');
      if (!in_array($activityTypeInfo['name'], array(
        'Email',
        'Inbound Email',
      ))
      ) {
        $activity['editURL'] = CRM_Utils_System::url('civicrm/case/activity',
          "reset=1&cid={$clientID}&caseid={$activityDAO->caseID}&action=update&atype={$activityDAO->activity_type_id}&id={$activityDAO->id}"
        );
      }
      else {
        $activity['editURL'] = '';
      }

      $client = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $clientID, 'display_name');
      // add Client SortName as well as Display to the strings to be redacted across the case session
      // suffixed with a randomly generated 4-digit number
      if (!array_key_exists($client, $this->_redactionStringRules)) {
        $this->_redactionStringRules = CRM_Utils_Array::crmArrayMerge($this->_redactionStringRules,
          array($client => 'name_' . rand(10000, 100000))
        );

        $clientSortName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $clientID, 'sort_name');
        if (!array_key_exists($clientSortName, $this->_redactionStringRules)) {
          $this->_redactionStringRules[$clientSortName] = $this->_redactionStringRules[$client];
        }
      }

      $activity['fields'][] = array(
        'label' => 'Client',
        'value' => $this->redact($client),
        'type' => 'String',
      );
    }

    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
    if (!empty($activityDAO->targetID)) {
      // Re-lookup the target ID since the DAO only has the first recipient if there are multiple.
      // Maybe not the best solution.
      $targetNames = CRM_Activity_BAO_ActivityContact::getNames($activityDAO->id, $targetID);
      $processTarget = FALSE;
      $label = ts('With Contact(s)');
      if (in_array($activityTypeInfo['name'], array('Email', 'Inbound Email'))) {
        $processTarget = TRUE;
        $label = ts('Recipient');
      }
      if (!$processTarget) {
        foreach ($targetNames as $targetID => $targetName) {
          if ($targetID != $clientID) {
            $processTarget = TRUE;
            break;
          }
        }
      }

      if ($processTarget) {
        $targetRedacted = array();
        foreach ($targetNames as $targetID => $target) {
          // add Recipient SortName as well as Display to the strings to be redacted across the case session
          // suffixed with a randomly generated 4-digit number
          if (!array_key_exists($target, $this->_redactionStringRules)) {
            $this->_redactionStringRules = CRM_Utils_Array::crmArrayMerge($this->_redactionStringRules,
              array($target => 'name_' . rand(10000, 100000))
            );
            $targetSortName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $targetID, 'sort_name');
            if (!array_key_exists($targetSortName, $this->_redactionStringRules)) {
              $this->_redactionStringRules[$targetSortName] = $this->_redactionStringRules[$target];
            }
          }
          $targetRedacted[] = $this->redact($target);
        }

        $activity['fields'][] = array(
          'label' => $label,
          'value' => implode('; ', $targetRedacted),
          'type' => 'String',
        );
      }
    }

    // Activity Type info is a special field
    $activity['fields'][] = array(
      'label' => ts('Activity Type'),
      'value' => $activityTypeInfo['label'],
      'type' => 'String',
    );

    $activity['fields'][] = array(
      'label' => ts('Subject'),
      'value' => htmlspecialchars($this->redact($activityDAO->subject)),
      'type' => 'Memo',
    );

    $creator = $this->getCreatedBy($activityDAO->id);
    // add Creator to the strings to be redacted across the case session
    if (!array_key_exists($creator, $this->_redactionStringRules)) {
      $this->_redactionStringRules = CRM_Utils_Array::crmArrayMerge($this->_redactionStringRules,
        array($creator => 'name_' . rand(10000, 100000))
      );
    }
    $activity['fields'][] = array(
      'label' => ts('Created By'),
      'value' => $this->redact($creator),
      'type' => 'String',
    );
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $source_contact_id = CRM_Activity_BAO_Activity::getActivityContact($activityDAO->id, $sourceID);
    $reporter = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
      $source_contact_id,
      'display_name'
    );

    // add Reporter SortName as well as Display to the strings to be redacted across the case session
    // suffixed with a randomly generated 4-digit number
    if (!array_key_exists($reporter, $this->_redactionStringRules)) {
      $this->_redactionStringRules = CRM_Utils_Array::crmArrayMerge($this->_redactionStringRules,
        array($reporter => 'name_' . rand(10000, 100000))
      );

      $reporterSortName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $source_contact_id,
        'sort_name'
      );
      if (!array_key_exists($reporterSortName, $this->_redactionStringRules)) {
        $this->_redactionStringRules[$reporterSortName] = $this->_redactionStringRules[$reporter];
      }
    }

    $activity['fields'][] = array(
      'label' => ts('Reported By'),
      'value' => $this->redact($reporter),
      'type' => 'String',
    );

    if (!empty($activityDAO->assigneeID)) {
      //allow multiple assignee contacts.CRM-4503.
      $assignee_contact_names = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames(array($activityDAO->id), TRUE);

      foreach ($assignee_contact_names as & $assignee) {
        // add Assignee to the strings to be redacted across the case session
        $this->_redactionStringRules = CRM_Utils_Array::crmArrayMerge($this->_redactionStringRules,
          array($assignee => 'name_' . rand(10000, 100000))
        );
        $assignee = $this->redact($assignee);
      }
      $assigneeContacts = implode(', ', $assignee_contact_names);
      $activity['fields'][] = array(
        'label' => ts('Assigned to'),
        'value' => $assigneeContacts,
        'type' => 'String',
      );
    }

    if ($activityDAO->medium_id) {
      $activity['fields'][] = array(
        'label' => ts('Medium'),
        'value' => CRM_Core_OptionGroup::getLabel('encounter_medium',
          $activityDAO->medium_id, FALSE
        ),
        'type' => 'String',
      );
    }

    $activity['fields'][] = array(
      'label' => ts('Location'),
      'value' => $activityDAO->location,
      'type' => 'String',
    );

    $activity['fields'][] = array(
      'label' => ts('Date and Time'),
      'value' => $activityDAO->activity_date_time,
      'type' => 'Date',
    );

    $activity['fields'][] = array(
      'label' => ts('Details'),
      'value' => $this->redact(CRM_Utils_String::stripAlternatives($activityDAO->details)),
      'type' => 'Memo',
    );

    // Skip Duration field if empty (to avoid " minutes" output). Might want to do this for all fields at some point. dgg
    if ($activityDAO->duration) {
      $activity['fields'][] = array(
        'label' => ts('Duration'),
        'value' => $activityDAO->duration . ' ' . ts('minutes'),
        'type' => 'Int',
      );
    }
    $activity['fields'][] = array(
      'label' => ts('Status'),
      'value' => CRM_Core_PseudoConstant::getLabel('CRM_Activity_DAO_Activity', 'activity_status_id',
        $activityDAO->status_id
      ),
      'type' => 'String',
    );

    $activity['fields'][] = array(
      'label' => ts('Priority'),
      'value' => CRM_Core_PseudoConstant::getLabel('CRM_Activity_DAO_Activity', 'priority',
        $activityDAO->priority_id
      ),
      'type' => 'String',
    );

    //for now empty custom groups
    $activity['customGroups'] = $this->getCustomData($clientID,
      $activityDAO,
      $activityTypeInfo
    );

    return $activity;
  }

  /**
   * @param int $clientID
   * @param $activityDAO
   * @param $activityTypeInfo
   *
   * @return array|null
   */
  public function getCustomData($clientID, $activityDAO, &$activityTypeInfo) {
    list($typeValues, $options, $sql) = $this->getActivityTypeCustomSQL($activityTypeInfo['id'], '%Y-%m-%d');

    $params = array(1 => array($activityDAO->id, 'Integer'));

    $customGroups = array();
    foreach ($sql as $tableName => $sqlClause) {
      $dao = CRM_Core_DAO::executeQuery($sqlClause, $params);
      if ($dao->fetch()) {
        $customGroup = array();
        foreach ($typeValues[$tableName] as $columnName => $typeValue) {

          if (CRM_Utils_Array::value('type', $typeValue) == 'Date') {
            $value = $dao->$columnName;
          }
          else {
            $value = CRM_Core_BAO_CustomField::displayValue($dao->$columnName, $typeValue['fieldID'], $activityDAO->id);
          }

          if ($value) {
            // Note: this is already taken care in getDisplayValue above, but sometimes
            // strings like '^A^A' creates problem. So to fix this special case -
            if (strstr($value, CRM_Core_DAO::VALUE_SEPARATOR)) {
              $value = trim($value, CRM_Core_DAO::VALUE_SEPARATOR);
            }
            if (CRM_Utils_Array::value('type', $typeValue) == 'String' ||
              CRM_Utils_Array::value('type', $typeValue) == 'Memo'
            ) {
              $value = $this->redact($value);
            }
            elseif (CRM_Utils_Array::value('type', $typeValue) == 'Link') {
              $value = CRM_Utils_System::formatWikiURL($value);
            }
          }
          //$typeValue
          $customGroup[] = array(
            'label' => $typeValue['label'],
            'value' => $value,
            'type' => $typeValue['type'],
            'fieldID' => $typeValue['fieldID'],
          );
        }
        $customGroups[$dao->groupTitle] = $customGroup;
      }
    }

    return empty($customGroups) ? NULL : $customGroups;
  }

  /**
   * @param int $activityTypeID
   * @param null $dateFormat
   *
   * @return mixed
   */
  public function getActivityTypeCustomSQL($activityTypeID, $dateFormat = NULL) {
    static $cache = array();

    if (is_null($activityTypeID)) {
      $activityTypeID = 0;
    }

    if (!isset($cache[$activityTypeID])) {
      $query = "
SELECT cg.title           as groupTitle,
       cg.table_name      as tableName ,
       cf.column_name     as columnName,
       cf.label           as label     ,
       cg.id              as groupID   ,
       cf.id              as fieldID   ,
       cf.data_type       as dataType  ,
       cf.html_type       as htmlType  ,
       cf.option_group_id as optionGroupID
FROM   civicrm_custom_group cg,
       civicrm_custom_field cf
WHERE  cf.custom_group_id = cg.id
AND    cg.extends = 'Activity'
AND " . CRM_Core_Permission::customGroupClause(CRM_Core_Permission::VIEW, 'cg.');

      if ($activityTypeID) {
        $query .= "AND ( cg.extends_entity_column_value IS NULL OR cg.extends_entity_column_value LIKE '%" . CRM_Core_DAO::VALUE_SEPARATOR . "%1" . CRM_Core_DAO::VALUE_SEPARATOR . "%' )";
      }
      else {
        $query .= "AND cg.extends_entity_column_value IS NULL";
      }
      $query .= "ORDER BY cg.weight, cf.weight";
      $params = array(
        1 => array(
          $activityTypeID,
          'Integer',
        ),
      );
      $dao = CRM_Core_DAO::executeQuery($query, $params);

      $result = $options = $sql = $groupTitle = array();
      while ($dao->fetch()) {
        if (!array_key_exists($dao->tableName, $result)) {
          $result[$dao->tableName] = array();
          $sql[$dao->tableName] = array();
        }
        $result[$dao->tableName][$dao->columnName] = array(
          'label' => $dao->label,
          'type' => $dao->dataType,
          'fieldID' => $dao->fieldID,
        );

        $options[$dao->fieldID] = array();
        $options[$dao->fieldID]['attributes'] = array(
          'label' => $dao->label,
          'data_type' => $dao->dataType,
          'html_type' => $dao->htmlType,
        );
        // since we want to add ISO date format.
        if ($dateFormat && $dao->htmlType == 'Select Date') {
          $options[$dao->fieldID]['attributes']['format'] = $dateFormat;
        }
        if ($dao->optionGroupID) {
          $query = "
SELECT label, value
  FROM civicrm_option_value
 WHERE option_group_id = %1
";

          $option = CRM_Core_DAO::executeQuery($query, array(1 => array($dao->optionGroupID, 'Positive')));
          while ($option->fetch()) {
            $dataType = $dao->dataType;
            if ($dataType == 'Int' || $dataType == 'Float') {
              $num = round($option->value, 2);
              $options[$dao->fieldID]["$num"] = $option->label;
            }
            else {
              $options[$dao->fieldID][$option->value] = $option->label;
            }
          }
        }

        $sql[$dao->tableName][] = $dao->columnName;
        $groupTitle[$dao->tableName] = $dao->groupTitle;
      }

      foreach ($sql as $tableName => $values) {
        $columnNames = implode(',', $values);
        $title = CRM_Core_DAO::escapeString($groupTitle[$tableName]);
        $mysqlTableName = CRM_Utils_Type::escape($tableName, 'MysqlColumnNameOrAlias');
        $sql[$tableName] = "
SELECT '" . $title . "' as groupTitle, $columnNames
FROM   $mysqlTableName
WHERE  entity_id = %1
";
      }

      $cache[$activityTypeID] = array($result, $options, $sql);
    }
    return $cache[$activityTypeID];
  }

  /**
   * @param int $activityID
   *
   * @return null|string
   */
  public function getCreatedBy($activityID) {
    $query = "
SELECT c.display_name
FROM   civicrm_contact c,
       civicrm_log     l
WHERE  l.entity_table = 'civicrm_activity'
AND    l.entity_id    = %1
AND    l.modified_id  = c.id
LIMIT  1
";

    $params = array(1 => array($activityID, 'Integer'));
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * @param $string
   * @param bool $printReport
   * @param array $replaceString
   *
   * @return mixed
   */
  private function redact($string, $printReport = FALSE, $replaceString = array()) {
    if ($printReport) {
      return CRM_Utils_String::redaction($string, $replaceString);
    }
    elseif ($this->_isRedact) {
      $regexToReplaceString = CRM_Utils_String::regex($string, $this->_redactionRegexRules);
      return CRM_Utils_String::redaction($string, array_merge($this->_redactionStringRules, $regexToReplaceString));
    }
    return $string;
  }

  /**
   * @param int $clientID
   * @param int $caseID
   * @param string $activitySetName
   * @param array $params
   * @param CRM_Core_Form $form
   *
   * @return mixed
   */
  public static function getCaseReport($clientID, $caseID, $activitySetName, $params, $form) {

    $template = CRM_Core_Smarty::singleton();

    $template->assign('caseId', $caseID);
    $template->assign('clientID', $clientID);
    $template->assign('activitySetName', $activitySetName);

    if (!empty($params['is_redact'])) {
      $form->_isRedact = TRUE;
      $template->assign('_isRedact', 'true');
    }
    else {
      $form->_isRedact = FALSE;
      $template->assign('_isRedact', 'false');
    }

    // first get all case information
    $case = $form->caseInfo($clientID, $caseID);
    $template->assign_by_ref('case', $case);

    if ($params['include_activities'] == 1) {
      $template->assign('includeActivities', 'All');
    }
    else {
      $template->assign('includeActivities', 'Missing activities only');
    }

    $xml = $form->retrieve($case['caseTypeName']);

    $activitySetNames = CRM_Case_XMLProcessor_Process::activitySets($xml->ActivitySets);
    $pageTitle = CRM_Utils_Array::value($activitySetName, $activitySetNames);
    $template->assign('pageTitle', $pageTitle);

    if ($activitySetName) {
      $activityTypes = $form->getActivityTypes($xml, $activitySetName);
    }
    else {
      $activityTypes = CRM_Case_XMLProcessor::allActivityTypes();
    }

    if (!$activityTypes) {
      return FALSE;
    }

    // next get activity set Information
    $activitySet = array(
      'label' => $form->getActivitySetLabel($xml, $activitySetName),
      'includeActivities' => 'All',
      'redact' => 'false',
    );
    $template->assign_by_ref('activitySet', $activitySet);

    //now collect all the information about activities
    $activities = array();
    $form->getActivities($clientID, $caseID, $activityTypes, $activities);
    $template->assign_by_ref('activities', $activities);

    // now run the template
    $contents = $template->fetch('CRM/Case/XMLProcessor/Report.tpl');
    return $contents;
  }

  public static function printCaseReport() {
    $caseID = CRM_Utils_Request::retrieve('caseID', 'Positive');
    $clientID = CRM_Utils_Request::retrieve('cid', 'Positive');
    $activitySetName = CRM_Utils_Request::retrieve('asn', 'String');
    $isRedact = CRM_Utils_Request::retrieve('redact', 'Boolean');
    $includeActivities = CRM_Utils_Request::retrieve('all', 'Positive');
    $params = $otherRelationships = $globalGroupInfo = array();
    $report = new CRM_Case_XMLProcessor_Report($isRedact);
    if ($includeActivities) {
      $params['include_activities'] = 1;
    }

    if ($isRedact) {
      $params['is_redact'] = 1;
      $report->_redactionStringRules = array();
    }
    $template = CRM_Core_Smarty::singleton();

    //get case related relationships (Case Role)
    $caseRelationships = CRM_Case_BAO_Case::getCaseRoles($clientID, $caseID);
    $caseType = CRM_Case_BAO_Case::getCaseType($caseID, 'name');

    $xmlProcessor = new CRM_Case_XMLProcessor_Process();
    $caseRoles = $xmlProcessor->get($caseType, 'CaseRoles');
    foreach ($caseRelationships as $key => & $value) {
      if (!empty($caseRoles[$value['relation_type']])) {
        unset($caseRoles[$value['relation_type']]);
      }
      if ($isRedact) {
        if (!array_key_exists($value['name'], $report->_redactionStringRules)) {
          $report->_redactionStringRules = CRM_Utils_Array::crmArrayMerge($report->_redactionStringRules,
            array($value['name'] => 'name_' . rand(10000, 100000))
          );
        }
        $value['name'] = $report->redact($value['name'], TRUE, $report->_redactionStringRules);
        if (!empty($value['email']) &&
          !array_key_exists($value['email'], $report->_redactionStringRules)
        ) {
          $report->_redactionStringRules = CRM_Utils_Array::crmArrayMerge($report->_redactionStringRules,
            array($value['email'] => 'email_' . rand(10000, 100000))
          );
        }

        $value['email'] = $report->redact($value['email'], TRUE, $report->_redactionStringRules);

        if (!empty($value['phone']) &&
          !array_key_exists($value['phone'], $report->_redactionStringRules)
        ) {
          $report->_redactionStringRules = CRM_Utils_Array::crmArrayMerge($report->_redactionStringRules,
            array($value['phone'] => 'phone_' . rand(10000, 100000))
          );
        }
        $value['phone'] = $report->redact($value['phone'], TRUE, $report->_redactionStringRules);
      }
    }

    $caseRoles['client'] = CRM_Case_BAO_Case::getContactNames($caseID);
    if ($isRedact) {
      foreach ($caseRoles['client'] as &$client) {
        if (!array_key_exists(CRM_Utils_Array::value('sort_name', $client), $report->_redactionStringRules)) {

          $report->_redactionStringRules = CRM_Utils_Array::crmArrayMerge($report->_redactionStringRules,
            array(CRM_Utils_Array::value('sort_name', $client) => 'name_' . rand(10000, 100000))
          );
        }
        if (!array_key_exists(CRM_Utils_Array::value('display_name', $client), $report->_redactionStringRules)) {
          $report->_redactionStringRules[CRM_Utils_Array::value('display_name', $client)] = $report->_redactionStringRules[CRM_Utils_Array::value('sort_name', $client)];
        }
        $client['sort_name'] = $report->redact(CRM_Utils_Array::value('sort_name', $client), TRUE, $report->_redactionStringRules);
        if (!empty($client['email']) &&
          !array_key_exists($client['email'], $report->_redactionStringRules)
        ) {
          $report->_redactionStringRules = CRM_Utils_Array::crmArrayMerge($report->_redactionStringRules,
            array($client['email'] => 'email_' . rand(10000, 100000))
          );
        }
        $client['email'] = $report->redact(CRM_Utils_Array::value('email', $client), TRUE, $report->_redactionStringRules);

        if (!empty($client['phone']) &&
          !array_key_exists($client['phone'], $report->_redactionStringRules)
        ) {
          $report->_redactionStringRules = CRM_Utils_Array::crmArrayMerge($report->_redactionStringRules,
            array($client['phone'] => 'phone_' . rand(10000, 100000))
          );
        }
        $client['phone'] = $report->redact(CRM_Utils_Array::value('phone', $client), TRUE, $report->_redactionStringRules);
      }
    }
    // Retrieve ALL client relationships
    $relClient = CRM_Contact_BAO_Relationship::getRelationship($clientID,
      CRM_Contact_BAO_Relationship::CURRENT,
      0, 0, 0, NULL, NULL, FALSE
    );
    foreach ($relClient as $r) {
      if ($isRedact) {
        if (!array_key_exists($r['name'], $report->_redactionStringRules)) {
          $report->_redactionStringRules = CRM_Utils_Array::crmArrayMerge($report->_redactionStringRules,
            array($r['name'] => 'name_' . rand(10000, 100000))
          );
        }
        if (!array_key_exists($r['display_name'], $report->_redactionStringRules)) {
          $report->_redactionStringRules[$r['display_name']] = $report->_redactionStringRules[$r['name']];
        }
        $r['name'] = $report->redact($r['name'], TRUE, $report->_redactionStringRules);

        if (!empty($r['phone']) &&
          !array_key_exists($r['phone'], $report->_redactionStringRules)
        ) {
          $report->_redactionStringRules = CRM_Utils_Array::crmArrayMerge($report->_redactionStringRules,
            array($r['phone'] => 'phone_' . rand(10000, 100000))
          );
        }
        $r['phone'] = $report->redact($r['phone'], TRUE, $report->_redactionStringRules);

        if (!empty($r['email']) &&
          !array_key_exists($r['email'], $report->_redactionStringRules)
        ) {
          $report->_redactionStringRules = CRM_Utils_Array::crmArrayMerge($report->_redactionStringRules,
            array($r['email'] => 'email_' . rand(10000, 100000))
          );
        }
        $r['email'] = $report->redact($r['email'], TRUE, $report->_redactionStringRules);
      }
      if (!array_key_exists($r['id'], $caseRelationships)) {
        $otherRelationships[] = $r;
      }
    }

    // Now global contact list that appears on all cases.
    $relGlobal = CRM_Case_BAO_Case::getGlobalContacts($globalGroupInfo);
    foreach ($relGlobal as & $r) {
      if ($isRedact) {
        if (!array_key_exists($r['sort_name'], $report->_redactionStringRules)) {
          $report->_redactionStringRules = CRM_Utils_Array::crmArrayMerge($report->_redactionStringRules,
            array($r['sort_name'] => 'name_' . rand(10000, 100000))
          );
        }
        if (!array_key_exists($r['display_name'], $report->_redactionStringRules)) {
          $report->_redactionStringRules[$r['display_name']] = $report->_redactionStringRules[$r['sort_name']];
        }

        $r['sort_name'] = $report->redact($r['sort_name'], TRUE, $report->_redactionStringRules);
        if (!empty($r['phone']) &&
          !array_key_exists($r['phone'], $report->_redactionStringRules)
        ) {
          $report->_redactionStringRules = CRM_Utils_Array::crmArrayMerge($report->_redactionStringRules,
            array($r['phone'] => 'phone_' . rand(10000, 100000))
          );
        }
        $r['phone'] = $report->redact($r['phone'], TRUE, $report->_redactionStringRules);

        if (!empty($r['email']) &&
          !array_key_exists($r['email'], $report->_redactionStringRules)
        ) {
          $report->_redactionStringRules = CRM_Utils_Array::crmArrayMerge($report->_redactionStringRules,
            array($r['email'] => 'email_' . rand(10000, 100000))
          );
        }
        $r['email'] = $report->redact($r['email'], TRUE, $report->_redactionStringRules);
      }
    }

    // Retrieve custom values for cases.
    $customValues = CRM_Core_BAO_CustomValueTable::getEntityValues($caseID, 'Case');
    $extends = array('case');
    $groupTree = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, NULL, $extends);
    $caseCustomFields = array();
    foreach ($groupTree as $gid => $group_values) {
      foreach ($group_values['fields'] as $id => $field_values) {
        if (array_key_exists($id, $customValues)) {
          $caseCustomFields[$gid]['title'] = $group_values['title'];
          $caseCustomFields[$gid]['values'][$id] = array(
            'label' => $field_values['label'],
            'value' => $customValues[$id],
          );
        }
      }
    }
    $template->assign('caseRelationships', $caseRelationships);
    $template->assign('caseRoles', $caseRoles);
    $template->assign('otherRelationships', $otherRelationships);
    $template->assign('globalRelationships', $relGlobal);
    $template->assign('globalGroupInfo', $globalGroupInfo);
    $template->assign('caseCustomFields', $caseCustomFields);
    $contents = self::getCaseReport($clientID,
      $caseID,
      $activitySetName,
      $params,
      $report
    );
    $printReport = CRM_Case_Audit_Audit::run($contents, $clientID, $caseID, TRUE);
    echo $printReport;
    CRM_Utils_System::civiExit();
  }

}
