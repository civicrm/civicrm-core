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

use Civi\Api4\ContributionSoft;
use Civi\Api4\Membership;
use Civi\Api4\OptionValue;
use Civi\Api4\Order;
use Civi\Api4\Payment;

/**
 * This class generates form components for Membership Renewal
 */
class CRM_Member_Form_MembershipRenewal extends CRM_Member_Form {

  /**
   * Display name of the member.
   *
   * @var string
   */
  protected $_memberDisplayName = NULL;

  /**
   * email of the person paying for the membership (used for receipts)
   *
   * @var string
   */
  protected $_memberEmail = NULL;

  /**
   * Contact ID of the member.
   *
   *
   * @var int
   */
  public $_contactID = NULL;

  /**
   * Display name of the person paying for the membership (used for receipts)
   *
   * @var string
   */
  protected $_contributorDisplayName = NULL;

  /**
   * email of the person paying for the membership (used for receipts)
   *
   * @var string
   */
  protected $_contributorEmail = NULL;

  /**
   * email of the person paying for the membership (used for receipts)
   *
   * @var int
   */
  protected $_contributorContactID = NULL;

  /**
   * ID of the person the receipt is to go to
   *
   * @var int
   */
  protected $_receiptContactId = NULL;

  /**
   * context would be set to standalone if the contact is use is being selected from
   * the form rather than in the URL
   *
   * @var string
   */
  public $_context;

  /**
   * Has an email been sent.
   *
   * @var string
   */
  protected $isMailSent = FALSE;

  /**
   * Used in the wrangling of custom field data onto the form.
   *
   * There are known instances of extensions altering this array
   * in order to affect the custom data displayed & there is no
   * alternative recommendation.
   *
   * @var array
   */
  public $_groupTree;

  /**
   * Set entity fields to be assigned to the form.
   */
  protected function setEntityFields() {
  }

  /**
   * Set the delete message.
   *
   * We do this from the constructor in order to do a translation.
   */
  public function setDeleteMessage() {
  }

  /**
   * Set the renewal notification status message.
   */
  public function setRenewalMessage() {
    $statusMsg = ts('%1 membership for %2 has been renewed.', [1 => $this->getMembershipTypeValue('name'), 2 => $this->_memberDisplayName]);

    if ($this->isMailSent) {
      $statusMsg .= ' ' . ts('A renewal confirmation and receipt has been sent to %1.', [1 => $this->_contributorEmail]);
    }
    CRM_Core_Session::setStatus($statusMsg, ts('Complete'), 'success');
  }

