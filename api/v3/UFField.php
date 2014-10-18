<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * File for the CiviCRM APIv3 user framework group functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_UF
 *
 * @copyright CiviCRM LLC (c) 2004-2014
 * @version $Id: UFField.php 30171 2010-10-14 09:11:27Z mover $
 *
 */

/**
 * Defines 'uf field' within a group.
 *
 * @param $params  array  Associative array of property name/value pairs to create new uf field.
 *
 * @throws API_Exception
 * @internal param int $groupId Valid uf_group id
 *
 * @return Newly created $ufFieldArray array
 *
 * @access public
 * {@getfields UFField_create}
 * @example UFFieldCreate.php
 */
function civicrm_api3_uf_field_create($params) {
  civicrm_api3_verify_one_mandatory($params, NULL, array('field_name', 'uf_group_id'));
  $groupId = CRM_Utils_Array::value('uf_group_id', $params);
  if ((int) $groupId < 1) {
    throw new API_Exception('Params must be a field_name-carrying array and a positive integer.');
  }

  $field_type       = CRM_Utils_Array::value('field_type', $params);
  $field_name       = CRM_Utils_Array::value('field_name', $params);
  $location_type_id = CRM_Utils_Array::value('location_type_id', $params);
  $phone_type       = CRM_Utils_Array::value('phone_type_id', $params, CRM_Utils_Array::value('phone_type', $params));

  if (! CRM_Core_BAO_UFField::isValidFieldName($field_name)) {
    throw new API_Exception('The field_name is not valid');
  }
  $params['field_name'] = array($field_type, $field_name, $location_type_id, $phone_type);

  if (!(CRM_Utils_Array::value('group_id', $params))) {
    $params['group_id'] = $groupId;
  }

  $ids = $ufFieldArray = array();
  $ids['uf_group'] = $groupId;

  $fieldId = CRM_Utils_Array::value('id', $params);
  if (!empty($fieldId)) {
    $UFField = new CRM_Core_BAO_UFField();
    $UFField->id = $fieldId;
    if ($UFField->find(TRUE)) {
      $ids['uf_group'] = $UFField->uf_group_id;
      if (!(CRM_Utils_Array::value('group_id', $params))) {
        // this copied here from previous api function - not sure if required
        $params['group_id'] = $UFField->uf_group_id;
      }
    }
    else {
      throw new API_Exception("there is no field for this fieldId");
    }
    $ids['uf_field'] = $fieldId;
  }

  if (CRM_Core_BAO_UFField::duplicateField($params, $ids)) {
    throw new API_Exception("The field was not added. It already exists in this profile.");
  }
  //@todo why is this even optional? Surely weight should just be 'managed' ??
  if (CRM_Utils_Array::value('option.autoweight', $params, TRUE)) {
    $params['weight'] = CRM_Core_BAO_UFField::autoWeight($params);
  }
  $ufField = CRM_Core_BAO_UFField::add($params, $ids);

  $fieldsType = CRM_Core_BAO_UFGroup::calculateGroupType($groupId, TRUE);
  CRM_Core_BAO_UFGroup::updateGroupTypes($groupId, $fieldsType);

  _civicrm_api3_object_to_array($ufField, $ufFieldArray[$ufField->id]);
  civicrm_api3('profile', 'getfields', array('cache_clear' => TRUE));
  return civicrm_api3_create_success($ufFieldArray, $params);
}

/**
 * Gets field for civicrm_uf_field create
 *
 * @param $params
 *
 * @return array fields valid for other functions
 */
function _civicrm_api3_uf_field_create_spec(&$params) {
  $params['option.autoweight'] = array(
    'title' => "Automatically adjust weights in UFGroup to align with UFField",
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => TRUE,
  );
  $params['created_id']['api.default'] = 'user_contact_id';
  $params['created_id']['title']  = 'Created By';
  $params['is_active']['api.default'] = TRUE;
}

/**
 * Returns array of uf groups (profiles)  matching a set of one or more group properties
 *
 * @param array $params  (reference) Array of one or more valid
 *                       property_name=>value pairs. If $params is set
 *                       as null, all surveys will be returned
 *
 * @return array  (reference) Array
 * {@getfields UFField_get
 * @example UFFieldGet.php
 * @access public
 */
function civicrm_api3_uf_field_get($params) {
  return _civicrm_api3_basic_get('CRM_Core_BAO_UFField', $params);
}

/**
 * Delete uf field
 *
 * @param $params
 *
 * @throws API_Exception
 * @internal param int $fieldId Valid uf_field id that to be deleted
 *
 * @return true on successful delete or return error
 *
 * @access public
 * {@getfields UFField_delete}
 * @example UFFieldDelete.php
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
 * field id accepted for backward compatibility - unset required on id
 */
function _civicrm_api3_uf_field_delete_spec(&$params) {
  // legacy support for field_id
  $params['id']['api.aliases'] = array('field_id');
}

