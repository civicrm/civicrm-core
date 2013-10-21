<?php

namespace Civi\Contact;

use Doctrine\ORM\Mapping as ORM;

/**
 * RelationshipType
 *
 * @ORM\Table(name="civicrm_relationship_type", uniqueConstraints={@ORM\UniqueConstraint(name="UI_name_a_b", columns={"name_a_b"}), @ORM\UniqueConstraint(name="UI_name_b_a", columns={"name_b_a"})})
 * @ORM\Entity
 */
class RelationshipType extends \Civi\Core\Entity
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
     * @ORM\Column(name="name_a_b", type="string", length=64, nullable=true)
     */
    private $nameAB;

    /**
     * @var string
     *
     * @ORM\Column(name="label_a_b", type="string", length=64, nullable=true)
     */
    private $labelAB;

    /**
     * @var string
     *
     * @ORM\Column(name="name_b_a", type="string", length=64, nullable=true)
     */
    private $nameBA;

    /**
     * @var string
     *
     * @ORM\Column(name="label_b_a", type="string", length=64, nullable=true)
     */
    private $labelBA;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_type_a", type="string", nullable=true)
     */
    private $contactTypeA;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_type_b", type="string", nullable=true)
     */
    private $contactTypeB;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_sub_type_a", type="string", length=64, nullable=true)
     */
    private $contactSubTypeA;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_sub_type_b", type="string", length=64, nullable=true)
     */
    private $contactSubTypeB;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_reserved", type="boolean", nullable=true)
     */
    private $isReserved;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=true)
     */
    private $isActive = '1';



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
     * Set nameAB
     *
     * @param string $nameAB
     * @return RelationshipType
     */
    public function setNameAB($nameAB)
    {
        $this->nameAB = $nameAB;

        return $this;
    }

    /**
     * Get nameAB
     *
     * @return string 
     */
    public function getNameAB()
    {
        return $this->nameAB;
    }

    /**
     * Set labelAB
     *
     * @param string $labelAB
     * @return RelationshipType
     */
    public function setLabelAB($labelAB)
    {
        $this->labelAB = $labelAB;

        return $this;
    }

    /**
     * Get labelAB
     *
     * @return string 
     */
    public function getLabelAB()
    {
        return $this->labelAB;
    }

    /**
     * Set nameBA
     *
     * @param string $nameBA
     * @return RelationshipType
     */
    public function setNameBA($nameBA)
    {
        $this->nameBA = $nameBA;

        return $this;
    }

    /**
     * Get nameBA
     *
     * @return string 
     */
    public function getNameBA()
    {
        return $this->nameBA;
    }

    /**
     * Set labelBA
     *
     * @param string $labelBA
     * @return RelationshipType
     */
    public function setLabelBA($labelBA)
    {
        $this->labelBA = $labelBA;

        return $this;
    }

    /**
     * Get labelBA
     *
     * @return string 
     */
    public function getLabelBA()
    {
        return $this->labelBA;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return RelationshipType
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
     * Set contactTypeA
     *
     * @param string $contactTypeA
     * @return RelationshipType
     */
    public function setContactTypeA($contactTypeA)
    {
        $this->contactTypeA = $contactTypeA;

        return $this;
    }

    /**
     * Get contactTypeA
     *
     * @return string 
     */
    public function getContactTypeA()
    {
        return $this->contactTypeA;
    }

    /**
     * Set contactTypeB
     *
     * @param string $contactTypeB
     * @return RelationshipType
     */
    public function setContactTypeB($contactTypeB)
    {
        $this->contactTypeB = $contactTypeB;

        return $this;
    }

    /**
     * Get contactTypeB
     *
     * @return string 
     */
    public function getContactTypeB()
    {
        return $this->contactTypeB;
    }

    /**
     * Set contactSubTypeA
     *
     * @param string $contactSubTypeA
     * @return RelationshipType
     */
    public function setContactSubTypeA($contactSubTypeA)
    {
        $this->contactSubTypeA = $contactSubTypeA;

        return $this;
    }

    /**
     * Get contactSubTypeA
     *
     * @return string 
     */
    public function getContactSubTypeA()
    {
        return $this->contactSubTypeA;
    }

    /**
     * Set contactSubTypeB
     *
     * @param string $contactSubTypeB
     * @return RelationshipType
     */
    public function setContactSubTypeB($contactSubTypeB)
    {
        $this->contactSubTypeB = $contactSubTypeB;

        return $this;
    }

    /**
     * Get contactSubTypeB
     *
     * @return string 
     */
    public function getContactSubTypeB()
    {
        return $this->contactSubTypeB;
    }

    /**
     * Set isReserved
     *
     * @param boolean $isReserved
     * @return RelationshipType
     */
    public function setIsReserved($isReserved)
    {
        $this->isReserved = $isReserved;

        return $this;
    }

    /**
     * Get isReserved
     *
     * @return boolean 
     */
    public function getIsReserved()
    {
        return $this->isReserved;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return RelationshipType
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
}
