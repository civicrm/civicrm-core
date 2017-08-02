<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
 */
class CRM_Financial_Form_PaymentEdit extends CRM_Core_Form {

  /**
   * The id of the financial trxn.
   *
   * @var int
   */
  protected $_id;

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

    $this->_values = civicrm_api3('FinancialTrxn', 'getsingle', array('id' => $this->_id));
    if (!empty($this->_values['payment_processor_id'])) {
      CRM_Core_Error::statusBounce(ts('You cannot update this payment'));
    }
  }

  /**
   * Set default values.
   *
   * @return array
   */
  public function setDefaultValues() {
    return $this->_values;
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
        $attributes = array(
          'entity' => 'FinancialTrxn',
          'name' => $name,
        );
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
    $this->addFormRule(array(__CLASS__, 'formRule'), $this);

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Update'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    ));
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
    $errors = array();

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
    $params = array(
      'id' => $this->_id,
      'payment_instrument_id' => $this->_submitValues['payment_instrument_id'],
      'trxn_id' => CRM_Utils_Array::value('trxn_id', $this->_submitValues),
      'trxn_date' => CRM_Utils_Array::value('trxn_date', $this->_submitValues, date('YmdHis')),
    );

    $paymentInstrumentName = CRM_Core_PseudoConstant::getName('CRM_Financial_DAO_FinancialTrxn', 'payment_instrument_id', $params['payment_instrument_id']);
    if ($paymentInstrumentName == 'Credit Card') {
      $params['card_type_id'] = CRM_Utils_Array::value('card_type_id', $this->_submitValues);
      $params['pan_truncation'] = CRM_Utils_Array::value('pan_truncation', $this->_submitValues);
    }
    elseif ($paymentInstrumentName == 'Check') {
      $params['check_number'] = CRM_Utils_Array::value('check_number', $this->_submitValues);
    }

    if ($this->_submitValues['payment_instrument_id'] != $this->_values['payment_instrument_id']) {
      //first reverse previous transaction
      $previousFinanciaTrxn = $this->_values;
      unset($previousFinanciaTrxn['id'], $params['id']);
      $previousFinanciaTrxn['trxn_date'] = CRM_Utils_Array::value('trxn_date', $params, date('YmdHis'));
      $previousFinanciaTrxn['total_amount'] = -$previousFinanciaTrxn['total_amount'];
      $previousFinanciaTrxn['net_amount'] = -$previousFinanciaTrxn['net_amount'];
      $previousFinanciaTrxn['fee_amount'] = -$previousFinanciaTrxn['fee_amount'];
      $previousFinanciaTrxn['to_financial_account_id'] = CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($params['payment_instrument_id']);
      $previousFinanciaTrxn['contribution_id'] = self::getContributionIDbyTrxn($this->_id);
      CRM_Core_BAO_FinancialTrxn::create($previousFinanciaTrxn);

      $params['to_financial_account_id'] = $previousFinanciaTrxn['to_financial_account_id'];
      $params['contribution_id'] = $previousFinanciaTrxn['contribution_id'];
      foreach (array('total_amount', 'fee_amount', 'net_amount', 'currency', 'is_payment', 'status_id') as $fieldName) {
        $params[$fieldName] = $this->_values[$fieldName];
      }
      CRM_Core_BAO_FinancialTrxn::create($params);
    }
    else {
      // simply update the financial trxn
      civicrm_api3('FinancialTrxn', 'create', $params);
    }
    CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath()));
  }

  /**
   * Get contribution ID from financial trx ID
   * @param int $financialTrxnID
   *
   * @return int contribution ID
   */
  public static function getContributionIDbyTrxn($financialTrxnID) {
    return CRM_Core_DAO::singleValueQuery("
      SELECT entity_id
      FROM civicrm_entity_financial_trxn
      WHERE entity_table = 'civicrm_contribution' AND financial_trxn_id = %1
      LIMIT 1
    ", array(1 => array($financialTrxnID, 'Integer')));
  }

  /**
   * Get payment fields
   */
  public function getPaymentFields() {
    $paymentFields = array(
      'payment_instrument_id' => array(
        'is_required' => TRUE,
        'add_field' => TRUE,
      ),
      'check_number' => array(
        'is_required' => FALSE,
        'add_field' => TRUE,
      ),
      // @TODO we need to show card type icon in place of select field
      'card_type_id' => array(
        'is_required' => FALSE,
        'add_field' => TRUE,
      ),
      'pan_truncation' => array(
        'is_required' => FALSE,
        'add_field' => TRUE,
      ),
      'trxn_id' => array(
        'add_field' => TRUE,
        'is_required' => FALSE,
      ),
      'trxn_date' => array(
        'htmlType' => 'datepicker',
        'name' => 'trxn_date',
        'title' => ts('Transaction Date'),
        'is_required' => TRUE,
        'attributes' => array(
          'date' => 'yyyy-mm-dd',
          'time' => 24,
        ),
      ),
      'total_amount' => array(
        'htmlType' => 'text',
        'name' => 'total_amount',
        'title' => ts('Total Amount'),
        'is_required' => TRUE,
        'attributes' => array(
          'readonly' => TRUE,
          'size' => 6,
        ),
      ),
    );

    return $paymentFields;
  }

}
