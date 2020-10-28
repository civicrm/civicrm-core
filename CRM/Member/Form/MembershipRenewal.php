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
   * The name of the renewed membership type.
   *
   * @var string
   */
  protected $membershipTypeName = '';

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
    $statusMsg = ts('%1 membership for %2 has been renewed.', [1 => $this->membershipTypeName, 2 => $this->_memberDisplayName]);

    if ($this->isMailSent) {
      $statusMsg .= ' ' . ts('A renewal confirmation and receipt has been sent to %1.', [1 => $this->_contributorEmail]);
    }
    CRM_Core_Session::setStatus($statusMsg, ts('Complete'), 'success');
  }

  /**
   * Preprocess form.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function preProcess() {

    // This string makes up part of the class names, differentiating them (not sure why) from the membership fields.
    $this->assign('formClass', 'membershiprenew');
    parent::preProcess();

    // @todo - we should store this as a property & re-use in setDefaults - for now that's a bigger change.
    $currentMembership = civicrm_api3('Membership', 'getsingle', ['id' => $this->_id]);
    CRM_Member_BAO_Membership::fixMembershipStatusBeforeRenew($currentMembership);

    $this->assign('endDate', $currentMembership['end_date']);
    $this->assign('membershipStatus',
      CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus',
        CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership',
          $this->_id, 'status_id'
        ),
        'name'
      )
    );

    if ($this->_mode) {
      $membershipFee = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $this->_memType, 'minimum_fee');
      if (!$membershipFee) {
        $statusMsg = ts('Membership Renewal using a credit card requires a Membership fee. Since there is no fee associated with the selected membership type, you can use the normal renewal mode.');
        CRM_Core_Session::setStatus($statusMsg, '', 'info');
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/membership',
          "reset=1&action=renew&cid={$this->_contactID}&id={$this->_id}&context=membership"
        ));
      }
    }

    // when custom data is included in this page
    if (!empty($_POST['hidden_custom'])) {
      CRM_Custom_Form_CustomData::preProcess($this, NULL, $this->_memType, 1, 'Membership', $this->_id);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

    CRM_Utils_System::setTitle(ts('Renew Membership'));

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
    $now = date('Y-m-d');
    $defaults['renewal_date'] = $now;
    $defaults['receive_date'] = $now . ' ' . date('H:i:s');

    if ($defaults['id']) {
      $defaults['record_contribution'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipPayment',
        $defaults['id'],
        'contribution_id',
        'membership_id'
      );
    }

    $defaults['financial_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $this->_memType, 'financial_type_id');

    //CRM-13420
    if (empty($defaults['payment_instrument_id'])) {
      $defaults['payment_instrument_id'] = key(CRM_Core_OptionGroup::values('payment_instrument', FALSE, FALSE, FALSE, 'AND is_default = 1'));
    }

    $defaults['total_amount'] = CRM_Utils_Money::format(CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
      $this->_memType,
      'minimum_fee'
    ), NULL, '%a');

    $defaults['record_contribution'] = 0;
    $defaults['num_terms'] = 1;
    $defaults['send_receipt'] = 0;

    //set Soft Credit Type to Gift by default
    $scTypes = CRM_Core_OptionGroup::values('soft_credit_type');
    $defaults['soft_credit_type_id'] = CRM_Utils_Array::value(ts('Gift'), array_flip($scTypes));

    $renewalDate = $defaults['renewal_date'] ?? NULL;
    $this->assign('renewalDate', $renewalDate);
    $this->assign('member_is_test', CRM_Utils_Array::value('member_is_test', $defaults));

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

    $defaults = parent::setDefaultValues();
    $this->assign('customDataType', 'Membership');
    $this->assign('customDataSubType', $this->_memType);
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
            $selOrgMemType[$memberOfContactId][$key] = $values['name'] ?? NULL;
          }
        }

        //CRM-16950
        $taxAmount = NULL;
        $totalAmount = $values['minimum_fee'] ?? NULL;
        // @todo - feels a bug - we use taxRate from the form default rather than from the specified type?!?
        if ($this->getTaxRateForFinancialType($values['financial_type_id'])) {
          $taxAmount = ($taxRate / 100) * CRM_Utils_Array::value('minimum_fee', $values);
          $totalAmount = $totalAmount + $taxAmount;
        }

        // build membership info array, which is used to set the payment information block when
        // membership type is selected.
        $allMembershipInfo[$key] = [
          'financial_type_id' => $values['financial_type_id'] ?? NULL,
          'total_amount' => CRM_Utils_Money::format($totalAmount, NULL, '%a'),
          'total_amount_numeric' => $totalAmount,
          'tax_message' => $taxAmount ? ts("Includes %1 amount of %2", [1 => $this->getSalesTaxTerm(), 2 => CRM_Utils_Money::format($taxAmount)]) : $taxAmount,
        ];

        if (!empty($values['auto_renew'])) {
          $allMembershipInfo[$key]['auto_renew'] = $options[$values['auto_renew']];
        }
      }
    }

    $this->assign('allMembershipInfo', json_encode($allMembershipInfo));

    if ($this->_memType) {
      $this->assign('orgName', $selMemTypeOrg[$this->allMembershipTypeDetails[$this->_memType]['member_of_contact_id']]);
      $this->assign('memType', $this->allMembershipTypeDetails[$this->_memType]['name']);
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

    $this->add('datepicker', 'renewal_date', ts('Date Renewal Entered'), [], FALSE, ['time' => FALSE]);

    $this->add('select', 'financial_type_id', ts('Financial Type'),
      ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::financialType()
    );

    $this->add('number', 'num_terms', ts('Extend Membership by'), ['onchange' => "setPaymentBlock();"], TRUE);
    $this->addRule('num_terms', ts('Please enter a whole number for how many periods to renew.'), 'integer');

    if (CRM_Core_Permission::access('CiviContribute') && !$this->_mode) {
      $this->addElement('checkbox', 'record_contribution', ts('Record Renewal Payment?'), NULL, ['onclick' => "checkPayment();"]);

      $this->add('text', 'total_amount', ts('Amount'));
      $this->addRule('total_amount', ts('Please enter a valid amount.'), 'money');

      $this->add('datepicker', 'receive_date', ts('Received'), [], FALSE, ['time' => TRUE]);

      $this->add('select', 'payment_instrument_id', ts('Payment Method'),
        ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::paymentInstrument(),
        FALSE, ['onChange' => "return showHideByValue('payment_instrument_id','4','checkNumber','table-row','select',false);"]
      );

      $this->add('text', 'trxn_id', ts('Transaction ID'));
      $this->addRule('trxn_id', ts('Transaction ID already exists in Database.'),
        'objectExists', ['CRM_Contribute_DAO_Contribution', $this->_id, 'trxn_id']
      );

      $this->add('select', 'contribution_status_id', ts('Payment Status'),
        CRM_Contribute_BAO_Contribution_Utils::getContributionStatuses('membership')
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

    $this->add('select', 'from_email_address', ts('Receipt From'), $this->_fromEmails);

    $this->add('textarea', 'receipt_text_renewal', ts('Renewal Message'));

    // Retrieve the name and email of the contact - this will be the TO for receipt email
    list($this->_contributorDisplayName,
      $this->_contributorEmail
      ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);
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
   * @param $self
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
    if (isset($params['renewal_date'])) {
      if ($params['renewal_date'] < $joinDate) {
        $errors['renewal_date'] = ts('Renewal date must be the same or later than Member since (Join Date).');
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
   * @throws \CiviCRM_API3_Exception
   */
  public function postProcess() {
    // get the submitted form values.
    $this->_params = $this->controller->exportValues($this->_name);
    $this->assignBillingName();

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
   * @throws \CiviCRM_API3_Exception
   */
  protected function submit() {
    $this->storeContactFields($this->_params);
    $this->beginPostProcess();
    $now = CRM_Utils_Date::getToday(NULL, 'YmdHis');
    $this->assign('receive_date', CRM_Utils_Array::value('receive_date', $this->_params, date('Y-m-d H:i:s')));
    $this->processBillingAddress();

    $this->_params['total_amount'] = CRM_Utils_Array::value('total_amount', $this->_params,
      CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $this->_memType, 'minimum_fee')
    );
    $this->_membershipId = $this->_id;
    $customFieldsFormatted = CRM_Core_BAO_CustomField::postProcess($this->_params,
      $this->_id,
      'Membership'
    );
    if (empty($this->_params['financial_type_id'])) {
      $this->_params['financial_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $this->_memType, 'financial_type_id');
    }
    $contributionRecurID = NULL;
    $this->assign('membershipID', $this->_id);
    $this->assign('contactID', $this->_contactID);
    $this->assign('module', 'Membership');
    $this->assign('receiptType', 'membership renewal');
    $this->_params['currencyID'] = CRM_Core_Config::singleton()->defaultCurrency;
    $this->_params['invoice_id'] = $this->_params['invoiceID'] = md5(uniqid(rand(), TRUE));

    if (!empty($this->_params['send_receipt'])) {
      $this->_params['receipt_date'] = $now;
      $this->assign('receipt_date', CRM_Utils_Date::mysqlToIso($this->_params['receipt_date']));
    }
    else {
      $this->_params['receipt_date'] = NULL;
    }

    if ($this->_mode) {
      $this->_params['register_date'] = $now;
      $this->_params['description'] = ts("Contribution submitted by a staff person using member's credit card for renewal");
      $this->_params['amount'] = $this->_params['total_amount'];
      $this->_params['payment_instrument_id'] = $this->_paymentProcessor['payment_instrument_id'];
      $this->_params['receive_date'] = $now;

      // at this point we've created a contact and stored its address etc
      // all the payment processors expect the name and address to be in the passed params
      // so we copy stuff over to first_name etc.
      $paymentParams = $this->_params;
      if (!empty($this->_params['send_receipt'])) {
        $paymentParams['email'] = $this->_contributorEmail;
      }

      $paymentParams['contactID'] = $this->_contributorContactID;

      CRM_Core_Payment_Form::mapParams($this->_bltID, $this->_params, $paymentParams, TRUE);

      if (!empty($this->_params['auto_renew'])) {

        $contributionRecurParams = $this->processRecurringContribution([
          'contact_id' => $this->_contributorContactID,
          'amount' => $this->_params['total_amount'],
          'contribution_status_id' => 'Pending',
          'payment_processor_id' => $this->_params['payment_processor_id'],
          'financial_type_id' => $this->_params['financial_type_id'],
          'is_email_receipt' => !empty($this->_params['send_receipt']),
          'payment_instrument_id' => $this->_params['payment_instrument_id'],
          'invoice_id' => $this->_params['invoice_id'],
        ], $paymentParams['membership_type_id'][1]);

        $contributionRecurID = $contributionRecurParams['contributionRecurID'];
        $paymentParams = array_merge($paymentParams, $contributionRecurParams);
      }

      $payment = $this->_paymentProcessor['object'];
      $result = $payment->doPayment($paymentParams);
      $this->_params = array_merge($this->_params, $result);

      $this->_params['contribution_status_id'] = $result['payment_status_id'];
      $this->_params['trxn_id'] = $result['trxn_id'];
      $this->_params['is_test'] = ($this->_mode === 'live') ? 0 : 1;
      $this->set('params', $this->_params);
      $this->assign('trxn_id', $result['trxn_id']);
    }

    $renewalDate = !empty($this->_params['renewal_date']) ? $renewalDate = $this->_params['renewal_date'] : NULL;

    // chk for renewal for multiple terms CRM-8750
    $numRenewTerms = 1;
    if (is_numeric(CRM_Utils_Array::value('num_terms', $this->_params))) {
      $numRenewTerms = $this->_params['num_terms'];
    }

    //if contribution status is pending then set pay later
    $this->_params['is_pay_later'] = FALSE;
    if ($this->_params['contribution_status_id'] == array_search('Pending', CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'label'))) {
      $this->_params['is_pay_later'] = 1;
    }

    $pending = ($this->_params['contribution_status_id'] == CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'));

    $membershipParams = [
      'id' => $this->_membershipId,
      'membership_type_id' => $this->_params['membership_type_id'][1],
      'modified_id' => $this->_contactID,
      'custom' => $customFieldsFormatted,
      'membership_activity_status' => ($pending || $this->_params['is_pay_later']) ? 'Scheduled' : 'Completed',
      // Since we are renewing, make status override false.
      'is_override' => FALSE,
    ];
    if ($contributionRecurID) {
      $membershipParams['contribution_recur_id'] = $contributionRecurID;
    }
    $membership = $this->processMembership($membershipParams, $renewalDate, $numRenewTerms, $pending);

    if (!empty($this->_params['record_contribution']) || $this->_mode) {
      // set the source
      [$userName] = CRM_Contact_BAO_Contact_Location::getEmailDetails(CRM_Core_Session::singleton()->get('userID'));
      $this->membershipTypeName = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $membership->membership_type_id,
        'name');
      $this->_params['contribution_source'] = "{$this->membershipTypeName} Membership: Offline membership renewal (by {$userName})";

      //create line items
      $this->_params = $this->setPriceSetParameters($this->_params);

      $this->_params = array_merge($this->_params, $this->getOrderParams());

      //assign contribution contact id to the field expected by recordMembershipContribution
      if ($this->_contributorContactID != $this->_contactID) {
        $this->_params['contribution_contact_id'] = $this->_contributorContactID;
        if (!empty($this->_params['soft_credit_type_id'])) {
          $this->_params['soft_credit'] = [
            'soft_credit_type_id' => $this->_params['soft_credit_type_id'],
            'contact_id' => $this->_contactID,
          ];
        }
      }
      $this->_params['contact_id'] = $this->_contactID;
      //recordMembershipContribution receives params as a reference & adds one variable. This is
      // not a great pattern & ideally it would not receive as a reference. We assign our params as a
      // temporary variable to avoid e-notice & to make it clear to future refactorer that
      // this function is NOT reliant on that var being set
      $temporaryParams = array_merge($this->_params, [
        'membership_id' => $membership->id,
        'contribution_recur_id' => $contributionRecurID,
      ]);
      //Remove `tax_amount` if it is not calculated.
      // ?? WHY - I haven't been able to figure out...
      if (CRM_Utils_Array::value('tax_amount', $temporaryParams) === 0.0) {
        unset($temporaryParams['tax_amount']);
      }
      CRM_Member_BAO_Membership::recordMembershipContribution($temporaryParams);
    }

    if (!empty($this->_params['send_receipt'])) {
      $this->sendReceipt($membership);
    }
  }

  /**
   * Send a receipt.
   *
   * @param array $membership
   *
   * @throws \CRM_Core_Exception
   */
  protected function sendReceipt($membership) {
    $receiptFrom = $this->_params['from_email_address'];

    if (!empty($this->_params['payment_instrument_id'])) {
      $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
      $this->_params['paidBy'] = $paymentInstrument[$this->_params['payment_instrument_id']];
    }
    //get the group Tree
    $this->_groupTree = CRM_Core_BAO_CustomGroup::getTree('Membership', NULL, $this->_id, FALSE, $this->_memType);

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
    $members = [['member_id', '=', $this->_membershipId, 0, 0]];
    // check whether its a test drive
    if ($this->_mode === 'test') {
      $members[] = ['member_test', '=', 1, 0, 0];
    }
    CRM_Core_BAO_UFGroup::getValues($this->_contactID, $customFields, $customValues, FALSE, $members);

    $this->assign_by_ref('formValues', $this->_params);
    if (!empty($this->_params['contribution_id'])) {
      $this->assign('contributionID', $this->_params['contribution_id']);
    }

    $this->assign('membership_name', CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
      $membership->membership_type_id
    ));
    $this->assign('customValues', $customValues);
    $this->assign('mem_start_date', CRM_Utils_Date::formatDateOnlyLong($membership->start_date));
    $this->assign('mem_end_date', CRM_Utils_Date::formatDateOnlyLong($membership->end_date));
    if ($this->_mode) {
      $this->assign('address', CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters(
        $this->_params,
        $this->_bltID
      ));
      $this->assign('contributeMode', 'direct');
      $this->assign('isAmountzero', 0);
      $this->assign('is_pay_later', 0);
      $this->assign('isPrimary', 1);
      $this->assign('receipt_text_renewal', $this->_params['receipt_text']);
      if ($this->_mode === 'test') {
        $this->assign('action', '1024');
      }
    }

    list($this->isMailSent) = CRM_Core_BAO_MessageTemplate::sendTemplate(
      [
        'groupName' => 'msg_tpl_workflow_membership',
        'valueName' => 'membership_offline_receipt',
        'contactId' => $this->_receiptContactId,
        'from' => $receiptFrom,
        'toName' => $this->_contributorDisplayName,
        'toEmail' => $this->_contributorEmail,
        'isTest' => $this->_mode === 'test',
      ]
    );
  }

  /**
   * Process membership.
   *
   * This is duplicated from the BAO class - on the basis that it's actually easier to divide & conquer when
   * it comes to clearing up really bad code.
   *
   * @param array $memParams
   * @param bool $changeToday
   * @param $numRenewTerms
   * @param bool $pending
   *
   * @return CRM_Member_BAO_Membership
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function processMembership($memParams, $changeToday, $numRenewTerms, $pending) {
    $allStatus = CRM_Member_PseudoConstant::membershipStatus();
    $ids = [];
    $currentMembership = civicrm_api3('Membership', 'getsingle', ['id' => $memParams['id']]);

    $memParams['join_date'] = $currentMembership['join_date'];
    // Do NOT do anything.
    //1. membership with status : PENDING/CANCELLED (CRM-2395)
    //2. Paylater/IPN renew. CRM-4556.
    if ($pending || in_array($currentMembership['status_id'], [
      array_search('Pending', $allStatus),
      // CRM-15475
      array_search('Cancelled', CRM_Member_PseudoConstant::membershipStatus(NULL, " name = 'Cancelled' ", 'name', FALSE, TRUE)),
    ])) {
      $memParams = array_merge($memParams, [
        'status_id' => $currentMembership['status_id'],
        'start_date' => $currentMembership['start_date'],
        'end_date' => $currentMembership['end_date'],
      ]);
      return CRM_Member_BAO_Membership::create($memParams);
    }

    $isMembershipCurrent = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus', $currentMembership['status_id'], 'is_current_member');

    // CRM-7297 Membership Upsell - calculate dates based on new membership type
    $dates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType($currentMembership['id'],
      $changeToday,
      $memParams['membership_type_id'],
      $numRenewTerms
    );
    $memParams = array_merge($memParams, [
      'end_date' => $dates['end_date'] ?? NULL,
      'start_date' => $isMembershipCurrent ? $currentMembership['start_date'] : ($dates['start_date'] ?? NULL),
      'log_start_date' => $dates['log_start_date'],
    ]);

    // Now Renew the membership
    if ($isMembershipCurrent) {
      // CURRENT Membership
      if (!empty($currentMembership['id'])) {
        $ids['membership'] = $currentMembership['id'];
      }
    }

    // @todo stop passing $ids (membership and userId may be set by this point)
    $membership = CRM_Member_BAO_Membership::create($memParams, $ids);

    // not sure why this statement is here, seems quite odd :( - Lobo: 12/26/2010
    // related to: http://forum.civicrm.org/index.php/topic,11416.msg49072.html#msg49072
    $membership->find(TRUE);

    return $membership;
  }

  /**
   * Get order related params.
   *
   * In practice these are contribution params but later they cann be used with the Order api.
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected function getOrderParams(): array {
    $order = new CRM_Financial_BAO_Order();
    $order->setPriceSelectionFromUnfilteredInput($this->_params);
    $order->setPriceSetID(self::getPriceSetID($this->_params));
    $order->setOverrideTotalAmount($this->_params['total_amount']);
    $order->setOverrideFinancialTypeID((int) $this->_params['financial_type_id']);
    return [
      'lineItems' => [$this->_priceSetId => $order->getLineItems()],
      // This is one of those weird & wonderful legacy params we aim to get rid of.
      'processPriceSet' => TRUE,
      'tax_amount' => $order->getTotalTaxAmount(),
    ];
  }

}
