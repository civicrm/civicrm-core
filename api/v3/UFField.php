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
 * @throws CRM_Core_Exception
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

  $params['option.autoweight'] = [
    'title' => "Auto Weight",
    'description' => "Automatically adjust weights in UFGroup to align with UFField",
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => TRUE,
  ];
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
 * @throws CRM_Core_Exception
 *
 * @return array
 */
function civicrm_api3_uf_field_delete($params) {
  $fieldId = $params['id'];

  $ufGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFField', $fieldId, 'uf_group_id');
  if (!$ufGroupId) {
    throw new CRM_Core_Exception('Invalid value for field_id.');
  }

  $result = CRM_Core_BAO_UFField::deleteRecord(['id' => $fieldId]);

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
  $params['id']['api.aliases'] = ['field_id'];
}
