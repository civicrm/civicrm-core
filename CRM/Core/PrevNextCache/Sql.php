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

}
