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
 * This class generates form components for processing a contribution
 * CRM-16229 - During the event registration bulk action via search we
 * need to inherit CRM_Contact_Form_Task so that we can inherit functions
 * like getContactIds and make use of controller state. But this is not possible
 * because CRM_Event_Form_Participant inherits this class.
 * Ideal situation would be something like
 * CRM_Event_Form_Participant extends CRM_Contact_Form_Task,
 * CRM_Contribute_Form_AbstractEditPayment
 * However this is not possible. Currently PHP does not support multiple
 * inheritance. So work around solution is to extend this class with
 * CRM_Contact_Form_Task which further extends CRM_Core_Form.
 *
 */
class CRM_Contribute_Form_AbstractEditPayment extends CRM_Contact_Form_Task {

  use CRM_Financial_Form_SalesTaxTrait;

  public $_mode;

  public $_action;

  /**
   * @var int
   *
   * @deprecated
   */
  public $_bltID;

  public $_fields = [];

  /**
   * Current payment processor including a copy of the object in 'object' key.
   *
   * @var array
   */
  public $_paymentProcessor;

  /**
   * Available recurring processors.
   *
   * @var array
   */
  public $_recurPaymentProcessors = [];

  /**
   * Array of processor options in the format id => array($id => $label)
   * WARNING it appears that the format used to differ to this and there are places in the code that
   * expect the old format. $this->_paymentProcessors provides the additional data which this
   * array seems to have provided in the past
   * @var array
   */
  public $_processors;

  /**
   * Available payment processors with full details including the key 'object' indexed by their id
   * @var array
   */
  protected $_paymentProcessors = [];

  /**
   * Entity that $this->_id relates to.
   *
   * If set the contact id is not required in the url.
   *
   * @var string
   */
  protected $entity;

  /**
   * The id of the note
   *
   * @var int
   */
  public $_noteID;

  /**
   * The id of the contact associated with this contribution
   *
   * @var int
   */
  public $_contactID;

  /**
   * The id of the pledge payment that we are processing
   *
   * @var int
   */
  public $_ppID;

  /**
   * The id of the pledge that we are processing
   *
   * @var int
   */
  public $_pledgeID;

  /**
   * Is this contribution associated with an online
   * financial transaction
   *
   * @var bool
   */
  public $_online = FALSE;

  /**
   * Stores all product option
   *
   * @var array
   */
  public $_options;

  /**
   * Stores the honor id
   *
   * @var int
   */
  public $_honorID = NULL;

  /**
   * Array of payment related fields to potentially display on this form (generally credit card or debit card fields).
   *
   * Note that this is not accessed in core except in a function that could use
   * a local variable but both IATS & TSys access it.
   *
   * This is rendered via billingBlock.tpl.
   *
   * @var array
   */
  public $_paymentFields = [];

  /**
   * The contribution values if an existing contribution
   * @var array
   */
  public $_values;

  /**
   * The pledge values if this contribution is associated with pledge
   * @var array
   */
  public $_pledgeValues;

  public $_context;

  public $_compId;

  /**
   * Contribution ID.
   *
   * @var int|null
   */
  protected $contributionID;

  /**
   * Get the contribution ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @return int|null
   */
  public function getContributionID(): ?int {
    return $this->contributionID;
  }

  /**
   * Set the contribution id that has been created or is being edited.
   *
   * @internal - not supported for outside core.
   *
   * @param int|null $contributionID
   */
  protected function setContributionID(?int $contributionID): void {
    $this->contributionID = $contributionID;
  }

  /**
   * Store the line items if price set used.
   * @var array
   */
  public $_lineItems;

  /**
   * Is this a backoffice form
   *
   * @var bool
   */
  public $isBackOffice = TRUE;

  protected $_formType;

  /**
   * Payment instrument id for the transaction.
   *
   * @var int
   */
  public $paymentInstrumentID;

  /**
   * Component - event, membership or contribution.
   *
   * @var string
   */
  protected $_component;

  /**
   * Monetary fields that may be submitted.
   *
   * These should get a standardised format in the beginPostProcess function.
   *
   * These fields are common to many forms. Some may override this.
   * @var array
   */
  protected $submittableMoneyFields = ['total_amount', 'net_amount', 'non_deductible_amount', 'fee_amount'];

