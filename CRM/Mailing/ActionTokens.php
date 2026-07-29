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

use Civi\Token\TokenRow;

/**
 * Class CRM_Mailing_ActionTokens
 *
 * Generate "action.*" tokens for mailings.
 *
 * To activate these tokens, the TokenProcessor context must specify:
 * "mailingActionTarget" (array) with keys:
 *   'id' => int, event queue ID
 *   'hash' => string, event queue hash code
 *   'contact_id' => int, contact_id,
 *   'email' => string, email
 *   'phone' => string, phone
 *
 * "mailingJobId" (int) is no longer required and while it is added to the url it is ignored when unpacking them.
 */
class CRM_Mailing_ActionTokens extends \Civi\Token\AbstractTokenSubscriber {

  /**
   * Class constructor.
   */
  public function __construct() {
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
  public function checkActive(\Civi\Token\TokenProcessor $processor): bool {
    return !empty($processor->context['mailingId']) || !empty($processor->context['mailing'])
      || in_array('mailingId', $processor->context['schema']) || in_array('mailing', $processor->context['schema']);
  }

  /**
   * @inheritDoc
   */
  public function evaluateToken(
    TokenRow $row,
    $entity,
    $field,
    $prefetch = NULL
  ): void {
    // Most CiviMail action tokens were implemented via getActionTokenReplacement().
    // However, {action.subscribeUrl} has a second implementation via
    // replaceSubscribeInviteTokens(). The two appear mostly the same.
    // We use getActionTokenReplacement() since it's more consistent. However,
    // this doesn't provide the dynamic/parameterized tokens of
    // replaceSubscribeInviteTokens().

    if (empty($row->context['mailingActionTarget']['id']) || empty($row->context['mailingActionTarget']['hash'])) {
      // Strictly speaking, it doesn't make much sense to generate action-tokens when there's no event queue ID, but traditional CiviMail
      // does this in v5.6+ for "Preview" functionality. Relaxing this strictness check ensures parity between newer+older styles.
      // throw new \CRM_Core_Exception("Error: Cannot use action tokens unless context defines mailingActionTarget.");
    }

    if ($field === 'eventQueueId') {
      $row->format('text/plain')->tokens($entity, $field, $row->context['mailingActionTarget']['id']);
      return;
    }

    [$verp, $urls] = CRM_Mailing_BAO_Mailing::getVerpAndUrls(
      // $row->context['mailingJobId'] is deprecated - if present it will be added to the url, but ignored when
      // unpacking the url.
      $row->context['mailingJobId'] ?? NULL,
      $row->context['mailingActionTarget']['id'] ?? NULL,
      $row->context['mailingActionTarget']['hash'] ?? NULL
    );

    $row->format('text/plain')->tokens($entity, $field,
      CRM_Utils_Token::getActionTokenReplacement(
        $field, $verp, $urls, FALSE));
    $row->format('text/html')->tokens($entity, $field,
      CRM_Utils_Token::getActionTokenReplacement(
        $field, $verp, $urls, TRUE));
  }

}
