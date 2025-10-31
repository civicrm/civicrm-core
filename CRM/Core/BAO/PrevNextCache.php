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
use Civi\Api4\Contact;
use Civi\Api4\DedupeRuleGroup;

/**
 * BAO object for civicrm_prevnext_cache table.
 */
class CRM_Core_BAO_PrevNextCache extends CRM_Core_DAO_PrevNextCache {

  /**
   * Get the previous and next keys.
   *
   * @internal as of Feb 2014 no universe usages other than defunct CiviHR
   * code found.
   *
   * @param string $cacheKey
   * @param int $id1
   * @param int $id2
   * @param null $mergeId
   * @param null $ignore
   * @param null $ignore_more
   * @param bool $flip
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public static function getPositions($cacheKey, $id1, $id2, &$mergeId = NULL, $ignore = NULL, $ignore_more = NULL, $flip = FALSE) {
    $join = CRM_Dedupe_Merger::getJoinOnDedupeTable();
    $where = "de.id IS NULL";
    if ($flip) {
      CRM_Core_Error::deprecatedFunctionWarning('handle this outside the function');
      [$id1, $id2] = [$id2, $id1];
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
        $main[$count] = CRM_Utils_String::unserialize($dao->data);
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
   * @param mixed $string
   *
   * @return bool
   */
  public static function is_serialized($string) {
    return (@CRM_Utils_String::unserialize($string) !== FALSE);
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
      if (!empty($criteria) || !empty($searchLimit)) {
        $contactType = DedupeRuleGroup::get(FALSE)
          ->addWhere('id', '=', $rgid)
          ->addSelect('contact_type')
          ->execute()->first()['contact_type'];
        if (isset($criteria['where'])) {
          // API v4 criteria.
          $contacts = (array) Contact::get()
            ->addSelect('id')
            ->setLimit($searchLimit)
            ->setWhere($criteria['where'])
            ->addWhere('contact_type', '=', $contactType)
            ->execute()->indexBy('id');
        }
        else {
          // The thing we really need to filter out is any chaining that would 'DO SOMETHING' to the DB.
          // criteria could be passed in via url so we want to ensure nothing could be in that url that
          // would chain to a delete. Limiting to getfields for 'get' limits us to declared fields,
          // although we might wish to revisit later to allow joins.
          $validFieldsForRetrieval = civicrm_api3('Contact', 'getfields', ['action' => 'get'])['values'];
          $filteredCriteria = isset($criteria['contact']) ? array_intersect_key($criteria['contact'], $validFieldsForRetrieval) : [];

          $contacts = civicrm_api3('Contact', 'get', array_merge([
            'options' => ['limit' => $searchLimit],
            'return' => 'id',
            'check_permissions' => TRUE,
            'contact_type' => $contactType,
          ], $filteredCriteria))['values'];
        }
        $contactIDs = array_keys($contacts);

        if (empty($contactIDs)) {
          // If there is criteria but no contacts were found then we should return now
          // since we have no contacts to match.
          return [];
        }
      }
      $foundDupes = CRM_Dedupe_Finder::dupes($rgid, $contactIDs, $checkPermissions);
    }

    if (!empty($foundDupes)) {
      self::parseAndStoreDupePairs($foundDupes, $cacheKeyString);
    }
  }

  /**
   * Parse duplicate pairs into a standardised array and store in the prev_next_cache.
   *
   * @param array $foundDupes
   * @param string $cacheKeyString
   *
   * @return array
   *   Dupe pairs with the keys
   *   -srcID
   *   -srcName
   *   -dstID
   *   -dstName
   *   -weight
   *   -canMerge
   */
  private static function parseAndStoreDupePairs($foundDupes, $cacheKeyString) {
    $cids = [];
    foreach ($foundDupes as $dupe) {
      $cids[$dupe[0]] = 1;
      $cids[$dupe[1]] = 1;
    }
    $cidString = implode(', ', array_keys($cids));

    $dao = CRM_Core_DAO::executeQuery("SELECT id, display_name FROM civicrm_contact WHERE id IN ($cidString) ORDER BY sort_name");
    $displayNames = [];
    while ($dao->fetch()) {
      $displayNames[$dao->id] = $dao->display_name;
    }

    $userId = CRM_Core_Session::getLoggedInContactID();
    foreach ($foundDupes as $dupes) {
      $srcID = $dupes[1];
      $dstID = $dupes[0];
      // The logged in user should never be the src (ie. the contact to be removed).
      if ($srcID == $userId) {
        $srcID = $dstID;
        $dstID = $userId;
      }

      $mainContacts[] = $row = [
        'dstID' => (int) $dstID,
        'dstName' => $displayNames[$dstID],
        'srcID' => (int) $srcID,
        'srcName' => $displayNames[$srcID],
        'weight' => $dupes[2],
        'canMerge' => TRUE,
      ];

      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_prevnext_cache (entity_table, entity_id1, entity_id2, cacheKey, data) VALUES
        ('civicrm_contact', %1, %2, %3, %4)", [
          1 => [$dstID, 'Integer'],
          2 => [$srcID, 'Integer'],
          3 => [$cacheKeyString, 'String'],
          4 => [serialize($row), 'String'],
        ]
      );
    }
    return $mainContacts;
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
        $originalData = CRM_Utils_String::unserialize($dao->data);
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
   *
   * @throws \CRM_Core_Exception
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
