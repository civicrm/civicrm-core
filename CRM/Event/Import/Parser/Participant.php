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

require_once 'CRM/Utils/DeprecatedUtils.php';

/**
 * class to parse membership csv files
 */
class CRM_Event_Import_Parser_Participant extends CRM_Event_Import_Parser {
  protected $_mapperKeys;

  private $_contactIdIndex;

  //private $_totalAmountIndex;

  private $_eventIndex;
  private $_participantStatusIndex;
  private $_participantRoleIndex;
  private $_eventTitleIndex;

  /**
   * Array of successfully imported participants id's
   *
   * @array
   */
  protected $_newParticipants;

  /**
   * class constructor
   */
  function __construct(&$mapperKeys, $mapperLocType = NULL, $mapperPhoneType = NULL) {
    parent::__construct();
    $this->_mapperKeys = &$mapperKeys;
  }

  /**
   * the initializer code, called before the processing
   *
   * @return void
   * @access public
   */
  function init() {
    $fields = CRM_Event_BAO_Participant::importableFields($this->_contactType, FALSE);
    $fields['event_id']['title'] = 'Event ID';
    $eventfields = &CRM_Event_BAO_Event::fields();
    $fields['event_title'] = $eventfields['event_title'];

    foreach ($fields as $name => $field) {
      $field['type'] = CRM_Utils_Array::value('type', $field, CRM_Utils_Type::T_INT);
      $field['dataPattern'] = CRM_Utils_Array::value('dataPattern', $field, '//');
      $field['headerPattern'] = CRM_Utils_Array::value('headerPattern', $field, '//');
      $this->addField($name, $field['title'], $field['type'], $field['headerPattern'], $field['dataPattern']);
    }

    $this->_newParticipants = array();
    $this->setActiveFields($this->_mapperKeys);

    // FIXME: we should do this in one place together with Form/MapField.php
    $this->_contactIdIndex = -1;
    $this->_eventIndex = -1;
    $this->_participantStatusIndex = -1;
    $this->_participantRoleIndex = -1;
    $this->_eventTitleIndex = -1;

    $index = 0;
    foreach ($this->_mapperKeys as $key) {

      switch ($key) {
        case 'participant_contact_id':
          $this->_contactIdIndex = $index;
          break;

        case 'event_id':
          $this->_eventIndex = $index;
          break;

        case 'participant_status':
        case 'participant_status_id':
          $this->_participantStatusIndex = $index;
          break;

        case 'participant_role_id':
          $this->_participantRoleIndex = $index;
          break;

        case 'event_title':
          $this->_eventTitleIndex = $index;
          break;
      }
      $index++;
    }
  }

  /**
   * handle the values in mapField mode
   *
   * @param array $values the array of values belonging to this line
   *
   * @return boolean
   * @access public
   */
  function mapField(&$values) {
    return CRM_Import_Parser::VALID;
  }

  /**
   * handle the values in preview mode
   *
   * @param array $values the array of values belonging to this line
   *
   * @return boolean      the result of this processing
   * @access public
   */
  function preview(&$values) {
    return $this->summary($values);
  }

