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

use Civi\Payment\System;
use Civi\Payment\Exception\PaymentProcessorException;

/**
 * Class CRM_Core_Payment.
 *
 * This class is the main class for the payment processor subsystem.
 *
 * It is the parent class for payment processors. It also holds some IPN related functions
 * that need to be moved. In particular handlePaymentMethod should be moved to a factory class.
 */
abstract class CRM_Core_Payment {

  /**
   * Component - ie. event or contribute.
   *
   * This is used for setting return urls.
   *
   * @var string
   */
  protected $_component;

  /**
   * How are we getting billing information.
   *
   * We are trying to completely deprecate these parameters.
   *
   * FORM   - we collect it on the same page
   * BUTTON - the processor collects it and sends it back to us via some protocol
   */
  const
    BILLING_MODE_FORM = 1,
    BILLING_MODE_BUTTON = 2,
    BILLING_MODE_NOTIFY = 4;

  /**
   * Which payment type(s) are we using?
   *
   * credit card
   * direct debit
   * or both
   * @todo create option group - nb omnipay uses a 3rd type - transparent redirect cc
   */
  const
    PAYMENT_TYPE_CREDIT_CARD = 1,
    PAYMENT_TYPE_DIRECT_DEBIT = 2;

  /**
   * Subscription / Recurring payment Status
   * START, END
   */
  const
    RECURRING_PAYMENT_START = 'START',
    RECURRING_PAYMENT_END = 'END';

  protected $_paymentProcessor;

  /**
   * Base url of the calling form (offsite processors).
   *
   * @var string
   */
  protected $baseReturnUrl;

  /**
   * Return url upon success (offsite processors).
   *
   * @var string
   */
  protected $successUrl;

  /**
   * Return url upon failure (offsite processors).
   *
   * @var string
   */
  protected $cancelUrl;

  /**
   * Processor type label.
   *
   * (Deprecated parameter but used in some messages).
   *
   * @deprecated
   *
   * @var string
   */
  public $_processorName;

  /**
   * The profile configured to show on the billing form.
   *
   * Currently only the pseudo-profile 'billing' is supported but hopefully in time we will take an id and
   * load that from the DB and the processor will be able to return a set of fields that combines it's minimum
   * requirements with the configured requirements.
   *
   * Currently only the pseudo-processor 'manual' or 'pay-later' uses this setting to return a 'curated' set
   * of fields.
   *
   * Note this change would probably include converting 'billing' to a reserved profile.
   *
   * @var int|string
   */
  protected $billingProfile;

  /**
   * Payment instrument ID.
   *
   * This is normally retrieved from the payment_processor table.
   *
   * @var int
   */
  protected $paymentInstrumentID;

  /**
   * Is this a back office transaction.
   *
   * @var bool
   */
  protected $backOffice = FALSE;

  /**
   * @return bool
   */
  public function isBackOffice() {
    return $this->backOffice;
  }

  /**
   * Set back office property.
   *
   * @param bool $isBackOffice
   */
  public function setBackOffice($isBackOffice) {
    $this->backOffice = $isBackOffice;
  }

  /**
   * Get payment instrument id.
   *
   * @return int
   */
  public function getPaymentInstrumentID() {
    return $this->paymentInstrumentID ? $this->paymentInstrumentID : $this->_paymentProcessor['payment_instrument_id'];
  }

  /**
   * Set payment Instrument id.
   *
   * By default we actually ignore the form value. The manual processor takes it more seriously.
   *
   * @param int $paymentInstrumentID
   */
  public function setPaymentInstrumentID($paymentInstrumentID) {
    $this->paymentInstrumentID = $this->_paymentProcessor['payment_instrument_id'];
  }

  /**
   * Set base return path (offsite processors).
   *
   * This is only useful with an internal civicrm form.
   *
   * @param string $url
   *   Internal civicrm path.
   */
  public function setBaseReturnUrl($url) {
    $this->baseReturnUrl = $url;
  }

  /**
   * Set success return URL (offsite processors).
   *
   * This overrides $baseReturnUrl
   *
   * @param string $url
   *   Full url of site to return browser to upon success.
   */
  public function setSuccessUrl($url) {
    $this->successUrl = $url;
  }

  /**
   * Set cancel return URL (offsite processors).
   *
   * This overrides $baseReturnUrl
   *
   * @param string $url
   *   Full url of site to return browser to upon failure.
   */
  public function setCancelUrl($url) {
    $this->cancelUrl = $url;
  }

  /**
   * Set the configured payment profile.
   *
   * @param int|string $value
   */
  public function setBillingProfile($value) {
    $this->billingProfile = $value;
  }

  /**
   * Opportunity for the payment processor to override the entire form build.
   *
   * @param CRM_Core_Form $form
   *
   * @return bool
   *   Should form building stop at this point?
   */
  public function buildForm(&$form) {
    return FALSE;
  }

  /**
   * Log payment notification message to forensic system log.
   *
   * @todo move to factory class \Civi\Payment\System (or similar)
   *
   * @param array $params
   *
   * @return mixed
   */
  public static function logPaymentNotification($params) {
    $message = 'payment_notification ';
    if (!empty($params['processor_name'])) {
      $message .= 'processor_name=' . $params['processor_name'];
    }
    if (!empty($params['processor_id'])) {
      $message .= 'processor_id=' . $params['processor_id'];
    }

    $log = new CRM_Utils_SystemLogger();
    $log->alert($message, $_REQUEST);
  }

  /**
   * Check if capability is supported.
   *
   * Capabilities have a one to one relationship with capability-related functions on this class.
   *
   * Payment processor classes should over-ride the capability-specific function rather than this one.
   *
   * @param string $capability
   *   E.g BackOffice, LiveMode, FutureRecurStartDate.
   *
   * @return bool
   */
  public function supports($capability) {
    $function = 'supports' . ucfirst($capability);
    if (method_exists($this, $function)) {
      return $this->$function();
    }
    return FALSE;
  }

