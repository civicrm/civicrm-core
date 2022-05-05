<?php

/**
 * Class CRM_Custom_Import_Parser_Api
 */
class CRM_Custom_Import_Parser_Api extends CRM_Import_Parser {

  protected $_entity = '';
  protected $_fields = [];
  protected $_requiredFields = [];
  protected $_dateFields = [];
  protected $_multipleCustomData = '';

  /**
   * Params for the current entity being prepared for the api.
   * @var array
   */
  protected $_params = [];

  protected $_fileName;

  /**
   * Imported file size.
   *
   * @var int
   */
  protected $_fileSize;

  /**
   * Separator being used
   * @var string
   */
  protected $_separator;

  /**
   * Total number of lines in file
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
   * @param null $mapperLocType
   * @param null $mapperPhoneType
   */
  public function __construct(&$mapperKeys = [], $mapperLocType = NULL, $mapperPhoneType = NULL) {
    parent::__construct();
    $this->_mapperKeys = &$mapperKeys;
  }

  public function setFields() {
    $customGroupID = $this->_multipleCustomData;
    $importableFields = $this->getGroupFieldsForImport($customGroupID, $this);
    $this->_fields = array_merge([
      'do_not_import' => ['title' => ts('- do not import -')],
      'contact_id' => ['title' => ts('Contact ID')],
      'external_identifier' => ['title' => ts('External Identifier')],
    ], $importableFields);
  }

  /**
   * The initializer code, called before the processing
   *
   * @return void
   */
  public function init() {
    $this->setFields();
    $fields = $this->_fields;
    $hasLocationType = FALSE;

    foreach ($fields as $name => $field) {
      $field['type'] = CRM_Utils_Array::value('type', $field, CRM_Utils_Type::T_INT);
      $field['dataPattern'] = CRM_Utils_Array::value('dataPattern', $field, '//');
      $field['headerPattern'] = CRM_Utils_Array::value('headerPattern', $field, '//');
      $this->addField($name, $field['title'], $field['type'], $field['headerPattern'], $field['dataPattern'], $hasLocationType);
    }
    $this->setActiveFields($this->_mapperKeys);
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
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return bool
   *   the result of this processing
   *   It is called from both the preview & the import actions
   *
   * @see CRM_Custom_Import_Parser_BaseClass::summary()
   */
  public function summary(&$values) {
    $this->setActiveFieldValues($values);
    $errorRequired = FALSE;
    $missingField = '';
    $this->_params = &$this->getActiveFieldParams();

    $this->_updateWithId = FALSE;
    $this->_parseStreetAddress = CRM_Utils_Array::value('street_address_parsing', CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'address_options'), FALSE);

    $this->_params = $this->getActiveFieldParams();
    foreach ($this->_requiredFields as $requiredField) {
      if (empty($this->_params[$requiredField])) {
        $errorRequired = TRUE;
        $missingField .= ' ' . $requiredField;
        CRM_Contact_Import_Parser_Contact::addToErrorMsg($this->_entity, $requiredField);
      }
    }

    if ($errorRequired) {
      array_unshift($values, ts('Missing required field(s) :') . $missingField);
      return CRM_Import_Parser::ERROR;
    }

    $errorMessage = NULL;

    $contactType = $this->_contactType ? $this->_contactType : 'Organization';
    CRM_Contact_Import_Parser_Contact::isErrorInCustomData($this->_params + ['contact_type' => $contactType], $errorMessage, $this->_contactSubType, NULL);

    // pseudoconstants
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
    $response = $this->summary($values);
    if ($response != CRM_Import_Parser::VALID) {
      return $response;
    }

    $this->_updateWithId = FALSE;
    $this->_parseStreetAddress = CRM_Utils_Array::value('street_address_parsing', CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'address_options'), FALSE);

    $contactType = $this->_contactType ? $this->_contactType : 'Organization';
    $formatted = [
      'contact_type' => $contactType,
    ];

    if (isset($this->_params['external_identifier']) && !isset($this->_params['contact_id'])) {
      $checkCid = new CRM_Contact_DAO_Contact();
      $checkCid->external_identifier = $this->_params['external_identifier'];
      $checkCid->find(TRUE);
      $formatted['id'] = $checkCid->id;
    }
    else {
      $formatted['id'] = $this->_params['contact_id'];
    }

