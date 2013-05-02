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
class CRM_Group_Page_Group extends CRM_Core_Page_Basic {
  protected $_sortByCharacter;

  function getBAOName() {
    return 'CRM_Contact_BAO_Group';
  }

  /**
   * Function to define action links
   *
   * @return array self::$_links array of action links
   * @access public
   */
  function &links() {}

  /**
   * return class name of edit form
   *
   * @return string
   * @access public
   */
  function editForm() {
    return 'CRM_Group_Form_Edit';
  }

  /**
   * return name of edit form
   *
   * @return string
   * @access public
   */
  function editName() {
    return ts('Edit Group');
  }

  /**
   * return name of delete form
   *
   * @return string
   * @access public
   */
  function deleteName() {
    return 'Delete Group';
  }

  /**
   * return user context uri to return to
   *
   * @return string
   * @access public
   */
  function userContext($mode = NULL) {
    return 'civicrm/group';
  }

  /**
   * return user context uri params
   *
   * @return string
   * @access public
   */
  function userContextParams($mode = NULL) {
    return 'reset=1&action=browse';
  }

  /**
   * make sure that the user has permission to access this group
   *
   * @param int $id   the id of the object
   * @param int $name the name or title of the object
   *
   * @return string   the permission that the user has (or null)
   * @access public
   */
  function checkPermission($id, $title) {
    return CRM_Contact_BAO_Group::checkPermission($id, $title);
  }

  /**
   * We need to do slightly different things for groups vs saved search groups, hence we
   * reimplement browse from Page_Basic
   *
   * @param int $action
   *
   * @return void
   * @access public
   */
  function browse($action = NULL) {
    $groupPermission = CRM_Core_Permission::check('edit groups') ? CRM_Core_Permission::EDIT : CRM_Core_Permission::VIEW;
    $this->assign('groupPermission', $groupPermission);

    $showOrgInfo = FALSE;

    // CRM-9936
    $reservedPermission = CRM_Core_Permission::check('administer reserved groups') ? CRM_Core_Permission::EDIT : CRM_Core_Permission::VIEW;
    $this->assign('reservedPermission', $reservedPermission);

    if (CRM_Core_Permission::check('administer Multiple Organizations') &&
      CRM_Core_Permission::isMultisiteEnabled()
    ) {
      $showOrgInfo = TRUE;
    }
    $this->assign('showOrgInfo', $showOrgInfo);

    $this->search();
  }

  function search() {
    if ($this->_action &
      (CRM_Core_Action::ADD |
        CRM_Core_Action::UPDATE |
        CRM_Core_Action::DELETE
      )
    ) {
      return;
    }

    $form = new CRM_Core_Controller_Simple('CRM_Group_Form_Search', ts('Search Groups'), CRM_Core_Action::ADD);
    $form->setEmbedded(TRUE);
    $form->setParent($this);
    $form->process();
    $form->run();
  }
}

