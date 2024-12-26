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
class CRM_Core_BAO_Tag extends CRM_Core_DAO_Tag {

  /**
   * @var array
   */
  protected $tree;

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    CRM_Core_Error::deprecatedFunctionWarning('API');
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * Get tag tree.
   *
   * @param string $usedFor
   * @param bool $excludeHidden
   *
   * @return mixed
   */
  public function getTree($usedFor = NULL, $excludeHidden = FALSE) {
    if (!isset($this->tree)) {
      $this->buildTree($usedFor, $excludeHidden);
    }
    return $this->tree;
  }

  /**
   * Build a nested array from hierarchical tags.
   *
   * Supports infinite levels of nesting.
   *
   * @param string|null $usedFor
   * @param bool $excludeHidden
   */
  public function buildTree($usedFor = NULL, $excludeHidden = FALSE) {
    $sql = "SELECT id, parent_id, name, label, description, is_selectable FROM civicrm_tag";

    $whereClause = [];
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

    $dao = CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, FALSE, FALSE);

    $refs = [];
    $this->tree = [];
    while ($dao->fetch()) {
      $thisref = &$refs[$dao->id];

      $thisref['parent_id'] = $dao->parent_id;
      $thisref['name'] = $dao->name;
      $thisref['label'] = $dao->label;
      $thisref['description'] = $dao->description;
      $thisref['is_selectable'] = $dao->is_selectable;
      if (!isset($thisref['children'])) {
        $thisref['children'] = [];
      }
      if (!$dao->parent_id) {
        $this->tree[$dao->id] = &$thisref;
      }
      else {
        if (!isset($refs[$dao->parent_id])) {
          $refs[$dao->parent_id] = [
            'children' => [],
          ];
        }
        $refs[$dao->parent_id]['children'][$dao->id] = &$thisref;
      }
    }
  }

  /**
   * Get tags used for the given entity/entities.
   *
   * @param array $usedFor
   * @param bool $buildSelect
   * @param bool $all
   * @param int $parentId
   *
   * @return array
   */
  public static function getTagsUsedFor(
    $usedFor = ['civicrm_contact'],
    $buildSelect = TRUE,
    $all = FALSE,
    $parentId = NULL
  ) {
    $tags = [];

    if (empty($usedFor)) {
      return $tags;
    }
    if (!is_array($usedFor)) {
      $usedFor = [$usedFor];
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
          $tags[$tag->id]['description'] = $tag->description;
          $tags[$tag->id]['color'] = !empty($tag->color) ? $tag->color : NULL;
        }
      }
    }

    return $tags;
  }

  /**
   * Function to retrieve tags.
   *
   * @param string $usedFor
   *   Which type of tag entity.
   * @param array $tags
   *   Tags array.
   * @param int $parentId
   *   Parent id if you want need only children.
   * @param string $separator
   *   Separator to indicate children.
   * @param bool $formatSelectable
   *   Add special property for non-selectable.
   *                tag, so they cannot be selected
   *
   * @return array
   */
  public static function getTags(
    $usedFor = 'civicrm_contact',
    &$tags = [],
    $parentId = NULL,
    $separator = '&nbsp;&nbsp;',
    $formatSelectable = FALSE
  ) {
    if (!is_array($tags)) {
      $tags = [];
    }
    // We need to build a list of tags ordered by hierarchy and sorted by
    // name. The hierarchy will be communicated by an accumulation of
    // separators in front of the name to give it a visual offset.
    // Instead of recursively making mysql queries, we'll make one big
    // query and build the hierarchy with the algorithm below.
    $args = [1 => ['%' . $usedFor . '%', 'String']];
    $query = "SELECT id, name, label, parent_id, is_tagset, is_selectable
                  FROM civicrm_tag
              WHERE used_for LIKE %1";
    if ($parentId) {
      $query .= " AND parent_id = %2";
      $args[2] = [$parentId, 'Integer'];
    }
    $query .= " ORDER BY label";
    // @todo when it becomes localizable then we might need the i18nrewrite
    $dao = CRM_Core_DAO::executeQuery($query, $args, TRUE, NULL, FALSE, FALSE);

    // Sort the tags into the correct storage by the parent_id/is_tagset
    // filter the filter was in place previously, we're just reusing it.
    // $roots represents the current leaf nodes that need to be checked for
    // children. $rows represents the unplaced nodes, not all of much
    // are necessarily placed.
    $roots = $rows = [];
    while ($dao->fetch()) {
      // note that we are prepending id with "crm_disabled_opt" which identifies
      // them as disabled so that they cannot be selected. We do some magic
      // in crm-select2 js function that marks option values to "disabled"
      // current QF version in CiviCRM does not support passing this attribute,
      // so this is another ugly hack / workaround,
      // also know one is too keen to upgrade QF :P
      $idPrefix = '';
      if ($formatSelectable && !$dao->is_selectable) {
        $idPrefix = "crm_disabled_opt";
      }
      if ($dao->parent_id == $parentId && $dao->is_tagset == 0) {
        $roots[] = [
          'id' => $dao->id,
          'prefix' => '',
          'name' => $dao->name,
          'idPrefix' => $idPrefix,
          'label' => $dao->label,
        ];
      }
      else {
        $rows[] = [
          'id' => $dao->id,
          'prefix' => '',
          'name' => $dao->name,
          'parent_id' => $dao->parent_id,
          'idPrefix' => $idPrefix,
          'label' => $dao->label,
        ];
      }
    }

    // While we have nodes left to build, shift the first (alphabetically)
    // node of the list, place it in our tags list and loop through the
    // list of unplaced nodes to find its children. We make a copy to
    // iterate through because we must modify the unplaced nodes list
    // during the loop.
    while (count($roots)) {
      $new_roots = [];
      $current_rows = $rows;
      $root = array_shift($roots);
      $tags[$root['id']] = [
        $root['prefix'],
        $root['name'],
        $root['idPrefix'],
        $root['label'],
      ];

      // As you find the children, append them to the end of the new set
      // of roots (maintain alphabetical ordering). Also remove the node
      // from the set of unplaced nodes.
      if (is_array($current_rows)) {
        foreach ($current_rows as $key => $row) {
          if ($row['parent_id'] == $root['id']) {
            $new_roots[] = [
              'id' => $row['id'],
              'prefix' => $tags[$root['id']][0] . $separator,
              'name' => $row['name'],
              'idPrefix' => $row['idPrefix'],
              'label' => $row['label'],
            ];
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
    // here is the actual code that to prepends and set disabled attribute for
    // non-selectable tags
    $formattedTags = [];
    foreach ($tags as $key => $tag) {
      if (!empty($tag[2])) {
        $key = $tag[2] . "-" . $key;
      }
      $formattedTags[$key] = $tag[0] . $tag[3];
    }

    $tags = $formattedTags;
    return $tags;
  }

  /**
   * @param string $usedFor
   * @param bool $allowSelectingNonSelectable
   * @param null $exclude
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function getColorTags($usedFor = NULL, $allowSelectingNonSelectable = FALSE, $exclude = NULL) {
    $params = [
      'options' => [
        'limit' => 0,
        'sort' => "name ASC",
      ],
      'is_tagset' => 0,
      'return' => ['label', 'description', 'parent_id', 'color', 'is_selectable', 'used_for'],
    ];
    if ($usedFor) {
      $params['used_for'] = ['LIKE' => "%$usedFor%"];
    }
    if ($exclude) {
      $params['id'] = ['!=' => $exclude];
    }
    $allTags = [];
    foreach (CRM_Utils_Array::value('values', civicrm_api3('Tag', 'get', $params)) as $id => $tag) {
      $allTags[$id] = [
        'text' => $tag['label'],
        'id' => $id,
        'description' => $tag['description'] ?? NULL,
        'parent_id' => $tag['parent_id'] ?? NULL,
        'used_for' => $tag['used_for'] ?? NULL,
        'color' => $tag['color'] ?? NULL,
      ];
      if (!$allowSelectingNonSelectable && empty($tag['is_selectable'])) {
        $allTags[$id]['disabled'] = TRUE;
      }
    }
    return CRM_Utils_Array::buildTree($allTags);
  }

  /**
   * Delete the tag.
   *
   * @param int $id
   * @deprecated
   * @return bool
   */
  public static function del($id) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    return (bool) static::deleteRecord(['id' => $id]);
  }

  /**
   * Takes an associative array and creates a tag object.
   *
   * The function extract all the params it needs to initialize the create a
   * contact object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   * @param array $ids
   *   (optional) the array that holds all the db ids - we are moving away from this in bao.
   * signatures
   *
   * @return CRM_Core_DAO_Tag|null
   *   object on success, otherwise null
   */
  public static function add(&$params, $ids = []) {
    $id = $params['id'] ?? $ids['tag'] ?? NULL;
    if (!$id) {
      // Make label from name if missing.
      if (CRM_Utils_System::isNull($params['label'] ?? NULL)) {
        // If name is also missing, cannot create object.
        if (CRM_Utils_System::isNull($params['name'] ?? NULL)) {
          // FIXME: Throw exception
          return NULL;
        }
        $params['label'] = $params['name'];
      }
    }

    // Check permission to create or modify reserved tag
    if (!empty($params['check_permissions']) && !CRM_Core_Permission::check('administer reserved tags')) {
      if (!empty($params['is_reserved']) || ($id && CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $id, 'is_reserved'))) {
        throw new CRM_Core_Exception('Insufficient permission to administer reserved tag.');
      }
    }

    // Check permission to create or modify tagset
    if (!empty($params['check_permissions']) && !CRM_Core_Permission::check('administer Tagsets')) {
      if (!empty($params['is_tagset']) || ($id && CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $id, 'is_tagset'))) {
        throw new CRM_Core_Exception('Insufficient permission to administer tagset.');
      }
    }

    // if parent id is set then inherit used for and is hidden properties
    if (!empty($params['parent_id'])) {
      // get parent details
      $params['used_for'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $params['parent_id'], 'used_for');
    }
    elseif (isset($params['used_for']) && is_array($params['used_for'])) {
      $params['used_for'] = implode(',', $params['used_for']);
    }

    // Hack to make white null, because html5 color widget can't be empty
    if (isset($params['color']) && strtolower($params['color']) === '#ffffff') {
      $params['color'] = '';
    }

    $tag = self::writeRecord($params);

    // if we modify parent tag, then we need to update all children
    if ($id) {
      $tag->find(TRUE);
      if (!$tag->parent_id && $tag->used_for) {
        CRM_Core_DAO::executeQuery("UPDATE civicrm_tag SET used_for=%1 WHERE parent_id = %2", [
          1 => [$tag->used_for, 'String'],
          2 => [$tag->id, 'Integer'],
        ]);
      }
    }

    CRM_Core_PseudoConstant::flush();
    Civi::cache('metadata')->clear();

    return $tag;
  }

  /**
   * Get the tag sets for a entity object.
   *
   * @param string $entityTable
   *   Entity_table.
   *
   * @return array
   *   array of tag sets
   */
  public static function getTagSet($entityTable) {
    $tagSets = [];
    $query = "SELECT label, id FROM civicrm_tag
              WHERE is_tagset=1 AND parent_id IS NULL and used_for LIKE %1";
    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [
        '%' . $entityTable . '%',
        'String',
      ],
    ], TRUE, NULL, FALSE, FALSE);
    while ($dao->fetch()) {
      $tagSets[$dao->id] = $dao->label;
    }
    return $tagSets;
  }

  /**
   * Get the tags that are not children of a tagset.
   *
   * @return array
   *   associated array of tag name and id
   */
  public static function getTagsNotInTagset() {
    $tags = $tagSets = [];
    // first get all the tag sets
    $query = "SELECT id FROM civicrm_tag WHERE is_tagset=1 AND parent_id IS NULL";
    $dao = CRM_Core_DAO::executeQuery($query);
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

  /**
   * Get child tags IDs
   *
   * @param string $searchString
   *
   * @return array
   *   associated array of child tags in Array('Parent Tag ID' => Array('Child Tag 1', ...)) format
   */
  public static function getChildTags($searchString = NULL) {
    $childTagIDs = [];

    $whereClauses = ['parent.is_tagset <> 1'];
    if ($searchString) {
      $whereClauses[] = " child.name LIKE '%$searchString%' ";
    }

    // only fetch those tags which has child tags
    $dao = CRM_Utils_SQL_Select::from('civicrm_tag parent')
      ->join('child', 'INNER JOIN civicrm_tag child ON child.parent_id = parent.id ')
      ->select('parent.id as parent_id, GROUP_CONCAT(child.id) as child_id')
      ->where($whereClauses)
      ->groupBy('parent.id')
      ->execute();
    while ($dao->fetch()) {
      $childTagIDs[$dao->parent_id] = (array) explode(',', $dao->child_id);
      $parentID = $dao->parent_id;
      if ($searchString) {
        // recursively search for parent tag ID and it's child if any
        while ($parentID) {
          $newParentID = CRM_Core_DAO::singleValueQuery(" SELECT parent_id FROM civicrm_tag WHERE id = $parentID ");
          if ($newParentID) {
            $childTagIDs[$newParentID] = [$parentID];
          }
          $parentID = $newParentID;
        }
      }
    }

    // check if child tag has any childs, if found then include those child tags inside parent tag
    //  i.e. format Array('parent_tag' => array('child_tag_1', ...), 'child_tag_1' => array(child_tag_1_1, ..), ..)
    //  to Array('parent_tag' => array('child_tag_1', 'child_tag_1_1'...), ..)
    foreach ($childTagIDs as $parentTagID => $childTags) {
      foreach ($childTags as $childTag) {
        // if $childTag has any child tag of its own
        if (array_key_exists($childTag, $childTagIDs)) {
          $childTagIDs[$parentTagID] = array_merge($childTagIDs[$parentTagID], $childTagIDs[$childTag]);
        }
      }
    }

    return $childTagIDs;
  }

}
