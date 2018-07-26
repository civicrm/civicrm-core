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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * This class generates form components for offline membership form.
 */
class CRM_Member_Form_Membership extends CRM_Member_Form {

  protected $_memType = NULL;

  protected $_onlinePendingContributionId;

  public $_mode;

  public $_contributeMode = 'direct';

  protected $_recurMembershipTypes;

  protected $_memTypeSelected;

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
   * ID of the person the receipt is to go to.
   *
   * @var int
   */
  protected $_receiptContactId = NULL;

  /**
   * Keep a class variable for ALL membership IDs so
   * postProcess hook function can do something with it
   *
   * @var array
   */
  protected $_membershipIDs = array();

  /**
   * An array to hold a list of date fields on the form
   * so that they can be converted to ISO in a consistent manner
   *
   * @var array
   */
  protected $_dateFields = array(
    'receive_date' => array('default' => 'now'),
  );

  /**
   * Get the entity id being edited.
   *
   * @return int|null
   */
  public function getEntityId() {
    return $this->_id;
  }

  /**
   * Get selected membership type from the form values.
   *
   * @param array $priceSet
   * @param array $params
   *
   * @return array
   */
  public static function getSelectedMemberships($priceSet, $params) {
    $memTypeSelected = array();
    $priceFieldIDS = self::getPriceFieldIDs($params, $priceSet);
    if (isset($params['membership_type_id']) && !empty($params['membership_type_id'][1])) {
      $memTypeSelected = array($params['membership_type_id'][1] => $params['membership_type_id'][1]);
    }
    else {
      foreach ($priceFieldIDS as $priceFieldId) {
        if ($id = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $priceFieldId, 'membership_type_id')) {
          $memTypeSelected[$id] = $id;
        }
      }
    }
    return $memTypeSelected;
  }

  /**
   * Extract price set fields and values from $params.
   *
   * @param array $params
   * @param array $priceSet
   *
   * @return array
   */
  public static function getPriceFieldIDs($params, $priceSet) {
    $priceFieldIDS = array();
    if (isset($priceSet['fields']) && is_array($priceSet['fields'])) {
      foreach ($priceSet['fields'] as $fieldId => $field) {
        if (!empty($params['price_' . $fieldId])) {
          if (is_array($params['price_' . $fieldId])) {
            foreach ($params['price_' . $fieldId] as $priceFldVal => $isSet) {
              if ($isSet) {
                $priceFieldIDS[] = $priceFldVal;
              }
            }
          }
          elseif (!$field['is_enter_qty']) {
            $priceFieldIDS[] = $params['price_' . $fieldId];
          }
        }
      }
    }
    return $priceFieldIDS;
  }

  /**
   * Form preProcess function.
   *
   * @throws \Exception
   */
  public function preProcess() {
    // This string makes up part of the class names, differentiating them (not sure why) from the membership fields.
    $this->assign('formClass', 'membership');
    parent::preProcess();
    // get price set id.
    $this->_priceSetId = CRM_Utils_Array::value('priceSetId', $_GET);
    $this->set('priceSetId', $this->_priceSetId);
    $this->assign('priceSetId', $this->_priceSetId);

    if ($this->_action & CRM_Core_Action::DELETE) {
      $contributionID = CRM_Member_BAO_Membership::getMembershipContributionId($this->_id);
      // check delete permission for contribution
      if ($this->_id && $contributionID && !CRM_Core_Permission::checkActionPermission('CiviContribute', $this->_action)) {
        CRM_Core_Error::fatal(ts("This Membership is linked to a contribution. You must have 'delete in CiviContribute' permission in order to delete this record."));
      }
    }

    if ($this->_action & CRM_Core_Action::ADD) {
      if ($this->_contactID) {
        //check whether contact has a current membership so we can alert user that they may want to do a renewal instead
        $contactMemberships = array();
        $memParams = array('contact_id' => $this->_contactID);
        CRM_Member_BAO_Membership::getValues($memParams, $contactMemberships, TRUE);
        $cMemTypes = array();
        foreach ($contactMemberships as $mem) {
          $cMemTypes[] = $mem['membership_type_id'];
        }
        if (count($cMemTypes) > 0) {
          $memberorgs = CRM_Member_BAO_MembershipType::getMemberOfContactByMemTypes($cMemTypes);
          $mems_by_org = array();
          foreach ($contactMemberships as $mem) {
            $mem['member_of_contact_id'] = CRM_Utils_Array::value($mem['membership_type_id'], $memberorgs);
            if (!empty($mem['membership_end_date'])) {
              $mem['membership_end_date'] = CRM_Utils_Date::customformat($mem['membership_end_date']);
            }
            $mem['membership_type'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
              $mem['membership_type_id'],
              'name', 'id'
            );
            $mem['membership_status'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus',
              $mem['status_id'],
              'label', 'id'
            );
            $mem['renewUrl'] = CRM_Utils_System::url('civicrm/contact/view/membership',
              "reset=1&action=renew&cid={$this->_contactID}&id={$mem['id']}&context=membership&selectedChild=member"
              . ($this->_mode ? '&mode=live' : '')
            );
            $mem['membershipTab'] = CRM_Utils_System::url('civicrm/contact/view',
              "reset=1&force=1&cid={$this->_contactID}&selectedChild=member"
            );
            $mems_by_org[$mem['member_of_contact_id']] = $mem;
          }
          $this->assign('existingContactMemberships', $mems_by_org);
        }
      }
      else {
        // In standalone mode we don't have a contact id yet so lookup will be done client-side with this script:
        $resources = CRM_Core_Resources::singleton();
        $resources->addScriptFile('civicrm', 'templates/CRM/Member/Form/MembershipStandalone.js');
        $passthru = array(
          'typeorgs' => CRM_Member_BAO_MembershipType::getMembershipTypeOrganization(),
          'memtypes' => CRM_Core_PseudoConstant::get('CRM_Member_BAO_Membership', 'membership_type_id'),
          'statuses' => CRM_Core_PseudoConstant::get('CRM_Member_BAO_Membership', 'status_id'),
        );
        $resources->addSetting(array('existingMems' => $passthru));
      }
    }

    if (!$this->_memType) {
      $params = CRM_Utils_Request::exportValues();
      if (!empty($params['membership_type_id'][1])) {
        $this->_memType = $params['membership_type_id'][1];
      }
    }

    // Add custom data to form
    CRM_Custom_Form_CustomData::addToForm($this, $this->_memType);

    // CRM-4395, get the online pending contribution id.
    $this->_onlinePendingContributionId = NULL;
    if (!$this->_mode && $this->_id && ($this->_action & CRM_Core_Action::UPDATE)) {
      $this->_onlinePendingContributionId = CRM_Contribute_BAO_Contribution::checkOnlinePendingContribution($this->_id,
        'Membership'
      );
    }
    $this->assign('onlinePendingContributionId', $this->_onlinePendingContributionId);

    $this->setPageTitle(ts('Membership'));
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {

    if ($this->_priceSetId) {
      return CRM_Price_BAO_PriceSet::setDefaultPriceSet($this, $defaults);
    }

    $defaults = parent::setDefaultValues();

    //setting default join date and receive date
    list($now, $currentTime) = CRM_Utils_Date::setDateDefaults();
    if ($this->_action == CRM_Core_Action::ADD) {
      $defaults['receive_date'] = $now;
      $defaults['receive_date_time'] = $currentTime;
    }

    $defaults['num_terms'] = 1;

    if (!empty($defaults['id'])) {
      if ($this->_onlinePendingContributionId) {
        $defaults['record_contribution'] = $this->_onlinePendingContributionId;
      }
      else {
        $contributionId = CRM_Core_DAO::singleValueQuery("
  SELECT contribution_id
  FROM civicrm_membership_payment
  WHERE membership_id = $this->_id
  ORDER BY contribution_id
  DESC limit 1");

        if ($contributionId) {
          $defaults['record_contribution'] = $contributionId;
        }
      }
    }

    //set Soft Credit Type to Gift by default
    $scTypes = CRM_Core_OptionGroup::values("soft_credit_type");
    $defaults['soft_credit_type_id'] = CRM_Utils_Array::value(ts('Gift'), array_flip($scTypes));

    if (!empty($defaults['record_contribution']) && !$this->_mode) {
      $contributionParams = array('id' => $defaults['record_contribution']);
      $contributionIds = array();

      //keep main object campaign in hand.
      $memberCampaignId = CRM_Utils_Array::value('campaign_id', $defaults);

      CRM_Contribute_BAO_Contribution::getValues($contributionParams, $defaults, $contributionIds);

      //get back original object campaign id.
      $defaults['campaign_id'] = $memberCampaignId;

      if (!empty($defaults['receive_date'])) {
        list($defaults['receive_date']) = CRM_Utils_Date::setDateDefaults($defaults['receive_date']);
      }

      // Contribution::getValues() over-writes the membership record's source field value - so we need to restore it.
      if (!empty($defaults['membership_source'])) {
        $defaults['source'] = $defaults['membership_source'];
      }
    }
    //CRM-13420
    if (empty($defaults['payment_instrument_id'])) {
      $defaults['payment_instrument_id'] = key(CRM_Core_OptionGroup::values('payment_instrument', FALSE, FALSE, FALSE, 'AND is_default = 1'));
    }

    // User must explicitly choose to send a receipt in both add and update mode.
    $defaults['send_receipt'] = 0;

    if ($this->_action & CRM_Core_Action::UPDATE) {
      // in this mode by default uncheck this checkbox
      unset($defaults['record_contribution']);
    }

    $subscriptionCancelled = FALSE;
    if (!empty($defaults['id'])) {
      $subscriptionCancelled = CRM_Member_BAO_Membership::isSubscriptionCancelled($this->_id);
    }

    $alreadyAutoRenew = FALSE;
    if (!empty($defaults['contribution_recur_id']) && !$subscriptionCancelled) {
      $defaults['auto_renew'] = 1;
      $alreadyAutoRenew = TRUE;
    }
    $this->assign('alreadyAutoRenew', $alreadyAutoRenew);

    $this->assign('member_is_test', CRM_Utils_Array::value('member_is_test', $defaults));

    $this->assign('membership_status_id', CRM_Utils_Array::value('status_id', $defaults));

    if (!empty($defaults['is_pay_later'])) {
      $this->assign('is_pay_later', TRUE);
    }
    if ($this->_mode) {
      $defaults = $this->getBillingDefaults($defaults);
      // hack to simplify credit card entry for testing
      // $defaults['credit_card_type']     = 'Visa';
      // $defaults['credit_card_number']   = '4807731747657838';
      // $defaults['cvv2']                 = '000';
      // $defaults['credit_card_exp_date'] = array( 'Y' => '2012', 'M' => '05' );
    }

    $dates = array('join_date', 'start_date', 'end_date');
    foreach ($dates as $key) {
      if (!empty($defaults[$key])) {
        list($defaults[$key]) = CRM_Utils_Date::setDateDefaults(CRM_Utils_Array::value($key, $defaults));
      }
    }

    //setting default join date if there is no join date
    if (empty($defaults['join_date'])) {
      $defaults['join_date'] = $now;
    }

    if (!empty($defaults['membership_end_date'])) {
      $this->assign('endDate', $defaults['membership_end_date']);
    }

    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    $this->assign('taxRates', json_encode(CRM_Core_PseudoConstant::getTaxRates()));

    $this->assign('currency', CRM_Core_Config::singleton()->defaultCurrencySymbol);
    $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
    $invoicing = CRM_Utils_Array::value('invoicing', $invoiceSettings);
    if (isset($invoicing)) {
      $this->assign('taxTerm', CRM_Utils_Array::value('tax_term', $invoiceSettings));
    }
    // build price set form.
    $buildPriceSet = FALSE;
    if ($this->_priceSetId || !empty($_POST['price_set_id'])) {
      if (!empty($_POST['price_set_id'])) {
        $buildPriceSet = TRUE;
      }
      $getOnlyPriceSetElements = TRUE;
      if (!$this->_priceSetId) {
        $this->_priceSetId = $_POST['price_set_id'];
        $getOnlyPriceSetElements = FALSE;
      }

      $this->set('priceSetId', $this->_priceSetId);
      CRM_Price_BAO_PriceSet::buildPriceSet($this);

      $optionsMembershipTypes = array();
      foreach ($this->_priceSet['fields'] as $pField) {
        if (empty($pField['options'])) {
          continue;
        }
        foreach ($pField['options'] as $opId => $opValues) {
          $optionsMembershipTypes[$opId] = CRM_Utils_Array::value('membership_type_id', $opValues, 0);
        }
      }

      $this->assign('autoRenewOption', CRM_Price_BAO_PriceSet::checkAutoRenewForPriceSet($this->_priceSetId));

      $this->assign('optionsMembershipTypes', $optionsMembershipTypes);
      $this->assign('contributionType', CRM_Utils_Array::value('financial_type_id', $this->_priceSet));

      // get only price set form elements.
      if ($getOnlyPriceSetElements) {
        return;
      }
    }

    // use to build form during form rule.
    $this->assign('buildPriceSet', $buildPriceSet);

    if ($this->_action & CRM_Core_Action::ADD) {
      $buildPriceSet = FALSE;
      $priceSets = CRM_Price_BAO_PriceSet::getAssoc(FALSE, 'CiviMember');
      if (!empty($priceSets)) {
        $buildPriceSet = TRUE;
      }

      if ($buildPriceSet) {
        $this->add('select', 'price_set_id', ts('Choose price set'),
          array(
            '' => ts('Choose price set'),
          ) + $priceSets,
          NULL, array('onchange' => "buildAmount( this.value );")
        );
      }
      $this->assign('hasPriceSets', $buildPriceSet);
    }

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
      ));
      return;
    }

    if ($this->_context == 'standalone') {
      $this->addEntityRef('contact_id', ts('Contact'), array(
        'create' => TRUE,
        'api' => array('extra' => array('email')),
      ), TRUE);
    }

    $selOrgMemType[0][0] = $selMemTypeOrg[0] = ts('- select -');

    // Throw status bounce when no Membership type or priceset is present
    if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()
      && empty($this->allMembershipTypeDetails) && empty($priceSets)
    ) {
      CRM_Core_Error::statusBounce(ts('You do not have all the permissions needed for this page.'));
    }
    // retrieve all memberships
    $allMembershipInfo = array();
    foreach ($this->allMembershipTypeDetails as $key => $values) {
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
      $totalAmount = CRM_Utils_Array::value('minimum_fee', $values);
      //CRM-18827 - override the default value if total_amount is submitted
      if (!empty($this->_submitValues['total_amount'])) {
        $totalAmount = $this->_submitValues['total_amount'];
      }
      // build membership info array, which is used when membership type is selected to:
      // - set the payment information block
      // - set the max related block
      $allMembershipInfo[$key] = array(
        'financial_type_id' => CRM_Utils_Array::value('financial_type_id', $values),
        'total_amount' => CRM_Utils_Money::format($totalAmount, NULL, '%a'),
        'total_amount_numeric' => $totalAmount,
        'auto_renew' => CRM_Utils_Array::value('auto_renew', $values),
        'has_related' => isset($values['relationship_type_id']),
        'max_related' => CRM_Utils_Array::value('max_related', $values),
      );
    }

    $this->assign('allMembershipInfo', json_encode($allMembershipInfo));

    // show organization by default, if only one organization in
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

    $memTypeJs = array(
      'onChange' => "buildMaxRelated(this.value,true); CRM.buildCustomData('Membership', this.value);",
    );

    if (!empty($this->_recurPaymentProcessors)) {
      $memTypeJs['onChange'] = "" . $memTypeJs['onChange'] . "buildAutoRenew(this.value, null, '{$this->_mode}');";
    }

    $this->add('text', 'max_related', ts('Max related'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_Membership', 'max_related')
    );

    $sel = &$this->addElement('hierselect',
      'membership_type_id',
      ts('Membership Organization and Type'),
      $memTypeJs
    );

    $sel->setOptions(array($selMemTypeOrg, $selOrgMemType));
    $elements = array();
    if ($sel) {
      $elements[] = $sel;
    }

    $this->applyFilter('__ALL__', 'trim');

    if ($this->_action & CRM_Core_Action::ADD) {
      $this->add('text', 'num_terms', ts('Number of Terms'), array('size' => 6));
    }

    $this->addDate('join_date', ts('Member Since'), FALSE, array('formatType' => 'activityDate'));
    $this->addDate('start_date', ts('Start Date'), FALSE, array('formatType' => 'activityDate'));
    $endDate = $this->addDate('end_date', ts('End Date'), FALSE, array('formatType' => 'activityDate'));
    if ($endDate) {
      $elements[] = $endDate;
    }

    $this->add('text', 'source', ts('Source'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_Membership', 'source')
    );

    //CRM-7362 --add campaigns.
    $campaignId = NULL;
    if ($this->_id) {
      $campaignId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $this->_id, 'campaign_id');
    }
    CRM_Campaign_BAO_Campaign::addCampaign($this, $campaignId);

    if (!$this->_mode) {
      $this->add('select', 'status_id', ts('Membership Status'),
        array('' => ts('- select -')) + CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label')
      );

      $statusOverride = $this->addElement('select', 'is_override', ts('Status Override?'),
        CRM_Member_StatusOverrideTypes::getSelectOptions()
      );
      if ($statusOverride) {
        $elements[] = $statusOverride;
      }

      $this->add('datepicker', 'status_override_end_date', ts('Status Override End Date'), '', FALSE, array('minDate' => time(), 'time' => FALSE));

      $this->addElement('checkbox', 'record_contribution', ts('Record Membership Payment?'));

      $this->add('text', 'total_amount', ts('Amount'));
      $this->addRule('total_amount', ts('Please enter a valid amount.'), 'money');

      $this->addDate('receive_date', ts('Received'), FALSE, array('formatType' => 'activityDateTime'));

      $this->add('select', 'payment_instrument_id',
        ts('Payment Method'),
        array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::paymentInstrument(),
        FALSE, array('onChange' => "return showHideByValue('payment_instrument_id','4','checkNumber','table-row','select',false);")
      );
      $this->add('text', 'trxn_id', ts('Transaction ID'));
      $this->addRule('trxn_id', ts('Transaction ID already exists in Database.'),
        'objectExists', array(
          'CRM_Contribute_DAO_Contribution',
          $this->_id,
          'trxn_id',
        )
      );

      $this->add('select', 'contribution_status_id',
        ts('Payment Status'), CRM_Contribute_BAO_Contribution_Utils::getContributionStatuses('membership')
      );
      $this->add('text', 'check_number', ts('Check Number'),
        CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution', 'check_number')
      );
    }
    else {
      //add field for amount to allow an amount to be entered that differs from minimum
      $this->add('text', 'total_amount', ts('Amount'));
    }
    $this->add('select', 'financial_type_id',
      ts('Financial Type'),
      array('' => ts('- select -')) + CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes, $this->_action)
    );

    $this->addElement('checkbox', 'is_different_contribution_contact', ts('Record Payment from a Different Contact?'));

    $this->addSelect('soft_credit_type_id', array('entity' => 'contribution_soft'));
    $this->addEntityRef('soft_credit_contact_id', ts('Payment From'), array('create' => TRUE));

    $this->addElement('checkbox',
      'send_receipt',
      ts('Send Confirmation and Receipt?'), NULL,
      array('onclick' => "showEmailOptions()")
    );

    $this->add('select', 'from_email_address', ts('Receipt From'), $this->_fromEmails);

    $this->add('textarea', 'receipt_text', ts('Receipt Message'));

    // Retrieve the name and email of the contact - this will be the TO for receipt email
    if ($this->_contactID) {
      list($this->_memberDisplayName,
        $this->_memberEmail
        ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);

      $this->assign('emailExists', $this->_memberEmail);
      $this->assign('displayName', $this->_memberDisplayName);
    }

    $isRecur = FALSE;
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $recurContributionId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $this->_id,
        'contribution_recur_id'
      );
      if ($recurContributionId && !CRM_Member_BAO_Membership::isSubscriptionCancelled($this->_id)) {
        $isRecur = TRUE;
        if (CRM_Member_BAO_Membership::isCancelSubscriptionSupported($this->_id)) {
          $this->assign('cancelAutoRenew',
            CRM_Utils_System::url('civicrm/contribute/unsubscribe', "reset=1&mid={$this->_id}")
          );
        }
        foreach ($elements as $elem) {
          $elem->freeze();
        }
      }
    }
    $this->assign('isRecur', $isRecur);

    $this->addFormRule(array('CRM_Member_Form_Membership', 'formRule'), $this);
    $mailingInfo = Civi::settings()->get('mailing_backend');
    $this->assign('isEmailEnabledForSite', ($mailingInfo['outBound_option'] != 2));

    parent::buildQuickForm();
  }

  /**
   * Validation.
   *
   * @param array $params
   *   (ref.) an assoc array of name/value pairs.
   *
   * @param array $files
   * @param CRM_Member_Form_Membership $self
   *
   * @throws CiviCRM_API3_Exception
   * @return bool|array
   *   mixed true or array of errors
   */
  public static function formRule($params, $files, $self) {
    $errors = array();

    $priceSetId = self::getPriceSetID($params);
    $priceSetDetails = self::getPriceSetDetails($params);

    $selectedMemberships = self::getSelectedMemberships($priceSetDetails[$priceSetId], $params);

    if (!empty($params['price_set_id'])) {
      CRM_Price_BAO_PriceField::priceSetValidation($priceSetId, $params, $errors);

      $priceFieldIDS = self::getPriceFieldIDs($params, $priceSetDetails[$priceSetId]);

      if (!empty($priceFieldIDS)) {
        $ids = implode(',', $priceFieldIDS);

        $count = CRM_Price_BAO_PriceSet::getMembershipCount($ids);
        foreach ($count as $occurrence) {
          if ($occurrence > 1) {
            $errors['_qf_default'] = ts('Select at most one option associated with the same membership type.');
          }
        }
      }
      // Return error if empty $self->_memTypeSelected
      if (empty($errors) && empty($selectedMemberships)) {
        $errors['_qf_default'] = ts('Select at least one membership option.');
      }
      if (!$self->_mode && empty($params['record_contribution'])) {
        $errors['record_contribution'] = ts('Record Membership Payment is required when you use a price set.');
      }
    }
    else {
      if (empty($params['membership_type_id'][1])) {
        $errors['membership_type_id'] = ts('Please select a membership type.');
      }
      $numterms = CRM_Utils_Array::value('num_terms', $params);
      if ($numterms && intval($numterms) != $numterms) {
        $errors['num_terms'] = ts('Please enter an integer for the number of terms.');
      }

      if (($self->_mode || isset($params['record_contribution'])) && empty($params['financial_type_id'])) {
        $errors['financial_type_id'] = ts('Please enter the financial Type.');
      }
    }

    if (!empty($errors) && (count($selectedMemberships) > 1)) {
      $memberOfContacts = CRM_Member_BAO_MembershipType::getMemberOfContactByMemTypes($selectedMemberships);
      $duplicateMemberOfContacts = array_count_values($memberOfContacts);
      foreach ($duplicateMemberOfContacts as $countDuplicate) {
        if ($countDuplicate > 1) {
          $errors['_qf_default'] = ts('Please do not select more than one membership associated with the same organization.');
        }
      }
    }

    if (!empty($errors)) {
      return $errors;
    }

    if (!empty($params['record_contribution']) && empty($params['payment_instrument_id'])) {
      $errors['payment_instrument_id'] = ts('Payment Method is a required field.');
    }

    if (!empty($params['is_different_contribution_contact'])) {
      if (empty($params['soft_credit_type_id'])) {
        $errors['soft_credit_type_id'] = ts('Please Select a Soft Credit Type');
      }
      if (empty($params['soft_credit_contact_id'])) {
        $errors['soft_credit_contact_id'] = ts('Please select a contact');
      }
    }

    if (!empty($params['payment_processor_id'])) {
      // validate payment instrument (e.g. credit card number)
      CRM_Core_Payment_Form::validatePaymentInstrument($params['payment_processor_id'], $params, $errors, NULL);
    }

    $joinDate = NULL;
    if (!empty($params['join_date'])) {

      $joinDate = CRM_Utils_Date::processDate($params['join_date']);

      foreach ($selectedMemberships as $memType) {
        $startDate = NULL;
        if (!empty($params['start_date'])) {
          $startDate = CRM_Utils_Date::processDate($params['start_date']);
        }

        // if end date is set, ensure that start date is also set
        // and that end date is later than start date
        $endDate = NULL;
        if (!empty($params['end_date'])) {
          $endDate = CRM_Utils_Date::processDate($params['end_date']);
        }

        $membershipDetails = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($memType);

        if ($startDate && CRM_Utils_Array::value('period_type', $membershipDetails) == 'rolling') {
          if ($startDate < $joinDate) {
            $errors['start_date'] = ts('Start date must be the same or later than Member since.');
          }
        }

        if ($endDate) {
          if ($membershipDetails['duration_unit'] == 'lifetime') {
            // Check if status is NOT cancelled or similar. For lifetime memberships, there is no automated
            // process to update status based on end-date. The user must change the status now.
            $result = civicrm_api3('MembershipStatus', 'get', array(
              'sequential' => 1,
              'is_current_member' => 0,
            ));
            $tmp_statuses = $result['values'];
            $status_ids = array();
            foreach ($tmp_statuses as $cur_stat) {
              $status_ids[] = $cur_stat['id'];
            }

            if (empty($params['status_id']) || in_array($params['status_id'], $status_ids) == FALSE) {
              $errors['status_id'] = ts('Please enter a status that does NOT represent a current membership status.');
            }

            if (!empty($params['is_override']) && !CRM_Member_StatusOverrideTypes::isPermanent($params['is_override'])) {
              $errors['is_override'] = ts('Because you set an End Date for a lifetime membership, This must be set to "Override Permanently"');
            }
          }
          else {
            if (!$startDate) {
              $errors['start_date'] = ts('Start date must be set if end date is set.');
            }
            if ($endDate < $startDate) {
              $errors['end_date'] = ts('End date must be the same or later than start date.');
            }
          }
        }

        // Default values for start and end dates if not supplied on the form.
        $defaultDates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($memType,
          $joinDate,
          $startDate,
          $endDate
        );

        if (!$startDate) {
          $startDate = CRM_Utils_Array::value('start_date',
            $defaultDates
          );
        }
        if (!$endDate) {
          $endDate = CRM_Utils_Array::value('end_date',
            $defaultDates
          );
        }

        //CRM-3724, check for availability of valid membership status.
        if ((empty($params['is_override']) || CRM_Member_StatusOverrideTypes::isNo($params['is_override'])) && !isset($errors['_qf_default'])) {
          $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($startDate,
            $endDate,
            $joinDate,
            'today',
            TRUE,
            $memType,
            $params
          );
          if (empty($calcStatus)) {
            $url = CRM_Utils_System::url('civicrm/admin/member/membershipStatus', 'reset=1&action=browse');
            $errors['_qf_default'] = ts('There is no valid Membership Status available for selected membership dates.');
            $status = ts('Oops, it looks like there is no valid membership status available for the given membership dates. You can <a href="%1">Configure Membership Status Rules</a>.', array(1 => $url));
            if (!$self->_mode) {
              $status .= ' ' . ts('OR You can sign up by setting Status Override? to something other than "NO".');
            }
            CRM_Core_Session::setStatus($status, ts('Membership Status Error'), 'error');
          }
        }
      }
    }
    else {
      $errors['join_date'] = ts('Please enter the Member Since.');
    }

    if (!empty($params['is_override']) && CRM_Member_StatusOverrideTypes::isOverridden($params['is_override']) && empty($params['status_id'])) {
      $errors['status_id'] = ts('Please enter the Membership status.');
    }

    if (!empty($params['is_override']) && CRM_Member_StatusOverrideTypes::isUntilDate($params['is_override'])) {
      if (empty($params['status_override_end_date'])) {
        $errors['status_override_end_date'] = ts('Please enter the Membership override end date.');
      }
    }

    //total amount condition arise when membership type having no
    //minimum fee
    if (isset($params['record_contribution'])) {
      if (CRM_Utils_System::isNull($params['total_amount'])) {
        $errors['total_amount'] = ts('Please enter the contribution.');
      }
    }

    // validate contribution status for 'Failed'.
    if ($self->_onlinePendingContributionId && !empty($params['record_contribution']) &&
      (CRM_Utils_Array::value('contribution_status_id', $params) ==
        array_search('Failed', CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name'))
      )
    ) {
      $errors['contribution_status_id'] = ts('Please select a valid payment status before updating.');
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Member_BAO_Membership::del($this->_id);
      return;
    }
    // get the submitted form values.
    $this->_params = $this->controller->exportValues($this->_name);
    $this->prepareStatusOverrideValues();

    $this->submit();

    $this->setUserContext();
  }

  /**
   * Prepares the values related to status override.
   */
  private function prepareStatusOverrideValues() {
    $this->setOverrideDateValue();
    $this->convertIsOverrideValue();
  }

  /**
   * Sets status override end date to empty value if
   * the selected override option is not 'until date'.
   */
  private function setOverrideDateValue() {
    if (!CRM_Member_StatusOverrideTypes::isUntilDate(CRM_Utils_Array::value('is_override', $this->_params))) {
      $this->_params['status_override_end_date'] = '';
    }
  }

  /**
   * Convert the value of selected (status override?)
   * option to TRUE if it indicate an overridden status
   * or FALSE otherwise.
   */
  private function convertIsOverrideValue() {
    $this->_params['is_override'] = CRM_Member_StatusOverrideTypes::isOverridden($this->_params['is_override']);
  }

  /**
   * Send email receipt.
   *
   * @param CRM_Core_Form $form
   *   Form object.
   * @param array $formValues
   * @param object $membership
   *   Object.
   *
   * @return bool
   *   true if mail was sent successfully
   */
  public static function emailReceipt(&$form, &$formValues, &$membership) {
    // retrieve 'from email id' for acknowledgement
    $receiptFrom = CRM_Utils_Array::value('from_email_address', $formValues);

    if (!empty($formValues['payment_instrument_id'])) {
      $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
      $formValues['paidBy'] = $paymentInstrument[$formValues['payment_instrument_id']];
    }

    // retrieve custom data
    $customFields = $customValues = array();
    if (property_exists($form, '_groupTree')
      && !empty($form->_groupTree)
    ) {
      foreach ($form->_groupTree as $groupID => $group) {
        if ($groupID == 'info') {
          continue;
        }
        foreach ($group['fields'] as $k => $field) {
          $field['title'] = $field['label'];
          $customFields["custom_{$k}"] = $field;
        }
      }
    }

    $members = array(array('member_id', '=', $membership->id, 0, 0));
    // check whether its a test drive
    if ($form->_mode == 'test') {
      $members[] = array('member_test', '=', 1, 0, 0);
    }

    CRM_Core_BAO_UFGroup::getValues($formValues['contact_id'], $customFields, $customValues, FALSE, $members);
    $form->assign('customValues', $customValues);

    if ($form->_mode) {
      $form->assign('address', CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters(
        $form->_params,
        $form->_bltID
      ));

      $date = CRM_Utils_Date::format($form->_params['credit_card_exp_date']);
      $date = CRM_Utils_Date::mysqlToIso($date);
      $form->assign('credit_card_exp_date', $date);
      $form->assign('credit_card_number',
        CRM_Utils_System::mungeCreditCard($form->_params['credit_card_number'])
      );
      $form->assign('credit_card_type', $form->_params['credit_card_type']);
      $form->assign('contributeMode', 'direct');
      $form->assign('isAmountzero', 0);
      $form->assign('is_pay_later', 0);
      $form->assign('isPrimary', 1);
    }

    $form->assign('module', 'Membership');
    $form->assign('contactID', $formValues['contact_id']);

    $form->assign('membershipID', CRM_Utils_Array::value('membership_id', $form->_params, CRM_Utils_Array::value('membership_id', $form->_defaultValues)));

    if (!empty($formValues['contribution_id'])) {
      $form->assign('contributionID', $formValues['contribution_id']);
    }
    elseif (isset($form->_onlinePendingContributionId)) {
      $form->assign('contributionID', $form->_onlinePendingContributionId);
    }

    if (!empty($formValues['contribution_status_id'])) {
      $form->assign('contributionStatusID', $formValues['contribution_status_id']);
      $form->assign('contributionStatus', CRM_Contribute_PseudoConstant::contributionStatus($formValues['contribution_status_id'], 'name'));
    }

    if (!empty($formValues['is_renew'])) {
      $form->assign('receiptType', 'membership renewal');
    }
    else {
      $form->assign('receiptType', 'membership signup');
    }
    $form->assign('receive_date', CRM_Utils_Date::processDate(CRM_Utils_Array::value('receive_date', $formValues)));
    $form->assign('formValues', $formValues);

    if (empty($lineItem)) {
      $form->assign('mem_start_date', CRM_Utils_Date::customFormat($membership->start_date, '%B %E%f, %Y'));
      if (!CRM_Utils_System::isNull($membership->end_date)) {
        $form->assign('mem_end_date', CRM_Utils_Date::customFormat($membership->end_date, '%B %E%f, %Y'));
      }
      $form->assign('membership_name', CRM_Member_PseudoConstant::membershipType($membership->membership_type_id));
    }

    $isBatchProcess = is_a($form, 'CRM_Batch_Form_Entry');
    if ((empty($form->_contributorDisplayName) || empty($form->_contributorEmail)) || $isBatchProcess) {
      // in this case the form is being called statically from the batch editing screen
      // having one class in the form layer call another statically is not greate
      // & we should aim to move this function to the BAO layer in future.
      // however, we can assume that the contact_id passed in by the batch
      // function will be the recipient
      list($form->_contributorDisplayName, $form->_contributorEmail)
        = CRM_Contact_BAO_Contact_Location::getEmailDetails($formValues['contact_id']);
      if (empty($form->_receiptContactId) || $isBatchProcess) {
        $form->_receiptContactId = $formValues['contact_id'];
      }
    }
    $template = CRM_Core_Smarty::singleton();
    $taxAmt = $template->get_template_vars('dataArray');
    $eventTaxAmt = $template->get_template_vars('totalTaxAmount');
    $prefixValue = Civi::settings()->get('contribution_invoice_settings');
    $invoicing = CRM_Utils_Array::value('invoicing', $prefixValue);
    if ((!empty($taxAmt) || isset($eventTaxAmt)) && (isset($invoicing) && isset($prefixValue['is_email_pdf']))) {
      $isEmailPdf = TRUE;
    }
    else {
      $isEmailPdf = FALSE;
    }

    list($mailSend, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate(
      array(
        'groupName' => 'msg_tpl_workflow_membership',
        'valueName' => 'membership_offline_receipt',
        'contactId' => $form->_receiptContactId,
        'from' => $receiptFrom,
        'toName' => $form->_contributorDisplayName,
        'toEmail' => $form->_contributorEmail,
        'PDFFilename' => ts('receipt') . '.pdf',
        'isEmailPdf' => $isEmailPdf,
        'contributionId' => $formValues['contribution_id'],
        'isTest' => (bool) ($form->_action & CRM_Core_Action::PREVIEW),
      )
    );

    return TRUE;
  }

  /**
   * Submit function.
   *
   * This is also accessed by unit tests.
   *
   * @return array
   */
  public function submit() {
    $isTest = ($this->_mode == 'test') ? 1 : 0;
    $this->storeContactFields($this->_params);
    $this->beginPostProcess();
    $formValues = $this->_params;
    $joinDate = $startDate = $endDate = NULL;
    $membershipTypes = $membership = $calcDate = array();
    $membershipType = NULL;
    $paymentInstrumentID = $this->_paymentProcessor['object']->getPaymentInstrumentID();

    $mailSend = FALSE;
    $formValues = $this->setPriceSetParameters($formValues);
    $params = $softParams = $ids = array();

    $allMemberStatus = CRM_Member_PseudoConstant::membershipStatus();
    $allContributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $this->processBillingAddress();

    if ($this->_id) {
      $ids['membership'] = $params['id'] = $this->_id;
    }
    $ids['userId'] = CRM_Core_Session::singleton()->get('userID');

    // Set variables that we normally get from context.
    // In form mode these are set in preProcess.
    //TODO: set memberships, fixme
    $this->setContextVariables($formValues);

    $this->_memTypeSelected = self::getSelectedMemberships(
      $this->_priceSet,
      $formValues
    );
    if (empty($formValues['financial_type_id'])) {
      $formValues['financial_type_id'] = $this->_priceSet['financial_type_id'];
    }

    $config = CRM_Core_Config::singleton();

    // @todo this is no longer required if we convert some date fields.
    $this->convertDateFieldsToMySQL($formValues);

    $membershipTypeValues = array();
    foreach ($this->_memTypeSelected as $memType) {
      $membershipTypeValues[$memType]['membership_type_id'] = $memType;
    }

    //take the required membership recur values.
    if ($this->_mode && !empty($formValues['auto_renew'])) {
      $params['is_recur'] = $formValues['is_recur'] = TRUE;
      $mapping = array(
        'frequency_interval' => 'duration_interval',
        'frequency_unit' => 'duration_unit',
      );

      $count = 0;
      foreach ($this->_memTypeSelected as $memType) {
        $recurMembershipTypeValues = CRM_Utils_Array::value($memType,
          $this->_recurMembershipTypes, array()
        );
        foreach ($mapping as $mapVal => $mapParam) {
          $membershipTypeValues[$memType][$mapVal] = CRM_Utils_Array::value($mapParam,
            $recurMembershipTypeValues
          );
          if (!$count) {
            $formValues[$mapVal] = CRM_Utils_Array::value($mapParam,
              $recurMembershipTypeValues
            );
          }
        }
        $count++;
      }
    }

    $isQuickConfig = $this->_priceSet['is_quick_config'];

    $termsByType = array();

    $lineItem = array($this->_priceSetId => array());

    CRM_Price_BAO_PriceSet::processAmount($this->_priceSet['fields'],
      $formValues, $lineItem[$this->_priceSetId], NULL, $this->_priceSetId);

    if (CRM_Utils_Array::value('tax_amount', $formValues)) {
      $params['tax_amount'] = $formValues['tax_amount'];
    }
    $params['total_amount'] = CRM_Utils_Array::value('amount', $formValues);
    $submittedFinancialType = CRM_Utils_Array::value('financial_type_id', $formValues);
    if (!empty($lineItem[$this->_priceSetId])) {
      foreach ($lineItem[$this->_priceSetId] as &$li) {
        if (!empty($li['membership_type_id'])) {
          if (!empty($li['membership_num_terms'])) {
            $termsByType[$li['membership_type_id']] = $li['membership_num_terms'];
          }
        }

        ///CRM-11529 for quick config backoffice transactions
        //when financial_type_id is passed in form, update the
        //lineitems with the financial type selected in form
        if ($isQuickConfig && $submittedFinancialType) {
          $li['financial_type_id'] = $submittedFinancialType;
        }
      }
    }

    $params['contact_id'] = $this->_contactID;

    $fields = array(
      'status_id',
      'source',
      'is_override',
      'status_override_end_date',
      'campaign_id',
    );

    foreach ($fields as $f) {
      $params[$f] = CRM_Utils_Array::value($f, $formValues);
    }

    // fix for CRM-3724
    // when is_override false ignore is_admin statuses during membership
    // status calculation. similarly we did fix for import in CRM-3570.
    if (empty($params['is_override'])) {
      $params['exclude_is_admin'] = TRUE;
    }

    // process date params to mysql date format.
    $dateTypes = array(
      'join_date' => 'joinDate',
      'start_date' => 'startDate',
      'end_date' => 'endDate',
    );
    foreach ($dateTypes as $dateField => $dateVariable) {
      $$dateVariable = CRM_Utils_Date::processDate($formValues[$dateField]);
    }

    $memTypeNumTerms = empty($termsByType) ? CRM_Utils_Array::value('num_terms', $formValues) : NULL;

    $calcDates = array();
    foreach ($this->_memTypeSelected as $memType) {
      if (empty($memTypeNumTerms)) {
        $memTypeNumTerms = CRM_Utils_Array::value($memType, $termsByType, 1);
      }
      $calcDates[$memType] = CRM_Member_BAO_MembershipType::getDatesForMembershipType($memType,
        $joinDate, $startDate, $endDate, $memTypeNumTerms
      );
    }

    foreach ($calcDates as $memType => $calcDate) {
      foreach (array_keys($dateTypes) as $d) {
        //first give priority to form values then calDates.
        $date = CRM_Utils_Array::value($d, $formValues);
        if (!$date) {
          $date = CRM_Utils_Array::value($d, $calcDate);
        }

        $membershipTypeValues[$memType][$d] = CRM_Utils_Date::processDate($date);
      }
    }

    // max related memberships - take from form or inherit from membership type
    foreach ($this->_memTypeSelected as $memType) {
      if (array_key_exists('max_related', $formValues)) {
        $membershipTypeValues[$memType]['max_related'] = CRM_Utils_Array::value('max_related', $formValues);
      }
      $membershipTypeValues[$memType]['custom'] = CRM_Core_BAO_CustomField::postProcess($formValues,
        $this->_id,
        'Membership'
      );
      $membershipTypes[$memType] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
        $memType
      );
    }

    $membershipType = implode(', ', $membershipTypes);

    // Retrieve the name and email of the current user - this will be the FROM for the receipt email
    list($userName) = CRM_Contact_BAO_Contact_Location::getEmailDetails($ids['userId']);

    //CRM-13981, allow different person as a soft-contributor of chosen type
    if ($this->_contributorContactID != $this->_contactID) {
      $params['contribution_contact_id'] = $this->_contributorContactID;
      if (!empty($formValues['soft_credit_type_id'])) {
        $softParams['soft_credit_type_id'] = $formValues['soft_credit_type_id'];
        $softParams['contact_id'] = $this->_contactID;
      }
    }
    if (!empty($formValues['record_contribution'])) {
      $recordContribution = array(
        'total_amount',
        'financial_type_id',
        'payment_instrument_id',
        'trxn_id',
        'contribution_status_id',
        'check_number',
        'campaign_id',
        'receive_date',
        'card_type_id',
        'pan_truncation',
      );

      foreach ($recordContribution as $f) {
        $params[$f] = CRM_Utils_Array::value($f, $formValues);
      }

      if (!$this->_onlinePendingContributionId) {
        if (empty($formValues['source'])) {
          $params['contribution_source'] = ts('%1 Membership: Offline signup (by %2)', array(
            1 => $membershipType,
            2 => $userName,
          ));
        }
        else {
          $params['contribution_source'] = $formValues['source'];
        }
      }

      if (empty($params['is_override']) &&
        CRM_Utils_Array::value('contribution_status_id', $params) != array_search('Completed', $allContributionStatus)
      ) {
        $params['status_id'] = array_search('Pending', $allMemberStatus);
        $params['skipStatusCal'] = TRUE;
        $params['is_pay_later'] = 1;
        $this->assign('is_pay_later', 1);
      }

      if (!empty($formValues['send_receipt'])) {
        $params['receipt_date'] = CRM_Utils_Array::value('receive_date', $formValues);
      }

      //insert financial type name in receipt.
      $formValues['contributionType_name'] = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType',
        $formValues['financial_type_id']
      );
    }

    // process line items, until no previous line items.
    if (!empty($lineItem)) {
      $params['lineItems'] = $lineItem;
      $params['processPriceSet'] = TRUE;
    }
    $createdMemberships = array();
    if ($this->_mode) {
      $params['total_amount'] = CRM_Utils_Array::value('total_amount', $formValues, 0);

      //CRM-20264 : Store CC type and number (last 4 digit) during backoffice or online payment
      $params['card_type_id'] = CRM_Utils_Array::value('card_type_id', $this->_params);
      $params['pan_truncation'] = CRM_Utils_Array::value('pan_truncation', $this->_params);

      if (!$isQuickConfig) {
        $params['financial_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet',
          $this->_priceSetId,
          'financial_type_id'
        );
      }
      else {
        $params['financial_type_id'] = CRM_Utils_Array::value('financial_type_id', $formValues);
      }

      //get the payment processor id as per mode. Try removing in favour of beginPostProcess.
      $params['payment_processor_id'] = $formValues['payment_processor_id'] = $this->_paymentProcessor['id'];
      $params['register_date'] = date('YmdHis');

      // add all the additional payment params we need
      // @todo the country & state values should be set by the call to $this->assignBillingAddress.
      $formValues["state_province-{$this->_bltID}"] = $formValues["billing_state_province-{$this->_bltID}"]
        = CRM_Core_PseudoConstant::stateProvinceAbbreviation($formValues["billing_state_province_id-{$this->_bltID}"]);
      $formValues["country-{$this->_bltID}"] = $formValues["billing_country-{$this->_bltID}"] = CRM_Core_PseudoConstant::countryIsoCode($formValues["billing_country_id-{$this->_bltID}"]);

      $formValues['amount'] = $params['total_amount'];
      // @todo this is a candidate for beginPostProcessFunction.
      $formValues['currencyID'] = $config->defaultCurrency;
      $formValues['description'] = ts("Contribution submitted by a staff person using member's credit card for signup");
      $formValues['invoiceID'] = md5(uniqid(rand(), TRUE));
      $formValues['financial_type_id'] = $params['financial_type_id'];

      // at this point we've created a contact and stored its address etc
      // all the payment processors expect the name and address to be in the
      // so we copy stuff over to first_name etc.
      $paymentParams = $formValues;
      $paymentParams['contactID'] = $this->_contributorContactID;
      //CRM-10377 if payment is by an alternate contact then we need to set that person
      // as the contact in the payment params
      if ($this->_contributorContactID != $this->_contactID) {
        if (!empty($formValues['soft_credit_type_id'])) {
          $softParams['contact_id'] = $params['contact_id'];
          $softParams['soft_credit_type_id'] = $formValues['soft_credit_type_id'];
        }
      }
      if (!empty($formValues['send_receipt'])) {
        $paymentParams['email'] = $this->_contributorEmail;
      }

      // This is a candidate for shared beginPostProcess function.
      CRM_Core_Payment_Form::mapParams($this->_bltID, $formValues, $paymentParams, TRUE);
      // CRM-7137 -for recurring membership,
      // we do need contribution and recurring records.
      $result = NULL;
      if (!empty($paymentParams['is_recur'])) {
        $financialType = new CRM_Financial_DAO_FinancialType();
        $financialType->id = $params['financial_type_id'];
        $financialType->find(TRUE);
        $this->_params = $formValues;

        $contribution = CRM_Contribute_Form_Contribution_Confirm::processFormContribution($this,
          $paymentParams,
          NULL,
          array(
            'contact_id' => $this->_contributorContactID,
            'line_item' => $lineItem,
            'is_test' => $isTest,
            'campaign_id' => CRM_Utils_Array::value('campaign_id', $paymentParams),
            'contribution_page_id' => CRM_Utils_Array::value('contribution_page_id', $formValues),
            'source' => CRM_Utils_Array::value('source', $paymentParams, CRM_Utils_Array::value('description', $paymentParams)),
            'thankyou_date' => CRM_Utils_Array::value('thankyou_date', $paymentParams),
            'payment_instrument_id' => $paymentInstrumentID,
          ),
          $financialType,
          FALSE,
          $this->_bltID,
          TRUE
        );

        //create new soft-credit record, CRM-13981
        if ($softParams) {
          $softParams['contribution_id'] = $contribution->id;
          $softParams['currency'] = $contribution->currency;
          $softParams['amount'] = $contribution->total_amount;
          CRM_Contribute_BAO_ContributionSoft::add($softParams);
        }

        $paymentParams['contactID'] = $this->_contactID;
        $paymentParams['contributionID'] = $contribution->id;
        $paymentParams['contributionTypeID'] = $contribution->financial_type_id;
        $paymentParams['contributionPageID'] = $contribution->contribution_page_id;
        $paymentParams['contributionRecurID'] = $contribution->contribution_recur_id;
        $ids['contribution'] = $contribution->id;
        $params['contribution_recur_id'] = $paymentParams['contributionRecurID'];
      }

      if ($params['total_amount'] > 0.0) {
        $payment = $this->_paymentProcessor['object'];
        try {
          $result = $payment->doPayment($paymentParams);
          $formValues = array_merge($formValues, $result);
          // Assign amount to template if payment was successful.
          $this->assign('amount', $params['total_amount']);
        }
        catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
          if (!empty($paymentParams['contributionID'])) {
            CRM_Contribute_BAO_Contribution::failPayment($paymentParams['contributionID'], $this->_contactID,
              $e->getMessage());
          }
          if (!empty($paymentParams['contributionRecurID'])) {
            CRM_Contribute_BAO_ContributionRecur::deleteRecurContribution($paymentParams['contributionRecurID']);
          }

          CRM_Core_Session::singleton()->setStatus($e->getMessage());
          CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/membership',
            "reset=1&action=add&cid={$this->_contactID}&context=membership&mode={$this->_mode}"
          ));

        }
      }

      if ($formValues['payment_status_id'] != array_search('Completed', $allContributionStatus)) {
        $params['status_id'] = array_search('Pending', $allMemberStatus);
        $params['skipStatusCal'] = TRUE;
        // unset send-receipt option, since receipt will be sent when ipn is received.
        unset($formValues['send_receipt'], $formValues['send_receipt']);
        //as membership is pending set dates to null.
        $memberDates = array(
          'join_date' => 'joinDate',
          'start_date' => 'startDate',
          'end_date' => 'endDate',
        );
        foreach ($memberDates as $dv) {
          $$dv = NULL;
          foreach ($this->_memTypeSelected as $memType) {
            $membershipTypeValues[$memType][$dv] = NULL;
          }
        }
      }
      $now = date('YmdHis');
      $params['receive_date'] = $now;
      $params['invoice_id'] = $formValues['invoiceID'];
      $params['contribution_source'] = ts('%1 Membership Signup: Credit card or direct debit (by %2)',
        array(1 => $membershipType, 2 => $userName)
      );
      $params['source'] = $formValues['source'] ? $formValues['source'] : $params['contribution_source'];
      $params['trxn_id'] = CRM_Utils_Array::value('trxn_id', $result);
      $params['is_test'] = ($this->_mode == 'live') ? 0 : 1;
      if (!empty($formValues['send_receipt'])) {
        $params['receipt_date'] = $now;
      }
      else {
        $params['receipt_date'] = NULL;
      }

      $this->set('params', $formValues);
      $this->assign('trxn_id', CRM_Utils_Array::value('trxn_id', $result));
      $this->assign('receive_date',
        CRM_Utils_Date::mysqlToIso($params['receive_date'])
      );

      // required for creating membership for related contacts
      $params['action'] = $this->_action;

      //create membership record.
      $count = 0;
      foreach ($this->_memTypeSelected as $memType) {
        if ($count &&
          ($relateContribution = CRM_Member_BAO_Membership::getMembershipContributionId($membership->id))
        ) {
          $membershipTypeValues[$memType]['relate_contribution_id'] = $relateContribution;
        }

        $membershipParams = array_merge($membershipTypeValues[$memType], $params);
        //CRM-15366
        if (!empty($softParams) && empty($paymentParams['is_recur'])) {
          $membershipParams['soft_credit'] = $softParams;
        }
        if (isset($result['fee_amount'])) {
          $membershipParams['fee_amount'] = $result['fee_amount'];
        }
        // This is required to trigger the recording of the membership contribution in the
        // CRM_Member_BAO_Membership::Create function.
        // @todo stop setting this & 'teach' the create function to respond to something
        // appropriate as part of our 2-step always create the pending contribution & then finally add the payment
        // process -
        // @see http://wiki.civicrm.org/confluence/pages/viewpage.action?pageId=261062657#Payments&AccountsRoadmap-Movetowardsalwaysusinga2-steppaymentprocess
        $membershipParams['contribution_status_id'] = CRM_Utils_Array::value('payment_status_id', $result);
        if (!empty($paymentParams['is_recur'])) {
          // The earlier process created the line items (although we want to get rid of the earlier one in favour
          // of a single path!
          unset($membershipParams['lineItems']);
        }
        $membershipParams['payment_instrument_id'] = $paymentInstrumentID;
        $membership = CRM_Member_BAO_Membership::create($membershipParams, $ids);
        $params['contribution'] = CRM_Utils_Array::value('contribution', $membershipParams);
        unset($params['lineItems']);
        $this->_membershipIDs[] = $membership->id;
        $createdMemberships[$memType] = $membership;
        $count++;
      }

    }
    else {
      $params['action'] = $this->_action;
      if ($this->_onlinePendingContributionId && !empty($formValues['record_contribution'])) {

        // update membership as well as contribution object, CRM-4395
        $params['contribution_id'] = $this->_onlinePendingContributionId;
        $params['componentId'] = $params['id'];
        $params['componentName'] = 'contribute';
        $result = CRM_Contribute_BAO_Contribution::transitionComponents($params, TRUE);
        if (!empty($result) && !empty($params['contribution_id'])) {
          $lineItem = array();
          $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($params['contribution_id']);
          $itemId = key($lineItems);
          $priceSetId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $lineItems[$itemId]['price_field_id'], 'price_set_id');

          $lineItems[$itemId]['unit_price'] = $params['total_amount'];
          $lineItems[$itemId]['line_total'] = $params['total_amount'];
          $lineItems[$itemId]['id'] = $itemId;
          $lineItem[$priceSetId] = $lineItems;
          $contributionBAO = new CRM_Contribute_BAO_Contribution();
          $contributionBAO->id = $params['contribution_id'];
          $contributionBAO->contact_id = $params['contact_id'];
          $contributionBAO->find();
          CRM_Price_BAO_LineItem::processPriceSet($params['contribution_id'], $lineItem, $contributionBAO, 'civicrm_membership');

          //create new soft-credit record, CRM-13981
          if ($softParams) {
            $softParams['contribution_id'] = $params['contribution_id'];
            while ($contributionBAO->fetch()) {
              $softParams['currency'] = $contributionBAO->currency;
              $softParams['amount'] = $contributionBAO->total_amount;
            }
            CRM_Contribute_BAO_ContributionSoft::add($softParams);
          }
        }

        //carry updated membership object.
        $membership = new CRM_Member_DAO_Membership();
        $membership->id = $this->_id;
        $membership->find(TRUE);

        $cancelled = TRUE;
        if ($membership->end_date) {
          //display end date w/ status message.
          $endDate = $membership->end_date;

          if (!in_array($membership->status_id, array(
            // CRM-15475
            array_search('Cancelled', CRM_Member_PseudoConstant::membershipStatus(NULL, " name = 'Cancelled' ", 'name', FALSE, TRUE)),
            array_search('Expired', CRM_Member_PseudoConstant::membershipStatus()),
          ))
          ) {
            $cancelled = FALSE;
          }
        }
        // suppress form values in template.
        $this->assign('cancelled', $cancelled);

        $createdMemberships[] = $membership;
      }
      else {
        $count = 0;
        foreach ($this->_memTypeSelected as $memType) {
          if ($count && !empty($formValues['record_contribution']) &&
            ($relateContribution = CRM_Member_BAO_Membership::getMembershipContributionId($membership->id))
          ) {
            $membershipTypeValues[$memType]['relate_contribution_id'] = $relateContribution;
          }

          $membershipParams = array_merge($params, $membershipTypeValues[$memType]);
          if (!empty($formValues['int_amount'])) {
            $init_amount = array();
            foreach ($formValues as $key => $value) {
              if (strstr($key, 'txt-price')) {
                $init_amount[$key] = $value;
              }
            }
            $membershipParams['init_amount'] = $init_amount;
          }

          if (!empty($softParams)) {
            $membershipParams['soft_credit'] = $softParams;
          }

          $membership = CRM_Member_BAO_Membership::create($membershipParams, $ids);
          $params['contribution'] = CRM_Utils_Array::value('contribution', $membershipParams);
          unset($params['lineItems']);
          // skip line item creation for next interation since line item(s) are already created.
          $params['skipLineItem'] = TRUE;

          $this->_membershipIDs[] = $membership->id;
          $createdMemberships[$memType] = $membership;
          $count++;
        }
      }
    }

    if (!empty($lineItem[$this->_priceSetId])) {
      $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
      $invoicing = CRM_Utils_Array::value('invoicing', $invoiceSettings);
      $taxAmount = FALSE;
      $totalTaxAmount = 0;
      foreach ($lineItem[$this->_priceSetId] as & $priceFieldOp) {
        if (!empty($priceFieldOp['membership_type_id'])) {
          $priceFieldOp['start_date'] = $membershipTypeValues[$priceFieldOp['membership_type_id']]['start_date'] ? CRM_Utils_Date::customFormat($membershipTypeValues[$priceFieldOp['membership_type_id']]['start_date'], '%B %E%f, %Y') : '-';
          $priceFieldOp['end_date'] = $membershipTypeValues[$priceFieldOp['membership_type_id']]['end_date'] ? CRM_Utils_Date::customFormat($membershipTypeValues[$priceFieldOp['membership_type_id']]['end_date'], '%B %E%f, %Y') : '-';
        }
        else {
          $priceFieldOp['start_date'] = $priceFieldOp['end_date'] = 'N/A';
        }
        if ($invoicing && isset($priceFieldOp['tax_amount'])) {
          $taxAmount = TRUE;
          $totalTaxAmount += $priceFieldOp['tax_amount'];
        }
      }
      if ($invoicing) {
        $dataArray = array();
        foreach ($lineItem[$this->_priceSetId] as $key => $value) {
          if (isset($value['tax_amount']) && isset($value['tax_rate'])) {
            if (isset($dataArray[$value['tax_rate']])) {
              $dataArray[$value['tax_rate']] = $dataArray[$value['tax_rate']] + CRM_Utils_Array::value('tax_amount', $value);
            }
            else {
              $dataArray[$value['tax_rate']] = CRM_Utils_Array::value('tax_amount', $value);
            }
          }
        }
        if ($taxAmount) {
          $this->assign('totalTaxAmount', $totalTaxAmount);
          $this->assign('taxTerm', CRM_Utils_Array::value('tax_term', $invoiceSettings));
        }
        $this->assign('dataArray', $dataArray);
      }
    }
    $this->assign('lineItem', !empty($lineItem) && !$isQuickConfig ? $lineItem : FALSE);

    $receiptSend = FALSE;
    $contributionId = CRM_Member_BAO_Membership::getMembershipContributionId($membership->id);
    $membershipIds = $this->_membershipIDs;
    if ($contributionId && !empty($membershipIds)) {
      $contributionDetails = CRM_Contribute_BAO_Contribution::getContributionDetails(
        CRM_Export_Form_Select::MEMBER_EXPORT, $this->_membershipIDs);
      if ($contributionDetails[$membership->id]['contribution_status'] == 'Completed') {
        $receiptSend = TRUE;
      }
    }

    $receiptSent = FALSE;
    if (!empty($formValues['send_receipt']) && $receiptSend) {
      $formValues['contact_id'] = $this->_contactID;
      $formValues['contribution_id'] = $contributionId;
      // We really don't need a distinct receipt_text_signup vs receipt_text_renewal as they are
      // handled in the receipt. But by setting one we avoid breaking templates for now
      // although at some point we should switch in the templates.
      $formValues['receipt_text_signup'] = $formValues['receipt_text'];
      // send email receipt
      $this->assignBillingName();
      $mailSend = self::emailReceipt($this, $formValues, $membership);
      $receiptSent = TRUE;
    }

    // finally set membership id if already not set
    if (!$this->_id) {
      $this->_id = $membership->id;
    }

    $isRecur = CRM_Utils_Array::value('is_recur', $params);
    $this->updateContributionOnMembershipTypeChange($params, $membership);
    $this->setStatusMessage($membership, $endDate, $receiptSent, $membershipTypes, $createdMemberships, $isRecur, $calcDates, $mailSend);
    return $createdMemberships;
  }

  /**
   * Update related contribution of a membership if update_contribution_on_membership_type_change
   *   contribution setting is enabled and type is changed on edit
   *
   * @param array $inputParams
   *      submitted form values
   * @param CRM_Member_DAO_Membership $membership
   *     Updated membership object
   *
   */
  protected function updateContributionOnMembershipTypeChange($inputParams, $membership) {
    if (Civi::settings()->get('update_contribution_on_membership_type_change') &&
      ($this->_action & CRM_Core_Action::UPDATE) && // on update
      $this->_id && // if ID is present
      !in_array($this->_memType, $this->_memTypeSelected) // if selected membership doesn't match with earlier membership
    ) {
      if (CRM_Utils_Array::value('is_recur', $inputParams)) {
        CRM_Core_Session::setStatus(ts('Associated recurring contribution cannot be updated on membership type change.', ts('Error'), 'error'));
        return;
      }

      // fetch lineitems by updated membership ID
      $lineItems = CRM_Price_BAO_LineItem::getLineItems($membership->id, 'membership');
      // retrieve the related contribution ID
      $contributionID = CRM_Core_DAO::getFieldValue(
        'CRM_Member_DAO_MembershipPayment',
        $membership->id,
        'contribution_id',
        'membership_id'
      );
      // get price fields of chosen price-set
      $priceSetDetails = CRM_Utils_Array::value(
        $this->_priceSetId,
        CRM_Price_BAO_PriceSet::getSetDetail(
          $this->_priceSetId,
          TRUE,
          TRUE
        )
      );

      // add price field information in $inputParams
      self::addPriceFieldByMembershipType($inputParams, $priceSetDetails['fields'], $membership->membership_type_id);

      // update related contribution and financial records
      CRM_Price_BAO_LineItem::changeFeeSelections(
        $inputParams,
        $membership->id,
        'membership',
        $contributionID,
        $priceSetDetails['fields'],
        $lineItems
      );
      CRM_Core_Session::setStatus(ts('Associated contribution is updated on membership type change.'), ts('Success'), 'success');
    }
  }

  /**
   * Add selected price field information in $formValues
   *
   * @param array $formValues
   *      submitted form values
   * @param array $priceFields
   *     Price fields of selected Priceset ID
   * @param int $membershipTypeID
   *     Selected membership type ID
   *
   */
  public static function addPriceFieldByMembershipType(&$formValues, $priceFields, $membershipTypeID) {
    foreach ($priceFields as $priceFieldID => $priceField) {
      if (isset($priceField['options']) && count($priceField['options'])) {
        foreach ($priceField['options'] as $option) {
          if ($option['membership_type_id'] == $membershipTypeID) {
            $formValues["price_{$priceFieldID}"] = $option['id'];
            break;
          }
        }
      }
    }
  }
  /**
   * Set context in session.
   */
  protected function setUserContext() {
    $buttonName = $this->controller->getButtonName();
    $session = CRM_Core_Session::singleton();

    if ($this->_context == 'standalone') {
      if ($buttonName == $this->getButtonName('upload', 'new')) {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/member/add',
          'reset=1&action=add&context=standalone'
        ));
      }
      else {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
          "reset=1&cid={$this->_contactID}&selectedChild=member"
        ));
      }
    }
    elseif ($buttonName == $this->getButtonName('upload', 'new')) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/membership',
        "reset=1&action=add&context=membership&cid={$this->_contactID}"
      ));
    }
  }

  /**
   * Get status message for updating membership.
   *
   * @param CRM_Member_BAO_Membership $membership
   * @param string $endDate
   * @param bool $receiptSend
   *
   * @return string
   */
  protected function getStatusMessageForUpdate($membership, $endDate, $receiptSend) {
    // End date can be modified by hooks, so if end date is set then use it.
    $endDate = ($membership->end_date) ? $membership->end_date : $endDate;

    $statusMsg = ts('Membership for %1 has been updated.', array(1 => $this->_memberDisplayName));
    if ($endDate && $endDate !== 'null') {
      $endDate = CRM_Utils_Date::customFormat($endDate);
      $statusMsg .= ' ' . ts('The membership End Date is %1.', array(1 => $endDate));
    }

    if ($receiptSend) {
      $statusMsg .= ' ' . ts('A confirmation and receipt has been sent to %1.', array(1 => $this->_contributorEmail));
    }
    return $statusMsg;
  }

  /**
   * Get status message for create action.
   *
   * @param string $endDate
   * @param bool $receiptSend
   * @param array $membershipTypes
   * @param array $createdMemberships
   * @param bool $isRecur
   * @param array $calcDates
   * @param bool $mailSent
   *
   * @return array|string
   */
  protected function getStatusMessageForCreate($endDate, $receiptSend, $membershipTypes, $createdMemberships,
                                               $isRecur, $calcDates, $mailSent) {
    // FIX ME: fix status messages

    $statusMsg = array();
    foreach ($membershipTypes as $memType => $membershipType) {
      $statusMsg[$memType] = ts('%1 membership for %2 has been added.', array(
        1 => $membershipType,
        2 => $this->_memberDisplayName,
      ));

      $membership = $createdMemberships[$memType];
      $memEndDate = ($membership->end_date) ? $membership->end_date : $endDate;

      //get the end date from calculated dates.
      if (!$memEndDate && !$isRecur) {
        $memEndDate = CRM_Utils_Array::value('end_date', $calcDates[$memType]);
      }

      if ($memEndDate && $memEndDate !== 'null') {
        $memEndDate = CRM_Utils_Date::customFormat($memEndDate);
        $statusMsg[$memType] .= ' ' . ts('The new membership End Date is %1.', array(1 => $memEndDate));
      }
    }
    $statusMsg = implode('<br/>', $statusMsg);
    if ($receiptSend && !empty($mailSent)) {
      $statusMsg .= ' ' . ts('A membership confirmation and receipt has been sent to %1.', array(1 => $this->_contributorEmail));
    }
    return $statusMsg;
  }

  /**
   * @param $membership
   * @param $endDate
   * @param $receiptSend
   * @param $membershipTypes
   * @param $createdMemberships
   * @param $isRecur
   * @param $calcDates
   * @param $mailSend
   */
  protected function setStatusMessage($membership, $endDate, $receiptSend, $membershipTypes, $createdMemberships, $isRecur, $calcDates, $mailSend) {
    $statusMsg = '';
    if (($this->_action & CRM_Core_Action::UPDATE)) {
      $statusMsg = $this->getStatusMessageForUpdate($membership, $endDate, $receiptSend);
    }
    elseif (($this->_action & CRM_Core_Action::ADD)) {
      $statusMsg = $this->getStatusMessageForCreate($endDate, $receiptSend, $membershipTypes, $createdMemberships,
        $isRecur, $calcDates, $mailSend);
    }

    CRM_Core_Session::setStatus($statusMsg, ts('Complete'), 'success');
    //CRM-15187
    // display message when membership type is changed
    if (($this->_action & CRM_Core_Action::UPDATE) && $this->_id && !in_array($this->_memType, $this->_memTypeSelected)) {
      $lineItem = CRM_Price_BAO_LineItem::getLineItems($this->_id, 'membership');
      $maxID = max(array_keys($lineItem));
      $lineItem = $lineItem[$maxID];
      $membershipTypeDetails = $this->allMembershipTypeDetails[$membership->membership_type_id];
      if ($membershipTypeDetails['financial_type_id'] != $lineItem['financial_type_id']) {
        CRM_Core_Session::setStatus(
          ts('The financial types associated with the old and new membership types are different. You may want to edit the contribution associated with this membership to adjust its financial type.'),
          ts('Warning')
        );
      }
      if ($membershipTypeDetails['minimum_fee'] != $lineItem['line_total']) {
        CRM_Core_Session::setStatus(
          ts('The cost of the old and new membership types are different. You may want to edit the contribution associated with this membership to adjust its amount.'),
          ts('Warning')
        );
      }
    }
  }

}
