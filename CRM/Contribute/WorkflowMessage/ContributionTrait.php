<?php

/**
 * @method array getContribution()
 * @method ?int getContributionID()
 * @method $this setContributionID(?int $contributionId)
 */
trait CRM_Contribute_WorkflowMessage_ContributionTrait {
  /**
   * The contribution.
   *
   * @var array|null
   *
   * @scope tokenContext as contribution
   */
  public $contribution;

  /**
   * @var int
   * @scope tokenContext as contribution_id
   */
  public $contributionId;

  /**
   * Set contribution object.
   *
   * @param array $contribution
   *
   * @return $this
   */
  public function setContribution(array $contribution): self {
    $this->contribution = $contribution;
    if (!empty($contribution['id'])) {
      $this->contributionId = $contribution['id'];
    }
    return $this;
  }

}
