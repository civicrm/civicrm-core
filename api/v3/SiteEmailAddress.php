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
 * APIv3 for CiviCRM SiteEmailAddress, mostly for the sake of backward-compatability.
 *
 * @see \Civi\API\Subscriber\SiteEmailLegacyOptionValueAdapter
 *
 * @package CiviCRM_APIv3
 */

/**
 * Add or update a SiteEmailAddress.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_site_email_address_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Deletes an existing SiteEmailAddress.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_site_email_address_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Retrieve one or more SiteEmailAddress.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_site_email_address_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
