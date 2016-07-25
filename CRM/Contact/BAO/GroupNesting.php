<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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

  static $_sortOrder = 'ASC';

  private $_current;

  private $_parentStack = array();

  private $_lastParentlessGroup;

  private $_styleIndent;

  private $_alreadyStyled = FALSE;

  /**
   * Get the number of levels of nesting.
   *
   * @return int
   */
  protected function getCurrentNestingLevel() {
    return count($this->_parentStack);
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
    if (!$dao->find(TRUE)) {
      $dao->delete();
    }
  }

  /**
   * Returns true if the association between parent and child is present,
   * false otherwise.
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
    if (!$dao->find(TRUE)) {
      return $dao;
    }
    return FALSE;
  }

  /**
   * Returns true if the given groupId has 1 or more parent groups,
   * false otherwise.
   *
   * @param $groupId
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
   * Returns true if checkGroupId is a parent of one of the groups in
   * groupIds, false otherwise.
   *
   * @param array $groupIds
   *   of group ids (or one group id) to serve as the starting point.
   * @param $checkGroupId
   *   The group id to check if it is a parent of the $groupIds group(s).
   *
   * @return bool
   *   True if $checkGroupId points to a group that is a parent of one of the $groupIds groups, false otherwise.
   */
  public static function isParentGroup($groupIds, $checkGroupId) {
    if (!is_array($groupIds)) {
      $groupIds = array($groupIds);
    }
    $dao = new CRM_Contact_DAO_GroupNesting();
    $query = "SELECT parent_group_id FROM civicrm_group_nesting WHERE child_group_id IN (" . implode(',', $groupIds) . ")";
    $dao->query($query);
    while ($dao->fetch()) {
      $parentGroupId = $dao->parent_group_id;
      if ($parentGroupId == $checkGroupId) {
        /* print "One of these: <pre>";
        print_r($groupIds);
        print "</pre> has groupId $checkGroupId as an ancestor.<br/>"; */

        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Returns true if checkGroupId is a child of one of the groups in
   * groupIds, false otherwise.
   *
   * @param array $groupIds
   *   of group ids (or one group id) to serve as the starting point.
   * @param $checkGroupId
   *   The group id to check if it is a child of the $groupIds group(s).
   *
   * @return bool
   *   True if $checkGroupId points to a group that is a child of one of the $groupIds groups, false otherwise.
   */
  public static function isChildGroup($groupIds, $checkGroupId) {

    if (!is_array($groupIds)) {
      $groupIds = array($groupIds);
    }
    $dao = new CRM_Contact_DAO_GroupNesting();
    $query = "SELECT child_group_id FROM civicrm_group_nesting WHERE parent_group_id IN (" . implode(',', $groupIds) . ")";
    //print $query;
    $dao->query($query);
    while ($dao->fetch()) {
      $childGroupId = $dao->child_group_id;
      if ($childGroupId == $checkGroupId) {
        /* print "One of these: <pre>";
        print_r($groupIds);
        print "</pre> has groupId $checkGroupId as a descendent.<br/><br/>"; */

        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Returns true if checkGroupId is an ancestor of one of the groups in
   * groupIds, false otherwise.
   *
   * @param array $groupIds
   *   of group ids (or one group id) to serve as the starting point.
   * @param $checkGroupId
   *   The group id to check if it is an ancestor of the $groupIds group(s).
   *
   * @return bool
   *   True if $checkGroupId points to a group that is an ancestor of one of the $groupIds groups, false otherwise.
   */
  public static function isAncestorGroup($groupIds, $checkGroupId) {
    if (!is_array($groupIds)) {
      $groupIds = array($groupIds);
    }
    $dao = new CRM_Contact_DAO_GroupNesting();
    $query = "SELECT parent_group_id FROM civicrm_group_nesting WHERE child_group_id IN (" . implode(',', $groupIds) . ")";
    $dao->query($query);
    $nextGroupIds = array();
    $gotAtLeastOneResult = FALSE;
    while ($dao->fetch()) {
      $gotAtLeastOneResult = TRUE;
      $parentGroupId = $dao->parent_group_id;
      if ($parentGroupId == $checkGroupId) {
        /* print "One of these: <pre>";
        print_r($groupIds);
        print "</pre> has groupId $checkGroupId as an ancestor.<br/>"; */

        return TRUE;
      }
      $nextGroupIds[] = $parentGroupId;
    }
    if ($gotAtLeastOneResult) {
      return self::isAncestorGroup($nextGroupIds, $checkGroupId);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Returns true if checkGroupId is a descendent of one of the groups in
   * groupIds, false otherwise.
   *
   * @param array $groupIds
   *   of group ids (or one group id) to serve as the starting point.
   * @param $checkGroupId
   *   The group id to check if it is a descendent of the $groupIds group(s).
   *
   * @return bool
   *   True if $checkGroupId points to a group that is a descendent of one of the $groupIds groups, false otherwise.
   */
  public static function isDescendentGroup($groupIds, $checkGroupId) {
    if (!is_array($groupIds)) {
      $groupIds = array($groupIds);
    }
    $dao = new CRM_Contact_DAO_GroupNesting();
    $query = "SELECT child_group_id FROM civicrm_group_nesting WHERE parent_group_id IN (" . implode(',', $groupIds) . ")";
    $dao->query($query);
    $nextGroupIds = array();
    $gotAtLeastOneResult = FALSE;
    while ($dao->fetch()) {
      $gotAtLeastOneResult = TRUE;
      $childGroupId = $dao->child_group_id;
      if ($childGroupId == $checkGroupId) {
        /* print "One of these: <pre>";
        print_r($groupIds);
        print "</pre> has groupId $checkGroupId as a descendent.<br/><br/>"; */

        return TRUE;
      }
      $nextGroupIds[] = $childGroupId;
    }
    if ($gotAtLeastOneResult) {
      return self::isDescendentGroup($nextGroupIds, $checkGroupId);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Returns array of group ids of ancestor groups of the specified group.
   *
   * @param array $groupIds
   *   An array of valid group ids (passed by reference).
   *
   * @param bool $includeSelf
   *
   * @return array
   *   List of groupIds that represent the requested group and its ancestors
   */
  protected static function getAncestorGroupIds($groupIds, $includeSelf = TRUE) {
    if (!is_array($groupIds)) {
      $groupIds = array($groupIds);
    }
    $dao = new CRM_Contact_DAO_GroupNesting();
    $query = "SELECT parent_group_id, child_group_id
                  FROM   civicrm_group_nesting
                  WHERE  child_group_id IN (" . implode(',', $groupIds) . ")";
    $dao->query($query);
    $tmpGroupIds = array();
    $parentGroupIds = array();
    if ($includeSelf) {
      $parentGroupIds = $groupIds;
    }
    while ($dao->fetch()) {
      // make sure we're not following any cyclical references
      if (!array_key_exists($dao->child_group_id, $parentGroupIds) && $dao->parent_group_id != $groupIds[0]) {
        $tmpGroupIds[] = $dao->parent_group_id;
      }
    }
    if (!empty($tmpGroupIds)) {
      $newParentGroupIds = self::getAncestorGroupIds($tmpGroupIds);
      $parentGroupIds = array_merge($parentGroupIds, $newParentGroupIds);
    }
    return $parentGroupIds;
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

  /**
   * Returns array of descendent groups of the specified group.
   *
   * @param array $groupIds
   *   An array of valid group ids
   *
   * @param bool $includeSelf
   * @return array
   *   List of descendent groups
   */
  public static function getDescendentGroups($groupIds, $includeSelf = TRUE) {
    $groupIds = self::getDescendentGroupIds($groupIds, $includeSelf);
    $params['id'] = $groupIds;
    return CRM_Contact_BAO_Group::getGroups($params);
  }

  /**
   * Returns array of group ids of valid potential child groups of the specified group.
   *
   * @param $groupId
   *   The group id to get valid potential children for.
   *
   * @return array
   *   List of groupIds that represent the valid potential children of the group
   */
  public static function getPotentialChildGroupIds($groupId) {
    $groups = CRM_Contact_BAO_Group::getGroups();
    $potentialChildGroupIds = array();
    foreach ($groups as $group) {
      $potentialChildGroupId = $group->id;
      // print "Checking if $potentialChildGroupId is a descendent/ancestor of $groupId<br/><br/>";
      if (!self::isDescendentGroup($groupId, $potentialChildGroupId) &&
        !self::isAncestorGroup($groupId, $potentialChildGroupId) &&
        $potentialChildGroupId != $groupId
      ) {
        $potentialChildGroupIds[] = $potentialChildGroupId;
      }
    }
    return $potentialChildGroupIds;
  }

}
