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

require_once 'CRM/Utils/DeprecatedUtils.php';

/**
 * class to parse membership csv files
 */
class CRM_Event_Import_Parser_Participant extends CRM_Import_Parser {
  protected $_mapperKeys;

  private $_contactIdIndex;
  private $_eventIndex;
  private $_participantStatusIndex;
  private $_participantRoleIndex;
  private $_eventTitleIndex;

  /**
   * Array of successfully imported participants id's
   *
   * @var array
   */
  protected $_newParticipants;


  protected $_fileName;

  /**
   * Imported file size.
   *
   * @var int
   */
  protected $_fileSize;

  /**
   * Separator being used.
   *
   * @var string
   */
  protected $_separator;

  /**
   * Total number of lines in file.
   *
   * @var int
   */
  protected $_lineCount;

  /**
   * Whether the file has a column header or not
   *
   * @var bool
   */
  protected $_haveColumnHeader;

  /**
   * Class constructor.
   *
   * @param array $mapperKeys
   */
  public function __construct(&$mapperKeys = []) {
    parent::__construct();
    $this->_mapperKeys = &$mapperKeys;
  }

  /**
   * The initializer code, called before the processing.
   */
  public function init() {
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

    $this->_newParticipants = [];
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
    $this->setActiveFieldValues($values);
    $index = -1;

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
            if (!array_key_exists(trim($role), $roleIDs)) {
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
          if (!array_key_exists(trim($val), $statusIDs)) {
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

    // first make sure this is a valid line
    $response = $this->summary($values);
    if ($response != CRM_Import_Parser::VALID) {
      return $response;
    }
    $params = &$this->getActiveFieldParams();
    $session = CRM_Core_Session::singleton();
    $dateType = $session->get('dateTypes');
    $formatted = ['version' => 3];
    $customFields = CRM_Core_BAO_CustomField::getFields('Participant');

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
        if ($key == 'participant_register_date') {
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
        $params['participant_role_id'] = CRM_Core_DAO::singleValueQuery('SELECT default_role_id FROM civicrm_event WHERE title = %1', [
          1 => [$eventTitle, 'String'],
        ]);
      }
    }

    //date-Format part ends
    static $indieFields = NULL;
    if ($indieFields == NULL) {
      $indieFields = CRM_Event_BAO_Participant::import();
    }

    $formatValues = [];
    foreach ($params as $key => $field) {
      if ($field == NULL || $field === '') {
        continue;
      }

      $formatValues[$key] = $field;
    }

    $formatError = $this->formatValues($formatted, $formatValues);

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
        NULL,
        'Participant'
      );
    }
    else {
      if ($formatValues['participant_id']) {
        $dao = new CRM_Event_BAO_Participant();
        $dao->id = $formatValues['participant_id'];

        $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($formatted,
          $formatValues['participant_id'],
          'Participant'
        );
        if ($dao->find(TRUE)) {
          $ids = [
            'participant' => $formatValues['participant_id'],
            'userId' => $session->get('userID'),
          ];
          $participantValues = [];
          //@todo calling api functions directly is not supported
          $newParticipant = $this->deprecated_participant_check_params($formatted, $participantValues, FALSE);
          if ($newParticipant['error_message']) {
            array_unshift($values, $newParticipant['error_message']);
            return CRM_Import_Parser::ERROR;
          }
          $newParticipant = CRM_Event_BAO_Participant::create($formatted, $ids);
          if (!empty($formatted['fee_level'])) {
            $otherParams = [
              'fee_label' => $formatted['fee_level'],
              'event_id' => $newParticipant->event_id,
            ];
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
      $error = $this->checkContactDuplicate($formatValues);

      if (CRM_Core_Error::isAPIError($error, CRM_Core_ERROR::DUPLICATE_CONTACT)) {
        $matchedIDs = explode(',', $error['error_message']['params'][0]);
        if (count($matchedIDs) >= 1) {
          foreach ($matchedIDs as $contactId) {
            $formatted['contact_id'] = $contactId;
            $formatted['version'] = 3;
            $newParticipant = $this->deprecated_create_participant_formatted($formatted, $onDuplicate);
          }
        }
      }
      else {
        // Using new Dedupe rule.
        $ruleParams = [
          'contact_type' => $this->_contactType,
          'used' => 'Unsupervised',
        ];
        $fieldsArray = CRM_Dedupe_BAO_DedupeRule::dedupeRuleFields($ruleParams);

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
          array_unshift($values, 'Mismatch of External ID:' . $formatValues['external_identifier'] . ' and Contact Id:' . $formatted['contact_id']);
          return CRM_Import_Parser::ERROR;
        }
      }

      $newParticipant = $this->deprecated_create_participant_formatted($formatted, $onDuplicate);
    }

    if (is_array($newParticipant) && civicrm_error($newParticipant)) {
      if ($onDuplicate == CRM_Import_Parser::DUPLICATE_SKIP) {

        $contactID = $newParticipant['contactID'] ?? NULL;
        $participantID = $newParticipant['participantID'] ?? NULL;
        $url = CRM_Utils_System::url('civicrm/contact/view/participant',
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
      $this->_newParticipants[] = $newParticipant['id'] ?? NULL;
    }

    return CRM_Import_Parser::VALID;
  }

  /**
   * Get the array of successfully imported Participation ids.
   *
   * @return array
   */
  public function &getImportedParticipations() {
    return $this->_newParticipants;
  }

  /**
   * Format values
   *
   * @todo lots of tidy up needed here - very old function relocated.
   *
   * @param array $values
   * @param array $params
   *
   * @return array|null
   */
  protected function formatValues(&$values, $params) {
    $fields = CRM_Event_DAO_Participant::fields();
    _civicrm_api3_store_values($fields, $params, $values);

    $customFields = CRM_Core_BAO_CustomField::getFields('Participant', FALSE, FALSE, NULL, NULL, FALSE, FALSE, FALSE);

    foreach ($params as $key => $value) {
      // ignore empty values or empty arrays etc
      if (CRM_Utils_System::isNull($value)) {
        continue;
      }

      // Handling Custom Data
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
        $values[$key] = $value;
        $type = $customFields[$customFieldID]['html_type'];
        if (CRM_Core_BAO_CustomField::isSerialized($customFields[$customFieldID])) {
          $values[$key] = self::unserializeCustomValue($customFieldID, $value, $type);
        }
        elseif ($type == 'Select' || $type == 'Radio') {
          $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
          foreach ($customOption as $customFldID => $customValue) {
            $val = $customValue['value'] ?? NULL;
            $label = $customValue['label'] ?? NULL;
            $label = strtolower($label);
            $value = strtolower(trim($value));
            if (($value == $label) || ($value == strtolower($val))) {
              $values[$key] = $val;
            }
          }
        }
      }

      switch ($key) {
        case 'participant_contact_id':
          if (!CRM_Utils_Rule::integer($value)) {
            return civicrm_api3_create_error("contact_id not valid: $value");
          }
          if (!CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contact WHERE id = $value")) {
            return civicrm_api3_create_error("Invalid Contact ID: There is no contact record with contact_id = $value.");
          }
          $values['contact_id'] = $values['participant_contact_id'];
          unset($values['participant_contact_id']);
          break;

        case 'participant_register_date':
          if (!CRM_Utils_Rule::dateTime($value)) {
            return civicrm_api3_create_error("$key not a valid date: $value");
          }
          break;

        case 'event_title':
          $id = CRM_Core_DAO::getFieldValue("CRM_Event_DAO_Event", $value, 'id', 'title');
          $values['event_id'] = $id;
          break;

        case 'event_id':
          if (!CRM_Utils_Rule::integer($value)) {
            return civicrm_api3_create_error("Event ID is not valid: $value");
          }
          $svq = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_event WHERE id = %1', [
            1 => [$value, 'Integer'],
          ]);
          if (!$svq) {
            return civicrm_api3_create_error("Invalid Event ID: There is no event record with event_id = $value.");
          }
          break;

        case 'participant_status_id':
          if (!CRM_Utils_Rule::integer($value)) {
            return civicrm_api3_create_error("Event Status ID is not valid: $value");
          }
          break;

        case 'participant_status':
          $status = CRM_Event_PseudoConstant::participantStatus();
          $values['participant_status_id'] = CRM_Utils_Array::key($value, $status);
          break;

        case 'participant_role_id':
        case 'participant_role':
          $role = CRM_Event_PseudoConstant::participantRole();
          $participantRoles = explode(",", $value);
          foreach ($participantRoles as $k => $v) {
            $v = trim($v);
            if ($key == 'participant_role') {
              $participantRoles[$k] = CRM_Utils_Array::key($v, $role);
            }
            else {
              $participantRoles[$k] = $v;
            }
          }
          $values['role_id'] = implode(CRM_Core_DAO::VALUE_SEPARATOR, $participantRoles);
          unset($values[$key]);
          break;

        default:
          break;
      }
    }

    if (array_key_exists('participant_note', $params)) {
      $values['participant_note'] = $params['participant_note'];
    }

    // CRM_Event_BAO_Participant::create() handles register_date,
    // status_id and source. So, if $values contains
    // participant_register_date, participant_status_id or participant_source,
    // convert it to register_date, status_id or source
    $changes = [
      'participant_register_date' => 'register_date',
      'participant_source' => 'source',
      'participant_status_id' => 'status_id',
      'participant_role_id' => 'role_id',
      'participant_fee_level' => 'fee_level',
      'participant_fee_amount' => 'fee_amount',
      'participant_id' => 'id',
    ];

    foreach ($changes as $orgVal => $changeVal) {
      if (isset($values[$orgVal])) {
        $values[$changeVal] = $values[$orgVal];
        unset($values[$orgVal]);
      }
    }

    return NULL;
  }

