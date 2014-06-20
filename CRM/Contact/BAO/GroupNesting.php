<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
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
 * $Id$
 *
 */
class CRM_Contact_BAO_GroupNesting extends CRM_Contact_DAO_GroupNesting implements Iterator {

  static $_sortOrder = 'ASC';

  private $_current;

  private $_parentStack = array();

  private $_lastParentlessGroup;

  private $_styleLabels;

  private $_styleIndent;

  private $_alreadyStyled = FALSE;

  /**
   * class constructor
   */
  function __construct($styleLabels = FALSE, $styleIndent = "&nbsp;--&nbsp;") {
    parent::__construct();
    $this->_styleLabels = $styleLabels;
    $this->_styleIndent = $styleIndent;
  }

  /**
   * @param $sortOrder
   */
  function setSortOrder($sortOrder) {
    switch ($sortOrder) {
      case 'ASC':
      case 'DESC':
        if ($sortOrder != self::$_sortOrder) {
          self::$_sortOrder = $sortOrder;
          $this->rewind();
        }
        break;

      default:
        // spit out some error, someday
    }
  }

  /**
   * @return string
   */
  function getSortOrder() {
    return self::$_sortOrder;
  }

  /**
   * @return int
   */
  function getCurrentNestingLevel() {
    return count($this->_parentStack);
  }

  /**
   * Go back to the first element in the group nesting graph,
   * which is the first group (according to _sortOrder) that
   * has no parent groups
   */
  function rewind() {
    $this->_parentStack = array();
    // calling _getNextParentlessGroup w/ no arguments
    // makes it return the first parentless group
    $firstGroup = $this->_getNextParentlessGroup();
    $this->_current = $firstGroup;
    $this->_lastParentlessGroup = $firstGroup;
    $this->_alreadyStyled = FALSE;
  }

  function current() {
    if ($this->_styleLabels &&
      $this->valid() &&
      !$this->_alreadyStyled
    ) {
      $styledGroup  = clone($this->_current);
      $nestingLevel = $this->getCurrentNestingLevel();
      $indent       = '';
      while ($nestingLevel--) {
        $indent .= $this->_styleIndent;
      }
      $styledGroup->title = $indent . $styledGroup->title;

      $this->_current = &$styledGroup;
      $this->_alreadyStyled = TRUE;
    }
    return $this->_current;
  }

  /**
   * @return string
   */
  function key() {
    $group = &$this->_current;
    $ids = array();
    foreach ($this->_parentStack as $parentGroup) {
      $ids[] = $parentGroup->id;
    }
    $key = implode('-', $ids);
    if (strlen($key) > 0) {
      $key .= '-';
    }
    $key .= $group->id;
    return $key;
  }

  /**
   * @return CRM_Contact_BAO_Group|null
   */
  function next() {
    $currentGroup = &$this->_current;
    $childGroup = $this->_getNextChildGroup($currentGroup);
    if ($childGroup) {
      $nextGroup = &$childGroup;
      $this->_parentStack[] = &$this->_current;
    }
    else {
      $nextGroup = $this->_getNextSiblingGroup($currentGroup);
      if (!$nextGroup) {
        // no sibling, find an ancestor w/ a sibling
        for (;; ) {
          // since we pop this array everytime, we should be
          // reasonably safe from infinite loops, I think :)
          $ancestor = array_pop($this->_parentStack);
          $this->_current = &$ancestor;
          if ($ancestor == NULL) {
            break;
          }
          $nextGroup = $this->_getNextSiblingGroup($ancestor);
          if ($nextGroup) {
            break;
          }
        }
      }
    }
    $this->_current = &$nextGroup;
    $this->_alreadyStyled = FALSE;
    return $nextGroup;
  }

