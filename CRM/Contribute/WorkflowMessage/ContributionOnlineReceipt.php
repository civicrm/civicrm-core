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
 * Add payment, complete order and the front end contribution form
 * result in an email send using this, unless an event is involved.
 * In addition the api contribution.sendconfirmation and the search task
 * call this.
 *
 * @support template-only
 * @see CRM_Contribute_BAO_ContributionPage::sendMail
 */
class CRM_Contribute_WorkflowMessage_ContributionOnlineReceipt extends GenericWorkflowMessage {
  use CRM_Contribute_WorkflowMessage_ContributionTrait;
  public const WORKFLOW = 'contribution_online_receipt';

}
