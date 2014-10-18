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


abstract class CRM_Contact_Import_Parser extends CRM_Import_Parser {

  protected $_tableName;

  /**#@+
   * @access protected
   * @var integer
   */

  /**
   * total number of lines in file
   */
  protected $_rowCount;

  /**
   * running total number of un matched Conact
   */
  protected $_unMatchCount;

  /**
   * array of unmatched lines
   */
  protected $_unMatch;

  /**
   * total number of contacts with unparsed addresses
   */
  protected $_unparsedAddressCount;

  /**
   * filename of mismatch data
   *
   * @var string
   */
  protected $_misMatchFilemName;

  protected $_primaryKeyName;
  protected $_statusFieldName;

  /**
   * on duplicate
   *
   * @var int
   */
  public $_onDuplicate;

  /**
   * dedupe rule group id to use if set
   *
   * @var int
   */
  public $_dedupeRuleGroupID = NULL;

  /**
   * @param $tableName
   * @param $mapper
   * @param int $mode
   * @param int $contactType
   * @param string $primaryKeyName
   * @param string $statusFieldName
   * @param int $onDuplicate
   * @param null $statusID
   * @param null $totalRowCount
   * @param bool $doGeocodeAddress
   * @param int $timeout
   * @param null $contactSubType
   * @param null $dedupeRuleGroupID
   *
   * @return mixed
   */
  function run($tableName,
    &$mapper,
    $mode = self::MODE_PREVIEW,
    $contactType = self::CONTACT_INDIVIDUAL,
    $primaryKeyName = '_id',
    $statusFieldName = '_status',
    $onDuplicate = self::DUPLICATE_SKIP,
    $statusID = NULL,
    $totalRowCount = NULL,
    $doGeocodeAddress = FALSE,
    $timeout = CRM_Contact_Import_Parser::DEFAULT_TIMEOUT,
    $contactSubType = NULL,
    $dedupeRuleGroupID = NULL
  ) {

    // TODO: Make the timeout actually work
    $this->_onDuplicate = $onDuplicate;
    $this->_dedupeRuleGroupID = $dedupeRuleGroupID;

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

    $this->_contactSubType = $contactSubType;

    $this->init();

    $this->_rowCount = $this->_warningCount = 0;
    $this->_invalidRowCount = $this->_validCount = 0;
    $this->_totalCount = $this->_conflictCount = 0;

    $this->_errors = array();
    $this->_warnings = array();
    $this->_conflicts = array();
    $this->_unparsedAddresses = array();

    $status = '';

    $this->_tableName = $tableName;
    $this->_primaryKeyName = $primaryKeyName;
    $this->_statusFieldName = $statusFieldName;

    if ($mode == self::MODE_MAPFIELD) {
      $this->_rows = array();
    }
    else {
      $this->_activeFieldCount = count($this->_activeFields);
    }

    if ($mode == self::MODE_IMPORT) {
      //get the key of email field
      foreach ($mapper as $key => $value) {
        if (strtolower($value) == 'email') {
          $emailKey = $key;
          break;
        }
      }
    }

    if ($statusID) {
      $skip = 50;
      // $skip = 1;
      $config = CRM_Core_Config::singleton();
      $statusFile = "{$config->uploadDir}status_{$statusID}.txt";
      $status = "<div class='description'>&nbsp; " . ts('No processing status reported yet.') . "</div>";

      //do not force the browser to display the save dialog, CRM-7640
      $contents = json_encode(array(0, $status));

      file_put_contents($statusFile, $contents);

      $startTimestamp = $currTimestamp = $prevTimestamp = time();
    }

    // get the contents of the temp. import table
    $query = "SELECT * FROM $tableName";
    if ($mode == self::MODE_IMPORT) {
      $query .= " WHERE $statusFieldName = 'NEW'";
    }
    $dao = new CRM_Core_DAO();
    $db = $dao->getDatabaseConnection();
    $result = $db->query($query);

    while ($values = $result->fetchRow(DB_FETCHMODE_ORDERED)) {
      $this->_rowCount++;

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
        //print "Running parser in import mode<br/>\n";
        $returnCode = $this->import($onDuplicate, $values, $doGeocodeAddress);
        if ($statusID && (($this->_rowCount % $skip) == 0)) {
          $currTimestamp = time();
          $totalTime = ($currTimestamp - $startTimestamp);
          $time = ($currTimestamp - $prevTimestamp);
          $recordsLeft = $totalRowCount - $this->_rowCount;
          if ($recordsLeft < 0) {
            $recordsLeft = 0;
          }
          $estimatedTime = ($recordsLeft / $skip) * $time;
          $estMinutes = floor($estimatedTime / 60);
          $timeFormatted = '';
          if ($estMinutes > 1) {
            $timeFormatted = $estMinutes . ' ' . ts('minutes') . ' ';
            $estimatedTime = $estimatedTime - ($estMinutes * 60);
          }
          $timeFormatted .= round($estimatedTime) . ' ' . ts('seconds');
          $processedPercent = (int )(($this->_rowCount * 100) / $totalRowCount);
          $statusMsg = ts('%1 of %2 records - %3 remaining',
            array(1 => $this->_rowCount, 2 => $totalRowCount, 3 => $timeFormatted)
          );
          $status = "
<div class=\"description\">
&nbsp; <strong>{$statusMsg}</strong>
</div>
";

          $contents = json_encode (array($processedPercent, $status));

          file_put_contents($statusFile, $contents);

          $prevTimestamp = $currTimestamp;
        }
        // sleep(1);
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
          $this->_warningCount[] = $line;
        }
      }

