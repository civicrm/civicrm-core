<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
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

    // there is a bunch of processing on price set id.  Make sure to retrieve it from
    // submitteValues first when doing "submit".
    if (!empty($this->_submitValues['price_set_id'])) {
      $this->_priceSetId = $this->_submitValues['price_set_id'];
    }

    $skipMinimumAmountCheck = FALSE;
    if (empty($this->_priceSetId)) {
      $pair = CRM_Price_BAO_PriceSet::getLastPriceSetUsed($this->_id);
      if ($pair != NULL) {
        $this->_priceSetId = $pair['price_set_id'];
        // this logic here (and the more complicated SQL from getLastPriceSet used to
        // set this param) arguably not required.  For simple uses of price sets, (e.g.,
        // one price set / org & few contribs, works well.  For complicated use cases
        // (e.g., multiple price sets, multiple orgs per price set) also works well,
        // if not better, making it easier for an admin to pick a price set that has the
        // "most" coverage.
        $this->assign('show_price_set', $pair['price_set_is_through_contribution']);
        $skipMinimumAmountCheck = TRUE;
      }
    }
    else {
      // a price set is specified, we're reloading this page to load price set, skip the edit check.
      // wouldn't make sense to prevent, even if min amount was == 0, there are many price options
      // that *could* result in a chargeable amount.
      $skipMinimumAmountCheck = TRUE;
      $this->assign('show_price_set', TRUE);
    }

    if ($this->_mode && $skipMinimumAmountCheck == FALSE) {
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
   *
   * @return array
   *   Default values.
   */
  public function setDefaultValues() {

    $defaults = parent::setDefaultValues();

    // set renewal_date and receive_date to today in correct input format (setDateDefaults uses today if no value passed)
    list($now, $currentTime) = CRM_Utils_Date::setDateDefaults();
    $defaults['renewal_date'] = $now;
    $defaults['receive_date'] = $now;
    $defaults['receive_date_time'] = $currentTime;

    if ($this->_priceSetId) {
      if (isset($this->_priceSet) && !empty($this->_priceSet['fields'])) {
        CRM_Price_BAO_PriceSet::setPriceSetDefaultsToLastUsedValues($defaults, $this->_priceSet, $this->_contactID);
      }
      $defaults['price_set_id'] = $this->_priceSetId;
    }

    if ($this->_priceSet) {
      $defaults['financial_type_id'] = $this->_priceSet['financial_type_id'];
    }
    else {
      // retrieve best price set to use for this contact & org (this will be default if user selects "use price set"
      $defaults['financial_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $this->_memType, 'financial_type_id');
    }

    //CRM-13420
    if (empty($defaults['payment_instrument_id'])) {
      $defaults['payment_instrument_id'] = key(CRM_Core_OptionGroup::values('payment_instrument', FALSE, FALSE, FALSE, 'AND is_default = 1'));
    }

    $defaults['total_amount'] = CRM_Utils_Money::format(CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
      $this->_memType,
      'minimum_fee'
    ), NULL, '%a');

    $defaults['record_contribution'] = 0;

    $renewalDate = CRM_Utils_Date::processDate(CRM_Utils_Array::value('renewal_date', $defaults),
      NULL, NULL, 'Y-m-d'
    );
    $this->assign('renewalDate', $renewalDate);

    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    parent::buildQuickForm();

    $defaults = parent::setDefaultValues();
    $this->assign('customDataType', 'Membership');
    $this->assign('customDataSubType', $this->_memType);
    $this->assign('entityID', $this->_id);
    $selOrgMemType = array();
    $selOrgMemType[0][0] = $selMemTypeOrg[0] = ts('- select -');

    $allMembershipInfo = array();

    //CRM-16950
    $taxRates = CRM_Core_PseudoConstant::getTaxRates();
    $taxRate = CRM_Utils_Array::value($this->allMembershipTypeDetails[$this->_memType]['financial_type_id'], $taxRates);

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

    $this->buildQuickFormPriceSet();
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
    $this->addFormRule(array('CRM_Member_Form_MembershipRenewal', 'formRule'), $this);
    $this->addElement('checkbox', 'is_different_contribution_contact', ts('Record Payment from a Different Contact?'));
    $this->addSelect('soft_credit_type_id', array('entity' => 'contribution_soft'));
    $this->addEntityRef('soft_credit_contact_id', ts('Payment From'), array('create' => TRUE));
  }


  protected function shouldIncludeChoosePriceSetOption() {
    $showPriceSet = $this->get_template_vars("show_price_set");
    return ($showPriceSet !== TRUE);
  }

  private function buildQuickFormPriceSet() {
    if ($this->_submitValues && !$this->_submitValues['price_set_id']) {
      // user is 'submitting' and didn't select price set.  So no need to
      // add price set fields for validation.
      return;
    }

    // show_price_set will be set to one of FALSE, TRUE, or not set at all.
    // FALSE == membership type being renewed could have been picked from a price set, but membership never created
    //          using a price set, so don't show.  In this case we don't show price set options, but allow user
    //          to 'renew using a price set'.
    // TRUE  == membership type being renewed was created from a price set.  In this case we show price set options by default
    //          and allow user to "renew without priceset".
    // NULL  == membership type being renewed was created from a price set.
    $showPriceSet = $this->get_template_vars("show_price_set");
    if ($showPriceSet !== NULL) {
      // assign price set, looks at whether there are price sets defined or not.  We want more
      // we want to make sure membership type being renewed actually has a price set.
      $this->assignPriceSet();

      // pass the price set id of default (or selected) price set back to smarty
      $this->assign("priceSetId", $this->_priceSetId);
      // set default on price set to value determined in buildQuickForm
      $this->set("priceSetId", $this->_priceSetId);
      // add all elements that are part of price set (these will be hidden to start)
      // but will correspond to "last price set used".
      CRM_Price_BAO_PriceSet::buildPriceSet($this);
    }

  }

  /**
   * Validation.
   *
   * @param array $params
   *   (ref.) an assoc array of name/value pairs.
   *
   * @param mixed $files
   *    not used.
   *
   * @return bool|array
   *   mixed true or array of errors
   */
  public static function formRule($params, $files, $self) {
    $errors = array();
    // Only validate membership type id & org, when not using price sets.  Otherwise, these *will* be missing.
    if (empty($params['price_set_id'])) {
      if ($params['membership_type_id'][0] == 0) {
        $errors['membership_type_id'] = ts('Oops. It looks like you are trying to change the membership type while renewing the membership. Please click the "change membership type" link, and select a Membership Organization.');
      }
      if ($params['membership_type_id'][1] == 0) {
        $errors['membership_type_id'] = ts('Oops. It looks like you are trying to change the membership type while renewing the membership. Please click the "change membership type" link and select a Membership Type from the list.');
      }
    }

    // CRM-20571
    // Get the Join Date from Membership info as it is not available in the Renewal form
    $joinDate = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $self->_id, 'join_date');

    // CRM-20571: Check if the renewal date is not before Join Date, if it is then add to 'errors' array
    // The fields in Renewal form come into this routine in $params array. 'renewal_date' is in the form
    // We process both the dates before comparison using CRM utils so that they are in same date format
    if (isset($params['renewal_date'])) {
      if (CRM_Utils_Date::processDate($params['renewal_date']) < CRM_Utils_Date::processDate($joinDate)) {
        $errors['renewal_date'] = ts('Renewal date must be the same or later than Member since (Join Date).');
      }
    }

    //total amount condition arise when membership type having no minimum fee.
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

    if (empty($errors) && !empty($params['price_set_id'])) {
      $clonedParams = $params;
      $clonedParams['join_date'] = $params['renewal_date'];
      // avoid warning by redefining properties used by CRM_Member_Form_Membership
      $selfArray = (array) $self;
      $selfArray['_onlinePendingContributionId'] = 0; // avoid warning.
      return CRM_Member_Form_Membership::formRule($clonedParams, NULL, (object) $selfArray);
    }
    else {
      return empty($errors) ? TRUE : $errors;
    }
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
    $this->assign('contactID', $this->_contactID);

    if ($this->_priceSet) {
      // renewing using a price set, set the default financial type (if not set) to the price set's default financial type.
      if (empty($this->_params['financial_type_id'])) {
        $this->_params['financial_type_id'] = $this->_priceSet['financial_type'];
      }
    }
    else {
      $this->_params['total_amount'] = CRM_Utils_Array::value('total_amount', $this->_params, CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $this->_memType, 'minimum_fee'));
      // renewing a plain "renew this membership".  Set default financial type (if not set) to membership type's default financial type.
      if (empty($this->_params['financial_type_id'])) {
        $this->_params['financial_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $this->_memType, 'financial_type_id');
      }
    }

    $contributionRecurID = NULL;
    $this->assign('membershipID', $this->_id);
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

      // @todo this should happen AFTER the pending payment is created and be confirmed to Completed if it succeeds
      $result = $payment->doPayment($paymentParams);
      $this->_params = array_merge($this->_params, $result);

      $this->_params['contribution_status_id'] = $result['payment_status_id'];
      $this->_params['trxn_id'] = $result['trxn_id'];
      $this->_params['is_test'] = ($this->_mode == 'live') ? 0 : 1;
      $this->set('params', $this->_params);
      $this->assign('trxn_id', $result['trxn_id']);
    }

    //if contribution status is pending then set pay later
    $this->_params['is_pay_later'] = FALSE;
    if ($this->_params['contribution_status_id'] == array_search('Pending', CRM_Contribute_PseudoConstant::contributionStatus())) {
      $this->_params['is_pay_later'] = 1;
    }

    // taken from membership, adds to _params
    $this->submitAddContributionLineItemsToParams();

    // price_set_id only set if submit includes a price set based renewal
    if (!empty($this->_params['price_set_id'])) {
      $renewedMemberships = $this->submitRenewMembershipsPriceSet($contributionRecurID);

      // if membership renewal process was triggered from was renewed / included, then use *it*
      // for "renewMembership".  Otherwise, we arbitrarily pick the first membership.
      $renewMembership = $renewedMemberships[0];
      foreach ($renewedMemberships as $tmpRenewedMembership) {
        if ($tmpRenewedMembership->id === $this->_id) {
          $renewMembership = $tmpRenewedMembership;
          break;
        }
      }

      $membershipIdForUDF = $renewMembership->id;
    }
    else {
      $renewMembership = $this->submitRenewMembershipSingle($this->_id, $this->_params['membership_type_id'][1], $contributionRecurID);
      $membershipIdForUDF = $this->_id;
    }

    $this->endDate = CRM_Utils_Date::processDate($renewMembership->end_date);

    $this->membershipTypeName = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $renewMembership->membership_type_id, 'name');

    if (!empty($this->_params['record_contribution']) || $this->_mode) {
      // set the source
      $this->_params['contribution_source'] = "{$this->membershipTypeName} Membership: Offline membership renewal (by {$userName})";

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
      //Remove `tax_amount` if it is not calculated.
      if (CRM_Utils_Array::value('tax_amount', $temporaryParams) === 0) {
        unset($temporaryParams['tax_amount']);
      }
      CRM_Member_BAO_Membership::recordMembershipContribution($temporaryParams);
    }

    if (!empty($this->_params['send_receipt'])) {
      $receiptFrom = $this->_params['from_email_address'];

      if (!empty($this->_params['payment_instrument_id'])) {
        $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
        $this->_params['paidBy'] = $paymentInstrument[$this->_params['payment_instrument_id']];
      }
      //get the group Tree
      $this->_groupTree = CRM_Core_BAO_CustomGroup::getTree('Membership', NULL, $this->_id, FALSE, $this->_memType);

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
      $members = array(array('member_id', '=', $membershipIdForUDF, 0, 0));
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
        $this->assign('address', CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters(
          $this->_params,
          $this->_bltID
        ));
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

  private function submitAddContributionLineItemsToParams() {
    //create line items
    $lineItem = array();
    $this->_params = $this->setPriceSetParameters($this->_params);
    CRM_Price_BAO_PriceSet::processAmount($this->_priceSet['fields'], $this->_params, $lineItem[$this->_priceSetId], NULL, $this->_priceSetId);

    // litespeedmarc: commented out, this only made sense for price set based renewals
    // for single membership renewals, this doesn't apply.  And for price set based
    // ones, we always want to use price set line items, unless quick config (see
    // similar logic in CRM_Member_Form_Membership.php), which doesn't apply here.
    //    //CRM-11529 for quick config backoffice transactions
    //    //when financial_type_id is passed in form, update the
    //    //line items with the financial type selected in form
    //    $submittedFinancialType = CRM_Utils_Array::value('financial_type_id', $this->_params);
    //    if ($submittedFinancialType) {
    //      foreach ($lineItem[$this->_priceSetId] as &$li) {
    //        $li['financial_type_id'] = $submittedFinancialType;
    //      }
    //    }

    if (!empty($lineItem)) {
      $this->_params['lineItems'] = $lineItem;
      $this->_params['processPriceSet'] = TRUE;
    }
  }

  private function submitRenewMembershipsPriceSet($contributionRecurID) {
    // all organization types (e.g. Health Institute) by Type (e.g., Full Fee, Student)
    $allOrgsByType = CRM_Member_BAO_MembershipType::getMembershipTypeOrganization();

    // The membership types selected in the renew by priceset form.
    $membershipTypesSelected = CRM_Member_Form_Membership::getSelectedMemberships(
      $this->_priceSet,
      $this->_params
    );

    $renewedMemberships = array();

    // All membership types that this contact has, by org.  Assumes one / org.  If multiple (for whatever bad data reason),
    // heuristics are used.
    $allMemTypesByOrg = CRM_Member_BAO_Membership::getContactMembershipsByMembershipOrg($this->_contactID, $this->_id);

    $priceFieldValueIds = array_keys($this->_params['lineItems'][$this->_priceSetId]);
    $cnt = 0;

    $membershipTypeValues = array();
    foreach ($membershipTypesSelected as $memType) {
      $org = $allOrgsByType[$memType];
      $membershipIdToUpdate = empty($allMemTypesByOrg[$org]) ? NULL : $allMemTypesByOrg[$org]['membership_id'] ?: NULL;
      $membership = $this->submitRenewMembershipSingle($membershipIdToUpdate, $memType, $contributionRecurID);
      // set the id on the line item.  This will result in the proper line_item row being created later on in
      // CRM_Price_BAO_LineItem::processPriceSet (This is certainly a Demeter Law violation!)
      $this->_params['lineItems'][$this->_priceSetId][$priceFieldValueIds[$cnt++]]['entity_id'] = $membership->id;
      array_push($renewedMemberships, $membership);

      $membershipTypeValues[$membership->membership_type_id] = $membership;
    }

    // add line items to template for receipting.
    $lineItem = $this->_params['lineItems'];
    foreach ($lineItem[$this->_priceSetId] as & $priceFieldOp) {
      if (!empty($priceFieldOp['membership_type_id'])) {
        $membership = $membershipTypeValues[$priceFieldOp['membership_type_id']];
        $priceFieldOp['start_date'] = (!empty($membership) && !empty($membership->start_date)) ? CRM_Utils_Date::customFormat($membership->start_date, '%B %E%f, %Y') : '-';
        $priceFieldOp['end_date'] = (!empty($membership) && !empty($membership->end_date)) ? CRM_Utils_Date::customFormat($membership->end_date, '%B %E%f, %Y') : '-';
      }
      else {
        $priceFieldOp['start_date'] = $priceFieldOp['end_date'] = 'N/A';
      }
    }
    $this->assign('lineItem', !empty($lineItem) ? $lineItem : FALSE);

    return $renewedMemberships;
  }


  private function submitRenewMembershipSingle($membershipId, $membershipTypeId, $contributionRecurID) {
    // @todo we should always create as pending and update when completed.
    $isPending = ($this->_params['contribution_status_id'] == 2) ? TRUE : FALSE;

    // most vars are the same.  Some differ depending on whether a new membership, or renewing an existing one.

    // set test flag... copy from pre-existing membership, or if new membership, copy from membership from which it was copied.
    $isTestMembership = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', !$membershipId ? $this->_id : $membershipId, 'is_test');
    $renewalDate = !empty($this->_params['renewal_date']) ? CRM_Utils_Date::processDate($this->_params['renewal_date']) : NULL;
    $customFieldsFormatted = CRM_Core_BAO_CustomField::postProcess($this->_params, $membershipId, 'Membership');

    // These variable sets prior to renewMembership may not be required for this form. They were in
    // a function this form shared with other forms.
    $membershipSource = NULL;
    if (!empty($this->_params['membership_source'])) {
      $membershipSource = $this->_params['membership_source'];
    }

    // chk for renewal for multiple terms CRM-8750
    $numRenewTerms = 1;
    if (is_numeric(CRM_Utils_Array::value('num_terms', $this->_params))) {
      $numRenewTerms = $this->_params['num_terms'];
    }

    // CRM-15861.  Set join_date to renewal date.  If selected membership doesn't yet exist, then
    // it will be created with the "member since" date specified (as per message shown on screen
    // for this field find "renewal_date" in MembershipRenewal.tpl for more.
    $formDates = array('join_date' => $renewalDate);

    list($result) = CRM_Member_BAO_Membership::processMembership(
            $this->_contactID,
            $membershipTypeId,
            $isTestMembership,
            $renewalDate,
            NULL,
            $customFieldsFormatted,
            $numRenewTerms,
            $membershipId,
            $isPending,
            $contributionRecurID,
            $membershipSource,
            $this->_params['is_pay_later'],
            CRM_Utils_Array::value('campaign_id', $this->_params),
            $formDates
    );
    return $result;

  }

}
