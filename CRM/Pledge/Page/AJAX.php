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
  public function getPledgeDefaults() {
    $details = [];
    if (!empty($_POST['pid'])) {
      $pledgeID = CRM_Utils_Type::escape($_POST['pid'], 'Integer');
      $details = CRM_Pledge_BAO_PledgePayment::getOldestPledgePayment($pledgeID);
    }
    CRM_Utils_JSON::output($details);
  }

}
