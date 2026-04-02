<?php

namespace CiviMix\Schema;

/**
 * The "SchemaHelper" class provides helper methods for an extension to manage its schema.
 *
 * Target: CiviCRM v5.38+
 */
return new class() implements SchemaHelperInterface {

  /**
   * @var string
   *
   * Ex: 'org.civicrm.flexmailer'
   */
  private $key;

  private $sqlGenerator;

  public function __construct(?string $key = NULL) {
    $this->key = $key;
  }

  public function install(): void {
    $this->runSqls([$this->generateInstallSql()]);
  }

  public function uninstall(): void {
    $this->runSqls([$this->generateUninstallSql()]);
  }

  public function generateInstallSql(): ?string {
    return $this->getSqlGenerator()->getCreateTablesSql();
  }

  public function generateUninstallSql(): string {
    return $this->getSqlGenerator()->getDropTablesSql();
  }

  public function hasSchema(): bool {
    return file_exists($this->getExtensionDir() . '/schema');
  }

  /**
   * @param string $entityName
   * @return string|null
   */
  public function getTableName(string $entityName): ?string {
    // Legacy compatability with CiviCRM < 5.74
    if (!method_exists('Civi', 'entity')) {
      return \CRM_Core_DAO_AllCoreTables::getTableForEntityName($entityName);
    }
    return \Civi::entity($entityName)->getMeta('table');
  }

  /**
   * Check if a single table exists.
   *
   * Note, this function is case-insensitive.
   */
  public function tableExists(string $tableName): bool {
    $existing = $this->getExistingTables([$tableName]);
    return count($existing) === 1;
  }

  /**
   * Given a list of table names, return the ones that exist.
   *
   * Note: matching is case-insensitive, but the canonical case will be returned.
   * So `getExistingTables(['CiviCRM_ACTIVITY'])` will return `['civicrm_activity']`.
   *
   * @since 6.15
   */
  public function getExistingTables(array $tableNames): array {
    if (empty($tableNames)) {
      return [];
    }
    $conditions = implode("' OR TABLE_NAME LIKE '", array_map(['\CRM_Core_DAO', 'escapeString'], $tableNames));
    $dao = \CRM_Core_DAO::executeQuery("SELECT TABLE_NAME AS table_name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND (TABLE_NAME LIKE '$conditions')");
    return $dao->fetchMap('table_name', 'table_name');
  }

  /**
   * @param string $entityName
   * @param string $fieldName
   * @return bool
   */
  public function schemaFieldExists(string $entityName, string $fieldName): bool {
    return \CRM_Core_BAO_SchemaHandler::checkIfFieldExists($this->getTableName($entityName), $fieldName, FALSE);
  }

  /**
   * Converts an entity or field definition to SQL statement.
   *
   * @param array $defn
   *   The definition array, which can either represent
   *   an entity with fields or a single database column.
   * @return string
   *   The generated SQL statement, which is either an SQL command
   *   for creating a table with constraints or for defining a single column.
   */
  public function arrayToSql(array $defn): string {
    $generator = $this->getSqlGenerator();
    // Entity array: generate entire table
    if (isset($defn['getFields'])) {
      return $generator->generateCreateTableWithConstraintSql($defn);
    }
    // Field array: generate single column
    else {
      return $generator->generateFieldSql($defn);
    }
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
   * @throws \CRM_Core_Exception
   */
  public function createEntityTable(string $filePath): bool {
    $absolutePath = $this->getExtensionDir() . DIRECTORY_SEPARATOR . $filePath;
    $entityDefn = include $absolutePath;
    $sql = $this->arrayToSql($entityDefn);
    \CRM_Core_DAO::executeQuery($sql, i18nRewrite: FALSE);
    return TRUE;
  }

  /**
   * Task to add or change a column definition, based on the php schema spec.
   *
   * @param string $entityName
   * @param string $fieldName
   * @param array $fieldSpec
   *   As definied in the .entityType.php file for $entityName
   * @param string|null $position
   *   E.g. "AFTER `another_column_name`" or "FIRST"
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function alterSchemaField(string $entityName, string $fieldName, array $fieldSpec, ?string $position = NULL): bool {
    $tableName = $this->getTableName($entityName);
    $fieldSql = $this->arrayToSql($fieldSpec);
    if ($position) {
      $fieldSql .= " $position";
    }
    if ($this->schemaFieldExists($entityName, $fieldName)) {
      $query = "ALTER TABLE `$tableName` CHANGE `$fieldName` `$fieldName` $fieldSql";
    }
    else {
      $query = "ALTER TABLE `$tableName` ADD COLUMN `$fieldName` $fieldSql";
    }
    \CRM_Core_DAO::executeQuery($query, i18nRewrite: FALSE);

    return TRUE;
  }

  public function dropSchemaField(string $entityName, string $fieldName): bool {
    if ($this->schemaFieldExists($entityName, $fieldName)) {
      $tableName = $this->getTableName($entityName);
      \CRM_Core_DAO::executeQuery("ALTER TABLE `$tableName` DROP COLUMN `$fieldName`", i18nRewrite: FALSE);
    }
    return TRUE;
  }

  public function dropTable(string $tableName): bool {
    \CRM_Core_BAO_SchemaHandler::dropTable($tableName);
    return TRUE;
  }

  public function indexExists(string $tableName, string $indexName): bool {
    $result = \CRM_Core_DAO::executeQuery(
      "SHOW INDEX FROM %1 WHERE key_name = %2 AND seq_in_index = 1",
      [
        1 => [$tableName, 'MysqlColumnNameOrAlias'],
        2 => [$indexName, 'String'],
      ],
      i18nRewrite: FALSE
    );
    return $result->fetch();
  }

  public function createIndex(string $tableName, string $indexName, array $indexDef): bool {
    if (!$this->indexExists($tableName, $indexName)) {
      $indexSql = $this->getSqlGenerator()->generateIndexSql($indexName, $indexDef);
      \CRM_Core_DAO::executeQuery("ALTER TABLE `$tableName` ADD $indexSql", i18nRewrite: FALSE);
      return TRUE;
    }
    return FALSE;
  }

  public function dropIndex(string $tableName, string $indexName): bool {
    if ($this->indexExists($tableName, $indexName)) {
      \CRM_Core_DAO::executeQuery(
        "ALTER TABLE %1 DROP INDEX %2",
        [
          1 => [$tableName, 'MysqlColumnNameOrAlias'],
          2 => [$indexName, 'MysqlColumnNameOrAlias'],
        ],
        i18nRewrite: FALSE
      );
      return TRUE;
    }
    return FALSE;
  }

  public function foreignKeyExists(string $tableName, string $foreignKeyName): bool {
    return \CRM_Core_BAO_SchemaHandler::checkFKExists($tableName, $foreignKeyName);
  }

  public function createForeignKey(string $tableName, string $fieldName, array $fieldSpec): bool {
    [$fkName, $constraint] = $this->getSqlGenerator()->getFieldConstraint($tableName, $fieldName, $fieldSpec);
    if ($fkName && !$this->foreignKeyExists($tableName, $fkName)) {
      \CRM_Core_DAO::executeQuery("ALTER TABLE `$tableName` ADD $constraint", i18nRewrite: FALSE);
    }
    return TRUE;
  }

  public function dropForeignKey(string $tableName, string $foreignKeyName): bool {
    return \CRM_Core_BAO_SchemaHandler::safeRemoveFK($tableName, $foreignKeyName);
  }

  /**
   * @param array $sqls
   *  List of SQL scripts.
   */
  private function runSqls(array $sqls): void {
    foreach ($sqls as $sql) {
      \CRM_Utils_File::runSqlQuery(CIVICRM_DSN, $sql);
    }
  }

  protected function getExtensionDir(): string {
    if ($this->key === 'civicrm') {
      $r = new \ReflectionClass('CRM_Core_ClassLoader');
      return dirname($r->getFileName(), 3);
    }
    $system = \CRM_Extension_System::singleton();
    return $system->getMapper()->keyToBasePath($this->key);
  }

  /**
   * @return object
   * @see SqlGenerator.php
   */
  private function getSqlGenerator() {
    if ($this->sqlGenerator === NULL) {
      $gen = require __DIR__ . '/SqlGenerator.php';
      $this->sqlGenerator = $gen::createFromFolder($this->key, $this->getExtensionDir() . '/schema', $this->key === 'civicrm');
    }
    return $this->sqlGenerator;
  }

};
