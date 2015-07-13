<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * form to process actions on the group aspect of Custom Data
 */
class CRM_Contribute_Form_Contribution_Confirm extends CRM_Contribute_Form_ContributionBase {

  /**
   * The id of the contact associated with this contribution.
   *
   * @var int
   */
  public $_contactID;


  /**
   * The id of the contribution object that is created when the form is submitted.
   *
   * @var int
   */
  public $_contributionID;

  /**
   * Set the parameters to be passed to contribution create function.
   *
   * @param array $params
   * @param int $contactID
   * @param int $financialTypeID
   * @param bool $online
   * @param int $contributionPageId
   * @param float $nonDeductibleAmount
   * @param int $campaignId
   * @param bool $isMonetary
   * @param bool $pending
   * @param array $paymentProcessorOutcome
   * @param string $receiptDate
   * @param int $recurringContributionID
   * @param bool $isTest
   * @param int $addressID
   * @param int $softCreditToID
   * @param array $lineItems
   *
   * @return array
   */
  public static function getContributionParams(
    $params, $contactID, $financialTypeID, $online, $contributionPageId, $nonDeductibleAmount, $campaignId, $isMonetary, $pending,
    $paymentProcessorOutcome, $receiptDate, $recurringContributionID, $isTest, $addressID, $softCreditToID, $lineItems) {
    $contributionParams = array(
      'contact_id' => $contactID,
      'financial_type_id' => $financialTypeID,
      'contribution_page_id' => $contributionPageId,
      'receive_date' => (CRM_Utils_Array::value('receive_date', $params)) ? CRM_Utils_Date::processDate($params['receive_date']) : date('YmdHis'),
      'non_deductible_amount' => $nonDeductibleAmount,
      'total_amount' => $params['amount'],
      'tax_amount' => CRM_Utils_Array::value('tax_amount', $params),
      'amount_level' => CRM_Utils_Array::value('amount_level', $params),
      'invoice_id' => $params['invoiceID'],
      'currency' => $params['currencyID'],
      'source' => (!$online || !empty($params['source'])) ? CRM_Utils_Array::value('source', $params) : CRM_Utils_Array::value('description', $params),
      'is_pay_later' => CRM_Utils_Array::value('is_pay_later', $params, 0),
      //configure cancel reason, cancel date and thankyou date
      //from 'contribution' type profile if included
      'cancel_reason' => CRM_Utils_Array::value('cancel_reason', $params, 0),
      'cancel_date' => isset($params['cancel_date']) ? CRM_Utils_Date::format($params['cancel_date']) : NULL,
      'thankyou_date' => isset($params['thankyou_date']) ? CRM_Utils_Date::format($params['thankyou_date']) : NULL,
      'campaign_id' => $campaignId,
      'is_test' => $isTest,
      'address_id' => $addressID,
      //setting to make available to hook - although seems wrong to set on form for BAO hook availability
      'soft_credit_to' => $softCreditToID,
      'line_item' => $lineItems,
      'skipLineItem' => CRM_Utils_Array::value('skipLineItem', $params, 0),
    );
    if (!$online && isset($params['thankyou_date'])) {
      $contributionParam['thankyou_date'] = $params['thankyou_date'];
    }
    if (!$online || $isMonetary) {
      if (empty($params['is_pay_later'])) {
        $contributionParams['payment_instrument_id'] = 1;
      }
    }
    if ($paymentProcessorOutcome) {
      $contributionParams['payment_processor'] = CRM_Utils_Array::value('payment_processor', $paymentProcessorOutcome);
    }
    if (!$pending && $paymentProcessorOutcome) {
      $contributionParams += array(
        'fee_amount' => CRM_Utils_Array::value('fee_amount', $paymentProcessorOutcome),
        'net_amount' => CRM_Utils_Array::value('net_amount', $paymentProcessorOutcome, $params['amount']),
        'trxn_id' => $paymentProcessorOutcome['trxn_id'],
        'receipt_date' => $receiptDate,
        // also add financial_trxn details as part of fix for CRM-4724
        'trxn_result_code' => CRM_Utils_Array::value('trxn_result_code', $paymentProcessorOutcome),
      );
    }

    // CRM-4038: for non-en_US locales, CRM_Contribute_BAO_Contribution::add() expects localised amounts
    $contributionParams['non_deductible_amount'] = trim(CRM_Utils_Money::format($contributionParams['non_deductible_amount'], ' '));
    $contributionParams['total_amount'] = trim(CRM_Utils_Money::format($contributionParams['total_amount'], ' '));

    if ($recurringContributionID) {
      $contributionParams['contribution_recur_id'] = $recurringContributionID;
    }

    $contributionParams['contribution_status_id'] = $pending ? 2 : 1;
    if (isset($contributionParams['invoice_id'])) {
      $contributionParams['id'] = CRM_Core_DAO::getFieldValue(
        'CRM_Contribute_DAO_Contribution',
        $contributionParams['invoice_id'],
        'id',
        'invoice_id'
      );
    }

    return $contributionParams;
  }

