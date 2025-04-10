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
 * (Semi-Deprecated) The SchemaTrait provides utilities for altering tables during an upgrade.
 * It may be useful for some niche refactorings, but it is not recommended for new code/functionality.
 * For new code, use `E::schema()` (aka `SchemaHelper.php`).
 *
 * `SchemaTrait` and `E::schema()` both provide a list of helper functions, but `E::schema()`
 * is more adaptable:
 *
 * - `E::schema()` can be called in more ways. It can be called by full-size Upgrader classes,
 *   by standalone lifecycle-hooks, and/or by sysadmin scripts [cv].
 * - `E::schema()` is amenable to backports. `civix upgrade` can give you a new
 *   version of `E::schema()` even if the extension targets older `<ver>`sions of CiviCRM.
 *
 * `SchemaTrait` may have some niche uses when refactoring `CRM_Extension_Upgrader_Base` (or a
 * comparable class). This kind of use-case should be extremely rare.
 *
 * @deprecated
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
   * The original entityType.php file should be copied to a directory (e.g. `my_extension/upgrade/schema`)
   * and prefixed with the version-added.
   *
   * @param string $filePath
   *   Relative path to copied schema file (relative to extension directory).
   * @return bool
   * @throws CRM_Core_Exception
   */
  public function createEntityTable(string $filePath): bool {
    $absolutePath = $this->getExtensionDir() . DIRECTORY_SEPARATOR . $filePath;
    $entityDefn = include $absolutePath;
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
    $fieldSql = $schemaHelper->arrayToSql($fieldSpec);
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
