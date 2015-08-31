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

define('CRM_ENCRYPT', 1);
define('CRM_SETNULL', 2);
function encryptDB() {
  $tables = array(
    'civicrm_contact' => array(
      'first_name' => CRM_ENCRYPT,
      'last_name' => CRM_ENCRYPT,
      'organization_name' => CRM_ENCRYPT,
      'household_name' => CRM_ENCRYPT,
      'sort_name' => CRM_ENCRYPT,
      'display_name' => CRM_ENCRYPT,
      'legal_name' => CRM_ENCRYPT,
    ),
    'civicrm_address' => array(
      'street_address' => CRM_ENCRYPT,
      'supplemental_address_1' => CRM_ENCRYPT,
      'supplemental_address_2' => CRM_ENCRYPT,
      'city' => CRM_ENCRYPT,
      'postal_code' => CRM_SETNULL,
      'postal_code_suffix' => CRM_SETNULL,
      'geo_code_1' => CRM_SETNULL,
      'geo_code_2' => CRM_SETNULL,
    ),
    'civicrm_website' => array(
      'url' => CRM_ENCRYPT,
    ),
    'civicrm_email' => array(
      'email' => CRM_ENCRYPT,
    ),
    'civicrm_phone' => array(
      'phone' => CRM_ENCRYPT,
    ),
  );

  foreach ($tables as $tableName => $fields) {
    $clauses = array();
    foreach ($fields as $fieldName => $action) {
      if ($action == CRM_ENCRYPT) {
        $clauses[] = "$fieldName = md5($fieldName)";
      }
      elseif ($action == CRM_SETNULL) {
        $clauses[] = "$fieldName = null";
      }
    }

    if (!empty($clauses)) {
      $clause = implode(',', $clauses);
      $query = "UPDATE $tableName SET $clause";
      CRM_Core_DAO::executeQuery($query);
    }
  }
}

function run() {
  session_start();

  require_once '../civicrm.config.php';
  require_once 'CRM/Core/Config.php';
  $config = CRM_Core_Config::singleton();

  // this does not return on failure
  CRM_Utils_System::authenticateScript(TRUE);

  encryptDB();
}

run();
