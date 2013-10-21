<?php

namespace Civi\Member;

use Doctrine\ORM\Mapping as ORM;

/**
 * MembershipBlock
 *
 * @ORM\Table(name="civicrm_membership_block", indexes={@ORM\Index(name="FK_civicrm_membership_block_entity_id", columns={"entity_id"}), @ORM\Index(name="FK_civicrm_membership_block_membership_type_default", columns={"membership_type_default"})})
 * @ORM\Entity
 */
class MembershipBlock extends \Civi\Core\Entity
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
     * @ORM\Column(name="entity_table", type="string", length=64, nullable=true)
     */
    private $entityTable;

    /**
     * @var string
     *
     * @ORM\Column(name="membership_types", type="string", length=255, nullable=true)
     */
    private $membershipTypes;

    /**
     * @var boolean
     *
     * @ORM\Column(name="display_min_fee", type="boolean", nullable=true)
     */
    private $displayMinFee = '1';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_separate_payment", type="boolean", nullable=true)
     */
    private $isSeparatePayment = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="new_title", type="string", length=255, nullable=true)
     */
    private $newTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="new_text", type="text", nullable=true)
     */
    private $newText;

    /**
     * @var string
     *
     * @ORM\Column(name="renewal_title", type="string", length=255, nullable=true)
     */
    private $renewalTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="renewal_text", type="text", nullable=true)
     */
    private $renewalText;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_required", type="boolean", nullable=true)
     */
    private $isRequired = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';

    /**
     * @var \Civi\Contribute\ContributionPage
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contribute\ContributionPage")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="entity_id", referencedColumnName="id")
     * })
     */
    private $entity;

    /**
     * @var \Civi\Member\MembershipType
     *
     * @ORM\ManyToOne(targetEntity="Civi\Member\MembershipType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="membership_type_default", referencedColumnName="id")
     * })
     */
    private $membershipTypeDefault;



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
     * Set entityTable
     *
     * @param string $entityTable
     * @return MembershipBlock
     */
    public function setEntityTable($entityTable)
    {
        $this->entityTable = $entityTable;

        return $this;
    }

    /**
     * Get entityTable
     *
     * @return string 
     */
    public function getEntityTable()
    {
        return $this->entityTable;
    }

    /**
     * Set membershipTypes
     *
     * @param string $membershipTypes
     * @return MembershipBlock
     */
    public function setMembershipTypes($membershipTypes)
    {
        $this->membershipTypes = $membershipTypes;

        return $this;
    }

    /**
     * Get membershipTypes
     *
     * @return string 
     */
    public function getMembershipTypes()
    {
        return $this->membershipTypes;
    }

    /**
     * Set displayMinFee
     *
     * @param boolean $displayMinFee
     * @return MembershipBlock
     */
    public function setDisplayMinFee($displayMinFee)
    {
        $this->displayMinFee = $displayMinFee;

        return $this;
    }

    /**
     * Get displayMinFee
     *
     * @return boolean 
     */
    public function getDisplayMinFee()
    {
        return $this->displayMinFee;
    }

    /**
     * Set isSeparatePayment
     *
     * @param boolean $isSeparatePayment
     * @return MembershipBlock
     */
    public function setIsSeparatePayment($isSeparatePayment)
    {
        $this->isSeparatePayment = $isSeparatePayment;

        return $this;
    }

    /**
     * Get isSeparatePayment
     *
     * @return boolean 
     */
    public function getIsSeparatePayment()
    {
        return $this->isSeparatePayment;
    }

    /**
     * Set newTitle
     *
     * @param string $newTitle
     * @return MembershipBlock
     */
    public function setNewTitle($newTitle)
    {
        $this->newTitle = $newTitle;

        return $this;
    }

    /**
     * Get newTitle
     *
     * @return string 
     */
    public function getNewTitle()
    {
        return $this->newTitle;
    }

    /**
     * Set newText
     *
     * @param string $newText
     * @return MembershipBlock
     */
    public function setNewText($newText)
    {
        $this->newText = $newText;

        return $this;
    }

    /**
     * Get newText
     *
     * @return string 
     */
    public function getNewText()
    {
        return $this->newText;
    }

    /**
     * Set renewalTitle
     *
     * @param string $renewalTitle
     * @return MembershipBlock
     */
    public function setRenewalTitle($renewalTitle)
    {
        $this->renewalTitle = $renewalTitle;

        return $this;
    }

    /**
     * Get renewalTitle
     *
     * @return string 
     */
    public function getRenewalTitle()
    {
        return $this->renewalTitle;
    }

    /**
     * Set renewalText
     *
     * @param string $renewalText
     * @return MembershipBlock
     */
    public function setRenewalText($renewalText)
    {
        $this->renewalText = $renewalText;

        return $this;
    }

    /**
     * Get renewalText
     *
     * @return string 
     */
    public function getRenewalText()
    {
        return $this->renewalText;
    }

    /**
     * Set isRequired
     *
     * @param boolean $isRequired
     * @return MembershipBlock
     */
    public function setIsRequired($isRequired)
    {
        $this->isRequired = $isRequired;

        return $this;
    }

    /**
     * Get isRequired
     *
     * @return boolean 
     */
    public function getIsRequired()
    {
        return $this->isRequired;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return MembershipBlock
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
     * Set entity
     *
     * @param \Civi\Contribute\ContributionPage $entity
     * @return MembershipBlock
     */
    public function setEntity(\Civi\Contribute\ContributionPage $entity = null)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity
     *
     * @return \Civi\Contribute\ContributionPage 
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set membershipTypeDefault
     *
     * @param \Civi\Member\MembershipType $membershipTypeDefault
     * @return MembershipBlock
     */
    public function setMembershipTypeDefault(\Civi\Member\MembershipType $membershipTypeDefault = null)
    {
        $this->membershipTypeDefault = $membershipTypeDefault;

        return $this;
    }

    /**
     * Get membershipTypeDefault
     *
     * @return \Civi\Member\MembershipType 
     */
    public function getMembershipTypeDefault()
    {
        return $this->membershipTypeDefault;
    }
}
