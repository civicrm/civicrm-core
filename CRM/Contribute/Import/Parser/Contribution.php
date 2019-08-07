<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * Class to parse contribution csv files.
 */
class CRM_Contribute_Import_Parser_Contribution extends CRM_Contribute_Import_Parser {

  protected $_mapperKeys;

  private $_contactIdIndex;
  private $_totalAmountIndex;
  private $_contributionTypeIndex;

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
    $this->_totalAmountIndex = -1;
    $this->_contributionTypeIndex = -1;

    $index = 0;
    foreach ($this->_mapperKeys as $key) {
      switch ($key) {
        case 'contribution_contact_id':
          $this->_contactIdIndex = $index;
          break;

        case 'total_amount':
          $this->_totalAmountIndex = $index;
          break;

        case 'financial_type':
          $this->_contributionTypeIndex = $index;
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
    $response = $this->setActiveFieldValues($values, $erroneousField);

    $params = &$this->getActiveFieldParams();
    $errorMessage = NULL;

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
        return CRM_Contribute_Import_Parser::SOFT_CREDIT_ERROR;
      }
      elseif (CRM_Utils_Array::value('error_data', $formatError) == 'pledge_payment') {
        return CRM_Contribute_Import_Parser::PLEDGE_PAYMENT_ERROR;
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
          'id' => CRM_Utils_Array::value('contribution_id', $paramValues),
          'trxn_id' => CRM_Utils_Array::value('trxn_id', $paramValues),
          'invoice_id' => CRM_Utils_Array::value('invoice_id', $paramValues),
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

          $newContribution = CRM_Contribute_BAO_Contribution::create($formatted, $ids);
          $this->_newContributions[] = $newContribution->id;

          //return soft valid since we need to show how soft credits were added
          if (!empty($formatted['soft_credit'])) {
            return CRM_Contribute_Import_Parser::SOFT_CREDIT;
          }

          // process pledge payment assoc w/ the contribution
          return self::processPledgePayments($formatted);

          return CRM_Import_Parser::VALID;
        }
        else {
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
    }

    if ($this->_contactIdIndex < 0) {
      // set the contact type if its not set
      if (!isset($paramValues['contact_type'])) {
        $paramValues['contact_type'] = $this->_contactType;
      }

      $error = $this->checkContactDuplicate($paramValues);

      if (CRM_Core_Error::isAPIError($error, CRM_Core_ERROR::DUPLICATE_CONTACT)) {
        $matchedIDs = explode(',', $error['error_message']['params'][0]);
        if (count($matchedIDs) > 1) {
          array_unshift($values, 'Multiple matching contact records detected for this row. The contribution was not imported');
          return CRM_Import_Parser::ERROR;
        }
        else {
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
            return CRM_Contribute_Import_Parser::SOFT_CREDIT;
          }

          // process pledge payment assoc w/ the contribution
          return self::processPledgePayments($formatted);

          return CRM_Import_Parser::VALID;
        }
      }
      else {
        // Using new Dedupe rule.
        $ruleParams = [
          'contact_type' => $this->_contactType,
          'used' => 'Unsupervised',
        ];
        $fieldsArray = CRM_Dedupe_BAO_Rule::dedupeRuleFields($ruleParams);
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
    }
    else {
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
        return CRM_Contribute_Import_Parser::SOFT_CREDIT;
      }

      // process pledge payment assoc w/ the contribution
      return self::processPledgePayments($formatted);

      return CRM_Import_Parser::VALID;
    }
  }

  /**
   * Process pledge payments.
   *
   * @param array $formatted
   *
   * @return int
   */
  public function processPledgePayments(&$formatted) {
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

      return CRM_Contribute_Import_Parser::PLEDGE_PAYMENT;
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
   *   Associative array of property name/value.
   *                             pairs to insert in new contact.
   * @param array $values
   *   The reformatted properties that we can use internally.
   *                            '
   *
   * @param bool $create
   * @param null $onDuplicate
   *
   * @return array|CRM_Error
   */
  private function deprecatedFormatParams($params, &$values, $create = FALSE, $onDuplicate = NULL) {
    require_once 'CRM/Utils/DeprecatedUtils.php';
    // copy all the contribution fields as is
    require_once 'api/v3/utils.php';
    $fields = CRM_Contribute_DAO_Contribution::fields();

    _civicrm_api3_store_values($fields, $params, $values);

    require_once 'CRM/Core/OptionGroup.php';
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
        if ($type == 'CheckBox' || $type == 'Multi-Select') {
          $mulValues = explode(',', $value);
          $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
          $values[$key] = [];
          foreach ($mulValues as $v1) {
            foreach ($customOption as $customValueID => $customLabel) {
              $customValue = $customLabel['value'];
              if ((strtolower($customLabel['label']) == strtolower(trim($v1))) ||
                (strtolower($customValue) == strtolower(trim($v1)))
              ) {
                if ($type == 'CheckBox') {
                  $values[$key][$customValue] = 1;
                }
                else {
                  $values[$key][] = $customValue;
                }
              }
            }
          }
        }
        elseif ($type == 'Select' || $type == 'Radio' ||
          ($type == 'Autocomplete-Select' &&
            $customFields[$customFieldID]['data_type'] == 'String'
          )
        ) {
          $customOption = CRM_Core_BAO_CustomOption::getCustomOption($customFieldID, TRUE);
          foreach ($customOption as $customFldID => $customValue) {
            $val = CRM_Utils_Array::value('value', $customValue);
            $label = CRM_Utils_Array::value('label', $customValue);
            $label = strtolower($label);
            $value = strtolower(trim($value));
            if (($value == $label) || ($value == strtolower($val))) {
              $values[$key] = $val;
            }
          }
        }
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
          $contactId = CRM_Utils_Array::value('contribution_contact_id', $params);
          $externalId = CRM_Utils_Array::value('external_identifier', $params);
          $email = CRM_Utils_Array::value('email', $params);
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
            $emailParams = ['email' => $email, 'contact_type' => $params['contact_type']];
            $checkDedupe = _civicrm_api3_deprecated_duplicate_formatted_contact($emailParams);
            if (!$checkDedupe['is_error']) {
              return civicrm_api3_create_error("Invalid email address(doesn't exist) $email. Row was skipped");
            }
            else {
              $matchingContactIds = explode(',', $checkDedupe['error_message']['params'][0]);
              if (count($matchingContactIds) > 1) {
                return civicrm_api3_create_error("Invalid email address(duplicate) $email. Row was skipped");
              }
              elseif (count($matchingContactIds) == 1) {
                $params['contribution_contact_id'] = $matchingContactIds[0];
              }
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

        case 'payment_instrument':
          require_once 'CRM/Core/PseudoConstant.php';
          $values['payment_instrument_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', $value);
          if (empty($values['payment_instrument_id'])) {
            return civicrm_api3_create_error("Payment Instrument is not valid: $value");
          }
          break;

        case 'contribution_status_id':
          require_once 'CRM/Core/PseudoConstant.php';
          if (!$values['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $value)) {
            return civicrm_api3_create_error("Contribution Status is not valid: $value");
          }
          break;

        case 'soft_credit':
          // import contribution record according to select contact type
          // validate contact id and external identifier.
          $value[$key] = $mismatchContactType = $softCreditContactIds = '';
          if (isset($params[$key]) && is_array($params[$key])) {
            foreach ($params[$key] as $softKey => $softParam) {
              $contactId = CRM_Utils_Array::value('contact_id', $softParam);
              $externalId = CRM_Utils_Array::value('external_identifier', $softParam);
              $email = CRM_Utils_Array::value('email', $softParam);
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
                $emailParams = ['email' => $email, 'contact_type' => $params['contact_type']];
                $checkDedupe = _civicrm_api3_deprecated_duplicate_formatted_contact($emailParams);
                if (!$checkDedupe['is_error']) {
                  return civicrm_api3_create_error("Invalid email address(doesn't exist) $email for Soft Credit. Row was skipped");
                }
                else {
                  $matchingContactIds = explode(',', $checkDedupe['error_message']['params'][0]);
                  if (count($matchingContactIds) > 1) {
                    return civicrm_api3_create_error("Invalid email address(duplicate) $email for Soft Credit. Row was skipped");
                  }
                  elseif (count($matchingContactIds) == 1) {
                    $contactId = $matchingContactIds[0];
                    unset($softParam['email']);
                    $values[$key][$softKey] = $softParam + ['contact_id' => $contactId];
                  }
                }
              }
            }
          }
          break;

        case 'pledge_payment':
        case 'pledge_id':

          // giving respect to pledge_payment flag.
          if (empty($params['pledge_payment'])) {
            continue;
          }

          // get total amount of from import fields
          $totalAmount = CRM_Utils_Array::value('total_amount', $params);

          $onDuplicate = CRM_Utils_Array::value('onDuplicate', $params);

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
              $error = _civicrm_api3_deprecated_check_contact_dedupe($params);

              if (isset($error['error_message']['params'][0])) {
                $matchedIDs = explode(',', $error['error_message']['params'][0]);

                // check if only one contact is found
                if (count($matchedIDs) > 1) {
                  return civicrm_api3_create_error($error['error_message']['message']);
                }
                else {
                  $contributionContactID = $params['contribution_contact_id'] = $values['contribution_contact_id'] = $matchedIDs[0];
                }
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
            elseif (count($pledgeDetails) > 1) {
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

        default:
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