  /**
   * @param array $params
   * @param $onDuplicate
   *
   * @return array|bool
   *   <type>
   * @throws \CiviCRM_API3_Exception
   * @deprecated - this is part of the import parser not the API & needs to be
   *   moved on out
   *
   */
  protected function deprecated_create_participant_formatted($params, $onDuplicate) {
    if ($onDuplicate != CRM_Import_Parser::DUPLICATE_NOCHECK) {
      CRM_Core_Error::reset();
      $error = $this->deprecated_participant_check_params($params, TRUE);
      if (civicrm_error($error)) {
        return $error;
      }
    }
    return civicrm_api3('Participant', 'create', $params);
  }

  /**
   * Formatting that was written a long time ago and may not make sense now.
   *
   * @param array $params
   *
   * @param bool $checkDuplicate
   *
   * @return array|bool
   */
  protected function deprecated_participant_check_params($params, $checkDuplicate = FALSE) {

    // check if participant id is valid or not
    if (!empty($params['id'])) {
      $participant = new CRM_Event_BAO_Participant();
      $participant->id = $params['id'];
      if (!$participant->find(TRUE)) {
        return civicrm_api3_create_error(ts('Participant  id is not valid'));
      }
    }

    // check if contact id is valid or not
    if (!empty($params['contact_id'])) {
      $contact = new CRM_Contact_BAO_Contact();
      $contact->id = $params['contact_id'];
      if (!$contact->find(TRUE)) {
        return civicrm_api3_create_error(ts('Contact id is not valid'));
      }
    }

    // check that event id is not an template
    if (!empty($params['event_id'])) {
      $isTemplate = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $params['event_id'], 'is_template');
      if (!empty($isTemplate)) {
        return civicrm_api3_create_error(ts('Event templates are not meant to be registered.'));
      }
    }

