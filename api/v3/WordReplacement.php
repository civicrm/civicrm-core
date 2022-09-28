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
 * This api exposes CiviCRM WordReplacement records.
 *
 * Word replacements are used to globally alter strings in the CiviCRM UI.
 * Note that the original source string is always English, regardless of language settings.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Get CiviCRM Word Replacement details.
 *
 * @param array $params
 *
 * @return array
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_word_replacement_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Create a new Word Replacement.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_word_replacement_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'WordReplacement');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_word_replacement_create_spec(&$params) {
  $params['find_word']['api.required'] = 1;
  $params['replace_word']['api.required'] = 1;
  $params['is_active']['api.default'] = 1;
}

/**
 * Delete an existing WordReplacement.
 *
 * @param array $params
 *   Array containing id of the WordReplacement to be deleted.
 *
 * @return array
 *   API result array
 */
function civicrm_api3_word_replacement_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
