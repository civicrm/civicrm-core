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
 * Just another collection of static utils functions.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Utils_SQL {

  /**
   * Helper function for adding the permissioned subquery from one entity onto another
   *
   * @param string $entity
   * @param string $joinColumn
   * @return array
   */
  public static function mergeSubquery($entity, $joinColumn = 'id') {
    require_once 'api/v3/utils.php';
    $baoName = _civicrm_api3_get_BAO($entity);
    $bao = new $baoName();
    $clauses = $subclauses = array();
    foreach ((array) $bao->addSelectWhereClause() as $field => $vals) {
      if ($vals && $field == $joinColumn) {
        $clauses = array_merge($clauses, (array) $vals);
      }
      elseif ($vals) {
        $subclauses[] = "$field " . implode(" AND $field ", (array) $vals);
      }
    }
    if ($subclauses) {
      $clauses[] = "IN (SELECT `$joinColumn` FROM `" . $bao->tableName() . "` WHERE " . implode(' AND ', $subclauses) . ")";
    }
    return $clauses;
  }

}
