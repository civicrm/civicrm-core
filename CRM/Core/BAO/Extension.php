<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class contains functions for managing extensions
 */
class CRM_Core_BAO_Extension extends CRM_Core_DAO_Extension {

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Core_BAO_LocationType|null
   *   object on success, null otherwise
   */
  public static function retrieve(&$params, &$defaults) {
    $extension = new CRM_Core_DAO_Extension();
    $extension->copyValues($params);
    if ($extension->find(TRUE)) {
      CRM_Core_DAO::storeValues($extension, $defaults);
      return $extension;
    }
    return NULL;
  }

  /**
   * Delete an extension.
   *
   * @param int $id
   *   Id of the extension to be deleted.
   *
   * @return mixed
   */
  public static function del($id) {
    $extension = new CRM_Core_DAO_Extension();
    $extension->id = $id;
    return $extension->delete();
  }

  /**
   * Change the schema version of an extension.
   *
   * @param string $fullName
   *   the fully-qualified name (eg "com.example.myextension").
   * @param string $schemaVersion
   *
   * @return \CRM_Core_DAO|object
   */
  public static function setSchemaVersion($fullName, $schemaVersion) {
    $sql = 'UPDATE civicrm_extension SET schema_version = %1 WHERE full_name = %2';
    $params = [
      1 => [$schemaVersion, 'String'],
      2 => [$fullName, 'String'],
    ];
    return CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * Determine the schema version of an extension.
   *
   * @param string $fullName
   *   the fully-qualified name (eg "com.example.myextension").
   * @return string
   */
  public static function getSchemaVersion($fullName) {
    $sql = 'SELECT schema_version FROM civicrm_extension WHERE full_name = %1';
    $params = [
      1 => [$fullName, 'String'],
    ];
    return CRM_Core_DAO::singleValueQuery($sql, $params);
  }

}
