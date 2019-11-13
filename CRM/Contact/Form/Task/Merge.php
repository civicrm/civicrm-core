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
 * This class provides the functionality to Merge contacts.
 */
class CRM_Contact_Form_Task_Merge extends CRM_Contact_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preProcess();
    $statusMsg = NULL;
    $contactIds = [];
    if (is_array($this->_contactIds)) {
      $contactIds = array_unique($this->_contactIds);
    }
    if (count($contactIds) != 2) {
      $statusMsg = ts('Merge operation requires selecting two contacts.');
    }

    // do check for same contact type.
    $contactTypes = [];
    if (!$statusMsg) {
      $sql = "SELECT contact_type FROM civicrm_contact WHERE id IN (" . implode(',', $contactIds) . ")";
      $contact = CRM_Core_DAO::executeQuery($sql);
      while ($contact->fetch()) {
        $contactTypes[$contact->contact_type] = TRUE;
        if (count($contactTypes) > 1) {
          break;
        }
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
