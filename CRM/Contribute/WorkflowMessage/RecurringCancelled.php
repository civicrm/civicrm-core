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
 * FIXME: Describe 'contribution_recurring_cancelled' more precisely, as in:
 *   - Docblock: When does this message fire? What's the general gist?
 *   - Class: What input fields are expected?
 *
 * @support template-only
 * @see CRM_Contribute_Form_CancelSubscription::postProcess
 */
class CRM_Contribute_WorkflowMessage_RecurringCancelled extends \Civi\WorkflowMessage\GenericWorkflowMessage {

  const WORKFLOW = 'contribution_recurring_cancelled';
  const GROUP = 'msg_tpl_workflow_contribution';

}
