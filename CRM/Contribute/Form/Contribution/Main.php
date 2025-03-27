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

use Civi\Api4\Membership;

/**
 * This class generates form components for processing a Contribution.
 */
class CRM_Contribute_Form_Contribution_Main extends CRM_Contribute_Form_ContributionBase {

  /**
   * Define default MembershipType Id.
   *
   * @var int
   *
   * @deprecated unused
   */
  public $_defaultMemTypeId;

  public $_paymentProcessors;

  public $_membershipTypeValues;

  /**
   * Array of payment related fields to potentially display on this form (generally credit card or debit card fields). This is rendered via billingBlock.tpl
   * @var array
   */
  public $_paymentFields = [];

  protected $_paymentProcessorID;
  protected $_snippet;

  /**
   * Variable for legacy paypal express implementation.
   *
   * @var string
   *
   * @internal - only to be used by legacy paypal express implementation.
   */
  public $_expressButtonName;

  /**
   * Existing memberships the contact has.
   *
   * @var array
   */
  private $existingMemberships;

  /**
   * @param array $fields
   *
   * @return string|null
   * @throws \CRM_Core_Exception
   */
  protected function getAutoRenewError(array $fields): ?string {
    if (empty($fields['payment_processor_id'])) {
      foreach ($this->getLineItems() as $lineItem) {
        if ($lineItem['auto_renew'] === 2) {
          return ts('You cannot have auto-renewal on if you are paying later.');
        }
      }
    }
    return FALSE;
  }

  /**
   * Get the active UFGroups (profiles) on this form
   * Many forms load one or more UFGroups (profiles).
   * This provides a standard function to retrieve the IDs of those profiles from the form
   * so that you can implement things such as "is is_captcha field set on any of the active profiles on this form?"
   *
   * NOT SUPPORTED FOR USE OUTSIDE CORE EXTENSIONS - Added for reCAPTCHA core extension.
   *
   * @return array
   */
  public function getUFGroupIDs() {
    $ufGroupIDs = [];
    if (!empty($this->_values['custom_pre_id'])) {
      $ufGroupIDs[] = $this->_values['custom_pre_id'];
    }
    if (!empty($this->_values['custom_post_id'])) {
      // custom_post_id can be an array (because we can have multiple for events).
      // It is handled as array for contribution page as well though they don't support multiple profiles.
      if (!is_array($this->_values['custom_post_id'])) {
        $ufGroupIDs[] = $this->_values['custom_post_id'];
      }
      else {
        $ufGroupIDs = array_merge($ufGroupIDs, $this->_values['custom_post_id']);
      }
    }

    return $ufGroupIDs;
  }

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   * @throws \CRM_Contribute_Exception_InactiveContributionPageException
   */
  public function preProcess() {
    parent::preProcess();

    $this->_paymentProcessors = $this->get('paymentProcessors');
    $this->preProcessPaymentOptions();
    // If the in-use payment processor is the Dummy processor we assign the name so that a test warning is displayed.
    $this->assign('dummyTitle', $this->getPaymentProcessorValue('payment_processor_type_id.class') === 'CRM_Dummy' ? $this->getPaymentProcessorValue('payment_processor_type_id.front_end_title') : '');

    $this->assignFormVariablesByContributionID();

    // Make the contributionPageID available to the template
    $this->assign('contributionPageID', $this->_id);
    $this->assign('ccid', $this->_ccid);
    $this->assign('isShare', $this->_values['is_share'] ?? NULL);
    $this->assign('isConfirmEnabled', $this->_values['is_confirm_enabled'] ?? NULL);

    // Required for currency formatting in the JS layer
    // this is a temporary fix intended to resolve a regression quickly
    // And assigning moneyFormat for js layer formatting
    // will only work until that is done.
    // https://github.com/civicrm/civicrm-core/pull/19151
    $this->assign('moneyFormat', CRM_Utils_Money::format(1234.56));

    $this->assign('reset', CRM_Utils_Request::retrieve('reset', 'Boolean'));
    $this->assign('mainDisplay', CRM_Utils_Request::retrieve('_qf_Main_display', 'Boolean',
      CRM_Core_DAO::$_nullObject));

    if (!empty($this->_pcpInfo['id']) && !empty($this->_pcpInfo['intro_text'])) {
      $this->assign('intro_text', $this->_pcpInfo['intro_text']);
    }
    else {
      $this->assign('intro_text', $this->getContributionPageValue('intro_text'));
    }

    $qParams = "reset=1&amp;id={$this->_id}";
    $pcpId = $this->_pcpInfo['pcp_id'] ?? NULL;
    if ($pcpId) {
      $qParams .= "&amp;pcpId={$pcpId}";
    }
    $this->assign('qParams', $qParams);
    $this->assign('footer_text', $this->_values['footer_text'] ?? NULL);
  }