  /**
   * Are back office payments supported.
   *
   * e.g paypal standard won't permit you to enter a credit card associated
   * with someone else's login.
   * The intention is to support off-site (other than paypal) & direct debit but that is not all working yet so to
   * reach a 'stable' point we disable.
   *
   * @return bool
   */
  protected function supportsBackOffice() {
    if ($this->_paymentProcessor['billing_mode'] == 4 || $this->_paymentProcessor['payment_type'] != 1) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Can more than one transaction be processed at once?
   *
   * In general processors that process payment by server to server communication support this while others do not.
   *
   * In future we are likely to hit an issue where this depends on whether a token already exists.
   *
   * @return bool
   */
  protected function supportsMultipleConcurrentPayments() {
    if ($this->_paymentProcessor['billing_mode'] == 4 || $this->_paymentProcessor['payment_type'] != 1) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Are live payments supported - e.g dummy doesn't support this.
   *
   * @return bool
   */
  protected function supportsLiveMode() {
    return TRUE;
  }

  /**
   * Are test payments supported.
   *
   * @return bool
   */
  protected function supportsTestMode() {
    return TRUE;
  }

  /**
   * Does this payment processor support refund?
   *
   * @return bool
   */
  public function supportsRefund() {
    return FALSE;
  }

  /**
   * Should the first payment date be configurable when setting up back office recurring payments.
   *
   * We set this to false for historical consistency but in fact most new processors use tokens for recurring and can support this
   *
   * @return bool
   */
  protected function supportsFutureRecurStartDate() {
    return FALSE;
  }

  /**
   * Does this processor support cancelling recurring contributions through code.
   *
   * If the processor returns true it must be possible to take action from within CiviCRM
   * that will result in no further payments being processed. In the case of token processors (e.g
   * IATS, eWay) updating the contribution_recur table is probably sufficient.
   *
   * @return bool
   */
  protected function supportsCancelRecurring() {
    return method_exists(CRM_Utils_System::getClassName($this), 'cancelSubscription');
  }

  /**
   * Does this processor support pre-approval.
   *
   * This would generally look like a redirect to enter credentials which can then be used in a later payment call.
   *
   * Currently Paypal express supports this, with a redirect to paypal after the 'Main' form is submitted in the
   * contribution page. This token can then be processed at the confirm phase. Although this flow 'looks' like the
   * 'notify' flow a key difference is that in the notify flow they don't have to return but in this flow they do.
   *
   * @return bool
   */
  protected function supportsPreApproval() {
    return FALSE;
  }

  /**
   * Does this processor support updating billing info for recurring contributions through code.
   *
   * If the processor returns true then it must be possible to update billing info from within CiviCRM
   * that will be updated at the payment processor.
   *
   * @return bool
   */
  protected function supportsUpdateSubscriptionBillingInfo() {
    return method_exists(CRM_Utils_System::getClassName($this), 'updateSubscriptionBillingInfo');
  }

  /**
   * Can recurring contributions be set against pledges.
   *
   * In practice all processors that use the baseIPN function to finish transactions or
   * call the completetransaction api support this by looking up previous contributions in the
   * series and, if there is a prior contribution against a pledge, and the pledge is not complete,
   * adding the new payment to the pledge.
   *
   * However, only enabling for processors it has been tested against.
   *
   * @return bool
   */
  protected function supportsRecurContributionsForPledges() {
    return FALSE;
  }

  /**
   * Function to action pre-approval if supported
   *
   * @param array $params
   *   Parameters from the form
   *
   * This function returns an array which should contain
   *   - pre_approval_parameters (this will be stored on the calling form & available later)
   *   - redirect_url (if set the browser will be redirected to this.
   */
  public function doPreApproval(&$params) {
  }

  /**
   * Get any details that may be available to the payment processor due to an approval process having happened.
   *
   * In some cases the browser is redirected to enter details on a processor site. Some details may be available as a
   * result.
   *
   * @param array $storedDetails
   *
   * @return array
   */
  public function getPreApprovalDetails($storedDetails) {
    return [];
  }

  /**
   * Default payment instrument validation.
   *
   * Implement the usual Luhn algorithm via a static function in the CRM_Core_Payment_Form if it's a credit card
   * Not a static function, because I need to check for payment_type.
   *
   * @param array $values
   * @param array $errors
   */
  public function validatePaymentInstrument($values, &$errors) {
    CRM_Core_Form::validateMandatoryFields($this->getMandatoryFields(), $values, $errors);
    if ($this->_paymentProcessor['payment_type'] == 1) {
      CRM_Core_Payment_Form::validateCreditCard($values, $errors, $this->_paymentProcessor['id']);
    }
  }

  /**
   * Getter for the payment processor.
   *
   * The payment processor array is based on the civicrm_payment_processor table entry.
   *
   * @return array
   *   Payment processor array.
   */
  public function getPaymentProcessor() {
    return $this->_paymentProcessor;
  }

  /**
   * Setter for the payment processor.
   *
   * @param array $processor
   */
  public function setPaymentProcessor($processor) {
    $this->_paymentProcessor = $processor;
  }

  /**
   * Setter for the payment form that wants to use the processor.
   *
   * @deprecated
   *
   * @param CRM_Core_Form $paymentForm
   */
  public function setForm(&$paymentForm) {
    $this->_paymentForm = $paymentForm;
  }

  /**
   * Getter for payment form that is using the processor.
   * @deprecated
   * @return CRM_Core_Form
   *   A form object
   */
  public function getForm() {
    return $this->_paymentForm;
  }

  /**
   * Get help text information (help, description, etc.) about this payment,
   * to display to the user.
   *
   * @param string $context
   *   Context of the text.
   *   Only explicitly supported contexts are handled without error.
   *   Currently supported:
   *   - contributionPageRecurringHelp (params: is_recur_installments, is_email_receipt)
   *
   * @param array $params
   *   Parameters for the field, context specific.
   *
   * @return string
   */
  public function getText($context, $params) {
    // I have deliberately added a noisy fail here.
    // The function is intended to be extendable, but not by changes
    // not documented clearly above.
    switch ($context) {
      case 'contributionPageRecurringHelp':
        // require exactly two parameters
        if (array_keys($params) == [
            'is_recur_installments',
            'is_email_receipt',
          ]) {
          $gotText = ts('Your recurring contribution will be processed automatically.');
          if ($params['is_recur_installments']) {
            $gotText .= ' ' . ts('You can specify the number of installments, or you can leave the number of installments blank if you want to make an open-ended commitment. In either case, you can choose to cancel at any time.');
          }
          if ($params['is_email_receipt']) {
            $gotText .= ' ' . ts('You will receive an email receipt for each recurring contribution.');
          }
        }
        return $gotText;

      case 'contributionPageContinueText':
        if ($params['amount'] <= 0) {
          return ts('To complete this transaction, click the <strong>Continue</strong> button below.');
        }
        if ($this->_paymentProcessor['billing_mode'] == 4) {
          return ts('Click the <strong>Continue</strong> button to go to %1, where you will select your payment method and complete the contribution.', [$this->_paymentProcessor['payment_processor_type']]);
        }
        if ($params['is_payment_to_existing']) {
          return ts('To complete this transaction, click the <strong>Make Payment</strong> button below.');
        }
        return ts('To complete your contribution, click the <strong>Continue</strong> button below.');

    }
    CRM_Core_Error::deprecatedFunctionWarning('Calls to getText must use a supported method');
    return '';
  }

  /**
   * Getter for accessing member vars.
   *
   * @todo believe this is unused
   *
   * @param string $name
   *
   * @return null
   */
  public function getVar($name) {
    return isset($this->$name) ? $this->$name : NULL;
  }

  /**
   * Get name for the payment information type.
   * @todo - use option group + name field (like Omnipay does)
   * @return string
   */
  public function getPaymentTypeName() {
    return $this->_paymentProcessor['payment_type'] == 1 ? 'credit_card' : 'direct_debit';
  }

  /**
   * Get label for the payment information type.
   * @todo - use option group + labels (like Omnipay does)
   * @return string
   */
  public function getPaymentTypeLabel() {
    return $this->_paymentProcessor['payment_type'] == 1 ? 'Credit Card' : 'Direct Debit';
  }

  /**
   * Get array of fields that should be displayed on the payment form.
   *
   * Common results are
   *   array('credit_card_type', 'credit_card_number', 'cvv2', 'credit_card_exp_date')
   *   or
   *   array('account_holder', 'bank_account_number', 'bank_identification_number', 'bank_name')
   *   or
   *   array()
   *
   * @return array
   *   Array of payment fields appropriate to the payment processor.
   *
   * @throws CiviCRM_API3_Exception
   */
  public function getPaymentFormFields() {
    if ($this->_paymentProcessor['billing_mode'] == 4) {
      return [];
    }
    return $this->_paymentProcessor['payment_type'] == 1 ? $this->getCreditCardFormFields() : $this->getDirectDebitFormFields();
  }

  /**
   * Get an array of the fields that can be edited on the recurring contribution.
   *
   * Some payment processors support editing the amount and other scheduling details of recurring payments, especially
   * those which use tokens. Others are fixed. This function allows the processor to return an array of the fields that
   * can be updated from the contribution recur edit screen.
   *
   * The fields are likely to be a subset of these
   *  - 'amount',
   *  - 'installments',
   *  - 'frequency_interval',
   *  - 'frequency_unit',
   *  - 'cycle_day',
   *  - 'next_sched_contribution_date',
   *  - 'end_date',
   * - 'failure_retry_day',
   *
   * The form does not restrict which fields from the contribution_recur table can be added (although if the html_type
   * metadata is not defined in the xml for the field it will cause an error.
   *
   * Open question - would it make sense to return membership_id in this - which is sometimes editable and is on that
   * form (UpdateSubscription).
   *
   * @return array
   */
  public function getEditableRecurringScheduleFields() {
    if ($this->supports('changeSubscriptionAmount')) {
      return ['amount'];
    }
  }

  /**
   * Get the help text to present on the recurring update page.
   *
   * This should reflect what can or cannot be edited.
   *
   * @return string
   */
  public function getRecurringScheduleUpdateHelpText() {
    if (!in_array('amount', $this->getEditableRecurringScheduleFields())) {
      return ts('Updates made using this form will change the recurring contribution information stored in your CiviCRM database, but will NOT be sent to the payment processor. You must enter the same changes using the payment processor web site.');
    }
    return ts('Use this form to change the amount or number of installments for this recurring contribution. Changes will be automatically sent to the payment processor. You can not change the contribution frequency.');
  }

  /**
   * Get the metadata for all required fields.
   *
   * @return array;
   */
  protected function getMandatoryFields() {
    $mandatoryFields = [];
    foreach ($this->getAllFields() as $field_name => $field_spec) {
      if (!empty($field_spec['is_required'])) {
        $mandatoryFields[$field_name] = $field_spec;
      }
    }
    return $mandatoryFields;
  }

  /**
   * Get the metadata of all the fields configured for this processor.
   *
   * @return array
   */
  protected function getAllFields() {
    $paymentFields = array_intersect_key($this->getPaymentFormFieldsMetadata(), array_flip($this->getPaymentFormFields()));
    $billingFields = array_intersect_key($this->getBillingAddressFieldsMetadata(), array_flip($this->getBillingAddressFields()));
    return array_merge($paymentFields, $billingFields);
  }

  /**
   * Get array of fields that should be displayed on the payment form for credit cards.
   *
   * @return array
   */
  protected function getCreditCardFormFields() {
    return [
      'credit_card_type',
      'credit_card_number',
      'cvv2',
      'credit_card_exp_date',
    ];
  }

  /**
   * Get array of fields that should be displayed on the payment form for direct debits.
   *
   * @return array
   */
  protected function getDirectDebitFormFields() {
    return [
      'account_holder',
      'bank_account_number',
      'bank_identification_number',
      'bank_name',
    ];
  }

  /**
   * Return an array of all the details about the fields potentially required for payment fields.
   *
   * Only those determined by getPaymentFormFields will actually be assigned to the form
   *
   * @return array
   *   field metadata
   */
  public function getPaymentFormFieldsMetadata() {
    //@todo convert credit card type into an option value
    $creditCardType = ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::creditCard();
    $isCVVRequired = Civi::settings()->get('cvv_backoffice_required');
    if (!$this->isBackOffice()) {
      $isCVVRequired = TRUE;
    }
    return [
      'credit_card_number' => [
        'htmlType' => 'text',
        'name' => 'credit_card_number',
        'title' => ts('Card Number'),
        'attributes' => [
          'size' => 20,
          'maxlength' => 20,
          'autocomplete' => 'off',
          'class' => 'creditcard',
        ],
        'is_required' => TRUE,
        // 'description' => '16 digit card number', // If you enable a description field it will be shown below the field on the form
      ],
      'cvv2' => [
        'htmlType' => 'text',
        'name' => 'cvv2',
        'title' => ts('Security Code'),
        'attributes' => [
          'size' => 5,
          'maxlength' => 10,
          'autocomplete' => 'off',
        ],
        'is_required' => $isCVVRequired,
        'rules' => [
          [
            'rule_message' => ts('Please enter a valid value for your card security code. This is usually the last 3-4 digits on the card\'s signature panel.'),
            'rule_name' => 'integer',
            'rule_parameters' => NULL,
          ],
        ],
      ],
      'credit_card_exp_date' => [
        'htmlType' => 'date',
        'name' => 'credit_card_exp_date',
        'title' => ts('Expiration Date'),
        'attributes' => CRM_Core_SelectValues::date('creditCard'),
        'is_required' => TRUE,
        'rules' => [
          [
            'rule_message' => ts('Card expiration date cannot be a past date.'),
            'rule_name' => 'currentDate',
            'rule_parameters' => TRUE,
          ],
        ],
        'extra' => ['class' => 'crm-form-select'],
      ],
      'credit_card_type' => [
        'htmlType' => 'select',
        'name' => 'credit_card_type',
        'title' => ts('Card Type'),
        'attributes' => $creditCardType,
        'is_required' => FALSE,
      ],
      'account_holder' => [
        'htmlType' => 'text',
        'name' => 'account_holder',
        'title' => ts('Account Holder'),
        'attributes' => [
          'size' => 20,
          'maxlength' => 34,
          'autocomplete' => 'on',
        ],
        'is_required' => TRUE,
      ],
      //e.g. IBAN can have maxlength of 34 digits
      'bank_account_number' => [
        'htmlType' => 'text',
        'name' => 'bank_account_number',
        'title' => ts('Bank Account Number'),
        'attributes' => [
          'size' => 20,
          'maxlength' => 34,
          'autocomplete' => 'off',
        ],
        'rules' => [
          [
            'rule_message' => ts('Please enter a valid Bank Identification Number (value must not contain punctuation characters).'),
            'rule_name' => 'nopunctuation',
            'rule_parameters' => NULL,
          ],
        ],
        'is_required' => TRUE,
      ],
      //e.g. SWIFT-BIC can have maxlength of 11 digits
      'bank_identification_number' => [
        'htmlType' => 'text',
        'name' => 'bank_identification_number',
        'title' => ts('Bank Identification Number'),
        'attributes' => [
          'size' => 20,
          'maxlength' => 11,
          'autocomplete' => 'off',
        ],
        'is_required' => TRUE,
        'rules' => [
          [
            'rule_message' => ts('Please enter a valid Bank Identification Number (value must not contain punctuation characters).'),
            'rule_name' => 'nopunctuation',
            'rule_parameters' => NULL,
          ],
        ],
      ],
      'bank_name' => [
        'htmlType' => 'text',
        'name' => 'bank_name',
        'title' => ts('Bank Name'),
        'attributes' => [
          'size' => 20,
          'maxlength' => 64,
          'autocomplete' => 'off',
        ],
        'is_required' => TRUE,

      ],
      'check_number' => [
        'htmlType' => 'text',
        'name' => 'check_number',
        'title' => ts('Check Number'),
        'is_required' => FALSE,
        'attributes' => NULL,
      ],
      'pan_truncation' => [
        'htmlType' => 'text',
        'name' => 'pan_truncation',
        'title' => ts('Last 4 digits of the card'),
        'is_required' => FALSE,
        'attributes' => [
          'size' => 4,
          'maxlength' => 4,
          'minlength' => 4,
          'autocomplete' => 'off',
        ],
        'rules' => [
          [
            'rule_message' => ts('Please enter valid last 4 digit card number.'),
            'rule_name' => 'numeric',
            'rule_parameters' => NULL,
          ],
        ],
      ],
      'payment_token' => [
        'htmlType' => 'hidden',
        'name' => 'payment_token',
        'title' => ts('Authorization token'),
        'is_required' => FALSE,
        'attributes' => [
          'size' => 10,
          'autocomplete' => 'off',
          'id' => 'payment_token',
        ],
      ],
    ];
  }

  /**
   * Get billing fields required for this processor.
   *
   * We apply the existing default of returning fields only for payment processor type 1. Processors can override to
   * alter.
   *
   * @param int $billingLocationID
   *
   * @return array
   */
  public function getBillingAddressFields($billingLocationID = NULL) {
    if (!$billingLocationID) {
      // Note that although the billing id is passed around the forms the idea that it would be anything other than
      // the result of the function below doesn't seem to have eventuated.
      // So taking this as a param is possibly something to be removed in favour of the standard default.
      $billingLocationID = CRM_Core_BAO_LocationType::getBilling();
    }
    if ($this->_paymentProcessor['billing_mode'] != 1 && $this->_paymentProcessor['billing_mode'] != 3) {
      return [];
    }
    return [
      'first_name' => 'billing_first_name',
      'middle_name' => 'billing_middle_name',
      'last_name' => 'billing_last_name',
      'street_address' => "billing_street_address-{$billingLocationID}",
      'city' => "billing_city-{$billingLocationID}",
      'country' => "billing_country_id-{$billingLocationID}",
      'state_province' => "billing_state_province_id-{$billingLocationID}",
      'postal_code' => "billing_postal_code-{$billingLocationID}",
    ];
  }

  /**
   * Get form metadata for billing address fields.
   *
   * @param int $billingLocationID
   *
   * @return array
   *    Array of metadata for address fields.
   */
  public function getBillingAddressFieldsMetadata($billingLocationID = NULL) {
    if (!$billingLocationID) {
      // Note that although the billing id is passed around the forms the idea that it would be anything other than
      // the result of the function below doesn't seem to have eventuated.
      // So taking this as a param is possibly something to be removed in favour of the standard default.
      $billingLocationID = CRM_Core_BAO_LocationType::getBilling();
    }
    $metadata = [];
    $metadata['billing_first_name'] = [
      'htmlType' => 'text',
      'name' => 'billing_first_name',
      'title' => ts('Billing First Name'),
      'cc_field' => TRUE,
      'attributes' => [
        'size' => 30,
        'maxlength' => 60,
        'autocomplete' => 'off',
      ],
      'is_required' => TRUE,
    ];

    $metadata['billing_middle_name'] = [
      'htmlType' => 'text',
      'name' => 'billing_middle_name',
      'title' => ts('Billing Middle Name'),
      'cc_field' => TRUE,
      'attributes' => [
        'size' => 30,
        'maxlength' => 60,
        'autocomplete' => 'off',
      ],
      'is_required' => FALSE,
    ];

    $metadata['billing_last_name'] = [
      'htmlType' => 'text',
      'name' => 'billing_last_name',
      'title' => ts('Billing Last Name'),
      'cc_field' => TRUE,
      'attributes' => [
        'size' => 30,
        'maxlength' => 60,
        'autocomplete' => 'off',
      ],
      'is_required' => TRUE,
    ];

    $metadata["billing_street_address-{$billingLocationID}"] = [
      'htmlType' => 'text',
      'name' => "billing_street_address-{$billingLocationID}",
      'title' => ts('Street Address'),
      'cc_field' => TRUE,
      'attributes' => [
        'size' => 30,
        'maxlength' => 60,
        'autocomplete' => 'off',
      ],
      'is_required' => TRUE,
    ];

    $metadata["billing_city-{$billingLocationID}"] = [
      'htmlType' => 'text',
      'name' => "billing_city-{$billingLocationID}",
      'title' => ts('City'),
      'cc_field' => TRUE,
      'attributes' => [
        'size' => 30,
        'maxlength' => 60,
        'autocomplete' => 'off',
      ],
      'is_required' => TRUE,
    ];

    $metadata["billing_state_province_id-{$billingLocationID}"] = [
      'htmlType' => 'chainSelect',
      'title' => ts('State/Province'),
      'name' => "billing_state_province_id-{$billingLocationID}",
      'cc_field' => TRUE,
      'is_required' => TRUE,
    ];

    $metadata["billing_postal_code-{$billingLocationID}"] = [
      'htmlType' => 'text',
      'name' => "billing_postal_code-{$billingLocationID}",
      'title' => ts('Postal Code'),
      'cc_field' => TRUE,
      'attributes' => [
        'size' => 30,
        'maxlength' => 60,
        'autocomplete' => 'off',
      ],
      'is_required' => TRUE,
    ];

    $metadata["billing_country_id-{$billingLocationID}"] = [
      'htmlType' => 'select',
      'name' => "billing_country_id-{$billingLocationID}",
      'title' => ts('Country'),
      'cc_field' => TRUE,
      'attributes' => [
          '' => ts('- select -'),
        ] + CRM_Core_PseudoConstant::country(),
      'is_required' => TRUE,
    ];
    return $metadata;
  }

  /**
   * Get base url dependent on component.
   *
   * (or preferably set it using the setter function).
   *
   * @return string
   */
  protected function getBaseReturnUrl() {
    if ($this->baseReturnUrl) {
      return $this->baseReturnUrl;
    }
    if ($this->_component == 'event') {
      $baseURL = 'civicrm/event/register';
    }
    else {
      $baseURL = 'civicrm/contribute/transact';
    }
    return $baseURL;
  }

  /**
   * Get the currency for the transaction.
   *
   * Handle any inconsistency about how it is passed in here.
   *
   * @param $params
   *
   * @return string
   */
  protected function getCurrency($params) {
    return CRM_Utils_Array::value('currencyID', $params, CRM_Utils_Array::value('currency', $params));
  }

  /**
   * Get the currency for the transaction.
   *
   * Handle any inconsistency about how it is passed in here.
   *
   * @param $params
   *
   * @return string
   */
  protected function getAmount($params) {
    return CRM_Utils_Money::format($params['amount'], NULL, NULL, TRUE);
  }

  /**
   * Get url to return to after cancelled or failed transaction.
   *
   * @param string $qfKey
   * @param int $participantID
   *
   * @return string cancel url
   */
  public function getCancelUrl($qfKey, $participantID) {
    if (isset($this->cancelUrl)) {
      return $this->cancelUrl;
    }

    if ($this->_component == 'event') {
      return CRM_Utils_System::url($this->getBaseReturnUrl(), [
        'reset' => 1,
        'cc' => 'fail',
        'participantId' => $participantID,
      ],
        TRUE, NULL, FALSE
      );
    }

    return CRM_Utils_System::url($this->getBaseReturnUrl(), [
      '_qf_Main_display' => 1,
      'qfKey' => $qfKey,
      'cancel' => 1,
    ],
      TRUE, NULL, FALSE
    );
  }

  /**
   * Get URL to return the browser to on success.
   *
   * @param $qfKey
   *
   * @return string
   */
  protected function getReturnSuccessUrl($qfKey) {
    if (isset($this->successUrl)) {
      return $this->successUrl;
    }

    return CRM_Utils_System::url($this->getBaseReturnUrl(), [
      '_qf_ThankYou_display' => 1,
      'qfKey' => $qfKey,
    ],
      TRUE, NULL, FALSE
    );
  }

  /**
   * Get URL to return the browser to on failure.
   *
   * @param string $key
   * @param int $participantID
   * @param int $eventID
   *
   * @return string
   *   URL for a failing transactor to be redirected to.
   */
  protected function getReturnFailUrl($key, $participantID = NULL, $eventID = NULL) {
    if (isset($this->cancelUrl)) {
      return $this->cancelUrl;
    }

    $test = $this->_is_test ? '&action=preview' : '';
    if ($this->_component == "event") {
      return CRM_Utils_System::url('civicrm/event/register',
        "reset=1&cc=fail&participantId={$participantID}&id={$eventID}{$test}&qfKey={$key}",
        FALSE, NULL, FALSE
      );
    }
    else {
      return CRM_Utils_System::url('civicrm/contribute/transact',
        "_qf_Main_display=1&cancel=1&qfKey={$key}{$test}",
        FALSE, NULL, FALSE
      );
    }
  }

  /**
   * Get URl for when the back button is pressed.
   *
   * @param $qfKey
   *
   * @return string url
   */
  protected function getGoBackUrl($qfKey) {
    return CRM_Utils_System::url($this->getBaseReturnUrl(), [
      '_qf_Confirm_display' => 'true',
      'qfKey' => $qfKey,
    ],
      TRUE, NULL, FALSE
    );
  }

  /**
   * Get the notify (aka ipn, web hook or silent post) url.
   *
   * If there is no '.' in it we assume that we are dealing with localhost or
   * similar and it is unreachable from the web & hence invalid.
   *
   * @return string
   *   URL to notify outcome of transaction.
   */
  protected function getNotifyUrl() {
    $url = CRM_Utils_System::url(
      'civicrm/payment/ipn/' . $this->_paymentProcessor['id'],
      [],
      TRUE,
      NULL,
      FALSE,
      TRUE
    );
    return (stristr($url, '.')) ? $url : '';
  }

  /**
   * Calling this from outside the payment subsystem is deprecated - use doPayment.
   *
   * Does a server to server payment transaction.
   *
   * @param array $params
   *   Assoc array of input parameters for this transaction.
   *
   * @return array
   *   the result in an nice formatted array (or an error object - but throwing exceptions is preferred)
   */
  protected function doDirectPayment(&$params) {
    return $params;
  }

  /**
   * Process payment - this function wraps around both doTransferCheckout and doDirectPayment.
   *
   * The function ensures an exception is thrown & moves some of this logic out of the form layer and makes the forms
   * more agnostic.
   *
   * Payment processors should set payment_status_id. This function adds some historical defaults ie. the
   * assumption that if a 'doDirectPayment' processors comes back it completed the transaction & in fact
   * doTransferCheckout would not traditionally come back.
   *
   * doDirectPayment does not do an immediate payment for Authorize.net or Paypal so the default is assumed
   * to be Pending.
   *
   * Once this function is fully rolled out then it will be preferred for processors to throw exceptions than to
   * return Error objects
   *
   * Usage:
   * Payment processors should override this function directly instead of using doDirectPayment/doTransferCheckout which are deprecated.
   * Payment processors should set and return payment_status_id (Pending if the IPN will complete it, Completed if successful).
   * @fixme For the contribution workflow we have a contributionID, but for the event and membership workflow the contribution has not yet been created
   *  so we can't update params directly on the contribution.  However if you return trxn_id, fee_amount, net_amount they will be set on the contribution
   *  by those workflows.  Ideally all workflows would create a pending contribution BEFORE calling doPayment (eg. https://github.com/civicrm/civicrm-core/pull/13763 for events)
   *
   * @param array $params
   *
   * @param string $component
   *
   * @return array
   *   Result array
   *
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function doPayment(&$params, $component = 'contribute') {
    $this->_component = $component;
    $statuses = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'validate');

    // If we have a $0 amount, skip call to processor and set payment_status to Completed.
    // Conceivably a processor might override this - perhaps for setting up a token - but we don't
    // have an example of that at the mome.
    if ($params['amount'] == 0) {
      $result['payment_status_id'] = array_search('Completed', $statuses);
      return $result;
    }

    if ($this->_paymentProcessor['billing_mode'] == 4) {
      $result = $this->doTransferCheckout($params, $component);
      if (is_array($result) && !isset($result['payment_status_id'])) {
        $result['payment_status_id'] = array_search('Pending', $statuses);
      }
    }
    else {
      $result = $this->doDirectPayment($params, $component);
      if (is_array($result) && !isset($result['payment_status_id'])) {
        if (!empty($params['is_recur'])) {
          // See comment block.
          $result['payment_status_id'] = array_search('Pending', $statuses);
        }
        else {
          $result['payment_status_id'] = array_search('Completed', $statuses);
        }
      }
    }
    if (is_a($result, 'CRM_Core_Error')) {
      throw new PaymentProcessorException(CRM_Core_Error::getMessages($result));
    }
    return $result;
  }

  /**
   * Refunds payment
   *
   * Payment processors should set payment_status_id if it set the status to Refunded in case the transaction is successful
   *
   * @param array $params
   *
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function doRefund(&$params) {}

  /**
   * Query payment processor for details about a transaction.
   *
   * @param array $params
   *   Array of parameters containing one of:
   *   - trxn_id Id of an individual transaction.
   *   - processor_id Id of a recurring contribution series as stored in the civicrm_contribution_recur table.
   *
   * @return array
   *   Extra parameters retrieved.
   *   Any parameters retrievable through this should be documented in the function comments at
   *   CRM_Core_Payment::doQuery. Currently:
   *   - fee_amount Amount of fee paid
   */
  public function doQuery($params) {
    return [];
  }

  /**
   * This function checks to see if we have the right config values.
   *
   * @return string
   *   the error message if any
   */
  abstract protected function checkConfig();

  /**
   * Redirect for paypal.
   *
   * @todo move to paypal class or remove
   *
   * @param $paymentProcessor
   *
   * @return bool
   */
  public static function paypalRedirect(&$paymentProcessor) {
    if (!$paymentProcessor) {
      return FALSE;
    }

    if (isset($_GET['payment_date']) &&
      isset($_GET['merchant_return_link']) &&
      CRM_Utils_Array::value('payment_status', $_GET) == 'Completed' &&
      $paymentProcessor['payment_processor_type'] == "PayPal_Standard"
    ) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Handle incoming payment notification.
   *
   * IPNs, also called silent posts are notifications of payment outcomes or activity on an external site.
   *
   * @todo move to0 \Civi\Payment\System factory method
   * Page callback for civicrm/payment/ipn
   */
  public static function handleIPN() {
    try {
      self::handlePaymentMethod(
        'PaymentNotification',
        [
          'processor_name' => CRM_Utils_Request::retrieveValue('processor_name', 'String'),
          'processor_id' => CRM_Utils_Request::retrieveValue('processor_id', 'Integer'),
          'mode' => CRM_Utils_Request::retrieveValue('mode', 'Alphanumeric'),
        ]
      );
    }
    catch (CRM_Core_Exception $e) {
      Civi::log()->error('ipn_payment_callback_exception', [
        'context' => [
          'backtrace' => CRM_Core_Error::formatBacktrace(debug_backtrace()),
        ],
      ]);
    }
    CRM_Utils_System::civiExit();
  }

  /**
   * Payment callback handler.
   *
   * The processor_name or processor_id is passed in.
   * Note that processor_id is more reliable as one site may have more than one instance of a
   * processor & ideally the processor will be validating the results
   * Load requested payment processor and call that processor's handle<$method> method
   *
   * @todo move to \Civi\Payment\System factory method
   *
   * @param string $method
   *   'PaymentNotification' or 'PaymentCron'
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public static function handlePaymentMethod($method, $params = []) {
    if (!isset($params['processor_id']) && !isset($params['processor_name'])) {
      $q = explode('/', CRM_Utils_Array::value(CRM_Core_Config::singleton()->userFrameworkURLVar, $_GET, ''));
      $lastParam = array_pop($q);
      if (is_numeric($lastParam)) {
        $params['processor_id'] = $_GET['processor_id'] = $lastParam;
      }
      else {
        self::logPaymentNotification($params);
        throw new CRM_Core_Exception("Either 'processor_id' (recommended) or 'processor_name' (deprecated) is required for payment callback.");
      }
    }

    self::logPaymentNotification($params);

    $sql = "SELECT ppt.class_name, ppt.name as processor_name, pp.id AS processor_id
              FROM civicrm_payment_processor_type ppt
        INNER JOIN civicrm_payment_processor pp
                ON pp.payment_processor_type_id = ppt.id
               AND pp.is_active";

    if (isset($params['processor_id'])) {
      $sql .= " WHERE pp.id = %2";
      $args[2] = [$params['processor_id'], 'Integer'];
      $notFound = ts("No active instances of payment processor %1 were found.", [1 => $params['processor_id']]);
    }
    else {
      // This is called when processor_name is passed - passing processor_id instead is recommended.
      $sql .= " WHERE ppt.name = %2 AND pp.is_test = %1";
      $args[1] = [
        (CRM_Utils_Array::value('mode', $params) == 'test') ? 1 : 0,
        'Integer',
      ];
      $args[2] = [$params['processor_name'], 'String'];
      $notFound = ts("No active instances of payment processor '%1' were found.", [1 => $params['processor_name']]);
    }

    $dao = CRM_Core_DAO::executeQuery($sql, $args);

    // Check whether we found anything at all.
    if (!$dao->N) {
      throw new CRM_Core_Exception($notFound);
    }

    $method = 'handle' . $method;
    $extension_instance_found = FALSE;

    // In all likelihood, we'll just end up with the one instance returned here. But it's
    // possible we may get more. Hence, iterate through all instances ..

    while ($dao->fetch()) {
      // Check pp is extension - is this still required - surely the singleton below handles it.
      $ext = CRM_Extension_System::singleton()->getMapper();
      if ($ext->isExtensionKey($dao->class_name)) {
        $paymentClass = $ext->keyToClass($dao->class_name, 'payment');
        require_once $ext->classToPath($paymentClass);
      }

      $processorInstance = System::singleton()->getById($dao->processor_id);

      // Should never be empty - we already established this processor_id exists and is active.
      if (empty($processorInstance)) {
        continue;
      }

      // Does PP implement this method, and can we call it?
      if (!method_exists($processorInstance, $method) ||
        !is_callable([$processorInstance, $method])
      ) {
        // on the off chance there is a double implementation of this processor we should keep looking for another
        // note that passing processor_id is more reliable & we should work to deprecate processor_name
        continue;
      }

      // Everything, it seems, is ok - execute pp callback handler
      $processorInstance->$method();
      $extension_instance_found = TRUE;
    }

    // Call IPN postIPNProcess hook to allow for custom processing of IPN data.
    $IPNParams = array_merge($_GET, $_REQUEST);
    CRM_Utils_Hook::postIPNProcess($IPNParams);
    if (!$extension_instance_found) {
      $message = "No extension instances of the '%1' payment processor were found.<br />" .
        "%2 method is unsupported in legacy payment processors.";
      throw new CRM_Core_Exception(ts($message, [
        1 => $params['processor_name'],
        2 => $method,
      ]));
    }
  }

  /**
   * Check whether a method is present ( & supported ) by the payment processor object.
   *
   * @deprecated - use $paymentProcessor->supports(array('cancelRecurring');
   *
   * @param string $method
   *   Method to check for.
   *
   * @return bool
   */
  public function isSupported($method) {
    return method_exists(CRM_Utils_System::getClassName($this), $method);
  }

  /**
   * Some processors replace the form submit button with their own.
   *
   * Returning false here will leave the button off front end forms.
   *
   * At this stage there is zero cross-over between back-office processors and processors that suppress the submit.
   */
  public function isSuppressSubmitButtons() {
    return FALSE;
  }

  /**
   * Checks to see if invoice_id already exists in db.
   *
   * It's arguable if this belongs in the payment subsystem at all but since several processors implement it
   * it is better to standardise to being here.
   *
   * @param int $invoiceId The ID to check.
   *
   * @param null $contributionID
   *   If a contribution exists pass in the contribution ID.
   *
   * @return bool
   *   True if invoice ID otherwise exists, else false
   */
  protected function checkDupe($invoiceId, $contributionID = NULL) {
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->invoice_id = $invoiceId;
    if ($contributionID) {
      $contribution->whereAdd("id <> $contributionID");
    }
    return $contribution->find();
  }

  /**
   * Get url for users to manage this recurring contribution for this processor.
   *
   * @param int $entityID
   * @param null $entity
   * @param string $action
   *
   * @return string
   */
  public function subscriptionURL($entityID = NULL, $entity = NULL, $action = 'cancel') {
    // Set URL
    switch ($action) {
      case 'cancel':
        if (!$this->supports('cancelRecurring')) {
          return NULL;
        }
        $url = 'civicrm/contribute/unsubscribe';
        break;

      case 'billing':
        //in notify mode don't return the update billing url
        if (!$this->supports('updateSubscriptionBillingInfo')) {
          return NULL;
        }
        $url = 'civicrm/contribute/updatebilling';
        break;

      case 'update':
        if (!$this->supports('changeSubscriptionAmount') && !$this->supports('editRecurringContribution')) {
          return NULL;
        }
        $url = 'civicrm/contribute/updaterecur';
        break;
    }

    $userId = CRM_Core_Session::singleton()->get('userID');
    $contactID = 0;
    $checksumValue = '';
    $entityArg = '';

    // Find related Contact
    if ($entityID) {
      switch ($entity) {
        case 'membership':
          $contactID = CRM_Core_DAO::getFieldValue("CRM_Member_DAO_Membership", $entityID, "contact_id");
          $entityArg = 'mid';
          break;

        case 'contribution':
          $contactID = CRM_Core_DAO::getFieldValue("CRM_Contribute_DAO_Contribution", $entityID, "contact_id");
          $entityArg = 'coid';
          break;

        case 'recur':
          $sql = "
    SELECT DISTINCT con.contact_id
      FROM civicrm_contribution_recur rec
INNER JOIN civicrm_contribution con ON ( con.contribution_recur_id = rec.id )
     WHERE rec.id = %1";
          $contactID = CRM_Core_DAO::singleValueQuery($sql, [
            1 => [
              $entityID,
              'Integer',
            ],
          ]);
          $entityArg = 'crid';
          break;
      }
    }

    // Add entity arguments
    if ($entityArg != '') {
      // Add checksum argument
      if ($contactID != 0 && $userId != $contactID) {
        $checksumValue = '&cs=' . CRM_Contact_BAO_Contact_Utils::generateChecksum($contactID, NULL, 'inf');
      }
      return CRM_Utils_System::url($url, "reset=1&{$entityArg}={$entityID}{$checksumValue}", TRUE, NULL, FALSE, TRUE);
    }

    // Else login URL
    if ($this->supports('accountLoginURL')) {
      return $this->accountLoginURL();
    }

    // Else default
    return isset($this->_paymentProcessor['url_recur']) ? $this->_paymentProcessor['url_recur'] : '';
  }

  /**
   * Get description of payment to pass to processor.
   *
   * This is often what people see in the interface so we want to get
   * as much unique information in as possible within the field length (& presumably the early part of the field)
   *
   * People seeing these can be assumed to be advanced users so quantity of information probably trumps
   * having field names to clarify
   *
   * @param array $params
   * @param int $length
   *
   * @return string
   */
  protected function getPaymentDescription($params, $length = 24) {
    $parts = [
      'contactID',
      'contributionID',
      'description',
      'billing_first_name',
      'billing_last_name',
    ];
    $validParts = [];
    if (isset($params['description'])) {
      $uninformativeStrings = [
        ts('Online Event Registration: '),
        ts('Online Contribution: '),
      ];
      $params['description'] = str_replace($uninformativeStrings, '', $params['description']);
    }
    foreach ($parts as $part) {
      if ((!empty($params[$part]))) {
        $validParts[] = $params[$part];
      }
    }
    return substr(implode('-', $validParts), 0, $length);
  }

  /**
   * Checks if backoffice recurring edit is allowed
   *
   * @return bool
   */
  public function supportsEditRecurringContribution() {
    return FALSE;
  }

  /**
   * Does this processor support changing the amount for recurring contributions through code.
   *
   * If the processor returns true then it must be possible to update the amount from within CiviCRM
   * that will be updated at the payment processor.
   *
   * @return bool
   */
  protected function supportsChangeSubscriptionAmount() {
    return method_exists(CRM_Utils_System::getClassName($this), 'changeSubscriptionAmount');
  }

  /**
   * Checks if payment processor supports recurring contributions
   *
   * @return bool
   */
  public function supportsRecurring() {
    if (!empty($this->_paymentProcessor['is_recur'])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Checks if payment processor supports an account login URL
   * TODO: This is checked by self::subscriptionURL but is only used if no entityID is found.
   * TODO: It is implemented by AuthorizeNET, any others?
   *
   * @return bool
   */
  protected function supportsAccountLoginURL() {
    return method_exists(CRM_Utils_System::getClassName($this), 'accountLoginURL');
  }

  /**
   * Should a receipt be sent out for a pending payment.
   *
   * e.g for traditional pay later & ones with a delayed settlement a pending receipt makes sense.
   */
  public function isSendReceiptForPending() {
    return FALSE;
  }

}
