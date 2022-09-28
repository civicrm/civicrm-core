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
   * Retrieve DB object and copy to defaults array.
   *
   * @param array $params
   *   Array of criteria values.
   * @param array $defaults
   *   Array to be populated with found values.
   *
   * @return self|null
   *   The DAO object, if found.
   *
   * @deprecated
   */
  public static function retrieve($params, &$defaults) {
    return self::commonRetrieve(self::class, $params, $defaults);
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