  /**
   * @return bool
   */
  function valid() {
    if ($this->_current) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * @param null $group
   *
   * @return CRM_Contact_BAO_Group|null
   */
  function _getNextParentlessGroup(&$group = NULL) {
    $lastParentlessGroup = $this->_lastParentlessGroup;
    $nextGroup           = new CRM_Contact_BAO_Group();
    $nextGroup->order_by = 'title ' . self::$_sortOrder;
    $nextGroup->find();
    if ($group == NULL) {
      $sawLast = TRUE;
    }
    else {
      $sawLast = FALSE;
    }
    while ($nextGroup->fetch()) {
      if (!self::hasParentGroups($nextGroup->id) && $sawLast) {
        return $nextGroup;
      }
      elseif ($lastParentlessGroup->id == $nextGroup->id) {
        $sawLast = TRUE;
      }
    }
    return NULL;
  }

  /**
   * @param $parentGroup
   * @param null $group
   *
   * @return CRM_Contact_BAO_Group|null
   */
  function _getNextChildGroup(&$parentGroup, &$group = NULL) {
    $children = self::getChildGroupIds($parentGroup->id);
    if (count($children) > 0) {
      // we have child groups, so get the first one based on _sortOrder
      $childGroup = new CRM_Contact_BAO_Group();
      $cgQuery = "SELECT * FROM civicrm_group WHERE id IN (" . implode(',', $children) . ") ORDER BY title " . self::$_sortOrder;
      $childGroup->query($cgQuery);
      $currentGroup = &$this->_current;
      if ($group == NULL) {
        $sawLast = TRUE;
      }
      else {
        $sawLast = FALSE;
      }
      while ($childGroup->fetch()) {
        if ($sawLast) {
          return $childGroup;
        }
        elseif ($currentGroup->id === $childGroup->id) {
          $sawLast = TRUE;
        }
      }
    }
    return NULL;
  }

  /**
   * @param $group
   *
   * @return CRM_Contact_BAO_Group|null
   */
  function _getNextSiblingGroup(&$group) {
    $parentGroup = end($this->_parentStack);
    if ($parentGroup) {
      $nextGroup = $this->_getNextChildGroup($parentGroup, $group);
      return $nextGroup;
    }
    else {
      /* if we get here, it could be because we're out of siblings
             * (in which case we return null) or because we're at the
             * top level groups which do not have parents but may still
             * have siblings, so check for that first.
             */

      $nextGroup = $this->_getNextParentlessGroup($group);
      if ($nextGroup) {
        $this->_lastParentlessGroup = $nextGroup;
        return $nextGroup;
      }
      return NULL;
    }
  }

  /**
   * Adds a new child group identified by $childGroupId to the group
   * identified by $groupId
   *
   * @param $parentID
   * @param $childID
   *
   * @internal param \The $groupId id of the group to add the child to
   * @internal param \The $childGroupId id of the new child group
   *
   * @return           void
   *
   * @access public
   */
  static function add($parentID, $childID) {
    // TODO: Add checks here to make sure invalid nests can't be created
    $dao = new CRM_Contact_DAO_GroupNesting();
    $query = "REPLACE INTO civicrm_group_nesting (child_group_id, parent_group_id) VALUES ($childID,$parentID);";
    $dao->query($query);
  }

  /**
   * Removes a child group identified by $childGroupId from the group
   * identified by $groupId; does not delete child group, just the
   * association between the two
   *
   * @param            $parentID         The id of the group to remove the child from
   * @param            $childID          The id of the child group being removed
   *
   * @return           void
   *
   * @access public
   */
  static function remove($parentID, $childID) {
    $dao = new CRM_Contact_DAO_GroupNesting();
    $query = "DELETE FROM civicrm_group_nesting WHERE child_group_id = $childID AND parent_group_id = $parentID";
    $dao->query($query);
  }

  /**
   * Removes associations where a child group is identified by $childGroupId from the group
   * identified by $groupId; does not delete child group, just the
   * association between the two
   *
   * @param            $childID          The id of the child group being removed
   *
   * @internal param \The $parentID id of the group to remove the child from
   * @return           void
   *
   * @access public
   */
  static function removeAllParentForChild($childID) {
    $dao = new CRM_Contact_DAO_GroupNesting();
    $query = "DELETE FROM civicrm_group_nesting WHERE child_group_id = $childID";
    $dao->query($query);
  }

  /**
   * Returns true if the association between parent and child is present,
   * false otherwise.
   *
   * @param            $parentID         The parent id of the association
   * @param            $childID          The child id of the association
   *
   * @return           boolean           True if association is found, false otherwise.
   *
   * @access public
   */
  static function isParentChild($parentID, $childID) {
    $dao = new CRM_Contact_DAO_GroupNesting();
    $query = "SELECT id FROM civicrm_group_nesting WHERE child_group_id = $childID AND parent_group_id = $parentID";
    $dao->query($query);
    if ($dao->fetch()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Returns true if if the given groupId has 1 or more child groups,
   * false otherwise.
   *
   * @param            $groupId               The id of the group to check for child groups
   *
   * @return           boolean                True if 1 or more child groups are found, false otherwise.
   *
   * @access public
   */
  static function hasChildGroups($groupId) {
    $dao = new CRM_Contact_DAO_GroupNesting();
    $query = "SELECT child_group_id FROM civicrm_group_nesting WHERE parent_group_id = $groupId LIMIT 1";
    //print $query . "\n<br><br>";
    $dao->query($query);
    if ($dao->fetch()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Returns true if the given groupId has 1 or more parent groups,
   * false otherwise.
   *
   * @param            $groupId               The id of the group to check for parent groups
   *
   * @return           boolean                True if 1 or more parent groups are found, false otherwise.
   *
   * @access public
   */
  static function hasParentGroups($groupId) {
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
   * @param            $groupIds              Array of group ids (or one group id) to serve as the starting point
   * @param            $checkGroupId         The group id to check if it is a parent of the $groupIds group(s)
   *
   * @return           boolean                True if $checkGroupId points to a group that is a parent of one of the $groupIds groups, false otherwise.
   *
   * @access public
   */
  static function isParentGroup($groupIds, $checkGroupId) {
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
   * @param            $groupIds              Array of group ids (or one group id) to serve as the starting point
   * @param            $checkGroupId         The group id to check if it is a child of the $groupIds group(s)
   *
   * @return           boolean                True if $checkGroupId points to a group that is a child of one of the $groupIds groups, false otherwise.
   *
   * @access public
   */
  static function isChildGroup($groupIds, $checkGroupId) {

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
   * @param            $groupIds              Array of group ids (or one group id) to serve as the starting point
   * @param            $checkGroupId         The group id to check if it is an ancestor of the $groupIds group(s)
   *
   * @return           boolean                True if $checkGroupId points to a group that is an ancestor of one of the $groupIds groups, false otherwise.
   *
   * @access public
   */
  static function isAncestorGroup($groupIds, $checkGroupId) {
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
   * @param            $groupIds              Array of group ids (or one group id) to serve as the starting point
   * @param            $checkGroupId         The group id to check if it is a descendent of the $groupIds group(s)
   *
   * @return           boolean                True if $checkGroupId points to a group that is a descendent of one of the $groupIds groups, false otherwise.
   *
   * @access public
   */
  static function isDescendentGroup($groupIds, $checkGroupId) {
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
   * @param             $groupIds             An array of valid group ids (passed by reference)
   *
   * @param bool $includeSelf
   *
   * @return array $groupIdArray         List of groupIds that represent the requested group and its ancestors@access public
   */
  static function getAncestorGroupIds($groupIds, $includeSelf = TRUE) {
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
   * Returns array of ancestor groups of the specified group.
   *
   * @param             $groupIds     An array of valid group ids (passed by reference)
   *
   * @param bool $includeSelf
   * @return \An $groupArray   List of ancestor groups@access public
   */
  static function getAncestorGroups($groupIds, $includeSelf = TRUE) {
    $groupIds = self::getAncestorGroupIds($groupIds, $includeSelf);
    $params['id'] = $groupIds;
    return CRM_Contact_BAO_Group::getGroups($params);
  }

  /**
   * Returns array of group ids of child groups of the specified group.
   *
   * @param             $groupIds     An array of valid group ids (passed by reference)
   *
   * @return array $groupIdArray List of groupIds that represent the requested group and its children@access public
   */
  static function getChildGroupIds($groupIds) {
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
   * @param             $groupIds               An array of valid group ids (passed by reference)
   *
   * @return array $groupIdArray         List of groupIds that represent the requested group and its parents@access public
   */
  static function getParentGroupIds($groupIds) {
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
   * @param             $groupIds               An array of valid group ids (passed by reference)
   *
   * @param bool $includeSelf
   * @return array $groupIdArray         List of groupIds that represent the requested group and its descendents@access public
   */
  static function getDescendentGroupIds($groupIds, $includeSelf = TRUE) {
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
   * @param             $groupIds     An array of valid group ids (passed by reference)
   *
   * @param bool $includeSelf
   * @return \An $groupArray   List of descendent groups@access public
   */
  static function getDescendentGroups($groupIds, $includeSelf = TRUE) {
    $groupIds = self::getDescendentGroupIds($groupIds, $includeSelf);
    $params['id'] = $groupIds;
    return CRM_Contact_BAO_Group::getGroups($params);
  }

  /**
   * Returns array of group ids of valid potential child groups of the specified group.
   *
   * @param             $groupId              The group id to get valid potential children for
   *
   * @return array $groupIdArray         List of groupIds that represent the valid potential children of the group@access public
   */
  static function getPotentialChildGroupIds($groupId) {
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

  /**
   * @param $contactId
   * @param $parentGroupId
   *
   * @return array
   */
  static function getContainingGroups($contactId, $parentGroupId) {
    $groups = CRM_Contact_BAO_Group::getGroups();
    $containingGroups = array();
    foreach ($groups as $group) {
      if (self::isDescendentGroup($parentGroupId, $group->id)) {
        $members = CRM_Contact_BAO_Group::getMember($group->id);
        if ($members[$contactId]) {
          $containingGroups[] = $group->title;
        }
      }
    }

    return $containingGroups;
  }
}

