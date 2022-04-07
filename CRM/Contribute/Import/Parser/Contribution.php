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
 * Class to parse contribution csv files.
 */
class CRM_Contribute_Import_Parser_Contribution extends CRM_Import_Parser {

  protected $_mapperKeys;

  private $_contactIdIndex;

  protected $_mapperSoftCredit;
  //protected $_mapperPhoneType;

  /**
   * Array of successfully imported contribution id's
   *
   * @var array
   */
  protected $_newContributions;

  /**
   * Class constructor.
   *
   * @param $mapperKeys
   * @param array $mapperSoftCredit
   * @param null $mapperPhoneType
   * @param array $mapperSoftCreditType
   */
  public function __construct(&$mapperKeys, $mapperSoftCredit = [], $mapperPhoneType = NULL, $mapperSoftCreditType = []) {
    parent::__construct();
    $this->_mapperKeys = &$mapperKeys;
    $this->_mapperSoftCredit = &$mapperSoftCredit;
    $this->_mapperSoftCreditType = &$mapperSoftCreditType;
  }

  /**
   * Contribution-specific result codes
   * @see CRM_Import_Parser result code constants
   */
  const SOFT_CREDIT = 512, SOFT_CREDIT_ERROR = 1024, PLEDGE_PAYMENT = 2048, PLEDGE_PAYMENT_ERROR = 4096;

  /**
   * @var string
   */
  protected $_fileName;

  /**
   * Imported file size
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
   * Running total number of valid soft credit rows
   * @var int
   */
  protected $_validSoftCreditRowCount;

  /**
   * Running total number of invalid soft credit rows
   * @var int
   */
  protected $_invalidSoftCreditRowCount;

  /**
   * Running total number of valid pledge payment rows
   * @var int
   */
  protected $_validPledgePaymentRowCount;

  /**
   * Running total number of invalid pledge payment rows
   * @var int
   */
  protected $_invalidPledgePaymentRowCount;

  /**
   * Array of pledge payment error lines, bounded by MAX_ERROR
   * @var array
   */
  protected $_pledgePaymentErrors;

  /**
   * Array of pledge payment error lines, bounded by MAX_ERROR
   * @var array
   */
  protected $_softCreditErrors;

  /**
   * Filename of pledge payment error data
   *
   * @var string
   */
  protected $_pledgePaymentErrorsFileName;

  /**
   * Filename of soft credit error data
   *
   * @var string
   */
  protected $_softCreditErrorsFileName;

  /**
   * Whether the file has a column header or not
   *
   * @var bool
   */
  protected $_haveColumnHeader;