  /**
   * Preprocess form.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {

    // This string makes up part of the css class names, differentiating them (not sure why) from the membership fields.
    $this->assign('formClass', 'membershiprenew');
    parent::preProcess();

    // @todo - we should store this as a property & re-use in setDefaults - for now that's a bigger change.
    $currentMembership = civicrm_api3('Membership', 'getsingle', ['id' => $this->_id]);
    CRM_Member_BAO_Membership::fixMembershipStatusBeforeRenew($currentMembership);

    $this->assign('endDate', $this->getMembershipValue('end_date'));
    $this->assign('membershipStatus', $this->getMembershipValue('membership_status_id:name'));

    if ($this->_mode) {
      if (!$this->getMembershipValue('membership_type_id.minimum_fee')) {
        $statusMsg = ts('Membership Renewal using a credit card requires a Membership fee. Since there is no fee associated with the selected membership type, you can use the normal renewal mode.');
        CRM_Core_Session::setStatus($statusMsg, '', 'info');
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/membership',
          "reset=1&action=renew&cid={$this->_contactID}&id={$this->_id}&context=membership"
        ));
      }
    }

    $this->setTitle(ts('Renew Membership'));

    parent::preProcess();
  }

  /**
   * Set default values for the form.
   * the default values are retrieved from the database
   *
   * @return array
   *   Default values.
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues() {

    $defaults = parent::setDefaultValues();

    // set renewal_date and receive_date to today in correct input format (setDateDefaults uses today if no value passed)
    $now = CRM_Utils_Time::date('Y-m-d');
    $defaults['renewal_date'] = $now;
    $defaults['receive_date'] = $now . ' ' . CRM_Utils_Time::date('H:i:s');

    if ($defaults['id']) {
      $defaults['record_contribution'] = CRM_Member_BAO_MembershipPayment::getLatestContributionIDFromLineitemAndFallbackToMembershipPayment($defaults['id']);
    }

    $defaults['financial_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $this->getMembershipTypeID(), 'financial_type_id');

    //CRM-13420
    if (empty($defaults['payment_instrument_id'])) {
      $defaults['payment_instrument_id'] = key(CRM_Core_OptionGroup::values('payment_instrument', FALSE, FALSE, FALSE, 'AND is_default = 1'));
    }

    $defaults['total_amount'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency(CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
      $this->getMembershipTypeID(),
      'minimum_fee'
    ) ?? 0);

    $defaults['record_contribution'] = 0;
    $defaults['num_terms'] = 1;
    $defaults['send_receipt'] = 0;

    //set Soft Credit Type to Gift by default
    $defaults['soft_credit_type_id'] = OptionValue::get(FALSE)
      ->addSelect('value')
      ->addWhere('option_group_id:name', '=', 'soft_credit_type')
      ->addWhere('name', '=', 'gift')
      ->execute()
      ->first()['value'] ?? NULL;

    $this->assign('renewalDate', $defaults['renewal_date']);
    $this->assign('member_is_test', $defaults['member_is_test'] ?? NULL);

    if ($this->_mode) {
      $defaults = $this->getBillingDefaults($defaults);
    }
    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {

    parent::buildQuickForm();
    $this->addCustomDataToForm();

    $defaults = parent::setDefaultValues();
    $this->assign('customDataType', 'Membership');
    $this->assign('customDataSubType', $this->getMembershipTypeID());
    $this->assign('entityID', $this->_id);
    $selOrgMemType[0][0] = $selMemTypeOrg[0] = ts('- select -');

    $allMembershipInfo = [];

    // CRM-21485
    if (is_array($defaults['membership_type_id'])) {
      $defaults['membership_type_id'] = $defaults['membership_type_id'][1];
    }

    //CRM-16950
    $taxRate = $this->getTaxRateForFinancialType($this->allMembershipTypeDetails[$defaults['membership_type_id']]['financial_type_id']);

    $contactField = $this->addEntityRef('contact_id', ts('Member'), ['create' => TRUE, 'api' => ['extra' => ['email']]], TRUE);
    $contactField->freeze();

    // auto renew options if enabled for the membership
    $options = CRM_Core_SelectValues::memberAutoRenew();

    foreach ($this->allMembershipTypeDetails as $key => $values) {
      if (!empty($values['is_active'])) {
        if ($this->_mode && empty($values['minimum_fee'])) {
          continue;
        }
        else {
          $memberOfContactId = $values['member_of_contact_id'] ?? NULL;
          if (empty($selMemTypeOrg[$memberOfContactId])) {
            $selMemTypeOrg[$memberOfContactId] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
              $memberOfContactId,
              'display_name',
              'id'
            );

            $selOrgMemType[$memberOfContactId][0] = ts('- select -');
          }
          if (empty($selOrgMemType[$memberOfContactId][$key])) {
            $selOrgMemType[$memberOfContactId][$key] = $values['title'] ?? NULL;
          }
        }

        //CRM-16950
        $taxAmount = NULL;
        $totalAmount = $values['minimum_fee'] ?? 0;
        // @todo - feels a bug - we use taxRate from the form default rather than from the specified type?!?
        if ($this->getTaxRateForFinancialType($values['financial_type_id'])) {
          $taxAmount = ($taxRate / 100) * ($values['minimum_fee'] ?? 0);
          $totalAmount = $totalAmount + $taxAmount;
        }

        // build membership info array, which is used to set the payment information block when
        // membership type is selected.
        $allMembershipInfo[$key] = [
          'financial_type_id' => $values['financial_type_id'] ?? NULL,
          'total_amount' => CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($totalAmount),
          'total_amount_numeric' => $totalAmount,
          'tax_message' => $taxAmount ? ts("Includes %1 amount of %2", [1 => $this->getSalesTaxTerm(), 2 => CRM_Utils_Money::format($taxAmount)]) : $taxAmount,
        ];

        if (!empty($values['auto_renew'])) {
          $allMembershipInfo[$key]['auto_renew'] = $options[$values['auto_renew']];
        }
      }
    }

    $this->assign('allMembershipInfo', json_encode($allMembershipInfo));

    if ($this->getMembershipTypeID()) {
      $this->assign('orgName', $selMemTypeOrg[$this->allMembershipTypeDetails[$this->getMembershipTypeID()]['member_of_contact_id']]);
      $this->assign('memType', $this->allMembershipTypeDetails[$this->getMembershipTypeID()]['name']);
    }

    // force select of organization by default, if only one organization in
    // the list
    if (count($selMemTypeOrg) == 2) {
      unset($selMemTypeOrg[0], $selOrgMemType[0][0]);
    }
    //sort membership organization and type, CRM-6099
    natcasesort($selMemTypeOrg);
    foreach ($selOrgMemType as $index => $orgMembershipType) {
      natcasesort($orgMembershipType);
      $selOrgMemType[$index] = $orgMembershipType;
    }

    $js = ['onChange' => "setPaymentBlock(); CRM.buildCustomData('Membership', this.value);"];
    $sel = &$this->addElement('hierselect',
      'membership_type_id',
      ts('Renewal Membership Organization and Type'), $js
    );

    $sel->setOptions([$selMemTypeOrg, $selOrgMemType]);
    $elements = [];
    if ($sel) {
      $elements[] = $sel;
    }

    $this->applyFilter('__ALL__', 'trim');

    // Only add renewal date if membership is not current
    if (!Membership::get(FALSE)
      ->addSelect('status_id.is_current_member')
      ->addWhere('id', '=', $this->getMembershipID())
      ->execute()
      ->first()['status_id.is_current_member']) {
      $this->add('datepicker', 'renewal_date', ts('Renewal Date'), [], FALSE, ['time' => FALSE]);
    }

    $this->add('select', 'financial_type_id', ts('Financial Type'),
      ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::financialType()
    );

    $this->add('number', 'num_terms', ts('Extend Membership by'), ['onchange' => "setPaymentBlock();"], TRUE);
    $this->addRule('num_terms', ts('Please enter a whole number for how many periods to renew.'), 'integer');

    if (CRM_Core_Permission::access('CiviContribute') && !$this->_mode) {
      $this->addElement('checkbox', 'record_contribution', ts('Record Renewal Payment?'), NULL, ['onclick' => "checkPayment();"]);

      $this->add('text', 'total_amount', ts('Amount'));
      $this->addRule('total_amount', ts('Please enter a valid amount.'), 'money');

      $this->add('datepicker', 'receive_date', ts('Contribution Date'), [], FALSE, ['time' => TRUE]);

      $this->add('select', 'payment_instrument_id', ts('Payment Method'),
        ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::paymentInstrument(),
        FALSE
      );

      $this->add('text', 'trxn_id', ts('Transaction ID'));
      $this->addRule('trxn_id', ts('Transaction ID already exists in Database.'),
        'objectExists', ['CRM_Contribute_DAO_Contribution', $this->_id, 'trxn_id']
      );

      $this->add('select', 'contribution_status_id', ts('Payment Status'),
        CRM_Contribute_BAO_Contribution_Utils::getPendingAndCompleteStatuses()
      );

      $this->add('text', 'check_number', ts('Check Number'),
        CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution', 'check_number')
      );
    }
    else {
      $this->add('text', 'total_amount', ts('Amount'));
      $this->addRule('total_amount', ts('Please enter a valid amount.'), 'money');
    }
    $this->addElement('checkbox', 'send_receipt', ts('Send Confirmation and Receipt?'), NULL,
      ['onclick' => "showHideByValue( 'send_receipt', '', 'notice', 'table-row', 'radio', false ); showHideByValue( 'send_receipt', '', 'fromEmail', 'table-row', 'radio',false);"]
    );

    $fromEmailSelect = $this->add('select', 'from_email_address', ts('Receipt From'), $this->_fromEmails);
    $fromEmailSelect->setOptionTextEscaped();

    $this->add('textarea', 'receipt_text', ts('Renewal Message'));

    // Retrieve the name and email of the contact - this will be the TO for receipt email
    [$this->_contributorDisplayName, $this->_contributorEmail] = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);
    $this->assign('email', $this->_contributorEmail);
    // The member form uses emailExists. Assigning both while we transition / synchronise.
    $this->assign('emailExists', $this->_contributorEmail);

    $mailingInfo = Civi::settings()->get('mailing_backend');
    $this->assign('outBound_option', $mailingInfo['outBound_option']);

    if (CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $this->_id, 'contribution_recur_id')) {
      if (CRM_Member_BAO_Membership::isCancelSubscriptionSupported($this->_id)) {
        $this->assign('cancelAutoRenew',
          CRM_Utils_System::url('civicrm/contribute/unsubscribe', "reset=1&mid={$this->_id}")
        );
      }
    }
    $this->addFormRule(['CRM_Member_Form_MembershipRenewal', 'formRule'], $this);
    $this->addElement('checkbox', 'is_different_contribution_contact', ts('Record Payment from a Different Contact?'));
    $this->addSelect('soft_credit_type_id', ['entity' => 'contribution_soft']);
    $this->addEntityRef('soft_credit_contact_id', ts('Payment From'), ['create' => TRUE]);
  }

  /**
   * Validation.
   *
   * @param array $params
   *   (ref.) an assoc array of name/value pairs.
   * @param $files
   * @param self $self
   *
   * @return bool|array
   *   mixed true or array of errors
   * @throws \CRM_Core_Exception
   */
  public static function formRule($params, $files, $self) {
    $errors = [];
    if ($params['membership_type_id'][0] == 0) {
      $errors['membership_type_id'] = ts('Oops. It looks like you are trying to change the membership type while renewing the membership. Please click the "change membership type" link, and select a Membership Organization.');
    }
    if ($params['membership_type_id'][1] == 0) {
      $errors['membership_type_id'] = ts('Oops. It looks like you are trying to change the membership type while renewing the membership. Please click the "change membership type" link and select a Membership Type from the list.');
    }

    // CRM-20571
    // Get the Join Date from Membership info as it is not available in the Renewal form
    $joinDate = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $self->_id, 'join_date');

