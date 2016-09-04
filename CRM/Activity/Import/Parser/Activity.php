<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */


/**
 * Class to parse activity csv files.
 */
class CRM_Activity_Import_Parser_Activity extends CRM_Activity_Import_Parser {

  protected $_mapperKeys;

  private $_contactIdIndex;
  private $_activityTypeIndex;
  private $_activityLabelIndex;
  private $_activityDateIndex;

  /**
   * Array of successfully imported activity id's
   *
   * @array
   */
  protected $_newActivity;

  /**
   * Class constructor.
   *
   * @param array $mapperKeys
   * @param int $mapperLocType
   * @param int $mapperPhoneType
   */
  public function __construct(&$mapperKeys, $mapperLocType = NULL, $mapperPhoneType = NULL) {
    parent::__construct();
    $this->_mapperKeys = &$mapperKeys;
  }

  /**
   * Function of undocumented functionality required by the interface.
   */
  protected function fini() {}

  /**
   * The initializer code, called before the processing.
   */
  public function init() {
    $activityContact = CRM_Activity_BAO_ActivityContact::import();
    $activityTarget['target_contact_id'] = $activityContact['contact_id'];
    $fields = array_merge(CRM_Activity_BAO_Activity::importableFields(),
      $activityTarget
    );

    $fields = array_merge($fields, array(
      'source_contact_id' => array(
        'title' => ts('Source Contact'),
        'headerPattern' => '/Source.Contact?/i',
      ),
      'activity_label' => array(
        'title' => ts('Activity Type Label'),
        'headerPattern' => '/(activity.)?type label?/i',
      ),
    ));

    foreach ($fields as $name => $field) {
      $field['type'] = CRM_Utils_Array::value('type', $field, CRM_Utils_Type::T_INT);
      $field['dataPattern'] = CRM_Utils_Array::value('dataPattern', $field, '//');
      $field['headerPattern'] = CRM_Utils_Array::value('headerPattern', $field, '//');
      $this->addField($name, $field['title'], $field['type'], $field['headerPattern'], $field['dataPattern']);
    }

    $this->_newActivity = array();

    $this->setActiveFields($this->_mapperKeys);

    // FIXME: we should do this in one place together with Form/MapField.php
    $this->_contactIdIndex = -1;
    $this->_activityTypeIndex = -1;
    $this->_activityLabelIndex = -1;
    $this->_activityDateIndex = -1;

    $index = 0;
    foreach ($this->_mapperKeys as $key) {
      switch ($key) {
        case 'target_contact_id':
        case 'external_identifier':
          $this->_contactIdIndex = $index;
          break;

        case 'activity_label':
          $this->_activityLabelIndex = $index;
          break;

        case 'activity_type_id':
          $this->_activityTypeIndex = $index;
          break;

        case 'activity_date_time':
          $this->_activityDateIndex = $index;
          break;
      }
      $index++;
    }
  }

  /**
   * Handle the values in mapField mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return bool
   */
  public function mapField(&$values) {
    return CRM_Import_Parser::VALID;
  }

  /**
   * Handle the values in preview mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return bool
   *   the result of this processing
   */
  public function preview(&$values) {
    return $this->summary($values);
  }

  /**
   * Handle the values in summary mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return bool
   *   the result of this processing
   */
  public function summary(&$values) {
    $erroneousField = NULL;
    $this->setActiveFieldValues($values, $erroneousField);
    $index = -1;

    if ($this->_activityTypeIndex > -1 && $this->_activityLabelIndex > -1) {
      array_unshift($values, ts('Please select either Activity Type ID OR Activity Type Label.'));
      return CRM_Import_Parser::ERROR;
    }
    elseif ($this->_activityLabelIndex > -1) {
      $index = $this->_activityLabelIndex;
    }
    elseif ($this->_activityTypeIndex > -1) {
      $index = $this->_activityTypeIndex;
    }

    if ($index < 0 or $this->_activityDateIndex < 0) {
      $errorRequired = TRUE;
    }
    else {
      $errorRequired = !CRM_Utils_Array::value($index, $values) || !CRM_Utils_Array::value($this->_activityDateIndex, $values);
    }

    if ($errorRequired) {
      array_unshift($values, ts('Missing required fields'));
      return CRM_Import_Parser::ERROR;
    }

    $params = &$this->getActiveFieldParams();

    $errorMessage = NULL;

    // For date-Formats
    $session = CRM_Core_Session::singleton();
    $dateType = $session->get('dateTypes');
    if (!isset($params['source_contact_id'])) {
      $params['source_contact_id'] = $session->get('userID');
    }
    foreach ($params as $key => $val) {
      if ($key == 'activity_date_time') {
        if ($val) {
          $dateValue = CRM_Utils_Date::formatDate($val, $dateType);
          if ($dateValue) {
            $params[$key] = $dateValue;
          }
          else {
            CRM_Contact_Import_Parser_Contact::addToErrorMsg('Activity date', $errorMessage);
          }
        }
      }
      elseif ($key == 'activity_engagement_level' && $val &&
        !CRM_Utils_Rule::positiveInteger($val)
      ) {
        CRM_Contact_Import_Parser_Contact::addToErrorMsg('Activity Engagement Index', $errorMessage);
      }
    }
    // Date-Format part ends.

    // Checking error in custom data.
    $params['contact_type'] = isset($this->_contactType) ? $this->_contactType : 'Activity';

    CRM_Contact_Import_Parser_Contact::isErrorInCustomData($params, $errorMessage);

    if ($errorMessage) {
      $tempMsg = "Invalid value for field(s) : $errorMessage";
      array_unshift($values, $tempMsg);
      $errorMessage = NULL;
      return CRM_Import_Parser::ERROR;
    }

    return CRM_Import_Parser::VALID;
  }

