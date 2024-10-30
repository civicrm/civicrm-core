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
 * Receipt sent when confirming a back office participation record.
 *
 * @support template-only
 *
 * @see \CRM_Event_BAO_Event::sendMail()
 * @see \CRM_Event_Form_SelfSvcTransfer::participantTransfer
 */
class CRM_Event_WorkflowMessage_EventOnlineReceipt extends GenericWorkflowMessage {
  use CRM_Event_WorkflowMessage_ParticipantTrait;
  use CRM_Contribute_WorkflowMessage_ContributionTrait;
  public const WORKFLOW = 'event_online_receipt';

}
