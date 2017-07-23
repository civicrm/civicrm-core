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
   * Set variables up before form is built.
   */
  public function preProcess() {
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
    $defaults = $this->_values;

    list($defaults['trxn_date'], $defaults['trxn_date_time']) = CRM_Utils_Date::setDateDefaults(CRM_Utils_Array::value('trxn_date', $defaults), 'activityDateTime');

    return $this->_values;
  }

  /**
   * Build quickForm.
   */
  public function buildQuickForm() {
    $paymentInstrumentLabel = CRM_Core_PseudoConstant::getLabel(
      'CRM_Financial_DAO_FinancialTrxn',
      'payment_instrument_id',
      $this->_values['payment_instrument_id']
    );
    CRM_Utils_System::setTitle(ts('Update %1 details', array(1 => $paymentInstrumentLabel)));

    $paymentFields = $this->getPaymentFields();
    $this->assign('paymentFields', $paymentFields);
    CRM_Financial_Form_Payment::addCreditCardJs();
    foreach($paymentFields as $name => $paymentField) {
      if ($name == 'amount') {

      }
      elseif ($name == 'trxn_date') {
        $this->addDateTime($name, $paymentField['title'], FALSE, array('formatType' => 'activityDateTime'));
      }
      else {
        $this->add($paymentField['htmlType'],
          $paymentField['name'],
          $paymentField['title'],
          $paymentField['attributes'],
          FALSE
        );
        if (!empty($paymentField['rules'])) {
          foreach ($paymentField['rules'] as $rule) {
            $this->addRule($name,
              $rule['rule_message'],
              $rule['rule_name'],
              $rule['rule_parameters']
            );
          }
        }
      }
    }

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
   * Process the form submission.
   */
  public function postProcess() {
    $params = array(
      'id' => $this->_id,
      'check_number' => CRM_Utils_Array::value('check_number', $this->_submitValues),
      'pan_truncation' => CRM_Utils_Array::value('pan_truncation', $this->_submitValues),
    );
    if (!empty($this->_submitValues['credit_card_type'])) {
      $params['card_type_id'] = CRM_Core_PseudoConstant::getKey(
        'CRM_Financial_DAO_FinancialTrxn',
        'card_type_id',
        $this->_submitValues['credit_card_type']
      );
    }
    // update the financial trxn
    civicrm_api3('FinancialTrxn', 'create', $params);
    CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath()));
  }

  public function getPaymentFields() {
    $creditCardType = array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::creditCard();
    $paymentFields = array(
      'credit_card_type' => array(
        'htmlType' => 'select',
        'name' => 'credit_card_type',
        'title' => ts('Card Type'),
        'cc_field' => TRUE,
        'attributes' => $creditCardType,
        'is_required' => FALSE,
      ),
      'check_number' => array(
        'htmlType' => 'text',
        'name' => 'check_number',
        'title' => ts('Check Number'),
        'is_required' => FALSE,
        'cc_field' => TRUE,
        'attributes' => NULL,
      ),
      'pan_truncation' => array(
        'htmlType' => 'text',
        'name' => 'pan_truncation',
        'title' => ts('Last 4 digits of the card'),
        'is_required' => FALSE,
        'cc_field' => TRUE,
        'attributes' => array(
          'size' => 4,
          'maxlength' => 4,
          'minlength' => 4,
          'autocomplete' => 'off',
        ),
        'rules' => array(
          array(
            'rule_message' => ts('Please enter valid last 4 digit card number.'),
            'rule_name' => 'numeric',
            'rule_parameters' => NULL,
          ),
        ),
      ),
      'trxn_date' => array(
        'name' => 'trxn_date',
        'title' => ts('Transaction Date'),
      ),
      'amount' => array(
        'name' => 'amount',
        'title' => ts('Amount'),
      ),
    );

    return $paymentFields;
  }

}