  /**
   * handle the values in summary mode
   *
   * @param array $values the array of values belonging to this line
   *
   * @return boolean      the result of this processing
   * @access public
   */
  function summary(&$values) {
    $erroneousField = NULL;

    $response      = $this->setActiveFieldValues($values, $erroneousField);
    $errorRequired = FALSE;
    $index         = -1;

    if ($this->_eventIndex > -1 && $this->_eventTitleIndex > -1) {
      array_unshift($values, ts('Select either EventID OR Event Title'));
      return CRM_Import_Parser::ERROR;
    }
    elseif ($this->_eventTitleIndex > -1) {
      $index = $this->_eventTitleIndex;
    }
    elseif ($this->_eventIndex > -1) {
      $index = $this->_eventIndex;
    }
    $params = &$this->getActiveFieldParams();

    if (!(($index < 0) || ($this->_participantStatusIndex < 0))) {
      $errorRequired = !CRM_Utils_Array::value($this->_participantStatusIndex, $values);
      if (empty($params['event_id']) && empty($params['event_title'])) {
        CRM_Contact_Import_Parser_Contact::addToErrorMsg('Event', $missingField);
      }
      if (empty($params['participant_status_id'])) {
        CRM_Contact_Import_Parser_Contact::addToErrorMsg('Participant Status', $missingField);
      }
    }
    else {
      $errorRequired = TRUE;
      $missingField = NULL;
      if ($index < 0) {
        CRM_Contact_Import_Parser_Contact::addToErrorMsg('Event', $missingField);
      }
      if ($this->_participantStatusIndex < 0) {
        CRM_Contact_Import_Parser_Contact::addToErrorMsg('Participant Status', $missingField);
      }
    }

    if ($errorRequired) {
      array_unshift($values, ts('Missing required field(s) :') . $missingField);
      return CRM_Import_Parser::ERROR;
    }

    $errorMessage = NULL;

    //for date-Formats
    $session = CRM_Core_Session::singleton();
    $dateType = $session->get('dateTypes');

    foreach ($params as $key => $val) {
      if ($val && ($key == 'participant_register_date')) {
        if ($dateValue = CRM_Utils_Date::formatDate($params[$key], $dateType)) {
          $params[$key] = $dateValue;
        }
        else {
          CRM_Contact_Import_Parser_Contact::addToErrorMsg('Register Date', $errorMessage);
        }
      }
      elseif ($val && ($key == 'participant_role_id' || $key == 'participant_role')) {
        $roleIDs = CRM_Event_PseudoConstant::participantRole();
        $val = explode(',', $val);
        if ($key == 'participant_role_id') {
          foreach ($val as $role) {
            if (!in_array(trim($role), array_keys($roleIDs))) {
              CRM_Contact_Import_Parser_Contact::addToErrorMsg('Participant Role Id', $errorMessage);
              break;
            }
          }
        }
        else {
          foreach ($val as $role) {
            if (!CRM_Contact_Import_Parser_Contact::in_value(trim($role), $roleIDs)) {
                CRM_Contact_Import_Parser_Contact::addToErrorMsg('Participant Role', $errorMessage);
              break;
            }
          }
        }
      }
      elseif ($val && (($key == 'participant_status_id') || ($key == 'participant_status'))) {
        $statusIDs = CRM_Event_PseudoConstant::participantStatus();
        if ($key == 'participant_status_id') {
          if (!in_array(trim($val), array_keys($statusIDs))) {
            CRM_Contact_Import_Parser_Contact::addToErrorMsg('Participant Status Id', $errorMessage);
            break;
          }
        }
        elseif (!CRM_Contact_Import_Parser_Contact::in_value($val, $statusIDs)) {
          CRM_Contact_Import_Parser_Contact::addToErrorMsg('Participant Status', $errorMessage);
          break;
        }
      }
    }
    //date-Format part ends

    $params['contact_type'] = 'Participant';
    //checking error in custom data
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
   * handle the values in import mode
   *
   * @param int $onDuplicate the code for what action to take on duplicates
   * @param array $values the array of values belonging to this line
   *
   * @return boolean      the result of this processing
   * @access public
   */
  function import($onDuplicate, &$values) {

    // first make sure this is a valid line
    $response = $this->summary($values);
    if ($response != CRM_Import_Parser::VALID) {
      return $response;
    }
    $params       = &$this->getActiveFieldParams();
    $session      = CRM_Core_Session::singleton();
    $dateType     = $session->get('dateTypes');
    $formatted    = array('version' => 3);
    $customFields = CRM_Core_BAO_CustomField::getFields(CRM_Utils_Array::value('contact_type', $params));

    // don't add to recent items, CRM-4399
    $formatted['skipRecentView'] = TRUE;

    foreach ($params as $key => $val) {
      if ($val) {
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
          if ($customFields[$customFieldID]['data_type'] == 'Date') {
            CRM_Contact_Import_Parser_Contact::formatCustomDate($params, $formatted, $dateType, $key);
            unset($params[$key]);
          }
          elseif ($customFields[$customFieldID]['data_type'] == 'Boolean') {
            $params[$key] = CRM_Utils_String::strtoboolstr($val);
          }
        }
        if($key == 'participant_register_date') {
          CRM_Utils_Date::convertToDefaultDate($params, $dateType, 'participant_register_date');
          $formatted['participant_register_date'] = CRM_Utils_Date::processDate($params['participant_register_date']);
        }
      }
    }

    if (!(!empty($params['participant_role_id']) || !empty($params['participant_role']))) {
      if (!empty($params['event_id'])) {
        $params['participant_role_id'] = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $params['event_id'], 'default_role_id');
      }
      else {
        $eventTitle = $params['event_title'];
        $qParams = array();
        $dao = new CRM_Core_DAO();
        $params['participant_role_id'] = $dao->singleValueQuery("SELECT default_role_id FROM civicrm_event WHERE title = '$eventTitle' ",
          $qParams
        );
      }
    }

    //date-Format part ends
    static $indieFields = NULL;
    if ($indieFields == NULL) {
      $indieFields = CRM_Event_BAO_Participant::import();
    }

    $formatValues = array();
    foreach ($params as $key => $field) {
      if ($field == NULL || $field === '') {
        continue;
      }

      $formatValues[$key] = $field;
    }

    $formatError = _civicrm_api3_deprecated_participant_formatted_param($formatValues, $formatted, TRUE);

    if ($formatError) {
      array_unshift($values, $formatError['error_message']);
      return CRM_Import_Parser::ERROR;
    }

    if (!CRM_Utils_Rule::integer($formatted['event_id'])) {
      array_unshift($values, ts('Invalid value for Event ID'));
      return CRM_Import_Parser::ERROR;
    }

