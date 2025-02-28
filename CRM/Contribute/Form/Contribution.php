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

use Civi\Api4\Contribution;
use Civi\Api4\FinancialType;
use Civi\Payment\Exception\PaymentProcessorException;

/**
 * This class generates form components for processing a contribution.
 */
class CRM_Contribute_Form_Contribution extends CRM_Contribute_Form_AbstractEditPayment {
  use CRM_Contact_Form_ContactFormTrait;
  use CRM_Contribute_Form_ContributeFormTrait;
  use CRM_Financial_Form_PaymentProcessorFormTrait;
  use CRM_Custom_Form_CustomDataTrait;

  /**
   * The id of the contribution that we are processing.
   *
   * @var int
   */
  public $_id;

  /**
   * The id of the premium that we are processing.
   *
   * @var int
   */
  public $_premiumID;

  /**
   * @var CRM_Contribute_DAO_ContributionProduct
   */
  public $_productDAO;

  /**
   * The id of the note.
   *
   * @var int
   */
  public $_noteID;

  /**
   * The id of the contact associated with this contribution.
   *
   * @var int
   */
  public $_contactID;

  /**
   * The id of the pledge payment that we are processing.
   *
   * @var int
   * @internal Only retrieve using $this->getPledgePaymentID().
   */
  public $_ppID;

  /**
   * Is this contribution associated with an online.
   * financial transaction
   *
   * @var bool
   */
  public $_online = FALSE;

  /**
   * Stores all product options.
   *
   * @var array
   */
  public $_options;

  /**
   * Storage of parameters from form
   *
   * @var array
   */
  public $_params;

  /**
   * The contribution values if an existing contribution
   *
   * @var array
   *
   * @deprecated - try to use getContributionValue() instead as it is strictly a
   * cached lookup on the contribution values, rather than a grab-bag.
   */
  public $_values;

  /**
   * The pledge values if this contribution is associated with pledge
   * @var array
   */
  public $_pledgeValues;

  public $_context;

  /**
   * Parameter with confusing name.
   * @var string
   * @todo what is it?
   */
  public $_compContext;

  public $_compId;

  /**
   * Possible From email addresses
   * @var array
   */
  public $_fromEmails;

  /**
   * ID of from email.
   *
   * @var int
   */
  public $fromEmailId;

  /**
   * Store the line items if price set used.
   * @var array
   */
  public $_lineItems;

  /**
   * Line item
   * @var array
   * @todo explain why we use lineItem & lineItems
   */
  public $_lineItem;

  /**
   * Soft credit info.
   *
   * @var array
   */
  public $_softCreditInfo;

  protected $_formType;

  /**
   * Array of the payment fields to be displayed in the payment fieldset (pane) in billingBlock.tpl
   * this contains all the information to describe these fields from quickform. See CRM_Core_Form_Payment getPaymentFormFieldsMetadata
   *
   * @var array
   */
  public $_paymentFields = [];

  /**
   * Price set ID.
   *
   * @var int
   */
  public $_priceSetId;

  /**
   * Price set as an array
   *
   * @var array
   */
  public $_priceSet;

  /**
   * Status message to be shown to the user.
   *
   * @var array
   */
  protected $statusMessage = [];

  /**
   * Status message title to be shown to the user.
   *
   * Generally the payment processor message title is 'Complete' and offline is 'Saved'
   * although this might not be a good fit with the broad range of processors.
   *
   * @var string
   */
  protected $statusMessageTitle;

  /**
   * @var int
   *
   * Max row count for soft credits. The value here is +1 the actual number of
   * rows displayed.
   */
  public $_softCreditItemCount = 11;

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Status of contribution prior to edit.
   *
   * @var string
   */
  protected $previousContributionStatus;


  /**
   * Payment Instrument ID
   *
   * @var int
   */
  public $payment_instrument_id;

  /**
   * @var bool
   */
  private $_payNow;

  private $order;

  /**
   * Explicitly declare the form context.
   */
  public function getDefaultContext() {
    return 'create';
  }

