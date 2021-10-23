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
 * Reminder pledger that a payment is due.
 *
 * @support template-only
 * @see CRM_Pledge_BAO_Pledge::updatePledgeStatus
 */
class CRM_Pledge_WorkflowMessage_PledgeReminder extends GenericWorkflowMessage {

  public const WORKFLOW = 'pledge_reminder';

}
