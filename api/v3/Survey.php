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
 * This api exposes CiviCRM survey/petition records.
 *
 * @note Campaign component must be enabled.
 * @note There is no "petition" api.
 * Surveys and petitions are the same basic object and this api is used for both.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create or update a survey.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 *   api result array
 */
function civicrm_api3_survey_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'Survey');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_survey_create_spec(&$params) {
  $params['title']['api.required'] = 1;
}

/**
 * Returns array of surveys  matching a set of one or more group properties.
 *
 * @param array $params
 *   Array of properties. If empty, all records will be returned.
 *
 * @return array
 *   API result Array of matching surveys
 */
function civicrm_api3_survey_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'Survey');
}

/**
 * Delete an existing survey.
 *
 * This method is used to delete any existing survey given its id.
 *
 * @param array $params
 *   [id]
 *
 * @return array
 *   api result array
 */
function civicrm_api3_survey_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Set default getlist parameters.
 *
 * @see _civicrm_api3_generic_getlist_defaults
 *
 * @param array $request
 *
 * @return array
 */
function _civicrm_api3_survey_getlist_defaults(&$request) {
  return [
    'description_field' => [
      'campaign_id',
    ],
    'params' => [
      'is_active' => 1,
    ],
  ];
}
