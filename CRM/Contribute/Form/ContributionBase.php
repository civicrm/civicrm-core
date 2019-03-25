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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class generates form components for processing a contribution.
 */
class CRM_Contribute_Form_ContributionBase extends CRM_Core_Form {

  /**
   * The id of the contribution page that we are processing.
   *
   * @var int
   */
  public $_id;

  /**
   * The mode that we are in
   *
   * @var string
   * @protect
   */
  public $_mode;

  /**
   * The contact id related to a membership
   *
   * @var int
   */
  public $_membershipContactID;

  /**
   * The values for the contribution db object
   *
   * @var array
   */
  public $_values;

  /**
   * The paymentProcessor attributes for this page
   *
   * @var array
   */
  public $_paymentProcessor;

  public $_paymentObject = NULL;

  /**
   * The membership block for this page
   *
   * @var array
   */
  public $_membershipBlock = NULL;

  /**
   * Does this form support a separate membership payment
   * @var bool
   */
  protected $_separateMembershipPayment;

  /**
   * The params submitted by the form and computed by the app
   *
   * @var array
   */
  public $_params = array();

  /**
   * The fields involved in this contribution page
   *
   * @var array
   */
  public $_fields = array();

  /**
   * The billing location id for this contribution page.
   *
   * @var int
   */
  public $_bltID;

  /**
   * Cache the amount to make things easier
   *
   * @var float
   */
  public $_amount;

  /**
   * Pcp id
   *
   * @var integer
   */
  public $_pcpId;

  /**
   * Pcp block
   *
   * @var array
   */
  public $_pcpBlock;

  /**
   * Pcp info
   *
   * @var array
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
   * The Membership ID for membership renewal
   *
   * @var int
   */
  public $_membershipId;

  /**
   * Price Set ID, if the new price set method is used
   *
   * @var int
   */
  public $_priceSetId;

  /**
   * Array of fields for the price set
   *
   * @var array
   */
  public $_priceSet;

  public $_action;

  /**
   * Contribution mode e.g express for payment express, notify for off-site + notification back to CiviCRM
   * @var string
   */
  public $_contributeMode;

  /**
   * Contribution page supports memberships
   * @var boolean
   */
  public $_useForMember;

  /**
   * @deprecated
   *
   * @var
   */
  public $_isBillingAddressRequiredForPayLater;

  /**
   * Flag if email field exists in embedded profile
   *
   * @var bool
   */
  public $_emailExists = FALSE;

  /**
   * Is this a backoffice form
   * (this will affect whether paypal express code is displayed)
   * @var bool
   */
  public $isBackOffice = FALSE;

  /**
   * Payment instrument if for the transaction.
   *
   * This will generally be drawn from the payment processor and is ignored for
   * front end forms.
   *
   * @var int
   */
  public $paymentInstrumentID;

