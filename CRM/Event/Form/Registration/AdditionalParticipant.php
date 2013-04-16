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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for processing Event
 *
 */
class CRM_Event_Form_Registration_AdditionalParticipant extends CRM_Event_Form_Registration {

  /**
   * The defaults involved in this page
   *
   */
  public $_defaults = array();

  /**
   * pre-registered additional participant id.
   *
   */
  public $additionalParticipantId = NULL;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preProcess();

    $participantNo = substr($this->_name, 12);

    //lets process in-queue participants.
    if ($this->_participantId && $this->_additionalParticipantIds) {
      $this->_additionalParticipantId = CRM_Utils_Array::value($participantNo, $this->_additionalParticipantIds);
    }

    $participantCnt = $participantNo + 1;
    $this->assign('formId', $participantNo);
    $this->_params = array();
    $this->_params = $this->get('params');

    $participantTot = $this->_params[0]['additional_participants'] + 1;
    $skipCount = count(array_keys($this->_params, "skip"));
    if ($skipCount) {
      $this->assign('skipCount', $skipCount);
    }
    CRM_Utils_System::setTitle(ts('Register Participant %1 of %2', array(1 => $participantCnt, 2 => $participantTot)));

    //CRM-4320, hack to check last participant.
    $this->_lastParticipant = FALSE;
    if ($participantTot == $participantCnt) {
      $this->_lastParticipant = TRUE;
    }
    $this->assign('lastParticipant', $this->_lastParticipant);
  }

  /**
   * This function sets the default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $defaults = $unsetSubmittedOptions = array();
    $discountId = NULL;
    //fix for CRM-3088, default value for discount set.
    if (!empty($this->_values['discount'])) {
      $discountId = CRM_Core_BAO_Discount::findSet($this->_eventId, 'civicrm_event');
      if ($discountId && CRM_Utils_Array::value('default_discount_fee_id', $this->_values['event'])) {
        $discountKey = CRM_Core_DAO::getFieldValue("CRM_Core_DAO_OptionValue", $this->_values['event']['default_discount_fee_id']
          , 'weight', 'id'
        );
        $defaults['amount'] = key(array_slice($this->_values['discount'][$discountId], $discountKey - 1, $discountKey, TRUE));
      }
    }
    if ($this->_priceSetId) {
      foreach ($this->_feeBlock as $key => $val) {
        if (!CRM_Utils_Array::value('options', $val)) {
          continue;
        }

        $optionsFull = CRM_Utils_Array::value('option_full_ids', $val, array());
        foreach ($val['options'] as $keys => $values) {
          if ($values['is_default'] && !in_array($keys, $optionsFull)) {
            if ($val['html_type'] == 'CheckBox') {
              $defaults["price_{$key}"][$keys] = 1;
            }
            else {
              $defaults["price_{$key}"] = $keys;
            }
          }
        }
        if (!empty($optionsFull)) {
          $unsetSubmittedOptions[$val['id']] = $optionsFull;
        }
      }
    }

    //CRM-4320, setdefault additional participant values.
    if ($this->_allowConfirmation && $this->_additionalParticipantId) {
      //hack to get set default from eventFees.php
      $this->_discountId   = $discountId;
      $this->_pId          = $this->_additionalParticipantId;
      $this->_contactId    = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $this->_additionalParticipantId, 'contact_id');
      $participantDefaults = CRM_Event_Form_EventFees::setDefaultValues($this);
      $participantDefaults = array_merge($this->_defaults, $participantDefaults);
      // use primary email address if billing email address is empty
      if (empty($this->_defaults["email-{$this->_bltID}"]) &&
        !empty($this->_defaults["email-Primary"])
      ) {
        $participantDefaults["email-{$this->_bltID}"] = $this->_defaults["email-Primary"];
      }
      $defaults = array_merge($defaults, $participantDefaults);
    }

    $defaults = array_merge($this->_defaults, $defaults);

    //reset values for all options those are full.
    if (!empty($unsetSubmittedOptions) && empty($_POST)) {
      $this->resetElementValue($unsetSubmittedOptions);
    }

    //load default campaign from page.
    if (array_key_exists('participant_campaign_id', $this->_fields)) {
      $defaults['participant_campaign_id'] = CRM_Utils_Array::value('campaign_id', $this->_values['event']);
    }
    CRM_Core_BAO_Address::fixAllStateSelects($this, $this->_defaults);

    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $config = CRM_Core_Config::singleton();
    $button = substr($this->controller->getButtonName(), -4);

    $this->add('hidden', 'scriptFee', NULL);
    $this->add('hidden', 'scriptArray', NULL);

    if ($this->_values['event']['is_monetary']) {
      CRM_Event_Form_Registration_Register::buildAmount($this);
    }
    $first_name = $last_name = NULL;
    $pre = $post = array();
    foreach (array(
      'pre', 'post') as $keys) {
      if (isset($this->_values['additional_custom_' . $keys . '_id'])) {
        $this->buildCustom($this->_values['additional_custom_' . $keys . '_id'], 'additionalCustom' . ucfirst($keys), TRUE);
        $$keys = CRM_Core_BAO_UFGroup::getFields($this->_values['additional_custom_' . $keys . '_id']);
      }
      foreach (array(
        'first_name', 'last_name') as $name) {
        if (CRM_Utils_Array::value($name, $$keys) &&
          CRM_Utils_Array::value('is_required', CRM_Utils_Array::value($name, $$keys))
        ) {
          $$name = 1;
        }
      }
    }

    $required = ($button == 'skip' ||
      $this->_values['event']['allow_same_participant_emails'] == 1 &&
      ($first_name && $last_name)
    ) ? FALSE : TRUE;

    //add buttons
    $js = NULL;
    if ($this->isLastParticipant(TRUE) && !CRM_Utils_Array::value('is_monetary', $this->_values['event'])) {
      $js = array('onclick' => "return submitOnce(this,'" . $this->_name . "','" . ts('Processing') . "');");
    }

    //handle case where user might sart with waiting by group
    //registration and skip some people and now group fit to
    //become registered so need to take payment from user.
    //this case only occurs at dynamic waiting status, CRM-4320
    $statusMessage = NULL;
    $allowToProceed = TRUE;
    $includeSkipButton = TRUE;
    $this->_resetAllowWaitlist = FALSE;

    $pricesetFieldsCount = CRM_Price_BAO_Set::getPricesetCount($this->_priceSetId);

    if ($this->_lastParticipant || $pricesetFieldsCount) {
      //get the participant total.
      $processedCnt = self::getParticipantCount($this, $this->_params, TRUE);
    }

    if (!$this->_allowConfirmation &&
      CRM_Utils_Array::value('bypass_payment', $this->_params[0]) &&
      $this->_lastParticipant
    ) {

      //get the event spaces.
      $spaces = $this->_availableRegistrations;

      $currentPageMaxCount = 1;
      if ($pricesetFieldsCount) {
        $currentPageMaxCount = $pricesetFieldsCount;
      }

      //we might did reset allow waiting in case of dynamic calculation
      if (CRM_Utils_Array::value('bypass_payment', $this->_params[0]) &&
        is_numeric($spaces) &&
        $processedCnt > $spaces
      ) {
        $this->_allowWaitlist = TRUE;
        $this->set('allowWaitlist', TRUE);
      }

      //lets allow to become a part of runtime waiting list, if primary selected pay later.
      $realPayLater = FALSE;
      if (CRM_Utils_Array::value('is_monetary', $this->_values['event']) &&
        CRM_Utils_Array::value('is_pay_later', $this->_values['event'])
      ) {
        $realPayLater = CRM_Utils_Array::value('is_pay_later', $this->_params[0]);
      }

      //truly spaces are greater than required.
      if (is_numeric($spaces) && $spaces >= ($processedCnt + $currentPageMaxCount)) {
        if (CRM_Utils_Array::value('amount', $this->_params[0], 0) == 0 || $this->_requireApproval) {
          $this->_allowWaitlist = FALSE;
          $this->set('allowWaitlist', $this->_allowWaitlist);
          if ($this->_requireApproval) {
            $statusMessage = ts("It looks like you are now registering a group of %1 participants. The event has %2 available spaces (you will not be wait listed). Registration for this event requires approval. You will receive an email once your registration has been reviewed.", array(1 => ++$processedCnt, 2 => $spaces));
          }
          else {
            $statusMessage = ts("It looks like you are now registering a group of %1 participants. The event has %2 available spaces (you will not be wait listed).", array(1 => ++$processedCnt, 2 => $spaces));
          }
        }
        else {
          $statusMessage = ts("It looks like you are now registering a group of %1 participants. The event has %2 available spaces (you will not be wait listed). Please go back to the main registration page and reduce the number of additional people. You will also need to complete payment information.", array(1 => ++$processedCnt, 2 => $spaces));
          $allowToProceed = FALSE;
        }
        CRM_Core_Session::setStatus($statusMessage, ts('Registration Error'), 'error');
      }
      elseif ($processedCnt == $spaces) {
        if (CRM_Utils_Array::value('amount', $this->_params[0], 0) == 0
          || $realPayLater || $this->_requireApproval
        ) {
          $this->_resetAllowWaitlist = TRUE;
          if ($this->_requireApproval) {
            $statusMessage = ts("If you skip this participant there will be enough spaces in the event for your group (you will not be wait listed). Registration for this event requires approval. You will receive an email once your registration has been reviewed.");
          }
          else {
            $statusMessage = ts("If you skip this participant there will be enough spaces in the event for your group (you will not be wait listed).");
          }
        }
        else {
          //hey there is enough space and we require payment.
          $statusMessage = ts("If you skip this participant there will be enough spaces in the event for your group (you will not be wait listed). Please go back to the main registration page and reduce the number of additional people. You will also need to complete payment information.");
          $includeSkipButton = FALSE;
        }
      }
    }

    // for priceset with count
    if ($pricesetFieldsCount &&
      CRM_Utils_Array::value('has_waitlist', $this->_values['event']) &&
      !$this->_allowConfirmation
    ) {

      if ($this->_isEventFull) {
        $statusMessage = ts('This event is currently full. You are registering for the waiting list. You will be notified if spaces become available.');
      }
      elseif ($this->_allowWaitlist ||
        (!$this->_allowWaitlist && ($processedCnt + $pricesetFieldsCount) > $this->_availableRegistrations)
      ) {

        $waitingMsg = ts('It looks like you are registering more participants then there are spaces available. All participants will be added to the waiting list. You will be notified if spaces become available.');
        $confirmedMsg = ts('It looks like there are enough spaces in this event for your group (you will not be wait listed).');
        if ($this->_requireApproval) {
          $waitingMsg = ts('It looks like you are now registering a group of %1 participants. The event has %2 available spaces (you will not be wait listed). Registration for this event requires approval. You will receive an email once your registration has been reviewed.');
          $confirmedMsg = ts('It looks there are enough spaces in this event for your group (you will not be wait listed). Registration for this event requires approval. You will receive an email once your registration has been reviewed.');
        }

        $this->assign('waitingMsg', $waitingMsg);
        $this->assign('confirmedMsg', $confirmedMsg);

        $this->assign('availableRegistrations', $this->_availableRegistrations);
        $this->assign('currentParticipantCount', $processedCnt);
        $this->assign('allowGroupOnWaitlist', TRUE);

        $paymentBypassed = NULL;
        if (CRM_Utils_Array::value('bypass_payment', $this->_params[0]) &&
          !$this->_allowWaitlist &&
          !$realPayLater &&
          !$this->_requireApproval &&
          !(CRM_Utils_Array::value('amount', $this->_params[0], 0) == 0)
        ) {
          $paymentBypassed = ts('Please go back to the main registration page, to complete payment information.');
        }
        $this->assign('paymentBypassed', $paymentBypassed);
      }
    }

    $this->assign('statusMessage', $statusMessage);

    $buttons = array(
      array('type' => 'back',
        'name' => ts('<< Go Back'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp',
      ),
    );

    //CRM-4320
    if ($allowToProceed) {
      $buttons = array_merge($buttons, array(
        array('type' => 'next',
            'name' => ts('Continue >>'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
            'js' => $js,
          ),
        )
      );
      if ($includeSkipButton) {
        $buttons = array_merge($buttons, array(
          array('type' => 'next',
              'name' => ts('Skip Participant >>|'),
              'subName' => 'skip',
            ),
          )
        );
      }
    }
    $this->addButtons($buttons);
    $this->addFormRule(array('CRM_Event_Form_Registration_AdditionalParticipant', 'formRule'), $this);
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $self) {
    $errors = array();
    //get the button name.
    $button = substr($self->controller->getButtonName(), -4);

    $realPayLater = FALSE;
    if (CRM_Utils_Array::value('is_monetary', $self->_values['event']) &&
      CRM_Utils_Array::value('is_pay_later', $self->_values['event'])
    ) {
      $realPayLater = CRM_Utils_Array::value('is_pay_later', $self->_params[0]);
    }

    if ($button != 'skip') {
      //Check that either an email or firstname+lastname is included in the form(CRM-9587)
      CRM_Event_Form_Registration_Register::checkProfileComplete($fields, $errors, $self->_eventId);

      //Additional Participant can also register for an event only once
      $isRegistered = CRM_Event_Form_Registration_Register::checkRegistration($fields, $self, TRUE);
      if ($isRegistered) {
        if ($self->_values['event']['allow_same_participant_emails']) {
          $errors['_qf_default'] = ts('A person is already registered for this event.');
        }
        else {
          $errors["email-{$self->_bltID}"] = ts('A person with this email address is already registered for this event.');
        }
      }

      //get the complete params.
      $params = $self->get('params');

      //take the participant instance.
      $addParticipantNum = substr($self->_name, 12);

      if (is_array($params)) {
        foreach ($params as $key => $value) {
          if ($key != $addParticipantNum) {
            if (!$self->_values['event']['allow_same_participant_emails']) {
              //collect all email fields
              $existingEmails = array();
              $additionalParticipantEmails = array();
              if (is_array($value)) {
              foreach ($value as $key => $val) {
                if (substr($key, 0, 6) == 'email-' && $val) {
                  $existingEmails[] = $val;
                }
              }
              }
              foreach ($fields as $key => $val) {
                if (substr($key, 0, 6) == 'email-' && $val) {
                  $additionalParticipantEmails[] = $val;
                  $mailKey = $key;
                }
              }
              //check if any emails are common to both arrays
              if (count(array_intersect($existingEmails, $additionalParticipantEmails))) {
                $errors[$mailKey] = ts('The email address must be unique for each participant.');
                break;
              }
            }
            else {
              // check with first_name and last_name for additional participants
              if (!empty($value['first_name']) && ($value['first_name'] == CRM_Utils_Array::value('first_name', $fields)) &&
                (CRM_Utils_Array::value('last_name',$value) == CRM_Utils_Array::value('last_name', $fields))
              ) {
                $errors['first_name'] = ts('The first name and last name must be unique for each participant.');
                break;
              }
            }
          }
        }
      }

      //check for atleast one pricefields should be selected
      if (CRM_Utils_Array::value('priceSetId', $fields)) {
        $allParticipantParams = $params;

        //format current participant params.
        $allParticipantParams[$addParticipantNum] = self::formatPriceSetParams($self, $fields);
        $totalParticipants = self::getParticipantCount($self, $allParticipantParams);

        //validate price field params.
        $priceSetErrors = self::validatePriceSet($self, $allParticipantParams);
        $errors = array_merge($errors, CRM_Utils_Array::value($addParticipantNum, $priceSetErrors, array()));

        if (!$self->_allowConfirmation &&
          is_numeric($self->_availableRegistrations)
        ) {
          if (CRM_Utils_Array::value('bypass_payment', $self->_params[0]) &&
            !$self->_allowWaitlist &&
            !$realPayLater &&
            !$self->_requireApproval &&
            !(CRM_Utils_Array::value('amount', $self->_params[0], 0) == 0) &&
            $totalParticipants < $self->_availableRegistrations
          ) {
            $errors['_qf_default'] = ts("Your event registration will be confirmed. Please go back to the main registration page, to complete payment information.");
          }
          //check for availability of registrations.
          if (!$self->_allowConfirmation &&
            !CRM_Utils_Array::value('has_waitlist', $self->_values['event']) &&
            $totalParticipants > $self->_availableRegistrations
          ) {
            $errors['_qf_default'] = ts('It looks like event has only %2 seats available and you are trying to register %1 participants, so could you please select price options accordingly.', array(1 => $totalParticipants, 2 => $self->_availableRegistrations));
          }
        }
      }
    }

    if ($button == 'skip' && $self->_lastParticipant && CRM_Utils_Array::value('priceSetId', $fields)) {
      $pricesetFieldsCount = CRM_Price_BAO_Set::getPricesetCount($fields['priceSetId']);
      if (($pricesetFieldsCount < 1) || $self->_allowConfirmation) {
        return $errors;
      }

      if (CRM_Utils_Array::value('has_waitlist', $self->_values['event']) &&
        CRM_Utils_Array::value('bypass_payment', $self->_params[0]) &&
        !$self->_allowWaitlist &&
        !$realPayLater &&
        !$self->_requireApproval &&
        !(CRM_Utils_Array::value('amount', $self->_params[0], 0) == 0)
      ) {
        $errors['_qf_default'] = ts("You are going to skip the last participant, your event registration will be confirmed. Please go back to the main registration page, to complete payment information.");
      }
    }


    if ($button != 'skip' &&
      $self->_values['event']['is_monetary'] &&
      !isset($errors['_qf_default']) &&
      !$self->validatePaymentValues($self, $fields)
    ) {
      $errors['_qf_default'] = ts("Your payment information looks incomplete. Please go back to the main registration page, to complete payment information.");
      $self->set('forcePayement', TRUE);
    }
    elseif ($button == 'skip') {
      $self->set('forcePayement', TRUE);
    }

    return $errors;
  }

  function validatePaymentValues($self, $fields) {

    if (CRM_Utils_Array::value('bypass_payment', $self->_params[0]) ||
      $self->_allowWaitlist ||
      empty($self->_fields) ||
      CRM_Utils_Array::value('amount', $self->_params[0]) > 0
    ) {
      return TRUE;
    }

    $validatePayement = FALSE;
    if (CRM_Utils_Array::value('priceSetId', $fields)) {
      $lineItem = array();
      CRM_Price_BAO_Set::processAmount($self->_values['fee'], $fields, $lineItem);
      if ($fields['amount'] > 0) {
        $validatePayement = TRUE;
        // $self->_forcePayement = true;
        // return false;
      }
    }
    elseif (CRM_Utils_Array::value('amount', $fields) &&
      (CRM_Utils_Array::value('value', $self->_values['fee'][$fields['amount']]) > 0)
    ) {
      $validatePayement = TRUE;
    }

    if (!$validatePayement) {
      return TRUE;
    }

    $errors = array();

    CRM_Core_Form::validateMandatoryFields($self->_fields, $fields, $errors);

    // make sure that credit card number and cvv are valid
    CRM_Core_Payment_Form::validateCreditCard($self->_params[0], $errors);

    if ($errors) {
      return FALSE;
    }

    foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
      if ($greetingType = CRM_Utils_Array::value($greeting, $self->_params[0])) {
        $customizedValue = CRM_Core_OptionGroup::getValue($greeting, 'Customized', 'name');
        if ($customizedValue == $greetingType && empty($self->_params[0][$greeting . '_custom'])) {
          return FALSE;
        }
      }
    }
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    //get the button name.
    $button = substr($this->controller->getButtonName(), -4);

    //take the participant instance.
    $addParticipantNum = substr($this->_name, 12);

    //user submitted params.
    $params = $this->controller->exportValues($this->_name);

    if (!$this->_allowConfirmation) {
      // check if the participant is already registered
      $params['contact_id'] = CRM_Event_Form_Registration_Register::checkRegistration($params, $this, TRUE, TRUE);
    }

    //carry campaign to partcipants.
    if (array_key_exists('participant_campaign_id', $params)) {
      $params['campaign_id'] = $params['participant_campaign_id'];
    }
    else {
      $params['campaign_id'] = CRM_Utils_Array::value('campaign_id', $this->_values['event']);
    }

    // if waiting is enabled
    if (!$this->_allowConfirmation &&
      is_numeric($this->_availableRegistrations)
    ) {
      $this->_allowWaitlist = FALSE;
      //get the current page count.
      $currentCount = self::getParticipantCount($this, $params);
      if ($button == 'skip') {
        $currentCount = 'skip';
      }

      //get the total count.
      $previousCount = self::getParticipantCount($this, $this->_params, TRUE);
      $totalParticipants = $previousCount;
      if (is_numeric($currentCount)) {
        $totalParticipants += $currentCount;
      }
      if (CRM_Utils_Array::value('has_waitlist', $this->_values['event']) &&
        $totalParticipants > $this->_availableRegistrations
      ) {
        $this->_allowWaitlist = TRUE;
      }
      $this->set('allowWaitlist', $this->_allowWaitlist);
      $this->_lineItemParticipantsCount[$addParticipantNum] = $currentCount;
    }

    if ($button == 'skip') {
      //hack for free/zero amount event.
      if ($this->_resetAllowWaitlist) {
        $this->_allowWaitlist = FALSE;
        $this->set('allowWaitlist', FALSE);
        if ($this->_requireApproval) {
          $status = ts("You have skipped last participant and which result into event having enough spaces, but your registration require approval, Once your registration has been reviewed, you will receive an email with a link to a web page where you can complete the registration process.");
        }
        else {
          $status = ts("You have skipped last participant and which result into event having enough spaces, hence your group become as register participants though you selected on wait list.");
        }
        CRM_Core_Session::setStatus($status);
      }

      $this->_params[$addParticipantNum] = 'skip';
      if (isset($this->_lineItem)) {
        $this->_lineItem[$addParticipantNum] = 'skip';
        $this->_lineItemParticipantsCount[$addParticipantNum] = 'skip';
      }
    }
    else {

      $config = CRM_Core_Config::singleton();
      $params['currencyID'] = $config->defaultCurrency;

      if ($this->_values['event']['is_monetary']) {

        //added for discount
        $discountId = CRM_Core_BAO_Discount::findSet($this->_eventId, 'civicrm_event');

        if (!empty($this->_values['discount'][$discountId])) {
          $params['discount_id'] = $discountId;
          $params['amount_level'] = $this->_values['discount'][$discountId][$params['amount']]['label'];
          $params['amount'] = $this->_values['discount'][$discountId][$params['amount']]['value'];
        }
        elseif (empty($params['priceSetId'])) {
          $params['amount_level'] = $this->_values['fee'][$params['amount']]['label'];
          $params['amount'] = $this->_values['fee'][$params['amount']]['value'];
        }
        else {
          $lineItem = array();
          CRM_Price_BAO_Set::processAmount($this->_values['fee'], $params, $lineItem);

          //build the line item..
          if (array_key_exists($addParticipantNum, $this->_lineItem)) {
            $this->_lineItem[$addParticipantNum] = $lineItem;
          }
          else {
            $this->_lineItem[] = $lineItem;
          }
        }
      }

      if (array_key_exists('participant_role', $params)) {
        $params['participant_role_id'] = $params['participant_role'];
      }

      if (!CRM_Utils_Array::value('participant_role_id', $params) && $this->_values['event']['default_role_id']) {
        $params['participant_role_id'] = $this->_values['event']['default_role_id'];
      }

      if (CRM_Utils_Array::value('is_pay_later', $this->_params[0])) {
        $params['is_pay_later'] = 1;
      }

      //carry additional participant id, contact id if pre-registered.
      if ($this->_allowConfirmation && $this->_additionalParticipantId) {
        $params['contact_id'] = $this->_contactId;
        $params['participant_id'] = $this->_additionalParticipantId;
      }

      //build the params array.
      if (array_key_exists($addParticipantNum, $this->_params)) {
        $this->_params[$addParticipantNum] = $params;
      }
      else {
        $this->_params[] = $params;
      }
    }

    //finally set the params.
    $this->set('params', $this->_params);
    //set the line item.
    if ($this->_lineItem) {
      $this->set('lineItem', $this->_lineItem);
      $this->set('lineItemParticipantsCount', $this->_lineItemParticipantsCount);
    }

    $participantNo = count($this->_params);
    if ($button != 'skip') {
      $statusMsg = ts('Registration information for participant %1 has been saved.', array(1 => $participantNo));
      CRM_Core_Session::setStatus($statusMsg, ts('Registration Saved'), 'success');
    }

    //to check whether call processRegistration()
    if (!$this->_values['event']['is_monetary']
      && CRM_Utils_Array::value('additional_participants', $this->_params[0])
      && $this->isLastParticipant()
    ) {
      CRM_Event_Form_Registration_Register::processRegistration($this->_params, NULL);
    }
  }

  function &getPages($additionalParticipant) {
    $details = array();
    for ($i = 1; $i <= $additionalParticipant; $i++) {
      $details["Participant_{$i}"] = array(
        'className' => 'CRM_Event_Form_Registration_AdditionalParticipant',
        'title' => "Register Additional Participant {$i}",
      );
    }
    return $details;
  }

  /**
   * check whether call current participant is last one
   *
   * @return boolean ture on success.
   * @access public
   */
  function isLastParticipant($isButtonJs = FALSE) {
    $participant = $isButtonJs ? $this->_params[0]['additional_participants'] : $this->_params[0]['additional_participants'] + 1;
    if (count($this->_params) == $participant) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Reset values for all options those are full.
   *
   **/
  function resetElementValue($optionFullIds = array(
    )) {
    if (!is_array($optionFullIds) ||
      empty($optionFullIds) ||
      !$this->isSubmitted()
    ) {
      return;
    }

    foreach ($optionFullIds as $fldId => $optIds) {
      $name = "price_$fldId";
      if (!$this->elementExists($name)) {
        continue;
      }

      $element = $this->getElement($name);
      $eleType = $element->getType();

      $resetSubmitted = FALSE;
      switch ($eleType) {
        case 'text':
          if ($element->isFrozen()) {
            $element->setValue('');
            $resetSubmitted = TRUE;
          }
          break;

        case 'group':
          if (is_array($element->_elements)) {
            foreach ($element->_elements as $child) {
              $childType = $child->getType();
              $methodName = 'getName';
              if ($childType) {
                $methodName = 'getValue';
              }
              if (in_array($child->{$methodName}(), $optIds) && $child->isFrozen()) {
                $resetSubmitted = TRUE;
                $child->updateAttributes(array('checked' => NULL));
              }
            }
          }
          break;

        case 'select':
          $resetSubmitted = TRUE;
          $element->_values = array();
          break;
      }

      //finally unset values from submitted.
      if ($resetSubmitted) {
        $this->resetSubmittedValue($name, $optIds);
      }
    }
  }

  function resetSubmittedValue($elementName, $optionIds = array(
    )) {
    if (empty($elementName) ||
      !$this->elementExists($elementName) ||
      !$this->getSubmitValue($elementName)
    ) {
      return;
    }
    foreach (array(
      'constantValues', 'submitValues', 'defaultValues') as $val) {
      $values = &$this->{"_$val"};
      if (!is_array($values) || empty($values)) {
        continue;
      }
      $eleVal = CRM_Utils_Array::value($elementName, $values);
      if (empty($eleVal)) {
        continue;
      }
      if (is_array($eleVal)) {
        $found = FALSE;
        foreach ($eleVal as $keyId => $ignore) {
          if (in_array($keyId, $optionIds)) {
            $found = TRUE;
            unset($values[$elementName][$keyId]);
          }
        }
        if ($found && empty($values[$elementName][$keyId])) {
          $values[$elementName][$keyId] = NULL;
        }
      }
      else {
        $values[$elementName][$keyId] = NULL;
      }
    }
  }
}

