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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
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
   * @param \Civi\Api4\Query\Api4SelectQuery $query
   *   The query object to do the joins on
   * @param string $joinPath
   *   A path of aliases in dot notation, e.g. contact.phone
   * @param string $side
   *   Can be LEFT or INNER
   *
   * @throws \Exception
   * @return \Civi\Api4\Service\Schema\Joinable\Joinable[]
   *   The path used to make the join
   */
  public function join(Api4SelectQuery $query, $joinPath, $side = 'LEFT') {
    $fullPath = $this->getPath($query->getFrom(), $joinPath);
    $baseTable = $query::MAIN_TABLE_ALIAS;

    foreach ($fullPath as $link) {
      $target = $link->getTargetTable();
      $alias = $link->getAlias();
      $conditions = $link->getConditionsForJoin($baseTable);

      $query->join($side, $target, $alias, $conditions);
      $query->addJoinedTable($link);

      $baseTable = $link->getAlias();
    }

    return $fullPath;
  }

  /**
   * @param \Civi\Api4\Query\Api4SelectQuery $query
   * @param $joinPath
   *
   * @return bool
   */
  public function canJoin(Api4SelectQuery $query, $joinPath) {
    return !empty($this->getPath($query->getFrom(), $joinPath));
  }

  /**
   * @param string $baseTable
   * @param string $joinPath
   *
   * @return array
   * @throws \Exception
   */
  protected function getPath($baseTable, $joinPath) {
    $cacheKey = sprintf('%s.%s', $baseTable, $joinPath);
    if (!isset($this->cache[$cacheKey])) {
      $stack = explode('.', $joinPath);
      $fullPath = [];

      foreach ($stack as $key => $targetAlias) {
        $links = $this->schemaMap->getPath($baseTable, $targetAlias);

        if (empty($links)) {
          throw new \Exception(sprintf('Cannot join %s to %s', $baseTable, $targetAlias));
        }
        else {
          $fullPath = array_merge($fullPath, $links);
          $lastLink = end($links);
          $baseTable = $lastLink->getTargetTable();
        }
      }

      $this->cache[$cacheKey] = $fullPath;
    }

    return $this->cache[$cacheKey];
  }

}