  /**
   * @param string $fileName
   * @param string $separator
   * @param $mapper
   * @param bool $skipColumnHeader
   * @param int $mode
   * @param int $contactType
   * @param int $onDuplicate
   * @param int $statusID
   * @param int $totalRowCount
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
    $onDuplicate = self::DUPLICATE_SKIP,
    $statusID = NULL,
    $totalRowCount = NULL
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

    $this->_lineCount = $this->_warningCount = $this->_validSoftCreditRowCount = $this->_validPledgePaymentRowCount = 0;
    $this->_invalidRowCount = $this->_validCount = $this->_invalidSoftCreditRowCount = $this->_invalidPledgePaymentRowCount = 0;
    $this->_totalCount = $this->_conflictCount = 0;

    $this->_errors = [];
    $this->_warnings = [];
    $this->_conflicts = [];
    $this->_pledgePaymentErrors = [];
    $this->_softCreditErrors = [];
    if ($statusID) {
      $this->progressImport($statusID);
      $startTimestamp = $currTimestamp = $prevTimestamp = time();
    }

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
        $returnCode = $this->mapField($values);
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
      if ($returnCode == self::VALID) {
        $this->_validCount++;
        if ($mode == self::MODE_MAPFIELD) {
          $this->_rows[] = $values;
          $this->_activeFieldCount = max($this->_activeFieldCount, count($values));
        }
      }

      if ($returnCode == self::SOFT_CREDIT) {
        $this->_validSoftCreditRowCount++;
        $this->_validCount++;
        if ($mode == self::MODE_MAPFIELD) {
          $this->_rows[] = $values;
          $this->_activeFieldCount = max($this->_activeFieldCount, count($values));
        }
      }

      if ($returnCode == self::PLEDGE_PAYMENT) {
        $this->_validPledgePaymentRowCount++;
        $this->_validCount++;
        if ($mode == self::MODE_MAPFIELD) {
          $this->_rows[] = $values;
          $this->_activeFieldCount = max($this->_activeFieldCount, count($values));
        }
      }

      if ($returnCode == self::WARNING) {
        $this->_warningCount++;
        if ($this->_warningCount < $this->_maxWarningCount) {
          $this->_warningCount[] = $line;
        }
      }

      if ($returnCode == self::ERROR) {
        $this->_invalidRowCount++;
        $recordNumber = $this->_lineCount;
        if ($this->_haveColumnHeader) {
          $recordNumber--;
        }
        array_unshift($values, $recordNumber);
        $this->_errors[] = $values;
      }

      if ($returnCode == self::PLEDGE_PAYMENT_ERROR) {
        $this->_invalidPledgePaymentRowCount++;
        $recordNumber = $this->_lineCount;
        if ($this->_haveColumnHeader) {
          $recordNumber--;
        }
        array_unshift($values, $recordNumber);
        $this->_pledgePaymentErrors[] = $values;
      }

      if ($returnCode == self::SOFT_CREDIT_ERROR) {
        $this->_invalidSoftCreditRowCount++;
        $recordNumber = $this->_lineCount;
        if ($this->_haveColumnHeader) {
          $recordNumber--;
        }
        array_unshift($values, $recordNumber);
        $this->_softCreditErrors[] = $values;
      }

      if ($returnCode == self::CONFLICT) {
        $this->_conflictCount++;
        $recordNumber = $this->_lineCount;
        if ($this->_haveColumnHeader) {
          $recordNumber--;
        }
        array_unshift($values, $recordNumber);
        $this->_conflicts[] = $values;
      }

      if ($returnCode == self::DUPLICATE) {
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

      // we give the derived class a way of aborting the process
      // note that the return code could be multiple code or'ed together
      if ($returnCode == self::STOP) {
        break;
      }

      // if we are done processing the maxNumber of lines, break
      if ($this->_maxLinesToProcess > 0 && $this->_validCount >= $this->_maxLinesToProcess) {
        break;
      }
    }

    fclose($fd);

    if ($mode == self::MODE_PREVIEW || $mode == self::MODE_IMPORT) {
      $customHeaders = $mapper;

      $customfields = CRM_Core_BAO_CustomField::getFields('Contribution');
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

      if ($this->_invalidPledgePaymentRowCount) {
        // removed view url for invlaid contacts
        $headers = array_merge([
          ts('Line Number'),
          ts('Reason'),
        ], $customHeaders);
        $this->_pledgePaymentErrorsFileName = self::errorFileName(self::PLEDGE_PAYMENT_ERROR);
        self::exportCSV($this->_pledgePaymentErrorsFileName, $headers, $this->_pledgePaymentErrors);
      }

      if ($this->_invalidSoftCreditRowCount) {
        // removed view url for invlaid contacts
        $headers = array_merge([
          ts('Line Number'),
          ts('Reason'),
        ], $customHeaders);
        $this->_softCreditErrorsFileName = self::errorFileName(self::SOFT_CREDIT_ERROR);
        self::exportCSV($this->_softCreditErrorsFileName, $headers, $this->_softCreditErrors);
      }

      if ($this->_conflictCount) {
        $headers = array_merge([
          ts('Line Number'),
          ts('Reason'),
        ], $customHeaders);
        $this->_conflictFileName = self::errorFileName(self::CONFLICT);
        self::exportCSV($this->_conflictFileName, $headers, $this->_conflicts);
      }
      if ($this->_duplicateCount) {
        $headers = array_merge([
          ts('Line Number'),
          ts('View Contribution URL'),
        ], $customHeaders);

        $this->_duplicateFileName = self::errorFileName(self::DUPLICATE);
        self::exportCSV($this->_duplicateFileName, $headers, $this->_duplicates);
      }
    }
    return $this->fini();
  }

  /**
   * Given a list of the importable field keys that the user has selected
   * set the active fields array to this list
   *
   * @param array $fieldKeys mapped array of values
   */
  public function setActiveFields($fieldKeys) {
    $this->_activeFieldCount = count($fieldKeys);
    foreach ($fieldKeys as $key) {
      if (empty($this->_fields[$key])) {
        $this->_activeFields[] = new CRM_Contribute_Import_Field('', ts('- do not import -'));
      }
      else {
        $this->_activeFields[] = clone($this->_fields[$key]);
      }
    }
  }

  /**
   * Store the soft credit field information.
   *
   * This  was perhaps done this way on the believe that a lot of code pain
   * was worth it to avoid negligible-cost array iterations. Perhaps we could prioritise
   * readability & maintainability next since we can just work with functions to retrieve
   * data from the metadata.
   *
   * @param array $elements
   */
  public function setActiveFieldSoftCredit($elements) {
    foreach ((array) $elements as $i => $element) {
      $this->_activeFields[$i]->_softCreditField = $element;
    }
  }

