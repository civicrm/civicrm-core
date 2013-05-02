<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                               |
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


/*
 * This file checks and updates the status of all membership records for a given domain using the calc_membership_status and 
 * update_contact_membership APIs.
 * It takes the first argument as the domain-id if specified, otherwise takes the domain-id as 1.
 *
 * IMPORTANT: 
 * We are using the default Domain FROM Name and FROM Email Address as the From email address for emails sent by this script.  
 * Verify that this value has been properly set from Administer > Configure > Domain Information
 * If you want to use some other FROM email address, modify line 125 and set your valid email address.
 *
 * Save the file as UpdateMembershipRecord.php prior to running this script.
 */
class CRM_UpdateMembershipRecord {
  function __construct() {
    // you can run this program either from an apache command, or from the cli
    if (php_sapi_name() == "cli") {
      require_once ("cli.php");
      $cli = new civicrm_cli();
      //if it doesn't die, it's authenticated
    }
    else {
      //from the webserver
      $this->initialize();

      $config = CRM_Core_Config::singleton();

      // this does not return on failure
      CRM_Utils_System::authenticateScript(TRUE);

      //log the execution time of script
      CRM_Core_Error::debug_log_message('UpdateMembershipRecord.php');
    }
  }

  function initialize() {
    require_once '../civicrm.config.php';
    require_once 'CRM/Core/Config.php';

    $config = CRM_Core_Config::singleton();
  }

  public function updateMembershipStatus() {
    require_once 'CRM/Member/BAO/Membership.php';
    CRM_Member_BAO_Membership::updateAllMembershipStatus();
  }
}

$obj = new CRM_UpdateMembershipRecord();

echo "\n Updating ";
$obj->updateMembershipStatus();
echo "\n\n Membership records updated. (Done) \n";

