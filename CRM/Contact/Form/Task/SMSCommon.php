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

/**
 * This class provides the common functionality for sending sms to one or a group of contact ids.
 *
 * @deprecated since 5.71 will be removed around 5.77.
 */
class CRM_Contact_Form_Task_SMSCommon {
  const RECIEVED_SMS_ACTIVITY_SUBJECT = "SMS Received";

  public $_contactDetails = [];

  public $_allContactDetails = [];

  public $_toContactPhone = [];

  /**
   * Pre process the provider.
   *
   * @deprecated since 5.71 will be removed around 5.77.
   *
   * @param CRM_Core_Form $form
   */
  public static function preProcessProvider(&$form) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    $form->_single = FALSE;
    $className = CRM_Utils_System::getClassName($form);

    if (property_exists($form, '_context') &&
      $form->_context != 'search' &&
      $className == 'CRM_Contact_Form_Task_SMS'
    ) {
      $form->_single = TRUE;
    }

    $providersCount = CRM_SMS_BAO_SmsProvider::activeProviderCount();

    if (!$providersCount) {
      CRM_Core_Error::statusBounce(ts('There are no SMS providers configured, or no SMS providers are set active'));
    }

    if ($className == 'CRM_Activity_Form_Task_SMS') {
      $activityCheck = 0;
      foreach ($form->_activityHolderIds as $value) {
        if (CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $value, 'subject', 'id') != self::RECIEVED_SMS_ACTIVITY_SUBJECT) {
          $activityCheck++;
        }
      }
      if ($activityCheck == count($form->_activityHolderIds)) {
        CRM_Core_Error::statusBounce(ts("The Reply SMS Could only be sent for activities with '%1' subject.",
          [1 => self::RECIEVED_SMS_ACTIVITY_SUBJECT]
        ));
      }
    }
  }

  /**
   * Build the form object.
   *
   * @param CRM_Core_Form $form
   *
   * @deprecated since 5.71 will be removed around 5.77.
   */
  public static function buildQuickForm(&$form) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative supported for non-core use');

    $toArray = [];

    $providers = CRM_SMS_BAO_SmsProvider::getProviders(NULL, NULL, TRUE, 'is_default desc');

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
    if ($to->getValue()) {
      $allToPhone = explode(',', $to->getValue());

      $form->_contactIds = [];
      foreach ($allToPhone as $value) {
        [$contactId, $phone] = explode('::', $value);
        if ($contactId) {
          $form->_contactIds[] = $contactId;
          $form->_toContactPhone[] = $phone;
        }
      }
      $toSetDefault = TRUE;
    }

    //get the group of contacts as per selected by user in case of Find Activities
    if (!empty($form->_activityHolderIds)) {
      $extendTargetContacts = 0;
      $invalidActivity = 0;
      $validActivities = 0;
      foreach ($form->_activityHolderIds as $key => $id) {
        //valid activity check
        if (CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $id, 'subject', 'id') != self::RECIEVED_SMS_ACTIVITY_SUBJECT) {
          $invalidActivity++;
          continue;
        }

        $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
        $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
        //target contacts limit check
        $ids = array_keys(CRM_Activity_BAO_ActivityContact::getNames($id, $targetID));

        if (count($ids) > 1) {
          $extendTargetContacts++;
          continue;
        }
        $validActivities++;
        $form->_contactIds = empty($form->_contactIds) ? $ids : array_unique(array_merge($form->_contactIds, $ids));
      }

      if (!$validActivities) {
        $errorMess = "";
        if ($extendTargetContacts) {
          $errorMess = ts('One selected activity consists of more than one target contact.', [
            'count' => $extendTargetContacts,
            'plural' => '%count selected activities consist of more than one target contact.',
          ]);
        }
        if ($invalidActivity) {
          $errorMess = ($errorMess ? ' ' : '');
          $errorMess .= ts('The selected activity is invalid.', [
            'count' => $invalidActivity,
            'plural' => '%count selected activities are invalid.',
          ]);
        }
        CRM_Core_Error::statusBounce(ts("%1: SMS Reply will not be sent.", [1 => $errorMess]));
      }
    }

    if (is_array($form->_contactIds) && !empty($form->_contactIds) && $toSetDefault) {
      $form->_contactDetails = civicrm_api3('Contact', 'get', [
        'id' => ['IN' => $form->_contactIds],
        'return' => ['sort_name', 'phone', 'do_not_sms', 'is_deceased', 'display_name'],
        'options' => ['limit' => 0],
      ])['values'];

      // make a copy of all contact details
      $form->_allContactDetails = $form->_contactDetails;

      foreach ($form->_contactIds as $key => $contactId) {
        $mobilePhone = NULL;
        $contactDetails = $form->_contactDetails[$contactId];

        //to check if the phone type is "Mobile"
        $phoneTypes = CRM_Core_OptionGroup::values('phone_type', TRUE, FALSE, FALSE, NULL, 'name');

        if (CRM_Utils_System::getClassName($form) == 'CRM_Activity_Form_Task_SMS') {
          //to check for "if the contact id belongs to a specified activity type"
          // @todo use the api instead - function is deprecated.
          $actDetails = CRM_Activity_BAO_Activity::getContactActivity($contactId);
          if (self::RECIEVED_SMS_ACTIVITY_SUBJECT !=
            CRM_Utils_Array::retrieveValueRecursive($actDetails, 'subject')
          ) {
            $suppressedSms++;
            unset($form->_contactDetails[$contactId]);
            continue;
          }
        }

        // No phone, No SMS or Deceased: then we suppress it.
        if (empty($contactDetails['phone']) || $contactDetails['do_not_sms'] || !empty($contactDetails['is_deceased'])) {
          $suppressedSms++;
          unset($form->_contactDetails[$contactId]);
          continue;
        }
        elseif ($contactDetails['phone_type_id'] != ($phoneTypes['Mobile'] ?? NULL)) {
          //if phone is not primary check if non-primary phone is "Mobile"
          $filter = ['do_not_sms' => 0];
          $contactPhones = CRM_Core_BAO_Phone::allPhones($contactId, FALSE, 'Mobile', $filter);
          if (count($contactPhones) > 0) {
            $mobilePhone = CRM_Utils_Array::retrieveValueRecursive($contactPhones, 'phone');
            $form->_contactDetails[$contactId]['phone_id'] = CRM_Utils_Array::retrieveValueRecursive($contactPhones, 'id');
            $form->_contactDetails[$contactId]['phone'] = $mobilePhone;
            $form->_contactDetails[$contactId]['phone_type_id'] = $phoneTypes['Mobile'] ?? NULL;
          }
          else {
            $suppressedSms++;
            unset($form->_contactDetails[$contactId]);
            continue;
          }
        }

        if (isset($mobilePhone)) {
          $phone = $mobilePhone;
        }
        elseif (empty($form->_toContactPhone)) {
          $phone = $contactDetails['phone'];
        }
        else {
          $phone = $form->_toContactPhone[$key] ?? NULL;
        }

        if ($phone) {
          $toArray[] = [
            'text' => '"' . $contactDetails['sort_name'] . '" (' . $phone . ')',
            'id' => "$contactId::{$phone}",
          ];
        }
      }

      if (empty($toArray)) {
        CRM_Core_Error::statusBounce(ts('Selected contact(s) do not have a valid Phone, or communication preferences specify DO NOT SMS, or they are deceased'));
      }
    }

    //activity related variables
    if (isset($invalidActivity)) {
      $form->assign('invalidActivity', $invalidActivity);
    }
    if (isset($extendTargetContacts)) {
      $form->assign('extendTargetContacts', $extendTargetContacts);
    }

    $form->assign('toContact', json_encode($toArray));
    $form->assign('suppressedSms', $suppressedSms);
    $form->assign('totalSelectedContacts', count($form->_contactIds));

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

    $form->addFormRule(['CRM_Contact_Form_Task_SMSCommon', 'formRule'], $form);
  }

  /**
   * Form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $dontCare
   * @param self $self
   *   Additional values form 'this'.
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $dontCare, $self) {
    $errors = [];
    CRM_Core_Error::deprecatedFunctionWarning('no alternative supported for non-core use');
    $template = CRM_Core_Smarty::singleton();

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

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @param CRM_Core_Form $form
   *
   * @deprecated since 5.71 will be removed around 5.77.
   */
  public static function postProcess(&$form) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative supported for non-core use');
    $thisValues = $form->controller->exportValues($form->getName());

    $fromSmsProviderId = $thisValues['sms_provider_id'];

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

    foreach ($form->_contactIds as $key => $contactId) {
      $phone = $form->_toContactPhone[$key];

      if ($phone) {
        $phoneKey = "{$contactId}::{$phone}";
        if (!in_array($phoneKey, $tempPhones)) {
          $tempPhones[] = $phoneKey;
          if (!empty($form->_contactDetails[$contactId])) {
            $formattedContactDetails[] = $form->_contactDetails[$contactId];
          }
        }
      }
    }

    // $smsParams carries all the arguments provided on form (or via hooks), to the provider->send() method
    // this gives flexibity to the users / implementors to add their own args via hooks specific to their sms providers
    $smsParams = $thisValues;
    unset($smsParams['sms_text_message']);
    $smsParams['provider_id'] = $fromSmsProviderId;
    $contactIds = array_keys($form->_contactDetails);
    $allContactIds = array_keys($form->_allContactDetails);

    [$sent, $activityId, $countSuccess] = CRM_Activity_BAO_Activity::sendSMS($formattedContactDetails,
      $thisValues,
      $smsParams,
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
      $smsNotSent = array_diff_assoc($allContactIds, $contactIds);

      if (!empty($smsNotSent)) {
        $not_sent = [];
        foreach ($smsNotSent as $index => $contactId) {
          $displayName = $form->_allContactDetails[$contactId]['display_name'];
          $phone = $form->_allContactDetails[$contactId]['phone'];
          $contactViewUrl = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=$contactId");
          $not_sent[] = "<a href='$contactViewUrl' title='$phone'>$displayName</a>";
        }
        $status = '(' . ts('because no phone number on file or communication preferences specify DO NOT SMS or Contact is deceased');
        if (CRM_Utils_System::getClassName($form) == 'CRM_Activity_Form_Task_SMS') {
          $status .= ' ' . ts("or the contact is not part of the activity '%1'", [1 => self::RECIEVED_SMS_ACTIVITY_SUBJECT]);
        }
        $status .= ')<ul><li>' . implode('</li><li>', $not_sent) . '</li></ul>';
        CRM_Core_Session::setStatus($status, ts('One Message Not Sent', [
          'count' => count($smsNotSent),
          'plural' => '%count Messages Not Sent',
        ]), 'info');
      }
    }
  }

}
