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
 * This class generates form components for processing a contribution
 *
 */
class CRM_Contribute_Form_ContributionBase extends CRM_Core_Form {

  /**
   * the id of the contribution page that we are processsing
   *
   * @var int
   * @public
   */
  public $_id;

  /**
   * the mode that we are in
   *
   * @var string
   * @protect
   */
  public $_mode;

  /**
   * the contact id related to a membership
   *
   * @var int
   * @public
   */
  public $_membershipContactID;

  /**
   * the values for the contribution db object
   *
   * @var array
   * @protected
   */
  public $_values;

  /**
   * the paymentProcessor attributes for this page
   *
   * @var array
   * @protected
   */
  public $_paymentProcessor;
  public $_paymentObject = NULL;

  /**
   * The membership block for this page
   *
   * @var array
   * @protected
   */
  public $_membershipBlock = NULL;

  /**
   * Does this form support a separate membership payment
   * @var bool
   */
  protected $_separateMembershipPayment;
  /**
   * the default values for the form
   *
   * @var array
   * @protected
   */
  protected $_defaults;

  /**
   * The params submitted by the form and computed by the app
   *
   * @var array
   * @public
   */
  public $_params;

  /**
   * The fields involved in this contribution page
   *
   * @var array
   * @public
   */
  public $_fields = array();

  /**
   * The billing location id for this contribiution page
   *
   * @var int
   * @protected
   */
  public $_bltID;

  /**
   * Cache the amount to make things easier
   *
   * @var float
   * @public
   */
  public $_amount;

  /**
   * pcp id
   *
   * @var integer
   * @public
   */
  public $_pcpId;

  /**
   * pcp block
   *
   * @var array
   * @public
   */
  public $_pcpBlock;

  /**
   * pcp info
   *
   * @var array
   * @public
   */
  public $_pcpInfo;

  /**
   * The contact id of the person for whom membership is being added or renewed based on the cid in the url,
   * checksum, or session
   * @var int
   */
  public $_contactID;

  protected $_userID;

  /**
   * the Membership ID for membership renewal
   *
   * @var int
   * @public
   */
  public $_membershipId;

  /**
   * Price Set ID, if the new price set method is used
   *
   * @var int
   * @protected
   */
  public $_priceSetId;

  /**
   * Array of fields for the price set
   *
   * @var array
   * @protected
   */
  public $_priceSet;

  public $_action;

 /**
   * Is honor block is enabled for this contribution?
   *
   * @var boolean
   * @protected
   */
  public $_honor_block_is_active = FALSE;

  /**
   * Contribution mode e.g express for payment express, notify for off-site + notification back to CiviCRM
   * @var string
   */
  public $_contributeMode;

  /**
   * contribution page supports memberships
   * @var boolean
   */
  public $_useForMember;
  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $config = CRM_Core_Config::singleton();
    $session = CRM_Core_Session::singleton();

    // current contribution page id
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    if (!$this->_id) {
      // seems like the session is corrupted and/or we lost the id trail
      // lets just bump this to a regular session error and redirect user to main page
      $this->controller->invalidKeyRedirect();
    }

    // this was used prior to the cleverer this_>getContactID - unsure now
    $this->_userID = $session->get('userID');

    //Check if honor block is enabled for current contribution
    $ufJoinParams = array(
      'module' => 'soft_credit',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $this->_id,
    );
    $ufJoin = new CRM_Core_DAO_UFJoin();
    $ufJoin->copyValues($ufJoinParams);
    $ufJoin->find(TRUE);
    $this->_honor_block_is_active = $ufJoin->is_active;

