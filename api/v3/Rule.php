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
 * This api exposes CiviCRM (dedupe) rules.
 *
 * Rules dedupe critieria assigned to RuleGroups.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or update a rule.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_rule_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'DedupeRule');
}

/**
 * Specify Meta data for create.
 *
 * Note that this data is retrievable via the getfields function
 * and is used for pre-filling defaults and ensuring mandatory requirements are met.
 *
 * @param array $params
 */
function _civicrm_api3_rule_create_spec(&$params) {
  $params['dedupe_rule_group_id']['api.required'] = TRUE;
  $params['rule_table']['api.default'] = 'civicrm_contact';
  $params['rule_field']['api.required'] = TRUE;
  $params['rule_weight']['api.required'] = TRUE;
}

/**
 * Delete an existing Rule.
 *
 * @param array $params
 *
 * @return array
 *   API result array
 */
function civicrm_api3_rule_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Get a Rule.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_rule_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'DedupeRule');
}
