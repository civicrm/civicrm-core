<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
   *   the following columns (in order): entity_table, entity_id1, entity_id2, cacheKey, data
   * @return bool
   */
  public function fillWithSql($cacheKey, $sql);

  /**
   * Store the contents of an array in the cache.
   *
   * @param string $cacheKey
   * @param array $rows
   *   A list of cache records. Each record should have keys:
   *    - entity_table
   *    - entity_id1
   *    - entity_id2
   *    - data
   * @return bool
   */
  public function fillWithArray($cacheKey, $rows);

  /**
   * Fetch a list of contacts from the prev/next cache for displaying a search results page
   *
   * @param string $cacheKey
   * @param int $offset
   * @param int $rowCount
   * @param bool $includeContactIds
   *   FIXME: Masochistic.
   *   If this is TRUE, then $query->_params will be searched for items beginning
   *   with `mark_x_<number>`. Each <number> becomes part of a contact filter
   *   (`WHERE contact_id IN (...)`).
   * @param CRM_Contact_BAO_Query $queryBao
   *   FIXME: Masochistic.
   * @return Generator<CRM_Core_DAO>
   */
  public function fetch($cacheKey, $offset, $rowCount, $includeContactIds, $queryBao);

  /**
   * Save checkbox selections.
   *
   * @param string $cacheKey
   * @param string $action
   *   Ex: 'select', 'unselect'.
   * @param array|int|NULL $cIds
   *   A list of contact IDs to (un)select.
   *   To unselect all contact IDs, use NULL.
   * @param string $entity_table
   *   Ex: 'civicrm_contact'.
   */
  public function markSelection($cacheKey, $action = 'unselect', $cIds = NULL, $entity_table = 'civicrm_contact');

}