  public function __get($name) {
    if ($name === '_contributionID') {
      CRM_Core_Error::deprecatedWarning('_contributionID is not a form property - use getContributionID()');
      return $this->getContributionID();
    }
    return NULL;
  }

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess(): void {
    // Check permission for action.
    if (!CRM_Core_Permission::checkActionPermission('CiviContribute', $this->_action)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    if ($this->_action & CRM_Core_Action::UPDATE && !Contribution::checkAccess()
      ->setAction('update')
      ->addValue('id', $this->getContributionID())
      ->execute()->first()['access']) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    parent::preProcess();

    $this->_formType = $_GET['formType'] ?? NULL;

    // Get price set id.
    $this->_priceSetId = $_GET['priceSetId'] ?? NULL;
    $this->set('priceSetId', $this->_priceSetId);
    $this->assign('priceSetId', $this->_priceSetId);
    $this->assign('taxTerm', Civi::settings()->get('tax_term'));
    $this->assign('ppID', $this->getPledgePaymentID());

    $this->assign('action', $this->_action);

    // Get the contribution id if update
    $this->assign('isUsePaymentBlock', (bool) $this->getContributionID());
    if (!empty($this->_id)) {
      $this->assignPaymentInfoBlock();
      $this->assign('contribID', $this->_id);
    }

    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
    $this->assign('context', $this->_context);

    $this->_compId = CRM_Utils_Request::retrieve('compId', 'Positive', $this);

    $this->_compContext = CRM_Utils_Request::retrieve('compContext', 'String', $this);

    //set the contribution mode.
    $this->_mode = CRM_Utils_Request::retrieve('mode', 'Alphanumeric', $this);

    $this->assign('contributionMode', $this->_mode);
    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->_fromEmails = CRM_Core_BAO_Email::getFromEmail();

    if (CRM_Core_Component::isEnabled('CiviPledge') && !$this->_formType) {
      $this->preProcessPledge();
    }

    if ($this->_id) {
      $this->showRecordLinkMesssage($this->_id);
    }
    $this->_values = [];

    // Current contribution id.
    if ($this->_id) {
      $this->assignPremiumProduct($this->_id);
      $this->buildValuesAndAssignOnline_Note_Type($this->_id, $this->_values);
    }
    if (!isset($this->_values['is_template'])) {
      $this->_values['is_template'] = FALSE;
    }
    $this->assign('is_template', $this->_values['is_template']);

    if ($this->isSubmitted()) {
      // The custom data fields are added to the form by an ajax form.
      // However, if they are not present in the element index they will
      // not be available from `$this->getSubmittedValue()` in post process.
      // We do not have to set defaults or otherwise render - just add to the element index.
      $this->addCustomDataFieldsToForm('Contribution', array_filter([
        'id' => $this->getContributionID(),
        'financial_type_id' => $this->getFinancialTypeID(),
      ]));
    }

    $this->_lineItems = [];
    if ($this->_id) {
      if (!empty($this->_compId) && $this->_compContext === 'participant') {
        $this->assign('compId', $this->_compId);
        $lineItem = CRM_Price_BAO_LineItem::getLineItems($this->_compId);
      }
      else {
        $lineItem = CRM_Price_BAO_LineItem::getLineItems($this->_id, 'contribution', 1, TRUE, TRUE);
      }
      // wtf?
      empty($lineItem) ? NULL : $this->_lineItems[] = $lineItem;
    }

    $this->assign('lineItem', empty($lineItem) ? FALSE : [$lineItem]);

    // Set title
    if ($this->_mode && $this->_id) {
      $this->_payNow = TRUE;
      $this->setTitle(ts('Pay with Credit Card'));
    }
    elseif ($this->_values['is_template']) {
      $this->setPageTitle(ts('Template Contribution'));
    }
    elseif ($this->_mode) {
      $this->setPageTitle($this->_ppID ? ts('Credit Card Pledge Payment') : ts('Credit Card Contribution'));
    }
    else {
      $this->setPageTitle($this->_ppID ? ts('Pledge Payment') : ts('Contribution'));
    }
    $this->assign('payNow', $this->_payNow);
  }

  private function preProcessPledge(): void {
    //get the payment values associated with given pledge payment id OR check for payments due.
    $this->_pledgeValues = [];
    if ($this->_ppID) {
      $payParams = ['id' => $this->_ppID];

      CRM_Pledge_BAO_PledgePayment::retrieve($payParams, $this->_pledgeValues['pledgePayment']);
      $this->_pledgeID = $this->_pledgeValues['pledgePayment']['pledge_id'] ?? NULL;
      $paymentStatusID = $this->_pledgeValues['pledgePayment']['status_id'] ?? NULL;
      $this->_id = $this->_pledgeValues['pledgePayment']['contribution_id'] ?? NULL;

      //get all status
      $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
      if (!($paymentStatusID == array_search('Pending', $allStatus) || $paymentStatusID == array_search('Overdue', $allStatus))) {
        CRM_Core_Error::statusBounce(ts("Pledge payment status should be 'Pending' or  'Overdue'."));
      }

      //get the pledge values associated with given pledge payment.

      $ids = [];
      $pledgeParams = ['id' => $this->_pledgeID];
      CRM_Pledge_BAO_Pledge::getValues($pledgeParams, $this->_pledgeValues, $ids);
    }
    else {
      // Not making a pledge payment, so if adding a new contribution we should check if pledge payment(s) are due for this contact so we can alert the user. CRM-5206
      if (isset($this->_contactID)) {
        $contactPledges = CRM_Pledge_BAO_Pledge::getContactPledges($this->_contactID);

        if (!empty($contactPledges)) {
          $payments = $paymentsDue = NULL;
          $multipleDue = FALSE;
          foreach ($contactPledges as $key => $pledgeId) {
            $payments = CRM_Pledge_BAO_PledgePayment::getOldestPledgePayment($pledgeId);
            if ($payments) {
              if ($paymentsDue) {
                $multipleDue = TRUE;
                break;
              }
              else {
                $paymentsDue = $payments;
              }
            }
          }
          if ($multipleDue) {
            // Show link to pledge tab since more than one pledge has a payment due
            $pledgeTab = CRM_Utils_System::url('civicrm/contact/view',
              "reset=1&force=1&cid={$this->_contactID}&selectedChild=pledge"
            );
            CRM_Core_Session::setStatus(ts('This contact has pending or overdue pledge payments. <a href="%1">Click here to view their Pledges tab</a> and verify whether this contribution should be applied as a pledge payment.', [1 => $pledgeTab]), ts('Notice'), 'alert');
          }
          elseif ($paymentsDue) {
            // Show user link to oldest Pending or Overdue pledge payment
            $ppAmountDue = CRM_Utils_Money::format($payments['amount'], $payments['currency']);
            $ppSchedDate = CRM_Utils_Date::customFormat(CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_PledgePayment', $payments['id'], 'scheduled_date'));
            if ($this->_mode) {
              $ppUrl = CRM_Utils_System::url('civicrm/contact/view/contribution',
                "reset=1&action=add&cid={$this->_contactID}&ppid={$payments['id']}&context=pledge&mode=live"
              );
            }
            else {
              $ppUrl = CRM_Utils_System::url('civicrm/contact/view/contribution',
                "reset=1&action=add&cid={$this->_contactID}&ppid={$payments['id']}&context=pledge"
              );
            }
            CRM_Core_Session::setStatus(ts('This contact has a pending or overdue pledge payment of %2 which is scheduled for %3. <a href="%1">Click here to enter a pledge payment</a>.', [
              1 => $ppUrl,
              2 => $ppAmountDue,
              3 => $ppSchedDate,
            ]), ts('Notice'), 'alert');
          }
        }
      }
    }
  }

  /**
   * Set default values.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues() {
    $defaults = $this->_values;

    // Set defaults for pledge payment.
    if ($this->_ppID) {
      $defaults['total_amount'] = $this->_pledgeValues['pledgePayment']['scheduled_amount'] ?? NULL;
      $defaults['financial_type_id'] = $this->_pledgeValues['financial_type_id'] ?? NULL;
      $defaults['currency'] = $this->_pledgeValues['currency'] ?? NULL;
      $defaults['option_type'] = 1;
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      return $defaults;
    }

    $defaults['frequency_interval'] = 1;
    $defaults['frequency_unit'] = 'month';

    // Set soft credit defaults.
    CRM_Contribute_Form_SoftCredit::setDefaultValues($defaults, $this);

    if ($this->_mode) {
      // @todo - remove this function as the parent does it too.
      $config = CRM_Core_Config::singleton();
      // Set default country from config if no country set.
      if (empty($defaults["billing_country_id-{$this->_bltID}"])) {
        $defaults["billing_country_id-{$this->_bltID}"] = $config->defaultContactCountry;
      }

      if (empty($defaults["billing_state_province_id-{$this->_bltID}"])) {
        $defaults["billing_state_province_id-{$this->_bltID}"] = $config->defaultContactStateProvince;
      }

      $billingDefaults = $this->getProfileDefaults('Billing', $this->_contactID);
      $defaults = array_merge($defaults, $billingDefaults);
    }

    if ($this->_id) {
      $this->_contactID = $defaults['contact_id'];
    }
    elseif ($this->_contactID) {
      $defaults['contact_id'] = $this->_contactID;
    }

    // Set $newCredit variable in template to control whether link to credit card mode is included.
    $this->assign('newCredit', CRM_Core_Config::isEnabledBackOfficeCreditCardPayments());

    // Fix the display of the monetary value, CRM-4038.
    if (isset($defaults['total_amount'])) {
      $total_value = $defaults['total_amount'];
      $defaults['total_amount'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($total_value);
      if (!empty($defaults['tax_amount'])) {
        $componentDetails = CRM_Contribute_BAO_Contribution::getComponentDetails($this->_id);
        if (empty($componentDetails['membership']) && empty($componentDetails['participant'])) {
          $defaults['total_amount'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($total_value - $defaults['tax_amount']);
        }
      }
    }

    $amountFields = ['non_deductible_amount', 'fee_amount'];
    foreach ($amountFields as $amt) {
      if (isset($defaults[$amt])) {
        $defaults[$amt] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($defaults[$amt]);
      }
    }

    if (empty($defaults['payment_instrument_id'])) {
      $defaults['payment_instrument_id'] = $this->getDefaultPaymentInstrumentId();
    }

    $this->assign('is_test', !empty($defaults['is_test']));
    $this->assign('email', $this->getContactValue('email_primary.email'));
    $this->assign('is_pay_later', !empty($defaults['is_pay_later']));
    $this->assign('contribution_status_id', $defaults['contribution_status_id'] ?? NULL);
    $this->assign('showOption', TRUE);

    // For Premium section.
    if ($this->_premiumID) {
      $this->assign('showOption', FALSE);
      $options = $this->_options[$this->_productDAO->product_id] ?? '';
      if (!$options) {
        $this->assign('showOption', TRUE);
      }
      if ($this->_productDAO->product_option) {
        $defaults['product_name'] = [$this->_productDAO->product_id, $this->_productDAO->product_option];
      }
      else {
        $defaults['product_name'] = [$this->_productDAO->product_id];
      }
      if ($this->_productDAO->fulfilled_date) {
        $defaults['fulfilled_date'] = $this->_productDAO->fulfilled_date;
      }
    }

    if (!empty($defaults['contribution_status_id']) && in_array(
        CRM_Contribute_PseudoConstant::contributionStatus($defaults['contribution_status_id'], 'name'),
        // Historically not 'Cancelled' hence not using CRM_Contribute_BAO_Contribution::isContributionStatusNegative.
        ['Refunded', 'Chargeback']
      )) {
      $defaults['refund_trxn_id'] = CRM_Core_BAO_FinancialTrxn::getRefundTransactionTrxnID($this->_id);
    }
    else {
      $defaults['refund_trxn_id'] = $defaults['trxn_id'] ?? NULL;
    }

    if (!empty($defaults['contribution_status_id'])
      && ('Template' === CRM_Contribute_PseudoConstant::contributionStatus($defaults['contribution_status_id'], 'name'))
    ) {
      if ($this->elementExists('contribution_status_id')) {
        $this->getElement('contribution_status_id')->freeze();
      }
    }

    if (!$this->_id && empty($defaults['receive_date'])) {
      $defaults['receive_date'] = date('Y-m-d H:i:s');
    }

    $currency = $defaults['currency'] ?? NULL;
    $this->assign('currency', $currency);
    // Hack to get currency info to the js layer. CRM-11440.
    CRM_Utils_Money::format(1);
    $this->assign('currencySymbol', CRM_Utils_Money::$_currencySymbols[$currency] ?? NULL);
    $this->assign('totalAmount', $defaults['total_amount'] ?? NULL);

    // Inherit campaign from pledge.
    if ($this->_ppID && !empty($this->_pledgeValues['campaign_id'])) {
      $defaults['campaign_id'] = $this->_pledgeValues['campaign_id'];
    }

    $billing_address = '';
    if (!empty($defaults['address_id'])) {
      $addressDetails = CRM_Core_BAO_Address::getValues(['id' => $defaults['address_id']], FALSE, 'id');
      $addressDetails = array_values($addressDetails);
      $billing_address = $addressDetails[0]['display'];
    }
    $this->assign('billing_address', $billing_address);

    $this->_defaults = $defaults;
    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    if ($this->_id) {
      $this->add('hidden', 'id', $this->_id);
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Delete'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
      return;
    }
    $allPanes = [];

    //tax rate from financialType
    $this->assign('taxRates', json_encode(CRM_Core_PseudoConstant::getTaxRates()));
    $this->assign('currencies', json_encode(CRM_Core_OptionGroup::values('currencies_enabled')));

    // build price set form.
    $buildPriceSet = FALSE;
    $this->assign('invoicing', \Civi::settings()->get('invoicing'));
    // This is a probably-deprecated approach to partial payments - assign here
    // & if true it will be overwritten.
    $this->assign('payNow', FALSE);
    $buildRecurBlock = FALSE;

    if (empty($this->_lineItems) &&
      ($this->_priceSetId || !empty($_POST['price_set_id']))
    ) {
      $buildPriceSet = TRUE;
      $this->buildPriceSet();
      if (!$this->isSubmitted()) {
        // This is being called in overload mode to render the price set.
        return;
      }
    }
    // use to build form during form rule.
    $this->assign('buildPriceSet', $buildPriceSet);

    $defaults = $this->_values;
    $additionalDetailFields = [
      'note',
      'thankyou_date',
      'invoice_id',
      'non_deductible_amount',
      'fee_amount',
    ];
    foreach ($additionalDetailFields as $key) {
      if (!empty($defaults[$key])) {
        $defaults['hidden_AdditionalDetail'] = 1;
        break;
      }
    }

    if ($this->_productDAO) {
      if ($this->_productDAO->product_id) {
        $defaults['hidden_Premium'] = 1;
      }
    }

    if ($this->_noteID &&
      !CRM_Utils_System::isNull($this->_values['note'])
    ) {
      $defaults['hidden_AdditionalDetail'] = 1;
    }

    if (empty($this->_payNow)) {
      $allPanes = [ts('Additional Details') => $this->generatePane('AdditionalDetail', $defaults)];
      //Add Premium pane only if Premium is exists.
      $dao = new CRM_Contribute_DAO_Product();
      $dao->is_active = 1;

      if ($dao->find(TRUE)) {
        $allPanes[ts('Premium Information')] = $this->generatePane('Premium', $defaults);
      }
    }
    $this->assign('allPanes', $allPanes ?: []);

    $this->payment_instrument_id = $defaults['payment_instrument_id'] ?? $this->getDefaultPaymentInstrumentId();
    CRM_Core_Payment_Form::buildPaymentForm($this, $this->_paymentProcessor, FALSE, TRUE, $this->payment_instrument_id);
    if (!empty($this->_recurPaymentProcessors)) {
      $buildRecurBlock = TRUE;
      if ($this->_ppID) {
        // ppID denotes a pledge payment.
        foreach ($this->_paymentProcessors as $processor) {
          if (!empty($processor['is_recur']) && !empty($processor['object']) && $processor['object']->supports('recurContributionsForPledges')) {
            $buildRecurBlock = TRUE;
            break;
          }
          $buildRecurBlock = FALSE;
        }
      }
      if ($buildRecurBlock) {
        $this->buildRecur();
        $this->setDefaults(['is_recur' => 0]);
      }
    }
    $this->assign('buildRecurBlock', $buildRecurBlock);
    $this->addPaymentProcessorSelect(FALSE, $buildRecurBlock);

    $qfKey = $this->controller->_key;
    $this->assign('qfKey', $qfKey);

    $this->addFormRule(['CRM_Contribute_Form_Contribution', 'formRule'], $this);
    $this->assign('formType', $this->_formType);

    if ($this->_formType) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');

    //need to assign custom data subtype to the template for initial custom data load
    $this->assign('customDataSubType', $this->getFinancialTypeID());
    $this->assign('entityID', $this->getContributionID());
    $this->assign('email', $this->getContactValue('email_primary.email'));
    $contactField = $this->addEntityRef('contact_id', ts('Contributor'), ['create' => TRUE, 'api' => ['extra' => ['email']]], TRUE);
    if ($this->_context !== 'standalone') {
      $contactField->freeze();
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution');

    // Check permissions for financial type first
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes, $this->_action);
    if (empty($financialTypes)) {
      CRM_Core_Error::statusBounce(ts('You do not have all the permissions needed for this page.'));
    }
    $financialType = $this->add('select', 'financial_type_id',
      ts('Financial Type'),
      ['' => ts('- select -')] + $financialTypes,
      TRUE,
      ['onChange' => "CRM.buildCustomData( 'Contribution', this.value );", 'class' => 'crm-select2']
    );

    $paymentInstrument = FALSE;
    if (!$this->_mode) {
      // payment_instrument isn't required in edit and will not be present when payment block is enabled.
      $required = !$this->_id;
      $checkPaymentID = array_search('Check', CRM_Contribute_BAO_Contribution::buildOptions('payment_instrument_id', 'validate'));
      $paymentInstrument = $this->add('select', 'payment_instrument_id',
        ts('Payment Method'),
        ['' => ts('- select -')] + CRM_Contribute_BAO_Contribution::buildOptions('payment_instrument_id', 'create', ['filter' => 0]),
        $required,
        ['onChange' => "return showHideByValue('payment_instrument_id','{$checkPaymentID}','checkNumber','table-row','select',false);", 'class' => 'crm-select2']
      );
    }

    $trxnId = $this->add('text', 'trxn_id', ts('Transaction ID'), ['class' => 'twelve'] + $attributes['trxn_id']);

    //add receipt for offline contribution
    $this->addElement('checkbox', 'is_email_receipt', ts('Send Receipt?'));

    $this->add('select', 'from_email_address', ts('Receipt From'), $this->_fromEmails, FALSE, ['class' => 'crm-select2 huge']);

    $componentDetails = [];
    if ($this->_id) {
      $componentDetails = CRM_Contribute_BAO_Contribution::getComponentDetails($this->_id);
    }
    $status = $this->getAvailableContributionStatuses();

    // define the status IDs that show the cancellation info, see CRM-17589
    $cancelInfo_show_ids = [];
    foreach (array_keys($status) as $status_id) {
      if (CRM_Contribute_BAO_Contribution::isContributionStatusNegative($status_id)) {
        $cancelInfo_show_ids[] = "'$status_id'";
      }
    }
    $this->assign('cancelInfo_show_ids', implode(',', $cancelInfo_show_ids));

    $statusElement = $this->add('select', 'contribution_status_id',
      ts('Contribution Status'),
      $status,
      FALSE,
      ['class' => 'crm-select2']
    );

    $currencyFreeze = FALSE;
    if (!empty($this->_payNow) && ($this->_action & CRM_Core_Action::UPDATE)) {
      $statusElement->freeze();
      $currencyFreeze = TRUE;
      $attributes['total_amount']['readonly'] = TRUE;
    }

    // CRM-16189, add Revenue Recognition Date
    if (Civi::settings()->get('deferred_revenue_enabled')) {
      $revenueDate = $this->add('datepicker', 'revenue_recognition_date', ts('Revenue Recognition Date'), [], FALSE, ['time' => FALSE]);
      if ($this->_id && !CRM_Contribute_BAO_Contribution::allowUpdateRevenueRecognitionDate($this->_id)) {
        $revenueDate->freeze();
      }
    }

    // If contribution is a template receive date is not required and if we are in a live credit card mode
    $receiveDateRequired = !$this->_values['is_template'] && !$this->_mode;
    // add various dates
    $this->addField('receive_date', ['entity' => 'contribution'], $receiveDateRequired, FALSE);
    $this->addField('receipt_date', ['entity' => 'contribution'], FALSE, FALSE);
    $this->addField('cancel_date', ['entity' => 'contribution', 'label' => ts('Cancelled / Refunded Date')], FALSE, FALSE);

    if ($this->_online) {
      $this->assign('hideCalender', TRUE);
    }

    $this->add('textarea', 'cancel_reason', ts('Cancellation / Refund Reason'), $attributes['cancel_reason']);

    $totalAmount = NULL;
    if (empty($this->_lineItems)) {
      $buildPriceSet = FALSE;
      $priceSets = CRM_Price_BAO_PriceSet::getAssoc(FALSE, 'CiviContribute');
      if (!empty($priceSets) && !$this->_ppID) {
        $buildPriceSet = TRUE;
      }

      // don't allow price set for contribution if it is related to participant, or if it is a pledge payment
      // and if we already have line items for that participant. CRM-5095
      if ($buildPriceSet && $this->_id) {
        $pledgePaymentId = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_PledgePayment',
          $this->_id,
          'id',
          'contribution_id'
        );
        if ($pledgePaymentId) {
          $buildPriceSet = FALSE;
        }
        $participantID = $componentDetails['participant'] ?? NULL;
        if ($participantID) {
          $participantLI = CRM_Price_BAO_LineItem::getLineItems($participantID);
          if (!CRM_Utils_System::isNull($participantLI)) {
            $buildPriceSet = FALSE;
          }
        }
      }

      $hasPriceSets = FALSE;
      if ($buildPriceSet) {
        $hasPriceSets = TRUE;
        // CRM-16451: set financial type of 'Price Set' in back office contribution
        // instead of selecting manually
        $financialTypeIds = CRM_Price_BAO_PriceSet::getAssoc(FALSE, 'CiviContribute', 'financial_type_id');
        $element = $this->add('select', 'price_set_id', ts('Choose price set'),
          ['' => ts('Choose price set')] + $priceSets,
          NULL,
          ['onchange' => 'buildAmount( this.value, ' . json_encode($financialTypeIds) . ');', 'class' => 'crm-select2']
        );
        if ($this->_online && !($this->_action & CRM_Core_Action::UPDATE)) {
          $element->freeze();
        }
      }
      $this->assign('hasPriceSets', $hasPriceSets);
      if (!($this->_action & CRM_Core_Action::UPDATE)) {
        if ($this->_online || $this->_ppID) {
          $attributes['total_amount'] = array_merge($attributes['total_amount'], [
            'READONLY' => TRUE,
            'style' => "background-color:#EBECE4",
          ]);
          $optionTypes = [
            '1' => ts('Adjust Pledge Payment Schedule?'),
            '2' => ts('Adjust Total Pledge Amount?'),
          ];
          $this->addRadio('option_type',
            NULL,
            $optionTypes,
            [], '<br/>'
          );

          $currencyFreeze = TRUE;
        }
      }

      $totalAmount = $this->addMoney('total_amount',
        ts('Total Amount'),
        !$hasPriceSets,
        $attributes['total_amount'],
        TRUE, 'currency', NULL, $currencyFreeze
      );
    }

    $this->add('text', 'source', ts('Contribution Source'), $attributes['source'] ?? NULL);

    // CRM-7362 --add campaigns.
    CRM_Campaign_BAO_Campaign::addCampaign($this, $this->_values['campaign_id'] ?? NULL);

    if (empty($this->_payNow)) {
      CRM_Contribute_Form_SoftCredit::buildQuickForm($this);
    }

    $js = NULL;
    if (!$this->_mode) {
      $js = ['onclick' => 'return verify( );'];
    }

    $mailingInfo = Civi::settings()->get('mailing_backend');
    $this->assign('outBound_option', $mailingInfo['outBound_option']);

    $buttons = [
      [
        'type' => 'upload',
        'name' => ts('Save'),
        'js' => $js,
        'isDefault' => TRUE,
      ],
    ];
    if (!$this->_id) {
      $buttons[] = [
        'type' => 'upload',
        'name' => ts('Save and New'),
        'js' => $js,
        'subName' => 'new',
      ];
    }
    $buttons[] = [
      'type' => 'cancel',
      'name' => ts('Cancel'),
    ];
    $this->addButtons($buttons);

    // if contribution is related to membership or participant freeze Financial Type, Amount
    if ($this->_id) {
      $componentDetails = CRM_Contribute_BAO_Contribution::getComponentDetails($this->_id);
      $isCancelledStatus = ($this->_values['contribution_status_id'] == CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Cancelled'));

      if (!empty($componentDetails['membership']) ||
        !empty($componentDetails['participant']) ||
        // if status is Cancelled freeze Amount, Payment Instrument, Check #, Financial Type,
        // Net and Fee Amounts are frozen in AdditionalInfo::buildAdditionalDetail
        $isCancelledStatus
      ) {
        if ($totalAmount) {
          $totalAmount->freeze();
          $this->getElement('currency')->freeze();
        }
        if ($isCancelledStatus) {
          $paymentInstrument->freeze();
          $trxnId->freeze();
        }
        $financialType->freeze();
        $freezeFinancialType = TRUE;

      }
    }
    $this->assign('freezeFinancialType', $freezeFinancialType ?? FALSE);

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
    }
  }

  protected function isUpdate(): bool {
    return $this->getAction() === CRM_Core_Action::UPDATE && $this->getContributionID();
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function getOrder(): CRM_Financial_BAO_Order {
    if (!$this->order) {
      $this->initializeOrder();
    }
    return $this->order;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function initializeOrder(): void {
    $this->order = new CRM_Financial_BAO_Order();
    $this->order->setPriceSetID($this->getPriceSetID());
    $this->order->setForm($this);
    $this->order->setPriceSelectionFromUnfilteredInput($this->getSubmittedValues());
  }

  /**
   * Get the form context.
   *
   * This is important for passing to the buildAmount hook as CiviDiscount checks it.
   *
   * @return string
   */
  public function getFormContext(): string {
    return 'contribution';
  }

  /**
   * Build the price set form.
   */
  private function buildPriceSet(): void {
    $form = $this;
    $this->_priceSet = $this->getOrder()->getPriceSetMetadata();
    foreach ($this->getPriceFieldMetaData() as $id => $field) {
      $options = $field['options'] ?? NULL;
      if (!is_array($options)) {
        continue;
      }

      if (!empty($options)) {
        CRM_Price_BAO_PriceField::addQuickFormElement($form,
          'price_' . $field['id'],
          $field['id'],
          FALSE,
          $field['is_required'] ?? FALSE,
          NULL,
          $options
        );
      }
    }
    $form->assign('priceSet', $form->_priceSet);
  }

  /**
   * Get price field metadata.
   *
   * The returned value is an array of arrays where each array
   * is an id-keyed price field and an 'options' key has been added to that
   * array for any options.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @return array
   */
  public function getPriceFieldMetaData(): array {
    if (!empty($this->_priceSet['fields'])) {
      return $this->_priceSet['fields'];
    }

    $this->_priceSet['fields'] = $this->getOrder()->getPriceFieldsMetadata();
    return $this->_priceSet['fields'];
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
    // Check for Credit Card Contribution.
    if ($self->_mode) {
      if (empty($fields['payment_processor_id'])) {
        $errors['payment_processor_id'] = ts('Payment Processor is a required field.');
      }
      else {
        // validate payment instrument (e.g. credit card number)
        CRM_Core_Payment_Form::validatePaymentInstrument($fields['payment_processor_id'], $fields, $errors, NULL);
      }
    }

    // Do the amount validations.
    if (empty($fields['total_amount']) && empty($self->_lineItems)) {
      $priceSetId = $fields['price_set_id'] ?? NULL;
      if ($priceSetId) {
        CRM_Price_BAO_PriceField::priceSetValidation($priceSetId, $fields, $errors);
      }
    }

    $softErrors = CRM_Contribute_Form_SoftCredit::formRule($fields, $errors, $self);

    //CRM-16285 - Function to handle validation errors on form, for recurring contribution field.
    CRM_Contribute_BAO_ContributionRecur::validateRecurContribution($fields, $files, $self, $errors);

    // Form rule for status http://wiki.civicrm.org/confluence/display/CRM/CiviAccounts+4.3+Data+Flow
    if ($self->isUpdate()
      && $self->getContributionValue('contribution_status_id') != $fields['contribution_status_id']
      && !$self->getContributionValue('is_template')
    ) {
      try {
        CRM_Contribute_BAO_Contribution::checkStatusValidation([
          'contribution_status_id' => $self->getContributionValue('contribution_status_id'),
        ], $fields);
      }
      catch (CRM_Core_Exception $e) {
        $errors['contribution_status_id'] = $e->getMessage();
      }
    }
    // CRM-16015, add form-rule to restrict change of financial type if using price field of different financial type
    if ($self->isUpdate()
      && $self->getContributionValue('financial_type_id') != $fields['financial_type_id']
    ) {
      CRM_Contribute_BAO_Contribution::checkFinancialTypeChange(NULL, $self->getContributionID(), $errors);
    }
    //FIXME FOR NEW DATA FLOW http://wiki.civicrm.org/confluence/display/CRM/CiviAccounts+4.3+Data+Flow
    if (!empty($fields['fee_amount']) && !empty($fields['financial_type_id']) && $financialType = CRM_Contribute_BAO_Contribution::validateFinancialType($fields['financial_type_id'])) {
      $errors['financial_type_id'] = ts("Financial Account of account relationship of 'Expense Account is' is not configured for Financial Type : ") . $financialType;
    }

    // $trxn_id must be unique CRM-13919
    if (!empty($fields['trxn_id'])) {
      $queryParams = [1 => [$fields['trxn_id'], 'String']];
      $query = 'select count(*) from civicrm_contribution where trxn_id = %1';
      if ($self->_id) {
        $queryParams[2] = [(int) $self->_id, 'Integer'];
        $query .= ' and id !=%2';
      }
      $tCnt = CRM_Core_DAO::singleValueQuery($query, $queryParams);
      if ($tCnt) {
        $errors['trxn_id'] = ts('Transaction ID\'s must be unique. Transaction \'%1\' already exists in your database.', [1 => $fields['trxn_id']]);
      }
    }
    // CRM-16189
    $order = new CRM_Financial_BAO_Order();
    $order->setPriceSelectionFromUnfilteredInput($fields);
    if (isset($fields['total_amount'])) {
      $order->setOverrideTotalAmount((float) CRM_Utils_Rule::cleanMoney($fields['total_amount']));
    }
    $lineItems = $order->getLineItems();
    try {
      CRM_Financial_BAO_FinancialAccount::checkFinancialTypeHasDeferred($fields, $self->_id, $lineItems);
    }
    catch (CRM_Core_Exception $e) {
      $errors['financial_type_id'] = ' ';
      $errors['_qf_default'] = $e->getMessage();
    }
    $errors = array_merge($errors, $softErrors);
    return $errors;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Contribute_BAO_Contribution::deleteContribution($this->_id);
      CRM_Core_Session::singleton()->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$this->_contactID}&selectedChild=contribute"
      ));
      return;
    }
    // Get the submitted form values.
    $submittedValues = $this->getSubmittedValues();
    if ($this->_values['is_template']) {
      // If we are a template contribution we don't allow the contribution_status_id to be set
      //   on the form but we need it for the submit function.
      $submittedValues['is_template'] = $this->_values['is_template'];
      $submittedValues['contribution_status_id'] = $this->_values['contribution_status_id'];
    }

    try {
      $contribution = $this->submit($submittedValues, $this->_action, $this->_ppID);
    }
    catch (PaymentProcessorException $e) {
      // Set the contribution mode.
      $urlParams = "action=add&cid={$this->_contactID}";
      if ($this->_mode) {
        $urlParams .= "&mode={$this->_mode}";
      }
      if (!empty($this->_ppID)) {
        $urlParams .= "&context=pledge&ppid={$this->_ppID}";
      }

      CRM_Core_Error::statusBounce($e->getMessage(), $urlParams, ts('Payment Processor Error'));
    }
    $this->setUserContext();

    //store contribution ID if not yet set (on create)
    if (empty($this->_id) && !empty($contribution->id)) {
      $this->_id = $contribution->id;
    }
    $this->ajaxResponse['updateTabs']['#tab_activity'] = TRUE;
    if (!empty($this->_id) && CRM_Core_Permission::access('CiviMember')) {
      $membershipCount = CRM_Contact_BAO_Contact::getCountComponent('membership', $this->_contactID);
      // @fixme: Probably don't need a variable here but the old code counted MembershipPayment records and only returned a count if > 0
      if ($membershipCount) {
        $this->ajaxResponse['updateTabs']['#tab_member'] = $membershipCount;
      }
    }
    if (!empty($this->_id) && CRM_Core_Permission::access('CiviEvent')) {
      $participantPaymentCount = civicrm_api3('ParticipantPayment', 'getCount', ['contribution_id' => $this->_id]);
      if ($participantPaymentCount) {
        $this->ajaxResponse['updateTabs']['#tab_participant'] = CRM_Contact_BAO_Contact::getCountComponent('participant', $this->_contactID);
      }
    }
  }

  /**
   * Process credit card payment.
   *
   * @param array $submittedValues
   * @param array $lineItem
   *
   * @param int $contactID
   *   Contact ID
   *
   * @return bool|\CRM_Contribute_DAO_Contribution
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   * @throws \CRM_Core_Exception
   */
  protected function processCreditCard($submittedValues, $lineItem, $contactID) {
    $isTest = ($this->_mode === 'test') ? 1 : 0;
    // CRM-12680 set $_lineItem if its not set
    // @todo - I don't believe this would ever BE set. I can't find anywhere in the code.
    // It would be better to pass line item out to functions than $this->_lineItem as
    // we don't know what is being changed where.
    if (empty($this->_lineItem) && !empty($lineItem)) {
      $this->_lineItem = $lineItem;
    }

    $paymentObject = Civi\Payment\System::singleton()->getById($submittedValues['payment_processor_id']);
    $this->_paymentProcessor = $paymentObject->getPaymentProcessor();

    // Set source if not set
    if (empty($submittedValues['source'])) {
      $userID = CRM_Core_Session::singleton()->get('userID');
      $userSortName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $userID,
        'sort_name'
      );
      $userSortName = htmlentities($userSortName);
      $submittedValues['source'] = ts('Submit Credit Card Payment by: %1', [1 => $userSortName]);
    }

    $params = $submittedValues;
    $this->_params = array_merge($this->_params, $submittedValues);

    // Mapping requiring documentation.
    $this->_params['payment_processor'] = $submittedValues['payment_processor_id'];

    $now = date('YmdHis');

    $this->processBillingAddress($contactID, (string) $this->getContactValue('email_primary.email'));
    if (!empty($params['source'])) {
      unset($params['source']);
    }

    $this->_params['amount'] = $this->_params['total_amount'];
    // @todo - stop setting amount level in this function - use $this->order->getAmountLevel()
    $this->_params['amount_level'] = 0;
    $this->_params['description'] = ts("Contribution submitted by a staff person using contributor's credit card");
    $this->_params['currencyID'] = $this->_params['currency'] ?? CRM_Core_Config::singleton()->defaultCurrency;

    $this->_params['pcp_display_in_roll'] = $params['pcp_display_in_roll'] ?? NULL;
    $this->_params['pcp_roll_nickname'] = $params['pcp_roll_nickname'] ?? NULL;
    $this->_params['pcp_personal_note'] = $params['pcp_personal_note'] ?? NULL;

    //Add common data to formatted params
    CRM_Contribute_Form_AdditionalInfo::postProcessCommon($params, $this->_params, $this);

    if (empty($this->_params['invoice_id'])) {
      $this->_params['invoiceID'] = bin2hex(random_bytes(16));
    }
    else {
      $this->_params['invoiceID'] = $this->_params['invoice_id'];
    }

    // At this point we've created a contact and stored its address etc
    // all the payment processors expect the name and address to be in the
    // so we copy stuff over to first_name etc.
    $paymentParams = $this->_params;
    $paymentParams['contactID'] = $contactID;
    CRM_Core_Payment_Form::mapParams(NULL, $this->_params, $paymentParams, TRUE);

    $financialType = new CRM_Financial_DAO_FinancialType();
    $financialType->id = $params['financial_type_id'];
    $financialType->find(TRUE);

    // Add some financial type details to the params list
    // if folks need to use it.
    $paymentParams['contributionType_name'] = $this->_params['contributionType_name'] = $financialType->name;
    $paymentParams['contributionPageID'] = NULL;

    if (!empty($this->_params['is_email_receipt'])) {
      $paymentParams['email'] = $this->getContactValue('email_primary.email');
      $paymentParams['is_email_receipt'] = 1;
    }
    else {
      $paymentParams['is_email_receipt'] = 0;
      $this->_params['is_email_receipt'] = 0;
    }
    if (!empty($this->_params['receive_date'])) {
      $paymentParams['receive_date'] = $this->_params['receive_date'];
    }

    if (!empty($this->_params['is_email_receipt'])) {
      $this->_params['receipt_date'] = $now;
    }

    $this->set('params', $this->_params);
    // It actually makes no sense that we would set receive_date in params
    // for credit card payments....
    $this->assign('receive_date', $this->_params['receive_date'] ?? date('Y-m-d H:i:s'));

    // Result has all the stuff we need
    // lets archive it to a financial transaction
    if ($financialType->is_deductible) {
      $this->assign('is_deductible', TRUE);
      $this->set('is_deductible', TRUE);
    }
    $contributionParams = [
      'id' => $this->_params['contribution_id'] ?? NULL,
      'contact_id' => $contactID,
      'line_item' => $lineItem,
      'is_test' => $isTest,
      'campaign_id' => $this->_params['campaign_id'] ?? NULL,
      'contribution_page_id' => $this->_params['contribution_page_id'] ?? NULL,
      'source' => $paymentParams['source'] ?? $paymentParams['description'] ?? NULL,
      'thankyou_date' => $this->_params['thankyou_date'] ?? NULL,
    ];
    $contributionParams['payment_instrument_id'] = $this->_paymentProcessor['payment_instrument_id'];

    $contribution = $this->processFormContribution(
      $this->_params,
      $contributionParams,
      $financialType,
      $this->_params['is_recur'] ?? NULL
    );

    $paymentParams['contributionID'] = $contribution->id;
    $paymentParams['contributionPageID'] = $contribution->contribution_page_id;
    $paymentParams['contributionRecurID'] = $contribution->contribution_recur_id;

    if ($paymentParams['amount'] > 0.0) {
      // force a re-get of the payment processor in case the form changed it, CRM-7179
      // NOTE - I expect this is obsolete.
      $payment = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
      $payment->setBackOffice(TRUE);
      try {
        $completeStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
        $result = $payment->doPayment($paymentParams, 'contribute');
        $this->assign('trxn_id', $result['trxn_id']);
        $contribution->trxn_id = $result['trxn_id'];
        /* Our scenarios here are
         *  1) the payment failed & an Exception should have been thrown
         *  2) the payment succeeded but the payment is not immediate (for example a recurring payment
         *     with a delayed start)
         *  3) the payment succeeded with an immediate payment.
         *
         * The doPayment function ensures that payment_status_id is always set
         * as historically we have had to guess from the context - ie doDirectPayment
         * = error or success, unless it is a recurring contribution in which case it is pending.
         */
        if ($result['payment_status_id'] == $completeStatusId) {
          try {
            civicrm_api3('contribution', 'completetransaction', [
              'id' => $contribution->id,
              'trxn_id' => $result['trxn_id'],
              'payment_processor_id' => $this->_paymentProcessor['id'],
              'is_transactional' => FALSE,
              'fee_amount' => $result['fee_amount'] ?? NULL,
              'card_type_id' => $paymentParams['card_type_id'] ?? NULL,
              'pan_truncation' => $paymentParams['pan_truncation'] ?? NULL,
              'is_email_receipt' => FALSE,
            ]);
            // This has now been set to 1 in the DB - declare it here also
            $contribution->contribution_status_id = 1;
          }
          catch (CRM_Core_Exception $e) {
            if ($e->getErrorCode() !== 'contribution_completed') {
              \Civi::log()->error('CRM_Contribute_Form_Contribution::processCreditCard CRM_Core_Exception: ' . $e->getMessage());
              throw new CRM_Core_Exception('Failed to update contribution in database');
            }
          }
        }
        else {
          // Save the trxn_id.
          $contribution->save();
        }
      }
      catch (PaymentProcessorException $e) {
        CRM_Contribute_BAO_Contribution::failPayment($contribution->id, $paymentParams['contactID'], $e->getMessage());
        throw new PaymentProcessorException($e->getMessage());
      }
    }
    // Send receipt mail.
    array_unshift($this->statusMessage, ts('The contribution record has been saved.'));
    if ($contribution->id && !empty($this->_params['is_email_receipt'])) {
      $this->_params['trxn_id'] = $result['trxn_id'] ?? NULL;
      $this->_params['contact_id'] = $contactID;
      $this->_params['contribution_id'] = $contribution->id;
      if (CRM_Contribute_Form_AdditionalInfo::emailReceipt($this, $this->_params, TRUE)) {
        $this->statusMessage[] = ts('A receipt has been emailed to the contributor.');
      }
    }

    return $contribution;
  }

  /**
   * Process the contribution.
   *
   * @todo - this form is a copy of the previously shared code on the front
   * end form - not all aspects of the code will be relevant to this form.
   *
   * @param array $params
   * @param array $contributionParams
   *   Parameters to be passed to contribution create action.
   *   This differs from params in that we are currently adding params to it and 1) ensuring they are being
   *   passed consistently & 2) documenting them here.
   *   - contact_id
   *   - line_item
   *   - is_test
   *   - campaign_id
   *   - contribution_page_id
   *   - source
   *   - payment_type_id
   *   - thankyou_date (not all forms will set this)
   *
   * @param CRM_Financial_DAO_FinancialType $financialType
   *   ID of billing location type.
   * @param bool $isRecur
   *   Is this recurring?
   *
   * @return \CRM_Contribute_DAO_Contribution
   *
   * @throws \CRM_Core_Exception
   */
  private function processFormContribution(
    $params,
    $contributionParams,
    $financialType,
    $isRecur
  ) {
    $form = $this;
    $transaction = new CRM_Core_Transaction();
    $contactID = $contributionParams['contact_id'];

    $isEmailReceipt = !empty($form->_values['is_email_receipt']);
    $pledgeID = !empty($params['pledge_id']) ? $params['pledge_id'] : $form->_values['pledge_id'] ?? NULL;
    if ((!empty($params['is_pledge']) || $pledgeID)) {
      $isPledge = TRUE;
    }
    else {
      $isPledge = FALSE;
    }

    // add these values for the recurringContrib function ,CRM-10188
    $params['financial_type_id'] = $financialType->id;

    $contributionParams['address_id'] = CRM_Contribute_BAO_Contribution::createAddress($params);

    //@todo - this is being set from the form to resolve CRM-10188 - an
    // eNotice caused by it not being set @ the front end
    // however, we then get it being over-written with null for backend contributions
    // a better fix would be to set the values in the respective forms rather than require
    // a function being shared by two forms to deal with their respective values
    // moving it to the BAO & not taking the $form as a param would make sense here.
    if (!isset($params['is_email_receipt']) && $isEmailReceipt) {
      $params['is_email_receipt'] = $isEmailReceipt;
    }
    // We may no longer need to set params['is_recur'] - it used to be used in processRecurringContribution
    $params['is_recur'] = $isRecur;
    $params['payment_instrument_id'] = $contributionParams['payment_instrument_id'] ?? NULL;
    $recurringContributionID = !$isRecur ? NULL : $this->processRecurringContribution($form, $params, [
      'contact_id' => $contactID,
      'financial_type_id' => $financialType->id,
    ]);

    $now = date('YmdHis');
    $receiptDate = $params['receipt_date'] ?? NULL;
    if ($isEmailReceipt) {
      $receiptDate = $now;
    }

    if (isset($params['amount'])) {
      $contributionParams = array_merge($this->getContributionParams(
        $params, $financialType->id, $receiptDate,
        $recurringContributionID), $contributionParams
      );
      $contributionParams['non_deductible_amount'] = $this->getNonDeductibleAmount($params, $financialType, FALSE, $form);
      $contributionParams['skipCleanMoney'] = TRUE;
      // @todo this is the wrong place for this - it should be done as close to form submission
      // as possible
      $contributionParams['total_amount'] = $params['amount'];

      $contribution = CRM_Contribute_BAO_Contribution::add($contributionParams);

      if (Civi::settings()->get('invoicing')) {
        $smarty = CRM_Core_Smarty::singleton();
        // @todo - probably this assign is no longer needed as we use a token.
        $smarty->assign('totalTaxAmount', $params['tax_amount'] ?? NULL);
      }
    }

    // process soft credit / pcp params first
    CRM_Contribute_BAO_ContributionSoft::formatSoftCreditParams($params, $form);

    //CRM-13981, processing honor contact into soft-credit contribution
    CRM_Contribute_BAO_ContributionSoft::processSoftContribution($params, $contribution);

    if ($isPledge) {
      $this->processPledge($params, $contributionParams, $pledgeID, $contribution, $isEmailReceipt);
    }

    if ($contribution) {
      //handle custom data.
      $params['contribution_id'] = $contribution->id;
      if (!empty($params['custom']) && is_array($params['custom'])) {
        CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_contribution', $contribution->id);
      }
    }
    // Save note
    if ($contribution && !empty($params['contribution_note'])) {
      $noteParams = [
        'entity_table' => 'civicrm_contribution',
        'note' => $params['contribution_note'],
        'entity_id' => $contribution->id,
        'contact_id' => $contribution->contact_id,
      ];

      CRM_Core_BAO_Note::add($noteParams, []);
    }

    $transaction->commit();
    return $contribution;
  }

  /**
   * Previously shared code. Probably handles an online-only workflow & that code can go.
   *
   * @param $params
   * @param $contributionParams
   * @param $pledgeID
   * @param $contribution
   * @param $isEmailReceipt
   */
  private function processPledge($params, $contributionParams, $pledgeID, $contribution, $isEmailReceipt): void {
    $form = $this;
    if ($pledgeID) {
      //when user doing pledge payments.
      //update the schedule when payment(s) are made
      $amount = $params['amount'];
      $pledgePaymentParams = [];
      foreach ($params['pledge_amount'] as $paymentId => $dontCare) {
        $scheduledAmount = CRM_Core_DAO::getFieldValue(
          'CRM_Pledge_DAO_PledgePayment',
          $paymentId,
          'scheduled_amount',
          'id'
        );

        $pledgePayment = ($amount >= $scheduledAmount) ? $scheduledAmount : $amount;
        if ($pledgePayment > 0) {
          $pledgePaymentParams[] = [
            'id' => $paymentId,
            'contribution_id' => $contribution->id,
            'status_id' => $contribution->contribution_status_id,
            'actual_amount' => $pledgePayment,
          ];
          $amount -= $pledgePayment;
        }
      }
      if ($amount > 0 && count($pledgePaymentParams)) {
        $pledgePaymentParams[count($pledgePaymentParams) - 1]['actual_amount'] += $amount;
      }
      foreach ($pledgePaymentParams as $p) {
        CRM_Pledge_BAO_PledgePayment::add($p);
      }

      //update pledge status according to the new payment statuses
      CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($pledgeID);
      return;
    }
    else {
      //when user creating pledge record.
      CRM_Core_Error::deprecatedWarning('code slated for removal, believed to be only reachable in the online flow');
      $pledgeParams = [];
      $pledgeParams['contact_id'] = $contribution->contact_id;
      $pledgeParams['installment_amount'] = $pledgeParams['actual_amount'] = $contribution->total_amount;
      $pledgeParams['contribution_id'] = $contribution->id;
      $pledgeParams['contribution_page_id'] = $contribution->contribution_page_id;
      $pledgeParams['financial_type_id'] = $contribution->financial_type_id;
      $pledgeParams['frequency_interval'] = $params['pledge_frequency_interval'];
      $pledgeParams['installments'] = $params['pledge_installments'];
      $pledgeParams['frequency_unit'] = $params['pledge_frequency_unit'];
      if ($pledgeParams['frequency_unit'] === 'month') {
        $pledgeParams['frequency_day'] = intval(date("d"));
      }
      else {
        $pledgeParams['frequency_day'] = 1;
      }
      $pledgeParams['create_date'] = $pledgeParams['start_date'] = $pledgeParams['scheduled_date'] = date("Ymd");
      if (!empty($params['start_date'])) {
        $pledgeParams['frequency_day'] = intval(date("d", strtotime($params['start_date'])));
        $pledgeParams['start_date'] = $pledgeParams['scheduled_date'] = date('Ymd', strtotime($params['start_date']));
      }
      $pledgeParams['status_id'] = $contribution->contribution_status_id;
      $pledgeParams['max_reminders'] = $form->_values['max_reminders'];
      $pledgeParams['initial_reminder_day'] = $form->_values['initial_reminder_day'];
      $pledgeParams['additional_reminder_day'] = $form->_values['additional_reminder_day'];
      $pledgeParams['is_test'] = $contribution->is_test;
      $pledgeParams['acknowledge_date'] = date('Ymd');
      $pledgeParams['original_installment_amount'] = $pledgeParams['installment_amount'];

      //inherit campaign from contirb page.
      $pledgeParams['campaign_id'] = $contributionParams['campaign_id'] ?? NULL;

      $pledge = CRM_Pledge_BAO_Pledge::create($pledgeParams);

      $form->_params['pledge_id'] = $pledge->id;

      //send acknowledgment email. only when pledge is created
      if ($pledge->id && $isEmailReceipt) {
        //build params to send acknowledgment.
        $pledgeParams['id'] = $pledge->id;
        $pledgeParams['receipt_from_name'] = $form->_values['receipt_from_name'];
        $pledgeParams['receipt_from_email'] = $form->_values['receipt_from_email'];

        //scheduled amount will be same as installment_amount.
        $pledgeParams['scheduled_amount'] = $pledgeParams['installment_amount'];

        //get total pledge amount.
        $pledgeParams['total_pledge_amount'] = $pledge->amount;

        CRM_Pledge_BAO_Pledge::sendAcknowledgment($form, $pledgeParams);
      }
    }
  }

  /**
   * Get non-deductible amount.
   *
   * Previously shared function - was quite broken in the other flow.
   * Maybe here too
   *
   * This is a bit too much about wierd form interpretation to be this deep.
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-11885
   *  if non_deductible_amount exists i.e. Additional Details fieldset was opened [and staff typed something] -> keep
   * it.
   *
   * @param array $params
   * @param CRM_Financial_BAO_FinancialType $financialType
   * @param bool $online
   * @param CRM_Contribute_Form_Contribution_Confirm $form
   *
   * @return array
   */
  private function getNonDeductibleAmount($params, $financialType, $online, $form) {
    if (isset($params['non_deductible_amount']) && (!empty($params['non_deductible_amount']))) {
      return $params['non_deductible_amount'];
    }
    $priceSetId = $params['priceSetId'] ?? NULL;
    // return non-deductible amount if it is set at the price field option level
    if ($priceSetId && !empty($form->_lineItem)) {
      $nonDeductibleAmount = CRM_Price_BAO_PriceSet::getNonDeductibleAmountFromPriceSet($priceSetId, $form->_lineItem);
    }

    if (!empty($nonDeductibleAmount)) {
      return $nonDeductibleAmount;
    }
    else {
      if ($financialType->is_deductible) {
        if ($online && isset($params['selectProduct'])) {
          $selectProduct = $params['selectProduct'] ?? NULL;
        }
        if (!$online && isset($params['product_name'][0])) {
          $selectProduct = $params['product_name'][0];
        }
        // if there is a product - compare the value to the contribution amount
        if (isset($selectProduct) &&
          $selectProduct !== 'no_thanks'
        ) {
          $productDAO = new CRM_Contribute_DAO_Product();
          $productDAO->id = $selectProduct;
          $productDAO->find(TRUE);
          // product value exceeds contribution amount
          if ($params['amount'] < $productDAO->price) {
            $nonDeductibleAmount = $params['amount'];
            return $nonDeductibleAmount;
          }
          // product value does NOT exceed contribution amount
          else {
            return $productDAO->price;
          }
        }
        // contribution is deductible - but there is no product
        else {
          return '0.00';
        }
      }
      // contribution is NOT deductible
      else {
        return $params['amount'];
      }
    }
  }

  /**
   * Create the recurring contribution record.
   *
   * @param self $form
   * @param array $params
   * @param array $recurParams
   *
   * @return int|null
   */
  private function processRecurringContribution($form, $params, $recurParams) {
    // @todo - previously shared code - many items may be irrelevant.
    $recurParams['amount'] = $params['amount'] ?? NULL;
    $recurParams['auto_renew'] = $params['auto_renew'] ?? NULL;
    $recurParams['frequency_unit'] = $params['frequency_unit'] ?? NULL;
    $recurParams['frequency_interval'] = $params['frequency_interval'] ?? NULL;
    $recurParams['installments'] = $params['installments'] ?? NULL;
    $recurParams['currency'] = $params['currency'] ?? NULL;
    $recurParams['payment_instrument_id'] = $params['payment_instrument_id'];

    $recurParams['is_test'] = 0;
    if (($form->_action & CRM_Core_Action::PREVIEW) ||
      (isset($form->_mode) && ($form->_mode == 'test'))
    ) {
      $recurParams['is_test'] = 1;
    }

    $recurParams['start_date'] = $recurParams['create_date'] = $recurParams['modified_date'] = date('YmdHis');
    if (!empty($params['receive_date'])) {
      $recurParams['start_date'] = date('YmdHis', strtotime($params['receive_date']));
    }
    $recurParams['invoice_id'] = $params['invoiceID'] ?? NULL;
    $recurParams['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionRecur', 'contribution_status_id', 'Pending');
    $recurParams['payment_processor_id'] = $params['payment_processor_id'] ?? NULL;
    $recurParams['is_email_receipt'] = (bool) ($params['is_email_receipt'] ?? FALSE);
    // We set trxn_id=invoiceID specifically for paypal IPN. It is reset this when paypal sends us the real trxn id, CRM-2991
    $recurParams['processor_id'] = $recurParams['trxn_id'] = ($params['trxn_id'] ?? $params['invoiceID']);

    $campaignId = $params['campaign_id'] ?? $form->_values['campaign_id'] ?? NULL;
    $recurParams['campaign_id'] = $campaignId;
    $recurring = CRM_Contribute_BAO_ContributionRecur::add($recurParams);
    $form->_params['contributionRecurID'] = $recurring->id;

    return $recurring->id;
  }

  /**
   * Set the parameters to be passed to contribution create function.
   *
   * Previously shared function.
   *
   * @param array $params
   * @param int $financialTypeID
   * @param string $receiptDate
   * @param int $recurringContributionID
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function getContributionParams(
    $params, $financialTypeID, $receiptDate, $recurringContributionID) {
    $contributionParams = [
      'financial_type_id' => $financialTypeID,
      'receive_date' => !empty($params['receive_date']) ? CRM_Utils_Date::processDate($params['receive_date']) : date('YmdHis'),
      'tax_amount' => $params['tax_amount'] ?? NULL,
      'amount_level' => $params['amount_level'] ?? NULL,
      'invoice_id' => $params['invoiceID'],
      'currency' => $params['currencyID'],
      'is_pay_later' => $params['is_pay_later'] ?? 0,
      //configure cancel reason, cancel date and thankyou date
      //from 'contribution' type profile if included
      'cancel_reason' => $params['cancel_reason'] ?? 0,
      'cancel_date' => isset($params['cancel_date']) ? CRM_Utils_Date::format($params['cancel_date']) : NULL,
      'thankyou_date' => isset($params['thankyou_date']) ? CRM_Utils_Date::format($params['thankyou_date']) : NULL,
      //setting to make available to hook - although seems wrong to set on form for BAO hook availability
      'skipLineItem' => $params['skipLineItem'] ?? 0,
    ];

    if (!empty($params["is_email_receipt"])) {
      $contributionParams += [
        'receipt_date' => $receiptDate,
      ];
    }

    if ($recurringContributionID) {
      $contributionParams['contribution_recur_id'] = $recurringContributionID;
    }

    $contributionParams['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending');
    if (isset($contributionParams['invoice_id'])) {
      $contributionParams['id'] = CRM_Core_DAO::getFieldValue(
        'CRM_Contribute_DAO_Contribution',
        $contributionParams['invoice_id'],
        'id',
        'invoice_id'
      );
    }

    return $contributionParams;
  }

  /**
   * Generate the data to construct a snippet based pane.
   *
   * This form also assigns the showAdditionalInfo var based on historical code.
   * This appears to mean 'there is a pane to show'.
   *
   * @param string $type
   *   Type of Pane - only options are AdditionalDetail or Premium
   * @param array $defaults
   *
   * @return array
   *   We aim to further refactor & simplify this but currently
   *   - the panes array
   *   - should additional info be shown?
   */
  protected function generatePane($type, $defaults) {
    $urlParams = "snippet=4&formType={$type}";
    if ($this->_mode) {
      $urlParams .= "&mode={$this->_mode}";
    }

    $pane = [
      'url' => CRM_Utils_System::url('civicrm/contact/view/contribution', $urlParams),
      'open' => 'false',
      'id' => $type,
    ];

    // See if we need to include this paneName in the current form.
    if ($this->_formType == $type || !empty($_POST["hidden_{$type}"]) ||
      !empty($defaults["hidden_{$type}"])
    ) {
      $this->assign('showAdditionalInfo', TRUE);
      $pane['open'] = 'true';
    }
    if ($type === 'AdditionalDetail') {
      $this->buildAdditionalDetail();
    }
    if ($type === 'Premium') {
      $this->buildPremium();
    }
    return $pane;
  }

  /**
   * Build the form object for Premium Information.
   */
  private function buildPremium(): void {
    $form = $this;
    //premium section
    $form->add('hidden', 'hidden_Premium', 1);
    $sel1 = $sel2 = [];

    $dao = new CRM_Contribute_DAO_Product();
    $dao->is_active = 1;
    $dao->find();
    $min_amount = [];
    $sel1[0] = ts('-select product-');
    while ($dao->fetch()) {
      $sel1[$dao->id] = $dao->name . " ( " . $dao->sku . " )";
      $min_amount[$dao->id] = $dao->min_contribution;
      $options = CRM_Contribute_BAO_Premium::parseProductOptions($dao->options);
      if (!empty($options)) {
        $options = ['' => ts('- select -')] + $options;
        $sel2[$dao->id] = $options;
      }
      $form->assign('premiums', TRUE);
    }
    $form->_options = $sel2;
    $form->assign('mincontribution', $min_amount);
    $sel = &$form->addElement('hierselect', "product_name", ts('Premium'), 'onclick="showMinContrib();"');
    $js = "<script type='text/javascript'>\n";
    $formName = 'document.forms.' . $form->getName();

    for ($k = 1; $k < 2; $k++) {
      if (!isset($defaults['product_name'][$k]) || (!$defaults['product_name'][$k])) {
        $js .= "{$formName}['product_name[$k]'].style.display = 'none';\n";
      }
    }

    $sel->setOptions([$sel1, $sel2]);
    $js .= "</script>\n";
    $form->assign('initHideBoxes', $js);

    $form->add('datepicker', 'fulfilled_date', ts('Fulfilled'), [], FALSE, ['time' => FALSE]);
    $form->addElement('text', 'min_amount', ts('Minimum Contribution Amount'));
  }

  /**
   * Build the form object for Additional Details.
   */
  private function buildAdditionalDetail(): void {
    $form = $this;
    //Additional information section
    $form->add('hidden', 'hidden_AdditionalDetail', 1);

    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution');

    $form->addField('thankyou_date', ['entity' => 'contribution'], FALSE, FALSE);

    // add various amounts
    $nonDeductAmount = &$form->add('text', 'non_deductible_amount', ts('Non-deductible Amount'),
      $attributes['non_deductible_amount']
    );
    $form->addRule('non_deductible_amount', ts('Please enter a valid monetary value for Non-deductible Amount.'), 'money');

    if ($form->_online) {
      $nonDeductAmount->freeze();
    }
    $feeAmount = &$form->add('text', 'fee_amount', ts('Fee Amount'),
      $attributes['fee_amount']
    );
    $form->addRule('fee_amount', ts('Please enter a valid monetary value for Fee Amount.'), 'money');
    if ($form->_online) {
      $feeAmount->freeze();
    }

    $element = &$form->add('text', 'invoice_id', ts('Invoice ID'),
      $attributes['invoice_id']
    );
    if ($form->_online) {
      $element->freeze();
    }
    else {
      $form->addRule('invoice_id',
        ts('This Invoice ID already exists in the database.'),
        'objectExists',
        ['CRM_Contribute_DAO_Contribution', $form->_id, 'invoice_id']
      );
    }
    $element = $form->add('text', 'creditnote_id', ts('Credit Note ID'),
      $attributes['creditnote_id']
    );
    if ($form->_online) {
      $element->freeze();
    }
    else {
      $form->addRule('creditnote_id',
        ts('This Credit Note ID already exists in the database.'),
        'objectExists',
        ['CRM_Contribute_DAO_Contribution', $form->_id, 'creditnote_id']
      );
    }

    $form->add('select', 'contribution_page_id',
      ts('Contribution Page'),
      ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::contributionPage(),
      FALSE,
      ['class' => 'crm-select2']
    );

    $form->add('textarea', 'note', ts('Notes'), ["rows" => 4, "cols" => 60]);

    $statusName = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    if ($form->_id && $form->_values['contribution_status_id'] == array_search('Cancelled', $statusName)) {
      $feeAmount->freeze();
    }

  }

  /**
   * @param array $submittedValues
   *
   * @param int $action
   *   Action constant
   *    - CRM_Core_Action::UPDATE
   *
   * @param $pledgePaymentID
   *
   * @return \CRM_Contribute_BAO_Contribution
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  protected function submit($submittedValues, $action, $pledgePaymentID) {
    $pId = $contribution = $isRelatedId = FALSE;
    $this->_params = $submittedValues;
    $this->beginPostProcess();
    // reassign submitted form values if the any information is formatted via beginPostProcess
    $submittedValues = $this->_params;

    if ($this->getPriceSetID() && $action & CRM_Core_Action::UPDATE) {
      $line = CRM_Price_BAO_LineItem::getLineItems($this->_id, 'contribution');
      $lineID = key($line);
      $priceSetId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $line[$lineID]['price_field_id'] ?? NULL, 'price_set_id');
      $quickConfig = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $priceSetId, 'is_quick_config');
      // Why do we do this? Seems like a like a wrapper for old functionality - but single line price sets & quick
      // config should be treated the same.
      if ($quickConfig) {
        CRM_Price_BAO_LineItem::deleteLineItems($this->_id, 'civicrm_contribution');
      }
    }

    // Process price set and get total amount and line items.
    $lineItem = [];
    $priceSetId = $submittedValues['price_set_id'] ?? NULL;
    if (!$this->getPriceSetID() && !$this->_id) {
      $this->_priceSetId = $priceSetId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', 'default_contribution_amount', 'id', 'name');
      $this->_priceSet = current(CRM_Price_BAO_PriceSet::getSetDetail($priceSetId));
      $fieldID = key($this->_priceSet['fields']);
      $fieldValueId = key($this->_priceSet['fields'][$fieldID]['options']);
      $this->_priceSet['fields'][$fieldID]['options'][$fieldValueId]['amount'] = $submittedValues['total_amount'];
      $submittedValues['price_' . $fieldID] = 1;
    }

    // Every contribution has a price-set - the only reason it shouldn't be set is if we are dealing with
    // quick config (very very arguably) & yet we see that this could still be quick config so this should be understood
    // as a point of fragility rather than a logical 'if' clause.
    if ($priceSetId) {
      CRM_Price_BAO_PriceSet::processAmount($this->_priceSet['fields'],
        $submittedValues, $lineItem[$priceSetId], $priceSetId);
      // Unset tax amount for offline 'is_quick_config' contribution.
      // @todo WHY  - quick config was conceived as a quick way to configure contribution forms.
      // this is an example of 'other' functionality being hung off it.
      if ($this->_priceSet['is_quick_config'] &&
        !array_key_exists($submittedValues['financial_type_id'], CRM_Core_PseudoConstant::getTaxRates())
      ) {
        unset($submittedValues['tax_amount']);
      }
      $submittedValues['total_amount'] = $submittedValues['amount'] ?? NULL;
    }

    if ($this->_id) {
      if ($this->_compId) {
        if ($this->_context === 'participant') {
          $pId = $this->_compId;
        }
        elseif ($this->_context === 'membership') {
          $isRelatedId = TRUE;
        }
        else {
          $pId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $this->_id, 'participant_id', 'contribution_id');
        }
      }
      else {
        $contributionDetails = CRM_Contribute_BAO_Contribution::getComponentDetails($this->_id);
        if (array_key_exists('membership', $contributionDetails)) {
          $isRelatedId = TRUE;
        }
        elseif (array_key_exists('participant', $contributionDetails)) {
          $pId = $contributionDetails['participant'];
        }
      }
      if (!empty($this->_payNow)) {
        $this->_params['contribution_id'] = $this->_id;
      }
    }

    if (!$priceSetId && !empty($submittedValues['total_amount']) && $this->_id) {
      // CRM-10117 update the line items for participants.
      // @todo - if we are completing a contribution then the api call
      // civicrm_api3('Contribution', 'completetransaction') should take care of
      // all associated updates rather than replicating them on the form layer.
      if ($pId) {
        $entityTable = 'participant';
        $entityID = $pId;
        $isRelatedId = FALSE;
        $participantParams = [
          'fee_amount' => $submittedValues['total_amount'],
          'id' => $entityID,
        ];
        CRM_Event_BAO_Participant::add($participantParams);
        if (empty($this->_lineItems)) {
          $this->_lineItems[] = CRM_Price_BAO_LineItem::getLineItems($entityID, 'participant', TRUE);
        }
      }
      else {
        $entityTable = 'contribution';
        $entityID = $this->_id;
      }

      $lineItems = CRM_Price_BAO_LineItem::getLineItems($entityID, $entityTable, FALSE, TRUE, $isRelatedId);
      foreach (array_keys($lineItems) as $id) {
        $lineItems[$id]['id'] = $id;
      }
      $itemId = key($lineItems);
      if ($itemId && !empty($lineItems[$itemId]['price_field_id'])) {
        $this->_priceSetId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $lineItems[$itemId]['price_field_id'], 'price_set_id');
      }

      // @todo see above - new functionality has been inappropriately added to the quick config concept
      // and new functionality has been added onto the form layer rather than the BAO :-(
      if ($this->_priceSetId && CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_priceSetId, 'is_quick_config')) {
        //CRM-16833: Ensure tax is applied only once for membership conribution, when status changed.(e.g Pending to Completed).
        $componentDetails = CRM_Contribute_BAO_Contribution::getComponentDetails($this->_id);
        if (empty($componentDetails['membership']) && empty($componentDetails['participant'])) {
          if (!($this->_action & CRM_Core_Action::UPDATE && (($this->_defaults['contribution_status_id'] != $submittedValues['contribution_status_id'])))) {
            $lineItems[$itemId]['unit_price'] = $lineItems[$itemId]['line_total'] = $this->getSubmittedValue('total_amount');
          }
        }

        // Update line total and total amount with tax on edit.
        $financialItemsId = CRM_Core_PseudoConstant::getTaxRates();
        if (array_key_exists($submittedValues['financial_type_id'], $financialItemsId)) {
          $lineItems[$itemId]['tax_rate'] = $financialItemsId[$submittedValues['financial_type_id']];
        }
        else {
          $lineItems[$itemId]['tax_rate'] = $lineItems[$itemId]['tax_amount'] = '';
          $submittedValues['tax_amount'] = 0;
        }
        if ($lineItems[$itemId]['tax_rate']) {
          $lineItems[$itemId]['tax_amount'] = ($lineItems[$itemId]['tax_rate'] / 100) * $lineItems[$itemId]['line_total'];
          $submittedValues['total_amount'] = $lineItems[$itemId]['line_total'] + $lineItems[$itemId]['tax_amount'];
          $submittedValues['tax_amount'] = $lineItems[$itemId]['tax_amount'];
        }
      }
      // CRM-10117 update the line items for participants.
      if (!empty($lineItems[$itemId]['price_field_id'])) {
        $lineItem[$this->_priceSetId] = $lineItems;
      }
    }

    $isQuickConfig = 0;
    if ($this->_priceSetId && CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_priceSetId, 'is_quick_config')) {
      $isQuickConfig = 1;
    }
    //CRM-11529 for quick config back office transactions
    //when financial_type_id is passed in form, update the
    //line items with the financial type selected in form
    // NOTE that this IS still a legitimate use of 'quick-config' for contributions under the current DB but
    // we should look at having a price field per contribution type & then there would be little reason
    // for the back-office contribution form postProcess to know if it is a quick-config form.
    if ($isQuickConfig && !empty($submittedValues['financial_type_id']) && !empty($lineItem[$this->_priceSetId])
    ) {
      foreach ($lineItem[$this->_priceSetId] as &$values) {
        $values['financial_type_id'] = $submittedValues['financial_type_id'];
      }
    }

    if (!isset($submittedValues['total_amount'])) {
      $submittedValues['total_amount'] = $this->_values['total_amount'] ?? NULL;
      // Avoid tax amount deduction on edit form and keep it original, because this will lead to error described in CRM-20676
      if (!$this->_id) {
        $submittedValues['total_amount'] -= $this->_values['tax_amount'] ?? 0;
      }
    }
    $this->assign('lineItem', !empty($lineItem) && !$isQuickConfig ? $lineItem : FALSE);

    $isEmpty = array_keys(array_flip($submittedValues['soft_credit_contact_id'] ?? []));
    if ($this->_id && count($isEmpty) == 1 && key($isEmpty) == NULL) {
      civicrm_api3('ContributionSoft', 'get', ['contribution_id' => $this->_id, 'pcp_id' => ['IS NULL' => 1], 'api.ContributionSoft.delete' => 1]);
    }

    // set the contact, when contact is selected
    if (!empty($submittedValues['contact_id'])) {
      $this->_contactID = $submittedValues['contact_id'];
    }

    $formValues = $submittedValues;

    // Credit Card Contribution.
    if ($this->_mode) {
      $paramsSetByPaymentProcessingSubsystem = [
        'trxn_id',
        'payment_instrument_id',
        'contribution_status_id',
        'cancel_date',
        'cancel_reason',
      ];
      foreach ($paramsSetByPaymentProcessingSubsystem as $key) {
        if (isset($formValues[$key])) {
          unset($formValues[$key]);
        }
      }
      $contribution = $this->processCreditCard($formValues, $lineItem, $this->_contactID);
      foreach ($paramsSetByPaymentProcessingSubsystem as $key) {
        $formValues[$key] = $contribution->$key;
      }
    }
    else {
      // Offline Contribution.
      $submittedValues = $this->unsetCreditCardFields($submittedValues);

      // get the required field value only.

      $params = [
        'contact_id' => $this->_contactID,
        'currency' => $this->getCurrency(),
        'skipCleanMoney' => TRUE,
        'id' => $this->_id,
      ];

      //format soft-credit/pcp param first
      CRM_Contribute_BAO_ContributionSoft::formatSoftCreditParams($submittedValues, $this);
      $params = array_merge($params, $submittedValues);

      $fields = [
        'financial_type_id',
        'payment_instrument_id',
        'cancel_reason',
        'source',
        'check_number',
        'card_type_id',
        'pan_truncation',
      ];
      foreach ($fields as $f) {
        $params[$f] = $formValues[$f] ?? NULL;
      }
      if ($this->_id && $action & CRM_Core_Action::UPDATE) {
        // @todo - should we remove all this - if it's going from Pending to Completed then
        // add payment handles that - what statuses CAN be changed here?
        // Also - the changing of is_pay_later to 0 here has been debated at times
        // as it could be argued it still showed the intent.
        // Can only be updated to contribution which is handled via Payment.create
        $params['contribution_status_id'] = $this->getSubmittedValue('contribution_status_id');
        if ($params['contribution_status_id'] == CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed')) {
          // @todo - if the contribution is new then it should be Pending status & then we use
          // Payment.create to update to Completed.
          // If contribution_status is changed to Completed is_pay_later flag is changed to 0, CRM-15041
          $params['is_pay_later'] = 0;
        }
      }
      // Set is_pay_later flag for new back-office offline Pending status contributions CRM-8996
      if (!$this->getContributionID() && $params['contribution_status_id'] == CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending')) {
        $params['is_pay_later'] = 1;
      }

      $params['revenue_recognition_date'] = NULL;
      if (!empty($formValues['revenue_recognition_date'])) {
        $params['revenue_recognition_date'] = $formValues['revenue_recognition_date'];
      }

      if (!empty($formValues['is_email_receipt'])) {
        $params['receipt_date'] = date('Y-m-d');
      }

      if (CRM_Contribute_BAO_Contribution::isContributionStatusNegative($params['contribution_status_id'])
      ) {
        if (CRM_Utils_System::isNull($params['cancel_date'] ?? NULL)) {
          $params['cancel_date'] = date('YmdHis');
        }
      }
      else {
        $params['cancel_date'] = $params['cancel_reason'] = 'null';
      }

      // Add Additional common information to formatted params.
      CRM_Contribute_Form_AdditionalInfo::postProcessCommon($formValues, $params, $this);
      if ($pId) {
        $params['contribution_mode'] = 'participant';
        $params['participant_id'] = $pId;
        $params['skipLineItem'] = 1;
      }
      $params['line_item'] = $lineItem;
      $params['payment_processor_id'] = $params['payment_processor'] = $this->_paymentProcessor['id'] ?? NULL;
      $params['tax_amount'] = $submittedValues['tax_amount'] ?? $this->_values['tax_amount'] ?? NULL;
      //create contribution.
      if ($isQuickConfig) {
        $params['is_quick_config'] = 1;
      }
      $params['non_deductible_amount'] = $this->calculateNonDeductibleAmount($params, $formValues);

      // we are already handling note below, so to avoid duplicate notes against $contribution
      if (!empty($params['note']) && !empty($submittedValues['note'])) {
        unset($params['note']);
      }
      $previousStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $this->_values['contribution_status_id'] ?? NULL);
      // process associated membership / participant, CRM-4395
      if ($this->getContributionID() && $this->getAction() & CRM_Core_Action::UPDATE
        && in_array($previousStatus, ['Pending', 'Partially paid'], TRUE)
        && 'Completed' === CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $this->getSubmittedValue('contribution_status_id'))) {
        // @todo make users use add payment form.
        civicrm_api3('Payment', 'create', [
          'is_send_contribution_notification' => FALSE,
          'contribution_id' => $this->getContributionID(),
          'total_amount' => $this->getContributionValue('balance_amount'),
          'currency' => $this->getSubmittedValue('currency'),
          'payment_instrument_id' => $this->getSubmittedValue('payment_instrument_id'),
          'check_number' => $this->getSubmittedValue('check_number'),
        ]);
      }
      $contribution = CRM_Contribute_BAO_Contribution::create($params);

      array_unshift($this->statusMessage, ts('The contribution record has been saved.'));

      $this->invoicingPostProcessHook($submittedValues, $action, $lineItem);

      //send receipt mail.
      //FIXME: 'payment.create' could send a receipt.
      if ($contribution->id && !empty($formValues['is_email_receipt'])) {
        $formValues['contact_id'] = $this->_contactID;
        $formValues['contribution_id'] = $contribution->id;

        $formValues += CRM_Contribute_BAO_ContributionSoft::getSoftContribution($contribution->id);

        // to get 'from email id' for send receipt
        $this->fromEmailId = $formValues['from_email_address'] ?? NULL;
        if (CRM_Contribute_Form_AdditionalInfo::emailReceipt($this, $formValues)) {
          $this->statusMessage[] = ts('A receipt has been emailed to the contributor.');
        }
      }

      $this->statusMessageTitle = ts('Saved');

    }

    if ($contribution->id && isset($formValues['product_name'][0])) {
      CRM_Contribute_Form_AdditionalInfo::processPremium($submittedValues, $contribution->id,
        $this->_premiumID, $this->_options
      );
    }

    if ($contribution->id && array_key_exists('note', $submittedValues)) {
      CRM_Contribute_Form_AdditionalInfo::processNote($submittedValues, $this->_contactID, $contribution->id, $this->_noteID);
    }

    // If financial type has changed from non-deductible to deductible, let the user know so they can adjust the non-deductible amount
    $toType = $submittedValues['financial_type_id'] ?? NULL;
    $fromType = $this->_defaults['financial_type_id'] ?? NULL;
    if (($this->_action & CRM_Core_Action::UPDATE) && ($toType != $fromType) && ($submittedValues['non_deductible_amount'] ?? NULL)) {
      $deductible = FinancialType::get(TRUE)
        ->addSelect('is_deductible')
        ->addWhere('id', 'IN', [$toType, $fromType])
        ->execute()->column('is_deductible', 'id');
      if ($deductible[$fromType] == FALSE && $deductible[$toType] == TRUE) {
        CRM_Core_Session::setStatus(ts("You've changed the financial type for this %1 contribution from non-tax deductible to tax deductible, but the non-deductible amount of %2 has not been changed. This could prevent a tax receipt from being issued correctly. You may want to edit the non-deductible amount.",
          [1 => Civi::format()->money($submittedValues['total_amount']), 2 => Civi::format()->money($submittedValues['non_deductible_amount'])]),
          ts('Non-deductible amount'), 'alert', ['expires' => 30000]);
      }
    }

    CRM_Core_Session::setStatus(implode(' ', $this->statusMessage), $this->statusMessageTitle, 'success');

    CRM_Contribute_BAO_Contribution::updateRelatedPledge(
      $action,
      $pledgePaymentID,
      $contribution->id,
      ($formValues['option_type'] ?? 0) == 2,
      $formValues['total_amount'],
      $this->_defaults['total_amount'] ?? NULL,
      $formValues['contribution_status_id'],
      $this->_defaults['contribution_status_id'] ?? NULL
    );
    return $contribution;
  }

  /**
   * Assign tax calculations to contribution receipts.
   *
   * @param array $submittedValues
   * @param int $action
   * @param array $lineItem
   */
  protected function invoicingPostProcessHook($submittedValues, $action, $lineItem): void {
    if (!Civi::settings()->get('invoicing')) {
      return;
    }
    $taxRate = [];
    $getTaxDetails = FALSE;

    foreach ($lineItem as $key => $value) {
      foreach ($value as $v) {
        if (isset($taxRate[(string) CRM_Utils_Array::value('tax_rate', $v)])) {
        }
        else {
          if (isset($v['tax_rate'])) {
            $getTaxDetails = TRUE;
          }
        }
      }
    }

    if ($action & CRM_Core_Action::UPDATE) {
      $totalTaxAmount = $submittedValues['tax_amount'] ?? $this->_values['tax_amount'];
      // Assign likely replaced by a token
      $this->assign('totalTaxAmount', $totalTaxAmount);
    }
    else {
      if (!empty($submittedValues['price_set_id'])) {
        $this->assign('totalTaxAmount', $submittedValues['tax_amount']);
        $this->assign('getTaxDetails', $getTaxDetails);
      }
      else {
        $this->assign('totalTaxAmount', $submittedValues['tax_amount'] ?? NULL);
      }
    }
  }

  /**
   * Calculate non deductible amount.
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-11956
   * if non_deductible_amount exists i.e. Additional Details field set was opened [and staff typed something] -
   * if non_deductible_amount does NOT exist - then calculate it depending on:
   * $financialType->is_deductible and whether there is a product (premium).
   *
   * @param $params
   * @param $formValues
   *
   * @return array
   */
  protected function calculateNonDeductibleAmount($params, $formValues) {
    if (!empty($params['non_deductible_amount'])) {
      return $params['non_deductible_amount'];
    }

    $priceSetId = $params['price_set_id'] ?? NULL;
    // return non-deductible amount if it is set at the price field option level
    if ($priceSetId && !empty($params['line_item'])) {
      $nonDeductibleAmount = CRM_Price_BAO_PriceSet::getNonDeductibleAmountFromPriceSet($priceSetId, $params['line_item']);
      if (!empty($nonDeductibleAmount)) {
        return $nonDeductibleAmount;
      }
    }

    $financialType = new CRM_Financial_DAO_FinancialType();
    $financialType->id = $params['financial_type_id'];
    $financialType->find(TRUE);

    if ($financialType->is_deductible) {

      if (isset($formValues['product_name'][0])) {
        $selectProduct = $formValues['product_name'][0];
      }
      // if there is a product - compare the value to the contribution amount
      if (isset($selectProduct)) {
        $productDAO = new CRM_Contribute_DAO_Product();
        $productDAO->id = $selectProduct;
        $productDAO->find(TRUE);
        // product value exceeds contribution amount
        if ($params['total_amount'] < $productDAO->price) {
          return $params['total_amount'];
        }
        // product value does NOT exceed contribution amount
        else {
          return $productDAO->price;
        }
      }
      // contribution is deductible - but there is no product
      else {
        return '0.00';
      }
    }
    // contribution is NOT deductible
    else {
      return $params['total_amount'];
    }

    return 0;
  }

  /**
   * Get the financial Type ID for the contribution either from the submitted values or from the contribution values if possible.
   *
   * This is important for dev/core#1728 - ie ensure that if we are returned to the form for a form
   * error that any custom fields based on the selected financial type are loaded.
   *
   * @return int
   */
  protected function getFinancialTypeID() {
    if (!empty($this->_submitValues['financial_type_id'])) {
      return $this->_submitValues['financial_type_id'];
    }
    if (!empty($this->_values['financial_type_id'])) {
      return $this->_values['financial_type_id'];
    }
  }

  /**
   * Set context in session
   */
  public function setUserContext(): void {
    $session = CRM_Core_Session::singleton();
    $buttonName = $this->controller->getButtonName();
    if ($this->_context === 'standalone') {
      if ($buttonName == $this->getButtonName('upload', 'new')) {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/contribute/add',
          'reset=1&action=add&context=standalone'
        ));
      }
      else {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
          "reset=1&cid={$this->_contactID}&selectedChild=contribute"
        ));
      }
    }
    elseif ($this->_context === 'contribution' && $this->_mode && $buttonName == $this->getButtonName('upload', 'new')) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/contribution',
        "reset=1&action=add&context={$this->_context}&cid={$this->_contactID}&mode={$this->_mode}"
      ));
    }
    elseif ($buttonName == $this->getButtonName('upload', 'new')) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/contribution',
        "reset=1&action=add&context={$this->_context}&cid={$this->_contactID}"
      ));
    }
  }

  /**
   * Get the selected Contribution ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getContributionID(): ?int {
    if (!$this->_id) {
      $this->_id = CRM_Utils_Request::retrieve('id', 'Positive');
    }
    return $this->_id ? (int) $this->_id : NULL;
  }

  /**
   * Get id of contribution page being acted on.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function getContributionPageID(): ?int {
    return $this->getContributionID() ? $this->getContributionValue('contribution_page_id') : NULL;
  }

  /**
   * Get the selected contribution status.
   *
   * @return string|null
   *
   * @throws \CRM_Core_Exception
   */
  protected function getPreviousContributionStatus(): ?string {
    if (!$this->getContributionID()) {
      return NULL;
    }
    if (!$this->previousContributionStatus) {
      $this->previousContributionStatus = Contribution::get(FALSE)
        ->addWhere('id', '=', $this->getContributionID())
        ->addSelect('contribution_status_id:name')
        ->execute()
        ->first()['contribution_status_id:name'];
    }
    return $this->previousContributionStatus;
  }

  /**
   * Get the contribution statuses available on the form.
   *
   * @todo - this needs work - some returned options are invalid or do
   * not create good financial entities. Probably the only reason we don't just
   * return CRM_Contribute_BAO_Contribution_Utils::getPendingCompleteFailedAndCancelledStatuses();
   * is that it might exclude the current status of the contribution.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getAvailableContributionStatuses(): array {
    if (!$this->getPreviousContributionStatus()) {
      return CRM_Contribute_BAO_Contribution_Utils::getPendingCompleteFailedAndCancelledStatuses();
    }
    $statusNames = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'validate');
    $statusNamesToUnset = array_diff([
      // For records which represent a data template for a recurring
      // contribution that may not yet have a payment. This status should not
      // be available from forms. 'Template' contributions should only be created
      // in conjunction with a ContributionRecur record, and should have their
      // is_template field set to 1. This status excludes them from reports
      // that are still ignorant of the is_template field.
      'Template',
      'Partially paid',
      'Pending refund',
    ], [$this->getPreviousContributionStatus()]);
    switch ($this->getPreviousContributionStatus()) {
      case 'Completed':
        // [CRM-17498] Removing unsupported status change options.
        $statusNamesToUnset = array_merge($statusNamesToUnset, [
          'Pending',
          'Failed',
        ]);
        break;

      case 'Cancelled':
      case 'Chargeback':
      case 'Refunded':
        $statusNamesToUnset = array_merge($statusNamesToUnset, [
          'Pending',
          'Failed',
        ]);
        break;

      case 'Pending':
      case 'In Progress':
        $statusNamesToUnset = array_merge($statusNamesToUnset, [
          'Refunded',
          'Chargeback',
        ]);
        break;

      case 'Failed':
        $statusNamesToUnset = array_merge($statusNamesToUnset, [
          'Pending',
          'Refunded',
          'Chargeback',
          'Completed',
          'In Progress',
          'Cancelled',
        ]);
        break;
    }

    foreach ($statusNamesToUnset as $name) {
      unset($statusNames[CRM_Utils_Array::key($name, $statusNames)]);
    }

    $statuses = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id');

    foreach ($statuses as $statusID => $label) {
      if (!array_key_exists($statusID, $statusNames)) {
        unset($statuses[$statusID]);
      }
    }

    return $statuses;
  }

  /**
   * Get the price set ID.
   *
   * @api Supported for external use.
   *
   * @return int|null
   */
  public function getPriceSetID(): ?int {
    $priceSetID = $this->getSubmittedValue('price_set_id') ?: CRM_Utils_Request::retrieve('priceSetId', 'Integer');
    if (!$this->isFormBuilt() && !empty($this->getSubmitValue('price_set_id'))) {
      return (int) $this->getSubmitValue('price_set_id');
    }
    return $priceSetID ?? NULL;
  }

  /**
   * @param int $id
   * @todo - this function is a long way, non standard of saying $dao = new CRM_Contribute_DAO_ContributionProduct(); $dao->id = $id; $dao->find();
   */
  private function assignPremiumProduct($id): void {
    $sql = "
SELECT *
FROM   civicrm_contribution_product
WHERE  contribution_id = {$id}
";
    $dao = CRM_Core_DAO::executeQuery($sql);
    if ($dao->fetch()) {
      $this->_premiumID = $dao->id;
      $this->_productDAO = $dao;
    }
  }

  /**
   * Get the contact ID in use.
   *
   * Ideally override this as appropriate to the form.
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocSignatureIsNotCompleteInspection
   */
  public function getContactID(): ?int {
    if ($this->_contactID === NULL) {
      $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
      if (empty($this->_contactID) && !empty($this->_id) && $this->entity) {
        $this->_contactID = civicrm_api3($this->entity, 'getvalue', ['id' => $this->_id, 'return' => 'contact_id']);
      }
    }
    return $this->_contactID ? (int) $this->_contactID : NULL;
  }

  /**
   * @return int|null
   * @throws \CRM_Core_Exception
   */
  public function getPledgePaymentID(): ?int {
    $this->_ppID = CRM_Utils_Request::retrieve('ppid', 'Positive', $this) ?: FALSE;
    return $this->_ppID ? (int) $this->_ppID : NULL;
  }

  /**
   * Build elements to collect information for recurring contributions.
   *
   * Previously shared function.
   */
  private function buildRecur(): void {
    $form = $this;
    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionRecur');

    // @todo - this is previously shared code and it feels  like the recur_frequency_unit would always be NULL here.
    $frUnits = $form->_values['recur_frequency_unit'] ?? NULL;
    $frequencyUnits = CRM_Core_OptionGroup::values('recur_frequency_units', FALSE, FALSE, TRUE);
    if (empty($frUnits)
    ) {
      $frUnits = implode(CRM_Core_DAO::VALUE_SEPARATOR,
        CRM_Core_OptionGroup::values('recur_frequency_units', FALSE, FALSE, FALSE, NULL, 'value')
      );
    }

    $unitVals = explode(CRM_Core_DAO::VALUE_SEPARATOR, $frUnits);

    $form->add('text', 'installments', ts('installments'),
      $attributes['installments'] + ['class' => 'two']
    );
    $form->addRule('installments', ts('Number of installments must be a whole number.'), 'integer');

    $is_recur_label = ts('I want to contribute this amount every');

    // CRM 10860, display text instead of a dropdown if there's only 1 frequency unit
    if (count($unitVals) == 1) {
      $form->add('hidden', 'frequency_unit', $unitVals[0]);
      $unit = CRM_Contribute_BAO_Contribution::getUnitLabelWithPlural($unitVals[0]);
    }
    else {
      $units = [];
      foreach ($unitVals as $key => $val) {
        if (array_key_exists($val, $frequencyUnits)) {
          $units[$val] = $frequencyUnits[$val];
          $units[$val] = CRM_Contribute_BAO_Contribution::getUnitLabelWithPlural($val);
          $unit = ts('Every');
        }
      }
      $frequencyUnit = &$form->add('select', 'frequency_unit', NULL, $units, FALSE, ['aria-label' => ts('Frequency Unit'), 'class' => 'crm-select2 eight']);
    }

    $form->add('text', 'frequency_interval', $unit, $attributes['frequency_interval'] + ['aria-label' => ts('Every'), 'class' => 'two']);
    $form->addRule('frequency_interval', ts('Frequency must be a whole number (EXAMPLE: Every 3 months).'), 'integer');

    $form->add('checkbox', 'is_recur', $is_recur_label, NULL);
  }

}