  /**
   * Handle the values in import mode.
   *
   * @param int $onDuplicate
   *   The code for what action to take on duplicates.
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return bool
   *   the result of this processing
   */
  public function import($onDuplicate, &$values) {
    // First make sure this is a valid line
    $response = $this->summary($values);

    if ($response != CRM_Import_Parser::VALID) {
      return $response;
    }
    $params = &$this->getActiveFieldParams();
    $activityLabel = array_search('activity_label', $this->_mapperKeys);
    if ($activityLabel) {
      $params = array_merge($params, array('activity_label' => $values[$activityLabel]));
    }
    // For date-Formats.
    $session = CRM_Core_Session::singleton();
    $dateType = $session->get('dateTypes');
    if (!isset($params['source_contact_id'])) {
      $params['source_contact_id'] = $session->get('userID');
    }

    $customFields = CRM_Core_BAO_CustomField::getFields(CRM_Utils_Array::value('contact_type', $params));

    foreach ($params as $key => $val) {
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
        if ($key == 'activity_date_time' && $val) {
          $params[$key] = CRM_Utils_Date::formatDate($val, $dateType);
        }
        elseif (!empty($customFields[$customFieldID]) && $customFields[$customFieldID]['data_type'] == 'Date') {
          CRM_Contact_Import_Parser_Contact::formatCustomDate($params, $params, $dateType, $key);
        }
        elseif (!empty($customFields[$customFieldID]) && $customFields[$customFieldID]['data_type'] == 'Boolean') {
          $params[$key] = CRM_Utils_String::strtoboolstr($val);
        }
      }
      elseif ($key == 'activity_date_time') {
        $params[$key] = CRM_Utils_Date::formatDate($val, $dateType);
      }
      elseif ($key == 'activity_subject') {
        $params['subject'] = $val;
      }
    }
    // Date-Format part ends.
    require_once 'CRM/Utils/DeprecatedUtils.php';
    $formatError = _civicrm_api3_deprecated_activity_formatted_param($params, $params, TRUE);

    if ($formatError) {
      array_unshift($values, $formatError['error_message']);
      return CRM_Import_Parser::ERROR;
    }

    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
      NULL,
      'Activity'
    );

    if ($this->_contactIdIndex < 0) {

      // Retrieve contact id using contact dedupe rule.
      // Since we are supporting only individual's activity import.
      $params['contact_type'] = 'Individual';
      $params['version'] = 3;
      $error = _civicrm_api3_deprecated_duplicate_formatted_contact($params);

      if (CRM_Core_Error::isAPIError($error, CRM_Core_ERROR::DUPLICATE_CONTACT)) {
        $matchedIDs = explode(',', $error['error_message']['params'][0]);
        if (count($matchedIDs) > 1) {
          array_unshift($values, 'Multiple matching contact records detected for this row. The activity was not imported');
          return CRM_Import_Parser::ERROR;
        }
        else {
          $cid = $matchedIDs[0];
          $params['target_contact_id'] = $cid;
          $params['version'] = 3;
          $newActivity = civicrm_api('activity', 'create', $params);
          if (!empty($newActivity['is_error'])) {
            array_unshift($values, $newActivity['error_message']);
            return CRM_Import_Parser::ERROR;
          }

          $this->_newActivity[] = $newActivity['id'];
          return CRM_Import_Parser::VALID;
        }
      }
      else {
        // Using new Dedupe rule.
        $ruleParams = array(
          'contact_type' => 'Individual',
          'used' => 'Unsupervised',
        );
        $fieldsArray = CRM_Dedupe_BAO_Rule::dedupeRuleFields($ruleParams);

        $disp = NULL;
        foreach ($fieldsArray as $value) {
          if (array_key_exists(trim($value), $params)) {
            $paramValue = $params[trim($value)];
            if (is_array($paramValue)) {
              $disp .= $params[trim($value)][0][trim($value)] . " ";
            }
            else {
              $disp .= $params[trim($value)] . " ";
            }
          }
        }

        if (!empty($params['external_identifier'])) {
          if ($disp) {
            $disp .= "AND {$params['external_identifier']}";
          }
          else {
            $disp = $params['external_identifier'];
          }
        }

        array_unshift($values, 'No matching Contact found for (' . $disp . ')');
        return CRM_Import_Parser::ERROR;
      }
    }
    else {
      if (!empty($params['external_identifier'])) {
        $targetContactId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $params['external_identifier'], 'id', 'external_identifier'
        );

        if (!empty($params['target_contact_id']) &&
          $params['target_contact_id'] != $targetContactId
        ) {
          array_unshift($values, 'Mismatch of External ID:' . $params['external_identifier'] . ' and Contact Id:' . $params['target_contact_id']);
          return CRM_Import_Parser::ERROR;
        }
        elseif ($targetContactId) {
          $params['target_contact_id'] = $targetContactId;
        }
        else {
          array_unshift($values, 'No Matching Contact for External ID:' . $params['external_identifier']);
          return CRM_Import_Parser::ERROR;
        }
      }

      $params['version'] = 3;
      $newActivity = civicrm_api('activity', 'create', $params);
      if (!empty($newActivity['is_error'])) {
        array_unshift($values, $newActivity['error_message']);
        return CRM_Import_Parser::ERROR;
      }

      $this->_newActivity[] = $newActivity['id'];
      return CRM_Import_Parser::VALID;
    }
  }

}
