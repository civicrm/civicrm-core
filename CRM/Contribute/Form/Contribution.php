<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but   |
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

use Civi\Payment\Exception\PaymentProcessorException;

/**
 * This class generates form components for processing a contribution.
 */
class CRM_Contribute_Form_Contribution extends CRM_Contribute_Form_AbstractEditPayment {
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
  public $_premiumID = NULL;

  /**
   * @var CRM_Contribute_DAO_ContributionProduct
   */
  public $_productDAO = NULL;

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
   */
  public $_ppID;

  /**
   * Is this contribution associated with an online.
   * financial transaction
   *
   * @var boolean
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
   * Store the contribution Type ID
   *
   * @var array
   */
  public $_contributionType;

  /**
   * The contribution values if an existing contribution
   */
  public $_values;

  /**
   * The pledge values if this contribution is associated with pledge
   */
  public $_pledgeValues;

  public $_contributeMode = 'direct';

  public $_context;

  /**
   * Parameter with confusing name.
   * @todo what is it?
   * @var string
   */
  public $_compContext;

  public $_compId;

  /**
   * Possible From email addresses
   * @var array
   */
  public $_fromEmails;

  /**
   * ID of from email
   * @var integer
   */
  public $fromEmailId;

  /**
   * Store the line items if price set used.
   */
  public $_lineItems;

  /**
   * Line item
   * @todo explain why we use lineItem & lineItems
   * @var array
   */
  public $_lineItem;

  /**
   * @var array soft credit info
   */
  public $_softCreditInfo;

  protected $_formType;

  public $_honoreeProfileType;

  /**
   * Array of the payment fields to be displayed in the payment fieldset (pane) in billingBlock.tpl
   * this contains all the information to describe these fields from quickform. See CRM_Core_Form_Payment getPaymentFormFieldsMetadata
   *
   * @var array
   */
  public $_paymentFields = array();
  /**
   * Logged in user's email.
   * @var string
   */
  public $userEmail;

  /**
   * Price set ID
   * @var integer
   */
  public $_priceSetId;

  /**
   * Price set as an array
   * @var array
   */
  public $_priceSet;

  /**
   * User display name
   *
   * @var string
   */
  public $userDisplayName;

  /**
   * Status message to be shown to the user.
   *
   * @var array
   */
  protected $statusMessage = array();

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
   * Set variables up before form is built.
   */
  public function preProcess() {
    // Check permission for action.
    if (!CRM_Core_Permission::checkActionPermission('CiviContribute', $this->_action)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }

    parent::preProcess();

    $this->_formType = CRM_Utils_Array::value('formType', $_GET);

    // Get price set id.
    $this->_priceSetId = CRM_Utils_Array::value('priceSetId', $_GET);
    $this->set('priceSetId', $this->_priceSetId);
    $this->assign('priceSetId', $this->_priceSetId);

    // Get the pledge payment id
    $this->_ppID = CRM_Utils_Request::retrieve('ppid', 'Positive', $this);

    $this->assign('action', $this->_action);

    // Get the contribution id if update
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    if (!empty($this->_id)) {
      $this->assign('contribID', $this->_id);
    }

    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $this->assign('context', $this->_context);

    $this->_compId = CRM_Utils_Request::retrieve('compId', 'Positive', $this);

    $this->_compContext = CRM_Utils_Request::retrieve('compContext', 'String', $this);

    //set the contribution mode.
    $this->_mode = CRM_Utils_Request::retrieve('mode', 'String', $this);

    $this->assign('contributionMode', $this->_mode);
    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->assign('showCheckNumber', TRUE);

    $this->_fromEmails = CRM_Core_BAO_Email::getFromEmail();
    $this->assignPaymentRelatedVariables();

    if (in_array('CiviPledge', CRM_Core_Config::singleton()->enableComponents) && !$this->_formType) {
      $this->preProcessPledge();
    }

    if ($this->_id) {
      $this->showRecordLinkMesssage($this->_id);
    }
    $this->_values = array();

    // Current contribution id.
    if ($this->_id) {
      $this->assignPremiumProduct($this->_id);
      $this->buildValuesAndAssignOnline_Note_Type($this->_id, $this->_values);
    }

    // when custom data is included in this page
    if (!empty($_POST['hidden_custom'])) {
      $this->applyCustomData('Contribution', CRM_Utils_Array::value('financial_type_id', $_POST), $this->_id);
    }

    $this->_lineItems = array();
    if ($this->_id) {
      if (!empty($this->_compId) && $this->_compContext == 'participant') {
        $this->assign('compId', $this->_compId);
        $lineItem = CRM_Price_BAO_LineItem::getLineItems($this->_compId);
      }
      else {
        $lineItem = CRM_Price_BAO_LineItem::getLineItems($this->_id, 'contribution', 1, TRUE, TRUE);
      }
      empty($lineItem) ? NULL : $this->_lineItems[] = $lineItem;
    }

    $this->assign('lineItem', empty($this->_lineItems) ? FALSE : $this->_lineItems);

    // Set title
    if ($this->_mode && $this->_id) {
      $this->_payNow = TRUE;
      $this->assign('payNow', $this->_payNow);
      CRM_Utils_System::setTitle(ts('Pay with Credit Card'));
    }
    elseif ($this->_mode) {
      $this->setPageTitle($this->_ppID ? ts('Credit Card Pledge Payment') : ts('Credit Card Contribution'));
    }
    else {
      $this->setPageTitle($this->_ppID ? ts('Pledge Payment') : ts('Contribution'));
    }

    if ($this->_id) {
      CRM_Contribute_Form_SoftCredit::preprocess($this);
    }
  }

