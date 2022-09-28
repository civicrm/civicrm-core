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
 * Receipt sent when confirming a back office contribution.
 *
 * @support template-only
 *
 * @see CRM_Contribute_Form_AdditionalInfo::emailReceipt
 */
class CRM_Contribute_WorkflowMessage_ContributionOfflineReceipt extends GenericWorkflowMessage {
  use CRM_Contribute_WorkflowMessage_ContributionTrait;
  public const WORKFLOW = 'contribution_offline_receipt';

}