  /**
   * Set the default values.
   */
  public function setDefaultValues() {
    $fields = $this->getProfileCustomFields();
    // Set defaults for custom fields based on their configured default values.
    // Note that these will be overridden further down to those relevant to the
    // specific contact or entity, if one is determined.
    foreach (array_keys($fields) as $customFieldID) {
      if ($customFieldID) {
        CRM_Core_BAO_CustomField::setProfileDefaults($customFieldID, 'custom_' . $customFieldID, $this->_defaults,
          NULL, CRM_Profile_Form::MODE_REGISTER
        );
      }
    }
    // check if the user is registered and we have a contact ID
    $contactID = $this->getContactID();
    if (!empty($contactID)) {
      $billingDefaults = $this->getProfileDefaults('Billing', $contactID);
      $this->_defaults = array_merge($this->_defaults, $billingDefaults);
      $fields = $this->getContactProfileFields();
      CRM_Core_BAO_UFGroup::setProfileDefaults($contactID, $fields, $this->_defaults);
    }

    $balance = $this->getContributionBalance();
    if ($balance) {
      $this->_defaults['total_amount'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($balance);
    }

    /*
     * hack to simplify credit card entry for testing
     *
     * $this->_defaults['credit_card_type']     = 'Visa';
     *         $this->_defaults['amount']               = 168;
     *         $this->_defaults['credit_card_number']   = '4111111111111111';
     *         $this->_defaults['cvv2']                 = '000';
     *         $this->_defaults['credit_card_exp_date'] = array('Y' => date('Y')+1, 'M' => '05');
     *         // hack to simplify direct debit entry for testing
     *         $this->_defaults['account_holder'] = 'Max Müller';
     *         $this->_defaults['bank_account_number'] = '12345678';
     *         $this->_defaults['bank_identification_number'] = '12030000';
     *         $this->_defaults['bank_name'] = 'Bankname';
     */

    //build set default for pledge overdue payment.
    if (!empty($this->_values['pledge_id'])) {
      //used to record completed pledge payment ids used later for honor default
      $completedContributionIds = [];
      $pledgePayments = CRM_Pledge_BAO_PledgePayment::getPledgePayments($this->_values['pledge_id']);

      $paymentAmount = 0;
      $duePayment = FALSE;
      foreach ($pledgePayments as $payId => $value) {
        if ($value['status'] == 'Overdue') {
          $this->_defaults['pledge_amount'][$payId] = 1;
          $paymentAmount += $value['scheduled_amount'];
        }
        elseif (!$duePayment && $value['status'] == 'Pending') {
          $this->_defaults['pledge_amount'][$payId] = 1;
          $paymentAmount += $value['scheduled_amount'];
          $duePayment = TRUE;
        }
      }
      $this->_defaults['price_' . $this->_priceSetId] = $paymentAmount;
    }
    elseif (!empty($this->_values['pledge_block_id'])) {
      //set default to one time contribution.
      $this->_defaults['is_pledge'] = 0;
    }

    // to process Custom data that are appended to URL
    $getDefaults = CRM_Core_BAO_CustomGroup::extractGetParams($this, "'Contact', 'Individual', 'Contribution'");
    $this->_defaults = array_merge($this->_defaults, $getDefaults);

    $config = CRM_Core_Config::singleton();
    // set default country from config if no country set
    if (empty($this->_defaults["billing_country_id-{$this->_bltID}"])) {
      $this->_defaults["billing_country_id-{$this->_bltID}"] = $config->defaultContactCountry;
    }

    // set default state/province from config if no state/province set
    if (empty($this->_defaults["billing_state_province_id-{$this->_bltID}"])) {
      $this->_defaults["billing_state_province_id-{$this->_bltID}"] = $config->defaultContactStateProvince;
    }

    $memtypeID = NULL;
    if ($this->_priceSetId) {
      if ($this->getFormContext() === 'membership') {
        $existingMembershipTypeID = $this->getRenewableMembershipValue('membership_type_id');
        $selectedCurrentMemTypes = [];
        foreach ($this->_priceSet['fields'] as $key => $val) {
          foreach ($val['options'] as $keys => $priceFieldOption) {
            $opMemTypeId = $priceFieldOption['membership_type_id'] ?? NULL;
            $priceFieldName = 'price_' . $priceFieldOption['price_field_id'];
            $priceFieldValue = CRM_Price_BAO_PriceSet::getPriceFieldValueFromURL($this, $priceFieldName);
            if (!empty($priceFieldValue)) {
              CRM_Price_BAO_PriceSet::setDefaultPriceSetField($priceFieldName, $priceFieldValue, $val['html_type'], $this->_defaults);
              // break here to prevent overwriting of default due to 'is_default'
              // option configuration or setting of current membership or
              // membership for related organization.
              // The value sent via URL get's higher priority.
              break;
            }
            if ($existingMembershipTypeID && $existingMembershipTypeID === $priceFieldOption['membership_type_id']
              && !in_array($opMemTypeId, $selectedCurrentMemTypes)
            ) {
              CRM_Price_BAO_PriceSet::setDefaultPriceSetField($priceFieldName, $keys, $val['html_type'], $this->_defaults);
              $memtypeID = $selectedCurrentMemTypes[] = $priceFieldOption['membership_type_id'];
            }
            elseif (!empty($priceFieldOption['is_default']) && (!isset($this->_defaults[$priceFieldName]) ||
              ($val['html_type'] === 'CheckBox' && !isset($this->_defaults[$priceFieldName][$keys])))) {
              CRM_Price_BAO_PriceSet::setDefaultPriceSetField($priceFieldName, $keys, $val['html_type'], $this->_defaults);
            }
          }
        }

        if ($contactID && $existingMembershipTypeID) {
          // Set the default values for any membership custom fields on the page via a profile.
          // Note that this will have been done further up if the contact ID was not determined.
          foreach ($this->_fields as $name => $field) {
            if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
              // check if the custom field is on a membership, we only want to load
              // defaults for membership custom fields here, not contact fields
              if (!CRM_Core_BAO_CustomGroup::checkCustomField($customFieldID, ['Membership'])
              ) {
                CRM_Core_BAO_CustomField::setProfileDefaults($customFieldID, $name, $this->_defaults,
                  $existingMembershipTypeID, CRM_Profile_Form::MODE_REGISTER
                );
              }
            }
          }
        }
      }
      else {
        CRM_Price_BAO_PriceSet::setDefaultPriceSet($this, $this->_defaults);
      }
    }

    //set custom field defaults set by admin if value is not set
    if (!empty($this->_fields)) {
      //load default campaign from page.
      if (array_key_exists('contribution_campaign_id', $this->_fields)) {
        $this->_defaults['contribution_campaign_id'] = $this->_values['campaign_id'] ?? NULL;
      }
    }

    if (!empty($this->_paymentProcessors)) {
      foreach ($this->_paymentProcessors as $pid => $value) {
        if (!empty($value['is_default'])) {
          $this->_defaults['payment_processor_id'] = $pid;
        }
      }
    }

    return $this->_defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    // build profiles first so that we can determine address fields etc
    // and then show copy address checkbox
    if (empty($this->_ccid)) {
      $this->buildCustom($this->_values['custom_pre_id'], 'customPre');
      $this->buildCustom($this->_values['custom_post_id'], 'customPost');

      // CRM-18399: used by template to pass pre profile id as a url arg
      $this->assign('custom_pre_id', $this->_values['custom_pre_id']);

      $this->buildComponentForm();
    }

    // Build payment processor form
    CRM_Core_Payment_ProcessorForm::buildQuickForm($this);

    $config = CRM_Core_Config::singleton();

    $contactID = $this->getContactID();
    $this->assign('contact_id', $contactID);
    if ($contactID) {
      $this->assign('display_name', CRM_Contact_BAO_Contact::displayName($contactID));
    }

    $this->applyFilter('__ALL__', 'trim');
    $this->assign('showMainEmail', (empty($this->_ccid) && $this->_emailExists === FALSE));
    if (empty($this->_ccid)) {
      if ($this->_emailExists == FALSE) {
        $this->add('text', "email-{$this->_bltID}",
          ts('Email Address'),
          ['size' => 30, 'maxlength' => 60, 'class' => 'email'],
          TRUE
        );
        $this->addRule("email-{$this->_bltID}", ts('Email is not valid.'), 'email');
      }
    }
    else {
      $this->addElement('hidden', "email-{$this->_bltID}", 1);
      $this->add('text', 'total_amount', ts('Total Amount'), ['readonly' => TRUE], FALSE);
    }

    $this->addPaymentProcessorFieldsToForm();
    $this->assign('is_pay_later', $this->getCurrentPaymentProcessor() === 0 && $this->_values['is_pay_later']);
    $this->assign('pay_later_text', $this->getCurrentPaymentProcessor() === 0 ? $this->getPayLaterLabel() : NULL);
    $this->assign('nocid', $contactID === 0);
    if ($contactID === 0) {
      $this->addCidZeroOptions();
    }

    //build pledge block.
    //don't build membership block when pledge_id is passed
    if (empty($this->_values['pledge_id']) && empty($this->_ccid)) {
      $this->_separateMembershipPayment = FALSE;
      if (CRM_Core_Component::isEnabled('CiviMember')) {
        $this->_separateMembershipPayment = $this->buildMembershipBlock();
      }
      $this->set('separateMembershipPayment', $this->_separateMembershipPayment);
    }

    $this->assign('useForMember', (int) $this->isMembershipPriceSet());
    // If we configured price set for contribution page
    // we are not allow membership signup as well as any
    // other contribution amount field, CRM-5095
    if (!empty($this->_priceSetId)) {
      $this->add('hidden', 'priceSetId', $this->_priceSetId);
      // build price set form.
      $this->set('priceSetId', $this->_priceSetId);
      if (empty($this->_ccid)) {
        $this->buildPriceSet();
      }
      if ($this->_values['is_monetary'] &&
        $this->_values['is_recur'] && empty($this->_values['pledge_id'])
      ) {
        $this->buildRecur();
      }
    }

    //we allow premium for pledge during pledge creation only.
    if (empty($this->_values['pledge_id']) && empty($this->_ccid)) {
      $this->buildPremiumsBlock(TRUE);
    }

    //don't build pledge block when mid is passed
    if (!$this->getRenewalMembershipID() && empty($this->_ccid)) {
      if (CRM_Core_Component::isEnabled('CiviPledge') && !empty($this->_values['pledge_block_id'])) {
        $this->buildPledgeBlock();
      }
    }

    //to create an cms user
    if (!$this->_contactID && empty($this->_ccid)) {
      $createCMSUser = FALSE;

      if ($this->_values['custom_pre_id']) {
        $profileID = $this->_values['custom_pre_id'];
        $createCMSUser = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $profileID, 'is_cms_user');
      }

      if (!$createCMSUser &&
        $this->_values['custom_post_id']
      ) {
        if (!is_array($this->_values['custom_post_id'])) {
          $profileIDs = [$this->_values['custom_post_id']];
        }
        else {
          $profileIDs = $this->_values['custom_post_id'];
        }
        foreach ($profileIDs as $pid) {
          if (CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $pid, 'is_cms_user')) {
            $profileID = $pid;
            $createCMSUser = TRUE;
            break;
          }
        }
      }

