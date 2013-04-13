<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.3                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * BAO object for civicrm_prevnext_cache table
 */
class CRM_Core_BAO_PrevNextCache extends CRM_Core_DAO_PrevNextCache {

  static function getPositions($cacheKey, $id1, $id2, &$mergeId = NULL, $join = NULL, $where = NULL, $flip = FALSE) {
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

      $params = array(1 => array($id1, 'Integer'),
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
      $p = array(1 => array($mergeId, 'Integer'),
           2 => array($cacheKey, 'String'),
      );
      $sql       = "SELECT pn.id, pn.entity_id1, pn.entity_id2, pn.data FROM civicrm_prevnext_cache pn {$join} ";
      $wherePrev = " WHERE pn.id < %1 AND pn.cacheKey = %2 {$where} ORDER BY ID DESC LIMIT 1";
      $sqlPrev   = $sql . $wherePrev;


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

  static function deleteItem($id = NULL, $cacheKey = NULL, $entityTable = 'civicrm_contact') {

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

  static function deletePair($id1, $id2, $cacheKey = NULL, $isViceVersa = FALSE, $entityTable = 'civicrm_contact') {
    $sql = "DELETE FROM civicrm_prevnext_cache WHERE  entity_table = %1";
    $params = array(1 => array($entityTable, 'String'));

    $pair =
      ! $isViceVersa ?
      "entity_id1 = %2 AND entity_id2 = %3" :
      "(entity_id1 = %2 AND entity_id2 = %3) OR (entity_id1 = %3 AND entity_id2 = %2)";
    $sql .= " AND ( {$pair} )";
    $params[2] = array($id1, 'Integer');
    $params[3] = array($id2, 'Integer');

    if (isset($cacheKey)) {
      $sql .= " AND cacheKey LIKE %4";
      $params[4] = array("{$cacheKey}%", 'String');
    }

    CRM_Core_DAO::executeQuery($sql, $params);
  }

  function retrieve($cacheKey, $join = NULL, $where = NULL, $offset = 0, $rowCount = 0) {
    $query = "
SELECT data
FROM   civicrm_prevnext_cache pn
       {$join}
WHERE  cacheKey = %1
";
    $params = array(1 => array($cacheKey, 'String'));

    if ($where) {
      $query .= " AND {$where}";
    }

    if ($rowCount) {
      $query .= " LIMIT {$offset}, {$rowCount}";
    }

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $main = array();
    while ($dao->fetch()) {
      if (self::is_serialized($dao->data)) {
        $main[] = unserialize($dao->data);
      }
      else {
        $main[] = $dao->data;
      }
    }

    return $main;
  }

  public static function is_serialized($string) {
    return (@unserialize($string) !== false);
  }

  static function setItem($values) {
    $insert = "INSERT INTO civicrm_prevnext_cache ( entity_table, entity_id1, entity_id2, cacheKey, data ) VALUES \n";
    $query = $insert . implode(",\n ", $values);

    //dump the dedupe matches in the prevnext_cache table
    CRM_Core_DAO::executeQuery($query);
  }

  static function getCount($cacheKey, $join = NULL, $where = NULL, $op = "=") {
    $query = "
SELECT COUNT(*) FROM civicrm_prevnext_cache pn
       {$join}
WHERE cacheKey $op %1
";
    if ($where) {
      $query .= " AND {$where}";
    }

    $params = array(1 => array($cacheKey, 'String'));
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  static function refillCache($rgid = NULL, $gid = NULL, $cacheKeyString = NULL) {
    if (!$cacheKeyString && $rgid) {
      $contactType = CRM_Core_DAO::getFieldValue('CRM_Dedupe_DAO_RuleGroup', $rgid, 'contact_type');
      $cacheKeyString = "merge {$contactType}";
      $cacheKeyString .= $rgid ? "_{$rgid}" : '_0';
      $cacheKeyString .= $gid ? "_{$gid}" : '_0';
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
      $foundDupes = CRM_Dedupe_Finder::dupes($rgid);
    }

    if (!empty($foundDupes)) {
      $cids = $displayNames = $values = array();
      foreach ($foundDupes as $dupe) {
        $cids[$dupe[0]] = 1;
        $cids[$dupe[1]] = 1;
      }
      $cidString = implode(', ', array_keys($cids));
      $sql       = "SELECT id, display_name FROM civicrm_contact WHERE id IN ($cidString) ORDER BY sort_name";
      $dao       = new CRM_Core_DAO();
      $dao->query($sql);
      while ($dao->fetch()) {
        $displayNames[$dao->id] = $dao->display_name;
      }

      $session = CRM_Core_Session::singleton();
      $userId = $session->get('userID');

      foreach ($foundDupes as $dupes) {
        $srcID = $dupes[0];
        $dstID = $dupes[1];
        if ($dstID == $userId) {
          $srcID = $dupes[1];
          $dstID = $dupes[0];
        }

        $row = array(
          'srcID' => $srcID,
          'srcName' => $displayNames[$srcID],
          'dstID' => $dstID,
          'dstName' => $displayNames[$dstID],
          'weight' => $dupes[2],
          'canMerge' => TRUE,
        );

        $data = CRM_Core_DAO::escapeString(serialize($row));
        $values[] = " ( 'civicrm_contact', $srcID, $dstID, '$cacheKeyString', '$data' ) ";
      }
      self::setItem($values);
    }
  }

  static function cleanupCache() {
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


  /* function for saving the checkbox selections
   * $action select   - select a particular contact
   *         unselect - unselect a particular contact
   */
  static function markSelection($cacheKey, $action = 'unselect', $cIds = NULL, $entity_table = 'civicrm_contact') {
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
   * function to get the selections
   *
   * @param string $cacheKey     cache key
   * @param string $action       action
   *  $action : get - get only selection records
   *            getall - get all the records of the specified cache key
   * @param string $entity_table entity table
   */
  static function getSelection($cacheKey, $action = 'get', $entity_table = 'civicrm_contact') {
    if (!$cacheKey) {
      return;
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

  static function getSelectedContacts() {
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

    $val = array( );
    while ($dao->fetch()) {
      $val[] = $dao->data;
    }
    return $val;
  }

  static function buildSelectedContactPager( &$obj, &$params) {
    $params['status'] = ts('Contacts %%StatusMessage%%');
    $params['csvString'] = NULL;
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
    $params['rowCount'] = $obj->get(CRM_Utils_Pager::PAGE_ROWCOUNT);

    if (!$params['rowCount']) {
      $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    }

    $qfKey = CRM_Utils_Request::retrieve('qfKey','String', $this);
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
    $paramsTotal     = CRM_Core_DAO::singleValueQuery($query, $params1);
    $params['total'] = $paramsTotal;
    $obj->_pager    = new CRM_Utils_Pager($params);
    $obj->assign_by_ref('pager', $obj->_pager);
    list($offset, $rowCount) = $obj->_pager->getOffsetAndRowCount();
    $params['offset'] = $offset;
    $params['rowCount1'] = $rowCount;
    return $params;
  }
}

