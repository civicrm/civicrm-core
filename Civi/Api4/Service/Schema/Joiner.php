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

namespace Civi\Api4\Service\Schema;

use Civi\Api4\Query\Api4SelectQuery;

class Joiner {
  /**
   * @var SchemaMap
   */
  protected $schemaMap;

  /**
   * @var \Civi\Api4\Service\Schema\Joinable\Joinable[][]
   */
  protected $cache = [];

  /**
   * @param SchemaMap $schemaMap
   */
  public function __construct(SchemaMap $schemaMap) {
    $this->schemaMap = $schemaMap;
  }

  /**
   * Get the path used to create an implicit join
   *
   * @param string $baseTable
   * @param array $joinPath
   *
   * @return \Civi\Api4\Service\Schema\Joinable\Joinable[]
   * @throws \CRM_Core_Exception
   */
  public function getPath(string $baseTable, array $joinPath) {
    $cacheKey = sprintf('%s.%s', $baseTable, implode('.', $joinPath));
    if (!isset($this->cache[$cacheKey])) {
      $fullPath = [];

      foreach ($joinPath as $targetAlias) {
        $link = $this->schemaMap->getLink($baseTable, $targetAlias);

        if (!$link) {
          throw new \CRM_Core_Exception(sprintf('Cannot join %s to %s', $baseTable, $targetAlias));
        }
        else {
          $fullPath[$targetAlias] = $link;
          $baseTable = $link->getTargetTable();
        }
      }

      $this->cache[$cacheKey] = $fullPath;
    }

    return $this->cache[$cacheKey];
  }

  /**
   * SpecProvider callback for joins added via a SchemaMapSubscriber.
   *
   * This works for extra joins declared via SchemaMapSubscriber.
   * It allows implicit joins through custom sql, by virtue of the fact
   * that `$query->getField` will create the join not just to the `id` field
   * but to every field on the joined entity, allowing e.g. joins to `address_primary.country_id:label`.
   *
   * @param array $field
   * @param \Civi\Api4\Query\Api4SelectQuery $query
   * @return string
   */
  public static function getExtraJoinSql(array $field, Api4SelectQuery $query): string {
    $prefix = empty($field['explicit_join']) ? '' : $field['explicit_join'] . '.';
    $prefix .= (empty($field['implicit_join']) ? '' : $field['implicit_join'] . '.');
    $idField = $query->getField($prefix . $field['name'] . '.id');
    // If permission denied to join, SELECT NULL
    return $idField['sql_name'] ?? 'NULL';
  }

}
