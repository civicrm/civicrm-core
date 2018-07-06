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
abstract class CRM_Import_Parser {
  /**
   * Settings
   */
  const MAX_WARNINGS = 25, DEFAULT_TIMEOUT = 30;

  /**
   * Return codes
   */
  const VALID = 1, WARNING = 2, ERROR = 4, CONFLICT = 8, STOP = 16, DUPLICATE = 32, MULTIPLE_DUPE = 64, NO_MATCH = 128, UNPARSED_ADDRESS_WARNING = 256;

  /**
   * Parser modes
   */
  const MODE_MAPFIELD = 1, MODE_PREVIEW = 2, MODE_SUMMARY = 4, MODE_IMPORT = 8;

  /**
   * Codes for duplicate record handling
   */
  const DUPLICATE_SKIP = 1, DUPLICATE_REPLACE = 2, DUPLICATE_UPDATE = 4, DUPLICATE_FILL = 8, DUPLICATE_NOCHECK = 16;

  /**
   * Contact types
   */
  const CONTACT_INDIVIDUAL = 1, CONTACT_HOUSEHOLD = 2, CONTACT_ORGANIZATION = 4;


  /**
   * Total number of non empty lines
   */
  protected $_totalCount;

  /**
   * Running total number of valid lines
   */
  protected $_validCount;

  /**
   * Running total number of invalid rows
   */
  protected $_invalidRowCount;

  /**
   * Maximum number of non-empty/comment lines to process
   *
   * @var int
   */
  protected $_maxLinesToProcess;

  /**
   * Array of error lines, bounded by MAX_ERROR
   */
  protected $_errors;

  /**
   * Total number of conflict lines
   */
  protected $_conflictCount;

  /**
   * Array of conflict lines
   */
  protected $_conflicts;

  /**
   * Total number of duplicate (from database) lines
   */
  protected $_duplicateCount;

  /**
   * Array of duplicate lines
   */
  protected $_duplicates;

  /**
   * Running total number of warnings
   */
  protected $_warningCount;

  /**
   * Maximum number of warnings to store
   */
  protected $_maxWarningCount = self::MAX_WARNINGS;

  /**
   * Array of warning lines, bounded by MAX_WARNING
   */
  protected $_warnings;

  /**
   * Array of all the fields that could potentially be part
   * of this import process
   * @var array
   */
  protected $_fields;

  /**
   * Array of the fields that are actually part of the import process
   * the position in the array also dictates their position in the import
   * file
   * @var array
   */
  protected $_activeFields;

  /**
   * Cache the count of active fields
   *
   * @var int
   */
  protected $_activeFieldCount;

  /**
   * Cache of preview rows
   *
   * @var array
   */
  protected $_rows;

  /**
   * Filename of error data
   *
   * @var string
   */
  protected $_errorFileName;

  /**
   * Filename of conflict data
   *
   * @var string
   */
  protected $_conflictFileName;

  /**
   * Filename of duplicate data
   *
   * @var string
   */
  protected $_duplicateFileName;

