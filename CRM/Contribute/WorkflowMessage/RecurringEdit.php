<?php

/**
 * @support template-only
 * @method array getContact()
 * @method $this setContact(array $contact)
 * @method array getContributionRecur()
 */
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
   * @scope tokenContext as contribution_recur
   *
   * @required
   */
  public $contributionRecur;

  /**
   * @var int
   * @scope tokenContext as contribution_recurId
   */
  public $contributionRecurId;

  /**
   * Smarty template historically defined a property 'receipt_from_email'.
   * (Note the asymmetric lack of 'receipt_from_name'.)
   *
   * TODO: This should probably be deprecated/converted/reconciled with `$this->from` in the basic AddressingTrait.
   *
   * @var string|null
   * @scope tplParams as receipt_from_email
   */
  public $receiptFromEmail;

  public function setContributionRecur(array $contributionRecur) {
    $this->contributionRecur = $contributionRecur;
    if (!empty($contributionRecur['id'])) {
      $this->contributionRecurId = $contributionRecur['id'];
    }
    return $this;
  }

  protected function exportExtraTokenContext(array &$export): void {
    $export['smartyTokenAlias']['installments'] = 'contribution_recur.installments';
    $export['smartyTokenAlias']['amount'] = 'contribution_recur.amount';
    $export['smartyTokenAlias']['recur_frequency_unit'] = 'contribution_recur.frequency_unit:label';
    $export['smartyTokenAlias']['recur_frequency_interval'] = 'contribution_recur.frequency_interval';
  }

  protected function exportExtraTplParams(array &$export): void {
    if (empty($export['receipt_from_email']) && !empty($this->from)) {
      // At a minimum, we can at least autofill 'receipt_from_email' in the case where it's missing.
      $export['receipt_from_email'] = $this->getFrom('record')['email'];
    }
  }

}
