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
 * Receipt sent when confirming an online membership from a contribution page.
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
  use CRM_Core_WorkflowMessage_SingleProfileTrait;
  public const WORKFLOW = 'membership_online_receipt';

  /**
   * The soft credit type of the honor block profile.
   *
   * This is a bit ugly - ideally the template would have all soft credits
   * assigned and iterate for what it needs.
   *
   * @var string
   *
   * @scope tplParams as soft_credit_type
   */
  public $softCreditType;

}
