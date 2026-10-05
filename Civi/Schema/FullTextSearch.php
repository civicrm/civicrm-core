<?php

namespace Civi\Schema;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This service handles Full Text Search for EFv2
 *
 * It is similar to but supercedes the InnodbIndexer which was a) more hard-coded; b) moved to legacycustomsearches
 *
 * At the time of InnodbIndexes, Mysql FTS was only available for newer databases (5.7+). Now 5.7 is minimum for
 * CiviCRM we can assume support, and enable by default
 *
 * Consideration: this sits in a slightly awkward place between low-level schema and higher level admin user config
 * - it is low level in that it affects database schema; is tightly coupled with database column names,
 *   and (will be) integrated with Api4 query builder
 * - it is higher level in that we want to allow for:
 *   - sites to turn off if they dont have the resources or use a different FTS solution
 *   - sites to off and on temporarily (e.g. when importing large volumes of data)
 *   - sites to make fine-grained tweaks to which columns are indexed
 *
 * As such the implementation is here in core, but:
 *  - the index definitions are collected with hook event civi.schema.fts_indices
 *    (rather than *.entityType.php)
 * - the schema operations are actioned separately to the "core schema" in SqlGenerator
 *   (where site-level config cannot easily be referenced)
 *
 * @service civi.schema.fts
 */
class FullTextSearch extends AutoService implements EventSubscriberInterface {

  /**
   * @var array
   *   Local cache of the defined indices
   */
  protected ?array $definedIndices = NULL;

  public static function getSubscribedEvents(): array {
    return [
      // hook early to allow these to be overridden
      'civi.schema.fts_indices' => ['setDefaultIndices', 1000],
    ];
  }

  /**
   * Provide default "out of the box" indices. These can be overridden
   * by site level hooks
   *
   * NOTE: this overrides any earlier changes to 'indices' param - downstream
   * listeners should always hook earlier than this
   */
  public function setDefaultIndices(GenericHookEvent $e): void {
    $e->indices = [
      'Contact' => [
        'contact_names' => [
          'label' => ts('All Contact Names'),
          'description' => ts('Search across all contact name fields'),
          'columns' => ['first_name', 'middle_name', 'last_name', 'nick_name', 'organization_name', 'household_name', 'legal_name'],
        ],
      ],
    ];
  }

  /**
   * Get indices for each entity
   *
   * @return array
   *   [
   *     index1 => [
   *       'label' => indexLabel,
   *       'description' => indexDescription,
   *       'columns' => [column1,column2,...],
   *     ],
   *     ...
   *   ]
   */
  public function getIndicesForEntity(string $entity): array {
    return $this->getDefinedIndices()[$entity] ?? [];
  }

  /**
   * Extract full text search index definitions from the EntityRepository meta
   *
   * @param bool $includeInactive
   *   Whether to return indices which are disabled (by FTS settings)
   *
   * @return array
   *   [
   *     entity1 => [
   *       index1 => [
   *         'label' => indexLabel,
   *         'description' => indexDescription,
   *         'columns' => [column1,column2,...],
   *       ],
   *      ...
   *     ...
   *   ]
   */
  protected function getDefinedIndices(bool $includeInactive = FALSE): array {
    if (!$this->isActive() && !$includeInactive) {
      return [];
    }
    if (!is_array($this->definedIndices)) {
      $e = GenericHookEvent::create(['indices' => []]);
      \Civi::dispatcher()->dispatch('civi.schema.fts_indices', $e);
      $this->definedIndices = $e->indices;
    }
    return $this->definedIndices;
  }

  /**
   * Get the names of all full text indices which currently exist on a given table
   */
  protected function getExistingIndicesForTable(string $table): array {
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

    foreach ($this->getDefinedIndices() as $entity => $indices) {
      $table = \Civi::entity($entity)->getMeta('table');
      $indexNames = array_keys($indices);
      $toAdd = array_diff($indexNames, $this->getExistingIndicesForTable($table));
      if (!$toAdd) {
        continue;
      }
      $sqls = array_map(fn ($name) => "ADD FULLTEXT INDEX {$name} (" . implode(',', $indices[$name]['columns']) . ")", $toAdd);
      $sql = "ALTER TABLE {$table} " . \implode(', ', $sqls);
      \CRM_Core_DAO::executeQuery($sql);
    }
  }

  /**
   * Drop indicies specified in the meta definition
   */
  public function dropIndices(): void {
    foreach ($this->getDefinedIndices(TRUE) as $entity => $indices) {
      $table = \Civi::entity($entity)->getMeta('table');
      $indexNames = array_keys($indices);
      $toDrop = \array_intersect($indexNames, $this->getExistingIndicesForTable($table));
      if (!$toDrop) {
        continue;
      }
      $sqls = array_map(fn ($name) => "DROP INDEX {$name}", $toDrop);
      $sql = "ALTER TABLE {$table} " . implode(', ', $sqls);
      \CRM_Core_DAO::executeQuery($sql);
    }
  }

  public function isActive(): bool {
    return \Civi::settings()->get('search_mysql_fts');
  }

  /**
   * Create or drop according to whether currently active
   */
  public static function createOrDrop(): void {
    $service = \Civi::service('civi.schema.fts');
    if ($service->isActive()) {
      $service->createIndices();
    }
    else {
      $service->dropIndices();
    }
  }

}