  /**
   * Invoice ID.
   *
   * This is a generated unique string.
   *
   * @var string
   */
  protected $invoiceID;

  /**
   * Provide support for extensions that are used to being able to retrieve _lineItem
   *
   * Note extension should call getPriceSetID() and getLineItems() directly.
   * They are supported for external use per the api annotation.
   *
   * @param string $name
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function __get($name) {
    if ($name === '_params') {
      CRM_Core_Error::deprecatedWarning('attempt to access undefined property _params - use externally supported function getSubmittedValues()');
      return $this->getSubmittedValues();
    }
    if ($name === '_lineItem') {
      CRM_Core_Error::deprecatedWarning('attempt to access undefined property _params - use externally supported function getSubmittedValues()');
      return [0 => $this->getLineItems()];
    }
    CRM_Core_Error::deprecatedWarning('attempt to access invalid property :' . $name);
  }

  /**
   * Get the unique invoice ID.
   *
   * This is generated if one has not already been generated.
   *
   * @return string
   */
  public function getInvoiceID(): string {
    if (!$this->invoiceID) {
      $this->invoiceID = md5(uniqid(mt_rand(), TRUE));
    }
    return $this->invoiceID;
  }

  /**
   * Pre process function with common actions.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $this->assignContactID();
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
    $this->_mode = empty($this->_mode) ? CRM_Utils_Request::retrieve('mode', 'Alphanumeric', $this) : $this->_mode;
    $this->assign('isBackOffice', $this->isBackOffice);
    $this->assignContactEmailDetails();
    $this->assignPaymentRelatedVariables();
  }

  /**
   * Get the contact ID in use.
   *
   * Ideally override this as appropriate to the form.
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocSignatureIsNotCompleteInspection
   */
  public function getContactID():?int {
    if ($this->_contactID === NULL) {
      $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
      if (empty($this->_contactID) && !empty($this->_id) && $this->entity) {
        $this->_contactID = civicrm_api3($this->entity, 'getvalue', ['id' => $this->_id, 'return' => 'contact_id']);
      }
    }
    return $this->_contactID ? (int) $this->_contactID : NULL;
  }

  /**
   * @param int $id
   */
  public function showRecordLinkMesssage($id) {
    $statusId = CRM_Core_DAO::getFieldValue('CRM_Contribute_BAO_Contribution', $id, 'contribution_status_id');
    if (CRM_Contribute_PseudoConstant::contributionStatus($statusId, 'name') == 'Partially paid') {
      if ($pid = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_ParticipantPayment', $id, 'participant_id', 'contribution_id')) {
        $recordPaymentLink = CRM_Utils_System::url('civicrm/payment',
          "reset=1&id={$pid}&cid={$this->_contactID}&action=add&component=event"
        );
        CRM_Core_Session::setStatus(ts('Please use the <a href="%1">Record Payment</a> form if you have received an additional payment for this Partially paid contribution record.', [1 => $recordPaymentLink]), ts('Notice'), 'alert');
      }
    }
  }

  /**
   * @param int $id
   * @param $values
   */
  public function buildValuesAndAssignOnline_Note_Type($id, &$values) {
    $ids = [];
    $params = ['id' => $id];
    CRM_Contribute_BAO_Contribution::getValues($params, $values, $ids);

    //Check if this is an online transaction (financial_trxn.payment_processor_id NOT NULL)
    $this->_online = FALSE;
    $fids = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($id);
    if (!empty($fids['financialTrxnId'])) {
      $this->_online = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialTrxn', $fids['financialTrxnId'], 'payment_processor_id');
    }

    // Also don't allow user to update some fields for recurring contributions.
    if (!$this->_online) {
      $this->_online = $values['contribution_recur_id'] ?? NULL;
    }

    $this->assign('isOnline', (bool) $this->_online);

    //to get note id
    $daoNote = new CRM_Core_BAO_Note();
    $daoNote->entity_table = 'civicrm_contribution';
    $daoNote->entity_id = $id;
    if ($daoNote->find(TRUE)) {
      $this->_noteID = $daoNote->id;
      $values['note'] = $daoNote->note;
    }
  }

