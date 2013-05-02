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
 * APIv2 functions for registering/processing mailer events.
 *
 * @package CiviCRM_APIv2
 * @subpackage API_Mailer
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Files required for this package
 */


require_once 'api/v2/utils.php';

require_once 'CRM/Contact/BAO/Group.php';

require_once 'CRM/Mailing/BAO/BouncePattern.php';
require_once 'CRM/Mailing/Event/BAO/Bounce.php';
require_once 'CRM/Mailing/Event/BAO/Confirm.php';
require_once 'CRM/Mailing/Event/BAO/Opened.php';
require_once 'CRM/Mailing/Event/BAO/Queue.php';
require_once 'CRM/Mailing/Event/BAO/Reply.php';
require_once 'CRM/Mailing/Event/BAO/Subscribe.php';
require_once 'CRM/Mailing/Event/BAO/Unsubscribe.php';
require_once 'CRM/Mailing/Event/BAO/Resubscribe.php';
require_once 'CRM/Mailing/Event/BAO/Forward.php';
require_once 'CRM/Mailing/Event/BAO/TrackableURLOpen.php';

/**
 * Process a bounce event by passing through to the BAOs.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_mailer_event_bounce($params) {
  $errors = _civicrm_mailer_check_params($params, array('job_id', 'event_queue_id', 'hash', 'body'));

  if (!empty($errors)) {
    return $errors;
  }

  $body = $params['body'];
  unset($params['body']);

  $params += CRM_Mailing_BAO_BouncePattern::match($body);

  if (CRM_Mailing_Event_BAO_Bounce::create($params)) {
    return civicrm_create_success();
  }

  return civicrm_create_error(ts('Queue event could not be found'));
}

/**
 * Handle an unsubscribe event
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_mailer_event_unsubscribe($params) {
  $errors = _civicrm_mailer_check_params($params, array('job_id', 'event_queue_id', 'hash'));

  if (!empty($errors)) {
    return $errors;
  }

  $job   = $params['job_id'];
  $queue = $params['event_queue_id'];
  $hash  = $params['hash'];

  $groups = CRM_Mailing_Event_BAO_Unsubscribe::unsub_from_mailing($job, $queue, $hash);

  if (count($groups)) {
    CRM_Mailing_Event_BAO_Unsubscribe::send_unsub_response($queue, $groups, FALSE, $job);
    return civicrm_create_success();
  }

  return civicrm_create_error(ts('Queue event could not be found'));
}

/**
 * Handle a site-level unsubscribe event
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_mailer_event_domain_unsubscribe($params) {
  $errors = _civicrm_mailer_check_params($params, array('job_id', 'event_queue_id', 'hash'));

  if (!empty($errors)) {
    return $errors;
  }

  $job   = $params['job_id'];
  $queue = $params['event_queue_id'];
  $hash  = $params['hash'];

  $unsubs = CRM_Mailing_Event_BAO_Unsubscribe::unsub_from_domain($job, $queue, $hash);

  if (!$unsubs) {
    return civicrm_create_error(ts('Queue event could not be found'));
  }

  CRM_Mailing_Event_BAO_Unsubscribe::send_unsub_response($queue, NULL, TRUE, $job);
  return civicrm_create_success();
}

/**
 * Handle a resubscription event
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_mailer_event_resubscribe($params) {
  $errors = _civicrm_mailer_check_params($params, array('job_id', 'event_queue_id', 'hash'));

  if (!empty($errors)) {
    return $errors;
  }

  $job   = $params['job_id'];
  $queue = $params['event_queue_id'];
  $hash  = $params['hash'];

  $groups = CRM_Mailing_Event_BAO_Resubscribe::resub_to_mailing($job, $queue, $hash);

  if (count($groups)) {
    CRM_Mailing_Event_BAO_Resubscribe::send_resub_response($queue, $groups, FALSE, $job);
    return civicrm_create_success();
  }

  return civicrm_create_error(ts('Queue event could not be found'));
}

/**
 * Handle a subscription event
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_mailer_event_subscribe($params) {
  $errors = _civicrm_mailer_check_params($params, array('email', 'group_id'));

  if (!empty($errors)) {
    return $errors;
  }

  $email      = $params['email'];
  $group_id   = $params['group_id'];
  $contact_id = CRM_Utils_Array::value('contact_id', $params);

  $group            = new CRM_Contact_DAO_Group();
  $group->is_active = 1;
  $group->id        = (int)$group_id;
  if (!$group->find(TRUE)) {
    return civicrm_create_error(ts('Invalid Group id'));
  }

  $subscribe = CRM_Mailing_Event_BAO_Subscribe::subscribe($group_id, $email, $contact_id);

  if ($subscribe !== NULL) {
    /* Ask the contact for confirmation */


    $subscribe->send_confirm_request($email);

    $values = array();
    $values['contact_id'] = $subscribe->contact_id;
    $values['subscribe_id'] = $subscribe->id;
    $values['hash'] = $subscribe->hash;
    $values['is_error'] = 0;

    return $values;
  }

  return civicrm_create_error(ts('Subscription failed'));
}

