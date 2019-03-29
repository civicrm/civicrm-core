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
 *
 * APIv3 functions for registering/processing mailing events.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Handle a create event.
 *
 * @param array $params
 *
 * @return array
 *    API Success Array
 * @throws \API_Exception
 * @throws \Civi\API\Exception\UnauthorizedException
 */
function civicrm_api3_mailing_create($params) {
  if (isset($params['template_options']) && is_array($params['template_options'])) {
    $params['template_options'] = ($params['template_options'] === []) ? '{}' : json_encode($params['template_options']);
  }
  if (CRM_Mailing_Info::workflowEnabled()) {
    // Note: 'schedule mailings' and 'approve mailings' can update certain fields, but can't create.

    if (empty($params['id'])) {
      if (!CRM_Core_Permission::check('access CiviMail') && !CRM_Core_Permission::check('create mailings')) {
        throw new \Civi\API\Exception\UnauthorizedException("Cannot create new mailing. Required permission: 'access CiviMail' or 'create mailings'");
      }
    }

    $safeParams = [];
    $fieldPerms = CRM_Mailing_BAO_Mailing::getWorkflowFieldPerms();
    foreach (array_keys($params) as $field) {
      if (CRM_Core_Permission::check($fieldPerms[$field])) {
        $safeParams[$field] = $params[$field];
      }
    }
  }
  else {
    $safeParams = $params;
  }
  $timestampCheck = TRUE;
  if (!empty($params['id']) && !empty($params['modified_date'])) {
    $timestampCheck = _civicrm_api3_compare_timestamps($safeParams['modified_date'], $safeParams['id'], 'Mailing');
    unset($safeParams['modified_date']);
  }
  if (!$timestampCheck) {
    throw new API_Exception("Mailing has not been saved, Content maybe out of date, please refresh the page and try again");
  }

  // FlexMailer is a refactoring of CiviMail which provides new hooks/APIs/docs. If the sysadmin has opted to enable it, then use that instead of CiviMail.
  $safeParams['_evil_bao_validator_'] = \CRM_Utils_Constant::value('CIVICRM_FLEXMAILER_HACK_SENDABLE', 'CRM_Mailing_BAO_Mailing::checkSendable');
  $result = _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $safeParams, 'Mailing');
  return _civicrm_api3_mailing_get_formatResult($result);
}

/**
 * Get tokens for one or more entity type
 *
 * Output will be formatted either as a flat list,
 * or pass sequential=1 to retrieve as a hierarchy formatted for select2.
 *
 * @param array $params
 *   Should contain an array of entities to retrieve tokens for.
 *
 * @return array
 * @throws \API_Exception
 */