  /**
   * @return array
   *   Array of valid processors. The array resembles the DB table but also has 'object' as a key
   * @throws Exception
   */
  public function getValidProcessors() {
    $capabilities = ['BackOffice'];
    if ($this->_mode) {
      $capabilities[] = (ucfirst($this->_mode) . 'Mode');
    }
    $processors = CRM_Financial_BAO_PaymentProcessor::getPaymentProcessors($capabilities);
    return $processors;

  }

  /**
   * Assign $this->processors, $this->recurPaymentProcessors, and related Smarty variables
   */
  public function assignProcessors() {
    //ensure that processor has a valid config
    //only valid processors get display to user
    $this->assign('processorSupportsFutureStartDate', CRM_Financial_BAO_PaymentProcessor::hasPaymentProcessorSupporting(['FutureRecurStartDate']));
    $this->_paymentProcessors = $this->getValidProcessors();
    if (!isset($this->_paymentProcessor['id'])) {
      // if the payment processor isn't set yet (as indicated by the presence of an id,) we'll grab the first one which should be the default
      $this->_paymentProcessor = reset($this->_paymentProcessors);
    }
    if (!$this->_mode) {
      $this->_paymentProcessor = $this->_paymentProcessors[0];
    }
    elseif (empty($this->_paymentProcessors) || array_keys($this->_paymentProcessors) === [0]) {
      throw new CRM_Core_Exception(ts('You will need to configure the %1 settings for your Payment Processor before you can submit a credit card transactions.', [1 => $this->_mode]));
    }
    //Assign submitted processor value if it is different from the loaded one.
    if (!empty($this->_submitValues['payment_processor_id'])
      && $this->_paymentProcessor['id'] != $this->_submitValues['payment_processor_id']) {
      $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($this->_submitValues['payment_processor_id']);
    }
    $this->_processors = [];
    foreach ($this->_paymentProcessors as $id => $processor) {
      $this->_processors[$id] = $processor['name'];
      if (!empty($processor['description'])) {
        $this->_processors[$id] .= ' : ' . $processor['description'];
      }
      if ($this->_paymentProcessors[$id]['object']->supportsRecurring()) {
        $this->_recurPaymentProcessors[$id] = $this->_processors[$id];
      }
    }
    // CRM-21002: pass the default payment processor ID whose credit card type icons should be populated first
    CRM_Financial_Form_Payment::addCreditCardJs($this->_paymentProcessor['id']);

    $this->assign('recurringPaymentProcessorIds',
      empty($this->_recurPaymentProcessors) ? '' : implode(',', array_keys($this->_recurPaymentProcessors))
    );

    // this required to show billing block
    // @todo remove this assignment the billing block is now designed to be always included but will not show fieldsets unless those sets of fields are assigned
    $this->assign('paymentProcessor', $this->_paymentProcessor);
  }

  /**
   * @param array $submittedValues
   *
   * @return mixed
   */
  public function unsetCreditCardFields($submittedValues) {
    //Offline Contribution.
    $billingLocationTypeID = CRM_Core_BAO_LocationType::getBilling();
    $unsetParams = [
      'payment_processor_id',
      "email-{$billingLocationTypeID}",
      'hidden_buildCreditCard',
      'hidden_buildDirectDebit',
      'billing_first_name',
      'billing_middle_name',
      'billing_last_name',
      'street_address-5',
      "city-{$billingLocationTypeID}",
      "state_province_id-{$billingLocationTypeID}",
      "postal_code-{$billingLocationTypeID}",
      "country_id-{$billingLocationTypeID}",
      'credit_card_number',
      'cvv2',
      'credit_card_exp_date',
      'credit_card_type',
    ];
    foreach ($unsetParams as $key) {
      if (isset($submittedValues[$key])) {
        unset($submittedValues[$key]);
      }
    }
    return $submittedValues;
  }

  /**
   * Common block for setting up the parts of a form that relate to credit / debit card
   */
  protected function assignPaymentRelatedVariables() {
    try {
      $this->assignProcessors();
      $this->assignBillingType();
      CRM_Core_Payment_Form::setPaymentFieldsByProcessor($this, $this->_paymentProcessor, FALSE, TRUE, CRM_Utils_Request::retrieve('payment_instrument_id', 'Integer', $this));
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Error::statusBounce($e->getMessage());
    }
  }

