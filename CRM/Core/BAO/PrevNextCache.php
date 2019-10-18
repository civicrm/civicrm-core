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
      list($id1, $id2) = [$id2, $id1];
    }

    if ($mergeId == NULL) {
      $query = "
SELECT id
FROM   civicrm_prevnext_cache
WHERE  cachekey     = %3 AND
       entity_id1 = %1 AND
       entity_id2 = %2 AND
       entity_table = 'civicrm_contact'
";

      $params = [
        1 => [$id1, 'Integer'],
        2 => [$id2, 'Integer'],
        3 => [$cacheKey, 'String'],
      ];

      $mergeId = CRM_Core_DAO::singleValueQuery($query, $params);
    }

    $pos = ['foundEntry' => 0];
    if ($mergeId) {
      $pos['foundEntry'] = 1;

      if ($where) {

        $where = " AND {$where}";

      }
      $p = [
        1 => [$mergeId, 'Integer'],
        2 => [$cacheKey, 'String'],
      ];
      $sql = "SELECT pn.id, pn.entity_id1, pn.entity_id2, pn.data FROM civicrm_prevnext_cache pn {$join} ";
      $wherePrev = " WHERE pn.id < %1 AND pn.cachekey = %2 {$where} ORDER BY ID DESC LIMIT 1";
      $sqlPrev = $sql . $wherePrev;

      $dao = CRM_Core_DAO::executeQuery($sqlPrev, $p);
      if ($dao->fetch()) {
        $pos['prev']['id1'] = $dao->entity_id1;
        $pos['prev']['id2'] = $dao->entity_id2;
        $pos['prev']['mergeId'] = $dao->id;
        $pos['prev']['data'] = $dao->data;
      }

      $whereNext = " WHERE pn.id > %1 AND pn.cachekey = %2 {$where} ORDER BY ID ASC LIMIT 1";
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
    $params = [1 => [$entityTable, 'String']];

    if (is_numeric($id)) {
      $sql .= " AND ( entity_id1 = %2 OR entity_id2 = %2 )";
      $params[2] = [$id, 'Integer'];
    }

    if (isset($cacheKey)) {
      $sql .= " AND cachekey LIKE %3";
      $params[3] = ["{$cacheKey}%", 'String'];
    }
    CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * Delete pair from the previous next cache table to remove it from further merge consideration.
   *
   * The pair may have been flipped, so make sure we delete using both orders
   *
   * @param int $id1
   * @param int $id2
   * @param string $cacheKey
   */
  public static function deletePair($id1, $id2, $cacheKey = NULL) {
    $sql = "DELETE FROM civicrm_prevnext_cache WHERE  entity_table = 'civicrm_contact'";

    $pair = "(entity_id1 = %2 AND entity_id2 = %3) OR (entity_id1 = %3 AND entity_id2 = %2)";
    $sql .= " AND ( {$pair} )";
    $params[2] = [$id1, 'Integer'];
    $params[3] = [$id2, 'Integer'];

    if (isset($cacheKey)) {
      $sql .= " AND cachekey LIKE %4";
      // used % to address any row with conflict-cacheKey e.g "merge Individual_8_0_conflicts"
      $params[4] = ["{$cacheKey}%", 'String'];
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
   * @param string $mode
   *
   * @return bool
   * @throws CRM_Core_Exception
   */
  public static function markConflict($id1, $id2, $cacheKey, $conflicts, $mode) {
    if (empty($cacheKey) || empty($conflicts)) {
      return FALSE;
    }

    $sql  = "SELECT pn.*
      FROM  civicrm_prevnext_cache pn
      WHERE
      ((pn.entity_id1 = %1 AND pn.entity_id2 = %2) OR (pn.entity_id1 = %2 AND pn.entity_id2 = %1)) AND
      (cachekey = %3 OR cachekey = %4)";
    $params = [
      1 => [$id1, 'Integer'],
      2 => [$id2, 'Integer'],
      3 => ["{$cacheKey}", 'String'],
      4 => ["{$cacheKey}_conflicts", 'String'],
    ];
    $pncFind = CRM_Core_DAO::executeQuery($sql, $params);

    $conflictTexts = [];

    foreach ($conflicts as $entity => $entityConflicts) {
      if ($entity === 'contact') {
        foreach ($entityConflicts as $conflict) {
          $conflictTexts[] = "{$conflict['title']}: '{$conflict[$id1]}' vs. '{$conflict[$id2]}'";
        }
      }
      else {
        foreach ($entityConflicts as $locationConflict) {
          if (!is_array($locationConflict)) {
            continue;
          }
          $displayField = CRM_Dedupe_Merger::getLocationBlockInfo()[$entity]['displayField'];
          $conflictTexts[] = "{$locationConflict['title']}: '{$locationConflict[$displayField][$id1]}' vs. '{$locationConflict[$displayField][$id2]}'";
        }
      }
    }
    $conflictString = implode(', ', $conflictTexts);

    while ($pncFind->fetch()) {
      $data = $pncFind->data;
      if (!empty($data)) {
        $data = CRM_Core_DAO::unSerializeField($data, CRM_Core_DAO::SERIALIZE_PHP);
        $data['conflicts'] = $conflictString;
        $data[$mode]['conflicts'] = $conflicts;

        $pncUp = new CRM_Core_DAO_PrevNextCache();
        $pncUp->id = $pncFind->id;
        if ($pncUp->find(TRUE)) {
          $pncUp->data     = serialize($data);
          $pncUp->cachekey = "{$cacheKey}_conflicts";
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
  public static function retrieve($cacheKey, $join = NULL, $whereClause = NULL, $offset = 0, $rowCount = 0, $select = [], $orderByClause = '', $includeConflicts = TRUE, $params = []) {
    $selectString = 'pn.*';

    if (!empty($select)) {
      $aliasArray = [];
      foreach ($select as $column => $alias) {
        $aliasArray[] = $column . ' as ' . $alias;
      }
      $selectString .= " , " . implode(' , ', $aliasArray);
    }

    $params = [
      1 => [$cacheKey, 'String'],
    ] + $params;

    if (!empty($whereClause)) {
      $whereClause = " AND " . $whereClause;
    }
    if ($includeConflicts) {
      $where = ' WHERE (pn.cachekey = %1 OR pn.cachekey = %2)' . $whereClause;
      $params[2] = ["{$cacheKey}_conflicts", 'String'];
    }
    else {
      $where = ' WHERE (pn.cachekey = %1)' . $whereClause;
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

    $main  = [];
    $count = 0;
    while ($dao->fetch()) {
      if (self::is_serialized($dao->data)) {
        $main[$count] = unserialize($dao->data);
      }
      else {
        $main[$count] = $dao->data;
      }

      if (!empty($select)) {
        $extraData = [];
        foreach ($select as $sfield) {
          $extraData[$sfield] = $dao->$sfield;
        }
        $main[$count] = [
          'prevnext_id' => $dao->id,
          'is_selected' => $dao->is_selected,
          'entity_id1'  => $dao->entity_id1,
          'entity_id2'  => $dao->entity_id2,
          'data'        => $main[$count],
        ];
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
   * @param string $sqlValues string of SQLValues to insert
   * @return array
   */
  public static function convertSetItemValues($sqlValues) {
    $closingBrace = strpos($sqlValues, ')') - strlen($sqlValues);
    $valueArray = array_map('trim', explode(', ', substr($sqlValues, strpos($sqlValues, '(') + 1, $closingBrace - 1)));
    foreach ($valueArray as $key => &$value) {
      // remove any quotes from values.
      if (substr($value, 0, 1) == "'") {
        $valueArray[$key] = substr($value, 1, -1);
      }
    }
    return $valueArray;
  }

  /**
   * @param array|string $entity_table
   * @param int $entity_id1
   * @param int $entity_id2
   * @param string $cacheKey
   * @param string $data
   */
  public static function setItem($entity_table = NULL, $entity_id1 = NULL, $entity_id2 = NULL, $cacheKey = NULL, $data = NULL) {
    // If entity table is an array we are passing in an older format where this function only had 1 param $values. We put a deprecation warning.
    if (!empty($entity_table) && is_array($entity_table)) {
      Civi::log()->warning('Deprecated code path. Values should not be set this is going away in the future in favour of specific function params for each column.', array('civi.tag' => 'deprecated'));
      foreach ($values as $value) {
        $valueArray = self::convertSetItemValues($value);
        self::setItem($valueArray[0], $valueArray[1], $valueArray[2], $valueArray[3], $valueArray[4]);
      }
    }
    else {
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_prevnext_cache (entity_table, entity_id1, entity_id2, cacheKey, data) VALUES
        (%1, %2, %3, %4, '{$data}')", [
          1 => [$entity_table, 'String'],
          2 => [$entity_id1, 'Integer'],
          3 => [$entity_id2, 'Integer'],
          4 => [$cacheKey, 'String'],
        ]);
    }
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
  public static function getCount($cacheKey, $join = NULL, $where = NULL, $op = "=", $params = []) {
    $query = "
SELECT COUNT(*) FROM civicrm_prevnext_cache pn
       {$join}
WHERE (pn.cachekey $op %1 OR pn.cachekey $op %2)
";
    if ($where) {
      $query .= " AND {$where}";
    }

    $params = [
      1 => [$cacheKey, 'String'],
      2 => ["{$cacheKey}_conflicts", 'String'],
    ] + $params;
    return (int) CRM_Core_DAO::singleValueQuery($query, $params, TRUE, FALSE);
  }

  /**
   * Repopulate the cache of merge prospects.
   *
   * @param int $rgid
   * @param int $gid
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
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public static function refillCache($rgid, $gid, $criteria, $checkPermissions, $searchLimit = 0) {
    $cacheKeyString = CRM_Dedupe_Merger::getMergeCacheKeyString($rgid, $gid, $criteria, $checkPermissions, $searchLimit);

    // 1. Clear cache if any
    $sql = "DELETE FROM civicrm_prevnext_cache WHERE  cachekey LIKE %1";
    CRM_Core_DAO::executeQuery($sql, [1 => ["{$cacheKeyString}%", 'String']]);

    // FIXME: we need to start using temp tables / queries here instead of arrays.
    // And cleanup code in CRM/Contact/Page/DedupeFind.php

    // 2. FILL cache
    $foundDupes = [];
    if ($rgid && $gid) {
      $foundDupes = CRM_Dedupe_Finder::dupesInGroup($rgid, $gid, $searchLimit);
    }
    elseif ($rgid) {
      $contactIDs = [];
      // The thing we really need to filter out is any chaining that would 'DO SOMETHING' to the DB.
      // criteria could be passed in via url so we want to ensure nothing could be in that url that
      // would chain to a delete. Limiting to getfields for 'get' limits us to declared fields,
      // although we might wish to revisit later to allow joins.
      $validFieldsForRetrieval = civicrm_api3('Contact', 'getfields', ['action' => 'get'])['values'];
      $filteredCriteria = isset($criteria['contact']) ? array_intersect_key($criteria['contact'], $validFieldsForRetrieval) : [];

      if (!empty($criteria) || !empty($searchLimit)) {
        $contacts = civicrm_api3('Contact', 'get', array_merge([
          'options' => ['limit' => $searchLimit],
          'return' => 'id',
          'check_permissions' => TRUE,
          'contact_type' => civicrm_api3('RuleGroup', 'getvalue', ['id' => $rgid, 'return' => 'contact_type']),
        ], $filteredCriteria));
        $contactIDs = array_keys($contacts['values']);

        if (empty($contactIDs)) {
          // If there is criteria but no contacts were found then we should return now
          // since we have no contacts to match.
          return [];
        }
      }
      $foundDupes = CRM_Dedupe_Finder::dupes($rgid, $contactIDs, $checkPermissions);
    }

    if (!empty($foundDupes)) {
      CRM_Dedupe_Finder::parseAndStoreDupePairs($foundDupes, $cacheKeyString);
    }
  }

  public static function cleanupCache() {
    Civi::service('prevnext')->cleanup();
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
        $srcFields = ['ID', 'Name'];
        $swapFields = ['srcID', 'srcName', 'dstID', 'dstName'];
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

  /**
   * Get a list of available backend services.
   *
   * @return array
   *   Array(string $id => string $label).
   */
  public static function getPrevNextBackends() {
    return [
      'default' => ts('Default (Auto-detect)'),
      'sql' => ts('SQL'),
      'redis' => ts('Redis'),
    ];
  }

  /**
   * Generate and assign an arbitrary value to a field of a test object.
   *
   * This specifically supports testing the dedupe use case.
   *
   * @param string $fieldName
   * @param array $fieldDef
   * @param int $counter
   *   The globally-unique ID of the test object.
   */
  protected function assignTestValue($fieldName, &$fieldDef, $counter) {
    if ($fieldName === 'cachekey') {
      $this->cachekey = 'merge_' . rand();
      return;
    }
    if ($fieldName === 'data') {
      $this->data = serialize([]);
      return;
    }
    parent::assignTestValue($fieldName, $fieldDef, $counter);
  }

}
