<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.7                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */

/**
 * BAO object for civicrm_prevnext_cache table
 */
class CRM_Core_BAO_PrevNextCache extends CRM_Core_DAO_PrevNextCache {

  /**
   * @param $cacheKey
   * @param $id1
   * @param $id2
   * @param int $mergeId
   * @param NULL $join
   * @param NULL $where
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
   * @param int $id
   * @param NULL $cacheKey
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
   * @param $id1
   * @param $id2
   * @param NULL $cacheKey
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
        foreach ($select as $dfield => $sfield) {
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
   * @param $cacheKey
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
   * @return bool
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public static function refillCache($rgid, $gid, $cacheKeyString, $criteria, $checkPermissions) {
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
      $foundDupes = CRM_Dedupe_Finder::dupesInGroup($rgid, $gid);
    }
    elseif ($rgid) {
      $contactIDs = array();
      if (!empty($criteria)) {
        $contacts = civicrm_api3('Contact', 'get', array_merge(array('options' => array('limit' => 0), 'return' => 'id'), $criteria['contact']));
        $contactIDs = array_keys($contacts['values']);
      }
      $foundDupes = CRM_Dedupe_Finder::dupes($rgid, $contactIDs, $checkPermissions);
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
   * Save checkbox selections.
   *
   * @param $cacheKey
   * @param string $action
   * @param array $cIds
   * @param string $entity_table
   */
  public static function markSelection($cacheKey, $action = 'unselect', $cIds = NULL, $entity_table = 'civicrm_contact') {
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
   *   Action.
   *  $action : get - get only selection records
   *            getall - get all the records of the specified cache key
   * @param string $entity_table
   *   Entity table.
   *
   * @return array|NULL
   */
  public static function getSelection($cacheKey, $action = 'get', $entity_table = 'civicrm_contact') {
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
   * @return array
   */
  public static function getSelectedContacts() {
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String');
    $cacheKey = "civicrm search {$qfKey}";
    $query = "
SELECT *
FROM   civicrm_prevnext_cache
WHERE  cacheKey LIKE %1
  AND  is_selected=1
  AND  cacheKey NOT LIKE %2
";
    $params1[1] = array("{$cacheKey}%", 'String');
    $params1[2] = array("{$cacheKey}_alphabet%", 'String');
    $dao = CRM_Core_DAO::executeQuery($query, $params1);

    $val = array();
    while ($dao->fetch()) {
      $val[] = $dao->data;
    }
    return $val;
  }

  /**
   * @param CRM_Core_Form $form
   * @param array $params
   *
   * @return mixed
   */
  public static function buildSelectedContactPager(&$form, &$params) {
    $params['status'] = ts('Contacts %%StatusMessage%%');
    $params['csvString'] = NULL;
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
    $params['rowCount'] = $form->get(CRM_Utils_Pager::PAGE_ROWCOUNT);

    if (!$params['rowCount']) {
      $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    }

    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $form);
    $cacheKey = "civicrm search {$qfKey}";

    $query = "
SELECT count(id)
FROM   civicrm_prevnext_cache
WHERE  cacheKey LIKE %1
  AND  is_selected = 1
  AND  cacheKey NOT LIKE %2
";
    $params1[1] = array("{$cacheKey}%", 'String');
    $params1[2] = array("{$cacheKey}_alphabet%", 'String');
    $paramsTotal = CRM_Core_DAO::singleValueQuery($query, $params1);
    $params['total'] = $paramsTotal;
    $form->_pager = new CRM_Utils_Pager($params);
    $form->assign_by_ref('pager', $form->_pager);
    list($offset, $rowCount) = $form->_pager->getOffsetAndRowCount();
    $params['offset'] = $offset;
    $params['rowCount1'] = $rowCount;
    return $params;
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
