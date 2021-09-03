<?php

class CRM_Contribute_WorkflowMessage_RecurringEdit extends Civi\WorkflowMessage\GenericWorkflowMessage {
  const WORKFLOW = 'contribution_recurring_edit';

  /**
   * The recurring contribution contact.
   *
   * @var array|null
   *
   * @scope tokenContext
   *
   * @required
   */
  public $contact;

  /**
   * The recurring contribution.
   *
   * @var array|null
   *
   * @scope tokenContext
   *
   * @required
   */
  public $contribution_recur;

  protected function exportExtraTokenContext(array &$export): void {
    $export['smartyTokenAlias']['installments'] = 'contribution_recur.installments';
    $export['smartyTokenAlias']['amount'] = 'contribution_recur.amount';
    $export['smartyTokenAlias']['recur_frequency_unit'] = 'contribution_recur.frequency_unit';
    $export['smartyTokenAlias']['recur_frequency_interval'] = 'contribution_recur.frequency_interval';
  }

}
