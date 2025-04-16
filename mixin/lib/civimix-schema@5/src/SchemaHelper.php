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
    \CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, FALSE, FALSE);
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
   * @throws \CRM_Core_Exception
   */
  public function alterSchemaField(string $entityName, string $fieldName, array $fieldSpec): bool {
    $tableName = method_exists('Civi', 'entity')
      ? \Civi::entity($entityName)->getMeta('table')
      : \CRM_Core_DAO_AllCoreTables::getTableForEntityName($entityName);
    $fieldSql = $this->arrayToSql($fieldSpec);
    if (\CRM_Core_BAO_SchemaHandler::checkIfFieldExists($tableName, $fieldName, FALSE)) {
      $query = "ALTER TABLE `$tableName` CHANGE `$fieldName` `$fieldName` $fieldSql";
    }
    else {
      $query = "ALTER TABLE `$tableName` ADD COLUMN `$fieldName` $fieldSql";
    }
    \CRM_Core_DAO::executeQuery($query, [], TRUE, NULL, FALSE, FALSE);
    return TRUE;
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

  private function getSqlGenerator() {
    if ($this->sqlGenerator === NULL) {
      $gen = require __DIR__ . '/SqlGenerator.php';
      $this->sqlGenerator = $gen::createFromFolder($this->key, $this->getExtensionDir() . '/schema', $this->key === 'civicrm');
    }
    return $this->sqlGenerator;
  }

};
