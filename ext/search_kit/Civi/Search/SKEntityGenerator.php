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

namespace Civi\Search;

use Civi\API\Request;
use Civi\Api4\Query\Api4SelectQuery;

class SKEntityGenerator {

  /**
   * @param string $realEntity
   *   The underlying API entity that we need to query
   * @param array $realParams
   *   Basic API request
   * @param array $settings
   *   The settings from the SearchDisplay record.
   * @return string
   *   SQL query string
   * @throws \Civi\API\Exception\NotImplementedException
   */
  public function createQuery(string $realEntity, array $realParams, array $settings): string {
    $apiParams = $realParams;
    // Add orderBy to api params
    foreach ($settings['sort'] ?? [] as $item) {
      $apiParams['orderBy'][$item[0]] = $item[1];
    }
    // Set select clause to match display columns
    $select = [];
    foreach ($settings['columns'] as $column) {
      foreach ($apiParams['select'] as $selectExpr) {
        if ($selectExpr === $column['key'] || str_ends_with($selectExpr, " AS {$column['key']}")) {
          $select[] = $selectExpr;
          continue 2;
        }
      }
    }
    $apiParams['select'] = $select;
    $api = Request::create($realEntity, 'get', $apiParams);
    $query = new Api4SelectQuery($api);
    $query->forceSelectId = FALSE;
    $sql = $query->getSql();

    // Fix any column names that exceed the max length
    foreach ($settings['columns'] as $column) {
      if (strlen($column['key']) > Meta::MAX_COLUMN_LENGTH) {
        $shortAlias = Meta::createSqlName($column['key'])[0];
        $sql = str_replace("`{$column['key']}`", "`$shortAlias`", $sql);
      }
    }

    return $sql;
  }

}
