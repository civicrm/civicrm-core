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
