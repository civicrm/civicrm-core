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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * BAO object for civicrm_prevnext_cache table.
 */
class CRM_Core_BAO_PrevNextCache extends CRM_Core_DAO_PrevNextCache {

  /**
   * Get the previous and next keys.
   *
   * @param string $cacheKey
   * @param int $id1
   * @param int $id2
   * @param int $mergeId
   * @param string $join
   * @param string $where
   * @param bool $flip
   *
   * @return array
   */
  public static function getPositions($cacheKey, $id1, $id2, &$mergeId = NULL, $join = NULL, $where = NULL, $flip = FALSE) {
    if ($flip) {
      list($id1, $id2) = array($id2, $id1);
    }

    if ($mergeId == NULL) {
      $query = "
SELECT id
FROM   civicrm_prevnext_cache
WHERE  cacheKey     = %3 AND
       entity_id1 = %1 AND
       entity_id2 = %2 AND
       entity_table = 'civicrm_contact'
";

      $params = array(
        1 => array($id1, 'Integer'),
        2 => array($id2, 'Integer'),
        3 => array($cacheKey, 'String'),
      );

      $mergeId = CRM_Core_DAO::singleValueQuery($query, $params);
    }

    $pos = array('foundEntry' => 0);
    if ($mergeId) {
      $pos['foundEntry'] = 1;

      if ($where) {

        $where = " AND {$where}";

      }
      $p = array(
        1 => array($mergeId, 'Integer'),
        2 => array($cacheKey, 'String'),
      );
      $sql = "SELECT pn.id, pn.entity_id1, pn.entity_id2, pn.data FROM civicrm_prevnext_cache pn {$join} ";
      $wherePrev = " WHERE pn.id < %1 AND pn.cacheKey = %2 {$where} ORDER BY ID DESC LIMIT 1";
      $sqlPrev = $sql . $wherePrev;

      $dao = CRM_Core_DAO::executeQuery($sqlPrev, $p);
      if ($dao->fetch()) {
        $pos['prev']['id1'] = $dao->entity_id1;
        $pos['prev']['id2'] = $dao->entity_id2;
        $pos['prev']['mergeId'] = $dao->id;
        $pos['prev']['data'] = $dao->data;
      }

      $whereNext = " WHERE pn.id > %1 AND pn.cacheKey = %2 {$where} ORDER BY ID ASC LIMIT 1";
      $sqlNext = $sql . $whereNext;

      $dao = CRM_Core_DAO::executeQuery($sqlNext, $p);
      if ($dao->fetch()) {
        $pos['next']['id1'] = $dao->entity_id1;
        $pos['next']['id2'] = $dao->entity_id2;
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
   * @param string $entityTable
   */
  public static function deleteItem($id = NULL, $cacheKey = NULL, $entityTable = 'civicrm_contact') {

    //clear cache
    $sql = "DELETE FROM civicrm_prevnext_cache WHERE  entity_table = %1";
    $params = array(1 => array($entityTable, 'String'));

    if (is_numeric($id)) {
      $sql .= " AND ( entity_id1 = %2 OR entity_id2 = %2 )";
      $params[2] = array($id, 'Integer');
    }

    if (isset($cacheKey)) {
      $sql .= " AND cacheKey LIKE %3";
      $params[3] = array("{$cacheKey}%", 'String');
    }
    CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * Delete from the previous next cache table for a pair of ids.
   *
   * @param int $id1
   * @param int $id2
   * @param string $cacheKey
   * @param bool $isViceVersa
   * @param string $entityTable
   */
  public static function deletePair($id1, $id2, $cacheKey = NULL, $isViceVersa = FALSE, $entityTable = 'civicrm_contact') {
    $sql = "DELETE FROM civicrm_prevnext_cache WHERE  entity_table = %1";
    $params = array(1 => array($entityTable, 'String'));

    $pair = !$isViceVersa ? "entity_id1 = %2 AND entity_id2 = %3" : "(entity_id1 = %2 AND entity_id2 = %3) OR (entity_id1 = %3 AND entity_id2 = %2)";
    $sql .= " AND ( {$pair} )";
    $params[2] = array($id1, 'Integer');
    $params[3] = array($id2, 'Integer');

    if (isset($cacheKey)) {
      $sql .= " AND cacheKey LIKE %4";
      $params[4] = array("{$cacheKey}%", 'String'); // used % to address any row with conflict-cacheKey e.g "merge Individual_8_0_conflicts"
    }

    CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * Mark contacts as being in conflict.
   *
   * @param int $id1
   * @param int $id2
   * @param string $cacheKey
   * @param array $conflicts
   *
   * @return bool
   */
  public static function markConflict($id1, $id2, $cacheKey, $conflicts) {
    if (empty($cacheKey) || empty($conflicts)) {
      return FALSE;
    }

    $sql  = "SELECT pn.*
      FROM  civicrm_prevnext_cache pn
      WHERE
      ((pn.entity_id1 = %1 AND pn.entity_id2 = %2) OR (pn.entity_id1 = %2 AND pn.entity_id2 = %1)) AND
      (cacheKey = %3 OR cacheKey = %4)";
    $params = array(
      1 => array($id1, 'Integer'),
      2 => array($id2, 'Integer'),
      3 => array("{$cacheKey}", 'String'),
      4 => array("{$cacheKey}_conflicts", 'String'),
    );
    $pncFind = CRM_Core_DAO::executeQuery($sql, $params);

    while ($pncFind->fetch()) {
      $data = $pncFind->data;
      if (!empty($data)) {
        $data = unserialize($data);
        $data['conflicts'] = implode(",", array_values($conflicts));

        $pncUp = new CRM_Core_DAO_PrevNextCache();
        $pncUp->id = $pncFind->id;
        if ($pncUp->find(TRUE)) {
          $pncUp->data     = serialize($data);
          $pncUp->cacheKey = "{$cacheKey}_conflicts";
          $pncUp->save();
        }
      }
    }
    return TRUE;
  }

  /**
   * Retrieve from prev-next cache.
   *
   * This function is used from a variety of merge related functions, although
   * it would probably be good to converge on calling CRM_Dedupe_Merger::getDuplicatePairs.
   *
   * We seem to currently be storing stats in this table too & they might make more sense in
   * the main cache table.
   *
   * @param string $cacheKey
   * @param string $join
   * @param string $whereClause
   * @param int $offset
   * @param int $rowCount
   * @param array $select
   * @param string $orderByClause
   * @param bool $includeConflicts
   *   Should we return rows that have already been idenfified as having a conflict.
   *   When this is TRUE you should be careful you do not set up a loop.
   * @param array $params
   *
   * @return array
   */
  public static function retrieve($cacheKey, $join = NULL, $whereClause = NULL, $offset = 0, $rowCount = 0, $select = array(), $orderByClause = '', $includeConflicts = TRUE, $params = array()) {
    $selectString = 'pn.*';

    if (!empty($select)) {
      $aliasArray = array();
      foreach ($select as $column => $alias) {
        $aliasArray[] = $column . ' as ' . $alias;
      }
      $selectString .= " , " . implode(' , ', $aliasArray);
    }

    $params = array(
      1 => array($cacheKey, 'String'),
    ) + $params;

    if (!empty($whereClause)) {
      $whereClause = " AND " . $whereClause;
    }
    if ($includeConflicts) {
      $where = ' WHERE (pn.cacheKey = %1 OR pn.cacheKey = %2)' . $whereClause;
      $params[2] = array("{$cacheKey}_conflicts", 'String');
    }
    else {
      $where = ' WHERE (pn.cacheKey = %1)' . $whereClause;
    }

    $query = "
SELECT SQL_CALC_FOUND_ROWS {$selectString}
FROM   civicrm_prevnext_cache pn
       {$join}
       $where
       $orderByClause
";

    if ($rowCount) {
      $offset = CRM_Utils_Type::escape($offset, 'Int');
      $rowCount = CRM_Utils_Type::escape($rowCount, 'Int');

      $query .= " LIMIT {$offset}, {$rowCount}";
    }

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $main  = array();
    $count = 0;
    while ($dao->fetch()) {
      if (self::is_serialized($dao->data)) {
        $main[$count] = unserialize($dao->data);
      }
      else {
        $main[$count] = $dao->data;
      }

      if (!empty($select)) {
        $extraData = array();
        foreach ($select as $sfield) {
          $extraData[$sfield]  = $dao->$sfield;
        }
        $main[$count] = array(
          'prevnext_id' => $dao->id,
          'is_selected' => $dao->is_selected,
          'entity_id1'  => $dao->entity_id1,
          'entity_id2'  => $dao->entity_id2,
          'data'        => $main[$count],
        );
        $main[$count] = array_merge($main[$count], $extraData);
      }
      $count++;
    }

    return $main;
  }

  /**
   * @param $string
   *
   * @return bool
   */
  public static function is_serialized($string) {
    return (@unserialize($string) !== FALSE);
  }

  /**
   * @param $values
   */
  public static function setItem($values) {
    $insert = "INSERT INTO civicrm_prevnext_cache ( entity_table, entity_id1, entity_id2, cacheKey, data ) VALUES \n";
    $query = $insert . implode(",\n ", $values);

    //dump the dedupe matches in the prevnext_cache table
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Get count of matching rows.
   *
   * @param string $cacheKey
   * @param string $join
   * @param string $where
   * @param string $op
   * @param array $params
   *   Extra query params to parse into the query.
   *
   * @return int
   */
  public static function getCount($cacheKey, $join = NULL, $where = NULL, $op = "=", $params = array()) {
    $query = "
SELECT COUNT(*) FROM civicrm_prevnext_cache pn
       {$join}
WHERE (pn.cacheKey $op %1 OR pn.cacheKey $op %2)
";
    if ($where) {
      $query .= " AND {$where}";
    }

    $params = array(
      1 => array($cacheKey, 'String'),
      2 => array("{$cacheKey}_conflicts", 'String'),
    ) + $params;
    return (int) CRM_Core_DAO::singleValueQuery($query, $params, TRUE, FALSE);
  }

  /**
   * Repopulate the cache of merge prospects.
   *
   * @param int $rgid
   * @param int $gid
   * @param NULL $cacheKeyString
   * @param array $criteria
   *   Additional criteria to filter by.
   *
   * @param bool $checkPermissions
   *   Respect logged in user's permissions.
   *
   * @param int $searchLimit
   *  Limit for the number of contacts to be used for comparison.
   *  The search methodology finds all matches for the searchedContacts so this limits
   *  the number of searched contacts, not the matches found.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public static function refillCache($rgid, $gid, $cacheKeyString, $criteria, $checkPermissions, $searchLimit = 0) {
    if (!$cacheKeyString && $rgid) {
      $cacheKeyString = CRM_Dedupe_Merger::getMergeCacheKeyString($rgid, $gid, $criteria, $checkPermissions);
    }

    if (!$cacheKeyString) {
      return FALSE;
    }

    // 1. Clear cache if any
    $sql = "DELETE FROM civicrm_prevnext_cache WHERE  cacheKey LIKE %1";
    CRM_Core_DAO::executeQuery($sql, array(1 => array("{$cacheKeyString}%", 'String')));

    // FIXME: we need to start using temp tables / queries here instead of arrays.
    // And cleanup code in CRM/Contact/Page/DedupeFind.php

    // 2. FILL cache
    $foundDupes = array();
    if ($rgid && $gid) {
      $foundDupes = CRM_Dedupe_Finder::dupesInGroup($rgid, $gid, $searchLimit);
    }
    elseif ($rgid) {
      $contactIDs = array();
      // The thing we really need to filter out is any chaining that would 'DO SOMETHING' to the DB.
      // criteria could be passed in via url so we want to ensure nothing could be in that url that
      // would chain to a delete. Limiting to getfields for 'get' limits us to declared fields,
      // although we might wish to revisit later to allow joins.
      $validFieldsForRetrieval = civicrm_api3('Contact', 'getfields', ['action' => 'get'])['values'];
      if (!empty($criteria)) {
        $contacts = civicrm_api3('Contact', 'get', array_merge([
          'options' => ['limit' => 0],
          'return' => 'id',
          'check_permissions' => TRUE,
        ], array_intersect_key($criteria['contact'], $validFieldsForRetrieval)));
        $contactIDs = array_keys($contacts['values']);
      }
      $foundDupes = CRM_Dedupe_Finder::dupes($rgid, $contactIDs, $checkPermissions, $searchLimit);
    }

    if (!empty($foundDupes)) {
      CRM_Dedupe_Finder::parseAndStoreDupePairs($foundDupes, $cacheKeyString);
    }
  }

  public static function cleanupCache() {
    // clean up all prev next caches older than $cacheTimeIntervalDays days
    $cacheTimeIntervalDays = 2;

    // first find all the cacheKeys that match this
    $sql = "
DELETE     pn, c
FROM       civicrm_cache c
INNER JOIN civicrm_prevnext_cache pn ON c.path = pn.cacheKey
WHERE      c.group_name = %1
AND        c.created_date < date_sub( NOW( ), INTERVAL %2 day )
";
    $params = array(
      1 => array('CiviCRM Search PrevNextCache', 'String'),
      2 => array($cacheTimeIntervalDays, 'Integer'),
    );
    CRM_Core_DAO::executeQuery($sql, $params);
  }


  /**
   * Get the selections.
   *
   * NOTE: This stub has been preserved because one extension in `universe`
   * was referencing the function.
   *
   * @deprecated
   * @see CRM_Core_PrevNextCache_Sql::getSelection()
   */
  public static function getSelection($cacheKey, $action = 'get') {
    return Civi::service('prevnext')->getSelection($cacheKey, $action);
  }

  /**
   * Flip 2 contacts in the prevNext cache.
   *
   * @param array $prevNextId
   * @param bool $onlySelected
   *   Only flip those which have been marked as selected.
   */
  public static function flipPair(array $prevNextId, $onlySelected) {
    $dao = new CRM_Core_DAO_PrevNextCache();
    if ($onlySelected) {
      $dao->is_selected = 1;
    }
    foreach ($prevNextId as $id) {
      $dao->id = $id;
      if ($dao->find(TRUE)) {
        $originalData = unserialize($dao->data);
        $srcFields = array('ID', 'Name');
        $swapFields = array('srcID', 'srcName', 'dstID', 'dstName');
        $data = array_diff_assoc($originalData, array_fill_keys($swapFields, 1));
        foreach ($srcFields as $key) {
          $data['src' . $key] = $originalData['dst' . $key];
          $data['dst' . $key] = $originalData['src' . $key];
        }
        $dao->data = serialize($data);
        $dao->entity_id1 = $data['dstID'];
        $dao->entity_id2 = $data['srcID'];
        $dao->save();
      }
    }
  }

}
