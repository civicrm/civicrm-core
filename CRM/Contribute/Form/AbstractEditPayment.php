<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be usefusul, but   |
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
 * @copyright CiviCRM LLC (c) 2004-2018
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
  public $_mode;

  public $_action;

  public $_bltID;

  public $_fields = array();

  /**
   * @var array current payment processor including a copy of the object in 'object' key
   */
  public $_paymentProcessor;

  /**
   * Available recurring processors.
   *
   * @var array
   */
  public $_recurPaymentProcessors = array();

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
  protected $_paymentProcessors = array();

  /**
   * Instance of the payment processor object.
   *
   * @var CRM_Core_Payment
   */
  protected $_paymentObject;

  /**
   * The id of the contribution that we are processing.
   *
   * @var int
   */
  public $_id;

  /**
   * Entity that $this->_id relates to.
   *
   * If set the contact id is not required in the url.
   *
   * @var string
   */
  protected $entity;

  /**
   * The id of the premium that we are proceessing.
   *
   * @var int
   */
  public $_premiumID = NULL;

  /**
   * @var CRM_Contribute_DAO_ContributionProduct
   */
  public $_productDAO = NULL;

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
   * @var boolean
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
   * Store the financial Type ID
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

  public $_compId;

  /**
   * Store the line items if price set used.
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
   * Array of fields to display on billingBlock.tpl - this is not fully implemented but basically intent is the panes/fieldsets on this page should
   * be all in this array in order like
   *  'credit_card' => array('credit_card_number' ...
   *  'billing_details' => array('first_name' ...
   *
   * such that both the fields and the order can be more easily altered by payment processors & other extensions
   * @var array
   */
  public $billingFieldSets = array();

  /**
   * Monetary fields that may be submitted.
   *
   * These should get a standardised format in the beginPostProcess function.
   *
   * These fields are common to many forms. Some may override this.
   */
  protected $submittableMoneyFields = ['total_amount', 'net_amount', 'non_deductible_amount', 'fee_amount'];

  /**
   * Pre process function with common actions.
   */
  public function preProcess() {
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    if (empty($this->_contactID) && !empty($this->_id) && $this->entity) {
      $this->_contactID = civicrm_api3($this->entity, 'getvalue', array('id' => $this->_id, 'return' => 'contact_id'));
    }
    $this->assign('contactID', $this->_contactID);
    CRM_Core_Resources::singleton()->addVars('coreForm', array('contact_id' => (int) $this->_contactID));
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
    $this->_mode = empty($this->_mode) ? CRM_Utils_Request::retrieve('mode', 'Alphanumeric', $this) : $this->_mode;
    $this->assign('isBackOffice', $this->isBackOffice);
    $this->assignPaymentRelatedVariables();
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
        CRM_Core_Session::setStatus(ts('Please use the <a href="%1">Record Payment</a> form if you have received an additional payment for this Partially paid contribution record.', array(1 => $recordPaymentLink)), ts('Notice'), 'alert');
      }
    }
  }

  /**
   * @param int $id
   * @param $values
   */
  public function buildValuesAndAssignOnline_Note_Type($id, &$values) {
    $ids = array();
    $params = array('id' => $id);
    CRM_Contribute_BAO_Contribution::getValues($params, $values, $ids);

    //Check if this is an online transaction (financial_trxn.payment_processor_id NOT NULL)
    $this->_online = FALSE;
    $fids = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($id);
    if (!empty($fids['financialTrxnId'])) {
      $this->_online = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialTrxn', $fids['financialTrxnId'], 'payment_processor_id');
    }

    // Also don't allow user to update some fields for recurring contributions.
    if (!$this->_online) {
      $this->_online = CRM_Utils_Array::value('contribution_recur_id', $values);
    }

    $this->assign('isOnline', $this->_online ? TRUE : FALSE);

    //to get note id
    $daoNote = new CRM_Core_BAO_Note();
    $daoNote->entity_table = 'civicrm_contribution';
    $daoNote->entity_id = $id;
    if ($daoNote->find(TRUE)) {
      $this->_noteID = $daoNote->id;
      $values['note'] = $daoNote->note;
    }
    $this->_contributionType = $values['financial_type_id'];
  }

  /**
   * @param string $type
   *   Eg 'Contribution'.
   * @param string $subType
   * @param int $entityId
   */
  public function applyCustomData($type, $subType, $entityId) {
    $this->set('type', $type);
    $this->set('subType', $subType);
    $this->set('entityId', $entityId);

    CRM_Custom_Form_CustomData::preProcess($this, NULL, $subType, 1, $type, $entityId);
    CRM_Custom_Form_CustomData::buildQuickForm($this);
    CRM_Custom_Form_CustomData::setDefaultValues($this);
  }

  /**
   * @param int $id
   * @todo - this function is a long way, non standard of saying $dao = new CRM_Contribute_DAO_ContributionProduct(); $dao->id = $id; $dao->find();
   */
  public function assignPremiumProduct($id) {
    $sql = "
SELECT *
FROM   civicrm_contribution_product
WHERE  contribution_id = {$id}
";
    $dao = CRM_Core_DAO::executeQuery($sql,
      CRM_Core_DAO::$_nullArray
    );
    if ($dao->fetch()) {
      $this->_premiumID = $dao->id;
      $this->_productDAO = $dao;
    }
    $dao->free();
  }

  /**
   * @return array
   *   Array of valid processors. The array resembles the DB table but also has 'object' as a key
   * @throws Exception
   */
  public function getValidProcessors() {
    $capabilities = array('BackOffice');
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
    $this->assign('processorSupportsFutureStartDate', CRM_Financial_BAO_PaymentProcessor::hasPaymentProcessorSupporting(array('FutureRecurStartDate')));
    $this->_paymentProcessors = $this->getValidProcessors();
    if (!isset($this->_paymentProcessor['id'])) {
      // if the payment processor isn't set yet (as indicated by the presence of an id,) we'll grab the first one which should be the default
      $this->_paymentProcessor = reset($this->_paymentProcessors);
    }
    if (!$this->_mode) {
      $this->_paymentProcessor = $this->_paymentProcessors[0];
    }
    elseif (empty($this->_paymentProcessors) || array_keys($this->_paymentProcessors) === array(0)) {
      throw new CRM_Core_Exception(ts('You will need to configure the %1 settings for your Payment Processor before you can submit a credit card transactions.', array(1 => $this->_mode)));
    }
    //Assign submitted processor value if it is different from the loaded one.
    if (!empty($this->_submitValues['payment_processor_id'])
      && $this->_paymentProcessor['id'] != $this->_submitValues['payment_processor_id']) {
      $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($this->_submitValues['payment_processor_id']);
    }
    $this->_processors = array();
    foreach ($this->_paymentProcessors as $id => $processor) {
      // @todo review this. The inclusion of this IF was to address test processors being incorrectly loaded.
      // However the function $this->getValidProcessors() is expected to only return the processors relevant
      // to the mode (using the actual id - ie. the id of the test processor for the test processor).
      // for some reason there was a need to filter here per commit history - but this indicates a problem
      // somewhere else.
      if ($processor['is_test'] == ($this->_mode == 'test')) {
        $this->_processors[$id] = ts($processor['name']);
        if (!empty($processor['description'])) {
          $this->_processors[$id] .= ' : ' . ts($processor['description']);
        }
        if ($processor['is_recur']) {
          $this->_recurPaymentProcessors[$id] = $this->_processors[$id];
        }
      }
    }
    // CRM-21002: pass the default payment processor ID whose credit card type icons should be populated first
    CRM_Financial_Form_Payment::addCreditCardJs($this->_paymentProcessor['id']);

    $this->assign('recurringPaymentProcessorIds',
      empty($this->_recurPaymentProcessors) ? '' : implode(',', array_keys($this->_recurPaymentProcessors))
    );

    // this required to show billing block
    // @todo remove this assignment the billing block is now designed to be always included but will not show fieldsets unless those sets of fields are assigned
    $this->assign_by_ref('paymentProcessor', $this->_paymentProcessor);
  }

  /**
   * Get current currency from DB or use default currency.
   *
   * @param array $submittedValues
   *
   * @return string
   */
  public function getCurrency($submittedValues = array()) {
    $config = CRM_Core_Config::singleton();

    $currentCurrency = CRM_Utils_Array::value('currency',
      $this->_values,
      $config->defaultCurrency
    );

    // use submitted currency if present else use current currency
    $result = CRM_Utils_Array::value('currency',
      $submittedValues,
      $currentCurrency
    );
    return $result;
  }

  public function preProcessPledge() {
    //get the payment values associated with given pledge payment id OR check for payments due.
    $this->_pledgeValues = array();
    if ($this->_ppID) {
      $payParams = array('id' => $this->_ppID);

      CRM_Pledge_BAO_PledgePayment::retrieve($payParams, $this->_pledgeValues['pledgePayment']);
      $this->_pledgeID = CRM_Utils_Array::value('pledge_id', $this->_pledgeValues['pledgePayment']);
      $paymentStatusID = CRM_Utils_Array::value('status_id', $this->_pledgeValues['pledgePayment']);
      $this->_id = CRM_Utils_Array::value('contribution_id', $this->_pledgeValues['pledgePayment']);

      //get all status
      $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
      if (!($paymentStatusID == array_search('Pending', $allStatus) || $paymentStatusID == array_search('Overdue', $allStatus))) {
        CRM_Core_Error::fatal(ts("Pledge payment status should be 'Pending' or  'Overdue'."));
      }

      //get the pledge values associated with given pledge payment.

      $ids = array();
      $pledgeParams = array('id' => $this->_pledgeID);
      CRM_Pledge_BAO_Pledge::getValues($pledgeParams, $this->_pledgeValues, $ids);
      $this->assign('ppID', $this->_ppID);
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
            CRM_Core_Session::setStatus(ts('This contact has pending or overdue pledge payments. <a href="%1">Click here to view their Pledges tab</a> and verify whether this contribution should be applied as a pledge payment.', array(1 => $pledgeTab)), ts('Notice'), 'alert');
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
            CRM_Core_Session::setStatus(ts('This contact has a pending or overdue pledge payment of %2 which is scheduled for %3. <a href="%1">Click here to enter a pledge payment</a>.', array(
              1 => $ppUrl,
              2 => $ppAmountDue,
              3 => $ppSchedDate,
            )), ts('Notice'), 'alert');
          }
        }
      }
    }
  }

  /**
   * @param array $submittedValues
   *
   * @return mixed
   */
  public function unsetCreditCardFields($submittedValues) {
    //Offline Contribution.
    $unsetParams = array(
      'payment_processor_id',
      "email-{$this->_bltID}",
      'hidden_buildCreditCard',
      'hidden_buildDirectDebit',
      'billing_first_name',
      'billing_middle_name',
      'billing_last_name',
      'street_address-5',
      "city-{$this->_bltID}",
      "state_province_id-{$this->_bltID}",
      "postal_code-{$this->_bltID}",
      "country_id-{$this->_bltID}",
      'credit_card_number',
      'cvv2',
      'credit_card_exp_date',
      'credit_card_type',
    );
    foreach ($unsetParams as $key) {
      if (isset($submittedValues[$key])) {
        unset($submittedValues[$key]);
      }
    }
    return $submittedValues;
  }

  /**
   * Common block for setting up the parts of a form that relate to credit / debit card
   * @throws Exception
   */
  protected function assignPaymentRelatedVariables() {
    try {
      if ($this->_contactID) {
        list($this->userDisplayName, $this->userEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);
        $this->assign('displayName', $this->userDisplayName);
      }
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
      if (in_array('credit_card_exp_date', array_keys($this->_params))) {
        $this->_params['year'] = CRM_Core_Payment_Form::getCreditCardExpirationYear($this->_params);
        $this->_params['month'] = CRM_Core_Payment_Form::getCreditCardExpirationMonth($this->_params);
      }
      $this->assign('credit_card_exp_date', CRM_Utils_Date::mysqlToIso(CRM_Utils_Date::format($this->_params['credit_card_exp_date'])));
      $this->assign('credit_card_number',
        CRM_Utils_System::mungeCreditCard($this->_params['credit_card_number'])
      );
      $this->assign('credit_card_type', CRM_Utils_Array::value('credit_card_type', $this->_params));
    }
    $this->_params['ip_address'] = CRM_Utils_System::ipAddress();

    self::formatCreditCardDetails($this->_params);
    foreach ($this->submittableMoneyFields as $moneyField) {
      if (isset($this->_params[$moneyField])) {
        $this->_params[$moneyField] = CRM_Utils_Rule::cleanMoney($this->_params[$moneyField]);
      }
    }
  }

  /**
   * Format credit card details like:
   *  1. Retrieve last 4 digit from credit card number as pan_truncation
   *  2. Retrieve credit card type id from name
   *
   * @param array $params
   *
   * @return void
   */
  public static function formatCreditCardDetails(&$params) {
    if (in_array('credit_card_type', array_keys($params))) {
      $params['card_type_id'] = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_FinancialTrxn', 'card_type_id', $params['credit_card_type']);
    }
    if (!empty($params['credit_card_number']) && empty($params['pan_truncation'])) {
      $params['pan_truncation'] = substr($params['credit_card_number'], -4);
    }
  }

  /**
   * Add the billing address to the contact who paid.
   *
   * Note that this function works based on the presence or otherwise of billing fields & can be called regardless of
   * whether they are 'expected' (due to assumptions about the payment processor type or the setting to collect billing
   * for pay later.
   */
  protected function processBillingAddress() {
    $fields = array();

    $fields['email-Primary'] = 1;
    $this->_params['email-5'] = $this->_params['email-Primary'] = $this->_contributorEmail;
    // now set the values for the billing location.
    foreach (array_keys($this->_fields) as $name) {
      $fields[$name] = 1;
    }

    $fields["address_name-{$this->_bltID}"] = 1;

    //ensure we don't over-write the payer's email with the member's email
    if ($this->_contributorContactID == $this->_contactID) {
      $fields["email-{$this->_bltID}"] = 1;
    }

    list($hasBillingField, $addressParams) = CRM_Contribute_BAO_Contribution::getPaymentProcessorReadyAddressParams($this->_params, $this->_bltID);
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
      CRM_Contact_BAO_Contact::createProfileContact($addressParams, $fields,
        $this->_contributorContactID, NULL, NULL,
        CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactID, 'contact_type')
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
    if (empty($defaults["billing_country_id-{$this->_bltID}"])) {
      $defaults["billing_country_id-{$this->_bltID}"] = $config->defaultContactCountry;
    }

    if (empty($defaults["billing_state_province_id-{$this->_bltID}"])) {
      $defaults["billing_state_province_id-{$this->_bltID}"] = $config->defaultContactStateProvince;
    }

    $billingDefaults = $this->getProfileDefaults('Billing', $this->_contactID);
    return array_merge($defaults, $billingDefaults);
  }

  /**
   * Get the default payment instrument id.
   *
   * @return int
   */
  protected function getDefaultPaymentInstrumentId() {
    $paymentInstrumentID = CRM_Utils_Request::retrieve('payment_instrument_id', 'Integer');
    if ($paymentInstrumentID) {
      return $paymentInstrumentID;
    }
    return key(CRM_Core_OptionGroup::values('payment_instrument', FALSE, FALSE, FALSE, 'AND is_default = 1'));
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
    $js = ($isBuildRecurBlock ? array('onChange' => "buildRecurBlock( this.value ); return false;") : NULL);
    if ($isBuildAutoRenewBlock) {
      $js = array('onChange' => "buildAutoRenew( null, this.value, '{$this->_mode}');");
    }
    $element = $this->add('select',
      'payment_processor_id',
      ts('Payment Processor'),
      array_diff_key($this->_processors, array(0 => 1)),
      $isRequired,
      $js
    );
    // The concept of _online is not really explained & the code is old
    // @todo figure out & document.
    if ($this->_online) {
      $element->freeze();
    }
  }


  /**
   * Assign the values to build the payment info block.
   *
   * @return string $title
   *   Block title.
   */
  protected function assignPaymentInfoBlock() {
    $paymentInfo = CRM_Contribute_BAO_Contribution::getPaymentInfo($this->_id, $this->_component, TRUE);
    $title = ts('View Payment');
    if (!empty($this->_component) && $this->_component == 'event') {
      $info = CRM_Event_BAO_Participant::participantDetails($this->_id);
      $title .= " - {$info['title']}";
    }
    $this->assign('transaction', TRUE);
    $this->assign('payments', $paymentInfo['transaction']);
    $this->assign('paymentLinks', $paymentInfo['payment_links']);
    return $title;
  }

}
