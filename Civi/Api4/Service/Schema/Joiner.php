<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
