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
 * APIv3 for mailing groups.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Create mailing group.
 *
 * @param array $params
 *
 * @return array
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_mailing_group_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Get mailing group.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_group_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete mailing group.
 *
 * @param array $params
 *
 * @return array
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_mailing_group_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
