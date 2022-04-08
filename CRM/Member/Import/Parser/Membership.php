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
 * class to parse membership csv files
 */
class CRM_Member_Import_Parser_Membership extends CRM_Import_Parser {

  protected $_mapperKeys;

  private $_membershipTypeIndex;
  private $_membershipStatusIndex;

  /**
   * Array of metadata for all available fields.
   *
   * @var array
   */
  protected $fieldMetadata = [];

  /**
   * Array of successfully imported membership id's
   *
   * @var array
   */
  protected $_newMemberships;


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
   * Whether the file has a column header or not
   *
   * @var bool
   */
  protected $_haveColumnHeader;

  /**
   * Class constructor.
   *
   * @param $mapperKeys
   */
  public function __construct($mapperKeys) {
    parent::__construct();
    $this->_mapperKeys = $mapperKeys;
  }

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

    $this->_lineCount = $this->_warningCount = 0;
    $this->_invalidRowCount = $this->_validCount = 0;
    $this->_totalCount = $this->_conflictCount = 0;

    $this->_errors = [];
    $this->_warnings = [];
    $this->_conflicts = [];

    $this->_fileSize = number_format(filesize($fileName) / 1024.0, 2);

    if ($mode == self::MODE_MAPFIELD) {
      $this->_rows = [];
    }
    else {
      $this->_activeFieldCount = count($this->_activeFields);
    }
    if ($statusID) {
      $this->progressImport($statusID);
      $startTimestamp = $currTimestamp = $prevTimestamp = CRM_Utils_Time::time();
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
        $recordNumber = $this->_lineCount;
        array_unshift($values, $recordNumber);
        $this->_errors[] = $values;
      }

      if ($returnCode & self::CONFLICT) {
        $this->_conflictCount++;
        $recordNumber = $this->_lineCount;
        array_unshift($values, $recordNumber);
        $this->_conflicts[] = $values;
      }