/**
 * Handle a confirm event
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_mailer_event_confirm($params) {
  $errors = _civicrm_mailer_check_params($params, array('contact_id', 'subscribe_id', 'hash'));

  if (!empty($errors)) {
    return $errors;
  }

  $contact_id   = $params['contact_id'];
  $subscribe_id = $params['subscribe_id'];
  $hash         = $params['hash'];

  $confirm = CRM_Mailing_Event_BAO_Confirm::confirm($contact_id, $subscribe_id, $hash) !== FALSE;

  if (!$confirm) {
    return civicrm_create_error(ts('Confirmation failed'));
  }

  return civicrm_create_success();
}

/**
 * Handle a reply event
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_mailer_event_reply($params) {
  $errors = _civicrm_mailer_check_params($params, array('job_id', 'event_queue_id', 'hash', 'replyTo'));

  if (!empty($errors)) {
    return $errors;
  }

  // CRM-7333: we can’t require fullEmail for backwards compatibility, but we should require either it or bodyTxt
  if (empty($params['fullEmail']) and empty($params['bodyTxt'])) {
    return civicrm_create_error('Required parameter missing: either "fullEmail" or "bodyTxt" is required');
  }

  $job       = $params['job_id'];
  $queue     = $params['event_queue_id'];
  $hash      = $params['hash'];
  $bodyTxt   = $params['bodyTxt'];
  $replyto   = $params['replyTo'];
  $bodyHTML  = CRM_Utils_Array::value('bodyHTML', $params);
  $fullEmail = CRM_Utils_Array::value('fullEmail', $params);

  $mailing = CRM_Mailing_Event_BAO_Reply::reply($job, $queue, $hash, $replyto);

  if (empty($mailing)) {
    return civicrm_create_error(ts('Queue event could not be found'));
  }

  CRM_Mailing_Event_BAO_Reply::send($queue, $mailing, $bodyTxt, $replyto, $bodyHTML, $fullEmail);

  return civicrm_create_success();
}

/**
 * Handle a forward event
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_mailer_event_forward($params) {
  $errors = _civicrm_mailer_check_params($params, array('job_id', 'event_queue_id', 'hash', 'email'));

  if (!empty($errors)) {
    return $errors;
  }

  $job       = $params['job_id'];
  $queue     = $params['event_queue_id'];
  $hash      = $params['hash'];
  $email     = $params['email'];
  $fromEmail = CRM_Utils_Array::value('fromEmail', $params);
  $params    = CRM_Utils_Array::value('params', $params);

  $forward = CRM_Mailing_Event_BAO_Forward::forward($job, $queue, $hash, $email, $fromEmail, $params);

  if ($forward) {
    return civicrm_create_success();
  }

  return civicrm_create_error(ts('Queue event could not be found'));
}

/**
 * Handle a click event
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_mailer_event_click($params) {
  $errors = _civicrm_mailer_check_params($params, array('event_queue_id', 'url_id'));

  if (!empty($errors)) {
    return $errors;
  }

  $url_id = $params['url_id'];
  $queue = $params['event_queue_id'];

  $url = CRM_Mailing_Event_BAO_TrackableURLOpen::track($queue, $url_id);

  $values             = array();
  $values['url']      = $url;
  $values['is_error'] = 0;

  return $values;
}

/**
 * Handle an open event
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_mailer_event_open($params) {
  $errors = _civicrm_mailer_check_params($params, array('event_queue_id'));

  if (!empty($errors)) {
    return $errors;
  }

  $queue = $params['event_queue_id'];

  $success = CRM_Mailing_Event_BAO_Opened::open($queue);

  if (!$success) {
    return civicrm_create_error(ts('mailer open event failed'));
  }

  return civicrm_create_success();
}

/**
 * Helper function to check for required params
 *
 * @param array   $params       associated array of fields
 * @param array   $required     array of required fields
 *
 * @return array  $error        array with errors, null if none
 */
function _civicrm_mailer_check_params(&$params, $required) {
  // return error if we do not get any params
  if (empty($params)) {
    return civicrm_create_error(ts('Input Parameters empty'));
  }

  if (!is_array($params)) {
    return civicrm_create_error(ts('Input parameter is not an array'));
  }

  foreach ($required as $name) {
    if (!array_key_exists($name, $params) || !$params[$name]) {
      return civicrm_create_error(ts('Required parameter missing: "%1"', array(1 => $name)));
    }
  }

  return NULL;
}

