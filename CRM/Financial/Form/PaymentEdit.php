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
class CRM_Financial_Form_PaymentEdit extends CRM_Core_Form {
  use CRM_Custom_Form_CustomDataTrait;

  /**
   * Should financials be checked after the test but before tear down.
   *
   * Ideally all tests (or at least all that call any financial api calls ) should do this but there
   * are some test data issues and some real bugs currently blocking.
   *
   * @var bool
   */
  protected $isValidateFinancialsOnPostAssert = TRUE;

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
   * Get the related contribution id.
   *
   * @return int
   */
  public function getContributionID(): int {
    return $this->_contributionID;
  }

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
    $defaults['total_amount'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($this->_values['total_amount']);
    return $defaults;
  }

  /**
   * Build quickForm.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    $this->setTitle(ts('Update Payment details'));

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
    if ($this->isSubmitted()) {
      // The custom data fields are added to the form by an ajax form.
      // However, if they are not present in the element index they will
      // not be available from `$this->getSubmittedValue()` in post process.
      // We do not have to set defaults or otherwise render - just add to the element index.
      $this->addCustomDataFieldsToForm('FinancialTrxn', array_filter([
        'id' => $this->_id,
      ]));
    }

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
   * @param self $self
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];

    // if Credit Card is chosen and pan_truncation is not NULL ensure that it's value is numeric else throw validation error
    if (CRM_Core_PseudoConstant::getName('CRM_Financial_DAO_FinancialTrxn', 'payment_instrument_id', $fields['payment_instrument_id']) === 'Credit Card' &&
      !empty($fields['pan_truncation']) &&
      !CRM_Utils_Rule::numeric($fields['pan_truncation'])
    ) {
      $errors['pan_truncation'] = ts('Please enter a valid Card Number');
    }

    return $errors;
  }

  /**
   * Process the form submission.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess(): void {
    $params = [
      'id' => $this->_id,
      'payment_instrument_id' => $this->getSubmittedValue('payment_instrument_id'),
      'trxn_id' => $this->getSubmittedValue('trxn_id'),
      'trxn_date' => $this->getSubmittedValue('trxn_date') ?: date('YmdHis'),
    ];

    $paymentInstrumentName = CRM_Core_PseudoConstant::getName('CRM_Financial_DAO_FinancialTrxn', 'payment_instrument_id', $params['payment_instrument_id']);
    if ($paymentInstrumentName === 'Credit Card') {
      $params['card_type_id'] = $this->getSubmittedValue('card_type_id');
      $params['pan_truncation'] = $this->getSubmittedValue('pan_truncation');
    }
    elseif ($paymentInstrumentName === 'Check') {
      $params['check_number'] = $this->getSubmittedValue('check_number');
    }

    $this->submit($params);

    $contactId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $this->getContributionID(), 'contact_id');
    $url = CRM_Utils_System::url(
      "civicrm/contact/view/contribution",
      "reset=1&action=update&id=" . $this->getContributionID() . "&cid={$contactId}&context=contribution"
    );
    CRM_Core_Session::singleton()->pushUserContext($url);
  }

  /**
   * Wrapper function to process form submission
   *
   * @param array $submittedValues
   *
   * @throws \CRM_Core_Exception
   */
  protected function submit($submittedValues) {
    // if payment instrument is changed then
    //  1. Record a new reverse financial transaction with old payment instrument
    //  2. Record a new financial transaction with new payment instrument
    //  3. Add EntityFinancialTrxn records to relate with corresponding financial item and contribution
    if ($submittedValues['payment_instrument_id'] != $this->_values['payment_instrument_id']) {
      civicrm_api3('Payment', 'cancel', [
        'id' => $this->_values['id'],
        'trxn_date' => $submittedValues['trxn_date'],
      ]);

      $newFinancialTrxn = $submittedValues;
      unset($newFinancialTrxn['id']);
      $newFinancialTrxn['to_financial_account_id'] = CRM_Financial_BAO_EntityFinancialAccount::getInstrumentFinancialAccount($submittedValues['payment_instrument_id']);
      $newFinancialTrxn['total_amount'] = $this->_values['total_amount'];
      $newFinancialTrxn['currency'] = $this->_values['currency'];
      $newFinancialTrxn['contribution_id'] = $this->getContributionID();
      $newFinancialTrxn += $this->getSubmittedCustomFields();
      civicrm_api3('Payment', 'create', $newFinancialTrxn);
    }
    else {
      // simply update the financial trxn
      civicrm_api3('FinancialTrxn', 'create', $submittedValues + $this->getSubmittedCustomFields());
    }

    CRM_Financial_BAO_Payment::updateRelatedContribution($submittedValues, $this->getContributionID());
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
