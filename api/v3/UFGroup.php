<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * This api exposes CiviCRM profile group.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Adjust metadata for create action.
 *
 * @param array $params
 */
function _civicrm_api3_uf_group_create_spec(&$params) {
  $session = CRM_Core_Session::singleton();
  $params['title']['api.required'] = 1;
  $params['is_active']['api.default'] = 1;
  $params['is_update_dupe']['api.default'] = 1;
  // Default to the logged in user.
  $params['created_id']['api.default'] = 'user_contact_id';
  $params['created_date']['api.default'] = 'now';
}

/**
 * Use this API to create a new group.
 *
 * See the CRM Data Model for uf_group property definitions
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_uf_group_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Returns array of uf groups (profiles) matching a set of one or more group properties.
 *
 * @param array $params
 *   Array of properties. If empty, all records will be returned.
 *
 * @return array
 *   Array of matching profiles
 */
function civicrm_api3_uf_group_get($params) {

  return _civicrm_api3_basic_get('CRM_Core_BAO_UFGroup', $params);
}

/**
 * Delete uf group.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_uf_group_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Set default getlist parameters.
 *
 * @see _civicrm_api3_generic_getlist_defaults
 *
 * @param array $request
 *
 * @return array
 */
function _civicrm_api3_uf_group_getlist_defaults(&$request) {
  return array(
    'description_field' => array(
      'description',
      'group_type',
    ),
    'params' => array(
      'is_active' => 1,
    ),
  );
}

/**
 * Format getlist output
 *
 * @see _civicrm_api3_generic_getlist_output
 *
 * @param array $result
 * @param array $request
 * @param string $entity
 * @param array $fields
 *
 * @return array
 */
function _civicrm_api3_uf_group_getlist_output($result, $request, $entity, $fields) {
  $output = array();
  if (!empty($result['values'])) {
    foreach ($result['values'] as $row) {
      $data = array(
        'id' => $row[$request['id_field']],
        'label' => $row[$request['label_field']],
      );
      if (!empty($request['description_field'])) {
        $data['description'] = array();
        foreach ((array) $request['description_field'] as $field) {
          if (!empty($row[$field])) {
            // Special formatting for group_type field
            if ($field == 'group_type') {
              $groupTypes = CRM_UF_Page_Group::extractGroupTypes($row[$field]);
              $data['description'][] = CRM_UF_Page_Group::formatGroupTypes($groupTypes);
              continue;
            }
            if (!isset($fields[$field]['pseudoconstant'])) {
              $data['description'][] = $row[$field];
            }
            else {
              $data['description'][] = CRM_Core_PseudoConstant::getLabel(
                _civicrm_api3_get_BAO($entity),
                $field,
                $row[$field]
              );
            }
          }
        }
      };
      if (!empty($request['image_field'])) {
        $data['image'] = isset($row[$request['image_field']]) ? $row[$request['image_field']] : '';
      }
      $output[] = $data;
    }
  }
  return $output;
}