      if ($returnCode & self::ERROR) {
        $this->_invalidRowCount++;
        if ($this->_invalidRowCount < $this->_maxErrorCount) {
          array_unshift($values, $this->_rowCount);
          $this->_errors[] = $values;
        }
      }

      if ($returnCode & self::CONFLICT) {
        $this->_conflictCount++;
        array_unshift($values, $this->_rowCount);
        $this->_conflicts[] = $values;
      }

      if ($returnCode & self::NO_MATCH) {
        $this->_unMatchCount++;
        array_unshift($values, $this->_rowCount);
        $this->_unMatch[] = $values;
      }

      if ($returnCode & self::DUPLICATE) {
        if ($returnCode & self::MULTIPLE_DUPE) {
          /* TODO: multi-dupes should be counted apart from singles
                     * on non-skip action */
        }
        $this->_duplicateCount++;
        array_unshift($values, $this->_rowCount);
        $this->_duplicates[] = $values;
        if ($onDuplicate != self::DUPLICATE_SKIP) {
          $this->_validCount++;
        }
      }

      if ($returnCode & self::UNPARSED_ADDRESS_WARNING) {
        $this->_unparsedAddressCount++;
        array_unshift($values, $this->_rowCount);
        $this->_unparsedAddresses[] = $values;
      }
      // we give the derived class a way of aborting the process
      // note that the return code could be multiple code or'ed together
      if ($returnCode & self::STOP) {
        break;
      }

      // if we are done processing the maxNumber of lines, break
      if ($this->_maxLinesToProcess > 0 && $this->_validCount >= $this->_maxLinesToProcess) {
        break;
      }

      // clean up memory from dao's
      CRM_Core_DAO::freeResult();

