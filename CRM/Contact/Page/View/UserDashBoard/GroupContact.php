<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Contact_Page_View_UserDashBoard_GroupContact extends CRM_Contact_Page_View_UserDashBoard {

  /**
   * Called when action is browse.
   */
  public function browse() {
    $count = CRM_Contact_BAO_GroupContact::getContactGroup(
      $this->_contactId,
      NULL,
      NULL, TRUE, TRUE,
      $this->_onlyPublicGroups,
      NULL, NULL, TRUE
    );

    $in = CRM_Contact_BAO_GroupContact::getContactGroup(
      $this->_contactId,
      'Added',
      NULL, FALSE, TRUE,
      $this->_onlyPublicGroups,
      NULL, NULL, TRUE
    );

    $pending = CRM_Contact_BAO_GroupContact::getContactGroup(
      $this->_contactId,
      'Pending',
      NULL, FALSE, TRUE,
      $this->_onlyPublicGroups,
      NULL, NULL, TRUE
    );

    $out = CRM_Contact_BAO_GroupContact::getContactGroup(
      $this->_contactId,
      'Removed',
      NULL, FALSE, TRUE,
      $this->_onlyPublicGroups,
      NULL, NULL, TRUE
    );

    $this->assign('groupCount', $count);
    $this->assign_by_ref('groupIn', $in);
    $this->assign_by_ref('groupPending', $pending);
    $this->assign_by_ref('groupOut', $out);
  }

  /**
   * called when action is update.
   *
   * @param int $groupId
   *
   * @return null
   */
  public function edit($groupId = NULL) {
    $this->assign('edit', $this->_edit);
    if (!$this->_edit) {
      return NULL;
    }

    $action = CRM_Utils_Request::retrieve('action', 'String',
      CRM_Core_DAO::$_nullObject,
      FALSE, 'browse'
    );

    if ($action == CRM_Core_Action::DELETE) {
      $groupContactId = CRM_Utils_Request::retrieve('gcid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);
      $status = CRM_Utils_Request::retrieve('st', 'String', CRM_Core_DAO::$_nullObject, TRUE);
      if (is_numeric($groupContactId) && $status) {
        CRM_Contact_Page_View_GroupContact::del($groupContactId, $status, $this->_contactId);
      }

      $url = CRM_Utils_System::url('civicrm/user', "reset=1&id={$this->_contactId}");
      CRM_Utils_System::redirect($url);
    }

    $controller = new CRM_Core_Controller_Simple(
      'CRM_Contact_Form_GroupContact',
      ts("Contact's Groups"),
      CRM_Core_Action::ADD,
      FALSE, FALSE, TRUE, FALSE
    );
    $controller->setEmbedded(TRUE);

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(
      CRM_Utils_System::url('civicrm/user', "reset=1&id={$this->_contactId}"),
      FALSE
    );

    $controller->reset();
    $controller->set('contactId', $this->_contactId);
    $controller->set('groupId', $groupId);
    $controller->set('context', 'user');
    $controller->set('onlyPublicGroups', $this->_onlyPublicGroups);
    $controller->process();
    $controller->run();
  }

  /**
   * The main function that is called when the page loads.
   *
   * It decides the which action has to be taken for the page.
   */
  public function run() {
    $this->edit();
    $this->browse();
  }

}
