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
 * Class CRM_Mailing_ActionTokens
 *
 * Generate "action.*" tokens for mailings.
 *
 * To activate these tokens, the TokenProcessor context must specify:
 * "mailingJobId" (int)
 * "mailingActionTarget" (array) with keys:
 *   'id' => int, event queue ID
 *   'hash' => string, event queue hash code
 *   'contact_id' => int, contact_id,
 *   'email' => string, email
 *   'phone' => string, phone
 */
class CRM_Mailing_ActionTokens extends \Civi\Token\AbstractTokenSubscriber {

  /**
   * Class constructor.
   */
  public function __construct() {
    // TODO: Think about supporting dynamic tokens like "{action.subscribe.\d+}"
    parent::__construct('action', [
      'subscribeUrl' => ts('Subscribe URL (Action)'),
      'forward' => ts('Forward URL (Action)'),
      'optOut' => ts('Opt-Out (Action)'),
      'optOutUrl' => ts('Opt-Out URL (Action)'),
      'reply' => ts('Reply (Action)'),
      'unsubscribe' => ts('Unsubscribe (Action)'),
      'unsubscribeUrl' => ts('Unsubscribe URL (Action)'),
      'resubscribe' => ts('Resubscribe (Action)'),
      'resubscribeUrl' => ts('Resubscribe URL (Action)'),
      'eventQueueId' => ts('Event Queue ID'),
    ]);
  }

  /**
   * @inheritDoc
   */
  public function checkActive(\Civi\Token\TokenProcessor $processor) {
    return !empty($processor->context['mailingId']) || !empty($processor->context['mailing']);
  }

  /**
   * @inheritDoc
   */
  public function evaluateToken(
    \Civi\Token\TokenRow $row,
    $entity,
    $field,
    $prefetch = NULL
  ) {
    // Most CiviMail action tokens were implemented via getActionTokenReplacement().
    // However, {action.subscribeUrl} has a second implementation via
    // replaceSubscribeInviteTokens(). The two appear mostly the same.
    // We use getActionTokenReplacement() since it's more consistent. However,
    // this doesn't provide the dynamic/parameterized tokens of
    // replaceSubscribeInviteTokens().

    if (empty($row->context['mailingJobId']) || empty($row->context['mailingActionTarget']['hash'])) {
      throw new \CRM_Core_Exception("Error: Cannot use action tokens unless context defines mailingJobId and mailingActionTarget.");
    }

    if ($field === 'eventQueueId') {
      $row->format('text/plain')->tokens($entity, $field, $row->context['mailingActionTarget']['id']);
      return;
    }

    list($verp, $urls) = CRM_Mailing_BAO_Mailing::getVerpAndUrls(
      $row->context['mailingJobId'],
      $row->context['mailingActionTarget']['id'],
      $row->context['mailingActionTarget']['hash'],
      // Note: Behavior is already undefined for SMS/'phone' mailings...
      $row->context['mailingActionTarget']['email']
    );

    $row->format('text/plain')->tokens($entity, $field,
      CRM_Utils_Token::getActionTokenReplacement(
        $field, $verp, $urls, FALSE));
    $row->format('text/html')->tokens($entity, $field,
      CRM_Utils_Token::getActionTokenReplacement(
        $field, $verp, $urls, TRUE));
  }

}
