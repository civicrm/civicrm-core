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
 * This page displays custom data during inline edit.
 */
class CRM_Contact_Page_Inline_CustomData extends CRM_Core_Page {

  /**
   * Run the page.
   *
   * This method is called after the page is created.
   */
  public function run() {
    // get the emails for this contact
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);
    $cgId = CRM_Utils_Request::retrieve('groupID', 'Positive', CRM_Core_DAO::$_nullObject, TRUE);
    $customRecId = CRM_Utils_Request::retrieve('customRecId', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, 1);
    $cgcount = CRM_Utils_Request::retrieve('cgcount', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, 1);

    //custom groups Inline
    $entityType = CRM_Contact_BAO_Contact::getContactType($contactId);
    $entitySubType = CRM_Contact_BAO_Contact::getContactSubType($contactId);
    // Custom group with VIEW permission
    $visibleGroups = CRM_Core_BAO_CustomGroup::getTree($entityType,
      NULL,
      $contactId,
      NULL,
      $entitySubType,
      NULL,
      TRUE,
      NULL,
      FALSE,
      CRM_Core_Permission::VIEW
    );
    $details = CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $visibleGroups, FALSE, NULL, NULL, NULL, $contactId, CRM_Core_Permission::EDIT);
    //get the fields of single custom group record
    if ($customRecId == 1) {
      $fields = reset($details[$cgId]);
    }
    else {
      $fields = $details[$cgId][$customRecId] ?? NULL;
    }
    $this->assign('cgcount', $cgcount);
    $this->assign('customRecId', $customRecId);
    $this->assign('contactId', $contactId);
    $this->assign('customGroupId', $cgId);
    $this->assign('cd_edit', $fields);

    // check logged in user permission
    CRM_Contact_Page_View::checkUserPermission($this, $contactId);

    // finally call parent
    parent::run();
  }

}