      // see if we've hit our timeout yet
      /* if ( $the_thing_with_the_stuff ) {
                do_something( );
            } */
    }


    if ($mode == self::MODE_PREVIEW || $mode == self::MODE_IMPORT) {
      $customHeaders = $mapper;

      $customfields = CRM_Core_BAO_CustomField::getFields($this->_contactType);
      foreach ($customHeaders as $key => $value) {
        if ($id = CRM_Core_BAO_CustomField::getKeyID($value)) {
          $customHeaders[$key] = $customfields[$id][0];
        }
      }

      if ($this->_invalidRowCount) {
        // removed view url for invlaid contacts
        $headers = array_merge(array(ts('Line Number'),
            ts('Reason'),
          ),
          $customHeaders
        );
        $this->_errorFileName = self::errorFileName(self::ERROR);
        self::exportCSV($this->_errorFileName, $headers, $this->_errors);
      }
      if ($this->_conflictCount) {
        $headers = array_merge(array(ts('Line Number'),
            ts('Reason'),
          ),
          $customHeaders
        );
        $this->_conflictFileName = self::errorFileName(self::CONFLICT);
        self::exportCSV($this->_conflictFileName, $headers, $this->_conflicts);
      }
      if ($this->_duplicateCount) {
        $headers = array_merge(array(ts('Line Number'),
            ts('View Contact URL'),
          ),
          $customHeaders
        );

        $this->_duplicateFileName = self::errorFileName(self::DUPLICATE);
        self::exportCSV($this->_duplicateFileName, $headers, $this->_duplicates);
      }
      if ($this->_unMatchCount) {
        $headers = array_merge(array(ts('Line Number'),
            ts('Reason'),
          ),
          $customHeaders
        );

        $this->_misMatchFilemName = self::errorFileName(self::NO_MATCH);
        self::exportCSV($this->_misMatchFilemName, $headers, $this->_unMatch);
      }
      if ($this->_unparsedAddressCount) {
        $headers = array_merge(array(ts('Line Number'),
            ts('Contact Edit URL'),
          ),
          $customHeaders
        );
        $this->_errorFileName = self::errorFileName(self::UNPARSED_ADDRESS_WARNING);
        self::exportCSV($this->_errorFileName, $headers, $this->_unparsedAddresses);
      }
    }
    //echo "$this->_totalCount,$this->_invalidRowCount,$this->_conflictCount,$this->_duplicateCount";
    return $this->fini();
  }

  /**
   * Given a list of the importable field keys that the user has selected
   * set the active fields array to this list
   *
   * @param array mapped array of values
   *
   * @return void
   * @access public
   */
  function setActiveFields($fieldKeys) {
    $this->_activeFieldCount = count($fieldKeys);
    foreach ($fieldKeys as $key) {
      if (empty($this->_fields[$key])) {
        $this->_activeFields[] = new CRM_Contact_Import_Field('', ts('- do not import -'));
      }
      else {
        $this->_activeFields[] = clone($this->_fields[$key]);
      }
    }
  }

  /**
   * @param $elements
   */
  function setActiveFieldLocationTypes($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_hasLocationType = $elements[$i];
    }
  }

  /**
   * @param $elements
   */
  /**
   * @param $elements
   */
  function setActiveFieldPhoneTypes($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_phoneType = $elements[$i];
    }
  }

  /**
   * @param $elements
   */
  function setActiveFieldWebsiteTypes($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_websiteType = $elements[$i];
    }
  }

  /**
   * Function to set IM Service Provider type fields
   *
   * @param array $elements IM service provider type ids
   *
   * @return void
   * @access public
   */
  function setActiveFieldImProviders($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_imProvider = $elements[$i];
    }
  }

  /**
   * @param $elements
   */
  function setActiveFieldRelated($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_related = $elements[$i];
    }
  }

  /**
   * @param $elements
   */
  function setActiveFieldRelatedContactType($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_relatedContactType = $elements[$i];
    }
  }

  /**
   * @param $elements
   */
  function setActiveFieldRelatedContactDetails($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_relatedContactDetails = $elements[$i];
    }
  }

  /**
   * @param $elements
   */
  function setActiveFieldRelatedContactLocType($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_relatedContactLocType = $elements[$i];
    }
  }

  /**
   * @param $elements
   */
  function setActiveFieldRelatedContactPhoneType($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_relatedContactPhoneType = $elements[$i];
    }
  }

  /**
   * @param $elements
   */
  function setActiveFieldRelatedContactWebsiteType($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_relatedContactWebsiteType = $elements[$i];
    }
  }

  /**
   * Function to set IM Service Provider type fields for related contacts
   *
   * @param array $elements IM service provider type ids of related contact
   *
   * @return void
   * @access public
   */
  function setActiveFieldRelatedContactImProvider($elements) {
    for ($i = 0; $i < count($elements); $i++) {
      $this->_activeFields[$i]->_relatedContactImProvider = $elements[$i];
    }
  }

  /**
   * function to format the field values for input to the api
   *
   * @return array (reference ) associative array of name/value pairs
   * @access public
   */
  function &getActiveFieldParams() {
    $params = array();

    //CRM_Core_Error::debug( 'Count', $this->_activeFieldCount );
    for ($i = 0; $i < $this->_activeFieldCount; $i++) {
      if ($this->_activeFields[$i]->_name == 'do_not_import') {
        continue;
      }

      if (isset($this->_activeFields[$i]->_value)) {
        if (isset($this->_activeFields[$i]->_hasLocationType)) {
          if (!isset($params[$this->_activeFields[$i]->_name])) {
            $params[$this->_activeFields[$i]->_name] = array();
          }

          $value = array(
            $this->_activeFields[$i]->_name =>
            $this->_activeFields[$i]->_value,
            'location_type_id' =>
            $this->_activeFields[$i]->_hasLocationType,
          );

          if (isset($this->_activeFields[$i]->_phoneType)) {
            $value['phone_type_id'] = $this->_activeFields[$i]->_phoneType;
          }

          // get IM service Provider type id
          if (isset($this->_activeFields[$i]->_imProvider)) {
            $value['provider_id'] = $this->_activeFields[$i]->_imProvider;
          }

          $params[$this->_activeFields[$i]->_name][] = $value;
        }
        elseif (isset($this->_activeFields[$i]->_websiteType)) {
          $value = array(
            $this->_activeFields[$i]->_name => $this->_activeFields[$i]->_value,
            'website_type_id' => $this->_activeFields[$i]->_websiteType,
          );

          $params[$this->_activeFields[$i]->_name][] = $value;
        }

        if (!isset($params[$this->_activeFields[$i]->_name])) {
          if (!isset($this->_activeFields[$i]->_related)) {
            $params[$this->_activeFields[$i]->_name] = $this->_activeFields[$i]->_value;
          }
        }

        //minor fix for CRM-4062
        if (isset($this->_activeFields[$i]->_related)) {
          if (!isset($params[$this->_activeFields[$i]->_related])) {
            $params[$this->_activeFields[$i]->_related] = array();
          }

          if (!isset($params[$this->_activeFields[$i]->_related]['contact_type']) && !empty($this->_activeFields[$i]->_relatedContactType)) {
            $params[$this->_activeFields[$i]->_related]['contact_type'] = $this->_activeFields[$i]->_relatedContactType;
          }

          if (isset($this->_activeFields[$i]->_relatedContactLocType) && !empty($this->_activeFields[$i]->_value)) {
            if (!empty($params[$this->_activeFields[$i]->_related][$this->_activeFields[$i]->_relatedContactDetails]) &&
              !is_array($params[$this->_activeFields[$i]->_related][$this->_activeFields[$i]->_relatedContactDetails])) {
              $params[$this->_activeFields[$i]->_related][$this->_activeFields[$i]->_relatedContactDetails] = array();
            }
            $value = array(
              $this->_activeFields[$i]->_relatedContactDetails => $this->_activeFields[$i]->_value,
              'location_type_id' => $this->_activeFields[$i]->_relatedContactLocType,
            );

            if (isset($this->_activeFields[$i]->_relatedContactPhoneType)) {
              $value['phone_type_id'] = $this->_activeFields[$i]->_relatedContactPhoneType;
            }

            // get IM service Provider type id for related contact
            if (isset($this->_activeFields[$i]->_relatedContactImProvider)) {
              $value['provider_id'] = $this->_activeFields[$i]->_relatedContactImProvider;
            }

            $params[$this->_activeFields[$i]->_related][$this->_activeFields[$i]->_relatedContactDetails][] = $value;
          }
          elseif (isset($this->_activeFields[$i]->_relatedContactWebsiteType)) {
            $params[$this->_activeFields[$i]->_related][$this->_activeFields[$i]->_relatedContactDetails][] = array(
              'url' => $this->_activeFields[$i]->_value,
              'website_type_id' => $this->_activeFields[$i]->_relatedContactWebsiteType,
            );
          }
          else {
            $params[$this->_activeFields[$i]->_related][$this->_activeFields[$i]->_relatedContactDetails] = $this->_activeFields[$i]->_value;
          }
        }
      }
    }

    return $params;
  }

  /**
   * @return array
   */
  function getColumnPatterns() {
    $values = array();
    foreach ($this->_fields as $name => $field) {
      $values[$name] = $field->_columnPattern;
    }
    return $values;
  }

  /**
   * @param $name
   * @param $title
   * @param int $type
   * @param string $headerPattern
   * @param string $dataPattern
   * @param bool $hasLocationType
   */
  function addField($name, $title, $type = CRM_Utils_Type::T_INT,
    $headerPattern = '//', $dataPattern = '//',
    $hasLocationType = FALSE
  ) {
    $this->_fields[$name] = new CRM_Contact_Import_Field($name, $title, $type, $headerPattern, $dataPattern, $hasLocationType);
    if (empty($name)) {
      $this->_fields['doNotImport'] = new CRM_Contact_Import_Field($name, $title, $type, $headerPattern, $dataPattern, $hasLocationType);
    }
  }

  /**
   * Store parser values
   *
   * @param CRM_Core_Session $store
   *
   * @param int $mode
   *
   * @return void
   * @access public
   */
  function set($store, $mode = self::MODE_SUMMARY) {
    $store->set('rowCount', $this->_rowCount);
    $store->set('fields', $this->getSelectValues());
    $store->set('fieldTypes', $this->getSelectTypes());

    $store->set('columnPatterns', $this->getColumnPatterns());
    $store->set('dataPatterns', $this->getDataPatterns());
    $store->set('columnCount', $this->_activeFieldCount);

    $store->set('totalRowCount', $this->_totalCount);
    $store->set('validRowCount', $this->_validCount);
    $store->set('invalidRowCount', $this->_invalidRowCount);
    $store->set('conflictRowCount', $this->_conflictCount);
    $store->set('unMatchCount', $this->_unMatchCount);

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

    if ($this->_unMatchCount) {
      $store->set('mismatchFileName', $this->_misMatchFilemName);
    }

    if ($mode == self::MODE_IMPORT) {
      $store->set('duplicateRowCount', $this->_duplicateCount);
      $store->set('unparsedAddressCount', $this->_unparsedAddressCount);
      if ($this->_duplicateCount) {
        $store->set('duplicatesFileName', $this->_duplicateFileName);
      }
      if ($this->_unparsedAddressCount) {
        $store->set('errorsFileName', $this->_errorFileName);
      }
    }
    //echo "$this->_totalCount,$this->_invalidRowCount,$this->_conflictCount,$this->_duplicateCount";
  }

  /**
   * Export data to a CSV file
   *
   * @param $fileName
   * @param array $header
   * @param data $data
   *
   * @internal param string $filename
   * @return void
   * @access public
   */
  static function exportCSV($fileName, $header, $data) {

    if (file_exists($fileName) && !is_writable($fileName)) {
      CRM_Core_Error::movedSiteError($fileName);
    }
    //hack to remove '_status', '_statusMsg' and '_id' from error file
    $errorValues = array();
    $dbRecordStatus = array('IMPORTED', 'ERROR', 'DUPLICATE', 'INVALID', 'NEW');
    foreach ($data as $rowCount => $rowValues) {
      $count = 0;
      foreach ($rowValues as $key => $val) {
        if (in_array($val, $dbRecordStatus) && $count == (count($rowValues) - 3)) {
          break;
        }
        $errorValues[$rowCount][$key] = $val;
        $count++;
      }
    }
    $data = $errorValues;

    $output = array();
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
   * Update the record with PK $id in the import database table
   *
   * @param int $id
   * @param array $params
   *
   * @return void
   * @access public
   */
  public function updateImportRecord($id, &$params) {
    $statusFieldName = $this->_statusFieldName;
    $primaryKeyName = $this->_primaryKeyName;

    if ($statusFieldName && $primaryKeyName) {
      $dao = new CRM_Core_DAO();
      $db = $dao->getDatabaseConnection();

      $query = "UPDATE $this->_tableName
                      SET    $statusFieldName = ?,
                             ${statusFieldName}Msg = ?
                      WHERE  $primaryKeyName = ?";
      $args = array(
        $params[$statusFieldName],
        CRM_Utils_Array::value("${statusFieldName}Msg", $params),
        $id,
      );

      //print "Running query: $query<br/>With arguments: ".$params[$statusFieldName].", ".$params["${statusFieldName}Msg"].", $id<br/>";

      $db->query($query, $args);
    }
  }

}

