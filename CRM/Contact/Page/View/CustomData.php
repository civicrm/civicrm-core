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
   * The record ID for the custom data being viewed/edited
   *
   * @var mixed
   */
  public $_recId;

  /**
   * The mode in which the page is being run. ('single' or null)
   *
   * @var string|null
   */
  public $_multiRecordDisplay;

  /**
   * @var int
   */
  public $_cgcount;

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   */
  public function run() {
    $this->_groupId = CRM_Utils_Request::retrieve('gid', 'Positive', $this, TRUE);
    $this->assign('groupId', $this->_groupId);

    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    $this->_recId = CRM_Utils_Request::retrieve('recId', 'Positive', $this);

    // If no cid supplied, look it up
    if (!$this->_contactId && $this->_recId) {
      $tableName = CRM_Core_BAO_CustomGroup::getGroup(['id' => $this->_groupId])['table_name'] ?? NULL;
      if ($tableName) {
        $this->_contactId = CRM_Core_DAO::singleValueQuery("SELECT entity_id FROM `$tableName` WHERE id = %1", [1 => [$this->_recId, 'Integer']]);
      }
    }
    if (!$this->_contactId) {
      throw new CRM_Core_Exception(ts('Could not find valid value for %1', [1 => 'cid']));
    }

    $this->assign('contactId', $this->_contactId);

    // check logged in url permission
    CRM_Contact_Page_View::checkUserPermission($this);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->assign('action', $this->_action);

    $this->_multiRecordDisplay = CRM_Utils_Request::retrieve('multiRecordDisplay', 'String', $this, FALSE);
    $this->_cgcount = CRM_Utils_Request::retrieve('cgcount', 'Positive', $this, FALSE);

    //set the userContext stack
    $doneURL = 'civicrm/contact/view';
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url($doneURL, 'action=browse&selectedChild=custom_' . $this->_groupId), FALSE);

    // Check permission to edit this contact
    $editPermission = CRM_Contact_BAO_Contact_Permission::allow($this->_contactId, CRM_Core_Permission::EDIT);
    $this->assign('editPermission', $editPermission);

    if ($this->_action == CRM_Core_Action::BROWSE) {

      $displayStyle = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup',
        $this->_groupId,
        'style'
      );

      if ($this->_multiRecordDisplay != 'single') {
        $id = "custom_{$this->_groupId}";
        $this->ajaxResponse['tabCount'] = CRM_Contact_BAO_Contact::getCountComponent($id, $this->_contactId);
      }

      if ($displayStyle === 'Tab with table' && $this->_multiRecordDisplay != 'single') {
        $ctype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $this->_contactId,
          'contact_type'
        );

        $this->assign('displayStyle', 'tableOriented');
        // here the multi custom data listing code will go
        $page = new CRM_Profile_Page_MultipleRecordFieldsListing();
        $page->_contactId = $this->_contactId;
        $page->_customGroupId = $this->_groupId;
        $page->set('action', CRM_Core_Action::BROWSE);
        $page->_pageViewType = 'customDataView';
        $page->_contactType = $ctype;
        $page->_headersOnly = TRUE;
        // assign vars to templates
        $this->assign('action', CRM_Core_Action::BROWSE);
        $page->browse();
      }
      else {
        //Custom Groups Inline
        $entityType = CRM_Contact_BAO_Contact::getContactType($this->_contactId);
        $entitySubType = CRM_Contact_BAO_Contact::getContactSubType($this->_contactId);
        $recId = NULL;
        if ($this->_multiRecordDisplay == 'single') {
          $groupTitle = CRM_Core_BAO_CustomGroup::getTitle($this->_groupId);
          CRM_Utils_System::setTitle(ts('View %1 Record', [1 => $groupTitle]));
          $groupTree = CRM_Core_BAO_CustomGroup::getTree($entityType, NULL, $this->_contactId,
            $this->_groupId, $entitySubType, NULL, TRUE, NULL, FALSE, CRM_Core_Permission::VIEW, $this->_cgcount
          );

          $recId = $this->_recId;
          $this->assign('multiRecordDisplay', $this->_multiRecordDisplay);
          $this->assign('skipTitle', 1);
        }
        else {
          $groupTree = CRM_Core_BAO_CustomGroup::getTree($entityType, NULL, $this->_contactId,
            $this->_groupId, $entitySubType, NULL, TRUE, NULL, FALSE, CRM_Core_Permission::VIEW
          );
        }
        CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, NULL, $recId, $this->_contactId, TRUE);
      }
    }
    else {

      $controller = new CRM_Core_Controller_Simple('CRM_Contact_Form_CustomData',
        ts('Custom Data'),
        $this->_action
      );
      $controller->setEmbedded(TRUE);

      $controller->set('tableId', $this->_contactId);
      $controller->set('groupId', $this->_groupId);
      $controller->set('entityType', CRM_Contact_BAO_Contact::getContactType($this->_contactId));
      $controller->set('entitySubType', CRM_Contact_BAO_Contact::getContactSubType($this->_contactId, ','));
      $controller->process();
      $controller->run();
    }
    return parent::run();
  }

}
