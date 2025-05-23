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
 * Notification that a registration has been cancelled.
 *
 * @support template-only
 *
 * @see CRM_Event_BAO_Participant::sendTransitionParticipantMail
 */
class CRM_Event_WorkflowMessage_ParticipantExpired extends GenericWorkflowMessage {
  use CRM_Event_WorkflowMessage_ParticipantTrait;

  public const WORKFLOW = 'participant_expired';

}
