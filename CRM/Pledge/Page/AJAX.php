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
 * This class contains all the function that are called using AJAX
 */
class CRM_Pledge_Page_AJAX {

  /**
   * Function to setDefaults according to Pledge Id
   * for batch entry pledges
   */
  public static function getPledgeDefaults() {
    $details = [];
    $pledgeID = CRM_Utils_Request::retrieve('pid', 'Positive', NULL, FALSE, NULL, 'POST');
    if ($pledgeID) {
      $contactId = (int) CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_Pledge', $pledgeID, 'contact_id');
      if (!$contactId || !CRM_Contact_BAO_Contact_Permission::allow($contactId, CRM_Core_Permission::VIEW)) {
        CRM_Utils_System::permissionDenied();
      }
      $details = CRM_Pledge_BAO_PledgePayment::getOldestPledgePayment($pledgeID);
    }
    CRM_Utils_JSON::output($details);
  }

}
