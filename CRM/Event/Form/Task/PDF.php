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
 * $Id: PDF.php 45499 2013-02-08 12:31:05Z kurund $
 */

/**
 * This class provides the functionality to create PDF letter for a group of
 * participants or a single participant.
 */
class CRM_Event_Form_Task_PDF extends CRM_Event_Form_Task {

  use CRM_Contact_Form_Task_PDFTrait;

  /**
   * Are we operating in "single mode", i.e. printing letter to one
   * specific participant?
   *
   * @var bool
   */
  public $_single = FALSE;

  /**
   * All the existing templates in the system.
   *
   * @var array
   */
  public $_templates = NULL;
  public $_cid = NULL;
  public $_activityId = NULL;

  /**
   * The context this page is being rendered in
   *
   * @var string
   */
  protected $_context;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $this->preProcessPDF();
    $this->_context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
    if ($this->_context == 'view' || $this->_context == 'participant') {
      $this->_single = TRUE;

      $participantID = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
      $contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
      $this->_participantIds = [$participantID];
      $this->_componentClause = " civicrm_participant.id = $participantID ";
      $this->assign('totalSelectedParticipants', 1);

      if ($this->_context == 'view') {
        // also set the user context to send back to view page
        $session = CRM_Core_Session::singleton();
        $session->pushUserContext(CRM_Utils_System::url('civicrm/contact/view/participant',
          "reset=1&action=view&id={$participantID}&cid={$contactID}"
        ));
      }
    }
    else {
      parent::preProcess();
    }
    $this->assign('single', $this->_single);
  }

}
