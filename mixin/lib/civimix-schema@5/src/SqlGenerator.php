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

return new class() {

  /**
   * @var array
   */
  private $entities;

  /**
   * @var callable
   */
  private $findExternalTable;

  /**
   * @param string $module
   *   Ex: 'civicrm' or 'org.example.mymodule'
   * @param string $path
   *   Ex: '/var/www/sites/all/modules/civicrm/schema'
   * @param bool $isolated
   *   TRUE if these entities should be a self-sufficient (i.e. no external references).
   *   FALSE if these entities may include references to other tables.
   *   TRUE would make sense in (eg) civicrm-core, before installation or bootstrap
   *   FALSE would make sense in (eg) an extension on an active system.
   *
   * @return static
   */
  public static function createFromFolder(string $module, string $path, bool $isolated) {
    $files = \CRM_Utils_File::findFiles($path, '*.entityType.php');
    $entities = [];
    foreach ($files as $file) {
      $entity = include $file;
      $entity['module'] = $module;
      $entities[$entity['name']] = $entity;
    }

    $findExternalTable = $isolated ? NULL : (['CRM_Core_DAO_AllCoreTables', 'getTableForEntityName']);
    return new static($entities, $findExternalTable);
  }

  public function __construct(array $entities = [], ?callable $findExternalTable = NULL) {
    // Filter out entities without a sql table (e.g. Afform)
    $this->entities = array_filter($entities, function($entity) {
      return !empty($entity['table']);
    });
    $this->findExternalTable = $findExternalTable ?: function() {
      return NULL;
    };
  }

  public function getEntities(): array {
    return $this->entities;
  }

  public function getCreateTablesSql(): string {
    $sql = '';
    foreach ($this->entities as $entity) {
      $sql .= $this->generateCreateTableSql($entity);
    }
    foreach ($this->entities as $entity) {
      $sql .= $this->generateConstraintsSql($entity);
    }
    return $sql;
  }

  public function getCreateTableSql(string $entityName): string {
    $sql = $this->generateCreateTableSql($this->entities[$entityName]);
    $sql .= $this->generateConstraintsSql($this->entities[$entityName]);
    return $sql;
  }

  public function getDropTablesSql(): string {
    $sql = "SET FOREIGN_KEY_CHECKS=0;\n";
    foreach ($this->entities as $entity) {
      $sql .= "DROP TABLE IF EXISTS `{$entity['table']}`;\n";
    }
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $sql;
  }

  public function generateCreateTableWithConstraintSql(array $entity): string {
    $definition = $this->getTableDefinition($entity);
    $constraints = $this->getTableConstraints($entity);
    $sql = "CREATE TABLE IF NOT EXISTS `{$entity['table']}` (\n  " .
      implode(",\n  ", $definition);
    if ($constraints) {
      $sql .= ",\n  " . implode(",\n  ", $constraints);
    }
    $sql .= "\n)\n" . $this->getTableOptions() . ";\n";
    return $sql;
  }

  private function generateCreateTableSql(array $entity): string {
    $definition = $this->getTableDefinition($entity);
    $sql = "CREATE TABLE IF NOT EXISTS `{$entity['table']}` (\n  " .
      implode(",\n  ", $definition) .
      "\n)\n" .
      $this->getTableOptions() . ";\n";
    return $sql;
  }

  private function getTableDefinition(array $entity): array {
    $definition = [];
    $primaryKeys = [];
    foreach ($entity['getFields']() as $fieldName => $field) {
      if (!empty($field['primary_key'])) {
        $primaryKeys[] = "`$fieldName`";
      }
      $definition[] = "`$fieldName` " . self::generateFieldSql($field);
    }
    if ($primaryKeys) {
      $definition[] = 'PRIMARY KEY (' . implode(', ', $primaryKeys) . ')';
    }
    $indices = isset($entity['getIndices']) ? $entity['getIndices']() : [];
    foreach ($indices as $indexName => $index) {
      $indexFields = [];
      foreach ($index['fields'] as $fieldName => $length) {
        $indexFields[] = "`$fieldName`" . (is_int($length) ? "($length)" : '');
      }
      $definition[] = (!empty($index['unique']) ? 'UNIQUE ' : '') . "INDEX `$indexName`(" . implode(', ', $indexFields) . ')';
    }
    return $definition;
  }

  private function generateConstraintsSql(array $entity): string {
    $constraints = $this->getTableConstraints($entity);
    $sql = '';
    if ($constraints) {
      $sql .= "ALTER TABLE `{$entity['table']}`\n  ";
      $sql .= 'ADD ' . implode(",\n  ADD ", $constraints) . ";\n";
    }
    return $sql;
  }

  private function getTableConstraints(array $entity): array {
    $constraints = [];
    foreach ($entity['getFields']() as $fieldName => $field) {
      // `entity_reference.fk` defaults to TRUE if not set. If FALSE, do not add constraint.
      if (!empty($field['entity_reference']['entity']) && ($field['entity_reference']['fk'] ?? TRUE)) {
        $fkName = \CRM_Core_BAO_SchemaHandler::getIndexName($entity['table'], $fieldName);
        $constraint = "CONSTRAINT `FK_$fkName` FOREIGN KEY (`$fieldName`)" .
          " REFERENCES `" . $this->getTableForEntity($field['entity_reference']['entity']) . "`(`{$field['entity_reference']['key']}`)";
        if (!empty($field['entity_reference']['on_delete'])) {
          $constraint .= " ON DELETE {$field['entity_reference']['on_delete']}";
        }
        $constraints[] = $constraint;
      }
    }
    return $constraints;
  }

  public static function generateFieldSql(array $field): string {
    $fieldSql = $field['sql_type'];
    if (!empty($field['collate'])) {
      $fieldSql .= " COLLATE {$field['collate']}";
    }
    // Required fields and booleans cannot be null
    // FIXME: For legacy support this doesn't force boolean fields to be NOT NULL... but it really should.
    if (!empty($field['required'])) {
      $fieldSql .= ' NOT NULL';
    }
    else {
      $fieldSql .= ' NULL';
    }
    if (!empty($field['auto_increment'])) {
      $fieldSql .= " AUTO_INCREMENT";
    }
    $fieldSql .= self::getDefaultSql($field);
    if (!empty($field['description'])) {
      $fieldSql .= " COMMENT '" . \CRM_Core_DAO::escapeString($field['description']) . "'";
    }
    return $fieldSql;
  }

  private static function getDefaultSql(array $field): string {
    // Booleans always have a default
    if ($field['sql_type'] === 'boolean') {
      $field += ['default' => FALSE];
    }
    if (!array_key_exists('default', $field)) {
      return '';
    }
    if (is_null($field['default'])) {
      $default = 'NULL';
    }
    elseif (is_bool($field['default'])) {
      $default = $field['default'] ? 'TRUE' : 'FALSE';
    }
    elseif (!is_string($field['default']) || str_starts_with($field['default'], 'CURRENT_TIMESTAMP')) {
      $default = $field['default'];
    }
    else {
      $default = "'" . \CRM_Core_DAO::escapeString($field['default']) . "'";
    }
    return ' DEFAULT ' . $default;
  }

  private function getTableForEntity(string $entityName): string {
    return $this->entities[$entityName]['table'] ?? call_user_func($this->findExternalTable, $entityName);
  }

  /**
   * Get general/default options for use in CREATE TABLE (eg character set, collation).
   */
  private function getTableOptions(): string {
    if (!Civi\Core\Container::isContainerBooted()) {
      // Pre-installation environment ==> aka new install
      $collation = CRM_Core_BAO_SchemaHandler::DEFAULT_COLLATION;
    }
    else {
      // What character-set is used for CiviCRM core schema? What collation?
      // This depends on when the DB was *initialized*:
      // - civicrm-core >= 5.33 has used `CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`
      // - civicrm-core 4.3-5.32 has used `CHARACTER SET utf8 COLLATE utf8_unicode_ci`
      // - civicrm-core <= 4.2 -- I haven't checked, but it's probably the same.
      // Some systems have migrated (eg APIv3's `System.utf8conversion`), but (as of Feb 2024)
      // we haven't made any effort to push to this change.
      $collation = \CRM_Core_BAO_SchemaHandler::getInUseCollation();
    }

    $characterSet = (stripos($collation, 'utf8mb4') !== FALSE) ? 'utf8mb4' : 'utf8';
    return "ENGINE=InnoDB DEFAULT CHARACTER SET {$characterSet} COLLATE {$collation} ROW_FORMAT=DYNAMIC";
  }

};
