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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
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
   */
  public $_context;

  /**
   * End date of renewed membership.
   *
   * @var string
   */
  protected $endDate = NULL;

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
   * An array to hold a list of datefields on the form
   * so that they can be converted to ISO in a consistent manner
   *
   * @var array
   */
  protected $_dateFields = array(
    'receive_date' => array('default' => 'now'),
  );

  /**
   * Pre-process form.
   *
   * @throws \Exception
   */
  public function preProcess() {

    // This string makes up part of the class names, differentiating them (not sure why) from the membership fields.
    $this->assign('formClass', 'membershiprenew');
    parent::preProcess();
    // check for edit permission
    if (!CRM_Core_Permission::check('edit memberships')) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }

    $this->assign('endDate', CRM_Utils_Date::customFormat(CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership',
        $this->_id, 'end_date'
      )
    ));
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
   */
  public function setDefaultValues() {

    $defaults = parent::setDefaultValues();
    $this->_memType = $defaults['membership_type_id'];

    // set renewal_date and receive_date to today in correct input format (setDateDefaults uses today if no value passed)
    list($now, $currentTime) = CRM_Utils_Date::setDateDefaults();
    $defaults['renewal_date'] = $now;
    $defaults['receive_date'] = $now;
    $defaults['receive_date_time'] = $currentTime;

    if ($defaults['id']) {
      $defaults['record_contribution'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipPayment',
        $defaults['id'],
        'contribution_id',
        'membership_id'
      );
    }

    if (is_numeric($this->_memType)) {
      $defaults['membership_type_id'] = array();
      $defaults['membership_type_id'][0] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
        $this->_memType,
        'member_of_contact_id',
        'id'
      );
      $defaults['membership_type_id'][1] = $this->_memType;
    }
    else {
      $defaults['membership_type_id'] = $this->_memType;
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
    $scTypes = CRM_Core_OptionGroup::values("soft_credit_type");
    $defaults['soft_credit_type_id'] = CRM_Utils_Array::value(ts('Gift'), array_flip($scTypes));

    $renewalDate = CRM_Utils_Date::processDate(CRM_Utils_Array::value('renewal_date', $defaults),
      NULL, NULL, 'Y-m-d'
    );
    $this->assign('renewalDate', $renewalDate);
    $this->assign('member_is_test', CRM_Utils_Array::value('member_is_test', $defaults));

    if ($this->_mode) {
      $defaults = $this->getBillingDefaults($defaults);
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    parent::buildQuickForm();

    $defaults = parent::setDefaultValues();
    $this->_memType = $defaults['membership_type_id'];
    $this->assign('customDataType', 'Membership');
    $this->assign('customDataSubType', $this->_memType);
    $this->assign('entityID', $this->_id);
    $selOrgMemType[0][0] = $selMemTypeOrg[0] = ts('- select -');

    $allMembershipInfo = array();

    //CRM-16950
    $taxRates = CRM_Core_PseudoConstant::getTaxRates();
    $taxRate = CRM_Utils_Array::value($this->allMembershipTypeDetails[$defaults['membership_type_id']]['financial_type_id'], $taxRates);

    $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');

    // auto renew options if enabled for the membership
    $options = CRM_Core_SelectValues::memberAutoRenew();

    foreach ($this->allMembershipTypeDetails as $key => $values) {
      if (!empty($values['is_active'])) {
        if ($this->_mode && empty($values['minimum_fee'])) {
          continue;
        }
        else {
          $memberOfContactId = CRM_Utils_Array::value('member_of_contact_id', $values);
          if (empty($selMemTypeOrg[$memberOfContactId])) {
            $selMemTypeOrg[$memberOfContactId] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
              $memberOfContactId,
              'display_name',
              'id'
            );

            $selOrgMemType[$memberOfContactId][0] = ts('- select -');
          }
          if (empty($selOrgMemType[$memberOfContactId][$key])) {
            $selOrgMemType[$memberOfContactId][$key] = CRM_Utils_Array::value('name', $values);
          }
        }

        //CRM-16950
        $taxAmount = NULL;
        $totalAmount = CRM_Utils_Array::value('minimum_fee', $values);
        if (CRM_Utils_Array::value($values['financial_type_id'], $taxRates)) {
          $taxAmount = ($taxRate / 100) * CRM_Utils_Array::value('minimum_fee', $values);
          $totalAmount = $totalAmount + $taxAmount;
        }

        // build membership info array, which is used to set the payment information block when
        // membership type is selected.
        $allMembershipInfo[$key] = array(
          'financial_type_id' => CRM_Utils_Array::value('financial_type_id', $values),
          'total_amount' => CRM_Utils_Money::format($totalAmount, NULL, '%a'),
          'total_amount_numeric' => $totalAmount,
          'tax_message' => $taxAmount ? ts("Includes %1 amount of %2", array(1 => CRM_Utils_Array::value('tax_term', $invoiceSettings), 2 => CRM_Utils_Money::format($taxAmount))) : $taxAmount,
        );

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

    $js = array('onChange' => "setPaymentBlock(); CRM.buildCustomData('Membership', this.value);");
    $sel = &$this->addElement('hierselect',
      'membership_type_id',
      ts('Renewal Membership Organization and Type'), $js
    );

    $sel->setOptions(array($selMemTypeOrg, $selOrgMemType));
    $elements = array();
    if ($sel) {
      $elements[] = $sel;
    }

    $this->applyFilter('__ALL__', 'trim');

    $this->addDate('renewal_date', ts('Date Renewal Entered'), FALSE, array('formatType' => 'activityDate'));

    $this->add('select', 'financial_type_id', ts('Financial Type'),
      array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::financialType()
    );

    $this->add('text', 'num_terms', ts('Extend Membership by'), array('onchange' => "setPaymentBlock();"), TRUE);
    $this->addRule('num_terms', ts('Please enter a whole number for how many periods to renew.'), 'integer');

    if (CRM_Core_Permission::access('CiviContribute') && !$this->_mode) {
      $this->addElement('checkbox', 'record_contribution', ts('Record Renewal Payment?'), NULL, array('onclick' => "checkPayment();"));

      $this->add('text', 'total_amount', ts('Amount'));
      $this->addRule('total_amount', ts('Please enter a valid amount.'), 'money');

      $this->addDate('receive_date', ts('Received'), FALSE, array('formatType' => 'activityDateTime'));

      $this->add('select', 'payment_instrument_id', ts('Payment Method'),
        array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::paymentInstrument(),
        FALSE, array('onChange' => "return showHideByValue('payment_instrument_id','4','checkNumber','table-row','select',false);")
      );

      $this->add('text', 'trxn_id', ts('Transaction ID'));
      $this->addRule('trxn_id', ts('Transaction ID already exists in Database.'),
        'objectExists', array('CRM_Contribute_DAO_Contribution', $this->_id, 'trxn_id')
      );

      $this->add('select', 'contribution_status_id', ts('Payment Status'),
        CRM_Contribute_PseudoConstant::contributionStatus()
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
      array('onclick' => "showHideByValue( 'send_receipt', '', 'notice', 'table-row', 'radio', false ); showHideByValue( 'send_receipt', '', 'fromEmail', 'table-row', 'radio',false);")
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
    $this->addFormRule(array('CRM_Member_Form_MembershipRenewal', 'formRule'));
    $this->addElement('checkbox', 'is_different_contribution_contact', ts('Record Payment from a Different Contact?'));
    $this->addSelect('soft_credit_type_id', array('entity' => 'contribution_soft'));
    $this->addEntityRef('soft_credit_contact_id', ts('Payment From'), array('create' => TRUE));
  }

  /**
   * Validation.
   *
   * @param array $params
   *   (ref.) an assoc array of name/value pairs.
   *
   * @return bool|array
   *   mixed true or array of errors
   */
  public static function formRule($params) {
    $errors = array();
    if ($params['membership_type_id'][0] == 0) {
      $errors['membership_type_id'] = ts('Oops. It looks like you are trying to change the membership type while renewing the membership. Please click the "change membership type" link, and select a Membership Organization.');
    }
    if ($params['membership_type_id'][1] == 0) {
      $errors['membership_type_id'] = ts('Oops. It looks like you are trying to change the membership type while renewing the membership. Please click the "change membership type" link and select a Membership Type from the list.');
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
   */
  public function postProcess() {
    // get the submitted form values.
    $this->_params = $this->controller->exportValues($this->_name);
    $this->assignBillingName();

    try {
      $this->submit();
      $statusMsg = ts('%1 membership for %2 has been renewed.', array(1 => $this->membershipTypeName, 2 => $this->_memberDisplayName));

      if ($this->endDate) {
        $statusMsg .= ' ' . ts('The new membership End Date is %1.', array(
          1 => CRM_Utils_Date::customFormat(substr($this->endDate, 0, 8)),
        ));
      }

      if ($this->isMailSent) {
        $statusMsg .= ' ' . ts('A renewal confirmation and receipt has been sent to %1.', array(
          1 => $this->_contributorEmail,
        ));
        return $statusMsg;
      }
      return $statusMsg;
    }
    catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
      CRM_Core_Error::displaySessionError($e->getMessage());
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/membership',
        "reset=1&action=renew&cid={$this->_contactID}&id={$this->_id}&context=membership&mode={$this->_mode}"
      ));
    }

    CRM_Core_Session::setStatus($statusMsg, ts('Complete'), 'success');
  }

  /**
   * Process form submission.
   *
   * This function is also accessed by a unit test.
   */
  protected function submit() {
    $this->storeContactFields($this->_params);
    $this->beginPostProcess();
    $now = CRM_Utils_Date::getToday(NULL, 'YmdHis');
    $this->convertDateFieldsToMySQL($this->_params);
    $this->assign('receive_date', $this->_params['receive_date']);
    $this->processBillingAddress();
    list($userName) = CRM_Contact_BAO_Contact_Location::getEmailDetails(CRM_Core_Session::singleton()->get('userID'));
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

      // at this point we've created a contact and stored its address etc
      // all the payment processors expect the name and address to be in the passed params
      // so we copy stuff over to first_name etc.
      $paymentParams = $this->_params;
      if (!empty($this->_params['send_receipt'])) {
        $paymentParams['email'] = $this->_contributorEmail;
      }
      $paymentParams['is_email_receipt'] = !empty($this->_params['send_receipt']);

      $paymentParams['contactID'] = $this->_contributorContactID;

      CRM_Core_Payment_Form::mapParams($this->_bltID, $this->_params, $paymentParams, TRUE);

      $payment = $this->_paymentProcessor['object'];

      if (!empty($this->_params['auto_renew'])) {
        $contributionRecurParams = $this->processRecurringContribution($paymentParams);
        $contributionRecurID = $contributionRecurParams['contributionRecurID'];
        $paymentParams = array_merge($paymentParams, $contributionRecurParams);
      }

      $result = $payment->doPayment($paymentParams);
      $this->_params = array_merge($this->_params, $result);

      $this->_params['contribution_status_id'] = $result['payment_status_id'];
      $this->_params['trxn_id'] = $result['trxn_id'];
      $this->_params['payment_instrument_id'] = 1;
      $this->_params['is_test'] = ($this->_mode == 'live') ? 0 : 1;
      $this->set('params', $this->_params);
      $this->assign('trxn_id', $result['trxn_id']);
    }

    $renewalDate = !empty($this->_params['renewal_date']) ? $renewalDate = CRM_Utils_Date::processDate($this->_params['renewal_date']) : NULL;

    // check for test membership.
    $isTestMembership = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $this->_membershipId, 'is_test');

    // chk for renewal for multiple terms CRM-8750
    $numRenewTerms = 1;
    if (is_numeric(CRM_Utils_Array::value('num_terms', $this->_params))) {
      $numRenewTerms = $this->_params['num_terms'];
    }

    //if contribution status is pending then set pay later
    $this->_params['is_pay_later'] = FALSE;
    if ($this->_params['contribution_status_id'] == array_search('Pending', CRM_Contribute_PseudoConstant::contributionStatus())) {
      $this->_params['is_pay_later'] = 1;
    }

    // These variable sets prior to renewMembership may not be required for this form. They were in
    // a function this form shared with other forms.
    $membershipSource = NULL;
    if (!empty($this->_params['membership_source'])) {
      $membershipSource = $this->_params['membership_source'];
    }

    $isPending = ($this->_params['contribution_status_id'] == 2) ? TRUE : FALSE;

    list($renewMembership) = CRM_Member_BAO_Membership::renewMembership(
      $this->_contactID, $this->_params['membership_type_id'][1], $isTestMembership,
      $renewalDate, NULL, $customFieldsFormatted, $numRenewTerms, $this->_membershipId,
      $isPending,
      $contributionRecurID, $membershipSource, $this->_params['is_pay_later'], CRM_Utils_Array::value('campaign_id',
      $this->_params)
    );

    $this->endDate = CRM_Utils_Date::processDate($renewMembership->end_date);

    $this->membershipTypeName = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $renewMembership->membership_type_id,
      'name');

    if (!empty($this->_params['record_contribution']) || $this->_mode) {
      // set the source
      $this->_params['contribution_source'] = "{$this->membershipTypeName} Membership: Offline membership renewal (by {$userName})";

      //create line items
      $lineItem = array();
      $this->_params = $this->setPriceSetParameters($this->_params);
      CRM_Price_BAO_PriceSet::processAmount($this->_priceSet['fields'],
        $this->_params, $lineItem[$this->_priceSetId], NULL, $this->_priceSetId
      );
      //CRM-11529 for quick config backoffice transactions
      //when financial_type_id is passed in form, update the
      //line items with the financial type selected in form
      if ($submittedFinancialType = CRM_Utils_Array::value('financial_type_id', $this->_params)) {
        foreach ($lineItem[$this->_priceSetId] as &$li) {
          $li['financial_type_id'] = $submittedFinancialType;
        }
      }

      if (!empty($lineItem)) {
        $this->_params['lineItems'] = $lineItem;
        $this->_params['processPriceSet'] = TRUE;
      }

      //assign contribution contact id to the field expected by recordMembershipContribution
      if ($this->_contributorContactID != $this->_contactID) {
        $this->_params['contribution_contact_id'] = $this->_contributorContactID;
        if (!empty($this->_params['soft_credit_type_id'])) {
          $this->_params['soft_credit'] = array(
            'soft_credit_type_id' => $this->_params['soft_credit_type_id'],
            'contact_id' => $this->_contactID,
          );
        }
      }
      $this->_params['contact_id'] = $this->_contactID;
      //recordMembershipContribution receives params as a reference & adds one variable. This is
      // not a great pattern & ideally it would not receive as a reference. We assign our params as a
      // temporary variable to avoid e-notice & to make it clear to future refactorer that
      // this function is NOT reliant on that var being set
      $temporaryParams = array_merge($this->_params, array(
        'membership_id' => $renewMembership->id,
        'contribution_recur_id' => $contributionRecurID,
      ));
      CRM_Member_BAO_Membership::recordMembershipContribution($temporaryParams);
    }

    if (!empty($this->_params['send_receipt'])) {

      $receiptFrom = $this->_params['from_email_address'];

      if (!empty($this->_params['payment_instrument_id'])) {
        $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
        $this->_params['paidBy'] = $paymentInstrument[$this->_params['payment_instrument_id']];
      }
      //get the group Tree
      $this->_groupTree = CRM_Core_BAO_CustomGroup::getTree('Membership', $this, $this->_id, FALSE, $this->_memType);

      // retrieve custom data
      $customFields = $customValues = $fo = array();
      foreach ($this->_groupTree as $groupID => $group) {
        if ($groupID == 'info') {
          continue;
        }
        foreach ($group['fields'] as $k => $field) {
          $field['title'] = $field['label'];
          $customFields["custom_{$k}"] = $field;
        }
      }
      $members = array(array('member_id', '=', $this->_membershipId, 0, 0));
      // check whether its a test drive
      if ($this->_mode == 'test') {
        $members[] = array('member_test', '=', 1, 0, 0);
      }
      CRM_Core_BAO_UFGroup::getValues($this->_contactID, $customFields, $customValues, FALSE, $members);

      $this->assign_by_ref('formValues', $this->_params);
      if (!empty($this->_params['contribution_id'])) {
        $this->assign('contributionID', $this->_params['contribution_id']);
      }

      $this->assign('membership_name', CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
        $renewMembership->membership_type_id
      ));
      $this->assign('customValues', $customValues);
      $this->assign('mem_start_date', CRM_Utils_Date::customFormat($renewMembership->start_date));
      $this->assign('mem_end_date', CRM_Utils_Date::customFormat($renewMembership->end_date));
      if ($this->_mode) {
        // assign the address formatted up for display
        $addressParts = array(
          "street_address-{$this->_bltID}",
          "city-{$this->_bltID}",
          "postal_code-{$this->_bltID}",
          "state_province-{$this->_bltID}",
          "country-{$this->_bltID}",
        );
        $addressFields = array();
        foreach ($addressParts as $part) {
          list($n) = explode('-', $part);
          if (isset($this->_params['billing_' . $part])) {
            $addressFields[$n] = $this->_params['billing_' . $part];
          }
        }
        $this->assign('address', CRM_Utils_Address::format($addressFields));
        $this->assign('contributeMode', 'direct');
        $this->assign('isAmountzero', 0);
        $this->assign('is_pay_later', 0);
        $this->assign('isPrimary', 1);
        $this->assign('receipt_text_renewal', $this->_params['receipt_text']);
        if ($this->_mode == 'test') {
          $this->assign('action', '1024');
        }
      }

      list($this->isMailSent) = CRM_Core_BAO_MessageTemplate::sendTemplate(
        array(
          'groupName' => 'msg_tpl_workflow_membership',
          'valueName' => 'membership_offline_receipt',
          'contactId' => $this->_receiptContactId,
          'from' => $receiptFrom,
          'toName' => $this->_contributorDisplayName,
          'toEmail' => $this->_contributorEmail,
          'isTest' => $this->_mode == 'test',
        )
      );
    }
  }

}
