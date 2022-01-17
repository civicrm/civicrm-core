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

  /**
   * Export tokens to smarty as variables.
   *
   * The key represents the smarty token and the value is the token as
   * requested from the token processor.
   *
   * The token is 'the entire part between the curly quotes' eg.
   *
   * '{contribution_recur.amount|crmMoney}.
   *
   * Unlike using the contribution directly it will default to 'raw' formatting.
   *
   * @param array $export
   */
  protected function exportExtraTokenContext(array &$export): void {
    $export['smartyTokenAlias']['amount'] = 'contribution_recur.amount|crmMoney';
    $export['smartyTokenAlias']['recur_frequency_unit'] = 'contribution_recur.frequency_unit:label';
    $export['smartyTokenAlias']['recur_frequency_interval'] = 'contribution_recur.frequency_interval';
  }

}
