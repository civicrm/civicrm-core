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
 * FIXME: Describe 'pledge_reminder' more precisely, as in:
 *   - Docblock: When does this message fire? What's the general gist?
 *   - Class: What input fields are expected?
 *
 * @support template-only
 * @see CRM_Pledge_BAO_Pledge::updatePledgeStatus
 */
class CRM_Pledge_WorkflowMessage_PledgeReminder extends \Civi\WorkflowMessage\GenericWorkflowMessage {

  const WORKFLOW = 'pledge_reminder';
  const GROUP = 'msg_tpl_workflow_pledge';

}