  /**
   * Get non-deductible amount.
   *
   * This is a bit too much about wierd form interpretation to be this deep.
   *
   * CRM-11885
   *  if non_deductible_amount exists i.e. Additional Details fieldset was opened [and staff typed something] -> keep
   * it.
   *
   * @param array $params
   * @param CRM_Financial_BAO_FinancialType $financialType
   * @param bool $online
   *
   * @return array
   */
  protected static function getNonDeductibleAmount($params, $financialType, $online) {
    if (isset($params['non_deductible_amount']) && (!empty($params['non_deductible_amount']))) {
      return $params['non_deductible_amount'];
    }
    else {
      if ($financialType->is_deductible) {
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
            return $nonDeductibleAmount;
          }
          // product value does NOT exceed contribution amount
          else {
            return $productDAO->price;
          }
        }
        // contribution is deductible - but there is no product
        else {
          return '0.00';
        }
      }
      // contribution is NOT deductible
      else {
        return $params['amount'];
      }
    }
  }

  /**
   * Set variables up before form is built.
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
        $payment = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
        $expressParams = $payment->getPreApprovalDetails($this->get('pre_approval_parameters'));

        $this->_params['payer'] = CRM_Utils_Array::value('payer', $expressParams);
        $this->_params['payer_id'] = $expressParams['payer_id'];
        $this->_params['payer_status'] = $expressParams['payer_status'];

        CRM_Core_Payment_Form::mapParams($this->_bltID, $expressParams, $this->_params, FALSE);

        // fix state and country id if present
        if (!empty($this->_params["billing_state_province_id-{$this->_bltID}"])) {
          $this->_params["billing_state_province-{$this->_bltID}"] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($this->_params["billing_state_province_id-{$this->_bltID}"]);
        }
        if (!empty($this->_params["billing_country_id-{$this->_bltID}"]) && $this->_params["billing_country_id-{$this->_bltID}"]) {
          $this->_params["billing_country-{$this->_bltID}"] = CRM_Core_PseudoConstant::countryIsoCode($this->_params["billing_country_id-{$this->_bltID}"]);
        }

        // set a few other parameters for PayPal
        $this->_params['token'] = $this->get('token');

        $this->_params['amount'] = $this->get('amount');

        if (!empty($this->_membershipBlock)) {
          $this->_params['selectMembership'] = $this->get('selectMembership');
        }
        // we use this here to incorporate any changes made by folks in hooks
        $this->_params['currencyID'] = $config->defaultCurrency;

        // also merge all the other values from the profile fields
        $values = $this->controller->exportValues('Main');
        $skipFields = array(
          'amount',
          'amount_other',
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

      $this->_params['ip_address'] = CRM_Utils_System::ipAddress();
      $this->_params['amount'] = $this->get('amount');
      $this->_params['tax_amount'] = $this->get('tax_amount');

      $this->_useForMember = $this->get('useForMember');

      if (isset($this->_params['amount'])) {
        $this->setFormAmountFields($this->_params['priceSetId']);
      }
      $this->_params['currencyID'] = $config->defaultCurrency;
    }

    $this->_params['is_pay_later'] = $this->get('is_pay_later');
    $this->assign('is_pay_later', $this->_params['is_pay_later']);
    if ($this->_params['is_pay_later']) {
      $this->assign('pay_later_receipt', $this->_values['pay_later_receipt']);
    }
    // if onbehalf-of-organization
    if (!empty($this->_params['hidden_onbehalf_profile'])) {
      // CRM-15182
      if (empty($this->_params['org_option']) && empty($this->_params['organization_id'])) {
        if (!empty($this->_params['onbehalfof_id'])) {
          $this->_params['organization_id'] = $this->_params['onbehalfof_id'];
        }
        else {
          $this->_params['organization_id'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_params['onbehalf']['organization_name'], 'id', 'display_name');
        }
      }

      $this->_params['organization_name'] = $this->_params['onbehalf']['organization_name'];
      $addressBlocks = array(
        'street_address',
        'city',
        'state_province',
        'postal_code',
        'country',
        'supplemental_address_1',
        'supplemental_address_2',
        'supplemental_address_3',
        'postal_code_suffix',
        'geo_code_1',
        'geo_code_2',
        'address_name',
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
            && count($this->_params['onbehalf_location']['address']) > 0
          ) {
            $isPrimary = 0;
          }

          $this->_params['onbehalf_location']['address'][$locType][$field] = $value;
          if (empty($this->_params['onbehalf_location']['address'][$locType]['is_primary'])) {
            $this->_params['onbehalf_location']['address'][$locType]['is_primary'] = $isPrimary;
          }
          $this->_params['onbehalf_location']['address'][$locType]['location_type_id'] = $locType;
        }
        elseif (in_array($field, $blocks)) {
          if (!$typeId || is_numeric($typeId)) {
            $blockName = $fieldName = $field;
            $locationType = 'location_type_id';
            if ($locType == 'Primary') {
              $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
              $locationValue = $defaultLocationType->id;
            }
            else {
              $locationValue = $locType;
            }
            $locTypeId = '';
            $phoneExtField = array();

            if ($field == 'url') {
              $blockName = 'website';
              $locationType = 'website_type_id';
              list($field, $locationValue) = explode('-', $loc);
            }
            elseif ($field == 'im') {
              $fieldName = 'name';
              $locTypeId = 'provider_id';
              $typeId = $this->_params['onbehalf']["{$loc}-provider_id"];
            }
            elseif ($field == 'phone') {
              list($field, $locType, $typeId) = explode('-', $loc);
              $locTypeId = 'phone_type_id';

              //check if extension field exists
              $extField = str_replace('phone', 'phone_ext', $loc);
              if (isset($this->_params['onbehalf'][$extField])) {
                $phoneExtField = array('phone_ext' => $this->_params['onbehalf'][$extField]);
              }
            }

            $isPrimary = 1;
            if (isset ($this->_params['onbehalf_location'][$blockName])
              && count($this->_params['onbehalf_location'][$blockName]) > 0
            ) {
              $isPrimary = 0;
            }
            if ($locationValue) {
              $blockValues = array(
                $fieldName => $value,
                $locationType => $locationValue,
                'is_primary' => $isPrimary,
              );

              if ($locTypeId) {
                $blockValues = array_merge($blockValues, array($locTypeId => $typeId));
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
    elseif (!empty($this->_values['is_for_organization'])) {
      // no on behalf of an organization, CRM-5519
      // so reset loc blocks from main params.
      foreach (array(
                 'phone',
                 'email',
                 'address',
               ) as $blk) {
        if (isset($this->_params[$blk])) {
          unset($this->_params[$blk]);
        }
      }
    }

    // if auto renew checkbox is set, initiate a open-ended recurring membership
    if ((!empty($this->_params['selectMembership']) || !empty($this->_params['priceSetId'])) && !empty($this->_paymentProcessor['is_recur']) &&
      CRM_Utils_Array::value('auto_renew', $this->_params) && empty($this->_params['is_recur']) && empty($this->_params['frequency_interval'])
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
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->assignToTemplate();

    $params = $this->_params;
    // make sure we have values for it
    if ($this->_honor_block_is_active && !empty($params['soft_credit_type_id'])) {
      $honorName = NULL;
      $softCreditTypes = CRM_Core_OptionGroup::values("soft_credit_type", FALSE);

      $this->assign('honor_block_is_active', $this->_honor_block_is_active);
      $this->assign('soft_credit_type', $softCreditTypes[$params['soft_credit_type_id']]);
      CRM_Contribute_BAO_ContributionSoft::formatHonoreeProfileFields($this, $params['honor'], $params['honoree_profile_id']);

      $fieldTypes = array('Contact');
      $fieldTypes[] = CRM_Core_BAO_UFGroup::getContactType($params['honoree_profile_id']);
      $this->buildCustom($params['honoree_profile_id'], 'honoreeProfileFields', TRUE, 'honor', $fieldTypes);
    }
    $this->assign('receiptFromEmail', CRM_Utils_Array::value('receipt_from_email', $this->_values));
    $amount_block_is_active = $this->get('amount_block_is_active');
    $this->assign('amount_block_is_active', $amount_block_is_active);

    $invoiceSettings = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME, 'contribution_invoice_settings');
    $invoicing = CRM_Utils_Array::value('invoicing', $invoiceSettings);
    if ($invoicing) {
      $getTaxDetails = FALSE;
      $taxTerm = CRM_Utils_Array::value('tax_term', $invoiceSettings);
      foreach ($this->_lineItem as $key => $value) {
        foreach ($value as $v) {
          if (isset($v['tax_rate'])) {
            if ($v['tax_rate'] != '') {
              $getTaxDetails = TRUE;
            }
          }
        }
      }
      $this->assign('getTaxDetails', $getTaxDetails);
      $this->assign('taxTerm', $taxTerm);
      $this->assign('totalTaxAmount', $params['tax_amount']);
    }
    if (!empty($params['selectProduct']) && $params['selectProduct'] != 'no_thanks') {
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
        $this->buildMembershipBlock(
          $this->_membershipContactID,
          FALSE,
          $params['selectMembership'],
          FALSE
        );
        if (!empty($params['auto_renew'])) {
          $this->assign('auto_renew', TRUE);
        }
      }
      else {
        $this->assign('membershipBlock', FALSE);
      }
    }
    $this->buildCustom($this->_values['custom_pre_id'], 'customPre', TRUE);
    $this->buildCustom($this->_values['custom_post_id'], 'customPost', TRUE);

    if (!empty($params['hidden_onbehalf_profile'])) {
      $ufJoinParams = array(
        'module' => 'onBehalf',
        'entity_table' => 'civicrm_contribution_page',
        'entity_id' => $this->_id,
      );
      $OnBehalfProfile = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);
      $profileId = $OnBehalfProfile[0];

      $fieldTypes = array('Contact', 'Organization');
      $contactSubType = CRM_Contact_BAO_ContactType::subTypes('Organization');
      $fieldTypes = array_merge($fieldTypes, $contactSubType);
      if (is_array($this->_membershipBlock) && !empty($this->_membershipBlock)) {
        $fieldTypes = array_merge($fieldTypes, array('Membership'));
      }
      else {
        $fieldTypes = array_merge($fieldTypes, array('Contribution'));
      }

      $this->buildCustom($profileId, 'onbehalfProfile', TRUE, 'onbehalf', $fieldTypes);
    }

    $this->_separateMembershipPayment = $this->get('separateMembershipPayment');
    $this->assign('is_separate_payment', $this->_separateMembershipPayment);
    if ($this->_priceSetId && !CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_priceSetId, 'is_quick_config')) {
      $this->assign('lineItem', $this->_lineItem);
    }
    else {
      $this->assign('is_quick_config', 1);
      $this->_params['is_quick_config'] = 1;
    }
    $this->assign('priceSetID', $this->_priceSetId);
    $paymentProcessorType = CRM_Core_PseudoConstant::paymentProcessorType(FALSE, NULL, 'name');
    if ($this->_paymentProcessor &&
      $this->_paymentProcessor['payment_processor_type_id'] == CRM_Utils_Array::key('Google_Checkout', $paymentProcessorType)
      && !$this->_params['is_pay_later'] && !($this->_amount == 0)
    ) {
      $this->_checkoutButtonName = $this->getButtonName('next', 'checkout');
      $this->add('image',
        $this->_checkoutButtonName,
        $this->_paymentProcessor['url_button'],
        array('class' => 'crm-form-submit')
      );

      $this->addButtons(array(
          array(
            'type' => 'back',
            'name' => ts('Go Back'),
          ),
        )
      );
    }
    else {
      if ($this->_contributeMode == 'notify' || !$this->_values['is_monetary'] ||
        $this->_amount <= 0.0 || $this->_params['is_pay_later'] ||
        ($this->_separateMembershipPayment && $this->_amount <= 0.0)
      ) {
        $contribButton = ts('Continue');
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
    $fields = array_fill_keys(array_keys($this->_fields), 1);
    $fields["billing_state_province-{$this->_bltID}"] = $fields["billing_country-{$this->_bltID}"] = $fields["email-{$this->_bltID}"] = 1;

    $contact = $this->_params;
    foreach ($fields as $name => $dontCare) {
      // Recursively set defaults for nested fields
      if (isset($contact[$name]) && is_array($contact[$name]) && ($name == 'onbehalf' || $name == 'honor')) {
        foreach ($contact[$name] as $fieldName => $fieldValue) {
          if (is_array($fieldValue) && !in_array($this->_fields[$name][$fieldName]['html_type'], array(
              'Multi-Select',
              'AdvMulti-Select',
            ))
          ) {
            foreach ($fieldValue as $key => $value) {
              $defaults["{$name}[{$fieldName}][{$key}]"] = $value;
            }
          }
          else {
            $defaults["{$name}[{$fieldName}]"] = $fieldValue;
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
        elseif (in_array($name, array(
            'addressee',
            'email_greeting',
            'postal_greeting',
          )) && !empty($contact[$name . '_custom'])
        ) {
          $defaults[$name . '_custom'] = $contact[$name . '_custom'];
        }
      }
    }

    $this->assign('useForMember', $this->get('useForMember'));

    $this->setDefaults($defaults);

    $this->freeze();
  }

  /**
   * Overwrite action.
   *
   * Since we are only showing elements in frozen mode no help display needed.
   *
   * @return int
   */
  public function getAction() {
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      return CRM_Core_Action::VIEW | CRM_Core_Action::PREVIEW;
    }
    else {
      return CRM_Core_Action::VIEW;
    }
  }

  /**
   * Set default values for the form.
   *
   * Note that in edit/view mode
   * the default values are retrieved from the database
   */
  public function setDefaultValues() {
  }

  /**
   * Process the form.
   */
  public function postProcess() {
    $contactID = $this->getContactID();
    $result = $this->processFormSubmission($contactID);
    if (is_array($result) && !empty($result['is_payment_failure'])) {
      // We will probably have the function that gets this error throw an exception on the next round of refactoring.
      CRM_Core_Session::singleton()->setStatus(ts("Payment Processor Error message :") .
          $result['error']->getMessage());
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/transact',
        "_qf_Main_display=true&qfKey={$this->_params['qfKey']}"
      ));
    }
  }

  /**
   * Wrangle financial type ID.
   *
   * This wrangling of the financialType ID was happening in a shared function rather than in the form it relates to & hence has been moved to that form
   * Pledges are not relevant to the membership code so that portion will not go onto the membership form.
   *
   * Comments from previous refactor indicate doubt as to what was going on.
   *
   * @param int $contributionTypeId
   *
   * @return null|string
   */
  public function wrangleFinancialTypeID($contributionTypeId) {
    if (isset($paymentParams['financial_type'])) {
      $contributionTypeId = $paymentParams['financial_type'];
    }
    elseif (!empty($this->_values['pledge_id'])) {
      $contributionTypeId = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_Pledge',
        $this->_values['pledge_id'],
        'financial_type_id'
      );
    }
    return $contributionTypeId;
  }

  /**
   * Process the form.
   *
   * @param array $premiumParams
   * @param CRM_Contribute_BAO_Contribution $contribution
   */
  public function postProcessPremium($premiumParams, $contribution) {
    $hour = $minute = $second = 0;
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
            $date = explode('-', date('Y-m-d'));
            $month = substr($fixed_period_start_day, 0, strlen($fixed_period_start_day) - 2);
            $day = substr($fixed_period_start_day, -2) . "<br/>";
            $year = $date[0];
            $startDate = $year . '-' . $month . '-' . $day;
          }
          else {
            $startDate = date('Y-m-d');
          }
        }

        $date = explode('-', $startDate);
        $year = $date[0];
        $month = $date[1];
        $day = $date[2];

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

      $dao = new CRM_Contribute_DAO_Premium();
      $dao->entity_table = 'civicrm_contribution_page';
      $dao->entity_id = $this->_id;
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
      if (!empty($premiumParams['selectProduct'])) {
        $daoPremiumsProduct = new CRM_Contribute_DAO_PremiumsProduct();
        $daoPremiumsProduct->product_id = $premiumParams['selectProduct'];
        $daoPremiumsProduct->premiums_id = $dao->id;
        $daoPremiumsProduct->find(TRUE);
        $params['financial_type_id'] = $daoPremiumsProduct->financial_type_id;
      }
      //Fixed For CRM-3901
      $daoContrProd = new CRM_Contribute_DAO_ContributionProduct();
      $daoContrProd->contribution_id = $contribution->id;
      if ($daoContrProd->find(TRUE)) {
        $params['id'] = $daoContrProd->id;
      }

      CRM_Contribute_BAO_Contribution::addPremium($params);
      if ($productDAO->cost && !empty($params['financial_type_id'])) {
        $trxnParams = array(
          'cost' => $productDAO->cost,
          'currency' => $productDAO->currency,
          'financial_type_id' => $params['financial_type_id'],
          'contributionId' => $contribution->id,
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
   * Process the contribution.
   *
   * @param CRM_Core_Form $form
   * @param array $params
   * @param array $result
   * @param int $contactID
   * @param CRM_Financial_DAO_FinancialType $financialType
   * @param bool $pending
   * @param bool $online
   *
   * @param bool $isTest
   * @param array $lineItems
   *
   * @param int $billingLocationID
   *   ID of billing location type.
   *
   * @return \CRM_Contribute_DAO_Contribution
   * @throws \Exception
   */
  public static function processFormContribution(
    &$form,
    $params,
    $result,
    $contactID,
    $financialType,
    $pending,
    $online,
    $isTest,
    $lineItems,
    $billingLocationID
  ) {
    $transaction = new CRM_Core_Transaction();
    $contribSoftContactId = $addressID = NULL;
    $isMonetary = !empty($form->_values['is_monetary']);
    $isEmailReceipt = !empty($form->_values['is_email_receipt']);
    // How do these vary from params? These are currently passed to
    // - custom data function....
    $formParams = $form->_params;
    $isSeparateMembershipPayment = empty($formParams['separate_membership_payment']) ? FALSE : TRUE;
    $pledgeID = empty($formParams['pledge_id']) ? NULL : $formParams['pledge_id'];
    if (!$isSeparateMembershipPayment && !empty($form->_values['pledge_block_id']) &&
      (!empty($formParams['is_pledge']) || $pledgeID)) {
      $isPledge = TRUE;
    }
    else {
      $isPledge = FALSE;
    }

    // add these values for the recurringContrib function ,CRM-10188
    $params['financial_type_id'] = $financialType->id;

    $addressID = CRM_Contribute_BAO_Contribution::createAddress($params, $billingLocationID);

    //@todo - this is being set from the form to resolve CRM-10188 - an
    // eNotice caused by it not being set @ the front end
    // however, we then get it being over-written with null for backend contributions
    // a better fix would be to set the values in the respective forms rather than require
    // a function being shared by two forms to deal with their respective values
    // moving it to the BAO & not taking the $form as a param would make sense here.
    if (!isset($params['is_email_receipt']) && $isEmailReceipt) {
      $params['is_email_receipt'] = $isEmailReceipt;
    }
    $recurringContributionID = self::processRecurringContribution($form, $params, $contactID, $financialType, $online);
    $nonDeductibleAmount = self::getNonDeductibleAmount($params, $financialType, $online);

    $now = date('YmdHis');
    $receiptDate = CRM_Utils_Array::value('receipt_date', $params);
    if ($isEmailReceipt) {
      $receiptDate = $now;
    }

    //get the contrib page id.
    $contributionPageId = NULL;
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

    // Prepare soft contribution due to pcp or Submit Credit / Debit Card Contribution by admin.
    if (!empty($params['pcp_made_through_id']) || !empty($params['soft_credit_to'])) {
      // if its due to pcp
      if (!empty($params['pcp_made_through_id'])) {
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
      $params['soft_credit_to'] = $contribSoftContactId;
    }

    if (isset($params['amount'])) {
      $contribParams = self::getContributionParams(
        $params, $contactID, $financialType->id, $online, $contributionPageId, $nonDeductibleAmount, $campaignId, $isMonetary, $pending, $result, $receiptDate,
        $recurringContributionID, $isTest, $addressID, $contribSoftContactId, $lineItems
      );
      $contribution = CRM_Contribute_BAO_Contribution::add($contribParams);

      $invoiceSettings = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::CONTRIBUTE_PREFERENCES_NAME, 'contribution_invoice_settings');
      $invoicing = CRM_Utils_Array::value('invoicing', $invoiceSettings);
      if ($invoicing) {
        $dataArray = array();
        foreach ($form->_lineItem as $lineItemKey => $lineItemValue) {
          foreach ($lineItemValue as $key => $value) {
            if (isset($value['tax_amount']) && isset($value['tax_rate'])) {
              if (isset($dataArray[$value['tax_rate']])) {
                $dataArray[$value['tax_rate']] = $dataArray[$value['tax_rate']] + CRM_Utils_Array::value('tax_amount', $value);
              }
              else {
                $dataArray[$value['tax_rate']] = CRM_Utils_Array::value('tax_amount', $value);
              }
            }
          }
        }
        $smarty = CRM_Core_Smarty::singleton();
        $smarty->assign('dataArray', $dataArray);
        $smarty->assign('totalTaxAmount', $params['tax_amount']);
      }
      if (is_a($contribution, 'CRM_Core_Error')) {
        $message = CRM_Core_Error::getMessages($contribution);
        CRM_Core_Error::fatal($message);
      }

      // lets store it in the form variable so postProcess hook can get to this and use it
      $form->_contributionID = $contribution->id;
    }

    //CRM-13981, processing honor contact into soft-credit contribution
    CRM_Contact_Form_ProfileContact::postProcess($form);

    // process soft credit / pcp pages
    CRM_Contribute_Form_Contribution_Confirm::processPcpSoft($params, $contribution);

    //handle pledge stuff.
    if ($isPledge) {
      if ($pledgeID) {
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
        CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($pledgeID);
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
        'civicrm_contribution',
        $contribution->id,
        'Contribution'
      );
    }
    elseif ($contribution) {
      //handle custom data.
      $params['contribution_id'] = $contribution->id;
      if (!empty($params['custom']) &&
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

    //create contribution activity w/ individual and target
    //activity w/ organisation contact id when onbelf, CRM-4027
    $targetContactID = NULL;
    if (!empty($params['hidden_onbehalf_profile'])) {
      $targetContactID = $contribution->contact_id;
      $contribution->contact_id = $contactID;
    }

    // create an activity record
    if ($contribution) {
      CRM_Activity_BAO_Activity::addActivity($contribution, NULL, $targetContactID);
    }

    $transaction->commit();
    // CRM-13074 - create the CMSUser after the transaction is completed as it
    // is not appropriate to delete a valid contribution if a user create problem occurs
    CRM_Contribute_BAO_Contribution_Utils::createCMSUser($params,
      $contactID,
      'email-' . $billingLocationID
    );
    return $contribution;
  }

  /**
   * Create the recurring contribution record.
   *
   * @param CRM_Core_Form $form
   * @param array $params
   * @param int $contactID
   * @param string $contributionType
   * @param bool $online
   *
   * @return mixed
   */
  public static function processRecurringContribution(&$form, &$params, $contactID, $contributionType, $online = TRUE) {
    // return if this page is not set for recurring
    // or the user has not chosen the recurring option

    //this is online case validation.
    if ((empty($form->_values['is_recur']) && $online) || empty($params['is_recur'])) {
      return NULL;
    }

    $recurParams = array('contact_id' => $contactID);
    $recurParams['amount'] = CRM_Utils_Array::value('amount', $params);
    $recurParams['auto_renew'] = CRM_Utils_Array::value('auto_renew', $params);
    $recurParams['frequency_unit'] = CRM_Utils_Array::value('frequency_unit', $params);
    $recurParams['frequency_interval'] = CRM_Utils_Array::value('frequency_interval', $params);
    $recurParams['installments'] = CRM_Utils_Array::value('installments', $params);
    $recurParams['financial_type_id'] = CRM_Utils_Array::value('financial_type_id', $params);
    $recurParams['currency'] = CRM_Utils_Array::value('currency', $params);

    // CRM-14354: For an auto-renewing membership with an additional contribution,
    // if separate payments is not enabled, make sure only the membership fee recurs
    if (!empty($form->_membershipBlock)
      && $form->_membershipBlock['is_separate_payment'] === '0'
      && isset($params['selectMembership'])
      && $form->_values['is_allow_other_amount'] == '1'
      // CRM-16331
      && !empty($form->_membershipTypeValues)
      && !empty($form->_membershipTypeValues[$params['selectMembership']]['minimum_fee'])
    ) {
      $recurParams['amount'] = $form->_membershipTypeValues[$params['selectMembership']]['minimum_fee'];
    }

    $recurParams['is_test'] = 0;
    if (($form->_action & CRM_Core_Action::PREVIEW) ||
      (isset($form->_mode) && ($form->_mode == 'test'))
    ) {
      $recurParams['is_test'] = 1;
    }

    $recurParams['start_date'] = $recurParams['create_date'] = $recurParams['modified_date'] = date('YmdHis');
    if (!empty($params['receive_date'])) {
      $recurParams['start_date'] = $params['receive_date'];
    }
    $recurParams['invoice_id'] = CRM_Utils_Array::value('invoiceID', $params);
    $recurParams['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending');
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
      CRM_Core_Error::displaySessionError($recurring);
      $urlString = 'civicrm/contribute/transact';
      $urlParams = '_qf_Main_display=true';
      if (get_class($form) == 'CRM_Contribute_Form_Contribution') {
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
   * Add on behalf of organization and it's location.
   *
   * This situation occurs when on behalf of is enabled for the contribution page and the person
   * signing up does so on behalf of an organization.
   *
   * @param array $behalfOrganization
   *   array of organization info.
   * @param int $contactID
   *   individual contact id. One.
   *   who is doing the process of signup / contribution.
   *
   * @param array $values
   *   form values array.
   * @param array $params
   * @param array $fields
   *   Array of fields from the onbehalf profile relevant to the organization.
   */
  public static function processOnBehalfOrganization(&$behalfOrganization, &$contactID, &$values, &$params, $fields = NULL) {
    $isCurrentEmployer = FALSE;
    $dupeIDs = array();
    $orgID = NULL;
    if (!empty($behalfOrganization['organization_id']) && empty($behalfOrganization['org_option'])) {
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
        $behalfOrganization['contact_id'] = $orgID = $dupeIDs[0];
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
    if (!empty($behalfOrganization['image_URL'])) {
      CRM_Contact_BAO_Contact::processImageParams($behalfOrganization);
    }

    // create organization, add location
    $orgID = CRM_Contact_BAO_Contact::createProfileContact($behalfOrganization, $fields, $orgID,
      NULL, NULL, 'Organization'
    );
    // create relationship
    $relParams['contact_check'][$orgID] = 1;
    $cid = array('contact' => $contactID);
    CRM_Contact_BAO_Relationship::legacyCreateMultiple($relParams, $cid);

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
   * Function used to save pcp / soft credit entry.
   *
   * This is used by contribution and also event pcps
   *
   * @param array $params
   * @param object $contribution
   *   Contribution object.
   */
  public static function processPcpSoft(&$params, &$contribution) {
    // Add soft contribution due to pcp or Submit Credit / Debit Card Contribution by admin.
    if (!empty($params['soft_credit_to'])) {
      $contributionSoftParams = array();
      foreach (array(
          'pcp_display_in_roll',
          'pcp_roll_nickname',
          'pcp_personal_note',
          'amount',
        ) as $val) {
        if (!empty($params[$val])) {
          $contributionSoftParams[$val] = $params[$val];
        }
      }

      $contributionSoftParams['contact_id'] = $params['soft_credit_to'];
      // add contribution id
      $contributionSoftParams['contribution_id'] = $contribution->id;
      // add pcp id
      $contributionSoftParams['pcp_id'] = $params['pcp_made_through_id'];

      $contributionSoftParams['soft_credit_type_id'] = CRM_Core_OptionGroup::getValue('soft_credit_type', 'pcp', 'name');

      $contributionSoft = CRM_Contribute_BAO_ContributionSoft::add($contributionSoftParams);

      //Send notification to owner for PCP
      if ($contributionSoft->id && $contributionSoft->pcp_id) {
        CRM_Contribute_Form_Contribution_Confirm::pcpNotifyOwner($contribution, $contributionSoft);
      }
    }
  }

  /**
   * Function used to send notification mail to pcp owner.
   *
   * This is used by contribution and also event PCPs.
   *
   * @param object $contribution
   * @param object $contributionSoft
   *   Contribution object.
   */
  public static function pcpNotifyOwner($contribution, $contributionSoft) {
    $params = array('id' => $contributionSoft->pcp_id);
    CRM_Core_DAO::commonRetrieve('CRM_PCP_DAO_PCP', $params, $pcpInfo);
    $ownerNotifyID = CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCPBlock', $pcpInfo['pcp_block_id'], 'owner_notify_id');

    if ($ownerNotifyID != CRM_Core_OptionGroup::getValue('pcp_owner_notify', 'no_notifications', 'name') &&
        (($ownerNotifyID == CRM_Core_OptionGroup::getValue('pcp_owner_notify', 'owner_chooses', 'name') &&
        CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCP', $contributionSoft->pcp_id, 'is_notify')) ||
        $ownerNotifyID == CRM_Core_OptionGroup::getValue('pcp_owner_notify', 'all_owners', 'name'))) {
      $pcpInfoURL = CRM_Utils_System::url('civicrm/pcp/info',
        "reset=1&id={$contributionSoft->pcp_id}",
        TRUE, NULL, FALSE, TRUE
      );
      // set email in the template here
      // get the billing location type
      $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id', array(), 'validate');
      $billingLocationTypeId = array_search('Billing', $locationTypes);

      if ($billingLocationTypeId) {
        list($donorName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contribution->contact_id, FALSE, $billingLocationTypeId);
      }
      // get primary location email if no email exist( for billing location).
      if (!$email) {
        list($donorName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contribution->contact_id);
      }
      list($ownerName, $ownerEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contributionSoft->contact_id);
      $tplParams = array(
        'page_title' => $pcpInfo['title'],
        'receive_date' => $contribution->receive_date,
        'total_amount' => $contributionSoft->amount,
        'donors_display_name' => $donorName,
        'donors_email' => $email,
        'pcpInfoURL' => $pcpInfoURL,
        'is_honor_roll_enabled' => $contributionSoft->pcp_display_in_roll,
      );
      $domainValues = CRM_Core_BAO_Domain::getNameAndEmail();
      $sendTemplateParams = array(
        'groupName' => 'msg_tpl_workflow_contribution',
        'valueName' => 'pcp_owner_notify',
        'contactId' => $contributionSoft->contact_id,
        'toEmail' => $ownerEmail,
        'toName' => $ownerName,
        'from' => "$domainValues[0] <$domainValues[1]>",
        'tplParams' => $tplParams,
        'PDFFilename' => 'receipt.pdf',
      );
      CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
    }
  }

  /**
   * Function used to se pcp related defaults / params.
   *
   * This is used by contribution and also event PCPs
   *
   * @param CRM_Core_Form $page
   *   Form object.
   * @param array $params
   *
   * @return array
   */
  public static function processPcp(&$page, $params) {
    $params['pcp_made_through_id'] = $page->_pcpId;
    $page->assign('pcpBlock', TRUE);
    if (!empty($params['pcp_display_in_roll']) && empty($params['pcp_roll_nickname'])) {
      $params['pcp_roll_nickname'] = ts('Anonymous');
      $params['pcp_is_anonymous'] = 1;
    }
    else {
      $params['pcp_is_anonymous'] = 0;
    }
    foreach (array(
               'pcp_display_in_roll',
               'pcp_is_anonymous',
               'pcp_roll_nickname',
               'pcp_personal_note',
             ) as $val) {
      if (!empty($params[$val])) {
        $page->assign($val, $params[$val]);
      }
    }

    return $params;
  }

  /**
   * Process membership.
   *
   * @param array $membershipParams
   * @param int $contactID
   * @param array $customFieldsFormatted
   * @param array $fieldTypes
   * @param array $premiumParams
   * @param array $membershipLineItems
   *   Line items specifically relating to memberships.
   * @param bool $isPayLater
   */
  public function processMembership($membershipParams, $contactID, $customFieldsFormatted, $fieldTypes, $premiumParams, $membershipLineItems, $isPayLater) {
    try {
      $membershipTypeIDs = (array) $membershipParams['selectMembership'];
      $membershipTypes = CRM_Member_BAO_Membership::buildMembershipTypeValues($this, $membershipTypeIDs);
      $membershipType = empty($membershipTypes) ? array() : reset($membershipTypes);
      $isPending = $this->getIsPending();

      $this->assign('membership_name', CRM_Utils_Array::value('name', $membershipType));

      $isPaidMembership = FALSE;
      if ($this->_amount >= 0.0 && isset($membershipParams['amount'])) {
        //amount must be greater than zero for
        //adding contribution record  to contribution table.
        //this condition arises when separate membership payment is
        //enabled and contribution amount is not selected. fix for CRM-3010
        $isPaidMembership = TRUE;
      }
      $isProcessSeparateMembershipTransaction = $this->isSeparateMembershipTransaction($this->_id, $this->_values['amount_block_is_active']);

      if ($this->_values['amount_block_is_active']) {
        $financialTypeID = $this->_values['financial_type_id'];
      }
      else {
        $financialTypeID = CRM_Utils_Array::value('financial_type_id', $membershipType, CRM_Utils_Array::value('financial_type_id', $membershipParams));
      }

      if (CRM_Utils_Array::value('membership_source', $this->_params)) {
        $membershipParams['contribution_source'] = $this->_params['membership_source'];
      }

      $this->postProcessMembership($membershipParams, $contactID,
        $this, $premiumParams, $customFieldsFormatted, $fieldTypes, $membershipType, $membershipTypeIDs, $isPaidMembership, $this->_membershipId, $isProcessSeparateMembershipTransaction, $financialTypeID,
        $membershipLineItems, $isPayLater, $isPending);

      $this->assign('membership_assign', TRUE);
      $this->set('membershipTypeID', $membershipParams['selectMembership']);
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Session::singleton()->setStatus($e->getMessage());
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/transact', "_qf_Main_display=true&qfKey={$this->_params['qfKey']}"));
    }
  }

  /**
   * Process the Memberships.
   *
   * @param array $membershipParams
   *   Array of membership fields.
   * @param int $contactID
   *   Contact id.
   * @param CRM_Contribute_Form_Contribution_Confirm $form
   *   Confirmation form object.
   *
   * @param array $premiumParams
   * @param null $customFieldsFormatted
   * @param null $includeFieldTypes
   *
   * @param array $membershipDetails
   *
   * @param array $membershipTypeIDs
   *
   * @param bool $isPaidMembership
   * @param array $membershipID
   *
   * @param bool $isProcessSeparateMembershipTransaction
   *
   * @param int $financialTypeID
   * @param array $membershipLineItems
   *   Line items specific to membership payment that is separate to contribution.
   * @param bool $isPayLater
   * @param bool $isPending
   *
   * @throws \CRM_Core_Exception
   */
  protected function postProcessMembership(
    $membershipParams, $contactID, &$form, $premiumParams,
    $customFieldsFormatted = NULL, $includeFieldTypes = NULL, $membershipDetails, $membershipTypeIDs, $isPaidMembership, $membershipID,
    $isProcessSeparateMembershipTransaction, $financialTypeID, $membershipLineItems, $isPayLater, $isPending) {
    $result = $membershipContribution = NULL;
    $isTest = CRM_Utils_Array::value('is_test', $membershipParams, FALSE);
    $errors = $createdMemberships = $paymentResult = array();

    if ($isPaidMembership) {
      if ($isProcessSeparateMembershipTransaction) {
        // If we have 2 transactions only one can use the invoice id.
        $membershipParams['invoiceID'] .= '-2';
      }

      $paymentResult = CRM_Contribute_BAO_Contribution_Utils::processConfirm($form, $membershipParams,
        $premiumParams, $contactID,
        $financialTypeID,
        'membership',
        array(),
        $isTest,
        $isPayLater
      );
      if (is_a($result[1], 'CRM_Core_Error')) {
        $errors[1] = CRM_Core_Error::getMessages($paymentResult[1]);
      }

      if (is_a($paymentResult, 'CRM_Core_Error')) {
        $errors[1] = CRM_Core_Error::getMessages($paymentResult);
      }
      elseif (!empty($paymentResult['contribution'])) {
        //note that this will be over-written if we are using a separate membership transaction. Otherwise there is only one
        $membershipContribution = $paymentResult['contribution'];
        // Save the contribution ID so that I can be used in email receipts
        // For example, if you need to generate a tax receipt for the donation only.
        $form->_values['contribution_other_id'] = $membershipContribution->id;
      }
    }

    if ($isProcessSeparateMembershipTransaction) {
      try {
        $form->_lineItem = $membershipLineItems;
        if (empty($form->_params['auto_renew']) && !empty($membershipParams['is_recur'])) {
          unset($membershipParams['is_recur']);
        }
        $membershipContribution = $this->processSecondaryFinancialTransaction($contactID, $form, $membershipParams,
          $isTest, $membershipLineItems, CRM_Utils_Array::value('minimum_fee', $membershipDetails, 0), CRM_Utils_Array::value('financial_type_id', $membershipDetails));
      }
      catch (CRM_Core_Exception $e) {
        $errors[2] = $e->getMessage();
        $membershipContribution = NULL;
      }
    }

    $membership = NULL;
    if (!empty($membershipContribution) && !is_a($membershipContribution, 'CRM_Core_Error')) {
      $membershipContributionID = $membershipContribution->id;
    }

    //@todo - why is this nested so deep? it seems like it could be just set on the calling function on the form layer
    if (isset($membershipParams['onbehalf']) && !empty($membershipParams['onbehalf']['member_campaign_id'])) {
      $form->_params['campaign_id'] = $membershipParams['onbehalf']['member_campaign_id'];
    }
    //@todo it should no longer be possible for it to get to this point & membership to not be an array
    if (is_array($membershipTypeIDs) && !empty($membershipContributionID)) {
      $typesTerms = CRM_Utils_Array::value('types_terms', $membershipParams, array());
      foreach ($membershipTypeIDs as $memType) {
        $numTerms = CRM_Utils_Array::value($memType, $typesTerms, 1);
        if (!empty($membershipContribution)) {
          $pendingStatus = CRM_Core_OptionGroup::getValue('contribution_status', 'Pending', 'name');
          $pending = ($membershipContribution->contribution_status_id == $pendingStatus) ? TRUE : FALSE;
        }
        else {
          $pending = $isPending;
        }
        $contributionRecurID = isset($form->_params['contributionRecurID']) ? $form->_params['contributionRecurID'] : NULL;

        $membershipSource = NULL;
        if (!empty($form->_params['membership_source'])) {
          $membershipSource = $form->_params['membership_source'];
        }
        elseif (isset($form->_values['title']) && !empty($form->_values['title'])) {
          $membershipSource = ts('Online Contribution:') . ' ' . $form->_values['title'];
        }
        $isPayLater = NULL;
        if (isset($form->_params)) {
          $isPayLater = CRM_Utils_Array::value('is_pay_later', $form->_params);
        }
        $campaignId = NULL;
        if (isset($form->_values) && is_array($form->_values) && !empty($form->_values)) {
          $campaignId = CRM_Utils_Array::value('campaign_id', $form->_params);
          if (!array_key_exists('campaign_id', $form->_params)) {
            $campaignId = CRM_Utils_Array::value('campaign_id', $form->_values);
          }
        }

        list($membership, $renewalMode, $dates) = CRM_Member_BAO_Membership::renewMembership(
          $contactID, $memType, $isTest,
          date('YmdHis'), CRM_Utils_Array::value('cms_contactID', $membershipParams),
          $customFieldsFormatted,
          $numTerms, $membershipID, $pending,
          $contributionRecurID, $membershipSource, $isPayLater, $campaignId
        );
        $form->set('renewal_mode', $renewalMode);
        if (!empty($dates)) {
          $form->assign('mem_start_date',
            CRM_Utils_Date::customFormat($dates['start_date'], '%Y%m%d')
          );
          $form->assign('mem_end_date',
            CRM_Utils_Date::customFormat($dates['end_date'], '%Y%m%d')
          );
        }

        if (!empty($membershipContribution)) {
          // update recurring id for membership record
          CRM_Member_BAO_Membership::updateRecurMembership($membership, $membershipContribution);
          CRM_Member_BAO_Membership::linkMembershipPayment($membership, $membershipContribution);
        }
      }
      if ($form->_priceSetId && !empty($form->_useForMember) && !empty($form->_lineItem)) {
        foreach ($form->_lineItem[$form->_priceSetId] as & $priceFieldOp) {
          if (!empty($priceFieldOp['membership_type_id']) &&
            isset($createdMemberships[$priceFieldOp['membership_type_id']])
          ) {
            $membershipOb = $createdMemberships[$priceFieldOp['membership_type_id']];
            $priceFieldOp['start_date'] = $membershipOb->start_date ? CRM_Utils_Date::customFormat($membershipOb->start_date, '%B %E%f, %Y') : '-';
            $priceFieldOp['end_date'] = $membershipOb->end_date ? CRM_Utils_Date::customFormat($membershipOb->end_date, '%B %E%f, %Y') : '-';
          }
          else {
            $priceFieldOp['start_date'] = $priceFieldOp['end_date'] = 'N/A';
          }
        }
        $form->_values['lineItem'] = $form->_lineItem;
        $form->assign('lineItem', $form->_lineItem);
      }
    }

    if (!empty($errors)) {
      $message = $this->compileErrorMessage($errors);
      throw new CRM_Core_Exception($message);
    }
    $form->_params['createdMembershipIDs'] = array();

    // CRM-7851 - Moved after processing Payment Errors
    //@todo - the reasoning for this being here seems a little outdated
    foreach ($createdMemberships as $createdMembership) {
      CRM_Core_BAO_CustomValueTable::postProcess(
        $form->_params,
        'civicrm_membership',
        $createdMembership->id,
        'Membership'
      );
      $form->_params['createdMembershipIDs'][] = $createdMembership->id;
    }
    if (count($createdMemberships) == 1) {
      //presumably this is only relevant for exactly 1 membership
      $form->_params['membershipID'] = $createdMembership->id;
    }

    //CRM-15232: Check if membership is created and on the basis of it use
    //membership receipt template to send payment receipt
    if (count($createdMemberships)) {
      $form->_values['isMembership'] = TRUE;
    }
    if ($form->_contributeMode == 'notify') {
      if ($form->_values['is_monetary'] && $form->_amount > 0.0 && !$form->_params['is_pay_later']) {
        // call postProcess hook before leaving
        $form->postProcessHook();
        // this does not return
        $payment = Civi\Payment\System::singleton()->getByProcessor($form->_paymentProcessor);
        $payment->doTransferCheckout($form->_params, 'contribute');
      }
    }

    if (isset($membershipContributionID)) {
      $form->_values['contribution_id'] = $membershipContributionID;
    }

    if ($form->_contributeMode == 'direct') {
      if (CRM_Utils_Array::value('payment_status_id', $paymentResult) == 1) {
        // Refer to CRM-16737. Payment processors 'should' return payment_status_id
        // to denote the outcome of the transaction.
        try {
          civicrm_api3('contribution', 'completetransaction', array(
            'id' => $paymentResult['contribution']->id,
            'trxn_id' => $paymentResult['contribution']->trxn_id,
            'is_transactional' => FALSE,
          ));
        }
        catch (CiviCRM_API3_Exception $e) {
          // if for any reason it is already completed this will fail - e.g extensions hacking around core not completing transactions prior to CRM-15296
          // so let's be gentle here
          CRM_Core_Error::debug_log_message('contribution ' . $membershipContribution->id . ' not completed with trxn_id ' . $membershipContribution->trxn_id . ' and message ' . $e->getMessage());
        }
      }
      // Do not send an email if Recurring transaction is done via Direct Mode
      // Email will we sent when the IPN is received.
      return;
    }

    //finally send an email receipt
    CRM_Contribute_BAO_ContributionPage::sendMail($contactID,
      $form->_values,
      $isTest, FALSE,
      $includeFieldTypes
    );
  }

  /**
   * Turn array of errors into message string.
   *
   * @param array $errors
   *
   * @return string
   */
  protected function compileErrorMessage($errors) {
    foreach ($errors as $error) {
      if (is_string($error)) {
        $message[] = $error;
      }
    }
    return ts('Payment Processor Error message') . ': ' . implode('<br/>', $message);
  }

  /**
   * Where a second separate financial transaction is supported we will process it here.
   *
   * @param int $contactID
   * @param CRM_Contribute_Form_Contribution_Confirm $form
   * @param array $tempParams
   * @param bool $isTest
   * @param array $lineItems
   * @param $minimumFee
   * @param int $financialTypeID
   *
   * @throws CRM_Core_Exception
   * @throws Exception
   * @return CRM_Contribute_BAO_Contribution
   */
  protected function processSecondaryFinancialTransaction($contactID, &$form, $tempParams, $isTest, $lineItems, $minimumFee,
                                                   $financialTypeID) {
    $financialType = new CRM_Financial_DAO_FinancialType();
    $financialType->id = $financialTypeID;
    $financialType->find(TRUE);
    $tempParams['amount'] = $minimumFee;
    $tempParams['invoiceID'] = md5(uniqid(rand(), TRUE));

    $result = NULL;
    if ($form->_values['is_monetary'] && !$form->_params['is_pay_later'] && $minimumFee > 0.0) {
      // At the moment our tests are calling this form in a way that leaves 'object' empty. For
      // now we compensate here.
      if (empty($form->_paymentProcessor['object'])) {
        $payment = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
      }
      else {
        $payment = $form->_paymentProcessor['object'];
      }

      if ($form->_contributeMode == 'express') {
        $result = $payment->doExpressCheckout($tempParams);
        if (is_a($result, 'CRM_Core_Error')) {
          throw new CRM_Core_Exception(CRM_Core_Error::getMessages($result));
        }
      }
      else {
        $result = $payment->doPayment($tempParams, 'contribute');
      }
    }

    //assign receive date when separate membership payment
    //and contribution amount not selected.
    if ($form->_amount == 0) {
      $now = date('YmdHis');
      $form->_params['receive_date'] = $now;
      $receiveDate = CRM_Utils_Date::mysqlToIso($now);
      $form->set('params', $form->_params);
      $form->assign('receive_date', $receiveDate);
    }

    $form->set('membership_trx_id', $result['trxn_id']);
    $form->set('membership_amount', $minimumFee);

    $form->assign('membership_trx_id', $result['trxn_id']);
    $form->assign('membership_amount', $minimumFee);

    // we don't need to create the user twice, so lets disable cms_create_account
    // irrespective of the value, CRM-2888
    $tempParams['cms_create_account'] = 0;

    //CRM-16165, scenarios are
    // 1) If contribution is_pay_later and if contribution amount is > 0.0 we set pending = TRUE, vice-versa FALSE
    // 2) If not pay later but auto-renewal membership is chosen then pending = TRUE as it later triggers
    //   pending recurring contribution, vice-versa FALSE
    $pending = $form->_params['is_pay_later'] ? (($minimumFee > 0.0) ? TRUE : FALSE) : (!empty($form->_params['auto_renew']) ? TRUE : FALSE);

    //set this variable as we are not creating pledge for
    //separate membership payment contribution.
    //so for differentiating membership contribution from
    //main contribution.
    $form->_params['separate_membership_payment'] = 1;
    $membershipContribution = CRM_Contribute_Form_Contribution_Confirm::processFormContribution($form,
      $tempParams,
      $result,
      $contactID,
      $financialType,
      $pending,
      TRUE,
      $isTest,
      $lineItems,
      $form->_bltID
    );
    return $membershipContribution;
  }

  /**
   * Is the payment a pending payment.
   *
   * We are moving towards always creating as pending and updating at the end (based on payment), so this should be
   * an interim refactoring. It was shared with another unrelated form & some parameters may not apply to this form.
   *
   *
   * @return bool
   */
  protected function getIsPending() {
    if (((isset($this->_contributeMode)) || !empty
        ($this->_params['is_pay_later'])
      ) &&
      (($this->_values['is_monetary'] && $this->_amount > 0.0))
    ) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Are we going to do 2 financial transactions.
   *
   * Ie the membership block supports a separate transactions AND the contribution form has been configured for a
   * contribution
   * transaction AND a membership transaction AND the payment processor supports double financial transactions (ie. NOT doTransferPayment style)
   *
   * @param int $formID
   * @param bool $amountBlockActiveOnForm
   *
   * @return bool
   */
  public function isSeparateMembershipTransaction($formID, $amountBlockActiveOnForm) {
    $memBlockDetails = CRM_Member_BAO_Membership::getMembershipBlock($formID);
    if (!empty($memBlockDetails['is_separate_payment']) && $amountBlockActiveOnForm) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * This function sets the fields.
   *
   * - $this->_params['amount_level']
   * - $this->_params['selectMembership']
   * And under certain circumstances sets
   * $this->_params['amount'] = null;
   *
   * @param int $priceSetID
   */
  public function setFormAmountFields($priceSetID) {
    $isQuickConfig = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_params['priceSetId'], 'is_quick_config');
    $priceField = new CRM_Price_DAO_PriceField();
    $priceField->price_set_id = $priceSetID;
    $priceField->orderBy('weight');
    $priceField->find();
    $paramWeDoNotUnderstand = NULL;

    while ($priceField->fetch()) {
      if ($priceField->name == "contribution_amount") {
        $paramWeDoNotUnderstand = $priceField->id;
      }
      if ($isQuickConfig && !empty($this->_params["price_{$priceField->id}"])) {
        if ($this->_values['fee'][$priceField->id]['html_type'] != 'Text') {
          $this->_params['amount_level'] = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue',
            $this->_params["price_{$priceField->id}"], 'label');
        }
        if ($priceField->name == "membership_amount") {
          $this->_params['selectMembership'] = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue',
            $this->_params["price_{$priceField->id}"], 'membership_type_id');
        }
      }
      // If separate payment we set contribution amount to be null, so that it will not show contribution amount same
      // as membership amount.
      // @todo - this needs more documentation - it appears the setting to null is tied up with separate membership payments
      // but the circumstances are very confusing. Many of these conditions are repeated in the next conditional
      // so we should merge them together
      // the quick config seems like a red-herring - if this is about a separate membership payment then there
      // are 2 types of line items - membership ones & non-membership ones - regardless of whether quick config is set
      elseif (
        CRM_Utils_Array::value('is_separate_payment', $this->_membershipBlock)
        && !empty($this->_values['fee'][$priceField->id])
        && ($this->_values['fee'][$priceField->id]['name'] == "other_amount")
        && CRM_Utils_Array::value("price_{$paramWeDoNotUnderstand}", $this->_params) < 1
        && empty($this->_params["price_{$priceField->id}"])
      ) {
        $this->_params['amount'] = NULL;
      }

      // Fix for CRM-14375 - If we are using separate payments and "no
      // thank you" is selected for the additional contribution, set
      // contribution amount to be null, so that it will not show
      // contribution amount same as membership amount.
      //@todo - merge with section above
      if ($this->_membershipBlock['is_separate_payment']
        && !empty($this->_values['fee'][$priceField->id])
        && CRM_Utils_Array::value('name', $this->_values['fee'][$priceField->id]) == 'contribution_amount'
        && CRM_Utils_Array::value("price_{$priceField->id}", $this->_params) == '-1'
      ) {
        $this->_params['amount'] = NULL;
      }
    }
  }

  /**
   * Submit function.
   *
   * @param array $params
   *
   * @throws CiviCRM_API3_Exception
   */
  public static function submit($params) {
    $form = new CRM_Contribute_Form_Contribution_Confirm();
    $form->_id = $params['id'];

    CRM_Contribute_BAO_ContributionPage::setValues($form->_id, $form->_values);
    $form->_separateMembershipPayment = CRM_Contribute_BAO_ContributionPage::getIsMembershipPayment($form->_id);
    //this way the mocked up controller ignores the session stuff
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $form->controller = new CRM_Contribute_Controller_Contribution();
    $params['invoiceID'] = md5(uniqid(rand(), TRUE));
    $paramsProcessedForForm = $form->_params = self::getFormParams($params['id'], $params);
    $form->_amount = $params['amount'];
    $priceSetID = $form->_params['priceSetId'] = $paramsProcessedForForm['price_set_id'];
    $priceFields = CRM_Price_BAO_PriceSet::getSetDetail($priceSetID);
    $priceSetFields = reset($priceFields);
    $form->_values['fee'] = $priceSetFields['fields'];
    $form->_priceSetId = $priceSetID;
    $form->setFormAmountFields($priceSetID);
    if (!empty($params['payment_processor_id'])) {
      $form->_paymentProcessor = civicrm_api3('payment_processor', 'getsingle', array(
        'id' => $params['payment_processor_id'],
      ));
      if ($form->_paymentProcessor['billing_mode'] == 1) {
        $form->_contributeMode = 'direct';
      }
      else {
        $form->_contributeMode = 'notify';
      }
    }
    else {
      $form->_params['payment_processor_id'] = 0;
    }
    $priceFields = $priceFields[$priceSetID]['fields'];
    CRM_Price_BAO_PriceSet::processAmount($priceFields, $paramsProcessedForForm, $lineItems, 'civicrm_contribution');
    $form->_lineItem = array($priceSetID => $lineItems);
    $form->processFormSubmission(CRM_Utils_Array::value('contact_id', $params));
  }

  /**
   * Helper function for static submit function.
   *
   * Set relevant params - help us to build up an array that we can pass in.
   *
   * @param int $id
   * @param array $params
   *
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  public static function getFormParams($id, array $params) {
    if (!isset($params['is_pay_later'])) {
      if (!empty($params['payment_processor_id'])) {
        $params['is_pay_later'] = 0;
      }
      else {
        $params['is_pay_later'] = civicrm_api3('contribution_page', 'getvalue', array(
          'id' => $id,
          'return' => 'is_pay_later',
        ));
      }
    }
    if (empty($params['price_set_id'])) {
      $params['price_set_id'] = CRM_Price_BAO_PriceSet::getFor('civicrm_contribution_page', $params['id']);
    }
    return $params;
  }

  /**
   * Post form submission handling.
   *
   * This is also called from the test suite.
   *
   * @param int $contactID
   *
   * @return array
   */
  protected function processFormSubmission($contactID) {
    $isPayLater = $this->_params['is_pay_later'];
    if (isset($this->_params['payment_processor_id']) && $this->_params['payment_processor_id'] == 0) {
      $this->_params['is_pay_later'] = $isPayLater = TRUE;
    }
    // add a description field at the very beginning
    $this->_params['description'] = ts('Online Contribution') . ': ' . (($this->_pcpInfo['title']) ? $this->_pcpInfo['title'] : $this->_values['title']);

    $this->_params['accountingCode'] = CRM_Utils_Array::value('accountingCode', $this->_values);

    // fix currency ID
    $this->_params['currencyID'] = CRM_Core_Config::singleton()->defaultCurrency;

    //carry payment processor id.
    if (CRM_Utils_Array::value('id', $this->_paymentProcessor)) {
      $this->_params['payment_processor_id'] = $this->_paymentProcessor['id'];
    }
    if (!empty($params['image_URL'])) {
      CRM_Contact_BAO_Contact::processImageParams($params);
    }
    $premiumParams = $membershipParams = $params = $this->_params;
    $fields = array('email-Primary' => 1);

    // get the add to groups
    $addToGroups = array();

    // now set the values for the billing location.
    foreach ($this->_fields as $name => $value) {
      $fields[$name] = 1;

      // get the add to groups for uf fields
      if (!empty($value['add_to_group_id'])) {
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
    if ($isPayLater && !$this->_isBillingAddressRequiredForPayLater) {
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
    if (!empty($this->_params['hidden_onbehalf_profile'])) {
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
              'contribution_campaign_id',
              'member_campaign_id',
            ))) {
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
          if (substr($block, 0, 7) == 'custom_') {
            continue;
          }
          // fix the index of block elements
          if (is_array($vals)) {
            foreach ($vals as $key => $val) {
              //dont adjust the index of address block as
              //it's index is WRT to location type
              $newKey = ($block == 'address') ? $key : ++$key;
              $behalfOrganization[$block][$newKey] = $val;
            }
          }
        }
        unset($params['onbehalf_location']);
      }
      if (!empty($params['onbehalf[image_URL]'])) {
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

    if (empty($contactID)) {
      $dupeParams = $params;
      if (!empty($dupeParams['onbehalf'])) {
        unset($dupeParams['onbehalf']);
      }
      if (!empty($dupeParams['honor'])) {
        unset($dupeParams['honor']);
      }

      $dedupeParams = CRM_Dedupe_Finder::formatParams($dupeParams, 'Individual');
      $dedupeParams['check_permission'] = FALSE;
      $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual');

      // if we find more than one contact, use the first one
      $contactID = CRM_Utils_Array::value(0, $ids);

      // Fetch default greeting id's if creating a contact
      if (!$contactID) {
        foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
          if (!isset($params[$greeting])) {
            $params[$greeting] = CRM_Contact_BAO_Contact_Utils::defaultGreeting('Individual', $greeting);
          }
        }
      }
      $contactType = NULL;
    }
    else {
      $contactType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactID, 'contact_type');
    }
    $contactID = CRM_Contact_BAO_Contact::createProfileContact(
      $params,
      $fields,
      $contactID,
      $addToGroups,
      NULL,
      $contactType,
      TRUE
    );

    // Make the contact ID associated with the contribution available at the Class level.
    // Also make available to the session.
    //@todo consider handling this in $this->getContactID();
    $this->set('contactID', $contactID);
    $this->_contactID = $contactID;

    //get email primary first if exist
    $subscriptionEmail = array('email' => CRM_Utils_Array::value('email-Primary', $params));
    if (!$subscriptionEmail['email']) {
      $subscriptionEmail['email'] = CRM_Utils_Array::value("email-{$this->_bltID}", $params);
    }
    // subscribing contact to groups
    if (!empty($subscribeGroupIds) && $subscriptionEmail['email']) {
      CRM_Mailing_Event_BAO_Subscribe::commonSubscribe($subscribeGroupIds, $subscriptionEmail, $contactID);
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
    elseif (!empty($this->_membershipContactID) && $contactID != $this->_membershipContactID) {
      // this is an onbehalf renew case for inherited membership. For e.g a permissioned member of household,
      // store current user id as related contact for later use for mailing / activity..
      $this->_values['related_contact'] = $contactID;
      $this->_params['related_contact'] = $contactID;
      // swap contact like we do for on-behalf-org case, so parent/primary membership is affected
      $contactID = $this->_membershipContactID;
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
    if ((!empty($membershipParams['selectMembership']) &&
        $membershipParams['selectMembership'] != 'no_thanks'
      ) ||
      $this->_useForMember
    ) {
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

      //inherit campaign from contribution page.
      if (!array_key_exists('campaign_id', $membershipParams)) {
        $membershipParams['campaign_id'] = CRM_Utils_Array::value('campaign_id', $this->_values);
      }

      CRM_Core_Payment_Form::mapParams($this->_bltID, $this->_params, $membershipParams, TRUE);
      $this->doMembershipProcessing($contactID, $membershipParams, $premiumParams, $isPayLater);
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
      $financialTypeID = $this->wrangleFinancialTypeID($contributionTypeId);

      $result = CRM_Contribute_BAO_Contribution_Utils::processConfirm($this, $paymentParams,
        $premiumParams, $contactID,
        $financialTypeID,
        'contribution',
        $fieldTypes,
        ($this->_mode == 'test') ? 1 : 0,
        $isPayLater
      );

      if (CRM_Utils_Array::value('contribution_status_id', $result) == 1) {
        civicrm_api3('contribution', 'completetransaction', array(
          'id' => $result['contribution']->id,
          'trxn_id' => CRM_Utils_Array::value('trxn_id', $result),
          )
        );
      }
      return $result;
    }
  }

  /**
   * Membership processing section.
   *
   * This is in a separate function as part of a move towards refactoring.
   *
   * @param int $contactID
   * @param array $membershipParams
   * @param array $premiumParams
   * @param bool $isPayLater
   */
  protected function doMembershipProcessing($contactID, $membershipParams, $premiumParams, $isPayLater) {

    // added new parameter for cms user contact id, needed to distinguish behaviour for on behalf of sign-ups
    if (isset($this->_params['related_contact'])) {
      $membershipParams['cms_contactID'] = $this->_params['related_contact'];
    }
    else {
      $membershipParams['cms_contactID'] = $contactID;
    }

    if (!empty($membershipParams['onbehalf']) &&
      is_array($membershipParams['onbehalf']) && !empty($membershipParams['onbehalf']['member_campaign_id'])
    ) {
      $this->_params['campaign_id'] = $membershipParams['onbehalf']['member_campaign_id'];
    }

    $customFieldsFormatted = $fieldTypes = array();
    if (!empty($membershipParams['onbehalf']) &&
      is_array($membershipParams['onbehalf'])
    ) {
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
      $contributionTypeID = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $priceFieldIds['id'], 'financial_type_id');
      unset($priceFieldIds['id']);
      $membershipTypeIds = array();
      $membershipTypeTerms = array();
      foreach ($priceFieldIds as $priceFieldId) {
        if ($id = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $priceFieldId, 'membership_type_id')) {
          $membershipTypeIds[] = $id;
          //@todo the value for $term is immediately overwritten. It is unclear from the code whether it was intentional to
          // do this or a double = was intended (this ambiguity is the reason many IDEs complain about 'assignment in condition'
          $term = 1;
          if ($term = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $priceFieldId, 'membership_num_terms')) {
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
    if (!empty($membershipParams['selectMembership'])) {
      // CRM-12233
      $membershipLineItems = array();
      if ($this->_separateMembershipPayment && $this->_values['amount_block_is_active']) {
        foreach ($this->_values['fee'] as $key => $feeValues) {
          if ($feeValues['name'] == 'membership_amount') {
            $fieldId = $this->_params['price_' . $key];
            $membershipLineItems[$this->_priceSetId][$fieldId] = $this->_lineItem[$this->_priceSetId][$fieldId];
            unset($this->_lineItem[$this->_priceSetId][$fieldId]);
            break;
          }
        }
      }
      $this->processMembership($membershipParams, $contactID, $customFieldsFormatted, $fieldTypes, $premiumParams, $membershipLineItems, $isPayLater);
      if (!$this->_amount > 0.0 || !$membershipParams['amount']) {
        // we need to explicitly create a CMS user in case of free memberships
        // since it is done under processConfirm for paid memberships
        CRM_Contribute_BAO_Contribution_Utils::createCMSUser($membershipParams,
          $membershipParams['cms_contactID'],
          'email-' . $this->_bltID
        );
      }
    }
  }

}
