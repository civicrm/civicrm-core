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
