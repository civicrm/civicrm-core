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
 * This class provides the common functionality for tasks that send emails.
 */
trait CRM_Contact_Form_Task_EmailTrait {

  /**
   * Are we operating in "single mode", i.e. sending email to one
   * specific contact?
   *
   * @var bool
   */
  public $_single = FALSE;

  public $_noEmails = FALSE;

  /**
   * All the existing templates in the system.
   *
   * @var array
   */
  public $_templates;

  /**
   * Store "to" contact details.
   * @var array
   */
  public $_toContactDetails = [];

  /**
   * Store all selected contact id's, that includes to, cc and bcc contacts
   * @var array
   */
  public $_allContactIds = [];

  /**
   * Store only "to" contact ids.
   * @var array
   */
  public $_toContactIds = [];

  /**
   * Store only "cc" contact ids.
   * @var array
   */
  public $_ccContactIds = [];

  /**
   * Store only "bcc" contact ids.
   *
   * @var array
   */
  public $_bccContactIds = [];

  /**
   * Is the form being loaded from a search action.
   *
   * @var bool
   */
  public $isSearchContext = TRUE;

  /**
   * Getter for isSearchContext.
   *
   * @return bool
   */
  public function isSearchContext(): bool {
    return $this->isSearchContext;
  }

  /**
   * Setter for isSearchContext.
   *
   * @param bool $isSearchContext
   */
  public function setIsSearchContext(bool $isSearchContext) {
    $this->isSearchContext = $isSearchContext;
  }

