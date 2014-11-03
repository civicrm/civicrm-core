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
 * This class generates form components for offline membership form
 *
 */
class CRM_Member_Form_Membership extends CRM_Member_Form {

  protected $_memType = NULL;

  protected $_onlinePendingContributionId;

  public $_mode;

  public $_contributeMode = 'direct';

  protected $_recurMembershipTypes;

  protected $_memTypeSelected;

  /*
   * Display name of the member
   */
  protected $_memberDisplayName = NULL;

  /*
  * email of the person paying for the membership (used for receipts)
  */
  protected $_memberEmail = NULL;

  /*
  * Contact ID of the member
  */
  protected $_contactID = NULL;

  /*
  * Display name of the person paying for the membership (used for receipts)
  */
  protected $_contributorDisplayName = NULL;

  /*
   * email of the person paying for the membership (used for receipts)
   */
  protected $_contributorEmail = NULL;

  /*
  * email of the person paying for the membership (used for receipts)
  */
  protected $_contributorContactID = NULL;

  /*
   * ID of the person the receipt is to go to
   */
  protected $_receiptContactId = NULL;

  /*
   * Keep a class variable for ALL membeshipID's so
   * postProcess hook function can do something with it
   */
  protected $_membershipIDs = array();

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

    // get price set id.
    $this->_priceSetId = CRM_Utils_Array::value('priceSetId', $_GET);
    $this->set('priceSetId', $this->_priceSetId);
    $this->assign('priceSetId', $this->_priceSetId);

    // action
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    $this->_processors = array();
    $this->assign('contactID', $this->_contactID);

    // check for edit permission
    if (!CRM_Core_Permission::checkActionPermission('CiviMember', $this->_action)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      $contributionID = CRM_Member_BAO_Membership::getMembershipContributionId($this->_id);
      // check delete permission for contribution
      if ($this->_id && $contributionID && !CRM_Core_Permission::checkActionPermission('CiviContribute', $this->_action)) {
        CRM_Core_Error::fatal(ts("This Membership is linked to a contribution. You must have 'delete in CiviContribute' permission in order to delete this record."));
      }
    }

    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $this->assign('context', $this->_context);

