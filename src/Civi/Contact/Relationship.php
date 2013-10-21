<?php

namespace Civi\Contact;

use Doctrine\ORM\Mapping as ORM;

/**
 * Relationship
 *
 * @ORM\Table(name="civicrm_relationship", indexes={@ORM\Index(name="FK_civicrm_relationship_contact_id_a", columns={"contact_id_a"}), @ORM\Index(name="FK_civicrm_relationship_contact_id_b", columns={"contact_id_b"}), @ORM\Index(name="FK_civicrm_relationship_relationship_type_id", columns={"relationship_type_id"}), @ORM\Index(name="FK_civicrm_relationship_case_id", columns={"case_id"})})
 * @ORM\Entity
 */
class Relationship extends \Civi\Core\Entity
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
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_permission_a_b", type="boolean", nullable=true)
     */
    private $isPermissionAB = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_permission_b_a", type="boolean", nullable=true)
     */
    private $isPermissionBA = '0';

    /**
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contact_id_a", referencedColumnName="id")
     * })
     */
    private $contactA;

    /**
     * @var \Civi\Contact\Contact
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\Contact")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contact_id_b", referencedColumnName="id")
     * })
     */
    private $contactB;

    /**
     * @var \Civi\Contact\RelationshipType
     *
     * @ORM\ManyToOne(targetEntity="Civi\Contact\RelationshipType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="relationship_type_id", referencedColumnName="id")
     * })
     */
    private $relationshipType;

    /**
     * @var \Civi\CCase\CCase
     *
     * @ORM\ManyToOne(targetEntity="Civi\CCase\CCase")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="case_id", referencedColumnName="id")
     * })
     */
    private $case;



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
     * @return Relationship
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
     * @return Relationship
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
     * Set isActive
     *
     * @param boolean $isActive
     * @return Relationship
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
     * Set description
     *
     * @param string $description
     * @return Relationship
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
     * Set isPermissionAB
     *
     * @param boolean $isPermissionAB
     * @return Relationship
     */
    public function setIsPermissionAB($isPermissionAB)
    {
        $this->isPermissionAB = $isPermissionAB;

        return $this;
    }

    /**
     * Get isPermissionAB
     *
     * @return boolean 
     */
    public function getIsPermissionAB()
    {
        return $this->isPermissionAB;
    }

    /**
     * Set isPermissionBA
     *
     * @param boolean $isPermissionBA
     * @return Relationship
     */
    public function setIsPermissionBA($isPermissionBA)
    {
        $this->isPermissionBA = $isPermissionBA;

        return $this;
    }

    /**
     * Get isPermissionBA
     *
     * @return boolean 
     */
    public function getIsPermissionBA()
    {
        return $this->isPermissionBA;
    }

    /**
     * Set contactA
     *
     * @param \Civi\Contact\Contact $contactA
     * @return Relationship
     */
    public function setContactA(\Civi\Contact\Contact $contactA = null)
    {
        $this->contactA = $contactA;

        return $this;
    }

    /**
     * Get contactA
     *
     * @return \Civi\Contact\Contact 
     */
    public function getContactA()
    {
        return $this->contactA;
    }

    /**
     * Set contactB
     *
     * @param \Civi\Contact\Contact $contactB
     * @return Relationship
     */
    public function setContactB(\Civi\Contact\Contact $contactB = null)
    {
        $this->contactB = $contactB;

        return $this;
    }

    /**
     * Get contactB
     *
     * @return \Civi\Contact\Contact 
     */
    public function getContactB()
    {
        return $this->contactB;
    }

    /**
     * Set relationshipType
     *
     * @param \Civi\Contact\RelationshipType $relationshipType
     * @return Relationship
     */
    public function setRelationshipType(\Civi\Contact\RelationshipType $relationshipType = null)
    {
        $this->relationshipType = $relationshipType;

        return $this;
    }

    /**
     * Get relationshipType
     *
     * @return \Civi\Contact\RelationshipType 
     */
    public function getRelationshipType()
    {
        return $this->relationshipType;
    }

    /**
     * Set case
     *
     * @param \Civi\CCase\CCase $case
     * @return Relationship
     */
    public function setCase(\Civi\CCase\CCase $case = null)
    {
        $this->case = $case;

        return $this;
    }

    /**
     * Get case
     *
     * @return \Civi\CCase\CCase 
     */
    public function getCase()
    {
        return $this->case;
    }
}
