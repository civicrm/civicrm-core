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
 * This api exposes CiviCRM EntityTag records.
 *
 * Use this api to add/remove tags from a contact/activity/etc.
 * To create/update/delete the tags themselves, use the Tag api.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Get entity tags.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_entity_tag_get($params) {

  if (empty($params['entity_id'])) {
    return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
  }
  else {
    //do legacy non-standard behaviour
    $values = CRM_Core_BAO_EntityTag::getTag($params['entity_id'], $params['entity_table']);
    $result = array();
    foreach ($values as $v) {
      $result[$v] = array('tag_id' => $v);
    }
    return civicrm_api3_create_success($result, $params, 'EntityTag');
  }
}

/**
 * Adjust Metadata for Get action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_entity_tag_get_spec(&$params) {
  $params['entity_id']['api.aliases'] = array('contact_id');
  $params['entity_table']['api.default'] = 'civicrm_contact';
}

/**
 * Create an entity tag.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_entity_tag_create($params) {
  return _civicrm_api3_entity_tag_common($params, 'add');
}

/**
 * Mark entity tag as removed.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_entity_tag_delete($params) {

  return _civicrm_api3_entity_tag_common($params, 'remove');
}

/**
 * Modify metadata.
 *
 * @param array $params
 */
function _civicrm_api3_entity_tag_delete_spec(&$params) {
  // set as not required as tag_id also acceptable & no either/or std yet
  $params['id']['api.required'] = 0;
}

/**
 * Helper function for formatting tags (part of api v2 legacy).
 *
 * @param array $params
 * @param string $op
 *
 * @return array
 */
function _civicrm_api3_entity_tag_common($params, $op = 'add') {

  $entityIDs   = array();
  $entityTable = 'civicrm_contact';
  if (is_array($params)) {
    foreach ($params as $n => $v) {
      if ((substr($n, 0, 10) == 'contact_id') || (substr($n, 0, 9) == 'entity_id')) {
        $entityIDs[] = $v;
      }
      elseif (substr($n, 0, 6) == 'tag_id') {
        $tagIDs[] = $v;
      }
      elseif (substr($n, 0, 12) == 'entity_table') {
        $entityTable = $v;
      }
    }
  }
  if (empty($entityIDs)) {
    return civicrm_api3_create_error('contact_id is a required field');
  }

  if (empty($tagIDs)) {
    return civicrm_api3_create_error('tag_id is a required field');
  }

  $values = array('is_error' => 0);
  if ($op == 'add') {
    $values['total_count'] = $values['added'] = $values['not_added'] = 0;
    foreach ($tagIDs as $tagID) {
      list($te, $a, $na) = CRM_Core_BAO_EntityTag::addEntitiesToTag($entityIDs, $tagID, $entityTable);
      $values['total_count'] += $te;
      $values['added'] += $a;
      $values['not_added'] += $na;
    }
  }
  else {
    $values['total_count'] = $values['removed'] = $values['not_removed'] = 0;
    foreach ($tagIDs as $tagID) {
      list($te, $r, $nr) = CRM_Core_BAO_EntityTag::removeEntitiesFromTag($entityIDs, $tagID, $entityTable);
      $values['total_count'] += $te;
      $values['removed'] += $r;
      $values['not_removed'] += $nr;
    }
  }
  return $values;
}