function civicrm_api3_mailing_gettokens($params) {
  $tokens = [];
  foreach ((array) $params['entity'] as $ent) {
    $func = lcfirst($ent) . 'Tokens';
    if (!method_exists('CRM_Core_SelectValues', $func)) {
      throw new API_Exception('Unknown token entity: ' . $ent);
    }
    $tokens = array_merge(CRM_Core_SelectValues::$func(), $tokens);
  }
  if (!empty($params['sequential'])) {
    $tokens = CRM_Utils_Token::formatTokensForDisplay($tokens);
  }
  return civicrm_api3_create_success($tokens, $params, 'Mailing', 'gettokens');
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_mailing_gettokens_spec(&$params) {
  $params['entity'] = [
    'api.default' => ['contact'],
    'api.required' => 1,
    'api.multiple' => 1,
    'title' => 'Entity',
    'options' => [],
  ];
  // Fetch a list of token functions and format to look like entity names
  foreach (get_class_methods('CRM_Core_SelectValues') as $func) {
    if (strpos($func, 'Tokens')) {
      $ent = ucfirst(str_replace('Tokens', '', $func));
      $params['entity']['options'][$ent] = $ent;
    }
  }
}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_mailing_create_spec(&$params) {
  $params['created_id']['api.default'] = 'user_contact_id';

  $params['override_verp']['api.default'] = !CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME, 'track_civimail_replies');
  $params['visibility']['api.default'] = 'Public Pages';
  $params['dedupe_email']['api.default'] = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME, 'dedupe_email_default');

  $params['forward_replies']['api.default'] = FALSE;
  $params['auto_responder']['api.default'] = FALSE;
  $params['open_tracking']['api.default'] = TRUE;
  $params['url_tracking']['api.default'] = TRUE;

  $params['header_id']['api.default'] = CRM_Mailing_PseudoConstant::defaultComponent('Header', '');
  $params['footer_id']['api.default'] = CRM_Mailing_PseudoConstant::defaultComponent('Footer', '');
  $params['optout_id']['api.default'] = CRM_Mailing_PseudoConstant::defaultComponent('OptOut', '');
  $params['reply_id']['api.default'] = CRM_Mailing_PseudoConstant::defaultComponent('Reply', '');
  $params['resubscribe_id']['api.default'] = CRM_Mailing_PseudoConstant::defaultComponent('Resubscribe', '');
  $params['unsubscribe_id']['api.default'] = CRM_Mailing_PseudoConstant::defaultComponent('Unsubscribe', '');
  $params['mailing_type']['api.default'] = 'standalone';
  $defaultAddress = CRM_Core_BAO_Domain::getNameAndEmail(TRUE, TRUE);
  foreach ($defaultAddress as $value) {
    if (preg_match('/"(.*)" <(.*)>/', $value, $match)) {
      $params['from_email']['api.default'] = $match[2];
      $params['from_name']['api.default'] = $match[1];
    }
  }
}

/**
 * Adjust metadata for clone spec action.
 *
 * @param array $spec
 */
function _civicrm_api3_mailing_clone_spec(&$spec) {
  $mailingFields = CRM_Mailing_DAO_Mailing::fields();
  $spec['id'] = $mailingFields['id'];
  $spec['id']['api.required'] = 1;
}

/**
 * Clone mailing.
 *
 * @param array $params
 *
 * @return array
 * @throws \CiviCRM_API3_Exception
 */
function civicrm_api3_mailing_clone($params) {
  $BLACKLIST = [
    'id',
    'is_completed',
    'created_id',
    'created_date',
    'scheduled_id',
    'scheduled_date',
    'approver_id',
    'approval_date',
    'approval_status_id',
    'approval_note',
    'is_archived',
    'hash',
    'mailing_type',
  ];

  $get = civicrm_api3('Mailing', 'getsingle', ['id' => $params['id']]);

  $newParams = [];
  $newParams['debug'] = CRM_Utils_Array::value('debug', $params);
  $newParams['groups']['include'] = [];
  $newParams['groups']['exclude'] = [];
  $newParams['mailings']['include'] = [];
  $newParams['mailings']['exclude'] = [];
  foreach ($get as $field => $value) {
    if (!in_array($field, $BLACKLIST)) {
      $newParams[$field] = $value;
    }
  }

  $dao = new CRM_Mailing_DAO_MailingGroup();
  $dao->mailing_id = $params['id'];
  $dao->find();
  while ($dao->fetch()) {
    // CRM-11431; account for multi-lingual
    $entity = (substr($dao->entity_table, 0, 15) == 'civicrm_mailing') ? 'mailings' : 'groups';
    $newParams[$entity][strtolower($dao->group_type)][] = $dao->entity_id;
  }

  return civicrm_api3('Mailing', 'create', $newParams);
}

/**
 * Handle a delete event.
 *
 * @param array $params
 *
 * @return array
 *   API Success Array
 */
function civicrm_api3_mailing_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Handle a get event.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_get($params) {
  $result = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'Mailing');
  return _civicrm_api3_mailing_get_formatResult($result);
}

/**
 * Format definition.
 *
 * @param array $result
 *
 * @return array
 * @throws \CRM_Core_Exception
 */
function _civicrm_api3_mailing_get_formatResult($result) {
  if (isset($result['values']) && is_array($result['values'])) {
    foreach ($result['values'] as $key => $caseType) {
      if (isset($result['values'][$key]['template_options']) && is_string($result['values'][$key]['template_options'])) {
        $result['values'][$key]['template_options'] = json_decode($result['values'][$key]['template_options'], TRUE);
      }
    }
  }
  return $result;
}

