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
use Civi\Api4\Activity;
use Civi\API\EntityLookupTrait;
use Civi\Api4\Phone;
use Civi\Token\TokenProcessor;

/**
 * This trait provides the common functionality for tasks that send sms.
 */
trait CRM_Contact_Form_Task_SMSTrait {
  use EntityLookupTrait;

  /**
   * The phones to be messaged.
   * @var array
   */
  private array $phones;

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    $this->postProcessSms();
  }

  /**
   */
  protected function filterContactIDs(): void {
    // Activity sub class does this.
  }

  /**
   * Get SMS provider parameters.
   *
   * @return array
   */
  protected function getSmsProviderParams(): array {
    // $smsParams carries all the arguments provided on form (or via hooks), to the provider->send() method
    // this gives flexibility to the users / implementors to add their own args via hooks specific to their sms providers
    $smsProviderParams = $this->getSubmittedValues();
    unset($smsProviderParams['sms_text_message']);
    $smsProviderParams['provider_id'] = $this->getSubmittedValue('sms_provider_id');
    return $smsProviderParams;
  }

  /**
   * Get phones to SMS.
   *
   * @internal
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function getPhones(): array {
    if (!isset($this->phones)) {
      $this->phones = (array) Phone::get()
        ->addWhere('contact_id', 'IN', $this->getContactIDs())
        ->addWhere('contact_id.do_not_sms', '=', FALSE)
        ->addWhere('contact_id.is_deceased', '=', FALSE)
        ->addWhere('phone_numeric', '>', 0)
        ->addWhere('phone_type_id:name', '=', 'Mobile')
        ->addOrderBy('is_primary')
        ->addSelect('id', 'contact_id', 'phone', 'phone_type_id:name', 'contact_id.sort_name', 'phone_type_id', 'contact_id.display_name')
        ->execute()->indexBy('contact_id');
    }
    return $this->phones;
  }

  protected function bounceOnNoActiveProviders(): void {
    $providersCount = CRM_SMS_BAO_Provider::activeProviderCount();
    if (!$providersCount) {
      CRM_Core_Error::statusBounce(ts('There are no SMS providers configured, or no SMS providers are set active'));
    }
  }

  /**
   * Build the SMS Form
   *
   * @throws \CRM_Core_Exception
   * @internal - highly likely to change!
   */
  protected function buildSmsForm(): void {
    if (!CRM_Core_Permission::check('send SMS')) {
      throw new CRM_Core_Exception("You do not have the 'send SMS' permission");
    }
    $form = $this;
    $toArray = [];

    $providers = CRM_SMS_BAO_Provider::getProviders(NULL, NULL, TRUE, 'is_default desc');

    $providerSelect = [];
    foreach ($providers as $provider) {
      $providerSelect[$provider['id']] = $provider['title'];
    }
    $suppressedSms = 0;
    //here we are getting logged in user id as array but we need target contact id. CRM-5988
    $cid = $form->get('cid');

    if ($cid) {
      $form->_contactIds = [$cid];
    }

    $to = $form->add('text', 'to', ts('To'), ['class' => 'huge'], TRUE);
    $form->add('text', 'activity_subject', ts('Name The SMS'), ['class' => 'huge'], TRUE);

    $toSetDefault = TRUE;
    if (property_exists($form, '_context') && $form->_context == 'standalone') {
      $toSetDefault = FALSE;
    }

    // when form is submitted recompute contactIds
    $allToSMS = [];
    if ($this->getSubmittedValue('to')) {
      $allToPhone = explode(',', $to->getValue());

      $form->_contactIds = [];
      foreach ($allToPhone as $value) {
        [$contactId, $phone] = explode('::', $value);
        if ($contactId) {
          $form->_contactIds[] = $contactId;
        }
      }
      $toSetDefault = TRUE;
    }

    //get the group of contacts as per selected by user in case of Find Activities
    $this->filterContactIDs();

    if (is_array($form->_contactIds) && !empty($form->_contactIds) && $toSetDefault) {
      $form->_contactDetails = civicrm_api3('Contact', 'get', [
        'id' => ['IN' => $form->_contactIds],
        'return' => ['sort_name', 'phone', 'do_not_sms', 'is_deceased', 'display_name'],
        'options' => ['limit' => 0],
      ])['values'];

      // make a copy of all contact details
      $form->_allContactDetails = $form->_contactDetails;

      $phoneNumbers = $this->getPhones();
      $suppressedSms = count($this->getContactIDs()) - count($phoneNumbers);
      foreach ($phoneNumbers as $phone) {
        if ($this->isInvalidRecipient($phone['contact_id'])) {
          $suppressedSms++;
          continue;
        }
        // We hope to refactor this array away but for now...
        $form->_contactDetails[$phone['contact_id']] = [
          'id' => $phone['contact_id'],
          'contact_id' => $phone['contact_id'],
          'sort_name' => $phone['contact_id.sort_name'],
          'display_name' => $phone['contact_id.display_name'],
          // Might need to be set later - we know it is false here.
          'do_not_sms' => FALSE,
          'phone_id' => $phone['id'],
          'phone' => $phone['phone'],
          'phone_type_id' => $phone['phone_type_id'],
        ];

        if ($phone) {
          $toArray[] = [
            'text' => CRM_Utils_String::purifyHTML('"' . $phone['contact_id.sort_name'] . '" (' . $phone['phone'] . ')'),
            'id' => $phone['contact_id'] . '::' . CRM_Utils_String::purifyHTML($phone['phone']),
          ];
        }
      }

      if (empty($toArray)) {
        CRM_Core_Error::statusBounce(ts('Selected contact(s) do not have a valid Phone, or communication preferences specify DO NOT SMS, or they are deceased'));
      }
    }

    //activity related variables
    $form->addExpectedSmartyVariable('invalidActivity');
    $form->addExpectedSmartyVariable('extendTargetContacts');

    $form->assign('toContact', json_encode($toArray));
    $form->assign('suppressedSms', $suppressedSms);
    $form->assign('totalSelectedContacts', count($this->getContactIDs()));

    $form->add('select', 'sms_provider_id', ts('From'), $providerSelect, TRUE);

    CRM_Mailing_BAO_Mailing::commonCompose($form);

    if ($form->_single) {
      // also fix the user context stack
      if ($form->_context) {
        $url = CRM_Utils_System::url('civicrm/dashboard', 'reset=1');
      }
      else {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          "&show=1&action=browse&cid={$form->_contactIds[0]}&selectedChild=activity"
        );
      }

      $session = CRM_Core_Session::singleton();
      $session->replaceUserContext($url);
      $form->addDefaultButtons(ts('Send SMS'), 'upload', 'cancel');
    }
    else {
      $form->addDefaultButtons(ts('Send SMS'), 'upload');
    }

    $form->addFormRule([__CLASS__, 'formRuleSms'], $form);
  }

  /**
   * Process the sms form after the input has been submitted and validated.
   *
   * @internal likely to change.
   *
   * @throws \CRM_Core_Exception
   */
  protected function postProcessSms(): void {
    $form = $this;
    $thisValues = $form->controller->exportValues($form->getName());

    // process message template
    if (!empty($thisValues['SMSsaveTemplate']) || !empty($thisValues['SMSupdateTemplate'])) {
      $messageTemplate = [
        'msg_text' => $thisValues['sms_text_message'],
        'is_active' => TRUE,
        'is_sms' => TRUE,
      ];

      if (!empty($thisValues['SMSsaveTemplate'])) {
        $messageTemplate['msg_title'] = $thisValues['SMSsaveTemplateName'];
        CRM_Core_BAO_MessageTemplate::add($messageTemplate);
      }

      if (!empty($thisValues['SMStemplate']) && !empty($thisValues['SMSupdateTemplate'])) {
        $messageTemplate['id'] = $thisValues['SMStemplate'];
        unset($messageTemplate['msg_title']);
        CRM_Core_BAO_MessageTemplate::add($messageTemplate);
      }
    }

    // format contact details array to handle multiple sms from same contact
    $formattedContactDetails = [];
    $tempPhones = [];
    $phonesToSendTo = explode(',', $this->getSubmittedValue('to'));
    $contactIds = $phones = [];
    foreach ($phonesToSendTo as $phone) {
      [$contactId, $phone] = explode('::', $phone);
      if ($contactId) {
        $contactIds[] = $contactId;
        $phones[] = $phone;
      }
    }
    foreach ($contactIds as $key => $contactId) {
      $phone = $phones[$key];

      if ($phone) {
        $phoneKey = "{$contactId}::{$phone}";
        if (!in_array($phoneKey, $tempPhones)) {
          $tempPhones[] = $phoneKey;
          if (!empty($form->_contactDetails[$contactId])) {
            $formattedContactDetails[] = $form->_contactDetails[$contactId];
            $contactIds[] = $contactId;
          }
        }
      }
    }

    [$sent, $countSuccess] = $this->sendSMS($formattedContactDetails,
      $thisValues,
      $contactIds
    );

    if ($countSuccess > 0) {
      CRM_Core_Session::setStatus(ts('One message was sent successfully.', [
        'plural' => '%count messages were sent successfully.',
        'count' => $countSuccess,
      ]), ts('Message Sent', ['plural' => 'Messages Sent', 'count' => $countSuccess]), 'success');
    }

    if (is_array($sent)) {
      // At least one PEAR_Error object was generated.
      // Display the error messages to the user.
      $status = '<ul>';
      foreach ($sent as $errMsg) {
        $status .= '<li>' . $errMsg . '</li>';
      }
      $status .= '</ul>';
      CRM_Core_Session::setStatus($status, ts('One Message Not Sent', [
        'count' => count($sent),
        'plural' => '%count Messages Not Sent',
      ]), 'info');
    }
    else {
      //Display the name and number of contacts for those sms is not sent.
      $smsNotSent = array_diff_assoc($this->getContactIDs(), $contactIds);

      if (!empty($smsNotSent)) {
        $not_sent = [];
        foreach ($smsNotSent as $contactId) {
          $displayName = CRM_Utils_String::purifyHTML($this->getValueForContact($contactId, 'display_name'));
          $contactViewUrl = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=$contactId");
          $not_sent[] = "<a href='$contactViewUrl' title='$displayName'>$displayName</a>";
        }
        $status = '(' . ts('because no phone number on file or communication preferences specify DO NOT SMS or Contact is deceased');
        if (CRM_Utils_System::getClassName($form) == 'CRM_Activity_Form_Task_SMS') {
          $status .= ' ' . ts("or the contact is not part of the activity '%1'", [1 => $this->getActivityName()]);
        }
        $status .= ')<ul><li>' . implode('</li><li>', $not_sent) . '</li></ul>';
        CRM_Core_Session::setStatus($status, ts('One Message Not Sent', [
          'count' => count($smsNotSent),
          'plural' => '%count Messages Not Sent',
        ]), 'info');
      }
    }
  }

  /**
   * Send SMS.  Returns: bool $sent, int $activityId, int $success (number of sent SMS)
   *
   * @param array $contactDetails
   * @param array $activityParams
   * @param array $contactIds
   *
   * @return array(bool $sent, int $activityId, int $success)
   * @throws CRM_Core_Exception
   */
  protected function sendSMS(
    &$contactDetails,
    &$activityParams,
    &$contactIds = NULL
  ) {

    if (!isset($contactDetails) && !isset($contactIds)) {
      throw new CRM_Core_Exception('You must specify either $contactDetails or $contactIds');
    }
    // Populate $contactDetails and $contactIds if only one is set
    if (is_array($contactIds) && !empty($contactIds) && empty($contactDetails)) {
      foreach ($contactIds as $id) {
        try {
          $contactDetails[] = civicrm_api3('Contact', 'getsingle', ['contact_id' => $id]);
        }
        catch (Exception $e) {
          // Contact Id doesn't exist
        }
      }
    }
    elseif (is_array($contactDetails) && !empty($contactDetails) && empty($contactIds)) {
      foreach ($contactDetails as $contact) {
        $contactIds[] = $contact['contact_id'];
      }
    }

    $text = &$activityParams['sms_text_message'];

    // Create the meta level record first ( sms activity )
    $activityID = Activity::create()->setValues([
      'source_contact_id' => CRM_Core_Session::getLoggedInContactID(),
      'activity_type_id:name' => 'SMS',
      'activity_date_time' => 'now',
      'subject' => $this->getSubmittedValue('activity_subject'),
      'details' => $this->getSubmittedValue('sms_text_message'),
      'status_id:name' => 'Completed',
    ])->execute()->first()['id'];

    $success = 0;
    $errMsgs = [];
    foreach ($contactDetails as $contact) {
      $contactId = $contact['contact_id'];
      $tokenText = CRM_Core_BAO_MessageTemplate::renderTemplate(['messageTemplate' => ['msg_text' => $text], 'contactId' => $contactId, 'disableSmarty' => TRUE])['text'];
      $smsProviderParams = $this->getSmsProviderParams();
      // Only send if the phone is of type mobile
      if ($contact['phone_type_id'] == CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Phone', 'phone_type_id', 'Mobile')) {
        $smsProviderParams['To'] = $contact['phone'];
      }
      else {
        $smsProviderParams['To'] = '';
      }

      $doNotSms = $contact['do_not_sms'] ?? 0;

      if ($doNotSms) {
        $errMsgs[] = PEAR::raiseError('Contact Does not accept SMS', NULL, PEAR_ERROR_RETURN);
      }
      else {
        try {
          $sendResult = CRM_Activity_BAO_Activity::sendSMSMessage(
            $contactId,
            $tokenText,
            $smsProviderParams,
            $activityID,
            CRM_Core_Session::getLoggedInContactID()
          );
          $success++;
        }
        catch (CRM_Core_Exception $e) {
          $errMsgs[] = $e->getMessage();
        }
      }
    }

    // If at least one message was sent and no errors
    // were generated then return a boolean value of TRUE.
    // Otherwise, return FALSE (no messages sent) or
    // and array of 1 or more PEAR_Error objects.
    $sent = FALSE;
    if ($success > 0 && count($errMsgs) == 0) {
      $sent = TRUE;
    }
    elseif (count($errMsgs) > 0) {
      $sent = $errMsgs;
    }

    return [$sent, $success];
  }

  /**
   * Form rule.
   *
   * @param array $fields
   *   The input form values.
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRuleSms($fields) {
    $errors = [];

    if (empty($fields['sms_text_message'])) {
      $errors['sms_text_message'] = ts('Please provide Text message.');
    }
    else {
      if (!empty($fields['sms_text_message'])) {
        $messageCheck = $fields['sms_text_message'] ?? NULL;
        $messageCheck = str_replace("\r\n", "\n", $messageCheck);
        if ($messageCheck && (strlen($messageCheck) > CRM_SMS_Provider::MAX_SMS_CHAR)) {
          $errors['sms_text_message'] = ts("You can configure the SMS message body up to %1 characters", [1 => CRM_SMS_Provider::MAX_SMS_CHAR]);
        }
      }
    }

    //Added for CRM-1393
    if (!empty($fields['SMSsaveTemplate']) && empty($fields['SMSsaveTemplateName'])) {
      $errors['SMSsaveTemplateName'] = ts("Enter name to save message template");
    }

    return empty($errors) ? TRUE : $errors;
  }

  protected function isInvalidRecipient($contactID): bool {
    //Overridden by the activity child class.
    return FALSE;
  }

  /**
   * Get the specified value for the contact.
   *
   * Note do not rename to `getContactValue()` - that function
   * is used on many forms and does not take $id as a parameter.
   *
   * @param int $contactID
   * @param string $value
   *
   * @return mixed|null
   * @throws \CRM_Core_Exception
   */
  protected function getValueForContact(int $contactID, $value) {
    if (!$this->isDefined('Contact' . $contactID)) {
      $this->define('Contact', 'Contact' . $contactID, ['id' => $contactID]);
    }
    return $this->lookup('Contact' . $contactID, $value);
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens(): array {
    $tokenProcessor = new TokenProcessor(Civi::dispatcher(), ['schema' => ['contactId']]);
    return $tokenProcessor->listTokens();
  }

}
