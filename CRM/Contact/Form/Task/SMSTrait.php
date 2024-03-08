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
      $contacts = [];
      $phoneGet = Phone::get()
        ->addWhere('contact_id.do_not_sms', '=', FALSE)
        ->addWhere('contact_id.is_deceased', '=', FALSE)
        ->addWhere('phone_numeric', '>', 0)
        ->addWhere('phone_type_id:name', '=', 'Mobile')
        ->addOrderBy('is_primary')
        ->addSelect('id', 'contact_id', 'phone', 'phone_type_id:name', 'phone_numeric', 'contact_id.sort_name', 'phone_type_id', 'contact_id.display_name');
      if ($this->getSubmittedValue('to')) {
        $phoneGet->addWhere('id', 'IN', explode(',', $this->getSubmittedValue('to')));
      }
      else {
        $phoneGet->addWhere('contact_id', 'IN', $this->getContactIDs());
      }
      $this->phones = (array) $phoneGet->execute()->indexBy('id');
      foreach ($this->phones as $index => $phone) {
        if ($this->isInvalidRecipient($phone['contact_id'])) {
          unset($this->phones[$index]);
        }
        if (!$this->getSubmittedValue('to')) {
          // In this pre-submit case we want to make the contact Ids unique.
          if (isset($contacts[$phone['contact_id']])) {
            unset($this->phones[$index]);
          }
          $contacts[$phone['contact_id']] = TRUE;
        }
      }
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

    $this->addAutocomplete('to', ts('Send to'), [
      'entity' => 'Phone',
      'api' => [
        'fieldName' => 'Phone.id',
      ],
      'select' => ['multiple' => TRUE],
      'class' => 'select2',
    ], TRUE);

    $form->add('text', 'activity_subject', ts('Name The SMS'), ['class' => 'huge'], TRUE);

    $toSetDefault = TRUE;
    if (property_exists($form, '_context') && $form->_context === 'standalone') {
      $toSetDefault = FALSE;
    }

    // when form is submitted recompute contactIds
    if ($this->isSubmitted()) {
      $form->_contactIds = [];
      foreach ($this->getPhones() as $phone) {
        $form->_contactIds[] = $phone['contact_id'];
      }
    }

    //get the group of contacts as per selected by user in case of Find Activities
    $this->filterContactIDs();

    if (!empty($form->getContactIDs())) {
      $phoneNumbers = $this->getPhones();
      $suppressedSms = count($this->getContactIDs()) - count($phoneNumbers);
    }
    if (!$this->getSubmittedValue('to') && !$this->getPhones()) {
      CRM_Core_Error::statusBounce(ts('Selected contact(s) do not have a valid Phone, or communication preferences specify DO NOT SMS, or they are deceased'));
    }

    //activity related variables
    $form->addExpectedSmartyVariable('invalidActivity');
    $form->addExpectedSmartyVariable('extendTargetContacts');

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
   * Set the default form values.
   *
   *
   * @return array
   *   the default array reference
   */
  public function setDefaultValues() {
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
        'msg_text' => $this->getSubmittedValue('sms_text_message'),
        'is_active' => TRUE,
        'is_sms' => TRUE,
      ];

      if (!empty($thisValues['SMSsaveTemplate'])) {
        $messageTemplate['msg_title'] = $this->getSubmittedValue('SMSsaveTemplateName');
      }

      if ($this->getSubmittedValue('SMStemplate') && $this->getSubmittedValue('SMSupdateTemplate')) {
        $messageTemplate['id'] = $this->getSubmittedValue('SMStemplate');
        unset($messageTemplate['msg_title']);
      }
      MessageTemplate::save()->addRecord($messageTemplate)->execute();
    }

    $phonesToSendTo = explode(',', $this->getSubmittedValue('to'));
    $unexpectedErrors = $phonesToSMS = [];
    foreach ($phonesToSendTo as $phoneID) {
      $phoneValues = $this->getPhones()[$phoneID] ?? NULL;
      if (!$phoneValues) {
        $unexpectedErrors[] = ts('Contact Does not accept SMS');
        continue;
      }
      $phonesToSMS[] = $phoneValues;
    }
    // These errors are unexpected because the ajax should filter them out.
    foreach ($unexpectedErrors as $unexpectedError) {
      CRM_Core_Session::setStatus($unexpectedError, ts('Invalid phone submitted'));
    }

    [$errors, $countSuccess] = $this->sendSMS($phonesToSMS);
    if ($countSuccess > 0) {
      CRM_Core_Session::setStatus(ts('One message was sent successfully.', [
        'plural' => '%count messages were sent successfully.',
        'count' => $countSuccess,
      ]), ts('Message Sent', ['plural' => 'Messages Sent', 'count' => $countSuccess]), 'success');
    }

    if (!empty($errors)) {
      // At least one PEAR_Error object was generated.
      // Display the error messages to the user.
      $status = '<ul>';
      foreach ($errors as $errMsg) {
        $status .= '<li>' . $errMsg . '</li>';
      }
      $status .= '</ul>';
      CRM_Core_Session::setStatus($status, ts('One Message Not Sent', [
        'count' => count($errors),
        'plural' => '%count Messages Not Sent',
      ]), 'info');
    }
    else {
      $contactIds = array_values($this->getContactIDs());
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
        if (CRM_Utils_System::getClassName($form) === 'CRM_Activity_Form_Task_SMS') {
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
   * Send SMS.
   *
   * @param array $phones
   *
   * @return array(array $errors, int $numberSent)
   * @throws CRM_Core_Exception
   */
  protected function sendSMS(array $phones): array {

    // Create the meta level record first ( sms activity )
    $activityID = Activity::create()->setValues([
      'source_contact_id' => CRM_Core_Session::getLoggedInContactID(),
      'activity_type_id:name' => 'SMS',
      'activity_date_time' => 'now',
      'subject' => $this->getSubmittedValue('activity_subject'),
      'details' => $this->getSubmittedValue('sms_text_message'),
      'status_id:name' => 'Completed',
    ])->execute()->first()['id'];

    $numberSent = 0;
    $errors = [];
    foreach ($phones as $phone) {
      $contactId = $phone['contact_id'];
      $tokenText = CRM_Core_BAO_MessageTemplate::renderTemplate(['messageTemplate' => ['msg_text' => $this->getSubmittedValue('sms_text_message')], 'contactId' => $contactId, 'disableSmarty' => TRUE])['text'];
      $smsProviderParams = $this->getSmsProviderParams();
      $smsProviderParams['To'] = $phone['phone_numeric'];
      try {
        CRM_Activity_BAO_Activity::sendSMSMessage(
          $contactId,
          $tokenText,
          $smsProviderParams,
          $activityID,
          CRM_Core_Session::getLoggedInContactID()
        );
        $numberSent++;
      }
      catch (CRM_Core_Exception $e) {
        $errors[] = $e->getMessage();
      }
    }
    return [$errors, $numberSent];
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
