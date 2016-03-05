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
class CRM_Contribute_Import_Field {

  /**#@+
   * @var string
   */

  /**
   * Name of the field
   */
  public $_name;

  /**
   * Title of the field to be used in display
   */
  public $_title;

  /**
   * Type of field
   * @var enum
   */
  public $_type;

  /**
   * Is this field required
   * @var boolean
   */
  public $_required;

  /**
   * Data to be carried for use by a derived class
   * @var object
   */
  public $_payload;

  /**
   * Regexp to match the CSV header of this column/field
   * @var string
   */
  public $_headerPattern;

  /**
   * Regexp to match the pattern of data from various column/fields
   * @var string
   */
  public $_dataPattern;

  /**
   * Value of this field
   * @var object
   */
  public $_value;

  /**
   * This is soft credit field
   * @var string
   */
  public $_softCreditField;

  /**
   * @param string $name
   * @param $title
   * @param int $type
   * @param string $headerPattern
   * @param string $dataPattern
   * @param null $softCreditField
   */
  public function __construct($name, $title, $type = CRM_Utils_Type::T_INT, $headerPattern = '//', $dataPattern = '//', $softCreditField = NULL) {
    $this->_name = $name;
    $this->_title = $title;
    $this->_type = $type;
    $this->_headerPattern = $headerPattern;
    $this->_dataPattern = $dataPattern;
    $this->_softCreditField = $softCreditField;
    $this->_value = NULL;
  }

  public function resetValue() {
    $this->_value = NULL;
  }

  /**
   * Set a value.
   *
   * The value is in string format. Convert the value to the type of this field
   * and set the field value with the appropriate type
   *
   * @param $value
   */
  public function setValue($value) {
    $this->_value = $value;
  }

  /**
   * Validate a field.
   *
   * @return bool
   */
  public function validate() {

    if (CRM_Utils_System::isNull($this->_value)) {
      return TRUE;
    }

    switch ($this->_name) {
      case 'contact_id':
        // note: we validate existence of the contact in API, upon
        // insert (it would be too costly to do a db call here)
        return CRM_Utils_Rule::integer($this->_value);

      case 'receive_date':
      case 'cancel_date':
      case 'receipt_date':
      case 'thankyou_date':
        return CRM_Utils_Rule::date($this->_value);

      case 'non_deductible_amount':
      case 'total_amount':
      case 'fee_amount':
      case 'net_amount':
        return CRM_Utils_Rule::money($this->_value);

      case 'trxn_id':
        static $seenTrxnIds = array();
        if (in_array($this->_value, $seenTrxnIds)) {
          return FALSE;
        }
        elseif ($this->_value) {
          $seenTrxnIds[] = $this->_value;
          return TRUE;
        }
        else {
          $this->_value = NULL;
          return TRUE;
        }
        break;

      case 'currency':
        return CRM_Utils_Rule::currencyCode($this->_value);

      case 'financial_type':
        static $contributionTypes = NULL;
        if (!$contributionTypes) {
          $contributionTypes = CRM_Contribute_PseudoConstant::financialType();
        }
        if (in_array($this->_value, $contributionTypes)) {
          return TRUE;
        }
        else {
          return FALSE;
        }
        break;

      case 'payment_instrument':
        static $paymentInstruments = NULL;
        if (!$paymentInstruments) {
          $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
        }
        if (in_array($this->_value, $paymentInstruments)) {
          return TRUE;
        }
        else {
          return FALSE;
        }
        break;

      default:
        break;
    }

    // check whether that's a valid custom field id
    // and if so, check the contents' validity
    if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($this->_name)) {
      static $customFields = NULL;
      if (!$customFields) {
        $customFields = CRM_Core_BAO_CustomField::getFields('Contribution');
      }
      if (!array_key_exists($customFieldID, $customFields)) {
        return FALSE;
      }
      return CRM_Core_BAO_CustomValue::typecheck($customFields[$customFieldID]['data_type'], $this->_value);
    }

    return TRUE;
  }

}
