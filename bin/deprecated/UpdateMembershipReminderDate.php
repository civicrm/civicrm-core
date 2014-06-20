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


/*
 * This file updates the Reminder dates of all valid membership records.
 *
 */

/**
 * Class CRM_UpdateMembershipReminderDate
 */
class CRM_UpdateMembershipReminderDate {
  /**
   *
   */
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
      CRM_Core_Error::debug_log_message('UpdateMembershipReminderDate.php');
    }
  }

  function initialize() {
    require_once '../civicrm.config.php';
    require_once 'CRM/Core/Config.php';

    $config = CRM_Core_Config::singleton();
  }

  public function updateMembershipReminderDate() {
    require_once 'CRM/Member/PseudoConstant.php';

    //get all active statuses of membership.
    $allStatuses = CRM_Member_PseudoConstant::membershipStatus();

    //set membership reminder date if membership
    //record has one of the following status.
    $validStatus = array('New', 'Current', 'Grace');

    $statusIds = array();
    foreach ($validStatus as $status) {
      $statusId = array_search($status, $allStatuses);
      if ($statusId) {
        $statusIds[$statusId] = $statusId;
      }
    }

    //we don't have valid status to check,
    //therefore no need to proceed further.
    if (empty($statusIds)) {
      return;
    }

    //set reminder date for all memberships,
    //in case reminder date is missing and
    //membership type has reminder day set.

    $query = '
    UPDATE  civicrm_membership membership
INNER JOIN  civicrm_contact contact ON ( contact.id = membership.contact_id )
INNER JOIN  civicrm_membership_type type ON ( type.id = membership.membership_type_id )
       SET  membership.reminder_date = DATE_SUB( membership.end_date, INTERVAL type.renewal_reminder_day + 1 DAY )
     WHERE  membership.reminder_date IS NULL
       AND  contact.is_deleted = 0
       AND  ( contact.is_deceased IS NULL OR contact.is_deceased = 0 )
       AND  type.renewal_reminder_day IS NOT NULL
       AND  membership.status_id IN ( ' . implode(' , ', $statusIds) . ' )';

    CRM_Core_DAO::executeQuery($query);
  }
}

$reminderDate = new CRM_UpdateMembershipReminderDate();

echo "\n Updating... ";
$reminderDate->updateMembershipReminderDate();
echo "\n\n Membership(s) reminder date updated. (Done) \n";

