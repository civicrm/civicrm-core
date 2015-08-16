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
 * This api exposes CiviCRM Case.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or update case type.
 *
 * @param array $params
 *   Input parameters.
 *
 * @throws API_Exception
 * @return array
 *   API result array
 */
function civicrm_api3_case_type_create($params) {
  civicrm_api3_verify_mandatory($params, _civicrm_api3_get_DAO(__FUNCTION__));
  // Computed properties.
  unset($params['is_forkable']);
  unset($params['is_forked']);

  if (!array_key_exists('is_active', $params) && empty($params['id'])) {
    $params['is_active'] = TRUE;
  }
  // This is an existing case-type.
  if (!empty($params['id'])
    && !CRM_Case_BAO_CaseType::isForked($params['id']) // which is not yet forked
    && !CRM_Case_BAO_CaseType::isForkable($params['id']) // for which new forks are prohibited
  ) {
    unset($params['definition']);
  }
  $result = _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'CaseType');
  return _civicrm_api3_case_type_get_formatResult($result);
}

/**
 * Retrieve case types.
 *
 * @param array $params
 *
 * @return array
 *   case types keyed by id
 */
function civicrm_api3_case_type_get($params) {
  if (!empty($params['options']) && !empty($params['options']['is_count'])) {
    return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
  }
  $caseTypes = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
  // format case type, to fetch xml definition
  return _civicrm_api3_case_type_get_formatResult($caseTypes);
}

/**
 * Format definition.
 *
 * @param array $result
 *
 * @return array
 * @throws \CRM_Core_Exception
 */
function _civicrm_api3_case_type_get_formatResult(&$result) {
  foreach ($result['values'] as $key => $caseType) {
    $caseTypeName = (isset($caseType['name'])) ? $caseType['name'] : CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseType', $caseType['id'], 'name', 'id', TRUE);
    $xml = CRM_Case_XMLRepository::singleton()->retrieve($caseTypeName);
    if ($xml) {
      $result['values'][$key]['definition'] = CRM_Case_BAO_CaseType::convertXmlToDefinition($xml);
    }
    else {
      $result['values'][$key]['definition'] = array();
    }
    $result['values'][$key]['is_forkable'] = CRM_Case_BAO_CaseType::isForkable($result['values'][$key]['id']);
    $result['values'][$key]['is_forked'] = CRM_Case_BAO_CaseType::isForked($result['values'][$key]['id']);
  }
  return $result;
}

/**
 * Function to delete case type.
 *
 * @param array $params
 *   Array including id of CaseType to delete.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_case_type_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
