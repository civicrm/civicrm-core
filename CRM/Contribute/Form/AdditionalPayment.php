<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This form records additional payments needed when
 * event/contribution is partially paid
 *
 */
class CRM_Contribute_Form_AdditionalPayment extends CRM_Contribute_Form_AbstractEditPayment {
  public $_contributeMode = 'direct';

  /**
   * related component whose financial payment is being processed
   *
   * @var string
   * @public
   */
  protected $_component = NULL;

  /**
   * id of the component entity
   */
  public $_id = NULL;

  protected $_owed = NULL;

  protected $_refund = NULL;

  protected $_contactId = NULL;

  protected $_contributorDisplayName = NULL;

  protected $_contributorEmail = NULL;

  protected $_toDoNotEmail = NULL;

  protected $_paymentType = NULL;

  protected $_contributionId = NULL;

  protected $fromEmailId = NULL;

  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->_component = CRM_Utils_Request::retrieve('component', 'String', $this, TRUE);
    $this->_fromEmails = CRM_Core_BAO_Email::getFromEmail();
    $this->_formType = CRM_Utils_Array::value('formType', $_GET);

    $enitityType = NULL;
    if ($this->_component == 'event') {
      $enitityType = 'participant';
      $this->_contributionId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment', $this->_id, 'contribution_id', 'participant_id');
    }

    $paymentInfo = CRM_Core_BAO_FinancialTrxn::getPartialPaymentWithType($this->_id, $enitityType);

    if (!empty($paymentInfo['refund_due'])) {
      $paymentAmt = $this->_refund = $paymentInfo['refund_due'];
      $this->_paymentType = 'refund';
    }
    elseif (!empty($paymentInfo['amount_owed'])) {
      $paymentAmt = $this->_owed = $paymentInfo['amount_owed'];
      $this->_paymentType = 'owed';
    }
    else {
      CRM_Core_Error::fatal(ts('No payment information found for this record'));
    }

    list($this->_contributorDisplayName, $this->_contributorEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactId);
    //set the payment mode - _mode property is defined in parent class
    $this->_mode = CRM_Utils_Request::retrieve('mode', 'String', $this);

    $this->assignProcessors();

    // also check for billing information
    // get the billing location type
    $this->assignBillingType();

    $this->assign('contributionMode', $this->_mode);
    $this->assign('contactId', $this->_contactId);
    $this->assign('component', $this->_component);
    $this->assign('id', $this->_id);
    $this->assign('paymentType', $this->_paymentType);
    $this->assign('paymentAmt', $paymentAmt);

    $this->_paymentProcessor = array('billing_mode' => 1);