  /**
   * Is the price set quick config.
   * @return bool
   */
  public function isQuickConfig() {
    return isset(self::$_quickConfig) ? self::$_quickConfig : FALSE;
  }

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Contribute_Exception_InactiveContributionPageException
   * @throws \Exception
   */
  public function preProcess() {

    // current contribution page id
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_ccid = CRM_Utils_Request::retrieve('ccid', 'Positive', $this);
    if (!$this->_id) {
      // seems like the session is corrupted and/or we lost the id trail
      // lets just bump this to a regular session error and redirect user to main page
      $this->controller->invalidKeyRedirect();
    }
    $this->_emailExists = $this->get('emailExists');

    // this was used prior to the cleverer this_>getContactID - unsure now
    $this->_userID = CRM_Core_Session::singleton()->getLoggedInContactID();

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
            $organizations = CRM_Contact_BAO_Relationship::getPermissionedContacts($this->_userID, NULL, NULL, 'Organization');
            if (!empty($organizations) && array_key_exists($membership->contact_id, $organizations)) {
              $this->_membershipContactID = $membership->contact_id;
              $this->assign('membershipContactID', $this->_membershipContactID);
              $this->assign('membershipContactName', $organizations[$this->_membershipContactID]['name']);
              $validMembership = TRUE;
            }
            else {
              $membershipType = new CRM_Member_BAO_MembershipType();
              $membershipType->id = $membership->membership_type_id;
              if ($membershipType->find(TRUE)) {
                // CRM-14051 - membership_type.relationship_type_id is a CTRL-A padded string w one or more ID values.
                // Convert to comma separated list.
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
      if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()
        && !CRM_Core_Permission::check('add contributions of type ' . CRM_Contribute_PseudoConstant::financialType($this->_values['financial_type_id']))
      ) {
        CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
      }
      if (empty($this->_values['is_active'])) {
        throw new CRM_Contribute_Exception_InactiveContributionPageException(ts('The page you requested is currently unavailable.'), $this->_id);
      }

      $endDate = CRM_Utils_Date::processDate(CRM_Utils_Array::value('end_date', $this->_values));
      $now = date('YmdHis');
      if ($endDate && $endDate < $now) {
        throw new CRM_Contribute_Exception_PastContributionPageException(ts('The page you requested has past its end date on ' . CRM_Utils_Date::customFormat($endDate)), $this->_id);
      }

      $startDate = CRM_Utils_Date::processDate(CRM_Utils_Array::value('start_date', $this->_values));
      if ($startDate && $startDate > $now) {
        throw new CRM_Contribute_Exception_FutureContributionPageException(ts('The page you requested will be active from ' . CRM_Utils_Date::customFormat($startDate)), $this->_id);
      }

      $this->assignBillingType();

      // check for is_monetary status
      $isMonetary = CRM_Utils_Array::value('is_monetary', $this->_values);
      $isPayLater = CRM_Utils_Array::value('is_pay_later', $this->_values);
      if (!empty($this->_ccid)) {
        $this->_values['financial_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution',
          $this->_ccid,
          'financial_type_id'
        );
        if ($isPayLater) {
          $isPayLater = FALSE;
          $this->_values['is_pay_later'] = FALSE;
        }
      }

      if ($isMonetary) {
        $this->_paymentProcessorIDs = array_filter(explode(
          CRM_Core_DAO::VALUE_SEPARATOR,
          CRM_Utils_Array::value('payment_processor', $this->_values)
        ));

        $this->assignPaymentProcessor($isPayLater);
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

      if (!empty($this->_values['custom_pre_id'])) {
        $preProfileType = CRM_Core_BAO_UFField::getProfileType($this->_values['custom_pre_id']);
      }

      if (!empty($this->_values['custom_post_id'])) {
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
      $pcp = CRM_PCP_BAO_PCP::handlePcp($pcpId, 'contribute', $this->_values);
      $this->_pcpId = $pcp['pcpId'];
      $this->_pcpBlock = $pcp['pcpBlock'];
      $this->_pcpInfo = $pcp['pcpInfo'];
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

    // check if one of the (amount , membership)  blocks is active or not.
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

    $this->setTitle(($this->_pcpId ? $this->_pcpInfo['title'] : $this->_values['title']));
    $this->_defaults = array();

    $this->_amount = $this->get('amount');
    // Assigning this to the template means it will be passed through to the payment form.
    // This can, for example, by used by payment processors using client side encryption
    $this->assign('currency', $this->getCurrency());

    //CRM-6907
    // these lines exist to support a non-default currenty on the form but are probably
    // obsolete & meddling wth the defaultCurrency is not the right approach....
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
    if (CRM_Utils_Request::retrieve('cancel', 'Boolean')) {
      self::cancelRecurring();
    }

    // check if billing block is required for pay later
    if (CRM_Utils_Array::value('is_pay_later', $this->_values)) {
      $this->_isBillingAddressRequiredForPayLater = CRM_Utils_Array::value('is_billing_required', $this->_values);
      $this->assign('isBillingAddressRequiredForPayLater', $this->_isBillingAddressRequiredForPayLater);
    }
  }

  /**
   * Set the default values.
   */
  public function setDefaultValues() {
    return $this->_defaults;
  }

  /**
   * Assign the minimal set of variables to the template.
   */
  public function assignToTemplate() {
    $this->set('name', $this->assignBillingName($this->_params));

    $this->assign('paymentProcessor', $this->_paymentProcessor);
    $vars = array(
      'amount',
      'currencyID',
      'credit_card_type',
      'trxn_id',
      'amount_level',
    );

    $config = CRM_Core_Config::singleton();
    if (isset($this->_values['is_recur']) && !empty($this->_paymentProcessor['is_recur'])) {
      $this->assign('is_recur_enabled', 1);
      $vars = array_merge($vars, array(
        'is_recur',
        'frequency_interval',
        'frequency_unit',
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

    // @todo - stop setting amount level in this function & call the CRM_Price_BAO_PriceSet::getAmountLevel
    // function to get correct amount level consistently. Remove setting of the amount level in
    // CRM_Price_BAO_PriceSet::processAmount. Extend the unit tests in CRM_Price_BAO_PriceSetTest
    // to cover all variants.
    if (isset($this->_params['amount_other']) || isset($this->_params['selectMembership'])) {
      $this->_params['amount_level'] = '';
    }

    foreach ($vars as $v) {
      if (isset($this->_params[$v])) {
        if ($v == "amount" && $this->_params[$v] === 0) {
          $this->_params[$v] = CRM_Utils_Money::format($this->_params[$v], NULL, NULL, TRUE);
        }
        $this->assign($v, $this->_params[$v]);
      }
    }

    $this->assign('address', CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters(
      $this->_params,
      $this->_bltID
    ));

    if (!empty($this->_params['onbehalf_profile_id']) && !empty($this->_params['onbehalf'])) {
      $this->assign('onBehalfName', $this->_params['organization_name']);
      $locTypeId = array_keys($this->_params['onbehalf_location']['email']);
      $this->assign('onBehalfEmail', $this->_params['onbehalf_location']['email'][$locTypeId[0]]['email']);
    }

    //fix for CRM-3767
    $isMonetary = FALSE;
    if ($this->_amount > 0.0) {
      $isMonetary = TRUE;
    }
    elseif (!empty($this->_params['selectMembership'])) {
      $memFee = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $this->_params['selectMembership'], 'minimum_fee');
      if ($memFee > 0.0) {
        $isMonetary = TRUE;
      }
    }

    // The concept of contributeMode is deprecated.
    // The payment processor object can provide info about the fields it shows.
    if ($isMonetary && is_a($this->_paymentProcessor['object'], 'CRM_Core_Payment')) {
      /** @var  $paymentProcessorObject \CRM_Core_Payment */
      $paymentProcessorObject = $this->_paymentProcessor['object'];

      $paymentFields = $paymentProcessorObject->getPaymentFormFields();
      foreach ($paymentFields as $index => $paymentField) {
        if (!isset($this->_params[$paymentField])) {
          unset($paymentFields[$index]);
          continue;
        }
        if ($paymentField === 'credit_card_exp_date') {
          $date = CRM_Utils_Date::format(CRM_Utils_Array::value('credit_card_exp_date', $this->_params));
          $date = CRM_Utils_Date::mysqlToIso($date);
          $this->assign('credit_card_exp_date', $date);
        }
        elseif ($paymentField === 'credit_card_number') {
          $this->assign('credit_card_number',
            CRM_Utils_System::mungeCreditCard(CRM_Utils_Array::value('credit_card_number', $this->_params))
          );
        }
        elseif ($paymentField === 'credit_card_type') {
          $this->assign('credit_card_type', CRM_Core_PseudoConstant::getLabel(
            'CRM_Core_BAO_FinancialTrxn',
            'card_type_id',
            CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_FinancialTrxn', 'card_type_id', $this->_params['credit_card_type'])
          ));
        }
        else {
          $this->assign($paymentField, $this->_params[$paymentField]);
        }
      }
      $this->assign('paymentFieldsetLabel', CRM_Core_Payment_Form::getPaymentLabel($paymentProcessorObject));
      $this->assign('paymentFields', $paymentFields);

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
   * Add the custom fields.
   *
   * @param int $id
   * @param string $name
   * @param bool $viewOnly
   * @param null $profileContactType
   * @param array $fieldTypes
   */
  public function buildCustom($id, $name, $viewOnly = FALSE, $profileContactType = NULL, $fieldTypes = NULL) {
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
        // @todo replace payment_instrument with payment instrument id.
        // both are available now but the id field is the most consistent.
        'payment_instrument' => 1,
        'payment_instrument_id' => 1,
        'contribution_check_number' => 1,
        'financial_type' => 1,
      );

      $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD, NULL, NULL, FALSE,
        NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL
      );

      if ($fields) {
        // determine if email exists in profile so we know if we need to manually insert CRM-2888, CRM-15067
        foreach ($fields as $key => $field) {
          if (substr($key, 0, 6) == 'email-' &&
              !in_array($profileContactType, array('honor', 'onbehalf'))
          ) {
            $this->_emailExists = TRUE;
            $this->set('emailExists', TRUE);
          }
        }

        if (array_intersect_key($fields, $fieldsToIgnore)) {
          $fields = array_diff_key($fields, $fieldsToIgnore);
          CRM_Core_Session::setStatus(ts('Some of the profile fields cannot be configured for this page.'), ts('Warning'), 'alert');
        }

        //remove common fields only if profile is not configured for onbehalf/honor
        if (!in_array($profileContactType, array('honor', 'onbehalf'))) {
          $fields = array_diff_key($fields, $this->_fields);
        }

        CRM_Core_BAO_Address::checkContactSharedAddressFields($fields, $contactID);
        $addCaptcha = FALSE;
        // fetch file preview when not submitted yet, like in online contribution Confirm and ThankYou page
        $viewOnlyFileValues = empty($profileContactType) ? array() : array($profileContactType => array());
        foreach ($fields as $key => $field) {
          if ($viewOnly &&
            isset($field['data_type']) &&
            $field['data_type'] == 'File' || ($viewOnly && $field['name'] == 'image_URL')
          ) {
            //retrieve file value from submitted values on basis of $profileContactType
            $fileValue = CRM_Utils_Array::value($key, $this->_params);
            if (!empty($profileContactType) && !empty($this->_params[$profileContactType])) {
              $fileValue = CRM_Utils_Array::value($key, $this->_params[$profileContactType]);
            }

            if ($fileValue) {
              $path = CRM_Utils_Array::value('name', $fileValue);
              $fileType = CRM_Utils_Array::value('type', $fileValue);
              $fileValue = CRM_Utils_File::getFileURL($path, $fileType);
            }

            // format custom file value fetched from submitted value
            if ($profileContactType) {
              $viewOnlyFileValues[$profileContactType][$key] = $fileValue;
            }
            else {
              $viewOnlyFileValues[$key] = $fileValue;
            }

            // On viewOnly use-case (as in online contribution Confirm page) we no longer need to set
            // required property because being required file is already uploaded while registration
            $field['is_required'] = FALSE;
          }
          if ($profileContactType) {
            //Since we are showing honoree name separately so we are removing it from honoree profile just for display
            if ($profileContactType == 'honor') {
              $honoreeNamefields = array(
                'prefix_id',
                'first_name',
                'last_name',
                'suffix_id',
                'organization_name',
                'household_name',
              );
              if (in_array($field['name'], $honoreeNamefields)) {
                unset($fields[$field['name']]);
                continue;
              }
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

        if ($profileContactType && count($viewOnlyFileValues[$profileContactType])) {
          $this->assign('viewOnlyPrefixFileValues', $viewOnlyFileValues);
        }
        elseif (count($viewOnlyFileValues)) {
          $this->assign('viewOnlyFileValues', $viewOnlyFileValues);
        }

        if ($addCaptcha && !$viewOnly) {
          $this->enableCaptchaOnForm();
        }
      }
    }
  }

  /**
   * Enable ReCAPTCHA on Contribution form
   */
  protected function enableCaptchaOnForm() {
    $captcha = CRM_Utils_ReCAPTCHA::singleton();
    if ($captcha->hasSettingsAvailable()) {
      $captcha->add($this);
      $this->assign('isCaptcha', TRUE);
    }
  }

  /**
   * Display ReCAPTCHA warning on Contribution form
   */
  protected function displayCaptchaWarning() {
    if (CRM_Core_Permission::check("administer CiviCRM")) {
      $captcha = CRM_Utils_ReCAPTCHA::singleton();
      if (!$captcha->hasSettingsAvailable()) {
        $this->assign('displayCaptchaWarning', TRUE);
      }
    }
  }

  /**
   * Check if ReCAPTCHA has to be added on Contribution form forcefully.
   */
  protected function hasToAddForcefully() {
    $captcha = CRM_Utils_ReCAPTCHA::singleton();
    return $captcha->hasToAddForcefully();
  }

  /**
   * Add onbehalf/honoree profile fields and native module fields.
   *
   * @param int $id
   * @param CRM_Core_Form $form
   */
  public function buildComponentForm($id, $form) {
    if (empty($id)) {
      return;
    }

    $contactID = $this->getContactID();

    foreach (array('soft_credit', 'on_behalf') as $module) {
      if ($module == 'soft_credit') {
        if (empty($form->_values['honoree_profile_id'])) {
          continue;
        }

        if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $form->_values['honoree_profile_id'], 'is_active')) {
          CRM_Core_Error::fatal(ts('This contribution page has been configured for contribution on behalf of honoree and the selected honoree profile is either disabled or not found.'));
        }

        $profileContactType = CRM_Core_BAO_UFGroup::getContactType($form->_values['honoree_profile_id']);
        $requiredProfileFields = array(
          'Individual' => array('first_name', 'last_name'),
          'Organization' => array('organization_name', 'email'),
          'Household' => array('household_name', 'email'),
        );
        $validProfile = CRM_Core_BAO_UFGroup::checkValidProfile($form->_values['honoree_profile_id'], $requiredProfileFields[$profileContactType]);
        if (!$validProfile) {
          CRM_Core_Error::fatal(ts('This contribution page has been configured for contribution on behalf of honoree and the required fields of the selected honoree profile are disabled or doesn\'t exist.'));
        }

        foreach (array('honor_block_title', 'honor_block_text') as $name) {
          $form->assign($name, $form->_values[$name]);
        }

        $softCreditTypes = CRM_Core_OptionGroup::values("soft_credit_type", FALSE);

        // radio button for Honor Type
        foreach ($form->_values['soft_credit_types'] as $value) {
          $honorTypes[$value] = $form->createElement('radio', NULL, NULL, $softCreditTypes[$value], $value);
        }
        $form->addGroup($honorTypes, 'soft_credit_type_id', NULL)->setAttribute('allowClear', TRUE);

        $honoreeProfileFields = CRM_Core_BAO_UFGroup::getFields(
          $this->_values['honoree_profile_id'], FALSE,
          NULL, NULL,
          NULL, FALSE,
          NULL, TRUE,
          NULL, CRM_Core_Permission::CREATE
        );
        $form->assign('honoreeProfileFields', $honoreeProfileFields);

        // add the form elements
        foreach ($honoreeProfileFields as $name => $field) {
          // If soft credit type is not chosen then make omit requiredness from honoree profile fields
          if (count($form->_submitValues) &&
            empty($form->_submitValues['soft_credit_type_id']) &&
            !empty($field['is_required'])
          ) {
            $field['is_required'] = FALSE;
          }
          CRM_Core_BAO_UFGroup::buildProfile($form, $field, CRM_Profile_Form::MODE_CREATE, NULL, FALSE, FALSE, NULL, 'honor');
        }
      }
      else {
        if (empty($form->_values['onbehalf_profile_id'])) {
          continue;
        }

        if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $form->_values['onbehalf_profile_id'], 'is_active')) {
          CRM_Core_Error::fatal(ts('This contribution page has been configured for contribution on behalf of an organization and the selected onbehalf profile is either disabled or not found.'));
        }

        $member = CRM_Member_BAO_Membership::getMembershipBlock($form->_id);
        if (empty($member['is_active'])) {
          $msg = ts('Mixed profile not allowed for on behalf of registration/sign up.');
          $onBehalfProfile = CRM_Core_BAO_UFGroup::profileGroups($form->_values['onbehalf_profile_id']);
          foreach (
            array(
              'Individual',
              'Organization',
              'Household',
            ) as $contactType
          ) {
            if (in_array($contactType, $onBehalfProfile) &&
              (in_array('Membership', $onBehalfProfile) ||
                in_array('Contribution', $onBehalfProfile)
              )
            ) {
              CRM_Core_Error::fatal($msg);
            }
          }
        }

        if ($contactID) {
          // retrieve all permissioned organizations of contact $contactID
          $organizations = CRM_Contact_BAO_Relationship::getPermissionedContacts($contactID, NULL, NULL, 'Organization');

          if (count($organizations)) {
            // Related org url - pass checksum if needed
            $args = array(
              'ufId' => $form->_values['onbehalf_profile_id'],
              'cid' => '',
            );
            if (!empty($_GET['cs'])) {
              $args = array(
                'ufId' => $form->_values['onbehalf_profile_id'],
                'uid' => $this->_contactID,
                'cs' => $_GET['cs'],
                'cid' => '',
              );
            }
            $locDataURL = CRM_Utils_System::url('civicrm/ajax/permlocation', $args, FALSE, NULL, FALSE);
            $form->assign('locDataURL', $locDataURL);
          }
          if (count($organizations) > 0) {
            $form->add('select', 'onbehalfof_id', '', CRM_Utils_Array::collect('name', $organizations));

            $orgOptions = array(
              0 => ts('Select an existing organization'),
              1 => ts('Enter a new organization'),
            );
            $form->addRadio('org_option', ts('options'), $orgOptions);
            $form->setDefaults(array('org_option' => 0));
          }
        }

        $form->assign('fieldSetTitle', ts(CRM_Core_BAO_UFGroup::getTitle($form->_values['onbehalf_profile_id'])));

        if (CRM_Utils_Array::value('is_for_organization', $form->_values)) {
          if ($form->_values['is_for_organization'] == 2) {
            $form->assign('onBehalfRequired', TRUE);
          }
          else {
            $form->addElement('checkbox', 'is_for_organization',
              $form->_values['for_organization'],
              NULL
            );
          }
        }

        $profileFields = CRM_Core_BAO_UFGroup::getFields(
          $form->_values['onbehalf_profile_id'],
          FALSE, CRM_Core_Action::VIEW, NULL,
          NULL, FALSE, NULL, FALSE, NULL,
          CRM_Core_Permission::CREATE, NULL
        );

        $form->assign('onBehalfOfFields', $profileFields);
        if (!empty($form->_submitValues['onbehalf'])) {
          if (!empty($form->_submitValues['onbehalfof_id'])) {
            $form->assign('submittedOnBehalf', $form->_submitValues['onbehalfof_id']);
          }
          $form->assign('submittedOnBehalfInfo', json_encode(str_replace('"', '\"', $form->_submitValues['onbehalf']), JSON_HEX_APOS));
        }

        $fieldTypes = array('Contact', 'Organization');
        if (!empty($form->_membershipBlock)) {
          $fieldTypes = array_merge($fieldTypes, array('Membership'));
        }
        $contactSubType = CRM_Contact_BAO_ContactType::subTypes('Organization');
        $fieldTypes = array_merge($fieldTypes, $contactSubType);

        foreach ($profileFields as $name => $field) {
          if (in_array($field['field_type'], $fieldTypes)) {
            list($prefixName, $index) = CRM_Utils_System::explode('-', $name, 2);
            if (in_array($prefixName, array('organization_name', 'email')) && empty($field['is_required'])) {
              $field['is_required'] = 1;
            }
            if (count($form->_submitValues) &&
              empty($form->_submitValues['is_for_organization']) &&
              $form->_values['is_for_organization'] == 1 &&
              !empty($field['is_required'])
            ) {
              $field['is_required'] = FALSE;
            }
            CRM_Core_BAO_UFGroup::buildProfile($form, $field, NULL, NULL, FALSE, 'onbehalf', NULL, 'onbehalf');
          }
        }
      }
    }

  }

  /**
   * Check template file exists.
   *
   * @param string $suffix
   *
   * @return null|string
   */
  public function checkTemplateFileExists($suffix = NULL) {
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
   * Use the form name to create the tpl file name.
   *
   * @return string
   */
  public function getTemplateFileName() {
    $fileName = $this->checkTemplateFileExists();
    return $fileName ? $fileName : parent::getTemplateFileName();
  }

  /**
   * Add the extra.tpl in.
   *
   * Default extra tpl file basically just replaces .tpl with .extra.tpl
   * i.e. we do not override - why isn't this done at the CRM_Core_Form level?
   *
   * @return string
   */
  public function overrideExtraTemplateFileName() {
    $fileName = $this->checkTemplateFileExists('extra.');
    return $fileName ? $fileName : parent::overrideExtraTemplateFileName();
  }

  /**
   * Authenticate pledge user during online payment.
   */
  public function authenticatePledgeUser() {
    //get the userChecksum and contact id
    $userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this);
    $contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    //get pledge status and contact id
    $pledgeValues = array();
    $pledgeParams = array('id' => $this->_values['pledge_id']);
    $returnProperties = array('contact_id', 'status_id');
    CRM_Core_DAO::commonRetrieve('CRM_Pledge_DAO_Pledge', $pledgeParams, $pledgeValues, $returnProperties);

    //get all status
    $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $validStatus = array(
      array_search('Pending', $allStatus),
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
   * Cancel recurring contributions.
   *
   * In case user cancel recurring contribution,
   * When we get the control back from payment gate way
   * lets delete the recurring and related contribution.
   */
  public function cancelRecurring() {
    $isCancel = CRM_Utils_Request::retrieve('cancel', 'Boolean');
    if ($isCancel) {
      $isRecur = CRM_Utils_Request::retrieve('isRecur', 'Boolean');
      $recurId = CRM_Utils_Request::retrieve('recurId', 'Positive');
      //clean db for recurring contribution.
      if ($isRecur && $recurId) {
        CRM_Contribute_BAO_ContributionRecur::deleteRecurContribution($recurId);
      }
      $contribId = CRM_Utils_Request::retrieve('contribId', 'Positive');
      if ($contribId) {
        CRM_Contribute_BAO_Contribution::deleteContribution($contribId);
      }
    }
  }

  /**
   * Build Membership  Block in Contribution Pages.
   *
   * @param int $cid
   *   Contact checked for having a current membership for a particular membership.
   * @param bool $isContributionMainPage
   *   Is this the main page? If so add form input fields.
   *   (or better yet don't have this functionality in a function shared with forms that don't share it).
   * @param int|array $selectedMembershipTypeID
   *   Selected membership id.
   * @param bool $thankPage
   *   Thank you page.
   * @param null $isTest
   *
   * @return bool
   *   Is this a separate membership payment
   */
  protected function buildMembershipBlock(
    $cid,
    $isContributionMainPage = FALSE,
    $selectedMembershipTypeID = NULL,
    $thankPage = FALSE,
    $isTest = NULL
  ) {

    $separateMembershipPayment = FALSE;
    if ($this->_membershipBlock) {
      $this->_currentMemberships = array();

      $membershipTypeIds = $membershipTypes = $radio = array();
      $membershipPriceset = (!empty($this->_priceSetId) && $this->_useForMember) ? TRUE : FALSE;

      $allowAutoRenewMembership = $autoRenewOption = FALSE;
      $autoRenewMembershipTypeOptions = array();

      $separateMembershipPayment = CRM_Utils_Array::value('is_separate_payment', $this->_membershipBlock);

      if ($membershipPriceset) {
        foreach ($this->_priceSet['fields'] as $pField) {
          if (empty($pField['options'])) {
            continue;
          }
          foreach ($pField['options'] as $opId => $opValues) {
            if (empty($opValues['membership_type_id'])) {
              continue;
            }
            $membershipTypeIds[$opValues['membership_type_id']] = $opValues['membership_type_id'];
          }
        }
      }
      elseif (!empty($this->_membershipBlock['membership_types'])) {
        $membershipTypeIds = explode(',', $this->_membershipBlock['membership_types']);
      }

      if (!empty($membershipTypeIds)) {
        //set status message if wrong membershipType is included in membershipBlock
        if (isset($this->_mid) && !$membershipPriceset) {
          $membershipTypeID = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership',
            $this->_mid,
            'membership_type_id'
          );
          if (!in_array($membershipTypeID, $membershipTypeIds)) {
            CRM_Core_Session::setStatus(ts("Oops. The membership you're trying to renew appears to be invalid. Contact your site administrator if you need assistance. If you continue, you will be issued a new membership."), ts('Invalid Membership'), 'error');
          }
        }

        $membershipTypeValues = CRM_Member_BAO_Membership::buildMembershipTypeValues($this, $membershipTypeIds);
        $this->_membershipTypeValues = $membershipTypeValues;
        $endDate = NULL;

        // Check if we support auto-renew on this contribution page
        // FIXME: If any of the payment processors do NOT support recurring you cannot setup an
        //   auto-renew payment even if that processor is not selected.
        $allowAutoRenewOpt = TRUE;
        if (is_array($this->_paymentProcessors)) {
          foreach ($this->_paymentProcessors as $id => $val) {
            if ($id && !$val['is_recur']) {
              $allowAutoRenewOpt = FALSE;
            }
          }
        }
        foreach ($membershipTypeIds as $value) {
          $memType = $membershipTypeValues[$value];
          if ($selectedMembershipTypeID != NULL) {
            if ($memType['id'] == $selectedMembershipTypeID) {
              $this->assign('minimum_fee',
                CRM_Utils_Array::value('minimum_fee', $memType)
              );
              $this->assign('membership_name', $memType['name']);
              if (!$thankPage && $cid) {
                $membership = new CRM_Member_DAO_Membership();
                $membership->contact_id = $cid;
                $membership->membership_type_id = $memType['id'];
                if ($membership->find(TRUE)) {
                  $this->assign('renewal_mode', TRUE);
                  $memType['current_membership'] = $membership->end_date;
                  $this->_currentMemberships[$membership->membership_type_id] = $membership->membership_type_id;
                }
              }
              $membershipTypes[] = $memType;
            }
          }
          elseif ($memType['is_active']) {

            if ($allowAutoRenewOpt) {
              $javascriptMethod = array('onclick' => "return showHideAutoRenew( this.value );");
              $autoRenewMembershipTypeOptions["autoRenewMembershipType_{$value}"] = (int) $memType['auto_renew'] * CRM_Utils_Array::value($value, CRM_Utils_Array::value('auto_renew', $this->_membershipBlock));
              $allowAutoRenewMembership = TRUE;
            }
            else {
              $javascriptMethod = NULL;
              $autoRenewMembershipTypeOptions["autoRenewMembershipType_{$value}"] = 0;
            }

            //add membership type.
            $radio[$memType['id']] = $this->createElement('radio', NULL, NULL, NULL,
              $memType['id'], $javascriptMethod
            );
            if ($cid) {
              $membership = new CRM_Member_DAO_Membership();
              $membership->contact_id = $cid;
              $membership->membership_type_id = $memType['id'];

              //show current membership, skip pending and cancelled membership records,
              //because we take first membership record id for renewal
              $membership->whereAdd('status_id != 5 AND status_id !=6');

              if (!is_null($isTest)) {
                $membership->is_test = $isTest;
              }

              //CRM-4297
              $membership->orderBy('end_date DESC');

              if ($membership->find(TRUE)) {
                if (!$membership->end_date) {
                  unset($radio[$memType['id']]);
                  $this->assign('islifetime', TRUE);
                  continue;
                }
                $this->assign('renewal_mode', TRUE);
                $this->_currentMemberships[$membership->membership_type_id] = $membership->membership_type_id;
                $memType['current_membership'] = $membership->end_date;
                if (!$endDate) {
                  $endDate = $memType['current_membership'];
                  $this->_defaultMemTypeId = $memType['id'];
                }
                if ($memType['current_membership'] < $endDate) {
                  $endDate = $memType['current_membership'];
                  $this->_defaultMemTypeId = $memType['id'];
                }
              }
            }
            $membershipTypes[] = $memType;
          }
        }
      }

      $this->assign('membershipBlock', $this->_membershipBlock);
      $this->assign('showRadio', $isContributionMainPage);
      $this->assign('membershipTypes', $membershipTypes);
      $this->assign('allowAutoRenewMembership', $allowAutoRenewMembership);
      $this->assign('autoRenewMembershipTypeOptions', json_encode($autoRenewMembershipTypeOptions));
      //give preference to user submitted auto_renew value.
      $takeUserSubmittedAutoRenew = (!empty($_POST) || $this->isSubmitted()) ? TRUE : FALSE;
      $this->assign('takeUserSubmittedAutoRenew', $takeUserSubmittedAutoRenew);

      // Assign autorenew option (0:hide,1:optional,2:required) so we can use it in confirmation etc.
      $autoRenewOption = CRM_Price_BAO_PriceSet::checkAutoRenewForPriceSet($this->_priceSetId);
      //$selectedMembershipTypeID is retrieved as an array for membership priceset if multiple
      //options for different organisation is selected on the contribution page.
      if (is_numeric($selectedMembershipTypeID) && isset($membershipTypeValues[$selectedMembershipTypeID]['auto_renew'])) {
        $this->assign('autoRenewOption', $membershipTypeValues[$selectedMembershipTypeID]['auto_renew']);
      }
      else {
        $this->assign('autoRenewOption', $autoRenewOption);
      }

      if ($isContributionMainPage) {
        if (!$membershipPriceset) {
          if (!$this->_membershipBlock['is_required']) {
            $this->assign('showRadioNoThanks', TRUE);
            $radio[''] = $this->createElement('radio', NULL, NULL, NULL, 'no_thanks', NULL);
            $this->addGroup($radio, 'selectMembership', NULL);
          }
          elseif ($this->_membershipBlock['is_required'] && count($radio) == 1) {
            $temp = array_keys($radio);
            $this->add('hidden', 'selectMembership', $temp[0], array('id' => 'selectMembership'));
            $this->assign('singleMembership', TRUE);
            $this->assign('showRadio', FALSE);
          }
          else {
            $this->addGroup($radio, 'selectMembership', NULL);
          }

          $this->addRule('selectMembership', ts('Please select one of the memberships.'), 'required');
        }

        if ((!$this->_values['is_pay_later'] || is_array($this->_paymentProcessors)) && ($allowAutoRenewMembership || $autoRenewOption)) {
          if ($autoRenewOption == 2) {
            $this->addElement('hidden', 'auto_renew', ts('Please renew my membership automatically.'));
          }
          else {
            $this->addElement('checkbox', 'auto_renew', ts('Please renew my membership automatically.'));
          }
        }

      }
    }

    return $separateMembershipPayment;
  }

  /**
   * Determine if recurring parameters need to be added to the form parameters.
   *
   *  - is_recur
   *  - frequency_interval
   *  - frequency_unit
   *
   * For membership this is based on the membership type.
   *
   * This needs to be done before processing the pre-approval redirect where relevant on the main page or before any payment processing.
   *
   * Arguably the form should start to build $this->_params in the pre-process main page & use that array consistently throughout.
   */
  protected function setRecurringMembershipParams() {
    $selectedMembershipTypeID = CRM_Utils_Array::value('selectMembership', $this->_params);
    if ($selectedMembershipTypeID) {
      // @todo the price_x fields will ALWAYS allow us to determine the membership - so we should ignore
      // 'selectMembership' and calculate from the price_x fields so we have one method that always works
      // this is lazy & only catches when selectMembership is set, but the worst of all worlds would be to fix
      // this with an else (calculate for price set).
      $membershipTypes = CRM_Price_BAO_PriceSet::getMembershipTypesFromPriceSet($this->_priceSetId);
      if (in_array($selectedMembershipTypeID, $membershipTypes['autorenew_required'])
        || (in_array($selectedMembershipTypeID, $membershipTypes['autorenew_optional']) &&
          !empty($this->_params['is_recur']))
      ) {
        $this->_params['auto_renew'] = TRUE;
      }
    }
    if ((!empty($this->_params['selectMembership']) || !empty($this->_params['priceSetId']))
      && !empty($this->_paymentProcessor['is_recur']) &&
      CRM_Utils_Array::value('auto_renew', $this->_params)
      && empty($this->_params['is_recur']) && empty($this->_params['frequency_interval'])
    ) {

      $this->_params['is_recur'] = $this->_values['is_recur'] = 1;
      // check if price set is not quick config
      if (!empty($this->_params['priceSetId']) && !CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_params['priceSetId'], 'is_quick_config')) {
        list($this->_params['frequency_interval'], $this->_params['frequency_unit']) = CRM_Price_BAO_PriceSet::getRecurDetails($this->_params['priceSetId']);
      }
      else {
        // FIXME: set interval and unit based on selected membership type
        $this->_params['frequency_interval'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
          $this->_params['selectMembership'], 'duration_interval'
        );
        $this->_params['frequency_unit'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
          $this->_params['selectMembership'], 'duration_unit'
        );
      }
    }
  }


  /**
   * Get the payment processor object for the submission, returning the manual one for offline payments.
   *
   * @return CRM_Core_Payment
   */
  protected function getPaymentProcessorObject() {
    if (!empty($this->_paymentProcessor)) {
      return $this->_paymentProcessor['object'];
    }
    return new CRM_Core_Payment_Manual();
  }

}
