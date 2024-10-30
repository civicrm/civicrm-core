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
class CRM_Mailing_Form_Approve extends CRM_Core_Form {

  public function redirectToListing() {
    $url = CRM_Utils_System::url('civicrm/mailing/browse/scheduled', 'reset=1&scheduled=true');
    CRM_Utils_System::redirect($url);
  }

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    if (CRM_Mailing_Info::workflowEnabled()) {
      if (!CRM_Core_Permission::check('approve mailings') && !CRM_Core_Permission::check('access CiviMail')) {
        $this->redirectToListing();
      }
    }
    else {
      $this->redirectToListing();
    }

    // when user come from search context.
    $this->_searchBasedMailing = CRM_Contact_Form_Search::isSearchContext($this->get('context'));

    //retrieve mid from different wizard and url contexts
    $this->_mailingID = $this->get('mailing_id');
    $this->_approveFormOnly = FALSE;
    if (!$this->_mailingID) {
      $this->_mailingID = CRM_Utils_Request::retrieve('mid', 'Integer', $this, TRUE);
      $this->_approveFormOnly = TRUE;
    }

    $session = CRM_Core_Session::singleton();
    $this->_contactID = $session->get('userID');

    $this->_mailing = new CRM_Mailing_BAO_Mailing();
    $this->_mailing->id = $this->_mailingID;
    if (!$this->_mailing->find(TRUE)) {
      $this->redirectToListing();
    }
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {
    $defaults = [];
    if ($this->_mailingID) {
      $defaults['approval_status_id'] = $this->_mailing->approval_status_id;
      $defaults['approval_note'] = $this->_mailing->approval_note;
    }

    return $defaults;
  }

  /**
   * Build the form object for the approval/rejection mailing.
   */
  public function buildQuickform() {
    $title = ts('Approve/Reject Mailing') . " - {$this->_mailing->name}";
    $this->setTitle($title);

    $this->addElement('textarea', 'approval_note', ts('Approve/Reject Note'));

    $mailApprovalStatus = CRM_Mailing_BAO_Mailing::buildOptions('approval_status_id');

    // eliminate the none option
    $noneOptionID = CRM_Core_PseudoConstant::getKey('CRM_Mailing_BAO_Mailing', 'approval_status_id', 'None');
    if ($noneOptionID) {
      unset($mailApprovalStatus[$noneOptionID]);
    }

    $this->addRadio('approval_status_id', ts('Approval Status'), $mailApprovalStatus, [], NULL, TRUE);

    $buttons = [
      [
        'type' => 'next',
        'name' => ts('Save'),
        'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ];

    $this->addButtons($buttons);

    // add the preview elements
    $preview = [];

    $preview['subject'] = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Mailing',
      $this->_mailingID,
      'subject'
    );

    $mailingKey = $this->_mailingID;
    if ($hash = CRM_Mailing_BAO_Mailing::getMailingHash($mailingKey)) {
      $mailingKey = $hash;
    }

    $preview['viewURL'] = CRM_Utils_System::url('civicrm/mailing/view', "reset=1&id={$mailingKey}");
    $preview['type'] = $this->_mailing->body_html ? 'html' : 'text';
    $preview['attachment'] = CRM_Core_BAO_File::attachmentInfo('civicrm_mailing', $this->_mailingID);

    $this->assign('preview', $preview);
  }

  /**
   * Process the posted form values.  Approve /reject a mailing.
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    if (isset($this->_mailingID)) {
      $params['id'] = $this->_mailingID;
    }
    else {
      $params['id'] = $this->get('mailing_id');
    }

    if (!$params['id']) {
      CRM_Core_Error::statusBounce(ts('No mailing id has been able to be determined'));
    }

    $params['approver_id'] = $this->_contactID;
    $params['approval_date'] = date('YmdHis');

    // if rejected, then we need to reset the scheduled date and scheduled id
    $rejectOptionID = CRM_Core_PseudoConstant::getKey('CRM_Mailing_BAO_Mailing', 'approval_status_id', 'Rejected');
    if ($rejectOptionID &&
      $params['approval_status_id'] == $rejectOptionID
    ) {
      $params['scheduled_id'] = 'null';
      $params['scheduled_date'] = 'null';

      // also delete any jobs associated with this mailing
      $job = new CRM_Mailing_BAO_MailingJob();
      $job->mailing_id = $params['id'];
      while ($job->fetch()) {
        CRM_Mailing_BAO_MailingJob::deleteRecord(['id' => $job->id]);
      }
    }
    else {
      $mailing = new CRM_Mailing_BAO_Mailing();
      $mailing->id = $params['id'];
      $mailing->find(TRUE);

      $params['scheduled_date'] = CRM_Utils_Date::processDate($mailing->scheduled_date);
    }

    CRM_Mailing_BAO_Mailing::create($params);

    //when user perform mailing from search context
    //redirect it to search result CRM-3711
    $ssID = $this->get('ssID');
    if ($ssID && $this->_searchBasedMailing) {
      if ($this->_action == CRM_Core_Action::BASIC) {
        $fragment = 'search';
      }
      elseif ($this->_action == CRM_Core_Action::PROFILE) {
        $fragment = 'search/builder';
      }
      elseif ($this->_action == CRM_Core_Action::ADVANCED) {
        $fragment = 'search/advanced';
      }
      else {
        $fragment = 'search/custom';
      }
      $context = $this->get('context');
      if (!CRM_Contact_Form_Search::isSearchContext($context)) {
        $context = 'search';
      }
      $urlParams = "force=1&reset=1&ssID={$ssID}&context={$context}";
      $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
      if (CRM_Utils_Rule::qfKey($qfKey)) {
        $urlParams .= "&qfKey=$qfKey";
      }

      $url = CRM_Utils_System::url('civicrm/contact/' . $fragment, $urlParams);
      return $this->controller->setDestination($url);
    }

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/mailing/browse/scheduled',
      'reset=1&scheduled=true'
    ));
  }

  /**
   * Display Name of the form.
   *
   *
   * @return string
   */
  public function getTitle() {
    return ts('Approve/Reject Mailing');
  }

}