  /**
   * Build all the data structures needed to build the form.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $this->traitPreProcess();
  }

  /**
   * Call trait preProcess function.
   *
   * This function exists as a transitional arrangement so classes overriding
   * preProcess can still call it. Ideally it will be melded into preProcess later.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  protected function traitPreProcess() {
    CRM_Contact_Form_Task_EmailCommon::preProcessFromAddress($this);
    if ($this->isSearchContext()) {
      // Currently only the contact email form is callable outside search context.
      parent::preProcess();
    }
    $this->setContactIDs();
    $this->assign('single', $this->_single);
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $this->assign('isAdmin', 1);
    }
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    // Suppress form might not be required but perhaps there was a risk some other  process had set it to TRUE.
    $this->assign('suppressForm', FALSE);
    $this->assign('emailTask', TRUE);

    $toArray = $ccArray = $bccArray = [];
    $suppressedEmails = 0;
    //here we are getting logged in user id as array but we need target contact id. CRM-5988
    $cid = $this->get('cid');
    if ($cid) {
      $this->_contactIds = explode(',', $cid);
    }
    if (count($this->_contactIds) > 1) {
      $this->_single = FALSE;
    }
    $this->bounceIfSimpleMailLimitExceeded(count($this->_contactIds));

    $emailAttributes = [
      'class' => 'huge',
    ];
    $to = $this->add('text', 'to', ts('To'), $emailAttributes, TRUE);
    $cc = $this->add('text', 'cc_id', ts('CC'), $emailAttributes);
    $bcc = $this->add('text', 'bcc_id', ts('BCC'), $emailAttributes);

    if ($to->getValue()) {
      $this->_toContactIds = $this->_contactIds = [];
    }
    $setDefaults = TRUE;
    if (property_exists($this, '_context') && $this->_context === 'standalone') {
      $setDefaults = FALSE;
    }

    $elements = ['to', 'cc', 'bcc'];
    $this->_allContactIds = $this->_toContactIds = $this->_contactIds;
    foreach ($elements as $element) {
      if ($$element->getValue()) {

        foreach ($this->getEmails($$element) as $value) {
          $contactId = $value['contact_id'];
          $email = $value['email'];
          if ($contactId) {
            switch ($element) {
              case 'to':
                $this->_contactIds[] = $this->_toContactIds[] = $contactId;
                $this->_toContactEmails[] = $email;
                break;

              case 'cc':
                $this->_ccContactIds[] = $contactId;
                break;

              case 'bcc':
                $this->_bccContactIds[] = $contactId;
                break;
            }

            $this->_allContactIds[] = $contactId;
          }
        }

        $setDefaults = TRUE;
      }
    }

    //get the group of contacts as per selected by user in case of Find Activities
    if (!empty($this->_activityHolderIds)) {
      $contact = $this->get('contacts');
      $this->_allContactIds = $this->_contactIds = $contact;
    }

    // check if we need to setdefaults and check for valid contact emails / communication preferences
    if (is_array($this->_allContactIds) && $setDefaults) {
      $returnProperties = [
        'sort_name' => 1,
        'email' => 1,
        'do_not_email' => 1,
        'is_deceased' => 1,
        'on_hold' => 1,
        'display_name' => 1,
        'preferred_mail_format' => 1,
      ];

      // get the details for all selected contacts ( to, cc and bcc contacts )
      list($this->_contactDetails) = CRM_Utils_Token::getTokenDetails($this->_allContactIds,
        $returnProperties,
        FALSE,
        FALSE
      );

      // make a copy of all contact details
      $this->_allContactDetails = $this->_contactDetails;

      // perform all validations on unique contact Ids
      foreach (array_unique($this->_allContactIds) as $key => $contactId) {
        $value = $this->_contactDetails[$contactId];
        if ($value['do_not_email'] || empty($value['email']) || !empty($value['is_deceased']) || $value['on_hold']) {
          $suppressedEmails++;

          // unset contact details for contacts that we won't be sending email. This is prevent extra computation
          // during token evaluation etc.
          unset($this->_contactDetails[$contactId]);
        }
        else {
          $email = $value['email'];

          // build array's which are used to setdefaults
          if (in_array($contactId, $this->_toContactIds)) {
            $this->_toContactDetails[$contactId] = $this->_contactDetails[$contactId];
            // If a particular address has been specified as the default, use that instead of contact's primary email
            if (!empty($this->_toEmail) && $this->_toEmail['contact_id'] == $contactId) {
              $email = $this->_toEmail['email'];
            }
            $toArray[] = [
              'text' => '"' . $value['sort_name'] . '" <' . $email . '>',
              'id' => "$contactId::{$email}",
            ];
          }
          elseif (in_array($contactId, $this->_ccContactIds)) {
            $ccArray[] = [
              'text' => '"' . $value['sort_name'] . '" <' . $email . '>',
              'id' => "$contactId::{$email}",
            ];
          }
          elseif (in_array($contactId, $this->_bccContactIds)) {
            $bccArray[] = [
              'text' => '"' . $value['sort_name'] . '" <' . $email . '>',
              'id' => "$contactId::{$email}",
            ];
          }
        }
      }

      if (empty($toArray)) {
        CRM_Core_Error::statusBounce(ts('Selected contact(s) do not have a valid email address, or communication preferences specify DO NOT EMAIL, or they are deceased or Primary email address is On Hold.'));
      }
    }

    $this->assign('toContact', json_encode($toArray));
    $this->assign('ccContact', json_encode($ccArray));
    $this->assign('bccContact', json_encode($bccArray));

    $this->assign('suppressedEmails', $suppressedEmails);

    $this->assign('totalSelectedContacts', count($this->_contactIds));

    $this->add('text', 'subject', ts('Subject'), 'size=50 maxlength=254', TRUE);

    $this->add('select', 'from_email_address', ts('From'), $this->_fromEmails, TRUE);

    CRM_Mailing_BAO_Mailing::commonCompose($this);

    // add attachments
    CRM_Core_BAO_File::buildAttachment($this, NULL);

    if ($this->_single) {
      // also fix the user context stack
      if ($this->_caseId) {
        $ccid = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseContact', $this->_caseId,
          'contact_id', 'case_id'
        );
        $url = CRM_Utils_System::url('civicrm/contact/view/case',
          "&reset=1&action=view&cid={$ccid}&id={$this->_caseId}"
        );
      }
      elseif ($this->_context) {
        $url = CRM_Utils_System::url('civicrm/dashboard', 'reset=1');
      }
      else {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          "&show=1&action=browse&cid={$this->_contactIds[0]}&selectedChild=activity"
        );
      }

      $session = CRM_Core_Session::singleton();
      $session->replaceUserContext($url);
      $this->addDefaultButtons(ts('Send Email'), 'upload', 'cancel');
    }
    else {
      $this->addDefaultButtons(ts('Send Email'), 'upload');
    }

    $fields = [
      'followup_assignee_contact_id' => [
        'type' => 'entityRef',
        'label' => ts('Assigned to'),
        'attributes' => [
          'multiple' => TRUE,
          'create' => TRUE,
          'api' => ['params' => ['is_deceased' => 0]],
        ],
      ],
      'followup_activity_type_id' => [
        'type' => 'select',
        'label' => ts('Followup Activity'),
        'attributes' => ['' => '- ' . ts('select activity') . ' -'] + CRM_Core_PseudoConstant::ActivityType(FALSE),
        'extra' => ['class' => 'crm-select2'],
      ],
      'followup_activity_subject' => [
        'type' => 'text',
        'label' => ts('Subject'),
        'attributes' => CRM_Core_DAO::getAttribute('CRM_Activity_DAO_Activity',
          'subject'
        ),
      ],
    ];

    //add followup date
    $this->add('datepicker', 'followup_date', ts('in'));

    foreach ($fields as $field => $values) {
      if (!empty($fields[$field])) {
        $attribute = $values['attributes'] ?? NULL;
        $required = !empty($values['required']);

        if ($values['type'] === 'select' && empty($attribute)) {
          $this->addSelect($field, ['entity' => 'activity'], $required);
        }
        elseif ($values['type'] === 'entityRef') {
          $this->addEntityRef($field, $values['label'], $attribute, $required);
        }
        else {
          $this->add($values['type'], $field, $values['label'], $attribute, $required, CRM_Utils_Array::value('extra', $values));
        }
      }
    }

    //Added for CRM-15984: Add campaign field
    CRM_Campaign_BAO_Campaign::addCampaign($this);

    $this->addFormRule(['CRM_Contact_Form_Task_EmailCommon', 'formRule'], $this);
    CRM_Core_Resources::singleton()->addScriptFile('civicrm', 'templates/CRM/Contact/Form/Task/EmailCommon.js', 0, 'html-header');
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function postProcess() {
    $this->bounceIfSimpleMailLimitExceeded(count($this->_contactIds));

    // check and ensure that
    $formValues = $this->controller->exportValues($this->getName());
    $this->submit($formValues);
  }

  /**
   * Bounce if there are more emails than permitted.
   *
   * @param int $count
   *  The number of emails the user is attempting to send
   */
  protected function bounceIfSimpleMailLimitExceeded($count) {
    $limit = Civi::settings()->get('simple_mail_limit');
    if ($count > $limit) {
      CRM_Core_Error::statusBounce(ts('Please do not use this task to send a lot of emails (greater than %1). Many countries have legal requirements when sending bulk emails and the CiviMail framework has opt out functionality and domain tokens to help meet these.',
        [1 => $limit]
      ));
    }
  }

