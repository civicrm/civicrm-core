<?php

/**
 * @method array getContributionRecur()
 * @method array getContact()
 * @method $this setContact(array $contact)
 */
trait CRM_Contribute_WorkflowMessage_RecurringTrait {
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
   * Set recurring contribution object.
   *
   * @param array $contributionRecur
   *
   * @return $this
   */
  public function setContributionRecur(array $contributionRecur): self {
    $this->contributionRecur = $contributionRecur;
    if (!empty($contributionRecur['id'])) {
      $this->contributionRecurId = $contributionRecur['id'];
    }
    return $this;
  }

}
