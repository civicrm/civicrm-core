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
 * FIXME: Describe 'petition_sign' more precisely, as in:
 *   - Docblock: When does this message fire? What's the general gist?
 *   - Class: What input fields are expected?
 *
 * @support template-only
 * @see CRM_Campaign_BAO_Petition::sendEmail
 */
class CRM_Campaign_WorkflowMessage_PetitionSign extends \Civi\WorkflowMessage\GenericWorkflowMessage {

  const WORKFLOW = 'petition_sign';
  const GROUP = 'msg_tpl_workflow_petition';

}
