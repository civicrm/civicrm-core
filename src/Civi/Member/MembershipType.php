<?php

namespace Civi\Member;

use Doctrine\ORM\Mapping as ORM;

/**
 * MembershipType
 *
 * @ORM\Table(name="civicrm_membership_type", indexes={@ORM\Index(name="index_relationship_type_id", columns={"relationship_type_id"}), @ORM\Index(name="FK_civicrm_membership_type_domain_id", columns={"domain_id"}), @ORM\Index(name="FK_civicrm_membership_type_member_of_contact_id", columns={"member_of_contact_id"}), @ORM\Index(name="FK_civicrm_membership_type_financial_type_id", columns={"financial_type_id"})})
 * @ORM\Entity
 */
class MembershipType extends \Civi\Core\Entity
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=128, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="minimum_fee", type="decimal", precision=20, scale=2, nullable=true)
     */
    private $minimumFee = '0.00';

    /**
     * @var string
     *
     * @ORM\Column(name="duration_unit", type="string", nullable=true)
     */
    private $durationUnit;

    /**
     * @var integer
     *
     * @ORM\Column(name="duration_interval", type="integer", nullable=true)
     */
    private $durationInterval;

    /**
     * @var string
     *
     * @ORM\Column(name="period_type", type="string", nullable=true)
     */
    private $periodType;

    /**
     * @var integer
     *
     * @ORM\Column(name="fixed_period_start_day", type="integer", nullable=true)
     */
    private $fixedPeriodStartDay;

    /**
     * @var integer
     *
     * @ORM\Column(name="fixed_period_rollover_day", type="integer", nullable=true)
     */
    private $fixedPeriodRolloverDay;

    /**
     * @var string
     *
     * @ORM\Column(name="relationship_type_id", type="string", length=64, nullable=true)
     */
    private $relationshipTypeId;

    /**
     * @var string
     *
     * @ORM\Column(name="relationship_direction", type="string", length=128, nullable=true)
     */
    private $relationshipDirection;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_related", type="integer", nullable=true)
     */
    private $maxRelated;

    /**
     * @var string
     *
     * @ORM\Column(name="visibility", type="string", length=64, nullable=true)
     */
    private $visibility;

    /**
     * @var integer
     *
     * @ORM\Column(name="weight", type="integer", nullable=true)
     */
    private $weight;

    /**
     * @var string
     *
     * @ORM\Column(name="receipt_text_signup", type="string", length=255, nullable=true)
     */
    private $receiptTextSignup;

    /**
     * @var string
     *
     * @ORM\Column(name="receipt_text_renewal", type="string", length=255, nullable=true)
     */
    private $receiptTextRenewal;

    /**
     * @var boolean
     *
     * @ORM\Column(name="auto_renew", type="boolean", nullable=true)
     */
    private $autoRenew = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';

    /**
     * @var \Civi\Core\Domain
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Domain")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="domain_id", referencedColumnName="id")
     * })
     */
    private $domain;

    /**
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="member_of_contact_id", referencedColumnName="id")
     * })
     */
    private $memberOfContact;

    /**
     * @var \Civi\Financial\Type
     *
     * @ORM\ManyToOne(targetEntity="Civi\Financial\Type")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="financial_type_id", referencedColumnName="id")
     * })
     */
    private $financialType;



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
     * Set name
     *
     * @param string $name
     * @return MembershipType
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return MembershipType
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set minimumFee
     *
     * @param string $minimumFee
     * @return MembershipType
     */
    public function setMinimumFee($minimumFee)
    {
        $this->minimumFee = $minimumFee;

        return $this;
    }

    /**
     * Get minimumFee
     *
     * @return string 
     */
    public function getMinimumFee()
    {
        return $this->minimumFee;
    }

    /**
     * Set durationUnit
     *
     * @param string $durationUnit
     * @return MembershipType
     */
    public function setDurationUnit($durationUnit)
    {
        $this->durationUnit = $durationUnit;

        return $this;
    }

    /**
     * Get durationUnit
     *
     * @return string 
     */
    public function getDurationUnit()
    {
        return $this->durationUnit;
    }

    /**
     * Set durationInterval
     *
     * @param integer $durationInterval
     * @return MembershipType
     */
    public function setDurationInterval($durationInterval)
    {
        $this->durationInterval = $durationInterval;

        return $this;
    }

    /**
     * Get durationInterval
     *
     * @return integer 
     */
    public function getDurationInterval()
    {
        return $this->durationInterval;
    }

    /**
     * Set periodType
     *
     * @param string $periodType
     * @return MembershipType
     */
    public function setPeriodType($periodType)
    {
        $this->periodType = $periodType;

        return $this;
    }

    /**
     * Get periodType
     *
     * @return string 
     */
    public function getPeriodType()
    {
        return $this->periodType;
    }

    /**
     * Set fixedPeriodStartDay
     *
     * @param integer $fixedPeriodStartDay
     * @return MembershipType
     */
    public function setFixedPeriodStartDay($fixedPeriodStartDay)
    {
        $this->fixedPeriodStartDay = $fixedPeriodStartDay;

        return $this;
    }

    /**
     * Get fixedPeriodStartDay
     *
     * @return integer 
     */
    public function getFixedPeriodStartDay()
    {
        return $this->fixedPeriodStartDay;
    }

    /**
     * Set fixedPeriodRolloverDay
     *
     * @param integer $fixedPeriodRolloverDay
     * @return MembershipType
     */
    public function setFixedPeriodRolloverDay($fixedPeriodRolloverDay)
    {
        $this->fixedPeriodRolloverDay = $fixedPeriodRolloverDay;

        return $this;
    }

    /**
     * Get fixedPeriodRolloverDay
     *
     * @return integer 
     */
    public function getFixedPeriodRolloverDay()
    {
        return $this->fixedPeriodRolloverDay;
    }

    /**
     * Set relationshipTypeId
     *
     * @param string $relationshipTypeId
     * @return MembershipType
     */
    public function setRelationshipTypeId($relationshipTypeId)
    {
        $this->relationshipTypeId = $relationshipTypeId;

        return $this;
    }

    /**
     * Get relationshipTypeId
     *
     * @return string 
     */
    public function getRelationshipTypeId()
    {
        return $this->relationshipTypeId;
    }

    /**
     * Set relationshipDirection
     *
     * @param string $relationshipDirection
     * @return MembershipType
     */
    public function setRelationshipDirection($relationshipDirection)
    {
        $this->relationshipDirection = $relationshipDirection;

        return $this;
    }

    /**
     * Get relationshipDirection
     *
     * @return string 
     */
    public function getRelationshipDirection()
    {
        return $this->relationshipDirection;
    }

    /**
     * Set maxRelated
     *
     * @param integer $maxRelated
     * @return MembershipType
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
     * Set visibility
     *
     * @param string $visibility
     * @return MembershipType
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return string 
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     * @return MembershipType
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return integer 
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set receiptTextSignup
     *
     * @param string $receiptTextSignup
     * @return MembershipType
     */
    public function setReceiptTextSignup($receiptTextSignup)
    {
        $this->receiptTextSignup = $receiptTextSignup;

        return $this;
    }

    /**
     * Get receiptTextSignup
     *
     * @return string 
     */
    public function getReceiptTextSignup()
    {
        return $this->receiptTextSignup;
    }

    /**
     * Set receiptTextRenewal
     *
     * @param string $receiptTextRenewal
     * @return MembershipType
     */
    public function setReceiptTextRenewal($receiptTextRenewal)
    {
        $this->receiptTextRenewal = $receiptTextRenewal;

        return $this;
    }

    /**
     * Get receiptTextRenewal
     *
     * @return string 
     */
    public function getReceiptTextRenewal()
    {
        return $this->receiptTextRenewal;
    }

    /**
     * Set autoRenew
     *
     * @param boolean $autoRenew
     * @return MembershipType
     */
    public function setAutoRenew($autoRenew)
    {
        $this->autoRenew = $autoRenew;

        return $this;
    }

    /**
     * Get autoRenew
     *
     * @return boolean 
     */
    public function getAutoRenew()
    {
        return $this->autoRenew;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return MembershipType
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set domain
     *
     * @param \Civi\Core\Domain $domain
     * @return MembershipType
     */
    public function setDomain(\Civi\Core\Domain $domain = null)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return \Civi\Core\Domain 
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set memberOfContact
     *
     * @param \Civi\Contact\Contact $memberOfContact
     * @return MembershipType
     */
    public function setMemberOfContact(\Civi\Contact\Contact $memberOfContact = null)
    {
        $this->memberOfContact = $memberOfContact;

        return $this;
    }

    /**
     * Get memberOfContact
     *
     * @return \Civi\Contact\Contact 
     */
    public function getMemberOfContact()
    {
        return $this->memberOfContact;
    }

    /**
     * Set financialType
     *
     * @param \Civi\Financial\Type $financialType
     * @return MembershipType
     */
    public function setFinancialType(\Civi\Financial\Type $financialType = null)
    {
        $this->financialType = $financialType;

        return $this;
    }

    /**
     * Get financialType
     *
     * @return \Civi\Financial\Type 
     */
    public function getFinancialType()
    {
        return $this->financialType;
    }
}
