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
 * This class generates form components for processing a pcp page.
 */
class CRM_PCP_Form_Campaign extends CRM_Core_Form {
  public $_context;
  public $_component;

  /**
   * Pre-process form.
   */
  public function preProcess() {
    // we do not want to display recently viewed items, so turn off
    $this->assign('displayRecent', FALSE);

    // component null in controller object - fix? dgg
    // $this->_component = $this->controller->get('component');
    $this->_component = CRM_Utils_Request::retrieve('component', 'String', $this);
    $this->assign('component', $this->_component);

    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
    $this->assign('context', $this->_context);

    $this->_pageId = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
    $title = ts('Setup a Personal Campaign Page - Step 2');

    if ($this->_pageId) {
      $title = ts('Edit Your Personal Campaign Page');
    }

    $this->setTitle($title);
    parent::preProcess();
  }

  /**
   * Set default form values.
   *
   * @return array
   *   Default values for the form.
   */
  public function setDefaultValues() {
    $defaults = [];
    $dao = new CRM_PCP_DAO_PCP();

    if ($this->_pageId) {
      $dao->id = $this->_pageId;
      if ($dao->find(TRUE)) {
        CRM_Core_DAO::storeValues($dao, $defaults);
      }
      // fix the display of the monetary value, CRM-4038
      if (isset($defaults['goal_amount'])) {
        $defaults['goal_amount'] = CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($defaults['goal_amount']);
      }

      $defaults['pcp_title'] = $defaults['title'] ?? NULL;
      $defaults['pcp_intro_text'] = $defaults['intro_text'] ?? NULL;
    }

    if ($this->get('action') & CRM_Core_Action::ADD) {
      $defaults['is_active'] = 1;
      $defaults['is_honor_roll'] = 1;
      $defaults['is_thermometer'] = 1;
      $defaults['is_notify'] = 1;
    }

    $this->_contactID = $defaults['contact_id'] ?? NULL;
    $this->_contriPageId = $defaults['page_id'] ?? NULL;

    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->add('text', 'pcp_title', ts('Title'), NULL, TRUE);
    $this->add('textarea', 'pcp_intro_text', ts('Welcome'), NULL, TRUE);
    $this->add('text', 'goal_amount', ts('Your Goal'), NULL, TRUE);
    $this->addRule('goal_amount', ts('Goal Amount should be a numeric value'), 'money');

    $attributes = [];
    if ($this->_component == 'event') {
      if ($this->get('action') & CRM_Core_Action::ADD) {
        $attributes = ['value' => ts('Join Us'), 'onClick' => 'select();'];
      }
      $this->add('text', 'donate_link_text', ts('Sign up Button'), $attributes);
    }
    else {
      if ($this->get('action') & CRM_Core_Action::ADD) {
        $attributes = ['value' => ts('Donate Now'), 'onClick' => 'select();'];
      }
      $this->add('text', 'donate_link_text', ts('Donation Button'), $attributes);
    }

    $attrib = ['rows' => 8, 'cols' => 60];
    $this->add('wysiwyg', 'page_text', ts('Your Message'), NULL, FALSE);

    $maxAttachments = 1;
    CRM_Core_BAO_File::buildAttachment($this, 'civicrm_pcp', $this->_pageId, $maxAttachments);

    $this->addElement('checkbox', 'is_thermometer', ts('Progress Bar'));
    $this->addElement('checkbox', 'is_honor_roll', ts('Honor Roll'), NULL);
    if ($this->_pageId) {
      $params = ['id' => $this->_pageId];
      CRM_Core_DAO::commonRetrieve('CRM_PCP_DAO_PCP', $params, $pcpInfo);
      $owner_notification_option = CRM_Core_DAO::getFieldValue('CRM_PCP_BAO_PCPBlock', $pcpInfo['pcp_block_id'], 'owner_notify_id');
    }
    else {
      $owner_notification_option = CRM_PCP_BAO_PCP::getOwnerNotificationId($this->controller->get('component_page_id'), $this->_component ?: 'contribute');
    }
    if ($owner_notification_option == CRM_Core_PseudoConstant::getKey('CRM_PCP_BAO_PCPBlock', 'owner_notify_id', 'owner_chooses')) {
      $this->assign('owner_notification_option', TRUE);
      $this->addElement('checkbox', 'is_notify', ts('Notify me via email when someone donates to my page'), NULL);
    }

    $this->addElement('checkbox', 'is_active', ts('Active'));
    if ($this->_context == 'dashboard') {
      CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/admin/pcp', 'reset=1'));
    }

    $this->addButtons(
      [
        [
          'type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );
    $this->addFormRule(['CRM_PCP_Form_Campaign', 'formRule'], $this);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param self $self
   *
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public static function formRule($fields, $files, $self) {
    $errors = [];
    if ($fields['goal_amount'] <= 0) {
      $errors['goal_amount'] = ts('Goal Amount should be a numeric value greater than zero.');
    }
    if (strlen($fields['donate_link_text']) >= 64) {
      $errors['donate_link_text'] = ts('Button Text must be less than 64 characters.');
    }
    return $errors;
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $checkBoxes = ['is_thermometer', 'is_honor_roll', 'is_active', 'is_notify'];

    foreach ($checkBoxes as $key) {
      if (!isset($params[$key])) {
        $params[$key] = 0;
      }
    }
    $session = CRM_Core_Session::singleton();
    $contactID = $this->_contactID ?? $session->get('userID');
    if (!$contactID) {
      $contactID = $this->get('contactID');
    }
    $params['title'] = $params['pcp_title'];
    $params['intro_text'] = $params['pcp_intro_text'];
    $params['contact_id'] = $contactID;
    $params['page_id'] = $this->get('component_page_id') ?: $this->_contriPageId;
    $params['page_type'] = $this->_component;

    // since we are allowing html input from the user
    // we also need to purify it, so lets clean it up
    $htmlFields = ['intro_text', 'page_text', 'title'];
    foreach ($htmlFields as $field) {
      if (!empty($params[$field])) {
        $params[$field] = CRM_Utils_String::purifyHTML($params[$field]);
      }
    }

    $entity_table = CRM_PCP_BAO_PCP::getPcpEntityTable($params['page_type']);

    $pcpBlock = new CRM_PCP_DAO_PCPBlock();
    $pcpBlock->entity_table = $entity_table;
    $pcpBlock->entity_id = $params['page_id'];
    $pcpBlock->find(TRUE);

    $params['pcp_block_id'] = $pcpBlock->id;

    $params['goal_amount'] = CRM_Utils_Rule::cleanMoney($params['goal_amount']);

    $approval_needed = $pcpBlock->is_approval_needed;
    $approvalMessage = NULL;

    if ($this->get('action') & CRM_Core_Action::ADD) {
      $params['status_id'] = $approval_needed ? 1 : 2;
      $approvalMessage = $approval_needed ? ts('but requires administrator review before you can begin promoting your campaign. You will receive an email confirmation shortly which includes a link to return to this page.') : ts('and is ready to use.');
    }

    $params['id'] = $this->_pageId;

    $pcp = CRM_PCP_BAO_PCP::writeRecord($params);

    // add attachments as needed
    CRM_Core_BAO_File::formatAttachment($params,
      $params,
      'civicrm_pcp',
      $pcp->id
    );

    $pageStatus = isset($this->_pageId) ? ts('updated') : ts('created');
    $statusId = CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCP', $pcp->id, 'status_id');

    //send notification of PCP create/update.
    $pcpParams = ['entity_table' => $entity_table, 'entity_id' => $pcp->page_id];
    $notifyParams = [];
    $notifyStatus = "";
    CRM_Core_DAO::commonRetrieve('CRM_PCP_DAO_PCPBlock', $pcpParams, $notifyParams, ['notify_email']);

    if ($emails = $pcpBlock->notify_email) {
      $this->assign('pcpTitle', $pcp->title);

      if ($this->_pageId) {
        $this->assign('mode', 'Update');
      }
      else {
        $this->assign('mode', 'Add');
      }
      $pcpStatus = CRM_Core_PseudoConstant::getLabel('CRM_PCP_DAO_PCP', 'status_id', $statusId);
      $this->assign('pcpStatus', $pcpStatus);

      $this->assign('pcpId', $pcp->id);

      $supporterUrl = CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$pcp->contact_id}",
        TRUE, NULL, FALSE,
        FALSE, TRUE
      );
      $this->assign('supporterUrl', $supporterUrl);
      $supporterName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $pcp->contact_id, 'display_name');
      $this->assign('supporterName', $supporterName);

      if ($this->_component == 'contribute') {
        $pageUrl = CRM_Utils_System::url('civicrm/contribute/transact',
          "reset=1&id={$pcpBlock->entity_id}",
          TRUE, NULL, FALSE,
          TRUE
        );
        $contribPageTitle = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $pcpBlock->entity_id, 'title');
      }
      elseif ($this->_component == 'event') {
        $pageUrl = CRM_Utils_System::url('civicrm/event',
          "reset=1&id={$pcpBlock->entity_id}",
          TRUE, NULL, FALSE,
          TRUE
        );
        $contribPageTitle = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $pcpBlock->entity_id, 'title');
      }

      $this->assign('contribPageUrl', $pageUrl);
      $this->assign('contribPageTitle', $contribPageTitle);

      $managePCPUrl = CRM_Utils_System::url('civicrm/admin/pcp',
        "reset=1",
        TRUE, NULL, FALSE,
        FALSE, TRUE
      );
      $this->assign('managePCPUrl', $managePCPUrl);

      //get the default domain email address.
      list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();

      if (!$domainEmailAddress || $domainEmailAddress == 'info@EXAMPLE.ORG') {
        $fixUrl = CRM_Utils_System::url('civicrm/admin/options/site_email_address');
        CRM_Core_Error::statusBounce(ts('The site administrator needs to enter a valid "Site From Email Address" in <a href="%1">Administer CiviCRM &raquo; Communications &raquo; Site Email Addresses</a>. The email address used may need to be a valid mail account with your email service provider.', [1 => $fixUrl]));
      }

      //if more than one email present for PCP notification ,
      //first email take it as To and other as CC and First email
      //address should be sent in users email receipt for
      //support purpose.
      $emailArray = explode(',', $emails);
      $to = $emailArray[0];
      unset($emailArray[0]);
      $cc = implode(',', $emailArray);

      list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate(
        [
          'groupName' => 'msg_tpl_workflow_contribution',
          'workflow' => 'pcp_notify',
          'contactId' => $contactID,
          'from' => "$domainEmailName <$domainEmailAddress>",
          'toEmail' => $to,
          'cc' => $cc,
        ]
      );

      if ($sent) {
        $notifyStatus = ts('A notification email has been sent to the site administrator.');
      }
    }

    CRM_Core_BAO_File::processAttachment($params, 'civicrm_pcp', $pcp->id);

    // send email notification to supporter, if initial setup / add mode.
    if (!$this->_pageId) {
      CRM_PCP_BAO_PCP::sendStatusUpdate($pcp->id, $statusId, TRUE, $this->_component);
      if ($approvalMessage && ($params['status_id'] ?? NULL) == 1) {
        $notifyStatus .= ' ' . ts('You will receive a second email as soon as the review process is complete.');
      }
    }

    //check if pcp created by anonymous user
    $anonymousPCP = 0;
    if (!$session->get('userID')) {
      $anonymousPCP = 1;
    }

    CRM_Core_Session::setStatus(ts("Your Personal Campaign Page has been %1 %2 %3",
      [1 => $pageStatus, 2 => $approvalMessage, 3 => $notifyStatus]
    ), '', 'info');
    if (!$this->_pageId) {
      $session->pushUserContext(CRM_Utils_System::url('civicrm/pcp/info', "reset=1&id={$pcp->id}&ap={$anonymousPCP}"));
    }
    elseif ($this->_context == 'dashboard') {
      $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/pcp', 'reset=1'));
    }
  }

}