  /**
   * Begin post processing.
   *
   * This function aims to start to bring together common postProcessing functions.
   *
   * Eventually these are also shared with the front end forms & may need to be moved to where they can also
   * access this function.
   */
  protected function beginPostProcess() {
    if ($this->_mode) {
      $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment(
        $this->_params['payment_processor_id'],
        ($this->_mode == 'test')
      );
    }
    $this->_params['ip_address'] = CRM_Utils_System::ipAddress();

    $valuesForForm = self::formatCreditCardDetails($this->_params);
    $this->assignVariables($valuesForForm, ['credit_card_exp_date', 'credit_card_type', 'credit_card_number']);

    foreach ($this->submittableMoneyFields as $moneyField) {
      if (isset($this->_params[$moneyField])) {
        $this->_params[$moneyField] = CRM_Utils_Rule::cleanMoney($this->_params[$moneyField]);
      }
    }
    if (!empty($this->_params['contact_id']) && empty($this->_contactID)) {
      // Contact ID has been set in the standalone form.
      $this->_contactID = $this->_params['contact_id'];
      $this->assignContactEmailDetails();
    }
  }

  /**
   * Format credit card details like:
   *  1. Retrieve last 4 digit from credit card number as pan_truncation
   *  2. Retrieve credit card type id from name
   *
   * @param array $params
   *
   * @return array An array of params suitable for assigning to the form/tpl
   */
  public static function formatCreditCardDetails(&$params) {
    if (!empty($params['credit_card_exp_date'])) {
      $params['year'] = CRM_Core_Payment_Form::getCreditCardExpirationYear($params);
      $params['month'] = CRM_Core_Payment_Form::getCreditCardExpirationMonth($params);
    }
    if (!empty($params['credit_card_type'])) {
      $params['card_type_id'] = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_FinancialTrxn', 'card_type_id', $params['credit_card_type']);
    }
    if (!empty($params['credit_card_number']) && empty($params['pan_truncation'])) {
      $params['pan_truncation'] = substr($params['credit_card_number'], -4);
    }
    if (!empty($params['credit_card_exp_date'])) {
      $params['year'] = CRM_Core_Payment_Form::getCreditCardExpirationYear($params);
      $params['month'] = CRM_Core_Payment_Form::getCreditCardExpirationMonth($params);
    }

    $tplParams['credit_card_exp_date'] = isset($params['credit_card_exp_date']) ? CRM_Utils_Date::mysqlToIso(CRM_Utils_Date::format($params['credit_card_exp_date'])) : NULL;
    $tplParams['credit_card_type'] = $params['credit_card_type'] ?? NULL;
    $tplParams['credit_card_number'] = CRM_Utils_System::mungeCreditCard(CRM_Utils_Array::value('credit_card_number', $params));
    return $tplParams;
  }

