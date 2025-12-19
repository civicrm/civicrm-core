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

use Civi\WorkflowMessage\GenericWorkflowMessage;

/**
 * Receipt sent when confirming contribution add payment.
 *
 * @method int getEventID()
 * @method int getParticipantID()
 *
 * @support template-only
 * @see CRM_Financial_BAO_Payment::sendConfirmation()
 */
class CRM_Contribute_WorkflowMessage_PaymentOrRefundNotification extends GenericWorkflowMessage {
  use CRM_Contribute_WorkflowMessage_ContributionTrait;
  public const WORKFLOW = 'payment_or_refund_notification';

  /**
   * @var int
   *
   * @scope tokenContext as eventId, tplParams as eventID
   */
  public $eventID;

  /**
   * @var int
   *
   * @scope tokenContext as participantId
   */
  public $participantID;

}
