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
 * FIXME: Describe 'payment_or_refund_notification' more precisely, as in:
 *   - Docblock: When does this message fire? What's the general gist?
 *   - Class: What input fields are expected?
 *
 * @support template-only
 * @see CRM_Financial_BAO_Payment::sendConfirmation
 */
class CRM_Contribute_WorkflowMessage_PaymentOrRefundNotification extends \Civi\WorkflowMessage\GenericWorkflowMessage {

  const WORKFLOW = 'payment_or_refund_notification';
  const GROUP = 'msg_tpl_workflow_contribution';

}
