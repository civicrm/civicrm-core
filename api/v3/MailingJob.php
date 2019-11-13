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
 *
 * APIv3 functions for registering/processing mailing jobs.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Handle creation of a MailingJob for a Mailing.
 *
 * @param array $params
 *
 * @return array
 * @throws \API_Exception
 */
function civicrm_api3_mailing_job_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'MailingJob');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_mailing_job_create_spec(&$params) {
  $params['status']['api.default'] = 'Scheduled';
  $params['scheduled_date']['api.default'] = 'now';
  $params['is_test']['api.default'] = 0;
}

/**
 * Returns array of Mailing Jobs matching a set of one or more group properties.
 *
 * @param array $params
 *
 * @return array
 *   API return Array of matching mailing jobs.
 */
function civicrm_api3_mailing_job_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'MailingJob');
}

/**
 * Handle deletion of a Mailing Job for a Mailing.
 *
 * @param array $params
 *
 * @return array
 * @throws \API_Exception
 */
function civicrm_api3_mailing_job_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'MailingJob');
}
