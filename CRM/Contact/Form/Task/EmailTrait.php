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

use Civi\Api4\Email;

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

  /**
   * All the existing templates in the system.
   *
   * @var array
   */
  public $_templates;

  /**
   * Email addresses to send to.
   *
   * @var array
   */
  protected $emails = [];

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
   * Is the form being loaded from a search action.
   *
   * @var bool
   */
  public $isSearchContext = TRUE;

  public $contactEmails = [];

  /**
   * Contacts form whom emails could not be sent.
   *
   * An array of contact ids and the relevant message details.
   *
   * @var array
   */
  protected $suppressedEmails = [];

  public $_contactDetails = [];

  public $_entityTagValues;

  public $_caseId;

  public $_context;

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
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $this->traitPreProcess();
  }

  /**
   * Call trait preProcess function.
   *
   * This function exists as a transitional arrangement so classes overriding
   * preProcess can still call it. Ideally it will be melded into preProcess
   * later.
   *
   * @throws \CRM_Core_Exception
   */
  protected function traitPreProcess(): void {
    $this->addExpectedSmartyVariable('rows');
    if ($this->isSearchContext()) {
      // Currently only the contact email form is callable outside search context.
      parent::preProcess();
    }
    else {
      // E-notice prevention in Task.tpl
      $this->assign('isSelectedContacts', FALSE);
    }
    $this->setContactIDs();
    $this->assign('single', $this->_single);
    $this->assign('isAdmin', CRM_Core_Permission::check('administer CiviCRM'));
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

    $toArray = [];
    $suppressedEmails = 0;
    //here we are getting logged in user id as array but we need target contact id. CRM-5988
    $cid = $this->get('cid');
    if ($cid) {
      $this->_contactIds = explode(',', $cid);
    }
    // The default in CRM_Core_Form_Task is null, but changing it there gives
    // errors later.
    if (is_null($this->_contactIds)) {
      $this->_contactIds = [];
    }
    if (count($this->_contactIds) > 1) {
      $this->_single = FALSE;
    }
    $this->bounceIfSimpleMailLimitExceeded(count($this->_contactIds));

    $emailAttributes = [
      'class' => 'huge',
    ];
    $this->add('text', 'to', ts('To'), $emailAttributes, TRUE);

    $this->addEntityRef('cc_id', ts('CC'), [
      'entity' => 'Email',
      'multiple' => TRUE,
    ]);

    $this->addEntityRef('bcc_id', ts('BCC'), [
      'entity' => 'Email',
      'multiple' => TRUE,
    ]);

    $setDefaults = TRUE;
    if (property_exists($this, '_context') && $this->_context === 'standalone') {
      $setDefaults = FALSE;
    }

    $this->_allContactIds = $this->_toContactIds = $this->_contactIds;

    //get the group of contacts as per selected by user in case of Find Activities
    if (!empty($this->_activityHolderIds)) {
      $contact = $this->get('contacts');
      $this->_allContactIds = $this->_toContactIds = $this->_contactIds = $contact;
    }

    // check if we need to setdefaults and check for valid contact emails / communication preferences
    if (!empty($this->_allContactIds) && $setDefaults) {
      // get the details for all selected contacts ( to, cc and bcc contacts )
      $allContactDetails = civicrm_api3('Contact', 'get', [
        'id' => ['IN' => $this->_allContactIds],
        'return' => ['sort_name', 'email', 'do_not_email', 'is_deceased', 'on_hold', 'display_name'],
        'options' => ['limit' => 0],
      ])['values'];

      // The contact task supports passing in email_id in a url. It supports a single email
      // and is marked as having been related to CiviHR.
      // The array will look like $this->_toEmail = ['email' => 'x', 'contact_id' => 2])
      // If it exists we want to use the specified email which might be different to the primary email
      // that we have.
      if (!empty($this->_toEmail['contact_id']) && !empty($allContactDetails[$this->_toEmail['contact_id']])) {
        $allContactDetails[$this->_toEmail['contact_id']]['email'] = $this->_toEmail['email'];
      }

      // perform all validations on unique contact Ids
      foreach ($allContactDetails as $contactId => $value) {
        if ($value['do_not_email'] || empty($value['email']) || !empty($value['is_deceased']) || $value['on_hold']) {
          $this->setSuppressedEmail($contactId, $value);
        }
        elseif (in_array($contactId, $this->_toContactIds)) {
          $this->_toContactDetails[$contactId] = $this->_contactDetails[$contactId] = $value;
          $toArray[] = [
            'text' => '"' . $value['sort_name'] . '" <' . $value['email'] . '>',
            'id' => "$contactId::{$value['email']}",
          ];
        }
      }

      if (empty($toArray)) {
        CRM_Core_Error::statusBounce(ts('Selected contact(s) do not have a valid email address, or communication preferences specify DO NOT EMAIL, or they are deceased or Primary email address is On Hold.'));
      }
    }

    $this->assign('toContact', json_encode($toArray));

    $this->assign('suppressedEmails', count($this->suppressedEmails));

    $this->assign('totalSelectedContacts', count($this->_contactIds));

    $this->add('text', 'subject', ts('Subject'), ['size' => 50, 'maxlength' => 254], TRUE);

    $this->add('select', 'from_email_address', ts('From'), $this->getFromEmails(), TRUE, ['class' => 'crm-select2 huge']);

    CRM_Mailing_BAO_Mailing::commonCompose($this);

    // add attachments
    CRM_Core_BAO_File::buildAttachment($this, NULL);

    if ($this->_single) {
      CRM_Core_Session::singleton()->replaceUserContext($this->getRedirectUrl());
    }
    $this->addDefaultButtons(ts('Send Email'), 'upload', 'cancel');

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
          $this->add($values['type'], $field, $values['label'], $attribute, $required, $values['extra'] ?? NULL);
        }
      }
    }

    //Added for CRM-15984: Add campaign field
    CRM_Campaign_BAO_Campaign::addCampaign($this);

    $this->addFormRule([__CLASS__, 'saveTemplateFormRule'], $this);
    $this->addFormRule([__CLASS__, 'deprecatedTokensFormRule'], $this);
    CRM_Core_Resources::singleton()->addScriptFile('civicrm', 'templates/CRM/Contact/Form/Task/EmailCommon.js', 0, 'html-header');
  }

  /**
   * Set relevant default values.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public function setDefaultValues(): array {
    $defaults = parent::setDefaultValues() ?: [];
    $fromEmails = $this->getFromEmails();
    if (is_numeric(key($fromEmails))) {
      $emailID = (int) key($fromEmails);
      $defaults = CRM_Core_BAO_Email::getEmailSignatureDefaults($emailID);
    }
    if (!Civi::settings()->get('allow_mail_from_logged_in_contact')) {
      $defaults['from_email_address'] = CRM_Core_BAO_Domain::getFromEmail();
    }
    return $defaults;
  }

  protected function getFieldsToExcludeFromPurification(): array {
    return [
      // Because value contains <angle brackets>
      'from_email_address',
    ];
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @throws \Civi\API\Exception\UnauthorizedException
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    $this->bounceIfSimpleMailLimitExceeded(count($this->_contactIds));
    $formValues = $this->controller->exportValues($this->getName());
    $this->submit($formValues);
  }

  /**
   * Bounce if there are more emails than permitted.
   *
   * @param int $count
   *  The number of emails the user is attempting to send
   */
  protected function bounceIfSimpleMailLimitExceeded($count): void {
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
   * @throws \Civi\API\Exception\UnauthorizedException
   * @throws \CRM_Core_Exception
   */
  public function submit($formValues): void {
    $this->saveMessageTemplate($formValues);
    $from = $formValues['from_email_address'];
    // dev/core#357 User Emails are keyed by their id so that the Signature is able to be added
    // If we have had a contact email used here the value returned from the line above will be the
    // numerical key where as $from for use in the sendEmail in Activity needs to be of format of "To Name" <toemailaddress>
    $from = CRM_Utils_Mail::formatFromAddress($from);

    $cc = $this->getCc();
    $additionalDetails = empty($cc) ? '' : "\ncc : " . $this->getEmailUrlString($this->getCcArray());

    $bcc = $this->getBcc();
    $additionalDetails .= empty($bcc) ? '' : "\nbcc : " . $this->getEmailUrlString($this->getBccArray());

    // send the mail
    [$sent, $activityIds] = $this->sendEmail(
      $this->getSubmittedValue('text_message'),
      $this->getSubmittedValue('html_message'),
      $from,
      $this->getAttachments($formValues),
      $cc,
      $bcc,
      $additionalDetails,
      $formValues['campaign_id'] ?? NULL,
      $this->getCaseID()
    );

    if ($sent) {
      // Only use the first activity id if there's multiple.
      // If there's multiple recipients the idea behind multiple activities
      // is to record the token value replacements separately, but that
      // has no meaning for followup activities, and this doesn't prevent
      // creating more manually if desired.
      $followupStatus = $this->createFollowUpActivities($formValues, $activityIds[0]);

      CRM_Core_Session::setStatus(ts('One message was sent successfully. ', [
        'plural' => '%count messages were sent successfully. ',
        'count' => $sent,
      ]) . $followupStatus, ts('Message Sent', ['plural' => 'Messages Sent', 'count' => $sent]), 'success');
    }

    if (!empty($this->suppressedEmails)) {
      $status = '(' . ts('because no email address on file or communication preferences specify DO NOT EMAIL or Contact is deceased or Primary email address is On Hold') . ')<ul><li>' . implode('</li><li>', $this->suppressedEmails) . '</li></ul>';
      CRM_Core_Session::setStatus($status, ts('One Message Not Sent', [
        'count' => count($this->suppressedEmails),
        'plural' => '%count Messages Not Sent',
      ]), 'info');
    }
  }

  /**
   * Save the template if update selected.
   *
   * @param array $formValues
   *
   * @throws \CRM_Core_Exception
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
   * Get the emails from the added element.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getEmails(): array {
    $allEmails = explode(',', $this->getSubmittedValue('to'));
    $return = [];
    $contactIDs = [];
    foreach ($allEmails as $value) {
      $values = explode('::', $value);
      $return[$values[0]] = ['contact_id' => $values[0], 'email' => $values[1]];
      $contactIDs[] = $values[0];
    }
    $this->suppressedEmails = [];
    $suppressionDetails = Email::get(FALSE)
      ->addWhere('contact_id', 'IN', $contactIDs)
      ->addWhere('is_primary', '=', TRUE)
      ->addSelect('email', 'contact_id', 'contact_id.is_deceased', 'on_hold', 'contact_id.do_not_email', 'contact_id.display_name')
      ->execute();
    foreach ($suppressionDetails as $details) {
      if (empty($details['email']) || $details['contact_id.is_deceased'] || $details['contact_id.do_not_email'] || $details['on_hold']) {
        $this->setSuppressedEmail($details['contact_id'], [
          'on_hold' => $details['on_hold'],
          'is_deceased' => $details['contact_id.is_deceased'],
          'email' => $details['email'],
          'display_name' => $details['contact_id.display_name'],
        ]);
        unset($return[$details['contact_id']]);
      }
    }
    return $return;
  }

  /**
   * Get the string for the email IDs.
   *
   * @param array $emailIDs
   *   Array of email IDs.
   *
   * @return string
   *   e.g. "Smith, Bob<bob.smith@example.com>".
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function getEmailString(array $emailIDs): string {
    if (empty($emailIDs)) {
      return '';
    }
    $emails = Email::get()
      ->addWhere('id', 'IN', $emailIDs)
      ->setCheckPermissions(FALSE)
      ->setSelect(['contact_id', 'email', 'contact_id.sort_name', 'contact_id.display_name'])->execute();
    $emailStrings = [];
    foreach ($emails as $email) {
      $this->contactEmails[$email['id']] = $email;
      $emailStrings[] = '"' . $email['contact_id.sort_name'] . '" <' . $email['email'] . '>';
    }
    return implode(',', $emailStrings);
  }

  /**
   * Get the url string.
   *
   * This is called after the contacts have been retrieved so we don't need to re-retrieve.
   *
   * @param array $emailIDs
   *
   * @return string
   *   e.g. <a href='{$contactURL}'>Bob Smith</a>'
   */
  protected function getEmailUrlString(array $emailIDs): string {
    $urls = [];
    foreach ($emailIDs as $email) {
      $contactURL = CRM_Utils_System::url('civicrm/contact/view', ['reset' => 1, 'cid' => $this->contactEmails[$email]['contact_id']], TRUE);
      $urls[] = "<a href='{$contactURL}'>" . $this->contactEmails[$email]['contact_id.display_name'] . '</a>';
    }
    return implode(', ', $urls);
  }

  /**
   * Set the emails that are not to be sent out.
   *
   * @param int $contactID
   * @param array $values
   */
  protected function setSuppressedEmail($contactID, $values) {
    $contactViewUrl = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $contactID);
    $this->suppressedEmails[$contactID] = "<a href='$contactViewUrl' title='{$values['email']}'>{$values['display_name']}</a>" . ($values['on_hold'] ? '(' . ts('on hold') . ')' : '');
  }

  /**
   * Get any attachments.
   *
   * @param array $formValues
   *
   * @return array
   */
  protected function getAttachments(array $formValues): array {
    $attachments = [];
    CRM_Core_BAO_File::formatAttachment($formValues,
      $attachments,
      NULL, NULL
    );
    return $attachments;
  }

  /**
   * Get the subject for the message.
   *
   * @return string
   */
  protected function getSubject():string {
    return (string) $this->getSubmittedValue('subject');
  }

  /**
   * Create any follow up activities.
   *
   * @param array $formValues
   * @param int $activityId
   *
   * @return string
   *
   * @throws \CRM_Core_Exception
   */
  protected function createFollowUpActivities($formValues, $activityId): string {
    $params = [];
    $followupStatus = '';
    $followupActivity = NULL;
    if (!empty($formValues['followup_activity_type_id'])) {
      $params['followup_activity_type_id'] = $formValues['followup_activity_type_id'];
      $params['followup_activity_subject'] = $formValues['followup_activity_subject'];
      $params['followup_date'] = $formValues['followup_date'];
      $params['target_contact_id'] = $this->_contactIds;
      $params['followup_assignee_contact_id'] = array_filter(explode(',', $formValues['followup_assignee_contact_id']));
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
            $followupStatus .= '<br />' . ts('A copy of the follow-up activity has also been sent to follow-up assignee contacts(s).');
          }
        }
      }
    }
    return $followupStatus;
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
  public static function saveTemplateFormRule(array $fields) {
    $errors = [];
    //Added for CRM-1393
    if (!empty($fields['saveTemplate']) && empty($fields['saveTemplateName'])) {
      $errors['saveTemplateName'] = ts('Enter name to save message template');
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Prevent submission of deprecated tokens.
   *
   * Note this rule can be removed after a transition period.
   * It's mostly to help to ensure users don't get missing tokens
   * or unexpected output after the 5.43 upgrade until any
   * old templates have aged out.
   *
   * @param array $fields
   *
   * @return bool|string[]
   */
  public static function deprecatedTokensFormRule(array $fields) {
    $deprecatedTokens = [
      '{case.status_id}' => '{case.status_id:label}',
      '{case.case_type_id}' => '{case.case_type_id:label}',
      '{contribution.campaign}' => '{contribution.campaign_id:label}',
      '{contribution.payment_instrument}' => '{contribution.payment_instrument_id:label}',
      '{contribution.contribution_id}' => '{contribution.id}',
      '{contribution.contribution_source}' => '{contribution.source}',
      '{contribution.contribution_status}' => '{contribution.contribution_status_id:label}',
      '{contribution.contribution_cancel_date}' => '{contribution.cancel_date}',
      '{contribution.type}' => '{contribution.financial_type_id:label}',
      '{contribution.contribution_page_id}' => '{contribution.contribution_page_id:label}',
    ];
    $tokenErrors = [];
    foreach ($deprecatedTokens as $token => $replacement) {
      if (str_contains($fields['html_message'], $token)) {
        $tokenErrors[] = ts('Token %1 is no longer supported - use %2 instead', [$token, $replacement]);
      }
    }
    return empty($tokenErrors) ? TRUE : ['html_message' => implode('<br>', $tokenErrors)];
  }

  /**
   * Get selected contribution IDs.
   *
   * @return array
   */
  protected function getContributionIDs(): array {
    return [];
  }

  /**
   * Get case ID - if any.
   *
   * @return int|null
   *
   * @throws \CRM_Core_Exception
   */
  protected function getCaseID(): ?int {
    $caseID = CRM_Utils_Request::retrieve('caseid', 'String', $this);
    if ($caseID) {
      return (int) $caseID;
    }
    return NULL;
  }

  /**
   * @return array
   */
  protected function getFromEmails(): array {
    $fromEmailValues = CRM_Core_BAO_Email::getFromEmail();

    if (empty($fromEmailValues)) {
      CRM_Core_Error::statusBounce(ts('Your user record does not have a valid email address and no from addresses have been configured.'));
    }
    return $fromEmailValues;
  }

  /**
   * Get the relevant emails.
   *
   * @param int $index
   *
   * @return string
   */
  protected function getEmail(int $index): string {
    if (empty($this->emails)) {
      $toEmails = explode(',', $this->getSubmittedValue('to'));
      foreach ($toEmails as $value) {
        $parts = explode('::', $value);
        $this->emails[] = $parts[1];
      }
    }
    return $this->emails[$index];
  }

  /**
   * Send the message to all the contacts.
   *
   * Do not use this function outside of core tested code. It will change.
   *
   * It will also become protected once tests no longer call it.
   *
   * @internal
   *
   * Also insert a contact activity in each contacts record.
   *
   * @param $text
   * @param $html
   * @param string $from
   * @param array|null $attachments
   *   The array of attachments if any.
   * @param string|null $cc
   *   Cc recipient.
   * @param string|null $bcc
   *   Bcc recipient.
   * @param string|null $additionalDetails
   *   The additional information of CC and BCC appended to the activity Details.
   * @param int|null $campaignId
   * @param int|null $caseId
   *
   * @return array
   *   bool $sent FIXME: this only indicates the status of the last email sent.
   *   array $activityIds The activity ids created, one per "To" recipient.
   *
   * @throws \CRM_Core_Exception
   * @throws \PEAR_Exception
   * @internal
   *
   * Also insert a contact activity in each contacts record.
   *
   * @internal
   *
   * Also insert a contact activity in each contacts record.
   */
  protected function sendEmail(
    $text,
    $html,
    $from,
    $attachments = NULL,
    $cc = NULL,
    $bcc = NULL,
    $additionalDetails = NULL,
    $campaignId = NULL,
    $caseId = NULL
  ) {

    $userID = CRM_Core_Session::getLoggedInContactID();

    $sent = 0;
    $attachmentFileIds = [];
    $activityIds = [];
    $firstActivityCreated = FALSE;
    foreach ($this->getRowsForEmails() as $values) {
      $contactId = $values['contact_id'];
      $emailAddress = $values['email'];
      $renderedTemplate = CRM_Core_BAO_MessageTemplate::renderTemplate([
        'messageTemplate' => [
          'msg_text' => $text,
          'msg_html' => $html,
          'msg_subject' => $this->getSubject(),
        ],
        'tokenContext' => array_merge(['schema' => $this->getTokenSchema()], ($values['schema'] ?? [])),
        'contactId' => $contactId,
        'disableSmarty' => !CRM_Utils_Constant::value('CIVICRM_MAIL_SMARTY'),
      ]);

      // To minimize storage requirements, only one copy of any file attachments uploaded to CiviCRM is kept,
      // even when multiple contacts will receive separate emails from CiviCRM.
      if (!empty($attachmentFileIds)) {
        $attachments = array_replace_recursive($attachments, $attachmentFileIds);
      }

      // Create email activity.
      $activityID = $this->createEmailActivity($userID, $renderedTemplate['subject'], $renderedTemplate['html'], $renderedTemplate['text'], $additionalDetails, $campaignId, $attachments, $caseId);
      $activityIds[] = $activityID;

      if ($firstActivityCreated == FALSE && !empty($attachments)) {
        $attachmentFileIds = CRM_Activity_BAO_Activity::getAttachmentFileIds($activityID, $attachments);
        $firstActivityCreated = TRUE;
      }

      if ($this->sendMessage(
        $from,
        $contactId,
        $renderedTemplate['subject'],
        $renderedTemplate['text'],
        $renderedTemplate['html'],
        $emailAddress,
        $activityID,
        // get the set of attachments from where they are stored
        CRM_Core_BAO_File::getEntityFile('civicrm_activity', $activityID),
        $cc,
        $bcc
      )
      ) {
        $sent++;
      }
    }

    return [$sent, $activityIds];
  }

  /**
   * @param int $sourceContactID
   *   The contact ID of the email "from".
   * @param string $subject
   * @param string $html
   * @param string $text
   * @param string $additionalDetails
   *   The additional information of CC and BCC appended to the activity details.
   * @param int $campaignID
   * @param array $attachments
   * @param int $caseID
   *
   * @return int
   *   The created activity ID
   * @throws \CRM_Core_Exception
   */
  protected function createEmailActivity($sourceContactID, $subject, $html, $text, $additionalDetails, $campaignID, $attachments, $caseID) {
    $activityTypeID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Email');

    // CRM-6265: save both text and HTML parts in details (if present)
    if ($html and $text) {
      $details = "-ALTERNATIVE ITEM 0-\n{$html}{$additionalDetails}\n-ALTERNATIVE ITEM 1-\n{$text}{$additionalDetails}\n-ALTERNATIVE END-\n";
    }
    else {
      $details = $html ?: $text;
      $details .= $additionalDetails;
    }

    $activityParams = [
      'source_contact_id' => $sourceContactID,
      'activity_type_id' => $activityTypeID,
      'activity_date_time' => date('YmdHis'),
      'subject' => $subject,
      'details' => $details,
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Completed'),
      'campaign_id' => $this->getSubmittedValue('campaign_id'),
    ];
    if (!empty($caseID)) {
      $activityParams['case_id'] = $caseID;
    }

    // CRM-5916: strip [case #…] before saving the activity (if present in subject)
    $activityParams['subject'] = preg_replace('/\[case #([0-9a-h]{7})\] /', '', $activityParams['subject']);

    // add the attachments to activity params here
    if ($attachments) {
      // first process them
      $activityParams = array_merge($activityParams, $attachments);
    }

    $activity = civicrm_api3('Activity', 'create', $activityParams);

    return $activity['id'];
  }

  /**
   * Send message - under refactor.
   *
   * @param $from
   * @param $toID
   * @param $subject
   * @param $text_message
   * @param $html_message
   * @param $emailAddress
   * @param $activityID
   * @param null $attachments
   * @param null $cc
   * @param null $bcc
   *
   * @return bool
   * @throws \CRM_Core_Exception
   * @throws \PEAR_Exception
   */
  protected function sendMessage(
    $from,
    $toID,
    $subject,
    $text_message,
    $html_message,
    $emailAddress,
    $activityID,
    $attachments = NULL,
    $cc = NULL,
    $bcc = NULL
  ) {
    [$toDisplayName, $toEmail, $toDoNotEmail] = CRM_Contact_BAO_Contact::getContactDetails($toID);
    if ($emailAddress) {
      $toEmail = trim($emailAddress);
    }

    // make sure both email addresses are valid
    // and that the recipient wants to receive email
    if (empty($toEmail) or $toDoNotEmail) {
      return FALSE;
    }
    if (!trim($toDisplayName)) {
      $toDisplayName = $toEmail;
    }

    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    // create the params array
    $mailParams = [
      'groupName' => 'Activity Email Sender',
      'contactId' => $toID,
      'from' => $from,
      'toName' => $toDisplayName,
      'toEmail' => $toEmail,
      'subject' => $subject,
      'cc' => $cc,
      'bcc' => $bcc,
      'text' => $text_message,
      'html' => $html_message,
      'attachments' => $attachments,
    ];

    try {
      if (!CRM_Utils_Mail::send($mailParams)) {
        return FALSE;
      }
    }
    catch (\Exception $e) {
      CRM_Core_Error::statusBounce($e->getMessage());
    }

    // add activity target record for every mail that is send
    $activityTargetParams = [
      'activity_id' => $activityID,
      'contact_id' => $toID,
      'record_type_id' => $targetID,
    ];
    CRM_Activity_BAO_ActivityContact::create($activityTargetParams);
    return TRUE;
  }

  /**
   * Get the url to redirect the user's browser to.
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  protected function getRedirectUrl(): string {
    // also fix the user context stack
    if ($this->getCaseID()) {
      $ccid = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseContact', $this->_caseId,
        'contact_id', 'case_id'
      );
      $url = CRM_Utils_System::url('civicrm/contact/view/case',
        "&reset=1&action=view&cid={$ccid}&id=" . $this->getCaseID()
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
    return $url;
  }

  /**
   * Get the result rows to email.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getRowsForEmails(): array {
    $rows = [];
    foreach ($this->getRows() as $row) {
      $rows[$row['contact_id']][] = $row;
    }
    // format contact details array to handle multiple emails from same contact
    $formattedContactDetails = [];
    foreach ($this->getEmails() as $details) {
      $contactID = $details['contact_id'];
      $index = $contactID . '::' . $details['email'];
      if (!isset($rows[$contactID])) {
        $formattedContactDetails[$index] = $details;
        continue;
      }
      if ($this->isGroupByContact()) {
        foreach ($rows[$contactID] as $rowDetail) {
          $details['schema'] = $rowDetail['schema'] ?? [];
        }
        $formattedContactDetails[$index] = $details;
      }
      else {
        foreach ($rows[$contactID] as $key => $rowDetail) {
          $index .= '_' . $key;
          $formattedContactDetails[$index] = $details;
          $formattedContactDetails[$index]['schema'] = $rowDetail['schema'] ?? [];
        }
      }

    }
    return $formattedContactDetails;
  }

  /**
   * Only send one email per contact.
   *
   * This has historically been done for contributions & makes sense if
   * no entity specific tokens are in use.
   *
   * @return bool
   */
  protected function isGroupByContact(): bool {
    return TRUE;
  }

  /**
   * Get the tokens in the submitted message.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getMessageTokens(): array {
    return CRM_Utils_Token::getTokens($this->getSubject() . $this->getSubmittedValue('html_message') . $this->getSubmittedValue('text_message'));
  }

  /**
   * @return string
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function getBcc(): string {
    return $this->getEmailString($this->getBccArray());
  }

  /**
   * @return string
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function getCc(): string {
    return $this->getEmailString($this->getCcArray());
  }

  /**
   * @return array
   */
  protected function getCcArray() {
    if ($this->getSubmittedValue('cc_id')) {
      return explode(',', $this->getSubmittedValue('cc_id'));
    }
    return [];
  }

  /**
   * @return array
   */
  protected function getBccArray() {
    $bccArray = [];
    if ($this->getSubmittedValue('bcc_id')) {
      $bccArray = explode(',', $this->getSubmittedValue('bcc_id'));
    }
    return $bccArray;
  }

}
