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
 * This api exposes CiviCRM dedupe functionality.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Get rows for any cached attempted merges on the passed criteria.
 *
 * @param array $params
 *
 * @return array
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_dedupe_get($params) {
  $sql = CRM_Utils_SQL_Select::fragment();
  $sql->where(['merge_data_restriction' => "cachekey LIKE 'merge_%'"]);

  $options = _civicrm_api3_get_options_from_params($params, TRUE, 'PrevNextCache', 'get');
  $result = _civicrm_api3_basic_get('CRM_Core_BAO_PrevNextCache', $params, FALSE, 'PrevNextCache', $sql);

  if ($options['is_count']) {
    return civicrm_api3_create_success($result, $params, 'PrevNextCache', 'get');
  }
  foreach ($result as $index => $values) {
    if (isset($values['data']) && !empty($values['data'])) {
      $result[$index]['data'] = CRM_Core_DAO::unSerializeField($values['data'], CRM_Core_DAO::SERIALIZE_PHP);
    }
  }
  return civicrm_api3_create_success($result, $params, 'PrevNextCache');
}

/**
 * Get rows for getting dedupe cache records.
 *
 * @param array $params
 */
function _civicrm_api3_dedupe_get_spec(&$params) {
  $params = CRM_Core_DAO_PrevNextCache::fields();
  $params['id']['api.aliases'] = ['dedupe_id'];
}

/**
 * Delete rows for any cached attempted merges on the passed criteria.
 *
 * @param array $params
 *
 * @return array
 *
 * @throws \CRM_Core_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function civicrm_api3_dedupe_delete($params) {
  return _civicrm_api3_basic_delete('CRM_Core_BAO_PrevNextCache', $params);
}

/**
 * Get the statistics for any cached attempted merges on the passed criteria.
 *
 * @param array $params
 *
 * @return array
 * @throws \CRM_Core_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function civicrm_api3_dedupe_create($params) {
  return _civicrm_api3_basic_create('CRM_Core_BAO_PrevNextCache', $params, 'PrevNextCache');
}

/**
 * Get the statistics for any cached attempted merges on the passed criteria.
 *
 * @param array $params
 *
 * @return array
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_dedupe_getstatistics($params) {
  $stats = CRM_Dedupe_Merger::getMergeStats(CRM_Dedupe_Merger::getMergeCacheKeyString(
    $params['rule_group_id'],
    $params['group_id'] ?? NULL,
    $params['criteria'] ?? [],
    !empty($params['check_permissions']),
    $params['search_limit'] ?? 0
  ));
  return civicrm_api3_create_success($stats);
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_dedupe_getstatistics_spec(&$params) {
  $params['rule_group_id'] = [
    'title' => ts('Rule Group ID'),
    'api.required' => TRUE,
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['group_id'] = [
    'title' => ts('Group ID'),
    'api.required' => FALSE,
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['criteria'] = [
    'title' => ts('Criteria'),
    'description' => ts('Dedupe search criteria, as parsable by v3 Contact.get api'),
  ];
  $spec['search_limit'] = [
    'title' => ts('Number of contacts to look for matches for.'),
    'type' => CRM_Utils_Type::T_INT,
    'api.default' => (int) Civi::settings()->get('dedupe_default_limit'),
  ];

}

/**
 * Get the duplicate contacts for the supplied parameters.
 *
 * @param array $params
 *
 * @return array
 * @throws \CRM_Core_Exception
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_dedupe_getduplicates($params) {
  $options = _civicrm_api3_get_options_from_params($params);
  $dupePairs = CRM_Dedupe_Merger::getDuplicatePairs($params['rule_group_id'], NULL, TRUE, $options['limit'], FALSE, TRUE, $params['criteria'], $params['check_permissions'] ?? FALSE, $params['search_limit'] ?? 0, $params['is_force_new_search'] ?? 0);
  return civicrm_api3_create_success($dupePairs);
}

/**
 * Adjust Metadata for getduplicates action..
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_dedupe_getduplicates_spec(&$params) {
  $params['rule_group_id'] = [
    'title' => ts('Rule Group ID'),
    'api.required' => TRUE,
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['criteria'] = [
    'title' => ts('Criteria'),
    'description' => ts("Dedupe search criteria, as parsable by v3 Contact.get api, keyed by Contact. Eg.['Contact' => ['id' => ['BETWEEN' => [1, 2000]], 'group' => 34]"),
    'api.default' => [],
  ];
  $spec['search_limit'] = [
    'title' => ts('Number of contacts to look for matches for.'),
    'type' => CRM_Utils_Type::T_INT,
    'api.default' => (int) Civi::settings()->get('dedupe_default_limit'),
  ];
  $spec['is_force_new_search'] = [
    'title' => ts('Force a new search, refreshing any cached search'),
    'type' => CRM_Utils_Type::T_BOOLEAN,
  ];

}