      if ($returnCode & self::DUPLICATE) {
        $this->_duplicateCount++;
        $recordNumber = $this->_lineCount;
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

      $customfields = CRM_Core_BAO_CustomField::getFields('Membership');
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
          ts('View Membership URL'),
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
   *
   * @return void
   */
  public function setActiveFields($fieldKeys) {
    $this->_activeFieldCount = count($fieldKeys);
    foreach ($fieldKeys as $key) {
      if (empty($this->_fields[$key])) {
        $this->_activeFields[] = new CRM_Member_Import_Field('', ts('- do not import -'));
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
   * @param string $name
   * @param $title
   * @param int $type
   * @param string $headerPattern
   * @param string $dataPattern
   */
  public function addField($name, $title, $type = CRM_Utils_Type::T_INT, $headerPattern = '//', $dataPattern = '//') {
    if (empty($name)) {
      $this->_fields['doNotImport'] = new CRM_Member_Import_Field($name, $title, $type, $headerPattern, $dataPattern);
    }
    else {

      //$tempField = CRM_Contact_BAO_Contact::importableFields('Individual', null );
      $tempField = CRM_Contact_BAO_Contact::importableFields('All', NULL);
      if (!array_key_exists($name, $tempField)) {
        $this->_fields[$name] = new CRM_Member_Import_Field($name, $title, $type, $headerPattern, $dataPattern);
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
    $store->set('fieldTypes', $this->getSelectTypes());

    $store->set('headerPatterns', $this->getHeaderPatterns());
    $store->set('dataPatterns', $this->getDataPatterns());
    $store->set('columnCount', $this->_activeFieldCount);

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
   * The initializer code, called before the processing
   *
   * @return void
   */
  public function init() {
    $this->fieldMetadata = CRM_Member_BAO_Membership::importableFields($this->_contactType, FALSE);

    foreach ($this->fieldMetadata as $name => $field) {
      // @todo - we don't really need to do all this.... fieldMetadata is just fine to use as is.
      $field['type'] = CRM_Utils_Array::value('type', $field, CRM_Utils_Type::T_INT);
      $field['dataPattern'] = CRM_Utils_Array::value('dataPattern', $field, '//');
      $field['headerPattern'] = CRM_Utils_Array::value('headerPattern', $field, '//');
      $this->addField($name, $field['title'], $field['type'], $field['headerPattern'], $field['dataPattern']);
    }

    $this->_newMemberships = [];

    $this->setActiveFields($this->_mapperKeys);

    // FIXME: we should do this in one place together with Form/MapField.php
    $this->_membershipTypeIndex = -1;
    $this->_membershipStatusIndex = -1;

    $index = 0;
    foreach ($this->_mapperKeys as $key) {
      switch ($key) {

        case 'membership_type_id':
          $this->_membershipTypeIndex = $index;
          break;

        case 'status_id':
          $this->_membershipStatusIndex = $index;
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
    $erroneousField = NULL;
    $this->setActiveFieldValues($values, $erroneousField);

    $errorRequired = FALSE;

    if ($this->_membershipTypeIndex < 0) {
      $errorRequired = TRUE;
    }
    else {
      $errorRequired = !CRM_Utils_Array::value($this->_membershipTypeIndex, $values);
    }

    if ($errorRequired) {
      array_unshift($values, ts('Missing required fields'));
      return CRM_Import_Parser::ERROR;
    }

    $params = $this->getActiveFieldParams();
    $errorMessage = NULL;

    //To check whether start date or join date is provided
    if (empty($params['membership_start_date']) && empty($params['membership_join_date'])) {
      $errorMessage = 'Membership Start Date is required to create a memberships.';
      CRM_Contact_Import_Parser_Contact::addToErrorMsg('Start Date', $errorMessage);
    }

    //for date-Formats
    $session = CRM_Core_Session::singleton();
    $dateType = $session->get('dateTypes');
    foreach ($params as $key => $val) {

      if ($val) {
        switch ($key) {
          case 'membership_join_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::date($params[$key])) {
                CRM_Contact_Import_Parser_Contact::addToErrorMsg('Member Since', $errorMessage);
              }
            }
            else {
              CRM_Contact_Import_Parser_Contact::addToErrorMsg('Member Since', $errorMessage);
            }
            break;

          case 'membership_start_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::date($params[$key])) {
                CRM_Contact_Import_Parser_Contact::addToErrorMsg('Start Date', $errorMessage);
              }
            }
            else {
              CRM_Contact_Import_Parser_Contact::addToErrorMsg('Start Date', $errorMessage);
            }
            break;

          case 'membership_end_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::date($params[$key])) {
                CRM_Contact_Import_Parser_Contact::addToErrorMsg('End date', $errorMessage);
              }
            }
            else {
              CRM_Contact_Import_Parser_Contact::addToErrorMsg('End date', $errorMessage);
            }
            break;

          case 'status_override_end_date':
            if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
              if (!CRM_Utils_Rule::date($params[$key])) {
                CRM_Contact_Import_Parser_Contact::addToErrorMsg('Status Override End Date', $errorMessage);
              }
            }
            else {
              CRM_Contact_Import_Parser_Contact::addToErrorMsg('Status Override End Date', $errorMessage);
            }
            break;

          case 'membership_type_id':
            // @todo - squish into membership status - can use same lines here too.
            $membershipTypes = CRM_Member_PseudoConstant::membershipType();
            if (!CRM_Utils_Array::crmInArray($val, $membershipTypes) &&
              !array_key_exists($val, $membershipTypes)
            ) {
              CRM_Contact_Import_Parser_Contact::addToErrorMsg('Membership Type', $errorMessage);
            }
            break;

          case 'status_id':
            if (!empty($val) && !$this->parsePseudoConstantField($val, $this->fieldMetadata[$key])) {
              CRM_Contact_Import_Parser_Contact::addToErrorMsg('Membership Status', $errorMessage);
            }
            break;

          case 'email':
            if (!CRM_Utils_Rule::email($val)) {
              CRM_Contact_Import_Parser_Contact::addToErrorMsg('Email Address', $errorMessage);
            }
        }
      }
    }
    //date-Format part ends

    $params['contact_type'] = 'Membership';

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
    try {
      // first make sure this is a valid line
      $response = $this->summary($values);
      if ($response != CRM_Import_Parser::VALID) {
        return $response;
      }

      $params = $this->getActiveFieldParams();

      //assign join date equal to start date if join date is not provided
      if (empty($params['membership_join_date']) && !empty($params['membership_start_date'])) {
        $params['membership_join_date'] = $params['membership_start_date'];
      }

      $session = CRM_Core_Session::singleton();
      $dateType = CRM_Core_Session::singleton()->get('dateTypes');
      $formatted = [];
      $customDataType = !empty($params['contact_type']) ? $params['contact_type'] : 'Membership';
      $customFields = CRM_Core_BAO_CustomField::getFields($customDataType);

      // don't add to recent items, CRM-4399
      $formatted['skipRecentView'] = TRUE;
      $dateLabels = [
        'membership_join_date' => ts('Member Since'),
        'membership_start_date' => ts('Start Date'),
        'membership_end_date' => ts('End Date'),
      ];
      foreach ($params as $key => $val) {
        if ($val) {
          switch ($key) {
            case 'membership_join_date':
            case 'membership_start_date':
            case 'membership_end_date':
              if (CRM_Utils_Date::convertToDefaultDate($params, $dateType, $key)) {
                if (!CRM_Utils_Rule::date($params[$key])) {
                  CRM_Contact_Import_Parser_Contact::addToErrorMsg($dateLabels[$key], $errorMessage);
                }
              }
              else {
                CRM_Contact_Import_Parser_Contact::addToErrorMsg($dateLabels[$key], $errorMessage);
              }
              break;

            case 'membership_type_id':
              if (!is_numeric($val)) {
                unset($params['membership_type_id']);
                $params['membership_type'] = $val;
              }
              break;

            case 'status_id':
              // @todo - we can do this based on the presence of 'pseudoconstant' in the metadata rather than field specific.
              $params[$key] = $this->parsePseudoConstantField($val, $this->fieldMetadata[$key]);
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
      //date-Format part ends

      $formatValues = [];
      foreach ($params as $key => $field) {
        // ignore empty values or empty arrays etc
        if (CRM_Utils_System::isNull($field)) {
          continue;
        }

        $formatValues[$key] = $field;
      }

      //format params to meet api v2 requirements.
      //@todo find a way to test removing this formatting
      $formatError = $this->membership_format_params($formatValues, $formatted, TRUE);

      if ($onDuplicate != CRM_Import_Parser::DUPLICATE_UPDATE) {
        $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($formatted,
          NULL,
          'Membership'
        );
      }
      else {
        //fix for CRM-2219 Update Membership
        // onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE
        if (!empty($formatted['member_is_override']) && empty($formatted['status_id'])) {
          array_unshift($values, 'Required parameter missing: Status');
          return CRM_Import_Parser::ERROR;
        }

        if (!empty($formatValues['membership_id'])) {
          $dao = new CRM_Member_BAO_Membership();
          $dao->id = $formatValues['membership_id'];
          $dates = ['join_date', 'start_date', 'end_date'];
          foreach ($dates as $v) {
            if (empty($formatted[$v])) {
              $formatted[$v] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $formatValues['membership_id'], $v);
            }
          }

          $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($formatted,
            $formatValues['membership_id'],
            'Membership'
          );
          if ($dao->find(TRUE)) {
            if (empty($params['line_item']) && !empty($formatted['membership_type_id'])) {
              CRM_Price_BAO_LineItem::getLineItemArray($formatted, NULL, 'membership', $formatted['membership_type_id']);
            }

            $newMembership = civicrm_api3('Membership', 'create', $formatted);
            $this->_newMemberships[] = $newMembership['id'];
            return CRM_Import_Parser::VALID;
          }
          else {
            array_unshift($values, 'Matching Membership record not found for Membership ID ' . $formatValues['membership_id'] . '. Row was skipped.');
            return CRM_Import_Parser::ERROR;
          }
        }
      }

      //Format dates
      $startDate = CRM_Utils_Date::customFormat(CRM_Utils_Array::value('start_date', $formatted), '%Y-%m-%d');
      $endDate = CRM_Utils_Date::customFormat(CRM_Utils_Array::value('end_date', $formatted), '%Y-%m-%d');
      $joinDate = CRM_Utils_Date::customFormat(CRM_Utils_Array::value('join_date', $formatted), '%Y-%m-%d');

      if (!$this->isContactIDColumnPresent()) {
        $error = $this->checkContactDuplicate($formatValues);

        if (CRM_Core_Error::isAPIError($error, CRM_Core_ERROR::DUPLICATE_CONTACT)) {
          $matchedIDs = explode(',', $error['error_message']['params'][0]);
          if (count($matchedIDs) > 1) {
            array_unshift($values, 'Multiple matching contact records detected for this row. The membership was not imported');
            return CRM_Import_Parser::ERROR;
          }
          else {
            $cid = $matchedIDs[0];
            $formatted['contact_id'] = $cid;

            //fix for CRM-1924
            $calcDates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($formatted['membership_type_id'],
              $joinDate,
              $startDate,
              $endDate
            );
            self::formattedDates($calcDates, $formatted);

            //fix for CRM-3570, exclude the statuses those having is_admin = 1
            //now user can import is_admin if is override is true.
            $excludeIsAdmin = FALSE;
            if (empty($formatted['member_is_override'])) {
              $formatted['exclude_is_admin'] = $excludeIsAdmin = TRUE;
            }
            $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($startDate,
              $endDate,
              $joinDate,
              'now',
              $excludeIsAdmin,
              $formatted['membership_type_id'],
              $formatted
            );

            if (empty($formatted['status_id'])) {
              $formatted['status_id'] = $calcStatus['id'];
            }
            elseif (empty($formatted['member_is_override'])) {
              if (empty($calcStatus)) {
                array_unshift($values, 'Status in import row (' . $formatValues['status_id'] . ') does not match calculated status based on your configured Membership Status Rules. Record was not imported.');
                return CRM_Import_Parser::ERROR;
              }
              elseif ($formatted['status_id'] != $calcStatus['id']) {
                //Status Hold" is either NOT mapped or is FALSE
                array_unshift($values, 'Status in import row (' . $formatValues['status_id'] . ') does not match calculated status based on your configured Membership Status Rules (' . $calcStatus['name'] . '). Record was not imported.');
                return CRM_Import_Parser::ERROR;
              }
            }

            $newMembership = civicrm_api3('membership', 'create', $formatted);

            $this->_newMemberships[] = $newMembership['id'];
            return CRM_Import_Parser::VALID;
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

          array_unshift($values, 'No matching Contact found for (' . $disp . ')');
          return CRM_Import_Parser::ERROR;
        }
      }
      else {
        if (!empty($formatValues['external_identifier'])) {
          $checkCid = new CRM_Contact_DAO_Contact();
          $checkCid->external_identifier = $formatValues['external_identifier'];
          $checkCid->find(TRUE);
          if ($checkCid->id != $formatted['contact_id']) {
            array_unshift($values, 'Mismatch of External ID:' . $formatValues['external_identifier'] . ' and Contact Id:' . $formatted['contact_id']);
            return CRM_Import_Parser::ERROR;
          }
        }

        //to calculate dates
        $calcDates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($formatted['membership_type_id'],
          $joinDate,
          $startDate,
          $endDate
        );
        self::formattedDates($calcDates, $formatted);
        //end of date calculation part

        //fix for CRM-3570, exclude the statuses those having is_admin = 1
        //now user can import is_admin if is override is true.
        $excludeIsAdmin = FALSE;
        if (empty($formatted['member_is_override'])) {
          $formatted['exclude_is_admin'] = $excludeIsAdmin = TRUE;
        }
        $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($startDate,
          $endDate,
          $joinDate,
          'now',
          $excludeIsAdmin,
          $formatted['membership_type_id'],
          $formatted
        );
        if (empty($formatted['status_id'])) {
          $formatted['status_id'] = $calcStatus['id'] ?? NULL;
        }
        elseif (empty($formatted['member_is_override'])) {
          if (empty($calcStatus)) {
            array_unshift($values, 'Status in import row (' . CRM_Utils_Array::value('status_id', $formatValues) . ') does not match calculated status based on your configured Membership Status Rules. Record was not imported.');
            return CRM_Import_Parser::ERROR;
          }
          elseif ($formatted['status_id'] != $calcStatus['id']) {
            //Status Hold" is either NOT mapped or is FALSE
            array_unshift($values, 'Status in import row (' . CRM_Utils_Array::value('status_id', $formatValues) . ') does not match calculated status based on your configured Membership Status Rules (' . $calcStatus['name'] . '). Record was not imported.');
            return CRM_Import_Parser::ERROR;
          }
        }

        $newMembership = civicrm_api3('membership', 'create', $formatted);

        $this->_newMemberships[] = $newMembership['id'];
        return CRM_Import_Parser::VALID;
      }
    }
    catch (Exception $e) {
      array_unshift($values, $e->getMessage());
      return CRM_Import_Parser::ERROR;
    }
  }

  /**
   * Get the array of successfully imported membership id's
   *
   * @return array
   */
  public function &getImportedMemberships() {
    return $this->_newMemberships;
  }

  /**
   * The initializer code, called before the processing
   *
   * @return void
   */
  public function fini() {
  }

  /**
   *  to calculate join, start and end dates
   *
   * @param array $calcDates
   *   Array of dates returned by getDatesForMembershipType().
   *
   * @param $formatted
   *
   */
  public function formattedDates($calcDates, &$formatted) {
    $dates = [
      'join_date',
      'start_date',
      'end_date',
    ];

    foreach ($dates as $d) {
      if (isset($formatted[$d]) &&
        !CRM_Utils_System::isNull($formatted[$d])
      ) {
        $formatted[$d] = CRM_Utils_Date::isoToMysql($formatted[$d]);
      }
      elseif (isset($calcDates[$d])) {
        $formatted[$d] = CRM_Utils_Date::isoToMysql($calcDates[$d]);
      }
    }
  }

  /**
   * @deprecated - this function formats params according to v2 standards but
   * need to be sure about the impact of not calling it so retaining on the import class
   * take the input parameter list as specified in the data model and
   * convert it into the same format that we use in QF and BAO object
   *
   * @param array $params
   *   Associative array of property name/value.
   *                             pairs to insert in new contact.
   * @param array $values
   *   The reformatted properties that we can use internally.
   *
   * @param array|bool $create Is the formatted Values array going to
   *                             be used for CRM_Member_BAO_Membership:create()
   *
   * @throws Exception
   * @return array|error
   */
  public function membership_format_params($params, &$values, $create = FALSE) {
    require_once 'api/v3/utils.php';
    $fields = CRM_Member_DAO_Membership::fields();
    _civicrm_api3_store_values($fields, $params, $values);

    $customFields = CRM_Core_BAO_CustomField::getFields('Membership');

    foreach ($params as $key => $value) {

      //Handling Custom Data
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
        $values[$key] = $value;
        $type = $customFields[$customFieldID]['html_type'];
        if (CRM_Core_BAO_CustomField::isSerialized($customFields[$customFieldID])) {
          $values[$key] = self::unserializeCustomValue($customFieldID, $value, $type);
        }
      }

      switch ($key) {
        case 'membership_contact_id':
          if (!CRM_Utils_Rule::integer($value)) {
            throw new Exception("contact_id not valid: $value");
          }
          $dao = new CRM_Core_DAO();
          $qParams = [];
          $svq = $dao->singleValueQuery("SELECT id FROM civicrm_contact WHERE id = $value",
            $qParams
          );
          if (!$svq) {
            throw new Exception("Invalid Contact ID: There is no contact record with contact_id = $value.");
          }
          $values['contact_id'] = $values['membership_contact_id'];
          unset($values['membership_contact_id']);
          break;

        case 'membership_type_id':
          if (!array_key_exists($value, CRM_Member_PseudoConstant::membershipType())) {
            throw new Exception('Invalid Membership Type Id');
          }
          $values[$key] = $value;
          break;

        case 'membership_type':
          $membershipTypeId = CRM_Utils_Array::key(ucfirst($value),
            CRM_Member_PseudoConstant::membershipType()
          );
          if ($membershipTypeId) {
            if (!empty($values['membership_type_id']) &&
              $membershipTypeId != $values['membership_type_id']
            ) {
              throw new Exception('Mismatched membership Type and Membership Type Id');
            }
          }
          else {
            throw new Exception('Invalid Membership Type');
          }
          $values['membership_type_id'] = $membershipTypeId;
          break;

        default:
          break;
      }
    }

    if ($create) {
      // CRM_Member_BAO_Membership::create() handles membership_start_date, membership_join_date,
      // membership_end_date and membership_source. So, if $values contains
      // membership_start_date, membership_end_date, membership_join_date or membership_source,
      // convert it to start_date, end_date, join_date or source
      $changes = [
        'membership_join_date' => 'join_date',
        'membership_start_date' => 'start_date',
        'membership_end_date' => 'end_date',
        'membership_source' => 'source',
      ];

      foreach ($changes as $orgVal => $changeVal) {
        if (isset($values[$orgVal])) {
          $values[$changeVal] = $values[$orgVal];
          unset($values[$orgVal]);
        }
      }
    }

    return NULL;
  }

  /**
   * Is the contact ID mapped.
   *
   * @return bool
   */
  protected function isContactIDColumnPresent(): bool {
    return in_array('membership_contact_id', $this->_mapperKeys, TRUE);
  }

}
