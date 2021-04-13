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
 */


namespace Civi\Api4\Service\Schema;

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Query\Api4SelectQuery;
use Civi\Api4\Utils\CoreUtil;

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
   * @param array $joinPath
   *   A list of aliases, e.g. [contact, phone]
   * @param string $side
   *   Can be LEFT or INNER
   *
   * @throws \Exception
   * @return \Civi\Api4\Service\Schema\Joinable\Joinable[]
   *   The path used to make the join
   */
  public function autoJoin(Api4SelectQuery $query, array $joinPath, $side = 'LEFT') {
    $explicitJoin = $query->getExplicitJoin($joinPath[0]);

    // If the first item is the name of an explicit join, use it as the base & shift it off the path
    if ($explicitJoin) {
      $from = $explicitJoin['table'];
      $baseTableAlias = array_shift($joinPath);
    }
    // Otherwise use the api entity as the base
    else {
      $from = $query->getFrom();
      $baseTableAlias = $query::MAIN_TABLE_ALIAS;
    }

    $fullPath = $this->getPath($from, $joinPath);

    foreach ($fullPath as $link) {
      $target = $link->getTargetTable();
      $alias = $link->getAlias();
      $joinEntity = CoreUtil::getApiNameFromTableName($target);

      if ($joinEntity && !$query->checkEntityAccess($joinEntity)) {
        throw new UnauthorizedException('Cannot join to ' . $joinEntity);
      }

      $bao = $joinEntity ? CoreUtil::getBAOFromApiName($joinEntity) : NULL;
      $conditions = $link->getConditionsForJoin($baseTableAlias);
      if ($bao) {
        $conditions = array_merge($conditions, $query->getAclClause($alias, $bao, $joinPath));
      }

      $query->join($side, $target, $alias, $conditions);

      $baseTableAlias = $link->getAlias();
    }

    return $fullPath;
  }

  /**
   * @param string $baseTable
   * @param array $joinPath
   *
   * @return \Civi\Api4\Service\Schema\Joinable\Joinable[]
   * @throws \Exception
   */
  protected function getPath(string $baseTable, array $joinPath) {
    $cacheKey = sprintf('%s.%s', $baseTable, implode('.', $joinPath));
    if (!isset($this->cache[$cacheKey])) {
      $fullPath = [];

      foreach ($joinPath as $key => $targetAlias) {
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
