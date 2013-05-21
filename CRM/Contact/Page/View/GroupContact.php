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
class CRM_Contact_Page_View_GroupContact extends CRM_Core_Page {

  /**
   * This function is called when action is browse
   *
   * return null
   * @access public
   */
  function browse() {

    $count = CRM_Contact_BAO_GroupContact::getContactGroup($this->_contactId, NULL, NULL, TRUE);

    $in      = CRM_Contact_BAO_GroupContact::getContactGroup($this->_contactId, 'Added');
    $pending = CRM_Contact_BAO_GroupContact::getContactGroup($this->_contactId, 'Pending');
    $out     = CRM_Contact_BAO_GroupContact::getContactGroup($this->_contactId, 'Removed');

    // keep track of all 'added' contact groups so we can remove them from the smart group
    // section
    $staticGroups = array();
    if (!empty($in)) {
      foreach ($in as $group) {
        $staticGroups[$group['group_id']] = 1;
      }
    }

    $this->assign('groupCount', $count);
    $this->assign_by_ref('groupIn', $in);
    $this->assign_by_ref('groupPending', $pending);
    $this->assign_by_ref('groupOut', $out);

    // get the info on contact smart groups
    $contactSmartGroupSettings = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'contact_smart_group_display');
    $this->assign('contactSmartGroupSettings', $contactSmartGroupSettings);
  }

  /**
   * This function is called when action is update
   *
   * @param int    $groupID group id
   *
   * return null
   * @access public
   */
  function edit($groupId = NULL) {
    $controller = new CRM_Core_Controller_Simple(
      'CRM_Contact_Form_GroupContact',
      ts('Contact\'s Groups'),
      $this->_action
    );
    $controller->setEmbedded(TRUE);

    // set the userContext stack
    $session = CRM_Core_Session::singleton();

    $session->pushUserContext(
      CRM_Utils_System::url(
        'civicrm/contact/view',
        "action=browse&selectedChild=group&cid={$this->_contactId}"
      ),
      FALSE
    );
    $controller->reset();

    $controller->set('contactId', $this->_contactId);
    $controller->set('groupId', $groupId);

    $controller->process();
    $controller->run();
  }

  function preProcess() {
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->assign('contactId', $this->_contactId);

    // check logged in url permission
    CRM_Contact_Page_View::checkUserPermission($this);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->assign('action', $this->_action);
  }

  /**
   * This function is the main function that is called
   * when the page loads, it decides the which action has
   * to be taken for the page.
   *
   * return null
   * @access public
   */
  function run() {
    $this->preProcess();

    $displayName = CRM_Contact_BAO_Contact::displayName($this->_contactId);
    $this->assign('displayName', $displayName);

    if ($this->_action == CRM_Core_Action::DELETE) {
      $groupContactId = CRM_Utils_Request::retrieve('gcid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);
      $status = CRM_Utils_Request::retrieve('st', 'String', CRM_Core_DAO::$_nullObject, TRUE);
      if (is_numeric($groupContactId) && $status) {
        $this->del($groupContactId, $status, $this->_contactId);
      }
      $session = CRM_Core_Session::singleton();
      CRM_Utils_System::redirect($session->popUserContext());
    }

    $this->edit(NULL, CRM_Core_Action::ADD);
    $this->browse();
    return parent::run();
  }

  /**
   * function to remove/ rejoin the group
   *
   * @param int $groupContactId id of crm_group_contact
   * @param string $status this is the status that should be updated.
   *
   * $access public
   */
  static function del($groupContactId, $status, $contactID) {
    $groupId = CRM_Contact_BAO_GroupContact::getGroupId($groupContactId);

    switch ($status) {
      case 'i':
        $groupStatus = 'Added';
        break;

      case 'p':
        $groupStatus = 'Pending';
        break;

      case 'o':
        $groupStatus = 'Removed';
        break;

      case 'd':
        $groupStatus = 'Deleted';
        break;
    }

    $groupNum =
      CRM_Contact_BAO_GroupContact::getContactGroup($contactID, 'Added', NULL, TRUE, TRUE);
    if ($groupNum == 1 &&
      $groupStatus == 'Removed' &&
      CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MULTISITE_PREFERENCES_NAME,
        'is_enabled'
      )
    ) {
      CRM_Core_Session::setStatus(ts('Please ensure at least one contact group association is maintained.'), ts('Could Not Remove'));
      return FALSE;
    }

    $ids = array($contactID);
    $method = 'Admin';

    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');

    if ($userID == $contactID) {
      $method = 'Web';
    }

    CRM_Contact_BAO_GroupContact::removeContactsFromGroup($ids, $groupId, $method, $groupStatus);
  }
}

