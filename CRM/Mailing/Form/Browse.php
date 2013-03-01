<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Build the form for disable mail feature
 *
 * @param
 *
 * @return void
 * @access public
 */
class CRM_Mailing_Form_Browse extends CRM_Core_Form {

  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   *
   * @return void
   * @access public
   *
   */
  function preProcess() {
    $this->_mailingId = CRM_Utils_Request::retrieve('mid', 'Positive', $this);
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);

    // check for action permissions.
    if (!CRM_Core_Permission::checkActionPermission('CiviMail', $this->_action)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
    }

    $mailing     = new CRM_Mailing_BAO_Mailing();
    $mailing->id = $this->_mailingId;
    $subject     = '';
    if ($mailing->find(TRUE)) {
      $subject = $mailing->subject;
    }
    $this->assign('subject', $subject);
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */

  public function buildQuickForm() {
    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Confirm'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Mailing_BAO_Mailing::del($this->_mailingId);
    }
    elseif ($this->_action & CRM_Core_Action::DISABLE) {
      CRM_Mailing_BAO_Job::cancel($this->_mailingId);
    }
    elseif ($this->_action & CRM_Core_Action::RENEW) {
      //set is_archived to 1
      CRM_Core_DAO::setFieldValue('CRM_Mailing_DAO_Mailing', $this->_mailingId, 'is_archived', TRUE);
    }
  }
  //end of function
}

