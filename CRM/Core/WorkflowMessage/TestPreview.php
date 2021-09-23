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
 * FIXME: Describe 'test_preview' more precisely, as in:
 *   - Docblock: When does this message fire? What's the general gist?
 *   - Class: What input fields are expected?
 *
 * @support template-only
 * @see CRM_Core_BAO_MessageTemplate::loadTemplate
 */
class CRM_Core_WorkflowMessage_TestPreview extends \Civi\WorkflowMessage\GenericWorkflowMessage {

  const WORKFLOW = 'test_preview';
  const GROUP = 'msg_tpl_workflow_meta';

}
