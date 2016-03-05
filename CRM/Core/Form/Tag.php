<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * This class generates form element for free tag widget.
 */
class CRM_Core_Form_Tag {
  public $_entityTagValues;

  /**
   * Build tag widget if correct parent is passed
   *
   * @param CRM_Core_Form $form
   *   Form object.
   * @param string $parentNames
   *   Parent name ( tag name).
   * @param string $entityTable
   *   Entitytable 'eg: civicrm_contact'.
   * @param int $entityId
   *   Entityid 'eg: contact id'.
   * @param bool $skipTagCreate
   *   True if tag need be created using ajax.
   * @param bool $skipEntityAction
   *   True if need to add entry in entry table via ajax.
   * @param string $tagsetElementName
   *   If you need to create tagsetlist with specific name.
   */
  public static function buildQuickForm(
    &$form, $parentNames, $entityTable, $entityId = NULL, $skipTagCreate = FALSE,
    $skipEntityAction = FALSE, $tagsetElementName = NULL) {
    $tagset = $form->_entityTagValues = array();
    $form->assign("isTagset", FALSE);
    $mode = NULL;

    foreach ($parentNames as &$parentNameItem) {
      // get the parent id for tag list input for keyword
      $parentId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $parentNameItem, 'id', 'name');

      // check if parent exists
      if ($parentId) {
        $tagsetItem = $tagsetElementName . 'parentId_' . $parentId;
        $tagset[$tagsetItem]['parentID'] = $parentId;

        list(, $mode) = explode('_', $entityTable);
        if (!$tagsetElementName) {
          $tagsetElementName = $mode . "_taglist";
        }
        $tagset[$tagsetItem]['tagsetElementName'] = $tagsetElementName;

        $form->addEntityRef("{$tagsetElementName}[{$parentId}]", $parentNameItem, array(
          'entity' => 'tag',
          'multiple' => TRUE,
          'create' => !$skipTagCreate,
          'api' => array('params' => array('parent_id' => $parentId)),
          'data-entity_table' => $entityTable,
          'data-entity_id' => $entityId,
          'class' => "crm-$mode-tagset",
        ));

        if ($entityId) {
          $tagset[$tagsetItem]['entityId'] = $entityId;
          $entityTags = CRM_Core_BAO_EntityTag::getChildEntityTags($parentId, $entityId, $entityTable);
          if ($entityTags) {
            $form->setDefaults(array("{$tagsetElementName}[{$parentId}]" => implode(',', array_keys($entityTags))));
          }
        }
        else {
          $skipEntityAction = TRUE;
        }
        $tagset[$tagsetItem]['skipEntityAction'] = $skipEntityAction;
      }
    }

    if (!empty($tagset)) {
      // assign current tagsets which is used in postProcess
      $form->_tagsetInfo = $tagset;
      $form->assign("tagsetType", $mode);
      // Merge this tagset info with possibly existing info in the template
      $tagsetInfo = (array) $form->get_template_vars("tagsetInfo");
      if (empty($tagsetInfo[$mode])) {
        $tagsetInfo[$mode] = array();
      }
      $tagsetInfo[$mode] = array_merge($tagsetInfo[$mode], $tagset);
      $form->assign("tagsetInfo", $tagsetInfo);
      $form->assign("isTagset", TRUE);
    }
  }

  /**
   * Save entity tags when it is not save used AJAX.
   *
   * @param array $params
   * @param int $entityId
   *   Entity id, eg: contact id, activity id, case id, file id.
   * @param string $entityTable
   *   Entity table.
   * @param CRM_Core_Form $form
   *   Form object.
   */
  public static function postProcess(&$params, $entityId, $entityTable = 'civicrm_contact', &$form) {
    if ($form && !empty($form->_entityTagValues)) {
      $existingTags = $form->_entityTagValues;
    }
    else {
      $existingTags = CRM_Core_BAO_EntityTag::getTag($entityId, $entityTable);
    }

    if ($form) {
      // if the key is missing from the form response then all the tags were deleted / cleared
      // in that case we create empty tagset params so that below logic works and tagset are
      // deleted correctly
      foreach ($form->_tagsetInfo as $tagsetName => $tagsetInfo) {
        $tagsetId = explode('parentId_', $tagsetName);
        $tagsetId = $tagsetId[1];
        if (empty($params[$tagsetId])) {
          $params[$tagsetId] = '';
        }
      }
    }

    // when form is submitted with tagset values below logic will work and in the case when all tags in a tagset
    // are deleted we will have to set $params[tagset id] = '' which is done by above logic
    foreach ($params as $parentId => $value) {
      $newTagIds = array();
      $tagIds = array();

      if ($value) {
        $tagIds = explode(',', $value);
        foreach ($tagIds as $tagId) {
          if ($form && $form->_action != CRM_Core_Action::UPDATE || !array_key_exists($tagId, $existingTags)) {
            $newTagIds[] = $tagId;
          }
        }
      }

      // Any existing entity tags from this tagset missing from the $params should be deleted
      $deleteSQL = "DELETE FROM civicrm_entity_tag
                    USING civicrm_entity_tag, civicrm_tag
                    WHERE civicrm_tag.id=civicrm_entity_tag.tag_id
                      AND civicrm_entity_tag.entity_table='{$entityTable}'
                      AND entity_id={$entityId} AND parent_id={$parentId}";
      if (!empty($tagIds)) {
        $deleteSQL .= " AND tag_id NOT IN (" . implode(', ', $tagIds) . ");";
      }

      CRM_Core_DAO::executeQuery($deleteSQL);

      if (!empty($newTagIds)) {
        // New tag ids can be inserted directly into the db table.
        $insertValues = array();
        foreach ($newTagIds as $tagId) {
          $insertValues[] = "( {$tagId}, {$entityId}, '{$entityTable}' ) ";
        }
        $insertSQL = 'INSERT INTO civicrm_entity_tag ( tag_id, entity_id, entity_table )
          VALUES ' . implode(', ', $insertValues) . ';';
        CRM_Core_DAO::executeQuery($insertSQL);
      }
    }
  }

}