  /**
   * Contact type
   *
   * @var int
   */
  public $_contactType;
  /**
   * Contact sub-type
   *
   * @var int
   */
  public $_contactSubType;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_maxLinesToProcess = 0;
  }

  /**
   * Abstract function definitions.
   */
  abstract protected function init();

  /**
   * @return mixed
   */
  abstract protected function fini();

  /**
   * Map field.
   *
   * @param array $values
   *
   * @return mixed
   */
  abstract protected function mapField(&$values);

  /**
   * Preview.
   *
   * @param array $values
   *
   * @return mixed
   */
  abstract protected function preview(&$values);

  /**
   * @param $values
   *
   * @return mixed
   */
  abstract protected function summary(&$values);

  /**
   * @param $onDuplicate
   * @param $values
   *
   * @return mixed
   */
  abstract protected function import($onDuplicate, &$values);

  /**
   * Set and validate field values.
   *
   * @param array $elements
   *   array.
   * @param $erroneousField
   *   reference.
   *
   * @return int
   */
  public function setActiveFieldValues($elements, &$erroneousField) {
    $maxCount = count($elements) < $this->_activeFieldCount ? count($elements) : $this->_activeFieldCount;
    for ($i = 0; $i < $maxCount; $i++) {
      $this->_activeFields[$i]->setValue($elements[$i]);
    }

    // reset all the values that we did not have an equivalent import element
    for (; $i < $this->_activeFieldCount; $i++) {
      $this->_activeFields[$i]->resetValue();
    }

    // now validate the fields and return false if error
    $valid = self::VALID;
    for ($i = 0; $i < $this->_activeFieldCount; $i++) {
      if (!$this->_activeFields[$i]->validate()) {
        // no need to do any more validation
        $erroneousField = $i;
        $valid = self::ERROR;
        break;
      }
    }
    return $valid;
  }

  /**
   * Format the field values for input to the api.
   *
   * @return array
   *   (reference) associative array of name/value pairs
   */
  public function &getActiveFieldParams() {
    $params = array();
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
   * Add progress bar to the import process. Calculates time remaining, status etc.
   *
   * @param $statusID
   *   status id of the import process saved in $config->uploadDir.
   * @param bool $startImport
   *   True when progress bar is to be initiated.
   * @param $startTimestamp
   *   Initial timstamp when the import was started.
   * @param $prevTimestamp
   *   Previous timestamp when this function was last called.
   * @param $totalRowCount
   *   Total number of rows in the import file.
   *
   * @return NULL|$currTimestamp
   */
  public function progressImport($statusID, $startImport = TRUE, $startTimestamp = NULL, $prevTimestamp = NULL, $totalRowCount = NULL) {
    $config = CRM_Core_Config::singleton();
    $statusFile = "{$config->uploadDir}status_{$statusID}.txt";

    if ($startImport) {
      $status = "<div class='description'>&nbsp; " . ts('No processing status reported yet.') . "</div>";
      //do not force the browser to display the save dialog, CRM-7640
      $contents = json_encode(array(0, $status));
      file_put_contents($statusFile, $contents);
    }
    else {
      $rowCount = isset($this->_rowCount) ? $this->_rowCount : $this->_lineCount;
      $currTimestamp = time();
      $totalTime = ($currTimestamp - $startTimestamp);
      $time = ($currTimestamp - $prevTimestamp);
      $recordsLeft = $totalRowCount - $rowCount;
      if ($recordsLeft < 0) {
        $recordsLeft = 0;
      }
      $estimatedTime = ($recordsLeft / 50) * $time;
      $estMinutes = floor($estimatedTime / 60);
      $timeFormatted = '';
      if ($estMinutes > 1) {
        $timeFormatted = $estMinutes . ' ' . ts('minutes') . ' ';
        $estimatedTime = $estimatedTime - ($estMinutes * 60);
      }
      $timeFormatted .= round($estimatedTime) . ' ' . ts('seconds');
      $processedPercent = (int ) (($rowCount * 100) / $totalRowCount);
      $statusMsg = ts('%1 of %2 records - %3 remaining',
        array(1 => $rowCount, 2 => $totalRowCount, 3 => $timeFormatted)
      );
      $status = "<div class=\"description\">&nbsp; <strong>{$statusMsg}</strong></div>";
      $contents = json_encode(array($processedPercent, $status));

      file_put_contents($statusFile, $contents);
      return $currTimestamp;
    }
  }

  /**
   * @return array
   */
  public function getSelectValues() {
    $values = array();
    foreach ($this->_fields as $name => $field) {
      $values[$name] = $field->_title;
    }
    return $values;
  }

  /**
   * @return array
   */
  public function getSelectTypes() {
    $values = array();
    foreach ($this->_fields as $name => $field) {
      if (isset($field->_hasLocationType)) {
        $values[$name] = $field->_hasLocationType;
      }
    }
    return $values;
  }

  /**
   * @return array
   */
  public function getHeaderPatterns() {
    $values = array();
    foreach ($this->_fields as $name => $field) {
      if (isset($field->_headerPattern)) {
        $values[$name] = $field->_headerPattern;
      }
    }
    return $values;
  }

  /**
   * @return array
   */
  public function getDataPatterns() {
    $values = array();
    foreach ($this->_fields as $name => $field) {
      $values[$name] = $field->_dataPattern;
    }
    return $values;
  }

  /**
   * Remove single-quote enclosures from a value array (row).
   *
   * @param array $values
   * @param string $enclosure
   *
   * @return void
   */
  public static function encloseScrub(&$values, $enclosure = "'") {
    if (empty($values)) {
      return;
    }

    foreach ($values as $k => $v) {
      $values[$k] = preg_replace("/^$enclosure(.*)$enclosure$/", '$1', $v);
    }
  }

  /**
   * Setter function.
   *
   * @param int $max
   *
   * @return void
   */
  public function setMaxLinesToProcess($max) {
    $this->_maxLinesToProcess = $max;
  }

  /**
   * Determines the file extension based on error code.
   *
   * @var $type error code constant
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
      case self::ERROR:
        $fileName .= '.errors';
        break;

      case self::CONFLICT:
        $fileName .= '.conflicts';
        break;

      case self::DUPLICATE:
        $fileName .= '.duplicates';
        break;

      case self::NO_MATCH:
        $fileName .= '.mismatch';
        break;

      case self::UNPARSED_ADDRESS_WARNING:
        $fileName .= '.unparsedAddress';
        break;
    }

    return $fileName;
  }

  /**
   * Determines the file name based on error code.
   *
   * @var $type error code constant
   * @return string
   */
  public static function saveFileName($type) {
    $fileName = NULL;
    if (empty($type)) {
      return $fileName;
    }
    switch ($type) {
      case self::ERROR:
        $fileName = 'Import_Errors.csv';
        break;

      case self::CONFLICT:
        $fileName = 'Import_Conflicts.csv';
        break;

      case self::DUPLICATE:
        $fileName = 'Import_Duplicates.csv';
        break;

      case self::NO_MATCH:
        $fileName = 'Import_Mismatch.csv';
        break;

      case self::UNPARSED_ADDRESS_WARNING:
        $fileName = 'Import_Unparsed_Address.csv';
        break;
    }

    return $fileName;
  }

  /**
   * Check if contact is a duplicate .
   *
   * @param array $formatValues
   *
   * @return array
   */
  protected function checkContactDuplicate(&$formatValues) {
    //retrieve contact id using contact dedupe rule
    $formatValues['contact_type'] = $this->_contactType;
    $formatValues['version'] = 3;
    require_once 'CRM/Utils/DeprecatedUtils.php';
    $error = _civicrm_api3_deprecated_check_contact_dedupe($formatValues);
    return $error;
  }

}
