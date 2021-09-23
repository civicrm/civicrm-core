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
 * FIXME: Describe 'participant_confirm' more precisely, as in:
 *   - Docblock: When does this message fire? What's the general gist?
 *   - Class: What input fields are expected?
 *
 * @support template-only
 * @see CRM_Event_BAO_Participant::sendTransitionParticipantMail
 */
class CRM_Event_WorkflowMessage_ParticipantConfirm extends \Civi\WorkflowMessage\GenericWorkflowMessage {

  const WORKFLOW = 'participant_confirm';
  const GROUP = 'msg_tpl_workflow_event';

}
