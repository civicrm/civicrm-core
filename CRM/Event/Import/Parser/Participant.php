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
    $this->setFieldMetadata();
    foreach ($this->importableFieldsMetadata as $name => $field) {
      $field['type'] = CRM_Utils_Array::value('type', $field, CRM_Utils_Type::T_INT);
      $field['dataPattern'] = CRM_Utils_Array::value('dataPattern', $field, '//');
      $field['headerPattern'] = CRM_Utils_Array::value('headerPattern', $field, '//');
      $this->addField($name, $field['title'], $field['type'], $field['headerPattern'], $field['dataPattern']);
    }
  }

  /**
   * Get the metadata field for which importable fields does not key the actual field name.
   *
   * @return string[]
   */
  protected function getOddlyMappedMetadataFields(): array {
    $uniqueNames = ['participant_id', 'participant_campaign_id', 'participant_contact_id', 'participant_status_id', 'participant_role_id', 'participant_register_date', 'participant_source', 'participant_is_pay_later'];
    $fields = [];
    foreach ($uniqueNames as $name) {
      $fields[$this->importableFieldsMetadata[$name]['name']] = $name;
    }
    // Include the parent fields as they could be present if required for matching ...in theory.
    return array_merge($fields, parent::getOddlyMappedMetadataFields());
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
    $params = $this->getMappedRow($values);
    $errors = [];
    try {
      $this->validateParams($params);
    }
    catch (CRM_Core_Exception $e) {
      $errors[] = $e->getMessage();
    }

    if ($errors) {
      $tempMsg = "Invalid value for field(s) : " . implode(',', $errors);
      array_unshift($values, $tempMsg);
      return CRM_Import_Parser::ERROR;
    }
    return CRM_Import_Parser::VALID;
  }

  /**
   * Handle the values in import mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return bool
   *   the result of this processing
   */
  public function import(&$values) {
    $rowNumber = (int) ($values[array_key_last($values)]);
    try {
      $params = $this->getMappedRow($values);
      $session = CRM_Core_Session::singleton();
      $formatted = $params;
      // don't add to recent items, CRM-4399
      $formatted['skipRecentView'] = TRUE;

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

      $formatValues = [];
      foreach ($params as $key => $field) {
        if ($field == NULL || $field === '') {
          continue;
        }

        $formatValues[$key] = $field;
      }

      $formatError = $this->formatValues($formatted, $formatValues);

      if ($formatError) {
        throw new CRM_Core_Exception($formatError['error_message']);
      }

      if (!$this->isUpdateExisting()) {
        $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($formatted,
          NULL,
          'Participant'
        );
      }
      else {
        if (!empty($formatValues['participant_id'])) {
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
              throw new CRM_Core_Exception($newParticipant['error_message']);
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
            $this->setImportStatus($rowNumber, 'IMPORTED', '', $newParticipant->id);
            return CRM_Import_Parser::VALID;
          }
          throw new CRM_Core_Exception('Matching Participant record not found for Participant ID ' . $formatValues['participant_id'] . '. Row was skipped.');
        }
      }

      if (empty($params['contact_id'])) {
        $error = $this->checkContactDuplicate($formatValues);

        if (CRM_Core_Error::isAPIError($error, CRM_Core_ERROR::DUPLICATE_CONTACT)) {
          $matchedIDs = explode(',', $error['error_message']['params'][0]);
          if (count($matchedIDs) >= 1) {
            foreach ($matchedIDs as $contactId) {
              $formatted['contact_id'] = $contactId;
              $formatted['version'] = 3;
              $newParticipant = $this->deprecated_create_participant_formatted($formatted);
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
          throw new CRM_Core_Exception('No matching Contact found for (' . $disp . ')');
        }
      }
      else {
        if (!empty($formatValues['external_identifier'])) {
          $checkCid = new CRM_Contact_DAO_Contact();
          $checkCid->external_identifier = $formatValues['external_identifier'];
          $checkCid->find(TRUE);
          if ($checkCid->id != $formatted['contact_id']) {
            throw new CRM_Core_Exception('Mismatch of External ID:' . $formatValues['external_identifier'] . ' and Contact Id:' . $formatted['contact_id']);
          }
        }
        $newParticipant = $this->deprecated_create_participant_formatted($formatted);
      }

      if (is_array($newParticipant) && civicrm_error($newParticipant)) {
        if ($this->isSkipDuplicates()) {

          $contactID = $newParticipant['contactID'] ?? NULL;
          $participantID = $newParticipant['participantID'] ?? NULL;
          $url = CRM_Utils_System::url('civicrm/contact/view/participant',
            "reset=1&id={$participantID}&cid={$contactID}&action=view", TRUE
          );
          if (is_array($newParticipant['error_message']) &&
            ($participantID == $newParticipant['error_message']['params'][0])
          ) {
            array_unshift($values, $url);
            $this->setImportStatus($rowNumber, 'DUPLICATE', '');
            return CRM_Import_Parser::DUPLICATE;
          }
          if ($newParticipant['error_message']) {
            throw new CRM_Core_Exception($newParticipant['error_message']);
          }
          throw new CRM_Core_Exception(ts('Unknown error'));
        }
      }

      if (!(is_array($newParticipant) && civicrm_error($newParticipant))) {
        $this->_newParticipants[] = $newParticipant['id'] ?? NULL;
      }
    }
    catch (CRM_Core_Exception $e) {
      array_unshift($values, $e->getMessage());
      $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage());
      return CRM_Import_Parser::ERROR;
    }
    catch (CiviCRM_API3_Exception $e) {
      array_unshift($values, $e->getMessage());
      $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage());
      return CRM_Import_Parser::ERROR;
    }
    $this->setImportStatus($rowNumber, 'IMPORTED', '');
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
   *
   * @return array|bool
   *   <type>
   * @throws \CiviCRM_API3_Exception
   * @deprecated - this is part of the import parser not the API & needs to be
   *   moved on out
   *
   */
  protected function deprecated_create_participant_formatted($params) {
    if ($this->isIgnoreDuplicates()) {
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
   *
   * @return mixed
   * @throws Exception
   */
  public function run(
    $fileName,
    $separator,
    $mapper,
    $skipColumnHeader = FALSE,
    $mode = self::MODE_PREVIEW
  ) {
    if (!is_array($fileName)) {
      throw new CRM_Core_Exception('Unable to determine import file');
    }
    $fileName = $fileName['name'];
    $this->getContactType();
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

    $dataSource = $this->getDataSourceObject();
    $dataSource->setStatuses(['new']);
    while ($row = $dataSource->getRow()) {
      $this->_lineCount++;
      $values = array_values($row);

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
        $returnCode = $this->import($values);
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

  /**
   * Set up field metadata.
   *
   * @return void
   */
  protected function setFieldMetadata(): void {
    if (empty($this->importableFieldsMetadata)) {
      $fields = CRM_Event_BAO_Participant::importableFields($this->getContactType(), FALSE);
      // We can't import event type, the other two duplicate id fields that work fine.
      unset($fields['participant_role'], $fields['participant_status'], $fields['event_type']);
      $this->importableFieldsMetadata = $fields;
    }
  }

  /**
   * @return array
   */
  protected function getRequiredFields(): array {
    return [['event_id' => ts('Event'), 'status_id' => ts('Status')]];
  }

}
