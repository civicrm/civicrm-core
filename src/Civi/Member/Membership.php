<?php

namespace Civi\Member;

use Doctrine\ORM\Mapping as ORM;

/**
 * Membership
 *
 * @ORM\Table(name="civicrm_membership", indexes={@ORM\Index(name="index_owner_membership_id", columns={"owner_membership_id"}), @ORM\Index(name="FK_civicrm_membership_contact_id", columns={"contact_id"}), @ORM\Index(name="FK_civicrm_membership_membership_type_id", columns={"membership_type_id"}), @ORM\Index(name="FK_civicrm_membership_status_id", columns={"status_id"}), @ORM\Index(name="FK_civicrm_membership_contribution_recur_id", columns={"contribution_recur_id"}), @ORM\Index(name="FK_civicrm_membership_campaign_id", columns={"campaign_id"})})
 * @ORM\Entity
 */
class Membership extends \Civi\Core\Entity
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
     * @ORM\Column(name="join_date", type="date", nullable=true)
     */
    private $joinDate;

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
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=128, nullable=true)
     */
    private $source;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_override", type="boolean", nullable=true)
     */
    private $isOverride;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_related", type="integer", nullable=true)
     */
    private $maxRelated;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_test", type="boolean", nullable=true)
     */
    private $isTest = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_pay_later", type="boolean", nullable=true)
     */
    private $isPayLater = '0';

    /**
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     * })
     */
    private $contact;

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
     * @var \Civi\Member\MembershipStatus
     *
     * @ORM\ManyToOne(targetEntity="Civi\Member\MembershipStatus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     * })
     */
    private $status;

    /**
     * @var \Civi\Member\Membership
     *
     * @ORM\ManyToOne(targetEntity="Civi\Member\Membership")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="owner_membership_id", referencedColumnName="id")
     * })
     */
    private $ownerMembership;

    /**
     * @var \Civi\Contribute\ContributionRecur
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contribute\ContributionRecur")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contribution_recur_id", referencedColumnName="id")
     * })
     */
    private $contributionRecur;

    /**
     * @var \Civi\Campaign\Campaign
     *
     * @ORM\ManyToOne(targetEntity="Civi\Campaign\Campaign")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="campaign_id", referencedColumnName="id")
     * })
     */
    private $campaign;



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
     * Set joinDate
     *
     * @param \DateTime $joinDate
     * @return Membership
     */
    public function setJoinDate($joinDate)
    {
        $this->joinDate = $joinDate;

        return $this;
    }

    /**
     * Get joinDate
     *
     * @return \DateTime 
     */
    public function getJoinDate()
    {
        return $this->joinDate;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Membership
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
     * @return Membership
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
     * Set source
     *
     * @param string $source
     * @return Membership
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string 
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set isOverride
     *
     * @param boolean $isOverride
     * @return Membership
     */
    public function setIsOverride($isOverride)
    {
        $this->isOverride = $isOverride;

        return $this;
    }

    /**
     * Get isOverride
     *
     * @return boolean 
     */
    public function getIsOverride()
    {
        return $this->isOverride;
    }

    /**
     * Set maxRelated
     *
     * @param integer $maxRelated
     * @return Membership
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
     * Set isTest
     *
     * @param boolean $isTest
     * @return Membership
     */
    public function setIsTest($isTest)
    {
        $this->isTest = $isTest;

        return $this;
    }

    /**
     * Get isTest
     *
     * @return boolean 
     */
    public function getIsTest()
    {
        return $this->isTest;
    }

    /**
     * Set isPayLater
     *
     * @param boolean $isPayLater
     * @return Membership
     */
    public function setIsPayLater($isPayLater)
    {
        $this->isPayLater = $isPayLater;

        return $this;
    }

    /**
     * Get isPayLater
     *
     * @return boolean 
     */
    public function getIsPayLater()
    {
        return $this->isPayLater;
    }

    /**
     * Set contact
     *
     * @param \Civi\Contact\Contact $contact
     * @return Membership
     */
    public function setContact(\Civi\Contact\Contact $contact = null)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact
     *
     * @return \Civi\Contact\Contact 
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set membershipType
     *
     * @param \Civi\Member\MembershipType $membershipType
     * @return Membership
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

    /**
     * Set status
     *
     * @param \Civi\Member\MembershipStatus $status
     * @return Membership
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
     * Set ownerMembership
     *
     * @param \Civi\Member\Membership $ownerMembership
     * @return Membership
     */
    public function setOwnerMembership(\Civi\Member\Membership $ownerMembership = null)
    {
        $this->ownerMembership = $ownerMembership;

        return $this;
    }

    /**
     * Get ownerMembership
     *
     * @return \Civi\Member\Membership 
     */
    public function getOwnerMembership()
    {
        return $this->ownerMembership;
    }

    /**
     * Set contributionRecur
     *
     * @param \Civi\Contribute\ContributionRecur $contributionRecur
     * @return Membership
     */
    public function setContributionRecur(\Civi\Contribute\ContributionRecur $contributionRecur = null)
    {
        $this->contributionRecur = $contributionRecur;

        return $this;
    }

    /**
     * Get contributionRecur
     *
     * @return \Civi\Contribute\ContributionRecur 
     */
    public function getContributionRecur()
    {
        return $this->contributionRecur;
    }

    /**
     * Set campaign
     *
     * @param \Civi\Campaign\Campaign $campaign
     * @return Membership
     */
    public function setCampaign(\Civi\Campaign\Campaign $campaign = null)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * Get campaign
     *
     * @return \Civi\Campaign\Campaign 
     */
    public function getCampaign()
    {
        return $this->campaign;
    }
}
