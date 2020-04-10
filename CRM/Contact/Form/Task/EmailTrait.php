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
    CRM_Contact_Form_Task_EmailCommon::bounceIfSimpleMailLimitExceeded(count($this->_contactIds));

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
    CRM_Contact_Form_Task_EmailCommon::postProcess($this);
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
