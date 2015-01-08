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
 * This class generates form components for processing a Contribution
 *
 */
class CRM_Contribute_Form_Contribution_Main extends CRM_Contribute_Form_ContributionBase {

  /**
   *Define default MembershipType Id
   *
   */
  public $_defaultMemTypeId;

  public $_relatedOrganizationFound;

  public $_onBehalfRequired = FALSE;
  public $_onbehalf = FALSE;
  public $_paymentProcessors;
  protected $_defaults;

  public $_membershipTypeValues;

  public $_useForMember;

  protected $_ppType;
  protected $_snippet;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    parent::preProcess();

    self::preProcessPaymentOptions($this);

    // Make the contributionPageID avilable to the template
    $this->assign('contributionPageID', $this->_id);
    $this->assign('isShare', CRM_Utils_Array::value('is_share', $this->_values));
    $this->assign('isConfirmEnabled', CRM_Utils_Array::value('is_confirm_enabled', $this->_values));

    // make sure we have right permission to edit this user
    $csContactID = $this->getContactID();
    $reset       = CRM_Utils_Request::retrieve('reset', 'Boolean', CRM_Core_DAO::$_nullObject);
    $mainDisplay = CRM_Utils_Request::retrieve('_qf_Main_display', 'Boolean', CRM_Core_DAO::$_nullObject);

    if ($reset) {
      $this->assign('reset', $reset);
    }

    if ($mainDisplay) {
      $this->assign('mainDisplay', $mainDisplay);
    }

    // Possible values for 'is_for_organization':
    // * 0 - org profile disabled
    // * 1 - org profile optional
    // * 2 - org profile required
    $this->_onbehalf = FALSE;
    if (!empty($this->_values['is_for_organization'])) {
      if ($this->_values['is_for_organization'] == 2) {
        $this->_onBehalfRequired = TRUE;
      }
      // Add organization profile if 1 of the following are true:
      // If the org profile is required
      if ($this->_onBehalfRequired ||
        // Or we are building the form for the first time
        empty($_POST) ||
        // Or the user has submitted the form and checked the "On Behalf" checkbox
        !empty($_POST['is_for_organization'])
      ) {
        $this->_onbehalf = TRUE;
        CRM_Contribute_Form_Contribution_OnBehalfOf::preProcess($this);
      }
    }
    $this->assign('onBehalfRequired', $this->_onBehalfRequired);

    if ($this->_honor_block_is_active) {
      CRM_Contact_Form_ProfileContact::preprocess($this);
    }

    if ($this->_snippet) {
      $this->assign('isOnBehalfCallback', CRM_Utils_Array::value('onbehalf', $_GET, FALSE));
      return;
    }

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

    //CRM-5001
    if (!empty($this->_values['is_for_organization'])) {
      $msg = ts('Mixed profile not allowed for on behalf of registration/sign up.');
      $ufJoinParams = array(
        'module' => 'onBehalf',
        'entity_table' => 'civicrm_contribution_page',
        'entity_id' => $this->_id,
      );
      $onBehalfProfileIDs = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);
      // getUFGroupIDs returns an array with the first item being the ID we need
      $onBehalfProfileID = $onBehalfProfileIDs[0];
      if ($onBehalfProfileID) {
        $onBehalfProfile = CRM_Core_BAO_UFGroup::profileGroups($onBehalfProfileID);
        foreach (array(
            'Individual', 'Organization', 'Household') as $contactType) {
          if (in_array($contactType, $onBehalfProfile) &&
            (in_array('Membership', $onBehalfProfile) ||
              in_array('Contribution', $onBehalfProfile)
            )
          ) {
            CRM_Core_Error::fatal($msg);
          }
        }
      }

