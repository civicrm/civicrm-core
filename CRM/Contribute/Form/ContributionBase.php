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
 * This class generates form components for processing a contribution.
 */
class CRM_Contribute_Form_ContributionBase extends CRM_Core_Form {
  use CRM_Financial_Form_FrontEndPaymentFormTrait;

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
   *
   * @var bool
   *
   * @deprecated use $this->isSeparateMembershipPayment() function.
   */
  protected $_separateMembershipPayment;

  /**
   * The params submitted by the form and computed by the app
   *
   * @var array
   */
  public $_params = [];

  /**
   * The fields involved in this contribution page
   *
   * @var array
   */
  public $_fields = [];

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
   * @var int
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
   * Contribution mode.
   *
   * In general we are trying to deprecate this parameter but some templates and processors still
   * require it to denote whether the processor redirects offsite (notify) or not.
   *
   * The intent is that this knowledge should not be required and all contributions should
   * be created in a pending state and updated based on the payment result without needing to be
   * aware of the processor workings.
   *
   * @var string
   *
   * @deprecated
   */
  public $_contributeMode;

  /**
   * Contribution page supports memberships
   * @var bool
   */
  public $_useForMember;

  /**
   * @var bool
   * @deprecated
   */
  public $_isBillingAddressRequiredForPayLater;

  /**
   * Flag if email field exists in embedded profile
   *
   * @var bool
   */
  public $_emailExists = FALSE;

  /**
   * Is this a backoffice form.
   *
   * Processors may display different options to backoffice users.
   *
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
   * The contribution ID - is an option in the URL if you are making a payment against an existing contribution (an
   * "invoice payment").
   *
   * @var int
   */
  public $_ccid;

  /**
   * Is the price set quick config.
   * @return bool
   */
  public function isQuickConfig() {
    return self::$_quickConfig ?? FALSE;
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
    $this->_userID = CRM_Core_Session::getLoggedInContactID();

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
      $this->_values = [];
      $this->_fields = [];

      CRM_Contribute_BAO_ContributionPage::setValues($this->_id, $this->_values);
      if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()
        && !CRM_Core_Permission::check('add contributions of type ' . CRM_Contribute_PseudoConstant::financialType($this->_values['financial_type_id']))
      ) {
        CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
      }
      if (empty($this->_values['is_active'])) {
        throw new CRM_Contribute_Exception_InactiveContributionPageException(ts('The page you requested is currently unavailable.'), $this->_id);
      }

      $endDate = CRM_Utils_Date::processDate(CRM_Utils_Array::value('end_date', $this->_values));
      $now = date('YmdHis');
      if ($endDate && $endDate < $now) {
        throw new CRM_Contribute_Exception_PastContributionPageException(ts('The page you requested has past its end date on %1', [1 => CRM_Utils_Date::customFormat($endDate)]), $this->_id);
      }

      $startDate = CRM_Utils_Date::processDate(CRM_Utils_Array::value('start_date', $this->_values));
      if ($startDate && $startDate > $now) {
        throw new CRM_Contribute_Exception_FutureContributionPageException(ts('The page you requested will be active from %1', [1 => CRM_Utils_Date::customFormat($startDate)]), $this->_id);
      }

      $this->assignBillingType();

      // check for is_monetary status
      $isPayLater = $this->_values['is_pay_later'] ?? NULL;
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
      if ($isPayLater) {
        $this->setPayLaterLabel($this->_values['pay_later_text']);
      }

      $this->_paymentProcessorIDs = array_filter(explode(
        CRM_Core_DAO::VALUE_SEPARATOR,
        ($this->_values['payment_processor'] ?? '')
      ));

      $this->assignPaymentProcessor($isPayLater);

      // get price info
      // CRM-5095
      $this->initSet($this);

      // this avoids getting E_NOTICE errors in php
      $setNullFields = [
        'is_allow_other_amount',
        'footer_text',
      ];
      foreach ($setNullFields as $f) {
        if (!isset($this->_values[$f])) {
          $this->_values[$f] = NULL;
        }
      }

      $pledgeBlock = CRM_Pledge_BAO_PledgeBlock::getPledgeBlock($this->_id);

