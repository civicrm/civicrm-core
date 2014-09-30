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


require_once 'api/api.php';

/**
 * class to parse membership csv files
 */
class CRM_Member_Import_Parser_Membership extends CRM_Member_Import_Parser {

  protected $_mapperKeys;

  private $_contactIdIndex;
  private $_totalAmountIndex;
  private $_membershipTypeIndex;
  private $_membershipStatusIndex;

  /**
   * Array of successfully imported membership id's
   *
   * @array
   */
  protected $_newMemberships;

  /**
   * class constructor
   */
  function __construct(&$mapperKeys, $mapperLocType = NULL, $mapperPhoneType = NULL) {
    parent::__construct();
    $this->_mapperKeys = &$mapperKeys;
  }

  /**
   * the initializer code, called before the processing
   *
   * @return void
   * @access public
   */
  function init() {
    $fields = CRM_Member_BAO_Membership::importableFields($this->_contactType, FALSE);

    foreach ($fields as $name => $field) {
      $field['type'] = CRM_Utils_Array::value('type', $field, CRM_Utils_Type::T_INT);
      $field['dataPattern'] = CRM_Utils_Array::value('dataPattern', $field, '//');
      $field['headerPattern'] = CRM_Utils_Array::value('headerPattern', $field, '//');
      $this->addField($name, $field['title'], $field['type'], $field['headerPattern'], $field['dataPattern']);
    }

    $this->_newMemberships = array();

    $this->setActiveFields($this->_mapperKeys);

    // FIXME: we should do this in one place together with Form/MapField.php
    $this->_contactIdIndex = -1;
    $this->_membershipTypeIndex = -1;
    $this->_membershipStatusIndex = -1;

    $index = 0;
    foreach ($this->_mapperKeys as $key) {
      switch ($key) {
        case 'membership_contact_id':
          $this->_contactIdIndex = $index;
          break;

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
   * handle the values in mapField mode
   *
   * @param array $values the array of values belonging to this line
   *
   * @return boolean
   * @access public
   */
  function mapField(&$values) {
    return CRM_Import_Parser::VALID;
  }

  /**
   * handle the values in preview mode
   *
   * @param array $values the array of values belonging to this line
   *
   * @return boolean      the result of this processing
   * @access public
   */
  function preview(&$values) {
    return $this->summary($values);
  }

  /**
   * handle the values in summary mode
   *
   * @param array $values the array of values belonging to this line
   *
   * @return boolean      the result of this processing
   * @access public
   */
  function summary(&$values) {
    $erroneousField = NULL;
    $response = $this->setActiveFieldValues($values, $erroneousField);

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

    $params = &$this->getActiveFieldParams();
    $errorMessage = NULL;

    //To check whether start date or join date is provided
    if (empty($params['membership_start_date']) && empty($params['join_date'])) {
      $errorMessage = 'Membership Start Date is required to create a memberships.';
      CRM_Contact_Import_Parser_Contact::addToErrorMsg('Start Date', $errorMessage);
    }
    //end

    //for date-Formats
    $session = CRM_Core_Session::singleton();
    $dateType = $session->get('dateTypes');
    foreach ($params as $key => $val) {

      if ($val) {
        switch ($key) {
          case 'join_date':
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

          case 'membership_type_id':
            $membershipTypes = CRM_Member_PseudoConstant::membershipType();
            if (!CRM_Utils_Array::crmInArray($val, $membershipTypes) &&
              !array_key_exists($val, $membershipTypes)
            ) {
              CRM_Contact_Import_Parser_Contact::addToErrorMsg('Membership Type', $errorMessage);
            }
            break;

          case 'status_id':
            if (!CRM_Utils_Array::crmInArray($val, CRM_Member_PseudoConstant::membershipStatus())) {
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
   * handle the values in import mode
   *
   * @param int $onDuplicate the code for what action to take on duplicates
   * @param array $values the array of values belonging to this line
   *
   * @return boolean      the result of this processing
   * @access public
   */
  function import($onDuplicate, &$values) {
    try{
      // first make sure this is a valid line
      $response = $this->summary($values);
      if ($response != CRM_Import_Parser::VALID) {
        return $response;
      }

      $params = &$this->getActiveFieldParams();

      //assign join date equal to start date if join date is not provided
      if (empty($params['join_date']) && !empty($params['membership_start_date'])) {
        $params['join_date'] = $params['membership_start_date'];
      }

      $session      = CRM_Core_Session::singleton();
      $dateType     = $session->get('dateTypes');
      $formatted    = array();
      $customFields = CRM_Core_BAO_CustomField::getFields(CRM_Utils_Array::value('contact_type', $params));

      // don't add to recent items, CRM-4399
      $formatted['skipRecentView'] = TRUE;
      $dateLabels = array(
        'join_date' => ts('Member Since'),
        'membership_start_date' => ts('Start Date'),
        'membership_end_date' => ts('End Date'),
      );
      foreach ($params as $key => $val) {
        if ($val) {
          switch ($key) {
            case 'join_date':
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
              if (!is_numeric($val)) {
                unset($params['status_id']);
                $params['membership_status'] = $val;
              }
              break;

            case 'is_override':
              $params[$key] = CRM_Utils_String::strtobool($val);
              break;
          }
          if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
                      if ( $customFields[$customFieldID]['data_type'] == 'Date' ) {
              CRM_Contact_Import_Parser_Contact::formatCustomDate($params, $formatted, $dateType, $key);
              unset($params[$key]);
                      } else if ( $customFields[$customFieldID]['data_type'] == 'Boolean' ) {
              $params[$key] = CRM_Utils_String::strtoboolstr($val);
            }
          }
        }
      }
      //date-Format part ends

      static $indieFields = NULL;
      if ($indieFields == NULL) {
        $tempIndieFields = CRM_Member_DAO_Membership::import();
        $indieFields = $tempIndieFields;
      }

      $formatValues = array();
      foreach ($params as $key => $field) {
        if ($field == NULL || $field === '') {
          continue;
        }

        $formatValues[$key] = $field;
      }

      //format params to meet api v2 requirements.
      //@todo find a way to test removing this formatting
      $formatError = $this->membership_format_params($formatValues, $formatted, TRUE);

      if ($onDuplicate != CRM_Import_Parser::DUPLICATE_UPDATE) {
        $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($formatted,
          CRM_Core_DAO::$_nullObject,
          NULL,
          'Membership'
        );
      }
      else {
        //fix for CRM-2219 Update Membership
        // onDuplicate == CRM_Import_Parser::DUPLICATE_UPDATE
        if (!empty($formatted['is_override']) && empty($formatted['status_id'])) {
          array_unshift($values, 'Required parameter missing: Status');
          return CRM_Import_Parser::ERROR;
        }

        if (!empty($formatValues['membership_id'])) {
          $dao     = new CRM_Member_BAO_Membership();
          $dao->id = $formatValues['membership_id'];
          $dates   = array('join_date', 'start_date', 'end_date');
          foreach ($dates as $v) {
            if (empty($formatted[$v])) {
              $formatted[$v] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $formatValues['membership_id'], $v);
            }
          }

          $formatted['custom'] = CRM_Core_BAO_CustomField::postProcess($formatted,
            CRM_Core_DAO::$_nullObject,
            $formatValues['membership_id'],
            'Membership'
          );
          if ($dao->find(TRUE)) {
            $ids = array(
              'membership' => $formatValues['membership_id'],
              'userId' => $session->get('userID'),
            );
            
            if (empty($params['line_item']) && !empty($formatted['membership_type_id'])) {
              CRM_Price_BAO_LineItem::getLineItemArray($formatted, NULL, 'membership', $formatted['membership_type_id']);
            }
            
            $newMembership = CRM_Member_BAO_Membership::create($formatted, $ids, TRUE);
            if (civicrm_error($newMembership)) {
              array_unshift($values, $newMembership['is_error'] . ' for Membership ID ' . $formatValues['membership_id'] . '. Row was skipped.');
              return CRM_Import_Parser::ERROR;
            }
            else {
              $this->_newMemberships[] = $newMembership->id;
              return CRM_Import_Parser::VALID;
            }
          }
          else {
            array_unshift($values, 'Matching Membership record not found for Membership ID ' . $formatValues['membership_id'] . '. Row was skipped.');
            return CRM_Import_Parser::ERROR;
          }
        }
      }

      //Format dates
      $startDate = CRM_Utils_Date::customFormat(CRM_Utils_Array::value('start_date', $formatted), '%Y-%m-%d');
      $endDate   = CRM_Utils_Date::customFormat(CRM_Utils_Array::value('end_date', $formatted), '%Y-%m-%d');
      $joinDate  = CRM_Utils_Date::customFormat(CRM_Utils_Array::value('join_date', $formatted), '%Y-%m-%d');

      if ($this->_contactIdIndex < 0) {

        //retrieve contact id using contact dedupe rule
        $formatValues['contact_type'] = $this->_contactType;
        $formatValues['version'] = 3;
        require_once 'CRM/Utils/DeprecatedUtils.php';
        $error = _civicrm_api3_deprecated_check_contact_dedupe($formatValues);

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
            if (empty($formatted['is_override'])) {
              $formatted['exclude_is_admin'] = $excludeIsAdmin = TRUE;
            }
            $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($startDate,
              $endDate,
              $joinDate,
              'today',
              $excludeIsAdmin,
              $formatted['membership_type_id'],
              $formatted
            );

            if (empty($formatted['status_id'])) {
              $formatted['status_id'] = $calcStatus['id'];
            }
            elseif (empty($formatted['is_override'])) {
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
          $ruleParams = array(
            'contact_type' => $this->_contactType,
            'used'         => 'Unsupervised',
          );
          $fieldsArray = CRM_Dedupe_BAO_Rule::dedupeRuleFields($ruleParams);
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
            array_unshift($values, 'Mismatch of External identifier :' . $formatValues['external_identifier'] . ' and Contact Id:' . $formatted['contact_id']);
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
        if (empty($formatted['is_override'])) {
          $formatted['exclude_is_admin'] = $excludeIsAdmin = TRUE;
        }
        $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($startDate,
          $endDate,
          $joinDate,
          'today',
          $excludeIsAdmin,
          $formatted['membership_type_id'],
          $formatted
        );
        if (empty($formatted['status_id'])) {
          $formatted['status_id'] = CRM_Utils_Array::value('id', $calcStatus);
        }
        elseif (empty($formatted['is_override'])) {
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
   * @access public
   */
  function &getImportedMemberships() {
    return $this->_newMemberships;
  }

  /**
   * the initializer code, called before the processing
   *
   * @return void
   * @access public
   */
  function fini() {}

  /**
   *  to calculate join, start and end dates
   *
   * @param Array $calcDates array of dates returned by getDatesForMembershipType()
   *
   * @param $formatted
   *
   * @return Array formatted containing date values
   *
   * @access public
   */
  function formattedDates($calcDates, &$formatted) {
    $dates = array(
      'join_date',
      'start_date',
      'end_date',
    );

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
   * @param array $params Associative array of property name/value
   *                             pairs to insert in new contact.
   * @param array $values The reformatted properties that we can use internally
   *
   * @param array|bool $create Is the formatted Values array going to
   *                             be used for CRM_Member_BAO_Membership:create()
   *
   * @throws Exception
   * @return array|error
   * @access public
   */
  function membership_format_params($params, &$values, $create = FALSE) {
    require_once 'api/v3/utils.php';
    $fields = CRM_Member_DAO_Membership::fields();
    _civicrm_api3_store_values($fields, $params, $values);

    $customFields = CRM_Core_BAO_CustomField::getFields( 'Membership');

    foreach ($params as $key => $value) {
      // ignore empty values or empty arrays etc
      if (CRM_Utils_System::isNull($value)) {
        continue;
      }

      //Handling Custom Data
      if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($key)) {
        $values[$key] = $value;
        $type = $customFields[$customFieldID]['html_type'];
        if( $type == 'CheckBox' || $type == 'Multi-Select' || $type == 'AdvMulti-Select') {
          $mulValues = explode( ',' , $value );
          $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, true);
          $values[$key] = array();
          foreach( $mulValues as $v1 ) {
            foreach($customOption as $customValueID => $customLabel) {
              $customValue = $customLabel['value'];
              if (( strtolower($customLabel['label']) == strtolower(trim($v1)) ) ||
                ( strtolower($customValue) == strtolower(trim($v1)) )) {
                if ( $type == 'CheckBox' ) {
                  $values[$key][$customValue] = 1;
                } else {
                  $values[$key][] = $customValue;
                }
              }
            }
          }
        }
      }

      switch ($key) {
        case 'membership_contact_id':
          if (!CRM_Utils_Rule::integer($value)) {
            throw new Exception("contact_id not valid: $value");
          }
          $dao     = new CRM_Core_DAO();
          $qParams = array();
          $svq     = $dao->singleValueQuery("SELECT id FROM civicrm_contact WHERE id = $value",
            $qParams
          );
          if (!$svq) {
            throw new Exception("Invalid Contact ID: There is no contact record with contact_id = $value.");
          }
          $values['contact_id'] = $values['membership_contact_id'];
          unset($values['membership_contact_id']);
          break;

        case 'membership_type_id':
          if (!CRM_Utils_Array::value($value, CRM_Member_PseudoConstant::membershipType())) {
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

        case 'status_id':
          if (!CRM_Utils_Array::value($value, CRM_Member_PseudoConstant::membershipStatus())) {
            throw new Exception('Invalid Membership Status Id');
          }
          $values[$key] = $value;
          break;

        case 'membership_status':
          $membershipStatusId = CRM_Utils_Array::key(ucfirst($value),
          CRM_Member_PseudoConstant::membershipStatus()
          );
          if ($membershipStatusId) {
            if (!empty($values['status_id']) &&
              $membershipStatusId != $values['status_id']
            ) {
              throw new Exception('Mismatched membership Status and Membership Status Id');
            }
          }
          else {
            throw new Exception('Invalid Membership Status');
          }
          $values['status_id'] = $membershipStatusId;
          break;

        default:
          break;
      }
    }

    _civicrm_api3_custom_format_params($params, $values, 'Membership');


    if ($create) {
      // CRM_Member_BAO_Membership::create() handles membership_start_date,
      // membership_end_date and membership_source. So, if $values contains
      // membership_start_date, membership_end_date  or membership_source,
      // convert it to start_date, end_date or source
      $changes = array(
        'membership_start_date' => 'start_date',
        'membership_end_date' => 'end_date',
        'membership_source' => 'source',
      );

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

