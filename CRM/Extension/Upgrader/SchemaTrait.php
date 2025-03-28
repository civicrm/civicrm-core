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

  /**
   * Create table (if not exists) from a given php schema file.
   *
   * The original entityType.php file should be copied and prefixed with the version-added.
   *
   * @param string $filePath
   *   Absolute path to schema file (should be a copy not the original)
   * @return bool
   * @throws CRM_Core_Exception
   */
  public function createEntityTable(string $filePath): bool {
    $entityDefn = include $filePath;
    $schemaHelper = Civi::schemaHelper($this->getExtensionKey());
    $sql = $schemaHelper->arrayToSql($entityDefn);
    CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, FALSE, FALSE);
    return TRUE;
  }

  /**
   * Task to add or change a column definition, based on the php schema spec.
   *
   * @param string $entityName
   * @param string $fieldName
   * @param array $fieldSpec
   *   As definied in the .entityType.php file for $entityName
   * @return bool
   * @throws CRM_Core_Exception
   */
  public function alterSchemaField(string $entityName, string $fieldName, array $fieldSpec): bool {
    $tableName = Civi::entity($entityName)->getMeta('table');
    $schemaHelper = Civi::schemaHelper($this->getExtensionKey());
    $fieldSql = $schemaHelper->generateFieldSql($fieldSpec);
    if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists($tableName, $fieldName, FALSE)) {
      $query = "ALTER TABLE `$tableName` CHANGE `$fieldName` `$fieldName` $fieldSql";
      CRM_Core_DAO::executeQuery($query, [], TRUE, NULL, FALSE, FALSE);
      return TRUE;
    }
    else {
      return self::addColumn($tableName, $fieldName, $fieldSql);
    }
  }

}
