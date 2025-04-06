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
   * Skip duplicate check.
   *
   * This can be set using hook_civicrm_buildForm() to override the registration dupe check.
   *
   * @var bool
   * @see https://issues.civicrm.org/jira/browse/CRM-7604
   */
  public $_skipDupeRegistrationCheck = FALSE;

  public $_paymentProcessorID;

  /**
   * Show fee block or not.
   *
   * @var bool
   *
   * @deprecated
   */
  public $_noFees;

  /**
   * Fee Block.
   *
   * @var array
   */
  public $_feeBlock;

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
        && ($form->_values['discount'][$fields['amount']]['value'] ?? NULL) == 0
      )
    ) {
      $isZeroAmount = TRUE;
    }
    elseif (!empty($fields['amount']) &&
      (isset($form->_values['fee'][$fields['amount']])
        && ($form->_values['fee'][$fields['amount']]['value'] ?? NULL) == 0
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
      $contactID = CRM_Contact_BAO_Contact::getFirstDuplicateContact($fields, 'Individual', 'Unsupervised', [], FALSE, $form->_values['event']['dedupe_rule_group_id'] ?? NULL, ['event_id' => $form->_values['event']['id'] ?? NULL]);
    }
    return $contactID;
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
   */
  public function preProcess() {
    parent::preProcess();

    //CRM-4320.
    //here we can't use parent $this->_allowWaitlist as user might
    //walk back and we might set this value in this postProcess.
    //(we set when spaces < group count and want to allow become part of waiting )
    $eventFull = CRM_Event_BAO_Participant::eventFull($this->_eventId, FALSE, $this->_values['event']['has_waitlist'] ?? NULL);

    // Get payment processors if appropriate for this event
    $this->_noFees = $suppressPayment = $this->isSuppressPayment();
    $this->_paymentProcessors = $suppressPayment ? [] : $this->get('paymentProcessors');
    $this->assign('suppressPaymentBlock', $suppressPayment);
    $this->preProcessPaymentOptions();

    $this->_allowWaitlist = FALSE;
    if ($eventFull && !$this->_allowConfirmation && !empty($this->_values['event']['has_waitlist'])) {
      $this->_allowWaitlist = TRUE;
      $this->_waitlistMsg = $this->_values['event']['waitlist_text'] ?? NULL;
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
      $this->eventFeeWrangling();
    }
  }

  /**
   * This is previously shared code which is probably of little value.
   *
   * @throws \CRM_Core_Exception
   */
  private function eventFeeWrangling() {
    $this->_pId = CRM_Utils_Request::retrieve('participantId', 'Positive', $this);
    $this->_discountId = CRM_Utils_Request::retrieve('discountId', 'Positive', $this);

    //CRM-6907 set event specific currency.
    if ($this->getEventID() &&
      ($currency = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->getEventID(), 'currency'))
    ) {
      CRM_Core_Config::singleton()->defaultCurrency = $currency;
    }
  }

  /**
   * Set default values for the form.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues() {
    $this->_defaults = [];
    if ($this->isSuppressPayment()) {
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
        $this->_defaults['participant_campaign_id'] = $this->_values['event']['campaign_id'] ?? NULL;
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
    if ($this->_priceSetId) {
      foreach ($this->getPriceFieldMetaData() as $key => $val) {
        if (empty($val['options'])) {
          continue;
        }
        $optionFullIds = $this->getOptionFullPriceFieldValues($val);
        foreach ($val['options'] as $keys => $values) {
          $priceFieldName = 'price_' . $values['price_field_id'];
          $priceFieldValue = CRM_Price_BAO_PriceSet::getPriceFieldValueFromURL($this, $priceFieldName);
          if (!empty($priceFieldValue)) {
            CRM_Price_BAO_PriceSet::setDefaultPriceSetField($priceFieldName, $priceFieldValue, $val['html_type'], $this->_defaults);
            // break here to prevent overwriting of default due to 'is_default'
            // option configuration. The value sent via URL get's higher priority.
            break;
          }
          else {
            if ($values['is_default'] && !$this->getIsOptionFull($values)) {
              if ($val['html_type'] === 'CheckBox') {
                $this->_defaults["price_{$key}"][$keys] = 1;
              }
              else {
                $this->_defaults["price_{$key}"] = $keys;
              }
            }
          }
        }
        $unsetSubmittedOptions[$val['id']] = $optionFullIds;
      }
      //reset values for all options those are full.
      CRM_Event_Form_Registration::resetElementValue($unsetSubmittedOptions ?? [], $this);
    }

    //set default participant fields, CRM-4320.
    $hasAdditionalParticipants = FALSE;
    if ($this->_allowConfirmation) {
      $this->_contactId = $contactID;
      $this->_discountId = $discountId;
      $forcePayLater = $this->_defaults['is_pay_later'] ?? FALSE;
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

    $contactID = $this->getContactID();
    $this->assign('contact_id', $contactID);
    if ($contactID) {
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
          NULL
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
        $this->_requireApprovalMsg = $this->_values['event']['approval_req_text'] ??
          ts('Registration for this event requires approval. Once your registration(s) have been reviewed, you will receive an email with a link to a web page where you can complete the registration process.');
      }
    }

    //case where only approval needed - no waitlist.
    if ($this->_requireApproval &&
      !$this->_allowWaitlist && !$bypassPayment
    ) {
      $this->_requireApprovalMsg = $this->_values['event']['approval_req_text'] ??
        ts('Registration for this event requires approval. Once your registration has been reviewed, you will receive an email with a link to a web page where you can complete the registration process.');
    }

    //lets display status to primary page only.
    $this->assign('waitlistMsg', $this->_waitlistMsg);
    $this->assign('requireApprovalMsg', $this->_requireApprovalMsg);
    $this->assign('allowGroupOnWaitlist', $allowGroupOnWaitlist);
    $this->assign('isAdditionalParticipants', $isAdditionalParticipants);

    if ($this->_values['event']['is_monetary']) {
      // build amount only when needed, skip incase of event full and waitlisting is enabled
      // and few other conditions check preProcess()
      if (!$this->isSuppressPayment()) {
        $this->buildAmount();
      }
      if (!$this->showPaymentOnConfirm) {
        CRM_Core_Payment_ProcessorForm::buildQuickForm($this);
        $this->addPaymentProcessorFieldsToForm();
      }
    }
    $isSelectContactID = ($contactID === 0 && !$this->_values['event']['is_multiple_registrations']);
    $this->assign('nocid', $isSelectContactID);
    if ($isSelectContactID) {
      //@todo we are blocking for multiple registrations because we haven't tested
      $this->addCIDZeroOptions();
    }
    $this->assign('priceSet', $this->_priceSet);
    $this->addElement('hidden', 'bypass_payment', NULL, ['id' => 'bypass_payment']);
    $this->assign('bypassPayment', $bypassPayment);

    if (!$contactID) {
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
      else {
        $this->assign('showCMS', FALSE);
      }
    }
    else {
      $this->assign('showCMS', FALSE);
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
      $buttonParams = [
        'type' => 'upload',
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,
      ];
      if (!$this->_values['event']['is_monetary'] && !$this->_values['event']['is_confirm_enabled']) {
        $buttonParams['name'] = ts('Register');
      }
      else {
        $buttonParams['name'] = ts('Review');
        $buttonParams['icon'] = 'fa-chevron-right';
      }

      $this->addButtons([$buttonParams]);
    }

    $this->addFormRule(['CRM_Event_Form_Registration_Register', 'formRule'], $this);
    $this->unsavedChangesWarn = TRUE;

    // add pcp fields
    if ($this->_pcpId) {
      CRM_PCP_BAO_PCP::buildPcp($this->_pcpId, $this);
    }
    else {
      $this->assign('pcp', FALSE);
    }
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
    $spacesAvailable = $form->getEventValue('available_spaces');
    //check for availability of registrations.
    if ($form->getEventValue('max_participants') !== NULL
      && !$form->_allowConfirmation
      && !empty($fields['additional_participants'])
      && empty($fields['bypass_payment']) &&
      ((int) $fields['additional_participants']) >= $spacesAvailable
    ) {
      $errors['additional_participants'] = ts("There is only enough space left on this event for %1 participant(s).", [1 => $spacesAvailable]);
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
      $errors['additional_participants'] = ts("It looks like you are trying to increase the number of additional people you are registering for. You can confirm registration for a maximum of %1 additional people.", [1 => count($form->_additionalParticipantIds)]);
    }

    //don't allow to register w/ waiting if enough spaces available.
    // @todo - this might not be working too well cos bypass_payment is working over time here.
    // it will always be true if there is no payment on the form & it's a bit hard
    // to determine if this is and we don't want people trying to confirm registration to be blocked here
    /// see https://lab.civicrm.org/dev/core/-/issues/5168
    if ($form->getPriceSetID() && !empty($fields['bypass_payment']) && $form->_allowConfirmation) {
      if ($spacesAvailable === 0 ||
        (empty($fields['priceSetId']) && ($fields['additional_participants'] ?? 0) < $spacesAvailable)
      ) {
        $errors['bypass_payment'] = ts("You have not been added to the waiting list because there are spaces available for this event. We recommend registering yourself for an available space instead.");
      }
    }

    // priceset validations
    if (!empty($fields['priceSetId']) &&
     !$form->_requireApproval && !$form->_allowWaitlist
     ) {
      //format params.
      $formatted = self::formatPriceSetParams($form, $fields);
      $ppParams = [$formatted];
      $priceSetErrors = $form->validatePriceSet($ppParams, $fields['priceSetId'], $form->get('priceSet'));
      $primaryParticipantCount = $form->getParticipantCount($ppParams);

      //get price set fields errors in.
      $errors = array_merge($errors, $priceSetErrors[0] ?? []);

      $totalParticipants = $primaryParticipantCount;
      if ($numberAdditionalParticipants) {
        $totalParticipants += $numberAdditionalParticipants;
      }

      if ($form->getEventValue('max_participants') !== NULL && empty($fields['bypass_payment']) &&
        !$form->_allowConfirmation &&
        $spacesAvailable < $totalParticipants
      ) {
        $errors['_qf_default'] = ts("Only %1 Registrations available.", [1 => $spacesAvailable]);
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
      $greetingType = $fields[$greeting] ?? NULL;
      if ($greetingType) {
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
        if (!$form->showPaymentOnConfirm) {
          $errors['payment_processor_id'] = ts('Please select a Payment Method');
        }
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

      if (!$form->showPaymentOnConfirm) {
        CRM_Core_Payment_Form::validatePaymentInstrument(
          $fields['payment_processor_id'],
          $fields,
          $errors,
          (!$form->_isBillingAddressRequiredForPayLater ? NULL : 'billing')
        );
      }
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
    $this->_params['is_pay_later'] = $params['is_pay_later'] ?? FALSE;
    $this->assign('is_pay_later', $params['is_pay_later']);
    $this->assign('pay_later_text', $params['is_pay_later'] ? $this->_values['event']['pay_later_text'] : NULL);
    $this->assign('pay_later_receipt', $params['is_pay_later'] ? $this->_values['event']['pay_later_receipt'] : NULL);

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
      $params['campaign_id'] = $this->_values['event']['campaign_id'] ?? NULL;
    }

    //hack to allow group to register w/ waiting
    $primaryParticipantCount = $this->getParticipantCount($params);

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
        // We would wind up here if waitlisting - in which case there should be no amount set.
        if (!empty($params['amount'])) {
          CRM_Core_Error::deprecatedWarning('unreachable code price set is always set here - passed as a hidden field although we could just load...');
          $params['amount'] = $this->_values['fee'][$params['amount']]['value'];
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

      $this->set('amount', $params['amount'] ?? 0);
      $this->set('amount_level', $params['amount_level']);

      // generate and set an invoiceID for this transaction
      $invoiceID = bin2hex(random_bytes(16));
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
      ($form->getPaymentMode() === 'test' || $form->_allowConfirmation)
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
            if ($registrationType == 'waitlisted') {
              $status = ts("It looks like you are already waitlisted for this event. If you want to change your registration, or you feel that you've received this message in error, please contact the site administrator.");
            }
            else {
              $status = ts("It looks like you are already registered for this event. If you want to change your registration, or you feel that you've received this message in error, please contact the site administrator.");
            }
            $status .= ' ' . ts('You can also <a href="%1">register another participant</a>.', [1 => $registerUrl]);
            CRM_Core_Session::singleton()->setStatus($status, '', 'alert');
            // @todo - pass cid=0 in the url & remove noFullMsg here.
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
            CRM_Core_Session::singleton()->setStatus($status, '', 'alert');
            return $participant->id;
          }
        }
      }
    }
  }

  /**
   * Is it appropriate to suppress the payment elements on the form.
   *
   * We hide the price and payment fields if the event is full or requires approval,
   * and the current user has not yet been approved CRM-12279
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  private function isSuppressPayment(): bool {
    if (!$this->getPriceSetID()) {
      return TRUE;
    }
    if ($this->_allowConfirmation) {
      // They might be paying for a now-confirmed registration.
      return FALSE;
    }
    if ($this->getSubmittedValue('bypass_payment')) {
      // Value set by javascript on the form.
      return TRUE;
    }
    return $this->isEventFull() || $this->_requireApproval;
  }

}
