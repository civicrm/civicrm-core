<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */


abstract class CRM_Custom_Import_Parser extends CRM_Import_Parser {

  protected $_fileName;

  /**#@+
   * @access protected
   * @var integer
   */

  /**
   * imported file size
   */
  protected $_fileSize;

  /**
   * separator being used
   */
  protected $_separator;

  /**
   * total number of lines in file
   */
  protected $_lineCount;

  /**
   * whether the file has a column header or not
   *
   * @var boolean
   */
  protected $_haveColumnHeader;

  function run($fileName,
    $separator = ',',
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
    $this->_totalCount = $this->_conflictCount = 0;

    $this->_errors = array();
    $this->_warnings = array();
    $this->_conflicts = array();

    $this->_fileSize = number_format(filesize($fileName) / 1024.0, 2);

    if ($mode == self::MODE_MAPFIELD) {
      $this->_rows = array();
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
          $recordNumber = $this->_lineCount;
          if ($this->_haveColumnHeader) {
            $recordNumber--;
          }
          array_unshift($values, $recordNumber);
          $this->_errors[] = $values;
        }
      }

      if ($returnCode & self::CONFLICT) {
        $this->_conflictCount++;
        $recordNumber = $this->_lineCount;
        if ($this->_haveColumnHeader) {
          $recordNumber--;
        }
        array_unshift($values, $recordNumber);
        $this->_conflicts[] = $values;
      }

      if ($returnCode & self::DUPLICATE) {
        if ($returnCode & self::MULTIPLE_DUPE) {
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
      if ($returnCode & self::STOP) {
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

      $customfields = CRM_Core_BAO_CustomField::getFields('Activity');
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
            ts('View Activity History URL'),
          ),
          $customHeaders
        );

        $this->_duplicateFileName = self::errorFileName(self::DUPLICATE);
        self::exportCSV($this->_duplicateFileName, $headers, $this->_duplicates);
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
        $this->_activeFields[] = new CRM_Custom_Import_Field('', ts('- do not import -'));
      }
      else {
        $this->_activeFields[] = clone($this->_fields[$key]);
      }
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

  function addField($name, $title, $type = CRM_Utils_Type::T_INT, $headerPattern = '//', $dataPattern = '//') {
    if (empty($name)) {
      $this->_fields['doNotImport'] = new CRM_Custom_Import_Field($name, $title, $type, $headerPattern, $dataPattern);
    }
    else {
      //$tempField = CRM_Contact_BAO_Contact::importableFields('Individual', null );
      $tempField = CRM_Contact_BAO_Contact::importableFields('All', NULL);
      if (!array_key_exists($name, $tempField)) {
        $this->_fields[$name] = new CRM_Custom_Import_Field($name, $title, $type, $headerPattern, $dataPattern);
      }
      else {
        $this->_fields[$name] = new CRM_Contact_Import_Field($name, $title, $type, $headerPattern, $dataPattern,
          CRM_Utils_Array::value('hasLocationType', $tempField[$name])
        );
      }
    }
  }

  /**
   * Store parser values
   *
   * @param CRM_Core_Session $store
   *
   * @return void
   * @access public
   */
  function set($store, $mode = self::MODE_SUMMARY) {
    $store->set('fileSize', $this->_fileSize);
    $store->set('lineCount', $this->_lineCount);
    $store->set('seperator', $this->_separator);
    $store->set('fields', $this->getSelectValues());
    $store->set('fieldTypes', $this->getSelectTypes());

    $store->set('headerPatterns', $this->getHeaderPatterns());
    $store->set('dataPatterns', $this->getDataPatterns());
    $store->set('columnCount', $this->_activeFieldCount);
    $store->set('_entity', $this->_entity);
    $store->set('totalRowCount', $this->_totalCount);
    $store->set('validRowCount', $this->_validCount);
    $store->set('invalidRowCount', $this->_invalidRowCount);
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

    if ($mode == self::MODE_IMPORT) {
      $store->set('duplicateRowCount', $this->_duplicateCount);
      if ($this->_duplicateCount) {
        $store->set('duplicatesFileName', $this->_duplicateFileName);
      }
    }
  }

