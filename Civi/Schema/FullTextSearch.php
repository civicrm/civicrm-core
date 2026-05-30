<?php

namespace Civi\Schema;

use Civi\Core\Service\AutoService;

/**
 * This service handles Full Text Search for EFv2
 *
 * It is similar to but supercedes the InnodbIndexer which was a) more hard-coded; b) moved to legacycustomsearches
 *
 * At the time of InnodbIndexes, Mysql FTS was only available for newer databases (5.7+). Now 5.7 is minimum for
 * CiviCRM we can assume support, and enable by default
 *
 * @service civi.schema.fts
 */
class FullTextSearch extends AutoService {

  /**
   * Get the names of defined indices for each entity
   *
   * @return array
   *   [
   *     entity1 => [index1, index2,...],
   *     ...
   *   ]
   */
  public function getIndexNamesByEntity(): array {
    return array_map(fn ($def) => array_keys($def['indices']), $this->getDefinedIndices());
  }

  /**
   * Extract full text search index definitions from the EntityRepository meta
   *
   * @return array
   *   [
   *     entity1 => [
   *       'table' => tableName,
   *       'indices' => [
   *          index1 => [column1, column2, ..]
   *          ...
   *        ],
   *     ...
   *   ]
   */
  protected function getDefinedIndices(): array {
    return array_filter(array_map(function ($meta) {
      $allIndices = !empty($meta['getIndices']) ? $meta['getIndices']() : [];
      $ftsIndices = array_filter($allIndices, fn ($indexDef) => !empty($indexDef['fts']));
      if (!$ftsIndices) {
        return NULL;
      }
      $indexNameToFields = array_map(fn ($indexDef) => array_keys($indexDef['fields']), $ftsIndices);
      return [
        'table' => $meta['table'],
        'indices' => $indexNameToFields,
      ];
    }, EntityRepository::getEntities()));
  }

  /**
   * Get the names of all full text indices which currently exist on a given table
   */
  protected function getExistingIndices(string $table): array {
    $rows = \CRM_Core_DAO::executeQuery("SHOW INDEX FROM %1 WHERE Index_type = 'FULLTEXT'", [1 => [$table, 'MysqlColumnNameOrAlias']])->fetchAll();
    return array_column($rows, 'Key_name');
  }

  /**
   * Create any defined indices that don't already exist.
   * Note: existence check is by name - will not check the existing index is across
   * the correct columns specified in the current definition
   *
   * @param bool $cleanSlate
   *   drop all existing FTS indices first - this will ensure created indices match
   *   the current defintion
   */
  public function createIndices(bool $cleanSlate = FALSE): void {
    if ($cleanSlate) {
      $this->dropIndices();
    }

    foreach ($this->getDefinedIndices() as $entity => $meta) {
      $table = $meta['table'];
      $indexNames = array_keys($meta['indices']);
      $toAdd = array_diff($indexNames, $this->getExistingIndices($table));
      if (!$toAdd) {
        continue;
      }
      $sqls = array_map(fn ($name) => "ADD FULLTEXT INDEX {$name} (" . implode(',', $meta['indices'][$name]) . ")", $toAdd);
      $sql = "ALTER TABLE {$table} " . \implode(', ', $sqls);
      \CRM_Core_DAO::executeQuery($sql);
    }
  }

  /**
   * Drop indicies specified in the meta definition
   */
  public function dropIndices(): void {
    foreach ($this->getDefinedIndices() as $entity => $meta) {
      $table = $meta['table'];
      $indexNames = array_keys($meta['indices']);
      $toDrop = \array_intersect($indexNames, $this->getExistingIndices($table));
      if (!$toDrop) {
        continue;
      }
      $sqls = array_map(fn ($name) => "DROP INDEX {$name}", $toDrop);
      $sql = "ALTER TABLE {$table} " . implode(', ', $sqls);
      \CRM_Core_DAO::executeQuery($sql);
    }
  }

}
