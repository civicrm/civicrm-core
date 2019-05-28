<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @throws \API_Exception
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
      $result[$index]['data'] = unserialize($values['data']);
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
 * @throws \API_Exception
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
 * @throws \API_Exception
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
 * @throws \CiviCRM_API3_Exception
 */
function civicrm_api3_dedupe_getstatistics($params) {
  $stats = CRM_Dedupe_Merger::getMergeStats(CRM_Dedupe_Merger::getMergeCacheKeyString(
    $params['rule_group_id'],
    CRM_Utils_Array::value('group_id', $params),
    CRM_Utils_Array::value('criteria', $params, []),
    CRM_Utils_Array::value('check_permissions', $params, [])
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

}
