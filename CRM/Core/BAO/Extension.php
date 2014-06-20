<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright (C) 2011 Marty Wright                                    |
 | Licensed to CiviCRM under the Academic Free License version 3.0.   |
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
 * This class contains functions for managing extensions
 */
class CRM_Core_BAO_Extension extends CRM_Core_DAO_Extension {

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Core_BAO_LocationType object on success, null otherwise
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $extension = new CRM_Core_DAO_Extension();
    $extension->copyValues($params);
    if ($extension->find(TRUE)) {
      CRM_Core_DAO::storeValues($extension, $defaults);
      return $extension;
    }
    return NULL;
  }

  /**
   * Function to delete an extension
   *
   * @param  int  $id     Id of the extension to be deleted.
   *
   * @return void
   *
   * @access public
   * @static
   */
  static function del($id) {
    $extension = new CRM_Core_DAO_Extension();
    $extension->id = $id;
    return $extension->delete();
  }

  /**
   * Change the schema version of an extension
   *
   * @param $fullName string, the fully-qualified name (eg "com.example.myextension")
   * @param $schemaVersion string
   * @return void
   */
  static function setSchemaVersion($fullName, $schemaVersion) {
    $sql = 'UPDATE civicrm_extension SET schema_version = %1 WHERE full_name = %2';
    $params = array(
      1 => array($schemaVersion, 'String'),
      2 => array($fullName, 'String'),
    );
    return CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * Determine the schema version of an extension
   *
   * @param $fullName string, the fully-qualified name (eg "com.example.myextension")
   * @return string
   */
  static function getSchemaVersion($fullName) {
    $sql = 'SELECT schema_version FROM civicrm_extension WHERE full_name = %1';
    $params = array(
      1 => array($fullName, 'String'),
    );
    return CRM_Core_DAO::singleValueQuery($sql, $params);
  }

}
