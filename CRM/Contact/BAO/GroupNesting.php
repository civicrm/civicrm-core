<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright U.S. PIRG Education Fund (c) 2007                        |
 | Licensed to CiviCRM under the Academic Free License version 3.0.   |
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
 * @copyright U.S. PIRG 2007
 */
class CRM_Contact_BAO_GroupNesting extends CRM_Contact_DAO_GroupNesting {

  /**
   * Add Dashboard.
   *
   * @param array $params
   *   Values.
   *
   *
   * @return object
   */
  public static function create($params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'GroupNesting', CRM_Utils_Array::value('id', $params), $params);
    $dao = new CRM_Contact_BAO_GroupNesting();
    $dao->copyValues($params);
    if (empty($params['id'])) {
      $dao->find(TRUE);
    }
    $dao->save();
    CRM_Utils_Hook::post($hook, 'GroupNesting', $dao->id, $dao);
    return $dao;
  }

  /**
   * Adds a new group nesting record.
   *
   * @param int $parentID
   *   Id of the group to add the child to.
   * @param int $childID
   *   Id of the new child group.
   *
   * @return \CRM_Contact_DAO_GroupNesting
   */
  public static function add($parentID, $childID) {
    $dao = new CRM_Contact_DAO_GroupNesting();
    $dao->child_group_id = $childID;
    $dao->parent_group_id = $parentID;
    if (!$dao->find(TRUE)) {
      $dao->save();
    }
    return $dao;
  }

  /**
   * Removes a child group from it's parent.
   *
   * Does not delete child group, just the association between the two
   *
   * @param int $parentID
   *   The id of the group to remove the child from.
   * @param int $childID
   *   The id of the child group being removed.
   */
  public static function remove($parentID, $childID) {
    $dao = new CRM_Contact_DAO_GroupNesting();
    $dao->child_group_id = $childID;
    $dao->parent_group_id = $parentID;
    if ($dao->find(TRUE)) {
      $dao->delete();
    }
  }

  /**
   * Checks whether the association between parent and child is present.
   *
   * @param int $parentID
   *   The parent id of the association.
   *
   * @param int $childID
   *   The child id of the association.
   *
   * @return bool
   *   True if association is found, false otherwise.
   */
  public static function isParentChild($parentID, $childID) {
    $dao = new CRM_Contact_DAO_GroupNesting();
    $dao->child_group_id = $childID;
    $dao->parent_group_id = $parentID;
    if ($dao->find()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Checks whether groupId has 1 or more parent groups.
   *
   * @param int $groupId
   *   The id of the group to check for parent groups.
   *
   * @return bool
   *   True if 1 or more parent groups are found, false otherwise.
   */
  public static function hasParentGroups($groupId) {
    $dao = new CRM_Contact_DAO_GroupNesting();
    $query = "SELECT parent_group_id FROM civicrm_group_nesting WHERE child_group_id = $groupId LIMIT 1";
    $dao->query($query);
    if ($dao->fetch()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Returns array of group ids of child groups of the specified group.
   *
   * @param array $groupIds
   *   An array of valid group ids (passed by reference).
   *
   * @return array
   *   List of groupIds that represent the requested group and its children
   */
  public static function getChildGroupIds($groupIds) {
    if (!is_array($groupIds)) {
      $groupIds = array($groupIds);
    }
    $dao = new CRM_Contact_DAO_GroupNesting();
    $query = "SELECT child_group_id FROM civicrm_group_nesting WHERE parent_group_id IN (" . implode(',', $groupIds) . ")";
    $dao->query($query);
    $childGroupIds = array();
    while ($dao->fetch()) {
      $childGroupIds[] = $dao->child_group_id;
    }
    return $childGroupIds;
  }

  /**
   * Returns array of group ids of parent groups of the specified group.
   *
   * @param array $groupIds
   *   An array of valid group ids (passed by reference).
   *
   * @return array
   *   List of groupIds that represent the requested group and its parents
   */
  public static function getParentGroupIds($groupIds) {
    if (!is_array($groupIds)) {
      $groupIds = array($groupIds);
    }
    $dao = new CRM_Contact_DAO_GroupNesting();
    $query = "SELECT parent_group_id FROM civicrm_group_nesting WHERE child_group_id IN (" . implode(',', $groupIds) . ")";
    $dao->query($query);
    $parentGroupIds = array();
    while ($dao->fetch()) {
      $parentGroupIds[] = $dao->parent_group_id;
    }
    return $parentGroupIds;
  }

  /**
   * Returns array of group ids of descendent groups of the specified group.
   *
   * @param array $groupIds
   *   An array of valid group ids (passed by reference).
   *
   * @param bool $includeSelf
   *
   * @return array
   *   List of groupIds that represent the requested group and its descendents
   */
  public static function getDescendentGroupIds($groupIds, $includeSelf = TRUE) {
    if (!is_array($groupIds)) {
      $groupIds = array($groupIds);
    }
    $dao = new CRM_Contact_DAO_GroupNesting();
    $query = "SELECT child_group_id, parent_group_id FROM civicrm_group_nesting WHERE parent_group_id IN (" . implode(',', $groupIds) . ")";
    $dao->query($query);
    $tmpGroupIds = array();
    $childGroupIds = array();
    if ($includeSelf) {
      $childGroupIds = $groupIds;
    }
    while ($dao->fetch()) {
      // make sure we're not following any cyclical references
      if (!array_key_exists($dao->parent_group_id, $childGroupIds) && $dao->child_group_id != $groupIds[0]) {
        $tmpGroupIds[] = $dao->child_group_id;
      }
    }
    if (!empty($tmpGroupIds)) {
      $newChildGroupIds = self::getDescendentGroupIds($tmpGroupIds);
      $childGroupIds = array_merge($childGroupIds, $newChildGroupIds);
    }
    return $childGroupIds;
  }

}
