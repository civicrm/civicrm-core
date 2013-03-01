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
class CRM_Core_BAO_Tag extends CRM_Core_DAO_Tag {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params      (reference ) an assoc array of name/value pairs
   * @param array $defaults    (reference ) an assoc array to hold the flattened values
   *
   * @return object     CRM_Core_DAO_Tag object on success, otherwise null
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $tag = new CRM_Core_DAO_Tag();
    $tag->copyValues($params);
    if ($tag->find(TRUE)) {
      CRM_Core_DAO::storeValues($tag, $defaults);
      return $tag;
    }
    return NULL;
  }

  function getTree($usedFor = NULL, $excludeHidden = FALSE) {
    if (!isset($this->tree)) {
      $this->buildTree($usedFor, $excludeHidden);
    }
    return $this->tree;
  }

  function buildTree($usedFor = NULL, $excludeHidden = FALSE) {
    $sql = "SELECT civicrm_tag.id, civicrm_tag.parent_id,civicrm_tag.name FROM civicrm_tag ";

    $whereClause = array();
    if ($usedFor) {
      $whereClause[] = "used_for like '%{$usedFor}%'";
    }
    if ($excludeHidden) {
      $whereClause[] = "is_tagset = 0";
    }

    if (!empty($whereClause)) {
      $sql .= " WHERE " . implode(' AND ', $whereClause);
    }

    $sql .= " ORDER BY parent_id,name";

    $dao = CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray, TRUE, NULL, FALSE, FALSE);

    $orphan = array();
    while ($dao->fetch()) {
      if (!$dao->parent_id) {
        $this->tree[$dao->id]['name'] = $dao->name;
      }
      else {
        if (array_key_exists($dao->parent_id, $this->tree)) {
          $parent = &$this->tree[$dao->parent_id];
          if (!isset($this->tree[$dao->parent_id]['children'])) {
            $this->tree[$dao->parent_id]['children'] = array();
          }
        }
        else {
          //3rd level tag
          if (!array_key_exists($dao->parent_id, $orphan)) {
            $orphan[$dao->parent_id] = array('children' => array());
          }
          $parent = &$orphan[$dao->parent_id];
        }
        $parent['children'][$dao->id] = array('name' => $dao->name);
      }
    }
    if (sizeof($orphan)) {
      //hang the 3rd level lists at the right place
      foreach ($this->tree as & $level1) {
        if (!isset($level1['children'])) {
          continue;
        }

        foreach ($level1['children'] as $key => & $level2) {
          if (array_key_exists($key, $orphan)) {
            $level2['children'] = $orphan[$key]['children'];
          }
        }
      }
    }
  }

  static function getTagsUsedFor($usedFor = array('civicrm_contact'),
    $buildSelect = TRUE,
    $all         = FALSE,
    $parentId    = NULL
  ) {
    $tags = array();

    if (empty($usedFor)) {
      return $tags;
    }
    if (!is_array($usedFor)) {
      $usedFor = array($usedFor);
    }

    if ($parentId === NULL) {
      $parentClause = " parent_id IS NULL AND ";
    }
    else {
      $parentClause = " parent_id = {$parentId} AND ";
    }

    foreach ($usedFor as $entityTable) {
      $tag = new CRM_Core_DAO_Tag();
      $tag->fields();
      $tag->orderBy('parent_id');
      if ($buildSelect) {
        $tag->whereAdd("is_tagset = 0 AND {$parentClause} used_for LIKE '%{$entityTable}%'");
      }
      else {
        $tag->whereAdd("used_for LIKE '%{$entityTable}%'");
      }
      if (!$all) {
        $tag->is_tagset = 0;
      }
      $tag->find();

      while ($tag->fetch()) {
        if ($buildSelect) {
          $tags[$tag->id] = $tag->name;
        }
        else {
          $tags[$tag->id]['name'] = $tag->name;
          $tags[$tag->id]['parent_id'] = $tag->parent_id;
          $tags[$tag->id]['is_tagset'] = $tag->is_tagset;
          $tags[$tag->id]['used_for'] = $tag->used_for;
        }
      }
      $tag->free();
    }

    return $tags;
  }

  static function getTags($usedFor = 'civicrm_contact',
    &$tags = array(),
    $parentId  = NULL,
    $separator = '&nbsp;&nbsp;'
  ) {
    // We need to build a list of tags ordered by hierarchy and sorted by
    // name. The heirarchy will be communicated by an accumulation of
    // separators in front of the name to give it a visual offset.
    // Instead of recursively making mysql queries, we'll make one big
    // query and build the heirarchy with the algorithm below.
    $args = array(1 => array('%' . $usedFor . '%', 'String'));
    $query = "SELECT id, name, parent_id, is_tagset
                  FROM civicrm_tag
              WHERE used_for LIKE %1";
    if ($parentId) {
      $query .= " AND parent_id = %2";
      $args[2] = array($parentId, 'Integer');
    }
    $query .= " ORDER BY name";
    $dao = CRM_Core_DAO::executeQuery($query, $args, TRUE, NULL, FALSE, FALSE);

    // Sort the tags into the correct storage by the parent_id/is_tagset
    // filter the filter was in place previously, we're just reusing it.
    // $roots represents the current leaf nodes that need to be checked for
    // children. $rows represents the unplaced nodes, not all of much
    // are necessarily placed.
    $roots = $rows = array();
    while ($dao->fetch()) {
      if ($dao->parent_id == $parentId && $dao->is_tagset == 0) {
        $roots[] = array('id' => $dao->id, 'prefix' => '', 'name' => $dao->name);
      }
      else {
        $rows[] = array('id' => $dao->id, 'prefix' => '', 'name' => $dao->name, 'parent_id' => $dao->parent_id);
      }
    }
    $dao->free();
    // While we have nodes left to build, shift the first (alphabetically)
    // node of the list, place it in our tags list and loop through the
    // list of unplaced nodes to find its children. We make a copy to
    // iterate through because we must modify the unplaced nodes list
    // during the loop.
    while (count($roots)) {
      $new_roots         = array();
      $current_rows      = $rows;
      $root              = array_shift($roots);
      $tags[$root['id']] = array($root['prefix'], $root['name']);

      // As you find the children, append them to the end of the new set
      // of roots (maintain alphabetical ordering). Also remove the node
      // from the set of unplaced nodes.
      if (is_array($current_rows)) {
        foreach ($current_rows as $key => $row) {
          if ($row['parent_id'] == $root['id']) {
            $new_roots[] = array('id' => $row['id'], 'prefix' => $tags[$root['id']][0] . $separator, 'name' => $row['name']);
            unset($rows[$key]);
          }
        }
      }

      //As a group, insert the new roots into the beginning of the roots
      //list. This maintains the hierarchical ordering of the tags.
      $roots = array_merge($new_roots, $roots);
    }

    // Prefix each name with the calcuated spacing to give the visual
    // appearance of ordering when transformed into HTML in the form layer.
    foreach ($tags as & $tag) {
      $tag = $tag[0] . $tag[1];
    }

    return $tags;
  }

  /**
   * Function to delete the tag
   *
   * @param int $id   tag id
   *
   * @return boolean
   * @access public
   * @static
   *
   */
  static function del($id) {
    // since this is a destructive operation, lets make sure
    // id is a postive number
    CRM_Utils_Type::validate($id, 'Positive');

    // delete all crm_entity_tag records with the selected tag id
    $entityTag = new CRM_Core_DAO_EntityTag();
    $entityTag->tag_id = $id;
    $entityTag->delete();

    // delete from tag table
    $tag = new CRM_Core_DAO_Tag();
    $tag->id = $id;

    CRM_Utils_Hook::pre('delete', 'Tag', $id, $tag);

    if ($tag->delete()) {
      CRM_Utils_Hook::post('delete', 'Tag', $id, $tag);
      CRM_Core_Session::setStatus(ts('Selected tag has been deleted successfuly.'), ts('Tag Deleted'), 'success');
      return TRUE;
    }
    return FALSE;
  }

  /**
   * takes an associative array and creates a contact object
   *
   * The function extract all the params it needs to initialize the create a
   * contact object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array  $params         (reference) an assoc array of name/value pairs
   * @param array  $ids            (reference) the array that holds all the db ids
   *
   * @return object    CRM_Core_DAO_Tag object on success, otherwise null
   * @access public
   * @static
   */
  static function add(&$params, &$ids) {
    if (!self::dataExists($params)) {
      return NULL;
    }

    $tag = new CRM_Core_DAO_Tag();

    // if parent id is set then inherit used for and is hidden properties
    if (CRM_Utils_Array::value('parent_id', $params)) {
      // get parent details
      $params['used_for'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $params['parent_id'], 'used_for');
    }

    $tag->copyValues($params);
    $tag->id = CRM_Utils_Array::value('tag', $ids);

    $edit = ($tag->id) ? TRUE : FALSE;
    if ($edit) {
      CRM_Utils_Hook::pre('edit', 'Tag', $tag->id, $tag);
    }
    else {
      CRM_Utils_Hook::pre('create', 'Tag', NULL, $tag);
    }

    // save creator id and time
    if (!$tag->id) {
      $session           = CRM_Core_Session::singleton();
      $tag->created_id   = $session->get('userID');
      $tag->created_date = date('YmdHis');
    }

    $tag->save();

    if ($edit) {
      CRM_Utils_Hook::post('edit', 'Tag', $tag->id, $tag);
    }
    else {
      CRM_Utils_Hook::post('create', 'Tag', NULL, $tag);
    }

    // if we modify parent tag, then we need to update all children
    if ($tag->parent_id === 'null') {
      CRM_Core_DAO::executeQuery("UPDATE civicrm_tag SET used_for=%1 WHERE parent_id = %2",
        array(1 => array($params['used_for'], 'String'),
          2 => array($tag->id, 'Integer'),
        )
      );
    }

    return $tag;
  }

  /**
   * Check if there is data to create the object
   *
   * @param array  $params         (reference ) an assoc array of name/value pairs
   *
   * @return boolean
   * @access public
   * @static
   */
  static function dataExists(&$params) {
    if (!empty($params['name'])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Function to get the tag sets for a entity object
   *
   * @param string $entityTable entity_table
   *
   * @return array $tagSets array of tag sets
   * @access public
   * @static
   */
  static function getTagSet($entityTable) {
    $tagSets = array();
    $query = "SELECT name, id FROM civicrm_tag
              WHERE is_tagset=1 AND parent_id IS NULL and used_for LIKE %1";
    $dao = CRM_Core_DAO::executeQuery($query, array(1 => array('%' . $entityTable . '%', 'String')), TRUE, NULL, FALSE, FALSE);
    while ($dao->fetch()) {
      $tagSets[$dao->id] = $dao->name;
    }
    $dao->free();
    return $tagSets;
  }

  /**
   * Function to get the tags that are not children of a tagset.
   *
   * @return $tags associated array of tag name and id
   * @access public
   * @static
   */
  static function getTagsNotInTagset() {
    $tags = $tagSets = array();
    // first get all the tag sets
    $query = "SELECT id FROM civicrm_tag WHERE is_tagset=1 AND parent_id IS NULL";
    $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    while ($dao->fetch()) {
      $tagSets[] = $dao->id;
    }

    $parentClause = '';
    if (!empty($tagSets)) {
      $parentClause = ' WHERE ( parent_id IS NULL ) OR ( parent_id NOT IN ( ' . implode(',', $tagSets) . ' ) )';
    }

    // get that tags that don't have tagset as parent
    $query = "SELECT id, name FROM civicrm_tag {$parentClause}";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $tags[$dao->id] = $dao->name;
    }

    return $tags;
  }
}

