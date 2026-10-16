<?php

use Civi\Api4\Membership;

/**
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
   * @fkEntity Membership
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

  /**
   * Get the membership record.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getMembership(): array {
    if (!$this->membership) {
      $this->membership = Membership::get(FALSE)
        ->addWhere('id', '=', $this->membershipID)
        ->setSelect(array_merge($this->getAdditionalFieldsToLoadForMembership(), ['*']))->execute()->first();
    }
    return $this->membership;
  }

}
