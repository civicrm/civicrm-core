<?php
// $Id: EntityTag.php 45502 2013-02-08 13:32:55Z kurund $


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
 * File for the CiviCRM APIv2 entity tag functions
 *
 * @package CiviCRM_APIv2
 * @subpackage API_EntityTag
 *
 * @copyright CiviCRM LLC (c) 2004-2013
 * @version $Id: EntityTag.php 45502 2013-02-08 13:32:55Z kurund $
 */

/**
 * Include utility functions
 */
require_once 'api/v2/utils.php';

/**
 *
 * @param <type> $params
 *
 * @return <type>
 */
function civicrm_entity_tag_get(&$params) {
  if (!is_array($params)) {
    return civicrm_create_error(ts('params should be an array.'));
  }

  $entityID = NULL;
  $entityTable = 'civicrm_contact';

  if (!($entityID = CRM_Utils_Array::value('entity_id', $params))) {
    $entityID = CRM_Utils_Array::value('contact_id', $params);
  }

  if (empty($entityID)) {
    return civicrm_create_error(ts('entity_id is a required field.'));
  }

  if (CRM_Utils_Array::value('entity_table', $params)) {
    $entityTable = $params['entity_table'];
  }

  require_once 'CRM/Core/BAO/EntityTag.php';
  $values = CRM_Core_BAO_EntityTag::getTag($entityID, $entityTable);
  $result = array();
  foreach ($values as $v) {
    $result[] = array('tag_id' => $v);
  }
  return $result;
}

/**
 *
 * @param <type> $params
 *
 * @return <type>
 */
function civicrm_entity_tag_display(&$params) {
  if (!is_array($params)) {
    return civicrm_create_error(ts('params should be an array.'));
  }

  $entityID = NULL;
  $entityTable = 'civicrm_contact';

  if (!($entityID = CRM_Utils_Array::value('entity_id', $params))) {
    $entityID = CRM_Utils_Array::value('contact_id', $params);
  }

  if (empty($entityID)) {
    return civicrm_create_error(ts('entity_id is a required field.'));
  }

  if (CRM_Utils_Array::value('entity_table', $params)) {
    $entityTable = $params['entity_table'];
  }

  require_once 'CRM/Core/BAO/EntityTag.php';
  $values = CRM_Core_BAO_EntityTag::getTag($entityID, $entityTable);
  $result = array();
  $tags   = CRM_Core_PseudoConstant::tag();
  foreach ($values as $v) {
    $result[] = $tags[$v];
  }
  return implode(',', $result);
}

/**
 * Returns all entities assigned to a specific Tag.
 *
 * @param  $params      Array   an array valid Tag id
 *
 * @return $entities    Array   An array of entity ids.
 * @access public
 */
function civicrm_tag_entities_get(&$params) {
  require_once 'CRM/Core/BAO/Tag.php';
  require_once 'CRM/Core/BAO/EntityTag.php';
  $tag      = new CRM_Core_BAO_Tag();
  $tag->id  = CRM_Utils_Array::value('tag_id', $params) ? $params['tag_id'] : NULL;
  $entities = CRM_Core_BAO_EntityTag::getEntitiesByTag($tag);
  return $entities;
}

/**
 *
 * @param <type> $params
 *
 * @return <type>
 * @deprecated
 */
function civicrm_entity_tag_add(&$params) {
  return civicrm_entity_tag_common($params, 'add');
}

/**
 *
 * @param <type> $params
 *
 * @return <type>
 */
function civicrm_entity_tag_create(&$params) {
  return civicrm_entity_tag_common($params, 'add');
}

/**
 *
 * @param <type> $params
 *
 * @return <type>
 * @deprecated
 */
function civicrm_entity_tag_remove(&$params) {
  return civicrm_entity_tag_common($params, 'remove');
}

/**
 *
 * @param array $params
 *
 * @return <type>
 */
function civicrm_entity_tag_delete(&$params) {
  return civicrm_entity_tag_common($params, 'remove');
}

/**
 *
 * @param <type> $params
 * @param <type> $op
 *
 * @return <type>
 */
function civicrm_entity_tag_common(&$params, $op = 'add') {
  $entityIDs   = array();
  $tagsIDs     = array();
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
    return civicrm_create_error(ts('contact_id is a required field'));
  }

  if (empty($tagIDs)) {
    return civicrm_create_error(ts('tag_id is a required field'));
  }

  require_once 'CRM/Core/BAO/EntityTag.php';
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