    $this->formatCommonData($this->_params, $formatted);
    foreach ($formatted['custom'] as $key => $val) {
      $this->_params['custom_' . $key] = $val[-1]['value'];
    }
    $this->_params['skipRecentView'] = TRUE;
    $this->_params['check_permissions'] = TRUE;
    $this->_params['entity_id'] = $formatted['id'];
    try {
      civicrm_api3('custom_value', 'create', $this->_params);
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      array_unshift($values, $error);
      return CRM_Import_Parser::ERROR;
    }
  }

  /**
   * Adapted from CRM_Contact_Import_Parser_Contact::formatCommonData
   *
   * TODO: Is this function even necessary? All values get passed to the api anyway.
   *
   * @param array $params
   *   Contain record values.
   * @param array $formatted
   *   Array of formatted data.
   */
  private function formatCommonData($params, &$formatted) {

    $customFields = CRM_Core_BAO_CustomField::getFields(NULL);

    //format date first
    $session = CRM_Core_Session::singleton();
    $dateType = $session->get("dateTypes");
    foreach ($params as $key => $val) {
      $customFieldID = CRM_Core_BAO_CustomField::getKeyID($key);
      if ($customFieldID) {
        //we should not update Date to null, CRM-4062
        if ($val && ($customFields[$customFieldID]['data_type'] == 'Date')) {
          //CRM-21267
          CRM_Contact_Import_Parser_Contact::formatCustomDate($params, $formatted, $dateType, $key);
        }
        elseif ($customFields[$customFieldID]['data_type'] == 'Boolean') {
          if (empty($val) && !is_numeric($val)) {
            //retain earlier value when Import mode is `Fill`
            unset($params[$key]);
          }
          else {
            $params[$key] = CRM_Utils_String::strtoboolstr($val);
          }
        }
      }
    }

    //now format custom data.
    foreach ($params as $key => $field) {

      if ($key == 'id' && isset($field)) {
        $formatted[$key] = $field;
      }

      //Handling Custom Data
      if (($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) &&
        array_key_exists($customFieldID, $customFields)
      ) {

        $extends = $customFields[$customFieldID]['extends'] ?? NULL;
        $htmlType = $customFields[$customFieldID]['html_type'] ?? NULL;
        $dataType = $customFields[$customFieldID]['data_type'] ?? NULL;
        $serialized = CRM_Core_BAO_CustomField::isSerialized($customFields[$customFieldID]);

        if (!$serialized && in_array($htmlType, ['Select', 'Radio', 'Autocomplete-Select']) && in_array($dataType, ['String', 'Int'])) {
          $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
          foreach ($customOption as $customValue) {
            $val = $customValue['value'] ?? NULL;
            $label = strtolower($customValue['label'] ?? '');
            $value = strtolower(trim($formatted[$key]));
            if (($value == $label) || ($value == strtolower($val))) {
              $params[$key] = $formatted[$key] = $val;
            }
          }
        }
        elseif ($serialized && !empty($formatted[$key]) && !empty($params[$key])) {
          $mulValues = explode(',', $formatted[$key]);
          $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
          $formatted[$key] = [];
          $params[$key] = [];
          foreach ($mulValues as $v1) {
            foreach ($customOption as $v2) {
              if ((strtolower($v2['label']) == strtolower(trim($v1))) ||
                (strtolower($v2['value']) == strtolower(trim($v1)))
              ) {
                if ($htmlType == 'CheckBox') {
                  $params[$key][$v2['value']] = $formatted[$key][$v2['value']] = 1;
                }
                else {
                  $params[$key][] = $formatted[$key][] = $v2['value'];
                }
              }
            }
          }
        }
      }
    }

    if (!empty($key) && ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) && array_key_exists($customFieldID, $customFields)) {
      // @todo calling api functions directly is not supported
      _civicrm_api3_custom_format_params($params, $formatted, $extends);
    }
  }

  /**
   * Set import entity.
   * @param string $entity
   */
  public function setEntity($entity) {
    $this->_entity = $entity;
    $this->_multipleCustomData = $entity;
  }

  /**
   * Return the field ids and names (with groups) for import purpose.
   *
   * @param int $id
   *   Custom group ID.
   *
   * @return array
   *
   */
  public function getGroupFieldsForImport($id) {
    $importableFields = [];
    $params = ['custom_group_id' => $id];
    $allFields = civicrm_api3('custom_field', 'get', $params);
    $fields = $allFields['values'];
    foreach ($fields as $id => $values) {
      $datatype = $values['data_type'] ?? NULL;
      if ($datatype == 'File') {
        continue;
      }
      /* generate the key for the fields array */
      $key = "custom_$id";
      $regexp = preg_replace('/[.,;:!?]/', '', CRM_Utils_Array::value(0, $values));
      $importableFields[$key] = [
        'name' => $key,
        'title' => $values['label'] ?? NULL,
        'headerPattern' => '/' . preg_quote($regexp, '/') . '/',
        'import' => 1,
        'custom_field_id' => $id,
        'options_per_line' => $values['options_per_line'] ?? NULL,
        'data_type' => $values['data_type'] ?? NULL,
        'html_type' => $values['html_type'] ?? NULL,
        'is_search_range' => $values['is_search_range'] ?? NULL,
      ];
      if (CRM_Utils_Array::value('html_type', $values) == 'Select Date') {
        $importableFields[$key]['date_format'] = $values['date_format'] ?? NULL;
        $importableFields[$key]['time_format'] = $values['time_format'] ?? NULL;
        $this->_dateFields[] = $key;
      }
    }
    return $importableFields;
  }

  /**
   * @param string $fileName
   * @param string $separator
   * @param int $mapper
   * @param bool $skipColumnHeader
   * @param int|string $mode
   * @param int|string $contactType
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
      case CRM_Import_Parser::CONTACT_INDIVIDUAL:
        $this->_contactType = 'Individual';
        break;

      case CRM_Import_Parser::CONTACT_HOUSEHOLD:
        $this->_contactType = 'Household';
        break;

      case CRM_Import_Parser::CONTACT_ORGANIZATION:
        $this->_contactType = 'Organization';
    }
    $this->init();

    $this->_haveColumnHeader = $skipColumnHeader;

    $this->_separator = $separator;

    $fd = fopen($fileName, "r");
    if (!$fd) {
      return FALSE;
    }

    $this->_lineCount = $this->_warningCount = 0;
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

      if ($returnCode & self::WARNING) {
        $this->_warningCount++;
        if ($this->_warningCount < $this->_maxWarningCount) {
          $this->_warnings[] = $this->_lineCount;
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

      $customfields = CRM_Core_BAO_CustomField::getFields('Activity');
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
        CRM_Contact_Import_Parser_Contact::exportCSV($this->_errorFileName, $headers, $this->_errors);
      }

      if ($this->_duplicateCount) {
        $headers = array_merge([
          ts('Line Number'),
          ts('View Activity History URL'),
        ], $customHeaders);

        $this->_duplicateFileName = self::errorFileName(self::DUPLICATE);
        CRM_Contact_Import_Parser_Contact::exportCSV($this->_duplicateFileName, $headers, $this->_duplicates);
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
        $this->_activeFields[] = new CRM_Custom_Import_Field('', ts('- do not import -'));
      }
      else {
        $this->_activeFields[] = clone($this->_fields[$key]);
      }
    }
  }

  /**
   * Format the field values for input to the api.
   *
   * @return array
   *   (reference ) associative array of name/value pairs
   */
  public function &getActiveFieldParams() {
    $params = [];
    for ($i = 0; $i < $this->_activeFieldCount; $i++) {
      if (isset($this->_activeFields[$i]->_value)
        && !isset($params[$this->_activeFields[$i]->_name])
        && !isset($this->_activeFields[$i]->_related)
      ) {
        $params[$this->_activeFields[$i]->_name] = $this->_activeFields[$i]->_value;
      }
    }
    return $params;
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
    $store->set('_entity', $this->_entity);
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
   * @param string $name
   * @param $title
   * @param int $type
   * @param string $headerPattern
   * @param string $dataPattern
   * @param bool $hasLocationType
   */
  public function addField(
    $name, $title, $type = CRM_Utils_Type::T_INT,
    $headerPattern = '//', $dataPattern = '//',
    $hasLocationType = FALSE
  ) {
    $this->_fields[$name] = new CRM_Custom_Import_Field($name, $title, $type, $headerPattern, $dataPattern, $hasLocationType);
    if (empty($name)) {
      $this->_fields['doNotImport'] = new CRM_Custom_Import_Field($name, $title, $type, $headerPattern, $dataPattern, $hasLocationType);
    }
  }

}