    if ($onDuplicate != CRM_Import_Parser::DUPLICATE_UPDATE) {
      $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($formatted,
        CRM_Core_DAO::$_nullObject,
        NULL,
        'Participant'
      );
    }
    else {
      if ($formatValues['participant_id']) {
        $dao = new CRM_Event_BAO_Participant();
        $dao->id = $formatValues['participant_id'];

        $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($formatted,
          CRM_Core_DAO::$_nullObject,
          $formatValues['participant_id'],
          'Participant'
        );
        if ($dao->find(TRUE)) {
          $ids = array(
            'participant' => $formatValues['participant_id'],
            'userId' => $session->get('userID'),
          );
          $participantValues = array();
          //@todo calling api functions directly is not supported
          $newParticipant = _civicrm_api3_deprecated_participant_check_params($formatted, $participantValues, FALSE);
          if ($newParticipant['error_message']) {
            array_unshift($values, $newParticipant['error_message']);
            return CRM_Import_Parser::ERROR;
          }
          $newParticipant = CRM_Event_BAO_Participant::create($formatted, $ids);
          if (!empty($formatted['fee_level'])) {
            $otherParams = array(
              'fee_label' => $formatted['fee_level'],
              'event_id' => $newParticipant->event_id
            );
            CRM_Price_BAO_LineItem::syncLineItems($newParticipant->id, 'civicrm_participant', $newParticipant->fee_amount, $otherParams);
          }

          $this->_newParticipant[] = $newParticipant->id;
          return CRM_Import_Parser::VALID;
        }
        else {
          array_unshift($values, 'Matching Participant record not found for Participant ID ' . $formatValues['participant_id'] . '. Row was skipped.');
          return CRM_Import_Parser::ERROR;
        }
      }
    }

    if ($this->_contactIdIndex < 0) {

      //retrieve contact id using contact dedupe rule
      $formatValues['contact_type'] = $this->_contactType;
      $formatValues['version'] = 3;
      $error = _civicrm_api3_deprecated_check_contact_dedupe($formatValues);

      if (CRM_Core_Error::isAPIError($error, CRM_Core_ERROR::DUPLICATE_CONTACT)) {
        $matchedIDs = explode(',', $error['error_message']['params'][0]);
        if (count($matchedIDs) >= 1) {
          foreach ($matchedIDs as $contactId) {
            $formatted['contact_id'] = $contactId;
            $formatted['version'] = 3;
            $newParticipant = _civicrm_api3_deprecated_create_participant_formatted($formatted, $onDuplicate);
          }
        }
      }
      else {
        // Using new Dedupe rule.
        $ruleParams = array(
          'contact_type' => $this->_contactType,
          'used'         => 'Unsupervised',
        );
        $fieldsArray = CRM_Dedupe_BAO_Rule::dedupeRuleFields($ruleParams);

        $disp = '';
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
      if (!empty($formatValues['external_identifier'])) {
        $checkCid = new CRM_Contact_DAO_Contact();
        $checkCid->external_identifier = $formatValues['external_identifier'];
        $checkCid->find(TRUE);
        if ($checkCid->id != $formatted['contact_id']) {
          array_unshift($values, 'Mismatch of External identifier :' . $formatValues['external_identifier'] . ' and Contact Id:' . $formatted['contact_id']);
          return CRM_Import_Parser::ERROR;
        }
      }

      $newParticipant = _civicrm_api3_deprecated_create_participant_formatted($formatted, $onDuplicate);
    }

    if (is_array($newParticipant) && civicrm_error($newParticipant)) {
      if ($onDuplicate == CRM_Import_Parser::DUPLICATE_SKIP) {

        $contactID     = CRM_Utils_Array::value('contactID', $newParticipant);
        $participantID = CRM_Utils_Array::value('participantID', $newParticipant);
        $url           = CRM_Utils_System::url('civicrm/contact/view/participant',
          "reset=1&id={$participantID}&cid={$contactID}&action=view", TRUE
        );
        if (is_array($newParticipant['error_message']) &&
          ($participantID == $newParticipant['error_message']['params'][0])
        ) {
          array_unshift($values, $url);
          return CRM_Import_Parser::DUPLICATE;
        }
        elseif ($newParticipant['error_message']) {
          array_unshift($values, $newParticipant['error_message']);
          return CRM_Import_Parser::ERROR;
        }
        return CRM_Import_Parser::ERROR;
      }
    }

    if (!(is_array($newParticipant) && civicrm_error($newParticipant))) {
      $this->_newParticipants[] = CRM_Utils_Array::value('id', $newParticipant);
    }

    return CRM_Import_Parser::VALID;
  }

  /**
   * Get the array of successfully imported Participation ids
   *
   * @return array
   * @access public
   */
  function &getImportedParticipations() {
    return $this->_newParticipants;
  }

  /**
   * the initializer code, called before the processing
   *
   * @return void
   * @access public
   */
  function fini() {}
}