    $this->_contactID = $this->_membershipContactID = $this->getContactID();
    $this->_mid = NULL;
    if ($this->_contactID) {
      $this->_mid = CRM_Utils_Request::retrieve('mid', 'Positive', $this);
      if ($this->_mid) {
        $membership = new CRM_Member_DAO_Membership();
        $membership->id = $this->_mid;

        if ($membership->find(TRUE)) {
          $this->_defaultMemTypeId = $membership->membership_type_id;
          if ($membership->contact_id != $this->_contactID) {
            $validMembership = FALSE;
            $employers = CRM_Contact_BAO_Relationship::getPermissionedEmployer($this->_userID);
            if (!empty($employers) && array_key_exists($membership->contact_id, $employers)) {
              $this->_membershipContactID = $membership->contact_id;
              $this->assign('membershipContactID', $this->_membershipContactID);
              $this->assign('membershipContactName', $employers[$this->_membershipContactID]['name']);
              $validMembership = TRUE;
            } else {
              $membershipType = new CRM_Member_BAO_MembershipType();
              $membershipType->id = $membership->membership_type_id;
              if ($membershipType->find(TRUE)) {
                // CRM-14051 - membership_type.relationship_type_id is a CTRL-A padded string w one or more ID values.
                // Convert to commma separated list.
                $inheritedRelTypes = implode(CRM_Utils_Array::explodePadded($membershipType->relationship_type_id), ',');
                $permContacts = CRM_Contact_BAO_Relationship::getPermissionedContacts($this->_userID, $membershipType->relationship_type_id);
                if (array_key_exists($membership->contact_id, $permContacts)) {
                  $this->_membershipContactID = $membership->contact_id;
                  $validMembership = TRUE;
                }
              }
            }
            if (!$validMembership) {
              CRM_Core_Session::setStatus(ts("Oops. The membership you're trying to renew appears to be invalid. Contact your site administrator if you need assistance. If you continue, you will be issued a new membership."), ts('Membership Invalid'), 'alert');
            }
          }
        }
        else {
          CRM_Core_Session::setStatus(ts("Oops. The membership you're trying to renew appears to be invalid. Contact your site administrator if you need assistance. If you continue, you will be issued a new membership."), ts('Membership Invalid'), 'alert');
        }
        unset($membership);
      }
    }

    // we do not want to display recently viewed items, so turn off
    $this->assign('displayRecent', FALSE);
    // Contribution page values are cleared from session, so can't use normal Printer Friendly view.
    // Use Browser Print instead.
    $this->assign('browserPrint', TRUE);

    // action
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
    $this->assign('action', $this->_action);

    // current mode
    $this->_mode = ($this->_action == 1024) ? 'test' : 'live';

    $this->_values = $this->get('values');
    $this->_fields = $this->get('fields');
    $this->_bltID = $this->get('bltID');
    $this->_paymentProcessor = $this->get('paymentProcessor');
    $this->_priceSetId = $this->get('priceSetId');
    $this->_priceSet = $this->get('priceSet');