  /**
   * Store the soft credit field type information.
   *
   * This  was perhaps done this way on the believe that a lot of code pain
   * was worth it to avoid negligible-cost array iterations. Perhaps we could prioritise
   * readability & maintainability next since we can just work with functions to retrieve
   * data from the metadata.
   *
   * @param array $elements
   */
  public function setActiveFieldSoftCreditType($elements) {
    foreach ((array) $elements as $i => $element) {
      $this->_activeFields[$i]->_softCreditType = $element;
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
      if (isset($this->_activeFields[$i]->_value)) {
        if (isset($this->_activeFields[$i]->_softCreditField)) {
          if (!isset($params[$this->_activeFields[$i]->_name])) {
            $params[$this->_activeFields[$i]->_name] = [];
          }
          $params[$this->_activeFields[$i]->_name][$i][$this->_activeFields[$i]->_softCreditField] = $this->_activeFields[$i]->_value;
          if (isset($this->_activeFields[$i]->_softCreditType)) {
            $params[$this->_activeFields[$i]->_name][$i]['soft_credit_type_id'] = $this->_activeFields[$i]->_softCreditType;
          }
        }

        if (!isset($params[$this->_activeFields[$i]->_name])) {
          if (!isset($this->_activeFields[$i]->_softCreditField)) {
            $params[$this->_activeFields[$i]->_name] = $this->_activeFields[$i]->_value;
          }
        }
      }
    }
    return $params;
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
      $this->_fields['doNotImport'] = new CRM_Contribute_Import_Field($name, $title, $type, $headerPattern, $dataPattern);
    }
    else {
      $tempField = CRM_Contact_BAO_Contact::importableFields('All', NULL);
      if (!array_key_exists($name, $tempField)) {
        $this->_fields[$name] = new CRM_Contribute_Import_Field($name, $title, $type, $headerPattern, $dataPattern);
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
    $store->set('invalidSoftCreditRowCount', $this->_invalidSoftCreditRowCount);
    $store->set('validSoftCreditRowCount', $this->_validSoftCreditRowCount);
    $store->set('invalidPledgePaymentRowCount', $this->_invalidPledgePaymentRowCount);
    $store->set('validPledgePaymentRowCount', $this->_validPledgePaymentRowCount);
    $store->set('conflictRowCount', $this->_conflictCount);

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
    if ($this->_conflictCount) {
      $store->set('conflictsFileName', $this->_conflictFileName);
    }
    if (isset($this->_rows) && !empty($this->_rows)) {
      $store->set('dataValues', $this->_rows);
    }

    if ($this->_invalidPledgePaymentRowCount) {
      $store->set('pledgePaymentErrorsFileName', $this->_pledgePaymentErrorsFileName);
    }

    if ($this->_invalidSoftCreditRowCount) {
      $store->set('softCreditErrorsFileName', $this->_softCreditErrorsFileName);
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
        if (isset($value[0]) && is_array($value)) {
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
   * Determines the file extension based on error code.
   *
   * @param int $type
   *   Error code constant.
   *
   * @return string
   */
  public static function errorFileName($type) {
    $fileName = NULL;
    if (empty($type)) {
      return $fileName;
    }

    $config = CRM_Core_Config::singleton();
    $fileName = $config->uploadDir . "sqlImport";

    switch ($type) {
      case self::SOFT_CREDIT_ERROR:
        $fileName .= '.softCreditErrors';
        break;

      case self::PLEDGE_PAYMENT_ERROR:
        $fileName .= '.pledgePaymentErrors';
        break;

      default:
        $fileName = parent::errorFileName($type);
        break;
    }

    return $fileName;
  }

  /**
   * Determines the file name based on error code.
   *
   * @param int $type
   *   Error code constant.
   *
   * @return string
   */
  public static function saveFileName($type) {
    $fileName = NULL;
    if (empty($type)) {
      return $fileName;
    }

    switch ($type) {
      case self::SOFT_CREDIT_ERROR:
        $fileName = 'Import_Soft_Credit_Errors.csv';
        break;

      case self::PLEDGE_PAYMENT_ERROR:
        $fileName = 'Import_Pledge_Payment_Errors.csv';
        break;

      default:
        $fileName = parent::saveFileName($type);
        break;
    }

    return $fileName;
  }

  /**
   * The initializer code, called before the processing
   */
  public function init() {
    $fields = CRM_Contribute_BAO_Contribution::importableFields($this->_contactType, FALSE);

    $fields = array_merge($fields,
      [
        'soft_credit' => [
          'title' => ts('Soft Credit'),
          'softCredit' => TRUE,
          'headerPattern' => '/Soft Credit/i',
        ],
      ]
    );

    // add pledge fields only if its is enabled
    if (CRM_Core_Permission::access('CiviPledge')) {
      $pledgeFields = [
        'pledge_payment' => [
          'title' => ts('Pledge Payment'),
          'headerPattern' => '/Pledge Payment/i',
        ],
        'pledge_id' => [
          'title' => ts('Pledge ID'),
          'headerPattern' => '/Pledge ID/i',
        ],
      ];

      $fields = array_merge($fields, $pledgeFields);
    }
    foreach ($fields as $name => $field) {
      $field['type'] = CRM_Utils_Array::value('type', $field, CRM_Utils_Type::T_INT);
      $field['dataPattern'] = CRM_Utils_Array::value('dataPattern', $field, '//');
      $field['headerPattern'] = CRM_Utils_Array::value('headerPattern', $field, '//');
      $this->addField($name, $field['title'], $field['type'], $field['headerPattern'], $field['dataPattern']);
    }

    $this->_newContributions = [];

    $this->setActiveFields($this->_mapperKeys);
    $this->setActiveFieldSoftCredit($this->_mapperSoftCredit);
    $this->setActiveFieldSoftCreditType($this->_mapperSoftCreditType);

    // FIXME: we should do this in one place together with Form/MapField.php
    $this->_contactIdIndex = -1;

    $index = 0;
    foreach ($this->_mapperKeys as $key) {
      switch ($key) {
        case 'contribution_contact_id':
          $this->_contactIdIndex = $index;
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
    $this->setActiveFieldValues($values);

    $params = $this->getActiveFieldParams();

    //for date-Formats
    $errorMessage = implode('; ', $this->formatDateFields($params));
    //date-Format part ends

    $params['contact_type'] = 'Contribution';

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
    $formatted = ['version' => 3, 'skipRecentView' => TRUE, 'skipCleanMoney' => FALSE];

    //CRM-10994
    if (isset($params['total_amount']) && $params['total_amount'] == 0) {
      $params['total_amount'] = '0.00';
    }
    $this->formatInput($params, $formatted);

    static $indieFields = NULL;
    if ($indieFields == NULL) {
      $tempIndieFields = CRM_Contribute_DAO_Contribution::import();
      $indieFields = $tempIndieFields;
    }

    $paramValues = [];
    foreach ($params as $key => $field) {
      if ($field == NULL || $field === '') {
        continue;
      }
      $paramValues[$key] = $field;
    }

    //import contribution record according to select contact type
    if ($onDuplicate == CRM_Import_Parser::DUPLICATE_SKIP &&
      (!empty($paramValues['contribution_contact_id']) || !empty($paramValues['external_identifier']))
    ) {
      $paramValues['contact_type'] = $this->_contactType;
    }
    elseif ($onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE &&
      (!empty($paramValues['contribution_id']) || !empty($values['trxn_id']) || !empty($paramValues['invoice_id']))
    ) {
      $paramValues['contact_type'] = $this->_contactType;
    }
    elseif (!empty($params['soft_credit'])) {
      $paramValues['contact_type'] = $this->_contactType;
    }
    elseif (!empty($paramValues['pledge_payment'])) {
      $paramValues['contact_type'] = $this->_contactType;
    }

    //need to pass $onDuplicate to check import mode.
    if (!empty($paramValues['pledge_payment'])) {
      $paramValues['onDuplicate'] = $onDuplicate;
    }
    $formatError = $this->deprecatedFormatParams($paramValues, $formatted, TRUE, $onDuplicate);

    if ($formatError) {
      array_unshift($values, $formatError['error_message']);
      if (CRM_Utils_Array::value('error_data', $formatError) == 'soft_credit') {
        return self::SOFT_CREDIT_ERROR;
      }
      if (CRM_Utils_Array::value('error_data', $formatError) == 'pledge_payment') {
        return self::PLEDGE_PAYMENT_ERROR;
      }
      return CRM_Import_Parser::ERROR;
    }

    if ($onDuplicate != CRM_Import_Parser::DUPLICATE_UPDATE) {
      $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($formatted,
        NULL,
        'Contribution'
      );
    }
    else {
      //fix for CRM-2219 - Update Contribution
      // onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE
      if (!empty($paramValues['invoice_id']) || !empty($paramValues['trxn_id']) || !empty($paramValues['contribution_id'])) {
        $dupeIds = [
          'id' => $paramValues['contribution_id'] ?? NULL,
          'trxn_id' => $paramValues['trxn_id'] ?? NULL,
          'invoice_id' => $paramValues['invoice_id'] ?? NULL,
        ];

        $ids['contribution'] = CRM_Contribute_BAO_Contribution::checkDuplicateIds($dupeIds);

        if ($ids['contribution']) {
          $formatted['id'] = $ids['contribution'];
          $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($formatted,
            $formatted['id'],
            'Contribution'
          );
          //process note
          if (!empty($paramValues['note'])) {
            $noteID = [];
            $contactID = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $ids['contribution'], 'contact_id');
            $daoNote = new CRM_Core_BAO_Note();
            $daoNote->entity_table = 'civicrm_contribution';
            $daoNote->entity_id = $ids['contribution'];
            if ($daoNote->find(TRUE)) {
              $noteID['id'] = $daoNote->id;
            }

            $noteParams = [
              'entity_table' => 'civicrm_contribution',
              'note' => $paramValues['note'],
              'entity_id' => $ids['contribution'],
              'contact_id' => $contactID,
            ];
            CRM_Core_BAO_Note::add($noteParams, $noteID);
            unset($formatted['note']);
          }

          //need to check existing soft credit contribution, CRM-3968
          if (!empty($formatted['soft_credit'])) {
            $dupeSoftCredit = [
              'contact_id' => $formatted['soft_credit'],
              'contribution_id' => $ids['contribution'],
            ];

            //Delete all existing soft Contribution from contribution_soft table for pcp_id is_null
            $existingSoftCredit = CRM_Contribute_BAO_ContributionSoft::getSoftContribution($dupeSoftCredit['contribution_id']);
            if (isset($existingSoftCredit['soft_credit']) && !empty($existingSoftCredit['soft_credit'])) {
              foreach ($existingSoftCredit['soft_credit'] as $key => $existingSoftCreditValues) {
                if (!empty($existingSoftCreditValues['soft_credit_id'])) {
                  civicrm_api3('ContributionSoft', 'delete', [
                    'id' => $existingSoftCreditValues['soft_credit_id'],
                    'pcp_id' => NULL,
                  ]);
                }
              }
            }
          }

          $formatted['id'] = $ids['contribution'];
          $newContribution = CRM_Contribute_BAO_Contribution::create($formatted);
          $this->_newContributions[] = $newContribution->id;

          //return soft valid since we need to show how soft credits were added
          if (!empty($formatted['soft_credit'])) {
            return self::SOFT_CREDIT;
          }

          // process pledge payment assoc w/ the contribution
          return $this->processPledgePayments($formatted);
        }
        $labels = [
          'id' => 'Contribution ID',
          'trxn_id' => 'Transaction ID',
          'invoice_id' => 'Invoice ID',
        ];
        foreach ($dupeIds as $k => $v) {
          if ($v) {
            $errorMsg[] = "$labels[$k] $v";
          }
        }
        $errorMsg = implode(' AND ', $errorMsg);
        array_unshift($values, 'Matching Contribution record not found for ' . $errorMsg . '. Row was skipped.');
        return CRM_Import_Parser::ERROR;
      }
    }

    if ($this->_contactIdIndex < 0) {

      $error = $this->checkContactDuplicate($paramValues);

      if (CRM_Core_Error::isAPIError($error, CRM_Core_ERROR::DUPLICATE_CONTACT)) {
        $matchedIDs = explode(',', $error['error_message']['params'][0]);
        if (count($matchedIDs) > 1) {
          array_unshift($values, 'Multiple matching contact records detected for this row. The contribution was not imported');
          return CRM_Import_Parser::ERROR;
        }
        $cid = $matchedIDs[0];
        $formatted['contact_id'] = $cid;

        $newContribution = civicrm_api('contribution', 'create', $formatted);
        if (civicrm_error($newContribution)) {
          if (is_array($newContribution['error_message'])) {
            array_unshift($values, $newContribution['error_message']['message']);
            if ($newContribution['error_message']['params'][0]) {
              return CRM_Import_Parser::DUPLICATE;
            }
          }
          else {
            array_unshift($values, $newContribution['error_message']);
            return CRM_Import_Parser::ERROR;
          }
        }

        $this->_newContributions[] = $newContribution['id'];
        $formatted['contribution_id'] = $newContribution['id'];

        //return soft valid since we need to show how soft credits were added
        if (!empty($formatted['soft_credit'])) {
          return self::SOFT_CREDIT;
        }

        // process pledge payment assoc w/ the contribution
        return $this->processPledgePayments($formatted);
      }

      // Using new Dedupe rule.
      $ruleParams = [
        'contact_type' => $this->_contactType,
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

    if (!empty($paramValues['external_identifier'])) {
      $checkCid = new CRM_Contact_DAO_Contact();
      $checkCid->external_identifier = $paramValues['external_identifier'];
      $checkCid->find(TRUE);
      if ($checkCid->id != $formatted['contact_id']) {
        array_unshift($values, 'Mismatch of External ID:' . $paramValues['external_identifier'] . ' and Contact Id:' . $formatted['contact_id']);
        return CRM_Import_Parser::ERROR;
      }
    }
    $newContribution = civicrm_api('contribution', 'create', $formatted);
    if (civicrm_error($newContribution)) {
      if (is_array($newContribution['error_message'])) {
        array_unshift($values, $newContribution['error_message']['message']);
        if ($newContribution['error_message']['params'][0]) {
          return CRM_Import_Parser::DUPLICATE;
        }
      }
      else {
        array_unshift($values, $newContribution['error_message']);
        return CRM_Import_Parser::ERROR;
      }
    }

    $this->_newContributions[] = $newContribution['id'];
    $formatted['contribution_id'] = $newContribution['id'];

    //return soft valid since we need to show how soft credits were added
    if (!empty($formatted['soft_credit'])) {
      return self::SOFT_CREDIT;
    }

    // process pledge payment assoc w/ the contribution
    return $this->processPledgePayments($formatted);
  }

  /**
   * Process pledge payments.
   *
   * @param array $formatted
   *
   * @return int
   */
  private function processPledgePayments(array $formatted) {
    if (!empty($formatted['pledge_payment_id']) && !empty($formatted['pledge_id'])) {
      //get completed status
      $completeStatusID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');

      //need to update payment record to map contribution_id
      CRM_Core_DAO::setFieldValue('CRM_Pledge_DAO_PledgePayment', $formatted['pledge_payment_id'],
        'contribution_id', $formatted['contribution_id']
      );

      CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($formatted['pledge_id'],
        [$formatted['pledge_payment_id']],
        $completeStatusID,
        NULL,
        $formatted['total_amount']
      );

      return self::PLEDGE_PAYMENT;
    }
  }

  /**
   * Get the array of successfully imported contribution id's
   *
   * @return array
   */
  public function &getImportedContributions() {
    return $this->_newContributions;
  }

  /**
   * The initializer code, called before the processing.
   */
  public function fini() {
  }

  /**
   * Format date fields from input to mysql.
   *
   * @param array $params
   *
   * @return array
   *   Error messages, if any.
   */
  public function formatDateFields(&$params) {
    $errorMessage = [];
    $dateType = CRM_Core_Session::singleton()->get('dateTypes');
    foreach ($params as $key => $val) {
      if ($val) {
        switch ($key) {
          case 'receive_date':
            if ($dateValue = CRM_Utils_Date::formatDate($params[$key], $dateType)) {
              $params[$key] = $dateValue;
            }
            else {
              $errorMessage[] = ts('Receive Date');
            }
            break;

          case 'cancel_date':
            if ($dateValue = CRM_Utils_Date::formatDate($params[$key], $dateType)) {
              $params[$key] = $dateValue;
            }
            else {
              $errorMessage[] = ts('Cancel Date');
            }
            break;

          case 'receipt_date':
            if ($dateValue = CRM_Utils_Date::formatDate($params[$key], $dateType)) {
              $params[$key] = $dateValue;
            }
            else {
              $errorMessage[] = ts('Receipt date');
            }
            break;

          case 'thankyou_date':
            if ($dateValue = CRM_Utils_Date::formatDate($params[$key], $dateType)) {
              $params[$key] = $dateValue;
            }
            else {
              $errorMessage[] = ts('Thankyou Date');
            }
            break;
        }
      }
    }
    return $errorMessage;
  }

  /**
   * Format input params to suit api handling.
   *
   * Over time all the parts of  deprecatedFormatParams
   * and all the parts of the import function on this class that relate to
   * reformatting input should be moved here and tests should be added in
   * CRM_Contribute_Import_Parser_ContributionTest.
   *
   * @param array $params
   * @param array $formatted
   */
  public function formatInput(&$params, &$formatted = []) {
    $dateType = CRM_Core_Session::singleton()->get('dateTypes');
    $customDataType = !empty($params['contact_type']) ? $params['contact_type'] : 'Contribution';
    $customFields = CRM_Core_BAO_CustomField::getFields($customDataType);
    // @todo call formatDateFields & move custom data handling there.
    // Also note error handling for dates is currently in  deprecatedFormatParams
    // we should use the error handling in formatDateFields.
    foreach ($params as $key => $val) {
      // @todo - call formatDateFields instead.
      if ($val) {
        switch ($key) {
          case 'receive_date':
          case 'cancel_date':
          case 'receipt_date':
          case 'thankyou_date':
            $params[$key] = CRM_Utils_Date::formatDate($params[$key], $dateType);
            break;

          case 'pledge_payment':
            $params[$key] = CRM_Utils_String::strtobool($val);
            break;

        }
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
          if ($customFields[$customFieldID]['data_type'] == 'Date') {
            CRM_Contact_Import_Parser_Contact::formatCustomDate($params, $formatted, $dateType, $key);
            unset($params[$key]);
          }
          elseif ($customFields[$customFieldID]['data_type'] == 'Boolean') {
            $params[$key] = CRM_Utils_String::strtoboolstr($val);
          }
        }
      }
    }
  }

  /**
   * take the input parameter list as specified in the data model and
   * convert it into the same format that we use in QF and BAO object
   *
   * @param array $params
   *   Associative array of property name/value
   *   pairs to insert in new contact.
   * @param array $values
   *   The reformatted properties that we can use internally.
   * @param bool $create
   * @param int $onDuplicate
   *
   * @return array|CRM_Error
   */
  private function deprecatedFormatParams($params, &$values, $create = FALSE, $onDuplicate = NULL) {
    require_once 'CRM/Utils/DeprecatedUtils.php';
    // copy all the contribution fields as is
    require_once 'api/v3/utils.php';
    $fields = CRM_Core_DAO::getExportableFieldsWithPseudoConstants('CRM_Contribute_BAO_Contribution');

    _civicrm_api3_store_values($fields, $params, $values);

    $customFields = CRM_Core_BAO_CustomField::getFields('Contribution', FALSE, FALSE, NULL, NULL, FALSE, FALSE, FALSE);

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
        elseif ($type == 'Select' || $type == 'Radio' ||
          ($type == 'Autocomplete-Select' &&
            $customFields[$customFieldID]['data_type'] == 'String'
          )
        ) {
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
        continue;
      }

      switch ($key) {
        case 'contribution_contact_id':
          if (!CRM_Utils_Rule::integer($value)) {
            return civicrm_api3_create_error("contact_id not valid: $value");
          }
          $dao = new CRM_Core_DAO();
          $qParams = [];
          $svq = $dao->singleValueQuery("SELECT is_deleted FROM civicrm_contact WHERE id = $value",
            $qParams
          );
          if (!isset($svq)) {
            return civicrm_api3_create_error("Invalid Contact ID: There is no contact record with contact_id = $value.");
          }
          elseif ($svq == 1) {
            return civicrm_api3_create_error("Invalid Contact ID: contact_id $value is a soft-deleted contact.");
          }

          $values['contact_id'] = $values['contribution_contact_id'];
          unset($values['contribution_contact_id']);
          break;

        case 'contact_type':
          // import contribution record according to select contact type
          require_once 'CRM/Contact/DAO/Contact.php';
          $contactType = new CRM_Contact_DAO_Contact();
          $contactId = $params['contribution_contact_id'] ?? NULL;
          $externalId = $params['external_identifier'] ?? NULL;
          $email = $params['email'] ?? NULL;
          //when insert mode check contact id or external identifier
          if ($contactId || $externalId) {
            $contactType->id = $contactId;
            $contactType->external_identifier = $externalId;
            if ($contactType->find(TRUE)) {
              if ($params['contact_type'] != $contactType->contact_type) {
                return civicrm_api3_create_error("Contact Type is wrong: $contactType->contact_type");
              }
            }
          }
          elseif ($email) {
            if (!CRM_Utils_Rule::email($email)) {
              return civicrm_api3_create_error("Invalid email address $email provided. Row was skipped");
            }

            // get the contact id from duplicate contact rule, if more than one contact is returned
            // we should return error, since current interface allows only one-one mapping
            $emailParams = [
              'email' => $email,
              'contact_type' => $params['contact_type'],
            ];
            $checkDedupe = _civicrm_api3_deprecated_duplicate_formatted_contact($emailParams);
            if (!$checkDedupe['is_error']) {
              return civicrm_api3_create_error("Invalid email address(doesn't exist) $email. Row was skipped");
            }
            $matchingContactIds = explode(',', $checkDedupe['error_message']['params'][0]);
            if (count($matchingContactIds) > 1) {
              return civicrm_api3_create_error("Invalid email address(duplicate) $email. Row was skipped");
            }
            if (count($matchingContactIds) == 1) {
              $params['contribution_contact_id'] = $matchingContactIds[0];
            }
          }
          elseif (!empty($params['contribution_id']) || !empty($params['trxn_id']) || !empty($params['invoice_id'])) {
            // when update mode check contribution id or trxn id or
            // invoice id
            $contactId = new CRM_Contribute_DAO_Contribution();
            if (!empty($params['contribution_id'])) {
              $contactId->id = $params['contribution_id'];
            }
            elseif (!empty($params['trxn_id'])) {
              $contactId->trxn_id = $params['trxn_id'];
            }
            elseif (!empty($params['invoice_id'])) {
              $contactId->invoice_id = $params['invoice_id'];
            }
            if ($contactId->find(TRUE)) {
              $contactType->id = $contactId->contact_id;
              if ($contactType->find(TRUE)) {
                if ($params['contact_type'] != $contactType->contact_type) {
                  return civicrm_api3_create_error("Contact Type is wrong: $contactType->contact_type");
                }
              }
            }
          }
          else {
            if ($onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE) {
              return civicrm_api3_create_error("Empty Contribution and Invoice and Transaction ID. Row was skipped.");
            }
          }
          break;

        case 'receive_date':
        case 'cancel_date':
        case 'receipt_date':
        case 'thankyou_date':
          if (!CRM_Utils_Rule::dateTime($value)) {
            return civicrm_api3_create_error("$key not a valid date: $value");
          }
          break;

        case 'non_deductible_amount':
        case 'total_amount':
        case 'fee_amount':
        case 'net_amount':
          // @todo add test like testPaymentTypeLabel & remove these lines as we can anticipate error will still be caught & handled.
          if (!CRM_Utils_Rule::money($value)) {
            return civicrm_api3_create_error("$key not a valid amount: $value");
          }
          break;

        case 'currency':
          if (!CRM_Utils_Rule::currencyCode($value)) {
            return civicrm_api3_create_error("currency not a valid code: $value");
          }
          break;

        case 'financial_type':
          // @todo add test like testPaymentTypeLabel & remove these lines in favour of 'default' part of switch.
          require_once 'CRM/Contribute/PseudoConstant.php';
          $contriTypes = CRM_Contribute_PseudoConstant::financialType();
          foreach ($contriTypes as $val => $type) {
            if (strtolower($value) == strtolower($type)) {
              $values['financial_type_id'] = $val;
              break;
            }
          }
          if (empty($values['financial_type_id'])) {
            return civicrm_api3_create_error("Financial Type is not valid: $value");
          }
          break;

        case 'soft_credit':
          // import contribution record according to select contact type
          // validate contact id and external identifier.
          $value[$key] = $mismatchContactType = $softCreditContactIds = '';
          if (isset($params[$key]) && is_array($params[$key])) {
            foreach ($params[$key] as $softKey => $softParam) {
              $contactId = $softParam['contact_id'] ?? NULL;
              $externalId = $softParam['external_identifier'] ?? NULL;
              $email = $softParam['email'] ?? NULL;
              if ($contactId || $externalId) {
                require_once 'CRM/Contact/DAO/Contact.php';
                $contact = new CRM_Contact_DAO_Contact();
                $contact->id = $contactId;
                $contact->external_identifier = $externalId;
                $errorMsg = NULL;
                if (!$contact->find(TRUE)) {
                  $field = $contactId ? ts('Contact ID') : ts('External ID');
                  $errorMsg = ts("Soft Credit %1 - %2 doesn't exist. Row was skipped.",
                    [1 => $field, 2 => $contactId ? $contactId : $externalId]);
                }

                if ($errorMsg) {
                  return civicrm_api3_create_error($errorMsg);
                }

                // finally get soft credit contact id.
                $values[$key][$softKey] = $softParam;
                $values[$key][$softKey]['contact_id'] = $contact->id;
              }
              elseif ($email) {
                if (!CRM_Utils_Rule::email($email)) {
                  return civicrm_api3_create_error("Invalid email address $email provided for Soft Credit. Row was skipped");
                }

                // get the contact id from duplicate contact rule, if more than one contact is returned
                // we should return error, since current interface allows only one-one mapping
                $emailParams = [
                  'email' => $email,
                  'contact_type' => $params['contact_type'],
                ];
                $checkDedupe = _civicrm_api3_deprecated_duplicate_formatted_contact($emailParams);
                if (!$checkDedupe['is_error']) {
                  return civicrm_api3_create_error("Invalid email address(doesn't exist) $email for Soft Credit. Row was skipped");
                }
                $matchingContactIds = explode(',', $checkDedupe['error_message']['params'][0]);
                if (count($matchingContactIds) > 1) {
                  return civicrm_api3_create_error("Invalid email address(duplicate) $email for Soft Credit. Row was skipped");
                }
                if (count($matchingContactIds) == 1) {
                  $contactId = $matchingContactIds[0];
                  unset($softParam['email']);
                  $values[$key][$softKey] = $softParam + ['contact_id' => $contactId];
                }
              }
            }
          }
          break;

        case 'pledge_payment':
        case 'pledge_id':

          // giving respect to pledge_payment flag.
          if (empty($params['pledge_payment'])) {
            break;
          }

          // get total amount of from import fields
          $totalAmount = $params['total_amount'] ?? NULL;

          $onDuplicate = $params['onDuplicate'] ?? NULL;

          // we need to get contact id $contributionContactID to
          // retrieve pledge details as well as to validate pledge ID

          // first need to check for update mode
          if ($onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE &&
            ($params['contribution_id'] || $params['trxn_id'] || $params['invoice_id'])
          ) {
            $contribution = new CRM_Contribute_DAO_Contribution();
            if ($params['contribution_id']) {
              $contribution->id = $params['contribution_id'];
            }
            elseif ($params['trxn_id']) {
              $contribution->trxn_id = $params['trxn_id'];
            }
            elseif ($params['invoice_id']) {
              $contribution->invoice_id = $params['invoice_id'];
            }

            if ($contribution->find(TRUE)) {
              $contributionContactID = $contribution->contact_id;
              if (!$totalAmount) {
                $totalAmount = $contribution->total_amount;
              }
            }
            else {
              return civicrm_api3_create_error('No match found for specified contact in pledge payment data. Row was skipped.');
            }
          }
          else {
            // first get the contact id for given contribution record.
            if (!empty($params['contribution_contact_id'])) {
              $contributionContactID = $params['contribution_contact_id'];
            }
            elseif (!empty($params['external_identifier'])) {
              require_once 'CRM/Contact/DAO/Contact.php';
              $contact = new CRM_Contact_DAO_Contact();
              $contact->external_identifier = $params['external_identifier'];
              if ($contact->find(TRUE)) {
                $contributionContactID = $params['contribution_contact_id'] = $values['contribution_contact_id'] = $contact->id;
              }
              else {
                return civicrm_api3_create_error('No match found for specified contact in pledge payment data. Row was skipped.');
              }
            }
            else {
              // we need to get contribution contact using de dupe
              $error = $this->checkContactDuplicate($params);

              if (isset($error['error_message']['params'][0])) {
                $matchedIDs = explode(',', $error['error_message']['params'][0]);

                // check if only one contact is found
                if (count($matchedIDs) > 1) {
                  return civicrm_api3_create_error($error['error_message']['message']);
                }
                $contributionContactID = $params['contribution_contact_id'] = $values['contribution_contact_id'] = $matchedIDs[0];
              }
              else {
                return civicrm_api3_create_error('No match found for specified contact in contribution data. Row was skipped.');
              }
            }
          }

          if (!empty($params['pledge_id'])) {
            if (CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_Pledge', $params['pledge_id'], 'contact_id') != $contributionContactID) {
              return civicrm_api3_create_error('Invalid Pledge ID provided. Contribution row was skipped.');
            }
            $values['pledge_id'] = $params['pledge_id'];
          }
          else {
            // check if there are any pledge related to this contact, with payments pending or in progress
            require_once 'CRM/Pledge/BAO/Pledge.php';
            $pledgeDetails = CRM_Pledge_BAO_Pledge::getContactPledges($contributionContactID);

            if (empty($pledgeDetails)) {
              return civicrm_api3_create_error('No open pledges found for this contact. Contribution row was skipped.');
            }
            if (count($pledgeDetails) > 1) {
              return civicrm_api3_create_error('This contact has more than one open pledge. Unable to determine which pledge to apply the contribution to. Contribution row was skipped.');
            }

            // this mean we have only one pending / in progress pledge
            $values['pledge_id'] = $pledgeDetails[0];
          }

          // we need to check if oldest payment amount equal to contribution amount
          require_once 'CRM/Pledge/BAO/PledgePayment.php';
          $pledgePaymentDetails = CRM_Pledge_BAO_PledgePayment::getOldestPledgePayment($values['pledge_id']);

          if ($pledgePaymentDetails['amount'] == $totalAmount) {
            $values['pledge_payment_id'] = $pledgePaymentDetails['id'];
          }
          else {
            return civicrm_api3_create_error('Contribution and Pledge Payment amount mismatch for this record. Contribution row was skipped.');
          }
          break;

        case 'contribution_campaign_id':
          if (empty(CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Campaign', $params['contribution_campaign_id']))) {
            return civicrm_api3_create_error('Invalid Campaign ID provided. Contribution row was skipped.');
          }
          $values['contribution_campaign_id'] = $params['contribution_campaign_id'];
          break;

        default:
          // Hande name or label for fields with options.
          if (isset($fields[$key]) &&
            // Yay - just for a surprise we are inconsistent on whether we pass the pseudofield (payment_instrument)
            // or the field name (contribution_status_id)
            (!empty($fields[$key]['is_pseudofield_for']) || !empty($fields[$key]['pseudoconstant']))
          ) {
            $realField = $fields[$key]['is_pseudofield_for'] ?? $key;
            $realFieldSpec = $fields[$realField];
            $values[$key] = $this->parsePseudoConstantField($value, $realFieldSpec);
          }
          break;
      }
    }

    if (array_key_exists('note', $params)) {
      $values['note'] = $params['note'];
    }

    if ($create) {
      // CRM_Contribute_BAO_Contribution::add() handles contribution_source
      // So, if $values contains contribution_source, convert it to source
      $changes = ['contribution_source' => 'source'];

      foreach ($changes as $orgVal => $changeVal) {
        if (isset($values[$orgVal])) {
          $values[$changeVal] = $values[$orgVal];
          unset($values[$orgVal]);
        }
      }
    }

    return NULL;
  }

}
