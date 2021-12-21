<?php

/**
 * @support template-only
 */
class CRM_Contribute_WorkflowMessage_RecurringCancelled extends Civi\WorkflowMessage\GenericWorkflowMessage {
  use CRM_Contribute_WorkflowMessage_RecurringTrait;

  public const WORKFLOW = 'contribution_recurring_cancelled';

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

  protected function exportExtraTokenContext(array &$export): void {
    $export['smartyTokenAlias']['amount'] = 'contribution_recur.amount';
    $export['smartyTokenAlias']['recur_frequency_unit'] = 'contribution_recur.frequency_unit:label';
    $export['smartyTokenAlias']['recur_frequency_interval'] = 'contribution_recur.frequency_interval';
  }

}
