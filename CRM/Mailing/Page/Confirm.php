<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 */
class CRM_Mailing_Page_Confirm extends CRM_Core_Page {
  /**
   * @return string
   * @throws Exception
   */
  public function run() {
    CRM_Utils_System::addHTMLHead('<META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">');

    $contact_id = CRM_Utils_Request::retrieve('cid', 'Integer', CRM_Core_DAO::$_nullObject);
    $subscribe_id = CRM_Utils_Request::retrieve('sid', 'Integer', CRM_Core_DAO::$_nullObject);
    $hash = CRM_Utils_Request::retrieve('h', 'String', CRM_Core_DAO::$_nullObject);

    if (!$contact_id ||
      !$subscribe_id ||
      !$hash
    ) {
      CRM_Core_Error::fatal(ts("Missing input parameters"));
    }

    $result = CRM_Mailing_Event_BAO_Confirm::confirm($contact_id, $subscribe_id, $hash);
    if ($result === FALSE) {
      $this->assign('success', $result);
    }
    else {
      $this->assign('success', TRUE);
      $this->assign('group', $result);
    }

    list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contact_id);
    $this->assign('display_name', $displayName);
    $this->assign('email', $email);

    return parent::run();
  }

}
