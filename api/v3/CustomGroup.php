<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * This api exposes CiviCRM custom group.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Use this API to create a new group.
 *
 * The 'extends' value accepts an array or a comma separated string.
 * e.g array(
 * 'Individual','Contact') or 'Individual,Contact'
 * See the CRM Data Model for custom_group property definitions
 * $params['class_name'] is a required field, class being extended.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 * @todo $params['extends'] is array format - is that std compatible
 */
function civicrm_api3_custom_group_create($params) {
  if (isset($params['extends']) && is_string($params['extends'])) {
    $extends = explode(",", $params['extends']);
    unset($params['extends']);
    $params['extends'] = $extends;
  }
  if (!isset($params['extends'][0]) || !trim($params['extends'][0])) {
    return civicrm_api3_create_error("First item in params['extends'] must be a class name (e.g. 'Contact').");
  }
  if (isset($params['extends_entity_column_value']) && !is_array($params['extends_entity_column_value'])) {
    // BAO fails if this is a string, but API getFields says this must be a string, so we'll do a double backflip
    $params['extends_entity_column_value'] = CRM_Utils_Array::explodePadded($params['extends_entity_column_value']);
  }

  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_custom_group_create_spec(&$params) {
  $params['extends']['api.required'] = 1;
  $params['title']['api.required'] = 1;
  $params['style']['api.default'] = 'Inline';
  $params['is_active']['api.default'] = 1;
}

/**
 * Use this API to delete an existing group.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_custom_group_delete($params) {
  $values = new CRM_Core_DAO_CustomGroup();
  $values->id = $params['id'];
  $values->find(TRUE);

  $result = CRM_Core_BAO_CustomGroup::deleteGroup($values, TRUE);
  return $result ? civicrm_api3_create_success() : civicrm_api3_create_error('Error while deleting custom group');
}

/**
 * API to get existing custom fields.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 */
function civicrm_api3_custom_group_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * CRM-15191 - Hack to ensure the cache gets cleared after updating a custom group.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 */
function civicrm_api3_custom_group_setvalue($params) {
  require_once 'api/v3/Generic/Setvalue.php';
  $result = civicrm_api3_generic_setValue(array("entity" => 'CustomGroup', 'params' => $params));
  if (empty($result['is_error'])) {
    CRM_Utils_System::flushCache();
  }
  return $result;
}
