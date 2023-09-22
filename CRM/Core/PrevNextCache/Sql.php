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
 * Class CRM_Core_PrevNextCache_Sql
 *
 * Store the previous/next cache in a special-purpose SQL table.
 */
class CRM_Core_PrevNextCache_Sql implements CRM_Core_PrevNextCache_Interface {

  /**
   * @var int
   * Days for cache to llast forr
   */
  const cacheDays = 2;

  /**
   * Store the results of a SQL query in the cache.
   * @param string $cacheKey
   * @param string $sql
   *   A SQL query. The query *MUST* be a SELECT statement which yields
   *   the following columns (in order): cachekey, entity_id1, data
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
INSERT INTO civicrm_prevnext_cache (cachekey, entity_id1, data)
";
    $result = CRM_Core_DAO::executeQuery($insertSQL . $sql, $sqlParams, FALSE);
    if (is_a($result, 'DB_Error')) {
      CRM_Core_Error::deprecatedFunctionWarning('errors are not expected to be returned');
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
        'cachekey',
        'data',
      ]);

    foreach ($rows as &$row) {
      $insert->row($row + ['cachekey' => $cacheKey]);
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
   * @param array|int|null $ids
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
WHERE cachekey = %1
AND (entity_id1 IN {$cIdFilter} OR entity_id2 IN {$cIdFilter})
";
      }
      else {
        $whereClause = "
WHERE cachekey = %1
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
WHERE  cachekey = %1 AND is_selected = 1
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
WHERE cachekey = %1
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
      "SELECT id FROM civicrm_prevnext_cache WHERE cachekey = %2 AND entity_id1 = %1",
      [
        1 => [$id1, 'Integer'],
        2 => [$cacheKey, 'String'],
      ]
    );

    $pos = ['foundEntry' => 0];
    if ($mergeId) {
      $pos['foundEntry'] = 1;

      $sql = "SELECT pn.id, pn.entity_id1, pn.entity_id2, pn.data FROM civicrm_prevnext_cache pn ";
      $wherePrev = " WHERE pn.id < %1 AND pn.cachekey = %2 ORDER BY ID DESC LIMIT 1";
      $whereNext = " WHERE pn.id > %1 AND pn.cachekey = %2 ORDER BY ID ASC LIMIT 1";
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
      $sql .= " AND cachekey = %3";
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
    $query = "SELECT COUNT(*) FROM civicrm_prevnext_cache pn WHERE pn.cachekey = %1";
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
      ->where('pnc.cachekey = @cacheKey', ['cacheKey' => $cacheKey])
      ->select('pnc.entity_id1 as cid')
      ->orderBy('pnc.id')
      ->limit($rowCount, $offset)
      ->execute();
    while ($dao->fetch()) {
      $cids[] = $dao->cid;
    }
    return $cids;
  }

  /**
   * @inheritDoc
   */
  public function cleanup() {
    // clean up all prev next caches older than $cacheTimeIntervalDays days
    // first find all the cacheKeys that match this
    $sql = "
      DELETE     pn, c
      FROM       civicrm_cache c
      INNER JOIN civicrm_prevnext_cache pn ON c.path = pn.cachekey
      WHERE      c.group_name = %1
      AND        c.created_date < date_sub( NOW( ), INTERVAL %2 day )
    ";
    $params = [
      1 => [CRM_Utils_Cache::cleanKey('CiviCRM Search PrevNextCache'), 'String'],
      2 => [self::cacheDays, 'Integer'],
    ];
    CRM_Core_DAO::executeQuery($sql, $params);
  }

}
