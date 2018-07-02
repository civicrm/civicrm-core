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
  public function fetch($cacheKey, $offset, $rowCount, $includeContactIds, $queryBao) {
    $queryBao->_includeContactIds = $includeContactIds;
    $onlyDeleted = in_array(array('deleted_contacts', '=', '1', '0', '0'), $queryBao->_params);
    list($select, $from, $where) = $queryBao->query(FALSE, FALSE, FALSE, $onlyDeleted);
    $from = " FROM civicrm_prevnext_cache pnc INNER JOIN civicrm_contact contact_a ON contact_a.id = pnc.entity_id1 AND pnc.cacheKey = '$cacheKey' " . substr($from, 31);
    $order = " ORDER BY pnc.id";
    $groupByCol = array('contact_a.id', 'pnc.id');
    $select = CRM_Contact_BAO_Query::appendAnyValueToSelect($queryBao->_select, $groupByCol, 'GROUP_CONCAT');
    $groupBy = " GROUP BY " . implode(', ', $groupByCol);
    $limit = " LIMIT $offset, $rowCount";
    $query = "$select $from $where $groupBy $order $limit";

    return CRM_Core_DAO::executeQuery($query)->fetchGenerator();
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
   * @param string $entity_table
   *   Ex: 'civicrm_contact'.
   */
  public function markSelection($cacheKey, $action = 'unselect', $cIds = NULL, $entity_table = 'civicrm_contact') {
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

}
