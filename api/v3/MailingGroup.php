<?php
// $Id$

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 *
 * APIv3 functions for registering/processing mailing group events.
 *
 * @package CiviCRM_APIv3
 * @subpackage API_MailerGroup
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Files required for this package
 */



require_once 'CRM/Contact/BAO/Group.php';
require_once 'CRM/Mailing/Event/BAO/Queue.php';
require_once 'CRM/Mailing/Event/BAO/Subscribe.php';
require_once 'CRM/Mailing/Event/BAO/Unsubscribe.php';
require_once 'CRM/Mailing/Event/BAO/Resubscribe.php';
require_once 'CRM/Mailing/Event/BAO/TrackableURLOpen.php';

/**
 * Handle an unsubscribe event
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
 * Handle a site-level unsubscribe event
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
 * Handle a resubscription event
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
 * Handle a subscription event
 * @deprecated
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_group_event_subscribe($params) {
  return civicrm_api('mailing_event_subscribe', 'create', $params);
}

function civicrm_api3_mailing_group_getfields($params) {
  $dao = _civicrm_api3_get_DAO('Subscribe');
  $file = str_replace('_', '/', $dao) . ".php";
  require_once ($file);
  $d = new $dao();
  $fields = $d->fields();
  $d->free();

  $dao = _civicrm_api3_get_DAO('Unsubscribe');
  $file = str_replace('_', '/', $dao) . ".php";
  require_once ($file);
  $d = new $dao();
  $fields = $fields + $d->fields();
  $d->free();

  return civicrm_api3_create_success($fields);
}

