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

use Civi\Api4\MailingEventQueue;
use Civi\Token\TokenRow;

/**
 * Class CRM_Mailing_ActionTokens
 *
 * Generate "action.*" tokens for mailings.
 *
 * To activate these tokens, the TokenProcessor context must specify:
 *  mailingEventQueueId (preferred)
 *  or the legacy options
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
    return !empty($processor->context['mailingEventQueueId']) || !empty($processor->context['mailingId']) || !empty($processor->context['mailing'])
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
    $mailingEventQueueID = $row->context['mailingEventQueueId'] ?? $row->context['mailingActionTarget']['id'] ?? '';

    // Strictly speaking, it doesn't make much sense to generate action-tokens when there's no event queue ID, but traditional CiviMail
    // does this in v5.6+ for "Preview" functionality.
    $hash = $row->context['mailingActionTarget']['hash'] ?? '';
    if (!$hash && $mailingEventQueueID) {
      $hash = MailingEventQueue::get(FALSE)
        ->addWhere('id', '=', $mailingEventQueueID)
        ->addSelect('hash')->execute()->first()['hash'] ?? '';
    }

    if ($field === 'eventQueueId') {
      $row->format('text/plain')->tokens($entity, $field, $mailingEventQueueID);
      return;
    }

    [$verp, $urls] = CRM_Mailing_BAO_Mailing::getVerpAndUrls(
      // Job ID is now ignored when rendering verp urls so it is very optional.
      $row->context['mailingJobId'] ?? NULL,
      $mailingEventQueueID,
      $hash
    );

    $row->format('text/plain')->tokens($entity, $field,
      $this->getActionTokenReplacement(
        $field, $verp, $urls, FALSE));
    $row->format('text/html')->tokens($entity, $field,
      $this->getActionTokenReplacement(
        $field, $verp, $urls, TRUE));
  }

  /**
   * Copy of some very old code.
   *
   * @param $token
   * @param $addresses
   * @param $urls
   * @param bool $html
   *
   * @return mixed|string
   */
  private function getActionTokenReplacement(
    $token,
    $addresses,
    $urls,
    $html = FALSE
  ) {
    // If the token is an email action, use it.  Otherwise, find the
    // appropriate URL.

    if (!in_array($token, [
      'optOut',
      'optOutUrl',
      'reply',
      'unsubscribe',
      'unsubscribeUrl',
      'resubscribe',
      'resubscribeUrl',
      'subscribeUrl',
    ])) {
      $value = "{action.$token}";
    }
    else {
      $value = $addresses[$token] ?? NULL;

      if ($value == NULL) {
        $value = $urls[$token] ?? NULL;
      }

      if ($value && $html) {
        // fix for CRM-2318
        if (substr($token, -3) != 'Url') {
          $value = "mailto:$value";
        }
      }
      elseif ($value && !$html) {
        $value = str_replace('&amp;', '&', $value);
      }
    }

    return $value;
  }

}
