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
 * The SchemaTrait provides utilities for altering tables during an upgrade.
 */
trait CRM_Extension_Upgrader_SchemaTrait {

  /**
   * Add a column to a table if it doesn't already exist
   *
   * @param string $table
   * @param string $column
   * @param string $properties
   *
   * @return bool
   */
  public static function addColumn($table, $column, $properties) {
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, $column, FALSE)) {
      $query = "ALTER TABLE `$table` ADD COLUMN `$column` $properties";
      CRM_Core_DAO::executeQuery($query, [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

  /**
   * Drop a column from a table if it exists.
   *
   * @param string $table
   * @param string $column
   * @return bool
   */
  public static function dropColumn($table, $column) {
    if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, $column, FALSE)) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `$table` DROP COLUMN `$column`",
        [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

  /**
   * Add an index to one or more columns.
   *
   * @param string $table
   * @param string|array $columns
   * @param string $prefix
   * @return bool
   */
  public static function addIndex($table, $columns, $prefix = 'index') {
    $tables = [$table => (array) $columns];
    CRM_Core_BAO_SchemaHandler::createIndexes($tables, $prefix);
    return TRUE;
  }

  /**
   * Drop index from a table if it exists.
   *
   * @param string $table
   * @param string $indexName
   * @return bool
   */
  public static function dropIndex($table, $indexName) {
    CRM_Core_BAO_SchemaHandler::dropIndexIfExists($table, $indexName);
    return TRUE;
  }

}
