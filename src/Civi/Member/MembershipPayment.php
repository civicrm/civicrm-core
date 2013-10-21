<?php

namespace Civi\Member;

use Doctrine\ORM\Mapping as ORM;

/**
 * MembershipPayment
 *
 * @ORM\Table(name="civicrm_membership_payment", uniqueConstraints={@ORM\UniqueConstraint(name="UI_contribution_membership", columns={"contribution_id", "membership_id"})}, indexes={@ORM\Index(name="FK_civicrm_membership_payment_membership_id", columns={"membership_id"}), @ORM\Index(name="IDX_AF30CA8DFE5E5FBD", columns={"contribution_id"})})
 * @ORM\Entity
 */
class MembershipPayment extends \Civi\Core\Entity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Civi\Member\Membership
     *
     * @ORM\ManyToOne(targetEntity="Civi\Member\Membership")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="membership_id", referencedColumnName="id")
     * })
     */
    private $membership;

    /**
     * @var \Civi\Contribute\Contribution
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contribute\Contribution")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contribution_id", referencedColumnName="id")
     * })
     */
    private $contribution;



    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set membership
     *
     * @param \Civi\Member\Membership $membership
     * @return MembershipPayment
     */
    public function setMembership(\Civi\Member\Membership $membership = null)
    {
        $this->membership = $membership;

        return $this;
    }

    /**
     * Get membership
     *
     * @return \Civi\Member\Membership 
     */
    public function getMembership()
    {
        return $this->membership;
    }

    /**
     * Set contribution
     *
     * @param \Civi\Contribute\Contribution $contribution
     * @return MembershipPayment
     */
    public function setContribution(\Civi\Contribute\Contribution $contribution = null)
    {
        $this->contribution = $contribution;

        return $this;
    }

    /**
     * Get contribution
     *
     * @return \Civi\Contribute\Contribution 
     */
    public function getContribution()
    {
        return $this->contribution;
    }
}
