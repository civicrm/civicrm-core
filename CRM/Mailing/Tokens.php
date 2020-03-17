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
 * Class CRM_Mailing_Tokens
 *
 * Generate "mailing.*" tokens.
 *
 * To activate these tokens, the TokenProcessor context must specify either
 * "mailingId" (int) or "mailing" (CRM_Mailing_BAO_Mailing).
 */
class CRM_Mailing_Tokens extends \Civi\Token\AbstractTokenSubscriber {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct('mailing', [
      'id' => ts('Mailing ID'),
      'key' => ts('Mailing Key'),
      'name' => ts('Mailing Name'),
      'group' => ts('Mailing Group(s)'),
      'subject' => ts('Mailing Subject'),
      'viewUrl' => ts('Mailing URL (View)'),
      'editUrl' => ts('Mailing URL (Edit)'),
      'scheduleUrl' => ts('Mailing URL (Schedule)'),
      'html' => ts('Mailing HTML'),
      'approvalStatus' => ts('Mailing Approval Status'),
      'approvalNote' => ts('Mailing Approval Note'),
      'approveUrl' => ts('Mailing Approval URL'),
      'creator' => ts('Mailing Creator (Name)'),
      'creatorEmail' => ts('Mailing Creator (Email)'),
    ]);
  }

  /**
   * @inheritDoc
   */
  public function checkActive(\Civi\Token\TokenProcessor $processor) {
    return !empty($processor->context['mailingId']) || !empty($processor->context['mailing'])
      || in_array('mailingId', $processor->context['schema']) || in_array('mailing', $processor->context['schema']);
  }

  /**
   * Prefetch tokens.
   *
   * @param \Civi\Token\Event\TokenValueEvent $e
   *
   * @return array
   * @throws \Exception
   */
  public function prefetch(\Civi\Token\Event\TokenValueEvent $e) {
    $processor = $e->getTokenProcessor();
    $mailing = isset($processor->context['mailing'])
      ? $processor->context['mailing']
      : CRM_Mailing_BAO_Mailing::findById($processor->context['mailingId']);

    return [
      'mailing' => $mailing,
    ];
  }

  /**
   * @inheritDoc
   */
  public function evaluateToken(\Civi\Token\TokenRow $row, $entity, $field, $prefetch = NULL) {
    $row->format('text/plain')->tokens($entity, $field,
      (string) CRM_Utils_Token::getMailingTokenReplacement($field, $prefetch['mailing']));
  }

}
