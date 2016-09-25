<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */
abstract class CRM_Custom_Import_Parser extends CRM_Contact_Import_Parser {

  protected $_fileName;

  /**#@+
   * @var integer
   */

  /**
   * Imported file size
   */
  protected $_fileSize;

  /**
   * Separator being used
   */
  protected $_separator;

  /**
   * Total number of lines in file
   */
  protected $_lineCount;

  /**
   * Whether the file has a column header or not
   *
   * @var boolean
   */
  protected $_haveColumnHeader;

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
        $headers = array_merge(array(
            ts('Line Number'),
            ts('Reason'),
          ),
          $customHeaders
        );
        $this->_errorFileName = self::errorFileName(self::ERROR);
        self::exportCSV($this->_errorFileName, $headers, $this->_errors);
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
            ts('View Activity History URL'),
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

}