  /**
   * Set default values.
   *
   * @return array
   */
  public function setDefaultValues() {

    $defaults = $this->_values;

    // Set defaults for pledge payment.
    if ($this->_ppID) {
      $defaults['total_amount'] = CRM_Utils_Array::value('scheduled_amount', $this->_pledgeValues['pledgePayment']);
      $defaults['financial_type_id'] = CRM_Utils_Array::value('financial_type_id', $this->_pledgeValues);
      $defaults['currency'] = CRM_Utils_Array::value('currency', $this->_pledgeValues);
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

    // Set $newCredit variable in template to control whether link to credit card mode is included.
    $this->assign('newCredit', CRM_Core_Config::isEnabledBackOfficeCreditCardPayments());

    // Fix the display of the monetary value, CRM-4038.
    if (isset($defaults['total_amount'])) {
      if (!empty($defaults['tax_amount'])) {
        $componentDetails = CRM_Contribute_BAO_Contribution::getComponentDetails($this->_id);
        if (!(CRM_Utils_Array::value('membership', $componentDetails) || CRM_Utils_Array::value('participant', $componentDetails))) {
          $defaults['total_amount'] = CRM_Utils_Money::format($defaults['total_amount'] - $defaults['tax_amount'], NULL, '%a');
        }
      }
      else {
        $defaults['total_amount'] = CRM_Utils_Money::format($defaults['total_amount'], NULL, '%a');
      }
    }

    if (isset($defaults['non_deductible_amount'])) {
      $defaults['non_deductible_amount'] = CRM_Utils_Money::format($defaults['non_deductible_amount'], NULL, '%a');
    }

    if (isset($defaults['fee_amount'])) {
      $defaults['fee_amount'] = CRM_Utils_Money::format($defaults['fee_amount'], NULL, '%a');
    }

    if (isset($defaults['net_amount'])) {
      $defaults['net_amount'] = CRM_Utils_Money::format($defaults['net_amount'], NULL, '%a');
    }

    if ($this->_contributionType) {
      $defaults['financial_type_id'] = $this->_contributionType;
    }

    if (empty($defaults['payment_instrument_id'])) {
      $defaults['payment_instrument_id'] = key(CRM_Core_OptionGroup::values('payment_instrument', FALSE, FALSE, FALSE, 'AND is_default = 1'));
    }

    if (!empty($defaults['is_test'])) {
      $this->assign('is_test', TRUE);
    }

    $this->assign('showOption', TRUE);
    // For Premium section.
    if ($this->_premiumID) {
      $this->assign('showOption', FALSE);
      $options = isset($this->_options[$this->_productDAO->product_id]) ? $this->_options[$this->_productDAO->product_id] : "";
      if (!$options) {
        $this->assign('showOption', TRUE);
      }
      $options_key = CRM_Utils_Array::key($this->_productDAO->product_option, $options);
      if ($options_key) {
        $defaults['product_name'] = array($this->_productDAO->product_id, trim($options_key));
      }
      else {
        $defaults['product_name'] = array($this->_productDAO->product_id);
      }
      if ($this->_productDAO->fulfilled_date) {
        list($defaults['fulfilled_date']) = CRM_Utils_Date::setDateDefaults($this->_productDAO->fulfilled_date);
      }
    }

    if (isset($this->userEmail)) {
      $this->assign('email', $this->userEmail);
    }

    if (!empty($defaults['is_pay_later'])) {
      $this->assign('is_pay_later', TRUE);
    }
    $this->assign('contribution_status_id', CRM_Utils_Array::value('contribution_status_id', $defaults));
    if (!empty($defaults['contribution_status_id']) && in_array(
        CRM_Contribute_PseudoConstant::contributionStatus($defaults['contribution_status_id'], 'name'),
        // Historically not 'Cancelled' hence not using CRM_Contribute_BAO_Contribution::isContributionStatusNegative.
        array('Refunded', 'Chargeback')
      )) {
      $defaults['refund_trxn_id'] = CRM_Core_BAO_FinancialTrxn::getRefundTransactionTrxnID($this->_id);
    }
    else {
      $defaults['refund_trxn_id'] = isset($defaults['trxn_id']) ? $defaults['trxn_id'] : NULL;
    }
    $dates = array(
      'receive_date',
      'receipt_date',
      'cancel_date',
      'thankyou_date',
    );
    foreach ($dates as $key) {
      if (!empty($defaults[$key])) {
        list($defaults[$key], $defaults[$key . '_time'])
          = CRM_Utils_Date::setDateDefaults(CRM_Utils_Array::value($key, $defaults), 'activityDateTime');
      }
    }

    if (!$this->_id && empty($defaults['receive_date'])) {
      list($defaults['receive_date'],
        $defaults['receive_date_time']
        ) = CRM_Utils_Date::setDateDefaults(NULL, 'activityDateTime');
    }

    $this->assign('receive_date', CRM_Utils_Date::processDate(CRM_Utils_Array::value('receive_date', $defaults),
      CRM_Utils_Array::value('receive_date_time', $defaults)
    ));
    $currency = CRM_Utils_Array::value('currency', $defaults);
    $this->assign('currency', $currency);
    // Hack to get currency info to the js layer. CRM-11440.
    CRM_Utils_Money::format(1);
    $this->assign('currencySymbol', CRM_Utils_Array::value($currency, CRM_Utils_Money::$_currencySymbols));
    $this->assign('totalAmount', CRM_Utils_Array::value('total_amount', $defaults));

    // Inherit campaign from pledge.
    if ($this->_ppID && !empty($this->_pledgeValues['campaign_id'])) {
      $defaults['campaign_id'] = $this->_pledgeValues['campaign_id'];
    }

    $this->_defaults = $defaults;
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    // FIXME: This probably needs to be done in preprocess
    if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()
      && $this->_action & CRM_Core_Action::UPDATE
      && CRM_Utils_Array::value('financial_type_id', $this->_values)
    ) {
      $financialTypeID = CRM_Contribute_PseudoConstant::financialType($this->_values['financial_type_id']);
      CRM_Financial_BAO_FinancialType::checkPermissionedLineItems($this->_id, 'edit');
      if (!CRM_Core_Permission::check('edit contributions of type ' . $financialTypeID)) {
        CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
      }
    }
    $allPanes = array();
    $recurJs = NULL;
    //tax rate from financialType
    $this->assign('taxRates', json_encode(CRM_Core_PseudoConstant::getTaxRates()));
    $this->assign('currencies', json_encode(CRM_Core_OptionGroup::values('currencies_enabled')));

    // build price set form.
    $buildPriceSet = FALSE;
    $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
    $invoicing = CRM_Utils_Array::value('invoicing', $invoiceSettings);
    $this->assign('invoicing', $invoicing);

    // display tax amount on edit contribution page
    if ($invoicing && $this->_action & CRM_Core_Action::UPDATE && isset($this->_values['tax_amount'])) {
      $this->assign('totalTaxAmount', $this->_values['tax_amount']);
    }

    if (empty($this->_lineItems) &&
      ($this->_priceSetId || !empty($_POST['price_set_id']))
    ) {
      $buildPriceSet = TRUE;
      $getOnlyPriceSetElements = TRUE;
      if (!$this->_priceSetId) {
        $this->_priceSetId = $_POST['price_set_id'];
        $getOnlyPriceSetElements = FALSE;
      }

      $this->set('priceSetId', $this->_priceSetId);
      CRM_Price_BAO_PriceSet::buildPriceSet($this);

      // get only price set form elements.
      if ($getOnlyPriceSetElements) {
        return;
      }
    }
    // use to build form during form rule.
    $this->assign('buildPriceSet', $buildPriceSet);

    $defaults = $this->_values;
    $additionalDetailFields = array(
      'note',
      'thankyou_date',
      'invoice_id',
      'non_deductible_amount',
      'fee_amount',
      'net_amount',
    );
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
      isset($this->_values['note'])
    ) {
      $defaults['hidden_AdditionalDetail'] = 1;
    }

