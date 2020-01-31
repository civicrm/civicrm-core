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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class generates form components for processing Event.
 */
class CRM_Event_Form_Registration_Register extends CRM_Event_Form_Registration {

  /**
   * The fields involved in this page.
   *
   * @var array
   */
  public $_fields;

  /**
   * The status message that user view.
   *
   * @var string
   */
  protected $_waitlistMsg;
  protected $_requireApprovalMsg;

  /**
   * Deprecated parameter that we hope to remove.
   *
   * @var bool
   */
  public $_quickConfig;

  /**
   * Skip duplicate check.
   *
   * This can be set using hook_civicrm_buildForm() to override the registration dupe check.
   * CRM-7604
   *
   * @var bool
   */
  public $_skipDupeRegistrationCheck = FALSE;

  public $_paymentProcessorID;

  /**
   * Show fee block or not.
   *
   * @var bool
   */
  public $_noFees;

  /**
   * Fee Block.
   *
   * @var array
   */
  public $_feeBlock;

  /**
   * Array of payment related fields to potentially display on this form (generally credit card or debit card fields).
   *
   * This is rendered via billingBlock.tpl.
   *
   * @var array
   */
  public $_paymentFields = [];

  /**
   * Is this submission incurring no costs.
   *
   * @param array $fields
   * @param \CRM_Event_Form_Registration_Register $form
   *
   * @return bool
   */
  protected static function isZeroAmount($fields, $form): bool {
    $isZeroAmount = FALSE;
    if (!empty($fields['priceSetId'])) {
      if (empty($fields['amount'])) {
        $isZeroAmount = TRUE;
      }
    }
    elseif (!empty($fields['amount']) &&
      (isset($form->_values['discount'][$fields['amount']])
        && CRM_Utils_Array::value('value', $form->_values['discount'][$fields['amount']]) == 0
      )
    ) {
      $isZeroAmount = TRUE;
    }
    elseif (!empty($fields['amount']) &&
      (isset($form->_values['fee'][$fields['amount']])
        && CRM_Utils_Array::value('value', $form->_values['fee'][$fields['amount']]) == 0
      )
    ) {
      $isZeroAmount = TRUE;
    }
    return $isZeroAmount;
  }

  /**
   * Get the contact id for the registration.
   *
   * @param array $fields
   * @param CRM_Event_Form_Registration $form
   * @param bool $isAdditional
   *
   * @return int|null
   */
  public static function getRegistrationContactID($fields, $form, $isAdditional) {
    $contactID = NULL;
    if (!$isAdditional) {
      $contactID = $form->getContactID();
    }
    if (!$contactID && is_array($fields) && $fields) {
      $contactID = CRM_Contact_BAO_Contact::getFirstDuplicateContact($fields, 'Individual', 'Unsupervised', [], FALSE, CRM_Utils_Array::value('dedupe_rule_group_id', $form->_values['event']), ['event_id' => CRM_Utils_Array::value('id', $form->_values['event'])]);
    }
    return $contactID;
  }

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    parent::preProcess();

    //CRM-4320.
    //here we can't use parent $this->_allowWaitlist as user might
    //walk back and we might set this value in this postProcess.
    //(we set when spaces < group count and want to allow become part of waiting )
    $eventFull = CRM_Event_BAO_Participant::eventFull($this->_eventId, FALSE, CRM_Utils_Array::value('has_waitlist', $this->_values['event']));

    // Get payment processors if appropriate for this event
    // We hide the payment fields if the event is full or requires approval,
    // and the current user has not yet been approved CRM-12279
    $this->_noFees = (($eventFull || $this->_requireApproval) && !$this->_allowConfirmation);
    $this->_paymentProcessors = $this->_noFees ? [] : $this->get('paymentProcessors');
    $this->preProcessPaymentOptions();

    $this->_allowWaitlist = FALSE;
    if ($eventFull && !$this->_allowConfirmation && !empty($this->_values['event']['has_waitlist'])) {
      $this->_allowWaitlist = TRUE;
      $this->_waitlistMsg = CRM_Utils_Array::value('waitlist_text', $this->_values['event']);
      if (!$this->_waitlistMsg) {
        $this->_waitlistMsg = ts('This event is currently full. However you can register now and get added to a waiting list. You will be notified if spaces become available.');
      }
    }
    $this->set('allowWaitlist', $this->_allowWaitlist);

    //To check if the user is already registered for the event(CRM-2426)
    if (!$this->_skipDupeRegistrationCheck) {
      self::checkRegistration(NULL, $this);
    }

    $this->assign('availableRegistrations', $this->_availableRegistrations);

