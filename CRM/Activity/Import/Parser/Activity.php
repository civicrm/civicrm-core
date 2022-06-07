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

  /**
   * Array of successfully imported activity id's
   *
   * @var array
   */
  protected $_newActivity;

  /**
   * Total number of lines in file.
   * @var int
   */
  protected $_lineCount;

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
    $this->setFieldMetadata();

    foreach ($this->importableFieldsMetadata as $name => $field) {
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
  }

  /**
   * Handle the values in summary mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return int
   *   CRM_Import_Parser::VALID for success or
   *   CRM_Import_Parser::ERROR for error.
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
   * @param array $values
   *   The array of values belonging to this line.
   */
  public function import($values) {
    $rowNumber = (int) ($values[array_key_last($values)]);
    // First make sure this is a valid line
    try {
      $params = $this->getMappedRow($values);

      if (empty($params['external_identifier']) && empty($params['target_contact_id'])) {

        // Retrieve contact id using contact dedupe rule.
        // Since we are supporting only individual's activity import.
        $params['contact_type'] = 'Individual';
        $params['version'] = 3;
        $matchedIDs = CRM_Contact_BAO_Contact::getDuplicateContacts($params, 'Individual');

        if (!empty($matchedIDs)) {
          if (count($matchedIDs) > 1) {
            throw new CRM_Core_Exception('Multiple matching contact records detected for this row. The activity was not imported');
          }
          $cid = $matchedIDs[0];
          $params['target_contact_id'] = $cid;
          $params['version'] = 3;
          $newActivity = civicrm_api('activity', 'create', $params);
          if (!empty($newActivity['is_error'])) {
            throw new CRM_Core_Exception($newActivity['error_message']);
          }

          $this->_newActivity[] = $newActivity['id'];
          $this->setImportStatus($rowNumber, 'IMPORTED', '', $newActivity['id']);
          return;

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

        throw new CRM_Core_Exception('No matching Contact found for (' . $disp . ')');
      }
      if (!empty($params['external_identifier'])) {
        $targetContactId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $params['external_identifier'], 'id', 'external_identifier'
        );

        if (!empty($params['target_contact_id']) &&
          $params['target_contact_id'] != $targetContactId
        ) {
          throw new CRM_Core_Exception('Mismatch of External ID:' . $params['external_identifier'] . ' and Contact Id:' . $params['target_contact_id']);
        }
        if ($targetContactId) {
          $params['target_contact_id'] = $targetContactId;
        }
        else {
          throw new CRM_Core_Exception('No Matching Contact for External ID:' . $params['external_identifier']);
        }
      }

      $params['version'] = 3;
      $newActivity = civicrm_api('activity', 'create', $params);
      if (!empty($newActivity['is_error'])) {
        throw new CRM_Core_Exception($newActivity['error_message']);
      }
    }
    catch (CRM_Core_Exception $e) {
      $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage());
      return;
    }
    $this->_newActivity[] = $newActivity['id'];
    $this->setImportStatus($rowNumber, 'IMPORTED', '', $newActivity['id']);
  }

  /**
   * Get the row from the csv mapped to our parameters.
   *
   * @param array $values
   *
   * @return array
   * @throws \API_Exception
   */
  public function getMappedRow(array $values): array {
    $params = [];
    foreach ($this->getFieldMappings() as $i => $mappedField) {
      if ($mappedField['name'] === 'do_not_import') {
        continue;
      }
      if ($mappedField['name']) {
        $fieldName = $this->getFieldMetadata($mappedField['name'])['name'];
        if (in_array($mappedField['name'], ['target_contact_id', 'source_contact_id'])) {
          $fieldName = $mappedField['name'];
        }
        $params[$fieldName] = $this->getTransformedFieldValue($mappedField['name'], $values[$i]);
      }
    }
    return $params;
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
   * @return array
   */
  protected function getRequiredFields(): array {
    return [['activity_type_id' => ts('Activity Type'), 'activity_date_time' => ts('Activity Date')]];
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
    $this->isErrorInCustomData($params, $errorMessage);
    if ($errorMessage) {
      throw new CRM_Core_Exception('Invalid value for field(s) : ' . $errorMessage);
    }
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
    $this->init();

    $this->_lineCount = 0;
    $this->_invalidRowCount = $this->_validCount = 0;
    $this->_totalCount = 0;

    $this->_errors = [];
    $this->_warnings = [];
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

    $dataSource = $this->getDataSourceObject();
    $dataSource->setStatuses(['new']);
    while ($row = $dataSource->getRow()) {
      $this->_lineCount++;
      $values = array_values($row);
      $this->_totalCount++;

      if ($mode == self::MODE_MAPFIELD) {
        $returnCode = CRM_Import_Parser::VALID;
      }
      // Note that MODE_SUMMARY seems to be never used.
      elseif ($mode == self::MODE_PREVIEW || $mode == self::MODE_SUMMARY) {
        $returnCode = $this->summary($values);
      }
      elseif ($mode == self::MODE_IMPORT) {
        $this->import($values);
        if ($statusID && (($this->_lineCount % 50) == 0)) {
          $prevTimestamp = $this->progressImport($statusID, FALSE, $startTimestamp, $prevTimestamp, $totalRowCount);
        }
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
   */
  public function set($store) {}

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

  /**
   * Ensure metadata is loaded.
   */
  protected function setFieldMetadata(): void {
    if (empty($this->importableFieldsMetadata)) {
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
      ]);
      $this->importableFieldsMetadata = $fields;
    }
  }

}
