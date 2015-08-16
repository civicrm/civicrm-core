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
 * This api exposes CiviCRM LocBlock records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or update a LocBlock.
 *
 * @param array $params
 *   name/value pairs to insert in new 'LocBlock'
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_loc_block_create($params) {
  $entities = array();
  // Call the appropriate api to create entities if any are passed in the params
  // This is basically chaining but in reverse - we create the sub-entities first
  // This exists because chainging does not work in reverse, or with keys like 'email_2'
  $items = array('address', 'email', 'phone', 'im');
  foreach ($items as $item) {
    foreach (array('', '_2') as $suf) {
      $key = $item . $suf;
      if (!empty($params[$key]) && is_array($params[$key])) {
        $info = $params[$key];
        // If all we get is an id don't bother calling the api
        if (count($info) == 1 && !empty($info['id'])) {
          $params[$key . '_id'] = $info['id'];
        }
        // Bother calling the api
        else {
          $info['version'] = $params['version'];
          $info['contact_id'] = CRM_Utils_Array::value('contact_id', $info, 'null');
          $result = civicrm_api($item, 'create', $info);
          if (!empty($result['is_error'])) {
            return $result;
          }
          $entities[$key] = $result['values'][$result['id']];
          $params[$key . '_id'] = $result['id'];
        }
      }
    }
  }
  $dao = new CRM_Core_DAO_LocBlock();
  $dao->copyValues($params);
  $dao->save();
  if (!empty($dao->id)) {
    $values = array($dao->id => $entities);
    _civicrm_api3_object_to_array($dao, $values[$dao->id]);
    return civicrm_api3_create_success($values, $params, 'LocBlock', 'create', $dao);
  }
  return civicrm_api3_create_error('Unable to create LocBlock. Please check your params.');
}

/**
 * Returns array of loc_blocks matching a set of one or more properties.
 *
 * @param array $params
 *   Array of one or more valid property_name=>value pairs. If $params is set.
 *   as null, all loc_blocks will be returned (default limit is 25)
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_loc_block_get($params) {
  $options = _civicrm_api3_get_options_from_params($params);
  // If a return param has been set then fetch the appropriate fk objects
  // This is a helper because api chaining does not work with a key like 'email_2'
  if (!empty($options['return'])) {
    unset($params['return']);
    $values = array();
    $items = array('address', 'email', 'phone', 'im');
    $returnAll = !empty($options['return']['all']);
    foreach (_civicrm_api3_basic_get('CRM_Core_DAO_LocBlock', $params, FALSE) as $val) {
      foreach ($items as $item) {
        foreach (array('', '_2') as $suf) {
          $key = $item . $suf;
          if (!empty($val[$key . '_id']) && ($returnAll || !empty($options['return'][$key]))) {
            $val[$key] = civicrm_api($item, 'getsingle', array('version' => 3, 'id' => $val[$key . '_id']));
          }
        }
      }
      $values[$val['id']] = $val;
    }
    return civicrm_api3_create_success($values, $params, 'LocBlock', 'get');
  }
  return _civicrm_api3_basic_get('CRM_Core_DAO_LocBlock', $params);
}

/**
 * Delete an existing LocBlock.
 *
 * @param array $params
 *   Array containing id of the record to be deleted.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_loc_block_delete($params) {
  return _civicrm_api3_basic_delete('CRM_Core_DAO_LocBlock', $params);
}
