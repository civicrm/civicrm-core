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
 * @throws CRM_Core_Exception
 * @return array
 *   API result array
 */
function civicrm_api3_case_type_create($params) {
  civicrm_api3_verify_mandatory($params, _civicrm_api3_get_DAO(__FUNCTION__));

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
  if (!empty($params['options']['is_count'])) {
    return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
  }
  $caseTypes = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
  // format case type, to fetch xml definition
  $options = _civicrm_api3_get_options_from_params($params);
  return _civicrm_api3_case_type_get_formatResult($caseTypes, $options);
}

/**
 * Format definition.
 *
 * @param array $result
 * @param array $options
 *
 * @return array
 * @throws \CRM_Core_Exception
 */
function _civicrm_api3_case_type_get_formatResult(&$result, $options = []) {
  foreach ($result['values'] as $key => &$caseType) {
    if (!empty($caseType['definition']) || empty($options['return']) || !empty($options['return']['definition'])) {
      $caseType += ['definition' => NULL];
      CRM_Case_BAO_CaseType::formatOutputDefinition($caseType['definition'], $caseType);
    }
    $caseType['is_forkable'] = CRM_Case_BAO_CaseType::isForkable($caseType['id']);
    $caseType['is_forked'] = CRM_Case_BAO_CaseType::isForked($caseType['id']);
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