  /**
   * function to check if an error in custom data
   *
   * @param String   $errorMessage   A string containing all the error-fields.
   *
   * @access public
   */
  function isErrorInCustomData(&$params, &$errorMessage, $csType = NULL, $relationships = NULL) {
    $session = CRM_Core_Session::singleton();
    $dateType = $session->get("dateTypes");

    if (CRM_Utils_Array::value('contact_sub_type', $params)) {
      $csType = CRM_Utils_Array::value('contact_sub_type', $params);
    }

    if (!CRM_Utils_Array::value('contact_type', $params)) {
      $params['contact_type'] = 'Individual';
    }
    $customFields = CRM_Core_BAO_CustomField::getFields($this->_contactType, FALSE, FALSE, $csType);

    $addressCustomFields = CRM_Core_BAO_CustomField::getFields('Address');
    $customFields = $customFields + $addressCustomFields;
    foreach ($params as $key => $value) {
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
        /* check if it's a valid custom field id */
        if (!array_key_exists($customFieldID, $customFields)) {
          self::addToErrorMsg(ts('field ID'), $errorMessage);
          break;
        }
        //For address custom fields, we do get actual custom field value as an inner array of
        //values so need to modify
        if (array_key_exists($customFieldID, $addressCustomFields)) {
          $value = $value[$key];
        }
        /* validate the data against the CF type */

        if ($value) {
          if ($customFields[$customFieldID]['data_type'] == 'Date') {
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              $value = $params[$key];
            }
            else {
              self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
            }
          }
          elseif ($customFields[$customFieldID]['data_type'] == 'Boolean') {
            if (CRM_Utils_String::strtoboolstr($value) === FALSE) {
              self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
            }
          }
          // need not check for label filed import
          $htmlType = array(
            'CheckBox',
            'Multi-Select',
            'AdvMulti-Select',
            'Select',
            'Radio',
            'Multi-Select State/Province',
            'Multi-Select Country',
          );
          if (!in_array($customFields[$customFieldID]['html_type'], $htmlType) || $customFields[$customFieldID]['data_type'] == 'Boolean' || $customFields[$customFieldID]['data_type'] == 'ContactReference') {
            $valid = CRM_Core_BAO_CustomValue::typecheck($customFields[$customFieldID]['data_type'], $value);
            if (!$valid) {
              self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
            }
          }

          // check for values for custom fields for checkboxes and multiselect
          if ($customFields[$customFieldID]['html_type'] == 'CheckBox' || $customFields[$customFieldID]['html_type'] == 'AdvMulti-Select' || $customFields[$customFieldID]['html_type'] == 'Multi-Select') {
            $value        = trim($value);
            $value        = str_replace('|', ',', $value);
            $mulValues    = explode(',', $value);
            $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
            foreach ($mulValues as $v1) {
              if (strlen($v1) == 0) {
                continue;
              }

              $flag = FALSE;
              foreach ($customOption as $v2) {
                if ((strtolower(trim($v2['label'])) == strtolower(trim($v1))) || (strtolower(trim($v2['value'])) == strtolower(trim($v1)))) {
                  $flag = TRUE;
                }
              }

              if (!$flag) {
                self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
              }
            }
          }
          elseif ($customFields[$customFieldID]['html_type'] == 'Select' || ($customFields[$customFieldID]['html_type'] == 'Radio' && $customFields[$customFieldID]['data_type'] != 'Boolean')) {
            $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
            $flag = FALSE;
            foreach ($customOption as $v2) {
              if ((strtolower(trim($v2['label'])) == strtolower(trim($value))) || (strtolower(trim($v2['value'])) == strtolower(trim($value)))) {
                $flag = TRUE;
              }
            }
            if (!$flag) {
              self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
            }
          }
          elseif ($customFields[$customFieldID]['html_type'] == 'Multi-Select State/Province' || $customFields[$customFieldID]['html_type'] == 'Select State/Province') {
            $mulValues = explode(',', $value);
            $stateVal = array();
            foreach ($mulValues as $stateValue) {
              if ($stateValue) {
                if (self::in_value(trim($stateValue), CRM_Core_PseudoConstant::stateProvinceAbbreviation()) || self::in_value(trim($stateValue), CRM_Core_PseudoConstant::stateProvince())) {
                  continue;
                }
                else {
                  self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
                }
              }
            }
          }
          elseif ($customFields[$customFieldID]['html_type'] == 'Multi-Select Country' || $customFields[$customFieldID]['html_type'] == 'Select Country') {
            $countryVal = array();
            $mulValues = explode(',', $value);
            foreach ($mulValues as $countryValue) {
              if ($countryValue) {
                CRM_Core_PseudoConstant::populate($countryNames, 'CRM_Core_DAO_Country', TRUE, 'name', 'is_active');               
                CRM_Core_PseudoConstant::populate($countryIsoCodes, 'CRM_Core_DAO_Country', TRUE, 'iso_code');
                $config = CRM_Core_Config::singleton();
                $limitCodes = $config->countryLimit();

                $error = TRUE;
                foreach (array(
                    $countryNames,
                    $countryIsoCodes,
                    $limitCodes,
                  ) as $values) {
                    $t = self::in_value(trim($countryValue), $values);
                    if (self::in_value($countryValue, $values)) {
                      $error = FALSE;                   
                      break;                    
                  }
                }
                if ($error) {
                  self::addToErrorMsg($customFields[$customFieldID]['label'], $errorMessage);
                }
              }
            }
          }
        }
      }
      elseif (is_array($params[$key]) && isset($params[$key]["contact_type"])) {
        $relation = NULL;
        if ($relationships) {
          if (array_key_exists($key, $relationships)) {
            $relation = $key;
          }
          elseif (CRM_Utils_Array::key($key, $relationships)) {
            $relation = CRM_Utils_Array::key($key, $relationships);
          }
        }
        if (!empty($relation)) {
          list($id, $first, $second) = CRM_Utils_System::explode('_', $relation, 3);
          $direction = "contact_sub_type_$second";
          $relationshipType = new CRM_Contact_BAO_RelationshipType();
          $relationshipType->id = $id;
          if ($relationshipType->find(TRUE)) {
            if (isset($relationshipType->$direction)) {
              $params[$key]['contact_sub_type'] = $relationshipType->$direction;
            }
          }
          $relationshipType->free();
        }

        self::isErrorInCustomData($params[$key], $errorMessage, $csType, $relationships);
      }
    }
  }


  /**
   * function to build error-message containing error-fields
   *
   * @param String   $errorName      A string containing error-field name.
   * @param String   $errorMessage   A string containing all the error-fields, where the new errorName is concatenated.
   *
   * @static
   * @access public
   */
  static function addToErrorMsg($errorName, &$errorMessage) {
    if ($errorMessage) {
      $errorMessage .= "; $errorName";
    }
    else {
      $errorMessage = $errorName;
    }
  }

  /**
   * function to ckeck a value present or not in a array
   *
   * @return ture if value present in array or retun false
   *
   * @access public
   */
  function in_value($value, $valueArray) {
    foreach ($valueArray as $key => $v) {
      if (strtolower(trim($v, ".")) == strtolower(trim($value, "."))) {
        return TRUE;        
      }
    }
    return FALSE;
  }

  /**
   * Export data to a CSV file
   *
   * @param string $filename
   * @param array $header
   * @param data $data
   *
   * @return void
   * @access public
   */
  static function exportCSV($fileName, $header, $data) {
    $output = array();
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

