<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 5                                                 |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * This script recaches the display_name and sort_name values
 *
 */

/**
 * Class CRM_UpdateNameCache
 */
class CRM_UpdateNameCache {
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
      CRM_Core_Error::debug_log_message('UpdateNameCache.php');
    }
  }

  function initialize() {
    require_once '../civicrm.config.php';
    require_once 'CRM/Core/Config.php';

    $config = CRM_Core_Config::singleton();
  }

  public function updateConstructedNames() {
    require_once 'CRM/Utils/Address.php';
    require_once 'CRM/Core/BAO/Preferences.php';
    require_once 'CRM/Core/DAO.php';
    require_once 'CRM/Core/PseudoConstant.php';
    require_once 'CRM/Contact/BAO/Contact.php';

    //handle individuals using settings in the system
    $query = "SELECT * FROM civicrm_contact WHERE contact_type = 'Individual';";
    $dao = CRM_Core_DAO::executeQuery($query);

    $prefixes = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'prefix_id');
    $suffixes = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'suffix_id');

    $tokens = array();
    CRM_Utils_Hook::tokens($tokens);
    $tokenFields = array();
    foreach ($tokens as $category => $catTokens) {
      foreach ($catTokens as $token) {
        $tokenFields[] = $token;
      }
    }

    //determine sort name construction
    $sortFormat = CRM_Core_BAO_Preferences::value('sort_name_format');
    $sortFormat = str_replace('contact.', '', $sortFormat);

    //determine display name construction
    $displayFormat = CRM_Core_BAO_Preferences::value('display_name_format');
    $displayFormat = str_replace('contact.', '', $displayFormat);

    while ($dao->fetch()) {
      $contactID = $dao->id;
      $params = array('first_name' => $dao->first_name,
        'middle_name' => $dao->middle_name,
        'last_name' => $dao->last_name,
        'prefix_id' => $dao->prefix_id,
        'suffix_id' => $dao->suffix_id,
      );
      $params['individual_prefix'] = $prefixes[$dao->prefix_id];
      $params['individual_suffix'] = $suffixes[$dao->suffix_id];

      $sortName = CRM_Utils_Address::format($params, $sortFormat, FALSE, FALSE, $tokenFields);
      $sortName = trim(CRM_Core_DAO::escapeString($sortName));

      $displayName = CRM_Utils_Address::format($params, $displayFormat, FALSE, FALSE, $tokenFields);
      $displayName = trim(CRM_Core_DAO::escapeString($displayName));

      //check for email
      if (empty($sortName) || empty($displayName)) {
        $email = NULL;
        $email = CRM_Contact_BAO_Contact::getPrimaryEmail($contactID);
        if (empty($email)) {
          $email = $contactID;
        }

        if (empty($sortName)) {

          $sortName = $email;
        }
        if (empty($displayName)) {
          $displayName = $email;
        }
      }

      //update record
      $updateQuery = "UPDATE civicrm_contact SET display_name = '$displayName', sort_name = '$sortName' WHERE id = $contactID;";
      CRM_Core_DAO::executeQuery($updateQuery);
    }
    //end indiv
    echo "\n Individuals recached... ";

    //set organizations
    $query = "UPDATE civicrm_contact
		          SET display_name = organization_name,
				      sort_name = organization_name
			      WHERE contact_type = 'Organization';";
    $dao = CRM_Core_DAO::executeQuery($query);
    echo "\n Organizations recached... ";

    //set households
    $query = "UPDATE civicrm_contact
		          SET display_name = household_name,
				      sort_name = household_name
			      WHERE contact_type = 'Household';";
    $dao = CRM_Core_DAO::executeQuery($query);
    echo "\n Households recached... ";
  }
  //end updateConstructedNames
}

$obj = new CRM_UpdateNameCache();

echo "\n Updating display_name and sort_name for all contacts. ";
$obj->updateConstructedNames();
echo "\n\n Processing complete. \n";