  /**
   * Add the billing address to the contact who paid.
   *
   * Note that this function works based on the presence or otherwise of billing fields & can be called regardless of
   * whether they are 'expected' (due to assumptions about the payment processor type or the setting to collect billing
   * for pay later.
   *
   * @param int $contactID
   * @param string $email
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function processBillingAddress(int $contactID, string $email): void {
    $fields = [];

    $fields['email-Primary'] = 1;
    $this->_params['email-5'] = $this->_params['email-Primary'] = $email;
    // now set the values for the billing location.
    foreach (array_keys($this->_fields) as $name) {
      $fields[$name] = 1;
    }
    $billingLocationID = CRM_Core_BAO_LocationType::getBilling();
    $fields["address_name-{$billingLocationID}"] = 1;

    //ensure we don't over-write the payer's email with the member's email
    if ($contactID == $this->_contactID) {
      $fields["email-{$billingLocationID}"] = 1;
    }

    [$hasBillingField, $addressParams] = CRM_Contribute_BAO_Contribution::getPaymentProcessorReadyAddressParams($this->_params);
    $fields = $this->formatParamsForPaymentProcessor($fields);

    if ($hasBillingField) {
      $addressParams = array_merge($this->_params, $addressParams);
      // CRM-18277 don't let this get passed in because we don't want contribution source to override contact source.
      // Ideally we wouldn't just randomly merge everything into addressParams but just pass in a relevant array.
      // Note this source field is covered by a unit test.
      if (isset($addressParams['source'])) {
        unset($addressParams['source']);
      }
      //here we are setting up the billing contact - if different from the member they are already created
      // but they will get billing details assigned
      $addressParams['contact_id'] = $contactID;
      CRM_Contact_BAO_Contact::createProfileContact($addressParams, $fields,
        $contactID, NULL, NULL,
        CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactID, 'contact_type')
      );
    }

    $this->assignBillingName($this->_params);
  }

  /**
   * Get default values for billing fields.
   *
   * @todo this function still replicates code in several other places in the code.
   *
   * Also - the call to getProfileDefaults possibly covers the state_province & country already.
   *
   * @param $defaults
   *
   * @return array
   */
  protected function getBillingDefaults($defaults) {
    // set default country from config if no country set
    $config = CRM_Core_Config::singleton();
    $billingLocationTypeID = CRM_Core_BAO_LocationType::getBilling();
    if (empty($defaults["billing_country_id-{$billingLocationTypeID}"])) {
      $defaults["billing_country_id-{$billingLocationTypeID}"] = \Civi::settings()->get('defaultContactCountry');
    }

    if (empty($defaults["billing_state_province_id-{$billingLocationTypeID}"])) {
      $defaults["billing_state_province_id-{$billingLocationTypeID}"] = \Civi::settings()->get('defaultContactStateProvince');
    }

    $billingDefaults = $this->getProfileDefaults('Billing', $this->_contactID);
    return array_merge($defaults, $billingDefaults);
  }

  /**
   * Get the default payment instrument id.
   *
   * This priortises the submitted value, if any and falls back on the processor.
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  protected function getDefaultPaymentInstrumentId(): int {
    $paymentInstrumentID = CRM_Utils_Request::retrieve('payment_instrument_id', 'Integer');
    return (int) ($paymentInstrumentID ?? $this->_paymentProcessor['payment_instrument_id']);
  }

  /**
   * Add the payment processor select to the form.
   *
   * @param bool $isRequired
   *   Is it a mandatory field.
   * @param bool $isBuildRecurBlock
   *   True if we want to build recur on change
   * @param bool $isBuildAutoRenewBlock
   *   True if we want to build autorenew on change.
   */
  protected function addPaymentProcessorSelect($isRequired, $isBuildRecurBlock = FALSE, $isBuildAutoRenewBlock = FALSE) {
    if (!$this->_mode) {
      return;
    }
    $js = ($isBuildRecurBlock ? ['onChange' => "buildRecurBlock( this.value ); return false;"] : []);
    if ($isBuildAutoRenewBlock) {
      $js = ['onChange' => "buildAutoRenew( null, this.value, '{$this->_mode}');"];
    }
    $element = $this->add('select',
      'payment_processor_id',
      ts('Payment Processor'),
      array_diff_key($this->_processors, [0 => 1]),
      $isRequired,
      $js + ['class' => 'crm-select2']
    );
    // The concept of _online is not really explained & the code is old
    // @todo figure out & document.
    if ($this->_online) {
      $element->freeze();
    }
  }

  /**
   * Assign the values to build the payment info block.
   */
  protected function assignPaymentInfoBlock() {
    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($this->_id, $this->_component, TRUE);
    $this->assign('transaction', TRUE);
    $this->assign('payments', $paymentInfo['transaction'] ?? NULL);
    $this->assign('paymentLinks', $paymentInfo['payment_links']);
  }

  protected function assignContactEmailDetails(): void {
    if ($this->getContactID()) {
      [$displayName] = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->getContactID());
      if (!$displayName) {
        $displayName = civicrm_api3('contact', 'getvalue', ['id' => $this->getContactID(), 'return' => 'display_name']);
      }
    }
    $this->assign('displayName', $displayName ?? NULL);
  }

  protected function assignContactID(): void {
    $this->assign('contactID', $this->getContactID());
    CRM_Core_Resources::singleton()
      ->addVars('coreForm', ['contact_id' => (int) $this->getContactID()]);
  }

}
