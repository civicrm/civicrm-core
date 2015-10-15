<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */
abstract class CRM_Contribute_Import_Parser extends CRM_Import_Parser {

  /**
   * Contribution-specific result codes
   * @see CRM_Import_Parser result code constants
   */
  const SOFT_CREDIT = 512, SOFT_CREDIT_ERROR = 1024, PLEDGE_PAYMENT = 2048, PLEDGE_PAYMENT_ERROR = 4096;

  protected $_fileName;

  /**
   * Imported file size
   */
  protected $_fileSize;

  /**
   * Seperator being used
   */
  protected $_seperator;

  /**
   * Total number of lines in file
   */
  protected $_lineCount;

  /**
   * Running total number of valid soft credit rows
   */
  protected $_validSoftCreditRowCount;

  /**
   * Running total number of invalid soft credit rows
   */
  protected $_invalidSoftCreditRowCount;

  /**
   * Running total number of valid pledge payment rows
   */
  protected $_validPledgePaymentRowCount;

  /**
   * Running total number of invalid pledge payment rows
   */
  protected $_invalidPledgePaymentRowCount;

  /**
   * Array of pledge payment error lines, bounded by MAX_ERROR
   */
  protected $_pledgePaymentErrors;

  /**
   * Array of pledge payment error lines, bounded by MAX_ERROR
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
   * @var boolean
   */
  protected $_haveColumnHeader;

  /**
   * @param string $fileName
   * @param string $seperator
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
    $seperator = ',',
    &$mapper,
    $skipColumnHeader = FALSE,
    $mode = self::MODE_PREVIEW,
    $contactType = self::CONTACT_INDIVIDUAL,
    $onDuplicate = self::DUPLICATE_SKIP
  ) {
    if (!is_array($fileName)) {
      CRM_Core_Error::fatal();
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

    $this->_seperator = $seperator;

    $fd = fopen($fileName, "r");
    if (!$fd) {
      return FALSE;
    }

    $this->_lineCount = $this->_warningCount = $this->_validSoftCreditRowCount = $this->_validPledgePaymentRowCount = 0;
    $this->_invalidRowCount = $this->_validCount = $this->_invalidSoftCreditRowCount = $this->_invalidPledgePaymentRowCount = 0;
    $this->_totalCount = $this->_conflictCount = 0;

    $this->_errors = array();
    $this->_warnings = array();
    $this->_conflicts = array();
    $this->_pledgePaymentErrors = array();
    $this->_softCreditErrors = array();

    $this->_fileSize = number_format(filesize($fileName) / 1024.0, 2);

    if ($mode == self::MODE_MAPFIELD) {
      $this->_rows = array();
    }
    else {
      $this->_activeFieldCount = count($this->_activeFields);
    }

    while (!feof($fd)) {
      $this->_lineCount++;

      $values = fgetcsv($fd, 8192, $seperator);
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
        if ($this->_invalidRowCount < $this->_maxErrorCount) {
          $recordNumber = $this->_lineCount;
          if ($this->_haveColumnHeader) {
            $recordNumber--;
          }
          array_unshift($values, $recordNumber);
          $this->_errors[] = $values;
        }
      }

      if ($returnCode == self::PLEDGE_PAYMENT_ERROR) {
        $this->_invalidPledgePaymentRowCount++;
        if ($this->_invalidPledgePaymentRowCount < $this->_maxErrorCount) {
          $recordNumber = $this->_lineCount;
          if ($this->_haveColumnHeader) {
            $recordNumber--;
          }
          array_unshift($values, $recordNumber);
          $this->_pledgePaymentErrors[] = $values;
        }
      }

      if ($returnCode == self::SOFT_CREDIT_ERROR) {
        $this->_invalidSoftCreditRowCount++;
        if ($this->_invalidSoftCreditRowCount < $this->_maxErrorCount) {
          $recordNumber = $this->_lineCount;
          if ($this->_haveColumnHeader) {
            $recordNumber--;
          }
          array_unshift($values, $recordNumber);
          $this->_softCreditErrors[] = $values;
        }
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
        if ($returnCode == self::MULTIPLE_DUPE) {
          /* TODO: multi-dupes should be counted apart from singles
           * on non-skip action */
        }
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
        $headers = array_merge(array(
            ts('Line Number'),
            ts('Reason'),
          ),
          $customHeaders
        );
        $this->_errorFileName = self::errorFileName(self::ERROR);
        self::exportCSV($this->_errorFileName, $headers, $this->_errors);
      }

      if ($this->_invalidPledgePaymentRowCount) {
        // removed view url for invlaid contacts
        $headers = array_merge(array(
            ts('Line Number'),
            ts('Reason'),
          ),
          $customHeaders
        );
        $this->_pledgePaymentErrorsFileName = self::errorFileName(self::PLEDGE_PAYMENT_ERROR);
        self::exportCSV($this->_pledgePaymentErrorsFileName, $headers, $this->_pledgePaymentErrors);
      }

      if ($this->_invalidSoftCreditRowCount) {
        // removed view url for invlaid contacts
        $headers = array_merge(array(
            ts('Line Number'),
            ts('Reason'),
          ),
          $customHeaders
        );
        $this->_softCreditErrorsFileName = self::errorFileName(self::SOFT_CREDIT_ERROR);
        self::exportCSV($this->_softCreditErrorsFileName, $headers, $this->_softCreditErrors);
      }

      if ($this->_conflictCount) {
        $headers = array_merge(array(
            ts('Line Number'),
            ts('Reason'),
          ),
          $customHeaders
        );
        $this->_conflictFileName = self::errorFileName(self::CONFLICT);
        self::exportCSV($this->_conflictFileName, $headers, $this->_conflicts);
      }
      if ($this->_duplicateCount) {
        $headers = array_merge(array(
            ts('Line Number'),
            ts('View Contribution URL'),
          ),
          $customHeaders
        );

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
   * @param array $elements
   */
  public function setActiveFieldSoftCredit($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_softCreditField = $elements[$i];
    }
  }

  /**
   * @param array $elements
   */
  public function setActiveFieldSoftCreditType($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_softCreditType = $elements[$i];
    }
  }

  /**
   * Format the field values for input to the api.
   *
   * @return array
   *   (reference ) associative array of name/value pairs
   */
  public function &getActiveFieldParams() {
    $params = array();
    for ($i = 0; $i < $this->_activeFieldCount; $i++) {
      if (isset($this->_activeFields[$i]->_value)) {
        if (isset($this->_activeFields[$i]->_softCreditField)) {
          if (!isset($params[$this->_activeFields[$i]->_name])) {
            $params[$this->_activeFields[$i]->_name] = array();
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
    $store->set('seperator', $this->_seperator);
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
    $output = array();
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
      case CRM_Contribute_Import_Parser::SOFT_CREDIT_ERROR:
        $fileName .= '.softCreditErrors';
        break;

      case CRM_Contribute_Import_Parser::PLEDGE_PAYMENT_ERROR:
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
      case CRM_Contribute_Import_Parser::SOFT_CREDIT_ERROR:
        $fileName = 'Import_Soft_Credit_Errors.csv';
        break;

      case CRM_Contribute_Import_Parser::PLEDGE_PAYMENT_ERROR:
        $fileName = 'Import_Pledge_Payment_Errors.csv';
        break;

      default:
        $fileName = parent::saveFileName($type);
        break;
    }

    return $fileName;
  }

}
