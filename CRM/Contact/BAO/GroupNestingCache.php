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
class CRM_Contact_BAO_GroupNestingCache {

  /**
   * Update cache.
   *
   * @throws \CRM_Core_Exception
   */
  public static function update() {
    // lets build the tree in memory first

    $sql = '
SELECT n.child_group_id  as child ,
       n.parent_group_id as parent
FROM   civicrm_group_nesting n,
       civicrm_group gc,
       civicrm_group gp
WHERE  n.child_group_id  = gc.id
  AND  n.parent_group_id = gp.id
';

    $dao = CRM_Core_DAO::executeQuery($sql);

    $tree = [];
    while ($dao->fetch()) {
      if (!array_key_exists($dao->child, $tree)) {
        $tree[$dao->child] = [
          'children' => [],
          'parents' => [],
        ];
      }

      if (!array_key_exists($dao->parent, $tree)) {
        $tree[$dao->parent] = [
          'children' => [],
          'parents' => [],
        ];
      }

      $tree[$dao->child]['parents'][] = $dao->parent;
      $tree[$dao->parent]['children'][] = $dao->child;
    }

    if (self::checkCyclicGraph($tree)) {
      throw new CRM_Core_Exception(ts('We detected a cycle which we can\'t handle. aborting'));
    }

    // first reset the current cache entries
    $sql = '
UPDATE civicrm_group
SET    parents  = null,
       children = null
';
    CRM_Core_DAO::executeQuery($sql);

    $values = [];
    foreach (array_keys($tree) as $id) {
      $parents = implode(',', $tree[$id]['parents']);
      $children = implode(',', $tree[$id]['children']);
      $parents = $parents == NULL ? 'null' : "'$parents'";
      $children = $children == NULL ? 'null' : "'$children'";
      $sql = "
UPDATE civicrm_group
SET    parents  = $parents ,
       children = $children
WHERE  id = $id
";
      CRM_Core_DAO::executeQuery($sql);
    }

    // this tree stuff is quite useful, so lets store it in the cache
    Civi::cache('groups')->set('nestable tree hierarchy', $tree);
  }

  /**
   * @param $tree
   *
   * @return bool
   */
  public static function checkCyclicGraph(&$tree) {
    // lets keep this simple, we should probably use a graph algorithm here at some stage

    // foreach group that has a parent or a child, ensure that
    // the ancestors and descendants dont intersect
    foreach ($tree as $id => $dontCare) {
      if (self::isCyclic($tree, $id)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * @param $tree
   * @param int $id
   *
   * @return bool
   */
  public static function isCyclic(&$tree, $id) {
    $parents = $children = [];
    self::getAll($parent, $tree, $id, 'parents');
    self::getAll($child, $tree, $id, 'children');

    $one = array_intersect($parents, $children);
    $two = array_intersect($children, $parents);
    if (!empty($one) ||
      !empty($two)
    ) {
      CRM_Core_Error::debug($id, $tree);
      CRM_Core_Error::debug($id, $one);
      CRM_Core_Error::debug($id, $two);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param int $id
   * @param array $groups
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function getPotentialCandidates($id, &$groups) {
    $tree = Civi::cache('groups')->get('nestable tree hierarchy');

    if ($tree === NULL) {
      self::update();
      $tree = Civi::cache('groups')->get('nestable tree hierarchy');
    }

    $potential = $groups;

    // remove all descendants
    self::invalidate($potential, $tree, $id, 'children');

    // remove all ancestors
    self::invalidate($potential, $tree, $id, 'parents');

    return array_keys($potential);
  }

  /**
   * @param $potential
   * @param $tree
   * @param int $id
   * @param $token
   */
  public static function invalidate(&$potential, &$tree, $id, $token) {
    unset($potential[$id]);

    if (!isset($tree[$id]) ||
      empty($tree[$id][$token])
    ) {
      return;
    }

    foreach ($tree[$id][$token] as $tokenID) {
      self::invalidate($potential, $tree, $tokenID, $token);
    }
  }

  /**
   * @param $all
   * @param $tree
   * @param int $id
   * @param $token
   */
  public static function getAll(&$all, &$tree, $id, $token) {
    // if seen before, dont do anything
    if (isset($all[$id])) {
      return;
    }

    $all[$id] = 1;
    if (!isset($tree[$id]) ||
      empty($tree[$id][$token])
    ) {
      return;
    }

    foreach ($tree[$id][$token] as $tokenID) {
      self::getAll($all, $tree, $tokenID, $token);
    }
  }

  /**
   * @return string
   *
   * @throws \CRM_Core_Exception
   */
  public static function json() {
    $tree = Civi::cache('groups')->get('nestable tree hierarchy');

    if ($tree === NULL) {
      self::update();
      $tree = Civi::cache('groups')->get('nestable tree hierarchy');
    }

    // get all the groups
    $groups = CRM_Core_PseudoConstant::group();

    foreach ($groups as $id => $name) {
      $string = "id:'$id', name:'$name'";
      if (isset($tree[$id])) {
        $children = [];
        if (!empty($tree[$id]['children'])) {
          foreach ($tree[$id]['children'] as $child) {
            $children[] = "{_reference:'$child'}";
          }
          $children = implode(',', $children);
          $string .= ", children:[$children]";
          if (empty($tree[$id]['parents'])) {
            $string .= ", type:'rootGroup'";
          }
          else {
            $string .= ", type:'middleGroup'";
          }
        }
        else {
          $string .= ", type:'leafGroup'";
        }
      }
      else {
        $string .= ", children:[], type:'rootGroup'";
      }
      $values[] = "{ $string }";
    }

    $items = implode(",\n", $values);
    $json = "{
  identifier:'id',
  label:'name',
  items:[ $items ]
}";
    return $json;
  }

}
