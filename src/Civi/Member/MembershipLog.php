<?php

namespace Civi\Member;

use Doctrine\ORM\Mapping as ORM;

/**
 * MembershipLog
 *
 * @ORM\Table(name="civicrm_membership_log", indexes={@ORM\Index(name="FK_civicrm_membership_log_membership_id", columns={"membership_id"}), @ORM\Index(name="FK_civicrm_membership_log_status_id", columns={"status_id"}), @ORM\Index(name="FK_civicrm_membership_log_modified_id", columns={"modified_id"}), @ORM\Index(name="FK_civicrm_membership_log_membership_type_id", columns={"membership_type_id"})})
 * @ORM\Entity
 */
class MembershipLog extends \Civi\Core\Entity
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
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="date", nullable=true)
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="date", nullable=true)
     */
    private $endDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified_date", type="date", nullable=true)
     */
    private $modifiedDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_related", type="integer", nullable=true)
     */
    private $maxRelated;

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
     * @var \Civi\Member\MembershipStatus
     *
     * @ORM\ManyToOne(targetEntity="Civi\Member\MembershipStatus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     * })
     */
    private $status;

    /**
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="modified_id", referencedColumnName="id")
     * })
     */
    private $modified;

    /**
     * @var \Civi\Member\MembershipType
     *
     * @ORM\ManyToOne(targetEntity="Civi\Member\MembershipType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="membership_type_id", referencedColumnName="id")
     * })
     */
    private $membershipType;



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
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return MembershipLog
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return MembershipLog
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set modifiedDate
     *
     * @param \DateTime $modifiedDate
     * @return MembershipLog
     */
    public function setModifiedDate($modifiedDate)
    {
        $this->modifiedDate = $modifiedDate;

        return $this;
    }

    /**
     * Get modifiedDate
     *
     * @return \DateTime 
     */
    public function getModifiedDate()
    {
        return $this->modifiedDate;
    }

    /**
     * Set maxRelated
     *
     * @param integer $maxRelated
     * @return MembershipLog
     */
    public function setMaxRelated($maxRelated)
    {
        $this->maxRelated = $maxRelated;

        return $this;
    }

    /**
     * Get maxRelated
     *
     * @return integer 
     */
    public function getMaxRelated()
    {
        return $this->maxRelated;
    }

    /**
     * Set membership
     *
     * @param \Civi\Member\Membership $membership
     * @return MembershipLog
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
     * Set status
     *
     * @param \Civi\Member\MembershipStatus $status
     * @return MembershipLog
     */
    public function setStatus(\Civi\Member\MembershipStatus $status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return \Civi\Member\MembershipStatus 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set modified
     *
     * @param \Civi\Contact\Contact $modified
     * @return MembershipLog
     */
    public function setModified(\Civi\Contact\Contact $modified = null)
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * Get modified
     *
     * @return \Civi\Contact\Contact 
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Set membershipType
     *
     * @param \Civi\Member\MembershipType $membershipType
     * @return MembershipLog
     */
    public function setMembershipType(\Civi\Member\MembershipType $membershipType = null)
    {
        $this->membershipType = $membershipType;

        return $this;
    }

    /**
     * Get membershipType
     *
     * @return \Civi\Member\MembershipType 
     */
    public function getMembershipType()
    {
        return $this->membershipType;
    }
}
