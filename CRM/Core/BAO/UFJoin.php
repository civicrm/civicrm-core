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
 *
 */
class CRM_Core_BAO_UFJoin extends CRM_Core_DAO_UFJoin {

  /**
   * takes an associative array and creates a uf join object
   *
   * @param array $params (reference) an assoc array of name/value pairs
   *
   * @return object CRM_Core_DAO_UFJoin object
   * @access public
   * @static
   */
  public static function &create($params) {
    // see if a record exists with the same weight
    $id = self::findJoinEntryId($params);
    if ($id) {
      $params['id'] = $id;
    }

    $dao = new CRM_Core_DAO_UFJoin();
    $dao->copyValues($params);
    if ($params['uf_group_id']) {
      $dao->save();
    }
    else {
      $dao->delete();
    }

    return $dao;
  }

  public static function deleteAll(&$params) {
    $module      = CRM_Utils_Array::value('module', $params);
    $entityTable = CRM_Utils_Array::value('entity_table', $params);
    $entityID    = CRM_Utils_Array::value('entity_id', $params);

    if (empty($entityTable) ||
      empty($entityID) ||
      empty($module)
    ) {
      return;
    }

    $dao               = new CRM_Core_DAO_UFJoin();
    $dao->module       = $module;
    $dao->entity_table = $entityTable;
    $dao->entity_id    = $entityID;
    $dao->delete();
  }

  /**
   * Given an assoc list of params, find if there is a record
   * for this set of params
   *
   * @param array $params (reference) an assoc array of name/value pairs
   *
   * @return int or null
   * @access public
   * @static
   */
  public static function findJoinEntryId(&$params) {
    if (CRM_Utils_Array::value('id', $params)) {
      return $params['id'];
    }

    $dao = new CRM_Core_DAO_UFJoin();

    // CRM-4377 (ab)uses the module column
    if (isset($params['module'])) {
      $dao->module = CRM_Utils_Array::value('module', $params);
    }
    $dao->entity_table = CRM_Utils_Array::value('entity_table', $params);
    $dao->entity_id = CRM_Utils_Array::value('entity_id', $params);
    // user reg / my account can have multiple entries, so we return if thats
    // the case. (since entity_table/id is empty in those cases
    if (!$dao->entity_table ||
      !$dao->entity_id
    ) {
      return NULL;
    }
    $dao->weight = CRM_Utils_Array::value('weight', $params);
    if ($dao->find(TRUE)) {
      return $dao->id;
    }
    return NULL;
  }

  /**
   * Given an assoc list of params, find if there is a record
   * for this set of params and return the group id
   *
   * @param array $params (reference) an assoc array of name/value pairs
   *
   * @return int or null
   * @access public
   * @static
   */
  public static function findUFGroupId(&$params) {

    $dao = new CRM_Core_DAO_UFJoin();

    $dao->entity_table = CRM_Utils_Array::value('entity_table', $params);
    $dao->entity_id    = CRM_Utils_Array::value('entity_id', $params);
    $dao->weight       = CRM_Utils_Array::value('weight', $params);
    if ($dao->find(TRUE)) {
      return $dao->uf_group_id;
    }
    return NULL;
  }

  public static function getUFGroupIds(&$params) {

    $dao = new CRM_Core_DAO_UFJoin();

    // CRM-4377 (ab)uses the module column
    if (isset($params['module'])) {
      $dao->module = CRM_Utils_Array::value('module', $params);
    }
    $dao->entity_table = CRM_Utils_Array::value('entity_table', $params);
    $dao->entity_id = CRM_Utils_Array::value('entity_id', $params);
    $dao->orderBy('weight asc');
    $dao->find();
    $first = $firstActive = NULL;
    $second = $secondActive = array();

    while ($dao->fetch()) {
      if ($dao->weight == 1) {
        $first = $dao->uf_group_id;
        $firstActive = $dao->is_active;
      }
      else {
        $second[] = $dao->uf_group_id;
        $secondActive[] = $dao->is_active;
      }
    }
    return array($first, $second, $firstActive, $secondActive);
  }
}