    $paneNames = array();
    if (empty($this->_payNow)) {
      $paneNames[ts('Additional Details')] = 'AdditionalDetail';
    }

    //Add Premium pane only if Premium is exists.
    $dao = new CRM_Contribute_DAO_Product();
    $dao->is_active = 1;

    if ($dao->find(TRUE) && empty($this->_payNow)) {
      $paneNames[ts('Premium Information')] = 'Premium';
    }

    if ($this->_mode) {
      if (CRM_Core_Payment_Form::buildPaymentForm($this, $this->_paymentProcessor, FALSE, TRUE) == TRUE) {
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
            CRM_Contribute_Form_Contribution_Main::buildRecur($this);
            $this->setDefaults(array('is_recur' => 0));
            $this->assign('buildRecurBlock', TRUE);
            $recurJs = array('onChange' => "buildRecurBlock( this.value ); return false;");
          }
        }
      }
    }

    foreach ($paneNames as $name => $type) {
      $allPanes[$name] = $this->generatePane($type, $defaults);
    }

    $qfKey = $this->controller->_key;
    $this->assign('qfKey', $qfKey);
    $this->assign('allPanes', $allPanes);

    $this->addFormRule(array('CRM_Contribute_Form_Contribution', 'formRule'), $this);

    if ($this->_formType) {
      $this->assign('formType', $this->_formType);
      return;
    }

    $this->applyFilter('__ALL__', 'trim');

    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => ts('Delete'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
      return;
    }

    //need to assign custom data type and subtype to the template
    $this->assign('customDataType', 'Contribution');
    $this->assign('customDataSubType', $this->_contributionType);
    $this->assign('entityID', $this->_id);

    if ($this->_context == 'standalone') {
      $this->addEntityRef('contact_id', ts('Contact'), array(
          'create' => TRUE,
          'api' => array('extra' => array('email')),
        ), TRUE);
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution');

    // Check permissions for financial type first
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes, $this->_action);
    if (empty($financialTypes)) {
      CRM_Core_Error::statusBounce(ts('You do not have all the permissions needed for this page.'));
    }
    $financialType = $this->add('select', 'financial_type_id',
      ts('Financial Type'),
      array('' => ts('- select -')) + $financialTypes,
      TRUE,
      array('onChange' => "CRM.buildCustomData( 'Contribution', this.value );")
    );

    $paymentInstrument = FALSE;
    if (!$this->_mode) {
      $paymentInstrument = $this->add('select', 'payment_instrument_id',
        ts('Payment Method'),
        array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::paymentInstrument(),
        TRUE, array('onChange' => "return showHideByValue('payment_instrument_id','4','checkNumber','table-row','select',false);")
      );
    }

    $trxnId = $this->add('text', 'trxn_id', ts('Transaction ID'), array('class' => 'twelve') + $attributes['trxn_id']);

    //add receipt for offline contribution
    $this->addElement('checkbox', 'is_email_receipt', ts('Send Receipt?'));

    $this->add('select', 'from_email_address', ts('Receipt From'), $this->_fromEmails);

    $status = CRM_Contribute_PseudoConstant::contributionStatus();

    // suppressing contribution statuses that are NOT relevant to pledges (CRM-5169)
    $statusName = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    if ($this->_ppID) {
      foreach (array(
                 'Cancelled',
                 'Failed',
                 'In Progress',
               ) as $suppress) {
        unset($status[CRM_Utils_Array::key($suppress, $statusName)]);
      }
    }
    elseif ((!$this->_ppID && $this->_id) || !$this->_id) {
      $suppressFlag = FALSE;
      if ($this->_id) {
        $componentDetails = CRM_Contribute_BAO_Contribution::getComponentDetails($this->_id);
        if (CRM_Utils_Array::value('membership', $componentDetails) || CRM_Utils_Array::value('participant', $componentDetails)) {
          $suppressFlag = TRUE;
        }
      }
      if (!$suppressFlag) {
        foreach (array(
                   'Overdue',
                   'In Progress',
                 ) as $suppress) {
          unset($status[CRM_Utils_Array::key($suppress, $statusName)]);
        }
      }
      else {
        unset($status[CRM_Utils_Array::key('Overdue', $statusName)]);
      }
    }

    // define the status IDs that show the cancellation info, see CRM-17589
    $cancelInfo_show_ids = array();
    foreach (array_keys($statusName) as $status_id) {
      if (CRM_Contribute_BAO_Contribution::isContributionStatusNegative($status_id)) {
        $cancelInfo_show_ids[] = "'$status_id'";
      }
    }
    $this->assign('cancelInfo_show_ids', implode(',', $cancelInfo_show_ids));

    if ($this->_id) {
      $contributionStatus = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $this->_id, 'contribution_status_id');
      $name = CRM_Utils_Array::value($contributionStatus, $statusName);
      switch ($name) {
        case 'Completed':
          // [CRM-17498] Removing unsupported status change options.
          unset($status[CRM_Utils_Array::key('Pending', $statusName)]);
          unset($status[CRM_Utils_Array::key('Failed', $statusName)]);
          unset($status[CRM_Utils_Array::key('Partially paid', $statusName)]);
          unset($status[CRM_Utils_Array::key('Pending refund', $statusName)]);
        case 'Cancelled':
        case 'Chargeback':
        case 'Refunded':
          unset($status[CRM_Utils_Array::key('In Progress', $statusName)]);
          unset($status[CRM_Utils_Array::key('Pending', $statusName)]);
          unset($status[CRM_Utils_Array::key('Failed', $statusName)]);
          break;

        case 'Pending':
        case 'In Progress':
          unset($status[CRM_Utils_Array::key('Refunded', $statusName)]);
          unset($status[CRM_Utils_Array::key('Chargeback', $statusName)]);
          break;

        case 'Failed':
          foreach (array(
                     'Pending',
                     'Refunded',
                     'Chargeback',
                     'Completed',
                     'In Progress',
                     'Cancelled',
                   ) as $suppress) {
            unset($status[CRM_Utils_Array::key($suppress, $statusName)]);
          }
          break;
      }
    }
    else {
      unset($status[CRM_Utils_Array::key('Refunded', $statusName)]);
      unset($status[CRM_Utils_Array::key('Chargeback', $statusName)]);
    }

    $statusElement = $this->add('select', 'contribution_status_id',
      ts('Contribution Status'),
      $status,
      FALSE
    );

    $currencyFreeze = FALSE;
    if (!empty($this->_payNow) && ($this->_action & CRM_Core_Action::UPDATE)) {
      $statusElement->freeze();
      $currencyFreeze = TRUE;
      $attributes['total_amount']['readonly'] = TRUE;
    }

    // CRM-16189, add Revenue Recognition Date
    if (CRM_Contribute_BAO_Contribution::checkContributeSettings('deferred_revenue_enabled')) {
      $revenueDate = $this->add('date', 'revenue_recognition_date', ts('Revenue Recognition Date'), CRM_Core_SelectValues::date(NULL, 'M Y', NULL, 5));
      if ($this->_id && !CRM_Contribute_BAO_Contribution::allowUpdateRevenueRecognitionDate($this->_id)) {
        $revenueDate->freeze();
      }
    }

    // add various dates
    $this->addDateTime('receive_date', ts('Received'), FALSE, array('formatType' => 'activityDateTime'));

    if ($this->_online) {
      $this->assign('hideCalender', TRUE);
    }
    $checkNumber = $this->add('text', 'check_number', ts('Check Number'), $attributes['check_number']);

    $this->addDateTime('receipt_date', ts('Receipt Date'), FALSE, array('formatType' => 'activityDateTime'));
    $this->addDateTime('cancel_date', ts('Cancelled / Refunded Date'), FALSE, array('formatType' => 'activityDateTime'));

    $this->add('textarea', 'cancel_reason', ts('Cancellation / Refund Reason'), $attributes['cancel_reason']);
    $this->add('text', 'refund_trxn_id', ts('Transaction ID for the refund payment'));
    $element = $this->add('select',
      'payment_processor_id',
      ts('Payment Processor'),
      $this->_processors,
      NULL,
      $recurJs
    );

    if ($this->_online) {
      $element->freeze();
    }

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
        $componentDetails = CRM_Contribute_BAO_Contribution::getComponentDetails($this->_id);
        $pledgePaymentId = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_PledgePayment',
          $this->_id,
          'id',
          'contribution_id'
        );
        if ($pledgePaymentId) {
          $buildPriceSet = FALSE;
        }
        if ($participantID = CRM_Utils_Array::value('participant', $componentDetails)) {
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
          array(
            '' => ts('Choose price set'),
          ) + $priceSets,
          NULL, array('onchange' => "buildAmount( this.value, " . json_encode($financialTypeIds) . ");")
        );
        if ($this->_online && !($this->_action & CRM_Core_Action::UPDATE)) {
          $element->freeze();
        }
      }
      $this->assign('hasPriceSets', $hasPriceSets);
      if (!($this->_action & CRM_Core_Action::UPDATE)) {
        if ($this->_online || $this->_ppID) {
          $attributes['total_amount'] = array_merge($attributes['total_amount'], array(
            'READONLY' => TRUE,
            'style' => "background-color:#EBECE4",
          ));
          $optionTypes = array(
            '1' => ts('Adjust Pledge Payment Schedule?'),
            '2' => ts('Adjust Total Pledge Amount?'),
          );
          $this->addRadio('option_type',
            NULL,
            $optionTypes,
            array(), '<br/>'
          );

          $currencyFreeze = TRUE;
        }
      }

      $totalAmount = $this->addMoney('total_amount',
        ts('Total Amount'),
        ($hasPriceSets) ? FALSE : TRUE,
        $attributes['total_amount'],
        TRUE, 'currency', NULL, $currencyFreeze
      );
    }

    $this->add('text', 'source', ts('Source'), CRM_Utils_Array::value('source', $attributes));

    // CRM-7362 --add campaigns.
    CRM_Campaign_BAO_Campaign::addCampaign($this, CRM_Utils_Array::value('campaign_id', $this->_values));

    if (empty($this->_payNow)) {
      CRM_Contribute_Form_SoftCredit::buildQuickForm($this);
    }

    $js = NULL;
    if (!$this->_mode) {
      $js = array('onclick' => "return verify( );");
    }

    $mailingInfo = Civi::settings()->get('mailing_backend');
    $this->assign('outBound_option', $mailingInfo['outBound_option']);

    $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' => ts('Save'),
          'js' => $js,
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'upload',
          'name' => ts('Save and New'),
          'js' => $js,
          'subName' => 'new',
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );

    // if status is Cancelled freeze Amount, Payment Instrument, Check #, Financial Type,
    // Net and Fee Amounts are frozen in AdditionalInfo::buildAdditionalDetail
    if ($this->_id && $this->_values['contribution_status_id'] == array_search('Cancelled', $statusName)) {
      if ($totalAmount) {
        $totalAmount->freeze();
      }
      $checkNumber->freeze();
      $paymentInstrument->freeze();
      $trxnId->freeze();
      $financialType->freeze();
    }

    // if contribution is related to membership or participant freeze Financial Type, Amount
    if ($this->_id && isset($this->_values['tax_amount'])) {
      $componentDetails = CRM_Contribute_BAO_Contribution::getComponentDetails($this->_id);
      if (CRM_Utils_Array::value('membership', $componentDetails) || CRM_Utils_Array::value('participant', $componentDetails)) {
        if ($totalAmount) {
          $totalAmount->freeze();
        }
        $financialType->freeze();
        $this->assign('freezeFinancialType', TRUE);
      }
    }

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->freeze();
    }
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
      if ($priceSetId = CRM_Utils_Array::value('price_set_id', $fields)) {
        CRM_Price_BAO_PriceField::priceSetValidation($priceSetId, $fields, $errors);
      }
    }

    $softErrors = CRM_Contribute_Form_SoftCredit::formRule($fields, $errors, $self);

    if (!empty($fields['total_amount']) && (!empty($fields['net_amount']) || !empty($fields['fee_amount']))) {
      $sum = CRM_Utils_Rule::cleanMoney($fields['net_amount']) + CRM_Utils_Rule::cleanMoney($fields['fee_amount']);
      // For taxable contribution we need to deduct taxable amount from
      // (net amount + fee amount) before comparing it with total amount
      if (!empty($self->_values['tax_amount'])) {
        $componentDetails = CRM_Contribute_BAO_Contribution::getComponentDetails($self->_id);
        if (!(CRM_Utils_Array::value('membership', $componentDetails) ||
            CRM_Utils_Array::value('participant', $componentDetails))
        ) {
          $sum = CRM_Utils_Money::format($sum - $self->_values['tax_amount'], NULL, '%a');
        }
      }
      if (CRM_Utils_Rule::cleanMoney($fields['total_amount']) != $sum) {
        $errors['total_amount'] = ts('The sum of fee amount and net amount must be equal to total amount');
      }
    }

    //CRM-16285 - Function to handle validation errors on form, for recurring contribution field.
    CRM_Contribute_BAO_ContributionRecur::validateRecurContribution($fields, $files, $self, $errors);

    // Form rule for status http://wiki.civicrm.org/confluence/display/CRM/CiviAccounts+4.3+Data+Flow
    if (($self->_action & CRM_Core_Action::UPDATE)
      && $self->_id
      && $self->_values['contribution_status_id'] != $fields['contribution_status_id']
    ) {
      CRM_Contribute_BAO_Contribution::checkStatusValidation($self->_values, $fields, $errors);
    }
    // CRM-16015, add form-rule to restrict change of financial type if using price field of different financial type
    if (($self->_action & CRM_Core_Action::UPDATE)
      && $self->_id
      && $self->_values['financial_type_id'] != $fields['financial_type_id']
    ) {
      CRM_Contribute_BAO_Contribution::checkFinancialTypeChange(NULL, $self->_id, $errors);
    }
    //FIXME FOR NEW DATA FLOW http://wiki.civicrm.org/confluence/display/CRM/CiviAccounts+4.3+Data+Flow
    if (!empty($fields['fee_amount']) && !empty($fields['financial_type_id']) && $financialType = CRM_Contribute_BAO_Contribution::validateFinancialType($fields['financial_type_id'])) {
      $errors['financial_type_id'] = ts("Financial Account of account relationship of 'Expense Account is' is not configured for Financial Type : ") . $financialType;
    }

    // $trxn_id must be unique CRM-13919
    if (!empty($fields['trxn_id'])) {
      $queryParams = array(1 => array($fields['trxn_id'], 'String'));
      $query = 'select count(*) from civicrm_contribution where trxn_id = %1';
      if ($self->_id) {
        $queryParams[2] = array((int) $self->_id, 'Integer');
        $query .= ' and id !=%2';
      }
      $tCnt = CRM_Core_DAO::singleValueQuery($query, $queryParams);
      if ($tCnt) {
        $errors['trxn_id'] = ts('Transaction ID\'s must be unique. Transaction \'%1\' already exists in your database.', array(1 => $fields['trxn_id']));
      }
    }
    if (!empty($fields['revenue_recognition_date'])
      && count(array_filter($fields['revenue_recognition_date'])) == 1
    ) {
      $errors['revenue_recognition_date'] = ts('Month and Year are required field for Revenue Recognition.');
    }
    // CRM-16189
    try {
      CRM_Financial_BAO_FinancialAccount::checkFinancialTypeHasDeferred($fields, $self->_id, $self->_priceSet['fields']);
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
    $submittedValues = $this->controller->exportValues($this->_name);

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
    $session = CRM_Core_Session::singleton();
    $buttonName = $this->controller->getButtonName();
    if ($this->_context == 'standalone') {
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
    elseif ($this->_context == 'contribution' && $this->_mode && $buttonName == $this->getButtonName('upload', 'new')) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/contribution',
        "reset=1&action=add&context={$this->_context}&cid={$this->_contactID}&mode={$this->_mode}"
      ));
    }
    elseif ($buttonName == $this->getButtonName('upload', 'new')) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/contribution',
        "reset=1&action=add&context={$this->_context}&cid={$this->_contactID}"
      ));
    }

    //store contribution ID if not yet set (on create)
    if (empty($this->_id) && !empty($contribution->id)) {
      $this->_id = $contribution->id;
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
   * @throws \CRM_Core_Exception
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  protected function processCreditCard($submittedValues, $lineItem, $contactID) {
    $isTest = ($this->_mode == 'test') ? 1 : 0;
    // CRM-12680 set $_lineItem if its not set
    // @todo - I don't believe this would ever BE set. I can't find anywhere in the code.
    // It would be better to pass line item out to functions than $this->_lineItem as
    // we don't know what is being changed where.
    if (empty($this->_lineItem) && !empty($lineItem)) {
      $this->_lineItem = $lineItem;
    }

    $this->_paymentObject = Civi\Payment\System::singleton()->getById($submittedValues['payment_processor_id']);
    $this->_paymentProcessor = $this->_paymentObject->getPaymentProcessor();

    // Set source if not set
    if (empty($submittedValues['source'])) {
      $userID = CRM_Core_Session::singleton()->get('userID');
      $userSortName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $userID,
        'sort_name'
      );
      $submittedValues['source'] = ts('Submit Credit Card Payment by: %1', array(1 => $userSortName));
    }

    $params = $submittedValues;
    $this->_params = array_merge($this->_params, $submittedValues);

    // Mapping requiring documentation.
    $this->_params['payment_processor'] = $submittedValues['payment_processor_id'];

    $now = date('YmdHis');

    // we need to retrieve email address
    if ($this->_context == 'standalone' && !empty($submittedValues['is_email_receipt'])) {
      list($this->userDisplayName,
        $this->userEmail
        ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);
      $this->assign('displayName', $this->userDisplayName);
    }

    $this->_contributorEmail = $this->userEmail;
    $this->_contributorContactID = $contactID;
    $this->processBillingAddress();
    if (!empty($params['source'])) {
      unset($params['source']);
    }

    $this->_params['amount'] = $this->_params['total_amount'];
    // @todo - stop setting amount level in this function & call the CRM_Price_BAO_PriceSet::getAmountLevel
    // function to get correct amount level consistently. Remove setting of the amount level in
    // CRM_Price_BAO_PriceSet::processAmount. Extend the unit tests in CRM_Price_BAO_PriceSetTest
    // to cover all variants.
    $this->_params['amount_level'] = 0;
    $this->_params['description'] = ts("Contribution submitted by a staff person using contributor's credit card");
    $this->_params['currencyID'] = CRM_Utils_Array::value('currency',
      $this->_params,
      CRM_Core_Config::singleton()->defaultCurrency
    );

    if (!empty($this->_params['receive_date'])) {
      $this->_params['receive_date'] = CRM_Utils_Date::processDate($this->_params['receive_date'], $this->_params['receive_date_time']);
    }

    $this->_params['pcp_display_in_roll'] = CRM_Utils_Array::value('pcp_display_in_roll', $params);
    $this->_params['pcp_roll_nickname'] = CRM_Utils_Array::value('pcp_roll_nickname', $params);
    $this->_params['pcp_personal_note'] = CRM_Utils_Array::value('pcp_personal_note', $params);

    //Add common data to formatted params
    CRM_Contribute_Form_AdditionalInfo::postProcessCommon($params, $this->_params, $this);

    if (empty($this->_params['invoice_id'])) {
      $this->_params['invoiceID'] = md5(uniqid(rand(), TRUE));
    }
    else {
      $this->_params['invoiceID'] = $this->_params['invoice_id'];
    }

    // At this point we've created a contact and stored its address etc
    // all the payment processors expect the name and address to be in the
    // so we copy stuff over to first_name etc.
    $paymentParams = $this->_params;
    $paymentParams['contactID'] = $contactID;
    CRM_Core_Payment_Form::mapParams($this->_bltID, $this->_params, $paymentParams, TRUE);

    $financialType = new CRM_Financial_DAO_FinancialType();
    $financialType->id = $params['financial_type_id'];
    $financialType->find(TRUE);

    // Add some financial type details to the params list
    // if folks need to use it.
    $paymentParams['contributionType_name'] = $this->_params['contributionType_name'] = $financialType->name;
    $paymentParams['contributionPageID'] = NULL;

    if (!empty($this->_params['is_email_receipt'])) {
      $paymentParams['email'] = $this->userEmail;
      $paymentParams['is_email_receipt'] = 1;
    }
    else {
      $paymentParams['is_email_receipt'] = 0;
      $this->_params['is_email_receipt'] = 0;
    }
    if (!empty($this->_params['receive_date'])) {
      $paymentParams['receive_date'] = $this->_params['receive_date'];
    }

    $this->_params['receive_date'] = $now;

    if (!empty($this->_params['is_email_receipt'])) {
      $this->_params['receipt_date'] = $now;
    }
    else {
      $this->_params['receipt_date'] = CRM_Utils_Date::processDate($this->_params['receipt_date'],
        $params['receipt_date_time'], TRUE
      );
    }

    $this->set('params', $this->_params);

    $this->assign('receive_date', $this->_params['receive_date']);

    // Result has all the stuff we need
    // lets archive it to a financial transaction
    if ($financialType->is_deductible) {
      $this->assign('is_deductible', TRUE);
      $this->set('is_deductible', TRUE);
    }
    $contributionParams = array(
      'id' => CRM_Utils_Array::value('contribution_id', $this->_params),
      'contact_id' => $contactID,
      'line_item' => $lineItem,
      'is_test' => $isTest,
      'campaign_id' => CRM_Utils_Array::value('campaign_id', $this->_params),
      'contribution_page_id' => CRM_Utils_Array::value('contribution_page_id', $this->_params),
      'source' => CRM_Utils_Array::value('source', $paymentParams, CRM_Utils_Array::value('description', $paymentParams)),
      'thankyou_date' => CRM_Utils_Array::value('thankyou_date', $this->_params),
    );

    if (empty($paymentParams['is_pay_later'])) {
      // @todo look up payment_instrument_id on payment processor table.
      $contributionParams['payment_instrument_id'] = 1;
    }

    $contribution = CRM_Contribute_Form_Contribution_Confirm::processFormContribution($this,
      $this->_params,
      NULL,
      $contributionParams,
      $financialType,
      FALSE,
      $this->_bltID,
      CRM_Utils_Array::value('is_recur', $this->_params)
    );

    $paymentParams['contributionID'] = $contribution->id;
    $paymentParams['contributionTypeID'] = $contribution->financial_type_id;
    $paymentParams['contributionPageID'] = $contribution->contribution_page_id;
    $paymentParams['contributionRecurID'] = $contribution->contribution_recur_id;

    if ($paymentParams['amount'] > 0.0) {
      // force a re-get of the payment processor in case the form changed it, CRM-7179
      // NOTE - I expect this is obsolete.
      $payment = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
      try {
        $statuses = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id');
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
        if ($result['payment_status_id'] == array_search('Completed', $statuses)) {
          try {
            civicrm_api3('contribution', 'completetransaction', array(
              'id' => $contribution->id,
              'trxn_id' => $result['trxn_id'],
              'payment_processor_id' => $this->_paymentProcessor['id'],
              'is_transactional' => FALSE,
              'fee_amount' => CRM_Utils_Array::value('fee_amount', $result),
            ));
            // This has now been set to 1 in the DB - declare it here also
            $contribution->contribution_status_id = 1;
          }
          catch (CiviCRM_API3_Exception $e) {
            if ($e->getErrorCode() != 'contribution_completed') {
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
      $this->_params['trxn_id'] = CRM_Utils_Array::value('trxn_id', $result);
      $this->_params['contact_id'] = $contactID;
      $this->_params['contribution_id'] = $contribution->id;
      if (CRM_Contribute_Form_AdditionalInfo::emailReceipt($this, $this->_params, TRUE)) {
        $this->statusMessage[] = ts('A receipt has been emailed to the contributor.');
      }
    }

    return $contribution;
  }

  /**
   * Generate the data to construct a snippet based pane.
   *
   * This form also assigns the showAdditionalInfo var based on historical code.
   * This appears to mean 'there is a pane to show'.
   *
   * @param string $type
   *   Type of Pane - this is generally used to determine the function name used to build it
   *   - e.g CreditCard, AdditionalDetail
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

    $open = 'false';
    if ($type == 'CreditCard' ||
      $type == 'DirectDebit'
    ) {
      $open = 'true';
    }

    $pane = array(
      'url' => CRM_Utils_System::url('civicrm/contact/view/contribution', $urlParams),
      'open' => $open,
      'id' => $type,
    );

    // See if we need to include this paneName in the current form.
    if ($this->_formType == $type || !empty($_POST["hidden_{$type}"]) ||
      CRM_Utils_Array::value("hidden_{$type}", $defaults)
    ) {
      $this->assign('showAdditionalInfo', TRUE);
      $pane['open'] = 'true';
    }

    if ($type == 'CreditCard' || $type == 'DirectDebit') {
      // @todo would be good to align tpl name with form name...
      // @todo document why this hidden variable is required.
      $this->add('hidden', 'hidden_' . $type, 1);
      return $pane;
    }
    else {
      $additionalInfoFormFunction = 'build' . $type;
      CRM_Contribute_Form_AdditionalInfo::$additionalInfoFormFunction($this);
      return $pane;
    }
  }

  /**
   * Wrapper for unit testing the post process submit function.
   *
   * (If we expose through api we can get default additions 'for free').
   *
   * @param array $params
   * @param int $action
   * @param string|null $creditCardMode
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function testSubmit($params, $action, $creditCardMode = NULL) {
    $defaults = array(
      'soft_credit_contact_id' => array(),
      'receipt_date' => '',
      'receipt_date_time' => '',
      'cancel_date' => '',
      'cancel_date_time' => '',
      'hidden_Premium' => 1,
    );
    $this->_bltID = 5;
    if (!empty($params['id'])) {
      $existingContribution = civicrm_api3('contribution', 'getsingle', array(
        'id' => $params['id'],
      ));
      $this->_id = $params['id'];
    }
    else {
      $existingContribution = array();
    }

    $this->_defaults['contribution_status_id'] = CRM_Utils_Array::value('contribution_status_id',
      $existingContribution
    );

    $this->_defaults['total_amount'] = CRM_Utils_Array::value('total_amount',
      $existingContribution
    );

    if ($creditCardMode) {
      $this->_mode = $creditCardMode;
    }

    // Required because processCreditCard calls set method on this.
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $this->controller = new CRM_Core_Controller();

    CRM_Contribute_Form_AdditionalInfo::buildPremium($this);

    $this->_fields = array();
    $this->submit(array_merge($defaults, $params), $action, CRM_Utils_Array::value('pledge_payment_id', $params));

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
   * @return array
   * @throws \Exception
   */
  protected function submit($submittedValues, $action, $pledgePaymentID) {
    $softParams = $softIDs = array();
    $pId = $contribution = $isRelatedId = FALSE;
    $this->_params = $submittedValues;
    $this->beginPostProcess();

    if (!empty($submittedValues['price_set_id']) && $action & CRM_Core_Action::UPDATE) {
      $line = CRM_Price_BAO_LineItem::getLineItems($this->_id, 'contribution');
      $lineID = key($line);
      $priceSetId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', CRM_Utils_Array::value('price_field_id', $line[$lineID]), 'price_set_id');
      $quickConfig = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $priceSetId, 'is_quick_config');
      // Why do we do this? Seems like a like a wrapper for old functionality - but single line price sets & quick
      // config should be treated the same.
      if ($quickConfig) {
        CRM_Price_BAO_LineItem::deleteLineItems($this->_id, 'civicrm_contribution');
      }
    }

    // Process price set and get total amount and line items.
    $lineItem = array();
    $priceSetId = CRM_Utils_Array::value('price_set_id', $submittedValues);
    if (empty($priceSetId) && !$this->_id) {
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
        $submittedValues, $lineItem[$priceSetId]);
      // Unset tax amount for offline 'is_quick_config' contribution.
      // @todo WHY  - quick config was conceived as a quick way to configure contribution forms.
      // this is an example of 'other' functionality being hung off it.
      if ($this->_priceSet['is_quick_config'] &&
        !array_key_exists($submittedValues['financial_type_id'], CRM_Core_PseudoConstant::getTaxRates())
      ) {
        unset($submittedValues['tax_amount']);
      }
      $submittedValues['total_amount'] = CRM_Utils_Array::value('amount', $submittedValues);
    }
    if ($this->_id) {
      if ($this->_compId) {
        if ($this->_context == 'participant') {
          $pId = $this->_compId;
        }
        elseif ($this->_context == 'membership') {
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
        $participantParams = array(
          'fee_amount' => $submittedValues['total_amount'],
          'id' => $entityID,
        );
        CRM_Event_BAO_Participant::add($participantParams);
        if (empty($this->_lineItems)) {
          $this->_lineItems[] = CRM_Price_BAO_LineItem::getLineItems($entityID, 'participant', 1);
        }
      }
      else {
        $entityTable = 'contribution';
        $entityID = $this->_id;
      }

      $lineItems = CRM_Price_BAO_LineItem::getLineItems($entityID, $entityTable, NULL, TRUE, $isRelatedId);
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
        if (!(CRM_Utils_Array::value('membership', $componentDetails) || CRM_Utils_Array::value('participant', $componentDetails))) {
          if (!($this->_action & CRM_Core_Action::UPDATE && (($this->_defaults['contribution_status_id'] != $submittedValues['contribution_status_id'])))) {
            $lineItems[$itemId]['unit_price'] = $lineItems[$itemId]['line_total'] = CRM_Utils_Rule::cleanMoney(CRM_Utils_Array::value('total_amount', $submittedValues));
          }
        }

        // Update line total and total amount with tax on edit.
        $financialItemsId = CRM_Core_PseudoConstant::getTaxRates();
        if (array_key_exists($submittedValues['financial_type_id'], $financialItemsId)) {
          $lineItems[$itemId]['tax_rate'] = $financialItemsId[$submittedValues['financial_type_id']];
        }
        else {
          $lineItems[$itemId]['tax_rate'] = $lineItems[$itemId]['tax_amount'] = "";
          $submittedValues['tax_amount'] = 'null';
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
    if ($isQuickConfig && !empty($submittedValues['financial_type_id']) && CRM_Utils_Array::value($this->_priceSetId, $lineItem)
    ) {
      foreach ($lineItem[$this->_priceSetId] as &$values) {
        $values['financial_type_id'] = $submittedValues['financial_type_id'];
      }
    }

    if (!isset($submittedValues['total_amount'])) {
      $submittedValues['total_amount'] = CRM_Utils_Array::value('total_amount', $this->_values);
    }
    $this->assign('lineItem', !empty($lineItem) && !$isQuickConfig ? $lineItem : FALSE);

    $isEmpty = array_keys(array_flip($submittedValues['soft_credit_contact_id']));
    if ($this->_id && count($isEmpty) == 1 && key($isEmpty) == NULL) {
      //Delete existing soft credit records if soft credit list is empty on update
      CRM_Contribute_BAO_ContributionSoft::del(array('contribution_id' => $this->_id, 'pcp_id' => 0));
    }

    // set the contact, when contact is selected
    if (!empty($submittedValues['contact_id'])) {
      $this->_contactID = $submittedValues['contact_id'];
    }

    $formValues = $submittedValues;

    // Credit Card Contribution.
    if ($this->_mode) {
      $paramsSetByPaymentProcessingSubsystem = array(
        'trxn_id',
        'payment_instrument_id',
        'contribution_status_id',
        'cancel_date',
        'cancel_reason',
      );
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

      $params = $ids = array();

      $params['contact_id'] = $this->_contactID;
      $params['currency'] = $this->getCurrency($submittedValues);

      //format soft-credit/pcp param first
      CRM_Contribute_BAO_ContributionSoft::formatSoftCreditParams($submittedValues, $this);
      $params = array_merge($params, $submittedValues);

      $fields = array(
        'financial_type_id',
        'contribution_status_id',
        'payment_instrument_id',
        'cancel_reason',
        'source',
        'check_number',
      );
      foreach ($fields as $f) {
        $params[$f] = CRM_Utils_Array::value($f, $formValues);
      }

      // CRM-5740 if priceset is used, no need to cleanup money.
      if ($priceSetId) {
        $params['skipCleanMoney'] = 1;
      }
      $params['revenue_recognition_date'] = NULL;
      if (!empty($formValues['revenue_recognition_date'])
        && count(array_filter($formValues['revenue_recognition_date'])) == 2
      ) {
        $params['revenue_recognition_date'] = CRM_Utils_Date::processDate(
          '01-' . implode('-', $formValues['revenue_recognition_date'])
        );
      }
      $dates = array(
        'receive_date',
        'receipt_date',
        'cancel_date',
      );

      foreach ($dates as $d) {
        if (isset($formValues[$d])) {
          $params[$d] = CRM_Utils_Date::processDate($formValues[$d], CRM_Utils_Array::value($d . '_time', $formValues), TRUE);
        }
      }

      if (!empty($formValues['is_email_receipt'])) {
        $params['receipt_date'] = date("Y-m-d");
      }

      if (CRM_Contribute_BAO_Contribution::isContributionStatusNegative($params['contribution_status_id'])
      ) {
        if (CRM_Utils_System::isNull(CRM_Utils_Array::value('cancel_date', $params))) {
          $params['cancel_date'] = date('YmdHis');
        }
      }
      else {
        $params['cancel_date'] = $params['cancel_reason'] = 'null';
      }

      // Set is_pay_later flag for back-office offline Pending status contributions CRM-8996
      // else if contribution_status is changed to Completed is_pay_later flag is changed to 0, CRM-15041
      if ($params['contribution_status_id'] == CRM_Core_OptionGroup::getValue('contribution_status', 'Pending', 'name')) {
        $params['is_pay_later'] = 1;
      }
      elseif ($params['contribution_status_id'] == CRM_Core_OptionGroup::getValue('contribution_status', 'Completed', 'name')) {
        $params['is_pay_later'] = 0;
      }

      $ids['contribution'] = $params['id'] = $this->_id;

      // Add Additional common information to formatted params.
      CRM_Contribute_Form_AdditionalInfo::postProcessCommon($formValues, $params, $this);
      if ($pId) {
        $params['contribution_mode'] = 'participant';
        $params['participant_id'] = $pId;
        $params['skipLineItem'] = 1;
      }
      elseif ($isRelatedId) {
        $params['contribution_mode'] = 'membership';
      }
      $params['line_item'] = $lineItem;
      $params['payment_processor_id'] = $params['payment_processor'] = CRM_Utils_Array::value('id', $this->_paymentProcessor);
      if (isset($submittedValues['tax_amount'])) {
        $params['tax_amount'] = $submittedValues['tax_amount'];
      }
      //create contribution.
      if ($isQuickConfig) {
        $params['is_quick_config'] = 1;
      }
      $params['non_deductible_amount'] = $this->calculateNonDeductibleAmount($params, $formValues);

      // we are already handling note below, so to avoid duplicate notes against $contribution
      if (!empty($params['note']) && !empty($submittedValues['note'])) {
        unset($params['note']);
      }
      $contribution = CRM_Contribute_BAO_Contribution::create($params, $ids);

      // process associated membership / participant, CRM-4395
      if ($contribution->id && $action & CRM_Core_Action::UPDATE) {
        $this->statusMessage[] = CRM_Contribute_BAO_Contribution::transitionComponentWithReturnMessage($contribution->id,
          $contribution->contribution_status_id,
          CRM_Utils_Array::value('contribution_status_id',
            $this->_values
          ),
          $contribution->receive_date
        );
      }

      array_unshift($this->statusMessage, ts('The contribution record has been saved.'));

      $this->invoicingPostProcessHook($submittedValues, $action, $lineItem);

      //send receipt mail.
      if ($contribution->id && !empty($formValues['is_email_receipt'])) {
        $formValues['contact_id'] = $this->_contactID;
        $formValues['contribution_id'] = $contribution->id;

        $formValues += CRM_Contribute_BAO_ContributionSoft::getSoftContribution($contribution->id);

        // to get 'from email id' for send receipt
        $this->fromEmailId = $formValues['from_email_address'];
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

    if ($contribution->id && !empty($submittedValues['note'])) {
      CRM_Contribute_Form_AdditionalInfo::processNote($submittedValues, $this->_contactID, $contribution->id, $this->_noteID);
    }

    CRM_Core_Session::setStatus(implode(' ', $this->statusMessage), $this->statusMessageTitle, 'success');

    CRM_Contribute_BAO_Contribution::updateRelatedPledge(
      $action,
      $pledgePaymentID,
      $contribution->id,
      (CRM_Utils_Array::value('option_type', $formValues) == 2) ? TRUE : FALSE,
      $formValues['total_amount'],
      CRM_Utils_Array::value('total_amount', $this->_defaults),
      $formValues['contribution_status_id'],
      CRM_Utils_Array::value('contribution_status_id', $this->_defaults)
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
  protected function invoicingPostProcessHook($submittedValues, $action, $lineItem) {

    $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
    if (!CRM_Utils_Array::value('invoicing', $invoiceSettings)) {
      return;
    }
    $taxRate = array();
    $getTaxDetails = FALSE;
    if ($action & CRM_Core_Action::ADD) {
      $line = $lineItem;
    }
    elseif ($action & CRM_Core_Action::UPDATE) {
      $line = $this->_lineItems;
    }
    foreach ($line as $key => $value) {
      foreach ($value as $v) {
        if (isset($taxRate[(string) CRM_Utils_Array::value('tax_rate', $v)])) {
          $taxRate[(string) $v['tax_rate']] = $taxRate[(string) $v['tax_rate']] + CRM_Utils_Array::value('tax_amount', $v);
        }
        else {
          if (isset($v['tax_rate'])) {
            $taxRate[(string) $v['tax_rate']] = CRM_Utils_Array::value('tax_amount', $v);
            $getTaxDetails = TRUE;
          }
        }
      }
    }

    if ($action & CRM_Core_Action::UPDATE) {
      if (isset($submittedValues['tax_amount'])) {
        $totalTaxAmount = $submittedValues['tax_amount'];
      }
      else {
        $totalTaxAmount = $this->_values['tax_amount'];
      }
      $this->assign('totalTaxAmount', $totalTaxAmount);
      $this->assign('dataArray', $taxRate);
    }
    else {
      if (!empty($submittedValues['price_set_id'])) {
        $this->assign('totalTaxAmount', $submittedValues['tax_amount']);
        $this->assign('getTaxDetails', $getTaxDetails);
        $this->assign('dataArray', $taxRate);
        $this->assign('taxTerm', CRM_Utils_Array::value('tax_term', $invoiceSettings));
      }
      else {
        $this->assign('totalTaxAmount', CRM_Utils_Array::value('tax_amount', $submittedValues));
      }
    }
  }

  /**
   * Calculate non deductible amount.
   *
   * CRM-11956
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

}