/**
 * Adjust metadata for mailing submit api function.
 *
 * @param array $spec
 */
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
 * Mailing submit.
 *
 * @param array $params
 *
 * @return array
 * @throws API_Exception
 */
function civicrm_api3_mailing_submit($params) {
  civicrm_api3_verify_mandatory($params, 'CRM_Mailing_DAO_Mailing', ['id']);

  if (!isset($params['scheduled_date']) && !isset($updateParams['approval_date'])) {
    throw new API_Exception("Missing parameter scheduled_date and/or approval_date");
  }
  if (!is_numeric(CRM_Core_Session::getLoggedInContactID())) {
    throw new API_Exception("Failed to determine current user");
  }

  $updateParams = [];
  $updateParams['id'] = $params['id'];

  // Note: we'll pass along scheduling/approval fields, but they may get ignored
  // if we don't have permission.
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
    throw new API_Exception(ts('Queue event could not be found'), 'no_queue_event');
  }
}

/**
 * Adjust Metadata for bounce_spec action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
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
 * Handle a confirm event.
 *
 * @deprecated
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_event_confirm($params) {
  return civicrm_api('MailingEventConfirm', 'create', $params);
}

/**
 * Declare deprecated functions.
 *
 * @deprecated api notice
 * @return array
 *   Array of deprecated actions
 */
function _civicrm_api3_mailing_deprecation() {
  return ['event_confirm' => 'Mailing api "event_confirm" action is deprecated. Use the mailing_event_confirm api instead.'];
}

/**
 * Handle a reply event.
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
 * Adjust Metadata for event_reply action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
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
 * Handle a forward event.
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
 * Adjust Metadata for event_forward action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
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
 * Handle a click event.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_event_click($params) {
  civicrm_api3_verify_mandatory($params,
    'CRM_Mailing_Event_DAO_TrackableURLOpen',
    ['event_queue_id', 'url_id'],
    FALSE
  );

  $url_id = $params['url_id'];
  $queue = $params['event_queue_id'];

  $url = CRM_Mailing_Event_BAO_TrackableURLOpen::track($queue, $url_id);

  $values             = [];
  $values['url']      = $url;
  $values['is_error'] = 0;

  return civicrm_api3_create_success($values);
}

/**
 * Handle an open event.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_event_open($params) {

  civicrm_api3_verify_mandatory($params,
    'CRM_Mailing_Event_DAO_Opened',
    ['event_queue_id'],
    FALSE
  );

  $queue = $params['event_queue_id'];
  $success = CRM_Mailing_Event_BAO_Opened::open($queue);

  if (!$success) {
    return civicrm_api3_create_error('mailing open event failed');
  }

  return civicrm_api3_create_success($params);
}

/**
 * Preview mailing.
 *
 * @param array $params
 *   Array per getfields metadata.
 *
 * @return array
 * @throws \API_Exception
 */
function civicrm_api3_mailing_preview($params) {
  $fromEmail = NULL;
  if (!empty($params['from_email'])) {
    $fromEmail = $params['from_email'];
  }

  $mailing = new CRM_Mailing_BAO_Mailing();
  $mailingID = CRM_Utils_Array::value('id', $params);
  if ($mailingID) {
    $mailing->id = $mailingID;
    $mailing->find(TRUE);
  }
  else {
    $mailing->copyValues($params);
  }

  $session = CRM_Core_Session::singleton();

  CRM_Mailing_BAO_Mailing::tokenReplace($mailing);

  // get and format attachments
  $attachments = CRM_Core_BAO_File::getEntityFile('civicrm_mailing', $mailing->id);

  $returnProperties = $mailing->getReturnProperties();
  $contactID = CRM_Utils_Array::value('contact_id', $params);
  if (!$contactID) {
    $contactID = $session->get('userID');
  }
  $mailingParams = ['contact_id' => $contactID];

  $details = CRM_Utils_Token::getTokenDetails($mailingParams, $returnProperties, TRUE, TRUE, NULL, $mailing->getFlattenedTokens());

  $mime = $mailing->compose(NULL, NULL, NULL, $session->get('userID'), $fromEmail, $fromEmail,
    TRUE, $details[0][$contactID], $attachments
  );

  return civicrm_api3_create_success([
    'id' => $params['id'],
    'contact_id' => $contactID,
    'subject' => $mime->headers()['Subject'],
    'body_html' => $mime->getHTMLBody(),
    'body_text' => $mime->getTXTBody(),
  ]);
}