      if ($createCMSUser) {
        CRM_Core_BAO_CMSUser::buildForm($this, $profileID, TRUE);
      }
    }
    if ($this->getPcpID() && empty($this->_ccid)) {
      if (CRM_PCP_BAO_PCP::displayName($this->_pcpId)) {
        $pcp_supporter_text = CRM_PCP_BAO_PCP::getPcpSupporterText($this->_pcpId, $this->_id, 'contribute');
      }
      $prms = ['id' => $this->_pcpId];
      CRM_Core_DAO::commonRetrieve('CRM_PCP_DAO_PCP', $prms, $pcpInfo);
      if ($pcpInfo['is_honor_roll']) {
        $this->add('checkbox', 'pcp_display_in_roll', ts('Show my contribution in the public honor roll'),
          ['onclick' => "showHideByValue('pcp_display_in_roll','','nameID|nickID|personalNoteID','block','radio',false); pcpAnonymous( );"],
          FALSE
        );
        $extraOption = ['onclick' => "return pcpAnonymous( );"];
        $this->addRadio('pcp_is_anonymous', NULL, [ts('Include my name and message'), ts('List my contribution anonymously')], [], '&nbsp;&nbsp;&nbsp;', FALSE, [$extraOption, $extraOption]);

        $this->add('text', 'pcp_roll_nickname', ts('Name'), ['maxlength' => 30]);
        $this->addField('pcp_personal_note', ['entity' => 'ContributionSoft', 'context' => 'create', 'style' => 'height: 3em; width: 40em;']);
      }
    }
    $this->assign('pcpSupporterText', $pcp_supporter_text ?? NULL);
    if (empty($this->_values['fee']) && empty($this->_ccid)) {
      throw new CRM_Core_Exception(ts('This page does not have any price fields configured or you may not have permission for them. Please contact the site administrator for more details.'));
    }

    //we have to load confirm contribution button in template
    //when multiple payment processor as the user
    //can toggle with payment processor selection
    $billingModePaymentProcessors = 0;
    if (!empty($this->_paymentProcessors)) {
      foreach ($this->_paymentProcessors as $key => $values) {
        if ($values['billing_mode'] == CRM_Core_Payment::BILLING_MODE_BUTTON) {
          $billingModePaymentProcessors++;
        }
      }
    }

    if ($billingModePaymentProcessors && count($this->_paymentProcessors) == $billingModePaymentProcessors) {
      $allAreBillingModeProcessors = TRUE;
    }
    else {
      $allAreBillingModeProcessors = FALSE;
    }

    if (!($allAreBillingModeProcessors && !$this->_values['is_pay_later'])) {
      $submitButton = [
        'type' => 'upload',
        'name' => ts('Contribute'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ];
      if (!empty($this->_values['is_confirm_enabled'])) {
        $submitButton['name'] = ts('Review your contribution');
        $submitButton['icon'] = 'fa-chevron-right';
      }
      // Add submit-once behavior when confirm page disabled
      if (empty($this->_values['is_confirm_enabled'])) {
        $this->submitOnce = TRUE;
      }
      //change button name for updating contribution
      if (!empty($this->_ccid)) {
        $submitButton['name'] = ts('Confirm Payment');
      }
      $this->addButtons([$submitButton]);
    }

    $this->addFormRule(['CRM_Contribute_Form_Contribution_Main', 'formRule'], $this);
  }

  /**
   * Build the price set form.
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  private function buildPriceSet() {
    $validPriceFieldIds = array_keys($this->getPriceFieldMetaData());
    $this->assign('priceSet', $this->_priceSet);
    $this->assign('membershipFieldID');

    // @todo - this hook wrangling can be done earlier if we set the form on $this->>order.
    $feeBlock = &$this->_values['fee'];
    // Call the buildAmount hook.
    CRM_Utils_Hook::buildAmount($this->getFormContext(), $this, $feeBlock);

    // CRM-14492 Admin price fields should show up on event registration if user has 'administer CiviCRM' permissions
    $adminFieldVisible = CRM_Core_Permission::check('administer CiviCRM');
    $checklifetime = FALSE;
    foreach ($this->getPriceFieldMetaData() as $id => $field) {
      if ($field['visibility_id:name'] === 'public' ||
        ($field['visibility_id:name'] === 'admin' && $adminFieldVisible)
      ) {
        $options = $field['options'] ?? NULL;
        if ($this->_membershipContactID && $options) {
          $contactsLifetimeMemberships = CRM_Member_BAO_Membership::getAllContactMembership($this->_membershipContactID, FALSE, TRUE);
          $contactsLifetimeMembershipTypes = array_column($contactsLifetimeMemberships, 'membership_type_id');
          $memTypeIdsInPriceField = array_column($options, 'membership_type_id');
          $isCurrentMember = (bool) array_intersect($memTypeIdsInPriceField, $contactsLifetimeMembershipTypes);
          $checklifetime = $checklifetime ?: $isCurrentMember;
        }

        if (!is_array($options) || !in_array($id, $validPriceFieldIds)) {
          continue;
        }
        if (!CRM_Core_Permission::check('edit contributions')) {
          foreach ($options as $key => $currentOption) {
            if ($currentOption['visibility_id:name'] === 'admin') {
              unset($options[$key]);
            }
          }
        }
        if (!empty($options)) {
          $label = (!empty($this->_membershipBlock) && $field['name'] === 'contribution_amount') ? ts('Additional Contribution') : $field['label'];
          $extra = [];
          $fieldID = (int) $field['id'];
          if ($fieldID === $this->getPriceFieldOtherID()) {
            $extra = [
              'onclick' => 'useAmountOther("price_' . $this->getPriceFieldMainID() . '");',
              'autocomplete' => 'off',
            ];
          }
          if ($fieldID === $this->getPriceFieldMainID()) {
            $extra = [
              'onclick' => 'clearAmountOther("price_' . $this->getPriceFieldOtherID() . '");',
            ];
          }

          if (!empty($field['options'])) {
            foreach ($field['options'] as $option) {
              if (!empty($option['membership_type_id.auto_renew'])) {
                $extra += [
                  'onclick' => "return showHideAutoRenew(CRM.$(this).data('membershipTypeId'));",
                ];
                $this->assign('membershipFieldID', $fieldID);
              }
            }
          }

          CRM_Price_BAO_PriceField::addQuickFormElement($this,
            'price_' . $fieldID,
            $field['id'],
            FALSE,
            $field['is_required'] ?? FALSE,
            $label,
            $options,
            [],
            $extra
          );
        }
      }
    }
    $this->assign('hasExistingLifetimeMembership', $checklifetime);
  }

  /**
   * Get the ID of the other amount field if the form is configured to offer it.
   *
   * The other amount field is an alternative to the configured radio options,
   * specific to this form.
   *
   * @return int|null
   */
  private function getPriceFieldOtherID(): ?int {
    if (!$this->isQuickConfig()) {
      return NULL;
    }
    foreach ($this->order->getPriceFieldsMetadata() as $field) {
      if ($field['name'] === 'other_amount') {
        return (int) $field['id'];
      }
    }
    return NULL;
  }

  /**
   * Get the ID of the main amount field if the form is configured to offer an other amount.
   *
   * The other amount field is an alternative to the configured radio options,
   * specific to this form.
   *
   * @return int|null
   */
  private function getPriceFieldMainID(): ?int {
    if (!$this->isQuickConfig() || !$this->getPriceFieldOtherID()) {
      return NULL;
    }
    foreach ($this->order->getPriceFieldsMetadata() as $field) {
      if ($field['name'] === 'contribution_amount') {
        return (int) $field['id'];
      }
    }
    return NULL;
  }

  /**
   * Build Membership  Block in Contribution Pages.
   * @todo this was shared on CRM_Contribute_Form_ContributionBase but we are refactoring and simplifying for each
   *   step (main/confirm/thankyou)
   *
   * @return bool
   *   Is this a separate membership payment
   *
   * @throws \CRM_Core_Exception
   */
  private function buildMembershipBlock(): ?bool {
    $separateMembershipPayment = FALSE;
    $this->addOptionalQuickFormElement('auto_renew');
    $this->addExpectedSmartyVariable('renewal_mode');
    if ($this->_membershipBlock) {
      $membershipTypes = $radio = [];
      // This is always true if this line is reachable - remove along with the upcoming if.
      $membershipPriceset = TRUE;

      $allowAutoRenewMembership = FALSE;
      $autoRenewMembershipTypeOptions = [];

      $separateMembershipPayment = $this->_membershipBlock['is_separate_payment'] ?? NULL;

      $membershipTypeIds = $this->getAvailableMembershipTypeIDs();

      //because we take first membership record id for renewal
      if (!empty($membershipTypeIds)) {
        // @todo = this hook should be called when loading the priceFieldMetadata in preProcess & incorporated
        // There should be function to retrieve rather than property access.
        $membershipTypeValues = CRM_Member_BAO_Membership::buildMembershipTypeValues($this, $membershipTypeIds);
        $this->_membershipTypeValues = $membershipTypeValues;
      }
      $endDate = NULL;

      foreach ($membershipTypeIds as $membershipTypeID) {
        $memType = $membershipTypeValues[$membershipTypeID];
        if ($memType['is_active']) {
          $autoRenewMembershipTypeOptions["autoRenewMembershipType_{$membershipTypeID}"] = $this->getConfiguredAutoRenewOptionForMembershipType($membershipTypeID);
          if ($this->isPageHasPaymentProcessorSupportForRecurring()) {
            $allowAutoRenewMembership = TRUE;
          }
          else {
            $javascriptMethod = NULL;
          }

          //add membership type.
          $radio[$memType['id']] = NULL;
          //show current membership, skip pending and cancelled membership records,
          $membership = $this->getExistingMembership($membershipTypeID);
          if ($membership) {
            if ($membership["membership_type_id.duration_unit:name"] === 'lifetime') {
              unset($radio[$memType['id']]);
              $this->assign('hasExistingLifetimeMembership', TRUE);
              continue;
            }
            $this->define('Membership', 'RenewableMembership', $membership);
            $memType['current_membership'] = $membership['end_date'];
            if (!$endDate) {
              $endDate = $memType['current_membership'];
              $this->_defaultMemTypeId = $memType['id'];
            }
            if ($memType['current_membership'] < $endDate) {
              $endDate = $memType['current_membership'];
              $this->_defaultMemTypeId = $memType['id'];
            }
          }
          $membershipTypes[] = $memType;
        }
      }

      $this->assign('membershipBlock', $this->_membershipBlock);
      $this->assign('showRadio', TRUE);
      $this->assign('renewal_mode', $this->contactHasRenewableMembership());
      $this->assign('membershipTypes', $membershipTypes);
      $this->assign('allowAutoRenewMembership', $allowAutoRenewMembership);
      $this->assign('autoRenewMembershipTypeOptions', json_encode($autoRenewMembershipTypeOptions));
      //give preference to user submitted auto_renew value.
      $takeUserSubmittedAutoRenew = (!empty($_POST) || $this->isSubmitted());
      $this->assign('takeUserSubmittedAutoRenew', $takeUserSubmittedAutoRenew);
      $autoRenewOption = $this->getAutoRenewOption();
      // Assign autorenew option (0:hide,1:optional,2:required) so we can use it in confirmation etc.
      $this->assign('autoRenewOption', $autoRenewOption);

      if ((!$this->_values['is_pay_later'] || is_array($this->_paymentProcessors)) && ($allowAutoRenewMembership || $autoRenewOption)) {
        $this->addElement('checkbox', 'auto_renew', ts('Please renew my membership automatically.'));
      }

    }

    return $separateMembershipPayment;
  }

  /**
   * Build elements to collect information for recurring contributions.
   *
   * Previously shared function.
   */
  private function buildRecur(): void {
    $form = $this;
    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionRecur');

    $form->assign('is_recur_interval', $this->getContributionPageValue('is_recur_interval'));
    $form->assign('is_recur_installments', $this->getContributionPageValue('is_recur_installments'));
    $paymentObject = $this->getPaymentProcessorObject();
    if ($paymentObject) {
      $form->assign('recurringHelpText', $paymentObject->getText('contributionPageRecurringHelp', [
        'is_recur_installments' => !empty($form->_values['is_recur_installments']),
        'is_email_receipt' => !empty($form->_values['is_email_receipt']),
      ]));
    }

    $frUnits = $form->_values['recur_frequency_unit'] ?? NULL;
    $frequencyUnits = CRM_Core_OptionGroup::values('recur_frequency_units', FALSE, FALSE, TRUE);

    $unitVals = explode(CRM_Core_DAO::VALUE_SEPARATOR, $frUnits);

    // FIXME: Ideally we should freeze select box if there is only
    // one option but looks there is some problem /w QF freeze.
    //if ( count( $units ) == 1 ) {
    //$frequencyUnit->freeze( );
    //}

    $form->add('text', 'installments', ts('installments'),
      $attributes['installments'] + ['class' => 'two']
    );
    $form->addRule('installments', ts('Number of installments must be a whole number.'), 'integer');

    $is_recur_label = ts('I want to contribute this amount every');

    // CRM 10860, display text instead of a dropdown if there's only 1 frequency unit
    if (count($unitVals) == 1) {
      $form->assign('one_frequency_unit', TRUE);
      $form->add('hidden', 'frequency_unit', $unitVals[0]);
      if (!empty($form->_values['is_recur_interval'])) {
        $unit = CRM_Contribute_BAO_Contribution::getUnitLabelWithPlural($unitVals[0]);
        $form->assign('frequency_unit', $unit);
      }
      else {
        $is_recur_label = ts('I want to contribute this amount every %1',
          [1 => $frequencyUnits[$unitVals[0]]]
        );
        $form->assign('all_text_recur', TRUE);
      }
    }
    else {
      $form->assign('one_frequency_unit', FALSE);
      $units = [];
      foreach ($unitVals as $key => $val) {
        if (array_key_exists($val, $frequencyUnits)) {
          $units[$val] = $frequencyUnits[$val];
          if (!empty($form->_values['is_recur_interval'])) {
            $units[$val] = CRM_Contribute_BAO_Contribution::getUnitLabelWithPlural($val);
            $unit = ts('Every');
          }
        }
      }
      $frequencyUnit = &$form->add('select', 'frequency_unit', NULL, $units, FALSE, ['aria-label' => ts('Frequency Unit'), 'class' => 'crm-select2 eight']);
    }

    if (!empty($form->_values['is_recur_interval'])) {
      $form->add('text', 'frequency_interval', $unit, $attributes['frequency_interval'] + ['aria-label' => ts('Every'), 'class' => 'two']);
      $form->addRule('frequency_interval', ts('Frequency must be a whole number (EXAMPLE: Every 3 months).'), 'integer');
    }
    else {
      // make sure frequency_interval is submitted as 1 if given no choice to user.
      $form->add('hidden', 'frequency_interval', 1);
    }

    $form->add('checkbox', 'is_recur', $is_recur_label, NULL);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param \CRM_Contribute_Form_Contribution_Main $self
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    foreach ($fields as $key => $field) {
      $fields[$key] = $self->getUnLocalizedSubmittedValue($key, $field);
    }
    $self->resetOrder($fields);
    $errors = array_filter(['auto_renew' => $self->getAutoRenewError($fields)]);
    // @todo - should just be $this->getOrder()->getTotalAmount()
    $amount = $self->computeAmount($fields, $self->_values);

    if ((!empty($fields['selectMembership']) &&
        $fields['selectMembership'] !== 'no_thanks'
      ) ||
      (!empty($fields['priceSetId']) &&
        $self->_useForMember
      )
    ) {

      // appears to be unreachable - selectMembership never set...
      $lifeMember = CRM_Member_BAO_Membership::getAllContactMembership($self->_membershipContactID, $self->isTest(), TRUE);

      $membershipOrgDetails = CRM_Member_BAO_MembershipType::getAllMembershipTypes();
      $unallowedOrgs = [];
      foreach (array_keys($lifeMember) as $memTypeId) {
        $unallowedOrgs[] = $membershipOrgDetails[$memTypeId]['member_of_contact_id'];
      }
    }

    //check for atleast one pricefields should be selected
    if (!empty($fields['priceSetId']) && empty($self->_ccid)) {
      $priceField = new CRM_Price_DAO_PriceField();
      $priceField->price_set_id = $fields['priceSetId'];
      $priceField->orderBy('weight');
      $priceField->find();

      $check = [];
      $membershipIsActive = TRUE;
      $previousId = $otherAmount = FALSE;
      while ($priceField->fetch()) {

        if ($self->isQuickConfig() && ($priceField->name === 'contribution_amount' || $priceField->name === 'membership_amount')) {
          $previousId = $priceField->id;
          if ($priceField->name === 'membership_amount' && !$priceField->is_active) {
            $membershipIsActive = FALSE;
          }
        }
        if ($priceField->name === 'other_amount') {
          if ($self->isQuickConfig() && empty($fields["price_{$priceField->id}"]) &&
            array_key_exists("price_{$previousId}", $fields) && isset($fields["price_{$previousId}"]) && $self->_values['fee'][$previousId]['name'] == 'contribution_amount' && empty($fields["price_{$previousId}"])
          ) {
            $otherAmount = $priceField->id;
          }
          elseif (!empty($fields["price_{$priceField->id}"])) {
            $otherAmountVal = $fields["price_{$priceField->id}"];
            $min = $self->_values['min_amount'] ?? NULL;
            $max = $self->_values['max_amount'] ?? NULL;
            if ($min && $otherAmountVal < $min) {
              $errors["price_{$priceField->id}"] = ts('Contribution amount must be at least %1',
                [1 => $min]
              );
            }
            if ($max && $otherAmountVal > $max) {
              $errors["price_{$priceField->id}"] = ts('Contribution amount cannot be more than %1.',
                [1 => $max]
              );
            }
          }
        }
        if (!empty($fields["price_{$priceField->id}"]) || ($previousId == $priceField->id && isset($fields["price_{$previousId}"])
            && empty($fields["price_{$previousId}"]))
        ) {
          $check[] = $priceField->id;
        }
      }

      // CRM-12233
      if ($membershipIsActive && empty($self->_membershipBlock['is_required'])
        && $self->isFormSupportsNonMembershipContributions()
      ) {
        $membershipFieldId = $contributionFieldId = $errorKey = $otherFieldId = NULL;
        foreach ($self->_values['fee'] as $fieldKey => $fieldValue) {
          // if 'No thank you' membership is selected then set $membershipFieldId
          if ($fieldValue['name'] == 'membership_amount' && ($fields['price_' . $fieldKey] ?? NULL) == 0) {
            $membershipFieldId = $fieldKey;
          }
          elseif ($membershipFieldId) {
            if ($fieldValue['name'] == 'other_amount') {
              $otherFieldId = $fieldKey;
            }
            elseif ($fieldValue['name'] == 'contribution_amount') {
              $contributionFieldId = $fieldKey;
            }

            if (!$errorKey || ($fields['price_' . $contributionFieldId] ?? NULL) == '0') {
              $errorKey = $fieldKey;
            }
          }
        }
        // $membershipFieldId is set and additional amount is 'No thank you' or NULL then throw error
        if ($membershipFieldId && !(($fields["price_$contributionFieldId"] ?? -1) > 0) && empty($fields['price_' . $otherFieldId])) {
          $errors["price_$errorKey"] = ts('Additional Contribution is required.');
        }
      }
      if (empty($check) && empty($self->_ccid)) {
        if ($self->_useForMember == 1 && $membershipIsActive) {
          $errors['_qf_default'] = ts('Select at least one option from Membership Type(s).');
        }
        else {
          $errors['_qf_default'] = ts('Select at least one option from Contribution(s).');
        }
      }
      if ($otherAmount && !empty($check)) {
        $errors["price_$otherAmount"] = ts('Amount is required field.');
      }

      // @todo - this should probably be $this->getFormContext() === 'membership'
      // which would make it apply to quick config & non quick config.
      // See https://lab.civicrm.org/dev/core/-/issues/3314
      if ($self->isMembershipPriceSet() && !empty($check) && $membershipIsActive) {
        $priceFieldIDS = [];
        $priceFieldMemTypes = [];

        foreach ($self->_priceSet['fields'] as $priceId => $value) {
          if (!empty($fields["price_$priceId"]) || ($self->isQuickConfig() && $value['name'] === 'membership_amount' && empty($self->_membershipBlock['is_required']))) {
            if (!empty($fields["price_$priceId"]) && is_array($fields["price_$priceId"])) {
              foreach ($fields["price_$priceId"] as $priceFldVal => $isSet) {
                if ($isSet) {
                  $priceFieldIDS[] = $priceFldVal;
                }
              }
            }
            elseif (!$value['is_enter_qty'] && !empty($fields["price_$priceId"])) {
              // The check for {!$value['is_enter_qty']} is done since, quantity fields allow entering
              // quantity. And the quantity can't be conisdered as civicrm_price_field_value.id, CRM-9577
              $priceFieldIDS[] = $fields["price_$priceId"];
            }

            if (!empty($value['options'])) {
              foreach ($value['options'] as $val) {
                if (!empty($val['membership_type_id']) && (
                    ($fields["price_$priceId"] == $val['id']) ||
                    (isset($fields["price_$priceId"]) && !empty($fields["price_$priceId"][$val['id']]))
                  )
                ) {
                  $priceFieldMemTypes[] = $val['membership_type_id'];
                }
              }
            }
          }
        }

        if (!empty($lifeMember)) {
          foreach ($priceFieldIDS as $priceFieldId) {
            if (($id = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $priceFieldId, 'membership_type_id')) &&
              in_array($membershipOrgDetails[$id]['member_of_contact_id'], $unallowedOrgs)
            ) {
              $errors['_qf_default'] = ts('You already have a lifetime membership and cannot select a membership with a shorter term.');
              break;
            }
          }
        }

        if (!empty($priceFieldIDS)) {
          $ids = implode(',', $priceFieldIDS);

          $priceFieldIDS['id'] = $fields['priceSetId'];
          $self->set('memberPriceFieldIDS', $priceFieldIDS);
          $count = CRM_Price_BAO_PriceSet::getMembershipCount($ids);
          foreach ($count as $id => $occurrence) {
            if ($occurrence > 1) {
              $errors['_qf_default'] = ts('You have selected multiple memberships for the same organization or entity. Please review your selections and choose only one membership per entity. Contact the site administrator if you need assistance.');
              break;
            }
          }
        }

        if (empty($priceFieldMemTypes) && $self->_membershipBlock['is_required'] == 1) {
          $errors['_qf_default'] = ts('Please select at least one membership option.');
        }
      }

      $amount = $self->getOrder()->getTotalAmount();
      $minAmt = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $fields['priceSetId'], 'min_amount');
      if ($amount < 0) {
        $errors['_qf_default'] = ts('Contribution can not be less than zero. Please select the options accordingly');
      }
      elseif (!empty($minAmt) && $amount < $minAmt) {
        $errors['_qf_default'] = ts('A minimum amount of %1 should be selected from Contribution(s).', [
          1 => CRM_Utils_Money::format($minAmt),
        ]);
      }
    }

    if (isset($fields['selectProduct']) &&
      $fields['selectProduct'] !== 'no_thanks'
    ) {
      $productDAO = new CRM_Contribute_DAO_Product();
      $productDAO->id = $fields['selectProduct'];
      $productDAO->find(TRUE);
      $min_amount = $productDAO->min_contribution;

      if ($amount < $min_amount) {
        $errors['selectProduct'] = ts('The premium you have selected requires a minimum contribution of %1', [1 => CRM_Utils_Money::format($min_amount)]);
        CRM_Core_Session::setStatus($errors['selectProduct']);
      }
    }

    //CRM-16285 - Function to handle validation errors on form, for recurring contribution field.
    CRM_Contribute_BAO_ContributionRecur::validateRecurContribution($fields, $files, $self, $errors);

    if (!empty($fields['is_recur']) && empty($fields['payment_processor_id'])) {
      $errors['_qf_default'] = ts('You cannot set up a recurring contribution if you are not paying online by credit card.');
    }

    // validate PCP fields - if not anonymous, we need a nick name value
    if ($self->_pcpId && !empty($fields['pcp_display_in_roll']) &&
      empty($fields['pcp_is_anonymous']) &&
      ($fields['pcp_roll_nickname'] ?? NULL) == ''
    ) {
      $errors['pcp_roll_nickname'] = ts('Please enter a name to include in the Honor Roll, or select \'contribute anonymously\'.');
    }

    // return if this is express mode
    $config = CRM_Core_Config::singleton();
    if ($self->_paymentProcessor &&
      (int) $self->_paymentProcessor['billing_mode'] & CRM_Core_Payment::BILLING_MODE_BUTTON
    ) {
      if (!empty($fields[$self->_expressButtonName . '_x']) || !empty($fields[$self->_expressButtonName . '_y']) ||
        !empty($fields[$self->_expressButtonName])
      ) {
        return $errors;
      }
    }

    //validate the pledge fields.
    if (!empty($self->_values['pledge_block_id'])) {
      //validation for pledge payment.
      if (!empty($self->_values['pledge_id'])) {
        if (empty($fields['pledge_amount'])) {
          $errors['pledge_amount'] = ts('At least one payment option needs to be checked.');
        }
      }
      elseif (!empty($fields['is_pledge'])) {
        if (!isset($fields['pledge_installments'])) {
          $errors['pledge_installments'] = ts('Pledge Installments is required field.');
        }
        elseif (!CRM_Utils_Rule::positiveInteger($fields['pledge_installments'])) {
          $errors['pledge_installments'] = ts('Please enter a valid number of pledge installments.');
        }
        elseif ($fields['pledge_installments'] == 1) {
          $errors['pledge_installments'] = ts('Pledges consist of multiple scheduled payments. Select one-time contribution if you want to make your gift in a single payment.');
        }
        elseif (!$fields['pledge_installments']) {
          $errors['pledge_installments'] = ts('Pledge Installments field must be > 1.');
        }

        //validation for Pledge Frequency Interval.
        if (!isset($fields['pledge_frequency_interval'])) {
          $errors['pledge_frequency_interval'] = ts('Pledge Frequency Interval. is required field.');
        }
        elseif (!CRM_Utils_Rule::positiveInteger($fields['pledge_frequency_interval'])) {
          $errors['pledge_frequency_interval'] = ts('Please enter a valid Pledge Frequency Interval.');
        }
        elseif (!$fields['pledge_frequency_interval']) {
          $errors['pledge_frequency_interval'] = ts('Pledge frequency interval field must be > 0');
        }
      }
    }

    // if the user has chosen a free membership or the amount is less than zero
    // i.e. we don't need to validate payment related fields or profiles.
    if ((float) $amount <= 0.0) {
      return $errors;
    }

    if (!isset($fields['payment_processor_id'])) {
      $errors['payment_processor_id'] = ts('Payment Method is a required field.');
    }
    else {
      CRM_Core_Payment_Form::validatePaymentInstrument(
        $fields['payment_processor_id'],
        $fields,
        $errors,
        (!$self->_isBillingAddressRequiredForPayLater ? NULL : 'billing')
      );
    }

    foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
      $greetingType = $fields[$greeting] ?? NULL;
      if ($greetingType) {
        $customizedValue = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Contact', $greeting . '_id', 'Customized');
        if ($customizedValue == $greetingType && empty($fielse[$greeting . '_custom'])) {
          $errors[$greeting . '_custom'] = ts('Custom %1 is a required field if %1 is of type Customized.',
            [1 => ucwords(str_replace('_', " ", $greeting))]
          );
        }
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Compute amount to be paid.
   *
   * @param array $params
   * @param array $formValues
   *
   * @return int|mixed|null|string
   */
  private function computeAmount($params, $formValues) {
    $amount = 0;

    if (($params['amount'] ?? NULL) == 'amount_other_radio' || !empty($params['amount_other'])) {
      // @todo - probably unreachable - field would be (e.) price_12 now....
      $amount = $params['amount_other'];
    }
    elseif (!empty($params['pledge_amount'])) {
      foreach ($params['pledge_amount'] as $paymentId => $dontCare) {
        // @todo - why would this be a good thing? Is it reachable.
        $amount += CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_PledgePayment', $paymentId, 'scheduled_amount');
      }
    }
    else {
      if (!empty($formValues['amount'])) {
        // @todo - probably unreachable.
        $amountID = $params['amount'] ?? NULL;

        if ($amountID) {
          $amount = $formValues[$amountID]['value'] ?? NULL;
        }
      }
    }
    return $amount;
  }

  /**
   * Process the form submission.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    // we first reset the confirm page so it accepts new values
    $this->controller->resetPage('Confirm');
    // Update order to the submitted values (in case the back button has been used
    // and the submitted values have changed.
    // This aleady happens in validate so might be overkill.
    $this->resetOrder($this->getSubmittedValues());

    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);
    $this->submit($params);

    if (empty($this->_values['is_confirm_enabled'])) {
      $this->skipToThankYouPage();
    }

  }

  /**
   * Submit function.
   *
   * This is the guts of the postProcess made also accessible to the test suite.
   *
   * @param array $params
   *   Submitted values.
   *
   * @throws \CRM_Core_Exception
   */
  public function submit($params) {
    //carry campaign from profile.
    if (array_key_exists('contribution_campaign_id', $params)) {
      $params['campaign_id'] = $params['contribution_campaign_id'];
    }

    $params['currencyID'] = CRM_Core_Config::singleton()->defaultCurrency;

    if ($this->isQuickConfig()) {
      // @todo - this is silly cruft - we can likely remove it.
      $priceField = new CRM_Price_DAO_PriceField();
      $priceField->price_set_id = $this->getPriceSetID();
      $priceField->orderBy('weight');
      $priceField->find();

      $priceOptions = [];
      while ($priceField->fetch()) {
        CRM_Price_BAO_PriceFieldValue::getValues($priceField->id, $priceOptions);
        $selectedPriceOptionID = $params["price_{$priceField->id}"] ?? NULL;
        if ($selectedPriceOptionID && $selectedPriceOptionID > 0) {
          switch ($priceField->name) {
            case 'membership_amount':
              $this->_params['selectMembership'] = $params['selectMembership'] = $priceOptions[$selectedPriceOptionID]['membership_type_id'] ?? NULL;
              $this->set('selectMembership', $params['selectMembership']);
              break;

            case 'other_amount':
              // Only used now when deciding whether to assign
              // amount_level to the template in subsequent screens.
              $params['amount_other'] = $selectedPriceOptionID;
              break;
          }
        }
      }
    }

    $params['amount'] = $this->getMainContributionAmount();
    $this->set('amount_level', $this->order->getAmountLevel());
    if (!empty($this->_ccid)) {
      // @todo - verify that this is the same as `$this->>getLineItems()` which it should be & consolidate
      $this->set('lineItem', [$this->getPriceSetID() => $this->getExistingContributionLineItems()]);
    }
    else {
      if ($this->_membershipBlock) {
        $this->processAmountAndGetAutoRenew($params);
      }
      $this->set('lineItem', [$this->getPriceSetID() => $this->getLineItems()]);
    }

    if ($params['amount'] != 0 && (($this->_values['is_pay_later'] &&
          empty($this->_paymentProcessor) &&
          !array_key_exists('hidden_processor', $params)) ||
        empty($params['payment_processor_id']))
    ) {
      $params['is_pay_later'] = 1;
    }
    else {
      $params['is_pay_later'] = 0;
    }

    // Would be nice to someday understand the point of this set.
    $this->set('is_pay_later', $params['is_pay_later']);
    $this->set('amount', $this->getMainContributionAmount());

    // generate and set an invoiceID for this transaction
    $invoiceID = bin2hex(random_bytes(16));
    $this->set('invoiceID', $invoiceID);
    $params['invoiceID'] = $invoiceID;
    $title = !empty($this->_values['frontend_title']) ? $this->_values['frontend_title'] : $this->_values['title'];
    $params['description'] = ts('Online Contribution') . ': ' . ((!empty($this->_pcpInfo['title']) ? $this->_pcpInfo['title'] : $title));
    $params['button'] = $this->controller->getButtonName();
    // required only if is_monetary and valid positive amount
    if ($this->_values['is_monetary'] &&
      !empty($this->_paymentProcessor) &&
      ((float) $params['amount'] > 0.0 || $this->hasSeparateMembershipPaymentAmount($params))
    ) {
      // The concept of contributeMode is deprecated - as should be the 'is_monetary' setting.
      $this->setContributeMode();
      // Really this setting of $this->_params & params within it should be done earlier on in the function
      // probably the values determined here should be reused in confirm postProcess as there is no opportunity to alter anything
      // on the confirm page. However as we are dealing with a stable release we go as close to where it is used
      // as possible.
      // In general the form has a lack of clarity of the logic of why things are set on the form in some cases &
      // the logic around when $this->_params is used compared to other params arrays.
      $this->_params = array_merge($params, $this->_params);
      $this->setRecurringMembershipParams();
      if ($this->_paymentProcessor &&
        $this->_paymentProcessor['object']->supports('preApproval')
      ) {
        $this->handlePreApproval($this->_params);
      }
    }
  }

  /**
   * Assign the billing mode to the template.
   *
   * This is required for legacy support for contributeMode in templates.
   *
   * The goal is to remove this parameter & use more relevant parameters.
   */
  protected function setContributeMode() {
    switch ($this->_paymentProcessor['billing_mode']) {
      case CRM_Core_Payment::BILLING_MODE_FORM:
        $this->set('contributeMode', 'direct');
        break;

      case CRM_Core_Payment::BILLING_MODE_BUTTON:
        $this->set('contributeMode', 'express');
        break;

      case CRM_Core_Payment::BILLING_MODE_NOTIFY:
        $this->set('contributeMode', 'notify');
        break;
    }

  }

  /**
   * Process confirm function and pass browser to the thank you page.
   */
  protected function skipToThankYouPage() {
    // call the post process hook for the main page before we switch to confirm
    $this->postProcessHook();

    // build the confirm page
    $confirmForm = &$this->controller->_pages['Confirm'];
    $confirmForm->buildForm();

    // the confirmation page is valid
    $data = &$this->controller->container();
    $data['valid']['Confirm'] = 1;

    // confirm the contribution
    // mainProcess calls the hook also
    $confirmForm->mainProcess();
    $qfKey = $this->controller->_key;

    // redirect to thank you page
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/transact', "_qf_ThankYou_display=1&qfKey=$qfKey", TRUE, NULL, FALSE));
  }

  /**
   * Set form variables if contribution ID is found
   */
  public function assignFormVariablesByContributionID(): void {
    $this->assign('isPaymentOnExistingContribution', (bool) $this->getExistingContributionID());
    $this->assign('pendingAmount', $this->getContributionBalance());
    if (empty($this->getExistingContributionID())) {
      return;
    }

    $this->assign('taxAmount', $this->getContributionValue('tax_amount'));
    $this->assign('taxTerm', Civi::settings()->get('tax_term'));

    $lineItems = $this->getExistingContributionLineItems();
    $this->assign('lineItem', [$this->getPriceSetID() => $lineItems]);
    $this->assign('priceSetID', $this->getPriceSetID());
    $this->assign('is_quick_config', $this->isQuickConfig());
  }

  /**
   * Get the balance amount if an existing contribution is being paid.
   *
   * @return float|null
   *
   * @throws \CRM_Core_Exception
   */
  private function getContributionBalance(): ?float {
    if (empty($this->getExistingContributionID())) {
      return NULL;
    }
    if (!$this->getContactID()) {
      CRM_Core_Error::statusBounce(ts('Returning since there is no contact attached to this contribution id.'));
    }
    if ($this->getContributionValue('contribution_status_id:name') === 'Cancelled') {
      throw new CRM_Core_Exception(ts('Sorry, this contribution has been cancelled.'));
    }

    $paymentBalance = CRM_Contribute_BAO_Contribution::getContributionBalance($this->_ccid);
    //bounce if the contribution is not pending.
    if ((float) $paymentBalance <= 0) {
      CRM_Core_Error::statusBounce(ts('Returning since contribution has already been handled.'));
    }
    return $paymentBalance;
  }

  /**
   * Function for unit tests on the postProcess function.
   *
   * @deprecated - we are ditching this approach in favour of 'full form flow'
   * = ie simulating postProcess.
   *
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmit($params) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $this->controller = new CRM_Contribute_Controller_Contribution();
    $this->submit($params);
  }

  /**
   * Has a separate membership payment amount been configured.
   *
   * @param array $params
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  protected function hasSeparateMembershipPaymentAmount($params) {
    return $this->_separateMembershipPayment && (int) CRM_Member_BAO_MembershipType::getMembershipType($params['selectMembership'])['minimum_fee'];
  }

  /**
   * Get the loaded payment processor - the default for the form.
   *
   * If the form is using 'pay later' then the value for the manual
   * pay later processor is 0.
   *
   * @return int|null
   */
  protected function getCurrentPaymentProcessor(): ?int {
    $pps = $this->getProcessors();
    if (!empty($pps) && count($pps) === 1) {
      $ppKeys = array_keys($pps);
      return array_pop($ppKeys);
    }
    // It seems like this might be un=reachable as there should always be a processor...
    return NULL;
  }

  /**
   * Add onbehalf/honoree profile fields and native module fields.
   *
   * @throws \CRM_Core_Exception
   */
  private function buildComponentForm(): void {

    foreach (['soft_credit', 'on_behalf'] as $module) {
      if ($module === 'soft_credit') {
        $this->addSoftCreditFields();
      }
      else {
        $this->addOnBehalfFields();
      }
    }

  }

  /**
   * Add soft credit fields.
   *
   * @throws \CRM_Core_Exception
   */
  private function addSoftCreditFields(): void {
    if (!empty($this->_values['honoree_profile_id'])) {
      if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->_values['honoree_profile_id'], 'is_active')) {
        CRM_Core_Error::statusBounce(ts('This contribution page has been configured for contribution on behalf of honoree and the selected honoree profile is either disabled or not found.'));
      }

      $profileContactType = CRM_Core_BAO_UFGroup::getContactType($this->_values['honoree_profile_id']);
      $requiredProfileFields = [
        'Individual' => ['first_name', 'last_name'],
        'Organization' => ['organization_name', 'email'],
        'Household' => ['household_name', 'email'],
      ];
      $validProfile = CRM_Core_BAO_UFGroup::checkValidProfile($this->_values['honoree_profile_id'], $requiredProfileFields[$profileContactType]);
      if (!$validProfile) {
        CRM_Core_Error::statusBounce(ts('This contribution page has been configured for contribution on behalf of honoree and the required fields of the selected honoree profile are disabled or doesn\'t exist.'));
      }

      $softCreditTypes = CRM_Core_OptionGroup::values("soft_credit_type", FALSE);

      // radio button for Honor Type
      foreach ($this->_values['soft_credit_types'] as $value) {
        $honorTypes[$value] = $softCreditTypes[$value];
      }
      $this->addRadio('soft_credit_type_id', NULL, $honorTypes, ['allowClear' => TRUE]);

      $honoreeProfileFields = CRM_Core_BAO_UFGroup::getFields(
        $this->_values['honoree_profile_id'], FALSE,
        NULL, NULL,
        NULL, FALSE,
        NULL, TRUE,
        NULL, CRM_Core_Permission::CREATE
      );

      // add the form elements
      foreach ($honoreeProfileFields as $name => $field) {
        // If soft credit type is not chosen then make omit requiredness from honoree profile fields
        if (count($this->_submitValues) &&
          empty($this->_submitValues['soft_credit_type_id']) &&
          !empty($field['is_required'])
        ) {
          $field['is_required'] = FALSE;
        }
        CRM_Core_BAO_UFGroup::buildProfile($this, $field, CRM_Profile_Form::MODE_CREATE, NULL, FALSE, FALSE, NULL, 'honor');
      }
    }
    $this->assign('honoreeProfileFields', $honoreeProfileFields ?? NULL);
    $this->assign('honor_block_title', $this->_values['honor_block_title'] ?? NULL);
    $this->assign('honor_block_text', $this->_values['honor_block_text'] ?? NULL);
  }

  /**
   * Add the on behalf fields.
   *
   * @throws \CRM_Core_Exception
   */
  private function addOnBehalfFields(): void {
    $contactID = $this->getContactID();
    if (!empty($this->_values['onbehalf_profile_id'])) {

      if (!CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->_values['onbehalf_profile_id'], 'is_active')) {
        CRM_Core_Error::statusBounce(ts('This contribution page has been configured for contribution on behalf of an organization and the selected onbehalf profile is either disabled or not found.'));
      }

      $member = CRM_Member_BAO_Membership::getMembershipBlock($this->getContributionPageID());
      if (empty($member['is_active'])) {
        $msg = ts('Mixed profile not allowed for on behalf of registration/sign up.');
        $onBehalfProfile = CRM_Core_BAO_UFGroup::profileGroups($this->_values['onbehalf_profile_id']);
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
            'ufID' => $this->_values['onbehalf_profile_id'],
            'cid' => '',
          ];
          if (!empty($_GET['cs'])) {
            $args = [
              'ufID' => $this->_values['onbehalf_profile_id'],
              'uid' => $this->_contactID,
              'cs' => $_GET['cs'],
              'cid' => '',
            ];
          }
          $locDataURL = CRM_Utils_System::url('civicrm/ajax/permlocation', $args, FALSE, NULL, FALSE);
        }
        if (count($organizations) > 0) {
          $this->add('select', 'onbehalfof_id', '', CRM_Utils_Array::collect('name', $organizations));

          $orgOptions = [
            0 => ts('Select an existing organization'),
            1 => ts('Enter a new organization'),
          ];
          $this->addRadio('org_option', ts('options'), $orgOptions);
          $this->setDefaults(['org_option' => 0]);
        }
      }

      if (!empty($this->_values['is_for_organization'])) {
        if ((int) $this->_values['is_for_organization'] !== 2) {
          $this->addElement('checkbox', 'is_for_organization',
            $this->_values['for_organization'],
            NULL
          );
        }
      }

      $profileFields = CRM_Core_BAO_UFGroup::getFields(
        $this->_values['onbehalf_profile_id'],
        FALSE, CRM_Core_Action::VIEW, NULL,
        NULL, FALSE, NULL, FALSE, NULL,
        CRM_Core_Permission::CREATE, NULL
      );

      $fieldTypes = ['Contact', 'Organization'];
      if (!empty($this->_membershipBlock)) {
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
          if (count($this->_submitValues) &&
            empty($this->_submitValues['is_for_organization']) &&
            $this->_values['is_for_organization'] == 1 &&
            !empty($field['is_required'])
          ) {
            $field['is_required'] = FALSE;
          }
          CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, NULL, FALSE, 'onbehalf', NULL, 'onbehalf');
        }
      }
    }
    $this->assign('onBehalfRequired', (int) ($this->_values['is_for_organization'] ?? 0) === 2);
    $this->assign('locDataURL', $locDataURL ?? NULL);
    $this->assign('onBehalfOfFields', $profileFields ?? NULL);
    $this->assign('fieldSetTitle', empty($this->_values['onbehalf_profile_id']) ? NULL : CRM_Core_BAO_UFGroup::getFrontEndTitle($this->_values['onbehalf_profile_id']));
    // @todo - this is horrible - we are accessing a value in the POST rather than via QF. _submitValues is 'raw'
    $this->assign('submittedOnBehalf', $this->_submitValues['onbehalfof_id'] ?? NULL);
    $this->assign('submittedOnBehalfInfo', empty($this->_submitValues['onbehalf']) ? NULL : json_encode(str_replace('"', '\"', $this->_submitValues['onbehalf']), JSON_HEX_APOS));
  }

  /**
   * Build Pledge Block in Contribution Pages.
   *
   * @throws \CRM_Core_Exception
   */
  private function buildPledgeBlock() {
    //build pledge payment fields.
    if (!empty($this->_values['pledge_id'])) {
      //get all payments required details.
      $allPayments = [];
      $returnProperties = [
        'status_id',
        'scheduled_date',
        'scheduled_amount',
        'currency',
      ];
      CRM_Core_DAO::commonRetrieveAll('CRM_Pledge_DAO_PledgePayment', 'pledge_id',
        $this->_values['pledge_id'], $allPayments, $returnProperties
      );
      // get all status
      $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

      $nextPayment = [];
      $isNextPayment = FALSE;
      $overduePayments = [];
      foreach ($allPayments as $payID => $value) {
        if ($allStatus[$value['status_id']] == 'Overdue') {
          $overduePayments[$payID] = [
            'id' => $payID,
            'scheduled_amount' => CRM_Utils_Rule::cleanMoney($value['scheduled_amount']),
            'scheduled_amount_currency' => $value['currency'],
            'scheduled_date' => CRM_Utils_Date::customFormat($value['scheduled_date'],
              '%B %d'
            ),
          ];
        }
        elseif (!$isNextPayment &&
          $allStatus[$value['status_id']] == 'Pending'
        ) {
          // get the next payment.
          $nextPayment = [
            'id' => $payID,
            'scheduled_amount' => CRM_Utils_Rule::cleanMoney($value['scheduled_amount']),
            'scheduled_amount_currency' => $value['currency'],
            'scheduled_date' => CRM_Utils_Date::customFormat($value['scheduled_date'],
              '%B %d'
            ),
          ];
          $isNextPayment = TRUE;
        }
      }

      // build check box array for payments.
      $payments = [];
      if (!empty($overduePayments)) {
        foreach ($overduePayments as $id => $payment) {
          $label = ts("%1 - due on %2 (overdue)", [
            1 => CRM_Utils_Money::format($payment['scheduled_amount'] ?? NULL, $payment['scheduled_amount_currency'] ?? NULL),
            2 => $payment['scheduled_date'] ?? NULL,
          ]);
          $paymentID = $payment['id'] ?? NULL;
          $payments[] = $this->createElement('checkbox', $paymentID, NULL, $label, ['amount' => $payment['scheduled_amount'] ?? NULL]);
        }
      }

      if (!empty($nextPayment)) {
        $label = ts("%1 - due on %2", [
          1 => CRM_Utils_Money::format($nextPayment['scheduled_amount'] ?? NULL, $nextPayment['scheduled_amount_currency'] ?? NULL),
          2 => $nextPayment['scheduled_date'] ?? NULL,
        ]);
        $paymentID = $nextPayment['id'] ?? NULL;
        $payments[] = $this->createElement('checkbox', $paymentID, NULL, $label, ['amount' => $nextPayment['scheduled_amount'] ?? NULL]);
      }
      // give error if empty or build form for payment.
      if (empty($payments)) {
        throw new CRM_Core_Exception(ts('Oops. It looks like there is no valid payment status for online payment.'));
      }
      $this->addGroup($payments, 'pledge_amount', ts('Make Pledge Payment(s):'), '<br />');
    }
    else {
      $pledgeBlock = [];

      $dao = new CRM_Pledge_DAO_PledgeBlock();
      $dao->entity_table = 'civicrm_contribution_page';
      $dao->entity_id = $this->getContributionPageID();
      if ($dao->find(TRUE)) {
        CRM_Core_DAO::storeValues($dao, $pledgeBlock);
      }
      // build form for pledge creation.
      $pledgeOptions = [
        '0' => ts('I want to make a one-time contribution'),
        '1' => ts('I pledge to contribute this amount every'),
      ];
      $this->addRadio('is_pledge', ts('Pledge Frequency Interval'), $pledgeOptions,
        NULL, ['<br/>']
      );
      $this->addElement('text', 'pledge_installments', ts('Installments'), ['size' => 3, 'aria-label' => ts('Installments')]);

      if (!empty($pledgeBlock['is_pledge_interval'])) {
        $this->addElement('text', 'pledge_frequency_interval', NULL, ['size' => 3, 'aria-label' => ts('Frequency Intervals')]);
      }
      else {
        $this->add('hidden', 'pledge_frequency_interval', 1);
      }
      // Frequency unit drop-down label suffixes switch from *ly to *(s)
      $freqUnitVals = explode(CRM_Core_DAO::VALUE_SEPARATOR, $pledgeBlock['pledge_frequency_unit']);
      $freqUnits = [];
      $frequencyUnits = CRM_Core_OptionGroup::values('recur_frequency_units');
      foreach ($freqUnitVals as $key => $val) {
        if (array_key_exists($val, $frequencyUnits)) {
          $freqUnits[$val] = !empty($pledgeBlock['is_pledge_interval']) ? "{$frequencyUnits[$val]}(s)" : $frequencyUnits[$val];
        }
      }
      $this->addElement('select', 'pledge_frequency_unit', NULL, $freqUnits, ['aria-label' => ts('Frequency Units')]);
      // CRM-18854
      if (!empty($pledgeBlock['is_pledge_start_date_visible'])) {
        if (!empty($pledgeBlock['pledge_start_date'])) {
          $defaults = [];
          $date = (array) json_decode($pledgeBlock['pledge_start_date']);
          foreach ($date as $field => $value) {
            switch ($field) {
              case 'contribution_date':
                $this->add('datepicker', 'start_date', ts('First installment payment'), [], FALSE, ['time' => FALSE]);
                $paymentDate = $value = date('Y-m-d');
                $defaults['start_date'] = $value;
                $this->assign('is_date', TRUE);
                break;

              case 'calendar_date':
                $this->add('datepicker', 'start_date', ts('First installment payment'), [], FALSE, ['time' => FALSE]);
                $defaults['start_date'] = $value;
                $this->assign('is_date', TRUE);
                $paymentDate = $value;
                break;

              case 'calendar_month':
                $month = CRM_Utils_Date::getCalendarDayOfMonth();
                $this->add('select', 'start_date', ts('Day of month installments paid'), $month);
                $paymentDate = CRM_Pledge_BAO_Pledge::getPaymentDate($value);
                $defaults['start_date'] = $paymentDate;
                break;

              default:
                break;

            }
            $this->setDefaults($defaults);
            $this->assign('start_date_display', $paymentDate);
            $this->assign('start_date_editable', FALSE);
            if (!empty($pledgeBlock['is_pledge_start_date_editable'])) {
              $this->assign('start_date_editable', TRUE);
              if ($field === 'calendar_month') {
                $this->assign('is_date', FALSE);
                $this->setDefaults(['start_date' => $value]);
              }
            }
          }
        }
      }
    }
  }

  /**
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function getExistingMemberships(): array {
    if ($this->existingMemberships === NULL) {
      $availableMembershipTypeIDs = $this->getAvailableMembershipTypeIDs();
      if (!empty($availableMembershipTypeIDs)) {
        $this->existingMemberships = (array) Membership::get(FALSE)
          ->addSelect('*', 'membership_type_id.duration_unit:name')
          ->addWhere('contact_id', '=', $this->_membershipContactID)
          ->addWhere('membership_type_id', 'IN', $availableMembershipTypeIDs)
          ->addWhere('status_id:name', 'NOT IN', ['Cancelled', 'Pending'])
          ->addWhere('is_test', '=', $this->isTest())
          ->addOrderBy('end_date', 'DESC')
          ->execute();
      }
    }
    return $this->existingMemberships ?? [];
  }

  /**
   * Get the first existing membership of the given type.
   *
   * @param int $membershipTypeID
   * @return array|null
   *
   * @throws \CRM_Core_Exception
   */
  private function getExistingMembership(int $membershipTypeID): ?array {
    foreach ($this->getExistingMemberships() as $membership) {
      if ($membership['membership_type_id'] === $membershipTypeID) {
        return $membership;
      }
    }
    return NULL;
  }

  private function contactHasRenewableMembership(): bool {
    foreach ($this->getExistingMemberships() as $membership) {
      if ($membership['membership_type_id.duration_unit:name'] !== 'lifetime') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get the membership type IDs available in the price set.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function getAvailableMembershipTypeIDs(): array {
    $membershipTypeIDs = [];
    foreach ($this->getPriceFieldMetaData() as $priceField) {
      foreach ($priceField['options'] ?? [] as $option) {
        if (!empty($option['membership_type_id'])) {
          $membershipTypeIDs[$option['membership_type_id']] = $option['membership_type_id'];
        }
      }
    }
    return $membershipTypeIDs;
  }

  /**
   * @return int
   */
  private function getAutoRenewOption(): int {
    $autoRenewOption = 0;
    foreach ($this->getPriceFieldMetaData() as $field) {
      foreach ($field['options'] as $option) {
        if ($option['membership_type_id.auto_renew'] === 1) {
          $autoRenewOption = 1;
          break 2;
        }
        if ($option['membership_type_id.auto_renew'] === 2) {
          $autoRenewOption = 2;
        }
      }
    }
    return $autoRenewOption;
  }

  /**
   * Get configured auto renew option.
   *
   * One of
   * 0 = never
   * 1 = optional
   * 2 - always
   *
   * This is based on the membership type but 1 can be moved up or down by membership block configuration.
   *
   * @param int $membershipTypeID
   *
   * @return int
   * @throws \CRM_Core_Exception
   */
  private function getConfiguredAutoRenewOptionForMembershipType($membershipTypeID): int {
    if (!$this->isPageHasPaymentProcessorSupportForRecurring()) {
      return 0;
    }
    if (!$this->isQuickConfig()) {
      return CRM_Member_BAO_MembershipType::getMembershipType($membershipTypeID)['auto_renew'] ?? 0;
    }
    $membershipTypeAutoRenewOption = CRM_Member_BAO_MembershipType::getMembershipType($membershipTypeID)['auto_renew'] ?? 0;
    if ($membershipTypeAutoRenewOption === 2 || $membershipTypeAutoRenewOption === 0) {
      // It is not possible to override never or always at the membership block leve.
      return $membershipTypeAutoRenewOption;
    }
    // For quick config it is possible to override the give option membership type setting in the membership block.
    return $this->_membershipBlock['auto_renew'][$membershipTypeID] ?? $membershipTypeAutoRenewOption;
  }

  /**
   * Is there payment processor support for recurring contributions on the the contribution page.
   *
   * As our front end js is not clever enough to deal with switching this returns FALSE
   * if any configured processor will not do recurring.
   *
   * @return bool
   */
  private function isPageHasPaymentProcessorSupportForRecurring(): bool {
    if (is_array($this->_paymentProcessors)) {
      foreach ($this->_paymentProcessors as $id => $val) {
        if ($id && !$val['is_recur']) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  /**
   * Get fields from the profiles in use that related to contacts.
   *
   * The fields are keyed by the field name and the keys are the metadata.
   * Fields that extend Membership or Contribution are excluded.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function getContactProfileFields(): array {
    $fields = [];
    $contribFields = CRM_Contribute_BAO_Contribution::getContributionFields();

    // remove component related fields
    foreach ($this->_fields as $name => $fieldInfo) {
      //don't set custom data Used for Contribution (CRM-1344)
      if (substr($name, 0, 7) === 'custom_') {
        $id = substr($name, 7);
        if (!CRM_Core_BAO_CustomGroup::checkCustomField($id, [
          'Contribution',
          'Membership',
        ])) {
          continue;
        }
        // ignore component fields
      }
      elseif (array_key_exists($name, $contribFields) || (substr($name, 0, 11) === 'membership_') || (substr($name, 0, 13) == 'contribution_')) {
        continue;
      }
      $fields[$name] = $fieldInfo;
    }
    return $fields;
  }

  /**
   * Get metadata for all custom fields in the attached profiles.
   *
   * Fields are keyed by the custom field ID.
   *
   * @return array
   */
  private function getProfileCustomFields (): array {
    // remove component related fields
    $customFields = [];
    foreach ($this->_fields as $name => $fieldInfo) {
      //don't set custom data Used for Contribution (CRM-1344)
      if (str_starts_with($name, 'custom_')) {
        $id = substr($name, 7);
        $customFields[(int) $id] = $fieldInfo;
      }
    }
    return $customFields;

  }

  /**
   * @param array $fields
   * @param bool $sanitized
   *   Has Quickform already sanitised the input. If not
   *   we will de-localize any money fields.
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  protected function resetOrder(array $fields, bool $sanitized = TRUE): void {
    if (!$sanitized) {
      // This happens in validate.
      foreach ($fields as $fieldName => $value) {
        $fields[$fieldName] = $this->getUnLocalizedSubmittedValue($fieldName, $value);
      }
    }
    $this->set('lineItem', NULL);
    $this->order->setPriceSelectionFromUnfilteredInput($fields);
    $this->order->recalculateLineItems();
  }

  /**
   * @param string $value
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  public function getRenewableMembershipValue(string $value) {
    if (!$this->isDefined('RenewableMembership')) {
      return NULL;
    }
    return $this->lookup('RenewableMembership', $value);
  }

}
