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
 * Dummy page for details of SMS
 *
 */
class CRM_Contact_Page_View_SMS extends CRM_Core_Page {

  /**
   * Run the page.
   *
   * This method is called after the page is created.
   *
   * @return void
   * @access public
   *
   */
  function run() {
    // get the callback, module and activity id
    $action = CRM_Utils_Request::retrieve('action', 'String',
      $this, FALSE, 'browse'
    );
    $id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this
    );

    $dao                = new CRM_Core_DAO_ActivityHistory();
    $dao->activity_id   = $id;
    $dao->activity_type = ts('SMS Sent');
    if ($dao->find(TRUE)) {
      $cid = $dao->entity_id;
    }

    $dao = new CRM_SMS_DAO_History();
    $dao->id = $id;

    if ($dao->find(TRUE)) {
      $this->assign('fromName',
        CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $dao->contact_id,
          'display_name'
        )
      );
      $this->assign('toName',
        CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $cid,
          'display_name'
        )
      );
      $this->assign('sentDate', $dao->sent_date);
      $this->assign('message', $dao->message);

      // get the display name and images for the contact
      list($displayName, $contactImage) = CRM_Contact_BAO_Contact::getDisplayAndImage($dao->contact_id);

      CRM_Utils_System::setTitle($contactImage . ' ' . $displayName);

      // also add the cid params to the Menu array
      CRM_Core_Menu::addParam('cid', $cid);
    }
    return parent::run();
  }
}

