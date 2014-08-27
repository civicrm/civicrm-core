<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class generates form components for Membership Renewal
 *
 */
class CRM_Member_Form_MembershipRenewal extends CRM_Member_Form {
  /*
   * Display name of the member
   */
  protected $_memberDisplayName = null;
  /*
  * email of the person paying for the membership (used for receipts)
  */
  protected $_memberEmail = null;
  /*
  * Contact ID of the member
  */
  protected $_contactID = null;
  /*
  * Display name of the person paying for the membership (used for receipts)
  */
  protected $_contributorDisplayName = null;
 /*
  * email of the person paying for the membership (used for receipts)
  */
  protected $_contributorEmail = null;
  /*
  * email of the person paying for the membership (used for receipts)
  */
  protected $_contributorContactID = null;
 /*
  * ID of the person the receipt is to go to
  */
  protected $_receiptContactId = null;
  /*
   * context would be set to standalone if the contact is use is being selected from
   * the form rather than in the URL
   */
  protected $_context;

  /**
   * An array to hold a list of datefields on the form
   * so that they can be converted to ISO in a consistent manner
   *
   * @var array
   */
  protected $_dateFields = array(
    'receive_date' => array('default' => 'now'),
  );

  public function preProcess() {
    //custom data related code
    $this->_cdType = CRM_Utils_Array::value('type', $_GET);
    $this->assign('cdType', FALSE);
    if ($this->_cdType) {
      $this->assign('cdType', TRUE);
      return CRM_Custom_Form_CustomData::preProcess($this);
    }

    // check for edit permission
    if (!CRM_Core_Permission::check('edit memberships')) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
    }
    // action
    $this->_action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 'add'
    );
    $this->_context = CRM_Utils_Request::retrieve('context', 'String',
      $this, FALSE, 'membership'
    );
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this
    );
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive',
      $this
    );
    if ($this->_id) {
      $this->_memType = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $this->_id, 'membership_type_id');
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

    //using credit card :: CRM-2759
    $this->_mode = CRM_Utils_Request::retrieve('mode', 'String', $this);
    if ($this->_mode) {
      $membershipFee = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $this->_memType, 'minimum_fee');
      if (!$membershipFee) {
        $statusMsg = ts('Membership Renewal using a credit card requires a Membership fee. Since there is no fee associated with the selected memebership type, you can use the normal renewal mode.');
        CRM_Core_Session::setStatus($statusMsg, '', 'info');
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/membership',
            "reset=1&action=renew&cid={$this->_contactID}&id={$this->_id}&context=membership"
          ));
      }
      $this->assign('membershipMode', $this->_mode);

      $this->_paymentProcessor = array('billing_mode' => 1);
      $validProcessors = array();
      $processors = CRM_Core_PseudoConstant::paymentProcessor(FALSE, FALSE, 'billing_mode IN ( 1, 3 )');

      foreach ($processors as $ppID => $label) {
        $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($ppID, $this->_mode);
        if ($paymentProcessor['payment_processor_type'] == 'PayPal' && !$paymentProcessor['user_name']) {
          continue;
        }
        elseif ($paymentProcessor['payment_processor_type'] == 'Dummy' && $this->_mode == 'live') {
          continue;
        }
        else {
          $paymentObject = CRM_Core_Payment::singleton($this->_mode, $paymentProcessor, $this);
          $error = $paymentObject->checkConfig();
          if (empty($error)) {
            $validProcessors[$ppID] = $label;
          }
          $paymentObject = NULL;
        }
      }
      if (empty($validProcessors)) {
        CRM_Core_Error::fatal(ts('Could not find valid payment processor for this page'));
      }
      else {
        $this->_processors = $validProcessors;
      }
      // also check for billing information
      // get the billing location type
      $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id', array(), 'validate');
      // CRM-8108 remove ts around Billing location type
      //$this->_bltID = array_search( ts('Billing'),  $locationTypes );
      $this->_bltID = array_search('Billing', $locationTypes);
      if (!$this->_bltID) {
        CRM_Core_Error::fatal(ts('Please set a location type of %1', array(1 => 'Billing')));
      }
      $this->set('bltID', $this->_bltID);
      $this->assign('bltID', $this->_bltID);

      $this->_fields = array();

      CRM_Core_Payment_Form::setCreditCardFields($this);

      // this required to show billing block
      $this->assign_by_ref('paymentProcessor', $paymentProcessor);
      $this->assign('hidePayPalExpress', TRUE);
    }
    else {
      $this->assign('membershipMode', FALSE);
    }

    // when custom data is included in this page
    if (!empty($_POST['hidden_custom'])) {
      CRM_Custom_Form_CustomData::preProcess($this);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

    $this->_fromEmails = CRM_Core_BAO_Email::getFromEmail();

    CRM_Utils_System::setTitle(ts('Renew Membership'));

    parent::preProcess();
  }

  /**
   * This function sets the default values for the form.
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  public function setDefaultValues() {
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::setDefaultValues($this);
    }
    $defaults       = array();
    $defaults       = parent::setDefaultValues();
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
      $fields = array();

      foreach ($this->_fields as $name => $dontCare) {
        $fields[$name] = 1;
      }

      $names = array(
        'first_name', 'middle_name', 'last_name', "street_address-{$this->_bltID}",
        "city-{$this->_bltID}", "postal_code-{$this->_bltID}", "country_id-{$this->_bltID}",
        "state_province_id-{$this->_bltID}",
      );
      foreach ($names as $name) {
        $fields[$name] = 1;
      }

      $fields["state_province-{$this->_bltID}"] = 1;
      $fields["country-{$this->_bltID}"] = 1;
      $fields["email-{$this->_bltID}"] = 1;
      $fields['email-Primary'] = 1;

      CRM_Core_BAO_UFGroup::setProfileDefaults($this->_contactID, $fields, $this->_defaults);

      // use primary email address if billing email address is empty
      if (empty($this->_defaults["email-{$this->_bltID}"]) &&
        !empty($this->_defaults['email-Primary'])
      ) {
        $defaults["email-{$this->_bltID}"] = $this->_defaults['email-Primary'];
      }

      foreach ($names as $name) {
        if (!empty($this->_defaults[$name])) {
          $defaults['billing_' . $name] = $this->_defaults[$name];
        }
      }
    }

    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::buildQuickForm($this);
    }

    parent::buildQuickForm();

    $defaults       = array();
    $defaults       = parent::setDefaultValues();
    $this->_memType = $defaults['membership_type_id'];
    $this->assign('customDataType', 'Membership');
    $this->assign('customDataSubType', $this->_memType);
    $this->assign('entityID', $this->_id);
    $selOrgMemType[0][0] = $selMemTypeOrg[0] = ts('- select -');

    $allMemberships = CRM_Member_BAO_Membership::buildMembershipTypeValues($this);

    $allMembershipInfo = $membershipType = array();

    // auto renew options if enabled for the membership
    $options = array(ts('No auto-renew option'), ts('Give option, but not required'), ts('Auto-renew required '));

    foreach( $allMemberships as $key => $values ) {
      if (!empty($values['is_active'])) {
        $membershipType[$key] = CRM_Utils_Array::value('name', $values);
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

        // build membership info array, which is used to set the payment information block when
        // membership type is selected.
        $allMembershipInfo[$key] = array(
          'financial_type_id' => CRM_Utils_Array::value('financial_type_id', $values),
          'total_amount'         => CRM_Utils_Money::format($values['minimum_fee'], NULL, '%a'),
          'total_amount_numeric' => CRM_Utils_Array::value('minimum_fee', $values)
        );

        if (!empty($values['auto_renew'])) {
          $allMembershipInfo[$key]['auto_renew'] = $options[$values['auto_renew']];
      }
    }
    }

    $this->assign('allMembershipInfo', json_encode($allMembershipInfo));

    if ($this->_memType) {
      $this->assign('orgName', $selMemTypeOrg[$allMemberships[$this->_memType]['member_of_contact_id']]);
      $this->assign('memType', $allMemberships[$this->_memType]['name']);
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

    $js = array('onChange' => "setPaymentBlock( ); CRM.buildCustomData( 'Membership', this.value );");

    //build the form for auto renew.
    $recurProcessor = array();
    if ($this->_mode || ($this->_action & CRM_Core_Action::UPDATE)) {
      //get the valid recurring processors.
      $recurring = CRM_Core_PseudoConstant::paymentProcessor(FALSE, FALSE, 'is_recur = 1');
      $recurProcessor = array_intersect_assoc($this->_processors, $recurring);
      if (!empty($recurProcessor)) {
        $autoRenew = array();
        if (!empty($membershipType)) {
          $sql = '
SELECT  id,
        auto_renew,
        duration_unit,
        duration_interval
 FROM   civicrm_membership_type
WHERE   id IN ( ' . implode(' , ', array_keys($membershipType)) . ' )';
          $recurMembershipTypes = CRM_Core_DAO::executeQuery($sql);
          while ($recurMembershipTypes->fetch()) {
            $autoRenew[$recurMembershipTypes->id] = $recurMembershipTypes->auto_renew;
            foreach (array(
              'id', 'auto_renew', 'duration_unit', 'duration_interval') as $fld) {
              $this->_recurMembershipTypes[$recurMembershipTypes->id][$fld] = $recurMembershipTypes->$fld;
            }
          }
        }
        $js = array('onChange' => "setPaymentBlock(); CRM.buildCustomData( 'Membership', this.value );");
        $this->assign('autoRenew', json_encode($autoRenew));
      }
      $autoRenewElement = $this->addElement('checkbox', 'auto_renew', ts('Membership renewed automatically'),
        NULL, array('onclick' => "showHideByValue('auto_renew','','send-receipt','table-row','radio',true); showHideNotice( );")
      );
      if ($this->_action & CRM_Core_Action::UPDATE) {
        $autoRenewElement->freeze();
      }
    }
    $this->assign('recurProcessor', json_encode($recurProcessor));

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
    if (CRM_Core_Permission::access('CiviContribute') && !$this->_mode) {
      $this->addElement('checkbox', 'record_contribution', ts('Record Renewal Payment?'), NULL, array('onclick' => "checkPayment();"));

      $this->add('text', 'total_amount', ts('Amount'));
      $this->addRule('total_amount', ts('Please enter a valid amount.'), 'money');

      $this->addDate('receive_date', ts('Received'), FALSE, array('formatType' => 'activityDateTime'));

      $this->add('text', 'num_terms', ts('Extend Membership by'), array('onchange' => "setPaymentBlock();"), TRUE);
      $this->addRule('num_terms', ts('Please enter a whole number for how many periods to renew.'), 'integer');

      $this->add('select', 'payment_instrument_id', ts('Paid By'),
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

    if ($this->_mode) {
      $this->add('select', 'payment_processor_id', ts('Payment Processor'), $this->_processors, TRUE);
      CRM_Core_Payment_Form::buildCreditCard($this, TRUE);
    }

    // Retrieve the name and email of the contact - this will be the TO for receipt email
    list($this->_contributorDisplayName,
      $this->_contributorEmail
    ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);
    $this->assign('email', $this->_contributorEmail);

    $mailingInfo = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
      'mailing_backend'
    );
    $this->assign('outBound_option', $mailingInfo['outBound_option']);

    if (CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $this->_id, 'contribution_recur_id')) {
      if (CRM_Member_BAO_Membership::isCancelSubscriptionSupported($this->_id)) {
        $this->assign('cancelAutoRenew',
          CRM_Utils_System::url('civicrm/contribute/unsubscribe', "reset=1&mid={$this->_id}")
        );
      }
    }
    $this->addFormRule(array('CRM_Member_Form_MembershipRenewal', 'formRule'));
    if ($this->_context != 'standalone') {
      //CRM-10223 - allow contribution to be recorded against different contact
      // causes a conflict in standalone mode so skip in standalone for now
      $this->addElement('checkbox', 'contribution_contact', ts('Record Payment from a Different Contact?'));
      $this->addSelect('soft_credit_type_id', array('entity' => 'contribution_soft'));
      $this->addEntityRef('soft_credit_contact_id', ts('Payment From'), array('create' => TRUE));
  }
    }

  /**
   * Function for validation
   *
   * @param array $params (ref.) an assoc array of name/value pairs
   *
   * @return mixed true or array of errors
   * @access public
   * @static
   */
  static function formRule($params) {
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
        $errors['payment_instrument_id'] = ts('Paid By is a required field.');
      }
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Function to process the renewal form
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {

    $ids = array();
    $config = CRM_Core_Config::singleton();

    // get the submitted form values.
    $this->_params = $formValues = $this->controller->exportValues($this->_name);

    $this->storeContactFields($formValues);
    // use values from screen

    if ($formValues['membership_type_id'][1] <> 0) {
      $defaults['receipt_text_renewal'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
        $formValues['membership_type_id'][1],
        'receipt_text_renewal'
      );
    }

    $now = CRM_Utils_Date::getToday( null, 'YmdHis');
    $this->convertDateFieldsToMySQL($formValues);
    $this->assign('receive_date', $formValues['receive_date']);

    if (!empty($this->_params['send_receipt'])) {
      $formValues['receipt_date'] = $now;
      $this->assign('receipt_date', CRM_Utils_Date::mysqlToIso($formValues['receipt_date']));
    }
    else {
      $formValues['receipt_date'] = NULL;
    }

    if ($this->_mode) {
      $formValues['total_amount'] = CRM_Utils_Array::value('total_amount', $this->_params, CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
          $this->_memType, 'minimum_fee'
        ));
      if (empty($formValues['financial_type_id'])) {
        $formValues['financial_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $this->_memType,'financial_type_id');
      }

      $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($formValues['payment_processor_id'],
        $this->_mode
      );

      $fields = array();

      // set email for primary location.
      $fields['email-Primary'] = 1;
      $formValues['email-5'] = $formValues['email-Primary'] = $this->_contributorEmail;
      $formValues['register_date'] = $now;

      // now set the values for the billing location.
      foreach ($this->_fields as $name => $dontCare) {
        $fields[$name] = 1;
      }

      // also add location name to the array
      $formValues["address_name-{$this->_bltID}"] = CRM_Utils_Array::value('billing_first_name', $formValues) . ' ' . CRM_Utils_Array::value('billing_middle_name', $formValues) . ' ' . CRM_Utils_Array::value('billing_last_name', $formValues);

      $formValues["address_name-{$this->_bltID}"] = trim($formValues["address_name-{$this->_bltID}"]);

      $fields["address_name-{$this->_bltID}"] = 1;

      $fields["email-{$this->_bltID}"] = 1;

      $ctype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactID, 'contact_type');

      $nameFields = array('first_name', 'middle_name', 'last_name');

      foreach ($nameFields as $name) {
        $fields[$name] = 1;
        if (array_key_exists("billing_$name", $formValues)) {
          $formValues[$name] = $formValues["billing_{$name}"];
          $formValues['preserveDBName'] = TRUE;
        }
      }

      //here we are setting up the billing contact - if different from the member they are already created
      // but they will get billing details assigned
      CRM_Contact_BAO_Contact::createProfileContact($formValues, $fields,
        $this->_contributorContactID, NULL, NULL, $ctype
      );

      // add all the additional payment params we need
      $this->_params["state_province-{$this->_bltID}"] = $this->_params["billing_state_province-{$this->_bltID}"] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($this->_params["billing_state_province_id-{$this->_bltID}"]);
      $this->_params["country-{$this->_bltID}"] = $this->_params["billing_country-{$this->_bltID}"] = CRM_Core_PseudoConstant::countryIsoCode($this->_params["billing_country_id-{$this->_bltID}"]);

      $this->_params['year'] = CRM_Core_Payment_Form::getCreditCardExpirationYear($this->_params);
      $this->_params['month'] = CRM_Core_Payment_Form::getCreditCardExpirationMonth($this->_params);
      $this->_params['ip_address'] = CRM_Utils_System::ipAddress();
      $this->_params['amount'] = $formValues['total_amount'];
      $this->_params['currencyID'] = $config->defaultCurrency;
      $this->_params['payment_action'] = 'Sale';
      $this->_params['invoiceID'] = md5(uniqid(rand(), TRUE));

      // at this point we've created a contact and stored its address etc
      // all the payment processors expect the name and address to be in the passed params
      // so we copy stuff over to first_name etc.
      $paymentParams = $this->_params;
      if (!empty($this->_params['send_receipt'])) {
        $paymentParams['email'] = $this->_contributorEmail;
      }

      $paymentParams['contactID'] = $this->_contributorContactID;

      CRM_Core_Payment_Form::mapParams($this->_bltID, $this->_params, $paymentParams, TRUE);

      $payment = CRM_Core_Payment::singleton($this->_mode, $this->_paymentProcessor, $this);

      $result = &$payment->doDirectPayment($paymentParams);

      if (is_a($result, 'CRM_Core_Error')) {
        CRM_Core_Error::displaySessionError($result);
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/membership',
            "reset=1&action=renew&cid={$this->_contactID}&id={$this->_id}&context=membership&mode={$this->_mode}"
          ));
      }

      if ($result) {
        $this->_params = array_merge($this->_params, $result);
      }
      $formValues['contribution_status_id'] = 1;
      $formValues['invoice_id'] = $this->_params['invoiceID'];
      $formValues['trxn_id'] = $result['trxn_id'];
      $formValues['payment_instrument_id'] = 1;
      $formValues['is_test'] = ($this->_mode == 'live') ? 0 : 1;
      $this->set('params', $this->_params);
      $this->assign('trxn_id', $result['trxn_id']);
    }

    $renewalDate = NULL;

    if ($formValues['renewal_date']) {
      $this->set('renewalDate', CRM_Utils_Date::processDate($formValues['renewal_date']));
    }
    $this->_membershipId = $this->_id;

    // membership type custom data
    $customFields = CRM_Core_BAO_CustomField::getFields('Membership', FALSE, FALSE,
      $formValues['membership_type_id'][1]
    );

    $customFields = CRM_Utils_Array::crmArrayMerge($customFields,
      CRM_Core_BAO_CustomField::getFields('Membership',
        FALSE, FALSE,
        NULL, NULL, TRUE
      )
    );

    $customFieldsFormatted = CRM_Core_BAO_CustomField::postProcess($formValues,
      $customFields,
      $this->_id,
      'Membership'
    );

    // check for test membership.
    $isTestMembership = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $this->_membershipId, 'is_test');

    // chk for renewal for multiple terms CRM-8750
    $numRenewTerms = 1;
    if (is_numeric(CRM_Utils_Array::value('num_terms', $formValues))) {
      $numRenewTerms = $formValues['num_terms'];
    }

    //if contribution status is pending then set pay later
    if ($formValues['contribution_status_id'] == array_search('Pending', CRM_Contribute_PseudoConstant::contributionStatus())) {
      $this->_params['is_pay_later'] = 1;
    }
    $renewMembership = CRM_Member_BAO_Membership::renewMembershipFormWrapper($this->_contactID,
      $formValues['membership_type_id'][1],
      $isTestMembership, $this, NULL, NULL,
      $customFieldsFormatted, $numRenewTerms,
      $this->_membershipId
    );

    $endDate = CRM_Utils_Date::processDate($renewMembership->end_date);

    // Retrieve the name and email of the current user - this will be the FROM for the receipt email
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');

    list($userName, $userEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($userID);

    $memType = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $renewMembership->membership_type_id, 'name');

    if (!empty($formValues['record_contribution']) || $this->_mode) {
      // set the source
      $formValues['contribution_source'] = "{$memType} Membership: Offline membership renewal (by {$userName})";

      //create line items
      $lineItem = array();
      $priceSetId = null;
      CRM_Member_BAO_Membership::createLineItems($this, $formValues['membership_type_id'], $priceSetId);
      CRM_Price_BAO_PriceSet::processAmount($this->_priceSet['fields'],
        $this->_params, $lineItem[$priceSetId]
      );
      //CRM-11529 for quick config backoffice transactions
      //when financial_type_id is passed in form, update the
      //lineitems with the financial type selected in form
      if ($submittedFinancialType = CRM_Utils_Array::value('financial_type_id', $formValues)) {
        foreach ($lineItem[$priceSetId] as &$li) {
          $li['financial_type_id'] = $submittedFinancialType;
        }
      }
      $formValues['total_amount'] = CRM_Utils_Array::value('amount', $this->_params);
      if (!empty($lineItem)) {
        $formValues['lineItems'] = $lineItem;
        $formValues['processPriceSet'] = TRUE;
      }

      //assign contribution contact id to the field expected by recordMembershipContribution
      if($this->_contributorContactID != $this->_contactID){
        $formValues['contribution_contact_id'] = $this->_contributorContactID;
        if (!empty($this->_params['soft_credit_type_id'])){
          $formValues['soft_credit'] = array(
            'soft_credit_type_id' => $this->_params['soft_credit_type_id'],
            'contact_id' => $this->_contactID,
          );
        }
      }
      $formValues['contact_id'] = $this->_contactID;

      CRM_Member_BAO_Membership::recordMembershipContribution(array_merge($formValues, array('membership_id' => $renewMembership->id)));
    }

    $receiptSend = FALSE;
    if (!empty($formValues['send_receipt'])) {
      $receiptSend = TRUE;

      $receiptFrom = $formValues['from_email_address'];

      if (!empty($formValues['payment_instrument_id'])) {
        $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
        $formValues['paidBy'] = $paymentInstrument[$formValues['payment_instrument_id']];
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

      $this->assign_by_ref('formValues', $formValues);
      if (!empty($formValues['contribution_id'])) {
        $this->assign('contributionID', $formValues['contribution_id']);
      }
      $this->assign('membershipID', $this->_id);
      $this->assign('contactID', $this->_contactID);
      $this->assign('module', 'Membership');
      $this->assign('receiptType', 'membership renewal');
      $this->assign('mem_start_date', CRM_Utils_Date::customFormat($renewMembership->start_date));
      $this->assign('mem_end_date', CRM_Utils_Date::customFormat($renewMembership->end_date));
      $this->assign('membership_name', CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
          $renewMembership->membership_type_id
        ));
      $this->assign('customValues', $customValues);
      if ($this->_mode) {
        if (!empty($this->_params['billing_first_name'])) {
          $name = $this->_params['billing_first_name'];
        }

        if (!empty($this->_params['billing_middle_name'])) {
          $name .= " {$this->_params['billing_middle_name']}";
        }

        if (!empty($this->_params['billing_last_name'])) {
          $name .= " {$this->_params['billing_last_name']}";
        }
        $this->assign('billingName', $name);

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
          list($n, $id) = explode('-', $part);
          if (isset($this->_params['billing_' . $part])) {
            $addressFields[$n] = $this->_params['billing_' . $part];
          }
        }
        $this->assign('address', CRM_Utils_Address::format($addressFields));
        $date = CRM_Utils_Date::format($this->_params['credit_card_exp_date']);
        $date = CRM_Utils_Date::mysqlToIso($date);
        $this->assign('credit_card_exp_date', $date);
        $this->assign('credit_card_number',
          CRM_Utils_System::mungeCreditCard($this->_params['credit_card_number'])
        );
        $this->assign('credit_card_type', $this->_params['credit_card_type']);
        $this->assign('contributeMode', 'direct');
        $this->assign('isAmountzero', 0);
        $this->assign('is_pay_later', 0);
        $this->assign('isPrimary', 1);
        if ($this->_mode == 'test') {
          $this->assign('action', '1024');
        }
      }

      list($mailSend, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate(
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

    $statusMsg = ts('%1 membership for %2 has been renewed.', array(1 => $memType, 2 => $this->_memberDisplayName));

    if ($endDate) {
      $statusMsg .= ' ' . ts('The new membership End Date is %1.', array(1 => CRM_Utils_Date::customFormat(substr($endDate,0,8))));
    }

    if ($receiptSend && $mailSend) {
      $statusMsg .= ' ' . ts('A renewal confirmation and receipt has been sent to %1.', array(1 => $this->_contributorEmail));
    }

    CRM_Core_Session::setStatus($statusMsg, ts('Complete'), 'success');
  }
}

