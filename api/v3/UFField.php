<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
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
 * This api exposes CiviCRM profile field.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Defines 'uf field' within a group.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @throws API_Exception
 *
 * @return array
 *   Newly created $ufFieldArray
 */
function civicrm_api3_uf_field_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'UFField');
}

/**
 * Adjust metadata for civicrm_uf_field create.
 *
 * @param array $params
 */
function _civicrm_api3_uf_field_create_spec(&$params) {
  $params['field_name']['api.required'] = TRUE;
  $params['uf_group_id']['api.required'] = TRUE;

  $params['option.autoweight'] = array(
    'title' => "Auto Weight",
    'description' => "Automatically adjust weights in UFGroup to align with UFField",
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => TRUE,
  );
  $params['is_active']['api.default'] = TRUE;
}

/**
 * Returns array of uf groups (profiles) matching a set of one or more group properties.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 */
function civicrm_api3_uf_field_get($params) {
  return _civicrm_api3_basic_get('CRM_Core_BAO_UFField', $params);
}

/**
 * Delete uf field.
 *
 * @param array $params
 *
 * @throws API_Exception
 *
 * @return array
 */
function civicrm_api3_uf_field_delete($params) {
  $fieldId = $params['id'];

  $ufGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFField', $fieldId, 'uf_group_id');
  if (!$ufGroupId) {
    throw new API_Exception('Invalid value for field_id.');
  }

  $result = CRM_Core_BAO_UFField::del($fieldId);

  $fieldsType = CRM_Core_BAO_UFGroup::calculateGroupType($ufGroupId, TRUE);
  CRM_Core_BAO_UFGroup::updateGroupTypes($ufGroupId, $fieldsType);

  return civicrm_api3_create_success($result, $params);
}

/**
 * Field id accepted for backward compatibility - unset required on id.
 *
 * @param array $params
 */
function _civicrm_api3_uf_field_delete_spec(&$params) {
  // legacy support for field_id
  $params['id']['api.aliases'] = array('field_id');
}
