<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 * This class helps to print the labels for contacts
 *
 */
class CRM_Event_Form_Task_Badge extends CRM_Event_Form_Task {

  /**
   * Are we operating in "single mode", i.e. sending email to one
   * specific contact?
   *
   * @var boolean
   */
  public $_single = FALSE;

  /**
   * Component clause.
   */
  public $_componentClause;

  /**
   * Build all the data structures needed to build the form.
   *
   * @param
   *
   * @return void
   */
  public function preProcess() {
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);
    if ($this->_context == 'view') {
      $this->_single = TRUE;

      $participantID = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
      $contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
      $this->_participantIds = array($participantID);
      $this->_componentClause = " civicrm_participant.id = $participantID ";
      $this->assign('totalSelectedParticipants', 1);

      // also set the user context to send back to view page
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/contact/view/participant',
        "reset=1&action=view&id={$participantID}&cid={$contactID}"
      ));
    }
    else {
      parent::preProcess();
    }
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Make Name Badges'));

    // Ajax submit would interfere with file download
    $this->preventAjaxSubmit();

    //add select for label
    $label = CRM_Badge_BAO_Layout::getList();

    $this->add('select',
      'badge_id',
      ts('Name Badge Format'),
      array(
        '' => ts('- select -'),
      ) + $label, TRUE
    );

    $next = 'next';
    $back = $this->_single ? 'cancel' : 'back';
    $this->addDefaultButtons(ts('Make Name Badges'), $next, $back);
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    CRM_Badge_BAO_Badge::buildBadges($params, $this);
  }

}
