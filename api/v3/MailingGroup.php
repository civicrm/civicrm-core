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
 * APIv3 functions for registering/processing mailing group events.
 *
 * @deprecated
 * @package CiviCRM_APIv3
 */

/**
 * Declare deprecated functions.
 *
 * @deprecated api notice
 * @return string
 *   to indicate this entire api entity is deprecated
 */
function _civicrm_api3_mailing_group_deprecation() {
  $message = 'This action is deprecated. Use the mailing_event API instead.';
  return [
    'event_unsubscribe' => $message,
    'event_domain_unsubscribe' => $message,
    'event_resubscribe' => $message,
    'event_subscribe' => $message,
  ];
}

/**
 * Handle an unsubscribe event.
 *
 * @deprecated
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_group_event_unsubscribe($params) {
  return civicrm_api('mailing_event_unsubscribe', 'create', $params);
}

/**
 * Handle a site-level unsubscribe event.
 *
 * @deprecated
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_group_event_domain_unsubscribe($params) {
  $params['org_unsubscribe'] = 1;
  return civicrm_api('mailing_event_unsubscribe', 'create', $params);
}

/**
 * Handle a re-subscription event.
 *
 * @deprecated
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_group_event_resubscribe($params) {
  return civicrm_api('mailing_event_resubscribe', 'create', $params);
}

/**
 * Handle a subscription event.
 *
 * @deprecated
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_group_event_subscribe($params) {
  return civicrm_api('mailing_event_subscribe', 'create', $params);
}

/**
 * Create mailing group.
 *
 * @param array $params
 *
 * @return array
 * @throws \API_Exception
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
 * @throws \API_Exception
 */
function civicrm_api3_mailing_group_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