    // get the participant values from EventFees.php, CRM-4320
    if ($this->_allowConfirmation) {
      CRM_Event_Form_EventFees::preProcess($this);
    }
  }

  /**
   * Set default values for the form.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function setDefaultValues() {
    $this->_defaults = [];
    if (!$this->_allowConfirmation && $this->_requireApproval) {
      $this->_defaults['bypass_payment'] = 1;
    }
    $contactID = $this->getContactID();
    CRM_Core_Payment_Form::setDefaultValues($this, $contactID);

    CRM_Event_BAO_Participant::formatFieldsAndSetProfileDefaults($contactID, $this);

    // Set default payment processor as default payment_processor radio button value
    if (!empty($this->_paymentProcessors)) {
      foreach ($this->_paymentProcessors as $pid => $value) {
        if (!empty($value['is_default'])) {
          $this->_defaults['payment_processor_id'] = $pid;
        }
      }
    }

    //if event is monetary and pay later is enabled and payment
    //processor is not available then freeze the pay later checkbox with
    //default check
    if (!empty($this->_values['event']['is_pay_later']) &&
      !is_array($this->_paymentProcessor)
    ) {
      $this->_defaults['is_pay_later'] = 1;
    }

    //set custom field defaults
    if (!empty($this->_fields)) {
      //load default campaign from page.
      if (array_key_exists('participant_campaign_id', $this->_fields)) {
        $this->_defaults['participant_campaign_id'] = CRM_Utils_Array::value('campaign_id',
          $this->_values['event']
        );
      }

      foreach ($this->_fields as $name => $field) {
        if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
          // fix for CRM-1743
          if (!isset($this->_defaults[$name])) {
            CRM_Core_BAO_CustomField::setProfileDefaults($customFieldID, $name, $this->_defaults,
              NULL, CRM_Profile_Form::MODE_REGISTER
            );
          }
        }
      }
    }

    //fix for CRM-3088, default value for discount set.
    $discountId = NULL;
    if (!empty($this->_values['discount'])) {
      $discountId = CRM_Core_BAO_Discount::findSet($this->_eventId, 'civicrm_event');
      if ($discountId) {
        if (isset($this->_values['event']['default_discount_fee_id'])) {
          $discountKey = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue',
            $this->_values['event']['default_discount_fee_id'],
            'weight', 'id'
          );

          $this->_defaults['amount'] = key(array_slice($this->_values['discount'][$discountId],
            $discountKey - 1, $discountKey, TRUE
          ));
        }
      }
    }

    // add this event's default participant role to defaults array
    // (for cases where participant_role field is included in form via profile)
    if ($this->_values['event']['default_role_id']) {
      $this->_defaults['participant_role']
        = $this->_defaults['participant_role_id'] = $this->_values['event']['default_role_id'];
    }
    if ($this->_priceSetId && !empty($this->_feeBlock)) {
      foreach ($this->_feeBlock as $key => $val) {
        if (empty($val['options'])) {
          continue;
        }
        $optionFullIds = CRM_Utils_Array::value('option_full_ids', $val, []);
        foreach ($val['options'] as $keys => $values) {
          if ($values['is_default'] && empty($values['is_full'])) {

            if ($val['html_type'] == 'CheckBox') {
              $this->_defaults["price_{$key}"][$keys] = 1;
            }
            else {
              $this->_defaults["price_{$key}"] = $keys;
            }
          }
        }
        $unsetSubmittedOptions[$val['id']] = $optionFullIds;
      }
      //reset values for all options those are full.
      CRM_Event_Form_Registration::resetElementValue($unsetSubmittedOptions, $this);
    }

    //set default participant fields, CRM-4320.
    $hasAdditionalParticipants = FALSE;
    if ($this->_allowConfirmation) {
      $this->_contactId = $contactID;
      $this->_discountId = $discountId;
      $forcePayLater = CRM_Utils_Array::value('is_pay_later', $this->_defaults, FALSE);
      $this->_defaults = array_merge($this->_defaults, CRM_Event_Form_EventFees::setDefaultValues($this));
      $this->_defaults['is_pay_later'] = $forcePayLater;

      if ($this->_additionalParticipantIds) {
        $hasAdditionalParticipants = TRUE;
        $this->_defaults['additional_participants'] = count($this->_additionalParticipantIds);
      }
    }
    $this->assign('hasAdditionalParticipants', $hasAdditionalParticipants);

    //         //hack to simplify credit card entry for testing
    //         $this->_defaults['credit_card_type']     = 'Visa';
    //         $this->_defaults['credit_card_number']   = '4807731747657838';
    //         $this->_defaults['cvv2']                 = '000';
    //         $this->_defaults['credit_card_exp_date'] = array( 'Y' => '2010', 'M' => '05' );

    // to process Custom data that are appended to URL
    $getDefaults = CRM_Core_BAO_CustomGroup::extractGetParams($this, "'Contact', 'Individual', 'Contribution', 'Participant'");
    if (!empty($getDefaults)) {
      $this->_defaults = array_merge($this->_defaults, $getDefaults);
    }

    return $this->_defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    // build profiles first so that we can determine address fields etc
    // and then show copy address checkbox
    $this->buildCustom($this->_values['custom_pre_id'], 'customPre');
    $this->buildCustom($this->_values['custom_post_id'], 'customPost');

    // CRM-18399: used by template to pass pre profile id as a url arg
    $this->assign('custom_pre_id', $this->_values['custom_pre_id']);

    CRM_Core_Payment_ProcessorForm::buildQuickForm($this);

    $contactID = $this->getContactID();
    if ($contactID) {
      $this->assign('contact_id', $contactID);
      $this->assign('display_name', CRM_Contact_BAO_Contact::displayName($contactID));
    }

    $bypassPayment = $allowGroupOnWaitlist = $isAdditionalParticipants = FALSE;
    if ($this->_values['event']['is_multiple_registrations']) {
      // don't allow to add additional during confirmation if not preregistered.
      if (!$this->_allowConfirmation || $this->_additionalParticipantIds) {
        // CRM-17745: Make maximum additional participants configurable
        // Label is value + 1, since the code sees this is ADDITIONAL participants (in addition to "self")
        $additionalOptions = [];
        $additionalOptions[''] = 1;
        for ($i = 1; $i <= $this->_values['event']['max_additional_participants']; $i++) {
          $additionalOptions[$i] = $i + 1;
        }
        $this->add('select', 'additional_participants',
          ts('How many people are you registering?'),
          $additionalOptions,
          NULL,
          ['onChange' => "allowParticipant()"]
        );
        $isAdditionalParticipants = TRUE;
      }
    }

    if (!$this->_allowConfirmation) {
      $bypassPayment = TRUE;
    }

    //hack to allow group to register w/ waiting
    if ((!empty($this->_values['event']['is_multiple_registrations']) ||
        $this->_priceSetId
      ) &&
      !$this->_allowConfirmation &&
      is_numeric($this->_availableRegistrations) && !empty($this->_values['event']['has_waitlist'])
    ) {
      $bypassPayment = TRUE;
      //case might be group become as a part of waitlist.
      //If not waitlist then they require admin approve.
      $allowGroupOnWaitlist = TRUE;
      $this->_waitlistMsg = ts("This event has only %1 space(s) left. If you continue and register more than %1 people (including yourself ), the whole group will be wait listed. Or, you can reduce the number of people you are registering to %1 to avoid being put on the waiting list.", [1 => $this->_availableRegistrations]);

      if ($this->_requireApproval) {
        $this->_requireApprovalMsg = CRM_Utils_Array::value('approval_req_text', $this->_values['event'],
          ts('Registration for this event requires approval. Once your registration(s) have been reviewed, you will receive an email with a link to a web page where you can complete the registration process.')
        );
      }
    }

    //case where only approval needed - no waitlist.
    if ($this->_requireApproval &&
      !$this->_allowWaitlist && !$bypassPayment
    ) {
      $this->_requireApprovalMsg = CRM_Utils_Array::value('approval_req_text', $this->_values['event'],
        ts('Registration for this event requires approval. Once your registration has been reviewed, you will receive an email with a link to a web page where you can complete the registration process.')
      );
    }

    //lets display status to primary page only.
    $this->assign('waitlistMsg', $this->_waitlistMsg);
    $this->assign('requireApprovalMsg', $this->_requireApprovalMsg);
    $this->assign('allowGroupOnWaitlist', $allowGroupOnWaitlist);
    $this->assign('isAdditionalParticipants', $isAdditionalParticipants);

    if ($this->_values['event']['is_monetary']) {
      self::buildAmount($this);
    }

    $pps = $this->getProcessors();
    if ($this->getContactID() === 0 && !$this->_values['event']['is_multiple_registrations']) {
      //@todo we are blocking for multiple registrations because we haven't tested
      $this->addCIDZeroOptions();
    }

    if ($this->_values['event']['is_monetary']) {
      if (count($pps) > 1) {
        $this->addRadio('payment_processor_id', ts('Payment Method'), $pps,
          NULL, "&nbsp;"
        );
      }
      elseif (!empty($pps)) {
        $ppKeys = array_keys($pps);
        $currentPP = array_pop($ppKeys);
        $this->addElement('hidden', 'payment_processor_id', $currentPP);
      }
    }

    $this->addElement('hidden', 'bypass_payment', NULL, ['id' => 'bypass_payment']);

    $this->assign('bypassPayment', $bypassPayment);

    $userID = $this->getContactID();

    if (!$userID) {
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

    //we have to load confirm contribution button in template
    //when multiple payment processor as the user
    //can toggle with payment processor selection
    $billingModePaymentProcessors = 0;
    if (!CRM_Utils_System::isNull($this->_paymentProcessors)) {
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

    if (!$allAreBillingModeProcessors || !empty($this->_values['event']['is_pay_later']) || $bypassPayment
    ) {

      //freeze button to avoid multiple calls.
      if (empty($this->_values['event']['is_monetary'])) {
        $this->submitOnce = TRUE;
      }

      // CRM-11182 - Optional confirmation screen
      // Change button label depending on whether the next action is confirm or register
      if (
        !$this->_values['event']['is_multiple_registrations']
        && !$this->_values['event']['is_monetary']
        && !$this->_values['event']['is_confirm_enabled']
      ) {
        $buttonLabel = ts('Register');
      }
      else {
        $buttonLabel = ts('Continue');
      }

      $this->addButtons([
        [
          'type' => 'upload',
          'name' => $buttonLabel,
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
      ]);
    }

    $this->addFormRule(['CRM_Event_Form_Registration_Register', 'formRule'], $this);
    $this->unsavedChangesWarn = TRUE;

    // add pcp fields
    if ($this->_pcpId) {
      CRM_PCP_BAO_PCP::buildPcp($this->_pcpId, $this);
    }
  }

  /**
   * Build the radio/text form elements for the amount field
   *
   * @param CRM_Event_Form_Registration_Register $form
   *   Form object.
   * @param bool $required
   *   True if you want to add formRule.
   * @param int $discountId
   *   Discount id for the event.
   *
   * @throws \CRM_Core_Exception
   */
  public static function buildAmount(&$form, $required = TRUE, $discountId = NULL) {
    // build amount only when needed, skip incase of event full and waitlisting is enabled
    // and few other conditions check preProcess()
    if (property_exists($form, '_noFees') && $form->_noFees) {
      return;
    }

    //if payment done, no need to build the fee block.
    if (!empty($form->_paymentId)) {
      //fix to display line item in update mode.
      $form->assign('priceSet', isset($form->_priceSet) ? $form->_priceSet : NULL);
      return;
    }

    $feeFields = CRM_Utils_Array::value('fee', $form->_values);

    if (is_array($feeFields)) {
      $form->_feeBlock = &$form->_values['fee'];
    }

    //check for discount.
    $discountedFee = CRM_Utils_Array::value('discount', $form->_values);
    if (is_array($discountedFee) && !empty($discountedFee)) {
      if (!$discountId) {
        $form->_discountId = $discountId = CRM_Core_BAO_Discount::findSet($form->_eventId, 'civicrm_event');
      }
      if ($discountId) {
        $form->_feeBlock = &$form->_values['discount'][$discountId];
      }
    }
    if (!is_array($form->_feeBlock)) {
      $form->_feeBlock = [];
    }

    //its time to call the hook.
    CRM_Utils_Hook::buildAmount('event', $form, $form->_feeBlock);

    //reset required if participant is skipped.
    $button = substr($form->controller->getButtonName(), -4);
    if ($required && $button == 'skip') {
      $required = FALSE;
    }

    $className = CRM_Utils_System::getClassName($form);

    //build the priceset fields.
    if (isset($form->_priceSetId) && $form->_priceSetId) {

      //format price set fields across option full.
      self::formatFieldsForOptionFull($form);

      if (!empty($form->_priceSet['is_quick_config'])) {
        $form->_quickConfig = $form->_priceSet['is_quick_config'];
      }
      $form->add('hidden', 'priceSetId', $form->_priceSetId);

      // CRM-14492 Admin price fields should show up on event registration if user has 'administer CiviCRM' permissions
      $adminFieldVisible = FALSE;
      if (CRM_Core_Permission::check('administer CiviCRM')) {
        $adminFieldVisible = TRUE;
      }

      $hideAdminValues = TRUE;
      if (CRM_Core_Permission::check('edit event participants')) {
        $hideAdminValues = FALSE;
      }

      foreach ($form->_feeBlock as $field) {
        // public AND admin visibility fields are included for back-office registration and back-office change selections
        if (CRM_Utils_Array::value('visibility', $field) == 'public' ||
          (CRM_Utils_Array::value('visibility', $field) == 'admin' && $adminFieldVisible == TRUE) ||
          $className == 'CRM_Event_Form_Participant' ||
          $className == 'CRM_Event_Form_ParticipantFeeSelection'
        ) {
          $fieldId = $field['id'];
          $elementName = 'price_' . $fieldId;

          $isRequire = CRM_Utils_Array::value('is_required', $field);
          if ($button == 'skip') {
            $isRequire = FALSE;
          }

          //user might modified w/ hook.
          $options = CRM_Utils_Array::value('options', $field);
          $formClasses = ['CRM_Event_Form_Participant', 'CRM_Event_Form_ParticipantFeeSelection'];

          if (!is_array($options)) {
            continue;
          }
          elseif ($hideAdminValues && !in_array($className, $formClasses)) {
            $publicVisibilityID = CRM_Price_BAO_PriceField::getVisibilityOptionID('public');
            $adminVisibilityID = CRM_Price_BAO_PriceField::getVisibilityOptionID('admin');

            foreach ($options as $key => $currentOption) {
              $optionVisibility = CRM_Utils_Array::value('visibility_id', $currentOption, $publicVisibilityID);
              if ($optionVisibility == $adminVisibilityID) {
                unset($options[$key]);
              }
            }
          }

          $optionFullIds = CRM_Utils_Array::value('option_full_ids', $field, []);

          //soft suppress required rule when option is full.
          if (!empty($optionFullIds) && (count($options) == count($optionFullIds))) {
            $isRequire = FALSE;
          }
          if (!empty($options)) {
            //build the element.
            CRM_Price_BAO_PriceField::addQuickFormElement($form,
              $elementName,
              $fieldId,
              FALSE,
              $isRequire,
              NULL,
              $options,
              $optionFullIds
            );
          }
        }
      }
      $form->assign('priceSet', $form->_priceSet);
    }
    else {
      $eventFeeBlockValues = [];
      foreach ($form->_feeBlock as $fee) {
        if (is_array($fee)) {

          //CRM-7632, CRM-6201
          $totalAmountJs = NULL;
          if ($className == 'CRM_Event_Form_Participant') {
            $totalAmountJs = ['onClick' => "fillTotalAmount(" . $fee['value'] . ")"];
          }

          $eventFeeBlockValues['amount_id_' . $fee['amount_id']] = $fee['value'];
          $elements[] = &$form->createElement('radio', NULL, '',
            CRM_Utils_Money::format($fee['value']) . ' ' .
            $fee['label'],
            $fee['amount_id'],
            $totalAmountJs
          );
        }
      }
      $form->assign('eventFeeBlockValues', json_encode($eventFeeBlockValues));

      $form->_defaults['amount'] = CRM_Utils_Array::value('default_fee_id', $form->_values['event']);
      $element = &$form->addGroup($elements, 'amount', ts('Event Fee(s)'), '<br />');
      if (isset($form->_online) && $form->_online) {
        $element->freeze();
      }
      if ($required) {
        $form->addRule('amount', ts('Fee Level is a required field.'), 'required');
      }
    }
  }

  /**
   * @param CRM_Event_Form_Registration $form
   */
  public static function formatFieldsForOptionFull(&$form) {
    $priceSet = $form->get('priceSet');
    $priceSetId = $form->get('priceSetId');
    $defaultPricefieldIds = [];
    if (!empty($form->_values['line_items'])) {
      foreach ($form->_values['line_items'] as $lineItem) {
        $defaultPricefieldIds[] = $lineItem['price_field_value_id'];
      }
    }
    if (!$priceSetId ||
      !is_array($priceSet) ||
      empty($priceSet) || empty($priceSet['optionsMaxValueTotal'])
    ) {
      return;
    }

    $skipParticipants = $formattedPriceSetDefaults = [];
    if (!empty($form->_allowConfirmation) && (isset($form->_pId) || isset($form->_additionalParticipantId))) {
      $participantId = isset($form->_pId) ? $form->_pId : $form->_additionalParticipantId;
      $pricesetDefaults = CRM_Event_Form_EventFees::setDefaultPriceSet($participantId,
        $form->_eventId
      );
      // modify options full to respect the selected fields
      // options on confirmation.
      $formattedPriceSetDefaults = self::formatPriceSetParams($form, $pricesetDefaults);

      // to skip current registered participants fields option count on confirmation.
      $skipParticipants[] = $form->_participantId;
      if (!empty($form->_additionalParticipantIds)) {
        $skipParticipants = array_merge($skipParticipants, $form->_additionalParticipantIds);
      }
    }

    $className = CRM_Utils_System::getClassName($form);

    //get the current price event price set options count.
    $currentOptionsCount = self::getPriceSetOptionCount($form);
    $recordedOptionsCount = CRM_Event_BAO_Participant::priceSetOptionsCount($form->_eventId, $skipParticipants);
    $optionFullTotalAmount = 0;
    $currentParticipantNo = (int) substr($form->_name, 12);
    foreach ($form->_feeBlock as & $field) {
      $optionFullIds = [];
      $fieldId = $field['id'];
      if (!is_array($field['options'])) {
        continue;
      }
      foreach ($field['options'] as & $option) {
        $optId = $option['id'];
        $count = CRM_Utils_Array::value('count', $option, 0);
        $maxValue = CRM_Utils_Array::value('max_value', $option, 0);
        $dbTotalCount = CRM_Utils_Array::value($optId, $recordedOptionsCount, 0);
        $currentTotalCount = CRM_Utils_Array::value($optId, $currentOptionsCount, 0);

        $totalCount = $currentTotalCount + $dbTotalCount;
        $isFull = FALSE;
        if ($maxValue &&
          (($totalCount >= $maxValue) &&
          (empty($form->_lineItem[$currentParticipantNo][$optId]['price_field_id']) || $dbTotalCount >= $maxValue))
        ) {
          $isFull = TRUE;
          $optionFullIds[$optId] = $optId;
          if ($field['html_type'] != 'Select') {
            if (in_array($optId, $defaultPricefieldIds)) {
              $optionFullTotalAmount += CRM_Utils_Array::value('amount', $option);
            }
          }
          else {
            if (!empty($defaultPricefieldIds) && in_array($optId, $defaultPricefieldIds)) {
              unset($optionFullIds[$optId]);
            }
          }
        }
        //here option is not full,
        //but we don't want to allow participant to increase
        //seats at the time of re-walking registration.
        if ($count &&
          !empty($form->_allowConfirmation) &&
          !empty($formattedPriceSetDefaults)
        ) {
          if (empty($formattedPriceSetDefaults["price_{$field}"]) || empty($formattedPriceSetDefaults["price_{$fieldId}"][$optId])) {
            $optionFullIds[$optId] = $optId;
            $isFull = TRUE;
          }
        }
        $option['is_full'] = $isFull;
        $option['db_total_count'] = $dbTotalCount;
        $option['total_option_count'] = $dbTotalCount + $currentTotalCount;
      }

      //ignore option full for offline registration.
      if ($className == 'CRM_Event_Form_Participant') {
        $optionFullIds = [];
      }

      //finally get option ids in.
      $field['option_full_ids'] = $optionFullIds;
    }
    $form->assign('optionFullTotalAmount', $optionFullTotalAmount);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param \CRM_Event_Form_Registration_Register $form
   *
   * @return bool|array
   *   true if no errors, else array of errors
   *
   * @throws \CRM_Core_Exception
   */
  public static function formRule($fields, $files, $form) {
    $errors = [];
    //check that either an email or firstname+lastname is included in the form(CRM-9587)
    self::checkProfileComplete($fields, $errors, $form->_eventId);
    //To check if the user is already registered for the event(CRM-2426)
    if (!$form->_skipDupeRegistrationCheck) {
      self::checkRegistration($fields, $form);
    }
    //check for availability of registrations.
    if (!$form->_allowConfirmation && empty($fields['bypass_payment']) &&
      is_numeric($form->_availableRegistrations) &&
      CRM_Utils_Array::value('additional_participants', $fields) >= $form->_availableRegistrations
    ) {
      $errors['additional_participants'] = ts("There is only enough space left on this event for %1 participant(s).", [1 => $form->_availableRegistrations]);
    }

    $numberAdditionalParticipants = $fields['additional_participants'] ?? 0;

    if ($numberAdditionalParticipants && !CRM_Utils_Rule::positiveInteger($fields['additional_participants'])) {
      $errors['additional_participants'] = ts('Please enter a whole number for Number of additional people.');
    }

    // during confirmation don't allow to increase additional participants, CRM-4320
    if ($form->_allowConfirmation && $numberAdditionalParticipants &&
      is_array($form->_additionalParticipantIds) &&
      $numberAdditionalParticipants > count($form->_additionalParticipantIds)
    ) {
      $errors['additional_participants'] = ts("Oops. It looks like you are trying to increase the number of additional people you are registering for. You can confirm registration for a maximum of %1 additional people.", [1 => count($form->_additionalParticipantIds)]);
    }

    //don't allow to register w/ waiting if enough spaces available.
    if (!empty($fields['bypass_payment']) && $form->_allowConfirmation) {
      if (!is_numeric($form->_availableRegistrations) ||
        (empty($fields['priceSetId']) && CRM_Utils_Array::value('additional_participants', $fields) < $form->_availableRegistrations)
      ) {
        $errors['bypass_payment'] = ts("Oops. There are enough available spaces in this event. You can not add yourself to the waiting list.");
      }
    }

    // priceset validations
    if (!empty($fields['priceSetId']) &&
     !$form->_requireApproval && !$form->_allowWaitlist
     ) {
      //format params.
      $formatted = self::formatPriceSetParams($form, $fields);
      $ppParams = [$formatted];
      $priceSetErrors = self::validatePriceSet($form, $ppParams);
      $primaryParticipantCount = self::getParticipantCount($form, $ppParams);

      //get price set fields errors in.
      $errors = array_merge($errors, CRM_Utils_Array::value(0, $priceSetErrors, []));

      $totalParticipants = $primaryParticipantCount;
      if ($numberAdditionalParticipants) {
        $totalParticipants += $numberAdditionalParticipants;
      }

      if (empty($fields['bypass_payment']) &&
        !$form->_allowConfirmation &&
        is_numeric($form->_availableRegistrations) &&
        $form->_availableRegistrations < $totalParticipants
      ) {
        $errors['_qf_default'] = ts("Only %1 Registrations available.", [1 => $form->_availableRegistrations]);
      }

      $lineItem = [];
      CRM_Price_BAO_PriceSet::processAmount($form->_values['fee'], $fields, $lineItem);

      $minAmt = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $fields['priceSetId'], 'min_amount');
      if ($fields['amount'] < 0) {
        $errors['_qf_default'] = ts('Event Fee(s) can not be less than zero. Please select the options accordingly');
      }
      elseif (!empty($minAmt) && $fields['amount'] < $minAmt) {
        $errors['_qf_default'] = ts('A minimum amount of %1 should be selected from Event Fee(s).', [
          1 => CRM_Utils_Money::format($minAmt),
        ]);
      }
    }
    foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
      if ($greetingType = CRM_Utils_Array::value($greeting, $fields)) {
        $customizedValue = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Contact', $greeting . '_id', 'Customized');
        if ($customizedValue == $greetingType && empty($fields[$greeting . '_custom'])) {
          $errors[$greeting . '_custom'] = ts('Custom %1 is a required field if %1 is of type Customized.',
            [1 => ucwords(str_replace('_', ' ', $greeting))]
          );
        }
      }
    }

    // @todo - can we remove the 'is_monetary' concept?
    if ($form->_values['event']['is_monetary']) {
      if (empty($form->_requireApproval) && !empty($fields['amount']) && $fields['amount'] > 0 &&
        !isset($fields['payment_processor_id'])) {
        $errors['payment_processor_id'] = ts('Please select a Payment Method');
      }

      if (self::isZeroAmount($fields, $form)) {
        return empty($errors) ? TRUE : $errors;
      }

      // also return if zero fees for valid members
      if (!empty($fields['bypass_payment']) ||
        (!$form->_allowConfirmation && ($form->_requireApproval || $form->_allowWaitlist))
      ) {
        return empty($errors) ? TRUE : $errors;
      }
      CRM_Core_Payment_Form::validatePaymentInstrument(
        $fields['payment_processor_id'],
        $fields,
        $errors,
        (!$form->_isBillingAddressRequiredForPayLater ? NULL : 'billing')
      );
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Check if profiles are complete when event registration occurs(CRM-9587).
   *
   * @param array $fields
   * @param array $errors
   * @param int $eventId
   */
  public static function checkProfileComplete($fields, &$errors, $eventId) {
    $email = '';
    foreach ($fields as $fieldname => $fieldvalue) {
      if (substr($fieldname, 0, 6) == 'email-' && $fieldvalue) {
        $email = $fieldvalue;
      }
    }

    if (!$email && !(!empty($fields['first_name']) && !empty($fields['last_name']))) {
      $defaults = $params = ['id' => $eventId];
      CRM_Event_BAO_Event::retrieve($params, $defaults);
      $message = ts("Mandatory fields (first name and last name, OR email address) are missing from this form.");
      $errors['_qf_default'] = $message;
    }
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    //set as Primary participant
    $params['is_primary'] = 1;

    if ($this->_values['event']['is_pay_later']
      && (!array_key_exists('hidden_processor', $params) || $params['payment_processor_id'] == 0)
    ) {
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
      $this->assign('pay_later_text', $this->_values['event']['pay_later_text']);
      $this->assign('pay_later_receipt', $this->_values['event']['pay_later_receipt']);
    }

    if (!$this->_allowConfirmation) {
      // check if the participant is already registered
      if (!$this->_skipDupeRegistrationCheck) {
        $params['contact_id'] = self::getRegistrationContactID($params, $this, FALSE);
      }
    }

    if (!empty($params['image_URL'])) {
      CRM_Contact_BAO_Contact::processImageParams($params);
    }

    //carry campaign to partcipants.
    if (array_key_exists('participant_campaign_id', $params)) {
      $params['campaign_id'] = $params['participant_campaign_id'];
    }
    else {
      $params['campaign_id'] = CRM_Utils_Array::value('campaign_id', $this->_values['event']);
    }

    //hack to allow group to register w/ waiting
    $primaryParticipantCount = self::getParticipantCount($this, $params);

    $totalParticipants = $primaryParticipantCount;
    if (!empty($params['additional_participants'])) {
      $totalParticipants += $params['additional_participants'];
    }
    if (!$this->_allowConfirmation && !empty($params['bypass_payment']) &&
      is_numeric($this->_availableRegistrations) &&
      $totalParticipants > $this->_availableRegistrations
    ) {
      $this->_allowWaitlist = TRUE;
      $this->set('allowWaitlist', TRUE);
    }

    //carry participant id if pre-registered.
    if ($this->_allowConfirmation && $this->_participantId) {
      $params['participant_id'] = $this->_participantId;
    }

    $params['defaultRole'] = 1;
    if (array_key_exists('participant_role', $params)) {
      $params['participant_role_id'] = $params['participant_role'];
    }

    if (array_key_exists('participant_role_id', $params)) {
      $params['defaultRole'] = 0;
    }
    if (empty($params['participant_role_id']) &&
      $this->_values['event']['default_role_id']
    ) {
      $params['participant_role_id'] = $this->_values['event']['default_role_id'];
    }

    $config = CRM_Core_Config::singleton();
    $params['currencyID'] = $config->defaultCurrency;

    if ($this->_values['event']['is_monetary']) {
      // we first reset the confirm page so it accepts new values
      $this->controller->resetPage('Confirm');

      //added for discount
      $discountId = CRM_Core_BAO_Discount::findSet($this->_eventId, 'civicrm_event');
      $params['amount_level'] = $this->getAmountLevel($params, $discountId);
      if (!empty($this->_values['discount'][$discountId])) {
        $params['discount_id'] = $discountId;
        $params['amount'] = $this->_values['discount'][$discountId][$params['amount']]['value'];
      }
      elseif (empty($params['priceSetId'])) {
        if (!empty($params['amount'])) {
          $params['amount'] = $this->_values['fee'][$params['amount']]['value'];
        }
        else {
          $params['amount'] = '';
        }
      }
      else {
        $lineItem = [];
        CRM_Price_BAO_PriceSet::processAmount($this->_values['fee'], $params, $lineItem);
        if ($params['tax_amount']) {
          $this->set('tax_amount', $params['tax_amount']);
        }
        $submittedLineItems = $this->get('lineItem');
        if (!empty($submittedLineItems) && is_array($submittedLineItems)) {
          $submittedLineItems[0] = $lineItem;
        }
        else {
          $submittedLineItems = [$lineItem];
        }
        $submittedLineItems = array_filter($submittedLineItems);
        $this->set('lineItem', $submittedLineItems);
        $this->set('lineItemParticipantsCount', [$primaryParticipantCount]);
      }

      $this->set('amount', $params['amount']);
      $this->set('amount_level', $params['amount_level']);

      // generate and set an invoiceID for this transaction
      $invoiceID = md5(uniqid(rand(), TRUE));
      $this->set('invoiceID', $invoiceID);

      if ($this->_paymentProcessor) {
        $payment = $this->_paymentProcessor['object'];
        $payment->setBaseReturnUrl('civicrm/event/register');
      }

      // ContributeMode is a deprecated concept. It is short-hand for a bunch of
      // assumptions we are working to remove.
      $this->set('contributeMode', 'direct');

      if ($this->_values['event']['is_monetary']) {
        $params['currencyID'] = $config->defaultCurrency;
        $params['invoiceID'] = $invoiceID;
      }
      $this->_params = $this->get('params');
      // Set the button so we know what
      $params['button'] = $this->controller->getButtonName();
      if (!empty($this->_params) && is_array($this->_params)) {
        $this->_params[0] = $params;
      }
      else {
        $this->_params = [];
        $this->_params[] = $params;
      }
      $this->set('params', $this->_params);
      if ($this->_paymentProcessor &&
        // Actually we don't really need to check if it supports pre-approval - we could just call
        // it regardless as the function we call re-acts tot the rests of the preApproval call.
        $this->_paymentProcessor['object']->supports('preApproval')
        && !$this->_allowWaitlist &&
        !$this->_requireApproval
      ) {

        // The concept of contributeMode is deprecated - but still needs removal from the message templates.
        $this->set('contributeMode', 'express');

        // Send Event Name & Id in Params
        $params['eventName'] = $this->_values['event']['title'];
        $params['eventId'] = $this->_values['event']['id'];

        $params['cancelURL'] = CRM_Utils_System::url('civicrm/event/register',
          "_qf_Register_display=1&qfKey={$this->controller->_key}",
          TRUE, NULL, FALSE
        );
        if (!empty($params['additional_participants'])) {
          $urlArgs = "_qf_Participant_1_display=1&rfp=1&qfKey={$this->controller->_key}";
        }
        else {
          $urlArgs = "_qf_Confirm_display=1&rfp=1&qfKey={$this->controller->_key}";
        }
        $params['returnURL'] = CRM_Utils_System::url('civicrm/event/register',
          $urlArgs,
          TRUE, NULL, FALSE
        );
        $params['invoiceID'] = $invoiceID;

        $params['component'] = 'event';
        // This code is duplicated multiple places and should be consolidated.
        $params = $this->prepareParamsForPaymentProcessor($params);
        $this->handlePreApproval($params);
      }
      elseif ($this->_paymentProcessor &&
        (int) $this->_paymentProcessor['billing_mode'] & CRM_Core_Payment::BILLING_MODE_NOTIFY
      ) {
        // The concept of contributeMode is deprecated - but still needs removal from the message templates.
        $this->set('contributeMode', 'notify');
      }
    }
    else {
      $params['description'] = ts('Online Event Registration') . ' ' . $this->_values['event']['title'];

      $this->_params = [];
      $this->_params[] = $params;
      $this->set('params', $this->_params);

      if (
        empty($params['additional_participants'])
      // CRM-11182 - Optional confirmation screen
        && !$this->_values['event']['is_confirm_enabled']
      ) {
        $this->processRegistration($this->_params);
      }
    }

    // If registering > 1 participant, give status message
    if (!empty($params['additional_participants'])) {
      $statusMsg = ts('Registration information for participant 1 has been saved.');
      CRM_Core_Session::setStatus($statusMsg, ts('Saved'), 'success');
    }
  }

  /**
   * Method to check if the user is already registered for the event.
   * and if result found redirect to the event info page
   *
   * @param array $fields
   *   The input form values(anonymous user).
   * @param CRM_Event_Form_Registration_Register $form
   *   Event data.
   * @param bool $isAdditional
   *   Treat isAdditional participants a bit differently.
   *
   * @return int
   */
  public static function checkRegistration($fields, $form, $isAdditional = FALSE) {
    // CRM-3907, skip check for preview registrations
    // CRM-4320 participant need to walk wizard
    if (
      ($form->_mode == 'test' || $form->_allowConfirmation)
    ) {
      return FALSE;
    }

    $contactID = self::getRegistrationContactID($fields, $form, $isAdditional);

    if ($contactID) {
      $participant = new CRM_Event_BAO_Participant();
      $participant->contact_id = $contactID;
      $participant->event_id = $form->_values['event']['id'];
      if (!empty($fields['participant_role']) && is_numeric($fields['participant_role'])) {
        $participant->role_id = $fields['participant_role'];
      }
      else {
        $participant->role_id = $form->_values['event']['default_role_id'];
      }
      $participant->is_test = 0;
      $participant->find();
      // Event#30 - Anyone whose status type has `is_counted` OR is on the waitlist should be considered as registered.
      $statusTypes = CRM_Event_PseudoConstant::participantStatus(NULL, 'is_counted = 1') + CRM_Event_PseudoConstant::participantStatus(NULL, "name = 'On waitlist'");
      while ($participant->fetch()) {
        if (array_key_exists($participant->status_id, $statusTypes)) {
          if (!$isAdditional && !$form->_values['event']['allow_same_participant_emails']) {
            $registerUrl = CRM_Utils_System::url('civicrm/event/register',
              "reset=1&id={$form->_values['event']['id']}&cid=0"
            );
            if ($form->_pcpId) {
              $registerUrl .= '&pcpId=' . $form->_pcpId;
            }
            $registrationType = (CRM_Event_PseudoConstant::getKey('CRM_Event_BAO_Participant', 'participant_status_id', 'On waitlist') == $participant->status_id) ? 'waitlisted' : 'registered';
            $status = ts("It looks like you are already %1 for this event. If you want to change your registration, or you feel that you've received this message in error, please contact the site administrator.", [1 => $registrationType]);
            $status .= ' ' . ts('You can also <a href="%1">register another participant</a>.', [1 => $registerUrl]);
            CRM_Core_Session::singleton()->setStatus($status, ts('Oops.'), 'alert');
            $url = CRM_Utils_System::url('civicrm/event/info',
              "reset=1&id={$form->_values['event']['id']}&noFullMsg=true"
            );
            if ($form->_action & CRM_Core_Action::PREVIEW) {
              $url .= '&action=preview';
            }

            if ($form->_pcpId) {
              $url .= '&pcpId=' . $form->_pcpId;
            }

            CRM_Utils_System::redirect($url);
          }

          if ($isAdditional) {
            $status = ts("It looks like this participant is already registered for this event. If you want to change your registration, or you feel that you've received this message in error, please contact the site administrator.");
            CRM_Core_Session::singleton()->setStatus($status, ts('Oops.'), 'alert');
            return $participant->id;
          }
        }
      }
    }
  }

}
