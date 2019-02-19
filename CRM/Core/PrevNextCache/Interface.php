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
 * Interface CRM_Core_PrevNextCache_Interface
 *
 * The previous/next cache is a service for tracking query results. Results
 * are stored in a cache, and they may be individually toggled.
 */
interface CRM_Core_PrevNextCache_Interface {

  /**
   * Store the results of a SQL query in the cache.
   *
   * @param string $cacheKey
   * @param string $sql
   *   A SQL query. The query *MUST* be a SELECT statement which yields
   *   the following columns (in order): cacheKey, entity_id1, data
   * @param array $sqlParams
   *   An array of parameters to be used with $sql.
   *   Use the same interpolation format as CRM_Core_DAO (composeQuery/executeQuery).
   *   Ex: [1 => ['foo', 'String']]
   * @return bool
   * @see CRM_Core_DAO::composeQuery
   */
  public function fillWithSql($cacheKey, $sql, $sqlParams = []);

  /**
   * Store the contents of an array in the cache.
   *
   * @param string $cacheKey
   * @param array $rows
   *   A list of cache records. Each record should have keys:
   *    - entity_id1
   *    - data
   * @return bool
   */
  public function fillWithArray($cacheKey, $rows);

  /**
   * Save checkbox selections.
   *
   * @param string $cacheKey
   * @param string $action
   *   Ex: 'select', 'unselect'.
   * @param array|int|NULL $ids
   *   A list of contact IDs to (un)select.
   *   To unselect all contact IDs, use NULL.
   */
  public function markSelection($cacheKey, $action, $ids = NULL);

  /**
   * Get the selections.
   *
   * @param string $cacheKey
   *   Cache key.
   * @param string $action
   *   One of the following:
   *   - 'get' - get only selection records
   *   - 'getall' - get all the records of the specified cache key
   *
   * @return array|NULL
   */
  public function getSelection($cacheKey, $action = 'get');

  /**
   * Get the previous and next keys.
   *
   * @param string $cacheKey
   * @param int $id1
   *
   * @return array
   *   List of neighbors.
   *   [
   *     'foundEntry' => 1,
   *     'prev' => ['id1' => 123, 'data'=>'foo'],
   *     'next' => ['id1' => 456, 'data'=>'foo'],
   *   ]
   */
  public function getPositions($cacheKey, $id1);

  /**
   * Delete an item from the prevnext cache table based on the entity.
   *
   * @param int $id
   * @param string $cacheKey
   */
  public function deleteItem($id = NULL, $cacheKey = NULL);

  /**
   * Get count of matching rows.
   *
   * @param string $cacheKey
   * @return int
   */
  public function getCount($cacheKey);

  /**
   * Fetch a list of contacts from the prev/next cache for displaying a search results page
   *
   * @param string $cacheKey
   * @param int $offset
   * @param int $rowCount
   * @return array
   *   List of contact IDs (entity_id1).
   */
  public function fetch($cacheKey, $offset, $rowCount);

}
