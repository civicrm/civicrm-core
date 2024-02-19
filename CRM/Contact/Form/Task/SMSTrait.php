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
use Civi\Api4\MessageTemplate;
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
  public function postProcess(): void {
    $this->postProcessSms();
  }

  /**
   * @param $form
   *
   * @return void
   */
  public function setRedirectURL($form): void {
    // also fix the user context stack
    if ($this->_context) {
      $url = CRM_Utils_System::url('civicrm/dashboard', 'reset=1');
    }
    else {
      $url = CRM_Utils_System::url('civicrm/contact/view',
        "&show=1&action=browse&cid={$form->_contactIds[0]}&selectedChild=activity"
      );
    }
    CRM_Core_Session::singleton()->replaceUserContext($url);
  }

  /**
   * Get additional form-specific invalid status message.
   *
   * @internal
   *
   * @return string
   */
  protected function getInvalidMessage(): string {
    return '';
  }

  /**
   * @internal
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  protected function addInvalidStatusMessage(): void {
    //Display the name and number of contacts for those sms is not sent.
    $cannotSendSMS = array_diff($this->getContactIDs(), $this->getRecipientContactIDs());
    if (!empty($cannotSendSMS)) {
      $not_sent = [];
      foreach ($cannotSendSMS as $contactId) {
        $displayName = $this->getValueForContact($contactId, 'display_name');
        $contactViewUrl = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=$contactId");
        $not_sent[] = "<a href='$contactViewUrl' title='$displayName'>$displayName</a>";
      }
      $status = '(' . ts('because no phone number on file or communication preferences specify DO NOT SMS or Contact is deceased');
      $status .= $this->getInvalidMessage();
      $status .= ')<ul><li>' . implode('</li><li>', $not_sent) . '</li></ul>';
      CRM_Core_Session::setStatus($status, ts('One Message Not Sent', [
        'count' => count($cannotSendSMS),
        'plural' => '%count Contacts not able receive this SMS',
      ]), 'info');
    }
  }

  /**
   * Add any form-relevant contact IDs.
   *
   * @internal
   */
  protected function addContactIDs(): void {
    // Activity sub class does this.
  }

  /**
   * @internal
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function getPhones(): array {
    if (!isset($this->phones)) {
      $phoneGet = Phone::get()
        ->addWhere('contact_id.do_not_sms', '=', FALSE)
        ->addWhere('contact_id.is_deceased', '=', FALSE)
        ->addWhere('phone_numeric', '>', 0)
        ->addWhere('phone_type_id:name', '=', 'Mobile')
        ->addOrderBy('is_primary', 'DESC')
        ->addGroupBy('contact_id')
        ->addSelect('contact_id', 'id', 'phone', 'phone_type_id:name', 'phone_numeric','contact_id.sort_name', 'phone_type_id', 'contact_id.display_name');
      if ($this->getSubmittedValue('to')) {
        $phoneGet->addWhere('id', 'IN', $this->getSelectedPhoneIDs());
      }
      else {
        $phoneGet->addWhere('contact_id', 'IN', $this->getContactIDs());
      }
      $this->phones = (array) $phoneGet->execute()->indexBy('id');
    }
    return $this->phones;
  }

  /**
   * Get the phone IDs as an array.
   *
   * This is the valid array of phone numbers that will be messaged.
   * If the form has been submitted this will be the same a
   * getSelectedPhoneIDs() unless the user has somehow added an id to the POSTed
   * value that was not available via the widget lookup.
   *
   * @return array
   */
  protected function getPhoneIDs(): array {
    $ids = array_keys($this->getPhones());
    asort($ids);
    return array_values($ids);
  }

  /**
   * Get the selected phone IDs as an array.
   *
   * This is based on the user submission and will be validated
   * against getPhoneIDs()/
   *
   * @internal
   *
   * @return array
   */
  protected function getSelectedPhoneIDs(): array {
    $submittedPhoneIDs = explode(',',$this->getSubmittedValue('to'));
    foreach ($submittedPhoneIDs as $index => $id) {
      $submittedPhoneIDs[$index] = (int) $id;
    }
    asort($submittedPhoneIDs);
    return array_values($submittedPhoneIDs);
  }

  /**
   * Get the contact IDs that have valid phone numbers for SMS purposes.
   *
   * On initial load this will be based on the selected contacts but once the
   * form is submitted it will be based on the selected phone numbers.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getRecipientContactIDs(): array {
    $ids = [];
    foreach ($this->getPhones() as $phone) {
      $ids[$phone['contact_id']] = $phone['contact_id'];
    }
    return array_values($ids);
  }

  /**
>>>>>>> 1cac4ebfe7 (clean up remaining use of undefined properties)
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

  /**
   * Get the array of contacts to SMS.
   *
   * Eg
   * [['contact_id' => 3, 'phone' => 911], ['contact_id' => 4, 'phone' => 111]]
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function getSMSContactDetails(): array {
    // format contact details array to handle multiple sms from same contact
    $contactDetails = [];
    foreach ($this->getPhones() as $phone) {
      $contactDetails[] = [
        'contact_id' => $phone['contact_id'],
        'phone' => $phone['phone_numeric'],
      ];
    }
    return $contactDetails;
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

    $this->addAutocomplete('to', ts('Send to'), [
      'entity' => 'Phone',
      'api' => [
        'fieldName' => 'Phone.id',
      ],
      'select' => ['multiple' => TRUE],
      'class' => 'select2',
    ], TRUE);

    $form->add('text', 'activity_subject', ts('Name The SMS'), ['class' => 'huge'], TRUE);

    //get the group of contacts as per selected by user in case of Find Activities
    $this->addContactIDs();
    if (!$this->getSubmittedValue('to') && !$this->getPhones()) {
      CRM_Core_Error::statusBounce(ts('Selected contact(s) do not have a valid Phone, or communication preferences specify DO NOT SMS, or they are deceased'));
    }

    $this->addInvalidStatusMessage();

    //activity related variables
    $form->addExpectedSmartyVariable('invalidActivity');
    $form->addExpectedSmartyVariable('extendTargetContacts');

    $form->assign('suppressedSms', count($this->getContactIDs()) - count($this->getRecipientContactIDs()));
    $form->assign('totalSelectedContacts', count($this->getContactIDs()));


    $providers = CRM_SMS_BAO_Provider::getProviders(NULL, NULL, TRUE, 'is_default desc');

    $providerSelect = [];
    foreach ($providers as $provider) {
      $providerSelect[$provider['id']] = $provider['title'];
    }
    $this->add('select', 'sms_provider_id', ts('From'), $providerSelect, TRUE);

    CRM_Mailing_BAO_Mailing::commonCompose($this);

    if ($this->_single) {
      $this->setRedirectURL($form);
      $this->addDefaultButtons(ts('Send SMS'), 'upload', 'cancel');
    }
    else {
      $this->addDefaultButtons(ts('Send SMS'), 'upload');
    }

    $this->addFormRule([__CLASS__, 'formRuleSms'], $this);
  }

  /**
   * Set the default form values.
   *
   * @return array
   *   the default array reference
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function setDefaultValues(): array {
    $phones = $this->getPhones();
    return ['to' => implode(',', array_keys($phones))];
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
        MessageTemplate::create(FALSE)->setValues($messageTemplate)->execute();
      }

      if ($this->getSubmittedValue('SMStemplate') && $this->getSubmittedValue('SMSupdateTemplate')) {
        $messageTemplate['id'] = $this->getSubmittedValue('SMStemplate');
        unset($messageTemplate['msg_title']);
        MessageTemplate::update(FALSE)->setValues($messageTemplate)->execute();
      }
    }

    [$errors, $countSuccess] = $this->sendSMS();

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
  }

  /**
   * Send SMS.
   *
   * @internal
   *
   * @return array(array $error, int $successCount)
   * @throws CRM_Core_Exception
   */
  protected function sendSMS(): array {

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
    $errors = [];
    foreach ($this->getSMSContactDetails() as $contact) {
      $contactId = $contact['contact_id'];
      $tokenText = CRM_Core_BAO_MessageTemplate::renderTemplate(['messageTemplate' => ['msg_text' => $this->getSubmittedValue('sms_text_message')], 'contactId' => $contactId, 'disableSmarty' => TRUE])['text'];
      $smsProviderParams = $this->getSmsProviderParams();
      // Only send if the phone is of type mobile
      if ($contact['phone_type_id'] == CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Phone', 'phone_type_id', 'Mobile')) {
        $smsProviderParams['To'] = $contact['phone'];
      }
      else {
        $smsProviderParams['To'] = '';
      }
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
   * @param array $files
   * @param \CRM_Contact_Form_Task_SMSTrait $self
   *
   * @return bool|array
   *   true if no errors, else array of errors
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   * @noinspection PhpUnusedParameterInspection*@internal
   *
   */
  public static function formRuleSms(array $fields, array $files, self $self) {
    $errors = [];

    if (empty($fields['sms_text_message'])) {
      $errors['sms_text_message'] = ts('Please provide Text message.');
    }
    else {
      $messageCheck = $fields['sms_text_message'];
      $messageCheck = str_replace("\r\n", "\n", $messageCheck);
      if ($messageCheck && (strlen($messageCheck) > CRM_SMS_Provider::MAX_SMS_CHAR)) {
        $errors['sms_text_message'] = ts("You can configure the SMS message body up to %1 characters", [1 => CRM_SMS_Provider::MAX_SMS_CHAR]);
      }
    }

    //Added for CRM-1393
    if (!empty($fields['SMSsaveTemplate']) && empty($fields['SMSsaveTemplateName'])) {
      $errors['SMSsaveTemplateName'] = ts("Enter name to save message template");
    }
    if ($self->getSelectedPhoneIDs() !== $self->getPhoneIDs()) {
      // This should not be reachable. Perhaps if a phone number got deleted concurrently?
      $errors['to'] = ts('Invalid phone included');
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
   * @internal
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
  protected function getValueForContact(int $contactID, string $value) {
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

  /**
   * Get the relevant contact IDs.
   *
   * @internal
   *
   * @return array
   */
  protected function getContactIDs(): array {
    // cid would be in the url if accessing from a contact summary.
    // url example is civicrm/activity/sms/add?action=add&reset=1&cid=99&selectedChild=activity&atype=4
    // where 99 is the Contact ID and 4 is the activity type ID for sms.
    if ($this->get('cid')) {
      $this->_contactIds = [$this->get('cid')];
    }
    if (!isset($this->_contactIds)) {
      $this->setContactIDs();
    }
    if ($this->isSubmitted()) {
      foreach ($this->getRecipientContactIDs() as $recipientContactID) {
        if (!in_array($recipientContactID, $this->_contactIds)) {
          // The user has selected an additional contact to include.
          // Add it to the selected contacts so that if validation fails
          // the various messages are right.
          $this->_contactIds[] = $recipientContactID;
        }
      }
    }
    return $this->_contactIds;
  }

  /**
   * @internal
   *
   * @return array
   */
  protected function getValidContactIDs(): array {
    $ids = [];
    foreach ($this->getContactIDs() as $id) {
      if (!$this->isInvalidRecipient($id)) {
        $ids[] = $id;
      }
    }
    return $ids;
  }

}
