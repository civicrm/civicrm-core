<?php

class CRM_Contribute_WorkflowMessage_RecurringEdit extends Civi\WorkflowMessage\GenericWorkflowMessage {
  const GROUP = 'msg_tpl_workflow_contribution_recur';
  const WORKFLOW = 'contribution_recurring_edit';

  /**
   * The recurring contribution contact..
   * @var array|null
   *
   * @scope tokenContext
   * @required
   */
  public $contact;

  /**
   * The recurring contribution contact.
   *
   * @var array|null
   *
   * @scope tokenContext
   *
   * @required
   */
  public $contribution_recur;

}
