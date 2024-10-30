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
 * This class helps to print the labels for contacts.
 */
class CRM_Event_Form_Task_Badge extends CRM_Event_Form_Task {

  /**
   * Are we operating in "single mode", i.e. sending email to one
   * specific contact?
   *
   * @var bool
   */
  public $_single = FALSE;

  /**
   * Component clause.
   * @var string
   */
  public $_componentClause;

  /**
   * The context this page is being rendered in
   *
   * @var string
   */
  protected $_context;

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
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
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->setTitle(ts('Make Name Badges'));

    // Ajax submit would interfere with file download
    $this->preventAjaxSubmit();

    //add select for label
    $label = CRM_Badge_BAO_Layout::getList();

    $this->add('select',
      'badge_id',
      ts('Name Badge Format'),
      [
        '' => ts('- select -'),
      ] + $label, TRUE
    );

    $next = 'next';
    $back = $this->_single ? 'cancel' : 'back';
    $this->addDefaultButtons(ts('Make Name Badges'), $next, $back);
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    CRM_Badge_BAO_Badge::buildBadges($params, $this);
  }

}
