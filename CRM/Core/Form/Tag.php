<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
 * This class generates form element for free tag widget
 *
 */
class CRM_Core_Form_Tag {
  public $_entityTagValues;

  /**
   * Function to build tag widget if correct parent is passed
   *
   * @param object  $form form object
   * @param string  $parentName parent name ( tag name)
   * @param string  $entityTable entitytable 'eg: civicrm_contact'
   * @param int     $entityId    entityid  'eg: contact id'
   * @param boolean $skipTagCreate true if tag need be created using ajax
   * @param boolean $skipEntityAction true if need to add entry in entry table via ajax
   * @param boolean $searchMode true if widget is used in search eg: advanced search
   * @param string  $tagsetElementName if you need to create tagsetlist with specific name
   *
   * @return void
   * @access public
   * @static
   */
  static function buildQuickForm(&$form, $parentNames, $entityTable, $entityId = NULL, $skipTagCreate = FALSE,
    $skipEntityAction = FALSE, $searchMode = FALSE, $tagsetElementName = NULL ) {
    $tagset = $form->_entityTagValues = array();
    $form->assign("isTagset", FALSE);
    $mode = NULL;

    foreach ($parentNames as & $parentNameItem) {
      // get the parent id for tag list input for keyword
      $parentId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $parentNameItem, 'id', 'name');

      // check if parent exists
      $entityTags = array();
      if ($parentId) {
        $tagsetItem = 'parentId_' . $parentId;
        $tagset[$tagsetItem]['parentName'] = $parentNameItem;
        $tagset[$tagsetItem]['parentID'] = $parentId;

        //tokeninput url
        $qparams = "parentId={$parentId}";

        if ($searchMode) {
          $qparams .= '&search=1';
        }

        $tagUrl = CRM_Utils_System::url('civicrm/ajax/taglist', $qparams, FALSE, NULL, FALSE);

        $tagset[$tagsetItem]['tagUrl'] = $tagUrl;
        $tagset[$tagsetItem]['entityTable'] = $entityTable;
        $tagset[$tagsetItem]['skipTagCreate'] = $skipTagCreate;
        $tagset[$tagsetItem]['skipEntityAction'] = $skipEntityAction;

        switch ($entityTable) {
          case 'civicrm_activity':
            $tagsetElementName = "activity_taglist";
            $mode = 'activity';
            break;

          case 'civicrm_case':
            $tagsetElementName = "case_taglist";
            $mode = 'case';
            break;

          case 'civicrm_file':
            $mode = 'attachment';
            break;

          default:
            $tagsetElementName = "contact_taglist";
            $mode = 'contact';
        }

        $tagset[$tagsetItem]['tagsetElementName'] = $tagsetElementName;
        if ($tagsetElementName) {
          $form->add('text', "{$tagsetElementName}[{$parentId}]", NULL);
        }

        if ($entityId) {
          $tagset[$tagsetItem]['entityId'] = $entityId;
          $entityTags = CRM_Core_BAO_EntityTag::getChildEntityTags($parentId, $entityId, $entityTable);
        }
        else {

          switch ($entityTable) {
            case 'civicrm_activity':
              if (!empty($form->_submitValues['activity_taglist']) &&
                CRM_Utils_Array::value($parentId, $form->_submitValues['activity_taglist'])
              ) {
                $allTags = CRM_Core_PseudoConstant::get('CRM_Core_DAO_EntityTag', 'tag_id', array('onlyActive' => FALSE));
                $tagIds = explode(',', $form->_submitValues['activity_taglist'][$parentId]);
                foreach ($tagIds as $tagId) {
                  if (is_numeric($tagId)) {
                    $tagName = $allTags[$tagId];
                  }
                  else {
                    $tagName = $tagId;
                  }
                  $entityTags[$tagId] = array(
                    'id' => $tagId,
                    'name' => $tagName,
                  );
                }
              }
              break;

            case 'civicrm_case':
              if (!empty($form->_submitValues['case_taglist']) &&
                CRM_Utils_Array::value($parentId, $form->_submitValues['case_taglist'])
              ) {
                $allTags = CRM_Core_PseudoConstant::get('CRM_Core_DAO_EntityTag', 'tag_id', array('onlyActive' => FALSE));
                $tagIds = explode(',', $form->_submitValues['case_taglist'][$parentId]);
                foreach ($tagIds as $tagId) {
                  if (is_numeric($tagId)) {
                    $tagName = $allTags[$tagId];
                  }
                  else {
                    $tagName = $tagId;
                  }
                  $entityTags[$tagId] = array(
                    'id' => $tagId,
                    'name' => $tagName,
                  );
                }
              }
              break;
            case 'civicrm_file':
              $numAttachments = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'max_attachments');
              for ($i = 1; $i <= $numAttachments; $i++) {
                $tagset[$i] = $tagset[$tagsetItem];
                $tagset[$i]['tagsetElementName'] = "attachment_taglist_$i";
                $form->add('text', "attachment_taglist_{$i}[{$parentId}]", NULL);
                if (!empty($form->_submitValues["attachment_taglist_$i"]) &&
                  CRM_Utils_Array::value($parentId, $form->_submitValues["attachment_taglist_$i"])
                ) {
                  $allTags = CRM_Core_PseudoConstant::get('CRM_Core_DAO_EntityTag', 'tag_id', array('onlyActive' => FALSE));
                  $tagIds = explode(',', $form->_submitValues["attachment_taglist_$i"][$parentId]);
                  foreach ($tagIds as $tagId) {
                    if (is_numeric($tagId)) {
                      $tagName = $allTags[$tagId];
                    }
                    else {
                      $tagName = $tagId;
                    }
                    $entityTags[$tagId] = array(
                      'id' => $tagId,
                      'name' => $tagName,
                    );
                  }
                }
              }
              unset($tagset[$tagsetItem]);
              break;

            default:
              if (!empty($form->_formValues['contact_tags'])) {
                $contactTags = CRM_Core_BAO_Tag::getTagsUsedFor('civicrm_contact', TRUE, FALSE, $parentId);

                foreach (array_keys($form->_formValues['contact_tags']) as $tagId) {
                  if (CRM_Utils_Array::value($tagId, $contactTags)) {
                    $tagName = $tagId;
                    if (is_numeric($tagId)) {
                      $tagName = $contactTags[$tagId];
                    }

                    $entityTags[$tagId] = array(
                      'id' => $tagId,
                      'name' => $tagName,
                    );
                  }
                }
              }
          }
        }

        if (!empty($entityTags)) {
          // assign as simple array for display in smarty
          $tagset[$tagsetItem]['entityTagsArray'] = $entityTags;
          // assign as json for js widget
          $tagset[$tagsetItem]['entityTags'] = json_encode(array_values($entityTags));

          if (!empty($form->_entityTagValues)) {
            $form->_entityTagValues = CRM_Utils_Array::crmArrayMerge($entityTags, $form->_entityTagValues);
          }
          else {
            $form->_entityTagValues = $entityTags;
          }
        }
      }
    }

    if (!empty($tagset)) {
      // assign current tagsets which is used in postProcess
      $form->_tagsetInfo = $tagset;
      $form->assign("tagsetInfo_$mode", $tagset);
      $form->assign("isTagset", TRUE);
    }
  }

  /**
   * Function to save entity tags when it is not save used AJAX
   *
   * @param array   $params      associated array
   * @param int     $entityId    entity id, eg: contact id, activity id, case id, file id
   * @param string  $entityTable entity table
   * @param object  $form        form object
   *
   * @return void
   * @access public
   * @static
   */
  static function postProcess(&$params, $entityId, $entityTable = 'civicrm_contact', &$form) {
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
        $tagsetId = substr($tagsetName, strlen('parentId_'));
        if (empty($params[$tagsetId])) {
          $params[$tagsetId] = '';
        }
      }
    }

    // when form is submitted with tagset values below logic will work and in the case when all tags in a tagset
    // are deleted we will have to set $params[tagset id] = '' which is done by above logic
    foreach ($params as $parentId => $value) {
      $newTagIds = array();
      $realTagIds = array();

      if ($value) {
        $tagsIDs = explode(',', $value);
        foreach ($tagsIDs as $tagId) {
          if (!is_numeric($tagId)) {
            // check if user has selected existing tag or is creating new tag
            // this is done to allow numeric tags etc.
            $tagValue = explode(':::', $tagId);

            if (isset($tagValue[1]) && $tagValue[1] == 'value') {
              $tagParams = array(
                'name' => $tagValue[0],
                'parent_id' => $parentId,
              );
              $tagObject = CRM_Core_BAO_Tag::add($tagParams, CRM_Core_DAO::$_nullArray);
              $tagId = $tagObject->id;
            }
          }

          $realTagIds[] = $tagId;
          if ($form && $form->_action != CRM_Core_Action::UPDATE) {
            $newTagIds[] = $tagId;
          }
          elseif (!array_key_exists($tagId, $existingTags)) {
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
      if (!empty($realTagIds)) {
        $deleteSQL .= " AND tag_id NOT IN (" . implode(', ', $realTagIds) . ");";
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