    if (!$this->_values) {
      // get all the values from the dao object
      $this->_values = array();
      $this->_fields = array();

      CRM_Contribute_BAO_ContributionPage::setValues($this->_id, $this->_values);

      // check if form is active
      if (empty($this->_values['is_active'])) {
        // form is inactive, die a fatal death
        CRM_Core_Error::fatal(ts('The page you requested is currently unavailable.'));
      }

      // also check for billing informatin
      // get the billing location type
      $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id', array(), 'validate');
      // CRM-8108 remove ts around Billing location type
      //$this->_bltID = array_search( ts('Billing'),  $locationTypes );
      $this->_bltID = array_search('Billing', $locationTypes);
      if (!$this->_bltID) {
        CRM_Core_Error::fatal(ts('Please set a location type of %1', array(1 => 'Billing')));
      }
      $this->set('bltID', $this->_bltID);

      // check for is_monetary status
      $isMonetary = CRM_Utils_Array::value('is_monetary', $this->_values);
      $isPayLater = CRM_Utils_Array::value('is_pay_later', $this->_values);

      //FIXME: to support multiple payment processors
      if ($isMonetary &&
        (!$isPayLater || !empty($this->_values['payment_processor']))
      ) {
        $ppID = CRM_Utils_Array::value('payment_processor', $this->_values);
        if (!$ppID) {
          CRM_Core_Error::fatal(ts('A payment processor must be selected for this contribution page (contact the site administrator for assistance).'));
        }

        $ppIds = explode(CRM_Core_DAO::VALUE_SEPARATOR, $ppID);
        $this->_paymentProcessors = CRM_Financial_BAO_PaymentProcessor::getPayments($ppIds, $this->_mode);

        $this->set('paymentProcessors', $this->_paymentProcessors);

        //set default payment processor
        if (!empty($this->_paymentProcessors) && empty($this->_paymentProcessor)) {
          foreach ($this->_paymentProcessors as $ppId => $values) {
            if ($values['is_default'] == 1 || (count($this->_paymentProcessors) == 1)) {
              $defaultProcessorId = $ppId;
              break;
            }
          }
        }

        if (isset($defaultProcessorId)) {
          $this->_paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($defaultProcessorId, $this->_mode);
          $this->assign_by_ref('paymentProcessor', $this->_paymentProcessor);
        }

        if (!CRM_Utils_System::isNull($this->_paymentProcessors)) {
          foreach ($this->_paymentProcessors as $eachPaymentProcessor) {
            // check selected payment processor is active
            if (empty($eachPaymentProcessor)) {
              CRM_Core_Error::fatal(ts('A payment processor configured for this page might be disabled (contact the site administrator for assistance).'));
            }

            // ensure that processor has a valid config
            $this->_paymentObject = &CRM_Core_Payment::singleton($this->_mode, $eachPaymentProcessor, $this);
            $error = $this->_paymentObject->checkConfig();
            if (!empty($error)) {
              CRM_Core_Error::fatal($error);
            }
          }
        }
      }

      // get price info
      // CRM-5095
      CRM_Price_BAO_PriceSet::initSet($this, $this->_id, 'civicrm_contribution_page');

      // this avoids getting E_NOTICE errors in php
      $setNullFields = array(
        'amount_block_is_active',
        'is_allow_other_amount',
        'footer_text',
      );
      foreach ($setNullFields as $f) {
        if (!isset($this->_values[$f])) {
          $this->_values[$f] = NULL;
        }
      }

      //check if Membership Block is enabled, if Membership Fields are included in profile
      //get membership section for this contribution page
      $this->_membershipBlock = CRM_Member_BAO_Membership::getMembershipBlock($this->_id);
      $this->set('membershipBlock', $this->_membershipBlock);

      if ($this->_values['custom_pre_id']) {
        $preProfileType = CRM_Core_BAO_UFField::getProfileType($this->_values['custom_pre_id']);
      }

      if ($this->_values['custom_post_id']) {
        $postProfileType = CRM_Core_BAO_UFField::getProfileType($this->_values['custom_post_id']);
      }

      if (((isset($postProfileType) && $postProfileType == 'Membership') ||
          (isset($preProfileType) && $preProfileType == 'Membership')
        ) &&
        !$this->_membershipBlock['is_active']
      ) {
        CRM_Core_Error::fatal(ts('This page includes a Profile with Membership fields - but the Membership Block is NOT enabled. Please notify the site administrator.'));
      }

      $pledgeBlock = CRM_Pledge_BAO_PledgeBlock::getPledgeBlock($this->_id);

      if ($pledgeBlock) {
        $this->_values['pledge_block_id'] = CRM_Utils_Array::value('id', $pledgeBlock);
        $this->_values['max_reminders'] = CRM_Utils_Array::value('max_reminders', $pledgeBlock);
        $this->_values['initial_reminder_day'] = CRM_Utils_Array::value('initial_reminder_day', $pledgeBlock);
        $this->_values['additional_reminder_day'] = CRM_Utils_Array::value('additional_reminder_day', $pledgeBlock);

        //set pledge id in values
        $pledgeId = CRM_Utils_Request::retrieve('pledgeId', 'Positive', $this);

        //authenticate pledge user for pledge payment.
        if ($pledgeId) {
          $this->_values['pledge_id'] = $pledgeId;

          //lets override w/ pledge campaign.
          $this->_values['campaign_id'] = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_Pledge',
            $pledgeId,
            'campaign_id'
          );
          self::authenticatePledgeUser();
        }
      }
      $this->set('values', $this->_values);
      $this->set('fields', $this->_fields);
    }

    // Handle PCP
    $pcpId = CRM_Utils_Request::retrieve('pcpId', 'Positive', $this);
    if ($pcpId) {
      $pcp             = CRM_PCP_BAO_PCP::handlePcp($pcpId, 'contribute', $this->_values);
      $this->_pcpId    = $pcp['pcpId'];
      $this->_pcpBlock = $pcp['pcpBlock'];
      $this->_pcpInfo  = $pcp['pcpInfo'];
    }

    // Link (button) for users to create their own Personal Campaign page
    if ($linkText = CRM_PCP_BAO_PCP::getPcpBlockStatus($this->_id, 'contribute')) {
      $linkTextUrl = CRM_Utils_System::url('civicrm/contribute/campaign',
        "action=add&reset=1&pageId={$this->_id}&component=contribute",
        FALSE, NULL, TRUE
      );
      $this->assign('linkTextUrl', $linkTextUrl);
      $this->assign('linkText', $linkText);
    }

    //set pledge block if block id is set
    if (!empty($this->_values['pledge_block_id'])) {
      $this->assign('pledgeBlock', TRUE);
    }

    // check if one of the (amount , membership)  bloks is active or not
    $this->_membershipBlock = $this->get('membershipBlock');

    if (!$this->_values['amount_block_is_active'] &&
      !$this->_membershipBlock['is_active'] &&
      !$this->_priceSetId
    ) {
      CRM_Core_Error::fatal(ts('The requested online contribution page is missing a required Contribution Amount section or Membership section or Price Set. Please check with the site administrator for assistance.'));
    }

    if ($this->_values['amount_block_is_active']) {
      $this->set('amount_block_is_active', $this->_values['amount_block_is_active']);
    }

    $this->_contributeMode = $this->get('contributeMode');
    $this->assign('contributeMode', $this->_contributeMode);

    //assigning is_monetary and is_email_receipt to template
    $this->assign('is_monetary', $this->_values['is_monetary']);
    $this->assign('is_email_receipt', $this->_values['is_email_receipt']);
    $this->assign('bltID', $this->_bltID);

    //assign cancelSubscription URL to templates
    $this->assign('cancelSubscriptionUrl',
      CRM_Utils_Array::value('cancelSubscriptionUrl', $this->_values)
    );

    // assigning title to template in case someone wants to use it, also setting CMS page title
    if ($this->_pcpId) {
      $this->assign('title', $this->_pcpInfo['title']);
      CRM_Utils_System::setTitle($this->_pcpInfo['title']);
    }
    else {
      $this->assign('title', $this->_values['title']);
      CRM_Utils_System::setTitle($this->_values['title']);
    }
    $this->_defaults = array();

    $this->_amount = $this->get('amount');

    //CRM-6907
    $config = CRM_Core_Config::singleton();
    $config->defaultCurrency = CRM_Utils_Array::value('currency',
      $this->_values,
      $config->defaultCurrency
    );

    //lets allow user to override campaign.
    $campID = CRM_Utils_Request::retrieve('campID', 'Positive', $this);
    if ($campID && CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Campaign', $campID)) {
      $this->_values['campaign_id'] = $campID;
    }

    //do check for cancel recurring and clean db, CRM-7696
    if (CRM_Utils_Request::retrieve('cancel', 'Boolean', CRM_Core_DAO::$_nullObject)) {
      self::cancelRecurring();
    }
  }

  /**
   * set the default values
   *
   * @return void
   * @access public
   */
  function setDefaultValues() {
    return $this->_defaults;
  }

  /**
   * assign the minimal set of variables to the template
   *
   * @return void
   * @access public
   */
  function assignToTemplate() {
    $name = CRM_Utils_Array::value('billing_first_name', $this->_params);
    if (!empty($this->_params['billing_middle_name'])) {
      $name .= " {$this->_params['billing_middle_name']}";
    }
    $name .= ' ' . CRM_Utils_Array::value('billing_last_name', $this->_params);
    $name = trim($name);
    $this->assign('billingName', $name);
    $this->set('name', $name);

    $this->assign('paymentProcessor', $this->_paymentProcessor);
    $vars = array(
      'amount', 'currencyID',
      'credit_card_type', 'trxn_id', 'amount_level',
    );

    $config = CRM_Core_Config::singleton();
    if (isset($this->_values['is_recur']) && !empty($this->_paymentProcessor['is_recur'])) {
      $this->assign('is_recur_enabled', 1);
      $vars = array_merge($vars, array(
        'is_recur', 'frequency_interval', 'frequency_unit',
          'installments',
        ));
    }

    if (in_array('CiviPledge', $config->enableComponents) &&
      CRM_Utils_Array::value('is_pledge', $this->_params) == 1
    ) {
      $this->assign('pledge_enabled', 1);

      $vars = array_merge($vars, array(
        'is_pledge',
          'pledge_frequency_interval',
          'pledge_frequency_unit',
          'pledge_installments',
        ));
    }

    if (isset($this->_params['amount_other']) || isset($this->_params['selectMembership'])) {
      $this->_params['amount_level'] = '';
    }

    foreach ($vars as $v) {
      if (isset($this->_params[$v])) {
        if ($v == 'frequency_unit' || $v == 'pledge_frequency_unit') {
          $frequencyUnits = CRM_Core_OptionGroup::values('recur_frequency_units');
          if (array_key_exists($this->_params[$v], $frequencyUnits)) {
            $this->_params[$v] = $frequencyUnits[$this->_params[$v]];
          }
        }
        if ($v == "amount" && $this->_params[$v] === 0) {
          $this->_params[$v] = CRM_Utils_Money::format($this->_params[$v], NULL, NULL, TRUE);
        }
        $this->assign($v, $this->_params[$v]);
      }
    }

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
      $addressFields[$n] = CRM_Utils_Array::value('billing_' . $part, $this->_params);
    }

    $this->assign('address', CRM_Utils_Address::format($addressFields));

    if (!empty($this->_params['hidden_onbehalf_profile'])) {
      $this->assign('onBehalfName', $this->_params['organization_name']);
      $locTypeId = array_keys($this->_params['onbehalf_location']['email']);
      $this->assign('onBehalfEmail', $this->_params['onbehalf_location']['email'][$locTypeId[0]]['email']);
    }

    //fix for CRM-3767
    $assignCCInfo = FALSE;
    if ($this->_amount > 0.0) {
      $assignCCInfo = TRUE;
    }
    elseif (!empty($this->_params['selectMembership'])) {
      $memFee = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $this->_params['selectMembership'], 'minimum_fee');
      if ($memFee > 0.0) {
        $assignCCInfo = TRUE;
      }
    }

    if ($this->_contributeMode == 'direct' && $assignCCInfo) {
      if ($this->_paymentProcessor &&
        $this->_paymentProcessor['payment_type'] & CRM_Core_Payment::PAYMENT_TYPE_DIRECT_DEBIT
      ) {
        $this->assign('payment_type', $this->_paymentProcessor['payment_type']);
        $this->assign('account_holder', $this->_params['account_holder']);
        $this->assign('bank_identification_number', $this->_params['bank_identification_number']);
        $this->assign('bank_name', $this->_params['bank_name']);
        $this->assign('bank_account_number', $this->_params['bank_account_number']);
      }
      else {
        $date = CRM_Utils_Date::format(CRM_Utils_array::value('credit_card_exp_date', $this->_params));
        $date = CRM_Utils_Date::mysqlToIso($date);
        $this->assign('credit_card_exp_date', $date);
        $this->assign('credit_card_number',
                      CRM_Utils_System::mungeCreditCard(CRM_Utils_array::value('credit_card_number', $this->_params))
        );
      }
    }

    $this->assign('email',
      $this->controller->exportValue('Main', "email-{$this->_bltID}")
    );

    // also assign the receipt_text
    if (isset($this->_values['receipt_text'])) {
      $this->assign('receipt_text', $this->_values['receipt_text']);
    }
  }

  /**
   * Function to add the custom fields
   *
   * @param $id
   * @param $name
   * @param bool $viewOnly
   * @param null $profileContactType
   * @param null $fieldTypes
   *
   * @return void
   * @access public
   */
  function buildCustom($id, $name, $viewOnly = FALSE, $profileContactType = NULL, $fieldTypes = NULL) {
    if ($id) {
      $contactID = $this->getContactID();

      // we don't allow conflicting fields to be
      // configured via profile - CRM 2100
      $fieldsToIgnore = array(
        'receive_date' => 1,
        'trxn_id' => 1,
        'invoice_id' => 1,
        'net_amount' => 1,
        'fee_amount' => 1,
        'non_deductible_amount' => 1,
        'total_amount' => 1,
        'amount_level' => 1,
        'contribution_status_id' => 1,
        'payment_instrument' => 1,
        'check_number' => 1,
        'financial_type' => 1,
      );

      $fields = NULL;
      if ($contactID && CRM_Core_BAO_UFGroup::filterUFGroups($id, $contactID)) {
        $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD, NULL, NULL, FALSE,
          NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL
        );
      }
      else {
        $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD, NULL, NULL, FALSE,
          NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL
        );
      }

      if ($fields) {
        // unset any email-* fields since we already collect it, CRM-2888
        foreach (array_keys($fields) as $fieldName) {
          if (substr($fieldName, 0, 6) == 'email-' && $profileContactType != 'honor') {
            unset($fields[$fieldName]);
          }
        }

        if (array_intersect_key($fields, $fieldsToIgnore)) {
          $fields = array_diff_key($fields, $fieldsToIgnore);
          CRM_Core_Session::setStatus(ts('Some of the profile fields cannot be configured for this page.'), ts('Warning'), 'alert');
        }

        $fields = array_diff_assoc($fields, $this->_fields);

        CRM_Core_BAO_Address::checkContactSharedAddressFields($fields, $contactID);
        $addCaptcha = FALSE;
        foreach ($fields as $key => $field) {
          if ($viewOnly &&
            isset($field['data_type']) &&
            $field['data_type'] == 'File' || ($viewOnly && $field['name'] == 'image_URL')
          ) {
            // ignore file upload fields
            continue;
          }

          if ($profileContactType) {
            //Since we are showing honoree name separately so we are removing it from honoree profile just for display
            $honoreeNamefields = array('prefix_id', 'first_name', 'last_name', 'suffix_id', 'organization_name', 'household_name');
            if ($profileContactType == 'honor' && in_array($field['name'], $honoreeNamefields)) {
              unset($fields[$field['name']]);
              continue;
            }
            if (!empty($fieldTypes) && in_array($field['field_type'], $fieldTypes)) {
              CRM_Core_BAO_UFGroup::buildProfile(
                $this,
                $field,
                CRM_Profile_Form::MODE_CREATE,
                $contactID,
                TRUE,
                $profileContactType
              );
              $this->_fields[$profileContactType][$key] = $field;
            }
            else {
              unset($fields[$key]);
            }
          }
          else {
            CRM_Core_BAO_UFGroup::buildProfile(
              $this,
              $field,
              CRM_Profile_Form::MODE_CREATE,
              $contactID,
              TRUE
            );
            $this->_fields[$key] = $field;
          }
          // CRM-11316 Is ReCAPTCHA enabled for this profile AND is this an anonymous visitor
          if ($field['add_captcha'] && !$this->_userID) {
            $addCaptcha = TRUE;
          }
        }

        $this->assign($name, $fields);

        if ($addCaptcha && !$viewOnly) {
          $captcha = CRM_Utils_ReCAPTCHA::singleton();
          $captcha->add($this);
          $this->assign('isCaptcha', TRUE);
        }
      }
    }
  }

  /**
   * Check template file exists
   * @param null $suffix
   *
   * @return null|string
   */
  function checkTemplateFileExists($suffix = NULL) {
    if ($this->_id) {
      $templateFile = "CRM/Contribute/Form/Contribution/{$this->_id}/{$this->_name}.{$suffix}tpl";
      $template = CRM_Core_Form::getTemplate();
      if ($template->template_exists($templateFile)) {
        return $templateFile;
      }
    }
    return NULL;
  }

  /**
   * Use the form name to create the tpl file name
   *
   * @return string
   * @access public
   */
  /**
   * @return string
   */
  function getTemplateFileName() {
    $fileName = $this->checkTemplateFileExists();
    return $fileName ? $fileName : parent::getTemplateFileName();
  }

  /**
   * Default extra tpl file basically just replaces .tpl with .extra.tpl
   * i.e. we dont override
   *
   * @return string
   * @access public
   */
  /**
   * @return string
   */
  function overrideExtraTemplateFileName() {
    $fileName = $this->checkTemplateFileExists('extra.');
    return $fileName ? $fileName : parent::overrideExtraTemplateFileName();
  }

  /**
   * Function to authenticate pledge user during online payment.
   *
   * @access public
   *
   * @return void
   */
  public function authenticatePledgeUser() {
    //get the userChecksum and contact id
    $userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this);
    $contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    //get pledge status and contact id
    $pledgeValues     = array();
    $pledgeParams     = array('id' => $this->_values['pledge_id']);
    $returnProperties = array('contact_id', 'status_id');
    CRM_Core_DAO::commonRetrieve('CRM_Pledge_DAO_Pledge', $pledgeParams, $pledgeValues, $returnProperties);

    //get all status
    $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $validStatus = array(array_search('Pending', $allStatus),
      array_search('In Progress', $allStatus),
      array_search('Overdue', $allStatus),
    );

    $validUser = FALSE;
    if ($this->_userID &&
      $this->_userID == $pledgeValues['contact_id']
    ) {
      //check for authenticated  user.
      $validUser = TRUE;
    }
    elseif ($userChecksum && $pledgeValues['contact_id']) {
      //check for anonymous user.
      $validUser = CRM_Contact_BAO_Contact_Utils::validChecksum($pledgeValues['contact_id'], $userChecksum);

      //make sure cid is same as pledge contact id
      if ($validUser && ($pledgeValues['contact_id'] != $contactID)) {
        $validUser = FALSE;
      }
    }

    if (!$validUser) {
      CRM_Core_Error::fatal(ts("Oops. It looks like you have an incorrect or incomplete link (URL). Please make sure you've copied the entire link, and try again. Contact the site administrator if this error persists."));
    }

    //check for valid pledge status.
    if (!in_array($pledgeValues['status_id'], $validStatus)) {
      CRM_Core_Error::fatal(ts('Oops. You cannot make a payment for this pledge - pledge status is %1.', array(1 => CRM_Utils_Array::value($pledgeValues['status_id'], $allStatus))));
    }
  }

  /**
   * In case user cancel recurring contribution,
   * When we get the control back from payment gate way
   * lets delete the recurring and related contribution.
   *
   **/
  public function cancelRecurring() {
    $isCancel = CRM_Utils_Request::retrieve('cancel', 'Boolean', CRM_Core_DAO::$_nullObject);
    if ($isCancel) {
      $isRecur = CRM_Utils_Request::retrieve('isRecur', 'Boolean', CRM_Core_DAO::$_nullObject);
      $recurId = CRM_Utils_Request::retrieve('recurId', 'Positive', CRM_Core_DAO::$_nullObject);
      //clean db for recurring contribution.
      if ($isRecur && $recurId) {
        CRM_Contribute_BAO_ContributionRecur::deleteRecurContribution($recurId);
      }
      $contribId = CRM_Utils_Request::retrieve('contribId', 'Positive', CRM_Core_DAO::$_nullObject);
      if ($contribId) {
        CRM_Contribute_BAO_Contribution::deleteContribution($contribId);
      }
    }
  }
}

