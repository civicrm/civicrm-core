<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class provides the functionality to Merge Contacts.
 *
 */
class CRM_Contact_Form_Task_Merge extends CRM_Contact_Form_Task {

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preProcess();
    $statusMsg = NULL;
    $contactIds = array();
    if (is_array($this->_contactIds)) {
      $contactIds = array_unique($this->_contactIds);
    }
    if (count($contactIds) != 2) {
      $statusMsg = ts('Merge operation requires selecting two contacts.');
    }

    // do check for same contact type.
    $contactTypes = array();
    if (!$statusMsg) {
      $sql = "SELECT contact_type FROM civicrm_contact WHERE id IN (" . implode(',', $contactIds) . ")";
      $contact = CRM_Core_DAO::executeQuery($sql);
      while ($contact->fetch()) {
        $contactTypes[$contact->contact_type] = TRUE;
        if (count($contactTypes) > 1)
        break;
      }
      if (count($contactTypes) > 1) {
        $statusMsg = ts('Selected records must all be the same contact type (i.e. all Individuals).');
      }
    }
    if ($statusMsg) {
      CRM_Core_Error::statusBounce($statusMsg);
    }

    // redirect to merge form directly.
    $cid = $contactIds[0];
    $oid = $contactIds[1];

    //don't allow to delete logged in user.
    $session = CRM_Core_Session::singleton();
    if ($oid == $session->get('userID')) {
      $oid = $cid;
      $cid = $session->get('userID');
    }

    $url = CRM_Utils_System::url('civicrm/contact/merge', "reset=1&cid={$cid}&oid={$oid}");

    // redirect to merge page.
    CRM_Utils_System::redirect($url);
  }
}

