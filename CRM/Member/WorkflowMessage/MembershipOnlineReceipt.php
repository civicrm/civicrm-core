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
 * Receipt sent when confirming a back office membership.
 *
 * @support template-only
 *
 * @see CRM_Member_Form_MembershipRenewal::sendReceipt
 * @see CRM_Member_Form_Membership::emailReceipt
 * @see CRM_Batch_Form_Entry::emailReceipt
 */
class CRM_Member_WorkflowMessage_MembershipOnlineReceipt extends GenericWorkflowMessage {
  use CRM_Member_WorkflowMessage_MembershipTrait;
  use CRM_Contribute_WorkflowMessage_ContributionTrait;
  public const WORKFLOW = 'membership_online_receipt';

}
