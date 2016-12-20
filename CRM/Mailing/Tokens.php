<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
    parent::__construct('mailing', array(
      'id' => ts('Mailing ID'),
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
    ));
  }

  /**
   * Check something about being active.
   *
   * @param \Civi\Token\TokenProcessor $processor
   *
   * @return bool
   */
  public function checkActive(\Civi\Token\TokenProcessor $processor) {
    return !empty($processor->context['mailingId']) || !empty($processor->context['mailing']);
  }

  public function prefetch(\Civi\Token\Event\TokenValueEvent $e) {
    $processor = $e->getTokenProcessor();
    $mailing = isset($processor->context['mailing'])
      ? $processor->context['mailing']
      : CRM_Mailing_BAO_Mailing::findById($processor->context['mailingId']);

    return array(
      'mailing' => $mailing,
    );
  }

  /**
   * Evaluate the content of a single token.
   *
   * @param \Civi\Token\TokenRow $row
   *   The record for which we want token values.
   * @param string $entity
   * @param string $field
   *   The name of the token field.
   * @param mixed $prefetch
   *   Any data that was returned by the prefetch().
   *
   * @return mixed
   */
  public function evaluateToken(\Civi\Token\TokenRow $row, $entity, $field, $prefetch = NULL) {
    $row->format('text/plain')->tokens($entity, $field,
      (string) CRM_Utils_Token::getMailingTokenReplacement($field, $prefetch['mailing']));
  }

}
