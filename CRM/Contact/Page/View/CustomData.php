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
 * Page for displaying custom data.
 */
class CRM_Contact_Page_View_CustomData extends CRM_Core_Page {

  /**
   * Custom group id.
   *
   * @var int
   */
  public $_groupId;

  /**
   * The contact being viewed/edited
   *
   * @var int
   */
  public $_contactId;

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   */
  public function run() {
    $this->_groupId = CRM_Utils_Request::retrieve('gid', 'Positive', $this, TRUE);
    $this->assign('groupId', $this->_groupId);

    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->assign('contactId', $this->_contactId);

    // check logged in url permission
    CRM_Contact_Page_View::checkUserPermission($this);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->assign('action', $this->_action);

    //set the userContext stack
    $doneURL = 'civicrm/contact/view';
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url($doneURL, 'action=browse&selectedChild=custom_' . $this->_groupId), FALSE);

    // Check permission to edit this contact
    $editPermission = CRM_Contact_BAO_Contact_Permission::allow($this->_contactId, CRM_Core_Permission::EDIT);
    $this->assign('editPermission', $editPermission);

    $id = "custom_{$this->_groupId}";
    $this->ajaxResponse['tabCount'] = CRM_Contact_BAO_Contact::getCountComponent($id, $this->_contactId);

    $entityType = CRM_Contact_BAO_Contact::getContactType($this->_contactId);
    $entitySubType = CRM_Contact_BAO_Contact::getContactSubType($this->_contactId);
    $groupTree = CRM_Core_BAO_CustomGroup::getTree($entityType, NULL, $this->_contactId,
      $this->_groupId, $entitySubType, NULL, TRUE, NULL, FALSE, CRM_Core_Permission::VIEW
    );
    CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, NULL, NULL, $this->_contactId, TRUE);

    return parent::run();
  }

}
