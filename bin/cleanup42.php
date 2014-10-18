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
 * A PHP script which deletes extraneous civicrm_membership_payment rows
 * in order to correct the condition where a contribution row is linked to > 1 membership.
  */

function initialize() {
  session_start();
  if (!function_exists('drush_get_context')) {
    require_once '../civicrm.config.php';
  }

  // hack to make code think its an upgrade mode, and not do lot of initialization which breaks the code due to new 4.2 schema
  $_GET['q'] = 'civicrm/upgrade/cleanup42';

  require_once 'CRM/Core/Config.php';
  $config = CRM_Core_Config::singleton();
  if (php_sapi_name() != "cli") {
    // this does not return on failure
    CRM_Utils_System::authenticateScript(TRUE);
  }
}

function run() {
  initialize();

  $fh   = fopen('php://output', 'w');
  $rows = CRM_Upgrade_Incremental_php_FourTwo::deleteInvalidPairs();

  if ( !empty($rows)) {
    echo "The following records have been processed. If action = Un-linked, that membership has been disconnected from the contribution record.\n";
    echo "Contact ID, ContributionID, Contribution Status, MembershipID, Membership Type, Start Date, End Date, Membership Status, Action \n";
  }
  else {
    echo "Could not find any records to process.\n";
  }

  foreach ( $rows as $row ) {
    fputcsv($fh, $row);
  }
}

run();