    if ($this->_id) {
      $this->_memType = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $this->_id, 'membership_type_id');
      $this->_membershipIDs[] = $this->_id;
    }

    $this->_mode = CRM_Utils_Request::retrieve('mode', 'String', $this);
    $this->assign('membershipMode', $this->_mode);

    if ($this->_mode) {
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

    if ($this->_action & CRM_Core_Action::ADD) {
      if (!CRM_Member_BAO_Membership::statusAvailabilty($this->_contactID)) {
        // all possible statuses are disabled - redirect back to contact form
        CRM_Core_Error::statusBounce(ts('There are no configured membership statuses. You cannot add this membership until your membership statuses are correctly configured'));
      }

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
          foreach ($contactMemberships as $memid => $mem) {
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

    // when custom data is included in this page
    if (!empty($_POST['hidden_custom'])) {
      CRM_Custom_Form_CustomData::preProcess($this);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

    // CRM-4395, get the online pending contribution id.
    $this->_onlinePendingContributionId = NULL;
    if (!$this->_mode && $this->_id && ($this->_action & CRM_Core_Action::UPDATE)) {
      $this->_onlinePendingContributionId = CRM_Contribute_BAO_Contribution::checkOnlinePendingContribution($this->_id,
        'Membership'
      );
    }
    $this->assign('onlinePendingContributionId', $this->_onlinePendingContributionId);
    $this->_fromEmails = CRM_Core_BAO_Email::getFromEmail();

    $this->setPageTitle(ts('Membership'));


    parent::preProcess();
  }

  /**
   * This function sets the default values for the form. MobileProvider that in edit/view mode
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
      // set default country from config if no country set
      $config = CRM_Core_Config::singleton();
      if (empty($defaults["billing_country_id-{$this->_bltID}"])) {
        $defaults["billing_country_id-{$this->_bltID}"] = $config->defaultContactCountry;
      }

      if (empty($defaults["billing_state_province_id-{$this->_bltID}"])) {
        $defaults["billing_state_province_id-{$this->_bltID}"] = $config->defaultContactStateProvince;
      }

      $billingDefaults = $this->getProfileDefaults('Billing', $this->_contactID);
      $defaults = array_merge($defaults, $billingDefaults);

      //             // hack to simplify credit card entry for testing
      //             $defaults['credit_card_type']     = 'Visa';
      //             $defaults['credit_card_number']   = '4807731747657838';
      //             $defaults['cvv2']                 = '000';
      //             $defaults['credit_card_exp_date'] = array( 'Y' => '2012', 'M' => '05' );
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
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::buildQuickForm($this);
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
            '' => ts('Choose price set')
          ) + $priceSets,
          NULL, array('onchange' => "buildAmount( this.value );")
        );
      }
      $this->assign('hasPriceSets', $buildPriceSet);
    }

    //need to assign custom data type and subtype to the template
    $this->assign('customDataType', 'Membership');
    $this->assign('customDataSubType', $this->_memType);
    $this->assign('entityID', $this->_id);

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

    if ($this->_context == 'standalone') {
      $this->addEntityRef('contact_id', ts('Contact'), array('create' => TRUE, 'api' => array('extra' => array('email'))), TRUE);
    }

    $selOrgMemType[0][0] = $selMemTypeOrg[0] = ts('- select -');

    $dao = new CRM_Member_DAO_MembershipType();
    $dao->domain_id = CRM_Core_Config::domainID();
    $dao->find();

    // retrieve all memberships
    $allMemberships = CRM_Member_BAO_Membership::buildMembershipTypeValues($this);

    $allMembershipInfo = $membershipType = array();
    foreach ($allMemberships as $key => $values) {
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

        // build membership info array, which is used when membership type is selected to:
        // - set the payment information block
        // - set the max related block
        $allMembershipInfo[$key] = array(
          'financial_type_id' => CRM_Utils_Array::value('financial_type_id', $values),
          'total_amount' => CRM_Utils_Money::format($values['minimum_fee'], NULL, '%a'),
          'total_amount_numeric' => CRM_Utils_Array::value('minimum_fee', $values),
          'auto_renew' => CRM_Utils_Array::value('auto_renew', $values),
          'has_related' => isset($values['relationship_type_id']),
          'max_related' => CRM_Utils_Array::value('max_related', $values),
        );
      }
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

    $memTypeJs = array('onChange' => "CRM.buildCustomData( 'Membership', this.value );");

    //build the form for auto renew.
    $recurProcessor = $autoRenew = array();
    if ($this->_mode || ($this->_action & CRM_Core_Action::UPDATE)) {
      $autoRenewElement = $this->addElement('checkbox',
        'auto_renew',
        ts('Membership renewed automatically'),
        NULL,
        array('onclick' => "buildReceiptANDNotice( );")
      );

      if ($this->_mode) {
        //get the valid recurring processors.
        $recurring = CRM_Core_PseudoConstant::paymentProcessor(FALSE, FALSE, 'is_recur = 1');
        $recurProcessor = array_intersect_assoc($this->_processors, $recurring);
        $autoRenew = array();
        if (!empty($recurProcessor)) {
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
                         'id',
                         'auto_renew',
                         'duration_unit',
                         'duration_interval'
                       ) as $fld) {
                $this->_recurMembershipTypes[$recurMembershipTypes->id][$fld] = $recurMembershipTypes->$fld;
              }
            }
          }
          $memTypeJs = array(
            'onChange' =>
            "CRM.buildCustomData( 'Membership', this.value ); buildAutoRenew(this.value, null );",
          );
        }
      }
    }
    $allowAutoRenew = FALSE;
    if ($this->_mode && !empty($recurProcessor)) {
      $allowAutoRenew = TRUE;
    }
    $this->assign('allowAutoRenew', $allowAutoRenew);
    $this->assign('autoRenewOptions', json_encode($autoRenew));
    $this->assign('recurProcessor', json_encode($recurProcessor));

    // for max_related: a little JS to show/hide & set default value
    $memTypeJs['onChange'] = "buildMaxRelated(this.value,true); " . $memTypeJs['onChange'];
    $this->add('text', 'max_related', ts('Max related'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_Membership', 'max_related')
    );

    $sel = & $this->addElement('hierselect',
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
      $statusOverride = $this->addElement('checkbox', 'is_override',
        ts('Status Override?'), NULL,
        array('onClick' => 'showHideMemberStatus()')
      );
      if ($statusOverride) {
        $elements[] = $statusOverride;
      }

      $this->addElement('checkbox', 'record_contribution', ts('Record Membership Payment?'));

      $this->add('text', 'total_amount', ts('Amount'));
      $this->addRule('total_amount', ts('Please enter a valid amount.'), 'money');

      $this->addDate('receive_date', ts('Received'), FALSE, array('formatType' => 'activityDateTime'));

      $this->add('select', 'payment_instrument_id',
        ts('Paid By'),
        array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::paymentInstrument(),
        FALSE, array('onChange' => "return showHideByValue('payment_instrument_id','4','checkNumber','table-row','select',false);")
      );
      $this->add('text', 'trxn_id', ts('Transaction ID'));
      $this->addRule('trxn_id', ts('Transaction ID already exists in Database.'),
        'objectExists', array('CRM_Contribute_DAO_Contribution', $this->_id, 'trxn_id')
      );

      $allowStatuses = array();
      $statuses = CRM_Contribute_PseudoConstant::contributionStatus();
      if ($this->_onlinePendingContributionId) {
        $statusNames = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
        foreach ($statusNames as $val => $name) {
          if (in_array($name, array(
            'In Progress',
            'Overdue'
          ))
          ) {
            continue;
          }
          $allowStatuses[$val] = $statuses[$val];
        }
      }
      else {
        $allowStatuses = $statuses;
      }
      $this->add('select', 'contribution_status_id',
        ts('Payment Status'), $allowStatuses
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
      array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::financialType()
    );

    //CRM-10223 - allow contribution to be recorded against different contact
    // causes a conflict in standalone mode so skip in standalone for now
    $this->addElement('checkbox', 'is_different_contribution_contact', ts('Record Payment from a Different Contact?'));
    $this->addSelect('soft_credit_type_id', array('entity' => 'contribution_soft'));
    $this->addEntityRef('soft_credit_contact_id', ts('Payment From'), array('create' => TRUE));


    $this->addElement('checkbox',
      'send_receipt',
      ts('Send Confirmation and Receipt?'), NULL,
      array('onclick' => "showHideByValue( 'send_receipt', '', 'notice', 'table-row', 'radio', false); showHideByValue( 'send_receipt', '', 'fromEmail', 'table-row', 'radio', false);")
    );

    $this->add('select', 'from_email_address', ts('Receipt From'), $this->_fromEmails);

    $this->add('textarea', 'receipt_text_signup', ts('Receipt Message'));
    if ($this->_mode) {

      $this->add('select', 'payment_processor_id',
        ts('Payment Processor'),
        $this->_processors, TRUE,
        array('onChange' => "buildAutoRenew( null, this.value );")
      );
      CRM_Core_Payment_Form::buildCreditCard($this, TRUE);
    }

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

    $mailingInfo = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
      'mailing_backend'
    );
    $this->assign('outBound_option', $mailingInfo['outBound_option']);

    parent::buildQuickForm();
  }

  /**
   * Function for validation
   *
   * @param array $params (ref.) an assoc array of name/value pairs
   *
   * @param $files
   * @param $self
   *
   * @throws CiviCRM_API3_Exception
   * @return mixed true or array of errors
   * @access public
   * @static
   */
  static function formRule($params, $files, $self) {
    $errors = array();

    $priceSetId = CRM_Utils_Array::value('price_set_id', $params);

    if ($priceSetId) {
      CRM_Price_BAO_PriceField::priceSetValidation($priceSetId, $params, $errors);

      $priceFieldIDS = array();
      foreach ($self->_priceSet['fields'] as $priceIds => $dontCare) {

        if (!empty($params['price_' . $priceIds])) {
          if (is_array($params['price_' . $priceIds])) {
            foreach ($params['price_' . $priceIds] as $priceFldVal => $isSet) {
              if ($isSet) {
                $priceFieldIDS[] = $priceFldVal;
              }
            }
          }
          else {
            $priceFieldIDS[] = $params['price_' . $priceIds];
          }
        }
      }

      if (!empty($priceFieldIDS)) {
        $ids = implode(',', $priceFieldIDS);

        $count = CRM_Price_BAO_PriceSet::getMembershipCount($ids);
        foreach ($count as $id => $occurance) {
          if ($occurance > 1) {
            $errors['_qf_default'] = ts('Select at most one option associated with the same membership type.');
          }
        }

        foreach ($priceFieldIDS as $priceFieldId) {
          if ($id = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $priceFieldId, 'membership_type_id')) {
            $self->_memTypeSelected[$id] = $id;
          }
        }
      }
    }
    elseif (empty($params['membership_type_id'][1])) {
      $errors['membership_type_id'] = ts('Please select a membership type.');
    }
    else {
      $self->_memTypeSelected[] = $params['membership_type_id'][1];
    }

    if (!$priceSetId) {
      $numterms = CRM_Utils_Array::value('num_terms', $params);
      if ($numterms && intval($numterms) != $numterms) {
        $errors['num_terms'] = ts('Please enter an integer for the number of terms.');
      }
    }

    // Return error if empty $self->_memTypeSelected
    if ($priceSetId && empty($errors) && empty($self->_memTypeSelected)) {
      $errors['_qf_default'] = ts('Select at least one membership option.');
    }

    if (!empty($errors) && (count($self->_memTypeSelected) > 1)) {
      $memberOfContacts = CRM_Member_BAO_MembershipType::getMemberOfContactByMemTypes($self->_memTypeSelected);
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

    if ($priceSetId && !$self->_mode && empty($params['record_contribution'])) {
      $errors['record_contribution'] = ts('Record Membership Payment is required when you using price set.');
    }

    if (!$priceSetId && $self->_mode && empty($params['financial_type_id'])) {
      $errors['financial_type_id'] = ts('Please enter the financial Type.');
    }

    if (!empty($params['record_contribution']) && empty($params['payment_instrument_id'])) {
      $errors['payment_instrument_id'] = ts('Paid By is a required field.');
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
      // make sure that credit card number and cvv are valid
      CRM_Core_Payment_Form::validateCreditCard($params, $errors);
    }

    $joinDate = NULL;
    if (!empty($params['join_date'])) {

      $joinDate = CRM_Utils_Date::processDate($params['join_date']);

      foreach ($self->_memTypeSelected as $memType) {
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
      	    foreach($tmp_statuses as $cur_stat) {
              $status_ids[] = $cur_stat['id'];
            }
            if (empty($params['status_id']) || in_array( $params['status_id'] , $status_ids) == false) {
              $errors['status_id'] = ts('Please enter a status that does NOT represent a current membership status.');
              $errors['is_override']  = ts('This must be checked because you set an End Date for a lifetime membership');
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

        //  Default values for start and end dates if not supplied
        //  on the form
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
        if (empty($params['is_override']) && !isset($errors['_qf_default'])) {
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
              $status .= ' ' . ts('OR You can sign up by setting Status Override? to true.');
            }
            CRM_Core_Session::setStatus($status, ts('Membership Status Error'), 'error');
          }
        }
      }
    }
    else {
      $errors['join_date'] = ts('Please enter the Member Since.');
    }

    if (isset($params['is_override']) &&
      $params['is_override'] && empty($params['status_id'])) {
      $errors['status_id'] = ts('Please enter the status.');
    }

    //total amount condition arise when membership type having no
    //minimum fee
    if (isset($params['record_contribution'])) {
      if (!$params['financial_type_id']) {
        $errors['financial_type_id'] = ts('Please enter the financial Type.');
      }
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
   * Function to process the form
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Member_BAO_Membership::del($this->_id);
      return;
    }

    $isTest = ($this->_mode == 'test') ? 1 : 0;

    $lineItems = NULL;
    if (!empty($this->_lineItem)) {
      $lineItems = $this->_lineItem;
    }

    $config = CRM_Core_Config::singleton();
    // get the submitted form values.
    $this->_params = $formValues = $this->controller->exportValues($this->_name);
    $this->convertDateFieldsToMySQL($formValues);

    $params = $softParams = $ids = array();

    $membershipTypeValues = array();
    foreach ($this->_memTypeSelected as $memType) {
      $membershipTypeValues[$memType]['membership_type_id'] = $memType;
    }

    //take the required membership recur values.
    if ($this->_mode && !empty($this->_params['auto_renew'])) {
      $params['is_recur'] = $this->_params['is_recur'] = $formValues['is_recur'] = TRUE;
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
            $this->_params[$mapVal] = $formValues[$mapVal] = CRM_Utils_Array::value($mapParam,
              $recurMembershipTypeValues
            );
          }
        }
        $count++;
      }

      // unset send-receipt option, since receipt will be sent when ipn is received.
      unset($this->_params['send_receipt'], $formValues['send_receipt']);
    }

    // process price set and get total amount and line items.
    $lineItem = array();
    $priceSetId = NULL;
    if (!$priceSetId = CRM_Utils_Array::value('price_set_id', $formValues)) {
      CRM_Member_BAO_Membership::createLineItems($this, $formValues['membership_type_id'], $priceSetId);
    }
    $isQuickConfig = 0;
    if ($this->_priceSetId && CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_priceSetId, 'is_quick_config')) {
      $isQuickConfig = 1;
    }

    $termsByType = array();
    if ($priceSetId) {
      CRM_Price_BAO_PriceSet::processAmount($this->_priceSet['fields'],
        $this->_params, $lineItem[$priceSetId]);
      $params['total_amount'] = CRM_Utils_Array::value('amount', $this->_params);
      $submittedFinancialType = CRM_Utils_Array::value('financial_type_id', $formValues);
      if (!empty($lineItem[$priceSetId])) {
        foreach ($lineItem[$priceSetId] as &$li) {
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
    }

    $this->storeContactFields($formValues);

    $params['contact_id'] = $this->_contactID;

    $fields = array(
      'status_id',
      'source',
      'is_override',
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

    $memTypeNumTerms =  empty($termsByType) ? CRM_Utils_Array::value('num_terms', $formValues) : NULL;

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
        //$params[$d] = CRM_Utils_Date::processDate( $date );
      }
    }

    // max related memberships - take from form or inherit from membership type
    foreach ($this->_memTypeSelected as $memType) {
      if (array_key_exists('max_related', $formValues)) {
        $membershipTypeValues[$memType]['max_related'] = CRM_Utils_Array::value('max_related', $formValues);
      }
    }

    if ($this->_id) {
      $ids['membership'] = $params['id'] = $this->_id;
    }

    $session = CRM_Core_Session::singleton();
    $ids['userId'] = $session->get('userID');

    // membership type custom data
    foreach ($this->_memTypeSelected as $memType) {
      $customFields = CRM_Core_BAO_CustomField::getFields('Membership', FALSE, FALSE,
        $memType
      );

      $customFields = CRM_Utils_Array::crmArrayMerge($customFields,
        CRM_Core_BAO_CustomField::getFields('Membership',
          FALSE, FALSE,
          NULL, NULL, TRUE
        )
      );

      $membershipTypeValues[$memType]['custom'] = CRM_Core_BAO_CustomField::postProcess($formValues,
        $customFields,
        $this->_id,
        'Membership'
      );
    }

    foreach ($this->_memTypeSelected as $memType) {
      $membershipTypes[$memType] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
        $memType
      );
    }

    $membershipType = implode(', ', $membershipTypes);

    // Retrieve the name and email of the current user - this will be the FROM for the receipt email
    list($userName, $userEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($ids['userId']);

    //CRM-13981, allow different person as a soft-contributor of chosen type
    if ($this->_contributorContactID != $this->_contactID) {
      $params['contribution_contact_id'] = $this->_contributorContactID;
      if (!empty($this->_params['soft_credit_type_id'])) {
        $softParams['soft_credit_type_id'] = $this->_params['soft_credit_type_id'];
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
      );

      foreach ($recordContribution as $f) {
        $params[$f] = CRM_Utils_Array::value($f, $formValues);
      }

      if (!$this->_onlinePendingContributionId) {
        $params['contribution_source'] = ts('%1 Membership: Offline signup (by %2)',
          array(1 => $membershipType, 2 => $userName)
        );
      }

      if (empty($params['is_override']) &&
        CRM_Utils_Array::value('contribution_status_id', $params) == array_search('Pending', CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name'))
      ) {
        $allStatus = CRM_Member_PseudoConstant::membershipStatus();
        $params['status_id'] = array_search('Pending', $allStatus);
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
      if (empty($formValues['total_amount']) && !$priceSetId) {
        // if total amount not provided minimum for membership type is used
        $params['total_amount'] = $formValues['total_amount'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
          $formValues['membership_type_id'][1], 'minimum_fee'
        );
      }
      else {
        $params['total_amount'] = CRM_Utils_Array::value('total_amount', $formValues, 0);
      }

      if ($priceSetId && !$isQuickConfig) {
        $params['financial_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet',
          $priceSetId,
          'financial_type_id'
        );
      }
      else {
        $params['financial_type_id'] = CRM_Utils_Array::value('financial_type_id', $formValues);
      }

      $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($formValues['payment_processor_id'],
        $this->_mode
      );

      //get the payment processor id as per mode.
      $params['payment_processor_id'] = $this->_params['payment_processor_id'] = $formValues['payment_processor_id'] = $this->_paymentProcessor['id'];


      $now = date('YmdHis');
      $fields = array();

      // set email for primary location.
      $fields['email-Primary'] = 1;
      $formValues['email-5'] = $formValues['email-Primary'] = $this->_memberEmail;
      $params['register_date'] = $now;

      // now set the values for the billing location.
      foreach ($this->_fields as $name => $dontCare) {
        $fields[$name] = 1;
      }

      // also add location name to the array
      $formValues["address_name-{$this->_bltID}"] = CRM_Utils_Array::value('billing_first_name', $formValues) . ' ' . CRM_Utils_Array::value('billing_middle_name', $formValues) . ' ' . CRM_Utils_Array::value('billing_last_name', $formValues);

      $formValues["address_name-{$this->_bltID}"] = trim($formValues["address_name-{$this->_bltID}"]);

      $fields["address_name-{$this->_bltID}"] = 1;
      //ensure we don't over-write the payer's email with the member's email
      if ($this->_contributorContactID == $this->_contactID) {
        $fields["email-{$this->_bltID}"] = 1;
      }

      $ctype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactID, 'contact_type');

      $nameFields = array('first_name', 'middle_name', 'last_name');

      foreach ($nameFields as $name) {
        $fields[$name] = 1;
        if (array_key_exists("billing_$name", $formValues)) {
          $formValues[$name] = $formValues["billing_{$name}"];
          $formValues['preserveDBName'] = TRUE;
        }
      }
      if ($this->_contributorContactID == $this->_contactID) {
        //see CRM-12869 for discussion of why we don't do this for separate payee payments
        CRM_Contact_BAO_Contact::createProfileContact($formValues, $fields,
          $this->_contributorContactID, NULL, NULL, $ctype
        );
      }

      // add all the additional payment params we need
      $this->_params["state_province-{$this->_bltID}"] = $this->_params["billing_state_province-{$this->_bltID}"] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($this->_params["billing_state_province_id-{$this->_bltID}"]);
      $this->_params["country-{$this->_bltID}"] = $this->_params["billing_country-{$this->_bltID}"] = CRM_Core_PseudoConstant::countryIsoCode($this->_params["billing_country_id-{$this->_bltID}"]);

      $this->_params['year'] = CRM_Core_Payment_Form::getCreditCardExpirationYear($this->_params);
      $this->_params['month'] = CRM_Core_Payment_Form::getCreditCardExpirationMonth($this->_params);
      $this->_params['ip_address'] = CRM_Utils_System::ipAddress();
      $this->_params['amount'] = $params['total_amount'];
      $this->_params['currencyID'] = $config->defaultCurrency;
      $this->_params['payment_action'] = 'Sale';
      $this->_params['invoiceID'] = md5(uniqid(rand(), TRUE));
      $this->_params['financial_type_id'] = $params['financial_type_id'];

      // at this point we've created a contact and stored its address etc
      // all the payment processors expect the name and address to be in the
      // so we copy stuff over to first_name etc.
      $paymentParams = $this->_params;
      $paymentParams['contactID'] = $this->_contributorContactID;
      //CRM-10377 if payment is by an alternate contact then we need to set that person
      // as the contact in the payment params
      if ($this->_contributorContactID != $this->_contactID) {
        if (!empty($this->_params['soft_credit_type_id'])) {
          $softParams['contact_id'] = $params['contact_id'];
          $softParams['soft_credit_type_id'] = $this->_params['soft_credit_type_id'];
        }
      }
      if (!empty($this->_params['send_receipt'])) {
        $paymentParams['email'] = $this->_contributorEmail;
      }

      CRM_Core_Payment_Form::mapParams($this->_bltID, $this->_params, $paymentParams, TRUE);

      // CRM-7137 -for recurring membership,
      // we do need contribution and recuring records.
      $result = NULL;
      if (!empty($paymentParams['is_recur'])) {
        $allStatus = CRM_Member_PseudoConstant::membershipStatus();

        $contributionType = new CRM_Financial_DAO_FinancialType();
        $contributionType->id = $params['financial_type_id'];
        if (!$contributionType->find(TRUE)) {
          CRM_Core_Error::fatal('Could not find a system table');
        }

        $contribution = CRM_Contribute_Form_Contribution_Confirm::processContribution($this,
          $paymentParams,
          $result,
          $this->_contributorContactID,
          $contributionType,
          TRUE,
          FALSE,
          $isTest,
          $lineItems
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
        $params['status_id'] = array_search('Pending', $allStatus);
        $params['skipStatusCal'] = TRUE;

        //as membership is pending set dates to null.
        $memberDates = array(
          'join_date' => 'joinDate',
          'start_date' => 'startDate',
          'end_date' => 'endDate',
        );

        foreach ($memberDates as $dp => $dv) {
          $$dv = NULL;
          foreach ($this->_memTypeSelected as $memType) {
            $membershipTypeValues[$memType][$dv] = NULL;
          }
        }
      }

      if ($params['total_amount'] > 0.0) {
        $payment = CRM_Core_Payment::singleton($this->_mode, $this->_paymentProcessor, $this);
        $result = $payment->doDirectPayment($paymentParams);
      }

      if (is_a($result, 'CRM_Core_Error')) {
        //make sure to cleanup db for recurring case.
        if (!empty($paymentParams['contributionID'])) {
          CRM_Contribute_BAO_Contribution::deleteContribution($paymentParams['contributionID']);
        }
        if (!empty($paymentParams['contributionRecurID'])) {
          CRM_Contribute_BAO_ContributionRecur::deleteRecurContribution($paymentParams['contributionRecurID']);
        }

        CRM_Core_Error::displaySessionError($result);
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/membership',
          "reset=1&action=add&cid={$this->_contactID}&context=&mode={$this->_mode}"
        ));
      }

      if ($result) {
        $this->_params = array_merge($this->_params, $result);
        //assign amount to template if payment was successful
        $this->assign('amount', $params['total_amount']);
      }

      $params['contribution_status_id'] = !empty($paymentParams['is_recur']) ? 2 : 1;
      $params['receive_date'] = $now;
      $params['invoice_id'] = $this->_params['invoiceID'];
      $params['contribution_source'] = ts('%1 Membership Signup: Credit card or direct debit (by %2)',
        array(1 => $membershipType, 2 => $userName)
      );
      $params['source'] = $formValues['source'] ? $formValues['source'] : $params['contribution_source'];
      $params['trxn_id'] = CRM_Utils_Array::value('trxn_id', $result);
      $params['payment_instrument_id'] = 1;
      $params['is_test'] = ($this->_mode == 'live') ? 0 : 1;
      if (!empty($this->_params['send_receipt'])) {
        $params['receipt_date'] = $now;
      }
      else {
        $params['receipt_date'] = NULL;
      }

      $this->set('params', $this->_params);
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
          $lineItems = CRM_Price_BAO_LineItem::getLineItems($params['contribution_id'], 'contribution', NULL, TRUE, TRUE);
          $itemId = key($lineItems);
          $priceSetId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $lineItems[$itemId]['price_field_id'], 'price_set_id');
          $fieldType = NULL;
          if ($itemId && !empty($lineItems[$itemId]['price_field_id'])) {
            $fieldType = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $lineItems[$itemId]['price_field_id'], 'html_type');
          }
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

        // FIX ME: need to recheck this
        // here we might updated dates, so get from object.
        foreach ($calcDates[$membership->membership_type_id] as $date => & $val) {
          if ($membership->$date) {
            $val = $membership->$date;
          }
        }

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

          $this->_membershipIDs[] = $membership->id;
          $createdMemberships[$memType] = $membership;
          $count++;
        }
      }
    }

    if (!empty($lineItem[$priceSetId])) {
      foreach ($lineItem[$priceSetId] as & $priceFieldOp) {
        if (!empty($priceFieldOp['membership_type_id'])) {
          $priceFieldOp['start_date'] = $membershipTypeValues[$priceFieldOp['membership_type_id']]['start_date'] ? CRM_Utils_Date::customFormat($membershipTypeValues[$priceFieldOp['membership_type_id']]['start_date'], '%B %E%f, %Y') : '-';
          $priceFieldOp['end_date'] = $membershipTypeValues[$priceFieldOp['membership_type_id']]['end_date'] ? CRM_Utils_Date::customFormat($membershipTypeValues[$priceFieldOp['membership_type_id']]['end_date'], '%B %E%f, %Y') : '-';
        }
        else {
          $priceFieldOp['start_date'] = $priceFieldOp['end_date'] = 'N/A';
        }
      }
    }
    $this->assign('lineItem', !empty($lineItem) && !$isQuickConfig ? $lineItem : FALSE);

    $receiptSend = FALSE;
    if (!empty($formValues['send_receipt'])) {
      $receiptSend = TRUE;

      $formValues['contact_id'] = $this->_contactID;

      $formValues['contribution_id'] = CRM_Member_BAO_Membership::getMembershipContributionId($membership->id);
      // send email receipt
      $mailSend = self::emailReceipt($this, $formValues, $membership);
    }

    if (($this->_action & CRM_Core_Action::UPDATE)) {
      //end date can be modified by hooks, so if end date is set then use it.
      $endDate = ($membership->end_date) ? $membership->end_date : $endDate;

      $statusMsg = ts('Membership for %1 has been updated.', array(1 => $this->_memberDisplayName));
      if ($endDate && $endDate !== 'null') {
        $endDate = CRM_Utils_Date::customFormat($endDate);
        $statusMsg .= ' ' . ts('The membership End Date is %1.', array(1 => $endDate));
      }
      if ($receiptSend) {
        $statusMsg .= ' ' . ts('A confirmation and receipt has been sent to %1.', array(1 => $this->_contributorEmail));
      }
    }
    elseif (($this->_action & CRM_Core_Action::ADD)) {
      // FIX ME: fix status messages

      $statusMsg = array();
      foreach ($membershipTypes as $memType => $membershipType) {
        $statusMsg[$memType] = ts('%1 membership for %2 has been added.', array(
          1 => $membershipType,
          2 => $this->_memberDisplayName
        ));

        $membership = $createdMemberships[$memType];
        $memEndDate = ($membership->end_date) ? $membership->end_date : $endDate;

        //get the end date from calculated dates.
        if (!$memEndDate && empty($params['is_recur'])) {
          $memEndDate = CRM_Utils_Array::value('end_date', $calcDates[$memType]);
        }

        if ($memEndDate && $memEndDate !== 'null') {
          $memEndDate = CRM_Utils_Date::customFormat($memEndDate);
          $statusMsg[$memType] .= ' ' . ts('The new membership End Date is %1.', array(1 => $memEndDate));
        }
      }
      $statusMsg = implode('<br/>', $statusMsg);
      if ($receiptSend && $mailSend) {
        $statusMsg .= ' ' . ts('A membership confirmation and receipt has been sent to %1.', array(1 => $this->_contributorEmail));
      }
    }

    // finally set membership id if already not set
    if (!$this->_id) {
      $this->_id = $membership->id;
    }

    CRM_Core_Session::setStatus($statusMsg, ts('Complete'), 'success');

    $buttonName = $this->controller->getButtonName();
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
   * Function to send email receipt
   *
   * @param object $form form object
   * @param $formValues
   * @param object $membership object
   *
   * @internal param array $values submitted values
   * @return boolean true if mail was sent successfully
   * @static
   */
  static function emailReceipt(&$form, &$formValues, &$membership) {
    // retrieve 'from email id' for acknowledgement
    $receiptFrom = $formValues['from_email_address'];

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

    if ($form->_mode) {
      if (!empty($form->_params['billing_first_name'])) {
        $name = $form->_params['billing_first_name'];
      }

      if (!empty($form->_params['billing_middle_name'])) {
        $name .= " {$form->_params['billing_middle_name']}";
      }

      if (!empty($form->_params['billing_last_name'])) {
        $name .= " {$form->_params['billing_last_name']}";
      }

      $form->assign('billingName', $name);

      // assign the address formatted up for display
      $addressParts = array(
        "street_address-{$form->_bltID}",
        "city-{$form->_bltID}",
        "postal_code-{$form->_bltID}",
        "state_province-{$form->_bltID}",
        "country-{$form->_bltID}",
      );
      $addressFields = array();
      foreach ($addressParts as $part) {
        list($n, $id) = explode('-', $part);
        if (isset($form->_params['billing_' . $part])) {
          $addressFields[$n] = $form->_params['billing_' . $part];
        }
      }
      $form->assign('address', CRM_Utils_Address::format($addressFields));

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
      $form->assign('mem_end_date', CRM_Utils_Date::customFormat($membership->end_date, '%B %E%f, %Y'));
      $form->assign('membership_name', CRM_Member_PseudoConstant::membershipType($membership->membership_type_id));
    }

    $form->assign('customValues', $customValues);
    $isBatchProcess = is_a($form, 'CRM_Batch_Form_Entry');
    if ((empty($form->_contributorDisplayName) || empty($form->_contributorEmail)) || $isBatchProcess) {
      // in this case the form is being called statically from the batch editing screen
      // having one class in the form layer call another statically is not greate
      // & we should aim to move this function to the BAO layer in future.
      // however, we can assume that the contact_id passed in by the batch
      // function will be the recipient
      list(
        $form->_contributorDisplayName,
        $form->_contributorEmail
        ) = CRM_Contact_BAO_Contact_Location::getEmailDetails(
        $formValues['contact_id']
      );
      if (empty($form->_receiptContactId) || $isBatchProcess) {
        $form->_receiptContactId = $formValues['contact_id'];
      }
    }

    list($mailSend, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate(
      array(
        'groupName' => 'msg_tpl_workflow_membership',
        'valueName' => 'membership_offline_receipt',
        'contactId' => $form->_receiptContactId,
        'from' => $receiptFrom,
        'toName' => $form->_contributorDisplayName,
        'toEmail' => $form->_contributorEmail,
        'isTest' => (bool) ($form->_action & CRM_Core_Action::PREVIEW)
      )
    );

    return TRUE;
  }
}

