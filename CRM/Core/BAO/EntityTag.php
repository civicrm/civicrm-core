<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * This class contains functions for managing Tag(tag) for a contact
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Core_BAO_EntityTag extends CRM_Core_DAO_EntityTag {

  /**
   *
   * Given a contact id, it returns an array of tag id's the
   * contact belongs to.
   *
   * @param int $entityID
   *   Id of the entity usually the contactID.
   * @param string $entityTable
   *   Name of the entity table usually 'civicrm_contact'.
   *
   * @return array
   *   reference $tag array of category id's the contact belongs to.
   *
   */
  public static function &getTag($entityID, $entityTable = 'civicrm_contact') {
    $tags = array();

    $entityTag = new CRM_Core_BAO_EntityTag();
    $entityTag->entity_id = $entityID;
    $entityTag->entity_table = $entityTable;
    $entityTag->find();

    while ($entityTag->fetch()) {
      $tags[$entityTag->tag_id] = $entityTag->tag_id;
    }
    return $tags;
  }

  /**
   * Takes an associative array and creates a entityTag object.
   *
   * the function extract all the params it needs to initialize the create a
   * group object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return CRM_Core_BAO_EntityTag
   */
  public static function add(&$params) {
    $dataExists = self::dataExists($params);
    if (!$dataExists) {
      return NULL;
    }

    $entityTag = new CRM_Core_BAO_EntityTag();
    $entityTag->copyValues($params);

    // dont save the object if it already exists, CRM-1276
    if (!$entityTag->find(TRUE)) {
      $entityTag->save();

      //invoke post hook on entityTag
      // we are using this format to keep things consistent between the single and bulk operations
      // so a bit different from other post hooks
      $object = array(0 => array(0 => $params['entity_id']), 1 => $params['entity_table']);
      CRM_Utils_Hook::post('create', 'EntityTag', $params['tag_id'], $object);
    }
    return $entityTag;
  }

  /**
   * Check if there is data to create the object.
   *
   * @param array $params
   *   An assoc array of name/value pairs.
   *
   * @return bool
   */
  public static function dataExists($params) {
    return !($params['tag_id'] == 0);
  }

  /**
   * Delete the tag for a contact.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   */
  public static function del(&$params) {
    $entityTag = new CRM_Core_BAO_EntityTag();
    $entityTag->copyValues($params);
    $entityTag->delete();

    //invoke post hook on entityTag
    $object = array(0 => array(0 => $params['entity_id']), 1 => $params['entity_table']);
    CRM_Utils_Hook::post('delete', 'EntityTag', $params['tag_id'], $object);
  }

  /**
   * Given an array of entity ids and entity table, add all the entity to the tags
   *
   * @param array $entityIds
   *   (reference ) the array of entity ids to be added.
   * @param int $tagId
   *   The id of the tag.
   * @param string $entityTable
   *   Name of entity table default:civicrm_contact.
   *
   * @return array
   *   (total, added, notAdded) count of enities added to tag
   */
  public static function addEntitiesToTag(&$entityIds, $tagId, $entityTable = 'civicrm_contact') {
    $numEntitiesAdded = 0;
    $numEntitiesNotAdded = 0;
    $entityIdsAdded = array();

    foreach ($entityIds as $entityId) {
      $tag = new CRM_Core_DAO_EntityTag();

      $tag->entity_id = $entityId;
      $tag->tag_id = $tagId;
      $tag->entity_table = $entityTable;
      if (!$tag->find()) {
        $tag->save();
        $entityIdsAdded[] = $entityId;
        $numEntitiesAdded++;
      }
      else {
        $numEntitiesNotAdded++;
      }
    }

    //invoke post hook on entityTag
    $object = array($entityIdsAdded, $entityTable);
    CRM_Utils_Hook::post('create', 'EntityTag', $tagId, $object);

    // reset the group contact cache for all groups
    // if tags are being used in a smart group
    CRM_Contact_BAO_GroupContactCache::remove();

    return array(count($entityIds), $numEntitiesAdded, $numEntitiesNotAdded);
  }

  /**
   * Given an array of entity ids and entity table, remove entity(s) tags
   *
   * @param array $entityIds
   *   (reference ) the array of entity ids to be removed.
   * @param int $tagId
   *   The id of the tag.
   * @param string $entityTable
   *   Name of entity table default:civicrm_contact.
   *
   * @return array
   *   (total, removed, notRemoved) count of entities removed from tags
   */
  public static function removeEntitiesFromTag(&$entityIds, $tagId, $entityTable = 'civicrm_contact') {
    $numEntitiesRemoved = 0;
    $numEntitiesNotRemoved = 0;
    $entityIdsRemoved = array();

    foreach ($entityIds as $entityId) {
      $tag = new CRM_Core_DAO_EntityTag();

      $tag->entity_id = $entityId;
      $tag->tag_id = $tagId;
      $tag->entity_table = $entityTable;
      if ($tag->find()) {
        $tag->delete();
        $entityIdsRemoved[] = $entityId;
        $numEntitiesRemoved++;
      }
      else {
        $numEntitiesNotRemoved++;
      }
    }

    //invoke post hook on entityTag
    $object = array($entityIdsRemoved, $entityTable);
    CRM_Utils_Hook::post('delete', 'EntityTag', $tagId, $object);

    // reset the group contact cache for all groups
    // if tags are being used in a smart group
    CRM_Contact_BAO_GroupContactCache::remove();

    return array(count($entityIds), $numEntitiesRemoved, $numEntitiesNotRemoved);
  }

  /**
   * Takes an associative array and creates tag entity record for all tag entities.
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   * @param $entityTable
   * @param int $entityID
   *
   * @return void
   */
  public static function create(&$params, $entityTable, $entityID) {
    // get categories for the entity id
    $entityTag = CRM_Core_BAO_EntityTag::getTag($entityID, $entityTable);

    // get the list of all the categories
    $allTag = CRM_Core_BAO_Tag::getTags($entityTable);

    // this fix is done to prevent warning generated by array_key_exits incase of empty array is given as input
    if (!is_array($params)) {
      $params = array();
    }

    // this fix is done to prevent warning generated by array_key_exits incase of empty array is given as input
    if (!is_array($entityTag)) {
      $entityTag = array();
    }

    // check which values has to be inserted/deleted for contact
    foreach ($allTag as $key => $varValue) {
      $tagParams['entity_table'] = $entityTable;
      $tagParams['entity_id'] = $entityID;
      $tagParams['tag_id'] = $key;

      if (array_key_exists($key, $params) && !array_key_exists($key, $entityTag)) {
        // insert a new record
        CRM_Core_BAO_EntityTag::add($tagParams);
      }
      elseif (!array_key_exists($key, $params) && array_key_exists($key, $entityTag)) {
        // delete a record for existing contact
        CRM_Core_BAO_EntityTag::del($tagParams);
      }
    }
  }

  /**
   * This function returns all entities assigned to a specific tag.
   *
   * @param object $tag
   *   An object of a tag.
   *
   * @return array
   *   array of entity ids
   */
  public function getEntitiesByTag($tag) {
    $entityIds = array();
    $entityTagDAO = new CRM_Core_DAO_EntityTag();
    $entityTagDAO->tag_id = $tag->id;
    $entityTagDAO->find();
    while ($entityTagDAO->fetch()) {
      $entityIds[] = $entityTagDAO->entity_id;
    }
    return $entityIds;
  }

  /**
   * Get contact tags.
   */
  public static function getContactTags($contactID, $count = FALSE) {
    $contactTags = array();
    if (!$count) {
      $select = "SELECT name ";
    }
    else {
      $select = "SELECT count(*) as cnt";
    }

    $query = "{$select}
        FROM civicrm_tag ct
        INNER JOIN civicrm_entity_tag et ON ( ct.id = et.tag_id AND
            et.entity_id    = {$contactID} AND
            et.entity_table = 'civicrm_contact' AND
            ct.is_tagset = 0 )";

    $dao = CRM_Core_DAO::executeQuery($query);

    if ($count) {
      $dao->fetch();
      return $dao->cnt;
    }

    while ($dao->fetch()) {
      $contactTags[] = $dao->name;
    }

    return $contactTags;
  }

  /**
   * Get child contact tags given parentId.
   */
  public static function getChildEntityTags($parentId, $entityId, $entityTable = 'civicrm_contact') {
    $entityTags = array();
    $query = "SELECT ct.id as tag_id, name FROM civicrm_tag ct
                    INNER JOIN civicrm_entity_tag et ON ( et.entity_id = {$entityId} AND
                     et.entity_table = '{$entityTable}' AND  et.tag_id = ct.id)
                  WHERE ct.parent_id = {$parentId}";

    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      $entityTags[$dao->tag_id] = array(
        'id' => $dao->tag_id,
        'name' => $dao->name,
      );
    }

    return $entityTags;
  }

  /**
   * Merge two tags: tag B into tag A.
   */
  public function mergeTags($tagAId, $tagBId) {
    $queryParams = array(
      1 => array($tagBId, 'Integer'),
      2 => array($tagAId, 'Integer'),
    );

    // re-compute used_for field
    $query = "SELECT id, name, used_for FROM civicrm_tag WHERE id IN (%1, %2)";
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    $tags = array();
    while ($dao->fetch()) {
      $label = ($dao->id == $tagAId) ? 'tagA' : 'tagB';
      $tags[$label] = $dao->name;
      $tags["{$label}_used_for"] = $dao->used_for ? explode(",", $dao->used_for) : array();
    }
    $usedFor = array_merge($tags["tagA_used_for"], $tags["tagB_used_for"]);
    $usedFor = implode(',', array_unique($usedFor));
    $tags["tagB_used_for"] = explode(",", $usedFor);

    // get all merge queries together
    $sqls = array(
      // 1. update entity tag entries
      "UPDATE IGNORE civicrm_entity_tag SET tag_id = %1 WHERE tag_id = %2",
      // 2. update used_for info for tag B
      "UPDATE civicrm_tag SET used_for = '{$usedFor}' WHERE id = %1",
      // 3. remove tag A, if tag A is getting merged into B
      "DELETE FROM civicrm_tag WHERE id = %2",
      // 4. remove duplicate entity tag records
      "DELETE et2.* from civicrm_entity_tag et1 INNER JOIN civicrm_entity_tag et2 ON et1.entity_table = et2.entity_table AND et1.entity_id = et2.entity_id AND et1.tag_id = et2.tag_id WHERE et1.id < et2.id",
      // 5. remove orphaned entity_tags
      "DELETE FROM civicrm_entity_tag WHERE tag_id = %2",
    );
    $tables = array('civicrm_entity_tag', 'civicrm_tag');

    // Allow hook_civicrm_merge() to add SQL statements for the merge operation AND / OR
    // perform any other actions like logging
    CRM_Utils_Hook::merge('sqls', $sqls, $tagAId, $tagBId, $tables);

    // call the SQL queries in one transaction
    $transaction = new CRM_Core_Transaction();
    foreach ($sqls as $sql) {
      CRM_Core_DAO::executeQuery($sql, $queryParams, TRUE, NULL, TRUE);
    }
    $transaction->commit();

    $tags['status'] = TRUE;
    return $tags;
  }

  /**
   * Get options for a given field.
   *
   * @see CRM_Core_DAO::buildOptions
   * @see CRM_Core_DAO::buildOptionsContext
   *
   * @param string $fieldName
   * @param string $context
   *   As per CRM_Core_DAO::buildOptionsContext.
   * @param array $props
   *   whatever is known about this dao object.
   *
   * @return array|bool
   */
  public static function buildOptions($fieldName, $context = NULL, $props = array()) {
    $params = array();

    if ($fieldName == 'tag' || $fieldName == 'tag_id') {
      if (!empty($props['entity_table'])) {
        $entity = CRM_Utils_Type::escape($props['entity_table'], 'String');
        $params[] = "used_for LIKE '%$entity%'";
      }

      // Output tag list as nested hierarchy
      // TODO: This will only work when api.entity is "entity_tag". What about others?
      if ($context == 'search' || $context == 'create') {
        return CRM_Core_BAO_Tag::getTags(CRM_Utils_Array::value('entity_table', $props, 'civicrm_contact'), CRM_Core_DAO::$_nullArray, CRM_Utils_Array::value('parent_id', $params), '- ');
      }
    }

    $options = CRM_Core_PseudoConstant::get(__CLASS__, $fieldName, $params, $context);

    return $options;
  }

}