      if ($pledgeBlock) {
        $this->_values['pledge_block_id'] = $pledgeBlock['id'] ?? NULL;
        $this->_values['max_reminders'] = $pledgeBlock['max_reminders'] ?? NULL;
        $this->_values['initial_reminder_day'] = $pledgeBlock['initial_reminder_day'] ?? NULL;
        $this->_values['additional_reminder_day'] = $pledgeBlock['additional_reminder_day'] ?? NULL;

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
          $this->authenticatePledgeUser();
        }
      }
      $this->set('values', $this->_values);
      $this->set('fields', $this->_fields);
    }
    $this->set('membershipBlock', $this->getMembershipBlock());

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

    // @todo - move this check to `getMembershipBlock`
    if (!$this->isFormSupportsNonMembershipContributions() &&
      !$this->_membershipBlock['is_active'] &&
      !$this->_priceSetId
    ) {
      CRM_Core_Error::statusBounce(ts('The requested online contribution page is missing a required Contribution Amount section or Membership section or Price Set. Please check with the site administrator for assistance.'));
    }
    // This can probably go as nothing it 'getting it' anymore since the values data is loaded
    // on every form, rather than being passed from form to form.
    $this->set('amount_block_is_active', $this->isFormSupportsNonMembershipContributions());

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

    $title = !empty($this->_values['frontend_title']) ? $this->_values['frontend_title'] : $this->_values['title'];

    $this->setTitle(($this->_pcpId ? $this->_pcpInfo['title'] : $title));
    $this->_defaults = [];

    $this->_amount = $this->get('amount');
    // Assigning this to the template means it will be passed through to the payment form.
    // This can, for example, by used by payment processors using client side encryption
    $this->assign('currency', $this->getCurrency());

    CRM_Contribute_BAO_Contribution_Utils::overrideDefaultCurrency($this->_values);

    //lets allow user to override campaign.
    $campID = CRM_Utils_Request::retrieve('campID', 'Positive', $this);
    if ($campID && CRM_Core_DAO::getFieldValue('CRM_Campaign_DAO_Campaign', $campID)) {
      $this->_values['campaign_id'] = $campID;
    }

    // check if billing block is required for pay later
    if (!empty($this->_values['is_pay_later'])) {
      $this->_isBillingAddressRequiredForPayLater = $this->_values['is_billing_required'] ?? NULL;
      $this->assign('isBillingAddressRequiredForPayLater', $this->_isBillingAddressRequiredForPayLater);
    }
  }

  /**
   * Initiate price set such that various non-BAO things are set on the form.
   *
   * This function is not really a BAO function so the location is misleading.
   *
   * @param CRM_Core_Form $form
   *   Form entity id.
   *
   * @todo - removed unneeded code from previously-shared function
   */
  private function initSet($form) {
    $priceSetId = CRM_Price_BAO_PriceSet::getFor('civicrm_contribution_page', $this->_id);
    //check if price set is is_config
    if (is_numeric($priceSetId)) {
      if (CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $priceSetId, 'is_quick_config') && $form->getVar('_name') != 'Participant') {
        $form->assign('quickConfig', 1);
      }
    }
    // get price info
    if ($priceSetId) {
      if ($form->_action & CRM_Core_Action::UPDATE) {
        $form->_values['line_items'] = CRM_Price_BAO_LineItem::getLineItems($form->_id, 'contribution');
        $required = FALSE;
      }
      else {
        $required = TRUE;
      }

      $form->_priceSetId = $priceSetId;
      $priceSet = CRM_Price_BAO_PriceSet::getSetDetail($priceSetId, $required);
      $form->_priceSet = $priceSet[$priceSetId] ?? NULL;
      $form->_values['fee'] = $form->_priceSet['fields'] ?? NULL;

      $form->set('priceSetId', $form->_priceSetId);
      $form->set('priceSet', $form->_priceSet);
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
    $vars = [
      'amount',
      'currencyID',
      'credit_card_type',
      'trxn_id',
      'amount_level',
    ];

    if (isset($this->_values['is_recur']) && !empty($this->_paymentProcessor['is_recur'])) {
      $this->assign('is_recur_enabled', 1);
      $vars = array_merge($vars, [
        'is_recur',
        'frequency_interval',
        'frequency_unit',
        'installments',
      ]);
    }

    if (CRM_Core_Component::isEnabled('CiviPledge') &&
      !empty($this->_params['is_pledge'])
    ) {
      // TODO: Assigned variable appears to be unused
      $this->assign('pledge_enabled', 1);

      $vars = array_merge($vars, [
        'is_pledge',
        'pledge_frequency_interval',
        'pledge_frequency_unit',
        'pledge_installments',
      ]);
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
    $this->assignPaymentFields();
    $this->assignEmailField();
    $this->assign('emailExists', $this->_emailExists);

    // also assign the receipt_text
    if (isset($this->_values['receipt_text'])) {
      $this->assign('receipt_text', $this->_values['receipt_text']);
    }
  }

  /**
   * Assign email variable in the template.
   */
  public function assignEmailField() {
    //If email exist in a profile, the default billing email field is not loaded on the page.
    //Hence, assign the existing location type email by iterating through the params.
    if ($this->_emailExists && empty($this->_params["email-{$this->_bltID}"])) {
      foreach ($this->_params as $key => $val) {
        if (substr($key, 0, 6) === 'email-') {
          $this->assign('email', $this->_params[$key]);
          break;
        }
      }
    }
    else {
      $this->assign('email', CRM_Utils_Array::value("email-{$this->_bltID}", $this->_params));
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
      $fieldsToIgnore = [
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
      ];

      $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD, NULL, NULL, FALSE,
        NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL
      );

      if ($fields) {
        // determine if email exists in profile so we know if we need to manually insert CRM-2888, CRM-15067
        foreach ($fields as $key => $field) {
          if (substr($key, 0, 6) == 'email-' &&
              !in_array($profileContactType, ['honor', 'onbehalf'])
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
        if (!in_array($profileContactType, ['honor', 'onbehalf'])) {
          $fields = array_diff_key($fields, $this->_fields);
        }

        CRM_Core_BAO_Address::checkContactSharedAddressFields($fields, $contactID);
        // fetch file preview when not submitted yet, like in online contribution Confirm and ThankYou page
        $viewOnlyFileValues = empty($profileContactType) ? [] : [$profileContactType => []];
        foreach ($fields as $key => $field) {
          if ($viewOnly &&
            isset($field['data_type']) &&
            $field['data_type'] == 'File' || ($viewOnly && $field['name'] == 'image_URL')
          ) {
            //retrieve file value from submitted values on basis of $profileContactType
            $fileValue = $this->_params[$key] ?? NULL;
            if (!empty($profileContactType) && !empty($this->_params[$profileContactType])) {
              $fileValue = $this->_params[$profileContactType][$key] ?? NULL;
            }

            if ($fileValue) {
              $path = $fileValue['name'] ?? NULL;
              $fileType = $fileValue['type'] ?? NULL;
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
              $honoreeNamefields = [
                'prefix_id',
                'first_name',
                'last_name',
                'suffix_id',
                'organization_name',
                'household_name',
              ];
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
        }

        $this->assign($name, $fields);

        if ($profileContactType && count($viewOnlyFileValues[$profileContactType])) {
          $this->assign('viewOnlyPrefixFileValues', $viewOnlyFileValues);
        }
        elseif (count($viewOnlyFileValues)) {
          $this->assign('viewOnlyFileValues', $viewOnlyFileValues);
        }
      }
    }
  }

  /**
   * Assign payment field information to the template.
   *
   * @throws \CRM_Core_Exception
   */
  public function assignPaymentFields() {
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
    if ($isMonetary && $this->_paymentProcessor['object'] instanceof \CRM_Core_Payment) {
      $paymentProcessorObject = $this->_paymentProcessor['object'];
      $this->assign('paymentAgreementTitle', $paymentProcessorObject->getText('agreementTitle', []));
      $this->assign('paymentAgreementText', $paymentProcessorObject->getText('agreementText', []));
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
  }

  /**
   * Add onbehalf/honoree profile fields and native module fields.
   *
   * @param int $id
   * @param CRM_Core_Form $form
   *
   * @throws \CRM_Core_Exception
   */
  public function buildComponentForm($id, $form): void {
    if (empty($id)) {
      return;
    }

    $contactID = $this->getContactID();

    foreach (['soft_credit', 'on_behalf'] as $module) {
      if ($module === 'soft_credit') {
        if (empty($form->_values['honoree_profile_id'])) {
          continue;
        }

        if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $form->_values['honoree_profile_id'], 'is_active')) {
          CRM_Core_Error::statusBounce(ts('This contribution page has been configured for contribution on behalf of honoree and the selected honoree profile is either disabled or not found.'));
        }

        $profileContactType = CRM_Core_BAO_UFGroup::getContactType($form->_values['honoree_profile_id']);
        $requiredProfileFields = [
          'Individual' => ['first_name', 'last_name'],
          'Organization' => ['organization_name', 'email'],
          'Household' => ['household_name', 'email'],
        ];
        $validProfile = CRM_Core_BAO_UFGroup::checkValidProfile($form->_values['honoree_profile_id'], $requiredProfileFields[$profileContactType]);
        if (!$validProfile) {
          CRM_Core_Error::statusBounce(ts('This contribution page has been configured for contribution on behalf of honoree and the required fields of the selected honoree profile are disabled or doesn\'t exist.'));
        }

        foreach (['honor_block_title', 'honor_block_text'] as $name) {
          $form->assign($name, $form->_values[$name]);
        }

        $softCreditTypes = CRM_Core_OptionGroup::values("soft_credit_type", FALSE);

        // radio button for Honor Type
        foreach ($form->_values['soft_credit_types'] as $value) {
          $honorTypes[$value] = $softCreditTypes[$value];
        }
        $form->addRadio('soft_credit_type_id', NULL, $honorTypes, ['allowClear' => TRUE]);

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
          CRM_Core_Error::statusBounce(ts('This contribution page has been configured for contribution on behalf of an organization and the selected onbehalf profile is either disabled or not found.'));
        }

        $member = CRM_Member_BAO_Membership::getMembershipBlock($form->_id);
        if (empty($member['is_active'])) {
          $msg = ts('Mixed profile not allowed for on behalf of registration/sign up.');
          $onBehalfProfile = CRM_Core_BAO_UFGroup::profileGroups($form->_values['onbehalf_profile_id']);
          foreach (
            [
              'Individual',
              'Organization',
              'Household',
            ] as $contactType
          ) {
            if (in_array($contactType, $onBehalfProfile) &&
              (in_array('Membership', $onBehalfProfile) ||
                in_array('Contribution', $onBehalfProfile)
              )
            ) {
              CRM_Core_Error::statusBounce($msg);
            }
          }
        }

        if ($contactID) {
          // retrieve all permissioned organizations of contact $contactID
          $organizations = CRM_Contact_BAO_Relationship::getPermissionedContacts($contactID, NULL, NULL, 'Organization');

          if (count($organizations)) {
            // Related org url - pass checksum if needed
            $args = [
              'ufId' => $form->_values['onbehalf_profile_id'],
              'cid' => '',
            ];
            if (!empty($_GET['cs'])) {
              $args = [
                'ufId' => $form->_values['onbehalf_profile_id'],
                'uid' => $this->_contactID,
                'cs' => $_GET['cs'],
                'cid' => '',
              ];
            }
            $locDataURL = CRM_Utils_System::url('civicrm/ajax/permlocation', $args, FALSE, NULL, FALSE);
            $form->assign('locDataURL', $locDataURL);
          }
          if (count($organizations) > 0) {
            $form->add('select', 'onbehalfof_id', '', CRM_Utils_Array::collect('name', $organizations));

            $orgOptions = [
              0 => ts('Select an existing organization'),
              1 => ts('Enter a new organization'),
            ];
            $form->addRadio('org_option', ts('options'), $orgOptions);
            $form->setDefaults(['org_option' => 0]);
          }
        }

        $form->assign('fieldSetTitle', CRM_Core_BAO_UFGroup::getFrontEndTitle($form->_values['onbehalf_profile_id']));

        if (!empty($form->_values['is_for_organization'])) {
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

        $fieldTypes = ['Contact', 'Organization'];
        if (!empty($form->_membershipBlock)) {
          $fieldTypes = array_merge($fieldTypes, ['Membership']);
        }
        $contactSubType = CRM_Contact_BAO_ContactType::subTypes('Organization');
        $fieldTypes = array_merge($fieldTypes, $contactSubType);

        foreach ($profileFields as $name => $field) {
          if (in_array($field['field_type'], $fieldTypes)) {
            [$prefixName, $index] = CRM_Utils_System::explode('-', $name, 2);
            if (in_array($prefixName, ['organization_name', 'email']) && empty($field['is_required'])) {
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
   * @param string|null $suffix
   *
   * @return string|null
   *   Template file path, else null
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
    return $fileName ?: parent::getTemplateFileName();
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
   *
   * @throws \CRM_Core_Exception
   */
  private function authenticatePledgeUser(): void {
    //get the userChecksum and contact id
    $userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $this);
    $contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    //get pledge status and contact id
    $pledgeValues = [];
    $pledgeParams = ['id' => $this->_values['pledge_id']];
    $returnProperties = ['contact_id', 'status_id'];
    CRM_Core_DAO::commonRetrieve('CRM_Pledge_DAO_Pledge', $pledgeParams, $pledgeValues, $returnProperties);

    //get all status
    $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $validStatus = [
      array_search('Pending', $allStatus),
      array_search('In Progress', $allStatus),
      array_search('Overdue', $allStatus),
    ];

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
      CRM_Core_Error::statusBounce(ts("Oops. It looks like you have an incorrect or incomplete link (URL). Please make sure you've copied the entire link, and try again. Contact the site administrator if this error persists."));
    }

    //check for valid pledge status.
    if (!in_array($pledgeValues['status_id'], $validStatus)) {
      CRM_Core_Error::statusBounce(ts('Oops. You cannot make a payment for this pledge - pledge status is %1.', [1 => CRM_Utils_Array::value($pledgeValues['status_id'], $allStatus)]));
    }
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
    $priceFieldId = array_key_first($this->_values['fee']);
    // Why is this an array in CRM_Contribute_Form_Contribution_Main::submit and a string in CRM_Contribute_Form_Contribution_Confirm::preProcess()?
    if (is_array($this->_params["price_{$priceFieldId}"])) {
      $priceFieldValue = array_key_first($this->_params["price_{$priceFieldId}"]);
    }
    else {
      $priceFieldValue = $this->_params["price_{$priceFieldId}"];
    }
    $selectedMembershipTypeID = $this->_values['fee'][$priceFieldId]['options'][$priceFieldValue]['membership_type_id'] ?? NULL;
    if (!$selectedMembershipTypeID) {
      return;
    }

    // Check if membership the selected membership is automatically opted into auto renew or give user the option.
    // In the 2nd case we check that the user has in deed opted in (auto renew as at June 22 is the field name for the membership auto renew checkbox)
    // Also check that the payment Processor used can support recurring contributions.
    $membershipTypes = CRM_Price_BAO_PriceSet::getMembershipTypesFromPriceSet($this->_priceSetId);
    if (in_array($selectedMembershipTypeID, $membershipTypes['autorenew_required'])
      || (in_array($selectedMembershipTypeID, $membershipTypes['autorenew_optional']) &&
        !empty($this->_params['auto_renew']))
        && !empty($this->_paymentProcessor['is_recur'])
    ) {
      $this->_params['auto_renew'] = TRUE;
      $this->_params['is_recur'] = $this->_values['is_recur'] = 1;
      $membershipTypeDetails = \Civi\Api4\MembershipType::get(FALSE)
        ->addWhere('id', '=', $selectedMembershipTypeID)
        ->execute()
        ->first();
      $this->_params['frequency_interval'] = $this->_params['frequency_interval'] ?? $this->_values['fee'][$priceFieldId]['options'][$priceFieldValue]['membership_num_terms'];
      $this->_params['frequency_unit'] = $this->_params['frequency_unit'] ?? $membershipTypeDetails['duration_unit'];
    }
    elseif (!$this->_separateMembershipPayment && (in_array($selectedMembershipTypeID, $membershipTypes['autorenew_required'])
      || in_array($selectedMembershipTypeID, $membershipTypes['autorenew_optional']))) {
      // otherwise check if we have a separate membership payment setting as that will allow people to independently opt into recurring contributions and memberships
      // If we don't have that and the membership type is auto recur or opt into recur set is_recur to 0.
      $this->_params['is_recur'] = $this->_values['is_recur'] = 0;
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

  /**
   * Get the amount for the main contribution.
   *
   * The goal is to expand this function so that all the argy-bargy of figuring out the amount
   * winds up here as the main spaghetti shrinks.
   *
   * If there is a separate membership contribution this is the 'other one'. Otherwise there
   * is only one.
   *
   * @param $params
   *
   * @return float
   *
   * @throws \CRM_Core_Exception
   */
  protected function getMainContributionAmount($params) {
    if (!empty($params['selectMembership'])) {
      if (empty($params['amount']) && !$this->_separateMembershipPayment) {
        return CRM_Member_BAO_MembershipType::getMembershipType($params['selectMembership'])['minimum_fee'] ?? 0;
      }
    }
    return $params['amount'] ?? 0;
  }

  /**
   * Wrapper for processAmount that also sets autorenew.
   *
   * @param $fields
   *   This is the output of the function CRM_Price_BAO_PriceSet::getSetDetail($priceSetID, FALSE, FALSE);
   *   And, it would make sense to introduce caching into that function and call it from here rather than
   *   require the $fields array which is passed from pillar to post around the form in order to pass it in here.
   * @param array $params
   *   Params reflecting form input e.g with fields 'price_5' => 7, 'price_8' => array(7, 8)
   * @param $lineItems
   *   Line item array to be altered.
   * @param int $priceSetID
   */
  public function processAmountAndGetAutoRenew($fields, &$params, &$lineItems, $priceSetID = NULL) {
    CRM_Price_BAO_PriceSet::processAmount($fields, $params, $lineItems, $priceSetID);
    $autoRenew = [];
    $autoRenew[0] = $autoRenew[1] = $autoRenew[2] = 0;
    foreach ($lineItems as $lineItem) {
      if (!empty($lineItem['auto_renew']) &&
        is_numeric($lineItem['auto_renew'])
      ) {
        $autoRenew[$lineItem['auto_renew']] += $lineItem['line_total'];
      }
    }
    if (count($autoRenew) > 1) {
      $params['autoRenew'] = $autoRenew;
    }
  }

  /**
   * Is payment for (non membership) contributions enabled on this form.
   *
   * This would be true in a case of contributions only or where both
   * memberships and non-membership contributions are enabled (whether they
   * are using quick config price sets or explicit price sets).
   *
   * The value is a database value in the config for the contribution page. It
   * is loaded into values in ContributionBase::preProcess (called by this).
   *
   * @internal function is public to support validate but is for core use only.
   *
   * @return bool
   */
  public function isFormSupportsNonMembershipContributions(): bool {
    return (bool) ($this->_values['amount_block_is_active'] ?? FALSE);
  }

  /**
   * Get the membership block configured for the page, fetching if needed.
   *
   * The membership block is configured memberships are available to purchase via
   * a quick-config price set.
   *
   * @return array|false
   */
  protected function getMembershipBlock() {
    if (!isset($this->_membershipBlock)) {
      //check if Membership Block is enabled, if Membership Fields are included in profile
      //get membership section for this contribution page
      $this->_membershipBlock = CRM_Member_BAO_Membership::getMembershipBlock($this->_id) ?? FALSE;
      $preProfileType = empty($this->_values['custom_pre_id']) ? NULL : CRM_Core_BAO_UFField::getProfileType($this->_values['custom_pre_id']);
      $postProfileType = empty($this->_values['custom_post_id']) ? NULL : CRM_Core_BAO_UFField::getProfileType($this->_values['custom_post_id']);

      if ((($postProfileType === 'Membership') || ($preProfileType === 'Membership')) &&
        !$this->_membershipBlock['is_active']
      ) {
        CRM_Core_Error::statusBounce(ts('This page includes a Profile with Membership fields - but the Membership Block is NOT enabled. Please notify the site administrator.'));
      }
    }
    return $this->_membershipBlock;
  }

  /**
   * Is the contribution page configured for 2 payments, one being membership & one not.
   *
   * @return bool
   */
  protected function isSeparateMembershipPayment(): bool {
    return $this->getMembershipBlock() && $this->getMembershipBlock()['is_separate_payment'];
  }

}