/**
 * Adjust metadata for send test function.
 *
 * @param array $spec
 */
function _civicrm_api3_mailing_send_test_spec(&$spec) {
  $spec['test_group']['title'] = 'Test Group ID';
  $spec['test_email']['title'] = 'Test Email Address';
  $spec['mailing_id']['api.required'] = TRUE;
  $spec['mailing_id']['title'] = ts('Mailing Id');
}

/**
 * Send test mailing.
 *
 * @param array $params
 *
 * @return array
 * @throws \API_Exception
 * @throws \CiviCRM_API3_Exception
 */
function civicrm_api3_mailing_send_test($params) {
  if (!array_key_exists('test_group', $params) && !array_key_exists('test_email', $params)) {
    throw new API_Exception("Mandatory key(s) missing from params array: test_group and/or test_email field are required");
  }
  civicrm_api3_verify_mandatory($params,
    'CRM_Mailing_DAO_MailingJob',
    ['mailing_id'],
    FALSE
  );

  $testEmailParams = _civicrm_api3_generic_replace_base_params($params);
  if (isset($testEmailParams['id'])) {
    unset($testEmailParams['id']);
  }

  $testEmailParams['is_test'] = 1;
  $testEmailParams['status'] = 'Scheduled';
  $testEmailParams['scheduled_date'] = CRM_Utils_Date::processDate(date('Y-m-d'), date('H:i:s'));
  $job = civicrm_api3('MailingJob', 'create', $testEmailParams);
  $testEmailParams['job_id'] = $job['id'];
  $testEmailParams['emails'] = array_key_exists('test_email', $testEmailParams) ? explode(',', strtolower($testEmailParams['test_email'])) : NULL;
  if (!empty($params['test_email'])) {
    $query = CRM_Utils_SQL_Select::from('civicrm_email e')
        ->select(['e.id', 'e.contact_id', 'e.email'])
        ->join('c', 'INNER JOIN civicrm_contact c ON e.contact_id = c.id')
        ->where('e.email IN (@emails)', ['@emails' => $testEmailParams['emails']])
        ->where('e.on_hold = 0')
        ->where('c.is_opt_out = 0')
        ->where('c.do_not_email = 0')
        ->where('c.is_deceased = 0')
        ->where('c.is_deleted = 0')
        ->groupBy('e.id')
        ->orderBy(['e.is_bulkmail DESC', 'e.is_primary DESC'])
        ->toSQL();
    $dao = CRM_Core_DAO::executeQuery($query);
    $emailDetail = [];
    // fetch contact_id and email id for all existing emails
    while ($dao->fetch()) {
      $emailDetail[strtolower($dao->email)] = [
        'contact_id' => $dao->contact_id,
        'email_id' => $dao->id,
      ];
    }
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
          [
            'contact_type' => 'Individual',
            'email' => $email,
            'api.Email.get' => ['return' => 'id'],
          ]
        );
        $contactId = $contact['id'];
        $emailId   = $contact['values'][$contactId]['api.Email.get']['id'];
      }
      civicrm_api3('MailingEventQueue', 'create',
        [
          'job_id' => $job['id'],
          'email_id' => $emailId,
          'contact_id' => $contactId,
        ]
      );
    }
  }

  $isComplete = FALSE;

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
 * Adjust Metadata for send_mail action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_mailing_stats_spec(&$params) {
  $params['date']['api.default'] = 'now';
  $params['date']['title'] = 'Date';
  $params['is_distinct']['api.default'] = FALSE;
  $params['is_distinct']['title'] = 'Is Distinct';
}

