<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
   * The id of the pledge that we are processing.
   *
   * @var int
   */
  public $_pledgeID;

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

  /**
   * @todo what on earth does cdType stand for????
   * @var
   */
  protected $_cdType;
  public $_honoreeProfileType;

  /**
   * Array of billing panes to be displayed by billingBlock.tpl.
   * Currently this is likely to look like
   * array('Credit Card' => ts('Credit Card') or
   * array('Direct Debit => ts('Direct Debit')
   * @todo billing details (address stuff) to be added when we stop hard coding the panes in billingBlock.tpl
   *
   * @var array
   */
  public $billingPane = array();

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
   * Form defaults
   * @todo can we define this a as protected? can we define higher up the chain
   * @var array
   */
  public $_defaults;

  /**
   * User display name
   *
   * @var string
   */
  public $userDisplayName;

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {

    // Check permission for action.
    if (!CRM_Core_Permission::checkActionPermission('CiviContribute', $this->_action)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }

    // @todo - if anyone ever figures out what this _cdType subroutine is about
    // (or even if it still applies) please add comments!!!!!!!!!!
    $this->_cdType = CRM_Utils_Array::value('type', $_GET);
    $this->assign('cdType', FALSE);
    if ($this->_cdType) {
      $this->assign('cdType', TRUE);
      CRM_Custom_Form_CustomData::preProcess($this);
      return;
    }

    $this->_formType = CRM_Utils_Array::value('formType', $_GET);

    // Get price set id.
    $this->_priceSetId = CRM_Utils_Array::value('priceSetId', $_GET);
    $this->set('priceSetId', $this->_priceSetId);
    $this->assign('priceSetId', $this->_priceSetId);

    // Get the pledge payment id
    $this->_ppID = CRM_Utils_Request::retrieve('ppid', 'Positive', $this);

    // Get the contact id
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    // Get the action.
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
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
    if ($this->_mode) {
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
    if ($this->_cdType) {
      // @todo document when this function would be called in this way
      // (and whether it is valid or an overloading of this form).
      return CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

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
    //@todo document the purpose of cdType (if still in use)
    if ($this->_cdType) {
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      return;
    }
    $allPanes = array();
    //tax rate from financialType
    $this->assign('taxRates', json_encode(CRM_Core_PseudoConstant::getTaxRates()));
    $this->assign('currencies', json_encode(CRM_Core_OptionGroup::values('currencies_enabled')));

    // build price set form.
    $buildPriceSet = FALSE;
    $invoiceSettings = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME, 'contribution_invoice_settings');
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

    $showAdditionalInfo = FALSE;

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

    $paneNames = array(
      ts('Additional Details') => 'AdditionalDetail',
    );

    //Add Premium pane only if Premium is exists.
    $dao = new CRM_Contribute_DAO_Product();
    $dao->is_active = 1;

    if ($dao->find(TRUE)) {
      $paneNames[ts('Premium Information')] = 'Premium';
    }

    $billingPanes = array();
    if ($this->_mode) {
      if (CRM_Core_Payment_Form::buildPaymentForm($this, $this->_paymentProcessor, FALSE, TRUE) == TRUE) {
        $buildRecurBlock = TRUE;
        foreach ($this->billingPane as $name => $label) {
          if (!empty($this->billingFieldSets[$name]['fields'])) {
            // @todo reduce variation so we don't have to convert 'credit_card' to 'CreditCard'
            $billingPanes[$label] = $this->generatePane(CRM_Utils_String::convertStringToCamel($name), $defaults);
          }
        }
      }
    }

    foreach ($paneNames as $name => $type) {
      $allPanes[$name] = $this->generatePane($type, $defaults);
    }
    if (empty($this->_recurPaymentProcessors)) {
      $buildRecurBlock = FALSE;
    }
    if ($this->_ppID) {
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
    }
    $this->assign('buildRecurBlock', $buildRecurBlock);
    $qfKey = $this->controller->_key;
    $this->assign('qfKey', $qfKey);
    $this->assign('billingPanes', $billingPanes);
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

    $financialType = $this->add('select', 'financial_type_id',
      ts('Financial Type'),
      array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::financialType(),
      TRUE,
      array('onChange' => "CRM.buildCustomData( 'Contribution', this.value );")
    );
    $paymentInstrument = FALSE;
    if (!$this->_mode) {
      $paymentInstrument = $this->add('select', 'payment_instrument_id',
        ts('Paid By'),
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

    if ($this->_id) {
      $contributionStatus = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $this->_id, 'contribution_status_id');
      $name = CRM_Utils_Array::value($contributionStatus, $statusName);
      switch ($name) {
        case 'Completed':
        case 'Cancelled':
        case 'Refunded':
          unset($status[CRM_Utils_Array::key('In Progress', $statusName)]);
          unset($status[CRM_Utils_Array::key('Pending', $statusName)]);
          unset($status[CRM_Utils_Array::key('Failed', $statusName)]);
          break;

        case 'Pending':
        case 'In Progress':
          unset($status[CRM_Utils_Array::key('Refunded', $statusName)]);
          break;

        case 'Failed':
          foreach (array(
                     'Pending',
                     'Refunded',
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
    }

    $this->add('select', 'contribution_status_id',
      ts('Contribution Status'),
      $status,
      FALSE
    );

    // add various dates
    $this->addDateTime('receive_date', ts('Received'), FALSE, array('formatType' => 'activityDateTime'));

    if ($this->_online) {
      $this->assign('hideCalender', TRUE);
    }
    $checkNumber = $this->add('text', 'check_number', ts('Check Number'), $attributes['check_number']);

    $this->addDateTime('receipt_date', ts('Receipt Date'), FALSE, array('formatType' => 'activityDateTime'));
    $this->addDateTime('cancel_date', ts('Cancelled / Refunded Date'), FALSE, array('formatType' => 'activityDateTime'));

    $this->add('textarea', 'cancel_reason', ts('Cancellation / Refund Reason'), $attributes['cancel_reason']);

    $recurJs = NULL;
    if ($buildRecurBlock) {
      $recurJs = array('onChange' => "buildRecurBlock( this.value ); return false;");
    }
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
      $currencyFreeze = FALSE;
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

    CRM_Contribute_Form_SoftCredit::buildQuickForm($this);

    $js = NULL;
    if (!$this->_mode) {
      $js = array('onclick' => "return verify( );");
    }

    $mailingInfo = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
      'mailing_backend'
    );
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
        CRM_Core_Payment_Form::validatePaymentInstrument($fields['payment_processor_id'], $fields, $errors, $self);
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
      if (CRM_Utils_Rule::cleanMoney($fields['total_amount']) != CRM_Utils_Rule::cleanMoney($sum)) {
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

    $errors = array_merge($errors, $softErrors);
    return $errors;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $sendReceipt = $pId = $contribution = $isRelatedId = FALSE;
    $softParams = $softIDs = array();

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Contribute_BAO_Contribution::deleteContribution($this->_id);
      CRM_Core_Session::singleton()->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$this->_contactID}&selectedChild=contribute"
      ));
      return;
    }

    // Get the submitted form values.
    $submittedValues = $this->controller->exportValues($this->_name);
    if (!empty($submittedValues['price_set_id']) && $this->_action & CRM_Core_Action::UPDATE) {
      $line = CRM_Price_BAO_LineItem::getLineItems($this->_id, 'contribution');
      $lineID = key($line);
      $priceSetId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', CRM_Utils_Array::value('price_field_id', $line[$lineID]), 'price_set_id');
      $quickConfig = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $priceSetId, 'is_quick_config');
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

    if ($priceSetId) {
      CRM_Price_BAO_PriceSet::processAmount($this->_priceSet['fields'],
        $submittedValues, $lineItem[$priceSetId]);

      // Unset tax amount for offline 'is_quick_config' contribution.
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
    }
    if (!$priceSetId && !empty($submittedValues['total_amount']) && $this->_id) {
      // CRM-10117 update the line items for participants.
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

    if (!empty($submittedValues['pcp_made_through_id'])) {
      $pcp = array();
      $fields = array(
        'pcp_made_through_id',
        'pcp_display_in_roll',
        'pcp_roll_nickname',
        'pcp_personal_note',
      );
      foreach ($fields as $f) {
        $pcp[$f] = CRM_Utils_Array::value($f, $submittedValues);
      }
    }

    $isEmpty = array_keys(array_flip($submittedValues['soft_credit_contact_id']));
    if ($this->_id && count($isEmpty) == 1 && key($isEmpty) == NULL) {
      //Delete existing soft credit records if soft credit list is empty on update
      CRM_Contribute_BAO_ContributionSoft::del(array('contribution_id' => $this->_id, 'pcp_id' => 0));
    }
    else {
      //build soft credit params
      foreach ($submittedValues['soft_credit_contact_id'] as $key => $val) {
        if ($val && $submittedValues['soft_credit_amount'][$key]) {
          $softParams[$key]['contact_id'] = $val;
          $softParams[$key]['amount'] = CRM_Utils_Rule::cleanMoney($submittedValues['soft_credit_amount'][$key]);
          $softParams[$key]['soft_credit_type_id'] = $submittedValues['soft_credit_type'][$key];
          if (!empty($submittedValues['soft_credit_id'][$key])) {
            $softIDs[] = $softParams[$key]['id'] = $submittedValues['soft_credit_id'][$key];
          }
        }
      }
    }

    // set the contact, when contact is selected
    if (!empty($submittedValues['contact_id'])) {
      $this->_contactID = $submittedValues['contact_id'];
    }

    // Credit Card Contribution.
    if ($this->_mode) {
      $this->processCreditCard($submittedValues, $lineItem);
    }
    else {
      // Offline Contribution.
      $submittedValues = $this->unsetCreditCardFields($submittedValues);

      // get the required field value only.
      $formValues = $submittedValues;
      $params = $ids = array();

      $params['contact_id'] = $this->_contactID;

      $params['currency'] = $this->getCurrency($submittedValues);

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

      $params['pcp'] = !empty($pcp) ? $pcp : NULL;
      $params['soft_credit'] = !empty($softParams) ? $softParams : array();
      $params['soft_credit_ids'] = !empty($softIDs) ? $softIDs : array();

      // CRM-5740 if priceset is used, no need to cleanup money.
      if ($priceSetId) {
        $params['skipCleanMoney'] = 1;
      }

      $dates = array(
        'receive_date',
        'receipt_date',
        'cancel_date',
      );

      foreach ($dates as $d) {
        $params[$d] = CRM_Utils_Date::processDate($formValues[$d], $formValues[$d . '_time'], TRUE);
      }

      if (!empty($formValues['is_email_receipt'])) {
        $params['receipt_date'] = date("Y-m-d");
      }

      if ($params['contribution_status_id'] == CRM_Core_OptionGroup::getValue('contribution_status', 'Cancelled', 'name')
        || $params['contribution_status_id'] == CRM_Core_OptionGroup::getValue('contribution_status', 'Refunded', 'name')
      ) {
        if (CRM_Utils_System::isNull(CRM_Utils_Array::value('cancel_date', $params))) {
          $params['cancel_date'] = date('Y-m-d');
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

      // CRM-11956
      // if non_deductible_amount exists i.e. Additional Details field set was opened [and staff typed something] -
      // if non_deductible_amount does NOT exist - then calculate it depending on:
      // $ContributionType->is_deductible and whether there is a product (premium).
      if (empty($params['non_deductible_amount'])) {
        $contributionType = new CRM_Financial_DAO_FinancialType();
        $contributionType->id = $params['financial_type_id'];
        if (!$contributionType->find(TRUE)) {
          CRM_Core_Error::fatal('Could not find a system table');
        }
        if ($contributionType->is_deductible) {

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
              $params['non_deductible_amount'] = $params['total_amount'];
            }
            // product value does NOT exceed contribution amount
            else {
              $params['non_deductible_amount'] = $productDAO->price;
            }
          }
          // contribution is deductible - but there is no product
          else {
            $params['non_deductible_amount'] = '0.00';
          }
        }
        // contribution is NOT deductible
        else {
          $params['non_deductible_amount'] = $params['total_amount'];
        }
      }

      $contribution = CRM_Contribute_BAO_Contribution::create($params, $ids);

      // process associated membership / participant, CRM-4395
      $relatedComponentStatusMsg = NULL;
      if ($contribution->id && $this->_action & CRM_Core_Action::UPDATE) {
        $relatedComponentStatusMsg = $this->updateRelatedComponent($contribution->id,
          $contribution->contribution_status_id,
          CRM_Utils_Array::value('contribution_status_id',
            $this->_values
          ),
          $contribution->receive_date
        );
      }

      //process  note
      if ($contribution->id && isset($formValues['note'])) {
        CRM_Contribute_Form_AdditionalInfo::processNote($formValues, $this->_contactID, $contribution->id, $this->_noteID);
      }

      //process premium
      if ($contribution->id && isset($formValues['product_name'][0])) {
        CRM_Contribute_Form_AdditionalInfo::processPremium($formValues, $contribution->id,
          $this->_premiumID, $this->_options
        );
      }

      // assign tax calculation for contribution receipts
      $taxRate = array();
      $getTaxDetails = FALSE;
      $invoiceSettings = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME, 'contribution_invoice_settings');
      $invoicing = CRM_Utils_Array::value('invoicing', $invoiceSettings);
      if ($invoicing) {
        if ($this->_action & CRM_Core_Action::ADD) {
          $line = $lineItem;
        }
        elseif ($this->_action & CRM_Core_Action::UPDATE) {
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
      }

      if ($invoicing) {
        if ($this->_action & CRM_Core_Action::UPDATE) {
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

      //send receipt mail.
      if ($contribution->id && !empty($formValues['is_email_receipt'])) {
        $formValues['contact_id'] = $this->_contactID;
        $formValues['contribution_id'] = $contribution->id;

        $formValues += CRM_Contribute_BAO_ContributionSoft::getSoftContribution($contribution->id);

        // to get 'from email id' for send receipt
        $this->fromEmailId = $formValues['from_email_address'];
        $sendReceipt = CRM_Contribute_Form_AdditionalInfo::emailReceipt($this, $formValues);
      }

      $pledgePaymentId = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_PledgePayment',
        $contribution->id,
        'id',
        'contribution_id'
      );

      //update pledge payment status.
      if ((($this->_ppID && $contribution->id) && $this->_action & CRM_Core_Action::ADD) ||
        (($pledgePaymentId) && $this->_action & CRM_Core_Action::UPDATE)
      ) {

        if ($this->_ppID) {
          //store contribution id in payment record.
          CRM_Core_DAO::setFieldValue('CRM_Pledge_DAO_PledgePayment', $this->_ppID, 'contribution_id', $contribution->id);
        }
        else {
          $this->_ppID = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_PledgePayment',
            $contribution->id,
            'id',
            'contribution_id'
          );
          $this->_pledgeID = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_PledgePayment',
            $contribution->id,
            'pledge_id',
            'contribution_id'
          );
        }

        $adjustTotalAmount = FALSE;
        if (CRM_Utils_Array::value('option_type', $formValues) == 2) {
          $adjustTotalAmount = TRUE;
        }

        $updatePledgePaymentStatus = FALSE;
        //do only if either the status or the amount has changed
        if ($this->_action & CRM_Core_Action::ADD) {
          $updatePledgePaymentStatus = TRUE;
        }
        elseif ($this->_action & CRM_Core_Action::UPDATE && (($this->_defaults['contribution_status_id'] != $formValues['contribution_status_id']) ||
            ($this->_defaults['total_amount'] != $formValues['total_amount']))
        ) {
          $updatePledgePaymentStatus = TRUE;
        }

        if ($updatePledgePaymentStatus) {
          CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($this->_pledgeID,
            array($this->_ppID),
            $contribution->contribution_status_id,
            NULL,
            $contribution->total_amount,
            $adjustTotalAmount
          );
        }
      }

      $statusMsg = ts('The contribution record has been saved.');
      if (!empty($formValues['is_email_receipt']) && $sendReceipt) {
        $statusMsg .= ' ' . ts('A receipt has been emailed to the contributor.');
      }

      if ($relatedComponentStatusMsg) {
        $statusMsg .= ' ' . $relatedComponentStatusMsg;
      }

      CRM_Core_Session::setStatus($statusMsg, ts('Saved'), 'success');
      //Offline Contribution ends.
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
   * @throws CRM_Core_Exception
   */
  protected function processCreditCard($submittedValues, $lineItem) {
    $sendReceipt = $contribution = FALSE;

    $unsetParams = array(
      'trxn_id',
      'payment_instrument_id',
      'contribution_status_id',
      'cancel_date',
      'cancel_reason',
    );
    foreach ($unsetParams as $key) {
      if (isset($submittedValues[$key])) {
        unset($submittedValues[$key]);
      }
    }
    $isTest = ($this->_mode == 'test') ? 1 : 0;
    // CRM-12680 set $_lineItem if its not set
    if (empty($this->_lineItem) && !empty($lineItem)) {
      $this->_lineItem = $lineItem;
    }

    //Get the require fields value only.
    $params = $this->_params = $submittedValues;

    $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($this->_params['payment_processor_id'],
      $this->_mode
    );

    // Get the payment processor id as per mode.
    $this->_params['payment_processor'] = $params['payment_processor_id']
      = $this->_params['payment_processor_id'] = $submittedValues['payment_processor_id'] = $this->_paymentProcessor['id'];

    $now = date('YmdHis');
    $fields = array();

    // we need to retrieve email address
    if ($this->_context == 'standalone' && !empty($submittedValues['is_email_receipt'])) {
      list($this->userDisplayName,
        $this->userEmail
        ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);
      $this->assign('displayName', $this->userDisplayName);
    }

    // Set email for primary location.
    $fields['email-Primary'] = 1;
    $params['email-Primary'] = $this->userEmail;

    // now set the values for the billing location.
    foreach (array_keys($this->_fields) as $name) {
      $fields[$name] = 1;
    }

    // also add location name to the array
    $params["address_name-{$this->_bltID}"] = CRM_Utils_Array::value('billing_first_name', $params) . ' ' . CRM_Utils_Array::value('billing_middle_name', $params) . ' ' . CRM_Utils_Array::value('billing_last_name', $params);

    $params["address_name-{$this->_bltID}"] = trim($params["address_name-{$this->_bltID}"]);
    $fields["address_name-{$this->_bltID}"] = 1;
    $ctype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
      $this->_contactID,
      'contact_type'
    );

    $nameFields = array('first_name', 'middle_name', 'last_name');
    foreach ($nameFields as $name) {
      $fields[$name] = 1;
      if (array_key_exists("billing_$name", $params)) {
        $params[$name] = $params["billing_{$name}"];
        $params['preserveDBName'] = TRUE;
      }
    }

    if (!empty($params['source'])) {
      unset($params['source']);
    }
    $contactID = CRM_Contact_BAO_Contact::createProfileContact($params, $fields,
      $this->_contactID,
      NULL, NULL,
      $ctype
    );

    // add all the additional payment params we need
    if (!empty($this->_params["billing_state_province_id-{$this->_bltID}"])) {
      $this->_params["state_province-{$this->_bltID}"] = $this->_params["billing_state_province-{$this->_bltID}"] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($this->_params["billing_state_province_id-{$this->_bltID}"]);
    }
    if (!empty($this->_params["billing_country_id-{$this->_bltID}"])) {
      $this->_params["country-{$this->_bltID}"] = $this->_params["billing_country-{$this->_bltID}"] = CRM_Core_PseudoConstant::countryIsoCode($this->_params["billing_country_id-{$this->_bltID}"]);
    }
    $legacyCreditCardExpiryCheck = FALSE;
    if ($this->_paymentProcessor['payment_type'] & CRM_Core_Payment::PAYMENT_TYPE_CREDIT_CARD && !isset($this->_paymentFields)) {
      $legacyCreditCardExpiryCheck = TRUE;
    }
    if ($legacyCreditCardExpiryCheck || in_array('credit_card_exp_date', array_keys($this->_paymentFields))) {
      $this->_params['year'] = CRM_Core_Payment_Form::getCreditCardExpirationYear($this->_params);
      $this->_params['month'] = CRM_Core_Payment_Form::getCreditCardExpirationMonth($this->_params);
    }
    $this->_params['ip_address'] = CRM_Utils_System::ipAddress();
    $this->_params['amount'] = $this->_params['total_amount'];
    $this->_params['amount_level'] = 0;
    $this->_params['description'] = ts('Office Credit Card contribution');
    $this->_params['currencyID'] = CRM_Utils_Array::value('currency',
      $this->_params,
      CRM_Core_Config::singleton()->defaultCurrency
    );
    $this->_params['payment_action'] = 'Sale';
    if (!empty($this->_params['receive_date'])) {
      $this->_params['receive_date'] = CRM_Utils_Date::processDate($this->_params['receive_date'], $this->_params['receive_date_time']);
    }

    if (!empty($params['soft_credit_to'])) {
      $this->_params['soft_credit_to'] = $params['soft_credit_to'];
      $this->_params['pcp_made_through_id'] = $params['pcp_made_through_id'];
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
    $paymentParams['contactID'] = $this->_contactID;
    CRM_Core_Payment_Form::mapParams($this->_bltID, $this->_params, $paymentParams, TRUE);

    $contributionType = new CRM_Financial_DAO_FinancialType();
    $contributionType->id = $params['financial_type_id'];
    $contributionType->find(TRUE);

    // Add some financial type details to the params list
    // if folks need to use it.
    $paymentParams['contributionType_name'] = $this->_params['contributionType_name'] = $contributionType->name;
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

    // For recurring contribution, create Contribution Record first.
    // Contribution ID, Recurring ID and Contact ID needed
    // When we get a callback from the payment processor, CRM-7115

    if (!empty($paymentParams['is_recur'])) {
      $contribution = CRM_Contribute_Form_Contribution_Confirm::processContribution($this,
        $this->_params,
        NULL,
        $this->_contactID,
        $contributionType,
        TRUE,
        FALSE,
        $isTest,
        $this->_lineItem
      );
      $paymentParams['contributionID'] = $contribution->id;
      $paymentParams['contributionTypeID'] = $contribution->financial_type_id;
      $paymentParams['contributionPageID'] = $contribution->contribution_page_id;
      $paymentParams['contributionRecurID'] = $contribution->contribution_recur_id;
    }
    $result = array();
    if ($paymentParams['amount'] > 0.0) {
      // force a re-get of the payment processor in case the form changed it, CRM-7179
      $payment = CRM_Core_Payment::singleton($this->_mode, $this->_paymentProcessor, $this, TRUE);
      try {
        $result = $payment->doPayment($paymentParams, 'contribute');
      }
      catch (CRM_Core_Exception $e) {
        $message = ts("Payment Processor Error message") . $e->getMessage();
        $this->cleanupDBAfterPaymentFailure($paymentParams, $message);
        // Set the contribution mode.
        $urlParams = "action=add&cid={$this->_contactID}";
        if ($this->_mode) {
          $urlParams .= "&mode={$this->_mode}";
        }
        if (!empty($this->_ppID)) {
          $urlParams .= "&context=pledge&ppid={$this->_ppID}";
        }
        CRM_Core_Error::statusBounce($message, $urlParams, ts('Payment Processor Error'));
      }
    }

    $this->_params = array_merge($this->_params, $result);

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
    $this->assign('trxn_id', $result['trxn_id']);
    $this->assign('receive_date', $this->_params['receive_date']);

    // Result has all the stuff we need
    // lets archive it to a financial transaction
    if ($contributionType->is_deductible) {
      $this->assign('is_deductible', TRUE);
      $this->set('is_deductible', TRUE);
    }

    // Set source if not set
    if (empty($this->_params['source'])) {
      $userID = CRM_Core_Session::singleton()->get('userID');
      $userSortName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $userID,
        'sort_name'
      );
      $this->_params['source'] = ts('Submit Credit Card Payment by: %1', array(1 => $userSortName));
    }

    // Build custom data getFields array
    $customFieldsContributionType = CRM_Core_BAO_CustomField::getFields('Contribution', FALSE, FALSE,
      CRM_Utils_Array::value('financial_type_id', $params)
    );
    $customFields = CRM_Utils_Array::crmArrayMerge($customFieldsContributionType,
      CRM_Core_BAO_CustomField::getFields('Contribution', FALSE, FALSE, NULL, NULL, TRUE)
    );
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
      $customFields,
      $this->_id,
      'Contribution'
    );
    if (empty($paymentParams['is_recur'])) {
      $contribution = CRM_Contribute_Form_Contribution_Confirm::processContribution($this,
        $this->_params,
        $result,
        $this->_contactID,
        $contributionType,
        FALSE, FALSE,
        $isTest,
        $this->_lineItem
      );
    }

    if (!empty($paymentParams['is_recur']) && is_array($result) && CRM_Utils_Array::value('payment_status_id', $result) == 1) {
      try {
        civicrm_api3('contribution', 'completetransaction', array(
          'id' => $contribution->id,
          'trxn_id' => CRM_Utils_Array::value('trxn_id', $result),
          'is_transactional' => FALSE,
        ));
        // This has not been set to 1 in the DB - declare it here also
        $contribution->contribution_status_id = 1;
      }
      catch (CiviCRM_API3_Exception $e) {
        if ($e->getErrorCode() != 'contribution_completed') {
          throw new CRM_Core_Exception('Failed to update contribution in database');
        }
      }
    }
    // Send receipt mail. (if the complete transaction ran it will have sent it - so avoid 2
    // with the elseif. CRM-16926
    elseif ($contribution->id && !empty($this->_params['is_email_receipt'])) {
      $this->_params['trxn_id'] = CRM_Utils_Array::value('trxn_id', $result);
      $this->_params['contact_id'] = $this->_contactID;
      $this->_params['contribution_id'] = $contribution->id;
      $sendReceipt = CRM_Contribute_Form_AdditionalInfo::emailReceipt($this, $this->_params, TRUE);
    }

    //process the note
    if ($contribution->id && isset($params['note'])) {
      CRM_Contribute_Form_AdditionalInfo::processNote($params, $contactID, $contribution->id, NULL);
    }
    //process premium
    if ($contribution->id && isset($params['product_name'][0])) {
      CRM_Contribute_Form_AdditionalInfo::processPremium($params, $contribution->id, NULL, $this->_options);
    }

    //update pledge payment status.
    if ($this->_ppID && $contribution->id) {
      // Store contribution id in payment record.
      CRM_Core_DAO::setFieldValue('CRM_Pledge_DAO_PledgePayment', $this->_ppID, 'contribution_id', $contribution->id);

      CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($this->_pledgeID,
        array($this->_ppID),
        $contribution->contribution_status_id,
        NULL,
        $contribution->total_amount
      );
    }

    if ($contribution->id) {
      $statusMsg = ts('The contribution record has been processed.');
      if (!empty($this->_params['is_email_receipt']) && $sendReceipt) {
        $statusMsg .= ' ' . ts('A receipt has been emailed to the contributor.');
      }
      CRM_Core_Session::setStatus($statusMsg, ts('Complete'), 'success');
    }
  }

  /**
   * Clean up DB after payment fails.
   *
   * This function removes related DB entries. Note that it has been agreed in principle,
   * but not implemented, that contributions should be retained as 'Failed' rather than
   * deleted.
   *
   * @todo it doesn't clean up line items.
   *
   * @param array $paymentParams
   * @param string $message
   */
  public function cleanupDBAfterPaymentFailure($paymentParams, $message) {
    // Make sure to cleanup db for recurring case.
    if (!empty($paymentParams['contributionID'])) {
      CRM_Core_Error::debug_log_message($message .
        "contact id={$this->_contactID} (deleting contribution {$paymentParams['contributionID']}");
      CRM_Contribute_BAO_Contribution::deleteContribution($paymentParams['contributionID']);
    }
    if (!empty($paymentParams['contributionRecurID'])) {
      CRM_Core_Error::debug_log_message($message .
        "contact id={$this->_contactID} (deleting recurring contribution {$paymentParams['contributionRecurID']}");
      CRM_Contribute_BAO_ContributionRecur::deleteRecurContribution($paymentParams['contributionRecurID']);
    }
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

}
