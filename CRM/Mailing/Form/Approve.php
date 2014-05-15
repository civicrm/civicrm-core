<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.5                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 *
 */
class CRM_Mailing_Form_Approve extends CRM_Core_Form {

  public function redirectToListing() {
    $url = CRM_Utils_System::url('civicrm/mailing/browse/scheduled', 'reset=1&scheduled=true');
    CRM_Utils_System::redirect($url);
  }

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    if (CRM_Mailing_Info::workflowEnabled()) {
      if (!CRM_Core_Permission::check('approve mailings')) {
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
   * This function sets the default values for the form.
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    $defaults = array();
    if ($this->_mailingID) {
      $defaults['approval_status_id'] = $this->_mailing->approval_status_id;
      $defaults['approval_note'] = $this->_mailing->approval_note;
    }

    return $defaults;
  }

  /**
   * Build the form for the approval/rejection mailing
   *
   * @param
   *
   * @return void
   * @access public
   */
  public function buildQuickform() {
    $title = ts('Approve/Reject Mailing') . " - {$this->_mailing->name}";
    CRM_Utils_System::setTitle($title);

    $this->addElement('textarea', 'approval_note', ts('Approve/Reject Note'));

    $mailApprovalStatus = CRM_Core_OptionGroup::values('mail_approval_status');

    // eliminate the none option
    $noneOptionID = CRM_Core_OptionGroup::getValue('mail_approval_status',
      'None',
      'name'
    );
    if ($noneOptionID) {
      unset($mailApprovalStatus[$noneOptionID]);
    }

    $this->addRadio('approval_status_id', ts('Approval Status'), $mailApprovalStatus, TRUE, NULL, TRUE);

    $buttons = array(
      array('type' => 'next',
        'name' => ts('Save'),
        'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    );

    $this->addButtons($buttons);

    // add the preview elements
    $preview = array();

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

    $this->assign_by_ref('preview', $preview);
  }

  /**
   * Process the posted form values.  Approve /reject a mailing.
   *
   * @param
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    $ids = array();
    if (isset($this->_mailingID)) {
      $ids['mailing_id'] = $this->_mailingID;
    }
    else {
      $ids['mailing_id'] = $this->get('mailing_id');
    }

    if (!$ids['mailing_id']) {
      CRM_Core_Error::fatal();
    }

    $params['approver_id'] = $this->_contactID;
    $params['approval_date'] = date('YmdHis');

    // if rejected, then we need to reset the scheduled date and scheduled id
    $rejectOptionID = CRM_Core_OptionGroup::getValue('mail_approval_status',
      'Rejected',
      'name'
    );
    if ($rejectOptionID &&
      $params['approval_status_id'] == $rejectOptionID
    ) {
      $params['scheduled_id'] = 'null';
      $params['scheduled_date'] = 'null';

      // also delete any jobs associated with this mailing
      $job = new CRM_Mailing_BAO_MailingJob();
      $job->mailing_id = $ids['mailing_id'];
      $job->delete();
    }
    else {
      $mailing = new CRM_Mailing_BAO_Mailing();
      $mailing->id = $ids['mailing_id'];
      $mailing->find(TRUE);

      $params['scheduled_date'] = CRM_Utils_Date::processDate($mailing->scheduled_date);
    }

    CRM_Mailing_BAO_Mailing::create($params, $ids);


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
   * Display Name of the form
   *
   * @access public
   *
   * @return string
   */
  public function getTitle() {
    return ts('Approve/Reject Mailing');
  }
}

