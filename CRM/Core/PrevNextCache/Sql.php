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
 * Class CRM_Core_PrevNextCache_Sql
 *
 * Store the previous/next cache in a special-purpose SQL table.
 */
class CRM_Core_PrevNextCache_Sql implements CRM_Core_PrevNextCache_Interface {

  /**
   * Store the results of a SQL query in the cache.
   *
   * @param string $sql
   *   A SQL query. The query *MUST* be a SELECT statement which yields
   *   the following columns (in order): entity_table, entity_id1, entity_id2, cacheKey, data
   * @return bool
   * @throws CRM_Core_Exception
   */
  public function fillWithSql($cacheKey, $sql) {
    $insertSQL = "
INSERT INTO civicrm_prevnext_cache ( entity_table, entity_id1, entity_id2, cacheKey, data )
";
    $result = CRM_Core_DAO::executeQuery($insertSQL . $sql, [], FALSE, NULL, FALSE, TRUE, TRUE);
    if (is_a($result, 'DB_Error')) {
      throw new CRM_Core_Exception($result->message);
    }
    return TRUE;
  }

  public function fillWithArray($cacheKey, $rows) {
    if (empty($rows)) {
      return;
    }

    $insert = CRM_Utils_SQL_Insert::into('civicrm_prevnext_cache')
      ->columns([
        'entity_table',
        'entity_id1',
        'entity_id2',
        'cacheKey',
        'data'
      ]);

    foreach ($rows as &$row) {
      $insert->row($row + ['cacheKey' => $cacheKey]);
    }

    CRM_Core_DAO::executeQuery($insert->toSQL());
    return TRUE;
  }

  /**
   * Save checkbox selections.
   *
   * @param string $cacheKey
   * @param string $action
   *   Ex: 'select', 'unselect'.
   * @param array|int|NULL $cIds
   *   A list of contact IDs to (un)select.
   *   To unselect all contact IDs, use NULL.
   */
  public function markSelection($cacheKey, $action, $cIds = NULL) {
    $entity_table = 'civicrm_contact';

    if (!$cacheKey) {
      return;
    }
    $params = array();

    $entity_whereClause = " AND entity_table = '{$entity_table}'";
    if ($cIds && $cacheKey && $action) {
      if (is_array($cIds)) {
        $cIdFilter = "(" . implode(',', $cIds) . ")";
        $whereClause = "
WHERE cacheKey LIKE %1
AND (entity_id1 IN {$cIdFilter} OR entity_id2 IN {$cIdFilter})
";
      }
      else {
        $whereClause = "
WHERE cacheKey LIKE %1
AND (entity_id1 = %2 OR entity_id2 = %2)
";
        $params[2] = array("{$cIds}", 'Integer');
      }
      if ($action == 'select') {
        $whereClause .= "AND is_selected = 0";
        $sql = "UPDATE civicrm_prevnext_cache SET is_selected = 1 {$whereClause} {$entity_whereClause}";
        $params[1] = array("{$cacheKey}%", 'String');
      }
      elseif ($action == 'unselect') {
        $whereClause .= "AND is_selected = 1";
        $sql = "UPDATE civicrm_prevnext_cache SET is_selected = 0 {$whereClause} {$entity_whereClause}";
        $params[1] = array("%{$cacheKey}%", 'String');
      }
      // default action is reseting
    }
    elseif (!$cIds && $cacheKey && $action == 'unselect') {
      $sql = "
UPDATE civicrm_prevnext_cache
SET    is_selected = 0
WHERE  cacheKey LIKE %1 AND is_selected = 1
       {$entity_whereClause}
";
      $params[1] = array("{$cacheKey}%", 'String');
    }
    CRM_Core_DAO::executeQuery($sql, $params);
  }

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
  public function getSelection($cacheKey, $action = 'get') {
    $entity_table = 'civicrm_contact';

    if (!$cacheKey) {
      return NULL;
    }
    $params = array();

    $entity_whereClause = " AND entity_table = '{$entity_table}'";
    if ($cacheKey && ($action == 'get' || $action == 'getall')) {
      $actionGet = ($action == "get") ? " AND is_selected = 1 " : "";
      $sql = "
SELECT entity_id1, entity_id2 FROM civicrm_prevnext_cache
WHERE cacheKey LIKE %1
      $actionGet
      $entity_whereClause
ORDER BY id
";
      $params[1] = array("{$cacheKey}%", 'String');

      $contactIds = array($cacheKey => array());
      $cIdDao = CRM_Core_DAO::executeQuery($sql, $params);
      while ($cIdDao->fetch()) {
        if ($cIdDao->entity_id1 == $cIdDao->entity_id2) {
          $contactIds[$cacheKey][$cIdDao->entity_id1] = 1;
        }
      }
      return $contactIds;
    }
  }

  /**
   * Get the previous and next keys.
   *
   * @param string $cacheKey
   * @param int $id1
   * @param int $id2
   *
   * NOTE: I don't really get why there are two ID columns, but we'll
   * keep passing them through as a matter of safe-refactoring.
   *
   * @return array
   */
  public function getPositions($cacheKey, $id1, $id2) {
    return CRM_Core_BAO_PrevNextCache::getPositions($cacheKey, $id1, $id2);
  }

  /**
   * Delete an item from the prevnext cache table based on the entity.
   *
   * @param int $id
   * @param string $cacheKey
   * @param string $entityTable
   */
  public function deleteItem($id = NULL, $cacheKey = NULL, $entityTable = 'civicrm_contact') {
    CRM_Core_BAO_PrevNextCache::deleteItem($id, $cacheKey, $entityTable);
  }

  /**
   * Get count of matching rows.
   *
   * @param string $cacheKey
   * @return int
   */
  public function getCount($cacheKey) {
    return CRM_Core_BAO_PrevNextCache::getCount($cacheKey, NULL, "entity_table = 'civicrm_contact'");
  }

}
