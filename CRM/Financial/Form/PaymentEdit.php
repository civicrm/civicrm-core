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
class CRM_Financial_Form_PaymentEdit extends CRM_Core_Form {

  /**
   * The id of the financial trxn.
   *
   * @var int
   */
  protected $_id;

  /**
   * The id of the related contribution ID
   *
   * @var int
   */
  protected $_contributionID;

  /**
   * The variable which holds the information of a financial transaction
   *
   * @var array
   */
  protected $_values;

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }
  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $this->_action = CRM_Core_Action::UPDATE;
    parent::preProcess();
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->assign('id', $this->_id);
    $this->_contributionID = CRM_Utils_Request::retrieve('contribution_id', 'Positive', $this);

    $this->_values = civicrm_api3('FinancialTrxn', 'getsingle', ['id' => $this->_id]);
    if (!empty($this->_values['payment_processor_id'])) {
      CRM_Core_Error::statusBounce(ts('You cannot update this payment as it is tied to a payment processor'));
    }
  }

  /**
   * Set default values.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = $this->_values;
    // Format money fields - localize for display
    $moneyFields = ['total_amount', 'fee_amount', 'net_amount'];
    foreach ($moneyFields as $field) {
      $defaults[$field] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($this->_values[$field]);
    }
    return $defaults;
  }

  /**
   * Build quickForm.
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Update Payment details'));

    $paymentFields = $this->getPaymentFields();
    $this->assign('paymentFields', $paymentFields);
    foreach ($paymentFields as $name => $paymentField) {
      if (!empty($paymentField['add_field'])) {
        $attributes = [
          'entity' => 'FinancialTrxn',
          'name' => $name,
        ];
        $this->addField($name, $attributes, $paymentField['is_required']);
      }
      else {
        $this->add($paymentField['htmlType'],
          $name,
          $paymentField['title'],
          $paymentField['attributes'],
          $paymentField['is_required']
        );
      }
    }

    $this->assign('currency', CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_Currency', $this->_values['currency'], 'symbol', 'name'));
    $this->addFormRule([__CLASS__, 'formRule'], $this);

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Update'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param $self
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];

    // if Credit Card is chosen and pan_truncation is not NULL ensure that it's value is numeric else throw validation error
    if (CRM_Core_PseudoConstant::getName('CRM_Financial_DAO_FinancialTrxn', 'payment_instrument_id', $fields['payment_instrument_id']) == 'Credit Card' &&
      !empty($fields['pan_truncation']) &&
      !CRM_Utils_Rule::numeric($fields['pan_truncation'])
    ) {
      $errors['pan_truncation'] = ts('Please enter a valid Card Number');
    }

    return $errors;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $params = [
      'id' => $this->_id,
      'payment_instrument_id' => $this->_submitValues['payment_instrument_id'],
      'trxn_id' => CRM_Utils_Array::value('trxn_id', $this->_submitValues),
      'trxn_date' => CRM_Utils_Array::value('trxn_date', $this->_submitValues, date('YmdHis')),
    ];

    $paymentInstrumentName = CRM_Core_PseudoConstant::getName('CRM_Financial_DAO_FinancialTrxn', 'payment_instrument_id', $params['payment_instrument_id']);
    if ($paymentInstrumentName == 'Credit Card') {
      $params['card_type_id'] = CRM_Utils_Array::value('card_type_id', $this->_submitValues);
      $params['pan_truncation'] = CRM_Utils_Array::value('pan_truncation', $this->_submitValues);
    }
    elseif ($paymentInstrumentName == 'Check') {
      $params['check_number'] = CRM_Utils_Array::value('check_number', $this->_submitValues);
    }

    $this->submit($params);

    CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath()));
  }

  /**
   * Wrapper function to process form submission
   *
   * @param array $submittedValues
   *
   */
  protected function submit($submittedValues) {
    // if payment instrument is changed then
    //  1. Record a new reverse financial transaction with old payment instrument
    //  2. Record a new financial transaction with new payment instrument
    //  3. Add EntityFinancialTrxn records to relate with corresponding financial item and contribution
    if ($submittedValues['payment_instrument_id'] != $this->_values['payment_instrument_id']) {
      $previousFinanciaTrxn = $this->_values;
      $newFinancialTrxn = $submittedValues;
      unset($previousFinanciaTrxn['id'], $newFinancialTrxn['id']);
      $previousFinanciaTrxn['trxn_date'] = CRM_Utils_Array::value('trxn_date', $submittedValues, date('YmdHis'));
      $previousFinanciaTrxn['total_amount'] = -$previousFinanciaTrxn['total_amount'];
      $previousFinanciaTrxn['net_amount'] = -$previousFinanciaTrxn['net_amount'];
      $previousFinanciaTrxn['fee_amount'] = -$previousFinanciaTrxn['fee_amount'];
      $previousFinanciaTrxn['contribution_id'] = $newFinancialTrxn['contribution_id'] = $this->_contributionID;

      $newFinancialTrxn['to_financial_account_id'] = CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($submittedValues['payment_instrument_id']);
      foreach (['total_amount', 'fee_amount', 'net_amount', 'currency', 'is_payment', 'status_id'] as $fieldName) {
        $newFinancialTrxn[$fieldName] = $this->_values[$fieldName];
      }

      foreach ([$previousFinanciaTrxn, $newFinancialTrxn] as $financialTrxnParams) {
        $financialTrxn = civicrm_api3('FinancialTrxn', 'create', $financialTrxnParams);
        $trxnParams = [
          'total_amount' => $financialTrxnParams['total_amount'],
          'contribution_id' => $this->_contributionID,
        ];
        $contributionTotalAmount = CRM_Core_DAO::getFieldValue('CRM_Contribute_BAO_Contribution', $this->_contributionID, 'total_amount');
        CRM_Contribute_BAO_Contribution::assignProportionalLineItems($trxnParams, $financialTrxn['id'], $contributionTotalAmount);
      }
    }
    else {
      // simply update the financial trxn
      civicrm_api3('FinancialTrxn', 'create', $submittedValues);
    }

    self::updateRelatedContribution($submittedValues, $this->_contributionID);
  }

  /**
   * Wrapper for unit testing the post process submit function.
   *
   * @param array $params
   */
  public function testSubmit($params) {
    $this->_id = $params['id'];
    $this->_contributionID = $params['contribution_id'];
    $this->_values = civicrm_api3('FinancialTrxn', 'getsingle', ['id' => $params['id']]);

    $this->submit($params);
  }

  /**
   * Function to update contribution's check_number and trxn_id by
   *  concatenating values from financial trxn's check_number and trxn_id respectively
   *
   * @param array $params
   * @param int $contributionID
   */
  public static function updateRelatedContribution($params, $contributionID) {
    $contributionDAO = new CRM_Contribute_DAO_Contribution();
    $contributionDAO->id = $contributionID;
    $contributionDAO->find(TRUE);

    foreach (['trxn_id', 'check_number'] as $fieldName) {
      if (!empty($params[$fieldName])) {
        if (!empty($contributionDAO->$fieldName)) {
          $values = explode(',', $contributionDAO->$fieldName);
          // if submitted check_number or trxn_id value is
          //   already present then ignore else add to $values array
          if (!in_array($params[$fieldName], $values)) {
            $values[] = $params[$fieldName];
          }
          $contributionDAO->$fieldName = implode(',', $values);
        }
      }
    }

    $contributionDAO->save();
  }

  /**
   * Get payment fields
   */
  public function getPaymentFields() {
    $paymentFields = [
      'payment_instrument_id' => [
        'is_required' => TRUE,
        'add_field' => TRUE,
      ],
      'check_number' => [
        'is_required' => FALSE,
        'add_field' => TRUE,
      ],
      // @TODO we need to show card type icon in place of select field
      'card_type_id' => [
        'is_required' => FALSE,
        'add_field' => TRUE,
      ],
      'pan_truncation' => [
        'is_required' => FALSE,
        'add_field' => TRUE,
      ],
      'trxn_id' => [
        'add_field' => TRUE,
        'is_required' => FALSE,
      ],
      'trxn_date' => [
        'htmlType' => 'datepicker',
        'name' => 'trxn_date',
        'title' => ts('Transaction Date'),
        'is_required' => TRUE,
        'attributes' => [
          'date' => 'yyyy-mm-dd',
          'time' => 24,
        ],
      ],
      'total_amount' => [
        'htmlType' => 'text',
        'name' => 'total_amount',
        'title' => ts('Total Amount'),
        'is_required' => TRUE,
        'attributes' => [
          'readonly' => TRUE,
          'size' => 6,
        ],
      ],
    ];

    return $paymentFields;
  }

}
