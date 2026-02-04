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
use Civi\Api4\Contribution;
use Civi\Api4\Membership;

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
   * The id of the contribution object that is created when the form is submitted, or passed in.
   *
   * @var int
   *
   * @deprecated use getContributionID()
   */
  public $_contributionID;

  public $submitOnce = TRUE;

  private array $lineItems;

  /**
   * @return int|null
   */
  private function getSelectedProductID(): ?int {
    $selectedProductID = $this->getSubmittedValue('selectProduct') ?: NULL;
    if ($selectedProductID === 'no_thanks') {
      $selectedProductID = NULL;
    }
    return $selectedProductID;
  }

  /**
   * @return mixed|null
   */
  private function getSelectedProductOption(): mixed {
    return $this->getSubmittedValue('options_' . $this->getSelectedProductID());
  }

  /**
   * @return mixed|string
   */
  public function getMembershipSource(): mixed {
    $membershipSource = $this->getSubmittedValue('membership_source') ?: (ts('Online Contribution:') . $this->getContributionPageValue('frontend_title'));
    return $membershipSource;
  }

  /**
   * @return int|null
   */
  private function getPaymentInstrumentID(): ?int {
    if (!empty($this->_paymentProcessor)) {
      return $this->_paymentProcessor['payment_instrument_id'];
    }
    return NULL;
  }

  /**
   * @param int $contactID
   *
   * @return array
   */
  public function getCustomFieldsForMembership(int $contactID): array {
    $customFieldsFormatted = [];
    if ($this->getSubmittedValue('onbehalf') &&
      is_array($this->getSubmittedValue('onbehalf'))
    ) {
      foreach ($this->getSubmittedValue('onbehalf') as $key => $value) {
        if (str_contains($key, 'custom_')) {
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
    }
    return $customFieldsFormatted;
  }

  /**
   * Get the contact id that the form is being submitted for.
   *
   * @return int|null
   */
  public function getContactID(): ?int {
    if (isset($this->_contactID)) {
      return $this->_contactID;
    }
    return parent::getContactID();
  }

  /**
   * @return string
   * @throws \CRM_Core_Exception
   */
  public function getSource(): string {
    return ts('Online Contribution') . ': ' . (!empty($this->_pcpInfo['title']) ? $this->_pcpInfo['title'] : $this->getContributionValue('frontend_title'));
  }

  /**
   * @param int $membershipTypeID
   *
   * @return array|bool
   * @throws \CRM_Core_Exception
   */
  protected function getExistingMembership(int $membershipTypeID): array|false {
    $contactID = $this->_membershipContactID ?: $this->getContactID();
    // CRM-7297 - allow membership type to be changed during renewal so long as the parent org of new membershipType
    // is the same as the parent org of an existing membership of the contact
    return CRM_Member_BAO_Membership::getContactMembership($contactID, $membershipTypeID,
      $this->isTest(), NULL, TRUE
    );
  }

  /**
   * @return array|null
   */
  public function getSubmittedPcpValues(): ?array {
    $pcp = $this->getPcpID() ? [
      'pcp_mode_through_id' => $this->getPcpID(),
      'pcp_display_in_roll' => $this->getSubmittedValue('pcp_display_in_roll'),
      'pcp_roll_nickname' => $this->getSubmittedValue('pcp_roll_nickname'),
      'pcp_personal_note' => $this->getSubmittedValue('pcp_personal_note'),
    ] : NULL;
    return $pcp;
  }

  /**
   * @return int
   */
  public function getPaymentProcessorID(): int {
    $submittedValue = $this->getSubmittedValue('payment_processor_id');
    if (is_numeric($submittedValue)) {
      return (int) $submittedValue;
    }
    // If there is no processor we are using the pay-later manual pseudo-processor.
    // (note it might make sense to make this a row in the processor table in the db).
    return $this->_paymentProcessor['id'] ?? 0;
  }

  /**
   * Get the parameters required for `doPayment()`
   *
   * The parameters set in this function should be those 'promised' in
   * https://docs.civicrm.org/dev/en/latest/extensions/payment-processors/paymentclass/#core-parameters
   *
   * @param int $financialTypeID
   * @param int $contactID
   *
   * @return array
   */
  private function getPaymentParams(int $financialTypeID, int $contactID): array {
    $paymentParams = $this->getBasePaymentParams();
    $paymentParams['financial_type_id'] = $financialTypeID;
    $paymentParams['accounting_code'] = CRM_Financial_BAO_FinancialAccount::getAccountingCode($financialTypeID);
    $paymentParams['contactID'] = $contactID;
    return $paymentParams;
  }

  /**
   * It is pay later if there IS an amount but the processor is 0 (Manual).
   * @return true
   * @throws \CRM_Core_Exception
   */
  public function isPayLater(): bool {
    return (!$this->getPaymentProcessorID() && $this->order->getTotalAmount());
  }

  /**
   *  Get the base parameters required for `doPayment()` that come directly from the submitted values
   *
   *  The parameters set in this function should be those 'promised' in
   *  https://docs.civicrm.org/dev/en/latest/extensions/payment-processors/paymentclass/#core-parameters
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function getBasePaymentParams(): array {
    $paymentParams = [];
    CRM_Core_Payment_Form::mapParams(NULL, $this->getSubmittedValues(), $paymentParams, TRUE);
    $paymentParams['contributionPageID'] = $this->getContributionPageID();
    $paymentParams['campaign_id'] = $this->getCampaignID();
    $paymentParams['currency'] = $this->getCurrency();
    $paymentParams['description'] = $this->getSource();
    return $paymentParams;
  }

  /**
   * @param int|null $financialTypeID
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function isDeductible(?int $financialTypeID): bool {
    return $financialTypeID && CRM_Financial_BAO_FinancialType::getFieldValue('CRM_Financial_BAO_FinancialType', 'is_deductible', 'id', $financialTypeID);
  }

  /**
   * Previously shared code.
   *
   * @param $params
   * @param $contribution
   */
  private function handlePledge($params, $contribution): void {
    $pledgeID = $this->getPledgeID();
    if ($pledgeID) {
      //when user doing pledge payments.
      //update the schedule when payment(s) are made
      $amount = $params['amount'];
      $pledgePaymentParams = [];
      foreach ($params['pledge_amount'] as $paymentId => $dontCare) {
        $scheduledAmount = CRM_Core_DAO::getFieldValue(
          'CRM_Pledge_DAO_PledgePayment',
          $paymentId,
          'scheduled_amount',
          'id'
        );

        $pledgePayment = ($amount >= $scheduledAmount) ? $scheduledAmount : $amount;
        if ($pledgePayment > 0) {
          $pledgePaymentParams[] = [
            'id' => $paymentId,
            'contribution_id' => $contribution->id,
            'status_id' => $contribution->contribution_status_id,
            'actual_amount' => $pledgePayment,
          ];
          $amount -= $pledgePayment;
        }
      }
      if ($amount > 0 && count($pledgePaymentParams)) {
        $pledgePaymentParams[count($pledgePaymentParams) - 1]['actual_amount'] += $amount;
      }
      foreach ($pledgePaymentParams as $p) {
        CRM_Pledge_BAO_PledgePayment::add($p);
      }

      //update pledge status according to the new payment statuses
      CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($pledgeID);
    }
    else {
      //when user creating pledge record.
      $pledgeParams = [];
      $pledgeParams['contact_id'] = $contribution->contact_id;
      $pledgeParams['installment_amount'] = $pledgeParams['actual_amount'] = $contribution->total_amount;
      $pledgeParams['contribution_id'] = $contribution->id;
      $pledgeParams['contribution_page_id'] = $contribution->contribution_page_id;
      $pledgeParams['financial_type_id'] = $contribution->financial_type_id;
      $pledgeParams['frequency_interval'] = $params['pledge_frequency_interval'];
      $pledgeParams['installments'] = $params['pledge_installments'];
      $pledgeParams['frequency_unit'] = $params['pledge_frequency_unit'];
      if ($pledgeParams['frequency_unit'] === 'month') {
        $pledgeParams['frequency_day'] = intval(date("d"));
      }
      else {
        $pledgeParams['frequency_day'] = 1;
      }
      $pledgeParams['create_date'] = $pledgeParams['start_date'] = $pledgeParams['scheduled_date'] = date("Ymd");
      if (!empty($params['start_date'])) {
        $pledgeParams['frequency_day'] = intval(date("d", strtotime($params['start_date'])));
        $pledgeParams['start_date'] = $pledgeParams['scheduled_date'] = date('Ymd', strtotime($params['start_date']));
      }
      $pledgeParams['status_id'] = $contribution->contribution_status_id;
      $pledgeParams['max_reminders'] = $this->getPledgeBlockValue('max_reminders');
      $pledgeParams['initial_reminder_day'] = $this->getPledgeBlockValue('initial_reminder_day');
      $pledgeParams['additional_reminder_day'] = $this->getPledgeBlockValue('additional_reminder_day');
      $pledgeParams['is_test'] = $this->isTest();
      $pledgeParams['acknowledge_date'] = date('Ymd');
      $pledgeParams['original_installment_amount'] = $pledgeParams['installment_amount'];

      $pledgeParams['campaign_id'] = $this->getCampaignID();

      $pledge = CRM_Pledge_BAO_Pledge::create($pledgeParams);

      $this->setPledgeID($pledge->id);

      //send acknowledgment email. only when pledge is created
      if ($pledge->id && $this->isEmailReceipt()) {
        //build params to send acknowledgment.
        $pledgeParams['id'] = $pledge->id;
        $pledgeParams['receipt_from_name'] = $this->getContributionPageValue('receipt_from_name');
        $pledgeParams['receipt_from_email'] = $this->getContributionPageValue('receipt_from_email');

        //scheduled amount will be same as installment_amount.
        $pledgeParams['scheduled_amount'] = $pledgeParams['installment_amount'];

        //get total pledge amount.
        $pledgeParams['total_pledge_amount'] = $pledge->amount;

        CRM_Pledge_BAO_Pledge::sendAcknowledgment($this, $pledgeParams);
      }
    }
  }

  /**
   * Set the parameters to be passed to contribution create function.
   *
   * @param array $params
   * @param int $recurringContributionID
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function getContributionParams(
    $params, $recurringContributionID) {
    $contributionParams = [
      'receive_date' => !empty($params['receive_date']) ? CRM_Utils_Date::processDate($params['receive_date']) : date('YmdHis'),
      'tax_amount' => $params['tax_amount'] ?? NULL,
      'amount_level' => $this->getMainContributionAmountLevel(),
      'invoice_id' => $params['invoiceID'],
      'currency' => $this->getCurrency(),
      'is_pay_later' => $this->isPayLater(),
      //configure cancel reason, cancel date and thankyou date
      //from 'contribution' type profile if included
      'cancel_reason' => $params['cancel_reason'] ?? 0,
      'cancel_date' => isset($params['cancel_date']) ? CRM_Utils_Date::format($params['cancel_date']) : NULL,
      'thankyou_date' => isset($params['thankyou_date']) ? CRM_Utils_Date::format($params['thankyou_date']) : NULL,
      'receipt_date' => $this->isEmailReceipt() ? date('YmdHis') : NULL,
      'payment_instrument_id' => $this->getPaymentInstrumentID(),
    ];

    if ($recurringContributionID) {
      $contributionParams['contribution_recur_id'] = $recurringContributionID;
    }

    $contributionParams['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending');
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
   * @see https://issues.civicrm.org/jira/browse/CRM-11885
   *  if non_deductible_amount exists i.e. Additional Details fieldset was opened [and staff typed something] -> keep
   * it.
   *
   * @param array $params
   * @param int|null $financialTypeID
   *
   * @return float
   */
  private function getNonDeductibleAmount($params, ?int $financialTypeID) {
    if ((!empty($params['non_deductible_amount']))) {
      return $params['non_deductible_amount'];
    }
    $priceSetId = $params['priceSetId'] ?? NULL;
    // return non-deductible amount if it is set at the price field option level
    if ($priceSetId && !empty($this->getLineItems())) {
      $nonDeductibleAmount = CRM_Price_BAO_PriceSet::getNonDeductibleAmountFromPriceSet($priceSetId, [$this->getPriceSetID() => $this->getLineItems()]);
    }

    if (!empty($nonDeductibleAmount)) {
      return $nonDeductibleAmount;
    }
    else {
      if ($this->isDeductible($financialTypeID)) {
        // if there is a product - compare the value to the contribution amount
        if ($this->getSelectedProductID()) {
          $productDAO = new CRM_Contribute_DAO_Product();
          $productDAO->id = $this->getSelectedProductID();
          $productDAO->find(TRUE);
          // product value exceeds contribution amount
          if ($params['amount'] < $productDAO->price) {
            return $params['amount'];
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
    parent::preProcess();
    $this->_ccid = $this->getExistingContributionID();

    $this->_params = $this->controller->exportValues('Main');
    $this->_params['ip_address'] = CRM_Utils_System::ipAddress();
    $this->_params['amount'] = $this->getMainContributionAmount();
    if (isset($this->_params['amount'])) {
      $this->setFormAmountFields($this->getPriceSetID());
    }

    $this->_useForMember = $this->get('useForMember');

    CRM_Contribute_Form_AbstractEditPayment::formatCreditCardDetails($this->_params);

    if (!empty($this->_membershipBlock)) {
      $this->_params['selectMembership'] = $this->get('selectMembership');
    }
    if (!empty($this->_paymentProcessor) &&  $this->_paymentProcessor['object']->supports('preApproval')) {
      $preApprovalParams = $this->_paymentProcessor['object']->getPreApprovalDetails($this->get('pre_approval_parameters'));
      $this->_params = array_merge($this->_params, $preApprovalParams);

      // We may have fetched some billing details from the getPreApprovalDetails function so we
      // want to ensure we set this after that function has been called.
      CRM_Core_Payment_Form::mapParams(NULL, $preApprovalParams, $this->_params, FALSE);
    }

    $this->_params['is_pay_later'] = $this->get('is_pay_later');
    $this->assign('is_pay_later', $this->_params['is_pay_later']);
    if ($this->_params['is_pay_later']) {
      $this->assign('pay_later_receipt', $this->_values['pay_later_receipt'] ?? NULL);
    }
    // if onbehalf-of-organization
    if (!empty($this->_values['onbehalf_profile_id']) && !empty($this->_params['onbehalf']['organization_name'])) {
      if (empty($this->_params['org_option']) && empty($this->_params['organization_id'])) {
        $this->_params['organization_id'] = $this->_params['onbehalfof_id'] ?? NULL;
      }
      $this->_params['organization_name'] = $this->_params['onbehalf']['organization_name'];
      $addressBlocks = [
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
      ];

      $blocks = ['email', 'phone', 'im', 'url', 'openid'];
      foreach ($this->_params['onbehalf'] as $loc => $value) {
        $field = $typeId = NULL;
        if (str_contains($loc, '-')) {
          [$field, $locType] = explode('-', $loc);
        }

        if (in_array($field, $addressBlocks) && !empty($value)) {
          if ($locType === 'Primary') {
            $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
            $locType = $defaultLocationType->id;
          }

          if ($field === 'country') {
            $value = CRM_Core_PseudoConstant::countryIsoCode($value);
          }
          elseif ($field === 'state_province') {
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
            if ($locType === 'Primary') {
              $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
              $locationValue = $defaultLocationType->id;
            }
            else {
              $locationValue = $locType;
            }
            $locTypeId = '';
            $phoneExtField = [];

            if ($field === 'url') {
              $blockName = 'website';
              $locationType = 'website_type_id';
              [$field, $locationValue] = explode('-', $loc);
            }
            elseif ($field === 'im') {
              $fieldName = 'name';
              $locTypeId = 'provider_id';
              $typeId = $this->_params['onbehalf']["{$loc}-provider_id"];
            }
            elseif ($field == 'phone') {
              [$field, $locType, $typeId] = explode('-', $loc);
              $locTypeId = 'phone_type_id';

              //check if extension field exists
              $extField = str_replace('phone', 'phone_ext', $loc);
              if (isset($this->_params['onbehalf'][$extField])) {
                $phoneExtField = ['phone_ext' => $this->_params['onbehalf'][$extField]];
              }
            }

            $isPrimary = 1;
            if (isset($this->_params['onbehalf_location'][$blockName])
              && count($this->_params['onbehalf_location'][$blockName]) > 0
            ) {
              $isPrimary = 0;
            }
            if ($locationValue) {
              $blockValues = [
                $fieldName => $value,
                $locationType => $locationValue,
                'is_primary' => $isPrimary,
              ];

              if ($locTypeId) {
                $blockValues = array_merge($blockValues, [$locTypeId => $typeId]);
              }
              if (!empty($phoneExtField)) {
                $blockValues = array_merge($blockValues, $phoneExtField);
              }

              $this->_params['onbehalf_location'][$blockName][] = $blockValues;
            }
          }
        }
        elseif (str_contains($loc, 'custom')) {
          if ($value && isset($this->_params['onbehalf']["{$loc}_id"])) {
            $value = $this->_params['onbehalf']["{$loc}_id"];
          }
          $this->_params['onbehalf_location']["{$loc}"] = $value;
        }
        else {
          if ($loc === 'contact_sub_type') {
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
      foreach (['phone', 'email', 'address'] as $blk) {
        if (isset($this->_params[$blk])) {
          unset($this->_params[$blk]);
        }
      }
    }
    $this->setRecurringMembershipParams();

    if ($this->getPcpID()) {
      $params = $this->processPcp($this->_params);
      $this->_params = $params;
    }
    else {
      $this->assign('pcpBlock');
    }
    $this->_params['invoiceID'] = $this->get('invoiceID');

    // @todo stop setting this - use directly
    $this->_params['campaign_id'] = $this->getCampaignID();

    // assign contribution page id to the template so we can add css class for it
    $this->assign('contributionPageID', $this->_id);
    $this->assign('is_for_organization', $this->_params['is_for_organization'] ?? NULL);

    $this->set('params', $this->_params);
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    // FIXME: Some of this code is identical to Thankyou.php and should be broken out into a shared function
    $this->assignToTemplate();

    $params = $this->_params;
    // make sure we have values for it
    if (!empty($this->_values['honoree_profile_id']) && !empty($params['soft_credit_type_id']) && empty($this->getExistingContributionID())) {
      $honorName = NULL;
      $softCreditTypes = CRM_Core_OptionGroup::values("soft_credit_type", FALSE);

      $this->assign('soft_credit_type', $softCreditTypes[$params['soft_credit_type_id']]);
      CRM_Contribute_BAO_ContributionSoft::formatHonoreeProfileFields($this, $params['honor']);

      $fieldTypes = ['Contact'];
      $fieldTypes[] = CRM_Core_BAO_UFGroup::getContactType($this->_values['honoree_profile_id']);
      $this->buildCustom($this->_values['honoree_profile_id'], 'honoreeProfileFields', TRUE, 'honor', $fieldTypes);
    }
    else {
      $this->assign('honoreeProfileFields');
    }
    $this->assign('receiptFromEmail', $this->_values['receipt_from_email'] ?? NULL);
    $this->assign('amount_block_is_active', $this->isFormSupportsNonMembershipContributions());
    $this->assign('taxTerm', \Civi::settings()->get('tax_term'));
    $this->assign('totalTaxAmount', $this->order->getTotalTaxAmount());
    $this->assign('isDisplayLineItems', !$this->isQuickConfig());
    $this->assign('is_quick_config', $this->isQuickConfig());
    // Accessing this value in params is deprecated.
    $this->_params['is_quick_config'] = $this->isQuickConfig();
    if (!$this->isQuickConfig()) {
      $this->assign('lineItem', [$this->getPriceSetID() => $this->order->getLineItems()]);
    }
    if ($this->getSelectedProductID()) {
      $option = $this->getSelectedProductOption();
      $this->buildPremiumsBlock(FALSE, $option);
      $this->set('option', $option);
    }
    else {
      $this->assign('products');
    }
    // These 2 assigns may be overwritten in buildMembershipBlock.
    // and they drive the text around reneal selection.
    $this->assign('auto_renew', $this->getSubmittedValue('auto_renew'));
    foreach ($this->getLineItems() as $lineItem) {
      if ($lineItem['auto_renew'] ?? NULL === 2) {
        $this->assign('auto_renew', TRUE);
        $this->assign('autoRenewOption', 2);
      }
    }
    $this->assign('membershipBlock', FALSE);
    if (CRM_Core_Component::isEnabled('CiviMember') && empty($this->getExistingContributionID())) {
      if (isset($params['selectMembership']) &&
        $params['selectMembership'] !== 'no_thanks'
      ) {
        $this->buildMembershipBlock(
          $this->_membershipContactID,
          $params['selectMembership']
        );
      }
    }

    if (empty($this->getExistingContributionID())) {
      $this->buildCustom($this->_values['custom_pre_id'], 'customPre', TRUE);
      $this->buildCustom($this->_values['custom_post_id'], 'customPost', TRUE);
    }

    if (!empty($this->_values['onbehalf_profile_id']) &&
      !empty($params['onbehalf']) &&
      ($this->_values['is_for_organization'] == 2 ||
        !empty($params['is_for_organization'])
      ) && empty($this->getExistingContributionID())
    ) {
      $fieldTypes = ['Contact', 'Organization'];
      $contactSubType = CRM_Contact_BAO_ContactType::subTypes('Organization');
      $fieldTypes = array_merge($fieldTypes, $contactSubType);
      if (is_array($this->_membershipBlock) && !empty($this->_membershipBlock)) {
        $fieldTypes = array_merge($fieldTypes, ['Membership']);
      }
      else {
        $fieldTypes = array_merge($fieldTypes, ['Contribution']);
      }

      $this->buildCustom($this->_values['onbehalf_profile_id'], 'onbehalfProfile', TRUE, 'onbehalf', $fieldTypes);
    }
    else {
      $this->assign('onbehalfProfile');
    }

    $this->_separateMembershipPayment = $this->isSeparateMembershipPayment();
    $this->assign('is_separate_payment', $this->isSeparateMembershipPayment());

    $this->assign('priceSetID', $this->_priceSetId);
    $contributionButtonText = $this->getPaymentProcessorObject()->getText('contributionPageButtonText', [
      'is_payment_to_existing' => !empty($this->getExistingContributionID()),
      'amount' => $this->_amount,
    ]);
    $this->assign('button', $contributionButtonText);

    $this->assign('continueText',
      $this->getPaymentProcessorObject()->getText('contributionPageContinueText', [
        'is_payment_to_existing' => !empty($this->getExistingContributionID()),
        'amount' => $this->_amount,
      ])
    );
    $this->assign('confirmText',
      $this->getPaymentProcessorObject()->getText('contributionPageConfirmText', [
        'is_payment_to_existing' => !empty($this->getExistingContributionID()),
        'amount' => $this->_amount,
      ])
    );

    $this->addButtons([
      [
        'type' => 'next',
        'name' => $contributionButtonText,
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ],
      [
        'type' => 'back',
        'name' => ts('Go Back'),
      ],
    ]);

    $defaults = [];
    $fields = array_fill_keys(array_keys($this->_fields), 1);
    $fields["billing_state_province-{$this->_bltID}"] = $fields["billing_country-{$this->_bltID}"] = $fields["email-{$this->_bltID}"] = 1;

    $contact = $this->_params;
    foreach ($fields as $name => $dontCare) {
      // Recursively set defaults for nested fields
      if (isset($contact[$name]) && is_array($contact[$name]) && ($name === 'onbehalf' || $name === 'honor')) {
        foreach ($contact[$name] as $fieldName => $fieldValue) {
          if (is_array($fieldValue) && $this->_fields[$name][$fieldName]['html_type'] === 'CheckBox') {
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
        if (substr($name, 0, 7) === 'custom_') {
          $timeField = "{$name}_time";
          if (isset($contact[$timeField])) {
            $defaults[$timeField] = $contact[$timeField];
          }
          if (isset($contact["{$name}_id"])) {
            $defaults["{$name}_id"] = $contact["{$name}_id"];
          }
        }
        elseif (in_array($name, [
          'addressee',
          'email_greeting',
          'postal_greeting',
        ]) && !empty($contact[$name . '_custom'])
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
   * Build Membership  Block in Contribution Pages.
   * @todo this was shared on CRM_Contribute_Form_ContributionBase but we are refactoring and simplifying for each
   *   step (main/confirm/thankyou)
   *
   * @param int $cid
   *   Contact checked for having a current membership for a particular membership.
   * @param int|array $selectedMembershipTypeID
   *   Selected membership id.
   *
   * @return bool
   *   Is this a separate membership payment
   *
   * @throws \CRM_Core_Exception
   */
  private function buildMembershipBlock($cid, $selectedMembershipTypeID = NULL) {
    $separateMembershipPayment = FALSE;
    if ($this->_membershipBlock) {
      $membershipTypeIds = $membershipTypes = [];
      $membershipPriceset = (!empty($this->_priceSetId) && $this->_useForMember);

      $autoRenewMembershipTypeOptions = [];

      $separateMembershipPayment = $this->_membershipBlock['is_separate_payment'] ?? NULL;

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
          $memType = $this->getMembershipType($value);
          if ($selectedMembershipTypeID != NULL) {
            if ($memType['id'] == $selectedMembershipTypeID) {
              $this->assign('minimum_fee', $memType['minimum_fee'] ?? NULL);
              if ($cid) {
                $membership = new CRM_Member_DAO_Membership();
                $membership->contact_id = $cid;
                $membership->membership_type_id = $memType['id'];
                if ($membership->find(TRUE)) {
                  $this->assign('renewal_mode', TRUE);
                  $memType['current_membership'] = $membership->end_date;
                }
              }
              $membershipTypes[] = $memType;
            }
          }
          elseif ($memType['is_active']) {

            if ($allowAutoRenewOpt) {
              $isAvailableAutoRenew = $this->_membershipBlock['auto_renew'][$value] ?? 1;
              $autoRenewMembershipTypeOptions["autoRenewMembershipType_{$value}"] = (int) $memType['auto_renew'] * $isAvailableAutoRenew;
            }
            else {
              $autoRenewMembershipTypeOptions["autoRenewMembershipType_{$value}"] = 0;
            }

            if ($cid) {
              //show current membership, skip pending and cancelled membership records,
              //because we take first membership record id for renewal
              $membership = Membership::get(FALSE)
                ->addSelect('end_date', 'membership_type_id', 'membership_type_id.duration_unit:name')
                ->addWhere('contact_id', '=', $cid)
                ->addWhere('membership_type_id', '=', $memType['id'])
                ->addWhere('status_id:name', 'NOT IN', ['Cancelled', 'Pending'])
                ->addWhere('is_test', '=', FALSE)
                ->addOrderBy('end_date', 'DESC')
                ->execute()
                ->first();

              if ($membership && $membership['membership_type_id.duration_unit:name'] !== 'lifetime') {
                $this->assign('renewal_mode', TRUE);
                $memType['current_membership'] = $membership['end_date'];
                if (!$endDate) {
                  $endDate = $memType['current_membership'];
                }
                if ($memType['current_membership'] < $endDate) {
                  $endDate = $memType['current_membership'];
                }
              }
            }
            $membershipTypes[] = $memType;
          }
        }
      }

      $this->assign('membershipBlock', $this->_membershipBlock);
      $this->assign('showRadio', FALSE);
      $this->assignTotalAmounts();
      $this->assign('membershipTypes', $membershipTypes);
      $this->assign('autoRenewMembershipTypeOptions', json_encode($autoRenewMembershipTypeOptions));
      //give preference to user submitted auto_renew value.
      $takeUserSubmittedAutoRenew = (!empty($_POST) || $this->isSubmitted());
      $this->assign('takeUserSubmittedAutoRenew', $takeUserSubmittedAutoRenew);

      // Assign autorenew option (0:hide,1:optional,2:required) so we can use it in confirmation etc.
      $autoRenewOption = CRM_Price_BAO_PriceSet::checkAutoRenewForPriceSet($this->_priceSetId);
      //$selectedMembershipTypeID is retrieved as an array for membership priceset if multiple
      //options for different organisation is selected on the contribution page.
      if (is_numeric($selectedMembershipTypeID) && $this->getMembershipType($selectedMembershipTypeID)['auto_renew']) {
        $this->assign('autoRenewOption', $this->getMembershipType($selectedMembershipTypeID)['auto_renew']);
      }
      else {
        $this->assign('autoRenewOption', $autoRenewOption);
      }
    }

    return $separateMembershipPayment;
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
    try {
      $result = $this->processFormSubmission($contactID);
    }
    catch (CRM_Core_Exception $e) {
      \Civi::log()->error('CRM_Contribute_Form_Contribution_Confirm::PostProcess processFormSubmissionException: ' . $e->getMessage());
      $this->bounceOnError($e->getMessage());
    }

    if (is_array($result) && !empty($result['is_payment_failure'])) {
      \Civi::log()->error('CRM_Contribute_Form_Contribution_Confirm::PostProcess is_payment_failure: ' . $result['error']->getMessage());
      $this->bounceOnError($result['error']->getMessage());
    }
    // Presumably this is for hooks to access? Not quite clear & perhaps not required.
    $this->set('params', $this->_params);
  }

  /**
   * Process the form.
   *
   * @param CRM_Contribute_BAO_Contribution $contribution
   */
  protected function postProcessPremium($contribution) {
    $hour = $minute = $second = 0;
    // assigning Premium information to receipt tpl
    $selectProduct = $this->getSelectedProductID();
    if ($this->getSelectedProductID()) {
      $startDate = $endDate = "";
      $productDAO = new CRM_Contribute_DAO_Product();
      $productDAO->id = $this->getSelectedProductID();
      $productDAO->find(TRUE);

      $periodType = $productDAO->period_type;

      if ($periodType) {
        $fixed_period_start_day = $productDAO->fixed_period_start_day;
        $duration_unit = $productDAO->duration_unit;
        $duration_interval = $productDAO->duration_interval;
        if ($periodType === 'rolling') {
          $startDate = date('Y-m-d');
        }
        elseif ($periodType === 'fixed') {
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
            $year += $duration_interval;
            break;

          case 'month':
            $month += $duration_interval;
            break;

          case 'day':
            $day += $duration_interval;
            break;

          case 'week':
            $day += ($duration_interval * 7);
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
      $params = [
        'product_id' => $this->getSelectedProductID(),
        'contribution_id' => $contribution->id,
        'product_option' => $this->getSelectedProductOption(),
        'quantity' => 1,
        'start_date' => CRM_Utils_Date::customFormat($startDate, '%Y%m%d'),
        'end_date' => CRM_Utils_Date::customFormat($endDate, '%Y%m%d'),
      ];
      if ($this->getSelectedProductID()) {
        $daoPremiumsProduct = new CRM_Contribute_DAO_PremiumsProduct();
        $daoPremiumsProduct->product_id = $this->getSelectedProductID();
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
        $trxnParams = [
          'cost' => $productDAO->cost,
          'currency' => $productDAO->currency,
          'financial_type_id' => $params['financial_type_id'],
          'contributionId' => $contribution->id,
        ];
        CRM_Core_BAO_FinancialTrxn::createPremiumTrxn($trxnParams);
      }
    }
    elseif ($this->getSubmittedValue('selectProduct') === 'no_thanks') {
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
   * @param array $params
   * @param array $contributionParams
   *   Parameters to be passed to contribution create action.
   *   This differs from params in that we are currently adding params to it and 1) ensuring they are being
   *   passed consistently & 2) documenting them here.
   *   - contact_id
   *   - line_item
   *   - is_test (no longer used)
   *   - campaign_id (no longer used)
   *   - contribution_page_id
   *   - source
   *   - payment_type_id
   *   - thankyou_date (not all forms will set this)
   *
   * @param bool $isRecur
   *   Is this recurring?
   * @param bool $isSecondContribution
   *
   * @return \CRM_Contribute_DAO_Contribution
   *
   * @throws \CRM_Core_Exception
   * @todo - this code was previously shared with the backoffice form - some parts of this
   * function may relate to that form, not this one.
   */
  protected function processFormContribution(
    $params,
    $contributionParams,
    $isRecur,
    bool $isSecondContribution
  ) {
    $contactID = $contributionParams['contact_id'];

    if (!$isSecondContribution && !empty($this->getPledgeBlockID()) &&
      ($this->getSubmittedValue('is_pledge') || $this->getPledgeID())) {
      $isPledge = TRUE;
    }
    else {
      $isPledge = FALSE;
    }

    $contributionParams['address_id'] = CRM_Contribute_BAO_Contribution::createAddress($params);
    // We may no longer need to set params['is_recur'] - it used to be used in processRecurringContribution
    $params['is_recur'] = $isRecur;
    $recurringContributionID = !$isRecur ? NULL : $this->processRecurringContribution($params, [
      'contact_id' => $contactID,
      'financial_type_id' => $contributionParams['financial_type_id'],
    ]);

    if (isset($params['amount'])) {
      $contributionParams = array_merge($this->getContributionParams(
        $params,
        $recurringContributionID), $contributionParams
      );

      $contributionParams['non_deductible_amount'] = $this->getNonDeductibleAmount($params, $contributionParams['financial_type_id']);
      $contributionParams['skipCleanMoney'] = TRUE;
      // @todo this is the wrong place for this - it should be done as close to form submission
      // as possible
      $contributionParams['total_amount'] = $params['amount'];

      if (!empty($contribution['id'])) {
        Contribution::update(FALSE)
          ->addWhere('id', '=', $contribution['id'])
          ->setValues($contributionParams)
          ->execute()->first();
      }
      else {
        $contribution = CRM_Contribute_BAO_Contribution::add($contributionParams);
      }

      // lets store it in the form variable so postProcess hook can access it via getContributionID()
      $this->_contributionID = $contribution->id;
    }

    // process soft credit / pcp params first
    $this->formatSoftCreditParams($params);

    //CRM-13981, processing honor contact into soft-credit contribution
    if ($this->getSubmittedPcpValues()) {
      $this->savePCP(['id' => $contribution->id, 'total_amount' => $contribution->total_amount]);
    }
    $this->processSoftContribution($params, $contribution);

    if ($isPledge) {
      $this->handlePledge($params, $contribution);
    }

    if ($contribution) {
      CRM_Core_BAO_CustomValueTable::postProcess($params,
        'civicrm_contribution',
        $contribution->id,
        'Contribution'
      );
    }
    // Save note
    if ($contribution && !empty($params['contribution_note'])) {
      $noteParams = [
        'entity_table' => 'civicrm_contribution',
        'note' => $params['contribution_note'],
        'entity_id' => $contribution->id,
        'contact_id' => $contribution->contact_id,
      ];

      CRM_Core_BAO_Note::add($noteParams, []);
    }

    //create contribution activity w/ individual and target
    //activity w/ organisation contact id when onbelf, CRM-4027
    if ($this->getSubmittedValue('onbehalf_contact_id')) {
      $this->addActivity([
        'source_contact_id' => $params['onbehalf_contact_id'],
        'source_record_id' => $contribution->id,
        'activity_date_time' => $contribution->receive_date,
        'target_contact_id' => [$contribution->contact_id],
      ], [
        'amount' => CRM_Utils_Money::format($contribution->total_amount, $contribution->currency),
        'description' => $contribution->source,
      ]);
    }

    return $contribution;
  }

  /**
   * Process the soft contribution and/or link to personal campaign page.
   *
   * @param array $params
   * @param CRM_Contribute_BAO_Contribution $contribution
   *
   * @throws \CRM_Core_Exception
   */
  private function processSoftContribution($params, $contribution) {
    if (isset($params['soft_credit'])) {
      $softIDs = CRM_Contribute_BAO_ContributionSoft::getSoftCreditIds($contribution->id);
      $softParams = $params['soft_credit'];
      foreach ($softParams as $softParam) {
        if (!empty($softIDs)) {
          $key = key($softIDs);
          $softParam['id'] = $softIDs[$key];
          unset($softIDs[$key]);
        }
        $softParam['contribution_id'] = $contribution->id;
        $softParam['currency'] = $contribution->currency;
        //case during Contribution Import when we assign soft contribution amount as contribution's total_amount by default
        if (empty($softParam['amount'])) {
          $softParam['amount'] = $contribution->total_amount;
        }
        CRM_Contribute_BAO_ContributionSoft::add($softParam);
      }
    }
  }

  /**
   * Add activity for the Contribution.
   *
   * At this point we have a pending donation.
   *
   * Normally the activity is created when the donation is completed.
   * However, for on-behalf donations the way in which we establish the
   * on-behalf connection is via the activity - so we create it
   * in a pending form when we create the pending donation.
   *
   * Could we create pending contributions for all pending contributions at this point?
   * The code should cope with updating them to completed appropriately if we chose to.
   *
   * @param array $activityValues
   * @param array $subjectValues
   *
   * @throws \CRM_Core_Exception
   */
  private function addActivity(
    array $activityValues,
    array $subjectValues,
  ): void {
    $activityParams = $activityValues + [
      'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Contribution'),
      'is_test' => $this->isTest(),
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Scheduled'),
      'skipRecentView' => TRUE,
      'campaign_id' => $this->getCampaignID(),
    ];
    // Amount and source could exceed max length of subject column.
    $activityParams['subject'] = CRM_Utils_String::ellipsify($subjectValues['amount'] . ' - ' . $subjectValues['description'], 255);
    CRM_Activity_BAO_Activity::create($activityParams);
  }

  /**
   * Create the recurring contribution record.
   *
   * @param array $params
   * @param array $recurParams
   *
   * @return int|null
   */
  private function processRecurringContribution(array $params, array $recurParams) {

    $recurParams['amount'] = $params['amount'] ?? NULL;
    $recurParams['auto_renew'] = $params['auto_renew'] ?? NULL;
    $recurParams['frequency_unit'] = $params['frequency_unit'] ?? NULL;
    $recurParams['frequency_interval'] = $params['frequency_interval'] ?? NULL;
    $recurParams['installments'] = $params['installments'] ?? NULL;
    $recurParams['currency'] = $this->getCurrency();
    $recurParams['payment_instrument_id'] = $this->getPaymentInstrumentID();

    // CRM-14354: For an auto-renewing membership with an additional contribution,
    // if separate payments is not enabled, make sure only the membership fee recurs
    if ($this->isSeparateMembershipPayment()
      && isset($params['selectMembership'])
      && $this->getContributionPageValue('is_allow_other_amount')
      // CRM-16331
      && !empty($this->order->getMembershipTotalAmount())
    ) {
      $recurParams['amount'] = $this->order->getMembershipTotalAmount();
    }

    $recurParams['is_test'] = 0;
    if (($this->_action & CRM_Core_Action::PREVIEW) ||
      (isset($this->_mode) && ($this->_mode === 'test'))
    ) {
      $recurParams['is_test'] = 1;
    }

    $recurParams['start_date'] = $recurParams['create_date'] = $recurParams['modified_date'] = date('YmdHis');
    if (!empty($params['receive_date'])) {
      $recurParams['start_date'] = date('YmdHis', strtotime($params['receive_date']));
    }
    $recurParams['invoice_id'] = $params['invoiceID'] ?? NULL;
    $recurParams['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionRecur', 'contribution_status_id', 'Pending');
    $recurParams['payment_processor_id'] = $this->getPaymentProcessorID();
    $recurParams['is_email_receipt'] = $this->isEmailReceipt();
    // We set trxn_id=invoiceID specifically for paypal IPN. It is reset this when paypal sends us the real trxn id, CRM-2991
    $recurParams['processor_id'] = $recurParams['trxn_id'] = ($params['trxn_id'] ?? $params['invoiceID']);

    $recurParams['campaign_id'] = $this->getCampaignID();
    $recurring = CRM_Contribute_BAO_ContributionRecur::add($recurParams);
    $this->_params['contributionRecurID'] = $recurring->id;

    return $recurring->id;
  }

  /**
   * Function used to save pcp / soft credit entry.
   *
   * This is used by contribution and also event pcps
   *
   * @param array $params
   */
  private function formatSoftCreditParams(&$params) {
    $form = $this;
    $softParams = [];

    if (!empty($form->_values['honoree_profile_id']) && !empty($params['soft_credit_type_id'])) {
      $honorId = NULL;

      // @todo fix use of deprecated function.
      $contributionSoftParams['soft_credit_type_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', 'pcp');
      //check if there is any duplicate contact
      // honoree should never be the donor
      $exceptKeys = [
        'contactID' => 0,
        'onbehalf_contact_id' => 0,
      ];
      $except = array_values(array_intersect_key($params, $exceptKeys));
      $ids = CRM_Contact_BAO_Contact::getDuplicateContacts(
        $params['honor'],
        CRM_Core_BAO_UFGroup::getContactType($form->_values['honoree_profile_id']),
        'Unsupervised',
        $except,
        FALSE
      );
      if (count($ids)) {
        $honorId = $ids[0] ?? NULL;
      }

      $values = $this->getSubmittedValue('honor');
      $honorId = CRM_Contact_BAO_Contact::createProfileContact(
        $values, [],
        $honorId, NULL,
        $form->_values['honoree_profile_id']
      );
      $softParams[] = [
        'contact_id' => $honorId,
        'soft_credit_type_id' => $params['soft_credit_type_id'],
      ];
    }
    elseif ($this->getSubmittedValue('soft_credit_contact_id')) {
      //build soft credit params
      // Is this actually reachable or is it just left over from when this code was shared with the back office?
      foreach ($this->getSubmittedValue('soft_credit_contact_id') as $key => $val) {
        if ($val && $params['soft_credit_amount'][$key]) {
          $softParams[$key]['contact_id'] = $val;
          $softParams[$key]['amount'] = CRM_Utils_Rule::cleanMoney($params['soft_credit_amount'][$key]);
          $softParams[$key]['soft_credit_type_id'] = $params['soft_credit_type'][$key];
        }
      }
    }

    $params['soft_credit'] = $softParams;
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
    $isNotCurrentEmployer = FALSE;
    $dupeIDs = [];
    $orgID = NULL;
    if (!empty($behalfOrganization['organization_id'])) {
      $orgID = $behalfOrganization['organization_id'];
      unset($behalfOrganization['organization_id']);
    }
    // create employer relationship with $contactID only when new organization is there
    // else retain the existing relationship
    else {
      $isNotCurrentEmployer = TRUE;
    }

    if (!$orgID) {
      // check if matching organization contact exists
      $dupeIDs = CRM_Contact_BAO_Contact::getDuplicateContacts($behalfOrganization, 'Organization', 'Unsupervised', [], FALSE);

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
    $behalfOrganization['contact_type'] = 'Organization';
    $orgID = CRM_Contact_BAO_Contact::createProfileContact($behalfOrganization, $fields, $orgID,
      NULL, NULL, 'Organization'
    );
    // create relationship
    if ($isNotCurrentEmployer) {
      try {
        \Civi\Api4\Relationship::create(FALSE)
          ->addValue('contact_id_a', $contactID)
          ->addValue('contact_id_b', $orgID)
          ->addValue('relationship_type_id', CRM_Contact_BAO_RelationshipType::getEmployeeRelationshipTypeID())
          ->addValue('is_permission_a_b:name', 'View and update')
          ->execute();
      }
      catch (CRM_Core_Exception $e) {
        // Ignore if duplicate relationship.
        if ($e->getMessage() !== 'Duplicate Relationship') {
          throw $e;
        }
      }
    }

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

      //CRM-19172: Create CMS user for individual on whose behalf organization is doing contribution
      $params['onbehalf_contact_id'] = $contactID;

      //make this employee of relationship as current
      //employer / employee relationship,  CRM-3532
      if ($isNotCurrentEmployer &&
        ($orgID != CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactID, 'employer_id'))
      ) {
        $isNotCurrentEmployer = FALSE;
      }

      if (!$isNotCurrentEmployer && $orgID) {
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
   * Function used to se pcp related defaults / params.
   *
   * @param array $params
   *
   * @return array
   */
  private function processPcp($params): array {
    $params['pcp_made_through_id'] = $this->getPcpID();

    $this->assign('pcpBlock', FALSE);
    // display honor roll data only if it's enabled for the PCP page
    if (!empty($this->_pcpInfo['is_honor_roll'])) {
      $this->assign('pcpBlock', TRUE);
      if (!empty($params['pcp_display_in_roll']) && empty($params['pcp_roll_nickname'])) {
        $params['pcp_roll_nickname'] = ts('Anonymous');
        $params['pcp_is_anonymous'] = 1;
      }
      else {
        $params['pcp_is_anonymous'] = 0;
      }
      foreach ([
        'pcp_display_in_roll',
        'pcp_is_anonymous',
        'pcp_roll_nickname',
        'pcp_personal_note',
      ] as $val) {
        if (!empty($params[$val])) {
          $this->assign($val, $params[$val]);
        }
      }
    }

    return $params;
  }

  /**
   * Process the pcp associated with a contribution.
   *
   * @param array $contribution
   *
   * @throws \CRM_Core_Exception
   */
  private function savePCP(array $contribution): void {
    $pcp = $this->getSubmittedPcpValues();
    $pcpId = CRM_Contribute_BAO_ContributionSoft::getSoftCreditIds($contribution['id'], TRUE);

    $softParams = [];
    $softParams['id'] = $pcpId ?: NULL;
    $softParams['contribution_id'] = $contribution['id'];
    $softParams['pcp_id'] = $pcp['pcp_made_through_id'];
    $softParams['contact_id'] = CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCP',
      $pcp['pcp_made_through_id'], 'contact_id'
    );
    $softParams['currency'] = $this->getCurrency();
    $softParams['amount'] = $contribution['total_amount'];
    $softParams['pcp_display_in_roll'] = $pcp['pcp_display_in_roll'] ?? NULL;
    $softParams['pcp_roll_nickname'] = $pcp['pcp_roll_nickname'] ?? NULL;
    $softParams['pcp_personal_note'] = $pcp['pcp_personal_note'] ?? NULL;
    $softParams['soft_credit_type_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', 'pcp');
    CRM_Contribute_BAO_ContributionSoft::add($softParams);
  }

  /**
   * Process membership.
   *
   * @param array $membershipParams
   * @param int $contactID
   */
  protected function processMembership($membershipParams, $contactID): void {
    $this->_values['membership_name'] = $membershipType['name'] ?? NULL;

    if (!empty($this->_params['membership_source'])) {
      $membershipParams['contribution_source'] = $this->_params['membership_source'];
    }

    $this->postProcessMembership($membershipParams, $contactID);

    $this->set('membershipTypeID', $membershipParams['selectMembership']);
  }

  /**
   * Process the Memberships.
   *
   * @param array $membershipParams
   *   Array of membership fields.
   * @param int $contactID
   *   Contact id.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  protected function postProcessMembership(
    $membershipParams, $contactID) {
    $membershipContribution = NULL;
    $errors = $paymentResults = [];

    $isRecurForFirstTransaction = (bool) ($this->_params['is_recur'] ?? $membershipParams['is_recur'] ?? NULL) && (!$this->isSeparatePaymentSelected() || empty($membershipParams['auto_renew']));

    $totalAmount = $membershipParams['amount'];
    $this->_params['createdMembershipIDs'] = [];
    $lineItems = $this->getLineItems();
    foreach ($lineItems as $index => $lineItem) {
      $membershipTypeID = $lineItem['membership_type_id'];
      if (!$membershipTypeID) {
        continue;
      }
      if (!$this->getExistingContributionID() && !$this->getExistingMembership($membershipTypeID)) {
        // Create membership & hack line items to connect to it
        // NEW Membership, set up as pending and once Contribution is completed, the membership can be finished processing.
        $memParams = [
          'campaign_id' => $this->getCampaignID(),
          'is_test' => $this->isTest(),
          'contact_id' => $contactID,
          'membership_type_id' => $membershipTypeID,
          'membership_activity_status' => 'Scheduled',
          'status_id:name' => 'Pending',
          'source' => $this->getMembershipSource(),
          'is_pay_later' => $this->isPayLater(),
          // This kinda has a carve out for membership I think but custom field handling
          // is the only thing keeping us from the order api switch now
          'custom' => $this->getCustomFieldsForMembership($contactID),
        ];

        $membership = Membership::create(FALSE)
          ->setValues($memParams)
          ->execute()
          ->first();
        CRM_Core_BAO_CustomValueTable::postProcess($this->_params, 'civicrm_membership', $membership['id'], 'Membership');
        $this->_params['createdMembershipIDs'][] = $membership['id'];
        $this->_params['membershipID'] = $membership['id'];
        $lineItems[$index]['entity_id'] = $membership['id'];
      }
      // Overwrite the array with our augmented version.
      $this->setLineItems($lineItems);
      $this->lineItems = $lineItems;
    }

    if ($this->isSeparatePaymentSelected()) {
      // If we have 2 transactions only one can use the invoice id.
      $membershipParams['invoiceID'] .= '-2';
      $membershipParams['total_amount'] = $totalAmount;
    }

    $paymentResult = $this->processConfirm(
      $membershipParams,
      $contactID,
      $this->getFinancialTypeID(),
      $isRecurForFirstTransaction,
      $this->isSeparatePaymentSelected()
    );
    // Ideally we would have created the contribution recur first or with (looking at you Order api)
    // the membership & contribution create but in this case we need to add it after.
    if (!empty($paymentResult['contribution']->contribution_recur_id)) {
      foreach ($this->getMainContributionLineItems() as $lineItem) {
        if (!empty($lineItem['membership_type_id']) && $lineItem['entity_id']) {
          Membership::update(FALSE)
            ->setValues([
              'contribution_recur_id' => $paymentResult['contribution']->contribution_recur_id,
              // Ouch! It recalculates from Pending to Completed in the BAO
              // without this! Fix in BAO needed but for now...
              'skipStatusCal' => TRUE,
            ])
            ->addWhere('id', '=', $lineItem['entity_id'])->execute();
        }
      }
    }
    if (!empty($paymentResult['contribution'])) {
      $paymentResults[] = ['contribution_id' => $paymentResult['contribution']->id, 'result' => $paymentResult];
      $this->postProcessPremium($paymentResult['contribution']);
      //note that this will be over-written if we are using a separate membership transaction. Otherwise there is only one
      $membershipContribution = $paymentResult['contribution'];
      // Save the contribution ID so that I can be used in email receipts
      // For example, if you need to generate a tax receipt for the donation only.
      $membershipContributionID = $this->_values['contribution_other_id'] = $membershipContribution->id;
    }

    if ($this->isSeparatePaymentSelected()) {
      try {
        if (empty($this->_params['auto_renew']) && !empty($membershipParams['is_recur'])) {
          unset($membershipParams['is_recur']);
        }
        [$membershipContribution, $secondPaymentResult] = $this->processSecondaryFinancialTransaction($contactID, array_merge($membershipParams));
        $paymentResults[] = ['contribution_id' => $membershipContribution->id, 'result' => $secondPaymentResult];
        $totalAmount = $membershipContribution->total_amount;
        $membershipContributionID = $membershipContribution->id;
        // Ideally we would have created the contribution recur first or with (looking at you Order api)
        // the membership & contribution create but in this case we need to add it after.
        if (!empty($membershipContribution->contribution_recur_id)) {
          foreach ($this->getSecondaryMembershipContributionLineItems() as $lineItem) {
            if (!empty($lineItem['membership_type_id']) && $lineItem['entity_id']) {
              Membership::update(FALSE)
                ->setValues(['contribution_recur_id' => $membershipContribution->contribution_recur_id])
                ->addWhere('id', '=', $lineItem['entity_id'])->execute();
            }
          }
        }
      }
      catch (CRM_Core_Exception $e) {
        $errors[2] = $e->getMessage();
        $membershipContribution = $membershipContributionID = NULL;
      }
    }

    $membership = NULL;

    if (!empty($errors)) {
      $message = $this->compileErrorMessage($errors);
      throw new CRM_Core_Exception($message);
    }

    if (isset($membershipContributionID)) {
      $this->_values['contribution_id'] = $membershipContributionID;
    }

    if (!$this->isPayLater() && $this->_paymentProcessor) {
      // the is_monetary concept probably should be deprecated as it can be calculated from
      // the existence of 'amount' & seems fragile.
      if ($this->_values['is_monetary'] && $this->_amount > 0.0) {
        // call postProcess hook before leaving
        $this->postProcessHook();
      }

      $payment = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
      // The contribution_other_id is effectively the ID for the only contribution or the non-membership contribution.
      // Since we have called the membership contribution (in a 2 contribution scenario) this is out
      // primary-contribution compared to that - but let's face it - it's all just too hard & confusing at the moment!
      $paymentParams = array_merge($this->_params, ['contributionID' => $this->_values['contribution_other_id']]);

      // If this is a single membership-related contribution, it won't have
      // be performed yet, so do it now.
      if (!$this->isSeparatePaymentSelected()) {
        $paymentParams['amount'] = $this->getMainContributionAmount();
        $paymentParams += $this->getBasePaymentParams();
        $paymentActionResult = $payment->doPayment($paymentParams);
        $paymentResults[] = ['contribution_id' => $paymentResult['contribution']->id, 'result' => $paymentActionResult];
      }
      // Do not send an email if Recurring transaction is done via Direct Mode
      // Email will we sent when the IPN is received.
      foreach ($paymentResults as $result) {
        //CRM-18211: Fix situation where second contribution doesn't exist because it is optional.
        if ($result['contribution_id']) {
          if (($result['result']['payment_status_id'] ?? NULL) == 1) {
            try {
              civicrm_api3('contribution', 'completetransaction', [
                'id' => $result['contribution_id'],
                'trxn_id' => $result['result']['trxn_id'] ?? NULL,
                'payment_processor_id' => $this->getPaymentProcessorID(),
                'is_transactional' => FALSE,
                'fee_amount' => $result['result']['fee_amount'] ?? NULL,
                'receive_date' => $result['result']['receive_date'] ?? NULL,
                'card_type_id' => $paymentParams['card_type_id'] ?? NULL,
                'pan_truncation' => $paymentParams['pan_truncation'] ?? NULL,
              ]);
            }
            catch (CRM_Core_Exception $e) {
              if ($e->getErrorCode() != 'contribution_completed') {
                \Civi::log()->error('CRM_Contribute_Form_Contribution_Confirm::completeTransaction CRM_Core_Exception: ' . $e->getMessage());
                throw new CRM_Core_Exception('Failed to update contribution in database');
              }
            }
          }
        }
      }
      return;
    }

    $emailValues = array_merge($membershipParams, $this->_values);
    $emailValues['useForMember'] = !empty($this->_useForMember);
    $emailValues['membership_id'] = $this->_params['membershipID'];

    // Finally send an email receipt for pay-later scenario (although it might sometimes be caught above!)
    if ($totalAmount == 0) {
      // This feels like a bizarre hack as the variable name doesn't seem to be directly connected to it's use in the template.
      $emailValues['useForMember'] = 0;
      $emailValues['amount'] = 0;

      //CRM-18071, where on selecting $0 free membership payment section got hidden and
      // also it reset any payment processor selection result into pending free membership
      // so its a kind of hack to complete free membership at this point since there is no $form->_paymentProcessor info
      if (!empty($membershipContribution) && !is_a($membershipContribution, 'CRM_Core_Error')) {
        try {
          CRM_Contribute_BAO_Contribution::completeOrder(
            ['payment_processor_id' => $this->getPaymentProcessorID()],
            $membershipContribution->contribution_recur_id,
            $membershipContribution->id,
            NULL);
        }
        catch (CRM_Core_Exception $e) {
          if ($e->getErrorCode() != 'contribution_completed') {
            \Civi::log()->error('CRM_Contribute_Form_Contribution_Confirm::completeTransaction CRM_Core_Exception: ' . $e->getMessage());
            throw new CRM_Core_Exception('Failed to update contribution in database');
          }
        }
      }
      // return as completeTransaction() already sends the receipt mail.
      return;
    }

    CRM_Contribute_BAO_ContributionPage::sendMail($contactID,
      $emailValues,
      $this->isTest()
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
   * @param array $tempParams
   *
   * @return array []
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  private function processSecondaryFinancialTransaction($contactID, $tempParams): array {
    $membershipDetails = $this->getFirstSelectedMembershipType();
    $financialTypeID = $membershipDetails['financial_type_id'] ?? NULL;
    $minimumFee = $membershipDetails['minimum_fee'] ?? 0;
    $tempParams['amount'] = $minimumFee;
    $tempParams['invoiceID'] = bin2hex(random_bytes(16));
    $isRecur = $tempParams['is_recur'] ?? NULL;

    //assign receive date when separate membership payment
    //and contribution amount not selected.
    if ($this->_amount == 0) {
      $now = date('YmdHis');
      $this->_params['receive_date'] = $now;
      $this->set('params', $this->_params);
    }

    $contributionParams = [
      'contact_id' => $contactID,
      'line_item' => [$this->getPriceSetID() => $this->getSecondaryMembershipContributionLineItems()],
      'is_test' => $this->isTest(),
      'campaign_id' => $this->getCampaignID(),
      'contribution_page_id' => $this->_id,
      'source' => $tempParams['source'] ?? $this->getSource(),
      'financial_type_id' => $financialTypeID,
    ];

    $transaction = new CRM_Core_Transaction();
    $membershipContribution = $this->processFormContribution(
      $tempParams,
      $contributionParams,
      $isRecur,
      TRUE
    );
    $transaction->commit();

    $result = [];

    // We're not processing the line item here because we are processing a membership.
    // To ensure processing of the correct parameters, replace relevant parameters
    // in $tempParams with those in $membershipContribution.
    $tempParams['amount_level'] = $membershipContribution->amount_level;
    $tempParams['total_amount'] = $membershipContribution->total_amount;
    $tempParams['tax_amount'] = $membershipContribution->tax_amount;
    $tempParams['contactID'] = $membershipContribution->contact_id;
    $tempParams['financialTypeID'] = $membershipContribution->financial_type_id;
    $tempParams['invoiceID'] = $membershipContribution->invoice_id;
    $tempParams['trxn_id'] = $membershipContribution->trxn_id;
    $tempParams['contributionID'] = $membershipContribution->id;

    if ($this->_values['is_monetary'] && !$this->isPayLater() && $minimumFee > 0.0) {
      $payment = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
      $tempParams += $this->getBasePaymentParams();
      $result = $payment->doPayment($tempParams);
      $this->set('membership_trx_id', $result['trxn_id']);
      $this->assign('membership_trx_id', $result['trxn_id']);
    }

    return [$membershipContribution, $result];
  }

  /**
   * Are we going to do 2 financial transactions.
   *
   * Ie the membership block supports a separate transactions AND the contribution form has been configured for a
   * contribution
   * transaction AND a membership transaction AND the payment processor supports double financial transactions (ie. NOT
   * doTransferCheckout style)
   *
   * @todo - this is confusing - does isSeparateMembershipPayment need to
   * check both conditions, making this redundant, or are there 2 legit
   * variations here?
   *
   * @return bool
   */
  protected function isSeparateMembershipTransaction(): bool {
    return $this->isSeparateMembershipPayment() && $this->isFormSupportsNonMembershipContributions();
  }

  /**
   * This function sets the fields.
   *
   * The results of it are likely unused.
   *
   * - $this->_params['amount_level']
   * - $this->_params['selectMembership']
   * And under certain circumstances sets
   * $this->_params['amount'] = null;
   *
   * @param int $priceSetID
   */
  public function setFormAmountFields($priceSetID) {
    $priceField = new CRM_Price_DAO_PriceField();
    $priceField->price_set_id = $priceSetID;
    $priceField->orderBy('weight');
    $priceField->find();
    $paramWeDoNotUnderstand = NULL;

    while ($priceField->fetch()) {
      if ($priceField->name == "contribution_amount") {
        $paramWeDoNotUnderstand = $priceField->id;
      }
      if ($this->isQuickConfig() && !empty($this->_params["price_{$priceField->id}"])) {
        if ($this->_values['fee'][$priceField->id]['html_type'] != 'Text') {
          // @todo - stop setting amount level in this function - use $this->order->getAmountLevel()
          // We expect this to be ignored.
          $this->_params['amount_level'] = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue',
            $this->_params["price_{$priceField->id}"], 'label');
        }
        if ($priceField->name == "membership_amount") {
          // We expect this to be ignored.
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
        !empty($this->_membershipBlock['is_separate_payment'])
        && !empty($this->_values['fee'][$priceField->id])
        && ($this->_values['fee'][$priceField->id]['name'] == "other_amount")
        && ($this->_params["price_{$paramWeDoNotUnderstand}"] ?? NULL) < 1
        && empty($this->_params["price_{$priceField->id}"])
      ) {
        // We expect this to be ignored.
        $this->_params['amount'] = NULL;
      }

      // Fix for CRM-14375 - If we are using separate payments and "no
      // thank you" is selected for the additional contribution, set
      // contribution amount to be null, so that it will not show
      // contribution amount same as membership amount.
      //@todo - merge with section above
      if (!empty($this->_membershipBlock['is_separate_payment'])
        && !empty($this->_values['fee'][$priceField->id])
        && ($this->_values['fee'][$priceField->id]['name'] ?? NULL) == 'contribution_amount'
        && ($this->_params["price_{$priceField->id}"] ?? NULL) == '-1'
      ) {
        // We expect this to be ignored.
        $this->_params['amount'] = NULL;
      }
    }
  }

  /**
   * Submit function.
   *
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public static function submit($params) {
    $form = new CRM_Contribute_Form_Contribution_Confirm();
    $form->_id = $params['id'];

    $form->loadContributionPageValues($form->_values);
    //this way the mocked up controller ignores the session stuff
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $form->controller = new CRM_Contribute_Controller_Contribution();
    $params['invoiceID'] = bin2hex(random_bytes(16));

    $paramsProcessedForForm = $form->_params = self::getFormParams($params['id'], $params);

    $form->order = new CRM_Financial_BAO_Order();
    $form->order->setPriceSetIDByContributionPageID($params['id']);
    $form->order->setPriceSelectionFromUnfilteredInput($params);
    if (isset($params['amount']) && !$form->isSeparateMembershipPayment()) {
      // @todo deprecate receiving amount, calculate on the form.
      $form->order->setOverrideTotalAmount((float) $params['amount']);
    }
    // hack these in for test support.
    $form->_fields['billing_first_name'] = 1;
    $form->_fields['billing_last_name'] = 1;
    // CRM-18854 - Set form values to allow pledge to be created for api test.
    $form->setPledgeID($params['pledge_id'] ?? NULL);
    if (!empty($params['pledge_block_id'])) {
      $form->_values['pledge_block_id'] = $params['pledge_block_id'];
      $pledgeBlock = CRM_Pledge_BAO_PledgeBlock::getPledgeBlock($params['id']);
      $form->_values['max_reminders'] = $pledgeBlock['max_reminders'];
      $form->_values['initial_reminder_day'] = $form->getPledgeBlockValue('initial_reminder_day');
      $form->_values['additional_reminder_day'] = $pledgeBlock['additional_reminder_day'];
    }
    $priceSetID = $form->_params['priceSetId'] = $paramsProcessedForForm['price_set_id'];
    $priceFields = CRM_Price_BAO_PriceSet::getSetDetail($priceSetID);
    $priceSetFields = reset($priceFields);
    $form->_values['fee'] = $priceSetFields['fields'];
    $form->_priceSetId = $priceSetID;
    $form->setFormAmountFields($priceSetID);
    $capabilities = [];
    if ($form->_mode) {
      $capabilities[] = (ucfirst($form->_mode) . 'Mode');
    }
    $form->_paymentProcessors = CRM_Financial_BAO_PaymentProcessor::getPaymentProcessors($capabilities);
    $form->_params['payment_processor_id'] = $params['payment_processor_id'] ?? 0;
    if ($form->_params['payment_processor_id'] !== '') {
      // It can be blank with a $0 transaction - then no processor needs to be selected
      $form->_paymentProcessor = $form->_paymentProcessors[$form->_params['payment_processor_id']];
    }

    $priceFields = $priceFields[$priceSetID]['fields'];
    $membershipPriceFieldIDs = [];
    foreach ($form->order->getLineItems() as $lineItem) {
      if (!empty($lineItem['membership_type_id'])) {
        $membershipPriceFieldIDs['id'] = $priceSetID;
        $membershipPriceFieldIDs[] = $lineItem['price_field_value_id'];
      }
    }
    $form->set('memberPriceFieldIDS', $membershipPriceFieldIDs);
    $form->setRecurringMembershipParams();
    $form->processFormSubmission($params['contact_id'] ?? NULL);
  }

  /**
   * Get the contribution ID.
   *
   * @api This function will not change in a minor release and is supported for
   * use outside of core. This annotation / external support for properties
   * is only given where there is specific test cover.
   *
   * @return int|null
   */
  public function getContributionID(): ?int {
    return $this->_ccid ?: $this->_contributionID;
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
   * @throws CRM_Core_Exception
   */
  public static function getFormParams($id, array $params) {
    if (!isset($params['is_pay_later'])) {
      if (!empty($params['payment_processor_id'])) {
        $params['is_pay_later'] = 0;
      }
      elseif (($params['amount'] ?? 0) !== 0) {
        $params['is_pay_later'] = civicrm_api3('contribution_page', 'getvalue', [
          'id' => $id,
          'return' => 'is_pay_later',
        ]);
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
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function processFormSubmission($contactID) {
    $this->_params['payment_processor_id'] = $this->getPaymentProcessorID();
    $this->_params['is_pay_later'] = $this->isPayLater();

    if ($this->getContributionID()) {
      $this->_params['contribution_id'] = $this->getContributionID();
    }
    //Set email-bltID if pre/post profile contains an email.
    if ($this->_emailExists == TRUE) {
      foreach ($this->_params as $key => $val) {
        if (substr($key, 0, 6) == 'email-' && empty($this->_params["email-{$this->_bltID}"])) {
          $this->_params["email-{$this->_bltID}"] = $this->_params[$key];
        }
      }
    }

    // CRM-18854
    if (!empty($this->_params['is_pledge']) && !$this->getPledgeID() && $this->getContributionPageValue('adjust_recur_start_date')) {
      $pledgeBlock = CRM_Pledge_BAO_PledgeBlock::getPledgeBlock($this->_id);
      if (!empty($this->_params['start_date']) || empty($pledgeBlock['is_pledge_start_date_visible'])
          || empty($pledgeBlock['is_pledge_start_date_editable'])) {
        $pledgeStartDate = $this->_params['start_date'] ?? NULL;
        $this->_params['receive_date'] = CRM_Pledge_BAO_Pledge::getPledgeStartDate($pledgeStartDate, $pledgeBlock);
        $recurParams = CRM_Pledge_BAO_Pledge::buildRecurParams($this->_params);
        $this->_params = array_merge($this->_params, $recurParams);
      }
    }

    $membershipParams = $params = $this->_params;
    if (!empty($params['image_URL'])) {
      CRM_Contact_BAO_Contact::processImageParams($params);
    }

    $fields = ['email-Primary' => 1];

    // get the add to groups
    $addToGroups = [];

    // now set the values for the billing location.
    foreach ($this->_fields as $name => $value) {
      $fields[$name] = 1;

      // get the add to groups for uf fields
      if (!empty($value['add_to_group_id'])) {
        $addToGroups[$value['add_to_group_id']] = $value['add_to_group_id'];
      }
    }

    $fields = $this->formatParamsForPaymentProcessor($fields);

    // billing email address
    $fields["email-{$this->_bltID}"] = 1;

    // if onbehalf-of-organization contribution, take out
    // organization params in a separate variable, to make sure
    // normal behavior is continued. And use that variable to
    // process on-behalf-of functionality.
    if (!empty($this->_values['onbehalf_profile_id']) && empty($this->getExistingContributionID())) {
      $behalfOrganization = [];
      $orgFields = ['organization_name', 'organization_id', 'org_option'];
      foreach ($orgFields as $organizationField) {
        if (array_key_exists($organizationField, $params)) {
          $behalfOrganization[$organizationField] = $params[$organizationField];
          unset($params[$organizationField]);
        }
      }

      if (is_array($params['onbehalf']) && !empty($params['onbehalf'])) {
        foreach ($params['onbehalf'] as $onBehalfField => $values) {
          if (str_contains($onBehalfField, 'custom_')) {
            $behalfOrganization[$onBehalfField] = $values;
          }
          elseif (!str_contains($onBehalfField, '-')) {
            $behalfOrganization[$onBehalfField] = $values;
            $this->_params[$onBehalfField] = $values;
          }
        }
      }

      if (array_key_exists('onbehalf_location', $params) && is_array($params['onbehalf_location'])) {
        foreach ($params['onbehalf_location'] as $block => $vals) {
          //fix for custom data (of type checkbox, multi-select)
          if (str_starts_with($block, 'custom_')) {
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

      $contactID = CRM_Contact_BAO_Contact::getFirstDuplicateContact($dupeParams, 'Individual', 'Unsupervised', [], FALSE);

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
    $subscriptionEmail = ['email' => $params['email-Primary'] ?? NULL];
    if (!$subscriptionEmail['email']) {
      $subscriptionEmail['email'] = $params["email-{$this->_bltID}"] ?? NULL;
    }
    // subscribing contact to groups
    if (!empty($subscribeGroupIds) && $subscriptionEmail['email']) {
      CRM_Mailing_Event_BAO_MailingEventSubscribe::commonSubscribe($subscribeGroupIds, $subscriptionEmail, $contactID);
    }

    // If onbehalf-of-organization contribution / signup, add organization
    // and it's location.
    if (isset($this->_values['onbehalf_profile_id']) &&
      isset($behalfOrganization['organization_name']) &&
      ($this->_values['is_for_organization'] == 2 ||
        !empty($this->_params['is_for_organization'])
      )
    ) {
      $ufFields = [];
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
    if ($this->isMembershipSelected()) {
      $this->doMembershipProcessing($contactID, $membershipParams);
    }
    else {
      // at this point we've created a contact and stored its address etc
      // all the payment processors expect the name and address to be in the
      // so we copy stuff over to first_name etc.
      $paymentParams = $this->_params;

      if (!empty($paymentParams['onbehalf']) &&
        is_array($paymentParams['onbehalf'])
      ) {
        foreach ($paymentParams['onbehalf'] as $key => $value) {
          if (str_contains($key, 'custom_')) {
            $this->_params[$key] = $value;
          }
        }
      }
      $paymentParams['amount'] = $this->getMainContributionAmount();
      $result = $this->processConfirm($paymentParams,
        $contactID,
        $this->getFinancialTypeID(),
        !empty($paymentParams['is_recur']),
        TRUE
      );

      if (empty($result['is_payment_failure'])) {
        // @todo move premium processing to complete transaction if it truly is an 'after' action.
        $this->postProcessPremium($result['contribution']);
      }
      if (!empty($result['contribution'])) {
        // It seems this line is hit when there is a zero dollar transaction & in tests, not sure when else.
        if (($result['payment_status_id'] ?? NULL) == 1) {
          try {
            civicrm_api3('contribution', 'completetransaction', [
              'id' => $result['contribution']->id,
              'trxn_id' => $result['trxn_id'] ?? NULL,
              'payment_processor_id' => $this->getPaymentProcessorID(),
              'is_transactional' => FALSE,
              'fee_amount' => $result['fee_amount'] ?? NULL,
              'receive_date' => $result['receive_date'] ?? NULL,
              'card_type_id' => $paymentParams['card_type_id'] ?? NULL,
              'pan_truncation' => $paymentParams['pan_truncation'] ?? NULL,
            ]);
          }
          catch (CRM_Core_Exception $e) {
            if ($e->getErrorCode() != 'contribution_completed') {
              \Civi::log()->error('CRM_Contribute_Form_Contribution_Confirm::completeTransaction CRM_Core_Exception: ' . $e->getMessage());
              throw new CRM_Core_Exception('Failed to update contribution in database');
            }
          }
        }
      }
      return $result;
    }
  }

  /**
   * Return True/False if we have a membership selected on the contribution page
   *
   * @return bool
   */
  private function isMembershipSelected(): bool {
    return !empty($this->getMembershipLineItems());
  }

  /**
   * Extract the selected memberships from a priceSet
   *
   * @param array $membershipParams
   *
   * @return array
   */
  private function getMembershipParamsFromPriceSet($membershipParams) {
    $priceFieldIds = $this->get('memberPriceFieldIDS');
    if (empty($priceFieldIds)) {
      return $membershipParams;
    }
    $membershipParams['financial_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $priceFieldIds['id'], 'financial_type_id');
    unset($priceFieldIds['id']);
    $membershipTypeIds = [];
    $membershipTypeTerms = [];
    foreach ($priceFieldIds as $priceFieldId) {
      $membershipTypeId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $priceFieldId, 'membership_type_id');
      if ($membershipTypeId) {
        $membershipTypeIds[] = $membershipTypeId;
        $term = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $priceFieldId, 'membership_num_terms') ?: 1;
        $membershipTypeTerms[$membershipTypeId] = ($term > 1) ? $term : 1;
      }
    }
    $membershipParams['selectMembership'] = $membershipTypeIds;
    $membershipParams['types_terms'] = $membershipTypeTerms;
    return $membershipParams;
  }

  /**
   * Membership processing section.
   *
   * This is in a separate function as part of a move towards refactoring.
   *
   * @param int $contactID
   * @param array $membershipParams
   */
  protected function doMembershipProcessing($contactID, $membershipParams) {
    if (!$this->isMembershipPriceSet()) {
      $this->set('membershipTypeID', $this->_params['selectMembership']);
    }

    $membershipParams['is_test'] = $this->isTest();
    $membershipParams['is_pay_later'] = $this->isPayLater();

    if (isset($this->_params['onbehalf_contact_id'])) {
      $membershipParams['onbehalf_contact_id'] = $this->_params['onbehalf_contact_id'];
    }
    //inherit campaign from contribution page.
    $membershipParams['campaign_id'] = $this->getCampaignID();

    // This could be set by a hook.
    if (!empty($this->_params['installments'])) {
      $membershipParams['installments'] = $this->_params['installments'];
    }
    // added new parameter for cms user contact id, needed to distinguish behaviour for on behalf of sign-ups
    if (isset($this->_params['related_contact'])) {
      $membershipParams['cms_contactID'] = $this->_params['related_contact'];
    }
    else {
      $membershipParams['cms_contactID'] = $contactID;
    }

    $membershipParams = $this->getMembershipParamsFromPriceSet($membershipParams);
    if ($this->isMembershipSelected()) {
      // CRM-12233.
      try {
        $membershipParams['amount'] = $this->getMainContributionAmount();
        $this->processMembership($membershipParams, $contactID);
      }
      catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
        CRM_Core_Session::singleton()->setStatus($e->getMessage());
        if ($this->getContributionID()) {
          CRM_Contribute_BAO_Contribution::failPayment($this->getContributionID(),
            $contactID, $e->getMessage());
        }
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/transact', "_qf_Main_display=true&qfKey={$this->_params['qfKey']}"));
      }
      catch (CRM_Core_Exception $e) {
        CRM_Core_Session::singleton()->setStatus($e->getMessage());
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/transact', "_qf_Main_display=true&qfKey=" . ($this->_params['qfKey'] ?? '')));
      }
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

  /**
   * Bounce the user back to retry when an error occurs.
   *
   * @param string $message
   */
  protected function bounceOnError($message): void {
    CRM_Core_Session::singleton()
      ->setStatus(ts('Payment Processor Error message') . ': ' . $message);
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/transact',
      '_qf_Main_display=true&qfKey=' . ($this->_params['qfKey'] ?? NULL)
    ));
  }

  /**
   * Process payment after confirmation.
   *
   * @param array $paymentParams
   *   Array with payment related key.
   *   value pairs
   * @param int $contactID
   *   Contact id.
   * @param int $financialTypeID
   *   Financial type id.
   * @param bool $isRecur
   * @param bool $isProcessPayment
   *
   * @return array
   *   associated array
   * @throws \CRM_Core_Exception
   */
  public function processConfirm(
    &$paymentParams,
    $contactID,
    int $financialTypeID,
    bool $isRecur,
    bool $isProcessPayment
  ): array {
    $form = $this;

    $financialType = new CRM_Financial_DAO_FinancialType();
    $financialType->id = $financialTypeID;
    $financialType->find(TRUE);

    $paymentParams = $this->getPaymentParams($financialTypeID, $contactID) + $paymentParams;

    //fix for CRM-16317
    if (empty($form->_params['receive_date'])) {
      $form->_params['receive_date'] = date('YmdHis');
    }
    if (!empty($form->_params['start_date'])) {
      $form->_params['start_date'] = date('YmdHis');
    }

    if (isset($paymentParams['contribution_source'])) {
      $paymentParams['source'] = $paymentParams['contribution_source'];
    }

    $contributionParams = [
      'id' => $paymentParams['contribution_id'] ?? NULL,
      'contact_id' => $contactID,
      'is_test' => $this->isTest(),
      'source' => $paymentParams['source'] ?? $this->getSource(),
      'financial_type_id' => $financialTypeID,
      'line_item' => [$this->getPriceSetID() => $this->getMainContributionLineItems()],
    ];

    // CRM-21200: Don't overwrite contribution details during 'Pay now' payment
    if (empty($form->_params['contribution_id'])) {
      $contributionParams['contribution_page_id'] = $form->_id;
      $contributionParams['campaign_id'] = $this->getCampaignID();
    }
    // In case of 'Pay now' payment, append the contribution source with new text 'Paid later via page ID: N.'
    else {
      // contribution.source only allows 255 characters so we are using ellipsify(...) to ensure it.
      $contributionParams['source'] = CRM_Utils_String::ellipsify(
        ts('Paid later via page ID: %1. %2', [
          1 => $form->_id,
          2 => $contributionParams['source'],
        ]),
        // eventually activity.description append price information to source text so keep it 220 to ensure string length doesn't exceed 255 characters.
        220
      );
    }

    $transaction = new CRM_Core_Transaction();
    $contribution = $this->processFormContribution(
      $paymentParams,
      $contributionParams,
      $isRecur,
      FALSE
    );
    $transaction->commit();
    // CRM-13074 - create the CMSUser after the transaction is completed as it
    // is not appropriate to delete a valid contribution if a user create problem occurs
    if (isset($this->_params['related_contact'])) {
      $contactID = $this->_params['related_contact'];
    }
    elseif (isset($this->_params['cms_contactID'])) {
      $contactID = $this->_params['cms_contactID'];
    }
    CRM_Contribute_BAO_Contribution_Utils::createCMSUser($this->_params,
      $contactID,
      'email-' . $form->_bltID
    );

    $paymentParams['item_name'] = $this->getSource();

    $paymentParams['qfKey'] = empty($paymentParams['qfKey']) ? $form->controller->_key : $paymentParams['qfKey'];
    if (!$isProcessPayment) {
      // We are not processing the line item here because we are processing a membership.
      // Do not continue with contribution processing in this function.
      return ['contribution' => $contribution];
    }

    $paymentParams['contributionID'] = $contribution->id;
    $paymentParams['contributionPageID'] = $contribution->contribution_page_id;

    if (!empty($form->_params['is_recur']) && $contribution->contribution_recur_id) {
      $paymentParams['contributionRecurID'] = $contribution->contribution_recur_id;
    }
    if (isset($paymentParams['contribution_source'])) {
      $form->_params['source'] = $paymentParams['contribution_source'];
    }

    $form->_values['contribution_id'] = $contribution->id;
    $form->_values['contribution_page_id'] = $contribution->contribution_page_id;

    if (!empty($form->_paymentProcessor)) {
      return $this->processConfirmPayment($contribution, $contactID, $paymentParams);
    }

    // Only pay later or unpaid should reach this point, although pay later likely does not & is handled via the
    // manual processor, so it's unclear what this set is for and whether the following send ever fires.
    $form->set('params', $form->_params);

    if ($form->_params['amount'] == 0) {
      // This is kind of a back-up for pay-later $0 transactions.
      // In other flows they pick up the manual processor & get dealt with above (I
      // think that might be better...).
      return [
        'payment_status_id' => 1,
        'contribution' => $contribution,
        'payment_processor_id' => $this->getPaymentProcessorID(),
      ];
    }
    throw new CRM_Core_Exception('code is unreachable, exception is for clarity for refactoring');
  }

  /**
   * This was extracted from processConfirm() as part of a refactoring process.
   * Signature/params subject to change!
   * This should probably throw the exception instead of catching it and putting it in an array
   * @internal
   *
   * @param \CRM_Contribute_DAO_Contribution $contribution
   * @param int $contactID
   * @param array $paymentParams
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function processConfirmPayment(\CRM_Contribute_DAO_Contribution $contribution, int $contactID, array $paymentParams): array {
    $form = $this;
    try {
      $payment = Civi\Payment\System::singleton()->getByProcessor($this->_paymentProcessor);
      if ($contribution->contribution_recur_id && $this->getPaymentProcessorObject()->supports('noReturnForRecurring')) {
        // We want to get rid of this & make it generic - eg. by making payment processing the last thing
        // and always calling it first.
        $form->postProcessHook();
      }
      $paymentParams += $this->getBasePaymentParams();
      $result = $payment->doPayment($paymentParams);
      $form->_params = array_merge($form->_params, $result);
      $form->assign('trxn_id', $result['trxn_id'] ?? '');
      $contribution->trxn_id = $result['trxn_id'] ?? $contribution->trxn_id ?? '';
      $contribution->payment_status_id = $result['payment_status_id'];
      $result['contribution'] = $contribution;
      if ($result['payment_status_id'] == CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending')
        && $payment->isSendReceiptForPending()) {
        CRM_Contribute_BAO_ContributionPage::sendMail($contactID, $form->_values, $contribution->is_test);
      }
      return $result;
    }
    catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
      // Clean up DB as appropriate.
      if (!empty($paymentParams['contributionID'])) {
        CRM_Contribute_BAO_Contribution::failPayment($paymentParams['contributionID'],
          $paymentParams['contactID'], $e->getMessage());
      }
      if (!empty($paymentParams['contributionRecurID'])) {
        CRM_Contribute_BAO_ContributionRecur::deleteRecurContribution($paymentParams['contributionRecurID']);
      }

      $result['is_payment_failure'] = TRUE;
      $result['error'] = $e;
      return $result;
    }
  }

  /**
   * Get the line items for the membership create call.
   *
   * This form follows a legacy code path - rather than creating the membership
   * and then creating the contribution / order with the right line items it
   * creates the contribution and then the membership & uses some old code in
   * the membership BAO to create the line items. In order to do this it needs to
   * separate out the line items into the separate membership create calls.
   * However, any non-membership lines need to be assigned to one & only one
   * of these line item splits.
   *
   * Where we have separate payments enabled then we should ignore any non-membership
   * line items as they will have been processed as part of the non-membership
   * contribution.
   *
   * @param int $membershipTypeID
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getLineItemsForMembershipCreate(int $membershipTypeID): array {
    $membershipTypeIDs = $this->getSelectedMembershipTypeIDs();
    $defaultMembershipTypeID = (int) reset($membershipTypeIDs);
    $lineItemSplit = [];
    foreach ($this->getLineItems() as $lineItem) {
      if (empty($lineItem['membership_type_id']) && $this->isSeparateMembershipPayment()) {
        continue;
      }
      $lineItemSplit[$lineItem['membership_type_id'] ?: $defaultMembershipTypeID]['price_field_value_' . $lineItem['price_field_value_id']] = $lineItem;
    }
    return $lineItemSplit[$membershipTypeID];
  }

  /**
   * Set the selected line items.
   *
   * This returns all selected line items, even if they will
   * be split to a secondary contribution.
   *
   * @api Supported for external use.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public function getLineItems(): array {
    if (!$this->isSubmitted()) {
      return parent::getLineItems();
    }
    if (!isset($this->lineItems)) {
      $this->lineItems = $this->order->getLineItems();

      if (!$this->getExistingContributionID()) {
        // If this is a renewal situation with a new contribution then augment
        // at this point with the membership entity_id.
        $assignedMemberships = [];
        foreach ($this->lineItems as &$lineItem) {
          if (!empty($lineItem['membership_type_id'])) {
            $existingMembership = $this->getExistingMembership($lineItem['membership_type_id']);
            if ($existingMembership && !in_array($existingMembership['id'], $assignedMemberships)) {
              $assignedMemberships[] = $existingMembership['id'];
              $lineItem['entity_id'] = $existingMembership['id'];
              $this->set('renewalMode', TRUE);
            }
          }
        }
      }
    }
    return $this->lineItems;
  }

}
