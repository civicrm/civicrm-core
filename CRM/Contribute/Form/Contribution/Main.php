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
 * This class generates form components for processing a Contribution.
 */
class CRM_Contribute_Form_Contribution_Main extends CRM_Contribute_Form_ContributionBase {

  /**
   * Define default MembershipType Id.
   */
  public $_defaultMemTypeId;

  public $_paymentProcessors;

  public $_membershipTypeValues;

  public $_useForMember;

  /**
   * Array of payment related fields to potentially display on this form (generally credit card or debit card fields). This is rendered via billingBlock.tpl
   * @var array
   */
  public $_paymentFields = array();

  protected $_paymentProcessorID;
  protected $_snippet;

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    parent::preProcess();

    $this->_paymentProcessors = $this->get('paymentProcessors');
    $this->preProcessPaymentOptions();

    $this->assignFormVariablesByContributionID();

    // Make the contributionPageID available to the template
    $this->assign('contributionPageID', $this->_id);
    $this->assign('ccid', $this->_ccid);
    $this->assign('isShare', CRM_Utils_Array::value('is_share', $this->_values));
    $this->assign('isConfirmEnabled', CRM_Utils_Array::value('is_confirm_enabled', $this->_values));

    $this->assign('reset', CRM_Utils_Request::retrieve('reset', 'Boolean'));
    $this->assign('mainDisplay', CRM_Utils_Request::retrieve('_qf_Main_display', 'Boolean',
      CRM_Core_DAO::$_nullObject));

    if (!empty($this->_pcpInfo['id']) && !empty($this->_pcpInfo['intro_text'])) {
      $this->assign('intro_text', $this->_pcpInfo['intro_text']);
    }
    elseif (!empty($this->_values['intro_text'])) {
      $this->assign('intro_text', $this->_values['intro_text']);
    }

    $qParams = "reset=1&amp;id={$this->_id}";
    if ($pcpId = CRM_Utils_Array::value('pcp_id', $this->_pcpInfo)) {
      $qParams .= "&amp;pcpId={$pcpId}";
    }
    $this->assign('qParams', $qParams);

