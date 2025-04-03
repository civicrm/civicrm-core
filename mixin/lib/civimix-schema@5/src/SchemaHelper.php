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

  // FIXME: You can add more utility methods here

  // public function addTables(array $names): void {
  //   throw new \RuntimeException("TODO: Install a single tables");
  // }
  //
  // public function addColumn(string $table, string $column): void {
  //   throw new \RuntimeException("TODO: Install a single tables");
  // }

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