    // CRM-20571: Check if the renewal date is not before Join Date, if it is then add to 'errors' array
    // The fields in Renewal form come into this routine in $params array. 'renewal_date' is in the form
    // We process both the dates before comparison using CRM utils so that they are in same date format
    // If renewal date is empty we renew based on existing membership end date and 'num_terms'.
    // If renewal date is specified it will always renew from that date.
    if (!empty($params['renewal_date'])) {
      if ($params['renewal_date'] < $joinDate) {
        $errors['renewal_date'] = ts('Renewal date must be the same or later than Member Since.');
      }
    }

    //total amount condition arise when membership type having no
    //minimum fee
    if (isset($params['record_contribution'])) {
      if (!$params['financial_type_id']) {
        $errors['financial_type_id'] = ts('Please select a Financial Type.');
      }
      if (!$params['total_amount']) {
        $errors['total_amount'] = ts('Please enter a Contribution Amount.');
      }
      if (empty($params['payment_instrument_id'])) {
        $errors['payment_instrument_id'] = ts('Payment Method is a required field.');
      }
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the renewal form.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess(): void {
    // get the submitted form values.
    $this->_params = $this->controller->exportValues($this->_name);

    try {
      $this->submit();
      $this->setRenewalMessage();
    }
    catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
      CRM_Core_Session::singleton()->setStatus($e->getMessage());
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/membership',
        "reset=1&action=renew&cid={$this->_contactID}&id={$this->_id}&context=membership&mode={$this->_mode}"
      ));
    }
  }

  /**
   * Process form submission.
   *
   * This function is also accessed by a unit test.
   *
   * @throws \CRM_Core_Exception
   */
  protected function submit() {
    $this->storeContactFields($this->_params);
    $this->beginPostProcess();
    $now = CRM_Utils_Date::getToday(NULL, 'YmdHis');
    $this->processBillingAddress($this->getContributionContactID(), (string) $this->_contributorEmail);
    $this->_params['total_amount'] = $this->_params['total_amount'] ?? CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $this->getMembershipTypeID(), 'minimum_fee');
    $contributionRecurID = NULL;
    $this->assign('receiptType', 'membership renewal');
    $this->_params['currencyID'] = CRM_Core_Config::singleton()->defaultCurrency;
    $this->_params['invoice_id'] = $this->getInvoiceID();

    if ($this->getSubmittedValue('send_receipt')) {
      $this->_params['receipt_date'] = $now;
    }
    else {
      $this->_params['receipt_date'] = NULL;
    }

    if ($this->_mode) {
      $this->_params['register_date'] = $now;
      $this->_params['description'] = ts("Contribution submitted by a staff person using member's credit card for renewal");
      $this->_params['amount'] = $this->_params['total_amount'];

      // at this point we've created a contact and stored its address etc
      // all the payment processors expect the name and address to be in the passed params
      // so we copy stuff over to first_name etc.
      // @todo - probably do not need to add in _params - just need to check what we added above.
      $paymentParams = $this->prepareParamsForPaymentProcessor($this->getSubmittedValues()) + $this->_params;
      if ($this->getSubmittedValue('send_receipt')) {
        $paymentParams['email'] = $this->_contributorEmail;
      }

      $paymentParams['contactID'] = $this->_contributorContactID;

      CRM_Core_Payment_Form::mapParams(NULL, $this->_params, $paymentParams, TRUE);

      if (!empty($this->_params['auto_renew'])) {

        $contributionRecurParams = $this->processRecurringContribution([
          'contact_id' => $this->_contributorContactID,
          'amount' => $this->_params['total_amount'],
          'contribution_status_id' => 'Pending',
          'payment_processor_id' => $this->getPaymentProcessorID(),
          'financial_type_id' => $this->getFinancialTypeID(),
          'is_email_receipt' => (bool) $this->getSubmittedValue('send_receipt'),
          'payment_instrument_id' => $this->getPaymentInstrumentID(),
          'invoice_id' => $this->getInvoiceID(),
        ], $paymentParams['membership_type_id'][1]);

        $contributionRecurID = $contributionRecurParams['contributionRecurID'];
        $paymentParams = array_merge($paymentParams, $contributionRecurParams);
      }

      $paymentParams['invoiceID'] = $paymentParams['invoice_id'];

      $payment = $this->_paymentProcessor['object'];
      $paymentParams['currency'] = $this->getCurrency();
      $payment->setBackOffice(TRUE);
      $result = $payment->doPayment($paymentParams);
      $this->_params = array_merge($this->_params, $result);

      $this->_params['contribution_status_id'] = $result['payment_status_id'];
      $this->_params['trxn_id'] = $result['trxn_id'];
      $this->set('params', $this->_params);
      $this->assign('trxn_id', $result['trxn_id']);
    }

    $pending = ($this->_params['contribution_status_id'] == CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'));

    // if contribution status is pending then set pay later
    $this->_params['is_pay_later'] = $pending;

    $membershipValues = [
      'membership_type_id' => $this->getMembershipTypeID(),
      'modified_id' => $this->_contactID,
      // Since we are renewing, make status override false.
      'is_override' => FALSE,
    ] + $this->getSubmittedCustomFields(4, 'Membership');
    if ($contributionRecurID) {
      $membershipValues['contribution_recur_id'] = $contributionRecurID;
    }
    // Paylater/IPN renewals are renewed when the payment completes - CRM-4556.
    $renewalDates = $pending ? [] : $this->getRenewalDates();

    if (!empty($this->_params['record_contribution']) || $this->_mode) {
      $this->setContributionID($this->saveOrder($membershipValues, $renewalDates, $pending, $contributionRecurID));
    }
    else {
      Membership::update(FALSE)
        ->addWhere('id', '=', $this->getMembershipID())
        ->setValues($membershipValues + $renewalDates + ['membership_activity_status' => $pending ? 'Scheduled' : 'Completed'])
        ->execute();
    }

    if ($this->getSubmittedValue('send_receipt')) {
      $this->sendReceipt();
    }
  }

  /**
   * Create the renewal contribution, recording a payment if it is completed.
   *
   * The membership's other values are saved now, but its dates change when
   * the contribution completes, in \Civi\Membership\OrderCompleteSubscriber.
   *
   * @param array $membershipValues
   * @param array $renewalDates
   *   Dates for OrderCompleteSubscriber to apply in place of its own.
   * @param bool $pending
   * @param int|null $contributionRecurID
   *
   * @return int
   *   Contribution ID.
   *
   * @throws \CRM_Core_Exception
   */
  private function saveOrder(array $membershipValues, array $renewalDates, bool $pending, ?int $contributionRecurID): int {
    foreach ($this->getOrder()->getLineItems() as $index => $lineItem) {
      if (($lineItem['entity_id'] ?? NULL) != $this->getMembershipID()) {
        continue;
      }
      if ($pending) {
        $membershipValues['membership_activity_status'] = 'Scheduled';
      }
      else {
        $this->order->setLineItemValue('order_completion_metadata', [
          'entity' => $renewalDates,
        ], $index);
      }
      $this->order->setEntityParameters($membershipValues, $index);
    }

    $contribution = Order::create(FALSE)
      ->setContributionValues($this->getContributionValues() + [
        'contact_id' => $this->_contributorContactID,
        'financial_type_id' => $this->getFinancialTypeID(),
        'is_pay_later' => $pending,
        'check_number' => $this->_params['check_number'] ?? NULL,
        'trxn_id' => $this->_params['trxn_id'] ?? NULL,
        'contribution_recur_id' => $contributionRecurID,
        'revenue_recognition_date' => $this->getDeferredRevenueRecognitionDate($renewalDates['start_date'] ?? NULL) ?: NULL,
        'contribution_status_id:name' => 'Pending',
      ])
      ->setLineItems($this->order->getLineItemsForV4OrderApi())
      ->execute()->single();

    if ($this->_contributorContactID != $this->_contactID && !empty($this->_params['soft_credit_type_id'])) {
      ContributionSoft::create(FALSE)
        ->setValues([
          'contribution_id' => $contribution['id'],
          'contact_id' => $this->_contactID,
          'soft_credit_type_id' => $this->_params['soft_credit_type_id'],
          'amount' => $contribution['total_amount'],
          'currency' => $contribution['currency'],
        ])
        ->execute();
    }

    if (!$pending) {
      Payment::create(FALSE)
        ->setNotificationForCompleteOrder(FALSE)
        ->setNotificationForPayment(FALSE)
        ->setValues([
          'contribution_id' => $contribution['id'],
          'total_amount' => $contribution['total_amount'],
          'trxn_date' => $this->getReceiveDate(),
          'payment_processor_id' => $this->_mode ? $this->getPaymentProcessorID() : NULL,
          'payment_instrument_id' => $this->getPaymentInstrumentID(),
          'trxn_id' => $this->_params['trxn_id'] ?? NULL,
          'fee_amount' => $this->_params['fee_amount'] ?? NULL,
          'check_number' => $this->_params['check_number'] ?? NULL,
          'card_type_id' => $this->getCardTypeID(),
          'pan_truncation' => $this->getPanTruncation(),
        ])
        ->execute();
    }
    return $contribution['id'];
  }

  /**
   * Send a receipt.
   *
   * @throws \CRM_Core_Exception
   */
  protected function sendReceipt() {
    $receiptFrom = $this->_params['from_email_address'];
    //get the group Tree
    $this->_groupTree = CRM_Core_BAO_CustomGroup::getTree('Membership', NULL, $this->_id, FALSE, $this->getMembershipTypeID());

    // retrieve custom data
    $customFields = $customValues = $fo = [];
    foreach ($this->_groupTree as $groupID => $group) {
      if ($groupID === 'info') {
        continue;
      }
      foreach ($group['fields'] as $k => $field) {
        $field['title'] = $field['label'];
        $customFields["custom_{$k}"] = $field;
      }
    }
    $members = [['member_id', '=', $this->getMembershipID(), 0, 0]];
    // check whether its a test drive
    if ($this->_mode === 'test') {
      $members[] = ['member_test', '=', 1, 0, 0];
    }
    CRM_Core_BAO_UFGroup::getValues($this->_contactID, $customFields, $customValues, FALSE, $members);

    $this->assign('customValues', $customValues);

    if ($this->_mode) {
      $this->assign('address', CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters($this->_params));

      $this->assign('is_pay_later', 0);
      $this->assign('isPrimary', 1);
      if ($this->_mode === 'test') {
        $this->assign('action', '1024');
      }
    }

    // This is being replaced by userEnteredText.
    $this->assign('receipt_text', $this->getSubmittedValue('receipt_text'));
    [$this->isMailSent] = CRM_Core_BAO_MessageTemplate::sendTemplate(
      [
        'workflow' => 'membership_offline_receipt',
        'from' => $receiptFrom,
        'toName' => $this->_contributorDisplayName,
        'toEmail' => $this->_contributorEmail,
        'isTest' => $this->_mode === 'test',
        'PDFFilename' => ts('receipt') . '.pdf',
        'isEmailPdf' => Civi::settings()->get('invoice_is_email_pdf'),
        'modelProps' => [
          'userEnteredText' => $this->getSubmittedValue('receipt_text'),
          'contactID' => $this->_receiptContactId,
          'contributionID' => $this->getContributionID(),
          'membershipID' => $this->getMembershipID(),
        ],
      ]
    );
  }

  /**
   * Get the membership dates after this renewal.
   *
   * Pending and cancelled memberships keep their current dates - CRM-2395.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  private function getRenewalDates(): array {
    $currentMembership = Membership::get(FALSE)
      ->addSelect('join_date', 'start_date', 'end_date', 'status_id:name', 'status_id.is_current_member')
      ->addWhere('id', '=', $this->getMembershipID())
      ->execute()
      ->single();
    $dates = [
      'join_date' => $currentMembership['join_date'],
      'start_date' => $currentMembership['start_date'],
      'end_date' => $currentMembership['end_date'],
    ];
    if (in_array($currentMembership['status_id:name'], ['Pending', 'Cancelled'], TRUE)) {
      return $dates;
    }

    // Only pass through "changeToday" for non-current memberships as it's not used otherwise
    $changeToday = NULL;
    if (!$currentMembership['status_id.is_current_member']) {
      $changeToday = $this->getSubmittedValue('renewal_date') ?: date('Ymd', strtotime($currentMembership['end_date'] . '+1 day'));
    }
    // CRM-7297 Membership Upsell - calculate dates based on new membership type
    $renewalDates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType($this->getMembershipID(),
      $changeToday,
      $this->getMembershipTypeID(),
      $this->getNumRenewTerms()
    );
    return [
      'end_date' => $renewalDates['end_date'] ?? NULL,
      'start_date' => $currentMembership['status_id.is_current_member'] ? $currentMembership['start_date'] : ($renewalDates['start_date'] ?? NULL),
    ] + $dates;
  }

  /**
   * Get the source to record against the contribution.
   *
   * @return string
   */
  protected function getSource(): string {
    if ($this->getSubmittedValue('source')) {
      return $this->getSubmittedValue('source');
    }
    [$userName] = CRM_Contact_BAO_Contact_Location::getEmailDetails(CRM_Core_Session::singleton()->get('userID'));
    $userName = htmlentities((string) $userName);
    return "{$this->getMembershipTypeValue('name')} Membership: Offline membership renewal (by {$userName})";
  }

}
