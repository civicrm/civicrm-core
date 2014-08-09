<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * APIv3 functions for registering/processing mailing events.
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Mailing
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * Files required for this package
 */

/**
 * Handle a create event.
 *
 * @param array $params
 * @param array $ids
 *
 * @return array API Success Array
 */
function civicrm_api3_mailing_create($params, $ids = array()) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_mailing_create_spec(&$params) {
  $params['name']['api.required'] = 1;
  $params['subject']['api.required'] = 1;
  // should be able to default to 'user_contact_id' & have it work but it didn't work in test so
  // making required for simplicity
  $params['created_id']['api.required'] = 1;
  $params['api.mailing_job.create']['api.default'] = 1;
}

/**
 * Handle a delete event.
 *
 * @param array $params
 * @param array $ids
 *
 * @return array API Success Array
 */
function civicrm_api3_mailing_delete($params, $ids = array()) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}


/**
 * Handle a get event.
 *
 * @param array $params
 * @return array
 */
function civicrm_api3_mailing_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Process a bounce event by passing through to the BAOs.
 *
 * @param array $params
 *
 * @throws API_Exception
 * @return array
 */
function civicrm_api3_mailing_event_bounce($params) {

  $body = $params['body'];
  unset($params['body']);

  $params += CRM_Mailing_BAO_BouncePattern::match($body);

  if (CRM_Mailing_Event_BAO_Bounce::create($params)) {
    return civicrm_api3_create_success($params);
  }
  else {
    throw new API_Exception(ts('Queue event could not be found'),'no_queue_event
      ');
  }
}

/**
 * Adjust Metadata for bounce_spec action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_mailing_event_bounce_spec(&$params) {
  $params['job_id']['api.required'] = 1;
  $params['event_queue_id']['api.required'] = 1;
  $params['hash']['api.required'] = 1;
  $params['body']['api.required'] = 1;
}

/**
 * Handle a confirm event
 * @deprecated
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_event_confirm($params) {
  return civicrm_api('mailing_event_confirm', 'create', $params);
}

/**
 * Handle a reply event
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_event_reply($params) {
  $job       = $params['job_id'];
  $queue     = $params['event_queue_id'];
  $hash      = $params['hash'];
  $replyto   = $params['replyTo'];
  $bodyTxt   = CRM_Utils_Array::value('bodyTxt', $params);
  $bodyHTML  = CRM_Utils_Array::value('bodyHTML', $params);
  $fullEmail = CRM_Utils_Array::value('fullEmail', $params);

  $mailing = CRM_Mailing_Event_BAO_Reply::reply($job, $queue, $hash, $replyto);

  if (empty($mailing)) {
    return civicrm_api3_create_error('Queue event could not be found');
  }

  CRM_Mailing_Event_BAO_Reply::send($queue, $mailing, $bodyTxt, $replyto, $bodyHTML, $fullEmail);

  return civicrm_api3_create_success($params);
}

/**
 * Adjust Metadata for event_reply action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_mailing_event_reply_spec(&$params) {
  $params['job_id']['api.required'] = 1;
  $params['event_queue_id']['api.required'] = 1;
  $params['hash']['api.required'] = 1;
  $params['replyTo']['api.required'] = 0;
}

/**
 * Handle a forward event
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_event_forward($params) {
  $job       = $params['job_id'];
  $queue     = $params['event_queue_id'];
  $hash      = $params['hash'];
  $email     = $params['email'];
  $fromEmail = CRM_Utils_Array::value('fromEmail', $params);
  $params    = CRM_Utils_Array::value('params', $params);

  $forward = CRM_Mailing_Event_BAO_Forward::forward($job, $queue, $hash, $email, $fromEmail, $params);

  if ($forward) {
    return civicrm_api3_create_success($params);
  }

  return civicrm_api3_create_error('Queue event could not be found');
}

/**
 * Adjust Metadata for event_forward action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_mailing_event_forward_spec(&$params) {
  $params['job_id']['api.required'] = 1;
  $params['event_queue_id']['api.required'] = 1;
  $params['hash']['api.required'] = 1;
  $params['email']['api.required'] = 1;
}

/**
 * Handle a click event
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_event_click($params) {

  civicrm_api3_verify_mandatory($params,
    'CRM_Mailing_Event_DAO_TrackableURLOpen',
    array('event_queue_id', 'url_id'),
    FALSE
  );

  $url_id = $params['url_id'];
  $queue = $params['event_queue_id'];

  $url = CRM_Mailing_Event_BAO_TrackableURLOpen::track($queue, $url_id);

  $values             = array();
  $values['url']      = $url;
  $values['is_error'] = 0;

  return civicrm_api3_create_success($values);
}

/**
 * Handle an open event
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_event_open($params) {

  civicrm_api3_verify_mandatory($params,
    'CRM_Mailing_Event_DAO_Opened',
    array('event_queue_id'),
    FALSE
  );

  $queue = $params['event_queue_id'];
  $success = CRM_Mailing_Event_BAO_Opened::open($queue);

  if (!$success) {
    return civicrm_api3_create_error('mailing open event failed');
  }

  return civicrm_api3_create_success($params);
}

function civicrm_api3_mailing_preview($params) {
  civicrm_api3_verify_mandatory($params,
    'CRM_Mailing_DAO_Mailing',
    array('id'),
    FALSE
  );

  $fromEmail = NULL;
  if (!empty($params['from_email'])) {
    $fromEmail = $params['from_email'];
  }

  $session = CRM_Core_Session::singleton();
  $mailing = new CRM_Mailing_BAO_Mailing();
  $mailing->id = $params['id'];
  $mailing->find(TRUE);

  CRM_Mailing_BAO_Mailing::tokenReplace($mailing);

  // get and format attachments
  $attachments = CRM_Core_BAO_File::getEntityFile('civicrm_mailing', $mailing->id);

  $returnProperties = $mailing->getReturnProperties();
  $contactID = CRM_Utils_Array::value('contact_id', $params);
  if (!$contactID) {
    $contactID = $session->get('userID');
  }
  $mailingParams = array('contact_id' => $contactID);

  $details = CRM_Utils_Token::getTokenDetails($mailingParams, $returnProperties, TRUE, TRUE, NULL, $mailing->getFlattenedToken());

  $mime = &$mailing->compose(NULL, NULL, NULL, $session->get('userID'), $fromEmail, $fromEmail,
    TRUE, $details[0][$contactID], $attachments
  );

  return civicrm_api3_create_success(array('subject' => $mime->_headers['Subject'], 'html' => $mime->getHTMLBody(), 'text' => $mime->getTXTBody()));
}

function civicrm_api3_mailing_send_test($params) {
  if (!array_key_exists('test_group', $params) && !array_key_exists('test_email', $params)) {
    throw new API_Exception("Mandatory key(s) missing from params array: test_group and/or test_email field are required" );
  }
  civicrm_api3_verify_mandatory($params,
    'CRM_Mailing_DAO_MailingJob',
    array('mailing_id'),
    FALSE
  );

  $testEmailParams = _civicrm_api3_generic_replace_base_params($params);
  $testEmailParams['is_test'] = 1;
  $job = civicrm_api3('MailingJob', 'create', $testEmailParams);
  $testEmailParams['job_id'] = $job['id'];
  $testEmailParams['emails'] = explode(',', $testEmailParams['test_email']);
  if (!empty($params['test_email'])) {
    $query = "
SELECT     e.id, e.contact_id, e.email
FROM       civicrm_email e
INNER JOIN civicrm_contact c ON e.contact_id = c.id
WHERE      e.email IN ('" . implode("','", $testEmailParams['emails']) . "')
AND        e.on_hold = 0
AND        c.is_opt_out = 0
AND        c.do_not_email = 0
AND        c.is_deceased = 0
GROUP BY   e.id
ORDER BY   e.is_bulkmail DESC, e.is_primary DESC
";
    $dao = CRM_Core_DAO::executeQuery($query);
    $emailDetail = array();
    // fetch contact_id and email id for all existing emails
    while ($dao->fetch()) {
      $emailDetail[$dao->email] = array(
        'contact_id' => $dao->contact_id,
        'email_id' => $dao->id,
      );
    }
    $dao->free();
    foreach ($testEmailParams['emails'] as $key => $email) {
      $email = trim($email);
      $contactId = $emailId = NULL;
      if (array_key_exists($email, $emailDetail)) {
        $emailId = $emailDetail[$email]['email_id'];
        $contactId = $emailDetail[$email]['contact_id'];
      }
      if (!$contactId) {
        //create new contact.
        $contact   = civicrm_api3('Contact', 'create',
          array('contact_type' => 'Individual',
            'email' => $email,
            'api.Email.get' => array('return' => 'id')
          )
        );
        $contactId = $contact['id'];
        $emailId   = $contact['values'][$contactId]['api.Email.get']['id'];
      }
      civicrm_api3('MailingEventQueue', 'create',
        array('job_id' => $job['id'],
          'email_id' => $emailId,
          'contact_id' => $contactId
        )
      );
    }
  }

  $isComplete = FALSE;
  while (!$isComplete) {
    $isComplete = CRM_Mailing_BAO_MailingJob::runJobs($testEmailParams);
  }

  //return delivered mail info
  $mailDelivered = CRM_Mailing_Event_BAO_Delivered::getRows($params['mailing_id'], $job['id'], TRUE, NULL, NULL, NULL, TRUE);

  return civicrm_api3_create_success($mailDelivered);
}

/**
 * Adjust Metadata for send_mail action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_mailing_stats_spec(&$params) {
  $params['date']['api.default'] = 'now';
}

function civicrm_api3_mailing_stats($params) {
  civicrm_api3_verify_mandatory($params,
    'CRM_Mailing_DAO_MailingJob',
    array('mailing_id'),
    FALSE
  );

  if ($params['date'] == 'now') {
    $params['date'] = date('YmdHis');
  }
  else {
    $params['date'] = CRM_Utils_Date::processDate($params['date'] . ' ' . $params['date_time']);
  }

  $stats[$params['mailing_id']] = array();
  if (empty($params['job_id'])) {
    $params['job_id'] = NULL;
  }
  foreach (array('Delivered', 'Bounces', 'Unsubscribers', 'Unique Clicks', 'Opened') as $detail) {
    switch ($detail) {
      case 'Delivered':
        $stats[$params['mailing_id']] += array(
          $detail =>  CRM_Mailing_Event_BAO_Delivered::getTotalCount($params['mailing_id'], $params['job_id'], FALSE, $params['date'])
        );
        break;
      case 'Bounces':
        $stats[$params['mailing_id']] += array(
          $detail =>  CRM_Mailing_Event_BAO_Bounce::getTotalCount($params['mailing_id'], $params['job_id'], FALSE, $params['date'])
        );
        break;
      case 'Unsubscribers':
        $stats[$params['mailing_id']] += array(
          $detail =>  CRM_Mailing_Event_BAO_Unsubscribe::getTotalCount($params['mailing_id'], $params['job_id'], FALSE, NULL, $params['date'])
        );
        break;
      case 'Unique Clicks':
        $stats[$params['mailing_id']] += array(
          $detail =>  CRM_Mailing_Event_BAO_TrackableURLOpen::getTotalCount($params['mailing_id'], $params['job_id'], FALSE, NULL, $params['date'])
        );
        break;
      case 'Opened':
        $stats[$params['mailing_id']] += array(
          $detail =>  CRM_Mailing_Event_BAO_Opened::getTotalCount($params['mailing_id'], $params['job_id'], FALSE, $params['date'])
        );
        break;
    }
  }
  return civicrm_api3_create_success($stats);
}

/**
 * Fix the reset dates on the email record based on when a mail was last delivered
 * We only consider mailings that were completed and finished in the last 3 to 7 days
 * Both the min and max days can be set via the params
 */
function civicrm_api3_mailing_update_email_resetdate($params) {
  CRM_Mailing_Event_BAO_Delivered::updateEmailResetDate(
    CRM_Utils_Array::value('minDays', $params, 3),
    CRM_Utils_Array::value('maxDays', $params, 3)
  );
  return civicrm_api3_create_success();
}
