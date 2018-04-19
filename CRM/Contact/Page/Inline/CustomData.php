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
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, $_REQUEST);
    $cgId = CRM_Utils_Request::retrieve('groupID', 'Positive', CRM_Core_DAO::$_nullObject, TRUE, NULL, $_REQUEST);
    $customRecId = CRM_Utils_Request::retrieve('customRecId', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, 1, $_REQUEST);
    $cgcount = CRM_Utils_Request::retrieve('cgcount', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, 1, $_REQUEST);

    //custom groups Inline
    $entityType = CRM_Contact_BAO_Contact::getContactType($contactId);
    $entitySubType = CRM_Contact_BAO_Contact::getContactSubType($contactId);
    $groupTree = CRM_Core_BAO_CustomGroup::getTree($entityType, NULL, $contactId,
      $cgId, $entitySubType
    );
    $details = CRM_Core_BAO_CustomGroup::buildCustomDataView($this, $groupTree, FALSE, NULL, NULL, NULL, $contactId);
    //get the fields of single custom group record
    if ($customRecId == 1) {
      $fields = reset($details[$cgId]);
    }
    else {
      $fields = CRM_Utils_Array::value($customRecId, $details[$cgId]);
    }
    $this->assign('cgcount', $cgcount);
    $this->assign('customRecId', $customRecId);
    $this->assign('contactId', $contactId);
    $this->assign('customGroupId', $cgId);
    $this->assign_by_ref('cd_edit', $fields);

    // check logged in user permission
    CRM_Contact_Page_View::checkUserPermission($this, $contactId);

    // finally call parent
    parent::run();
  }

}