      if ($postID = CRM_Utils_Array::value('custom_post_id', $this->_values)) {
        $postProfile = CRM_Core_BAO_UFGroup::profileGroups($postID);
        foreach (array(
            'Individual', 'Organization', 'Household') as $contactType) {
          if (in_array($contactType, $postProfile) &&
            (in_array('Membership', $postProfile) ||
              in_array('Contribution', $postProfile)
            )
          ) {
            CRM_Core_Error::fatal($msg);
          }
        }
      }
    }
  }

  /**
   * set the default values
   *
   * @return void
   * @access public
   */
  /**
   *
   */
  function setDefaultValues() {
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
              NULL, CRM_Profile_Form::MODE_REGISTER
            );
          }
        }
      }
    }

    //         // hack to simplify credit card entry for testing
    //         $this->_defaults['credit_card_type']     = 'Visa';
    //         $this->_defaults['amount']               = 168;
    //         $this->_defaults['credit_card_number']   = '4111111111111111';
    //         $this->_defaults['cvv2']                 = '000';
    //         $this->_defaults['credit_card_exp_date'] = array('Y' => date('Y')+1, 'M' => '05');

    //         // hack to simplify direct debit entry for testing
    //         $this->_defaults['account_holder'] = 'Max Müller';
    //         $this->_defaults['bank_account_number'] = '12345678';
    //         $this->_defaults['bank_identification_number'] = '12030000';
    //         $this->_defaults['bank_name'] = 'Bankname';

    //build set default for pledge overdue payment.
    if (!empty($this->_values['pledge_id'])) {
      //get all pledge payment records of current pledge id.
      $pledgePayments = array();

      //used to record completed pledge payment ids used later for honor default
      $completedContributionIds = array();

      $pledgePayments = CRM_Pledge_BAO_PledgePayment::getPledgePayments($this->_values['pledge_id']);

      $duePayment = FALSE;
      foreach ($pledgePayments as $payId => $value) {
        if ($value['status'] == 'Overdue') {
          $this->_defaults['pledge_amount'][$payId] = 1;
        }
        elseif (!$duePayment && $value['status'] == 'Pending') {
          $this->_defaults['pledge_amount'][$payId] = 1;
          $duePayment = TRUE;
        }
        elseif ($value['status'] == 'Completed' && $value['contribution_id']) {
          $completedContributionIds[] = $value['contribution_id'];
        }
      }

      if ($this->_honor_block_is_active && count($completedContributionIds)) {
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
    if (!empty($getDefaults)) {
      $this->_defaults = array_merge($this->_defaults, $getDefaults);
    }

    $config = CRM_Core_Config::singleton();
    // set default country from config if no country set
    if (empty($this->_defaults["billing_country_id-{$this->_bltID}"])) {
      $this->_defaults["billing_country_id-{$this->_bltID}"] = $config->defaultContactCountry;
    }

    // set default state/province from config if no state/province set
    if (empty($this->_defaults["billing_state_province_id-{$this->_bltID}"])) {
      $this->_defaults["billing_state_province_id-{$this->_bltID}"] = $config->defaultContactStateProvince;
    }

    if ($this->_priceSetId) {
      if (($this->_useForMember && !empty($this->_currentMemberships)) || $this->_defaultMemTypeId) {
        $selectedCurrentMemTypes = array();
        foreach ($this->_priceSet['fields'] as $key => $val) {
          foreach ($val['options'] as $keys => $values) {
            $opMemTypeId = CRM_Utils_Array::value('membership_type_id', $values);
            if ($opMemTypeId &&
              in_array($opMemTypeId, $this->_currentMemberships) &&
              !in_array($opMemTypeId, $selectedCurrentMemTypes)
            ) {
              if ($val['html_type'] == 'CheckBox') {
                $this->_defaults["price_{$key}"][$keys] = 1;
              }
              else {
                $this->_defaults["price_{$key}"] = $keys;
              }
              $selectedCurrentMemTypes[] = $values['membership_type_id'];
            }
            elseif (!empty($values['is_default']) &&
              !$opMemTypeId &&
              (!isset($this->_defaults["price_{$key}"]) ||
                ($val['html_type'] == 'CheckBox' && !isset($this->_defaults["price_{$key}"][$keys]))
              )
            ) {
              if ($val['html_type'] == 'CheckBox') {
                $this->_defaults["price_{$key}"][$keys] = 1;
              }
              else {
                $this->_defaults["price_{$key}"] = $keys;
              }
            }
          }
        }
      }
      else {
        CRM_Price_BAO_PriceSet::setDefaultPriceSet($this, $this->_defaults);
      }
    }

    if (!empty($this->_paymentProcessors)) {
      foreach ($this->_paymentProcessors as $pid => $value) {
        if (!empty($value['is_default'])) {
          $this->_defaults['payment_processor'] = $pid;
        }
      }
    }

    return $this->_defaults;
  }

  /**
   * Function to build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    // build profiles first so that we can determine address fields etc
    // and then show copy address checkbox
    $this->buildCustom($this->_values['custom_pre_id'], 'customPre');
    $this->buildCustom($this->_values['custom_post_id'], 'customPost');

    if (!empty($this->_fields) && !empty($this->_values['custom_pre_id'])) {
      $profileAddressFields = array();
      foreach ($this->_fields as $key => $value) {
        CRM_Core_BAO_UFField::assignAddressField($key, $profileAddressFields, array('uf_group_id' => $this->_values['custom_pre_id']));
      }
      $this->set('profileAddressFields', $profileAddressFields);
    }

    // Build payment processor form
    if ($this->_ppType && empty($_GET['onbehalf'])) {
      CRM_Core_Payment_ProcessorForm::buildQuickForm($this);
      // Return if we are in an ajax callback
      if ($this->_snippet) {
        return;
      }
    }

    $config = CRM_Core_Config::singleton();

    if ($this->_onbehalf) {
      CRM_Contribute_Form_Contribution_OnBehalfOf::buildQuickForm($this);
      // Return if we are in an ajax callback
      if ($this->_snippet) {
        return;
      }
    }

    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', "email-{$this->_bltID}",
      ts('Email Address'),
      array('size' => 30, 'maxlength' => 60, 'class' => 'email'),
      TRUE
    );
    $this->addRule("email-{$this->_bltID}", ts('Email is not valid.'), 'email');
    $pps = array();
    $onlinePaymentProcessorEnabled = FALSE;
    if (!empty($this->_paymentProcessors)) {
      foreach ($this->_paymentProcessors as $key => $name) {
        if($name['billing_mode'] == 1) {
          $onlinePaymentProcessorEnabled = TRUE;
        }
        $pps[$key] = $name['name'];
      }
    }
    if (!empty($this->_values['is_pay_later'])) {
      $pps[0] = $this->_values['pay_later_text'];
    }

    if (count($pps) > 1) {
      $this->addRadio('payment_processor', ts('Payment Method'), $pps,
        NULL, "&nbsp;", TRUE
      );
    }
    elseif (!empty($pps)) {
      $key = array_keys($pps);
      $key = array_pop($key);
      $this->addElement('hidden', 'payment_processor', $key);
      if ($key === 0) {
        $this->assign('is_pay_later', $this->_values['is_pay_later']);
        $this->assign('pay_later_text', $this->_values['pay_later_text']);
      }
    }

    $contactID = $this->getContactID();
    if($this->getContactID() === '0') {
      $this->addCidZeroOptions($onlinePaymentProcessorEnabled);
    }
    //build pledge block.
    $this->_useForMember = 0;
    //don't build membership block when pledge_id is passed
    if (empty($this->_values['pledge_id'])) {
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

        $this->_separateMembershipPayment = CRM_Member_BAO_Membership::buildMembershipBlock($this,
          $this->_id,
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
      CRM_Price_BAO_PriceSet::buildPriceSet($this);
      if ($this->_values['is_monetary'] &&
        $this->_values['is_recur'] && empty($this->_values['pledge_id'])) {
        self::buildRecur($this);
      }
    }

    if ($this->_priceSetId) {
      $is_quick_config = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_priceSetId, 'is_quick_config');
      if ($is_quick_config) {
        $this->_useForMember = 0;
        $this->set('useForMember', $this->_useForMember);
      }
    }

    if ($this->_values['is_for_organization']) {
      $this->buildOnBehalfOrganization();
    }

    //we allow premium for pledge during pledge creation only.
    if (empty($this->_values['pledge_id'])) {
      CRM_Contribute_BAO_Premium::buildPremiumBlock($this, $this->_id, TRUE);
    }

    //add honor block
    if ($this->_honor_block_is_active) {
      $this->assign('honor_block_is_active', TRUE);

      //build soft-credit section
      CRM_Contribute_Form_SoftCredit::buildQuickForm($this);
      //build honoree profile section
      CRM_Contact_Form_ProfileContact::buildQuickForm($this);
    }


    //don't build pledge block when mid is passed
    if (!$this->_mid) {
      $config = CRM_Core_Config::singleton();
      if (in_array('CiviPledge', $config->enableComponents) && !empty($this->_values['pledge_block_id'])) {
        CRM_Pledge_BAO_PledgeBlock::buildPledgeBlock($this);
      }
    }

    //to create an cms user
    if (!$this->_userID) {
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
    if ($this->_pcpId) {
      if ($pcpSupporter = CRM_PCP_BAO_PCP::displayName($this->_pcpId)) {
        $pcp_supporter_text = ts('This contribution is being made thanks to the effort of <strong>%1</strong>, who supports our campaign.', array(1 => $pcpSupporter));
        // Only tell people that can also create a PCP if the contribution page has a non-empty value in the "Create Personal Campaign Page link" field.
        $text = CRM_PCP_BAO_PCP::getPcpBlockStatus($this->_id, 'contribute');
        if(!empty($text)) {
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
        $elements    = array();
        $elements[]  = &$this->createElement('radio', NULL, '', ts('Include my name and message'), 0, $extraOption);
        $elements[]  = &$this->createElement('radio', NULL, '', ts('List my contribution anonymously'), 1, $extraOption);
        $this->addGroup($elements, 'pcp_is_anonymous', NULL, '&nbsp;&nbsp;&nbsp;');
        $this->setDefaults(array('pcp_display_in_roll' => 1));
        $this->setDefaults(array('pcp_is_anonymous' => 1));

        $this->add('text', 'pcp_roll_nickname', ts('Name'), array('maxlength' => 30));
        $this->add('textarea', 'pcp_personal_note', ts('Personal Note'), array('style' => 'height: 3em; width: 40em;'));
      }
    }

    //we have to load confirm contribution button in template
    //when multiple payment processor as the user
    //can toggle with payment processor selection
    $billingModePaymentProcessors = 0;
    if ( !empty( $this->_paymentProcessors ) ) {
      foreach ($this->_paymentProcessors as $key => $values) {
        if ($values['billing_mode'] == CRM_Core_Payment::BILLING_MODE_BUTTON) {
          $billingModePaymentProcessors++;
        }
      }
    }

    if ($billingModePaymentProcessors && count($this->_paymentProcessors) == $billingModePaymentProcessors) {
      $allAreBillingModeProcessors = TRUE;
    } else {
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
      $this->addButtons(array($submitButton));
    }

    $this->addFormRule(array('CRM_Contribute_Form_Contribution_Main', 'formRule'), $this);
  }

  /**
   * build elements to enable pay on behalf of an organization.
   *
   * @access public
   */
  function buildOnBehalfOrganization() {
    if ($this->_membershipContactID) {
      $entityBlock = array('contact_id' => $this->_membershipContactID);
      CRM_Core_BAO_Location::getValues($entityBlock, $this->_defaults);
    }

    if (!$this->_onBehalfRequired) {
      $this->addElement('checkbox', 'is_for_organization',
        $this->_values['for_organization'],
        NULL, array('onclick' => "showOnBehalf( );")
      );
    }

    $this->assign('is_for_organization', TRUE);
    $this->assign('urlPath', 'civicrm/contribute/transact');
  }

  /**
   * build elements to collect information for recurring contributions
   *
   * @access public
   */
  public static function buildRecur(&$form) {
    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionRecur');
    $className = get_class($form);

    $form->assign('is_recur_interval', CRM_Utils_Array::value('is_recur_interval', $form->_values));
    $form->assign('is_recur_installments', CRM_Utils_Array::value('is_recur_installments', $form->_values));

    $form->add('checkbox', 'is_recur', ts('I want to contribute this amount'), NULL);

    if (!empty($form->_values['is_recur_interval']) || $className == 'CRM_Contribute_Form_Contribution') {
      $form->add('text', 'frequency_interval', ts('Every'), $attributes['frequency_interval']);
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
    if(sizeof($unitVals) == 1) {
      $form->assign('one_frequency_unit', true);
      $unit = $unitVals[0];
      $form->add('hidden', 'frequency_unit', $unit);
      if (!empty($form->_values['is_recur_interval']) || $className == 'CRM_Contribute_Form_Contribution') {
        $unit .= "(s)";
      }
      $form->assign('frequency_unit', $unit);
    } else {
      $form->assign('one_frequency_unit', false);
      $units = array();
      $frequencyUnits = CRM_Core_OptionGroup::values('recur_frequency_units');
      foreach ($unitVals as $key => $val) {
        if (array_key_exists($val, $frequencyUnits)) {
          $units[$val] = $frequencyUnits[$val];
          if (!empty($form->_values['is_recur_interval']) || $className == 'CRM_Contribute_Form_Contribution') {
            $units[$val] = "{$frequencyUnits[$val]}(s)";
          }
        }
      }
      $frequencyUnit = &$form->add('select', 'frequency_unit', NULL, $units);
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
   * global form rule
   *
   * @param array $fields the input form values
   * @param array $files the uploaded files if any
   * @param $self
   *
   * @internal param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $self) {
    $errors = array();
    $amount = self::computeAmount($fields, $self);

    if ((!empty($fields['selectMembership']) &&
        $fields['selectMembership'] != 'no_thanks'
      ) ||
      (!empty($fields['priceSetId']) &&
        $self->_useForMember
      )
    ) {
      $lifeMember = CRM_Member_BAO_Membership::getAllContactMembership($self->_userID, FALSE, TRUE);

      $membershipOrgDetails = CRM_Member_BAO_MembershipType::getMembershipTypeOrganization();

      $unallowedOrgs = array();
      foreach (array_keys($lifeMember) as $memTypeId) {
        $unallowedOrgs[] = $membershipOrgDetails[$memTypeId];
      }
    }

    //check for atleast one pricefields should be selected
    if (!empty($fields['priceSetId'])) {
      $priceField = new CRM_Price_DAO_PriceField();
      $priceField->price_set_id = $fields['priceSetId'];
      $priceField->orderBy('weight');
      $priceField->find();

      $check = array();
      $membershipIsActive = TRUE;
      $previousId = $otherAmount = FALSE;
      while ($priceField->fetch()) {

        if ($self->_quickConfig && ($priceField->name == 'contribution_amount' || $priceField->name == 'membership_amount')) {
          $previousId = $priceField->id;
          if ($priceField->name == 'membership_amount' && !$priceField->is_active ) {
            $membershipIsActive = FALSE;
          }
        }
        if ($priceField->name == 'other_amount') {
          if ($self->_quickConfig && empty($fields["price_{$priceField->id}"]) &&
            array_key_exists("price_{$previousId}", $fields) && isset($fields["price_{$previousId}"]) && $self->_values['fee'][$previousId]['name'] == 'contribution_amount' && empty($fields["price_{$previousId}"])) {
            $otherAmount = $priceField->id;
          }
          elseif (!empty($fields["price_{$priceField->id}"])) {
            $otherAmountVal = $fields["price_{$priceField->id}"];
            $min            = CRM_Utils_Array::value('min_amount', $self->_values);
            $max            = CRM_Utils_Array::value('max_amount', $self->_values);
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
            && empty($fields["price_{$previousId}"]))) {
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
          $dedupeParams = CRM_Dedupe_Finder::formatParams($fields, 'Individual');
          $dedupeParams['check_permission'] = FALSE;
          $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual');
          // if we find more than one contact, use the first one
          $memContactID = CRM_Utils_Array::value(0, $ids);
        }
        $currentMemberships = CRM_Member_BAO_Membership::getContactsCancelledMembership($memContactID,
          $is_test
        );
        
        $errorText = 'Your %1 membership was previously cancelled and can not be renewed online. Please contact the site administrator for assistance.';
        foreach ($self->_values['fee'] as $fieldKey => $fieldValue) {
          if ($fieldValue['html_type'] != 'Text' && CRM_Utils_Array::value('price_' . $fieldKey, $fields)) {
            if (!is_array($fields['price_' . $fieldKey])) {
              if (array_key_exists('membership_type_id', $fieldValue['options'][$fields['price_' . $fieldKey]]) 
                && in_array($fieldValue['options'][$fields['price_' . $fieldKey]]['membership_type_id'], $currentMemberships)) {
                $errors['price_' . $fieldKey] = ts($errorText, array(1 => CRM_Member_PseudoConstant::membershipType($fieldValue['options'][$fields['price_' . $fieldKey]]['membership_type_id'])));
              }
            }
            else {
              foreach ($fields['price_' . $fieldKey] as $key => $ignore) {
                if (array_key_exists('membership_type_id', $fieldValue['options'][$key]) 
                  && in_array($fieldValue['options'][$key]['membership_type_id'], $currentMemberships)) {
                  $errors['price_' . $fieldKey] = ts($errorText, array(1 => CRM_Member_PseudoConstant::membershipType($fieldValue['options'][$key]['membership_type_id'])));
                }
              }
            }
          }
        }
      }
 
      // CRM-12233
      if ($membershipIsActive && !$self->_membershipBlock['is_required']
        && $self->_values['amount_block_is_active']) {
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
      if (empty($check)) {
        if ($self->_useForMember == 1 && $membershipIsActive) {
          $errors['_qf_default'] = ts('Select at least one option from Membership Type(s).');
        }
        else {
          $errors['_qf_default'] = ts('Select at least one option from Contribution(s).');
        }
      }
      if($otherAmount && !empty($check)) {
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
          foreach ($count as $id => $occurance) {
            if ($occurance > 1) {
              $errors['_qf_default'] = ts('You have selected multiple memberships for the same organization or entity. Please review your selections and choose only one membership per entity. Contact the site administrator if you need assistance.');
            }
          }
        }

        if (empty($priceFieldMemTypes)) {
          $errors['_qf_default'] = ts('Please select at least one membership option.');
        }
      }

      CRM_Price_BAO_PriceSet::processAmount($self->_values['fee'],
        $fields, $lineItem
      );

      if ($fields['amount'] < 0) {
        $errors['_qf_default'] = ts('Contribution can not be less than zero. Please select the options accordingly');
      }
      $amount = $fields['amount'];
    }

    if (isset($fields['selectProduct']) &&
      $fields['selectProduct'] != 'no_thanks') {
      $productDAO = new CRM_Contribute_DAO_Product();
      $productDAO->id = $fields['selectProduct'];
      $productDAO->find(TRUE);
      $min_amount = $productDAO->min_contribution;

      if ($amount < $min_amount) {
        $errors['selectProduct'] = ts('The premium you have selected requires a minimum contribution of %1', array(1 => CRM_Utils_Money::format($min_amount)));
        CRM_Core_Session::setStatus($errors['selectProduct']);
      }
    }

    if (!empty($fields['is_recur'])) {
      if ($fields['frequency_interval'] <= 0) {
        $errors['frequency_interval'] = ts('Please enter a number for how often you want to make this recurring contribution (EXAMPLE: Every 3 months).');
      }
      if ($fields['frequency_unit'] == '0') {
        $errors['frequency_unit'] = ts('Please select a period (e.g. months, years ...) for how often you want to make this recurring contribution (EXAMPLE: Every 3 MONTHS).');
      }
    }

    if (!empty($fields['is_recur']) &&
      CRM_Utils_Array::value('payment_processor', $fields) == 0) {
      $errors['_qf_default'] = ts('You cannot set up a recurring contribution if you are not paying online by credit card.');
    }

    if (!empty($fields['is_for_organization']) &&
      !property_exists($self, 'organizationName')
    ) {

      if (empty($fields['onbehalf']['organization_name'])) {
        if (!empty($fields['org_option']) && !$fields['onbehalfof_id']) {
          $errors['organization_id'] = ts('Please select an organization or enter a new one.');
        }
        elseif (empty($fields['org_option'])) {
          $errors['onbehalf']['organization_name'] = ts('Please enter the organization name.');
        }
      }

      foreach ($fields['onbehalf'] as $key => $value) {
        if (strstr($key, 'email')) {
          $emailLocType = explode('-', $key);
        }
      }
      if (empty($fields['onbehalf']["email-{$emailLocType[1]}"])) {
        $errors['onbehalf']["email-{$emailLocType[1]}"] = ts('Organization email is required.');
      }
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
      $self->_paymentProcessor['billing_mode'] & CRM_Core_Payment::BILLING_MODE_BUTTON
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
          elseif (CRM_Utils_array::value('pledge_installments', $fields) == 1) {
            $errors['pledge_installments'] = ts('Pledges consist of multiple scheduled payments. Select one-time contribution if you want to make your gift in a single payment.');
          }
          elseif (CRM_Utils_array::value('pledge_installments', $fields) == 0) {
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
          elseif (CRM_Utils_array::value('pledge_frequency_interval', $fields) == 0) {
            $errors['pledge_frequency_interval'] = ts('Pledge frequency interval field must be > 0');
          }
        }
      }
    }

    // also return if paylater mode
    if (CRM_Utils_Array::value('payment_processor', $fields) == 0) {
      return empty($errors) ? TRUE : $errors;
    }

    // if the user has chosen a free membership or the amount is less than zero
    // i.e. we skip calling the payment processor and hence dont need credit card
    // info
    if ((float) $amount <= 0.0) {
      return $errors;
    }

    if (!empty($self->_paymentFields)) {
      CRM_Core_Form::validateMandatoryFields($self->_paymentFields, $fields, $errors);
    }
    CRM_Core_Payment_Form::validateCreditCard($fields, $errors);

    foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
      if ($greetingType = CRM_Utils_Array::value($greeting, $fields)) {
        $customizedValue = CRM_Core_OptionGroup::getValue($greeting, 'Customized', 'name');
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
   * @param $params
   * @param $form
   *
   * @return int|mixed|null|string
   */
  public static function computeAmount(&$params, &$form) {
    $amount = NULL;

    // first clean up the other amount field if present
    if (isset($params['amount_other'])) {
      $params['amount_other'] = CRM_Utils_Rule::cleanMoney($params['amount_other']);
    }

    if (CRM_Utils_Array::value('amount', $params) == 'amount_other_radio' || !empty($params['amount_other'])) {
      $amount = $params['amount_other'];
    }
    elseif (!empty($params['pledge_amount'])) {
      $amount = 0;
      foreach ($params['pledge_amount'] as $paymentId => $dontCare) {
        $amount += CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_PledgePayment', $paymentId, 'scheduled_amount');
      }
    }
    else {
      if (!empty($form->_values['amount'])) {
        $amountID = CRM_Utils_Array::value('amount', $params);

        if ($amountID) {
          $params['amount_level'] = CRM_Utils_Array::value('label', $form->_values[$amountID]);
          $amount = CRM_Utils_Array::value('value', $form->_values[$amountID]);
        }
      }
    }
    return $amount;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    $config = CRM_Core_Config::singleton();
    // we first reset the confirm page so it accepts new values
    $this->controller->resetPage('Confirm');

    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    //carry campaign from profile.
    if (array_key_exists('contribution_campaign_id', $params)) {
      $params['campaign_id'] = $params['contribution_campaign_id'];
    }

    if (!empty($params['onbehalfof_id'])) {
      $params['organization_id'] = $params['onbehalfof_id'];
    }

    $params['currencyID'] = $config->defaultCurrency;

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
          if ($selectedPriceOptionID = CRM_Utils_Array::value("price_{$priceField->id}", $params)) {
            switch ($priceField->name) {
              case 'membership_amount':
                $this->_params['selectMembership'] = $params['selectMembership'] = CRM_Utils_Array::value('membership_type_id', $priceOptions[$selectedPriceOptionID]);
                $this->set('selectMembership', $params['selectMembership']);
                if (CRM_Utils_Array::value('is_separate_payment', $this->_membershipBlock) == 0) {
                  $this->_values['amount'] = CRM_Utils_Array::value('amount', $priceOptions[$selectedPriceOptionID]);
                }
                break;

              case 'contribution_amount':
                $params['amount'] = $selectedPriceOptionID;
                $this->_values['amount'] = CRM_Utils_Array::value('amount', $priceOptions[$selectedPriceOptionID]);
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

    if (($this->_values['is_pay_later'] &&
        empty($this->_paymentProcessor) &&
        !array_key_exists('hidden_processor', $params)) ||
      (!empty($params['payment_processor']) && $params['payment_processor'] == 0)) {
      $params['is_pay_later'] = 1;
    }
    else {
      $params['is_pay_later'] = 0;
    }

    $this->set('is_pay_later', $params['is_pay_later']);
    // assign pay later stuff
    $this->_params['is_pay_later'] = CRM_Utils_Array::value('is_pay_later', $params, FALSE);
    $this->assign('is_pay_later', $params['is_pay_later']);
    if ($params['is_pay_later']) {
      $this->assign('pay_later_text', $this->_values['pay_later_text']);
      $this->assign('pay_later_receipt', $this->_values['pay_later_receipt']);
    }

    // from here on down, $params['amount'] holds a monetary value (or null) rather than an option ID
    $params['amount'] = self::computeAmount($params, $this);
    $params['separate_amount'] = $params['amount'];
    $memFee = NULL;
    if (!empty($params['selectMembership'])) {
      if (!empty($this->_membershipTypeValues)) {
        $membershipTypeValues = $this->_membershipTypeValues[$params['selectMembership']];
      }
      else {
        $membershipTypeValues = CRM_Member_BAO_Membership::buildMembershipTypeValues($this,
          $params['selectMembership']
        );
      }
      $memFee = $membershipTypeValues['minimum_fee'];
      if (!$params['amount'] && !$this->_separateMembershipPayment) {
        $params['amount'] = $memFee ? $memFee : 0;
      }
    }

    //If the membership & contribution is used in contribution page & not separate payment
    $fieldId = $memPresent = $membershipLabel = $fieldOption = $is_quick_config = NULL;
    $proceFieldAmount = 0;
    if (property_exists($this, '_separateMembershipPayment') && $this->_separateMembershipPayment == 0) {
      $is_quick_config = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_priceSetId, 'is_quick_config');
      if ($is_quick_config) {
        foreach ($this->_priceSet['fields'] as $fieldKey => $fieldVal) {
          if ($fieldVal['name'] == 'membership_amount' && !empty($params['price_' . $fieldKey ])) {
            $fieldId     = $fieldVal['id'];
            $fieldOption = $params['price_' . $fieldId];
            $proceFieldAmount += $fieldVal['options'][$this->_submitValues['price_' . $fieldId]]['amount'];
            $memPresent  = TRUE;
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

    if ($priceSetId = CRM_Utils_Array::value('priceSetId', $params)) {
      $lineItem = array();
      $is_quick_config = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $priceSetId, 'is_quick_config' );
      if ( $is_quick_config ) {
        foreach ( $this->_values['fee'] as $key => & $val ) {
          if ( $val['name'] == 'other_amount' && $val['html_type'] == 'Text' && array_key_exists( 'price_'.$key, $params ) && $params['price_'.$key] != 0 ) {
            foreach ( $val['options'] as $optionKey => & $options ) {
              $options['amount'] = CRM_Utils_Array::value( 'price_'.$key, $params );
              break;
            }
            $params['price_'.$key] = 1;
            break;
          }
        }
      }

      $component = '';
      if ($this->_membershipBlock) {
        $component = 'membership';
      }
      CRM_Price_BAO_PriceSet::processAmount($this->_values['fee'], $params, $lineItem[$priceSetId], $component);

      if ($proceFieldAmount) {
        $lineItem[$params['priceSetId']][$fieldOption]['line_total'] = $proceFieldAmount;
        $lineItem[$params['priceSetId']][$fieldOption]['unit_price'] = $proceFieldAmount;
        if (!$this->_membershipBlock['is_separate_payment']) {
          $params['amount'] = $proceFieldAmount; //require when separate membership not used
        }
      }
      $this->set('lineItem', $lineItem);
    }

    if ($this->_membershipBlock['is_separate_payment'] && !empty($params['separate_amount'])) {
      $this->set('amount', $params['separate_amount']);
    } else {
      $this->set('amount', $params['amount']);
    }

    // generate and set an invoiceID for this transaction
    $invoiceID = md5(uniqid(rand(), TRUE));
    $this->set('invoiceID', $invoiceID);

    // required only if is_monetary and valid positive amount
    if ($this->_values['is_monetary'] &&
      is_array($this->_paymentProcessor) &&
      ((float ) $params['amount'] > 0.0 || $memFee > 0.0)
    ) {

      // default mode is direct
      $this->set('contributeMode', 'direct');

      if ($this->_paymentProcessor &&
        $this->_paymentProcessor['billing_mode'] & CRM_Core_Payment::BILLING_MODE_BUTTON
      ) {
        //get the button name
        $buttonName = $this->controller->getButtonName();
        if (in_array($buttonName,
            array($this->_expressButtonName, $this->_expressButtonName . '_x', $this->_expressButtonName . '_y')
          ) && empty($params['is_pay_later'])) {
          $this->set('contributeMode', 'express');

          $donateURL           = CRM_Utils_System::url('civicrm/contribute', '_qf_Contribute_display=1');
          $params['cancelURL'] = CRM_Utils_System::url('civicrm/contribute/transact', "_qf_Main_display=1&qfKey={$params['qfKey']}", TRUE, NULL, FALSE);
          $params['returnURL'] = CRM_Utils_System::url('civicrm/contribute/transact', "_qf_Confirm_display=1&rfp=1&qfKey={$params['qfKey']}", TRUE, NULL, FALSE);
          $params['invoiceID'] = $invoiceID;
          $params['description'] = ts('Online Contribution') . ': ' . (($this->_pcpInfo['title']) ? $this->_pcpInfo['title'] : $this->_values['title']);

          //default action is Sale
          $params['payment_action'] = 'Sale';

          $payment = CRM_Core_Payment::singleton($this->_mode, $this->_paymentProcessor, $this);
          $token = $payment->setExpressCheckout($params);
          if (is_a($token, 'CRM_Core_Error')) {
            CRM_Core_Error::displaySessionError($token);
            CRM_Utils_System::redirect($params['cancelURL']);
          }

          $this->set('token', $token);

          $paymentURL = $this->_paymentProcessor['url_site'] . "/cgi-bin/webscr?cmd=_express-checkout&token=$token";
          CRM_Utils_System::redirect($paymentURL);
        }
      }
      elseif ($this->_paymentProcessor &&
        $this->_paymentProcessor['billing_mode'] & CRM_Core_Payment::BILLING_MODE_NOTIFY
      ) {
        $this->set('contributeMode', 'notify');
      }
    }

    // should we skip the confirm page?
    if (empty($this->_values['is_confirm_enabled'])) {
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
  }

  /**
   * Handle Payment Processor switching
   * For contribution and event registration forms
   */
  static function preProcessPaymentOptions(&$form, $noFees = FALSE) {
    $form->_snippet = CRM_Utils_Array::value('snippet', $_GET);

    $form->_paymentProcessors = $noFees ? array() : $form->get('paymentProcessors');
    $form->_ppType = NULL;
    if ($form->_paymentProcessors) {
      // Fetch type during ajax request
      if (isset($_GET['type']) && $form->_snippet) {
        $form->_ppType = $_GET['type'];
      }
      // Remember type during form post
      elseif (!empty($form->_submitValues)) {
        $form->_ppType = CRM_Utils_Array::value('payment_processor', $form->_submitValues);
        $form->_paymentProcessor = CRM_Utils_Array::value($form->_ppType, $form->_paymentProcessors);
        $form->set('type', $form->_ppType);
        $form->set('mode', $form->_mode);
        $form->set('paymentProcessor', $form->_paymentProcessor);
      }
      // Set default payment processor
      else {
        foreach ($form->_paymentProcessors as $values) {
          if (!empty($values['is_default']) || count($form->_paymentProcessors) == 1) {
            $form->_ppType = $values['id'];
            break;
          }
        }
      }
      if ($form->_ppType) {
        CRM_Core_Payment_ProcessorForm::preProcess($form);
      }

      //get payPal express id and make it available to template
      foreach ($form->_paymentProcessors as $ppId => $values) {
        $payPalExpressId = ($values['payment_processor_type'] == 'PayPal_Express') ? $values['id'] : 0;
        $form->assign('payPalExpressId', $payPalExpressId);
        if ($payPalExpressId) {
          break;
        }
      }
      if (!$form->_snippet) {
        // Add JS to show icons for the accepted credit cards
        $creditCardTypes = CRM_Core_Payment_Form::getCreditCardCSSNames();
        CRM_Core_Resources::singleton()
          ->addScriptFile('civicrm', 'templates/CRM/Core/BillingBlock.js', 10)
          // workaround for CRM-13634
          // ->addSetting(array('config' => array('creditCardTypes' => $creditCardTypes)));
          ->addScript('CRM.config.creditCardTypes = ' . json_encode($creditCardTypes) . ';');
      }
    }
    $form->assign('ppType', $form->_ppType);
  }
}