    $title = ($this->_refund) ? "Refund for {$this->_contributorDisplayName}" : "Payment from {$this->_contributorDisplayName}";
    if ($title) {
      CRM_Utils_System::setTitle(ts('%1', array(1 => $title)));
    }
  }

  public function setDefaultValues() {
    if ($this->_mode) {
      $defaults = $this->_values;

      $config = CRM_Core_Config::singleton();
      // set default country from config if no country set
      if (!CRM_Utils_Array::value("billing_country_id-{$this->_bltID}", $defaults)) {
        $defaults["billing_country_id-{$this->_bltID}"] = $config->defaultContactCountry;
      }

      if (!CRM_Utils_Array::value("billing_state_province_id-{$this->_bltID}", $defaults)) {
        $defaults["billing_state_province_id-{$this->_bltID}"] = $config->defaultContactStateProvince;
      }

      $billingDefaults = $this->getProfileDefaults('Billing', $this->_contactId);
      $defaults = array_merge($defaults, $billingDefaults);

      // now fix all state country selectors, set correct state based on country
      CRM_Core_BAO_Address::fixAllStateSelects($this, $defaults);
    }

    // Set $newCredit variable in template to control whether link to credit card mode is included
    CRM_Core_Payment::allowBackofficeCreditCard($this);
  }

  public function buildQuickForm() {
    $ccPane = NULL;
    if ($this->_mode) {
      if (CRM_Utils_Array::value('payment_type', $this->_processors) & CRM_Core_Payment::PAYMENT_TYPE_DIRECT_DEBIT
      ) {
        $ccPane = array(ts('Direct Debit Information') => 'DirectDebit');
      }
      else {
        $ccPane = array(ts('Credit Card Information') => 'CreditCard');
      }
      $defaults = $this->_values;
      $showAdditionalInfo = FALSE;

      foreach ($ccPane as $name => $type) {
        if ($this->_formType == $type ||
          CRM_Utils_Array::value("hidden_{$type}", $_POST) ||
          CRM_Utils_Array::value("hidden_{$type}", $defaults)
        ) {
          $showAdditionalInfo = TRUE;
          $allPanes[$name]['open'] = 'true';
        }

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

        $allPanes[$name] = array(
          'url' => CRM_Utils_System::url('civicrm/payment/add', $urlParams),
          'open' => $open,
          'id' => $type
        );

        if ($type == 'CreditCard') {
          $this->add('hidden', 'hidden_CreditCard', 1);
          CRM_Core_Payment_Form::buildCreditCard($this, TRUE);
        }
        elseif ($type == 'DirectDebit') {
          $this->add('hidden', 'hidden_DirectDebit', 1);
          CRM_Core_Payment_Form::buildDirectDebit($this, TRUE);
        }
        $qfKey = $this->controller->_key;
        $this->assign('qfKey', $qfKey);
        $this->assign('allPanes', $allPanes);
        $this->assign('showAdditionalInfo', $showAdditionalInfo);

        if ($this->_formType) {
          $this->assign('formType', $this->_formType);
          return;
        }
      }
    }
    $attributes = CRM_Core_DAO::getAttribute('CRM_Financial_DAO_FinancialTrxn');

    $this->add('select', 'payment_processor_id', ts('Payment Processor'), $this->_processors, NULL);
    $this->add('select', 'financial_type_id',
      ts('Financial Type'),
      array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::financialType(),
      TRUE
    );
    $label = ($this->_refund) ? 'Refund Amount' : 'Payment Amount';
    $this->addMoney('total_amount',
      ts('%1', array(1 => $label)),
      FALSE,
      $attributes['total_amount'],
      TRUE, 'currency', NULL
    );

    if (!$this->_mode) {
      $this->add('select', 'payment_instrument_id',
        ts('Paid By'),
        array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::paymentInstrument(),
        TRUE, array('onChange' => "return showHideByValue('payment_instrument_id','4','checkNumber','table-row','select',false);")
      );
    }

    $this->add('text', 'check_number', ts('Check Number'), $attributes['financial_trxn_check_number']);
    $trxnId = $this->add('text', 'trxn_id', ts('Transaction ID'), $attributes['trxn_id']);

    //add receipt for offline contribution
    $this->addElement('checkbox', 'is_email_receipt', ts('Send Receipt?'));

    $this->add('select', 'from_email_address', ts('Receipt From'), $this->_fromEmails);

    // add various dates
    $this->addDateTime('receive_date', ts('Received'), FALSE, array('formatType' => 'activityDateTime'));

    if ($this->_contactId && $this->_id) {
      if ($this->_component == 'event') {
        $eventId = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $this->_id, 'event_id', 'id');
        $event = CRM_Event_BAO_Event::getEvents(0, $eventId);
        $this->assign('eventName', $event[$eventId]);
      }
    }

    $this->assign('displayName', $this->_contributorDisplayName);
    $this->assign('component', $this->_component);
    $this->assign('email', $this->_contributorEmail);

    $this->add('text', 'fee_amount', ts('Fee Amount'),
      $attributes['fee_amount']
    );
    $this->addRule('fee_amount', ts('Please enter a valid monetary value for Fee Amount.'), 'money');

    $this->add('text', 'net_amount', ts('Net Amount'),
      $attributes['net_amount']
    );
    $this->addRule('net_amount', ts('Please enter a valid monetary value for Net Amount.'), 'money');

    $js = NULL;
    if (!$this->_mode) {
      $js = array('onclick' => "return verify( );");
    }

    $buttonName = $this->_refund ? 'Record Refund' : 'Record Payment';
    $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' => ts('%1', array(1 => $buttonName)),
          'js' => $js,
          'isDefault' => TRUE
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel')
        ),
      )
    );


    $mailingInfo = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
      'mailing_backend'
    );
    $this->assign('outBound_option', $mailingInfo['outBound_option']);

    $this->addFormRule(array('CRM_Contribute_Form_AdditionalPayment', 'formRule'), $this);
  }

  static function formRule($fields, $files, $self) {
    $errors = array();
    if ($self->_paymentType == 'owed' && $fields['total_amount'] > $self->_owed) {
      $errors['total_amount'] = ts('Payment amount cannot be greater than owed amount');
    }
    if ($self->_paymentType == 'refund' && $fields['total_amount'] != $self->_refund) {
      $errors['total_amount'] = ts('Refund amount should not differ');
    }
    $netAmt = $fields['total_amount'] - $fields['fee_amount'];
    if (!empty($fields['net_amount']) && $netAmt != $fields['net_amount']) {
      $errors['net_amount'] = ts('Net amount should be difference of payment amount and fee amount');
    }
    return $errors;
  }

  public function postProcess() {
    $participantId = NULL;
    if ($this->_component == 'event') {
      $participantId = $this->_id;
    }
    if ($this->_mode) {
      // process credit card
    }
    else {
      $submittedValues = $this->controller->exportValues($this->_name);
      $result = CRM_Contribute_BAO_Contribution::recordAdditionPayment($this->_contributionId, $submittedValues, $this->_paymentType, $participantId);

      // email sending
      if (!empty($result) && CRM_Utils_Array::value('is_email_receipt', $submittedValues)) {
        $submittedValues['contact_id'] = $this->_contactID;
        $submittedValues['contribution_id'] = $this->_contributionId;

        // to get 'from email id' for send receipt
        $this->fromEmailId = $submittedValues['from_email_address'];
        $sendReceipt = self::emailReceipt($this, $submittedValues);
      }
    }
  }

  static function emailReceipt(&$form, &$params) {
    // email receipt sending
  }
}