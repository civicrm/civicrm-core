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

namespace Civi\Schema;

use MJS\TopSort\Implementations\FixedArraySort;

class SqlGenerator {

  /**
   * @var array
   */
  private array $entities;

  public function __construct(array $entities) {
    $this->entities = $this->sortEntitiesByForeignKey($entities);
  }

  public function getEntitiesSortedByForeignKey(): array {
    return $this->entities;
  }

  public function getCreateTablesSql(): string {
    $sql = '';
    foreach ($this->entities as $entity) {
      $sql .= $this->generateCreateTableSql($entity);
    }
    return $sql;
  }

  public function getCreateTableSql(string $entityName): string {
    return $this->generateCreateTableSql($this->entities[$entityName]);
  }

  public function getDropTablesSql(): string {
    $sql = "SET FOREIGN_KEY_CHECKS=0;\n";
    foreach (array_reverse($this->entities) as $entity) {
      $sql .= "DROP TABLE IF EXISTS `{$entity['table']}`;\n";
    }
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $sql;
  }

  private function sortEntitiesByForeignKey(array $entities): array {
    $entities = array_column($entities, NULL, 'name');
    $sorter = new FixedArraySort();
    foreach ($entities as $name => $entity) {
      $references = [];
      foreach ($entity['getFields']() as $field) {
        if (!empty($field['entity_reference']['entity']) && isset($entities[$field['entity_reference']['entity']])) {
          $references[] = $field['entity_reference']['entity'];
        }
      }
      $sorter->add($name, $references);
    }
    $sortedEntityNames = $sorter->sort();
    $sortedEntities = [];
    foreach ($sortedEntityNames as $name) {
      $sortedEntities[$name] = $entities[$name];
    }
    return $sortedEntities;
  }

  private function generateCreateTableSql(array $entity): string {
    $definition = [];
    $primaryKeys = [];
    foreach ($entity['getFields']() as $fieldName => $field) {
      if (!empty($field['primary_key'])) {
        $primaryKeys[] = "`$fieldName`";
      }
      $fieldSql = "`$fieldName` {$field['sql_type']}";
      if (!empty($field['collate'])) {
        $fieldSql .= " COLLATE {$field['collate']}";
      }
      // Required fields and booleans cannot be null
      if (!empty($field['required']) || $field['sql_type'] === 'boolean') {
        $fieldSql .= ' NOT NULL';
      }
      if (!empty($field['auto_increment'])) {
        $fieldSql .= " AUTO_INCREMENT";
      }
      $fieldSql .= self::getDefaultSql($field);
      if (!empty($field['description'])) {
        $fieldSql .= " COMMENT '" . \CRM_Core_DAO::escapeString($field['description']) . "'";
      }
      $definition[] = $fieldSql;
    }
    if ($primaryKeys) {
      $definition[] = 'PRIMARY KEY (' . implode(', ', $primaryKeys) . ')';
    }
    foreach ($entity['getIndices']() as $indexName => $index) {
      $indexFields = [];
      foreach ($index['fields'] as $fieldName => $length) {
        $indexFields[] = "`$fieldName`" . (is_int($length) ? "($length)" : '');
      }
      $definition[] = (!empty($index['unique']) ? 'UNIQUE ' : '') . "INDEX `$indexName`(" . implode(', ', $indexFields) . ')';
    }
    foreach ($entity['getFields']() as $fieldName => $field) {
      if (!empty($field['entity_reference']['entity'])) {
        $definition[] = "CONSTRAINT `FK_{$entity['table']}_$fieldName` FOREIGN KEY (`$fieldName`)" .
          " REFERENCES `" . $this->getTableForEntity($field['entity_reference']['entity']) . "`(`{$field['entity_reference']['key']}`)" .
          " ON DELETE {$field['entity_reference']['on_delete']}";
      }
    }

    $sql = "CREATE TABLE `{$entity['table']}` (\n  " .
      implode(",\n  ", $definition) .
      "\n)\n" .
      $this->getTableOptions() . ";\n";
    return $sql;
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
    return $this->entities[$entityName]['table'] ?? \CRM_Core_DAO_AllCoreTables::getTableForEntityName($entityName);
  }

  /**
   * Get general/default options for use in CREATE TABLE (eg character set, collation).
   */
  private function getTableOptions(): string {
    // What character-set is used for CiviCRM core schema? What collation?
    // This depends on when the DB was *initialized*:
    // - civicrm-core >= 5.33 has used `CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`
    // - civicrm-core 4.3-5.32 has used `CHARACTER SET utf8 COLLATE utf8_unicode_ci`
    // - civicrm-core <= 4.2 -- I haven't checked, but it's probably the same.
    // Some systems have migrated (eg APIv3's `System.utf8conversion`), but (as of Feb 2024)
    // we haven't made any effort to push to this change.
    $collation = \CRM_Core_BAO_SchemaHandler::getInUseCollation();
    $characterSet = (stripos($collation, 'utf8mb4') !== FALSE) ? 'utf8mb4' : 'utf8';
    return "ENGINE=InnoDB DEFAULT CHARACTER SET {$characterSet} COLLATE {$collation} ROW_FORMAT=DYNAMIC";
  }

}
