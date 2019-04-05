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
   *   the following columns (in order): cacheKey, entity_id1, data
   * @param array $sqlParams
   *   An array of parameters to be used with $sql.
   *   Use the same interpolation format as CRM_Core_DAO (composeQuery/executeQuery).
   *   Ex: [1 => ['foo', 'String']]
   * @return bool
   * @throws CRM_Core_Exception
   * @see CRM_Core_DAO::composeQuery
   */
  public function fillWithSql($cacheKey, $sql, $sqlParams = []) {
    $insertSQL = "
INSERT INTO civicrm_prevnext_cache (cacheKey, entity_id1, data)
";
    $result = CRM_Core_DAO::executeQuery($insertSQL . $sql, $sqlParams, FALSE, NULL, FALSE, TRUE, TRUE);
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
        'entity_id1',
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
   * @param array|int|NULL $ids
   *   A list of contact IDs to (un)select.
   *   To unselect all contact IDs, use NULL.
   */
  public function markSelection($cacheKey, $action, $ids = NULL) {
    if (!$cacheKey) {
      return;
    }
    $params = [];

    if ($ids && $cacheKey && $action) {
      if (is_array($ids)) {
        $cIdFilter = "(" . implode(',', $ids) . ")";
        $whereClause = "
WHERE cacheKey = %1
AND (entity_id1 IN {$cIdFilter} OR entity_id2 IN {$cIdFilter})
";
      }
      else {
        $whereClause = "
WHERE cacheKey = %1
AND (entity_id1 = %2 OR entity_id2 = %2)
";
        $params[2] = ["{$ids}", 'Integer'];
      }
      if ($action == 'select') {
        $whereClause .= "AND is_selected = 0";
        $sql = "UPDATE civicrm_prevnext_cache SET is_selected = 1 {$whereClause}";
        $params[1] = [$cacheKey, 'String'];
      }
      elseif ($action == 'unselect') {
        $whereClause .= "AND is_selected = 1";
        $sql = "UPDATE civicrm_prevnext_cache SET is_selected = 0 {$whereClause}";
        $params[1] = [$cacheKey, 'String'];
      }
      // default action is reseting
    }
    elseif (!$ids && $cacheKey && $action == 'unselect') {
      $sql = "
UPDATE civicrm_prevnext_cache
SET    is_selected = 0
WHERE  cacheKey = %1 AND is_selected = 1
";
      $params[1] = [$cacheKey, 'String'];
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
    if (!$cacheKey) {
      return NULL;
    }
    $params = [];

    if ($cacheKey && ($action == 'get' || $action == 'getall')) {
      $actionGet = ($action == "get") ? " AND is_selected = 1 " : "";
      $sql = "
SELECT entity_id1 FROM civicrm_prevnext_cache
WHERE cacheKey = %1
      $actionGet
ORDER BY id
";
      $params[1] = [$cacheKey, 'String'];

      $contactIds = [$cacheKey => []];
      $cIdDao = CRM_Core_DAO::executeQuery($sql, $params);
      while ($cIdDao->fetch()) {
        $contactIds[$cacheKey][$cIdDao->entity_id1] = 1;
      }
      return $contactIds;
    }
  }

  /**
   * Get the previous and next keys.
   *
   * @param string $cacheKey
   * @param int $id1
   *
   * @return array
   */
  public function getPositions($cacheKey, $id1) {
    $mergeId = CRM_Core_DAO::singleValueQuery(
      "SELECT id FROM civicrm_prevnext_cache WHERE cacheKey = %2 AND entity_id1 = %1",
      [
        1 => [$id1, 'Integer'],
        2 => [$cacheKey, 'String'],
      ]
    );

    $pos = ['foundEntry' => 0];
    if ($mergeId) {
      $pos['foundEntry'] = 1;

      $sql = "SELECT pn.id, pn.entity_id1, pn.entity_id2, pn.data FROM civicrm_prevnext_cache pn ";
      $wherePrev = " WHERE pn.id < %1 AND pn.cacheKey = %2 ORDER BY ID DESC LIMIT 1";
      $whereNext = " WHERE pn.id > %1 AND pn.cacheKey = %2 ORDER BY ID ASC LIMIT 1";
      $p = [
        1 => [$mergeId, 'Integer'],
        2 => [$cacheKey, 'String'],
      ];

      $dao = CRM_Core_DAO::executeQuery($sql . $wherePrev, $p);
      if ($dao->fetch()) {
        $pos['prev']['id1'] = $dao->entity_id1;
        $pos['prev']['mergeId'] = $dao->id;
        $pos['prev']['data'] = $dao->data;
      }

      $dao = CRM_Core_DAO::executeQuery($sql . $whereNext, $p);
      if ($dao->fetch()) {
        $pos['next']['id1'] = $dao->entity_id1;
        $pos['next']['mergeId'] = $dao->id;
        $pos['next']['data'] = $dao->data;
      }
    }
    return $pos;

  }

  /**
   * Delete an item from the prevnext cache table based on the entity.
   *
   * @param int $id
   * @param string $cacheKey
   */
  public function deleteItem($id = NULL, $cacheKey = NULL) {
    $sql = "DELETE FROM civicrm_prevnext_cache WHERE (1)";
    $params = [];

    if (is_numeric($id)) {
      $sql .= " AND ( entity_id1 = %2 OR entity_id2 = %2 )";
      $params[2] = [$id, 'Integer'];
    }

    if (isset($cacheKey)) {
      $sql .= " AND cacheKey = %3";
      $params[3] = [$cacheKey, 'String'];
    }
    CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * Get count of matching rows.
   *
   * @param string $cacheKey
   * @return int
   */
  public function getCount($cacheKey) {
    $query = "SELECT COUNT(*) FROM civicrm_prevnext_cache pn WHERE pn.cacheKey = %1";
    $params = [1 => [$cacheKey, 'String']];
    return (int) CRM_Core_DAO::singleValueQuery($query, $params, TRUE, FALSE);
  }

  /**
   * Fetch a list of contacts from the prev/next cache for displaying a search results page
   *
   * @param string $cacheKey
   * @param int $offset
   * @param int $rowCount
   * @return array
   *   List of contact IDs.
   */
  public function fetch($cacheKey, $offset, $rowCount) {
    $cids = [];
    $dao = CRM_Utils_SQL_Select::from('civicrm_prevnext_cache pnc')
      ->where('pnc.cacheKey = @cacheKey', ['cacheKey' => $cacheKey])
      ->select('pnc.entity_id1 as cid')
      ->orderBy('pnc.id')
      ->limit($rowCount, $offset)
      ->execute();
    while ($dao->fetch()) {
      $cids[] = $dao->cid;
    }
    return $cids;
  }

}