  /**
   * Submit the form values.
   *
   * This is also accessible for testing.
   *
   * @param array $formValues
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function submit($formValues) {
    $this->saveMessageTemplate($formValues);

    // dev/core#357 User Emails are keyed by their id so that the Signature is able to be added
    // If we have had a contact email used here the value returned from the line above will be the
    // numerical key where as $from for use in the sendEmail in Activity needs to be of format of "To Name" <toemailaddress>
    $from = CRM_Utils_Mail::formatFromAddress($this);
    $subject = $formValues['subject'];

    // CRM-13378: Append CC and BCC information at the end of Activity Details and format cc and bcc fields
    $elements = ['cc_id', 'bcc_id'];
    $additionalDetails = NULL;
    $ccValues = $bccValues = [];
    foreach ($elements as $element) {
      if (!empty($formValues[$element])) {
        $allEmails = explode(',', $formValues[$element]);
        foreach ($allEmails as $value) {
          list($contactId, $email) = explode('::', $value);
          $contactURL = CRM_Utils_System::url('civicrm/contact/view', "reset=1&force=1&cid={$contactId}", TRUE);
          switch ($element) {
            case 'cc_id':
              $ccValues['email'][] = '"' . $this->_contactDetails[$contactId]['sort_name'] . '" <' . $email . '>';
              $ccValues['details'][] = "<a href='{$contactURL}'>" . $this->_contactDetails[$contactId]['display_name'] . "</a>";
              break;

            case 'bcc_id':
              $bccValues['email'][] = '"' . $this->_contactDetails[$contactId]['sort_name'] . '" <' . $email . '>';
              $bccValues['details'][] = "<a href='{$contactURL}'>" . $this->_contactDetails[$contactId]['display_name'] . "</a>";
              break;
          }
        }
      }
    }

    $cc = $bcc = '';
    if (!empty($ccValues)) {
      $cc = implode(',', $ccValues['email']);
      $additionalDetails .= "\ncc : " . implode(", ", $ccValues['details']);
    }
    if (!empty($bccValues)) {
      $bcc = implode(',', $bccValues['email']);
      $additionalDetails .= "\nbcc : " . implode(", ", $bccValues['details']);
    }

    // CRM-5916: prepend case id hash to CiviCase-originating emails’ subjects
    if (isset($this->_caseId) && is_numeric($this->_caseId)) {
      $hash = substr(sha1(CIVICRM_SITE_KEY . $this->_caseId), 0, 7);
      $subject = "[case #$hash] $subject";
    }

    $attachments = [];
    CRM_Core_BAO_File::formatAttachment($formValues,
      $attachments,
      NULL, NULL
    );

    // format contact details array to handle multiple emails from same contact
    $formattedContactDetails = [];
    $tempEmails = [];
    foreach ($this->_contactIds as $key => $contactId) {
      // if we dont have details on this contactID, we should ignore
      // potentially this is due to the contact not wanting to receive email
      if (!isset($this->_contactDetails[$contactId])) {
        continue;
      }
      $email = $this->_toContactEmails[$key];
      // prevent duplicate emails if same email address is selected CRM-4067
      // we should allow same emails for different contacts
      $emailKey = "{$contactId}::{$email}";
      if (!in_array($emailKey, $tempEmails)) {
        $tempEmails[] = $emailKey;
        $details = $this->_contactDetails[$contactId];
        $details['email'] = $email;
        unset($details['email_id']);
        $formattedContactDetails[] = $details;
      }
    }

    $contributionIds = [];
    if ($this->getVar('_contributionIds')) {
      $contributionIds = $this->getVar('_contributionIds');
    }

    // send the mail
    list($sent, $activityId) = CRM_Activity_BAO_Activity::sendEmail(
      $formattedContactDetails,
      $subject,
      $formValues['text_message'],
      $formValues['html_message'],
      NULL,
      NULL,
      $from,
      $attachments,
      $cc,
      $bcc,
      array_keys($this->_toContactDetails),
      $additionalDetails,
      $contributionIds,
      CRM_Utils_Array::value('campaign_id', $formValues),
      $this->getVar('_caseId')
    );

    $followupStatus = '';
    if ($sent) {
      $followupActivity = NULL;
      if (!empty($formValues['followup_activity_type_id'])) {
        $params['followup_activity_type_id'] = $formValues['followup_activity_type_id'];
        $params['followup_activity_subject'] = $formValues['followup_activity_subject'];
        $params['followup_date'] = $formValues['followup_date'];
        $params['target_contact_id'] = $this->_contactIds;
        $params['followup_assignee_contact_id'] = explode(',', $formValues['followup_assignee_contact_id']);
        $followupActivity = CRM_Activity_BAO_Activity::createFollowupActivity($activityId, $params);
        $followupStatus = ts('A followup activity has been scheduled.');

        if (Civi::settings()->get('activity_assignee_notification')) {
          if ($followupActivity) {
            $mailToFollowupContacts = [];
            $assignee = [$followupActivity->id];
            $assigneeContacts = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames($assignee, TRUE, FALSE);
            foreach ($assigneeContacts as $values) {
              $mailToFollowupContacts[$values['email']] = $values;
            }

            $sentFollowup = CRM_Activity_BAO_Activity::sendToAssignee($followupActivity, $mailToFollowupContacts);
            if ($sentFollowup) {
              $followupStatus .= '<br />' . ts("A copy of the follow-up activity has also been sent to follow-up assignee contacts(s).");
            }
          }
        }
      }

      $count_success = count($this->_toContactDetails);
      CRM_Core_Session::setStatus(ts('One message was sent successfully. ', [
        'plural' => '%count messages were sent successfully. ',
        'count' => $count_success,
      ]) . $followupStatus, ts('Message Sent', ['plural' => 'Messages Sent', 'count' => $count_success]), 'success');
    }

    // Display the name and number of contacts for those email is not sent.
    // php 5.4 throws out a notice since the values of these below arrays are arrays.
    // the behavior is not documented in the php manual, but it does the right thing
    // suppressing the notices to get things in good shape going forward
    $emailsNotSent = @array_diff_assoc($this->_allContactDetails, $this->_contactDetails);

    if ($emailsNotSent) {
      $not_sent = [];
      foreach ($emailsNotSent as $contactId => $values) {
        $displayName = $values['display_name'];
        $email = $values['email'];
        $contactViewUrl = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=$contactId");
        $not_sent[] = "<a href='$contactViewUrl' title='$email'>$displayName</a>" . ($values['on_hold'] ? '(' . ts('on hold') . ')' : '');
      }
      $status = '(' . ts('because no email address on file or communication preferences specify DO NOT EMAIL or Contact is deceased or Primary email address is On Hold') . ')<ul><li>' . implode('</li><li>', $not_sent) . '</li></ul>';
      CRM_Core_Session::setStatus($status, ts('One Message Not Sent', [
        'count' => count($emailsNotSent),
        'plural' => '%count Messages Not Sent',
      ]), 'info');
    }

    if (isset($this->_caseId)) {
      // if case-id is found in the url, create case activity record
      $cases = explode(',', $this->_caseId);
      foreach ($cases as $key => $val) {
        if (is_numeric($val)) {
          $caseParams = [
            'activity_id' => $activityId,
            'case_id' => $val,
          ];
          CRM_Case_BAO_Case::processCaseActivity($caseParams);
        }
      }
    }
  }

  /**
   * Save the template if update selected.
   *
   * @param array $formValues
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function saveMessageTemplate($formValues) {
    if (!empty($formValues['saveTemplate']) || !empty($formValues['updateTemplate'])) {
      $messageTemplate = [
        'msg_text' => $formValues['text_message'],
        'msg_html' => $formValues['html_message'],
        'msg_subject' => $formValues['subject'],
        'is_active' => TRUE,
      ];

      if (!empty($formValues['saveTemplate'])) {
        $messageTemplate['msg_title'] = $formValues['saveTemplateName'];
        CRM_Core_BAO_MessageTemplate::add($messageTemplate);
      }

      if (!empty($formValues['template']) && !empty($formValues['updateTemplate'])) {
        $messageTemplate['id'] = $formValues['template'];
        unset($messageTemplate['msg_title']);
        CRM_Core_BAO_MessageTemplate::add($messageTemplate);
      }
    }
  }

  /**
   * List available tokens for this form.
   *
   * @return array
   */
  public function listTokens() {
    return CRM_Core_SelectValues::contactTokens();
  }

  /**
   * Get the emails from the added element.
   *
   * @param HTML_QuickForm_Element $element
   *
   * @return array
   */
  protected function getEmails($element): array {
    $allEmails = explode(',', $element->getValue());
    $return = [];
    foreach ($allEmails as $value) {
      $values = explode('::', $value);
      $return[] = ['contact_id' => $values[0], 'email' => $values[1]];
    }
    return $return;
  }

}
