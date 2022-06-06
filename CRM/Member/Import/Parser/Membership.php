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
   * Class constructor.
   *
   * @param $mapperKeys
   */
  public function __construct($mapperKeys = []) {
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
    $statusID = NULL
  ) {
    $this->_contactType = $this->getContactType();
    $this->init();

    $this->_lineCount = 0;
    $this->_invalidRowCount = $this->_validCount = 0;
    $this->_totalCount = 0;

    $this->_errors = [];
    $this->_warnings = [];
    if ($statusID) {
      $this->progressImport($statusID);
      $startTimestamp = $currTimestamp = $prevTimestamp = CRM_Utils_Time::time();
    }
    $dataSource = $this->getDataSourceObject();
    $totalRowCount = $dataSource->getRowCount(['new']);
    $dataSource->setStatuses(['new']);
    while ($row = $dataSource->getRow()) {
      $values = array_values($row);
      if ($mode == self::MODE_IMPORT) {
        $this->import($values);
        if ($statusID && (($this->_lineCount % 50) == 0)) {
          $prevTimestamp = $this->progressImport($statusID, FALSE, $startTimestamp, $prevTimestamp, $totalRowCount);
        }
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
  public function getParams() {
    $this->getSubmittedValue('mapper');
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
    $store->set('lineCount', $this->_lineCount);
    $store->set('validRowCount', $this->_validCount);
    $store->set('invalidRowCount', $this->_invalidRowCount);

    if ($this->_invalidRowCount) {
      $store->set('errorsFileName', $this->_errorFileName);
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
    // Force re-load of user job.
    unset($this->userJob);
    $this->setFieldMetadata();

    $this->_newMemberships = [];

    $this->setActiveFields($this->_mapperKeys);
  }

  /**
   * Validate the values.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return bool
   *   the result of this processing
   */
  public function validateValues($values) {
    $params = $this->getMappedRow($values);
    $errors = [];
    foreach ($params as $key => $value) {
      $errors = array_merge($this->getInvalidValues($value, $key), $errors);
    }

    if (empty($params['membership_type_id'])) {
      $errors[] = ts('Missing required fields');
      return NULL;
    }

    //To check whether start date or join date is provided
    if (empty($params['start_date']) && empty($params['join_date'])) {
      $errors[] = 'Membership Start Date is required to create a memberships.';
    }
    if ($errors) {
      throw new CRM_Core_Exception('Invalid value for field(s) : ' . implode(',', $errors));
    }

    return CRM_Import_Parser::VALID;
  }

  /**
   * Handle the values in import mode.
   *
   * @param array $values
   *   The array of values belonging to this line.
   *
   * @return int|void|null
   *   the result of this processing - which is ignored
   */
  public function import($values) {
    $onDuplicate = $this->getSubmittedValue('onDuplicate');
    $rowNumber = (int) ($values[array_key_last($values)]);
    try {
      $params = $this->getMappedRow($values);

      //assign join date equal to start date if join date is not provided
      if (empty($params['join_date']) && !empty($params['start_date'])) {
        $params['join_date'] = $params['start_date'];
      }

      $formatted = $params;
      // don't add to recent items, CRM-4399
      $formatted['skipRecentView'] = TRUE;

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
        if (!empty($formatted['is_override']) && empty($formatted['status_id'])) {
          throw new CRM_Core_Exception('Required parameter missing: Status', CRM_Import_Parser::ERROR);
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
            $this->setImportStatus($rowNumber, 'IMPORTED', 'Required parameter missing: Status');
            return CRM_Import_Parser::VALID;
          }
          throw new CRM_Core_Exception('Matching Membership record not found for Membership ID ' . $formatValues['membership_id'] . '. Row was skipped.', CRM_Import_Parser::ERROR);
        }
      }

      //Format dates
      $startDate = $formatted['start_date'];
      $endDate = $formatted['end_date'] ?? NULL;
      $joinDate = $formatted['join_date'];

      if (empty($formatValues['id']) && empty($formatValues['contact_id'])) {
        $error = $this->checkContactDuplicate($formatValues);

        if (CRM_Core_Error::isAPIError($error, CRM_Core_ERROR::DUPLICATE_CONTACT)) {
          $matchedIDs = explode(',', $error['error_message']['params'][0]);
          if (count($matchedIDs) > 1) {
            throw new CRM_Core_Exception('Multiple matching contact records detected for this row. The membership was not imported', CRM_Import_Parser::ERROR);
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
            if (empty($formatted['is_override'])) {
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
            elseif (empty($formatted['is_override'])) {
              if (empty($calcStatus)) {
                throw new CRM_Core_Exception('Status in import row (' . $formatValues['status_id'] . ') does not match calculated status based on your configured Membership Status Rules. Record was not imported.', CRM_Import_Parser::ERROR);
              }
              if ($formatted['status_id'] != $calcStatus['id']) {
                //Status Hold" is either NOT mapped or is FALSE
                throw new CRM_Core_Exception('Status in import row (' . $formatValues['status_id'] . ') does not match calculated status based on your configured Membership Status Rules (' . $calcStatus['name'] . '). Record was not imported.', CRM_Import_Parser::ERROR);
              }
            }

            $newMembership = civicrm_api3('membership', 'create', $formatted);

            $this->_newMemberships[] = $newMembership['id'];
            $this->setImportStatus($rowNumber, 'IMPORTED', '');
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
          throw new CRM_Core_Exception('No matching Contact found for (' . $disp . ')', CRM_Import_Parser::ERROR);
        }
      }
      else {
        if (!empty($formatValues['external_identifier'])) {
          $checkCid = new CRM_Contact_DAO_Contact();
          $checkCid->external_identifier = $formatValues['external_identifier'];
          $checkCid->find(TRUE);
          if ($checkCid->id != $formatted['contact_id']) {
            throw new CRM_Core_Exception('Mismatch of External ID:' . $formatValues['external_identifier'] . ' and Contact Id:' . $formatted['contact_id'], CRM_Import_Parser::ERROR);
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
        if (empty($formatted['is_override'])) {
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
        elseif (empty($formatted['is_override'])) {
          if (empty($calcStatus)) {
            throw new CRM_Core_Exception('Status in import row (' . CRM_Utils_Array::value('status_id', $formatValues) . ') does not match calculated status based on your configured Membership Status Rules. Record was not imported.', CRM_Import_Parser::ERROR);
          }
          if ($formatted['status_id'] != $calcStatus['id']) {
            //Status Hold" is either NOT mapped or is FALSE
            throw new CRM_Core_Exception($rowNumber, 'ERROR', 'Status in import row (' . CRM_Utils_Array::value('status_id', $formatValues) . ') does not match calculated status based on your configured Membership Status Rules (' . $calcStatus['name'] . '). Record was not imported.', CRM_Import_Parser::ERROR);
          }
        }

        $newMembership = civicrm_api3('membership', 'create', $formatted);

        $this->_newMemberships[] = $newMembership['id'];
        $this->setImportStatus($rowNumber, 'IMPORTED', '');
        return CRM_Import_Parser::VALID;
      }
    }
    catch (CRM_Core_Exception $e) {
      $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage());
      return CRM_Import_Parser::ERROR;
    }
    catch (CiviCRM_API3_Exception $e) {
      $this->setImportStatus($rowNumber, 'ERROR', $e->getMessage());
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

      switch ($key) {
        case 'contact_id':
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
          $values[$key] = $value;
          break;

        default:
          break;
      }
    }

    return NULL;
  }

  /**
   * Set field metadata.
   */
  protected function setFieldMetadata(): void {
    if (empty($this->importableFieldsMetadata)) {
      $metadata = CRM_Member_BAO_Membership::importableFields($this->getContactType(), FALSE);

      foreach ($metadata as $name => $field) {
        // @todo - we don't really need to do all this.... fieldMetadata is just fine to use as is.
        $field['type'] = CRM_Utils_Array::value('type', $field, CRM_Utils_Type::T_INT);
        $field['dataPattern'] = CRM_Utils_Array::value('dataPattern', $field, '//');
        $field['headerPattern'] = CRM_Utils_Array::value('headerPattern', $field, '//');
        $this->addField($name, $field['title'], $field['type'], $field['headerPattern'], $field['dataPattern']);
      }
      // We are consolidating on `importableFieldsMetadata` - but both still used.
      $this->importableFieldsMetadata = $this->fieldMetadata = $metadata;
    }
  }

  /**
   * Get the metadata field for which importable fields does not key the actual field name.
   *
   * @return string[]
   */
  protected function getOddlyMappedMetadataFields(): array {
    $uniqueNames = ['membership_id', 'membership_contact_id'];
    $fields = [];
    foreach ($uniqueNames as $name) {
      $fields[$this->importableFieldsMetadata[$name]['name']] = $name;
    }
    // Include the parent fields as they could be present if required for matching ...in theory.
    return array_merge($fields, parent::getOddlyMappedMetadataFields());
  }

}