    $result = [];
    if ($checkDuplicate) {
      if (CRM_Event_BAO_Participant::checkDuplicate($params, $result)) {
        $participantID = array_pop($result);

        $error = CRM_Core_Error::createError("Found matching participant record.",
          CRM_Core_Error::DUPLICATE_PARTICIPANT,
          'Fatal', $participantID
        );

        return civicrm_api3_create_error($error->pop(),
          [
            'contactID' => $params['contact_id'],
            'participantID' => $participantID,
          ]
        );
      }
    }
    return TRUE;
  }

  /**
   * @param string $fileName
   * @param string $separator
   * @param $mapper
   * @param bool $skipColumnHeader
   * @param int $mode
   * @param int $contactType
   * @param int $onDuplicate
   *
   * @return mixed
   * @throws Exception
   */
  public function run(
    $fileName,
    $separator,
    $mapper,
    $skipColumnHeader = FALSE,
    $mode = self::MODE_PREVIEW,
    $contactType = self::CONTACT_INDIVIDUAL,
    $onDuplicate = self::DUPLICATE_SKIP
  ) {
    if (!is_array($fileName)) {
      throw new CRM_Core_Exception('Unable to determine import file');
    }
    $fileName = $fileName['name'];

    switch ($contactType) {
      case self::CONTACT_INDIVIDUAL:
        $this->_contactType = 'Individual';
        break;

      case self::CONTACT_HOUSEHOLD:
        $this->_contactType = 'Household';
        break;

      case self::CONTACT_ORGANIZATION:
        $this->_contactType = 'Organization';
    }

    $this->init();

    $this->_haveColumnHeader = $skipColumnHeader;

    $this->_separator = $separator;

    $fd = fopen($fileName, "r");
    if (!$fd) {
      return FALSE;
    }

    $this->_lineCount = 0;
    $this->_invalidRowCount = $this->_validCount = 0;
    $this->_totalCount = 0;

    $this->_errors = [];
    $this->_warnings = [];

    $this->_fileSize = number_format(filesize($fileName) / 1024.0, 2);

    if ($mode == self::MODE_MAPFIELD) {
      $this->_rows = [];
    }
    else {
      $this->_activeFieldCount = count($this->_activeFields);
    }

    while (!feof($fd)) {
      $this->_lineCount++;

      $values = fgetcsv($fd, 8192, $separator);
      if (!$values) {
        continue;
      }

      self::encloseScrub($values);

      // skip column header if we're not in mapfield mode
      if ($mode != self::MODE_MAPFIELD && $skipColumnHeader) {
        $skipColumnHeader = FALSE;
        continue;
      }

      /* trim whitespace around the values */

      $empty = TRUE;
      foreach ($values as $k => $v) {
        $values[$k] = trim($v, " \t\r\n");
      }

      if (CRM_Utils_System::isNull($values)) {
        continue;
      }

      $this->_totalCount++;

      if ($mode == self::MODE_MAPFIELD) {
        $returnCode = CRM_Import_Parser::VALID;
      }
      elseif ($mode == self::MODE_PREVIEW) {
        $returnCode = $this->preview($values);
      }
      elseif ($mode == self::MODE_SUMMARY) {
        $returnCode = $this->summary($values);
      }
      elseif ($mode == self::MODE_IMPORT) {
        $returnCode = $this->import($onDuplicate, $values);
      }
      else {
        $returnCode = self::ERROR;
      }

      // note that a line could be valid but still produce a warning
      if ($returnCode & self::VALID) {
        $this->_validCount++;
        if ($mode == self::MODE_MAPFIELD) {
          $this->_rows[] = $values;
          $this->_activeFieldCount = max($this->_activeFieldCount, count($values));
        }
      }

      if ($returnCode & self::ERROR) {
        $this->_invalidRowCount++;
        $recordNumber = $this->_lineCount;
        if ($this->_haveColumnHeader) {
          $recordNumber--;
        }
        array_unshift($values, $recordNumber);
        $this->_errors[] = $values;
      }

      if ($returnCode & self::DUPLICATE) {
        $this->_duplicateCount++;
        $recordNumber = $this->_lineCount;
        if ($this->_haveColumnHeader) {
          $recordNumber--;
        }
        array_unshift($values, $recordNumber);
        $this->_duplicates[] = $values;
        if ($onDuplicate != self::DUPLICATE_SKIP) {
          $this->_validCount++;
        }
      }

      // if we are done processing the maxNumber of lines, break
      if ($this->_maxLinesToProcess > 0 && $this->_validCount >= $this->_maxLinesToProcess) {
        break;
      }
    }

    fclose($fd);

    if ($mode == self::MODE_PREVIEW || $mode == self::MODE_IMPORT) {
      $customHeaders = $mapper;

      $customfields = CRM_Core_BAO_CustomField::getFields('Participant');
      foreach ($customHeaders as $key => $value) {
        if ($id = CRM_Core_BAO_CustomField::getKeyID($value)) {
          $customHeaders[$key] = $customfields[$id][0];
        }
      }

      if ($this->_invalidRowCount) {
        // removed view url for invlaid contacts
        $headers = array_merge([
          ts('Line Number'),
          ts('Reason'),
        ], $customHeaders);
        $this->_errorFileName = self::errorFileName(self::ERROR);
        self::exportCSV($this->_errorFileName, $headers, $this->_errors);
      }
      if ($this->_duplicateCount) {
        $headers = array_merge([
          ts('Line Number'),
          ts('View Participant URL'),
        ], $customHeaders);

        $this->_duplicateFileName = self::errorFileName(self::DUPLICATE);
        self::exportCSV($this->_duplicateFileName, $headers, $this->_duplicates);
      }
    }
  }

  /**
   * Given a list of the importable field keys that the user has selected
   * set the active fields array to this list
   *
   * @param array $fieldKeys mapped array of values
   *
   * @return void
   */
  public function setActiveFields($fieldKeys) {
    $this->_activeFieldCount = count($fieldKeys);
    foreach ($fieldKeys as $key) {
      if (empty($this->_fields[$key])) {
        $this->_activeFields[] = new CRM_Event_Import_Field('', ts('- do not import -'));
      }
      else {
        $this->_activeFields[] = clone($this->_fields[$key]);
      }
    }
  }

  /**
   * @param string $name
   * @param $title
   * @param int $type
   * @param string $headerPattern
   * @param string $dataPattern
   */
  public function addField($name, $title, $type = CRM_Utils_Type::T_INT, $headerPattern = '//', $dataPattern = '//') {
    if (empty($name)) {
      $this->_fields['doNotImport'] = new CRM_Event_Import_Field($name, $title, $type, $headerPattern, $dataPattern);
    }
    else {

      //$tempField = CRM_Contact_BAO_Contact::importableFields('Individual', null );
      $tempField = CRM_Contact_BAO_Contact::importableFields('All', NULL);
      if (!array_key_exists($name, $tempField)) {
        $this->_fields[$name] = new CRM_Event_Import_Field($name, $title, $type, $headerPattern, $dataPattern);
      }
      else {
        $this->_fields[$name] = new CRM_Contact_Import_Field($name, $title, $type, $headerPattern, $dataPattern,
          CRM_Utils_Array::value('hasLocationType', $tempField[$name])
        );
      }
    }
  }

  /**
   * Store parser values.
   *
   * @param CRM_Core_Session $store
   *
   * @param int $mode
   *
   * @return void
   */
  public function set($store, $mode = self::MODE_SUMMARY) {
    $store->set('fileSize', $this->_fileSize);
    $store->set('lineCount', $this->_lineCount);
    $store->set('separator', $this->_separator);
    $store->set('fields', $this->getSelectValues());
    $store->set('fieldTypes', $this->getSelectTypes());

    $store->set('headerPatterns', $this->getHeaderPatterns());
    $store->set('dataPatterns', $this->getDataPatterns());
    $store->set('columnCount', $this->_activeFieldCount);

    $store->set('totalRowCount', $this->_totalCount);
    $store->set('validRowCount', $this->_validCount);
    $store->set('invalidRowCount', $this->_invalidRowCount);

    switch ($this->_contactType) {
      case 'Individual':
        $store->set('contactType', CRM_Import_Parser::CONTACT_INDIVIDUAL);
        break;

      case 'Household':
        $store->set('contactType', CRM_Import_Parser::CONTACT_HOUSEHOLD);
        break;

      case 'Organization':
        $store->set('contactType', CRM_Import_Parser::CONTACT_ORGANIZATION);
    }

    if ($this->_invalidRowCount) {
      $store->set('errorsFileName', $this->_errorFileName);
    }
    if (isset($this->_rows) && !empty($this->_rows)) {
      $store->set('dataValues', $this->_rows);
    }

    if ($mode == self::MODE_IMPORT) {
      $store->set('duplicateRowCount', $this->_duplicateCount);
      if ($this->_duplicateCount) {
        $store->set('duplicatesFileName', $this->_duplicateFileName);
      }
    }
  }

  /**
   * Export data to a CSV file.
   *
   * @param string $fileName
   * @param array $header
   * @param array $data
   *
   * @return void
   */
  public static function exportCSV($fileName, $header, $data) {
    $output = [];
    $fd = fopen($fileName, 'w');

    foreach ($header as $key => $value) {
      $header[$key] = "\"$value\"";
    }
    $config = CRM_Core_Config::singleton();
    $output[] = implode($config->fieldSeparator, $header);

    foreach ($data as $datum) {
      foreach ($datum as $key => $value) {
        if (is_array($value)) {
          foreach ($value[0] as $k1 => $v1) {
            if ($k1 == 'location_type_id') {
              continue;
            }
            $datum[$k1] = $v1;
          }
        }
        else {
          $datum[$key] = "\"$value\"";
        }
      }
      $output[] = implode($config->fieldSeparator, $datum);
    }
    fwrite($fd, implode("\n", $output));
    fclose($fd);
  }

}
