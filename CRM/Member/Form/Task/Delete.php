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
 * This class provides the functionality to delete a group of
 * members. This class provides functionality for the actual
 * deletion.
 */
class CRM_Member_Form_Task_Delete extends CRM_Member_Form_Task {

  /**
   * Are we operating in "single mode", i.e. deleting one
   * specific membership?
   *
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */ 
  function preProcess() {
    //check for delete
    if (!CRM_Core_Permission::checkActionPermission('CiviMember', CRM_Core_Action::DELETE)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
    }
    parent::preProcess();
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    $this->addDefaultButtons(ts('Delete Members'), 'done');
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $deletedMembers = 0;
    foreach ($this->_memberIds as $memberId) {
      if (CRM_Member_BAO_Membership::deleteMembership($memberId)) {
        $deletedMembers++;
      }
    }

    CRM_Core_Session::setStatus($deletedMembers, ts('Deleted Member(s)'), 'success');
    CRM_Core_Session::setStatus(ts('Total Selected Membership(s): %1', array(1 => count($this->_memberIds))), '', 'info');
  }
}

