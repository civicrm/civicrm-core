<?php

/**
 * @method array getMembership()
 * @method ?int getMembershipID()
 * @method $this setMembershipID(?int $membershipID)
 */
trait CRM_Member_WorkflowMessage_MembershipTrait {
  /**
   * The membership.
   *
   * @var array|null
   *
   * @scope tokenContext as membership
   */
  protected $membership;

  /**
   * @var int
   * @scope tokenContext as membershipId, tplParams as membershipID
   */
  protected $membershipID;

  /**
   * Set membership object.
   *
   * @param array $membership
   *
   * @return $this
   */
  public function setMembership(array $membership): self {
    $this->membership = $membership;
    if (!empty($membership['id'])) {
      $this->membershipID = $membership['id'];
    }
    return $this;
  }

}
