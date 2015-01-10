<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 * @return array
   *   API Success Array
 */
function civicrm_api3_mailing_create($params, $ids = array()) {
  if (CRM_Mailing_Info::workflowEnabled()) {
    if (!CRM_Core_Permission::check('create mailings')) {
      throw new \Civi\API\Exception\UnauthorizedException("This system uses advanced CiviMail workflows which require additional permissions");
    }
    if (!CRM_Core_Permission::check('schedule mailings')) {
      unset($params['scheduled_date']);
      unset($params['scheduled_id']);
    }
    if (!CRM_Core_Permission::check('approve mailings')) {
      unset($params['approval_date']);
      unset($params['approver_id']);
      unset($params['approval_status_id']);
      unset($params['approval_note']);
    }
  }
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

function civicrm_api3_mailing_get_token($params) {
  if (!array_key_exists("usage", $params)) {
    throw new API_Exception('Mandatory keys missing from params array: entity');
  }

  $tokens = CRM_Core_SelectValues::contactTokens();
  switch ($params['usage']) {
    case 'Mailing':
      $tokens = array_merge(CRM_Core_SelectValues::mailingTokens(), $tokens);
      break;

    case 'ScheduleEventReminder':
      $tokens = array_merge(CRM_Core_SelectValues::activityTokens(), $tokens);
      $tokens = array_merge(CRM_Core_SelectValues::eventTokens(), $tokens);
      $tokens = array_merge(CRM_Core_SelectValues::membershipTokens(), $tokens);
      break;

    case 'ManageEventScheduleReminder':
      $tokens = array_merge(CRM_Core_SelectValues::eventTokens(), $tokens);
      break;
  }

  return CRM_Utils_Token::formatTokensForDisplay($tokens);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params
 *   Array or parameters determined by getfields.
 */
function _civicrm_api3_mailing_create_spec(&$params) {
  $params['name']['api.required'] = 1;
  $params['subject']['api.required'] = 1;
  $params['created_id']['api.required'] = 1;
  $params['created_id']['api.default'] = 'user_contact_id';
  $params['api.mailing_job.create']['api.default'] = 1;
  $params['api.mailing_job.create']['title'] = 'Schedule Mailing?';
}

/**
 * Handle a delete event.
 *
 * @param array $params
 * @param array $ids
 *
 * @return array
   *   API Success Array
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

function _civicrm_api3_mailing_submit_spec(&$spec) {
  $mailingFields = CRM_Mailing_DAO_Mailing::fields();
  $spec['id'] = $mailingFields['id'];
  $spec['scheduled_date'] = $mailingFields['scheduled_date'];
  $spec['approval_date'] = $mailingFields['approval_date'];
  $spec['approval_status_id'] = $mailingFields['approval_status_id'];
  $spec['approval_note'] = $mailingFields['approval_note'];
  // _skip_evil_bao_auto_recipients_: bool
}

/**
 * @param array $params
 * @return array
 * @throws API_Exception
 */
function civicrm_api3_mailing_submit($params) {
  civicrm_api3_verify_mandatory($params, 'CRM_Mailing_DAO_Mailing', array('id'));

  if (!isset($params['scheduled_date']) && !isset($updateParams['approval_date'])) {
    throw new API_Exception("Missing parameter scheduled_date and/or approval_date");
  }
  if (!is_numeric(CRM_Core_Session::getLoggedInContactID())) {
    throw new API_Exception("Failed to determine current user");
  }

  $updateParams = array();
  $updateParams['id'] = $params['id'];

  // the BAO will autocreate the job
  $updateParams['api.mailing_job.create'] = 0; // note: exact match to API default

  // note: we'll pass along scheduling/approval fields, but they may get ignored
  // if we don't have permission
  if (isset($params['scheduled_date'])) {
    $updateParams['scheduled_date'] = $params['scheduled_date'];
    $updateParams['scheduled_id'] = CRM_Core_Session::getLoggedInContactID();
  }
  if (isset($params['approval_date'])) {
    $updateParams['approval_date'] = $params['approval_date'];
    $updateParams['approver_id'] = CRM_Core_Session::getLoggedInContactID();
    $updateParams['approval_status_id'] = CRM_Utils_Array::value('approval_status_id', $updateParams, CRM_Core_OptionGroup::getDefaultValue('mail_approval_status'));
  }
  if (isset($params['approval_note'])) {
    $updateParams['approval_note'] = $params['approval_note'];
  }
  if (isset($params['_skip_evil_bao_auto_recipients_'])) {
    $updateParams['_skip_evil_bao_auto_recipients_'] = $params['_skip_evil_bao_auto_recipients_'];
  }

  $updateParams['options']['reload'] = 1;
  return civicrm_api3('Mailing', 'create', $updateParams);
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
 * @param array $params
 *   Array or parameters determined by getfields.
 */
function _civicrm_api3_mailing_event_bounce_spec(&$params) {
  $params['job_id']['api.required'] = 1;
  $params['job_id']['title'] = 'Job ID';
  $params['event_queue_id']['api.required'] = 1;
  $params['event_queue_id']['title'] = 'Event Queue ID';
  $params['hash']['api.required'] = 1;
  $params['hash']['title'] = 'Hash';
  $params['body']['api.required'] = 1;
  $params['body']['title'] = 'Body';
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
 * @deprecated api notice
 * @return array
   *   of deprecated actions
 */
function _civicrm_api3_mailing_deprecation() {
  return array('event_confirm' => 'Mailing api "event_confirm" action is deprecated. Use the mailing_event_confirm api instead.');
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
 * @param array $params
 *   Array or parameters determined by getfields.
 */
function _civicrm_api3_mailing_event_reply_spec(&$params) {
  $params['job_id']['api.required'] = 1;
  $params['job_id']['title'] = 'Job ID';
  $params['event_queue_id']['api.required'] = 1;
  $params['event_queue_id']['title'] = 'Event Queue ID';
  $params['hash']['api.required'] = 1;
  $params['hash']['title'] = 'Hash';
  $params['replyTo']['api.required'] = 0;
  $params['replyTo']['title'] = 'Reply To';//doesn't really explain adequately
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
 * @param array $params
 *   Array or parameters determined by getfields.
 */
function _civicrm_api3_mailing_event_forward_spec(&$params) {
  $params['job_id']['api.required'] = 1;
  $params['job_id']['title'] = 'Job ID';
  $params['event_queue_id']['api.required'] = 1;
  $params['event_queue_id']['title'] = 'Event Queue ID';
  $params['hash']['api.required'] = 1;
  $params['hash']['title'] = 'Hash';
  $params['email']['api.required'] = 1;
  $params['email']['title'] = 'Forwarded to Email';
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

  $details = CRM_Utils_Token::getTokenDetails($mailingParams, $returnProperties, TRUE, TRUE, NULL, $mailing->getFlattenedTokens());

  $mime = &$mailing->compose(NULL, NULL, NULL, $session->get('userID'), $fromEmail, $fromEmail,
    TRUE, $details[0][$contactID], $attachments
  );

  return civicrm_api3_create_success(array(
    'id' => $params['id'],
    'contact_id' => $contactID,
    'subject' => $mime->_headers['Subject'],
    'body_html' => $mime->getHTMLBody(),
    'body_text' => $mime->getTXTBody(),
  ));
}

function _civicrm_api3_mailing_send_test_spec(&$spec) {
  $spec['test_group']['title'] = 'Test Group ID';
  $spec['test_email']['title'] = 'Test Email Address';
}

function civicrm_api3_mailing_send_test($params) {
  if (!array_key_exists('test_group', $params) && !array_key_exists('test_email', $params)) {
    throw new API_Exception("Mandatory key(s) missing from params array: test_group and/or test_email field are required");
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
    $query = CRM_Utils_SQL_Select::from('civicrm_email e')
        ->select(array('e.id', 'e.contact_id', 'e.email'))
        ->join('c', 'INNER JOIN civicrm_contact c ON e.contact_id = c.id')
        ->where('e.email IN (@emails)', array('@emails' => $testEmailParams['emails']))
        ->where('e.on_hold = 0')
        ->where('c.is_opt_out = 0')
        ->where('c.do_not_email = 0')
        ->where('c.is_deceased = 0')
        ->groupBy('e.id')
        ->orderBy(array('e.is_bulkmail DESC', 'e.is_primary DESC'))
        ->toSQL();
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
          array(
            'contact_type' => 'Individual',
            'email' => $email,
            'api.Email.get' => array('return' => 'id'),
          )
        );
        $contactId = $contact['id'];
        $emailId   = $contact['values'][$contactId]['api.Email.get']['id'];
      }
      civicrm_api3('MailingEventQueue', 'create',
        array(
          'job_id' => $job['id'],
          'email_id' => $emailId,
          'contact_id' => $contactId,
        )
      );
    }
  }

  $isComplete = FALSE;
  $config = CRM_Core_Config::singleton();
  $mailerJobSize = (property_exists($config, 'mailerJobSize')) ? $config->mailerJobSize : NULL;
  while (!$isComplete) {
    // Q: In CRM_Mailing_BAO_Mailing::processQueue(), the three runJobs*()
    // functions are all called. Why does Mailing.send_test only call one?
    // CRM_Mailing_BAO_MailingJob::runJobs_pre($mailerJobSize, NULL);
    $isComplete = CRM_Mailing_BAO_MailingJob::runJobs($testEmailParams);
    // CRM_Mailing_BAO_MailingJob::runJobs_post(NULL);
  }

  //return delivered mail info
  $mailDelivered = CRM_Mailing_Event_BAO_Delivered::getRows($params['mailing_id'], $job['id'], TRUE, NULL, NULL, NULL, TRUE);

  return civicrm_api3_create_success($mailDelivered);
}

/**
 * Adjust Metadata for send_mail action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params
 *   Array or parameters determined by getfields.
 */
function _civicrm_api3_mailing_stats_spec(&$params) {
  $params['date']['api.default'] = 'now';
  $params['date']['title'] = 'Date';
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
          $detail =>  CRM_Mailing_Event_BAO_Delivered::getTotalCount($params['mailing_id'], $params['job_id'], FALSE, $params['date']),
        );
        break;

      case 'Bounces':
        $stats[$params['mailing_id']] += array(
          $detail =>  CRM_Mailing_Event_BAO_Bounce::getTotalCount($params['mailing_id'], $params['job_id'], FALSE, $params['date']),
        );
        break;

      case 'Unsubscribers':
        $stats[$params['mailing_id']] += array(
          $detail =>  CRM_Mailing_Event_BAO_Unsubscribe::getTotalCount($params['mailing_id'], $params['job_id'], FALSE, NULL, $params['date']),
        );
        break;

      case 'Unique Clicks':
        $stats[$params['mailing_id']] += array(
          $detail =>  CRM_Mailing_Event_BAO_TrackableURLOpen::getTotalCount($params['mailing_id'], $params['job_id'], FALSE, NULL, $params['date']),
        );
        break;

      case 'Opened':
        $stats[$params['mailing_id']] += array(
          $detail =>  CRM_Mailing_Event_BAO_Opened::getTotalCount($params['mailing_id'], $params['job_id'], FALSE, $params['date']),
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
