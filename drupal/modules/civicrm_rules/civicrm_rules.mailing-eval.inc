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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */

require_once 'civicrm_rules_utils.inc';

function civicrm_rules_rules_action_mailing_send_email($to,
  $subject,
  $message,
  $from = NULL,
  $settings,
  RulesState $state,
  RulesPlugin $element
) {
  if (isset($state->variables['mailing']) && $state->variables['mailing']->id) {
    $to = str_replace(array("\r", "\n"), ',', $to);
    $toEmails = explode(',', $to);
    if (empty($toEmails)) {
      return;
    }
    require_once 'CRM/Utils/Token.php';
    $tokens['mailing'] = array(
      'mailing.editUrl',
      'mailing.scheduleUrl',
      'mailing.html',
      'mailing.creatorEmail',
      'mailing.creator',
    );

    $params            = array();
    $params['from']    = !empty($from) ? str_replace(array(
      "\r", "\n",
    ), '', $from) : 'Admin';
    $params['subject'] = CRM_Utils_Token::replaceMailingTokens($subject, $state->variables['mailing'], NULL, $tokens);
    $params['html']    = CRM_Utils_Token::replaceMailingTokens($message, $state->variables['mailing'], NULL, $tokens);

    // also allow some user permission tokens for the toEmail
    $tokens['user'] = array(
      'user.permission-schedule mailings',
      'user.permission-create mailings',
      'user.permission-approve mailings',
    );
    foreach ($toEmails as $toEmail) {
      $params['toEmail'] = CRM_Utils_Token::replaceMailingTokens($toEmail, $state->variables['mailing'], NULL, $tokens);

      //only process user permission tokens if used in toEmail
      if (in_array(trim($toEmail, '{}'), $tokens['user'])) {
        //we require a TO email, but can only send one; so we strip the first off and send the rest as CCs
        $userEmails = explode(',', CRM_Utils_Token::replaceUserTokens($params['toEmail'], $tokens));
        $params['toEmail'] = $userEmails[0];
        array_shift($userEmails);
        $params['cc'] = implode(',', $userEmails);
      }

      CRM_Utils_Mail::send($params);
    }
  }
}

function civicrm_rules_condition_mailing_approved($mailing, $settings) {
  return $mailing->approval_status_id == 1;
}

function civicrm_rules_condition_mailing_rejected($mailing, $settings) {
  return $mailing->approval_status_id != 1;
}
