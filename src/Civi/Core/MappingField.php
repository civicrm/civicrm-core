<?php

namespace Civi\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * MappingField
 *
 * @ORM\Table(name="civicrm_mapping_field", indexes={@ORM\Index(name="FK_civicrm_mapping_field_mapping_id", columns={"mapping_id"}), @ORM\Index(name="FK_civicrm_mapping_field_location_type_id", columns={"location_type_id"}), @ORM\Index(name="FK_civicrm_mapping_field_relationship_type_id", columns={"relationship_type_id"})})
 * @ORM\Entity
 */
class MappingField extends \Civi\Core\Entity
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
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_type", type="string", length=64, nullable=true)
     */
    private $contactType;

    /**
     * @var integer
     *
     * @ORM\Column(name="column_number", type="integer", nullable=false)
     */
    private $columnNumber;

    /**
     * @var integer
     *
     * @ORM\Column(name="phone_type_id", type="integer", nullable=true)
     */
    private $phoneTypeId;

    /**
     * @var integer
     *
     * @ORM\Column(name="im_provider_id", type="integer", nullable=true)
     */
    private $imProviderId;

    /**
     * @var integer
     *
     * @ORM\Column(name="website_type_id", type="integer", nullable=true)
     */
    private $websiteTypeId;

    /**
     * @var string
     *
     * @ORM\Column(name="relationship_direction", type="string", length=6, nullable=true)
     */
    private $relationshipDirection;

    /**
     * @var integer
     *
     * @ORM\Column(name="grouping", type="integer", nullable=true)
     */
    private $grouping = '1';

    /**
     * @var string
     *
     * @ORM\Column(name="operator", type="string", nullable=true)
     */
    private $operator;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255, nullable=true)
     */
    private $value;

    /**
     * @var \Civi\Core\Mapping
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\Mapping")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="mapping_id", referencedColumnName="id")
     * })
     */
    private $mapping;

    /**
     * @var \Civi\Core\LocationType
     *
     * @ORM\ManyToOne(targetEntity="Civi\Core\LocationType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="location_type_id", referencedColumnName="id")
     * })
     */
    private $locationType;

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
     * @return MappingField
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
     * Set contactType
     *
     * @param string $contactType
     * @return MappingField
     */
    public function setContactType($contactType)
    {
        $this->contactType = $contactType;

        return $this;
    }

    /**
     * Get contactType
     *
     * @return string 
     */
    public function getContactType()
    {
        return $this->contactType;
    }

    /**
     * Set columnNumber
     *
     * @param integer $columnNumber
     * @return MappingField
     */
    public function setColumnNumber($columnNumber)
    {
        $this->columnNumber = $columnNumber;

        return $this;
    }

    /**
     * Get columnNumber
     *
     * @return integer 
     */
    public function getColumnNumber()
    {
        return $this->columnNumber;
    }

    /**
     * Set phoneTypeId
     *
     * @param integer $phoneTypeId
     * @return MappingField
     */
    public function setPhoneTypeId($phoneTypeId)
    {
        $this->phoneTypeId = $phoneTypeId;

        return $this;
    }

    /**
     * Get phoneTypeId
     *
     * @return integer 
     */
    public function getPhoneTypeId()
    {
        return $this->phoneTypeId;
    }

    /**
     * Set imProviderId
     *
     * @param integer $imProviderId
     * @return MappingField
     */
    public function setImProviderId($imProviderId)
    {
        $this->imProviderId = $imProviderId;

        return $this;
    }

    /**
     * Get imProviderId
     *
     * @return integer 
     */
    public function getImProviderId()
    {
        return $this->imProviderId;
    }

    /**
     * Set websiteTypeId
     *
     * @param integer $websiteTypeId
     * @return MappingField
     */
    public function setWebsiteTypeId($websiteTypeId)
    {
        $this->websiteTypeId = $websiteTypeId;

        return $this;
    }

    /**
     * Get websiteTypeId
     *
     * @return integer 
     */
    public function getWebsiteTypeId()
    {
        return $this->websiteTypeId;
    }

    /**
     * Set relationshipDirection
     *
     * @param string $relationshipDirection
     * @return MappingField
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
     * Set grouping
     *
     * @param integer $grouping
     * @return MappingField
     */
    public function setGrouping($grouping)
    {
        $this->grouping = $grouping;

        return $this;
    }

    /**
     * Get grouping
     *
     * @return integer 
     */
    public function getGrouping()
    {
        return $this->grouping;
    }

    /**
     * Set operator
     *
     * @param string $operator
     * @return MappingField
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Get operator
     *
     * @return string 
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return MappingField
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set mapping
     *
     * @param \Civi\Core\Mapping $mapping
     * @return MappingField
     */
    public function setMapping(\Civi\Core\Mapping $mapping = null)
    {
        $this->mapping = $mapping;

        return $this;
    }

    /**
     * Get mapping
     *
     * @return \Civi\Core\Mapping 
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * Set locationType
     *
     * @param \Civi\Core\LocationType $locationType
     * @return MappingField
     */
    public function setLocationType(\Civi\Core\LocationType $locationType = null)
    {
        $this->locationType = $locationType;

        return $this;
    }

    /**
     * Get locationType
     *
     * @return \Civi\Core\LocationType 
     */
    public function getLocationType()
    {
        return $this->locationType;
    }

    /**
     * Set relationshipType
     *
     * @param \Civi\Contact\RelationshipType $relationshipType
     * @return MappingField
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
}
