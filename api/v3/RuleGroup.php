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
 * This api exposes CiviCRM rule_groups.
 *
 * RuleGroups are used to group dedupe critieria.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or update a rule_group.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_rule_group_create($params) {
  civicrm_api3_verify_one_mandatory($params, NULL, ['title', 'name']);
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'DedupeRuleGroup');
}

/**
 * Specify Meta data for create.
 *
 * Note that this data is retrievable via the getfields function
 * and is used for pre-filling defaults and ensuring mandatory requirements are met.
 *
 * @param array $params
 */
function _civicrm_api3_rule_group_create_spec(&$params) {
  $params['contact_type']['api.required'] = TRUE;
  $params['threshold']['api.required'] = TRUE;
  $params['used']['api.required'] = TRUE;
}

/**
 * Delete an existing RuleGroup.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_rule_group_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Get a RuleGroup.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_rule_group_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'DedupeRuleGroup');
}
