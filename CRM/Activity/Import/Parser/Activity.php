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


/**
 * Class to parse activity csv files.
 */
class CRM_Activity_Import_Parser_Activity extends CRM_Import_Parser {

  protected $_mapperKeys;

  private $_contactIdIndex;

  /**
   * Array of successfully imported activity id's
   *
   * @var array
   */
  protected $_newActivity;

  protected $_fileName;

  /**
   * Imported file size.
   * @var int
   */
  protected $_fileSize;

  /**
   * Separator being used.
   * @var string
   */
  protected $_separator;

  /**
   * Total number of lines in file.
   * @var int
   */
  protected $_lineCount;

  /**
   * Whether the file has a column header or not.
   *
   * @var bool
   */
  protected $_haveColumnHeader;

  /**
   * Class constructor.
   *
   * @param array $mapperKeys
   */
  public function __construct($mapperKeys = []) {
    parent::__construct();
    $this->_mapperKeys = $mapperKeys;
  }

  /**
   * The initializer code, called before the processing.
   */
  public function init() {
    $activityContact = CRM_Activity_BAO_ActivityContact::import();
    $activityTarget['target_contact_id'] = $activityContact['contact_id'];
    $fields = array_merge(CRM_Activity_BAO_Activity::importableFields(),
      $activityTarget
    );

    $fields = array_merge($fields, [
      'source_contact_id' => [
        'title' => ts('Source Contact'),
        'headerPattern' => '/Source.Contact?/i',
      ],
      'activity_label' => [
        'title' => ts('Activity Type Label'),
        'headerPattern' => '/(activity.)?type label?/i',
      ],
    ]);

    foreach ($fields as $name => $field) {
      $field['type'] = CRM_Utils_Array::value('type', $field, CRM_Utils_Type::T_INT);
      $field['dataPattern'] = CRM_Utils_Array::value('dataPattern', $field, '//');
      $field['headerPattern'] = CRM_Utils_Array::value('headerPattern', $field, '//');
      if (!empty($field['custom_group_id'])) {
        $field['title'] = $field["groupTitle"] . ' :: ' . $field["title"];
      }
      $this->addField($name, $field['title'], $field['type'], $field['headerPattern'], $field['dataPattern']);
    }

    $this->_newActivity = [];

    $this->setActiveFields($this->_mapperKeys);

    // FIXME: we should do this in one place together with Form/MapField.php
    $this->_contactIdIndex = -1;

    $index = 0;
    foreach ($this->_mapperKeys as $key) {
      switch ($key) {
        case 'target_contact_id':
        case 'external_identifier':
          $this->_contactIdIndex = $index;
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
    try {
      $this->validateValues($values);
    }
    catch (CRM_Core_Exception $e) {
      return $this->addError($values, [$e->getMessage()]);
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
   * @throws \CRM_Core_Exception
   */
  public function import($onDuplicate, &$values) {
    // First make sure this is a valid line
    try {
      $this->validateValues($values);
    }
    catch (CRM_Core_Exception $e) {
      return $this->addError($values, [$e->getMessage()]);
    }
    $params = $this->getApiReadyParams($values);
    // For date-Formats.
    $session = CRM_Core_Session::singleton();
    $dateType = $session->get('dateTypes');

    $customFields = CRM_Core_BAO_CustomField::getFields('Activity');

    foreach ($params as $key => $val) {
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
        if (!empty($customFields[$customFieldID]) && $customFields[$customFieldID]['data_type'] == 'Date') {
          CRM_Contact_Import_Parser_Contact::formatCustomDate($params, $params, $dateType, $key);
        }
        elseif (!empty($customFields[$customFieldID]) && $customFields[$customFieldID]['data_type'] == 'Boolean') {
          $params[$key] = CRM_Utils_String::strtoboolstr($val);
        }
      }
      elseif ($key === 'activity_date_time') {
        $params[$key] = CRM_Utils_Date::formatDate($val, $dateType);
      }
      elseif ($key === 'activity_subject') {
        $params['subject'] = $val;
      }
    }

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
      // Using new Dedupe rule.
      $ruleParams = [
        'contact_type' => 'Individual',
        'used' => 'Unsupervised',
      ];
      $fieldsArray = CRM_Dedupe_BAO_DedupeRule::dedupeRuleFields($ruleParams);

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
      if ($targetContactId) {
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

  /**
   *
   * Get the value for the given field from the row of values.
   *
   * @param array $row
   * @param string $fieldName
   *
   * @return null|string
   */
  protected function getFieldValue(array $row, string $fieldName) {
    if (!is_numeric($this->getFieldIndex($fieldName))) {
      return NULL;
    }
    return $row[$this->getFieldIndex($fieldName)] ?? NULL;
  }

  /**
   * Get the index for the given field.
   *
   * @param string $fieldName
   *
   * @return false|int
   */
  protected function getFieldIndex(string $fieldName) {
    return array_search($fieldName, $this->_mapperKeys, TRUE);

  }

  /**
   * Add an error to the values.
   *
   * @param array $values
   * @param array $error
   *
   * @return int
   */
  protected function addError(array &$values, array $error): int {
    array_unshift($values, implode(';', $error));
    return CRM_Import_Parser::ERROR;
  }

  /**
   * Validate that the activity type id does not conflict with the label.
   *
   * @param array $values
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  protected function validateActivityTypeIDAndLabel(array $values): void {
    $activityLabel = $this->getFieldValue($values, 'activity_label');
    $activityTypeID = $this->getFieldValue($values, 'activity_type_id');
    if ($activityLabel && $activityTypeID
      && $activityLabel !== CRM_Core_PseudoConstant::getLabel('CRM_Activity_BAO_Activity', 'activity_type_id', $activityTypeID)) {
      throw new CRM_Core_Exception(ts('Activity type label and Activity type ID are in conflict'));
    }
  }

  /**
   * Is the supplied date field valid based on selected date format.
   *
   * @param string $value
   *
   * @return bool
   */
  protected function isValidDate(string $value): bool {
    return (bool) CRM_Utils_Date::formatDate($value, CRM_Core_Session::singleton()->get('dateTypes'));
  }

  /**
   * Is the supplied field a valid contact id.
   *
   * @param string|int $value
   *
   * @return bool
   */
  protected function isValidContactID($value): bool {
    if (!CRM_Utils_Rule::integer($value)) {
      return FALSE;
    }
    if (!CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contact WHERE id = " . (int) $value)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Validate custom fields.
   *
   * @param array $values
   *
   * @throws \CRM_Core_Exception
   */
  protected function validateCustomFields($values):void {
    $this->setActiveFieldValues($values);
    $params = $this->getActiveFieldParams();
    $errorMessage = NULL;
    // Checking error in custom data.
    $params['contact_type'] = 'Activity';
    CRM_Contact_Import_Parser_Contact::isErrorInCustomData($params, $errorMessage);
    if ($errorMessage) {
      throw new CRM_Core_Exception('Invalid value for field(s) : ' . $errorMessage);
    }
  }

  /**
   * @param array $values
   *
   * @throws \CRM_Core_Exception
   */
  protected function validateValues(array $values): void {
    // Check required fields if this is not an update.
    if (!$this->getFieldValue($values, 'activity_id')) {
      if (!$this->getFieldValue($values, 'activity_label')
        && !$this->getFieldValue($values, 'activity_type_id')) {
        throw new CRM_Core_Exception(ts('Missing required fields: Activity type label or Activity type ID'));
      }
      if (!$this->getFieldValue($values, 'activity_date_time')) {
        throw new CRM_Core_Exception(ts('Missing required fields'));
      }
    }

    $this->validateActivityTypeIDAndLabel($values);
    if ($this->getFieldValue($values, 'activity_date_time')
      && !$this->isValidDate($this->getFieldValue($values, 'activity_date_time'))) {
      throw new CRM_Core_Exception(ts('Invalid Activity Date'));
    }

    if ($this->getFieldValue($values, 'activity_engagement_level')
      && !CRM_Utils_Rule::positiveInteger($this->getFieldValue($values, 'activity_engagement_level'))) {
      throw new CRM_Core_Exception(ts('Activity Engagement Index'));
    }

    $targetContactID = $this->getFieldValue($values, 'target_contact_id');
    if ($targetContactID && !$this->isValidContactID($targetContactID)) {
      throw new CRM_Core_Exception("Invalid Contact ID: There is no contact record with contact_id = " . CRM_Utils_Type::escape($targetContactID, 'String'));
    }
    $this->validateCustomFields($values);
  }

  /**
   * Get array of parameters formatted for the api from the submitted values.
   *
   * @param array $values
   *
   * @return array
   */
  protected function getApiReadyParams(array $values): array {
    $this->setActiveFieldValues($values);
    $params = $this->getActiveFieldParams();
    if ($this->getFieldValue($values, 'activity_label')) {
      $params['activity_type_id'] = array_search(
         $this->getFieldValue($values, 'activity_label'),
         CRM_Activity_BAO_Activity::buildOptions('activity_type_id', 'create'),
        TRUE
      );
    }
    return $params;
  }

  /**
   * @param array $fileName
   * @param string $separator
   * @param $mapper
   * @param bool $skipColumnHeader
   * @param int $mode
   * @param int $onDuplicate
   * @param int $statusID
   * @param int $totalRowCount
   *
   * @return mixed
   * @throws Exception
   */
  public function run(
    array $fileName,
          $separator,
          $mapper,
          $skipColumnHeader = FALSE,
          $mode = self::MODE_PREVIEW,
          $onDuplicate = self::DUPLICATE_SKIP,
          $statusID = NULL,
          $totalRowCount = NULL
  ) {

    $fileName = $fileName['name'];

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
    if ($statusID) {
      $this->progressImport($statusID);
      $startTimestamp = $currTimestamp = $prevTimestamp = time();
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

      // Trim whitespace around the values.

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
        if ($statusID && (($this->_lineCount % 50) == 0)) {
          $prevTimestamp = $this->progressImport($statusID, FALSE, $startTimestamp, $prevTimestamp, $totalRowCount);
        }
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

      $customfields = CRM_Core_BAO_CustomField::getFields('Activity');
      foreach ($customHeaders as $key => $value) {
        if ($id = CRM_Core_BAO_CustomField::getKeyID($value)) {
          $customHeaders[$key] = $customfields[$id][0];
        }
      }
      if ($this->_invalidRowCount) {
        // removed view url for invlaid contacts
        $headers = array_merge(
          [ts('Line Number'), ts('Reason')],
          $customHeaders
        );
        $this->_errorFileName = self::errorFileName(self::ERROR);
        self::exportCSV($this->_errorFileName, $headers, $this->_errors);
      }

      if ($this->_duplicateCount) {
        $headers = array_merge(
          [ts('Line Number'), ts('View Activity History URL')],
          $customHeaders
        );

        $this->_duplicateFileName = self::errorFileName(self::DUPLICATE);
        self::exportCSV($this->_duplicateFileName, $headers, $this->_duplicates);
      }
    }
  }

  /**
   * Given a list of the importable field keys that the user has selected set the active fields array to this list.
   *
   * @param array $fieldKeys
   */
  public function setActiveFields($fieldKeys) {
    $this->_activeFieldCount = count($fieldKeys);
    foreach ($fieldKeys as $key) {
      if (empty($this->_fields[$key])) {
        $this->_activeFields[] = new CRM_Activity_Import_Field('', ts('- do not import -'));
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
      $this->_fields['doNotImport'] = new CRM_Activity_Import_Field($name, $title, $type, $headerPattern, $dataPattern);
    }
    else {

      $tempField = CRM_Contact_BAO_Contact::importableFields('Individual', NULL);
      if (!array_key_exists($name, $tempField)) {
        $this->_fields[$name] = new CRM_Activity_Import_Field($name, $title, $type, $headerPattern, $dataPattern);
      }
      else {
        $this->_fields[$name] = new CRM_Contact_Import_Field($name, $title, $type, $headerPattern, $dataPattern, CRM_Utils_Array::value('hasLocationType', $tempField[$name]));
      }
    }
  }

  /**
   * Store parser values.
   *
   * @param CRM_Core_Session $store
   *
   * @param int $mode
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
        $datum[$key] = "\"$value\"";
      }
      $output[] = implode($config->fieldSeparator, $datum);
    }
    fwrite($fd, implode("\n", $output));
    fclose($fd);
  }

}
