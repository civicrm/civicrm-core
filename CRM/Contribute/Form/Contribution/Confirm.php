<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * form to process actions on the group aspect of Custom Data
 */
class CRM_Contribute_Form_Contribution_Confirm extends CRM_Contribute_Form_ContributionBase {

  /**
   * the id of the contact associated with this contribution
   *
   * @var int
   * @public
   */
  public $_contactID;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $config = CRM_Core_Config::singleton();
    parent::preProcess();

    // lineItem isn't set until Register postProcess
    $this->_lineItem = $this->get('lineItem');
    $this->_paymentProcessor = $this->get('paymentProcessor');

    if ($this->_contributeMode == 'express') {
      // rfp == redirect from paypal
      $rfp = CRM_Utils_Request::retrieve('rfp', 'Boolean',
        CRM_Core_DAO::$_nullObject, FALSE, NULL, 'GET'
      );
      if ($rfp) {
        $payment = CRM_Core_Payment::singleton($this->_mode, $this->_paymentProcessor, $this);
        $expressParams = $payment->getExpressCheckoutDetails($this->get('token'));

        $this->_params['payer'] = $expressParams['payer'];
        $this->_params['payer_id'] = $expressParams['payer_id'];
        $this->_params['payer_status'] = $expressParams['payer_status'];

        CRM_Core_Payment_Form::mapParams($this->_bltID, $expressParams, $this->_params, FALSE);

        // fix state and country id if present
        if (!empty($this->_params["billing_state_province_id-{$this->_bltID}"]) && $this->_params["billing_state_province_id-{$this->_bltID}"]) {
          $this->_params["billing_state_province-{$this->_bltID}"] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($this->_params["billing_state_province_id-{$this->_bltID}"]);
        }
        if (!empty($this->_params["billing_country_id-{$this->_bltID}"]) && $this->_params["billing_country_id-{$this->_bltID}"]) {
          $this->_params["billing_country-{$this->_bltID}"] = CRM_Core_PseudoConstant::countryIsoCode($this->_params["billing_country_id-{$this->_bltID}"]);
        }

        // set a few other parameters for PayPal
        $this->_params['token'] = $this->get('token');

        $this->_params['amount'] = $this->get('amount');

        if (!empty($this->_membershipBlock)){
          $this->_params['selectMembership'] = $this->get('selectMembership');
        }
        // we use this here to incorporate any changes made by folks in hooks
        $this->_params['currencyID'] = $config->defaultCurrency;

        $this->_params['payment_action'] = 'Sale';

        // also merge all the other values from the profile fields
        $values = $this->controller->exportValues('Main');
        $skipFields = array(
          'amount', 'amount_other',
          "billing_street_address-{$this->_bltID}",
          "billing_city-{$this->_bltID}",
          "billing_state_province_id-{$this->_bltID}",
          "billing_postal_code-{$this->_bltID}",
          "billing_country_id-{$this->_bltID}",
        );
        foreach ($values as $name => $value) {
          // skip amount field
          if (!in_array($name, $skipFields)) {
            $this->_params[$name] = $value;
          }
        }
        $this->set('getExpressCheckoutDetails', $this->_params);
      }
      else {
        $this->_params = $this->get('getExpressCheckoutDetails');
      }
    }
    else {
      $this->_params = $this->controller->exportValues('Main');

      if (!empty($this->_params["billing_state_province_id-{$this->_bltID}"])) {
        $this->_params["billing_state_province-{$this->_bltID}"] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($this->_params["billing_state_province_id-{$this->_bltID}"]);
      }
      if (!empty($this->_params["billing_country_id-{$this->_bltID}"])) {
        $this->_params["billing_country-{$this->_bltID}"] = CRM_Core_PseudoConstant::countryIsoCode($this->_params["billing_country_id-{$this->_bltID}"]);
      }

      if (isset($this->_params['credit_card_exp_date'])) {
        $this->_params['year'] = CRM_Core_Payment_Form::getCreditCardExpirationYear($this->_params);
        $this->_params['month'] = CRM_Core_Payment_Form::getCreditCardExpirationMonth($this->_params);
      }
      $this->_params['ip_address'] = $_SERVER['REMOTE_ADDR'];
      // hack for safari
      if ($this->_params['ip_address'] == '::1') {
        $this->_params['ip_address'] = '127.0.0.1';
      }
      $this->_params['amount'] = $this->get('amount');

      $this->_useForMember = $this->get('useForMember');

      if (isset($this->_params['amount'])) {
        $priceField = new CRM_Price_DAO_Field();
        $priceField->price_set_id = $this->_params['priceSetId'];
        $priceField->orderBy('weight');
        $priceField->find();
        $contriPriceId = NULL;
        $isQuickConfig = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_Set', $this->_params['priceSetId'], 'is_quick_config');
        while ($priceField->fetch()) {
          if ($priceField->name == "contribution_amount") {
            $contriPriceId = $priceField->id;
          }
          if ($isQuickConfig && !empty($this->_params["price_{$priceField->id}"])) {
            if ($this->_values['fee'][$priceField->id]['html_type'] != 'Text') {
            $this->_params['amount_level'] = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_FieldValue',
              $this->_params["price_{$priceField->id}"], 'label');
            }
            if ($priceField->name == "membership_amount") {
              $this->_params['selectMembership'] = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_FieldValue',
                $this->_params["price_{$priceField->id}"], 'membership_type_id');
            }
          } // if seperate payment we set contribution amount to be null, so that it will not show contribution amount same as membership amount.
          elseif ((CRM_Utils_Array::value('is_separate_payment', $this->_membershipBlock))
              && CRM_Utils_Array::value($priceField->id, $this->_values['fee'])
              && ($this->_values['fee'][$priceField->id]['name'] == "other_amount")
              && CRM_Utils_Array::value("price_{$contriPriceId}", $this->_params) < 1
              && !CRM_Utils_Array::value("price_{$priceField->id}", $this->_params)) {
              $this->_params['amount'] = null;
          }
        }
      }
      $this->_params['currencyID'] = $config->defaultCurrency;
      $this->_params['payment_action'] = 'Sale';
    }

    $this->_params['is_pay_later'] = $this->get('is_pay_later');
    $this->assign('is_pay_later', $this->_params['is_pay_later']);
    if ($this->_params['is_pay_later']) {
      $this->assign('pay_later_receipt', $this->_values['pay_later_receipt']);
    }
    // if onbehalf-of-organization
    if (CRM_Utils_Array::value('hidden_onbehalf_profile', $this->_params)) {
      if (CRM_Utils_Array::value('org_option', $this->_params) &&
        CRM_Utils_Array::value('organization_id', $this->_params)
      ) {
        if (CRM_Utils_Array::value('onbehalfof_id', $this->_params)) {
          $this->_params['organization_id'] = $this->_params['onbehalfof_id'];
        }
      }

      $this->_params['organization_name'] = $this->_params['onbehalf']['organization_name'];
      $addressBlocks = array(
        'street_address', 'city', 'state_province',
        'postal_code', 'country', 'supplemental_address_1',
        'supplemental_address_2', 'supplemental_address_3',
        'postal_code_suffix', 'geo_code_1', 'geo_code_2', 'address_name',
      );

      $blocks = array('email', 'phone', 'im', 'url', 'openid');
      foreach ($this->_params['onbehalf'] as $loc => $value) {
        $field = $typeId = NULL;
        if (strstr($loc, '-')) {
          list($field, $locType) = explode('-', $loc);
        }

        if (in_array($field, $addressBlocks)) {
          if ($locType == 'Primary') {
            $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
            $locType = $defaultLocationType->id;
          }

          if ($field == 'country') {
            $value = CRM_Core_PseudoConstant::countryIsoCode($value);
          }
          elseif ($field == 'state_province') {
            $value = CRM_Core_PseudoConstant::stateProvinceAbbreviation($value);
          }

          $isPrimary = 1;
          if (isset($this->_params['onbehalf_location']['address'])
               && count($this->_params['onbehalf_location']['address']) > 0) {
            $isPrimary = 0;
          }

          $this->_params['onbehalf_location']['address'][$locType][$field] = $value;
          if (!CRM_Utils_Array::value('is_primary', $this->_params['onbehalf_location']['address'][$locType])) {
            $this->_params['onbehalf_location']['address'][$locType]['is_primary'] = $isPrimary;
        }
          $this->_params['onbehalf_location']['address'][$locType]['location_type_id'] = $locType;
        }
        elseif (in_array($field, $blocks)) {
          if (!$typeId || is_numeric($typeId)) {
            $blockName     = $fieldName = $field;
            $locationType  = 'location_type_id';
            if ( $locType == 'Primary' ) {
              $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
              $locationValue = $defaultLocationType->id;
            }
            else {
              $locationValue = $locType;
            }
            $locTypeId     = '';
            $phoneExtField = array();

            if ($field == 'url') {
              $blockName     = 'website';
              $locationType  = 'website_type_id';
              $locationValue = CRM_Utils_Array::value("{$loc}-website_type_id", $this->_params['onbehalf']);
            }
            elseif ($field == 'im') {
              $fieldName = 'name';
              $locTypeId = 'provider_id';
              $typeId    = $this->_params['onbehalf']["{$loc}-provider_id"];
            }
            elseif ($field == 'phone') {
              list($field, $locType, $typeId) = explode('-', $loc);
              $locTypeId = 'phone_type_id';

              //check if extension field exists
              $extField = str_replace('phone','phone_ext', $loc);
              if (isset($this->_params['onbehalf'][$extField])) {
                $phoneExtField = array('phone_ext' => $this->_params['onbehalf'][$extField]);
              }
            }

            $isPrimary = 1;
            if ( isset ($this->_params['onbehalf_location'][$blockName] )
              && count( $this->_params['onbehalf_location'][$blockName] ) > 0 ) {
                $isPrimary = 0;
            }
            if ($locationValue) {
              $blockValues = array(
                $fieldName    => $value,
                $locationType => $locationValue,
                'is_primary'  => $isPrimary,
              );

              if ($locTypeId) {
                $blockValues = array_merge($blockValues, array($locTypeId  => $typeId));
              }
              if (!empty($phoneExtField)) {
                $blockValues = array_merge($blockValues, $phoneExtField);
              }

              $this->_params['onbehalf_location'][$blockName][] = $blockValues;
            }
          }
        }
        elseif (strstr($loc, 'custom')) {
          if ($value && isset($this->_params['onbehalf']["{$loc}_id"])) {
            $value = $this->_params['onbehalf']["{$loc}_id"];
          }
          $this->_params['onbehalf_location']["{$loc}"] = $value;
        }
        else {
          if ($loc == 'contact_sub_type') {
            $this->_params['onbehalf_location'][$loc] = $value;
          }
          else {
            $this->_params['onbehalf_location'][$field] = $value;
          }
        }
      }
    }
    elseif (CRM_Utils_Array::value('is_for_organization', $this->_values)) {
      // no on behalf of an organization, CRM-5519
      // so reset loc blocks from main params.
      foreach (array(
        'phone', 'email', 'address') as $blk) {
        if (isset($this->_params[$blk])) {
          unset($this->_params[$blk]);
        }
      }
    }

    // if auto renew checkbox is set, initiate a open-ended recurring membership
    if ((CRM_Utils_Array::value('selectMembership', $this->_params) ||
        CRM_Utils_Array::value('priceSetId', $this->_params)
      ) &&
      CRM_Utils_Array::value('is_recur', $this->_paymentProcessor) &&
      CRM_Utils_Array::value('auto_renew', $this->_params) &&
      !CRM_Utils_Array::value('is_recur', $this->_params) &&
      !CRM_Utils_Array::value('frequency_interval', $this->_params)
    ) {

      $this->_params['is_recur'] = $this->_values['is_recur'] = 1;
      // check if price set is not quick config
      if (CRM_Utils_Array::value('priceSetId', $this->_params) && !$isQuickConfig) {
        list($this->_params['frequency_interval'], $this->_params['frequency_unit']) = CRM_Price_BAO_Set::getRecurDetails($this->_params['priceSetId']);
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

    if ($this->_pcpId) {
      $params = $this->processPcp($this, $this->_params);
      $this->_params = $params;
    }
    $this->_params['invoiceID'] = $this->get('invoiceID');

    //carry campaign from profile.
    if (array_key_exists('contribution_campaign_id', $this->_params)) {
      $this->_params['campaign_id'] = $this->_params['contribution_campaign_id'];
    }

    // assign contribution page id to the template so we can add css class for it
    $this->assign('contributionPageID', $this->_id);

    $this->set('params', $this->_params);
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $this->assignToTemplate();

    $params = $this->_params;
    $honor_block_is_active = $this->get('honor_block_is_active');
    // make sure we have values for it
    if ($honor_block_is_active &&
      ((!empty($params['honor_first_name']) && !empty($params['honor_last_name'])) ||
        (!empty($params['honor_email']))
      )
    ) {
      $this->assign('honor_block_is_active', $honor_block_is_active);
      $this->assign('honor_block_title', CRM_Utils_Array::value('honor_block_title', $this->_values));

      $prefix = CRM_Core_PseudoConstant::individualPrefix();
      $honor = CRM_Core_PseudoConstant::honor();
      $this->assign('honor_type', CRM_Utils_Array::value($params['honor_type_id'], $honor));
      $this->assign('honor_prefix', CRM_Utils_Array::value($params['honor_prefix_id'], $prefix));
      $this->assign('honor_first_name', $params['honor_first_name']);
      $this->assign('honor_last_name', $params['honor_last_name']);
      $this->assign('honor_email', $params['honor_email']);
    }
    $this->assign('receiptFromEmail', CRM_Utils_Array::value('receipt_from_email', $this->_values));
    $amount_block_is_active = $this->get('amount_block_is_active');
    $this->assign('amount_block_is_active', $amount_block_is_active);

    if (CRM_Utils_Array::value('selectProduct', $params) && $params['selectProduct'] != 'no_thanks') {
      $option = CRM_Utils_Array::value('options_' . $params['selectProduct'], $params);
      $productID = $params['selectProduct'];
      CRM_Contribute_BAO_Premium::buildPremiumBlock($this, $this->_id, FALSE,
        $productID, $option
      );
      $this->set('productID', $productID);
      $this->set('option', $option);
    }
    $config = CRM_Core_Config::singleton();
    if (in_array('CiviMember', $config->enableComponents)) {
      if (isset($params['selectMembership']) &&
        $params['selectMembership'] != 'no_thanks'
      ) {
        CRM_Member_BAO_Membership::buildMembershipBlock($this,
          $this->_id,
          FALSE,
          $params['selectMembership'],
          FALSE, NULL,
          $this->_membershipContactID
        );
      }
      else {
        $this->assign('membershipBlock', FALSE);
      }
    }
    $this->buildCustom($this->_values['custom_pre_id'], 'customPre', TRUE);
    $this->buildCustom($this->_values['custom_post_id'], 'customPost', TRUE);

    if (CRM_Utils_Array::value('hidden_onbehalf_profile', $params)) {
      $ufJoinParams = array(
        'module' => 'onBehalf',
        'entity_table' => 'civicrm_contribution_page',
        'entity_id' => $this->_id,
      );
      $OnBehalfProfile = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);
      $profileId = $OnBehalfProfile[0];

      $fieldTypes     = array('Contact', 'Organization');
      $contactSubType = CRM_Contact_BAO_ContactType::subTypes('Organization');
      $fieldTypes     = array_merge($fieldTypes, $contactSubType);
      if (is_array($this->_membershipBlock) && !empty($this->_membershipBlock)) {
        $fieldTypes = array_merge($fieldTypes, array('Membership'));
      }
      else {
        $fieldTypes = array_merge($fieldTypes, array('Contribution'));
      }

      $this->buildCustom($profileId, 'onbehalfProfile', TRUE, TRUE, $fieldTypes);
    }

    $this->_separateMembershipPayment = $this->get('separateMembershipPayment');
    $this->assign('is_separate_payment', $this->_separateMembershipPayment);
    if ($this->_priceSetId && !CRM_Core_DAO::getFieldValue('CRM_Price_DAO_Set', $this->_priceSetId, 'is_quick_config')) {
      $this->assign('lineItem', $this->_lineItem);
    } else {
      $this->assign('is_quick_config', 1);
      $this->_params['is_quick_config'] = 1;
    }
    $this->assign('priceSetID', $this->_priceSetId);
    $paymentProcessorType = CRM_Core_PseudoConstant::paymentProcessorType(false, null, 'name');
    if ($this->_paymentProcessor &&
      $this->_paymentProcessor['payment_processor_type_id'] == CRM_Utils_Array::key('Google_Checkout', $paymentProcessorType)
      && !$this->_params['is_pay_later'] && !($this->_amount == 0)
    ) {
      $this->_checkoutButtonName = $this->getButtonName('next', 'checkout');
      $this->add('image',
        $this->_checkoutButtonName,
        $this->_paymentProcessor['url_button'],
        array('class' => 'form-submit')
      );

      $this->addButtons(array(
          array(
            'type' => 'back',
            'name' => ts('<< Go Back'),
          ),
        )
      );
    }
    else {
      if ($this->_contributeMode == 'notify' || !$this->_values['is_monetary'] ||
        $this->_amount <= 0.0 || $this->_params['is_pay_later'] ||
        ($this->_separateMembershipPayment && $this->_amount <= 0.0)
      ) {
        $contribButton = ts('Continue >>');
        $this->assign('button', ts('Continue'));
      }
      else {
        $contribButton = ts('Make Contribution');
        $this->assign('button', ts('Make Contribution'));
      }
      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => $contribButton,
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
            'js' => array('onclick' => "return submitOnce(this,'" . $this->_name . "','" . ts('Processing') . "');"),
          ),
          array(
            'type' => 'back',
            'name' => ts('Go Back'),
          ),
        )
      );
    }

    $defaults = array();
    $fields = array();
    foreach ($this->_fields as $name => $dontCare) {
      if ($name == 'onbehalf') {
        foreach ($dontCare as $key => $value) {
          $fields['onbehalf'][$key] = 1;
        }
      }
      else {
        $fields[$name] = 1;
      }
    }
    $fields["billing_state_province-{$this->_bltID}"] = $fields["billing_country-{$this->_bltID}"] = $fields["email-{$this->_bltID}"] = 1;

    $contact = $this->_params;
    foreach ($fields as $name => $dontCare) {
      if ($name == 'onbehalf') {
        foreach ($dontCare as $key => $value) {
          if (isset($contact['onbehalf'][$key])) {
            $defaults[$key] = $contact['onbehalf'][$key];
          }
          if (isset($contact['onbehalf']["{$key}_id"])) {
            $defaults["{$key}_id"] = $contact['onbehalf']["{$key}_id"];
          }
        }
      }
      elseif (isset($contact[$name])) {
        $defaults[$name] = $contact[$name];
        if (substr($name, 0, 7) == 'custom_') {
          $timeField = "{$name}_time";
          if (isset($contact[$timeField])) {
            $defaults[$timeField] = $contact[$timeField];
          }
          if (isset($contact["{$name}_id"])) {
            $defaults["{$name}_id"] = $contact["{$name}_id"];
          }
        }
        elseif (in_array($name, array('addressee', 'email_greeting', 'postal_greeting'))
          && CRM_Utils_Array::value($name . '_custom', $contact)
        ) {
          $defaults[$name . '_custom'] = $contact[$name . '_custom'];
        }
      }
    }

    $this->assign('useForMember', $this->get('useForMember'));

    // now fix all state country selectors
    CRM_Core_BAO_Address::fixAllStateSelects($this, $defaults);

    $this->setDefaults($defaults);

    $this->freeze();
  }

  /**
   * overwrite action, since we are only showing elements in frozen mode
   * no help display needed
   *
   * @return int
   * @access public
   */
  function getAction() {
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      return CRM_Core_Action::VIEW | CRM_Core_Action::PREVIEW;
    }
    else {
      return CRM_Core_Action::VIEW;
    }
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {}

  /**
   * Process the form
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $config = CRM_Core_Config::singleton();
    $contactID = $this->_userID;

    // add a description field at the very beginning
    $this->_params['description'] = ts('Online Contribution') . ': ' . (($this->_pcpInfo['title']) ? $this->_pcpInfo['title'] : $this->_values['title']);

    // also add accounting code
    $this->_params['accountingCode'] = CRM_Utils_Array::value('accountingCode',
      $this->_values
    );

    // fix currency ID
    $this->_params['currencyID'] = $config->defaultCurrency;

    $premiumParams = $membershipParams = $tempParams = $params = $this->_params;

    //carry payment processor id.
    if ($paymentProcessorId = CRM_Utils_Array::value('id', $this->_paymentProcessor)) {
      $this->_params['payment_processor_id'] = $paymentProcessorId;
      foreach (array(
        'premiumParams', 'membershipParams', 'tempParams', 'params') as $p) {
        ${$p}['payment_processor_id'] = $paymentProcessorId;
      }
    }

    $fields = array();

    if (CRM_Utils_Array::value('image_URL', $params)) {
      CRM_Contact_BAO_Contact::processImageParams($params);
    }

    // set email for primary location.
    $fields['email-Primary'] = 1;

    // get the add to groups
    $addToGroups = array();

    // now set the values for the billing location.
    foreach ($this->_fields as $name => $value) {
      $fields[$name] = 1;

      // get the add to groups for uf fields
      if (CRM_Utils_Array::value('add_to_group_id', $value)) {
        $addToGroups[$value['add_to_group_id']] = $value['add_to_group_id'];
      }
    }

    if (!array_key_exists('first_name', $fields)) {
      $nameFields = array('first_name', 'middle_name', 'last_name');
      foreach ($nameFields as $name) {
        $fields[$name] = 1;
        if (array_key_exists("billing_$name", $params)) {
          $params[$name] = $params["billing_{$name}"];
          $params['preserveDBName'] = TRUE;
        }
      }
    }

    // billing email address
    $fields["email-{$this->_bltID}"] = 1;

    //unset the billing parameters if it is pay later mode
    //to avoid creation of billing location
    if ($params['is_pay_later']) {
      $billingFields = array(
        'billing_first_name',
        'billing_middle_name',
        'billing_last_name',
        "billing_street_address-{$this->_bltID}",
        "billing_city-{$this->_bltID}",
        "billing_state_province-{$this->_bltID}",
        "billing_state_province_id-{$this->_bltID}",
        "billing_postal_code-{$this->_bltID}",
        "billing_country-{$this->_bltID}",
        "billing_country_id-{$this->_bltID}",
      );

      foreach ($billingFields as $value) {
        unset($params[$value]);
        unset($fields[$value]);
      }
    }

    // if onbehalf-of-organization contribution, take out
    // organization params in a separate variable, to make sure
    // normal behavior is continued. And use that variable to
    // process on-behalf-of functionality.
    if (CRM_Utils_Array::value('hidden_onbehalf_profile', $this->_params)) {
      $behalfOrganization = array();
      $orgFields = array('organization_name', 'organization_id', 'org_option');
      foreach ($orgFields as $fld) {
        if (array_key_exists($fld, $params)) {
          $behalfOrganization[$fld] = $params[$fld];
          unset($params[$fld]);
        }
      }

      if (is_array($params['onbehalf']) && !empty($params['onbehalf'])) {
        foreach ($params['onbehalf'] as $fld => $values) {
          if (strstr($fld, 'custom_')) {
            $behalfOrganization[$fld] = $values;
          }
          elseif (!(strstr($fld, '-'))) {
            if (in_array($fld, array(
              'contribution_campaign_id', 'member_campaign_id'))) {
              $fld = 'campaign_id';
            }
            else {
              $behalfOrganization[$fld] = $values;
            }
            $this->_params[$fld] = $values;
          }
        }
      }

      if (array_key_exists('onbehalf_location', $params) && is_array($params['onbehalf_location'])) {
        foreach ($params['onbehalf_location'] as $block => $vals) {
          //fix for custom data (of type checkbox, multi-select)
          if ( substr($block, 0, 7) == 'custom_' ) {
            continue;
          }
          // fix the index of block elements
          if (is_array($vals) ) {
            foreach ( $vals as $key => $val ) {
              //dont adjust the index of address block as
              //it's index is WRT to location type
              $newKey = ($block == 'address') ? $key : ++$key;
              $behalfOrganization[$block][$newKey] = $val;
            }
          }
        }
        unset($params['onbehalf_location']);
      }
      if (CRM_Utils_Array::value('onbehalf[image_URL]', $params)) {
        $behalfOrganization['image_URL'] = $params['onbehalf[image_URL]'];
      }
    }

    // check for profile double opt-in and get groups to be subscribed
    $subscribeGroupIds = CRM_Core_BAO_UFGroup::getDoubleOptInGroupIds($params, $contactID);

    // since we are directly adding contact to group lets unset it from mailing
    if (!empty($addToGroups)) {
      foreach ($addToGroups as $groupId) {
        if (isset($subscribeGroupIds[$groupId])) {
          unset($subscribeGroupIds[$groupId]);
        }
      }
    }

    foreach ($addToGroups as $k) {
      if (array_key_exists($k, $subscribeGroupIds)) {
        unset($addToGroups[$k]);
      }
    }

    if (!isset($contactID)) {
      $dupeParams = $params;
      if (CRM_Utils_Array::value('onbehalf', $dupeParams)) {
        unset($dupeParams['onbehalf']);
      }

      $dedupeParams = CRM_Dedupe_Finder::formatParams($dupeParams, 'Individual');
      $dedupeParams['check_permission'] = FALSE;
      $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual');

      // if we find more than one contact, use the first one
      $contact_id = CRM_Utils_Array::value(0, $ids);

      // Fetch default greeting id's if creating a contact
      if (!$contact_id) {
        foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
          if (!isset($params[$greeting])) {
            $params[$greeting] = CRM_Contact_BAO_Contact_Utils::defaultGreeting('Individual', $greeting);
          }
        }
      }

      $contactID = CRM_Contact_BAO_Contact::createProfileContact(
        $params,
        $fields,
        $contact_id,
        $addToGroups,
        NULL,
        NULL,
        TRUE
      );
    }
    else {
      $ctype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactID, 'contact_type');
      $contactID = CRM_Contact_BAO_Contact::createProfileContact(
        $params,
        $fields,
        $contactID,
        $addToGroups,
        NULL,
        $ctype,
        TRUE
      );
    }

    // Make the contact ID associated with the contribution available at the Class level.
    // Also make available to the session.
    $this->set('contactID', $contactID);
    $this->_contactID = $contactID;

    //get email primary first if exist
    $subscribtionEmail = array('email' => CRM_Utils_Array::value('email-Primary', $params));
    if (!$subscribtionEmail['email']) {
      $subscribtionEmail['email'] = CRM_Utils_Array::value("email-{$this->_bltID}", $params);
    }
    // subscribing contact to groups
    if (!empty($subscribeGroupIds) && $subscribtionEmail['email']) {
      CRM_Mailing_Event_BAO_Subscribe::commonSubscribe($subscribeGroupIds, $subscribtionEmail, $contactID);
    }

    // If onbehalf-of-organization contribution / signup, add organization
    // and it's location.
    if (isset($params['hidden_onbehalf_profile']) && isset($behalfOrganization['organization_name'])) {
      $ufFields = array();
      foreach ($this->_fields['onbehalf'] as $name => $value) {
        $ufFields[$name] = 1;
      }
      self::processOnBehalfOrganization($behalfOrganization, $contactID, $this->_values,
        $this->_params, $ufFields
      );
    }

    // lets store the contactID in the session
    // for things like tell a friend
    $session = CRM_Core_Session::singleton();
    if (!$session->get('userID')) {
      $session->set('transaction.userID', $contactID);
    }
    else {
      $session->set('transaction.userID', NULL);
    }

    $this->_useForMember = $this->get('useForMember');

    // store the fact that this is a membership and membership type is selected
    $processMembership = FALSE;
    if ((CRM_Utils_Array::value('selectMembership', $membershipParams) &&
        $membershipParams['selectMembership'] != 'no_thanks'
      ) ||
      $this->_useForMember
    ) {
      $processMembership = TRUE;

      if (!$this->_useForMember) {
        $this->assign('membership_assign', TRUE);
        $this->set('membershipTypeID', $this->_params['selectMembership']);
      }

      if ($this->_action & CRM_Core_Action::PREVIEW) {
        $membershipParams['is_test'] = 1;
      }
      if ($this->_params['is_pay_later']) {
        $membershipParams['is_pay_later'] = 1;
      }
    }

    if ($processMembership) {
      CRM_Core_Payment_Form::mapParams($this->_bltID, $this->_params, $membershipParams, TRUE);

      // added new parameter for cms user contact id, needed to distinguish behaviour for on behalf of sign-ups
      if (isset($this->_params['related_contact'])) {
        $membershipParams['cms_contactID'] = $this->_params['related_contact'];
      }
      else {
        $membershipParams['cms_contactID'] = $contactID;
      }

      //inherit campaign from contirb page.
      if (!array_key_exists('campaign_id', $membershipParams)) {
        $membershipParams['campaign_id'] = CRM_Utils_Array::value('campaign_id', $this->_values);
      }

      if (!empty($membershipParams['onbehalf']) &&
        is_array($membershipParams['onbehalf']) &&
        CRM_Utils_Array::value('member_campaign_id', $membershipParams['onbehalf'])) {
        $this->_params['campaign_id'] = $membershipParams['onbehalf']['member_campaign_id'];
      }

      $customFieldsFormatted = $fieldTypes = array();
      if (!empty($membershipParams['onbehalf']) &&
        is_array($membershipParams['onbehalf'])) {
        foreach ($membershipParams['onbehalf'] as $key => $value) {
          if (strstr($key, 'custom_')) {
            $customFieldId = explode('_', $key);
            CRM_Core_BAO_CustomField::formatCustomField(
              $customFieldId[1],
              $customFieldsFormatted,
              $value,
              'Membership',
              NULL,
              $contactID
            );
          }
        }
        $fieldTypes = array('Contact', 'Organization', 'Membership');
      }

      $priceFieldIds = $this->get('memberPriceFieldIDS');

      if (!empty($priceFieldIds)) {
        $contributionTypeID = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_Set', $priceFieldIds['id'], 'financial_type_id');
        unset($priceFieldIds['id']);
        $membershipTypeIds = array();
        $membershipTypeTerms = array();
        foreach ($priceFieldIds as $priceFieldId) {
          if ($id = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_FieldValue', $priceFieldId, 'membership_type_id')) {
            $membershipTypeIds[] = $id;
            $term = 1;
            if ($term = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_FieldValue', $priceFieldId, 'membership_num_terms')) {
              $membershipTypeTerms[$id] = ($term > 1) ? $term : 1;
            }
            else {
              $membershipTypeTerms[$id] = 1;
            }
          }
        }
        $membershipParams['selectMembership'] = $membershipTypeIds;
        $membershipParams['financial_type_id'] = $contributionTypeID;
        $membershipParams['types_terms'] = $membershipTypeTerms;
      }
      if (CRM_Utils_Array::value('selectMembership', $membershipParams)) {
        // CRM-12233
        if ($this->_separateMembershipPayment && $this->_values['amount_block_is_active']) {
          foreach ($this->_values['fee'] as $key => $feeValues) {
            if ($feeValues['name'] == 'membership_amount') {
              $fieldId = $this->_params['price_' . $key];
              $this->_memLineItem[$this->_priceSetId][$fieldId] = $this->_lineItem[$this->_priceSetId][$fieldId];
              unset($this->_lineItem[$this->_priceSetId][$fieldId]);
              break;
            }
          }
        }

        CRM_Member_BAO_Membership::postProcessMembership($membershipParams, $contactID,
          $this, $premiumParams, $customFieldsFormatted,
          $fieldTypes
        );
      }
    }
    else {
      // at this point we've created a contact and stored its address etc
      // all the payment processors expect the name and address to be in the
      // so we copy stuff over to first_name etc.
      $paymentParams = $this->_params;
            $contributionTypeId = $this->_values['financial_type_id'];

      $fieldTypes = array();
      if (!empty($paymentParams['onbehalf']) &&
        is_array($paymentParams['onbehalf'])
      ) {
        foreach ($paymentParams['onbehalf'] as $key => $value) {
          if (strstr($key, 'custom_')) {
            $this->_params[$key] = $value;
          }
        }
        $fieldTypes = array('Contact', 'Organization', 'Contribution');
      }

      CRM_Contribute_BAO_Contribution_Utils::processConfirm($this, $paymentParams,
        $premiumParams, $contactID,
        $contributionTypeId,
        'contribution',
        $fieldTypes
      );
    }
  }

  /**
   * Process the form
   *
   * @return void
   * @access public
   */
  public function postProcessPremium($premiumParams, $contribution) {
    // assigning Premium information to receipt tpl
    $selectProduct = CRM_Utils_Array::value('selectProduct', $premiumParams);
    if ($selectProduct &&
      $selectProduct != 'no_thanks'
    ) {
      $startDate = $endDate = "";
      $this->assign('selectPremium', TRUE);
      $productDAO = new CRM_Contribute_DAO_Product();
      $productDAO->id = $selectProduct;
      $productDAO->find(TRUE);
      $this->assign('product_name', $productDAO->name);
      $this->assign('price', $productDAO->price);
      $this->assign('sku', $productDAO->sku);
      $this->assign('option', CRM_Utils_Array::value('options_' . $premiumParams['selectProduct'], $premiumParams));

      $periodType = $productDAO->period_type;

      if ($periodType) {
        $fixed_period_start_day = $productDAO->fixed_period_start_day;
        $duration_unit = $productDAO->duration_unit;
        $duration_interval = $productDAO->duration_interval;
        if ($periodType == 'rolling') {
          $startDate = date('Y-m-d');
        }
        elseif ($periodType == 'fixed') {
          if ($fixed_period_start_day) {
            $date      = explode('-', date('Y-m-d'));
            $month     = substr($fixed_period_start_day, 0, strlen($fixed_period_start_day) - 2);
            $day       = substr($fixed_period_start_day, -2) . "<br>";
            $year      = $date[0];
            $startDate = $year . '-' . $month . '-' . $day;
          }
          else {
            $startDate = date('Y-m-d');
          }
        }

        $date  = explode('-', $startDate);
        $year  = $date[0];
        $month = $date[1];
        $day   = $date[2];

        switch ($duration_unit) {
          case 'year':
            $year = $year + $duration_interval;
            break;

          case 'month':
            $month = $month + $duration_interval;
            break;

          case 'day':
            $day = $day + $duration_interval;
            break;

          case 'week':
            $day = $day + ($duration_interval * 7);
        }
        $endDate = date('Y-m-d H:i:s', mktime($hour, $minute, $second, $month, $day, $year));
        $this->assign('start_date', $startDate);
        $this->assign('end_date', $endDate);
      }

      $dao               = new CRM_Contribute_DAO_Premium();
      $dao->entity_table = 'civicrm_contribution_page';
      $dao->entity_id    = $this->_id;
      $dao->find(TRUE);
      $this->assign('contact_phone', $dao->premiums_contact_phone);
      $this->assign('contact_email', $dao->premiums_contact_email);

      //create Premium record
      $params = array(
        'product_id' => $premiumParams['selectProduct'],
        'contribution_id' => $contribution->id,
        'product_option' => CRM_Utils_Array::value('options_' . $premiumParams['selectProduct'], $premiumParams),
        'quantity' => 1,
        'start_date' => CRM_Utils_Date::customFormat($startDate, '%Y%m%d'),
        'end_date' => CRM_Utils_Date::customFormat($endDate, '%Y%m%d'),
      );
      if( CRM_Utils_Array::value( 'selectProduct', $premiumParams ) ){
        $daoPremiumsProduct             = new CRM_Contribute_DAO_PremiumsProduct();
        $daoPremiumsProduct->product_id = $premiumParams['selectProduct'];
        $daoPremiumsProduct->premiums_id = $dao->id;
        $daoPremiumsProduct->find(true);
        $params['financial_type_id'] = $daoPremiumsProduct->financial_type_id;
      }
      //Fixed For CRM-3901
      $daoContrProd = new CRM_Contribute_DAO_ContributionProduct();
      $daoContrProd->contribution_id = $contribution->id;
      if ($daoContrProd->find(TRUE)) {
        $params['id'] = $daoContrProd->id;
      }

      CRM_Contribute_BAO_Contribution::addPremium($params);
      if ($productDAO->cost && CRM_Utils_Array::value('financial_type_id', $params)) {
        $trxnParams = array(
          'cost' => $productDAO->cost,
          'currency' => $productDAO->currency,
          'financial_type_id' => $params['financial_type_id'],
          'contributionId' => $contribution->id
        );
        CRM_Core_BAO_FinancialTrxn::createPremiumTrxn($trxnParams);
      }
    }
    elseif ($selectProduct == 'no_thanks') {
      //Fixed For CRM-3901
      $daoContrProd = new CRM_Contribute_DAO_ContributionProduct();
      $daoContrProd->contribution_id = $contribution->id;
      if ($daoContrProd->find(TRUE)) {
        $daoContrProd->delete();
      }
    }
  }

  /**
   * Process the contribution
   *
   * @return CRM_Contribute_DAO_Contribution
   * @access public
   */
  static function processContribution(&$form,
    $params,
    $result,
    $contactID,
    $contributionType,
    $deductibleMode = TRUE,
    $pending        = FALSE,
    $online         = TRUE
  ) {
    $transaction = new CRM_Core_Transaction();
    $className   = get_class($form);
    $honorCId    = $recurringContributionID = NULL;

    if ($online && $form->get('honor_block_is_active')) {
      $honorCId = $form->createHonorContact();
    }

    // add these values for the recurringContrib function ,CRM-10188
    $params['financial_type_id'] = $contributionType->id;
    //@todo - this is being set from the form to resolve CRM-10188 - an
    // eNotice caused by it not being set @ the front end
    // however, we then get it being over-written with null for backend contributions
    // a better fix would be to set the values in the respective forms rather than require
    // a function being shared by two forms to deal with their respective values
    // moving it to the BAO & not taking the $form as a param would make sense here.
    if(!isset($params['is_email_receipt'])){
      $params['is_email_receipt'] = CRM_Utils_Array::value( 'is_email_receipt', $form->_values );
    }
    $recurringContributionID = self::processRecurringContribution($form, $params, $contactID, $contributionType, $online);

    if (!$online && isset($params['honor_contact_id'])) {
      $honorCId = $params['honor_contact_id'];
    }

    $config = CRM_Core_Config::singleton();
    // CRM-11885
    // if non_deductible_amount exists i.e. Additional Details fieldset was opened [and staff typed something] -> keep it.
    if (isset($params['non_deductible_amount']) && (!empty($params['non_deductible_amount']))) {
      $nonDeductibleAmount = $params['non_deductible_amount'];
    }
    // if non_deductible_amount does NOT exist - then calculate it depending on:
    // $contributionType->is_deductible and whether there is a product (premium).
    else {
      //if ($contributionType->is_deductible && $deductibleMode) {
      if ($contributionType->is_deductible) {
        if ($online && isset($params['selectProduct'])) {
          $selectProduct = CRM_Utils_Array::value('selectProduct', $params);
        }
        if (!$online && isset($params['product_name'][0])) {
          $selectProduct = $params['product_name'][0];
        }
        // if there is a product - compare the value to the contribution amount
        if (isset($selectProduct) &&
          $selectProduct != 'no_thanks'
        ) {
          $productDAO = new CRM_Contribute_DAO_Product();
          $productDAO->id = $selectProduct;
          $productDAO->find(TRUE);
          // product value exceeds contribution amount
          if ($params['amount'] < $productDAO->price) {
            $nonDeductibleAmount = $params['amount'];
          }
          // product value does NOT exceed contribution amount
          else {
            $nonDeductibleAmount = $productDAO->price;
          }
        }
        // contribution is deductible - but there is no product
        else {
          $nonDeductibleAmount = '0.00';
        }
      }
      // contribution is NOT deductible
      else {
        $nonDeductibleAmount = $params['amount'];
      }
    }

    $now = date('YmdHis');
    $receiptDate = CRM_Utils_Array::value('receipt_date', $params);
    if (CRM_Utils_Array::value('is_email_receipt', $form->_values)) {
      $receiptDate = $now;
    }

    //get the contrib page id.
    $campaignId = $contributionPageId = NULL;
    if ($online) {
      $contributionPageId = $form->_id;
      $campaignId = CRM_Utils_Array::value('campaign_id', $params);
      if (!array_key_exists('campaign_id', $params)) {
        $campaignId = CRM_Utils_Array::value('campaign_id', $form->_values);
      }
    }
    else {
      //also for offline we do support - CRM-7290
      $contributionPageId = CRM_Utils_Array::value('contribution_page_id', $params);
      $campaignId = CRM_Utils_Array::value('campaign_id', $params);
    }

    // first create the contribution record
    $contribParams = array(
      'contact_id' => $contactID,
      'financial_type_id'  => $contributionType->id,
      'contribution_page_id' => $contributionPageId,
      'receive_date' => (CRM_Utils_Array::value('receive_date', $params)) ? CRM_Utils_Date::processDate($params['receive_date']) : date('YmdHis'),
      'non_deductible_amount' => $nonDeductibleAmount,
      'total_amount' => $params['amount'],
      'amount_level' => CRM_Utils_Array::value('amount_level', $params),
      'invoice_id' => $params['invoiceID'],
      'currency' => $params['currencyID'],
      'source' =>
      (!$online || CRM_Utils_Array::value('source', $params)) ?
      CRM_Utils_Array::value('source', $params) :
      CRM_Utils_Array::value('description', $params),
      'is_pay_later' => CRM_Utils_Array::value('is_pay_later', $params, 0),
      //configure cancel reason, cancel date and thankyou date
      //from 'contribution' type profile if included
      'cancel_reason' => CRM_Utils_Array::value('cancel_reason', $params, 0),
      'cancel_date' =>
      isset($params['cancel_date']) ?
      CRM_Utils_Date::format($params['cancel_date']) :
      NULL,
      'thankyou_date' =>
      isset($params['thankyou_date']) ?
      CRM_Utils_Date::format($params['thankyou_date']) :
      NULL,
      'campaign_id' => $campaignId,
    );

    if (!$online && isset($params['thankyou_date'])) {
      $contribParams['thankyou_date'] = $params['thankyou_date'];
    }

    if (!$online || $form->_values['is_monetary']) {
      if (!CRM_Utils_Array::value('is_pay_later', $params)) {
        $contribParams['payment_instrument_id'] = 1;
      }
    }

    if (!$pending && $result) {
      $contribParams += array(
        'fee_amount' => CRM_Utils_Array::value('fee_amount', $result),
        'net_amount' => CRM_Utils_Array::value('net_amount', $result, $params['amount']),
        'trxn_id' => $result['trxn_id'],
        'receipt_date' => $receiptDate,
        // also add financial_trxn details as part of fix for CRM-4724
        'trxn_result_code' => CRM_Utils_Array::value('trxn_result_code', $result),
        'payment_processor' => CRM_Utils_Array::value('payment_processor', $result),
      );
    }

    if (isset($honorCId)) {
      $contribParams['honor_contact_id'] = $honorCId;
      $contribParams['honor_type_id'] = $params['honor_type_id'];
    }

    if ($recurringContributionID) {
      $contribParams['contribution_recur_id'] = $recurringContributionID;
    }

    $contribParams['contribution_status_id'] = $pending ? 2 : 1;

    $contribParams['is_test'] = 0;
    if ($form->_mode == 'test') {
      $contribParams['is_test'] = 1;
    }

    $ids = array();
    if (isset($contribParams['invoice_id'])) {
      $contribID = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution',
        $contribParams['invoice_id'],
        'id',
        'invoice_id'
      );
      if (isset($contribID)) {
        $ids['contribution'] = $contribID;
        $contribParams['id'] = $contribID;
      }
    }


    //create an contribution address
    if ($form->_contributeMode != 'notify'
      && !CRM_Utils_Array::value('is_pay_later', $params)
      && CRM_Utils_Array::value('is_monetary', $form->_values)
    ) {
      $contribParams['address_id'] = CRM_Contribute_BAO_Contribution::createAddress($params, $form->_bltID);
    }

    // CRM-4038: for non-en_US locales, CRM_Contribute_BAO_Contribution::add() expects localised amounts
    $contribParams['non_deductible_amount'] = trim(CRM_Utils_Money::format($contribParams['non_deductible_amount'], ' '));
    $contribParams['total_amount'] = trim(CRM_Utils_Money::format($contribParams['total_amount'], ' '));

    // Prepare soft contribution due to pcp or Submit Credit / Debit Card Contribution by admin.
    if (
      CRM_Utils_Array::value('pcp_made_through_id', $params) ||
      CRM_Utils_Array::value('soft_credit_to', $params)
    ) {
      // if its due to pcp
      if (CRM_Utils_Array::value('pcp_made_through_id', $params)) {
        $contribSoftContactId = CRM_Core_DAO::getFieldValue(
          'CRM_PCP_DAO_PCP',
          $params['pcp_made_through_id'],
          'contact_id'
        );
      }
      else {
        $contribSoftContactId = CRM_Utils_Array::value('soft_credit_to', $params);
      }

      // Pass these details onto with the contribution to make them
      // available at hook_post_process, CRM-8908
      $contribParams['soft_credit_to'] = $params['soft_credit_to'] = $contribSoftContactId;
    }

    if (isset($params['amount'])) {
      $contribParams['line_item'] = $form->_lineItem;
      //add contribution record
      $contribution = CRM_Contribute_BAO_Contribution::add($contribParams, $ids);
      if (is_a($contribution, 'CRM_Core_Error')) {
        $message = CRM_Core_Error::getMessages($contribution);
        CRM_Core_Error::fatal($message);
      }
    }

    // process soft credit / pcp pages
    CRM_Contribute_Form_Contribution_Confirm::processPcpSoft($params, $contribution);

    //handle pledge stuff.
    if (
      !CRM_Utils_Array::value('separate_membership_payment', $form->_params) &&
      CRM_Utils_Array::value('pledge_block_id', $form->_values) &&
      (CRM_Utils_Array::value('is_pledge', $form->_params) ||
        CRM_Utils_Array::value('pledge_id', $form->_values)
      )
    ) {

      if (CRM_Utils_Array::value('pledge_id', $form->_values)) {

        //when user doing pledge payments.
        //update the schedule when payment(s) are made
        foreach ($form->_params['pledge_amount'] as $paymentId => $dontCare) {
          $scheduledAmount = CRM_Core_DAO::getFieldValue(
            'CRM_Pledge_DAO_PledgePayment',
            $paymentId,
            'scheduled_amount',
            'id'
          );

          $pledgePaymentParams = array(
            'id' => $paymentId,
            'contribution_id' => $contribution->id,
            'status_id' => $contribution->contribution_status_id,
            'actual_amount' => $scheduledAmount,
          );


          CRM_Pledge_BAO_PledgePayment::add($pledgePaymentParams);
        }

        //update pledge status according to the new payment statuses
        CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($form->_values['pledge_id']);
      }
      else {
        //when user creating pledge record.
        $pledgeParams = array();
        $pledgeParams['contact_id'] = $contribution->contact_id;
        $pledgeParams['installment_amount'] = $pledgeParams['actual_amount'] = $contribution->total_amount;
        $pledgeParams['contribution_id'] = $contribution->id;
        $pledgeParams['contribution_page_id'] = $contribution->contribution_page_id;
        $pledgeParams['financial_type_id'] = $contribution->financial_type_id;
        $pledgeParams['frequency_interval'] = $params['pledge_frequency_interval'];
        $pledgeParams['installments'] = $params['pledge_installments'];
        $pledgeParams['frequency_unit'] = $params['pledge_frequency_unit'];
        if ($pledgeParams['frequency_unit'] == 'month') {
          $pledgeParams['frequency_day'] = intval(date("d"));
        }
        else {
          $pledgeParams['frequency_day'] = 1;
        }
        $pledgeParams['create_date'] = $pledgeParams['start_date'] = $pledgeParams['scheduled_date'] = date("Ymd");
        $pledgeParams['status_id'] = $contribution->contribution_status_id;
        $pledgeParams['max_reminders'] = $form->_values['max_reminders'];
        $pledgeParams['initial_reminder_day'] = $form->_values['initial_reminder_day'];
        $pledgeParams['additional_reminder_day'] = $form->_values['additional_reminder_day'];
        $pledgeParams['is_test'] = $contribution->is_test;
        $pledgeParams['acknowledge_date'] = date('Ymd');
        $pledgeParams['original_installment_amount'] = $pledgeParams['installment_amount'];

        //inherit campaign from contirb page.
        $pledgeParams['campaign_id'] = $campaignId;

        $pledge = CRM_Pledge_BAO_Pledge::create($pledgeParams);

        $form->_params['pledge_id'] = $pledge->id;

        //send acknowledgment email. only when pledge is created
        if ($pledge->id) {
          //build params to send acknowledgment.
          $pledgeParams['id'] = $pledge->id;
          $pledgeParams['receipt_from_name'] = $form->_values['receipt_from_name'];
          $pledgeParams['receipt_from_email'] = $form->_values['receipt_from_email'];

          //scheduled amount will be same as installment_amount.
          $pledgeParams['scheduled_amount'] = $pledgeParams['installment_amount'];

          //get total pledge amount.
          $pledgeParams['total_pledge_amount'] = $pledge->amount;

          CRM_Pledge_BAO_Pledge::sendAcknowledgment($form, $pledgeParams);
        }
      }
    }

    if ($online && $contribution) {
      CRM_Core_BAO_CustomValueTable::postProcess($form->_params,
        CRM_Core_DAO::$_nullArray,
        'civicrm_contribution',
        $contribution->id,
        'Contribution'
      );
    }
    elseif ($contribution) {
      //handle custom data.
      $params['contribution_id'] = $contribution->id;
      if (CRM_Utils_Array::value('custom', $params) &&
        is_array($params['custom']) &&
        !is_a($contribution, 'CRM_Core_Error')
      ) {
        CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_contribution', $contribution->id);
      }
    }
    // Save note
    if ($contribution && !empty($params['contribution_note'])) {
      $noteParams = array(
        'entity_table' => 'civicrm_contribution',
        'note' => $params['contribution_note'],
        'entity_id' => $contribution->id,
        'contact_id' => $contribution->contact_id,
        'modified_date' => date('Ymd'),
      );

      CRM_Core_BAO_Note::add($noteParams, array());
    }


    if (isset($params['related_contact'])) {
      $contactID = $params['related_contact'];
    }
    elseif (isset($params['cms_contactID'])) {
      $contactID = $params['cms_contactID'];
    }
    CRM_Contribute_BAO_Contribution_Utils::createCMSUser($params,
      $contactID,
      'email-' . $form->_bltID
    );

    //create contribution activity w/ individual and target
    //activity w/ organisation contact id when onbelf, CRM-4027
    $targetContactID = NULL;
    if (CRM_Utils_Array::value('hidden_onbehalf_profile', $params)) {
      $targetContactID = $contribution->contact_id;
      $contribution->contact_id = $contactID;
    }

    // create an activity record
    if ($contribution) {
      CRM_Activity_BAO_Activity::addActivity($contribution, NULL, $targetContactID);
    }

    $transaction->commit();
    return $contribution;
  }

  /**
   * Create the recurring contribution record
   *
   */
  static function processRecurringContribution(&$form, &$params, $contactID, $contributionType, $online = TRUE) {
    // return if this page is not set for recurring
    // or the user has not chosen the recurring option

    //this is online case validation.
    if ((!CRM_Utils_Array::value('is_recur', $form->_values) && $online) ||
      !CRM_Utils_Array::value('is_recur', $params)
    ) {
      return NULL;
    }

    $recurParams = array();
    $config = CRM_Core_Config::singleton();
    $recurParams['contact_id'] = $contactID;
    $recurParams['amount'] = CRM_Utils_Array::value('amount', $params);
    $recurParams['auto_renew'] = CRM_Utils_Array::value('auto_renew', $params);
    $recurParams['frequency_unit'] = CRM_Utils_Array::value('frequency_unit', $params);
    $recurParams['frequency_interval'] = CRM_Utils_Array::value('frequency_interval', $params);
    $recurParams['installments'] = CRM_Utils_Array::value('installments', $params);
    $recurParams['financial_type_id'] = CRM_Utils_Array::value('financial_type_id', $params);

    $recurParams['is_test'] = 0;
    if (($form->_action & CRM_Core_Action::PREVIEW) ||
      (isset($form->_mode) && ($form->_mode == 'test'))
    ) {
      $recurParams['is_test'] = 1;
    }

    $recurParams['start_date'] = $recurParams['create_date'] = $recurParams['modified_date'] = date('YmdHis');
    if (CRM_Utils_Array::value('receive_date', $params)) {
      $recurParams['start_date'] = $params['receive_date'];
    }
    $recurParams['invoice_id'] = CRM_Utils_Array::value('invoiceID', $params);
    $recurParams['contribution_status_id'] = 2;
    $recurParams['payment_processor_id'] = CRM_Utils_Array::value('payment_processor_id', $params);
    $recurParams['is_email_receipt'] = CRM_Utils_Array::value('is_email_receipt', $params);
    // we need to add a unique trxn_id to avoid a unique key error
    // in paypal IPN we reset this when paypal sends us the real trxn id, CRM-2991
    $recurParams['trxn_id'] = CRM_Utils_Array::value('trxn_id', $params, $params['invoiceID']);
    $recurParams['financial_type_id'] = $contributionType->id;

    if (!$online || $form->_values['is_monetary']) {
      $recurParams['payment_instrument_id'] = 1;
    }

    $campaignId = CRM_Utils_Array::value('campaign_id', $params);
    if ($online) {
      if (!array_key_exists('campaign_id', $params)) {
        $campaignId = CRM_Utils_Array::value('campaign_id', $form->_values);
      }
    }
    $recurParams['campaign_id'] = $campaignId;

    $recurring = CRM_Contribute_BAO_ContributionRecur::add($recurParams);
    if (is_a($recurring, 'CRM_Core_Error')) {
      CRM_Core_Error::displaySessionError($result);
      $urlString = 'civicrm/contribute/transact';
      $urlParams = '_qf_Main_display=true';
      if ($className == 'CRM_Contribute_Form_Contribution') {
        $urlString = 'civicrm/contact/view/contribution';
        $urlParams = "action=add&cid={$form->_contactID}";
        if ($form->_mode) {
          $urlParams .= "&mode={$form->_mode}";
        }
      }
      CRM_Utils_System::redirect(CRM_Utils_System::url($urlString, $urlParams));
    }

    return $recurring->id;
  }

  /**
   * Create the Honor contact
   *
   * @return void
   * @access public
   */
  function createHonorContact() {
    $params = $this->controller->exportValues('Main');

    // email is enough to create a contact
    if (! CRM_Utils_Array::value('honor_email', $params) &&
      // or we need first name AND last name
      (! CRM_Utils_Array::value('honor_first_name', $params)
      || ! CRM_Utils_Array::value('honor_last_name', $params))) {
      //don't create contact - possibly the form was left blank
      return null;
    }

    //assign to template for email receipt
    $honor_block_is_active = $this->get('honor_block_is_active');

    $this->assign('honor_block_is_active', $honor_block_is_active);
    $this->assign('honor_block_title', CRM_Utils_Array::value('honor_block_title', $this->_values));

    $prefix = CRM_Core_PseudoConstant::individualPrefix();
    $honorType = CRM_Core_PseudoConstant::honor();
    $this->assign('honor_type', CRM_Utils_Array::value(CRM_Utils_Array::value('honor_type_id', $params), $honorType));
    $this->assign('honor_prefix', CRM_Utils_Array::value(CRM_Utils_Array::value('honor_prefix_id', $params), $prefix));
    $this->assign('honor_first_name', CRM_Utils_Array::value('honor_first_name', $params));
    $this->assign('honor_last_name', CRM_Utils_Array::value('honor_last_name', $params));
    $this->assign('honor_email', CRM_Utils_Array::value('honor_email', $params));

    //create honoree contact
    return CRM_Contribute_BAO_Contribution::createHonorContact($params);
  }

  /**
   * Function to add on behalf of organization and it's location
   *
   * @param $behalfOrganization array  array of organization info
   * @param $values             array  form values array
   * @param $contactID          int    individual contact id. One
   * who is doing the process of signup / contribution.
   *
   * @return void
   * @access public
   */
  static function processOnBehalfOrganization(&$behalfOrganization, &$contactID, &$values, &$params, $fields = NULL) {
    $isCurrentEmployer = FALSE;
    $orgID = NULL;
    if (CRM_Utils_Array::value('organization_id', $behalfOrganization) &&
      CRM_Utils_Array::value('org_option', $behalfOrganization)
    ) {
      $orgID = $behalfOrganization['organization_id'];
      unset($behalfOrganization['organization_id']);
      $isCurrentEmployer = TRUE;
    }

    // formalities for creating / editing organization.
    $behalfOrganization['contact_type'] = 'Organization';

    // get the relationship type id
    $relType = new CRM_Contact_DAO_RelationshipType();
    $relType->name_a_b = 'Employee of';
    $relType->find(TRUE);
    $relTypeId = $relType->id;

    // keep relationship params ready
    $relParams['relationship_type_id'] = $relTypeId . '_a_b';
    $relParams['is_permission_a_b'] = 1;
    $relParams['is_active'] = 1;

    if (!$orgID) {
      // check if matching organization contact exists
      $dedupeParams = CRM_Dedupe_Finder::formatParams($behalfOrganization, 'Organization');
      $dedupeParams['check_permission'] = FALSE;
      $dupeIDs = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Organization', 'Unsupervised');

      // CRM-6243 says to pick the first org even if more than one match
      if (count($dupeIDs) >= 1) {
        $behalfOrganization['contact_id'] = $dupeIDs[0];
        // don't allow name edit
        unset($behalfOrganization['organization_name']);
      }
    }
    else {
      // if found permissioned related organization, allow location edit
      $behalfOrganization['contact_id'] = $orgID;
      // don't allow name edit
      unset($behalfOrganization['organization_name']);
    }

    // handling for image url
    if (CRM_Utils_Array::value('image_URL', $behalfOrganization)) {
      CRM_Contact_BAO_Contact::processImageParams($behalfOrganization);
    }

    // create organization, add location
    $orgID = CRM_Contact_BAO_Contact::createProfileContact($behalfOrganization, $fields, $orgID,
      NULL, NULL, 'Organization'
    );
    // create relationship
    $relParams['contact_check'][$orgID] = 1;
    $cid = array('contact' => $contactID);
    CRM_Contact_BAO_Relationship::create($relParams, $cid);

    // if multiple match - send a duplicate alert
    if ($dupeIDs && (count($dupeIDs) > 1)) {
      $values['onbehalf_dupe_alert'] = 1;
      // required for IPN
      $params['onbehalf_dupe_alert'] = 1;
    }

    // make sure organization-contact-id is considered for recording
    // contribution/membership etc..
    if ($contactID != $orgID) {
      // take a note of contact-id, so we can send the
      // receipt to individual contact as well.

      // required for mailing/template display ..etc
      $values['related_contact'] = $contactID;
      // required for IPN
      $params['related_contact'] = $contactID;

      //make this employee of relationship as current
      //employer / employee relationship,  CRM-3532
      if ($isCurrentEmployer &&
        ($orgID != CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactID, 'employer_id'))
      ) {
        $isCurrentEmployer = FALSE;
      }

      if (!$isCurrentEmployer && $orgID) {
        //build current employer params
        $currentEmpParams[$contactID] = $orgID;
        CRM_Contact_BAO_Contact_Utils::setCurrentEmployer($currentEmpParams);
      }

      // contribution / signup will be done using this
      // organization id.
      $contactID = $orgID;
    }
  }

  /**
   * Function used to save pcp / soft credit entry
   * This is used by contribution and also event pcps
   *
   * @param array  $params         associated array
   * @param object $contribution   contribution object
   *
   * @static
   * @access public
   */
  static function processPcpSoft(&$params, &$contribution) {
    //add soft contribution due to pcp or Submit Credit / Debit Card Contribution by admin.
    if (CRM_Utils_Array::value('soft_credit_to', $params)) {
      $contribSoftParams = array();
      foreach (array(
        'pcp_display_in_roll', 'pcp_roll_nickname', 'pcp_personal_note', 'amount') as $val) {
        if (CRM_Utils_Array::value($val, $params)) {
          $contribSoftParams[$val] = $params[$val];
        }
      }

      $contribSoftParams['contact_id'] = $params['soft_credit_to'];
      // add contribution id
      $contribSoftParams['contribution_id'] = $contribution->id;
      // add pcp id
      $contribSoftParams['pcp_id'] = $params['pcp_made_through_id'];

      $softContribution = CRM_Contribute_BAO_Contribution::addSoftContribution($contribSoftParams);
    }
  }

  /**
   * Function used to se pcp related defaults / params
   * This is used by contribution and also event pcps
   *
   * @param object $page   form object
   * @param array  $params associated array
   *
   * @static
   * @access public
   */
  static function processPcp(&$page, $params) {
    $params['pcp_made_through_id'] = $page->_pcpInfo['pcp_id'];
    $page->assign('pcpBlock', TRUE);
    if (CRM_Utils_Array::value('pcp_display_in_roll', $params) &&
      !CRM_Utils_Array::value('pcp_roll_nickname', $params)
    ) {
      $params['pcp_roll_nickname'] = ts('Anonymous');
      $params['pcp_is_anonymous'] = 1;
    }
    else {
      $params['pcp_is_anonymous'] = 0;
    }
    foreach (array(
      'pcp_display_in_roll', 'pcp_is_anonymous', 'pcp_roll_nickname', 'pcp_personal_note') as $val) {
      if (CRM_Utils_Array::value($val, $params)) {
        $page->assign($val, $params[$val]);
      }
    }

    return $params;
  }
}