/**
 * Function which needs to be explained.
 *
 * @param array $params
 *
 * @return array
 * @throws \API_Exception
 */
function civicrm_api3_mailing_stats($params) {
  civicrm_api3_verify_mandatory($params,
    'CRM_Mailing_DAO_MailingJob',
    ['mailing_id'],
    FALSE
  );

  if ($params['date'] == 'now') {
    $params['date'] = date('YmdHis');
  }
  else {
    $params['date'] = CRM_Utils_Date::processDate($params['date'] . ' ' . $params['date_time']);
  }

  $stats[$params['mailing_id']] = [];
  if (empty($params['job_id'])) {
    $params['job_id'] = NULL;
  }
  foreach (['Delivered', 'Bounces', 'Unsubscribers', 'Unique Clicks', 'Opened'] as $detail) {
    switch ($detail) {
      case 'Delivered':
        $stats[$params['mailing_id']] += [
          $detail => CRM_Mailing_Event_BAO_Delivered::getTotalCount($params['mailing_id'], $params['job_id'], (bool) $params['is_distinct'], $params['date']),
        ];
        break;

      case 'Bounces':
        $stats[$params['mailing_id']] += [
          $detail => CRM_Mailing_Event_BAO_Bounce::getTotalCount($params['mailing_id'], $params['job_id'], (bool) $params['is_distinct'], $params['date']),
        ];
        break;

      case 'Unsubscribers':
        $stats[$params['mailing_id']] += [
          $detail => CRM_Mailing_Event_BAO_Unsubscribe::getTotalCount($params['mailing_id'], $params['job_id'], (bool) $params['is_distinct'], NULL, $params['date']),
        ];
        break;

      case 'Unique Clicks':
        $stats[$params['mailing_id']] += [
          $detail => CRM_Mailing_Event_BAO_TrackableURLOpen::getTotalCount($params['mailing_id'], $params['job_id'], (bool) $params['is_distinct'], NULL, $params['date']),
        ];
        break;

      case 'Opened':
        $stats[$params['mailing_id']] += [
          $detail => CRM_Mailing_Event_BAO_Opened::getTotalCount($params['mailing_id'], $params['job_id'], (bool) $params['is_distinct'], $params['date']),
        ];
        break;
    }
  }
  $stats[$params['mailing_id']]['delivered_rate'] = $stats[$params['mailing_id']]['opened_rate'] = $stats[$params['mailing_id']]['clickthrough_rate'] = '0.00%';
  if (!empty(CRM_Mailing_Event_BAO_Queue::getTotalCount($params['mailing_id'], $params['job_id']))) {
    $stats[$params['mailing_id']]['delivered_rate'] = round((100.0 * $stats[$params['mailing_id']]['Delivered']) / CRM_Mailing_Event_BAO_Queue::getTotalCount($params['mailing_id'], $params['job_id']), 2) . '%';
  }
  if (!empty($stats[$params['mailing_id']]['Delivered'])) {
    $stats[$params['mailing_id']]['opened_rate'] = round($stats[$params['mailing_id']]['Opened'] / $stats[$params['mailing_id']]['Delivered'] * 100.0, 2) . '%';
    $stats[$params['mailing_id']]['clickthrough_rate'] = round($stats[$params['mailing_id']]['Unique Clicks'] / $stats[$params['mailing_id']]['Delivered'] * 100.0, 2) . '%';
  }
  return civicrm_api3_create_success($stats);
}

/**
 * Fix the reset dates on the email record based on when a mail was last delivered.
 *
 * We only consider mailings that were completed and finished in the last 3 to 7 days
 * Both the min and max days can be set via the params
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_mailing_update_email_resetdate($params) {
  CRM_Mailing_Event_BAO_Delivered::updateEmailResetDate(
    CRM_Utils_Array::value('minDays', $params, 3),
    CRM_Utils_Array::value('maxDays', $params, 3)
  );
  return civicrm_api3_create_success();
}