    if (!empty($this->_values['footer_text'])) {
      $this->assign('footer_text', $this->_values['footer_text']);
    }
  }

  /**
   * Set the default values.
   */
  public function setDefaultValues() {
    // check if the user is registered and we have a contact ID
    $contactID = $this->getContactID();

    if (!empty($contactID)) {
      $fields = array();
      $removeCustomFieldTypes = array('Contribution', 'Membership');
      $contribFields = CRM_Contribute_BAO_Contribution::getContributionFields();

      // remove component related fields
      foreach ($this->_fields as $name => $dontCare) {
        //don't set custom data Used for Contribution (CRM-1344)
        if (substr($name, 0, 7) == 'custom_') {
          $id = substr($name, 7);
          if (!CRM_Core_BAO_CustomGroup::checkCustomField($id, $removeCustomFieldTypes)) {
            continue;
          }
          // ignore component fields
        }
        elseif (array_key_exists($name, $contribFields) || (substr($name, 0, 11) == 'membership_') || (substr($name, 0, 13) == 'contribution_')) {
          continue;
        }
        $fields[$name] = 1;
      }

      if (!empty($fields)) {
        CRM_Core_BAO_UFGroup::setProfileDefaults($contactID, $fields, $this->_defaults);
      }

      $billingDefaults = $this->getProfileDefaults('Billing', $contactID);
      $this->_defaults = array_merge($this->_defaults, $billingDefaults);
    }
    if (!empty($this->_ccid) && !empty($this->_pendingAmount)) {
      $this->_defaults['total_amount'] = CRM_Utils_Money::format($this->_pendingAmount, NULL, '%a');
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
      $completedContributionIds = array();
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
        elseif ($value['status'] == 'Completed' && $value['contribution_id']) {
          $completedContributionIds[] = $value['contribution_id'];
        }
      }
      $this->_defaults['price_' . $this->_priceSetId] = $paymentAmount;

      if (count($completedContributionIds)) {
        $softCredit = array();
        foreach ($completedContributionIds as $id) {
          $softCredit = CRM_Contribute_BAO_ContributionSoft::getSoftContribution($id);
        }
        if (isset($softCredit['soft_credit'])) {
          $this->_defaults['soft_credit_type_id'] = $softCredit['soft_credit'][1]['soft_credit_type'];

          //since honoree profile fieldname of fields are prefixed with 'honor'
          //we need to reformat the fieldname to append prefix during setting default values
          CRM_Core_BAO_UFGroup::setProfileDefaults(
            $softCredit['soft_credit'][1]['contact_id'],
            CRM_Core_BAO_UFGroup::getFields($this->_honoreeProfileId),
            $defaults
          );
          foreach ($defaults as $fieldName => $value) {
            $this->_defaults['honor[' . $fieldName . ']'] = $value;
          }
        }
      }
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

    $entityId = $memtypeID = NULL;
    if ($this->_priceSetId) {
      if (($this->_useForMember && !empty($this->_currentMemberships)) || $this->_defaultMemTypeId) {
        $selectedCurrentMemTypes = array();
        foreach ($this->_priceSet['fields'] as $key => $val) {
          foreach ($val['options'] as $keys => $values) {
            $opMemTypeId = CRM_Utils_Array::value('membership_type_id', $values);
            $priceFieldName = 'price_' . $values['price_field_id'];
            $priceFieldValue = CRM_Price_BAO_PriceSet::getPriceFieldValueFromURL($this, $priceFieldName);
            if (!empty($priceFieldValue)) {
              CRM_Price_BAO_PriceSet::setDefaultPriceSetField($priceFieldName, $priceFieldValue, $val['html_type'], $this->_defaults);
              // break here to prevent overwriting of default due to 'is_default'
              // option configuration or setting of current membership or
              // membership for related organization.
              // The value sent via URL get's higher priority.
              break;
            }
            elseif ($opMemTypeId &&
              in_array($opMemTypeId, $this->_currentMemberships) &&
              !in_array($opMemTypeId, $selectedCurrentMemTypes)
            ) {
              CRM_Price_BAO_PriceSet::setDefaultPriceSetField($priceFieldName, $keys, $val['html_type'], $this->_defaults);
              $memtypeID = $selectedCurrentMemTypes[] = $values['membership_type_id'];
            }
            elseif (!empty($values['is_default']) &&
              !$opMemTypeId &&
              (!isset($this->_defaults[$priceFieldName]) ||
                ($val['html_type'] == 'CheckBox' &&
                !isset($this->_defaults[$priceFieldName][$keys]))
              )) {
                CRM_Price_BAO_PriceSet::setDefaultPriceSetField($priceFieldName, $keys, $val['html_type'], $this->_defaults);
                $memtypeID = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $this->_defaults[$priceFieldName], 'membership_type_id');
            }
          }
        }
        $entityId = CRM_Utils_Array::value('id', CRM_Member_BAO_Membership::getContactMembership($contactID, $memtypeID, NULL));
      }
      else {
        CRM_Price_BAO_PriceSet::setDefaultPriceSet($this, $this->_defaults);
      }
    }

    //set custom field defaults set by admin if value is not set
    if (!empty($this->_fields)) {
      //load default campaign from page.
      if (array_key_exists('contribution_campaign_id', $this->_fields)) {
        $this->_defaults['contribution_campaign_id'] = CRM_Utils_Array::value('campaign_id', $this->_values);
      }

      //set custom field defaults
      foreach ($this->_fields as $name => $field) {
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
          if (!isset($this->_defaults[$name])) {
            CRM_Core_BAO_CustomField::setProfileDefaults($customFieldID, $name, $this->_defaults,
              $entityId, CRM_Profile_Form::MODE_REGISTER
            );
          }
        }
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

      $this->buildComponentForm($this->_id, $this);
    }

    if (count($this->_paymentProcessors) >= 1 && !isset($this->_paymentProcessors[0]) && !$this->get_template_vars("isCaptcha") && $this->hasToAddForcefully()) {
      if (!$this->_userID) {
        $this->enableCaptchaOnForm();
      }
      else {
        $this->displayCaptchaWarning();
      }
    }

    // Build payment processor form
    CRM_Core_Payment_ProcessorForm::buildQuickForm($this);

    $config = CRM_Core_Config::singleton();

    $contactID = $this->getContactID();
    if ($contactID) {
      $this->assign('contact_id', $contactID);
      $this->assign('display_name', CRM_Contact_BAO_Contact::displayName($contactID));
    }

    $this->applyFilter('__ALL__', 'trim');
    if (empty($this->_ccid)) {
      if ($this->_emailExists == FALSE) {
        $this->add('text', "email-{$this->_bltID}",
          ts('Email Address'),
          array('size' => 30, 'maxlength' => 60, 'class' => 'email'),
          TRUE
        );
        $this->assign('showMainEmail', TRUE);
        $this->addRule("email-{$this->_bltID}", ts('Email is not valid.'), 'email');
      }
    }
    else {
      $this->addElement('hidden', "email-{$this->_bltID}", 1);
      $this->add('text', 'total_amount', ts('Total Amount'), array('readonly' => TRUE), FALSE);
    }
    $pps = array();
    //@todo - this should be replaced by a check as to whether billing fields are set
    $onlinePaymentProcessorEnabled = FALSE;
    if (!empty($this->_paymentProcessors)) {
      foreach ($this->_paymentProcessors as $key => $name) {
        if ($name['billing_mode'] == 1) {
          $onlinePaymentProcessorEnabled = TRUE;
        }
        $pps[$key] = $name['name'];
      }
    }
    if (!empty($this->_values['is_pay_later'])) {
      $pps[0] = $this->_values['pay_later_text'];
    }

    if (count($pps) > 1) {
      $this->addRadio('payment_processor_id', ts('Payment Method'), $pps,
        NULL, "&nbsp;"
      );
    }
    elseif (!empty($pps)) {
      $key = array_keys($pps);
      $key = array_pop($key);
      $this->addElement('hidden', 'payment_processor_id', $key);
      if ($key === 0) {
        $this->assign('is_pay_later', $this->_values['is_pay_later']);
        $this->assign('pay_later_text', $this->_values['pay_later_text']);
      }
    }

    $contactID = $this->getContactID();
    if ($this->getContactID() === 0) {
      $this->addCidZeroOptions($onlinePaymentProcessorEnabled);
    }

    //build pledge block.
    $this->_useForMember = 0;
    //don't build membership block when pledge_id is passed
    if (empty($this->_values['pledge_id']) && empty($this->_ccid)) {
      $this->_separateMembershipPayment = FALSE;
      if (in_array('CiviMember', $config->enableComponents)) {
        $isTest = 0;
        if ($this->_action & CRM_Core_Action::PREVIEW) {
          $isTest = 1;
        }

        if ($this->_priceSetId &&
          (CRM_Core_Component::getComponentID('CiviMember') == CRM_Utils_Array::value('extends', $this->_priceSet))
        ) {
          $this->_useForMember = 1;
          $this->set('useForMember', $this->_useForMember);
        }

        $this->_separateMembershipPayment = $this->buildMembershipBlock(
          $this->_membershipContactID,
          TRUE, NULL, FALSE,
          $isTest
        );
      }
      $this->set('separateMembershipPayment', $this->_separateMembershipPayment);
    }
    $this->assign('useForMember', $this->_useForMember);
    // If we configured price set for contribution page
    // we are not allow membership signup as well as any
    // other contribution amount field, CRM-5095
    if (isset($this->_priceSetId) && $this->_priceSetId) {
      $this->add('hidden', 'priceSetId', $this->_priceSetId);
      // build price set form.
      $this->set('priceSetId', $this->_priceSetId);
      if (empty($this->_ccid)) {
        CRM_Price_BAO_PriceSet::buildPriceSet($this);
      }
      if ($this->_values['is_monetary'] &&
        $this->_values['is_recur'] && empty($this->_values['pledge_id'])
      ) {
        self::buildRecur($this);
      }
    }

    if ($this->_priceSetId && empty($this->_ccid)) {
      $is_quick_config = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_priceSetId, 'is_quick_config');
      if ($is_quick_config) {
        $this->_useForMember = 0;
        $this->set('useForMember', $this->_useForMember);
      }
    }

    //we allow premium for pledge during pledge creation only.
    if (empty($this->_values['pledge_id']) && empty($this->_ccid)) {
      CRM_Contribute_BAO_Premium::buildPremiumBlock($this, $this->_id, TRUE);
    }

    //don't build pledge block when mid is passed
    if (!$this->_mid && empty($this->_ccid)) {
      $config = CRM_Core_Config::singleton();
      if (in_array('CiviPledge', $config->enableComponents) && !empty($this->_values['pledge_block_id'])) {
        CRM_Pledge_BAO_PledgeBlock::buildPledgeBlock($this);
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
          $profileIDs = array($this->_values['custom_post_id']);
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
    if ($this->_pcpId && empty($this->_ccid)) {
      if ($pcpSupporter = CRM_PCP_BAO_PCP::displayName($this->_pcpId)) {
        $pcp_supporter_text = ts('This contribution is being made thanks to the effort of <strong>%1</strong>, who supports our campaign.', array(1 => $pcpSupporter));
        // Only tell people that can also create a PCP if the contribution page has a non-empty value in the "Create Personal Campaign Page link" field.
        $text = CRM_PCP_BAO_PCP::getPcpBlockStatus($this->_id, 'contribute');
        if (!empty($text)) {
          $pcp_supporter_text .= ts("You can support it as well - once you complete the donation, you will be able to create your own Personal Campaign Page!");
        }
        $this->assign('pcpSupporterText', $pcp_supporter_text);
      }
      $prms = array('id' => $this->_pcpId);
      CRM_Core_DAO::commonRetrieve('CRM_PCP_DAO_PCP', $prms, $pcpInfo);
      if ($pcpInfo['is_honor_roll']) {
        $this->assign('isHonor', TRUE);
        $this->add('checkbox', 'pcp_display_in_roll', ts('Show my contribution in the public honor roll'), NULL, NULL,
          array('onclick' => "showHideByValue('pcp_display_in_roll','','nameID|nickID|personalNoteID','block','radio',false); pcpAnonymous( );")
        );
        $extraOption = array('onclick' => "return pcpAnonymous( );");
        $elements = array();
        $elements[] = &$this->createElement('radio', NULL, '', ts('Include my name and message'), 0, $extraOption);
        $elements[] = &$this->createElement('radio', NULL, '', ts('List my contribution anonymously'), 1, $extraOption);
        $this->addGroup($elements, 'pcp_is_anonymous', NULL, '&nbsp;&nbsp;&nbsp;');

        $this->add('text', 'pcp_roll_nickname', ts('Name'), array('maxlength' => 30));
        $this->addField('pcp_personal_note', array('entity' => 'ContributionSoft', 'context' => 'create', 'style' => 'height: 3em; width: 40em;'));
      }
    }
    if (empty($this->_values['fee']) && empty($this->_ccid)) {
      CRM_Core_Error::fatal(ts('This page does not have any price fields configured or you may not have permission for them. Please contact the site administrator for more details.'));
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
      $submitButton = array(
        'type' => 'upload',
        'name' => CRM_Utils_Array::value('is_confirm_enabled', $this->_values) ? ts('Confirm Contribution') : ts('Contribute'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      );
      // Add submit-once behavior when confirm page disabled
      if (empty($this->_values['is_confirm_enabled'])) {
        $submitButton['js'] = array('onclick' => "return submitOnce(this,'" . $this->_name . "','" . ts('Processing') . "');");
      }
      //change button name for updating contribution
      if (!empty($this->_ccid)) {
        $submitButton['name'] = ts('Confirm Payment');
      }
      $this->addButtons(array($submitButton));
    }

    $this->addFormRule(array('CRM_Contribute_Form_Contribution_Main', 'formRule'), $this);
  }

  /**
   * Build elements to collect information for recurring contributions.
   *
   *
   * @param CRM_Core_Form $form
   */
  public static function buildRecur(&$form) {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionRecur');
    $className = get_class($form);

    $form->assign('is_recur_interval', CRM_Utils_Array::value('is_recur_interval', $form->_values));
    $form->assign('is_recur_installments', CRM_Utils_Array::value('is_recur_installments', $form->_values));
    $paymentObject = $form->getVar('_paymentObject');
    if ($paymentObject) {
      $form->assign('recurringHelpText', $paymentObject->getText('contributionPageRecurringHelp', array(
        'is_recur_installments' => !empty($form->_values['is_recur_installments']),
        'is_email_receipt' => !empty($form->_values['is_email_receipt']),
      )));
    }

    $form->add('checkbox', 'is_recur', ts('I want to contribute this amount'), NULL);

    if (!empty($form->_values['is_recur_interval']) || $className == 'CRM_Contribute_Form_Contribution') {
      $form->add('text', 'frequency_interval', ts('Every'), $attributes['frequency_interval'] + ['aria-label' => ts('Every')]);
      $form->addRule('frequency_interval', ts('Frequency must be a whole number (EXAMPLE: Every 3 months).'), 'integer');
    }
    else {
      // make sure frequency_interval is submitted as 1 if given no choice to user.
      $form->add('hidden', 'frequency_interval', 1);
    }

    $frUnits = CRM_Utils_Array::value('recur_frequency_unit', $form->_values);
    if (empty($frUnits) &&
      $className == 'CRM_Contribute_Form_Contribution'
    ) {
      $frUnits = implode(CRM_Core_DAO::VALUE_SEPARATOR,
        CRM_Core_OptionGroup::values('recur_frequency_units')
      );
    }

    $unitVals = explode(CRM_Core_DAO::VALUE_SEPARATOR, $frUnits);

    // CRM 10860, display text instead of a dropdown if there's only 1 frequency unit
    if (count($unitVals) == 1) {
      $form->assign('one_frequency_unit', TRUE);
      $unit = $unitVals[0];
      $form->add('hidden', 'frequency_unit', $unit);
      if (!empty($form->_values['is_recur_interval']) || $className == 'CRM_Contribute_Form_Contribution') {
        $unit .= "(s)";
      }
      $form->assign('frequency_unit', $unit);
    }
    else {
      $form->assign('one_frequency_unit', FALSE);
      $units = array();
      $frequencyUnits = CRM_Core_OptionGroup::values('recur_frequency_units', FALSE, FALSE, TRUE);
      foreach ($unitVals as $key => $val) {
        if (array_key_exists($val, $frequencyUnits)) {
          $units[$val] = $frequencyUnits[$val];
          if (!empty($form->_values['is_recur_interval']) || $className == 'CRM_Contribute_Form_Contribution') {
            $units[$val] = "{$frequencyUnits[$val]}(s)";
          }
        }
      }
      $frequencyUnit = &$form->addElement('select', 'frequency_unit', NULL, $units, ['aria-label' => ts('Frequency Unit')]);
    }

    // FIXME: Ideally we should freeze select box if there is only
    // one option but looks there is some problem /w QF freeze.
    //if ( count( $units ) == 1 ) {
    //$frequencyUnit->freeze( );
    //}

    $form->add('text', 'installments', ts('installments'),
      $attributes['installments']
    );
    $form->addRule('installments', ts('Number of installments must be a whole number.'), 'integer');
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
    $errors = array();
    $amount = self::computeAmount($fields, $self->_values);
    if (CRM_Utils_Array::value('auto_renew', $fields) &&
      CRM_Utils_Array::value('payment_processor_id', $fields) == 0
    ) {
      $errors['auto_renew'] = ts('You cannot have auto-renewal on if you are paying later.');
    }

    if ((!empty($fields['selectMembership']) &&
        $fields['selectMembership'] != 'no_thanks'
      ) ||
      (!empty($fields['priceSetId']) &&
        $self->_useForMember
      )
    ) {
      $isTest = ($self->_action & CRM_Core_Action::PREVIEW) ? TRUE : FALSE;
      $lifeMember = CRM_Member_BAO_Membership::getAllContactMembership($self->_membershipContactID, $isTest, TRUE);

      $membershipOrgDetails = CRM_Member_BAO_MembershipType::getMembershipTypeOrganization();

      $unallowedOrgs = array();
      foreach (array_keys($lifeMember) as $memTypeId) {
        $unallowedOrgs[] = $membershipOrgDetails[$memTypeId];
      }
    }

    //check for atleast one pricefields should be selected
    if (!empty($fields['priceSetId']) && empty($self->_ccid)) {
      $priceField = new CRM_Price_DAO_PriceField();
      $priceField->price_set_id = $fields['priceSetId'];
      $priceField->orderBy('weight');
      $priceField->find();

      $check = array();
      $membershipIsActive = TRUE;
      $previousId = $otherAmount = FALSE;
      while ($priceField->fetch()) {

        if ($self->isQuickConfig() && ($priceField->name == 'contribution_amount' || $priceField->name == 'membership_amount')) {
          $previousId = $priceField->id;
          if ($priceField->name == 'membership_amount' && !$priceField->is_active) {
            $membershipIsActive = FALSE;
          }
        }
        if ($priceField->name == 'other_amount') {
          if ($self->_quickConfig && empty($fields["price_{$priceField->id}"]) &&
            array_key_exists("price_{$previousId}", $fields) && isset($fields["price_{$previousId}"]) && $self->_values['fee'][$previousId]['name'] == 'contribution_amount' && empty($fields["price_{$previousId}"])
          ) {
            $otherAmount = $priceField->id;
          }
          elseif (!empty($fields["price_{$priceField->id}"])) {
            $otherAmountVal = CRM_Utils_Rule::cleanMoney($fields["price_{$priceField->id}"]);
            $min = CRM_Utils_Array::value('min_amount', $self->_values);
            $max = CRM_Utils_Array::value('max_amount', $self->_values);
            if ($min && $otherAmountVal < $min) {
              $errors["price_{$priceField->id}"] = ts('Contribution amount must be at least %1',
                array(1 => $min)
              );
            }
            if ($max && $otherAmountVal > $max) {
              $errors["price_{$priceField->id}"] = ts('Contribution amount cannot be more than %1.',
                array(1 => $max)
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

      $currentMemberships = NULL;
      if ($membershipIsActive) {
        $is_test = $self->_mode != 'live' ? 1 : 0;
        $memContactID = $self->_membershipContactID;

        // For anonymous user check using dedupe rule
        // if user has Cancelled Membership
        if (!$memContactID) {
          $memContactID = CRM_Contact_BAO_Contact::getFirstDuplicateContact($fields, 'Individual', 'Unsupervised', array(), FALSE);
        }
        $currentMemberships = CRM_Member_BAO_Membership::getContactsCancelledMembership($memContactID,
          $is_test
        );

        $errorText = 'Your %1 membership was previously cancelled and can not be renewed online. Please contact the site administrator for assistance.';
        foreach ($self->_values['fee'] as $fieldKey => $fieldValue) {
          if ($fieldValue['html_type'] != 'Text' && CRM_Utils_Array::value('price_' . $fieldKey, $fields)) {
            if (!is_array($fields['price_' . $fieldKey]) && isset($fieldValue['options'][$fields['price_' . $fieldKey]])) {
              if (array_key_exists('membership_type_id', $fieldValue['options'][$fields['price_' . $fieldKey]])
                && in_array($fieldValue['options'][$fields['price_' . $fieldKey]]['membership_type_id'], $currentMemberships)
              ) {
                $errors['price_' . $fieldKey] = ts($errorText, array(1 => CRM_Member_PseudoConstant::membershipType($fieldValue['options'][$fields['price_' . $fieldKey]]['membership_type_id'])));
              }
            }
            else {
              if (is_array($fields['price_' . $fieldKey])) {
                foreach (array_keys($fields['price_' . $fieldKey]) as $key) {
                  if (array_key_exists('membership_type_id', $fieldValue['options'][$key])
                    && in_array($fieldValue['options'][$key]['membership_type_id'], $currentMemberships)
                  ) {
                    $errors['price_' . $fieldKey] = ts($errorText, array(1 => CRM_Member_PseudoConstant::membershipType($fieldValue['options'][$key]['membership_type_id'])));
                  }
                }
              }
            }
          }
        }
      }

      // CRM-12233
      if ($membershipIsActive && !$self->_membershipBlock['is_required']
        && $self->_values['amount_block_is_active']
      ) {
        $membershipFieldId = $contributionFieldId = $errorKey = $otherFieldId = NULL;
        foreach ($self->_values['fee'] as $fieldKey => $fieldValue) {
          // if 'No thank you' membership is selected then set $membershipFieldId
          if ($fieldValue['name'] == 'membership_amount' && CRM_Utils_Array::value('price_' . $fieldKey, $fields) == 0) {
            $membershipFieldId = $fieldKey;
          }
          elseif ($membershipFieldId) {
            if ($fieldValue['name'] == 'other_amount') {
              $otherFieldId = $fieldKey;
            }
            elseif ($fieldValue['name'] == 'contribution_amount') {
              $contributionFieldId = $fieldKey;
            }

            if (!$errorKey || CRM_Utils_Array::value('price_' . $contributionFieldId, $fields) == '0') {
              $errorKey = $fieldKey;
            }
          }
        }
        // $membershipFieldId is set and additional amount is 'No thank you' or NULL then throw error
        if ($membershipFieldId && !(CRM_Utils_Array::value('price_' . $contributionFieldId, $fields, -1) > 0) && empty($fields['price_' . $otherFieldId])) {
          $errors["price_{$errorKey}"] = ts('Additional Contribution is required.');
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
        $errors["price_{$otherAmount}"] = ts('Amount is required field.');
      }

      if ($self->_useForMember == 1 && !empty($check) && $membershipIsActive) {
        $priceFieldIDS = array();
        $priceFieldMemTypes = array();

        foreach ($self->_priceSet['fields'] as $priceId => $value) {
          if (!empty($fields['price_' . $priceId]) || ($self->_quickConfig && $value['name'] == 'membership_amount' && empty($self->_membershipBlock['is_required']))) {
            if (!empty($fields['price_' . $priceId]) && is_array($fields['price_' . $priceId])) {
              foreach ($fields['price_' . $priceId] as $priceFldVal => $isSet) {
                if ($isSet) {
                  $priceFieldIDS[] = $priceFldVal;
                }
              }
            }
            elseif (!$value['is_enter_qty'] && !empty($fields['price_' . $priceId])) {
              // The check for {!$value['is_enter_qty']} is done since, quantity fields allow entering
              // quantity. And the quantity can't be conisdered as civicrm_price_field_value.id, CRM-9577
              $priceFieldIDS[] = $fields['price_' . $priceId];
            }

            if (!empty($value['options'])) {
              foreach ($value['options'] as $val) {
                if (!empty($val['membership_type_id']) && (
                    ($fields['price_' . $priceId] == $val['id']) ||
                    (isset($fields['price_' . $priceId]) && !empty($fields['price_' . $priceId][$val['id']]))
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
              in_array($membershipOrgDetails[$id], $unallowedOrgs)
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
            }
          }
        }

        if (empty($priceFieldMemTypes) && $self->_membershipBlock['is_required'] == 1) {
          $errors['_qf_default'] = ts('Please select at least one membership option.');
        }
      }

      CRM_Price_BAO_PriceSet::processAmount($self->_values['fee'],
        $fields, $lineItem
      );

      $minAmt = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $fields['priceSetId'], 'min_amount');
      if ($fields['amount'] < 0) {
        $errors['_qf_default'] = ts('Contribution can not be less than zero. Please select the options accordingly');
      }
      elseif (!empty($minAmt) && $fields['amount'] < $minAmt) {
        $errors['_qf_default'] = ts('A minimum amount of %1 should be selected from Contribution(s).', array(
          1 => CRM_Utils_Money::format($minAmt),
        ));
      }

      $amount = $fields['amount'];
    }

    if (isset($fields['selectProduct']) &&
      $fields['selectProduct'] != 'no_thanks'
    ) {
      $productDAO = new CRM_Contribute_DAO_Product();
      $productDAO->id = $fields['selectProduct'];
      $productDAO->find(TRUE);
      $min_amount = $productDAO->min_contribution;

      if ($amount < $min_amount) {
        $errors['selectProduct'] = ts('The premium you have selected requires a minimum contribution of %1', array(1 => CRM_Utils_Money::format($min_amount)));
        CRM_Core_Session::setStatus($errors['selectProduct']);
      }
    }

    //CRM-16285 - Function to handle validation errors on form, for recurring contribution field.
    CRM_Contribute_BAO_ContributionRecur::validateRecurContribution($fields, $files, $self, $errors);

    if (!empty($fields['is_recur']) &&
      CRM_Utils_Array::value('payment_processor_id', $fields) == 0
    ) {
      $errors['_qf_default'] = ts('You cannot set up a recurring contribution if you are not paying online by credit card.');
    }

    // validate PCP fields - if not anonymous, we need a nick name value
    if ($self->_pcpId && !empty($fields['pcp_display_in_roll']) &&
      (CRM_Utils_Array::value('pcp_is_anonymous', $fields) == 0) &&
      CRM_Utils_Array::value('pcp_roll_nickname', $fields) == ''
    ) {
      $errors['pcp_roll_nickname'] = ts('Please enter a name to include in the Honor Roll, or select \'contribute anonymously\'.');
    }

    // return if this is express mode
    $config = CRM_Core_Config::singleton();
    if ($self->_paymentProcessor &&
      (int) $self->_paymentProcessor['billing_mode'] & CRM_Core_Payment::BILLING_MODE_BUTTON
    ) {
      if (!empty($fields[$self->_expressButtonName . '_x']) || !empty($fields[$self->_expressButtonName . '_y']) ||
        CRM_Utils_Array::value($self->_expressButtonName, $fields)
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
        if (CRM_Utils_Rule::positiveInteger(CRM_Utils_Array::value('pledge_installments', $fields)) == FALSE) {
          $errors['pledge_installments'] = ts('Please enter a valid number of pledge installments.');
        }
        else {
          if (CRM_Utils_Array::value('pledge_installments', $fields) == NULL) {
            $errors['pledge_installments'] = ts('Pledge Installments is required field.');
          }
          elseif (CRM_Utils_Array::value('pledge_installments', $fields) == 1) {
            $errors['pledge_installments'] = ts('Pledges consist of multiple scheduled payments. Select one-time contribution if you want to make your gift in a single payment.');
          }
          elseif (CRM_Utils_Array::value('pledge_installments', $fields) == 0) {
            $errors['pledge_installments'] = ts('Pledge Installments field must be > 1.');
          }
        }

        //validation for Pledge Frequency Interval.
        if (CRM_Utils_Rule::positiveInteger(CRM_Utils_Array::value('pledge_frequency_interval', $fields)) == FALSE) {
          $errors['pledge_frequency_interval'] = ts('Please enter a valid Pledge Frequency Interval.');
        }
        else {
          if (CRM_Utils_Array::value('pledge_frequency_interval', $fields) == NULL) {
            $errors['pledge_frequency_interval'] = ts('Pledge Frequency Interval. is required field.');
          }
          elseif (CRM_Utils_Array::value('pledge_frequency_interval', $fields) == 0) {
            $errors['pledge_frequency_interval'] = ts('Pledge frequency interval field must be > 0');
          }
        }
      }
    }

    // if the user has chosen a free membership or the amount is less than zero
    // i.e. we don't need to validate payment related fields or profiles.
    if ((float) $amount <= 0.0) {
      return $errors;
    }

    if (CRM_Utils_Array::value('payment_processor_id', $fields) === NULL) {
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
      if ($greetingType = CRM_Utils_Array::value($greeting, $fields)) {
        $customizedValue = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Contact', $greeting . '_id', 'Customized');
        if ($customizedValue == $greetingType && empty($fielse[$greeting . '_custom'])) {
          $errors[$greeting . '_custom'] = ts('Custom %1 is a required field if %1 is of type Customized.',
            array(1 => ucwords(str_replace('_', " ", $greeting)))
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
  public static function computeAmount($params, $formValues) {
    $amount = 0;
    // First clean up the other amount field if present.
    if (isset($params['amount_other'])) {
      $params['amount_other'] = CRM_Utils_Rule::cleanMoney($params['amount_other']);
    }

    if (CRM_Utils_Array::value('amount', $params) == 'amount_other_radio' || !empty($params['amount_other'])) {
      $amount = $params['amount_other'];
    }
    elseif (!empty($params['pledge_amount'])) {
      foreach ($params['pledge_amount'] as $paymentId => $dontCare) {
        $amount += CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_PledgePayment', $paymentId, 'scheduled_amount');
      }
    }
    else {
      if (!empty($formValues['amount'])) {
        $amountID = CRM_Utils_Array::value('amount', $params);

        if ($amountID) {
          // @todo - stop setting amount level in this function & call the CRM_Price_BAO_PriceSet::getAmountLevel
          // function to get correct amount level consistently. Remove setting of the amount level in
          // CRM_Price_BAO_PriceSet::processAmount. Extend the unit tests in CRM_Price_BAO_PriceSetTest
          // to cover all variants.
          $params['amount_level'] = CRM_Utils_Array::value('label', $formValues[$amountID]);
          $amount = CRM_Utils_Array::value('value', $formValues[$amountID]);
        }
      }
    }
    return $amount;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    // we first reset the confirm page so it accepts new values
    $this->controller->resetPage('Confirm');

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
   */
  public function submit($params) {
    //carry campaign from profile.
    if (array_key_exists('contribution_campaign_id', $params)) {
      $params['campaign_id'] = $params['contribution_campaign_id'];
    }

    $params['currencyID'] = CRM_Core_Config::singleton()->defaultCurrency;

    // @todo refactor this & leverage it from the unit tests.
    if (!empty($params['priceSetId'])) {
      $is_quick_config = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_priceSetId, 'is_quick_config');
      if ($is_quick_config) {
        $priceField = new CRM_Price_DAO_PriceField();
        $priceField->price_set_id = $params['priceSetId'];
        $priceField->orderBy('weight');
        $priceField->find();

        $priceOptions = array();
        while ($priceField->fetch()) {
          CRM_Price_BAO_PriceFieldValue::getValues($priceField->id, $priceOptions);
          if (($selectedPriceOptionID = CRM_Utils_Array::value("price_{$priceField->id}", $params)) != FALSE && $selectedPriceOptionID > 0) {
            switch ($priceField->name) {
              case 'membership_amount':
                $this->_params['selectMembership'] = $params['selectMembership'] = CRM_Utils_Array::value('membership_type_id', $priceOptions[$selectedPriceOptionID]);
                $this->set('selectMembership', $params['selectMembership']);

              case 'contribution_amount':
                $params['amount'] = $selectedPriceOptionID;
                if ($priceField->name == 'contribution_amount' ||
                    ($priceField->name == 'membership_amount' &&
                      CRM_Utils_Array::value('is_separate_payment', $this->_membershipBlock) == 0)
                ) {
                  $this->_values['amount'] = CRM_Utils_Array::value('amount', $priceOptions[$selectedPriceOptionID]);
                }
                $this->_values[$selectedPriceOptionID]['value'] = CRM_Utils_Array::value('amount', $priceOptions[$selectedPriceOptionID]);
                $this->_values[$selectedPriceOptionID]['label'] = CRM_Utils_Array::value('label', $priceOptions[$selectedPriceOptionID]);
                $this->_values[$selectedPriceOptionID]['amount_id'] = CRM_Utils_Array::value('id', $priceOptions[$selectedPriceOptionID]);
                $this->_values[$selectedPriceOptionID]['weight'] = CRM_Utils_Array::value('weight', $priceOptions[$selectedPriceOptionID]);
                break;

              case 'other_amount':
                $params['amount_other'] = $selectedPriceOptionID;
                break;
            }
          }
        }
      }
    }

    if (!empty($this->_ccid) && !empty($this->_pendingAmount)) {
      $params['amount'] = $this->_pendingAmount;
    }
    else {
      // from here on down, $params['amount'] holds a monetary value (or null) rather than an option ID
      $params['amount'] = self::computeAmount($params, $this->_values);
    }

    $params['separate_amount'] = $params['amount'];
    $memFee = NULL;
    if (!empty($params['selectMembership'])) {
      if (empty($this->_membershipTypeValues)) {
        $this->_membershipTypeValues = CRM_Member_BAO_Membership::buildMembershipTypeValues($this,
          (array) $params['selectMembership']
        );
      }
      $membershipTypeValues = $this->_membershipTypeValues[$params['selectMembership']];
      $memFee = $membershipTypeValues['minimum_fee'];
      if (!$params['amount'] && !$this->_separateMembershipPayment) {
        $params['amount'] = $memFee ? $memFee : 0;
      }
    }
    //If the membership & contribution is used in contribution page & not separate payment
    $memPresent = $membershipLabel = $fieldOption = $is_quick_config = NULL;
    $proceFieldAmount = 0;
    if (property_exists($this, '_separateMembershipPayment') && $this->_separateMembershipPayment == 0) {
      $is_quick_config = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_priceSetId, 'is_quick_config');
      if ($is_quick_config) {
        foreach ($this->_priceSet['fields'] as $fieldKey => $fieldVal) {
          if ($fieldVal['name'] == 'membership_amount' && !empty($params['price_' . $fieldKey])) {
            $fieldId = $fieldVal['id'];
            $fieldOption = $params['price_' . $fieldId];
            $proceFieldAmount += $fieldVal['options'][$this->_submitValues['price_' . $fieldId]]['amount'];
            $memPresent = TRUE;
          }
          else {
            if (!empty($params['price_' . $fieldKey]) && $memPresent && ($fieldVal['name'] == 'other_amount' || $fieldVal['name'] == 'contribution_amount')) {
              $fieldId = $fieldVal['id'];
              if ($fieldVal['name'] == 'other_amount') {
                $proceFieldAmount += $this->_submitValues['price_' . $fieldId];
              }
              elseif ($fieldVal['name'] == 'contribution_amount' && $this->_submitValues['price_' . $fieldId] > 0) {
                $proceFieldAmount += $fieldVal['options'][$this->_submitValues['price_' . $fieldId]]['amount'];
              }
              unset($params['price_' . $fieldId]);
              break;
            }
          }
        }
      }
    }

    if (!isset($params['amount_other'])) {
      $this->set('amount_level', CRM_Utils_Array::value('amount_level', $params));
    }

    if (!empty($this->_ccid)) {
      $this->set('lineItem', $this->_lineItem);
    }
    elseif ($priceSetId = CRM_Utils_Array::value('priceSetId', $params)) {
      $lineItem = array();
      $is_quick_config = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $priceSetId, 'is_quick_config');
      if ($is_quick_config) {
        foreach ($this->_values['fee'] as $key => & $val) {
          if ($val['name'] == 'other_amount' && $val['html_type'] == 'Text' && !empty($params['price_' . $key])) {
            // Clean out any currency symbols.
            $params['price_' . $key] = CRM_Utils_Rule::cleanMoney($params['price_' . $key]);
            if ($params['price_' . $key] != 0) {
              foreach ($val['options'] as $optionKey => & $options) {
                $options['amount'] = CRM_Utils_Array::value('price_' . $key, $params);
                break;
              }
            }
            $params['price_' . $key] = 1;
            break;
          }
        }
      }
      $component = '';
      if ($this->_membershipBlock) {
        $component = 'membership';
      }

      CRM_Price_BAO_PriceSet::processAmount($this->_values['fee'], $params, $lineItem[$priceSetId], $component, $priceSetId);
      if ($params['tax_amount']) {
        $this->set('tax_amount', $params['tax_amount']);
      }

      if ($proceFieldAmount) {
        $lineItem[$params['priceSetId']][$fieldOption]['unit_price'] = $proceFieldAmount;
        $lineItem[$params['priceSetId']][$fieldOption]['line_total'] = $proceFieldAmount;
        if (isset($lineItem[$params['priceSetId']][$fieldOption]['tax_amount'])) {
          $proceFieldAmount += $lineItem[$params['priceSetId']][$fieldOption]['tax_amount'];
        }
        if (!$this->_membershipBlock['is_separate_payment']) {
          //require when separate membership not used
          $params['amount'] = $proceFieldAmount;
        }
      }
      $this->set('lineItem', $lineItem);
    }

    if ($params['amount'] != 0 && (($this->_values['is_pay_later'] &&
          empty($this->_paymentProcessor) &&
          !array_key_exists('hidden_processor', $params)) ||
        (CRM_Utils_Array::value('payment_processor_id', $params) == 0))
    ) {
      $params['is_pay_later'] = 1;
    }
    else {
      $params['is_pay_later'] = 0;
    }

    // Would be nice to someday understand the point of this set.
    $this->set('is_pay_later', $params['is_pay_later']);
    // assign pay later stuff
    $this->_params['is_pay_later'] = CRM_Utils_Array::value('is_pay_later', $params, FALSE);
    $this->assign('is_pay_later', $params['is_pay_later']);
    if ($params['is_pay_later']) {
      $this->assign('pay_later_text', $this->_values['pay_later_text']);
      $this->assign('pay_later_receipt', CRM_Utils_Array::value('pay_later_receipt', $this->_values));
    }

    if ($this->_membershipBlock['is_separate_payment'] && !empty($params['separate_amount'])) {
      $this->set('amount', $params['separate_amount']);
    }
    else {
      $this->set('amount', $params['amount']);
    }

    // generate and set an invoiceID for this transaction
    $invoiceID = md5(uniqid(rand(), TRUE));
    $this->set('invoiceID', $invoiceID);
    $params['invoiceID'] = $invoiceID;
    $params['description'] = ts('Online Contribution') . ': ' . ((!empty($this->_pcpInfo['title']) ? $this->_pcpInfo['title'] : $this->_values['title']));
    $params['button'] = $this->controller->getButtonName();
    // required only if is_monetary and valid positive amount
    // @todo it seems impossible for $memFee to be greater than 0 & $params['amount'] not to
    // be & by requiring $memFee down here we make it harder to do a sensible refactoring of the function
    // above (ie. extract the amount in a small function).
    if ($this->_values['is_monetary'] &&
      !empty($this->_paymentProcessor) &&
      ((float ) $params['amount'] > 0.0 || $memFee > 0.0)
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
    $confirmForm->preProcess();
    $confirmForm->buildQuickForm();

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
  public function assignFormVariablesByContributionID() {
    if (empty($this->_ccid)) {
      return;
    }
    if (!$this->getContactID()) {
      CRM_Core_Error::statusBounce(ts("Returning since there is no contact attached to this contribution id."));
    }

    $paymentBalance = CRM_Contribute_BAO_Contribution::getContributionBalance($this->_ccid);
    //bounce if the contribution is not pending.
    if ((int) $paymentBalance <= 0) {
      CRM_Core_Error::statusBounce(ts("Returning since contribution has already been handled."));
    }
    if (!empty($paymentBalance)) {
      $this->_pendingAmount = $paymentBalance;
      $this->assign('pendingAmount', $this->_pendingAmount);
    }

    if ($taxAmount = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $this->_ccid, 'tax_amount')) {
      $this->set('tax_amount', $taxAmount);
      $this->assign('taxAmount', $taxAmount);
    }

    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($this->_ccid);
    foreach (array_keys($lineItems) as $id) {
      $lineItems[$id]['id'] = $id;
    }
    $itemId = key($lineItems);
    if ($itemId && !empty($lineItems[$itemId]['price_field_id'])) {
      $this->_priceSetId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $lineItems[$itemId]['price_field_id'], 'price_set_id');
    }

    if (!empty($lineItems[$itemId]['price_field_id'])) {
      $this->_lineItem[$this->_priceSetId] = $lineItems;
    }
    $isQuickConfig = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_priceSetId, 'is_quick_config');
    $this->assign('lineItem', $this->_lineItem);
    $this->assign('is_quick_config', $isQuickConfig);
    $this->assign('priceSetID', $this->_priceSetId);
  }

  /**
   * Function for unit tests on the postProcess function.
   *
   * @param array $params
   */
  public function testSubmit($params) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $this->controller = new CRM_Contribute_Controller_Contribution();
    $this->submit($params);
  }

}
