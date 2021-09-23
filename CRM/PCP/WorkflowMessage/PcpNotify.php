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
 * FIXME: Describe 'pcp_notify' more precisely, as in:
 *   - Docblock: When does this message fire? What's the general gist?
 *   - Class: What input fields are expected?
 *
 * @support template-only
 * @see CRM_PCP_Form_Campaign::postProcess
 */
class CRM_PCP_WorkflowMessage_PcpNotify extends \Civi\WorkflowMessage\GenericWorkflowMessage {

  const WORKFLOW = 'pcp_notify';
  const GROUP = 'msg_tpl_workflow_contribution';

}
